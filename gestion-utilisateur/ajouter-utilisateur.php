<?php
/**
 * Ajout d'un utilisateur — page réservée aux administrateurs.
 *
 * L'Admin peut créer un compte Admin ou Patient. Le mot de passe est
 * obligatoire et haché par le contrôleur (jamais stocké en clair).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/controllers/UtilisateurController.php';

// Contrôle d'accès au début de la page (le contrôleur le refait).
requireRole('Admin');

$controller = new UtilisateurController($pdo);
$result     = $controller->create(); // requireRole('Admin') à l'intérieur

$errors = $result['errors'];
$old    = $result['old'];
$roles  = $result['roles'];

// Rôle Patient proposé par défaut à l'ouverture du formulaire.
$defaultRoleId = 0;
foreach ($roles as $role) {
    if ($role['nom'] === 'Patient') {
        $defaultRoleId = (int) $role['id'];
        break;
    }
}
$selectedRoleId = (int) ($old['role_id'] ?? $defaultRoleId);

$pageTitle = 'Ajouter un utilisateur';
?>
<?php require __DIR__ . '/../includes/header.php'; ?>
<?php require __DIR__ . '/../includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">Ajouter un utilisateur</h1>
      <p class="app-page-sub">
        Le compte sera actif immédiatement et pourra se connecter avec le mot de passe choisi.
      </p>
    </div>

    <div class="app-head-actions">
      <a class="btn app-btn-outline" href="utilisateurs.php">
        <i class="fa fa-arrow-left" aria-hidden="true"></i> Retour à la liste
      </a>
    </div>
  </div>

  <div class="app-card">
    <form method="post" action="ajouter-utilisateur.php" class="app-form" data-validate="user">
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
          <option value="">— Choisir un rôle —</option>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id'] ?>"
              <?= $selectedRoleId === (int) $role['id'] ? 'selected' : '' ?>>
              <?= e($role['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="form-hint">
          Un administrateur peut gérer les utilisateurs, les laboratoires et les analyses.
          Un patient consulte uniquement les laboratoires et le catalogue.
        </p>
        <span class="help-block" data-error-for="role_id"><?= e($errors['role_id'] ?? '') ?></span>
      </div>

      <div class="row">
        <div class="col-sm-6">
          <div class="form-group<?= isset($errors['password']) ? ' has-error' : '' ?>">
            <label class="control-label" for="password">Mot de passe</label>
            <div class="auth-password">
              <input type="password" class="form-control" id="password" name="password"
                     autocomplete="new-password" required>
              <button type="button" class="auth-password-toggle"
                      data-toggle-password="password" aria-label="Afficher le mot de passe">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </div>
            <p class="form-hint">8 caractères minimum, avec au moins une lettre et un chiffre.</p>
            <span class="help-block" data-error-for="password"><?= e($errors['password'] ?? '') ?></span>
          </div>
        </div>

        <div class="col-sm-6">
          <div class="form-group<?= isset($errors['password_confirm']) ? ' has-error' : '' ?>">
            <label class="control-label" for="password_confirm">Confirmer le mot de passe</label>
            <div class="auth-password">
              <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                     autocomplete="new-password" required>
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
          <i class="fa fa-plus" aria-hidden="true"></i> Ajouter l'utilisateur
        </button>
        <a class="btn app-btn-outline" href="utilisateurs.php">Annuler</a>
      </div>
    </form>
  </div>
</main>

<script src="<?= BASE_URL ?>assets/js/validation.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
