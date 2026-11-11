<?php
/**
 * Remplit l'agenda des 7 prochains jours avec des rendez-vous fictifs,
 * pour que le tableau de bord (et ses captures d'écran) ne soit pas vide.
 *
 * Utilisation, depuis la racine du projet :
 *   php outils/remplir-demo.php           ajoute des rendez-vous de démonstration
 *   php outils/remplir-demo.php --vider   supprime tous les rendez-vous de démonstration
 *
 * Seulement en mode démo ('demo' => true dans config/site.php).
 * Les rendez-vous de démonstration se reconnaissent à leur numéro : 01 99 00 9x xx
 * (tranche réservée à la fiction par l'ARCEP).
 */

if (PHP_SAPI !== 'cli') {
    exit('À lancer en ligne de commande.');
}

require_once __DIR__ . '/../includes/rendez-vous.php';

if (empty($site['demo'])) {
    fwrite(STDERR, "Le site n'est pas en mode démo : rien n'a été fait.\n");
    exit(1);
}

if (in_array('--vider', $argv, true)) {
    $requete = db()->prepare("DELETE FROM rendez_vous WHERE telephone LIKE '0199009%'");
    $requete->execute();
    echo $requete->rowCount() . " rendez-vous de démonstration supprimés.\n";
    exit(0);
}

$prenoms = ['Yanis', 'Moussa', 'Léo', 'Adam', 'Sofiane', 'Hugo', 'Ibrahim', 'Nathan', 'Rayan', 'Malik',
            'Théo', 'Samir', 'Enzo', 'Bilal', 'Lucas', 'Kenzo', 'Mehdi', 'Noah', 'Amine', 'Jules'];
$coupes = array_values(array_filter(prestations(), fn ($p) => !$p['option']));
$option = array_values(array_filter(prestations(), fn ($p) => $p['option']))[0] ?? null;

$maintenant = new DateTimeImmutable();
$insertion = db()->prepare(
    // cree_le à la veille : sinon ces rendez-vous compteraient dans la limite
    // de 20 réservations par heure et bloqueraient le formulaire public
    'INSERT INTO rendez_vous (prestation_id, barber_id, debut, fin, avec_motif, prenom, telephone, statut, cree_le)
     VALUES (:prestation, :barber, :debut, :fin, :motif, :prenom, :telephone, :statut, NOW() - INTERVAL 1 DAY)'
);

$total = 0;
$jours = array_slice(joursProposes(0, $maintenant), 0, 7, true);

foreach ($jours as $jour) {
    foreach (equipe() as $barber) {
        // 4 à 7 rendez-vous par barber et par jour, à des heures libres tirées au hasard
        for ($n = random_int(4, 7); $n > 0; $n--) {
            $coupe = $coupes[array_rand($coupes)];
            $motif = $option && $coupe['motif_possible'] && random_int(1, 5) === 1;
            $duree = $coupe['duree'] + ($motif ? $option['duree'] : 0);

            $libres = array_keys(journee($jour, (int) $barber['id'], $duree, $maintenant)['libres']);
            if ($libres === []) {
                break; // journée pleine pour ce barber
            }

            $heure = $libres[array_rand($libres)];
            $debut = $jour->setTime(...array_map('intval', explode(':', $heure)));
            $insertion->execute([
                'prestation' => $coupe['id'],
                'barber'     => $barber['id'],
                'debut'      => $debut->format('Y-m-d H:i:s'),
                'fin'        => $debut->modify('+' . dureeBloquee($duree) . ' minutes')->format('Y-m-d H:i:s'),
                'motif'      => (int) $motif,
                'prenom'     => $prenoms[array_rand($prenoms)],
                'telephone'  => '0199009' . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT),
                'statut'     => random_int(1, 10) === 1 ? 'annule' : 'confirme', // quelques annulations
            ]);
            $total++;
        }
    }
}

echo "$total rendez-vous de démonstration ajoutés sur " . count($jours) . " jours.\n";
