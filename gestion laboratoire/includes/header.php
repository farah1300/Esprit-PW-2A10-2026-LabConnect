<?php
/**
 * En-tête commun à toutes les pages connectées.
 *
 * La page doit définir $pageTitle AVANT d'inclure ce fichier :
 *   $pageTitle = 'Utilisateurs';
 *   require __DIR__ . '/includes/header.php';
 *
 * Ce fichier ouvre la balise <body> et la grille de page. Le fichier
 * footer.php la referme. La page insère sidebar.php puis son <main>.
 */

require_once __DIR__ . '/auth.php';

// Variables préfixées : header.php est inclus dans la même portée que la page.
// On évite ainsi d'écraser par erreur une variable utilisée par la page.
$headerUser      = currentUser();
$headerFullName  = trim(($headerUser['prenom'] ?? '') . ' ' . ($headerUser['nom'] ?? ''));
$headerRole      = $headerUser['role'] ?? '';
$headerPageTitle = $pageTitle ?? 'Accueil';
$headerRoleLabel = $headerRole === 'Admin' ? 'Administration' : 'Patient';

if ($headerFullName === '') {
    $headerFullName = 'Utilisateur';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="LabConnect – gestion des laboratoires d'analyses médicales.">
  <meta name="theme-color" content="#4A1E56">
  <title><?= htmlspecialchars($headerPageTitle, ENT_QUOTES, 'UTF-8') ?> – <?= APP_NAME ?></title>

  <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/favicon.png">

  <!-- Polices du template -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&amp;family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet">

  <!-- Styles du template + surcharges LabConnect -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/font-awesome.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/tooplate-style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/labconnect.css">

  <!-- Sans JavaScript, le menu mobile reste visible (pas de blocage de navigation). -->
  <noscript>
    <style>
      @media (max-width: 991px) {
        .app-sidebar { display: block !important; }
        .app-menu-toggle { display: none !important; }
      }
    </style>
  </noscript>
</head>
<body class="app-body">

  <!-- Barre supérieure : identité + contexte de la session -->
  <header class="app-topbar">
    <div class="container-fluid">
      <div class="app-topbar-inner">
        <p><i class="fa fa-heartbeat" aria-hidden="true"></i> <?= APP_NAME ?> — Votre santé, simplifiée</p>
        <p class="app-topbar-meta">
          <i class="fa fa-calendar-o" aria-hidden="true"></i> <?= date('d/m/Y') ?>
          <span class="app-role-badge"><?= htmlspecialchars($headerRoleLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </p>
      </div>
    </div>
  </header>

  <!-- Barre de navigation : marque, utilisateur connecté, déconnexion -->
  <nav class="app-navbar" aria-label="Navigation principale">
    <div class="container-fluid app-navbar-inner">
      <button type="button" class="app-menu-toggle" id="sidebarToggle"
              aria-controls="appSidebar" aria-expanded="false" aria-label="Ouvrir le menu">
        <i class="fa fa-bars" aria-hidden="true"></i>
      </button>

      <a class="app-brand" href="<?= BASE_URL ?>index.php">
        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="" aria-hidden="true">
        Lab<span>Connect</span>
      </a>

      <div class="app-nav-user">
        <span class="app-nav-name">
          <i class="fa fa-user-o" aria-hidden="true"></i>
          <?= htmlspecialchars($headerFullName, ENT_QUOTES, 'UTF-8') ?>
        </span>
        <a class="btn btn-sm app-logout" href="<?= USER_MODULE_URL ?>logout.php">
          <i class="fa fa-sign-out" aria-hidden="true"></i> <span class="app-logout-text">Déconnexion</span>
        </a>
      </div>
    </div>
  </nav>

  <!-- Message flash : affiché une seule fois, puis supprimé de la session -->
  <?php $headerFlash = getFlash(); ?>
  <?php if ($headerFlash !== null): ?>
    <div class="container-fluid app-flash">
      <div class="alert alert-<?= htmlspecialchars($headerFlash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Fermer">
          <span aria-hidden="true">&times;</span>
        </button>
        <?= htmlspecialchars($headerFlash['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Ouverture de la grille : sidebar.php + <main> de la page -->
  <div class="container-fluid app-shell">
    <div class="row">
