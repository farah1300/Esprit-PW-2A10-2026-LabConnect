<?php
require_once __DIR__ . '/includes/auth.php';
if (currentUser()) redirect('/index.php');

// true = en local (XAMPP) : on affiche le lien à l'écran car l'envoi d'email ne marche pas.
// Mettre false en production (le lien est alors envoyé seulement par email).
const DEV_MODE = true;

$info = ''; $devLink = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = User::findByEmail($_POST['email'] ?? '');
    if ($u && $u['actif']) {
        $token = User::createResetToken((int)$u['id']);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link = $scheme . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/reset_password.php?token=' . $token;
        @mail($u['email'], 'LabConnect - Réinitialisation du mot de passe',
              "Bonjour,\n\nPour choisir un nouveau mot de passe (valable 1 heure) :\n$link\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.",
              'From: no-reply@labconnect.tn');
        if (DEV_MODE) $devLink = $link;
    }
    // Même message dans tous les cas : on ne révèle pas si l'email existe
    $info = "Si cet email existe, un lien de réinitialisation a été envoyé.";
}
include __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:420px;margin:auto">
  <h2>Mot de passe oublié</h2>
  <?php if ($info): ?><div class="ok"><?= e($info) ?></div><?php endif; ?>
  <?php if ($devLink): ?>
    <div class="err" style="background:#fff3cd">Mode développement — lien :<br>
      <a href="<?= e($devLink) ?>"><?= e($devLink) ?></a></div>
  <?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email</label><input type="email" name="email" required>
    <p><button class="btn">Envoyer le lien</button></p>
  </form>
  <a href="<?= BASE_URL ?>/login.php">Retour à la connexion</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
