<?php $me = currentUser(); $flash = flash(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>LabConnect</title>
<style>
  *{box-sizing:border-box} body{font-family:Arial,sans-serif;margin:0;background:#f4f6f9;color:#222}
  nav{background:#0d6efd;color:#fff;padding:12px 24px;display:flex;gap:18px;align-items:center}
  nav a{color:#fff;text-decoration:none} nav .sp{flex:1}
  main{max-width:1000px;margin:24px auto;padding:0 16px}
  .card{background:#fff;padding:24px;border-radius:8px;box-shadow:0 1px 4px #0001}
  table{width:100%;border-collapse:collapse} th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;font-size:14px}
  th{background:#f8f9fa} label{display:block;margin:12px 0 4px;font-weight:bold;font-size:14px}
  input,select{width:100%;padding:9px;border:1px solid #ccc;border-radius:4px}
  .btn{display:inline-block;padding:7px 12px;border:0;border-radius:4px;background:#0d6efd;color:#fff;cursor:pointer;text-decoration:none;font-size:13px}
  .btn.g{background:#6c757d}.btn.r{background:#dc3545}.btn.v{background:#198754}.btn.o{background:#fd7e14}
  .ok{background:#d1e7dd;padding:10px;border-radius:4px;margin-bottom:12px}
  .err{background:#f8d7da;padding:10px;border-radius:4px;margin-bottom:12px}
  .badge{padding:2px 8px;border-radius:10px;font-size:12px;color:#fff}
  .b-admin{background:#6f42c1}.b-patient{background:#0dcaf0;color:#000}.b-on{background:#198754}.b-off{background:#dc3545}
  .err a,.ok a{word-break:break-all}
  form.inline{display:inline} .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media(max-width:700px){.row{grid-template-columns:1fr}}
</style>
</head>
<body>
<nav>
  <strong>LabConnect</strong>
  <a href="<?= BASE_URL ?>/index.php">Accueil</a>
  <?php if ($me && $me['role'] === 'ADMIN'): ?>
    <a href="<?= BASE_URL ?>/users/index.php">Utilisateurs</a>
  <?php endif; ?>
  <span class="sp"></span>
  <?php if ($me): ?>
    <a href="<?= BASE_URL ?>/profile.php"><?= e($me['prenom'] . ' ' . $me['nom']) ?> (<?= e($me['role']) ?>)</a>
    <a href="<?= BASE_URL ?>/logout.php">Déconnexion</a>
  <?php else: ?>
    <a href="<?= BASE_URL ?>/login.php">Connexion</a>
    <a href="<?= BASE_URL ?>/register.php">Inscription</a>
  <?php endif; ?>
</nav>
<main>
<?php if ($flash): ?><div class="<?= $flash[1] === 'ok' ? 'ok' : 'err' ?>"><?= e($flash[0]) ?></div><?php endif; ?>
