<?php
/**
 * Liste des utilisateurs — page réservée aux administrateurs.
 *
 * Recherche (q) et filtre par rôle (role) sont passés en GET.
 * Les actions modifier le statut ou supprimer passent en POST + CSRF :
 * elles ne peuvent donc pas être déclenchées par un simple lien.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/controllers/UtilisateurController.php';

// Contrôle d'accès au tout début de la page : même une requête POST forgée
// est arrêtée avant de lire l'action demandée. Le contrôleur refait le
// contrôle (défense en profondeur), mais la page ne doit jamais dépendre
// uniquement de lui.
requireRole('Admin');

$controller = new UtilisateurController($pdo);

// --- Actions POST (supprimer / activer-désactiver) -------------------
$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
$targetId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($action === 'delete') {
    $controller->destroy($targetId);
} elseif ($action === 'toggle') {
    $controller->toggleActive($targetId);
}

// --- Données de la page ----------------------------------------------
$data   = $controller->index(); // requireRole('Admin') à l'intérieur
$users  = $data['users'];
$roles  = $data['roles'];
$search = $data['search'];
$searchError = $data['searchError'];
$roleId = $data['roleId'];
$stats  = $data['stats'];

$me       = currentUser() ?? [];
$meId     = (int) ($me['id'] ?? 0);
$pageTitle = 'Utilisateurs';
?>
<?php require __DIR__ . '/../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">Utilisateurs</h1>
      <p class="app-page-sub">
        Créez les comptes, modifiez les informations et gérez les accès.
      </p>
    </div>

    <div class="app-head-actions">
      <a class="btn app-btn-primary" href="ajouter-utilisateur.php">
        <i class="fa fa-plus" aria-hidden="true"></i> Ajouter un utilisateur
      </a>
    </div>
  </div>

  <!-- Statistiques globales (indépendantes du filtre) -->
  <div class="app-stats">
    <div class="app-stat">
      <small>Total</small>
      <strong><?= (int) $stats['total'] ?></strong>
    </div>

    <div class="app-stat green">
      <small>Comptes actifs</small>
      <strong><?= (int) $stats['active'] ?></strong>
    </div>

    <div class="app-stat gold">
      <small>Comptes inactifs</small>
      <strong><?= (int) $stats['inactive'] ?></strong>
    </div>

    <div class="app-stat blue">
      <small>Patients</small>
      <strong><?= (int) $stats['patients'] ?></strong>
    </div>

    <div class="app-stat">
      <small>Admins actifs</small>
      <strong><?= (int) $stats['activeAdmins'] ?></strong>
    </div>
  </div>

  <div class="app-card app-card-table">
    <form method="get" action="utilisateurs.php" class="app-toolbar" role="search"
          data-validate="search">
      <div class="app-search">
        <i class="fa fa-search" aria-hidden="true"></i>
        <input type="search" class="form-control" name="q"
               value="<?= e($search) ?>"
               placeholder="Rechercher un nom, un prénom ou un e-mail"
               aria-label="Rechercher un utilisateur"
               maxlength="100">
      </div>

      <select class="form-control app-filter" name="role" aria-label="Filtrer par rôle">
        <option value="">Tous les rôles</option>
        <?php foreach ($roles as $role): ?>
          <option value="<?= (int) $role['id'] ?>"
            <?= $roleId === (int) $role['id'] ? 'selected' : '' ?>>
            <?= e($role['nom']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="btn app-btn-primary">
        <i class="fa fa-filter" aria-hidden="true"></i> Filtrer
      </button>

      <?php if ($search !== '' || $roleId !== null): ?>
        <a class="btn app-btn-outline" href="utilisateurs.php">Réinitialiser</a>
      <?php endif; ?>

      <span class="help-block text-danger" data-error-for="q"
            style="flex-basis:100%; margin:0;"><?= e($searchError) ?></span>
    </form>

    <div class="table-responsive">
      <table class="table app-table">
        <thead>
          <tr>
            <th scope="col">Utilisateur</th>
            <th scope="col" class="hide-sm">Téléphone</th>
            <th scope="col">Rôle</th>
            <th scope="col">Statut</th>
            <th scope="col" class="hide-sm">Dernière connexion</th>
            <th scope="col" class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($users === []): ?>
            <tr>
              <td colspan="6" class="app-empty">
                <i class="fa fa-users" aria-hidden="true"></i>
                <?= $search !== '' || $roleId !== null
                    ? 'Aucun utilisateur ne correspond à votre recherche.'
                    : 'Aucun utilisateur enregistré pour le moment.' ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $user): ?>
              <?php
                $id       = (int) $user['id'];
                $isActive = (int) $user['is_active'] === 1;
                $isAdmin  = $user['role'] === 'Admin';
                $isSelf   = $id === $meId;

                // Le dernier Admin actif ne doit jamais pouvoir être
                // supprimé ni désactivé, même si le bouton est cliqué.
                $protectLastAdmin = $isAdmin && $isActive && (int) $stats['activeAdmins'] <= 1;

                $canToggle = !$isSelf && !($isActive && $protectLastAdmin);
                $canDelete = !$isSelf && !$protectLastAdmin;

                $toggleTitle = $isActive ? 'Désactiver le compte' : 'Activer le compte';
                if ($isSelf) {
                    $toggleTitle = 'Vous ne pouvez pas désactiver votre propre compte';
                } elseif ($isActive && $protectLastAdmin) {
                    $toggleTitle = 'Dernier administrateur actif : action impossible';
                }

                $deleteTitle = 'Supprimer le compte';
                if ($isSelf) {
                    $deleteTitle = 'Vous ne pouvez pas supprimer votre propre compte';
                } elseif ($protectLastAdmin) {
                    $deleteTitle = 'Dernier administrateur actif : action impossible';
                }
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

                <td class="hide-sm app-lab-phone">
                  <?= $user['telephone'] !== null && $user['telephone'] !== ''
                      ? e($user['telephone'])
                      : '—' ?>
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

                <td class="hide-sm">
                  <?= $user['last_login'] !== null && $user['last_login'] !== ''
                      ? e(formatDateTime($user['last_login']))
                      : 'Jamais' ?>
                </td>

                <td>
                  <div class="app-actions">
                    <a class="app-action" href="details-utilisateur.php?id=<?= $id ?>"
                       title="Voir la fiche" aria-label="Voir la fiche de <?= e($user['prenom'] . ' ' . $user['nom']) ?>">
                      <i class="fa fa-eye" aria-hidden="true"></i>
                    </a>

                    <a class="app-action" href="modifier-utilisateur.php?id=<?= $id ?>"
                       title="Modifier" aria-label="Modifier <?= e($user['prenom'] . ' ' . $user['nom']) ?>">
                      <i class="fa fa-pencil" aria-hidden="true"></i>
                    </a>

                    <form method="post" action="utilisateurs.php">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= $id ?>">
                      <button type="submit" class="app-action"
                              title="<?= e($toggleTitle) ?>"
                              aria-label="<?= e($toggleTitle) ?>"
                              <?= $canToggle ? '' : 'disabled' ?>>
                        <i class="fa <?= $isActive ? 'fa-ban' : 'fa-check' ?>" aria-hidden="true"></i>
                      </button>
                    </form>

                    <form method="post" action="utilisateurs.php"
                          onsubmit="return confirm('Supprimer définitivement cet utilisateur ?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $id ?>">
                      <button type="submit" class="app-action danger"
                              title="<?= e($deleteTitle) ?>"
                              aria-label="<?= e($deleteTitle) ?>"
                              <?= $canDelete ? '' : 'disabled' ?>>
                        <i class="fa fa-trash" aria-hidden="true"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p class="app-note">
      <?= count($users) ?> utilisateur<?= count($users) > 1 ? 's' : '' ?> affiché<?= count($users) > 1 ? 's' : '' ?>.
      Le dernier administrateur actif ne peut être ni supprimé, ni désactivé, ni rétrogradé.
    </p>
  </div>
</main>

<script src="<?= BASE_URL ?>assets/js/validation.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
