<?php
require_once __DIR__ . '/includes/auth.php';
$me = requireLogin();

$errors = []; $u = $me;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = array_merge($me, $_POST);
    $errors = User::validate($_POST, false, (int)$me['id']);
    if (!$errors) {
        User::update((int)$me['id'], $_POST);   // le rôle n'est jamais modifiable ici
        flash("Profil mis à jour.");
        redirect('/profile.php');
    }
}
$isNew = false; $showRole = false; $submitLabel = "Enregistrer";
include __DIR__ . '/includes/header.php';
?>
<div class="card"><h2>Mon profil</h2>
<?php include __DIR__ . '/includes/user_form.php'; ?></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
