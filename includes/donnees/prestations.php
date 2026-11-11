<?php
/**
 * Prestations du salon.
 *
 * shortcut: liste écrite en dur en phase 3 ; en phase 4, l'intérieur de la
 * fonction devient une requête SQL, les pages qui l'appellent ne changent pas.
 *
 * 'option' => true : s'ajoute à une coupe (motif au rasoir), affichée à part.
 */
function prestations(): array
{
    return [
        ['nom' => 'Coupe homme', 'duree' => 20, 'prix' => 20, 'option' => false,
         'description' => 'Ciseaux ou tondeuse, finitions au rasoir, coiffage.'],
        ['nom' => 'Coupe + barbe', 'duree' => 35, 'prix' => 25, 'option' => false,
         'description' => 'La coupe complète, puis la barbe taillée et dessinée au rasoir.'],
        ['nom' => 'Taille de barbe', 'duree' => 15, 'prix' => 10, 'option' => false,
         'description' => 'Longueur, contours nets, serviette chaude.'],
        ['nom' => 'Coupe enfant (−12 ans)', 'duree' => 15, 'prix' => 15, 'option' => false,
         'description' => 'Une coupe rapide et soignée, sans stress pour les plus petits.'],
        ['nom' => 'Motif / trait au rasoir', 'duree' => 5, 'prix' => 5, 'option' => true,
         'description' => 'Un trait ou un motif dessiné sur le côté, en plus d\'une coupe.'],
    ];
}
