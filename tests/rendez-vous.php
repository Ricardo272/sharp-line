<?php
/**
 * Tests de la prise de rendez-vous : calcul des créneaux, choix du barber,
 * jeton anti-robots, coordonnées, et double réservation du même créneau.
 *
 * À lancer depuis la racine du projet, MySQL démarré :
 *   php tests/rendez-vous.php
 *
 * Crée deux rendez-vous de test dans trois semaines, puis les supprime.
 * Code de sortie 1 si un test échoue.
 */

if (PHP_SAPI !== 'cli') {
    exit('À lancer en ligne de commande.');
}

require_once __DIR__ . '/../includes/rendez-vous.php';
// On garde le gestionnaire d'erreurs de helpers.php : il affiche le message
// seul. Sans lui, PHP 8.1 afficherait la trace complète, mot de passe de la
// base compris, si la connexion échouait.

$echecs = 0;
function verifier(string $nom, bool $condition): void
{
    global $echecs;
    echo ($condition ? '  ok      ' : '  ÉCHEC   ') . $nom . "\n";
    $echecs += $condition ? 0 : 1;
}


echo "Créneaux\n";
// Karim (1) pris de 10:00 à 10:35, Inès (2) libre
$libres = creneauxLibres('10:00', '20:00', 20, [1 => [[600, 635]], 2 => []]);
verifier('10:00 : seule Inès est libre', $libres['10:00'] === [2]);
verifier('10:35 : les deux sont libres', $libres['10:35'] === [1, 2]);
verifier('10:30 : Karim encore pris (chevauchement)', $libres['10:30'] === [2]);
verifier('19:40 : dernier créneau (finit à 20:00)', isset($libres['19:40']) && !isset($libres['19:45']));
verifier('un créneau toutes les 5 min', isset($libres['10:05']) && !isset($libres['10:02']));

$tot = creneauxLibres('10:00', '20:00', 20, [1 => []], minutes('14:32'));
verifier('le jour même : arrondi à 14:35', array_key_first($tot) === '14:35');
verifier('aucun barber présent : aucun créneau', creneauxLibres('10:00', '20:00', 20, []) === []);
verifier('prestation plus longue que la journée : aucun créneau', creneauxLibres('10:00', '10:30', 35, [1 => []]) === []);

echo "Choix du barber (peu importe)\n";
$occupes = [1 => [[600, 620], [700, 720]], 2 => [[600, 620]], 3 => []];
verifier('le moins chargé : Tony', choisirBarber([1, 2, 3], $occupes) === 3);
verifier('à égalité : le premier de l\'équipe', choisirBarber([2, 1], [1 => [], 2 => []]) === 2);


echo "Jeton\n";
$t = time() - 10;
$jeton = $t . '.' . signer('formulaire', (string) $t);
verifier('jeton valide accepté', verifierJeton($jeton) === 'ok');
verifier('envoyé en 1 s : robot', verifierJeton($jeton, $t + 1) === 'trop_rapide');
verifier('après 3 h : expiré', verifierJeton($jeton, $t + 3 * 3600) === 'invalide');
verifier('heure modifiée : refusé', verifierJeton(($t - 100) . '.' . signer('formulaire', (string) $t)) === 'invalide');
verifier('lien de confirmation utilisé comme jeton : refusé',
    verifierJeton($t . '.' . signer('confirmation', (string) $t)) === 'invalide');


echo "Coordonnées\n";
$valide = ['prenom' => 'Inès-Marie', 'telephone' => '+33 6 12 34 56 78', 'jeton' => $jeton, 'site_web' => ''];
[$v, $e, $spam] = verifierCoordonnees($valide);
verifier('demande valide acceptée', !$e && !$spam && $v['telephone'] === '0612345678');
verifier('prénom avec chiffres refusé', isset(verifierCoordonnees(['prenom' => 'R2D2'] + $valide)[1]['prenom']));
verifier('prénom d\'une lettre refusé', isset(verifierCoordonnees(['prenom' => 'A'] + $valide)[1]['prenom']));
verifier('UTF-8 invalide refusé', isset(verifierCoordonnees(['prenom' => "Jo\xC3\x28"] + $valide)[1]['prenom']));
verifier('numéro étranger refusé', isset(verifierCoordonnees(['telephone' => '+44 20 7946 0958'] + $valide)[1]['telephone']));
verifier('champ piège rempli : robot', verifierCoordonnees(['site_web' => 'http://spam.test'] + $valide)[2]);
verifier('tableau à la place du texte : refusé sans planter',
    isset(verifierCoordonnees(['prenom' => ['x']] + $valide)[1]['prenom']));


echo "Double réservation (base de données)\n";
$maintenant = new DateTimeImmutable();
$etat = analyserChoix(['prestation' => '1', 'barber' => '0'], $maintenant);
$jour = array_key_last($etat['jours']); // le jour proposé le plus lointain
$etat = analyserChoix(['prestation' => '1', 'barber' => '1', 'jour' => $jour, 'heure' => '' ], $maintenant);
$heure = array_key_last($etat['journee']['libres']); // le dernier créneau libre
$etat = analyserChoix(['prestation' => '1', 'barber' => '1', 'jour' => $jour, 'heure' => $heure], $maintenant);
verifier('choix complets : étape 4', $etat['etape'] === 4);

$crees = [];
$premier = enregistrerRendezVous($etat, ['prenom' => 'Test', 'telephone' => '0199000001'], $maintenant);
$crees[] = $premier['id'] ?? null;
verifier('premier rendez-vous enregistré', isset($premier['id']));

$second = enregistrerRendezVous($etat, ['prenom' => 'Test', 'telephone' => '0199000002'], $maintenant);
$crees[] = $second['id'] ?? null;
verifier('même créneau, même barber : refusé', ($second['erreur'] ?? '') === 'pris');

$apres = analyserChoix(['prestation' => '1', 'barber' => '1', 'jour' => $jour, 'heure' => $heure], $maintenant);
verifier('le créneau n\'est plus proposé', $apres['etape'] === 3);
verifier('lien de confirmation valide', rendezVousConfirme(explode('=', lienConfirmation($premier['id']))[1])['barber'] === 'Karim');
verifier('lien de confirmation modifié : refusé', rendezVousConfirme(($premier['id'] + 1) . '.abc') === null);

// Nettoyage
$ids = array_filter($crees);
if ($ids) {
    db()->prepare('DELETE FROM rendez_vous WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')')
        ->execute(array_values($ids));
}


echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en échec.\n";
exit($echecs === 0 ? 0 : 1);
