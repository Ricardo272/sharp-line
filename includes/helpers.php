<?php
/**
 * Chargé en premier par chaque page (via head.php).
 * Charge les informations du site et définit les petites fonctions communes.
 */

// Toutes les heures du site (horaires, rendez-vous) sont celles de Paris.
date_default_timezone_set('Europe/Paris');

// En ligne, une erreur PHP n'est jamais affichée au visiteur (elle reste dans le
// journal) : certains hébergeurs l'affichent par défaut. En local (.test), on la voit.
if (PHP_SAPI !== 'cli' && !str_ends_with($_SERVER['HTTP_HOST'] ?? '', '.test')) {
    ini_set('display_errors', '0');
}

$site = require __DIR__ . '/../config/site.php';

/**
 * Toute erreur non prévue (base injoignable, requête en échec…) arrive ici.
 * On écrit le message dans le journal, jamais à l'écran : il peut contenir
 * des détails techniques. On ne journalise pas la trace complète, qui peut
 * contenir les arguments des fonctions (et donc le mot de passe de la base).
 */
set_exception_handler(function (Throwable $erreur) use ($site): void {
    error_log('[Sharp Line] ' . get_class($erreur) . ' : ' . $erreur->getMessage());

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Erreur : ' . $erreur->getMessage() . PHP_EOL);
        exit(1);
    }

    // On jette le début de page déjà produit mais pas encore envoyé (tampon de sortie)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Rien d'envoyé au navigateur : vraie page 503. Sinon, un simple message.
    if (!headers_sent()) {
        http_response_code(503);
        header('Retry-After: 300');
        require __DIR__ . '/erreur-503.php';
    } else {
        echo '<p class="bandeau-demo">Une erreur est survenue, réessaie dans quelques minutes.</p>';
    }
});

/**
 * Configuration locale (config/config.local.php) : adresse du site,
 * identifiants de la base, clé secrète. Lue une seule fois, puis gardée en mémoire.
 */
function configLocale(): array
{
    static $config = null;

    if ($config === null) {
        $fichier = __DIR__ . '/../config/config.local.php';
        if (!is_file($fichier)) {
            throw new RuntimeException('config/config.local.php introuvable : copier config.example.php.');
        }
        $config = require $fichier;
    }

    return $config;
}

/**
 * Échappe un texte avant de l'afficher dans le HTML.
 * Empêche qu'un texte contenant des balises soit interprété par le navigateur.
 */
