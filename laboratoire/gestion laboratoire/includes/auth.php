<?php
/**
 * Authentification, rôles, protection CSRF et messages flash.
 *
 * Toute page protégée commence par :
 *   require_once __DIR__ . '/includes/auth.php';   // page à la racine
 *   require_once __DIR__ . '/../includes/auth.php'; // page du module utilisateur
 *   requireLogin();            // tout utilisateur connecté
 *   requireRole('Admin');      // Admin uniquement (appelle requireLogin)
 *
 * Ce fichier doit être inclus AVANT tout affichage HTML : il utilise
 * session_start() et header() (redirections).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// --- Démarrage sécurisé de la session -------------------------------
if (session_status() === PHP_SESSION_NONE) {
    // Strict mode : PHP refuse un identifiant de session qu'il n'a pas
    // lui-même généré. Un attaquant ne peut donc pas imposer un identifiant
    // connu à la victime (attaque par fixation de session).
    ini_set('session.use_strict_mode', '1');

    // L'identifiant circule uniquement dans le cookie : jamais dans l'URL,
    // où il finirait dans les historiques, les logs et les liens partagés.
    ini_set('session.use_only_cookies', '1');

    session_name('LABCONNECTSESSID');

    session_set_cookie_params([
        'lifetime' => 0,        // cookie supprimé à la fermeture du navigateur
        'path'     => '/',
        'httponly' => true,     // inaccessible en JavaScript (limite le vol via XSS)
        'samesite' => 'Lax',    // non envoyé depuis un site tiers (limite le CSRF)
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);

    session_start();

    // À la connexion, UtilisateurController::login() appellera
    // session_regenerate_id(true) pour changer l'identifiant de session.
}

// --- Utilisateur connecté --------------------------------------------

/**
 * Retourne l'utilisateur stocké en session, ou null.
 * Clés disponibles : id, nom, prenom, email, role.
 */
function currentUser(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

/**
 * Indique si une session utilisateur existe, sans interroger la base.
 * requireLogin() fait la vérification complète (compte actif ou non).
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user']['id']);
}

/**
 * Bloque l'accès si aucun utilisateur n'est connecté.
 * Revérifie aussi le compte en base à chaque requête : un compte supprimé
 * ou désactivé (statut = 0) est déconnecté immédiatement, même s'il
 * possède encore un cookie de session valide.
 */
function requireLogin(): void
{
    // Une seule vérification en base par requête, même si la page,
    // header.php et sidebar.php appellent toutes requireLogin().
    static $checked = false;

    if (!isLoggedIn()) {
        setFlash('warning', 'Veuillez vous connecter pour accéder à cette page.');
        header('Location: ' . USER_MODULE_URL . 'login.php');
        exit;
    }

    if ($checked) {
        return;
    }

    global $pdo;

    // Le rôle est relu depuis la base, pas depuis le cookie : une
    // rétrogradation ou un changement de rôle s'applique dès la requête
    // suivante. La requête est préparée : aucun identifiant n'est concaténé.
    // La colonne de base s'appelle "statut" ; l'alias "is_active" garde le
    // même nom dans le reste du code (session, vues, contrôleur).
    $stmt = $pdo->prepare(
        'SELECT u.id, u.nom, u.prenom, u.email, u.statut AS is_active, r.nom AS role
         FROM utilisateurs AS u
         INNER JOIN roles AS r ON r.id = u.id_role
         WHERE u.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => (int) $_SESSION['user']['id']]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    $invalidMessage = null;

    if ($account === false) {
        $invalidMessage = 'Votre session a expiré. Veuillez vous reconnecter.';
    } elseif ((int) $account['is_active'] !== 1) {
        $invalidMessage = 'Votre compte a été désactivé. Contactez un administrateur.';
    }

    if ($invalidMessage !== null) {
        // On supprime l'utilisateur de la session mais on garde la session
        // le temps du message flash. Le nouvel identifiant de session rend
        // inutilisable un éventuel cookie volé.
        unset($_SESSION['user']);
        session_regenerate_id(true);
        setFlash('warning', $invalidMessage);
        header('Location: ' . USER_MODULE_URL . 'login.php');
        exit;
    }

    // On réécrit les données utiles depuis la base : le rôle affiché et
    // contrôlé par requireRole() ne peut pas être falsifié côté client.
    $_SESSION['user'] = [
        'id'     => (int) $account['id'],
        'nom'    => $account['nom'],
        'prenom' => $account['prenom'],
        'email'  => $account['email'],
        'role'   => $account['role'],
    ];

    $checked = true;
}

/**
 * Réserve une page à un rôle précis (ex. requireRole('Admin')).
 *
 * Masquer un lien dans le menu ne protège rien : c'est ce contrôle
 * serveur qui empêche un Patient d'ouvrir une page Admin en tapant l'URL.
 */
function requireRole(string $roleName): void
{
    requireLogin();

    $actualRole = $_SESSION['user']['role'] ?? '';

    if ($actualRole !== $roleName) {
        $labels = [
            'Admin'   => 'administrateurs',
            'Patient' => 'patients',
        ];
        $label = $labels[$roleName] ?? 'utilisateurs autorisés';

        setFlash('danger', 'Accès refusé : cette page est réservée aux ' . $label . '.');
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

// --- Messages flash (affichés une seule fois) ------------------------

/**
 * Enregistre un message pour la prochaine page.
 * Types Bootstrap autorisés : success, danger, warning, info.
 */
function setFlash(string $type, string $message): void
{
    $allowed = ['success', 'danger', 'warning', 'info'];
    if (!in_array($type, $allowed, true)) {
        $type = 'info';
    }

    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Récupère puis supprime le message flash.
 * Utilisé par header.php et par showFlash().
 */
function getFlash(): ?array
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    if (!isset($flash['type'], $flash['message'])) {
        return null;
    }

    return $flash;
}

/**
 * Affiche le message flash en HTML Bootstrap, puis le consomme.
 * Le type est limité à une liste blanche et le texte est échappé : même
 * un message contenant du HTML ne peut pas injecter de balise.
 */
function showFlash(): void
{
    $flash = getFlash();
    if ($flash === null) {
        return;
    }

    $allowed = ['success', 'danger', 'warning', 'info'];
    $type = in_array($flash['type'], $allowed, true) ? $flash['type'] : 'info';

    echo '<div class="alert alert-' . $type . ' alert-dismissible" role="alert">'
        . '<button type="button" class="close" data-dismiss="alert" aria-label="Fermer">'
        . '<span aria-hidden="true">&times;</span></button>'
        . htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8')
        . '</div>';
}

// --- Protection CSRF -------------------------------------------------

/**
 * Retourne le jeton CSRF de la session (créé au premier appel).
 *
 * random_bytes() produit 32 octets imprévisibles : un site tiers ne peut
 * pas deviner le jeton et donc pas rejouer un formulaire à la place de
 * l'utilisateur (Cross-Site Request Forgery).
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Retourne le champ caché à insérer dans chaque formulaire POST :
 *   <?= csrfField() ?>
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Vérifie le jeton CSRF de chaque POST. À appeler en tout début de
 * traitement, AVANT de lire ou modifier quoi que ce soit en base.
 *
 * hash_equals() compare en temps constant : la durée de la comparaison
 * ne révèle pas combien de caractères du jeton sont corrects.
 */
function verifyCsrf(): void
{
    $sentToken = $_POST['csrf_token'] ?? '';

    if (!is_string($sentToken) || !hash_equals(csrfToken(), $sentToken)) {
        http_response_code(403);
        exit('Session expirée ou requête invalide. Rechargez la page et réessayez.');
    }
}
