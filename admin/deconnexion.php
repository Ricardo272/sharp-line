<?php
// Administration : déconnexion (uniquement par formulaire POST, avec jeton CSRF)

require_once __DIR__ . '/../includes/admin/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('admin/');
}

verifierCsrf();
deconnecter();
rediriger('admin/connexion.php');
