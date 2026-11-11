<?php
// Page « Mentions légales »
$pageId          = 'mentions';
$pageTitle       = 'Mentions légales';
$pageDescription = 'Mentions légales, données personnelles et cookies du site de Sharp Line.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="contenu">
    <section class="section">
        <div class="conteneur conteneur--etroit prose">
            <p class="surtitre">Informations</p>
            <h1>Mentions légales</h1>
            <hr class="filet">

            <div class="encadre">
                <p>
                    <strong><?= e($site['nom']) ?> est un barber shop fictif.</strong>
                    Ce site a été réalisé par Ricardo R. dans le cadre de son portfolio
                    de développeur web. Les personnes, l'adresse, le numéro de téléphone et
                    le compte Instagram sont inventés : aucun rendez-vous n'est réellement pris.
                </p>
            </div>

            <h2>Éditeur du site</h2>
            <p>
                Ricardo R., développeur web.<br>
                Directeur de la publication : Ricardo R.
            </p>

            <h2>Hébergement</h2>
            <!-- shortcut: site encore en local ; coordonnées d'alwaysdata à mettre en phase 8 -->
            <p>Le site est en cours de développement. L'hébergeur sera indiqué à la mise en ligne.</p>

            <h2>Propriété intellectuelle</h2>
            <p>
                Les textes, la mise en page et le code du site sont la propriété de leur auteur.
                Les polices Bebas Neue et Space Grotesk sont distribuées sous licence libre
                SIL Open Font License 1.1, la police Permanent Marker sous licence Apache 2.0.
            </p>
            <p>
                Les photographies sont en attente : les emplacements affichent des images de
                remplacement. Leurs auteurs seront crédités ici.
            </p>

            <h2>Données personnelles</h2>
            <p>
                Le formulaire de rendez-vous recueille uniquement votre prénom et votre numéro
                de téléphone, avec la prestation, le barber et le créneau choisis.
            </p>
            <ul>
                <li>
                    <strong>Pourquoi :</strong> uniquement pour organiser votre rendez-vous et
                    vous joindre en cas d'imprévu.
                </li>
                <li>
                    <strong>Sur quelle base :</strong> votre demande (mesures préalables à un
                    contrat, article 6.1.b du RGPD).
                </li>
                <li>
                    <strong>Qui y a accès :</strong> le salon seulement. Vos données ne sont
                    ni vendues ni transmises à des tiers. Votre adresse IP n'est pas enregistrée.
                </li>
                <li>
                    <strong>Combien de temps :</strong> un mois après la date du rendez-vous,
                    puis elles sont supprimées automatiquement.
                </li>
            </ul>
            <p>
                Vous pouvez à tout moment demander l'accès à vos données, leur rectification
                ou leur suppression, ou vous opposer à leur traitement, en écrivant à
                <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a>.
                Vous pouvez aussi adresser une réclamation à la CNIL
                (<a href="https://www.cnil.fr" rel="noopener">cnil.fr</a>).
            </p>

            <h2>Cookies</h2>
            <p>
                Le site public ne dépose <strong>aucun cookie</strong> et n'utilise aucun outil de
                mesure d'audience ni de publicité : c'est pourquoi il n'affiche pas de bandeau
                de consentement. Les polices sont hébergées sur le site lui-même et le plan
                d'accès est dessiné, sans service de carte : aucune donnée n'est transmise
                à un tiers lors de votre visite.
            </p>
            <p>
                Seul l'espace d'administration, réservé au salon, utilise un cookie de
                session strictement nécessaire à la connexion.
            </p>

            <p class="prose__maj">Dernière mise à jour : novembre 2026.</p>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
