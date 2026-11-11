<?php
/**
 * Barbers actifs du salon, lus dans la table « barbers ».
 * Photos sans visage : les mains, les outils, le poste de chacun.
 */
require_once __DIR__ . '/../db.php';

function equipe(): array
{
    return db()->query(
        'SELECT id, prenom, role, specialite, phrase, photo, photo_alt AS alt
         FROM barbers
         WHERE actif = 1
         ORDER BY ordre, id'
    )->fetchAll();
}
