<?php
/**
 * Menu latéral, affiché selon le rôle de l'utilisateur.
 *
 * ATTENTION : masquer un lien n'est PAS une sécurité. Un Patient pourrait
 * taper l'URL à la main. Chaque page Admin doit donc appeler requireRole('Admin').
 *
 * Pour ajouter un module : copiez un bloc commenté et ajoutez UN <li> PAR LIGNE.
 * Ainsi, deux personnes qui ajoutent des liens différents créent rarement un conflit Git.
 */

require_once __DIR__ . '/auth.php';

// Variables préfixées : sidebar.php partage la portée de la page.
$sideUser        = currentUser();
$sideRole        = $sideUser['role'] ?? '';
$sideCurrentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Ajoute la classe "active" au lien de la page courante.
$sideActive = static function (string $page) use ($sideCurrentPage): string {
    return $sideCurrentPage === $page ? ' class="active"' : '';
};
?>
<aside class="col-md-3 app-sidebar" id="appSidebar" aria-label="Menu latéral">
  <div class="app-sidebar-inner">
    <p class="app-sidebar-title">Navigation</p>

    <ul class="app-menu">
      <li<?= $sideActive('index.php') ?>><a href="<?= BASE_URL ?>index.php"><i class="fa fa-tachometer" aria-hidden="true"></i> Tableau de bord</a></li>

      <?php if ($sideRole === 'Admin'): ?>
        <!-- ===== MODULE 1 : UTILISATEURS (Admin) ===== -->
        <li class="app-menu-heading">Module 1 — Utilisateurs</li>
        <li<?= $sideActive('utilisateurs.php') ?>><a href="<?= USER_MODULE_URL ?>utilisateurs.php"><i class="fa fa-users" aria-hidden="true"></i> Utilisateurs</a></li>
        <li<?= $sideActive('roles.php') ?>><a href="<?= USER_MODULE_URL ?>roles.php"><i class="fa fa-shield" aria-hidden="true"></i> Rôles</a></li>

        <!-- ===== MODULE 2 : LABORATOIRES (Admin) ===== -->
        <li class="app-menu-heading">Module 2 — Laboratoires</li>
        <li<?= $sideActive('laboratoires.php') ?>><a href="<?= BASE_URL ?>laboratoires.php"><i class="fa fa-flask" aria-hidden="true"></i> Laboratoires</a></li>
        <li<?= $sideActive('catalogue.php') ?>><a href="<?= BASE_URL ?>catalogue.php"><i class="fa fa-list-alt" aria-hidden="true"></i> Catalogue des analyses</a></li>
        <li<?= $sideActive('validation.php') ?>><a href="<?= BASE_URL ?>validation.php"><i class="fa fa-check-square-o" aria-hidden="true"></i> Validation</a></li>

        <!-- ===== AJOUTER UN MODULE ICI (Admin) ===== -->
      <?php else: ?>
        <!-- ===== MODULE 2 : LABORATOIRES (Patient — consultation seulement) ===== -->
        <li class="app-menu-heading">Laboratoires</li>
        <li<?= $sideActive('laboratoires.php') ?>><a href="<?= BASE_URL ?>laboratoires.php"><i class="fa fa-flask" aria-hidden="true"></i> Laboratoires</a></li>
        <li<?= $sideActive('catalogue.php') ?>><a href="<?= BASE_URL ?>catalogue.php"><i class="fa fa-list-alt" aria-hidden="true"></i> Catalogue des analyses</a></li>

        <!-- ===== AJOUTER UN MODULE ICI (Patient) ===== -->
      <?php endif; ?>
    </ul>
  </div>
</aside>
