<?php
require_once __DIR__ . '/../includes/auth.php';
$me = requireAdmin();
$q = trim($_GET['q'] ?? '');
$users = User::all($q);
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <h2>Gestion des utilisateurs</h2>
    <a class="btn v" href="create.php">+ Ajouter</a>
  </div>
  <form method="get" style="display:flex;gap:8px;margin-bottom:14px">
    <input name="q" value="<?= e($q) ?>" placeholder="Rechercher (nom, prénom, email, téléphone)">
    <button class="btn">Rechercher</button>
    <?php if ($q): ?><a class="btn g" href="index.php">Reset</a><?php endif; ?>
  </form>
  <div style="overflow-x:auto">
  <table>
    <tr><th>#</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr>
    <?php foreach ($users as $x): ?>
    <tr>
      <td><?= $x['id'] ?></td>
      <td><?= e($x['prenom'] . ' ' . $x['nom']) ?></td>
      <td><?= e($x['email']) ?></td>
      <td>
        <form class="inline" method="post" action="role.php">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>">
          <select name="role" onchange="this.form.submit()" style="width:auto;padding:4px"
                  <?= $x['id'] == $me['id'] ? 'disabled' : '' ?>>
            <option value="PATIENT" <?= $x['role'] === 'PATIENT' ? 'selected' : '' ?>>Patient</option>
            <option value="ADMIN" <?= $x['role'] === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
          </select>
        </form>
      </td>
      <td><span class="badge <?= $x['actif'] ? 'b-on' : 'b-off' ?>"><?= $x['actif'] ? 'Actif' : 'Désactivé' ?></span></td>
      <td>
        <a class="btn g" href="show.php?id=<?= $x['id'] ?>">Voir</a>
        <a class="btn" href="edit.php?id=<?= $x['id'] ?>">Modifier</a>
        <?php if ($x['id'] != $me['id']): ?>
        <form class="inline" method="post" action="toggle.php">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>">
          <button class="btn o"><?= $x['actif'] ? 'Désactiver' : 'Activer' ?></button>
        </form>
        <form class="inline" method="post" action="delete.php" onsubmit="return confirm('Supprimer cet utilisateur ?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>">
          <button class="btn r">Supprimer</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$users): ?><tr><td colspan="6">Aucun utilisateur trouvé.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
