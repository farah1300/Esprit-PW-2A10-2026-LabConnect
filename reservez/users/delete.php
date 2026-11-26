<?php
require_once __DIR__ . '/../includes/auth.php';
$me = requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/users/index.php');
csrf_check();
$id = (int)($_POST['id'] ?? 0);
if ($id === (int)$me['id']) {
    flash("Vous ne pouvez pas supprimer votre propre compte.", 'err');
} else {
    User::delete($id);
    flash("Utilisateur supprimé.");
}
redirect('/users/index.php');
