<?php
/**
 * Page « Prendre rendez-vous », en 4 étapes :
 *   1 prestation · 2 barber (+ motif) · 3 jour puis heure · 4 prénom et téléphone
 *
 * Les étapes 1 à 3 sont de simples formulaires GET : chaque choix est un
 * bouton, la page se recharge avec les choix dans l'adresse. Aucun JavaScript
 * nécessaire. L'étape 4 est envoyée en POST, puis on redirige vers la page de
 * confirmation (Post/Redirect/Get : recharger ne crée pas de doublon).
 */
require_once __DIR__ . '/includes/rendez-vous.php';

$pageId          = 'rendez-vous';
$pageTitle       = 'Prendre rendez-vous';
$pageDescription = 'Réserve ta coupe chez Sharp Line à Belleville : choisis ta prestation, ton barber et ton créneau.';

function rediriger(string $adresse): never
{
    header('Location: ' . $adresse, true, 303); // 303 : la page suivante est lue en GET
    exit;
}

$maintenant = new DateTimeImmutable();
$vue        = 'etapes';  // 'etapes', 'confirmation' ou 'merci'
$erreurs    = [];
$valeurs    = ['prenom' => '', 'telephone' => ''];
$message    = null;       // message en haut d'une étape (« créneau pris »…)

if (is_string($_GET['confirmation'] ?? null)) {
    $vue = 'confirmation';
    $rdv = rendezVousConfirme($_GET['confirmation']);
} elseif (isset($_GET['merci'])) {
    $vue = 'merci';
} else {
    $envoi = $_SERVER['REQUEST_METHOD'] === 'POST';
    $etat = analyserChoix($envoi ? $_POST : $_GET, $maintenant);

    if ($envoi && $etat['etape'] < 4) {
        $message = 'Ce créneau n\'est plus disponible. Choisis-en un autre.';
    } elseif ($envoi) {
        [$valeurs, $erreurs, $spam] = verifierCoordonnees($_POST);

        if ($spam) {
            rediriger(url('rendez-vous.php?merci')); // on ne dit rien au robot
        }

        if ($erreurs === []) {
            $resultat = enregistrerRendezVous($etat, $valeurs, $maintenant);

            if (isset($resultat['id'])) {
                rediriger(lienConfirmation($resultat['id']));
            }

            switch ($resultat['erreur']) {
                case 'pris':
                    // Retour au choix de l'heure, avec les créneaux à jour
                    $etat = analyserChoix(parametresChoix($etat, 4), $maintenant);
                    $message = 'Ce créneau vient d\'être pris. Choisis-en un autre.';
                    break;
                case 'limite_tel':
                    $erreurs['telephone'] = 'Tu as déjà ' . RDV_MAX_A_VENIR_PAR_TEL
                        . ' rendez-vous à venir avec ce numéro. Pour en prendre un autre, appelle-nous.';
                    break;
                default:
                    $erreurs['formulaire'] = 'Trop de demandes en ce moment. Réessaie plus tard, ou appelle-nous au '
                        . $site['telephone'] . '.';
            }
        }
        // Réaffichage : le téléphone tel que tapé, pas la version normalisée
        $valeurs['telephone'] = lireChamps($_POST, ['telephone'])['telephone'];
    }
}

$adresse = fn (array $parametres) => url('rendez-vous.php' . ($parametres ? '?' . http_build_query($parametres) : ''));

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="contenu">
    <section class="section">
        <div class="conteneur conteneur--moyen">

<?php if ($vue === 'confirmation'): ?>
            <!-- ================================================ Confirmation -->
<?php if ($rdv === null): ?>
            <header class="titre-section">
                <p class="surtitre">Rendez-vous</p>
                <h1>Lien introuvable</h1>
                <hr class="filet">
                <p class="chapeau">Ce lien n'est pas valide, ou le rendez-vous est passé depuis plus d'un mois.</p>
            </header>
            <p><a class="bouton" href="<?= e(url('rendez-vous.php')) ?>">Prendre rendez-vous</a></p>
