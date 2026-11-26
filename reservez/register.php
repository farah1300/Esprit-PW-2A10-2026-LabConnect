<?php
require_once __DIR__ . '/includes/auth.php';
if (currentUser()) redirect('/index.php');

$errors = []; $u = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = $_POST;
    $errors = User::validate($u, true);
    if (!$errors) {
        $u['role'] = 'PATIENT';   // l'inscription publique crée toujours un patient
        User::create($u);
        flash("Compte créé, vous pouvez vous connecter.");
        redirect('/login.php');
    }
}
$isNew = true; $showRole = false; $submitLabel = "S'inscrire";
include __DIR__ . '/includes/header.php';
?>
<div class="card"><h2>Inscription</h2>
<?php include __DIR__ . '/includes/user_form.php'; ?></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
