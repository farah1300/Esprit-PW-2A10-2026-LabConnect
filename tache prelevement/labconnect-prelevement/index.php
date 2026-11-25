<?php
require_once "controllers/AdresseController.php";

$action = $_GET['action'] ?? 'adresse_liste';
$id = (int)($_GET['id'] ?? 0);
$controller = new AdresseController();

switch ($action) {
    case 'adresse_liste':     $controller->liste(); break;
    case 'adresse_ajouter':   $controller->ajouter(); break;
    case 'adresse_modifier':  $controller->modifier($id); break;
    case 'adresse_supprimer': $controller->supprimer($id); break;
    default: echo "Page introuvable.";
}