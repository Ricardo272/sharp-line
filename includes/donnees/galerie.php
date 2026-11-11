<?php
/**
 * Photos de la galerie (contenu fixe, il ne vient pas de la base).
 * Toutes sans visage ni enseigne lisible. Format carré 800 × 800.
 * Les 4 premières sont reprises sur l'accueil.
 */
function galerie(): array
{
    return [
        ['fichier' => 'degrade-dos.jpg',     'legende' => 'Dégradé américain, vu de dos'],
        ['fichier' => 'motif-rasoir.jpg',    'legende' => 'Motif tracé au rasoir sur le côté'],
        ['fichier' => 'boucles-profil.jpg',  'legende' => 'Boucles définies, vue de profil'],
        ['fichier' => 'neon-salon.jpg',      'legende' => 'Le néon bleu du salon'],
        ['fichier' => 'contour-nuque.jpg',   'legende' => 'Contour net de la nuque'],
        ['fichier' => 'outils.jpg',          'legende' => 'Tondeuses et rasoirs alignés sur le poste'],
        ['fichier' => 'texture-crepus.jpg',  'legende' => 'Coupe texturée sur cheveux crépus'],
        ['fichier' => 'fauteuil.jpg',        'legende' => 'Le fauteuil, cuir noir et chrome'],
        ['fichier' => 'coupe-enfant.jpg',    'legende' => 'Coupe enfant, vue de dos'],
        ['fichier' => 'barbe-profil.jpg',    'legende' => 'Barbe taillée, vue de profil'],
        ['fichier' => 'ciseaux-peigne.jpg',  'legende' => 'Ciseaux et peigne sur une serviette'],
        ['fichier' => 'miroir-neon.jpg',     'legende' => 'Les néons dans le miroir'],
    ];
}
