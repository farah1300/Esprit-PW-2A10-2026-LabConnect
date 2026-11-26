<?php
require_once __DIR__ . '/includes/auth.php';
if (currentUser()) redirect('/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = User::findByEmail($_POST['email'] ?? '');
    if (!$u || !password_verify($_POST['mot_de_passe'] ?? '', $u['mot_de_passe'])) {
        $error = "Email ou mot de passe incorrect.";
    } elseif (!$u['actif']) {
        $error = "Votre compte est désactivé. Contactez l'administrateur.";
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        redirect('/index.php');
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:420px;margin:auto">
  <h2>Connexion</h2>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email</label><input type="email" name="email" required>
    <label>Mot de passe</label><input type="password" name="mot_de_passe" required>
    <p><button class="btn">Se connecter</button></p>
  </form>
  <a href="<?= BASE_URL ?>/register.php">Créer un compte</a><br><br>
  <a href="<?= BASE_URL ?>/forgot_password.php">Mot de passe oublié ?</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
