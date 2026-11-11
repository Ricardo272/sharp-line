<?php
/**
 * Crée un compte du tableau de bord, ou change son mot de passe.
 *
 * Utilisation, depuis la racine du projet :
 *   php outils/creer-admin.php karim              crée « karim » (ou change son mot de passe)
 *   php outils/creer-admin.php --supprimer test   supprime le compte « test »
 *
 * Le mot de passe est demandé au clavier (12 caractères minimum).
 * Attention : la console Windows l'affiche pendant la frappe.
 * Pour un usage automatisé, il peut aussi être passé dans la variable
 * d'environnement ADMIN_MDP.
 */

if (PHP_SAPI !== 'cli') {
    exit('À lancer en ligne de commande.');
}

require_once __DIR__ . '/../includes/db.php';

const MDP_LONGUEUR_MIN = 12;

// Connexion testée d'emblée : sans ce try, l'erreur afficherait une trace
// contenant le mot de passe de la base (avec Xdebug ou display_errors).
try {
    db();
} catch (Throwable $e) {
    fwrite(STDERR, "La base ne répond pas : vérifiez que MySQL est démarré.\n");
    exit(1);
}

$supprimer   = in_array('--supprimer', $argv, true);
$arguments   = array_values(array_filter(array_slice($argv, 1), fn ($a) => !str_starts_with($a, '--')));
$identifiant = $arguments[0] ?? '';

if (!preg_match('/^[a-z0-9._-]{3,40}$/i', $identifiant)) {
    fwrite(STDERR, "Indiquez un identifiant (3 à 40 caractères : lettres, chiffres, . _ -).\n"
                 . "Exemple : php outils/creer-admin.php karim\n");
    exit(1);
}

// --- Suppression ---
if ($supprimer) {
    $requete = db()->prepare('DELETE FROM admins WHERE identifiant = :identifiant');
    $requete->execute(['identifiant' => $identifiant]);
    echo $requete->rowCount() ? "Compte « $identifiant » supprimé.\n" : "Aucun compte « $identifiant ».\n";
    exit(0);
}

// --- Mot de passe ---
$motDePasse = getenv('ADMIN_MDP');

if ($motDePasse === false || $motDePasse === '') {
    echo "Mot de passe pour « $identifiant » (" . MDP_LONGUEUR_MIN . " caractères minimum) : ";
    $motDePasse = rtrim((string) fgets(STDIN), "\r\n");
    echo "Confirmez le mot de passe : ";
    $confirmation = rtrim((string) fgets(STDIN), "\r\n");

    if ($motDePasse !== $confirmation) {
        fwrite(STDERR, "Les deux mots de passe ne correspondent pas. Rien n'a été modifié.\n");
        exit(1);
    }
}

if (mb_strlen($motDePasse) < MDP_LONGUEUR_MIN) {
    fwrite(STDERR, 'Mot de passe trop court (' . MDP_LONGUEUR_MIN . " caractères minimum). Rien n'a été modifié.\n");
    exit(1);
}

// --- Enregistrement : création, ou mise à jour si le compte existe déjà ---
$requete = db()->prepare(
    'INSERT INTO admins (identifiant, mot_de_passe) VALUES (:identifiant, :mdp)
     ON DUPLICATE KEY UPDATE mot_de_passe = VALUES(mot_de_passe), echecs = 0, bloque_jusqua = NULL'
);
$requete->execute([
    'identifiant' => $identifiant,
    'mdp'         => password_hash($motDePasse, PASSWORD_DEFAULT), // jamais le mot de passe en clair
]);

// rowCount() : 1 = ligne créée, 2 = ligne existante mise à jour (convention MySQL)
echo $requete->rowCount() === 1
    ? "Compte « $identifiant » créé.\n"
    : "Mot de passe de « $identifiant » mis à jour (et blocage éventuel levé).\n";
