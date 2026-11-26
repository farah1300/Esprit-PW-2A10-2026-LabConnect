<?php
/**
 * Connexion — page publique du module Utilisateurs.
 *
 * Toute la vérification (e-mail, mot de passe, compte actif, régénération
 * de session) est faite par UtilisateurController::login(). La page ne
 * fait qu'afficher le formulaire, les erreurs et les valeurs conservées.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/controllers/UtilisateurController.php';

$controller = new UtilisateurController($pdo);
$result     = $controller->login();

$errors = $result['errors'];
$old    = $result['old'];
$flash  = getFlash();

$pageTitle = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Connexion à l'espace LabConnect – gestion des laboratoires d'analyses médicales.">
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
        <h1>Votre santé, simplifiée.</h1>
        <p>
          Retrouvez vos laboratoires d'analyses et gérez votre compte
          depuis un espace unique, sécurisé et accessible à tout moment.
        </p>

        <ul class="auth-benefits">
          <li><i class="fa fa-check-circle" aria-hidden="true"></i> Un compte patient créé en quelques secondes</li>
          <li><i class="fa fa-lock" aria-hidden="true"></i> Un accès protégé par mot de passe</li>
          <li><i class="fa fa-heartbeat" aria-hidden="true"></i> Vos laboratoires réunis au même endroit</li>
        </ul>
      </div>

      <p class="auth-copyright">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
    </aside>

    <main class="auth-form-panel">
      <div class="auth-card">
        <h2>Connexion</h2>
        <p class="auth-sub">Accédez à votre espace LabConnect.</p>

        <?php if ($flash !== null): ?>
          <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Fermer">
              <span aria-hidden="true">&times;</span>
            </button>
            <?= e($flash['message']) ?>
          </div>
        <?php endif; ?>

        <?php if (isset($errors['login'])): ?>
          <div class="alert alert-danger" role="alert">
            <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
            <?= e($errors['login']) ?>
          </div>
        <?php endif; ?>

        <form method="post" action="login.php" data-validate="login">
          <?= csrfField() ?>

          <div class="form-group<?= isset($errors['email']) ? ' has-error' : '' ?>">
            <label class="control-label" for="email">Adresse e-mail</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="<?= e($old['email'] ?? '') ?>"
                   autocomplete="email" autofocus required>
            <span class="help-block" data-error-for="email"><?= e($errors['email'] ?? '') ?></span>
          </div>

          <div class="form-group<?= isset($errors['password']) ? ' has-error' : '' ?>">
            <label class="control-label" for="password">Mot de passe</label>
            <div class="auth-password">
              <input type="password" class="form-control" id="password" name="password"
                     autocomplete="current-password" required>
              <button type="button" class="auth-password-toggle"
                      data-toggle-password="password" aria-label="Afficher le mot de passe">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </div>
            <span class="help-block" data-error-for="password"><?= e($errors['password'] ?? '') ?></span>
          </div>

          <button type="submit" class="btn app-btn-primary btn-block">
            <i class="fa fa-sign-in" aria-hidden="true"></i> Se connecter
          </button>
        </form>

        <p class="auth-alt">
          Pas encore de compte ?
          <a href="inscription.php">Créer un compte patient</a>
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
