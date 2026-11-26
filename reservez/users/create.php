<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$errors = []; $u = ['role' => 'PATIENT'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = $_POST;
    $errors = User::validate($u, true);
    if (!$errors) {
        User::create($u);
        flash("Utilisateur ajouté.");
        redirect('/users/index.php');
    }
}
$isNew = true; $showRole = true; $submitLabel = "Ajouter";
include __DIR__ . '/../includes/header.php';
?>
<div class="card"><h2>Ajouter un utilisateur</h2>
<?php include __DIR__ . '/../includes/user_form.php'; ?>
<a href="index.php">← Retour</a></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
