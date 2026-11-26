<?php
// Variables attendues : $u (valeurs), $errors, $showRole (bool), $isNew (bool), $submitLabel
?>
<?php foreach ($errors as $err): ?><div class="err"><?= e($err) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="row">
    <div><label>Nom *</label><input name="nom" value="<?= e($u['nom'] ?? '') ?>" required></div>
    <div><label>Prénom *</label><input name="prenom" value="<?= e($u['prenom'] ?? '') ?>" required></div>
    <div><label>Email *</label><input type="email" name="email" value="<?= e($u['email'] ?? '') ?>" required></div>
    <div><label>Mot de passe <?= $isNew ? '*' : '(laisser vide pour ne pas changer)' ?></label>
         <input type="password" name="mot_de_passe" <?= $isNew ? 'required' : '' ?>></div>
    <div><label>Téléphone</label><input name="telephone" value="<?= e($u['telephone'] ?? '') ?>"></div>
    <div><label>Date de naissance</label><input type="date" name="date_naissance" value="<?= e($u['date_naissance'] ?? '') ?>"></div>
    <div><label>Sexe</label>
      <select name="sexe">
        <option value="">--</option>
        <option value="H" <?= ($u['sexe'] ?? '') === 'H' ? 'selected' : '' ?>>Homme</option>
        <option value="F" <?= ($u['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>Femme</option>
      </select></div>
    <div><label>Adresse</label><input name="adresse" value="<?= e($u['adresse'] ?? '') ?>"></div>
    <?php if ($showRole): ?>
    <div><label>Rôle</label>
      <select name="role">
        <option value="PATIENT" <?= ($u['role'] ?? 'PATIENT') === 'PATIENT' ? 'selected' : '' ?>>Patient</option>
        <option value="ADMIN" <?= ($u['role'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
      </select></div>
    <?php endif; ?>
  </div>
  <p><button class="btn v"><?= e($submitLabel) ?></button></p>
</form>