<?php else: $debut = new DateTimeImmutable($rdv['debut']); ?>
            <header class="titre-section">
                <p class="surtitre">Rendez-vous <?= $rdv['statut'] === 'annule' ? 'annulé' : 'confirmé' ?></p>
                <h1><?= $rdv['statut'] === 'annule' ? 'Rendez-vous annulé' : "C'est noté, à bientôt\u{202F}!" /* espace insécable : le « ! » ne part pas seul à la ligne */ ?></h1>
                <hr class="filet">
            </header>
            <dl class="recap">
                <div><dt>Quand</dt><dd><?= e(dateLongue($debut)) ?> à <?= e(formaterHeure($debut->format('H:i'))) ?></dd></div>
                <div><dt>Prestation</dt><dd><?= e($rdv['prestation']) ?><?= $rdv['avec_motif'] ? ' + motif au rasoir' : '' ?></dd></div>
                <div><dt>Avec</dt><dd><?= e($rdv['barber']) ?></dd></div>
                <div><dt>Où</dt><dd><?= e($site['adresse']['rue']) ?>, métro <?= e($site['metro']) ?></dd></div>
            </dl>
            <p>
                Pour annuler ou déplacer ton rendez-vous, appelle le salon au
                <a href="tel:<?= e(lienTelephone($site['telephone'])) ?>"><?= e($site['telephone']) ?></a>.
            </p>
<?php if (!empty($site['demo'])): ?>
            <p class="encadre">Site de démonstration : ce rendez-vous est fictif, personne ne t'attend au salon.</p>
<?php endif; ?>
            <p class="groupe-boutons"><a class="bouton bouton--contour" href="<?= e(url()) ?>">Retour à l'accueil</a></p>
<?php endif; ?>

<?php elseif ($vue === 'merci'): ?>
            <header class="titre-section">
                <p class="surtitre">Rendez-vous</p>
                <h1>Merci</h1>
                <hr class="filet">
                <p class="chapeau">Ta demande a bien été reçue.</p>
            </header>

<?php else: ?>
            <!-- ================================================ Étapes -->
            <header class="titre-section">
                <p class="surtitre">En 4 étapes</p>
                <h1>Prendre rendez-vous</h1>
                <hr class="filet">
            </header>

            <ol class="progression">
<?php foreach ([1 => 'Prestation', 2 => 'Barber', 3 => 'Jour et heure', 4 => 'Coordonnées'] as $n => $nom): ?>
                <li<?= $n === $etat['etape'] ? ' aria-current="step"' : '' ?><?= $n < $etat['etape'] ? ' class="est-fait"' : '' ?>><?= e($nom) ?></li>
<?php endforeach; ?>
            </ol>

<?php if ($etat['etape'] > 1): ?>
            <!-- Choix déjà faits, chacun avec un lien pour revenir en arrière -->
            <dl class="recap">
                <div>
                    <dt>Prestation</dt>
                    <dd>
                        <?= e($etat['prestation']['nom']) ?><?= $etat['motif'] ? ' + motif au rasoir' : '' ?>
                        <span class="recap__infos"><?= e((string) $etat['duree']) ?> min · <?= e(prix($etat['prix'])) ?></span>
                    </dd>
                    <dd><a href="<?= e($adresse([])) ?>">Modifier<span class="visuellement-cache"> la prestation</span></a></dd>
                </div>
<?php if ($etat['barber'] !== null): ?>
                <div>
                    <dt>Barber</dt>
                    <dd><?= $etat['barber'] === 0 ? 'Peu importe' : e(array_column(equipe(), 'prenom', 'id')[$etat['barber']]) ?></dd>
                    <dd><a href="<?= e($adresse(parametresChoix($etat, 2))) ?>">Modifier<span class="visuellement-cache"> le barber</span></a></dd>
                </div>
<?php endif; ?>
<?php if ($etat['heure'] !== null): ?>
                <div>
                    <dt>Rendez-vous</dt>
                    <dd><?= e(dateLongue($etat['jour'])) ?> à <?= e(formaterHeure($etat['heure'])) ?></dd>
                    <dd><a href="<?= e($adresse(parametresChoix($etat, 3))) ?>">Modifier<span class="visuellement-cache"> le jour et l'heure</span></a></dd>
                </div>
<?php endif; ?>
            </dl>
<?php endif; ?>

<?php if ($message !== null): ?>
            <p class="alerte" role="alert"><?= e($message) ?></p>
