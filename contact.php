<?php
// Page « Contact / Accès » : adresse, plan, horaires, téléphone, e-mail, réseaux.
$pageId          = 'contact';
$pageTitle       = 'Contact et accès';
$pageDescription = 'Sharp Line, 12 passage des Graffeurs, Paris 20e, métro Belleville : '
                 . 'plan d\'accès, horaires, téléphone et e-mail.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';

$horaires = resumerHoraires($site['horaires']);
// Lien externe vers le quartier réel (la rue, elle, est inventée) : métro Belleville
$lienCarte = 'https://www.openstreetmap.org/?mlat=48.87224&mlon=2.37669#map=17/48.87224/2.37669';
?>

<main id="contenu">
    <section class="section">
        <div class="conteneur">
            <header class="titre-section">
                <p class="surtitre">Nous trouver</p>
                <h1>Contact et accès</h1>
                <hr class="filet">
            </header>

            <div class="contact">
                <div class="contact__infos">
                    <h2 class="contact__titre">Adresse</h2>
                    <address>
                        <?= e($site['adresse']['rue']) ?><br>
                        <?= e($site['adresse']['code_postal'] . ' ' . $site['adresse']['ville']) ?>
                    </address>
                    <p>
                        Métro <?= e($site['metro']) ?>. Remonte la rue de Belleville sur
                        une centaine de mètres : le passage des Graffeurs est la première
                        ruelle à gauche.
                    </p>

                    <h2 class="contact__titre">Horaires</h2>
                    <ul class="liste-simple" role="list">
<?php foreach ($horaires['ouvertures'] as $ligne): ?>
                        <li><?= e($ligne['jours']) ?> : <?= implode(', ', array_map('e', $ligne['creneaux'])) ?></li>
<?php endforeach; ?>
                        <li><?= e($horaires['fermeture']) ?></li>
                    </ul>

                    <h2 class="contact__titre">Nous joindre</h2>
                    <ul class="liste-simple" role="list">
                        <li>Téléphone : <a href="tel:<?= e(lienTelephone($site['telephone'])) ?>"><?= e($site['telephone']) ?></a></li>
                        <li>E-mail : <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a></li>
                        <!-- Pas de lien : un vrai pseudo pourrait appartenir à quelqu'un -->
                        <li>Instagram : @sharpline.test <small>(compte fictif)</small></li>
                    </ul>

                    <p class="groupe-boutons">
                        <a class="bouton" href="<?= e(url('rendez-vous.php')) ?>">Prendre rendez-vous</a>
                    </p>
                </div>

                <figure class="contact__plan">
                    <!-- Plan schématique dessiné en SVG : aucun service de carte, donc aucun cookie -->
                    <svg class="plan" viewBox="0 0 400 320" role="img" aria-labelledby="plan-titre plan-desc">
                        <title id="plan-titre">Plan d'accès schématique</title>
                        <desc id="plan-desc">Depuis le métro Belleville, au croisement du boulevard de Belleville, remonter la rue de Belleville vers l'est : le passage des Graffeurs est la première ruelle à gauche, le salon est au numéro 12.</desc>

                        <rect width="400" height="320" fill="#1E1F24"/>

                        <!-- Rues -->
                        <g fill="none" stroke="#E6E4E0" stroke-opacity=".18" stroke-linecap="round">
                            <line x1="140" y1="0" x2="150" y2="320" stroke-width="18"/>
                            <line x1="0" y1="215" x2="146" y2="185" stroke-width="10"/>
                            <line x1="146" y1="185" x2="400" y2="125" stroke-width="12"/>
                            <line x1="300" y1="148" x2="330" y2="320" stroke-width="6"/>
                        </g>

                        <!-- Passage des Graffeurs : la seule rue en néon -->
                        <line class="plan__passage" x1="236" y1="164" x2="222" y2="70" stroke="#8A3FFC" stroke-width="6" stroke-linecap="round"/>

                        <!-- Noms des rues -->
                        <g fill="#E6E4E0" fill-opacity=".75" font-family="Space Grotesk, Arial, sans-serif" font-size="11">
                            <text transform="translate(128 300) rotate(-88)">Bd de Belleville</text>
                            <text transform="translate(262 168) rotate(-13)">Rue de Belleville</text>
                            <text transform="translate(8 236) rotate(-12)">Rue du Fg du Temple</text>
                            <text transform="translate(200 168) rotate(-81)" fill="#E6E4E0" fill-opacity="1">Passage des Graffeurs</text>
                        </g>

                        <!-- Métro -->
                        <circle cx="146" cy="185" r="14" fill="#2D5BFF"/>
                        <text x="146" y="190" fill="#fff" font-family="Arial, sans-serif" font-size="15" font-weight="700" text-anchor="middle">M</text>
                        <text x="166" y="210" fill="#E6E4E0" font-family="Space Grotesk, Arial, sans-serif" font-size="12" font-weight="700">Belleville</text>

                        <!-- Le salon -->
                        <circle class="plan__salon" cx="224" cy="84" r="8" fill="#E6E4E0" stroke="#2D5BFF" stroke-width="4"/>
                        <text x="240" y="80" fill="#E6E4E0" font-family="Bebas Neue, Arial Narrow, sans-serif" font-size="22" letter-spacing="1">Sharp Line</text>
                        <text x="240" y="96" fill="#E6E4E0" fill-opacity=".75" font-family="Space Grotesk, Arial, sans-serif" font-size="11">n° 12</text>
                    </svg>
                    <figcaption>
                        Plan schématique, pas à l'échelle.
                        <a href="<?= e($lienCarte) ?>" rel="noopener">Voir le quartier sur OpenStreetMap</a>
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
