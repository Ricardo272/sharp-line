<?php
/**
 * Bandeau du mode démo : affiché tant que 'demo' vaut true dans config/site.php.
 */
if (empty($site['demo'])) {
    return;
}
?>
<p class="bandeau-demo">
    <strong>Site de démonstration</strong> : Sharp Line est un salon fictif, aucun vrai rendez-vous n'est pris.
</p>
