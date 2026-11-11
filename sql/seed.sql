-- ==========================================================================
-- Données de départ de Sharp Line
-- À exécuter UNE SEULE FOIS, juste après schema.sql.
-- ATTENTION : vide les tables avant de les remplir.
-- ==========================================================================

SET NAMES utf8mb4;

-- Vidage dans l'ordre inverse des dépendances
DELETE FROM rendez_vous;
DELETE FROM fermetures;
DELETE FROM horaires;
DELETE FROM barbers;
DELETE FROM prestations;


-- Prestations (le motif est une option qui s'ajoute à une coupe)
INSERT INTO prestations (id, nom, description, duree_min, prix, est_option, ordre) VALUES
(1, 'Coupe homme',             'Ciseaux ou tondeuse, finitions au rasoir, coiffage.',              20, 20.00, 0, 1),
(2, 'Coupe + barbe',           'La coupe complète, puis la barbe taillée et dessinée au rasoir.',  35, 25.00, 0, 2),
(3, 'Taille de barbe',         'Longueur, contours nets, serviette chaude.',                       15, 10.00, 0, 3),
(4, 'Coupe enfant (−12 ans)',  'Une coupe rapide et soignée, sans stress pour les plus petits.',   15, 15.00, 0, 4),
(5, 'Motif / trait au rasoir', 'Un trait ou un motif dessiné sur le côté, en plus d''une coupe.',  5,  5.00, 1, 5);


-- Barbers (photos sans visage : mains, outils, poste)
INSERT INTO barbers (id, prenom, role, specialite, phrase, photo, photo_alt, ordre) VALUES
(1, 'Karim', 'Fondateur', 'Dégradés et contours au rasoir',
    'Le rasoir, c''est sa signature : chaque contour tombe au millimètre.',
    'equipe/karim.jpg', 'Les mains de Karim tenant un rasoir droit', 1),
(2, 'Inès', 'Barber', 'Coupes texturées, cheveux bouclés et crépus',
    'Elle travaille la texture au lieu de la combattre.',
    'equipe/ines.jpg', 'Peigne afro et ciseaux posés sur le poste d''Inès', 2),
(3, 'Tony', 'Barber', 'Coupes enfants et ados',
    'Patient avec les plus petits, précis avec les plus grands.',
    'equipe/tony.jpg', 'Tondeuse posée sur le poste de Tony', 3);


-- Horaires : mardi–vendredi 10h–20h, samedi 9h–19h, fermé dimanche et lundi
INSERT INTO horaires (jour, ouverture, fermeture) VALUES
(1, NULL,    NULL),
(2, '10:00', '20:00'),
(3, '10:00', '20:00'),
(4, '10:00', '20:00'),
(5, '10:00', '20:00'),
(6, '09:00', '19:00'),
(7, NULL,    NULL);
