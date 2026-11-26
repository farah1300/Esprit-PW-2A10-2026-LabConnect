<?php
/**
 * Modification d'un utilisateur — page réservée aux administrateurs.
 *
 * Le mot de passe est facultatif : s'il est laissé vide, l'ancien hash est
 * conservé par le modèle. Le dernier Admin actif ne peut pas être rétrogradé.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/controllers/UtilisateurController.php';

// Contrôle d'accès AVANT de lire l'identifiant : un visiteur non connecté
// ne doit pas recevoir « Utilisateur introuvable » à la place du login.
requireRole('Admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    setFlash('danger', 'Utilisateur introuvable.');
    header('Location: ' . USER_MODULE_URL . 'utilisateurs.php');
    exit;
}

$controller = new UtilisateurController($pdo);
$result     = $controller->edit((int) $id); // requireRole('Admin') à l'intérieur

$user   = $result['user'];
$errors = $result['errors'];
$old    = $result['old'];
$roles  = $result['roles'];

$selectedRoleId = (int) ($old['role_id'] ?? $user['role_id']);
$fullName       = trim($user['prenom'] . ' ' . $user['nom']);
$pageTitle      = 'Modifier un utilisateur';
?>
<?php require __DIR__ . '/../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">Modifier <?= e($fullName) ?></h1>
      <p class="app-page-sub">
        Laissez les champs mot de passe vides pour conserver le mot de passe actuel.
      </p>
    </div>

    <div class="app-head-actions">
      <a class="btn app-btn-outline" href="details-utilisateur.php?id=<?= (int) $user['id'] ?>">
        <i class="fa fa-eye" aria-hidden="true"></i> Voir la fiche
      </a>
      <a class="btn app-btn-outline" href="utilisateurs.php">
        <i class="fa fa-arrow-left" aria-hidden="true"></i> Retour
      </a>
    </div>
  </div>

  <div class="app-card">
    <form method="post" action="modifier-utilisateur.php?id=<?= (int) $user['id'] ?>"
          class="app-form" data-validate="user" data-password-optional="1">
      <?= csrfField() ?>

      <div class="row">
        <div class="col-sm-6">
          <div class="form-group<?= isset($errors['prenom']) ? ' has-error' : '' ?>">
            <label class="control-label" for="prenom">Prénom</label>
            <input type="text" class="form-control" id="prenom" name="prenom"
                   value="<?= e($old['prenom'] ?? '') ?>"
                   autocomplete="given-name" maxlength="60" required>
            <span class="help-block" data-error-for="prenom"><?= e($errors['prenom'] ?? '') ?></span>
          </div>
        </div>

        <div class="col-sm-6">
          <div class="form-group<?= isset($errors['nom']) ? ' has-error' : '' ?>">
            <label class="control-label" for="nom">Nom</label>
            <input type="text" class="form-control" id="nom" name="nom"
                   value="<?= e($old['nom'] ?? '') ?>"
                   autocomplete="family-name" maxlength="60" required>
            <span class="help-block" data-error-for="nom"><?= e($errors['nom'] ?? '') ?></span>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-sm-7">
          <div class="form-group<?= isset($errors['email']) ? ' has-error' : '' ?>">
            <label class="control-label" for="email">Adresse e-mail</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="<?= e($old['email'] ?? '') ?>"
                   autocomplete="email" maxlength="190" required>
            <span class="help-block" data-error-for="email"><?= e($errors['email'] ?? '') ?></span>
          </div>
        </div>

        <div class="col-sm-5">
          <div class="form-group<?= isset($errors['telephone']) ? ' has-error' : '' ?>">
            <label class="control-label" for="telephone">Téléphone <span class="text-muted">(facultatif)</span></label>
            <input type="tel" class="form-control" id="telephone" name="telephone"
                   value="<?= e($old['telephone'] ?? '') ?>"
                   autocomplete="tel" inputmode="numeric" maxlength="8"
                   placeholder="Ex. 98123456">
            <span class="help-block" data-error-for="telephone"><?= e($errors['telephone'] ?? '') ?></span>
          </div>
        </div>
      </div>

      <div class="form-group<?= isset($errors['role_id']) ? ' has-error' : '' ?>">
        <label class="control-label" for="role_id">Rôle</label>
        <select class="form-control" id="role_id" name="role_id" required>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id'] ?>"
              <?= $selectedRoleId === (int) $role['id'] ? 'selected' : '' ?>>
              <?= e($role['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="form-hint">
          Le dernier administrateur actif doit conserver le rôle Admin.
        </p>
        <span class="help-block" data-error-for="role_id"><?= e($errors['role_id'] ?? '') ?></span>
      </div>

      <div class="row">
        <div class="col-sm-6">
          <div class="form-group<?= isset($errors['password']) ? ' has-error' : '' ?>">
            <label class="control-label" for="password">Nouveau mot de passe <span class="text-muted">(facultatif)</span></label>
            <div class="auth-password">
              <input type="password" class="form-control" id="password" name="password"
                     autocomplete="new-password">
              <button type="button" class="auth-password-toggle"
                      data-toggle-password="password" aria-label="Afficher le mot de passe">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </div>
            <p class="form-hint">Vide = mot de passe inchangé. Sinon : 8 caractères minimum, une lettre et un chiffre.</p>
            <span class="help-block" data-error-for="password"><?= e($errors['password'] ?? '') ?></span>
          </div>
        </div>

        <div class="col-sm-6">
          <div class="form-group<?= isset($errors['password_confirm']) ? ' has-error' : '' ?>">
            <label class="control-label" for="password_confirm">Confirmer le nouveau mot de passe</label>
            <div class="auth-password">
              <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                     autocomplete="new-password">
              <button type="button" class="auth-password-toggle"
                      data-toggle-password="password_confirm" aria-label="Afficher le mot de passe">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </div>
            <span class="help-block" data-error-for="password_confirm"><?= e($errors['password_confirm'] ?? '') ?></span>
          </div>
        </div>
      </div>

      <div class="app-form-actions">
        <button type="submit" class="btn app-btn-primary">
          <i class="fa fa-save" aria-hidden="true"></i> Enregistrer les modifications
        </button>
        <a class="btn app-btn-outline" href="utilisateurs.php">Annuler</a>
      </div>
    </form>
  </div>
</main>

<script src="<?= BASE_URL ?>assets/js/validation.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
