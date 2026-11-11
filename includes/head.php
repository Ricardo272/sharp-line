<?php
/**
 * Début de chaque page : <head> et ouverture du <body>.
 *
 * Variables que la page peut définir AVANT d'inclure ce fichier :
 *   $pageId          identifiant de la page (« accueil », « galerie »…) pour le menu
 *   $pageTitle       titre de la page (le nom du salon est ajouté automatiquement)
 *   $pageDescription description pour les moteurs de recherche
 *   $pageNoIndex     true pour demander aux moteurs de ne pas indexer la page
 *   $pageSalon       true pour ajouter la fiche « HairSalon » pour Google (accueil)
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/donnees/horaires.php';

// Lus ici, AVANT tout affichage : si la base ne répond pas, la page 503
// peut encore s'afficher proprement (voir helpers.php). Gardés en mémoire
// pour le pied de page et l'appel au rendez-vous.
horaires();

$pageId          = $pageId ?? '';
$pageDescription = $pageDescription ?? $site['description'];

// Accueil : « Sharp Line — Barber shop à Belleville »
// Autres pages : « Galerie — Sharp Line »
$titreComplet = isset($pageTitle)
    ? $pageTitle . ' — ' . $site['nom']
    : $site['nom'] . ' — Barber shop à Belleville, Paris 20e';

// Adresse officielle de la page (sans paramètres) : « /index.php » devient « / »
$fichierPage  = basename($_SERVER['SCRIPT_NAME']);
$urlCanonique = urlAbsolue($fichierPage === 'index.php' ? '' : $fichierPage);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titreComplet) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#1E1F24">
<?php if (!empty($pageNoIndex)): ?>
    <meta name="robots" content="noindex, nofollow">
<?php else: ?>
    <!-- Adresse officielle de la page, pour éviter les doublons dans Google -->
    <link rel="canonical" href="<?= e($urlCanonique) ?>">
<?php endif; ?>
    <link rel="icon" href="<?= e(url('favicon.svg')) ?>" type="image/svg+xml">

    <!-- Open Graph : aperçu quand le lien est partagé (WhatsApp, LinkedIn…) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="<?= e($site['nom']) ?>">
    <meta property="og:title" content="<?= e($titreComplet) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($urlCanonique) ?>">
    <!-- shortcut: partage.jpg n'existe pas encore, une photo recadrée en 1200 × 630 arrive en phase 8 -->
    <meta property="og:image" content="<?= e(urlAbsolue('assets/img/partage.jpg')) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= e($site['nom'] . ' — ' . $site['slogan']) ?>">
    <meta name="twitter:card" content="summary_large_image">
<?php if (!empty($pageSalon)): ?>

    <!-- Fiche « salon de coiffure » pour Google (données structurées schema.org) -->
    <script type="application/ld+json">
<?= donneesStructurees($site, horaires()) ?>

    </script>
<?php endif; ?>

    <!-- Signale au CSS que JavaScript fonctionne. Sans cette classe, le menu
         reste affiché en liste : le site marche aussi sans JavaScript. -->
    <script>document.documentElement.classList.add('js');</script>

    <!-- Polices visibles dès le premier écran : chargées en priorité -->
    <link rel="preload" href="<?= e(url('assets/fonts/bebas-neue-latin-400-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(url('assets/fonts/space-grotesk-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>

    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
</head>
<body>
