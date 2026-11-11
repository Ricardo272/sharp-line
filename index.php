<?php
// Accueil. Squelette de la phase 1 : sert à tester le gabarit et les helpers.
// Le vrai contenu (bannière néon, aperçu prestations et galerie) arrive en phase 3.
$pageId = 'accueil';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="contenu">
    <section class="section">
        <!-- Deux colonnes en bureau (texte | image), empilées en mobile -->
        <div class="conteneur intro">
            <div class="intro__texte">
                <h1><?= e($site['nom']) ?></h1>
                <p class="intro__slogan"><?= e($site['slogan']) ?></p>
                <ul class="intro__atouts" role="list">
                    <li>Dégradés et contours au rasoir</li>
                    <li>Cheveux bouclés et crépus</li>
                    <li>Coupes enfants et ados</li>
                </ul>
                <p><em>Page en construction (phase 1 : fondations).</em></p>
            </div>

            <!-- Le fichier n'existe pas encore : image() affiche une image de remplacement -->
            <?= image('accueil/intro.jpg', 'Dégradé net vu de dos', 720, 900, 'intro__image', true) ?>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
