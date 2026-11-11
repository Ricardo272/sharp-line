<?php
/**
 * Rendez-vous : créneaux libres, lecture des choix, vérification, enregistrement.
 * Tout le SQL des rendez-vous est dans ce fichier.
 *
 * Règle d'or : on ne fait JAMAIS confiance à ce qui arrive du navigateur.
 * Chaque choix (prestation, barber, jour, heure) est revérifié à chaque étape,
 * et une dernière fois au moment d'enregistrer.
 */

require_once __DIR__ . '/donnees/prestations.php';
require_once __DIR__ . '/donnees/equipe.php';
require_once __DIR__ . '/donnees/horaires.php';
require_once __DIR__ . '/formulaire.php';

const RDV_PAS                = 5;    // un créneau toutes les 5 minutes
const RDV_BATTEMENT          = 5;    // minutes pour nettoyer, préparer, encaisser (jamais affichées au client)
const RDV_BLOC               = 10;   // le temps bloqué est arrondi à la dizaine supérieure
const RDV_HORIZON_JOURS      = 21;   // 3 semaines de jours proposés
const RDV_DELAI_MINUTES      = 30;   // le jour même : au plus tôt dans 30 min
const RDV_MAX_A_VENIR_PAR_TEL = 2;
// shortcut: limite globale, pas par visiteur : un robot qui l'atteint bloque aussi les vrais
// clients pendant une heure ; passer à une limite par visiteur si le salon prend de vrais rendez-vous.
const RDV_MAX_PAR_HEURE      = 20;
const RDV_CONSERVATION       = '-1 month';


/* ==========================================================================
   1. Calcul des créneaux (aucune base de données : testé par tests/rendez-vous.php)
   Les heures sont converties en minutes depuis minuit : 10:05 → 605.
   ========================================================================== */

function minutes(string $heure): int
{
    [$h, $m] = explode(':', $heure);
    return (int) $h * 60 + (int) $m;
}

