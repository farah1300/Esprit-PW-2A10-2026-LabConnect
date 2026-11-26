<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$id = (int)($_GET['id'] ?? 0);
$u = User::find($id);
if (!$u) { flash("Utilisateur introuvable.", 'err'); redirect('/users/index.php'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $errors = User::validate($_POST, false, $id);
    $u = array_merge($u, $_POST);
    if (!$errors) {
        User::update($id, $_POST);
        flash("Utilisateur modifié.");
        redirect('/users/index.php');
    }
}
$isNew = false; $showRole = false; $submitLabel = "Enregistrer";
include __DIR__ . '/../includes/header.php';
?>
<div class="card"><h2>Modifier l'utilisateur #<?= $id ?></h2>
<?php include __DIR__ . '/../includes/user_form.php'; ?>
<a href="index.php">← Retour</a></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
