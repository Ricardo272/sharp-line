<?php
/**
 * Administration : agenda.
 * Vue « jour » : les rendez-vous regroupés par barber. Vue « semaine » : les 7 jours à la suite.
 * Filtre par barber, navigation jour par jour ou semaine par semaine, annulation.
 *
 * Paramètres d'adresse : ?jour=2026-11-18&vue=semaine&barber=2 (tous facultatifs, revérifiés).
 */
require_once __DIR__ . '/../includes/admin/auth.php';
require_once __DIR__ . '/../includes/admin/requetes.php';
require_once __DIR__ . '/../includes/rendez-vous.php'; // equipe(), horaires(), fermeturesEntre()

$admin = exigerConnexion();

// --- Lecture et vérification des paramètres (GET, ou champs cachés du POST) ---
$source = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$texte  = fn (string $cle) => is_string($source[$cle] ?? null) ? $source[$cle] : '';

$jour = DateTimeImmutable::createFromFormat('!Y-m-d', $texte('jour'));
if (!$jour || $jour->format('Y-m-d') !== $texte('jour')) {
    $jour = new DateTimeImmutable('today');
}
$vue = $texte('vue') === 'semaine' ? 'semaine' : 'jour';

$barbers = array_column(equipe(), 'prenom', 'id'); // [1 => 'Karim', …]
$barber  = isset($barbers[(int) $texte('barber')]) ? (int) $texte('barber') : 0;

$parametres = fn (array $changements = []) => array_filter(
    array_merge(['jour' => $jour->format('Y-m-d'), 'vue' => $vue === 'semaine' ? 'semaine' : null, 'barber' => $barber ?: null], $changements),
    fn ($v) => $v !== null
);
$adresse = fn (array $changements = []) => url('admin/?' . http_build_query($parametres($changements)));

// --- Annulation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();
    $id = (int) $texte('annuler');
    annulerRendezVous($id)
        ? flash('Rendez-vous annulé : le créneau est de nouveau libre. Pense à prévenir le client.')
        : flash('Ce rendez-vous était déjà annulé.', 'erreur');
    rediriger('admin/?' . http_build_query($parametres()));
}

// --- Période affichée ---
if ($vue === 'semaine') {
    $debut = $jour->modify('monday this week');
    $fin   = $debut->modify('+7 days');
    $pas   = '7 days';
    $titre = 'Semaine du ' . dateLongue($debut, 'd MMMM') . ' au ' . dateLongue($fin->modify('-1 day'), 'd MMMM');
} else {
    $debut = $jour;
    $fin   = $jour->modify('+1 day');
    $pas   = '1 day';
    $titre = dateLongue($jour) . ($jour->format('Y-m-d') === date('Y-m-d') ? ' (aujourd\'hui)' : '');
}

$rendezVous = rendezVousEntre($debut->format('Y-m-d'), $fin->format('Y-m-d'), $barber);
$fermetures = fermeturesEntre($debut->format('Y-m-d'), $fin->modify('-1 day')->format('Y-m-d'));
$horaires   = horaires();
$maintenant = date('Y-m-d H:i:s');
$confirmes  = count(array_filter($rendezVous, fn ($r) => $r['statut'] === 'confirme'));

// Champs cachés des formulaires d'annulation : on revient sur la même vue après
$champsRetour = '';
foreach ($parametres() as $nom => $valeur) {
    $champsRetour .= '<input type="hidden" name="' . e($nom) . '" value="' . e((string) $valeur) . '">';
}

/**
 * Une ligne de rendez-vous. $avecBarber : afficher le prénom du barber (vue semaine).
 */
function ligneRendezVous(array $r, bool $avecBarber, string $maintenant, string $champsRetour): string
{
    $heure = formaterHeure(substr($r['debut'], 11, 5));
    $annule = $r['statut'] === 'annule';
    ob_start(); ?>
                <li class="rdv-ligne<?= $annule ? ' rdv-ligne--annule' : '' ?>">
                    <p class="rdv-ligne__heure"><?= e($heure) ?></p>
                    <div class="rdv-ligne__infos">
                        <p><strong><?= e($r['prenom']) ?></strong><?= $avecBarber ? ' · avec ' . e($r['barber']) : '' ?><?= $annule ? ' <span class="rdv-ligne__statut">annulé</span>' : '' ?></p>
                        <p><?= e($r['prestation']) ?><?= $r['avec_motif'] ? ' + motif' : '' ?></p>
                        <p><a href="tel:<?= e(lienTelephone($r['telephone'])) ?>"><?= e(formaterTelephone($r['telephone'])) ?></a></p>
                    </div>
<?php if (!$annule && $r['debut'] > $maintenant): ?>
                    <form method="post" action="<?= e(url('admin/')) ?>">
                        <?= champCsrf() ?>
                        <?= $champsRetour ?>
                        <button class="bouton-petit" type="submit" name="annuler" value="<?= e((string) $r['id']) ?>"
                                aria-label="Annuler le rendez-vous de <?= e($r['prenom']) ?> à <?= e($heure) ?>">Annuler</button>
                    </form>
<?php endif; ?>
                </li>
<?php
    return ob_get_clean();
}

