<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$u = User::find((int)($_GET['id'] ?? 0));
if (!$u) { flash("Utilisateur introuvable.", 'err'); redirect('/users/index.php'); }
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2><?= e($u['prenom'] . ' ' . $u['nom']) ?></h2>
  <table>
    <tr><th>ID</th><td><?= $u['id'] ?></td></tr>
    <tr><th>Email</th><td><?= e($u['email']) ?></td></tr>
    <tr><th>Téléphone</th><td><?= e($u['telephone'] ?: '—') ?></td></tr>
    <tr><th>Date de naissance</th><td><?= e($u['date_naissance'] ?: '—') ?></td></tr>
    <tr><th>Sexe</th><td><?= e($u['sexe'] ?: '—') ?></td></tr>
    <tr><th>Adresse</th><td><?= e($u['adresse'] ?: '—') ?></td></tr>
    <tr><th>Rôle</th><td><span class="badge b-<?= strtolower($u['role']) ?>"><?= e($u['role']) ?></span></td></tr>
    <tr><th>Statut</th><td><span class="badge <?= $u['actif'] ? 'b-on' : 'b-off' ?>"><?= $u['actif'] ? 'Actif' : 'Désactivé' ?></span></td></tr>
    <tr><th>Créé le</th><td><?= e($u['date_creation']) ?></td></tr>
    <tr><th>Modifié le</th><td><?= e($u['date_modification']) ?></td></tr>
  </table>
  <p><a class="btn" href="edit.php?id=<?= $u['id'] ?>">Modifier</a> <a class="btn g" href="index.php">Retour</a></p>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
