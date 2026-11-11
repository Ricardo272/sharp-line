<?php
// Administration : connexion

require_once __DIR__ . '/../includes/admin/auth.php';

// Déjà connecté : direction l'agenda
if (adminConnecte()) {
    rediriger('admin/');
}

$identifiant = '';
$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Le jeton CSRF protège aussi la connexion : sans lui, un autre site pourrait
    // te connecter à SON compte à ton insu (« login CSRF »).
    verifierCsrf();

    $identifiant = is_string($_POST['identifiant'] ?? null) ? trim($_POST['identifiant']) : '';
    $motDePasse  = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';

    $resultat = connecter($identifiant, $motDePasse);

    if ($resultat === 'ok') {
        rediriger('admin/');
    }

    // Même message pour un identifiant inconnu ou un mauvais mot de passe.
    // Seul « Trop de tentatives » révèle qu'un compte existe (assumé : voir auth.php).
    $erreur = $resultat === 'bloque'
        ? 'Trop de tentatives. Réessaie dans ' . ADMIN_BLOCAGE_MINUTES . ' minutes.'
        : 'Identifiant ou mot de passe incorrect.';
}

$titreAdmin = 'Connexion';
$pageAdmin  = '';
$admin      = null;
require __DIR__ . '/../includes/admin/haut.php';
?>

<section class="connexion" aria-labelledby="titre-connexion">
    <div class="neon-bloc connexion__boite">
        <p class="neon-texte"><?= e($site['nom']) ?></p>
        <h1 id="titre-connexion" class="connexion__titre">Administration</h1>

<?php if ($erreur): ?>
        <p class="erreurs" role="alert"><?= e($erreur) ?></p>
<?php endif; ?>

        <form class="formulaire" method="post" action="<?= e(url('admin/connexion.php')) ?>">
            <?= champCsrf() ?>

            <div class="champ">
                <label for="identifiant">Identifiant</label>
                <input type="text" id="identifiant" name="identifiant" value="<?= e($identifiant) ?>"
                       required autocomplete="username" autocapitalize="none" spellcheck="false">
            </div>

            <div class="champ">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password">
            </div>

            <button class="bouton" type="submit">Se connecter</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/admin/bas.php'; ?>
