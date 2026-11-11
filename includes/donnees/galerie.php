<?php
/**
 * Photos de la galerie (contenu fixe, il ne vient pas de la base).
 * Toutes sans visage ni enseigne lisible. Format carré 800 × 800.
 * Les 4 premières sont reprises sur l'accueil.
 * Photos Unsplash : auteurs crédités dans les mentions légales et le README.
 */
function galerie(): array
{
    return [
        ['fichier' => 'degrade-dos.jpg',     'legende' => 'Dégradé net, vu de dos'],
        ['fichier' => 'contour-nuque.jpg',   'legende' => 'Contour de la nuque, au peigne et aux ciseaux'],
        ['fichier' => 'boucles-dos.jpg',     'legende' => 'Boucles définies, vues de dos'],
        ['fichier' => 'neon-salon.jpg',      'legende' => 'Le fauteuil sous les arches néon'],
        ['fichier' => 'coupe-courte.jpg',    'legende' => 'Coupe courte et nette, vue de dos'],
        ['fichier' => 'tondeuses.jpg',       'legende' => 'Les tondeuses alignées sur le poste'],
        ['fichier' => 'texture-boucles.jpg', 'legende' => 'Volume et texture sur cheveux bouclés'],
        ['fichier' => 'fauteuil.jpg',        'legende' => 'Le fauteuil, cuir noir et chrome'],
        ['fichier' => 'rasoir-blaireau.jpg', 'legende' => 'Rasoir droit et blaireau'],
        ['fichier' => 'trousse.jpg',         'legende' => 'La trousse du barber'],
        ['fichier' => 'ciseaux-peigne.jpg',  'legende' => 'Ciseaux sur le comptoir'],
        ['fichier' => 'neon-lignes.jpg',     'legende' => 'Lignes de néon bleu'],
    ];
}
