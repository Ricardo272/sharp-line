<?php
/**
 * Informations du salon, utilisées sur tout le site.
 *
 * Ce fichier ne contient rien de secret : il est versionné dans Git.
 * L'adresse du site, les identifiants de la base et la clé secrète
 * sont dans config.local.php (exclu de Git).
 */

return [
    'nom'    => 'Sharp Line',
    'slogan' => 'Des lignes nettes, un style à toi.',

    // Racine du site. « / » sur sharp-line.test ; à changer si le site
    // est un jour hébergé dans un sous-dossier.
    'base_url' => '/',

    // Site de démonstration (portfolio) : bandeau « aucun vrai rendez-vous
    // n'est pris » et contenu jamais vide. Mettre false pour un vrai salon.
    'demo' => true,

    'description' => 'Sharp Line, barber shop urbain à Belleville (Paris 20e) : '
                   . 'dégradés, contours au rasoir, coupes texturées et bouclées, coupes enfants.',

    'adresse' => [
        'rue'         => '12, passage des Graffeurs', // rue inventée
        'code_postal' => '75020',
        'ville'       => 'Paris',
    ],
    'metro' => 'Belleville (lignes 2 et 11)',

    // Numéro fictif : la tranche 01 99 00 xx xx est réservée par l'ARCEP aux
    // œuvres de fiction (plan national de numérotation) : elle n'appartient à personne.
    'telephone' => '01 99 00 42 17',
    // Le domaine .test est réservé : il ne peut appartenir à personne.
    'email'     => 'contact@sharpline.test',

    // Horaires par jour, numérotés comme date('N') en PHP : 1 = lundi … 7 = dimanche.
    // Chaque jour contient une liste de créneaux [ouverture, fermeture] ; vide = fermé.
    // shortcut: horaires fixes ici en phase 1, ils passeront en base en phase 4.
    'horaires' => [
        1 => [],
        2 => [['10:00', '20:00']],
        3 => [['10:00', '20:00']],
        4 => [['10:00', '20:00']],
        5 => [['10:00', '20:00']],
        6 => [['09:00', '19:00']],
        7 => [],
    ],

    // Liens du menu principal. « id » permet de repérer la page en cours.
    'navigation' => [
        ['id' => 'accueil',     'label' => 'Accueil',      'url' => ''],
        ['id' => 'prestations', 'label' => 'Prestations',  'url' => 'prestations.php'],
        ['id' => 'equipe',      'label' => "L'équipe",     'url' => 'equipe.php'],
        ['id' => 'galerie',     'label' => 'Galerie',      'url' => 'galerie.php'],
        ['id' => 'contact',     'label' => 'Contact',      'url' => 'contact.php'],
    ],
    // Bouton mis à part dans le menu (appel à l'action)
    'rendez_vous' => ['id' => 'rendez-vous', 'label' => 'Prendre rendez-vous', 'url' => 'rendez-vous.php'],
];