<?php endif; ?>

<?php if ($etat['etape'] === 1): ?>
            <!-- ------------------------------------------ 1. Prestation -->
            <form class="etape" method="get" action="<?= e(url('rendez-vous.php')) ?>">
                <h2 class="etape__titre">Choisis ta prestation</h2>
                <ul class="choix-liste" role="list">
<?php foreach (prestations() as $p): if ($p['option']) continue; ?>
                    <li>
                        <button class="choix" type="submit" name="prestation" value="<?= e((string) $p['id']) ?>">
                            <span class="choix__titre"><?= e($p['nom']) ?></span>
                            <span class="choix__infos"><?= e((string) $p['duree']) ?> min · <?= e(prix($p['prix'])) ?></span>
                        </button>
                    </li>
<?php endforeach; ?>
                </ul>
                <p class="etape__aide">Un motif au rasoir peut s'ajouter à une coupe à l'étape suivante.</p>
            </form>

<?php elseif ($etat['etape'] === 2): ?>
            <!-- ------------------------------------------ 2. Barber (+ motif) -->
            <form class="etape" method="get" action="<?= e(url('rendez-vous.php')) ?>">
                <input type="hidden" name="prestation" value="<?= e((string) $etat['prestation']['id']) ?>">
<?php if ($etat['prestation']['motif_possible'] && $etat['option']): ?>
                <p class="case">
                    <input type="checkbox" id="motif" name="motif" value="1"<?= $etat['motif'] ? ' checked' : '' ?>>
                    <label for="motif">
                        Ajouter un <?= e(mb_strtolower($etat['option']['nom'])) ?>
                        (+ <?= e((string) $etat['option']['duree']) ?> min, + <?= e(prix($etat['option']['prix'])) ?>)
                    </label>
                </p>
<?php endif; ?>
                <h2 class="etape__titre">Choisis ton barber</h2>
                <ul class="choix-liste" role="list">
<?php foreach (equipe() as $b): ?>
                    <li>
                        <button class="choix" type="submit" name="barber" value="<?= e((string) $b['id']) ?>">
                            <span class="choix__titre"><?= e($b['prenom']) ?></span>
                            <span class="choix__infos"><?= e($b['specialite']) ?></span>
                        </button>
                    </li>
<?php endforeach; ?>
                    <li>
                        <button class="choix choix--neutre" type="submit" name="barber" value="0">
                            <span class="choix__titre">Peu importe</span>
                            <span class="choix__infos">Le premier fauteuil libre</span>
                        </button>
                    </li>
                </ul>
            </form>

<?php elseif ($etat['etape'] === 3 && $etat['jour'] === null): ?>
            <!-- ------------------------------------------ 3a. Jour -->
            <form class="etape" method="get" action="<?= e(url('rendez-vous.php')) ?>">
<?php foreach (parametresChoix($etat, 3) as $nom => $val): ?>
                <input type="hidden" name="<?= e($nom) ?>" value="<?= e((string) $val) ?>">
<?php endforeach; ?>
                <h2 class="etape__titre">Choisis ton jour</h2>
<?php if ($etat['jours'] === []): ?>
                <p>Aucun jour disponible dans les trois prochaines semaines. Appelle-nous au <?= e($site['telephone']) ?>.</p>
<?php else: ?>
                <ul class="jours" role="list">
<?php foreach ($etat['jours'] as $cle => $jour): ?>
                    <li>
                        <button class="jour" type="submit" name="jour" value="<?= e($cle) ?>" aria-label="<?= e(dateLongue($jour)) ?>">
                            <span class="jour__nom"><?= e(dateLongue($jour, 'EEE')) ?></span>
                            <span class="jour__date"><?= e(dateLongue($jour, 'd MMM')) ?></span>
                        </button>
                    </li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
            </form>

<?php elseif ($etat['etape'] === 3): ?>
            <!-- ------------------------------------------ 3b. Heure -->
            <form class="etape" method="get" action="<?= e(url('rendez-vous.php')) ?>">
<?php foreach (parametresChoix($etat, 4) as $nom => $val): ?>
                <input type="hidden" name="<?= e($nom) ?>" value="<?= e((string) $val) ?>">
