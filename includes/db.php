<?php
/**
 * Connexion à la base de données (PDO).
 *
 * db() ouvre la connexion au premier appel, puis renvoie toujours la même :
 * une seule connexion par page, quel que soit le nombre de requêtes.
 * Si la base ne répond pas, l'exception est attrapée par le gestionnaire
 * de helpers.php, qui affiche la page 503.
 */

require_once __DIR__ . '/helpers.php';

function db(): PDO
{
    // « static » : la variable garde sa valeur d'un appel à l'autre
    static $pdo = null;

    if ($pdo === null) {
        $c = configLocale()['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['hote'], $c['port'], $c['base']
        );

        $pdo = new PDO($dsn, $c['utilisateur'], $c['mot_de_passe'], [
            // Une erreur SQL lance une exception (au lieu d'échouer en silence)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Chaque ligne arrive sous forme de tableau ['nom' => …, 'prix' => …]
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Vraies requêtes préparées : la requête et les valeurs sont envoyées
            // séparément à MySQL, une valeur ne peut donc jamais devenir du SQL.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}
