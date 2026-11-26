<?php
/**
 * Contrôleur du module Utilisateurs.
 *
 * Les pages de TASK 4 appellent ces méthodes et affichent les données ou
 * les erreurs retournées. Toutes les règles métier sensibles (rôle, dernier
 * Admin, compte désactivé, CSRF) sont vérifiées ici, côté serveur.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../../includes/Validator.php';

class UtilisateurController
{
    private Utilisateur $utilisateurs;
    private Role $roles;
    private Validator $validator;

    public function __construct(PDO $pdo)
    {
        $this->utilisateurs = new Utilisateur($pdo);
        $this->roles = new Role($pdo);
        $this->validator = new Validator($this->utilisateurs, $this->roles);
    }

    /**
     * Connexion — login.php.
     *
     * Un seul message pour « e-mail inconnu » et « mauvais mot de passe » :
     * un attaquant ne peut pas deviner quel compte existe.
     */
    public function login(): array
    {
        if (isLoggedIn()) {
            $this->redirect(BASE_URL . 'index.php');
        }

        if (!$this->isPost()) {
            return ['errors' => [], 'old' => []];
        }

        verifyCsrf();

        $email = $this->postString('email');
        $password = $this->postRaw('password');
        $old = ['email' => $email];
        $genericError = 'Email ou mot de passe incorrect.';

        if ($email === '' || $password === '') {
            return ['errors' => ['login' => $genericError], 'old' => $old];
        }

        $user = $this->utilisateurs->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return ['errors' => ['login' => $genericError], 'old' => $old];
        }

        // Le message n'est affiché qu'après un mot de passe correct : il
        // n'aide donc pas à énumérer les comptes.
        if ((int) $user['is_active'] !== 1) {
            return [
                'errors' => ['login' => 'Votre compte est désactivé. Contactez un administrateur.'],
                'old' => $old,
            ];
        }

        // Changement d'identifiant de session au moment où les privilèges
        // changent : un cookie volé avant la connexion devient inutilisable.
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'     => (int) $user['id'],
            'nom'    => $user['nom'],
            'prenom' => $user['prenom'],
            'email'  => $user['email'],
            'role'   => $user['role'],
        ];

        // Nouveau jeton CSRF après la connexion.
        unset($_SESSION['csrf_token']);

        $this->utilisateurs->touchLastLogin((int) $user['id']);

        setFlash('success', 'Bienvenue, ' . $user['prenom'] . ' !');
        $this->redirect(BASE_URL . 'index.php');
    }

    /**
     * Déconnexion — logout.php.
     * Supprime aussi le cookie de session pour empêcher sa réutilisation.
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        $this->redirect(USER_MODULE_URL . 'login.php');
    }

    /**
     * Auto-inscription publique — inscription.php.
     *
     * Le rôle est forcé à Patient : un role_id envoyé dans le POST est
     * volontairement ignoré (tentative d'élévation de privilèges).
     */
    public function register(): array
    {
        if (isLoggedIn()) {
            $this->redirect(BASE_URL . 'index.php');
        }

        if (!$this->isPost()) {
            return ['errors' => [], 'old' => []];
        }

        verifyCsrf();

        $data = $this->collectUserInput();

        $patientRole = $this->roles->findByName('Patient');
        if ($patientRole === null) {
            return [
                'errors' => ['general' => 'Le rôle Patient est introuvable. Contactez un administrateur.'],
                'old' => $this->sticky($data),
            ];
        }

        $data['role_id'] = (int) $patientRole['id'];
        $data['is_active'] = 1;

        $errors = $this->validator->validateUtilisateur($data, null, true);
        if ($errors !== []) {
            return ['errors' => $errors, 'old' => $this->sticky($data)];
        }

        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
            $this->utilisateurs->create($data);
        } catch (PDOException $e) {
            if ($this->isDuplicateKey($e)) {
                return [
                    'errors' => ['email' => "Cette adresse e-mail est déjà utilisée."],
                    'old' => $this->sticky($data),
                ];
            }
            throw $e;
        }

        setFlash('success', 'Votre compte a été créé. Vous pouvez maintenant vous connecter.');
        $this->redirect(USER_MODULE_URL . 'login.php');
    }

    /**
     * Liste, recherche et filtre des utilisateurs — utilisateurs.php.
     * Paramètres GET : q (recherche), role (identifiant de rôle).
     *
     * La recherche est validée ici, côté serveur : 100 caractères maximum.
     * Le JavaScript fait la même vérification pour le confort, mais il peut
     * être désactivé ; cette limite protège donc réellement la requête.
     */
    public function index(): array
    {
        requireRole('Admin');

        $search = '';
        $searchError = '';

        if (isset($_GET['q'])) {
            if (!is_string($_GET['q'])) {
                $searchError = 'Recherche invalide.';
            } else {
                $search = trim($_GET['q']);

                if (mb_strlen($search, 'UTF-8') > 100) {
                    $searchError = 'La recherche est limitée à 100 caractères.';
                    $search = mb_substr($search, 0, 100, 'UTF-8');
                }
            }
        }

        $roleId = null;
        $roleParam = $_GET['role'] ?? '';
        if (is_string($roleParam) && $roleParam !== '') {
            $candidate = (int) $roleParam;
            if ($candidate > 0 && $this->roles->find($candidate) !== null) {
                $roleId = $candidate;
            }
        }

        $users = $this->utilisateurs->all($search, $roleId);

        // Les statistiques portent toujours sur TOUS les comptes, même
        // quand la liste est filtrée : un filtre ne doit pas faire croire
        // qu'il n'existe qu'un seul utilisateur dans la base.
        $allUsers = ($search === '' && $roleId === null)
            ? $users
            : $this->utilisateurs->all();

        $stats = [
            'total'         => count($allUsers),
            'active'        => 0,
            'inactive'      => 0,
            'admins'        => 0,
            'patients'      => 0,
            'activeAdmins'  => 0,
        ];

        foreach ($allUsers as $row) {
            $isActive = (int) $row['is_active'] === 1;
            $isAdmin  = $row['role'] === 'Admin';

            if ($isActive) {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }

            if ($isAdmin) {
                $stats['admins']++;
                if ($isActive) {
                    $stats['activeAdmins']++;
                }
            } else {
                $stats['patients']++;
            }
        }

        return [
            'users'  => $users,
            'roles'  => $this->roles->all(),
            'search' => $search,
            'searchError' => $searchError,
            'roleId' => $roleId,
            'stats'  => $stats,
        ];
    }

    /**
     * Données de la page roles.php : liste des rôles avec leur effectif et
     * liste des utilisateurs pour le sélecteur de changement de rôle.
     */
    public function rolesPage(): array
    {
        requireRole('Admin');

        $roles = $this->roles->all();

        foreach ($roles as &$role) {
            $role['count'] = $this->roles->countUsers((int) $role['id']);
        }
        unset($role);

        return [
            'roles'        => $roles,
            'users'        => $this->utilisateurs->all(),
            'activeAdmins' => $this->utilisateurs->countActiveAdmins(),
        ];
    }

    /**
     * Création d'un utilisateur par un Admin — ajouter-utilisateur.php.
     * Seul un Admin arrive ici, donc il peut créer un Admin ou un Patient.
     */
    public function create(): array
    {
        requireRole('Admin');

        $roles = $this->roles->all();

        if (!$this->isPost()) {
            return ['errors' => [], 'old' => [], 'roles' => $roles];
        }

        verifyCsrf();

        $data = $this->collectUserInput();
        $data['role_id'] = $this->postInt('role_id');
        $data['is_active'] = 1; // un compte créé par l'Admin est actif au départ

        $errors = $this->validator->validateUtilisateur($data, null, true);
        if ($errors !== []) {
            return [
                'errors' => $errors,
                'old' => $this->sticky($data),
                'roles' => $roles,
            ];
        }

        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
            $this->utilisateurs->create($data);
        } catch (PDOException $e) {
            if ($this->isDuplicateKey($e)) {
                return [
                    'errors' => ['email' => "Cette adresse e-mail est déjà utilisée."],
                    'old' => $this->sticky($data),
                    'roles' => $roles,
                ];
            }
            throw $e;
        }

        setFlash('success', "L'utilisateur a été ajouté.");
        $this->redirect(USER_MODULE_URL . 'utilisateurs.php');
    }

    /**
     * Modification d'un utilisateur — modifier-utilisateur.php.
     * Le mot de passe est optionnel : vide = ancien hash conservé.
     */
    public function edit(int $id): array
    {
        requireRole('Admin');

        $user = $this->utilisateurs->find($id);
        if ($user === null) {
            $this->redirectWithFlash(
                'danger',
                'Utilisateur introuvable.',
                USER_MODULE_URL . 'utilisateurs.php'
            );
        }

        $roles = $this->roles->all();

        if (!$this->isPost()) {
            return [
                'user' => $user,
                'errors' => [],
                'old' => $user,
                'roles' => $roles,
            ];
        }

        verifyCsrf();

        $data = $this->collectUserInput();
        $data['role_id'] = $this->postInt('role_id');

        // Le mot de passe est facultatif en modification, mais si l'un des
        // deux champs est rempli, la paire complète doit être valide.
        $changePassword = $data['password'] !== '';
        $validatePassword = $changePassword || $data['password_confirm'] !== '';

        $errors = $this->validator->validateUtilisateur($data, $id, $validatePassword);

        // Règle métier : le dernier Admin actif ne peut pas être rétrogradé.
        $newRole = $this->roles->find((int) $data['role_id']);
        if ($newRole !== null
            && $newRole['nom'] !== 'Admin'
            && (int) $user['role_id'] !== (int) $data['role_id']
            && $this->isLastActiveAdmin($user)
        ) {
            $errors['role_id'] = 'Impossible de retirer le rôle Admin au dernier administrateur actif.';
        }

        if ($errors !== []) {
            return [
                'user' => $user,
                'errors' => $errors,
                'old' => $this->sticky($data),
                'roles' => $roles,
            ];
        }

        if ($changePassword) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password_hash']);
        }

        try {
            $this->utilisateurs->update($id, $data);
        } catch (PDOException $e) {
            if ($this->isDuplicateKey($e)) {
                return [
                    'user' => $user,
                    'errors' => ['email' => "Cette adresse e-mail est déjà utilisée."],
                    'old' => $this->sticky($data),
                    'roles' => $roles,
                ];
            }
            throw $e;
        }

        setFlash('success', "L'utilisateur a été modifié.");
        $this->redirect(USER_MODULE_URL . 'utilisateurs.php');
    }

    /**
     * Fiche d'un utilisateur — details-utilisateur.php.
     */
    public function show(int $id): array
    {
        requireRole('Admin');

        $user = $this->utilisateurs->find($id);
        if ($user === null) {
            $this->redirectWithFlash(
                'danger',
                'Utilisateur introuvable.',
                USER_MODULE_URL . 'utilisateurs.php'
            );
        }

        return $user;
    }

    /**
     * Suppression — bouton Supprimer de utilisateurs.php (POST + CSRF).
     * Interdit sur son propre compte et sur le dernier Admin actif.
     */
    public function destroy(int $id): void
    {
        requireRole('Admin');
        $this->requirePost(USER_MODULE_URL . 'utilisateurs.php');
        verifyCsrf();

        $user = $this->utilisateurs->find($id);
        if ($user === null) {
            $this->redirectWithFlash(
                'danger',
                'Utilisateur introuvable.',
                USER_MODULE_URL . 'utilisateurs.php'
            );
        }

        if ($this->currentUserId() === $id) {
            $this->redirectWithFlash(
                'danger',
                'Vous ne pouvez pas supprimer votre propre compte.',
                USER_MODULE_URL . 'utilisateurs.php'
            );
        }

        if ($this->isLastActiveAdmin($user)) {
            $this->redirectWithFlash(
                'danger',
                'Impossible de supprimer le dernier administrateur actif.',
                USER_MODULE_URL . 'utilisateurs.php'
            );
        }

        $this->utilisateurs->delete($id);
        $this->redirectWithFlash(
            'success',
            "L'utilisateur a été supprimé.",
            USER_MODULE_URL . 'utilisateurs.php'
        );
    }

    /**
     * Activation / désactivation — bouton de utilisateurs.php
     * (POST + CSRF). Interdit sur son propre compte et sur le dernier
     * Admin actif lors d'une désactivation.
     */
    public function toggleActive(int $id): void
    {
        requireRole('Admin');
        $this->requirePost(USER_MODULE_URL . 'utilisateurs.php');
        verifyCsrf();

        $user = $this->utilisateurs->find($id);
        if ($user === null) {
            $this->redirectWithFlash(
                'danger',
                'Utilisateur introuvable.',
                USER_MODULE_URL . 'utilisateurs.php'
            );
        }

        $isActive = (int) $user['is_active'] === 1;

        if ($isActive && $this->currentUserId() === $id) {
            $this->redirectWithFlash(
                'danger',
                'Vous ne pouvez pas désactiver votre propre compte.',
                USER_MODULE_URL . 'utilisateurs.php'
            );
        }

        if ($isActive && $this->isLastActiveAdmin($user)) {
            $this->redirectWithFlash(
                'danger',
                'Impossible de désactiver le dernier administrateur actif.',
                USER_MODULE_URL . 'utilisateurs.php'
            );
        }

        $this->utilisateurs->setActive($id, !$isActive);

        $message = $isActive ? 'Le compte a été désactivé.' : 'Le compte a été activé.';
        $this->redirectWithFlash('success', $message, USER_MODULE_URL . 'utilisateurs.php');
    }

    /**
     * Changement de rôle — sélecteur de roles.php (POST + CSRF).
     * Seul un Admin y accède ; le dernier Admin actif ne peut pas être
     * rétrogradé.
     */
    public function changeRole(int $id): void
    {
        requireRole('Admin');
        $this->requirePost(USER_MODULE_URL . 'roles.php');
        verifyCsrf();

        $user = $this->utilisateurs->find($id);
        if ($user === null) {
            $this->redirectWithFlash(
                'danger',
                'Utilisateur introuvable.',
                USER_MODULE_URL . 'roles.php'
            );
        }

        $roleId = $this->postInt('role_id');
        $newRole = $this->roles->find($roleId);

        if ($newRole === null) {
            $this->redirectWithFlash(
                'danger',
                'Le rôle sélectionné est invalide.',
                USER_MODULE_URL . 'roles.php'
            );
        }

        if ((int) $user['role_id'] === $roleId) {
            $this->redirectWithFlash(
                'info',
                'Cet utilisateur possède déjà ce rôle.',
                USER_MODULE_URL . 'roles.php'
            );
        }

        if ($user['role'] === 'Admin'
            && $newRole['nom'] !== 'Admin'
            && $this->isLastActiveAdmin($user)
        ) {
            $this->redirectWithFlash(
                'danger',
                'Impossible de retirer le rôle Admin au dernier administrateur actif.',
                USER_MODULE_URL . 'roles.php'
            );
        }

        $this->utilisateurs->setRole($id, $roleId);
        $this->redirectWithFlash(
            'success',
            'Le rôle a été modifié.',
            USER_MODULE_URL . 'roles.php'
        );
    }

    // --- Aides internes -------------------------------------------------

    private function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    /**
     * Refuse les actions sensibles appelées en GET (ex. lien cliqué) :
     * elles doivent passer par un formulaire POST protégé par CSRF.
     */
    private function requirePost(string $redirectUrl): void
    {
        if (!$this->isPost()) {
            $this->redirectWithFlash('warning', 'Action invalide : utilisez le bouton prévu.', $redirectUrl);
        }
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function redirectWithFlash(string $type, string $message, string $url): void
    {
        setFlash($type, $message);
        $this->redirect($url);
    }

    /**
     * Récupère uniquement les champs texte du formulaire utilisateur.
     * Les mots de passe ne sont pas trimés : un espace peut être volontaire.
     */
    private function collectUserInput(): array
    {
        return [
            'nom' => $this->postString('nom'),
            'prenom' => $this->postString('prenom'),
            'email' => $this->postString('email'),
            'telephone' => $this->postString('telephone'),
            'password' => $this->postRaw('password'),
            'password_confirm' => $this->postRaw('password_confirm'),
        ];
    }

    /**
     * Valeurs à réafficher dans le formulaire après une erreur (sticky).
     * Les mots de passe ne sont jamais renvoyés au navigateur.
     */
    private function sticky(array $data): array
    {
        unset($data['password'], $data['password_confirm'], $data['password_hash']);

        return $data;
    }

    private function postRaw(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    private function postString(string $key): string
    {
        return trim($this->postRaw($key));
    }

    private function postInt(string $key): int
    {
        $value = $_POST[$key] ?? null;

        return is_int($value) || is_string($value) ? (int) $value : 0;
    }

    private function currentUserId(): int
    {
        $user = currentUser();

        return (int) ($user['id'] ?? 0);
    }

    /**
     * Vrai si cet utilisateur est un Admin actif et qu'il est le seul.
     * Appelé avant suppression, désactivation ou rétrogradation.
     */
    private function isLastActiveAdmin(array $user): bool
    {
        return ($user['role'] ?? '') === 'Admin'
            && (int) ($user['is_active'] ?? 0) === 1
            && $this->utilisateurs->countActiveAdmins() <= 1;
    }

    /**
     * Détecte une violation de la contrainte UNIQUE sur l'e-mail.
     * La vérification préalable du Validator ne suffit pas en cas de
     * deux requêtes simultanées : la base reste le juge final.
     */
    private function isDuplicateKey(PDOException $e): bool
    {
        return (string) $e->getCode() === '23000'
            || (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
