<?php
/**
 * Administration : prix, durées et visibilité des prestations.
 * Un seul formulaire pour toutes les prestations ; rien n'est enregistré
 * tant qu'une valeur est fausse.
 */
require_once __DIR__ . '/../includes/admin/auth.php';
require_once __DIR__ . '/../includes/admin/requetes.php';

$admin = exigerConnexion();
$prestations = toutesPrestations();
$erreurs = [];
$saisies = []; // valeurs tapées, pour les réafficher en cas d'erreur

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();
    $valides = [];
    // prix[3]=…, duree[3]=… arrivent en tableaux ; autre chose (texte trafiqué) : tableau vide
    $tableau = fn (string $cle) => is_array($_POST[$cle] ?? null) ? $_POST[$cle] : [];

    // On ne lit que les prestations qui existent : un identifiant inventé est ignoré
    foreach ($prestations as $p) {
        $id = (int) $p['id'];
        $prixTexte  = lireChamps($tableau('prix'), [$id])[$id];
        $dureeTexte = lireChamps($tableau('duree'), [$id])[$id];
        $active     = array_key_exists($id, $tableau('active'));
        $saisies[$id] = ['prix' => $prixTexte, 'duree' => $dureeTexte, 'active' => $active];

        $prix  = lirePrix($prixTexte);
        $duree = filter_var($dureeTexte, FILTER_VALIDATE_INT, ['options' => ['min_range' => 5, 'max_range' => 120]]);

        if ($prix === null) {
            $erreurs["prix-$id"] = $p['nom'] . ' : prix invalide (par exemple 20 ou 22,50).';
        }
        if ($duree === false || $duree % 5 !== 0) {
            $erreurs["duree-$id"] = $p['nom'] . ' : durée de 5 à 120 minutes, par pas de 5.';
        }
        $valides[$id] = [$prix, $duree, $active];
    }

    if ($erreurs === []) {
        db()->beginTransaction(); // tout ou rien
        foreach ($valides as $id => [$prix, $duree, $active]) {
            modifierPrestation($id, $prix, $duree, $active);
        }
        db()->commit();
        flash('Prestations enregistrées. Les nouveaux prix et durées sont en ligne.');
        rediriger('admin/prestations.php');
    }
}

$titreAdmin = 'Prestations';
$pageAdmin  = 'prestations';
require __DIR__ . '/../includes/admin/haut.php';
?>

<h1 class="admin-titre">Prestations</h1>
<p class="admin-aide">La durée est celle affichée au client. L'agenda ajoute 5 min de battement, arrondies à la dizaine.</p>

<?php if ($erreurs): ?>
<div class="erreurs" role="alert">
    <p><strong>Rien n'a été enregistré. Corrige :</strong></p>
    <ul>
<?php foreach ($erreurs as $champ => $texte): ?>
        <li><a href="#<?= e($champ) ?>"><?= e($texte) ?></a></li>
<?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" action="<?= e(url('admin/prestations.php')) ?>">
    <?= champCsrf() ?>
    <div class="admin-cartes">
<?php foreach ($prestations as $p): $id = (int) $p['id']; $s = $saisies[$id] ?? null; ?>
        <fieldset class="carte admin-prestation">
            <legend class="carte__titre"><?= e($p['nom']) ?><?= $p['est_option'] ? ' <small>(option)</small>' : '' ?></legend>
            <div class="admin-prestation__champs">
                <div class="champ">
                    <label for="prix-<?= $id ?>">Prix (€)</label>
                    <input type="text" id="prix-<?= $id ?>" name="prix[<?= $id ?>]" inputmode="decimal" required maxlength="8"
                           value="<?= e($s['prix'] ?? prixPourChamp($p['prix'])) ?>"<?= attributsChamp("prix-$id", $erreurs) ?>>
                </div>
                <div class="champ">
                    <label for="duree-<?= $id ?>">Durée (min)</label>
                    <input type="number" id="duree-<?= $id ?>" name="duree[<?= $id ?>]" min="5" max="120" step="5" required
                           value="<?= e($s['duree'] ?? (string) $p['duree_min']) ?>"<?= attributsChamp("duree-$id", $erreurs) ?>>
                </div>
            </div>
            <p class="case">
                <input type="checkbox" id="active-<?= $id ?>" name="active[<?= $id ?>]" value="1"<?= ($s['active'] ?? (bool) $p['active']) ? ' checked' : '' ?>>
                <label for="active-<?= $id ?>">Proposée sur le site</label>
            </p>
        </fieldset>
<?php endforeach; ?>
    </div>
    <button class="bouton" type="submit">Enregistrer</button>
</form>

<?php require __DIR__ . '/../includes/admin/bas.php'; ?>
