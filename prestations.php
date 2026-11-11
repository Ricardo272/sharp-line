<?php
// Page « Prestations » : prix, durées et infos pratiques.
$pageId          = 'prestations';
$pageTitle       = 'Prestations et prix';
$pageDescription = 'Coupe homme, coupe + barbe, taille de barbe, coupe enfant et motifs au rasoir : '
                 . 'les prix et durées de Sharp Line, barber shop à Belleville.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/donnees/prestations.php';
require_once __DIR__ . '/includes/header.php';

$avecDescription = true; // lu par carte-prestation.php
?>

<main id="contenu">
    <section class="section">
        <div class="conteneur">
            <header class="titre-section">
                <p class="surtitre">Prix et durées</p>
                <h1>Prestations</h1>
                <hr class="filet">
                <p class="chapeau">
                    Des prix simples, affichés, sans surprise. La durée indiquée est celle
                    réservée sur ton créneau.
                </p>
            </header>

            <ul class="grille" role="list">
<?php foreach (prestations() as $p): ?>
                <li><?php require __DIR__ . '/includes/carte-prestation.php'; ?></li>
<?php endforeach; ?>
            </ul>

            <aside class="encadre" aria-labelledby="infos-pratiques">
                <h2 class="encadre__titre" id="infos-pratiques">Bon à savoir</h2>
                <ul class="atouts" role="list">
                    <li>Paiement par carte bancaire ou en espèces</li>
                    <li>Sans rendez-vous si un fauteuil est libre</li>
                    <li>Plus de 10 minutes de retard : le rendez-vous est décalé</li>
                </ul>
            </aside>
        </div>
    </section>

    <?php require __DIR__ . '/includes/appel-rdv.php'; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