<?php endforeach; ?>
                <h2 class="etape__titre">Choisis ton heure</h2>
                <p class="etape__aide">
                    <?= e(dateLongue($etat['jour'])) ?> ·
                    <a href="<?= e($adresse(parametresChoix($etat, 3))) ?>">Changer de jour</a>
                </p>
<?php if ($etat['journee']['libres'] === []): ?>
                <p>Plus aucun créneau libre ce jour-là. Choisis un autre jour.</p>
<?php else: ?>
<?php
    // Regroupement par heure : « 10h » → 10:00, 10:05, 10:10…
    $parHeure = [];
    foreach (array_keys($etat['journee']['libres']) as $creneau) {
        $parHeure[(int) substr($creneau, 0, 2)][] = $creneau;
    }
?>
                <div class="creneaux">
<?php foreach ($parHeure as $h => $creneaux): ?>
                    <div class="creneaux__ligne">
                        <p class="creneaux__heure"><?= $h ?>h</p>
                        <ul class="creneaux__liste" role="list">
<?php foreach ($creneaux as $creneau): ?>
                            <li><button class="creneau" type="submit" name="heure" value="<?= e($creneau) ?>"><?= e(str_replace(':', 'h', $creneau)) ?></button></li>
<?php endforeach; ?>
                        </ul>
                    </div>
<?php endforeach; ?>
                </div>
<?php endif; ?>
            </form>

<?php else: ?>
            <!-- ------------------------------------------ 4. Coordonnées -->
            <form class="etape formulaire" method="post" action="<?= e(url('rendez-vous.php')) ?>">
                <h2 class="etape__titre">Tes coordonnées</h2>

<?php if ($erreurs): ?>
                <div class="erreurs" role="alert">
                    <p><strong>Vérifie ces informations :</strong></p>
                    <ul>
<?php foreach ($erreurs as $champ => $texte): ?>
                        <li><?= $champ === 'formulaire' ? e($texte) : '<a href="#champ-' . e($champ) . '">' . e($texte) . '</a>' ?></li>
<?php endforeach; ?>
                    </ul>
                </div>
<?php endif; ?>

<?php foreach (parametresChoix($etat) as $nom => $val): ?>
                <input type="hidden" name="<?= e($nom) ?>" value="<?= e((string) $val) ?>">
<?php endforeach; ?>
                <input type="hidden" name="jeton" value="<?= e(creerJeton()) ?>">

                <div class="champ">
                    <label for="champ-prenom">Prénom</label>
                    <input type="text" id="champ-prenom" name="prenom" value="<?= e($valeurs['prenom']) ?>"
                           autocomplete="given-name" maxlength="50" required<?= attributsChamp('prenom', $erreurs) ?>>
                    <?= messageErreur('prenom', $erreurs) ?>
                </div>

                <div class="champ">
                    <label for="champ-telephone">Téléphone</label>
                    <p class="champ__aide" id="aide-telephone">Seulement pour te prévenir en cas d'imprévu.</p>
                    <input type="tel" id="champ-telephone" name="telephone" value="<?= e($valeurs['telephone']) ?>"
                           autocomplete="tel" inputmode="tel" maxlength="20" required<?= attributsChamp('telephone', $erreurs, 'aide-telephone') ?>>
                    <?= messageErreur('telephone', $erreurs) ?>
                </div>

                <!-- Champ piège : invisible pour les humains, rempli par les robots -->
                <div class="champ-piege" aria-hidden="true">
                    <label for="site_web">Ne pas remplir ce champ</label>
                    <input type="text" id="site_web" name="site_web" tabindex="-1" autocomplete="off">
                </div>

                <p class="formulaire__total">
                    Total : <strong><?= e(prix($etat['prix'])) ?></strong> · <?= e((string) $etat['duree']) ?> min, à régler au salon.
                </p>
                <button class="bouton" type="submit">Confirmer le rendez-vous</button>
                <p class="formulaire__rgpd">
                    Ton prénom et ton numéro servent uniquement à ce rendez-vous et sont supprimés
                    un mois après. <a href="<?= e(url('mentions-legales.php')) ?>">En savoir plus</a>.
                </p>
            </form>
<?php endif; ?>
<?php endif; ?>

        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
