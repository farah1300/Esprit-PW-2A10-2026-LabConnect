<?php
require_once "controllers/AdresseController.php";
require_once "controllers/PrelevementController.php";
require_once "controllers/BackPrelevementController.php";

$action = $_GET['action'] ?? 'prelevement_liste';
$id = (int)($_GET['id'] ?? 0);

$adresseController = new AdresseController();
$prelevementController = new PrelevementController();
$backController = new BackPrelevementController();

switch ($action) {
    // FrontOffice: adresses
    case 'adresse_liste':          $adresseController->liste(); break;
    case 'adresse_ajouter':        $adresseController->ajouter(); break;
    case 'adresse_modifier':       $adresseController->modifier($id); break;
    case 'adresse_supprimer':      $adresseController->supprimer($id); break;

    // FrontOffice: prelevements
    case 'prelevement_liste':      $prelevementController->liste(); break;
    case 'prelevement_detail':     $prelevementController->detail($id); break;
    case 'prelevement_ajouter':    $prelevementController->ajouter(); break;
    case 'prelevement_modifier':   $prelevementController->modifier($id); break;
    case 'prelevement_annuler':    $prelevementController->annuler($id); break;

    // BackOffice: responsable des prelevements
    case 'back_liste':             $backController->liste(); break;
    case 'back_detail':            $backController->detail($id); break;
    case 'back_confirmer':         $backController->confirmer($id); break;
    case 'back_refuser':           $backController->refuser($id); break;
    case 'back_avancer':           $backController->avancer($id); break;

    default: echo "Page introuvable.";
}