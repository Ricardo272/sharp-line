<?php
/**
 * En-tête commun (bloc sombre) : lien d'évitement, logo, navigation,
 * puis le bandeau du mode démo.
 */
?>
<a class="lien-evitement" href="#contenu">Aller au contenu</a>

<header class="entete">
    <div class="conteneur entete__interieur">
        <!-- « Line » en Permanent Marker : un des deux seuls mots en style tag -->
        <a class="logo" href="<?= e(url()) ?>" aria-label="<?= e($site['nom']) ?>, accueil">
            <span class="logo__sharp">Sharp</span><span class="logo__line">Line</span>
        </a>

        <!-- Bouton du menu mobile. Il doit rester JUSTE AVANT la navigation :
             le CSS s'appuie sur cet ordre (sélecteur « + »). -->
        <button class="nav-bouton" type="button" aria-expanded="false" aria-controls="nav-liste">
            <span class="nav-bouton__icone" aria-hidden="true"></span>
            <span>Menu</span>
        </button>

        <nav class="nav" aria-label="Navigation principale">
            <!-- role="list" : Safari retire la sémantique de liste quand on supprime les puces en CSS -->
            <ul class="nav__liste" id="nav-liste" role="list">
<?php foreach ($site['navigation'] as $lien): ?>
                <li><a class="nav__lien" href="<?= e(url($lien['url'])) ?>"<?= $lien['id'] === $pageId ? ' aria-current="page"' : '' ?>><?= e($lien['label']) ?></a></li>
<?php endforeach; ?>
<?php $lienRdv = $site['rendez_vous']; ?>
                <li><a class="nav__lien nav__lien--rdv" href="<?= e(url($lienRdv['url'])) ?>"<?= $lienRdv['id'] === $pageId ? ' aria-current="page"' : '' ?>><?= e($lienRdv['label']) ?></a></li>
            </ul>
        </nav>
    </div>
</header>
<?php require __DIR__ . '/bandeau-demo.php'; ?>
