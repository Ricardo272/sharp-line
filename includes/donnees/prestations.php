<?php
/**
 * Prestations actives du salon, lues dans la table « prestations ».
 *
 * 'option' => true : s'ajoute à une coupe (motif au rasoir), affichée à part.
 * Les noms des clés sont ceux qu'attendent les pages (duree, option…).
 */
require_once __DIR__ . '/../db.php';

function prestations(): array
{
    $lignes = db()->query(
        'SELECT id, nom, description, duree_min, prix, est_option, motif_possible
         FROM prestations
         WHERE active = 1
         ORDER BY ordre, id'
    )->fetchAll();

    // MySQL renvoie DECIMAL en texte (« 20.00 ») : on convertit pour prix()
    return array_map(fn (array $l) => [
        'id'          => (int) $l['id'],
        'nom'         => $l['nom'],
        'description' => $l['description'],
        'duree'       => (int) $l['duree_min'],
        'prix'        => (float) $l['prix'],
        'option'      => (bool) $l['est_option'],
        'motif_possible' => (bool) $l['motif_possible'],
    ], $lignes);
}
