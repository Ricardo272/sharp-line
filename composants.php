<?php
// Page de démonstration TEMPORAIRE : montre tous les composants au même endroit.
// Non indexée, absente du menu. À supprimer à la fin de la phase 3.
$pageTitle   = 'Composants';
$pageNoIndex = true;

// Données d'exemple : elles viendront de la base en phase 4
$prestations = [
    ['nom' => 'Coupe homme',            'duree' => 20, 'prix' => 20],
    ['nom' => 'Coupe + barbe',          'duree' => 35, 'prix' => 25],
    ['nom' => 'Taille de barbe',        'duree' => 15, 'prix' => 10],
    ['nom' => 'Coupe enfant (−12 ans)', 'duree' => 15, 'prix' => 15],
];
$equipe = [
    ['prenom' => 'Karim', 'role' => 'Fondateur', 'specialite' => 'Dégradés et contours au rasoir', 'photo' => 'equipe/karim.jpg', 'alt' => 'Mains de Karim tenant un rasoir'],
    ['prenom' => 'Inès',  'role' => 'Barber',    'specialite' => 'Coupes texturées, cheveux bouclés et crépus', 'photo' => 'equipe/ines.jpg', 'alt' => 'Peigne et ciseaux sur le poste d\'Inès'],
    ['prenom' => 'Tony',  'role' => 'Barber',    'specialite' => 'Coupes enfants et ados', 'photo' => 'equipe/tony.jpg', 'alt' => 'Tondeuse posée sur le poste de Tony'],
];

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="contenu">

    <!-- Titre de section + filets -->
    <section class="section">
        <div class="conteneur">
            <header class="titre-section">
                <p class="surtitre">Démonstration</p>
                <h1>Les composants</h1>
                <hr class="filet">
            </header>

            <h2>Boutons</h2>
            <div class="groupe-boutons" style="margin-block: 1.5rem 3rem">
                <a class="bouton" href="#">Prendre rendez-vous</a>
                <a class="bouton bouton--contour" href="#">Voir la galerie</a>
            </div>

            <h2>Filet centré</h2>
            <hr class="filet filet--centre">
        </div>
    </section>

    <!-- Cartes prestations -->
    <section class="section">
        <div class="conteneur">
            <header class="titre-section">
                <p class="surtitre">Nos coupes</p>
                <h2>Prestations</h2>
            </header>
            <ul class="grille" role="list">
<?php foreach ($prestations as $p): ?>
                <li>
                    <article class="carte carte-prestation">
                        <h3 class="carte__titre"><?= e($p['nom']) ?></h3>
                        <p class="carte-prestation__duree"><?= e((string) $p['duree']) ?> min</p>
                        <p class="carte-prestation__prix"><?= e(prix($p['prix'])) ?></p>
                    </article>
                </li>
<?php endforeach; ?>
                <li>
                    <article class="carte carte-prestation carte-prestation--option">
                        <h3 class="carte__titre">Motif / trait au rasoir</h3>
                        <p class="carte-prestation__duree">En option · 5 min</p>
                        <p class="carte-prestation__prix">+ <?= e(prix(5)) ?></p>
                    </article>
                </li>
            </ul>
        </div>
    </section>

    <!-- Cartes barbers -->
    <section class="section">
        <div class="conteneur">
            <header class="titre-section">
                <p class="surtitre">L'équipe</p>
                <h2>Trois paires de mains</h2>
            </header>
            <ul class="grille" role="list">
<?php foreach ($equipe as $b): ?>
                <li>
                    <article class="carte carte-barber">
                        <?= image($b['photo'], $b['alt'], 600, 750) ?>
                        <div class="carte-barber__texte">
                            <p class="carte-barber__role"><?= e($b['role']) ?></p>
                            <h3 class="carte__titre"><?= e($b['prenom']) ?></h3>
                            <p><?= e($b['specialite']) ?></p>
                        </div>
                    </article>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- Bloc signature néon + tag -->
    <section class="section bloc-sombre">
        <div class="conteneur">
            <div class="neon-bloc">
                <p class="neon-texte">Des lignes nettes, un style <span class="tag">à toi.</span></p>
                <hr class="filet filet--centre">
                <div class="groupe-boutons" style="justify-content: center">
                    <a class="bouton" href="#">Prendre rendez-vous</a>
                    <a class="bouton bouton--contour" href="#">Nos prestations</a>
                </div>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
