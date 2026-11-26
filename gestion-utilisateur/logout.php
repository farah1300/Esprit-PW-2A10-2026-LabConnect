<?php
/**
 * Déconnexion — point d'entrée de la page.
 *
 * Toute la logique (destruction de la session + suppression du cookie)
 * est dans UtilisateurController::logout() pour rester centralisée.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/controllers/UtilisateurController.php';

$controller = new UtilisateurController($pdo);
$controller->logout();
