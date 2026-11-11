<?php
// Accueil : bannière néon, aperçu des prestations et de la galerie, appel au rendez-vous.
$pageId    = 'accueil';
$pageSalon = true; // fiche « HairSalon » pour Google

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/donnees/prestations.php';
require_once __DIR__ . '/includes/donnees/galerie.php';
require_once __DIR__ . '/includes/header.php';

// Aperçus : les 3 premières coupes (sans l'option) et les 4 premières photos
$apercuPrestations = array_slice(array_filter(prestations(), fn ($p) => !$p['option']), 0, 3);
$apercuGalerie     = array_slice(galerie(), 0, 4);
?>

<main id="contenu">

    <!-- Bannière sombre : texte en néon à gauche, photo à droite (dessous en mobile) -->
    <section class="banniere bloc-sombre">
        <div class="conteneur banniere__grille">
            <div>
                <p class="surtitre">Barber shop · Belleville, Paris 20e</p>
                <h1 class="banniere__titre"><?= e($site['nom']) ?></h1>
                <p class="banniere__slogan">Des lignes nettes, un style <span class="tag">à toi.</span></p>
                <ul class="atouts" role="list">
                    <li>Dégradés et contours au rasoir</li>
                    <li>Cheveux bouclés et crépus</li>
                    <li>Coupes enfants et ados</li>
                </ul>
                <div class="groupe-boutons">
                    <a class="bouton" href="<?= e(url('rendez-vous.php')) ?>">Prendre rendez-vous</a>
                    <a class="bouton bouton--contour" href="<?= e(url('prestations.php')) ?>">Nos prestations</a>
                </div>
            </div>
            <?= image('accueil/banniere.jpg', 'Coupe au peigne et aux ciseaux, vue de dos', 720, 900, 'banniere__image', true) ?>
        </div>
    </section>

    <!-- Aperçu des prestations -->
    <section class="section" aria-labelledby="apercu-prestations">
        <div class="conteneur">
            <header class="titre-section">
                <p class="surtitre">Nos coupes</p>
                <h2 id="apercu-prestations">Prestations</h2>
                <hr class="filet">
            </header>
            <ul class="grille" role="list">
<?php foreach ($apercuPrestations as $p): ?>
                <li><?php require __DIR__ . '/includes/carte-prestation.php'; ?></li>
<?php endforeach; ?>
            </ul>
            <p class="lien-suite"><a href="<?= e(url('prestations.php')) ?>">Toutes les prestations et les prix</a></p>
        </div>
    </section>

    <!-- Aperçu de la galerie -->
    <section class="section section--serree" aria-labelledby="apercu-galerie">
        <div class="conteneur">
            <header class="titre-section">
                <p class="surtitre">Au fauteuil</p>
                <h2 id="apercu-galerie">Galerie</h2>
                <hr class="filet">
            </header>
            <ul class="vignettes vignettes--apercu" role="list">
<?php foreach ($apercuGalerie as $photo): ?>
                <li><?= image('galerie/' . $photo['fichier'], $photo['legende'], 800, 800) ?></li>
<?php endforeach; ?>
            </ul>
            <p class="lien-suite"><a href="<?= e(url('galerie.php')) ?>">Voir toute la galerie</a></p>
        </div>
    </section>

    <?php require __DIR__ . '/includes/appel-rdv.php'; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
