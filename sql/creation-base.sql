-- ==========================================================================
-- Création de la base et de l'utilisateur dédié : à exécuter UNE FOIS, en root.
--
-- Remplacer CHANGER_CE_MOT_DE_PASSE par un vrai mot de passe,
-- puis reporter ce même mot de passe dans config/config.local.php.
-- ==========================================================================

-- utf8mb4 : le « vrai » UTF-8 de MySQL (accents, œ, €, et même les émojis).
CREATE DATABASE IF NOT EXISTS sharp_line
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Utilisateur réservé au site : il ne peut que lire et modifier des données.
-- Il ne peut ni créer, ni supprimer de tables, ni toucher aux autres bases.
CREATE USER IF NOT EXISTS 'sharp_line'@'localhost' IDENTIFIED BY 'CHANGER_CE_MOT_DE_PASSE';

GRANT SELECT, INSERT, UPDATE, DELETE ON sharp_line.* TO 'sharp_line'@'localhost';
