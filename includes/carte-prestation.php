<?php
/**
 * Carte d'une prestation. Attend :
 *   $p                  une ligne de prestations()
 *   $avecDescription    true pour afficher la description (page Prestations)
 */
?>
<article class="carte carte-prestation<?= $p['option'] ? ' carte-prestation--option' : '' ?>">
    <h3 class="carte__titre"><?= e($p['nom']) ?></h3>
    <p class="carte-prestation__duree"><?= $p['option'] ? 'En option · ' : '' ?><?= e((string) $p['duree']) ?> min</p>
<?php if (!empty($avecDescription)): ?>
    <p><?= e($p['description']) ?></p>
<?php endif; ?>
    <p class="carte-prestation__prix"><?= $p['option'] ? '+ ' : '' ?><?= e(prix($p['prix'])) ?></p>
</article>
