<?php
// Accueil. Squelette de la phase 1 : sert à tester le gabarit et les helpers.
// Le vrai contenu (bannière néon, aperçu prestations et galerie) arrive en phase 3.
$pageId = 'accueil';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="contenu">
    <section class="section">
        <div class="conteneur">
            <h1><?= e($site['nom']) ?></h1>
            <p><?= e($site['slogan']) ?></p>
            <p><em>Page en construction (phase 1 : fondations).</em></p>

            <!-- Test de image() : le fichier n'existe pas, une image de remplacement s'affiche -->
            <?= image('galerie/exemple.jpg', 'Dégradé net vu de dos', 960, 640) ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
