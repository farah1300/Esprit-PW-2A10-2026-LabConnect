<?php
/**
 * Gestion des rôles — page réservée aux administrateurs.
 *
 * Deux usages :
 *   1. visualiser les rôles et leur effectif ;
 *   2. changer le rôle d'un utilisateur via un formulaire POST + CSRF.
 *
 * Le contrôleur protège le dernier administrateur actif et vérifie que le
 * rôle envoyé existe réellement en base.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/controllers/UtilisateurController.php';

// Contrôle d'accès au tout début de la page (le contrôleur le refait).
requireRole('Admin');

$controller = new UtilisateurController($pdo);

$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

if ($action === 'changeRole') {
    $controller->changeRole(isset($_POST['id']) ? (int) $_POST['id'] : 0);
}

$data         = $controller->rolesPage(); // requireRole('Admin') à l'intérieur
$roles        = $data['roles'];
$users        = $data['users'];
$activeAdmins = (int) $data['activeAdmins'];

$me       = currentUser() ?? [];
$meId     = (int) ($me['id'] ?? 0);
$pageTitle = 'Rôles';
?>
<?php require __DIR__ . '/../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">Rôles</h1>
      <p class="app-page-sub">
        Un rôle détermine les pages accessibles. Seul un administrateur peut en attribuer un.
      </p>
    </div>

    <div class="app-head-actions">
      <a class="btn app-btn-outline" href="utilisateurs.php">
        <i class="fa fa-users" aria-hidden="true"></i> Liste des utilisateurs
      </a>
    </div>
  </div>

  <div class="app-stats">
    <div class="app-stat">
      <small>Rôles disponibles</small>
      <strong><?= count($roles) ?></strong>
    </div>

    <div class="app-stat blue">
      <small>Utilisateurs</small>
      <strong><?= count($users) ?></strong>
    </div>

    <div class="app-stat green">
      <small>Admins actifs</small>
      <strong><?= $activeAdmins ?></strong>
    </div>
  </div>

  <div class="app-card app-card-table">
    <div class="app-toolbar">
      <h2 class="app-page-title" style="font-size: 18px; margin: 0;">Effectif par rôle</h2>
    </div>

    <div class="table-responsive">
      <table class="table app-table">
        <thead>
          <tr>
            <th scope="col">Rôle</th>
            <th scope="col">Description</th>
            <th scope="col" class="text-right">Utilisateurs</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($roles as $role): ?>
            <tr>
              <td>
                <span class="app-badge <?= e(roleBadgeClass($role['nom'])) ?>">
                  <?= e($role['nom']) ?>
                </span>
              </td>
              <td>
                <?= $role['nom'] === 'Admin'
                    ? 'Accès complet : utilisateurs, rôles, laboratoires et analyses.'
                    : 'Consultation des laboratoires et du catalogue des analyses.' ?>
              </td>
              <td class="text-right">
                <span class="app-badge-count"><?= (int) $role['count'] ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="app-note">
      Les rôles sont des données de référence : ils ne se suppriment pas depuis l'interface.
    </p>
  </div>

  <div class="app-card app-card-table">
    <div class="app-toolbar">
      <h2 class="app-page-title" style="font-size: 18px; margin: 0;">Attribution des rôles</h2>
    </div>

    <div class="table-responsive">
      <table class="table app-table">
        <thead>
          <tr>
            <th scope="col">Utilisateur</th>
            <th scope="col">Rôle actuel</th>
            <th scope="col">Statut</th>
            <th scope="col" class="text-right">Nouveau rôle</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($users === []): ?>
            <tr>
              <td colspan="4" class="app-empty">
                <i class="fa fa-users" aria-hidden="true"></i>
                Aucun utilisateur enregistré pour le moment.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $user): ?>
              <?php
                $id       = (int) $user['id'];
                $isActive = (int) $user['is_active'] === 1;
                $isSelf   = $id === $meId;

                // Dernier Admin actif : le serveur refusera la rétrogradation.
                // On désactive aussi le contrôle pour éviter un aller-retour inutile.
                $locked = $user['role'] === 'Admin' && $isActive && $activeAdmins <= 1;
              ?>
              <tr>
                <td>
                  <span class="app-lab-name">
                    <?= e(trim($user['prenom'] . ' ' . $user['nom'])) ?>
                    <?php if ($isSelf): ?>
                      <span class="app-preview-badge">Vous</span>
                    <?php endif; ?>
                  </span>
                  <span class="app-lab-email"><?= e($user['email']) ?></span>
                </td>

                <td>
                  <span class="app-badge <?= e(roleBadgeClass($user['role'])) ?>">
                    <?= e($user['role']) ?>
                  </span>
                </td>

                <td>
                  <span class="app-badge <?= $isActive ? 'active' : 'inactive' ?>">
                    <?= $isActive ? 'Actif' : 'Désactivé' ?>
                  </span>
                </td>

                <td>
                  <form method="post" action="roles.php" class="app-role-form"
                        data-validate="role">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="changeRole">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <select class="form-control" name="role_id"
                            aria-label="Nouveau rôle de <?= e($user['prenom'] . ' ' . $user['nom']) ?>"
                            required
                            <?= $locked ? 'disabled' : '' ?>>
                      <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>"
                          <?= (int) $user['role_id'] === (int) $role['id'] ? 'selected' : '' ?>>
                          <?= e($role['nom']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-sm app-btn-primary"
                            title="<?= $locked ? 'Dernier administrateur actif : changement impossible' : 'Changer le rôle' ?>"
                            <?= $locked ? 'disabled' : '' ?>>
                      Changer
                    </button>

                    <span class="help-block text-danger" data-error-for="role_id"
                          style="flex-basis:100%; margin:0;"></span>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p class="app-note">
      Le dernier administrateur actif ne peut pas être rétrogradé : la plateforme
      doit toujours conserver au moins un Admin capable de gérer les comptes.
    </p>
  </div>
</main>

<script src="<?= BASE_URL ?>assets/js/validation.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
