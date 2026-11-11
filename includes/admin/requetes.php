<?php
/**
 * Tout le SQL du tableau de bord : agenda, prestations, fermetures.
 * Requêtes préparées partout : une valeur saisie ne devient jamais du SQL.
 */

require_once __DIR__ . '/../db.php';


/* ==========================================================================
   Agenda
   ========================================================================== */

/**
 * Rendez-vous (confirmés et annulés) entre deux dates : du inclus, au exclu.
 * $barber = 0 : tous les barbers.
 */
function rendezVousEntre(string $du, string $au, int $barber = 0): array
{
    $sql = 'SELECT r.id, r.debut, r.avec_motif, r.prenom, r.telephone, r.statut,
                   p.nom AS prestation, b.id AS barber_id, b.prenom AS barber
            FROM rendez_vous r
            JOIN prestations p ON p.id = r.prestation_id
            JOIN barbers b ON b.id = r.barber_id
            WHERE r.debut >= :du AND r.debut < :au';
    $parametres = ['du' => $du, 'au' => $au];

    if ($barber !== 0) {
        $sql .= ' AND r.barber_id = :barber';
        $parametres['barber'] = $barber;
    }

    $requete = db()->prepare($sql . ' ORDER BY r.debut, b.ordre');
    $requete->execute($parametres);
    return $requete->fetchAll();
}

/**
 * Annule un rendez-vous confirmé. Retourne false s'il n'existe pas ou l'est déjà.
 * On garde la ligne (statut « annule ») : le créneau se libère, l'historique reste.
 */
function annulerRendezVous(int $id): bool
{
    $requete = db()->prepare("UPDATE rendez_vous SET statut = 'annule' WHERE id = :id AND statut = 'confirme'");
    $requete->execute(['id' => $id]);
    return $requete->rowCount() === 1;
}


/* ==========================================================================
   Prestations
   ========================================================================== */

/** Toutes les prestations, y compris masquées. */
function toutesPrestations(): array
{
    return db()->query('SELECT id, nom, duree_min, prix, est_option, active FROM prestations ORDER BY ordre, id')->fetchAll();
}

function modifierPrestation(int $id, float $prix, int $duree, bool $active): void
{
    db()->prepare('UPDATE prestations SET prix = :prix, duree_min = :duree, active = :active WHERE id = :id')
        ->execute(['prix' => $prix, 'duree' => $duree, 'active' => (int) $active, 'id' => $id]);
}


/* ==========================================================================
   Fermetures exceptionnelles
   ========================================================================== */

/** Fermetures à partir d'aujourd'hui, avec le prénom du barber (null = tout le salon). */
function fermeturesAVenir(): array
{
    return db()->query(
        'SELECT f.id, f.date, f.motif, b.prenom AS barber
         FROM fermetures f
         LEFT JOIN barbers b ON b.id = f.barber_id
         WHERE f.date >= CURDATE()
         ORDER BY f.date, b.ordre'
    )->fetchAll();
}

/**
 * Ajoute une fermeture. Retourne le nombre de rendez-vous déjà pris ce jour-là
 * par les personnes concernées : ils ne sont PAS annulés automatiquement,
 * le salon doit prévenir les clients d'abord.
 */
function ajouterFermeture(string $date, ?int $barber, string $motif): int
{
    db()->prepare('INSERT INTO fermetures (date, barber_id, motif) VALUES (:date, :barber, :motif)')
        ->execute(['date' => $date, 'barber' => $barber, 'motif' => $motif]);

    $sql = "SELECT COUNT(*) FROM rendez_vous WHERE statut = 'confirme' AND DATE(debut) = :date";
    $parametres = ['date' => $date];
    if ($barber !== null) {
        $sql .= ' AND barber_id = :barber';
        $parametres['barber'] = $barber;
    }
    $requete = db()->prepare($sql);
    $requete->execute($parametres);
    return (int) $requete->fetchColumn();
}

function supprimerFermeture(int $id): void
{
    db()->prepare('DELETE FROM fermetures WHERE id = :id')->execute(['id' => $id]);
}