$titreAdmin = 'Agenda';
$pageAdmin  = 'agenda';
require __DIR__ . '/../includes/admin/haut.php';
?>

<h1 class="admin-titre">Agenda</h1>

<!-- Vue et filtre par barber -->
<nav class="filtres" aria-label="Affichage">
    <ul class="filtres__liste" role="list">
        <li><a href="<?= e($adresse(['vue' => null])) ?>"<?= $vue === 'jour' ? ' aria-current="true"' : '' ?>>Jour</a></li>
        <li><a href="<?= e($adresse(['vue' => 'semaine'])) ?>"<?= $vue === 'semaine' ? ' aria-current="true"' : '' ?>>Semaine</a></li>
    </ul>
    <ul class="filtres__liste" role="list">
        <li><a href="<?= e($adresse(['barber' => null])) ?>"<?= $barber === 0 ? ' aria-current="true"' : '' ?>>Tous</a></li>
<?php foreach ($barbers as $id => $prenom): ?>
        <li><a href="<?= e($adresse(['barber' => $id])) ?>"<?= $barber === $id ? ' aria-current="true"' : '' ?>><?= e($prenom) ?></a></li>
<?php endforeach; ?>
    </ul>
</nav>

<!-- Navigation dans le temps -->
<div class="periode">
    <a class="periode__fleche" href="<?= e($adresse(['jour' => $jour->modify('-' . $pas)->format('Y-m-d')])) ?>" aria-label="<?= $vue === 'semaine' ? 'Semaine précédente' : 'Jour précédent' ?>">←</a>
    <div class="periode__centre">
        <h2 class="periode__titre"><?= e($titre) ?></h2>
        <p><?= $confirmes ?> rendez-vous<?php if ($jour->format('Y-m-d') !== date('Y-m-d')): ?> · <a href="<?= e($adresse(['jour' => date('Y-m-d')])) ?>">Aujourd'hui</a><?php endif; ?></p>
    </div>
    <a class="periode__fleche" href="<?= e($adresse(['jour' => $jour->modify('+' . $pas)->format('Y-m-d')])) ?>" aria-label="<?= $vue === 'semaine' ? 'Semaine suivante' : 'Jour suivant' ?>">→</a>
</div>

<?php if ($vue === 'jour'):
    $cle = $jour->format('Y-m-d');
    $fermeture = $fermetures[$cle] ?? ['salon' => false, 'barbers' => []];
    $ferme = $horaires[(int) $jour->format('N')] === [] || $fermeture['salon'];
?>
<?php if ($ferme): ?>
<p class="encadre">Le salon est fermé ce jour-là.</p>
<?php endif; ?>
<?php foreach ($barbers as $id => $prenom): if ($barber !== 0 && $barber !== $id) continue;
    $siens = array_filter($rendezVous, fn ($r) => (int) $r['barber_id'] === $id); ?>
<section class="agenda-barber" aria-labelledby="barber-<?= $id ?>">
    <h3 class="agenda-barber__nom" id="barber-<?= $id ?>"><?= e($prenom) ?><?= in_array($id, $fermeture['barbers'], true) ? ' <span class="agenda-barber__absent">indisponible</span>' : '' ?></h3>
<?php if ($siens === []): ?>
    <p class="agenda-vide">Aucun rendez-vous.</p>
<?php else: ?>
    <ol class="rdv-liste" role="list">
<?php foreach ($siens as $r) echo ligneRendezVous($r, false, $maintenant, $champsRetour); ?>
    </ol>
<?php endif; ?>
</section>
<?php endforeach; ?>

<?php else: ?>
<?php for ($j = $debut; $j < $fin; $j = $j->modify('+1 day')):
    $cle = $j->format('Y-m-d');
    $duJour = array_filter($rendezVous, fn ($r) => str_starts_with($r['debut'], $cle));
    $fermeture = $fermetures[$cle] ?? ['salon' => false, 'barbers' => []];
    $ferme = $horaires[(int) $j->format('N')] === [] || $fermeture['salon'];
?>
<section class="agenda-jour" aria-labelledby="jour-<?= e($cle) ?>">
    <h3 class="agenda-barber__nom" id="jour-<?= e($cle) ?>">
        <a href="<?= e($adresse(['jour' => $cle, 'vue' => null])) ?>"><?= e(dateLongue($j)) ?></a>
<?php foreach ($fermeture['barbers'] as $absent): if (isset($barbers[$absent])): ?>
        <span class="agenda-barber__absent"><?= e($barbers[$absent]) ?> indisponible</span>
<?php endif; endforeach; ?>
    </h3>
<?php if ($ferme): ?>
    <p class="agenda-vide">Fermé.</p>
<?php elseif ($duJour === []): ?>
    <p class="agenda-vide">Aucun rendez-vous.</p>
<?php else: ?>
    <ol class="rdv-liste" role="list">
<?php foreach ($duJour as $r) echo ligneRendezVous($r, $barber === 0, $maintenant, $champsRetour); ?>
    </ol>
<?php endif; ?>
</section>
<?php endfor; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin/bas.php'; ?>
