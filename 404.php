<?php
// Page d'erreur 404, affichée par Apache pour toute adresse inconnue (voir .htaccess).
// Squelette de la phase 1 : la version soignée arrive en phase 3.

// Indispensable : sans ce code, le navigateur et Google recevraient « 200 OK »
// et prendraient cette page d'erreur pour une vraie page du site.
http_response_code(404);

$pageTitle   = 'Page introuvable';
$pageNoIndex = true;

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main id="contenu">
    <section class="section">
        <div class="conteneur">
            <p>Erreur 404</p>
            <h1>Cette ligne ne mène nulle part.</h1>
            <p><a href="<?= e(url()) ?>">Retour à l'accueil</a></p>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