function heure(int $minutes): string
{
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

/**
 * Temps bloqué dans l'agenda du barber : la vraie durée + le battement,
 * arrondie à la dizaine supérieure. 15 → 20, 20 → 30, 25 → 30, 35 → 40, 40 → 50.
 * Le client, lui, ne voit que la vraie durée.
 */
function dureeBloquee(int $duree): int
{
    return (int) ceil(($duree + RDV_BATTEMENT) / RDV_BLOC) * RDV_BLOC;
}

/**
 * Créneaux libres d'une journée.
 *
 * @param int   $duree    vraie durée de la prestation (le battement est ajouté ici)
 * @param array $occupes  [barberId => [[debut, fin], …]] en minutes, fin = fin du temps
 *                        bloqué : un tableau, même vide, pour chaque barber présent ce jour-là
 * @param int   $auPlusTot  minute à partir de laquelle on peut commencer (le jour même)
 * @return array  ['10:00' => [1, 3], …] : heure de début => barbers libres
 */
function creneauxLibres(string $ouverture, string $fermeture, int $duree, array $occupes, int $auPlusTot = 0): array
{
    $libres = [];
    $bloque = dureeBloquee($duree);
    $premier = max(minutes($ouverture), $auPlusTot);
    $premier = (int) ceil($premier / RDV_PAS) * RDV_PAS; // aligné sur les 5 minutes

    // La vraie prestation doit finir avant la fermeture ; le battement du
    // dernier client, lui, peut déborder (on range après la fermeture).
    for ($debut = $premier; $debut + $duree <= minutes($fermeture); $debut += RDV_PAS) {
        foreach ($occupes as $barber => $plages) {
            foreach ($plages as [$d, $f]) {
                // Deux plages se chevauchent si chacune commence avant la fin de l'autre
                if ($debut < $f && $debut + $bloque > $d) {
                    continue 2; // ce barber est pris : barber suivant
                }
            }
            $libres[heure($debut)][] = $barber;
        }
    }

    return $libres;
}

/**
 * « Peu importe » : parmi les barbers libres, celui qui a le moins de
 * rendez-vous ce jour-là. À égalité, le premier dans l'ordre de l'équipe.
 */
function choisirBarber(array $libres, array $occupes): int
{
    $choisi = $libres[0];
    foreach ($libres as $barber) {
        if (count($occupes[$barber]) < count($occupes[$choisi])) {
            $choisi = $barber;
        }
    }
    return $choisi;
}


/* ==========================================================================
   2. Lecture en base : fermetures et rendez-vous déjà pris
   ========================================================================== */

/**
 * Fermetures exceptionnelles entre deux dates (incluses) :
 *   ['2026-12-24' => ['salon' => false, 'barbers' => [2]], …]
 */
function fermeturesEntre(string $du, string $au): array
{
    $requete = db()->prepare('SELECT date, barber_id FROM fermetures WHERE date BETWEEN :du AND :au');
    $requete->execute(['du' => $du, 'au' => $au]);

    $fermetures = [];
    foreach ($requete->fetchAll() as $l) {
        $fermetures[$l['date']] ??= ['salon' => false, 'barbers' => []];
        if ($l['barber_id'] === null) {
            $fermetures[$l['date']]['salon'] = true;
        } else {
            $fermetures[$l['date']]['barbers'][] = (int) $l['barber_id'];
        }
    }
    return $fermetures;
}

/**
 * Plages déjà prises ce jour-là par ces barbers : [barberId => [[debut, fin], …]].
 * Chaque barber demandé a une entrée, même vide.
 */
function plagesOccupees(string $date, array $barbers): array
{
    $occupes = array_fill_keys($barbers, []);
    if ($barbers === []) {
        return $occupes;
    }

    // Un « ? » par barber : la liste reste une vraie requête préparée
    $marques = implode(', ', array_fill(0, count($barbers), '?'));
    $requete = db()->prepare(
        "SELECT barber_id, TIME_FORMAT(debut, '%H:%i') AS debut, TIME_FORMAT(fin, '%H:%i') AS fin
         FROM rendez_vous
         WHERE statut = 'confirme' AND DATE(debut) = ? AND barber_id IN ($marques)"
    );
    $requete->execute([$date, ...$barbers]);

    foreach ($requete->fetchAll() as $l) {
        $occupes[(int) $l['barber_id']][] = [minutes($l['debut']), minutes($l['fin'])];
    }
    return $occupes;
}


/* ==========================================================================
   3. Jours et créneaux proposés
   ========================================================================== */

/**
 * Jours proposés : les 3 prochaines semaines, sauf les jours de fermeture
 * (habituelle, du salon entier, ou du barber choisi).
 * Retourne ['2026-11-12' => DateTimeImmutable, …].
 */
function joursProposes(int $barber, DateTimeImmutable $maintenant): array
{
    $aujourdhui = $maintenant->setTime(0, 0);
    $dernier = $aujourdhui->modify('+' . (RDV_HORIZON_JOURS - 1) . ' days');
    $fermetures = fermeturesEntre($aujourdhui->format('Y-m-d'), $dernier->format('Y-m-d'));
    $horaires = horaires();

    $jours = [];
    for ($jour = $aujourdhui; $jour <= $dernier; $jour = $jour->modify('+1 day')) {
        $cle = $jour->format('Y-m-d');
        $fermeture = $fermetures[$cle] ?? ['salon' => false, 'barbers' => []];
        $absent = $barber !== 0 && in_array($barber, $fermeture['barbers'], true);

        if ($horaires[(int) $jour->format('N')] !== [] && !$fermeture['salon'] && !$absent) {
            $jours[$cle] = $jour;
        }
    }
    return $jours;
}

/**
 * Créneaux libres d'un jour pour un barber (ou 0 = peu importe).
 * Retourne ['libres' => [...], 'occupes' => [...]] (voir creneauxLibres()).
 */
function journee(DateTimeImmutable $jour, int $barber, int $duree, DateTimeImmutable $maintenant): array
{
    $vide = ['libres' => [], 'occupes' => []];
    $creneaux = horaires()[(int) $jour->format('N')];
    $cle = $jour->format('Y-m-d');
    $fermeture = fermeturesEntre($cle, $cle)[$cle] ?? ['salon' => false, 'barbers' => []];

    if ($creneaux === [] || $fermeture['salon']) {
        return $vide;
    }

    // Barbers présents ce jour-là (dans l'ordre de l'équipe)
    $barbers = $barber !== 0 ? [$barber] : array_map(fn ($b) => (int) $b['id'], equipe());
    $barbers = array_values(array_diff($barbers, $fermeture['barbers']));

    // Le jour même : pas avant maintenant + 30 min
    $auPlusTot = 0;
    if ($cle === $maintenant->format('Y-m-d')) {
        $limite = $maintenant->modify('+' . RDV_DELAI_MINUTES . ' minutes');
        if ($limite->format('Y-m-d') !== $cle) {
            return $vide;
        }
        $auPlusTot = minutes($limite->format('H:i'));
    }

    $occupes = plagesOccupees($cle, $barbers);
    [$ouverture, $fermetureHeure] = $creneaux[0];

    return [
        'libres'  => creneauxLibres($ouverture, $fermetureHeure, $duree, $occupes, $auPlusTot),
        'occupes' => $occupes,
    ];
}


/* ==========================================================================
   4. Lecture des choix du visiteur (dans l'adresse, ou dans le formulaire final)
   ========================================================================== */

/**
 * Analyse les choix reçus et dit à quelle étape on en est :
 *   1 prestation · 2 barber (+ motif) · 3 jour puis heure · 4 coordonnées
 *
 * Chaque choix n'est accepté que s'il fait partie de ce qu'on propose
 * réellement ; le premier choix absent ou invalide fixe l'étape.
 */
function analyserChoix(array $source, DateTimeImmutable $maintenant): array
{
    $etat = [
        'etape' => 1, 'prestation' => null, 'motif' => false, 'option' => null,
        'barber' => null, 'jours' => [], 'jour' => null, 'journee' => null, 'heure' => null,
        'duree' => 0, 'prix' => 0.0,
    ];
    $valeur = fn (string $cle) => is_string($source[$cle] ?? null) ? $source[$cle] : '';

    // --- Prestation (une vraie prestation, pas l'option) ---
    foreach (prestations() as $p) {
        if ($p['option']) {
            $etat['option'] ??= $p;
        } elseif ((string) $p['id'] === $valeur('prestation')) {
            $etat['prestation'] = $p;
        }
    }
    if ($etat['prestation'] === null) {
        return $etat;
    }

    $etat['motif'] = $valeur('motif') === '1' && $etat['prestation']['motif_possible'] && $etat['option'] !== null;
    $etat['duree'] = $etat['prestation']['duree'] + ($etat['motif'] ? $etat['option']['duree'] : 0);
    $etat['prix']  = $etat['prestation']['prix'] + ($etat['motif'] ? $etat['option']['prix'] : 0);

    // --- Barber : « 0 » (peu importe) ou un barber actif ---
    $etat['etape'] = 2;
    $ids = array_map(fn ($b) => (string) $b['id'], equipe());
    if ($valeur('barber') !== '0' && !in_array($valeur('barber'), $ids, true)) {
        return $etat;
    }
    $etat['barber'] = (int) $valeur('barber');

    // --- Jour : un des jours proposés ---
    $etat['etape'] = 3;
    $etat['jours'] = joursProposes($etat['barber'], $maintenant);
    if (!isset($etat['jours'][$valeur('jour')])) {
        return $etat;
    }
    $etat['jour'] = $etat['jours'][$valeur('jour')];

    // --- Heure : un créneau encore libre ---
    $etat['journee'] = journee($etat['jour'], $etat['barber'], $etat['duree'], $maintenant);
    if (!isset($etat['journee']['libres'][$valeur('heure')])) {
        return $etat;
    }
    $etat['heure'] = $valeur('heure');
    $etat['etape'] = 4;

    return $etat;
}

/**
 * Les choix sous forme de paramètres d'adresse, jusqu'à une étape donnée
 * (exclue) : sert aux liens « modifier » et aux champs cachés.
 */
function parametresChoix(array $etat, int $jusqua = 5): array
{
    $p = [];
    if ($jusqua > 1 && $etat['prestation']) {
        $p['prestation'] = $etat['prestation']['id'];
        if ($etat['motif']) {
            $p['motif'] = 1;
        }
    }
    if ($jusqua > 2 && $etat['barber'] !== null) {
        $p['barber'] = $etat['barber'];
    }
    if ($jusqua > 3 && $etat['jour']) {
        $p['jour'] = $etat['jour']->format('Y-m-d');
    }
    if ($jusqua > 4 && $etat['heure']) {
        $p['heure'] = $etat['heure'];
    }
    return $p;
}


/* ==========================================================================
   5. Vérification des coordonnées
   ========================================================================== */

/**
 * Vérifie prénom, téléphone, piège et jeton.
 * Retourne [$valeurs, $erreurs, $spam].
 */
function verifierCoordonnees(array $post, ?int $maintenant = null): array
{
    $valeurs = lireChamps($post, ['prenom', 'telephone']);

    // Robots : champ piège rempli, ou formulaire envoyé en moins de 3 s
    $jeton = verifierJeton(is_string($post['jeton'] ?? null) ? $post['jeton'] : '', $maintenant);
    if (($post['site_web'] ?? '') !== '' || $jeton === 'trop_rapide') {
        return [$valeurs, [], true];
    }

    $erreurs = [];
    if ($jeton === 'invalide') {
        $erreurs['formulaire'] = 'Le formulaire a expiré. Vérifie les informations puis envoie-le à nouveau.';
    }

    // Lettres (accents compris), espaces, tirets, apostrophes
    $longueur = mb_strlen($valeurs['prenom']);
    if ($longueur < 2 || $longueur > 50 || !preg_match("/^\p{L}[\p{L}\p{M}' \-’]*$/u", $valeurs['prenom'])) {
        $erreurs['prenom'] = 'Indique ton prénom (2 à 50 lettres).';
    }

    $telephone = normaliserTelephone($valeurs['telephone']);
    if ($telephone === null) {
        $erreurs['telephone'] = 'Indique un numéro de téléphone français, par exemple 06 12 34 56 78.';
    } else {
        $valeurs['telephone'] = $telephone;
    }

    return [$valeurs, $erreurs, false];
}


/* ==========================================================================
   6. Enregistrement
   ========================================================================== */

/**
 * Enregistre un rendez-vous dont les choix et les coordonnées sont vérifiés.
 *
 * Deux clients peuvent viser le même créneau au même instant. On verrouille
 * donc les lignes des barbers concernés (SELECT … FOR UPDATE) : le second
 * attend que le premier ait fini, puis revérifie le créneau et le trouve pris.
 *
 * Retourne ['id' => 12] si c'est enregistré, sinon ['erreur' => 'pris' | 'limite_tel' | 'limite_site'].
 */
function enregistrerRendezVous(array $etat, array $coordonnees, DateTimeImmutable $maintenant): array
{
    $pdo = db();
    $cle = $etat['jour']->format('Y-m-d');
    $candidats = $etat['barber'] !== 0 ? [$etat['barber']] : array_map(fn ($b) => (int) $b['id'], equipe());
    sort($candidats); // toujours le même ordre de verrouillage : pas d'interblocage

    $pdo->beginTransaction();
    try {
        $marques = implode(', ', array_fill(0, count($candidats), '?'));
        $pdo->prepare("SELECT id FROM barbers WHERE id IN ($marques) FOR UPDATE")->execute($candidats);

        // Revérification, maintenant que personne d'autre ne peut réserver ces barbers
        $journee = journee($etat['jour'], $etat['barber'], $etat['duree'], $maintenant);
        $libres = $journee['libres'][$etat['heure']] ?? [];
        if ($libres === []) {
            $pdo->rollBack();
            return ['erreur' => 'pris'];
        }

        // Pas plus de 2 rendez-vous à venir pour un même numéro
        $requete = $pdo->prepare(
            "SELECT COUNT(*) FROM rendez_vous WHERE telephone = :tel AND statut = 'confirme' AND debut >= :maintenant"
        );
        $requete->execute(['tel' => $coordonnees['telephone'], 'maintenant' => $maintenant->format('Y-m-d H:i:s')]);
        if ($requete->fetchColumn() >= RDV_MAX_A_VENIR_PAR_TEL) {
            $pdo->rollBack();
            return ['erreur' => 'limite_tel'];
        }

        // Un jeton reste valable 2 h : sans cette limite, un robot pourrait remplir l'agenda.
        // NOW() de MySQL, comme cree_le : les deux dates viennent de la même horloge.
        $recents = $pdo->query('SELECT COUNT(*) FROM rendez_vous WHERE cree_le >= NOW() - INTERVAL 1 HOUR')->fetchColumn();
        if ($recents >= RDV_MAX_PAR_HEURE) {
            $pdo->rollBack();
            error_log('[Sharp Line] rendez-vous refusé : plus de ' . RDV_MAX_PAR_HEURE . ' demandes en une heure');
            return ['erreur' => 'limite_site'];
        }

        $barber = choisirBarber($libres, $journee['occupes']);
        $debut = $etat['jour']->setTime(...array_map('intval', explode(':', $etat['heure'])));

        $requete = $pdo->prepare(
            'INSERT INTO rendez_vous (prestation_id, barber_id, debut, fin, avec_motif, prenom, telephone)
             VALUES (:prestation, :barber, :debut, :fin, :motif, :prenom, :telephone)'
        );
        $requete->execute([
            'prestation' => $etat['prestation']['id'],
            'barber'     => $barber,
            'debut'      => $debut->format('Y-m-d H:i:s'),
            // fin = fin du temps bloqué (prestation + battement, arrondi)
            'fin'        => $debut->modify('+' . dureeBloquee($etat['duree']) . ' minutes')->format('Y-m-d H:i:s'),
            'motif'      => (int) $etat['motif'],
            'prenom'     => $coordonnees['prenom'],
            'telephone'  => $coordonnees['telephone'],
        ]);
        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e; // erreur technique : page 503 (voir helpers.php)
    }

    purgerAnciensRendezVous();
    return ['id' => $id];
}

/**
 * Supprime les rendez-vous passés depuis plus d'un mois (durée annoncée dans
 * les mentions légales). Appelée à chaque nouveau rendez-vous : pas besoin
 * de tâche planifiée.
 */
function purgerAnciensRendezVous(): void
{
    try {
        $limite = (new DateTimeImmutable('today'))->modify(RDV_CONSERVATION);
        db()->prepare('DELETE FROM rendez_vous WHERE debut < :limite')
            ->execute(['limite' => $limite->format('Y-m-d')]);
    } catch (Throwable $e) {
        // Le nettoyage peut attendre la prochaine fois : on ne bloque pas le rendez-vous
        error_log('[Sharp Line] nettoyage des rendez-vous : ' . $e->getMessage());
    }
}


/* ==========================================================================
   7. Page de confirmation
   L'adresse contient le numéro du rendez-vous et sa signature :
   « 12.9f3c… ». Changer le numéro casse la signature : personne ne peut
   afficher le rendez-vous d'un autre.
   ========================================================================== */

function lienConfirmation(int $id): string
{
    return url('rendez-vous.php?confirmation=' . $id . '.' . signer('confirmation', (string) $id));
}

/**
 * Rendez-vous désigné par un lien de confirmation, ou null (lien faux ou rendez-vous supprimé).
 */
function rendezVousConfirme(string $code): ?array
{
    $morceaux = explode('.', $code, 2);
    if (count($morceaux) !== 2 || !ctype_digit($morceaux[0])
        || !hash_equals(signer('confirmation', $morceaux[0]), $morceaux[1])) {
        return null;
    }

    $requete = db()->prepare(
        'SELECT r.debut, r.fin, r.avec_motif, r.statut, p.nom AS prestation, b.prenom AS barber
         FROM rendez_vous r
         JOIN prestations p ON p.id = r.prestation_id
         JOIN barbers b ON b.id = r.barber_id
         WHERE r.id = :id'
    );
    $requete->execute(['id' => (int) $morceaux[0]]);
    return $requete->fetch() ?: null;
}
