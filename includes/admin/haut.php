<?php
/**
 * Haut des pages d'administration.
 *
 * Variables attendues :
 *   $titreAdmin   titre de la page
 *   $pageAdmin    identifiant de la page pour le menu (« agenda », « prestations »…)
 *   $admin        administrateur connecté, ou null (page de connexion)
 */
$liensAdmin = [
    'agenda'      => ['Agenda',      'admin/'],
    'prestations' => ['Prestations', 'admin/prestations.php'],
    'fermetures'  => ['Fermetures',  'admin/fermetures.php'],
];
$flash = lireFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1E1F24">
    <title><?= e($titreAdmin) ?> — Administration — <?= e($site['nom']) ?></title>
    <link rel="icon" href="<?= e(url('favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin">
<a class="lien-evitement" href="#contenu">Aller au contenu</a>

<?php if ($admin): ?>
<header class="admin-entete">
    <div class="conteneur admin-entete__interieur">
        <p class="admin-entete__nom"><?= e($site['nom']) ?> <span>admin</span></p>

        <nav class="admin-entete__nav" aria-label="Administration">
            <ul class="admin-nav" role="list">
<?php foreach ($liensAdmin as $id => [$libelle, $chemin]): ?>
                <li><a href="<?= e(url($chemin)) ?>"<?= $id === $pageAdmin ? ' aria-current="page"' : '' ?>><?= e($libelle) ?></a></li>
<?php endforeach; ?>
                <li><a href="<?= e(url()) ?>">Voir le site</a></li>
            </ul>
        </nav>

        <!-- Déconnexion en POST avec jeton CSRF : un simple lien (GET) pourrait
             être déclenché par une image piégée sur un autre site -->
        <form class="admin-entete__deconnexion" method="post" action="<?= e(url('admin/deconnexion.php')) ?>">
            <?= champCsrf() ?>
            <button type="submit">Déconnexion</button>
        </form>
    </div>
</header>
<?php endif; ?>

<main id="contenu" class="conteneur admin-page">
<?php if ($flash): ?>
    <p class="flash flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
<?php endif; ?>
