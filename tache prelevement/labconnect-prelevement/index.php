<?php
require_once "controllers/AdresseController.php";
require_once "controllers/PrelevementController.php";

$action = $_GET['action'] ?? 'prelevement_liste';
$id = (int)($_GET['id'] ?? 0);

$adresseController = new AdresseController();
$prelevementController = new PrelevementController();

switch ($action) {
    // Adresses
    case 'adresse_liste':          $adresseController->liste(); break;
    case 'adresse_ajouter':        $adresseController->ajouter(); break;
    case 'adresse_modifier':       $adresseController->modifier($id); break;
    case 'adresse_supprimer':      $adresseController->supprimer($id); break;

    // Prelevements
    case 'prelevement_liste':      $prelevementController->liste(); break;
    case 'prelevement_detail':     $prelevementController->detail($id); break;
    case 'prelevement_ajouter':    $prelevementController->ajouter(); break;
    case 'prelevement_modifier':   $prelevementController->modifier($id); break;
    case 'prelevement_annuler':    $prelevementController->annuler($id); break;

    default: echo "Page introuvable.";
}