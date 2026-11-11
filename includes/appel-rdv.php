<?php
/**
 * Appel final « Prendre rendez-vous » dans le bloc néon signature.
 * Repris en bas de l'accueil, des prestations et de l'équipe.
 */
$horairesRdv = resumerHoraires(horaires());
?>
<section class="section bloc-sombre" aria-labelledby="appel-rdv-titre">
    <div class="conteneur">
        <div class="neon-bloc appel-rdv">
            <h2 class="neon-texte" id="appel-rdv-titre">Ton fauteuil t'attend</h2>
            <hr class="filet filet--centre">
            <ul class="appel-rdv__infos" role="list">
<?php foreach ($horairesRdv['ouvertures'] as $ligne): ?>
                <li><?= e($ligne['jours']) ?> : <?= implode(', ', array_map('e', $ligne['creneaux'])) ?></li>
<?php endforeach; ?>
                <li><?= e($site['adresse']['rue']) ?>, métro <?= e($site['metro']) ?></li>
            </ul>
            <div class="groupe-boutons groupe-boutons--centre">
                <a class="bouton" href="<?= e(url('rendez-vous.php')) ?>">Prendre rendez-vous</a>
                <a class="bouton bouton--contour" href="tel:<?= e(lienTelephone($site['telephone'])) ?>">Appeler le <?= e($site['telephone']) ?></a>
            </div>
        </div>
    </div>
</section>