function e(?string $texte): string
{
    return htmlspecialchars($texte ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Construit une adresse à partir de la racine du site.
 * url('galerie.php') → « /galerie.php » ; url() → « / » (accueil).
 * Fonctionne aussi depuis admin/, contrairement à un lien relatif.
 */
function url(string $chemin = ''): string
{
    global $site;
    return rtrim($site['base_url'], '/') . '/' . ltrim($chemin, '/');
}

/**
 * Adresse complète : urlAbsolue('galerie.php') → « http://sharp-line.test/galerie.php ».
 * Indispensable quand l'adresse est lue hors du site (Google, réseaux sociaux).
 */
function urlAbsolue(string $chemin = ''): string
{
    return rtrim(configLocale()['url_absolue'], '/') . url($chemin);
}

/**
 * Adresse d'un fichier CSS, JS ou image, suivie de sa date de modification :
 * asset('assets/js/menu.js') → « /assets/js/menu.js?v=1762712345 ».
 * Quand le fichier change, l'adresse change : le navigateur ne peut plus
 * se servir d'une ancienne version gardée en cache.
 */
function asset(string $chemin): string
{
    $fichier = __DIR__ . '/../' . $chemin;
    $version = is_file($fichier) ? filemtime($fichier) : 0;
    return url($chemin) . '?v=' . $version;
}

/**
 * Transforme « 01 99 00 42 17 » en « +33199004217 » pour un lien tel:.
 */
function lienTelephone(string $numero): string
{
    $chiffres = preg_replace('/\D/', '', $numero);
    return '+33' . substr($chiffres, 1);
}

/**
 * Affiche une image du dossier assets/img/.
 *
 * Si le fichier n'existe pas encore, affiche à la place une image de remplacement
 * aux mêmes proportions, avec le chemin attendu écrit dessus.
 * Le jour où la vraie photo arrive, il suffit de la déposer au bon endroit.
 *
 * @param string $chemin       chemin dans assets/img/, ex. « galerie/degrade.jpg »
 * @param string $alt          description de l'image (lue par les lecteurs d'écran)
 * @param int    $largeur      largeur réelle de l'image, en pixels
 * @param int    $hauteur      hauteur réelle de l'image, en pixels
 * @param string $classe       classe CSS éventuelle
 * @param bool   $prioritaire  true pour une image visible dès l'ouverture de la page
 */
function image(string $chemin, string $alt, int $largeur, int $hauteur, string $classe = '', bool $prioritaire = false): string
{
    // width et height : le navigateur réserve la place avant le chargement,
    // la page ne « saute » pas.
    $attributs = sprintf('alt="%s" width="%d" height="%d"', e($alt), $largeur, $hauteur);
    $attributs .= $prioritaire ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"';

    if (is_file(__DIR__ . '/../assets/img/' . $chemin)) {
        $src = asset('assets/img/' . $chemin);
    } else {
        $src = imageRemplacement($chemin, $alt, $largeur, $hauteur);
        $classe = trim($classe . ' image-remplacement');
    }

    if ($classe !== '') {
        $attributs .= ' class="' . e($classe) . '"';
    }

    return '<img src="' . e($src) . '" ' . $attributs . '>';
}

/**
 * Fabrique une image SVG de remplacement (fond anthracite rayé + étiquette)
 * et la renvoie sous forme d'adresse « data: » : aucun fichier n'est créé.
 */
function imageRemplacement(string $chemin, string $alt, int $largeur, int $hauteur): string
{
    $x = fn (string $t) => htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $taille = max(14, (int) round(min($largeur, $hauteur) / 18));
    $description = mb_strlen($alt) > 55 ? mb_substr($alt, 0, 52) . '…' : $alt;
    $milieu = $hauteur / 2;

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $largeur . ' ' . $hauteur . '">'
         . '<defs><pattern id="r" width="16" height="16" patternUnits="userSpaceOnUse" patternTransform="rotate(-45)">'
         . '<line x1="0" y1="0" x2="0" y2="16" stroke="#2D5BFF" stroke-opacity=".25" stroke-width="2"/></pattern></defs>'
         . '<rect width="100%" height="100%" fill="#2A2B31"/>'
         . '<rect width="100%" height="100%" fill="url(#r)"/>'
         . '<rect x="6" y="6" width="' . ($largeur - 12) . '" height="' . ($hauteur - 12) . '" fill="none" stroke="#8A3FFC" stroke-width="2" stroke-dasharray="10 8"/>'
         . '<g font-family="Segoe UI, Arial, sans-serif" text-anchor="middle" fill="#E6E4E0">'
         . '<text x="50%" y="' . ($milieu - $taille * 1.4) . '" font-size="' . $taille . '" font-weight="700">IMAGE DE REMPLACEMENT</text>'
         . '<text x="50%" y="' . $milieu . '" font-size="' . round($taille * 0.8) . '">' . $x($chemin) . ' (' . $largeur . ' × ' . $hauteur . ')</text>'
         . '<text x="50%" y="' . ($milieu + $taille * 1.3) . '" font-size="' . round($taille * 0.7) . '" fill-opacity=".7">' . $x($description) . '</text>'
         . '</g></svg>';

    return 'data:image/svg+xml,' . rawurlencode($svg);
}

/**
 * Prix à la française : 20 → « 20 € » ; 12.5 → « 12,50 € ».
 * L'espace avant « € » est insécable : le symbole ne part jamais seul à la ligne.
 */
function prix(float $montant): string
{
    $decimales = floor($montant) == $montant ? 0 : 2;
    return number_format($montant, $decimales, ',', "\u{202F}") . "\u{00A0}€";
}

/**
 * « 14:30 » → « 14h30 » ; « 10:00 » → « 10h ».
 */
function formaterHeure(string $heure): string
{
    [$h, $m] = explode(':', $heure);
    return (int) $h . 'h' . ($m === '00' ? '' : $m);
}

/**
 * Regroupe les jours consécutifs qui ont les mêmes horaires.
 *
 * Retourne par exemple :
 *   'ouvertures' => [['jours' => 'Du mardi au vendredi', 'creneaux' => ['10h – 20h']], …]
 *   'fermeture'  => 'Fermé le lundi et le dimanche'
 */
function resumerHoraires(array $horaires): array
{
    $noms = [1 => 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
    $ouvertures = [];
    $fermes = [];
    $debut = null; // premier jour du groupe en cours

    // On va jusqu'à 8 (jour qui n'existe pas) pour clore le dernier groupe.
    for ($jour = 1; $jour <= 8; $jour++) {
        $creneaux = $horaires[$jour] ?? null;

        // Le groupe en cours s'arrête si les horaires changent.
        if ($debut !== null && $creneaux !== $horaires[$debut]) {
            $fin = $jour - 1;
            $ouvertures[] = [
                'jours'    => $debut === $fin
                    ? ucfirst($noms[$debut])
                    : 'Du ' . $noms[$debut] . ' au ' . $noms[$fin],
                'creneaux' => array_map(
                    fn ($c) => formaterHeure($c[0]) . ' – ' . formaterHeure($c[1]),
                    $horaires[$debut]
                ),
            ];
            $debut = null;
        }

        if ($creneaux === []) {
            $fermes[] = $noms[$jour];
        } elseif ($creneaux !== null && $debut === null) {
            $debut = $jour;
        }
    }

    $fermeture = '';
    if ($fermes) {
        $dernier = array_pop($fermes);
        $fermeture = 'Fermé le ' . ($fermes ? implode(', le ', $fermes) . ' et le ' : '') . $dernier;
    }

    return ['ouvertures' => $ouvertures, 'fermeture' => $fermeture];
}
