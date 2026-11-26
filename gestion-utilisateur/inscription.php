<?php
/**
 * Inscription publique — page réservée à la création d'un compte Patient.
 *
 * Le rôle n'est jamais demandé dans le formulaire : le contrôleur force
 * le rôle « Patient », même si un role_id est injecté dans le POST.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/controllers/UtilisateurController.php';

$controller = new UtilisateurController($pdo);
$result     = $controller->register();

$errors = $result['errors'];
$old    = $result['old'];
$flash  = getFlash();

$pageTitle = 'Créer un compte';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Créer un compte patient LabConnect.">
  <meta name="theme-color" content="#4A1E56">
  <title><?= e($pageTitle) ?> – <?= e(APP_NAME) ?></title>

  <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/favicon.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&amp;family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/font-awesome.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/tooplate-style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/labconnect.css">
</head>
<body class="auth-body">

  <div class="auth-shell">
    <aside class="auth-brand-panel">
      <a class="auth-brand" href="<?= BASE_URL ?>index.php">
        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="">
        Lab<span>Connect</span>
      </a>

      <div>
        <h1>Rejoignez LabConnect.</h1>
        <p>
          Créez votre compte patient pour consulter les laboratoires
          d'analyses et leurs catalogues en toute simplicité.
        </p>

        <ul class="auth-benefits">
          <li><i class="fa fa-user-plus" aria-hidden="true"></i> Inscription gratuite, sans démarche complexe</li>
          <li><i class="fa fa-shield" aria-hidden="true"></i> Vos données restent protégées</li>
          <li><i class="fa fa-flask" aria-hidden="true"></i> Les laboratoires proches de chez vous</li>
        </ul>
      </div>

      <p class="auth-copyright">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
    </aside>

    <main class="auth-form-panel">
      <div class="auth-card">
        <h2>Créer un compte</h2>
        <p class="auth-sub">Un compte patient vous sera attribué automatiquement.</p>

        <?php if ($flash !== null): ?>
          <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Fermer">
              <span aria-hidden="true">&times;</span>
            </button>
            <?= e($flash['message']) ?>
          </div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
          <div class="alert alert-danger" role="alert">
            <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
            <?= e($errors['general']) ?>
          </div>
        <?php endif; ?>

        <form method="post" action="inscription.php" data-validate="user">
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

          <div class="form-group<?= isset($errors['email']) ? ' has-error' : '' ?>">
            <label class="control-label" for="email">Adresse e-mail</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="<?= e($old['email'] ?? '') ?>"
                   autocomplete="email" maxlength="190" required>
            <span class="help-block" data-error-for="email"><?= e($errors['email'] ?? '') ?></span>
          </div>

          <div class="form-group<?= isset($errors['telephone']) ? ' has-error' : '' ?>">
            <label class="control-label" for="telephone">Téléphone <span class="text-muted">(facultatif)</span></label>
            <input type="tel" class="form-control" id="telephone" name="telephone"
                   value="<?= e($old['telephone'] ?? '') ?>"
                   autocomplete="tel" inputmode="numeric" maxlength="8"
                   placeholder="Ex. 98123456">
            <span class="help-block" data-error-for="telephone"><?= e($errors['telephone'] ?? '') ?></span>
          </div>

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

          <button type="submit" class="btn app-btn-primary btn-block">
            <i class="fa fa-user-plus" aria-hidden="true"></i> Créer mon compte
          </button>
        </form>

        <p class="auth-alt">
          Déjà inscrit ?
          <a href="login.php">Se connecter</a>
        </p>

        <p class="auth-back">
          <a href="<?= BASE_URL ?>index.php">&larr; Retour à l'accueil</a>
        </p>
      </div>
    </main>
  </div>

  <script src="<?= BASE_URL ?>assets/js/jquery.js"></script>
  <script src="<?= BASE_URL ?>assets/js/bootstrap.min.js"></script>
  <script src="<?= BASE_URL ?>assets/js/validation.js"></script>
</body>
</html>
