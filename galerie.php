<?php
// Page « Galerie » : 12 photos, agrandissement au clic (avec JavaScript).
$pageId          = 'galerie';
$pageTitle       = 'Galerie';
$pageDescription = 'Dégradés, contours au rasoir, boucles et motifs : les coupes et le salon Sharp Line en photos.';
$pageJs          = 'galerie'; // charge assets/js/galerie.js (voir footer.php)

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/donnees/galerie.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="contenu">
    <section class="section">
        <div class="conteneur">
            <header class="titre-section">
                <p class="surtitre">Au fauteuil</p>
                <h1>Galerie</h1>
                <hr class="filet">
                <p class="chapeau">Des coupes, des outils, et la lumière du salon.</p>
            </header>

            <ul class="vignettes" role="list">
<?php foreach (galerie() as $photo): ?>
                <li>
                    <figure class="photo">
                        <?= image('galerie/' . $photo['fichier'], $photo['legende'], 800, 800) ?>
                        <figcaption><?= e($photo['legende']) ?></figcaption>
                    </figure>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- Fenêtre d'agrandissement, remplie par galerie.js.
         Sans JavaScript, elle reste fermée et la grille suffit. -->
    <dialog class="agrandissement" id="agrandissement" aria-label="Photo agrandie">
        <div class="agrandissement__contenu">
            <p class="agrandissement__legende"></p>
            <form method="dialog">
                <button class="bouton" type="submit">Fermer</button>
            </form>
        </div>
    </dialog>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
