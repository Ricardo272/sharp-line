<?php
/**
 * Tests de sécurité du site, vu de l'extérieur (requêtes HTTP).
 * Interroge l'adresse de config.local.php : à lancer en local, puis sur le serveur.
 *
 *   php tests/securite.php
 *
 * Code de sortie 1 si un test échoue. Ne crée aucune donnée.
 */

if (PHP_SAPI !== 'cli') {
    exit('À lancer en ligne de commande.');
}

require_once __DIR__ . '/../includes/helpers.php';

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
function requete(string $adresse, ?array $post = null): array
{
    $options = ['ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10];
    if ($post !== null) {
        $options += [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($post),
        ];
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

if (!str_ends_with(parse_url($base, PHP_URL_HOST), '.test')) {
    echo "En ligne\n";
    $http = requete('http://' . parse_url($base, PHP_URL_HOST) . '/');
    verifier('http redirigé vers https', $http['code'] === 301 && str_starts_with($http['entetes']['location'] ?? '', 'https://'));
    verifier('aucune erreur PHP visible', !preg_match('/(Warning|Notice|Fatal error|Deprecated)/', $accueil['corps']));
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
