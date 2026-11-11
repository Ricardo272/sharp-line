<?php
/**
 * Les barbers du salon.
 *
 * shortcut: liste écrite en dur en phase 3 ; en phase 4, l'intérieur de la
 * fonction devient une requête SQL, les pages qui l'appellent ne changent pas.
 *
 * Photos sans visage : les mains, les outils, le poste de chacun.
 */
function equipe(): array
{
    return [
        ['prenom' => 'Karim', 'role' => 'Fondateur',
         'specialite' => 'Dégradés et contours au rasoir',
         'phrase' => 'Le rasoir, c\'est sa signature : chaque contour tombe au millimètre.',
         'photo' => 'equipe/karim.jpg', 'alt' => 'Les mains de Karim tenant un rasoir droit'],
        ['prenom' => 'Inès', 'role' => 'Barber',
         'specialite' => 'Coupes texturées, cheveux bouclés et crépus',
         'phrase' => 'Elle travaille la texture au lieu de la combattre.',
         'photo' => 'equipe/ines.jpg', 'alt' => 'Peigne afro et ciseaux posés sur le poste d\'Inès'],
        ['prenom' => 'Tony', 'role' => 'Barber',
         'specialite' => 'Coupes enfants et ados',
         'phrase' => 'Patient avec les plus petits, précis avec les plus grands.',
         'photo' => 'equipe/tony.jpg', 'alt' => 'Tondeuse posée sur le poste de Tony'],
    ];
}
