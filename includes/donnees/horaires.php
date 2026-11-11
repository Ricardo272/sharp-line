<?php
/**
 * Horaires habituels du salon, lus dans la table « horaires ».
 *
 * Retourne un tableau indexé par jour (1 = lundi … 7 = dimanche) :
 *   [2 => [['10:00', '20:00']], 1 => [] (fermé), …]
 * Une liste de créneaux par jour : le format attendu par resumerHoraires().
 * Lu une seule fois par page (en-tête, appel au rendez-vous, pied de page).
 */
require_once __DIR__ . '/../db.php';

function horaires(): array
{
    static $horaires = null;

    if ($horaires === null) {
        $horaires = array_fill(1, 7, []);
        $lignes = db()->query(
            "SELECT jour, TIME_FORMAT(ouverture, '%H:%i') AS ouverture, TIME_FORMAT(fermeture, '%H:%i') AS fermeture
             FROM horaires"
        )->fetchAll();

        foreach ($lignes as $l) {
            if ($l['ouverture'] !== null) {
                $horaires[(int) $l['jour']] = [[$l['ouverture'], $l['fermeture']]];
            }
        }
    }

    return $horaires;
}
