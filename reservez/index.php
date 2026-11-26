<?php
require_once __DIR__ . '/includes/auth.php';
$me = requireLogin();
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2>Bienvenue <?= e($me['prenom']) ?> 👋</h2>
  <p>Votre rôle : <span class="badge b-<?= strtolower($me['role']) ?>"><?= e($me['role']) ?></span></p>
  <?php if ($me['role'] === 'ADMIN'): ?>
    <a class="btn" href="<?= BASE_URL ?>/users/index.php">Gérer les utilisateurs</a>
  <?php endif; ?>
  <a class="btn g" href="<?= BASE_URL ?>/profile.php">Mon profil</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
