<?php
/**
 * Fiche d'un utilisateur — page réservée aux administrateurs.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/controllers/UtilisateurController.php';

// Contrôle d'accès AVANT de lire l'identifiant (voir modifier-utilisateur.php).
requireRole('Admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    setFlash('danger', 'Utilisateur introuvable.');
    header('Location: ' . USER_MODULE_URL . 'utilisateurs.php');
    exit;
}

$controller = new UtilisateurController($pdo);
$user       = $controller->show((int) $id); // requireRole('Admin') à l'intérieur

$isActive = (int) $user['is_active'] === 1;
$fullName = trim($user['prenom'] . ' ' . $user['nom']);
$pageTitle = 'Fiche utilisateur';
?>
<?php require __DIR__ . '/../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title"><?= e($fullName) ?></h1>
      <p class="app-page-sub">Fiche détaillée du compte utilisateur.</p>
    </div>

    <div class="app-head-actions">
      <a class="btn app-btn-primary" href="modifier-utilisateur.php?id=<?= (int) $user['id'] ?>">
        <i class="fa fa-pencil" aria-hidden="true"></i> Modifier
      </a>
      <a class="btn app-btn-outline" href="utilisateurs.php">
        <i class="fa fa-arrow-left" aria-hidden="true"></i> Retour à la liste
      </a>
    </div>
  </div>

  <div class="app-card">
    <div class="app-detail-grid">
      <div class="app-detail-item">
        <span class="app-detail-label">Nom complet</span>
        <span class="app-detail-value"><?= e($fullName) ?></span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Adresse e-mail</span>
        <span class="app-detail-value"><?= e($user['email']) ?></span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Téléphone</span>
        <span class="app-detail-value">
          <?= $user['telephone'] !== null && $user['telephone'] !== ''
              ? e($user['telephone'])
              : '—' ?>
        </span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Rôle</span>
        <span class="app-detail-value">
          <span class="app-badge <?= e(roleBadgeClass($user['role'])) ?>"><?= e($user['role']) ?></span>
        </span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Statut du compte</span>
        <span class="app-detail-value">
          <span class="app-badge <?= $isActive ? 'active' : 'inactive' ?>">
            <?= $isActive ? 'Actif' : 'Désactivé' ?>
          </span>
        </span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Dernière connexion</span>
        <span class="app-detail-value">
          <?= $user['last_login'] !== null && $user['last_login'] !== ''
              ? e(formatDateTime($user['last_login']))
              : 'Jamais' ?>
        </span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Compte créé le</span>
        <span class="app-detail-value"><?= e(formatDateTime($user['created_at'])) ?></span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Identifiant interne</span>
        <span class="app-detail-value">#<?= (int) $user['id'] ?></span>
      </div>
    </div>
  </div>

  <div class="app-card">
    <h2>Actions rapides</h2>
    <p class="app-page-sub">
      La suppression et la désactivation sont disponibles depuis la liste,
      avec une confirmation et une protection du dernier administrateur actif.
    </p>
    <a class="btn app-btn-outline" href="utilisateurs.php">
      <i class="fa fa-users" aria-hidden="true"></i> Gérer les utilisateurs
    </a>
  </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
