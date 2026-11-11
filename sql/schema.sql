-- ==========================================================================
-- Structure de la base Sharp Line
-- À exécuter en root, dans la base sharp_line, UNE SEULE FOIS à l'installation.
-- ATTENTION : supprime les tables existantes et toutes leurs données,
-- Y COMPRIS LES RENDEZ-VOUS ET LES COMPTES D'ADMINISTRATION.
-- ==========================================================================

SET NAMES utf8mb4;

-- On supprime d'abord les tables qui dépendent des autres (clés étrangères)
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS rendez_vous;
DROP TABLE IF EXISTS fermetures;
DROP TABLE IF EXISTS horaires;
DROP TABLE IF EXISTS barbers;
DROP TABLE IF EXISTS prestations;


-- --------------------------------------------------------------------------
-- Prestations
-- Les prix sont en DECIMAL (valeur exacte), jamais en FLOAT (valeur approchée).
-- est_option : s'ajoute à une coupe (motif au rasoir) au lieu d'être choisie seule.
-- motif_possible : l'option motif peut s'ajouter à cette prestation (pas à la barbe seule).
-- active = 0 : masquée du site, mais les anciens rendez-vous la gardent.
-- --------------------------------------------------------------------------
CREATE TABLE prestations (
    id              SMALLINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nom             VARCHAR(80)        NOT NULL,
    description     VARCHAR(255)       NOT NULL DEFAULT '',
    duree_min       TINYINT UNSIGNED   NOT NULL,            -- en minutes
    prix            DECIMAL(6,2)       NOT NULL,
    est_option      TINYINT(1)         NOT NULL DEFAULT 0,
    motif_possible  TINYINT(1)         NOT NULL DEFAULT 1,
    ordre        TINYINT UNSIGNED   NOT NULL DEFAULT 0,  -- ordre d'affichage
    active       TINYINT(1)         NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------------------------
-- Barbers
-- photo : chemin dans assets/img/ ; photo_alt : sa description.
-- --------------------------------------------------------------------------
CREATE TABLE barbers (
    id          SMALLINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    prenom      VARCHAR(50)        NOT NULL,
    role        VARCHAR(50)        NOT NULL DEFAULT 'Barber',
    specialite  VARCHAR(120)       NOT NULL,
    phrase      VARCHAR(255)       NOT NULL DEFAULT '',
    photo       VARCHAR(120)       NOT NULL DEFAULT '',
    photo_alt   VARCHAR(160)       NOT NULL DEFAULT '',
    ordre       TINYINT UNSIGNED   NOT NULL DEFAULT 0,
    actif       TINYINT(1)         NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------------------------
-- Horaires habituels : une ligne par jour, numérotés comme date('N') en PHP
-- (1 = lundi … 7 = dimanche). Ouverture et fermeture vides = fermé ce jour-là.
-- --------------------------------------------------------------------------
CREATE TABLE horaires (
    jour        TINYINT UNSIGNED  NOT NULL,
    ouverture   TIME              NULL,
    fermeture   TIME              NULL,
    PRIMARY KEY (jour),
    CONSTRAINT ck_horaires_jour CHECK (jour BETWEEN 1 AND 7),
    -- les deux heures sont remplies ensemble, ou vides ensemble
    CONSTRAINT ck_horaires_heures CHECK (
        (ouverture IS NULL AND fermeture IS NULL)
        OR (ouverture IS NOT NULL AND fermeture IS NOT NULL AND ouverture < fermeture)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------------------------
-- Fermetures exceptionnelles (jour férié, congé…)
-- barber_id vide : tout le salon est fermé ; sinon, seul ce barber est absent.
-- --------------------------------------------------------------------------
CREATE TABLE fermetures (
    id          INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    date        DATE               NOT NULL,
    barber_id   SMALLINT UNSIGNED  NULL,
    motif       VARCHAR(120)       NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_fermetures_date (date),
    CONSTRAINT fk_fermetures_barber FOREIGN KEY (barber_id) REFERENCES barbers (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------------------------
-- Rendez-vous
-- debut et fin : la fin est calculée à la réservation (durée de la prestation,
-- + 5 min si motif). Un créneau est libre si aucun rendez-vous confirmé du
-- même barber ne le chevauche.
-- RGPD : seulement le prénom et le téléphone, pas d'adresse IP. Les lignes sont
-- supprimées automatiquement un mois après la date (phase 5).
-- --------------------------------------------------------------------------
CREATE TABLE rendez_vous (
    id              INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    prestation_id   SMALLINT UNSIGNED  NOT NULL,
    barber_id       SMALLINT UNSIGNED  NOT NULL,
    debut           DATETIME           NOT NULL,
    fin             DATETIME           NOT NULL,
    avec_motif      TINYINT(1)         NOT NULL DEFAULT 0,
    prenom          VARCHAR(50)        NOT NULL,
    telephone       VARCHAR(20)        NOT NULL,
    statut          ENUM('confirme', 'annule') NOT NULL DEFAULT 'confirme',
    cree_le         DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    -- recherche des créneaux pris d'un barber sur une journée
    KEY idx_rdv_barber_debut (barber_id, debut),
    -- tableau de bord : rendez-vous du jour, de la semaine
    KEY idx_rdv_debut (debut),
    CONSTRAINT ck_rdv_horaires CHECK (debut < fin),
    -- RESTRICT : impossible de supprimer une prestation ou un barber qui a
    -- des rendez-vous ; on les désactive à la place (colonne active / actif).
    CONSTRAINT fk_rdv_prestation FOREIGN KEY (prestation_id) REFERENCES prestations (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_rdv_barber FOREIGN KEY (barber_id) REFERENCES barbers (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------------------------
-- Comptes du tableau de bord (phase 7), créés en ligne de commande :
-- php outils/creer-admin.php <identifiant>
-- echecs / bloque_jusqua : blocage après 5 mots de passe faux.
-- --------------------------------------------------------------------------
CREATE TABLE admins (
    id               SMALLINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    identifiant      VARCHAR(50)        NOT NULL,
    mot_de_passe     VARCHAR(255)       NOT NULL,  -- hash password_hash(), jamais en clair
    echecs           TINYINT UNSIGNED   NOT NULL DEFAULT 0,
    bloque_jusqua    DATETIME           NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_admins_identifiant (identifiant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
