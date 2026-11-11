<?php
/**
 * Page « Service momentanément indisponible », affichée quand la base ne
 * répond pas. Volontairement autonome : elle n'utilise ni l'en-tête ni le
 * pied de page communs, qui pourraient eux-mêmes avoir besoin de la base.
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Service momentanément indisponible — <?= e($site['nom']) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
</head>
<body>
<main id="contenu" class="erreur bloc-sombre">
    <section class="section">
        <div class="conteneur erreur__contenu">
            <p class="erreur__code" aria-hidden="true">503</p>
            <h1>Petite coupure de courant.</h1>
            <p>Le site est momentanément indisponible. Réessaie dans quelques minutes,
               ou appelle-nous au <a href="tel:<?= e(lienTelephone($site['telephone'])) ?>"><?= e($site['telephone']) ?></a>.</p>
        </div>
    </section>
</main>
</body>
</html>
