<?php
/**
 * Tests de sécurité du site, vu de l'extérieur (requêtes HTTP).
 * Interroge l'adresse de config.local.php : à lancer en local, puis sur le serveur.
 *
 *   php tests/securite.php
 *
 * Code de sortie 1 si un test échoue. Crée un compte d'administration
 * jetable (« test-securite ») pour tester la connexion, puis le supprime.
 */

if (PHP_SAPI !== 'cli') {
    exit('À lancer en ligne de commande.');
}

require_once __DIR__ . '/../includes/db.php';

$base = rtrim(configLocale()['url_absolue'], '/');
$echecs = 0;

function verifier(string $nom, bool $condition): void
{
    global $echecs;
    echo ($condition ? '  ok      ' : '  ÉCHEC   ') . $nom . "\n";
    $echecs += $condition ? 0 : 1;
}

/**
 * Requête HTTP sans suivre les redirections.
 * Retourne ['code' => 200, 'entetes' => ['content-type' => '…'], 'corps' => '…'].
 */
function requete(string $adresse, ?array $post = null, string $cookie = ''): array
{
    $options = ['ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10, 'header' => ''];
    if ($cookie !== '') {
        $options['header'] .= "Cookie: $cookie\r\n";
    }
    if ($post !== null) {
        $options['method']  = 'POST';
        $options['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $options['content'] = http_build_query($post);
    }
    $corps = @file_get_contents($adresse, false, stream_context_create(['http' => $options]));
    $lignes = $http_response_header ?? [];

    preg_match('{HTTP/\S+ (\d{3})}', $lignes[0] ?? '', $m);
    $entetes = [];
    foreach (array_slice($lignes, 1) as $ligne) {
        [$nom, $valeur] = array_map('trim', explode(':', $ligne, 2) + [1 => '']);
        $entetes[strtolower($nom)] = $valeur;
    }
    return ['code' => (int) ($m[1] ?? 0), 'entetes' => $entetes, 'corps' => (string) $corps];
}

echo "Site testé : $base\n";

echo "Pages publiques : réponse 200, aucun cookie\n";
foreach (['/', '/prestations.php', '/equipe.php', '/galerie.php', '/rendez-vous.php', '/contact.php', '/mentions-legales.php'] as $page) {
    $r = requete($base . $page);
    verifier("$page : 200 et aucun cookie", $r['code'] === 200 && !isset($r['entetes']['set-cookie']));
}

echo "En-têtes de sécurité\n";
$accueil = requete($base . '/');
verifier('CSP présente', str_contains($accueil['entetes']['content-security-policy'] ?? '', "default-src 'self'"));
verifier('nosniff', ($accueil['entetes']['x-content-type-options'] ?? '') === 'nosniff');
verifier('X-Frame-Options', isset($accueil['entetes']['x-frame-options']));
verifier('Referrer-Policy', isset($accueil['entetes']['referrer-policy']));
verifier('fiche HairSalon sur l\'accueil', str_contains($accueil['corps'], '"@type": "HairSalon"'));

echo "Dossiers internes : 404\n";
foreach (['/config/config.local.php', '/config/site.php', '/includes/db.php', '/sql/schema.sql', '/tests/securite.php', '/outils/'] as $chemin) {
    $r = requete($base . $chemin);
    verifier("$chemin : 404", $r['code'] === 404 && !str_contains($r['corps'], 'mot_de_passe'));
}
verifier('adresse inconnue : 404 (pas 200)', requete($base . '/nexiste-pas')['code'] === 404);
verifier('pas de liste de fichiers dans /assets/', requete($base . '/assets/')['code'] !== 200);

echo "Formulaire de rendez-vous\n";
$robot = requete($base . '/rendez-vous.php', [
    'prestation' => '1', 'barber' => '0', 'jour' => date('Y-m-d'), 'heure' => '10:00',
    'prenom' => 'Robot', 'telephone' => '0199000099', 'site_web' => 'http://spam.test', 'jeton' => '1.x',
]);
verifier('choix invalides : aucune redirection vers une confirmation',
    !str_contains($robot['entetes']['location'] ?? '', 'confirmation='));
verifier('lien de confirmation trafiqué : refusé',
    str_contains(requete($base . '/rendez-vous.php?confirmation=1.abc')['corps'], 'Lien introuvable'));

echo "Tableau de bord\n";
verifier('/admin/ sans connexion : renvoie vers la connexion',
    str_contains(requete($base . '/admin/')['entetes']['location'] ?? '', 'admin/connexion.php'));

/** Ouvre la page de connexion : retourne [cookie de session, jeton CSRF]. */
function pageConnexion(string $base): array
{
    $r = requete($base . '/admin/connexion.php');
    preg_match('/name="csrf" value="([0-9a-f]+)"/', $r['corps'], $m);
    return [explode(';', $r['entetes']['set-cookie'] ?? '')[0], $m[1] ?? '', $r['entetes']['set-cookie'] ?? ''];
}

[$cookie, $csrf, $setCookie] = pageConnexion($base);
verifier('cookie limité à /admin/, HttpOnly, SameSite=Strict',
    stripos($setCookie, 'path=/admin/') !== false && stripos($setCookie, 'httponly') !== false
    && stripos($setCookie, 'samesite=strict') !== false);
verifier('connexion sans jeton CSRF : refusée (400)',
    requete($base . '/admin/connexion.php', ['identifiant' => 'x', 'mot_de_passe' => 'y'], $cookie)['code'] === 400);

// Compte jetable, mot de passe aléatoire jamais affiché
$motDePasse = bin2hex(random_bytes(12));
db()->prepare('DELETE FROM admins WHERE identifiant = ?')->execute(['test-securite']);
db()->prepare('INSERT INTO admins (identifiant, mot_de_passe) VALUES (?, ?)')
    ->execute(['test-securite', password_hash($motDePasse, PASSWORD_DEFAULT)]);

try {
    $connexion = requete($base . '/admin/connexion.php',
        ['csrf' => $csrf, 'identifiant' => 'test-securite', 'mot_de_passe' => $motDePasse], $cookie);
    $nouveau = explode(';', $connexion['entetes']['set-cookie'] ?? '')[0];
    verifier('bonne connexion : redirection vers l\'agenda', str_ends_with($connexion['entetes']['location'] ?? '', '/admin/'));
    verifier('nouvel identifiant de session après connexion', $nouveau !== '' && $nouveau !== $cookie);
    verifier('agenda accessible une fois connecté', str_contains(requete($base . '/admin/', null, $nouveau)['corps'], 'Agenda'));
    verifier('annulation sans jeton CSRF : refusée (400)',
        requete($base . '/admin/', ['annuler' => '1'], $nouveau)['code'] === 400);

    // 5 mauvais mots de passe : compte bloqué, même avec le bon ensuite
    $resultat = '';
    for ($i = 1; $i <= 5; $i++) {
        [$c, $j] = pageConnexion($base);
        $resultat = requete($base . '/admin/connexion.php',
            ['csrf' => $j, 'identifiant' => 'test-securite', 'mot_de_passe' => 'faux-' . $i], $c)['corps'];
    }
    verifier('5 échecs : compte bloqué', str_contains($resultat, 'Trop de tentatives'));
    [$c, $j] = pageConnexion($base);
    verifier('bloqué : même le bon mot de passe est refusé', str_contains(requete($base . '/admin/connexion.php',
        ['csrf' => $j, 'identifiant' => 'test-securite', 'mot_de_passe' => $motDePasse], $c)['corps'], 'Trop de tentatives'));
    [$c, $j] = pageConnexion($base);
    verifier('identifiant inconnu : même message qu\'un mauvais mot de passe', str_contains(requete($base . '/admin/connexion.php',
        ['csrf' => $j, 'identifiant' => 'inconnu-' . bin2hex(random_bytes(4)), 'mot_de_passe' => 'x'], $c)['corps'], 'Identifiant ou mot de passe incorrect'));
} finally {
    db()->prepare('DELETE FROM admins WHERE identifiant = ?')->execute(['test-securite']);
}
verifier('compte supprimé : sa session ouverte ne donne plus accès',
    str_contains(requete($base . '/admin/', null, $nouveau ?? '')['entetes']['location'] ?? '', 'admin/connexion.php'));

if (!str_ends_with(parse_url($base, PHP_URL_HOST), '.test')) {
    echo "En ligne\n";
    $http = requete('http://' . parse_url($base, PHP_URL_HOST) . '/');
    verifier('http redirigé vers https', $http['code'] === 301 && str_starts_with($http['entetes']['location'] ?? '', 'https://'));
    verifier('aucune erreur PHP visible', !preg_match('/(Warning|Notice|Fatal error|Deprecated)/', $accueil['corps']));
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
