<?php
// Page « L'équipe » : les trois barbers et leur spécialité.
$pageId          = 'equipe';
$pageTitle       = "L'équipe";
$pageDescription = 'Karim, Inès et Tony : les trois barbers de Sharp Line à Belleville, '
                 . 'spécialistes des dégradés, des cheveux bouclés et crépus, et des coupes enfants.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/donnees/equipe.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="contenu">
    <section class="section">
        <div class="conteneur">
            <header class="titre-section">
                <p class="surtitre">Les barbers</p>
                <h1>L'équipe</h1>
                <hr class="filet">
                <p class="chapeau">
                    Trois fauteuils, trois spécialités. Au moment de réserver, choisis
                    ton barber ou laisse-nous faire.
                </p>
            </header>

            <ul class="grille" role="list">
<?php foreach (equipe() as $b): ?>
                <li>
                    <article class="carte carte-barber">
                        <?= image($b['photo'], $b['alt'], 600, 750) ?>
                        <div class="carte-barber__texte">
                            <p class="carte-barber__role"><?= e($b['role']) ?></p>
                            <h2 class="carte__titre"><?= e($b['prenom']) ?></h2>
                            <p class="carte-barber__specialite"><?= e($b['specialite']) ?></p>
                            <p><?= e($b['phrase']) ?></p>
                        </div>
                    </article>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>

    <?php require __DIR__ . '/includes/appel-rdv.php'; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
