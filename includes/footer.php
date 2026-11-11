<?php
/**
 * Pied de page commun (bloc sombre), puis fermeture du <body>.
 */
$horaires = resumerHoraires(horaires());
?>
<footer class="pied">
    <div class="conteneur pied__grille">

        <div class="pied__bloc">
            <p class="pied__nom"><?= e($site['nom']) ?></p>
            <p><?= e($site['slogan']) ?></p>
        </div>

        <div class="pied__bloc">
            <h2 class="pied__titre">Nous trouver</h2>
            <address>
                <?= e($site['adresse']['rue']) ?><br>
                <?= e($site['adresse']['code_postal'] . ' ' . $site['adresse']['ville']) ?><br>
                Métro <?= e($site['metro']) ?><br>
                <a href="tel:<?= e(lienTelephone($site['telephone'])) ?>"><?= e($site['telephone']) ?></a><br>
                <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a>
            </address>
        </div>

        <div class="pied__bloc">
            <h2 class="pied__titre">Horaires</h2>
            <ul class="pied__liste" role="list">
<?php foreach ($horaires['ouvertures'] as $ligne): ?>
                <li><?= e($ligne['jours']) ?> : <?= implode(', ', array_map('e', $ligne['creneaux'])) ?></li>
<?php endforeach; ?>
<?php if ($horaires['fermeture']): ?>
                <li><?= e($horaires['fermeture']) ?></li>
<?php endif; ?>
            </ul>
        </div>

        <nav class="pied__bloc" aria-label="Liens du pied de page">
            <h2 class="pied__titre">Le site</h2>
            <ul class="pied__liste" role="list">
<?php foreach ([...$site['navigation'], $site['rendez_vous']] as $lien): ?>
                <li><a href="<?= e(url($lien['url'])) ?>"><?= e($lien['label']) ?></a></li>
<?php endforeach; ?>
                <li><a href="<?= e(url('mentions-legales.php')) ?>">Mentions légales</a></li>
            </ul>
        </nav>

    </div>

    <p class="pied__copyright">
        © <?= date('Y') ?> <?= e($site['nom']) ?> · Barber shop fictif, projet portfolio
    </p>
</footer>

<script src="<?= e(asset('assets/js/menu.js')) ?>" defer></script>
<?php if (!empty($pageJs)): // script propre à une page (ex. galerie) ?>
<script src="<?= e(asset('assets/js/' . $pageJs . '.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
