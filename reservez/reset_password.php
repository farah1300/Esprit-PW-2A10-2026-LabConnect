<?php
require_once __DIR__ . '/includes/auth.php';
if (currentUser()) redirect('/index.php');

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$u = $token !== '' ? User::findByResetToken($token) : null;
$error = '';

if ($u && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pw = $_POST['mot_de_passe'] ?? '';
    if (strlen($pw) < 6)                          $error = "Mot de passe : 6 caractères minimum.";
    elseif ($pw !== ($_POST['confirm'] ?? ''))    $error = "Les mots de passe ne correspondent pas.";
    else {
        User::updatePassword((int)$u['id'], $pw);
        User::consumeResetToken($token);
        flash("Mot de passe modifié, vous pouvez vous connecter.");
        redirect('/login.php');
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:420px;margin:auto">
  <h2>Nouveau mot de passe</h2>
  <?php if (!$u): ?>
    <div class="err">Lien invalide ou expiré.</div>
    <a href="<?= BASE_URL ?>/forgot_password.php">Refaire une demande</a>
  <?php else: ?>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label>Nouveau mot de passe</label><input type="password" name="mot_de_passe" required minlength="6">
      <label>Confirmer</label><input type="password" name="confirm" required minlength="6">
      <p><button class="btn v">Enregistrer</button></p>
    </form>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
