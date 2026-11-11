<?php
/**
 * Début de chaque page : <head> et ouverture du <body>.
 *
 * Variables que la page peut définir AVANT d'inclure ce fichier :
 *   $pageId          identifiant de la page (« accueil », « galerie »…) pour le menu
 *   $pageTitle       titre de la page (le nom du salon est ajouté automatiquement)
 *   $pageDescription description pour les moteurs de recherche
 *   $pageNoIndex     true pour demander aux moteurs de ne pas indexer la page
 */
require_once __DIR__ . '/helpers.php';

$pageId          = $pageId ?? '';
$pageDescription = $pageDescription ?? $site['description'];

// Accueil : « Sharp Line — Barber shop à Belleville »
// Autres pages : « Galerie — Sharp Line »
$titreComplet = isset($pageTitle)
    ? $pageTitle . ' — ' . $site['nom']
    : $site['nom'] . ' — Barber shop à Belleville, Paris 20e';
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
