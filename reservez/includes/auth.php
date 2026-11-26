<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function redirect(string $path): void { header('Location: ' . BASE_URL . $path); exit; }

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

// Protection CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403); exit('Requête invalide (CSRF).');
    }
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    $u = User::find((int)$_SESSION['user_id']);
    if (!$u || !$u['actif']) { session_destroy(); return null; }
    return $u;
}

function requireLogin(): array
{
    $u = currentUser();
    if (!$u) redirect('/login.php');
    return $u;
}

function requireAdmin(): array
{
    $u = requireLogin();
    if ($u['role'] !== 'ADMIN') { http_response_code(403); exit('Accès réservé aux administrateurs.'); }
    return $u;
}
