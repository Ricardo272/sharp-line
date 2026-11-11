<?php
/**
 * Administration : fermetures exceptionnelles (jour férié, congé d'un barber…).
 * Un jour bloqué disparaît aussitôt de la prise de rendez-vous.
 */
require_once __DIR__ . '/../includes/admin/auth.php';
require_once __DIR__ . '/../includes/admin/requetes.php';
require_once __DIR__ . '/../includes/donnees/equipe.php';

$admin   = exigerConnexion();
$barbers = array_column(equipe(), 'prenom', 'id');
$erreurs = [];
$valeurs = ['date' => '', 'barber' => '', 'motif' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    // --- Suppression ---
    if (isset($_POST['supprimer'])) {
        supprimerFermeture((int) $_POST['supprimer']);
        flash('Fermeture supprimée : le jour est de nouveau réservable.');
        rediriger('admin/fermetures.php');
    }

    // --- Ajout ---
    $valeurs = lireChamps($_POST, ['date', 'barber', 'motif']);

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $valeurs['date']);
    if (!$date || $date->format('Y-m-d') !== $valeurs['date']) {
        $erreurs['date'] = 'Choisis une date.';
    } elseif ($date < new DateTimeImmutable('today')) {
        $erreurs['date'] = 'Cette date est déjà passée.';
    }

    // « » = tout le salon ; sinon un barber actif
    if ($valeurs['barber'] !== '' && !isset($barbers[(int) $valeurs['barber']])) {
        $erreurs['barber'] = 'Choisis tout le salon ou un barber de la liste.';
    }

    if (mb_strlen($valeurs['motif']) > 120) {
        $erreurs['motif'] = 'Motif trop long (120 caractères maximum).';
    }

    if ($erreurs === []) {
        $barber = $valeurs['barber'] === '' ? null : (int) $valeurs['barber'];
        $pris = ajouterFermeture($valeurs['date'], $barber, $valeurs['motif']);
        $qui = $barber === null ? 'tout le salon' : $barbers[$barber];

        // Les rendez-vous déjà pris ne sont pas annulés d'office : il faut prévenir les clients
        $pris > 0
            ? flash("Journée bloquée pour $qui le " . dateLongue($date, 'd MMMM') . ". Attention : $pris rendez-vous déjà pris ce jour-là, à annuler dans l'agenda après avoir prévenu les clients.", 'erreur')
            : flash("Journée bloquée pour $qui le " . dateLongue($date, 'd MMMM') . '.');
        rediriger('admin/fermetures.php');
    }
}

$aVenir = fermeturesAVenir();

$titreAdmin = 'Fermetures';
$pageAdmin  = 'fermetures';
require __DIR__ . '/../includes/admin/haut.php';
?>

<h1 class="admin-titre">Fermetures</h1>
<p class="admin-aide">Un jour bloqué n'est plus proposé à la réservation, pour tout le salon ou pour un seul barber.</p>

<form class="formulaire admin-bloc" method="post" action="<?= e(url('admin/fermetures.php')) ?>">
    <h2 class="etape__titre">Bloquer un jour</h2>
    <?= champCsrf() ?>

    <div class="champ">
        <label for="date">Date</label>
        <input type="date" id="date" name="date" min="<?= date('Y-m-d') ?>" required
               value="<?= e($valeurs['date']) ?>"<?= attributsChamp('date', $erreurs) ?>>
        <?= messageErreur('date', $erreurs) ?>
    </div>

    <div class="champ">
        <label for="barber">Qui</label>
        <select id="barber" name="barber"<?= attributsChamp('barber', $erreurs) ?>>
            <option value="">Tout le salon</option>
<?php foreach ($barbers as $id => $prenom): ?>
            <option value="<?= $id ?>"<?= $valeurs['barber'] === (string) $id ? ' selected' : '' ?>><?= e($prenom) ?></option>
<?php endforeach; ?>
        </select>
        <?= messageErreur('barber', $erreurs) ?>
    </div>

    <div class="champ">
        <label for="motif">Motif <span class="champ__facultatif">(facultatif)</span></label>
        <input type="text" id="motif" name="motif" maxlength="120" placeholder="Jour férié, congé…"
               value="<?= e($valeurs['motif']) ?>"<?= attributsChamp('motif', $erreurs) ?>>
        <?= messageErreur('motif', $erreurs) ?>
    </div>

    <button class="bouton" type="submit">Bloquer ce jour</button>
</form>

<section class="admin-bloc" aria-labelledby="titre-a-venir">
    <h2 class="etape__titre" id="titre-a-venir">À venir</h2>
<?php if ($aVenir === []): ?>
    <p class="agenda-vide">Aucune fermeture prévue.</p>
<?php else: ?>
    <ul class="rdv-liste" role="list">
<?php foreach ($aVenir as $f): $date = new DateTimeImmutable($f['date']); ?>
        <li class="rdv-ligne">
            <p class="rdv-ligne__heure"><?= e(dateLongue($date, 'd MMM')) ?></p>
            <div class="rdv-ligne__infos">
                <p><strong><?= e($f['barber'] ?? 'Tout le salon') ?></strong></p>
<?php if ($f['motif'] !== ''): ?>
                <p><?= e($f['motif']) ?></p>
<?php endif; ?>
            </div>
            <form method="post" action="<?= e(url('admin/fermetures.php')) ?>">
                <?= champCsrf() ?>
                <button class="bouton-petit" type="submit" name="supprimer" value="<?= e((string) $f['id']) ?>"
                        aria-label="Supprimer la fermeture du <?= e(dateLongue($date)) ?>">Supprimer</button>
            </form>
        </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/admin/bas.php'; ?>
