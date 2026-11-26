<?php
/**
 * Tableau de bord – page d'accueil après connexion.
 *
 * C'est le modèle à copier pour toutes les pages :
 *   1. require auth.php + contrôle d'accès
 *   2. $pageTitle
 *   3. header.php + sidebar.php
 *   4. contenu de la page
 *   5. footer.php
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin(); // Les deux rôles (Admin et Patient) peuvent voir cette page.

$pageTitle = 'Tableau de bord';
$user      = currentUser() ?? [];
$role      = $user['role'] ?? '';
$prenom    = trim($user['prenom'] ?? '');
$nom       = trim($user['nom'] ?? '');
$email     = $user['email'] ?? '';
$roleLabel = $role === 'Admin' ? 'Administrateur' : 'Patient';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<main class="col-md-9 app-content" id="contenu">
  <h1 class="app-page-title">
    Bonjour<?= $prenom !== '' ? ', ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') : '' ?>
  </h1>
  <p class="app-page-sub">
    Bienvenue dans votre espace <?= $role === 'Admin' ? 'administrateur' : 'patient' ?>.
    Choisissez un module pour commencer.
  </p>

  <div class="app-shortcuts">
    <?php if ($role === 'Admin'): ?>
      <!-- Raccourcis réservés à l'Admin -->
      <a class="app-shortcut" href="<?= USER_MODULE_URL ?>utilisateurs.php">
        <i class="fa fa-users" aria-hidden="true"></i>
        <h3>Utilisateurs</h3>
        <p>Créer, modifier, désactiver les comptes et attribuer les rôles.</p>
      </a>

      <a class="app-shortcut" href="<?= BASE_URL ?>laboratoires.php">
        <i class="fa fa-flask" aria-hidden="true"></i>
        <h3>Laboratoires</h3>
        <p>Gérer les laboratoires, leurs coordonnées et leurs analyses.</p>
      </a>

      <a class="app-shortcut" href="<?= BASE_URL ?>catalogue.php">
        <i class="fa fa-list-alt" aria-hidden="true"></i>
        <h3>Catalogue des analyses</h3>
        <p>Consulter les analyses disponibles et les prix par laboratoire.</p>
      </a>

      <a class="app-shortcut" href="<?= BASE_URL ?>validation.php">
        <i class="fa fa-check-square-o" aria-hidden="true"></i>
        <h3>Validation</h3>
        <p>Associer une analyse à un laboratoire avec son prix.</p>
      </a>
    <?php else: ?>
      <!-- Le Patient ne voit que les pages de consultation -->
      <a class="app-shortcut" href="<?= BASE_URL ?>laboratoires.php">
        <i class="fa fa-flask" aria-hidden="true"></i>
        <h3>Laboratoires</h3>
        <p>Rechercher un laboratoire par nom ou par ville.</p>
      </a>

      <a class="app-shortcut" href="<?= BASE_URL ?>catalogue.php">
        <i class="fa fa-list-alt" aria-hidden="true"></i>
        <h3>Catalogue des analyses</h3>
        <p>Consulter les analyses disponibles et leurs prix.</p>
      </a>
    <?php endif; ?>
  </div>

  <div class="app-card">
    <h2>Mon compte</h2>
    <dl class="app-account">
      <dt>Nom complet</dt>
      <dd><?= htmlspecialchars(trim($prenom . ' ' . $nom), ENT_QUOTES, 'UTF-8') ?></dd>

      <dt>Adresse e-mail</dt>
      <dd><?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?></dd>

      <dt>Rôle</dt>
      <dd><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
