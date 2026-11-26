<?php
/**
 * API JSON des laboratoires.
 *
 * GET    /api/laboratoires.php                 → liste
 * GET    /api/laboratoires.php?id=1            → fiche + analyses
 * GET    /api/laboratoires.php?ville=Tunis&q=… → liste filtrée
 * GET    /api/laboratoires.php?csrf=1          → jeton CSRF de la session
 * POST   /api/laboratoires.php                 → création (Admin)
 * PUT    /api/laboratoires.php?id=1            → modification (Admin)
 * DELETE /api/laboratoires.php?id=1            → suppression (Admin)
 *
 * Les écritures exigent une session Admin ET le jeton CSRF, envoyé dans
 * l'en-tête X-CSRF-Token ou dans le corps (csrf_token).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Laboratoire.php';
require_once __DIR__ . '/../includes/LaboratoireValidator.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

/**
 * Réponse JSON de succès.
 */
function apiRespond(array $payload, int $status = 200): void
{
    http_response_code($status);

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    echo $json === false ? '{"error":"Erreur d\'encodage JSON."}' : $json;
    exit;
}

/**
 * Réponse JSON d'erreur avec le code HTTP approprié.
 */
function apiFail(int $status, string $message, array $extra = []): void
{
    apiRespond(array_merge([
        'error'   => true,
        'status'  => $status,
        'message' => $message,
    ], $extra), $status);
}

/**
 * Toute exception non prévue reste une réponse JSON, jamais une page HTML.
 */
set_exception_handler(static function (Throwable $e): void {
    error_log('API laboratoires : ' . $e->getMessage());

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode([
        'error'   => true,
        'status'  => 500,
        'message' => 'Erreur interne du serveur.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
});

/**
 * Vérifie la session et relit le compte en base (compte actif).
 */
function apiRequireUser(): array
{
    $sessionUser = currentUser();

    if ($sessionUser === null) {
        apiFail(401, 'Authentification requise.');
    }

    $account = fetchAccount((int) $sessionUser['id']);

    if ($account === null || (int) $account['is_active'] !== 1) {
        unset($_SESSION['user']);
        apiFail(401, 'Session expirée ou compte désactivé.');
    }

    $_SESSION['user'] = [
        'id'     => (int) $account['id'],
        'nom'    => $account['nom'],
        'prenom' => $account['prenom'],
        'email'  => $account['email'],
        'role'   => $account['role'],
    ];

    return $_SESSION['user'];
}

/**
 * Réserve une opération d'écriture à l'Admin.
 */
function apiRequireAdmin(array $user): void
{
    if (($user['role'] ?? '') !== 'Admin') {
        apiFail(403, 'Accès réservé aux administrateurs.');
    }
}

/**
 * Vérifie le jeton CSRF pour POST / PUT / DELETE.
 * hash_equals() évite une comparaison en temps variable.
 */
function apiRequireCsrf(array $input): void
{
    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');

    if (!is_string($sentToken) || $sentToken === '' || !hash_equals(csrfToken(), $sentToken)) {
        apiFail(403, 'Jeton CSRF invalide ou absent.');
    }
}

/**
 * Représentation JSON stable d'un laboratoire.
 */
function apiLaboratoire(array $lab): array
{
    return [
        'id'          => (int) $lab['id'],
        'nom'         => (string) $lab['nom'],
        'adresse'     => (string) $lab['adresse'],
        'ville'       => (string) $lab['ville'],
        'telephone'   => (string) $lab['telephone'],
        'email'       => (string) $lab['email'],
        'horaires'    => $lab['horaires'] !== null ? (string) $lab['horaires'] : null,
        'actif'       => (int) $lab['actif'] === 1,
        'nb_analyses' => isset($lab['nb_analyses']) ? (int) $lab['nb_analyses'] : 0,
        'created_at'  => isset($lab['created_at']) ? (string) $lab['created_at'] : null,
    ];
}

function apiAnalyse(array $analyse): array
{
    return [
        'id'          => (int) $analyse['id'],
        'nom'         => (string) $analyse['nom'],
        'categorie'   => (string) $analyse['categorie'],
        'description' => $analyse['description'] !== null ? (string) $analyse['description'] : null,
        'prix'        => (float) $analyse['prix'],
    ];
}

// --- Lecture de l'entrée ---------------------------------------------
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$input  = $_POST;
$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$rawBody = file_get_contents('php://input');

if (is_string($rawBody) && trim($rawBody) !== '') {
    if (str_contains($contentType, 'application/json')) {
        $decoded = json_decode($rawBody, true);

        if (!is_array($decoded)) {
            apiFail(400, 'Corps JSON invalide.');
        }

        $input = array_merge($input, $decoded);
    } elseif ($method !== 'GET' && $input === []) {
        // PUT / DELETE en application/x-www-form-urlencoded : PHP ne
        // remplit pas $_POST pour ces méthodes, on analyse donc le corps.
        parse_str($rawBody, $parsed);

        if (is_array($parsed)) {
            $input = $parsed;
        }
    }
}

// --- Authentification -------------------------------------------------
$currentUser = apiRequireUser();

// Jeton CSRF pour un client qui doit ensuite envoyer une écriture.
if ($method === 'GET' && isset($_GET['csrf']) && $_GET['csrf'] === '1') {
    apiRespond(['csrf_token' => csrfToken()]);
}

$laboratoires = new Laboratoire($pdo);
$validator = new LaboratoireValidator($laboratoires);

// --- Routage ----------------------------------------------------------
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $idValue = $_GET['id'];

            if (!is_string($idValue) && !is_int($idValue)) {
                apiFail(400, 'Identifiant invalide.');
            }

            $id = filter_var((string) $idValue, FILTER_VALIDATE_INT);

            if ($id === false || $id < 1) {
                apiFail(400, 'Identifiant invalide.');
            }

            $lab = $laboratoires->find((int) $id);

            if ($lab === null) {
                apiFail(404, 'Laboratoire introuvable.');
            }

            $analyses = array_map('apiAnalyse', $laboratoires->analyses((int) $id));

            apiRespond([
                'data' => apiLaboratoire($lab),
                'analyses' => $analyses,
            ]);
        }

        $q = $_GET['q'] ?? '';
        $ville = $_GET['ville'] ?? '';

        if (!is_string($q) || !is_string($ville)) {
            apiFail(400, 'Les filtres q et ville doivent être des chaînes.');
        }

        $q = trim($q);
        $ville = trim($ville);
        $rows = $laboratoires->all($q, $ville);

        apiRespond([
            'data'    => array_map('apiLaboratoire', $rows),
            'count'   => count($rows),
            'filters' => ['q' => $q, 'ville' => $ville],
        ]);

    case 'POST':
        apiRequireAdmin($currentUser);
        apiRequireCsrf($input);

        $data = [
            'nom'       => $input['nom'] ?? '',
            'ville'     => $input['ville'] ?? '',
            'adresse'   => $input['adresse'] ?? '',
            'telephone' => $input['telephone'] ?? '',
            'email'     => $input['email'] ?? '',
            'horaires'  => $input['horaires'] ?? '',
            'actif'     => array_key_exists('actif', $input)
                ? ((int) $input['actif'] === 1 ? 1 : 0)
                : 1,
        ];

        $data = LaboratoireValidator::normalize($data);
        $errors = $validator->validate($data);

        if ($errors !== []) {
            apiFail(422, 'Données invalides.', ['errors' => $errors]);
        }

        try {
            $newId = $laboratoires->create($data);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000' || (int) ($e->errorInfo[1] ?? 0) === 1062) {
                apiFail(409, 'Cette adresse e-mail est déjà utilisée.', [
                    'errors' => ['email' => 'Cette adresse e-mail est déjà utilisée.'],
                ]);
            }
            throw $e;
        }

        $created = $laboratoires->find($newId);

        header('Location: ' . API_URL . 'laboratoires.php?id=' . $newId);
        apiRespond([
            'message' => 'Laboratoire créé.',
            'data'    => apiLaboratoire($created ?? $data + ['id' => $newId, 'nb_analyses' => 0, 'created_at' => null]),
        ], 201);

    case 'PUT':
        apiRequireAdmin($currentUser);
        apiRequireCsrf($input);

        $idValue = $_GET['id'] ?? ($input['id'] ?? null);

        if (!is_string($idValue) && !is_int($idValue)) {
            apiFail(400, 'Identifiant invalide.');
        }

        $id = filter_var((string) $idValue, FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            apiFail(400, 'Identifiant invalide.');
        }

        $existing = $laboratoires->find((int) $id);

        if ($existing === null) {
            apiFail(404, 'Laboratoire introuvable.');
        }

        // Mise à jour partielle : les champs absents conservent leur valeur.
        $data = [
            'nom'       => $input['nom'] ?? $existing['nom'],
            'ville'     => $input['ville'] ?? $existing['ville'],
            'adresse'   => $input['adresse'] ?? $existing['adresse'],
            'telephone' => $input['telephone'] ?? $existing['telephone'],
            'email'     => $input['email'] ?? $existing['email'],
            'horaires'  => $input['horaires'] ?? $existing['horaires'],
            'actif'     => array_key_exists('actif', $input)
                ? ((int) $input['actif'] === 1 ? 1 : 0)
                : (int) $existing['actif'],
        ];

        $data = LaboratoireValidator::normalize($data);
        $errors = $validator->validate($data, (int) $id);

        if ($errors !== []) {
            apiFail(422, 'Données invalides.', ['errors' => $errors]);
        }

        try {
            $laboratoires->update((int) $id, $data);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000' || (int) ($e->errorInfo[1] ?? 0) === 1062) {
                apiFail(409, 'Cette adresse e-mail est déjà utilisée.', [
                    'errors' => ['email' => 'Cette adresse e-mail est déjà utilisée.'],
                ]);
            }
            throw $e;
        }

        $updated = $laboratoires->find((int) $id);

        apiRespond([
            'message' => 'Laboratoire modifié.',
            'data'    => apiLaboratoire($updated),
        ]);

    case 'DELETE':
        apiRequireAdmin($currentUser);
        apiRequireCsrf($input);

        $idValue = $_GET['id'] ?? ($input['id'] ?? null);

        if (!is_string($idValue) && !is_int($idValue)) {
            apiFail(400, 'Identifiant invalide.');
        }

        $id = filter_var((string) $idValue, FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            apiFail(400, 'Identifiant invalide.');
        }

        if ($laboratoires->find((int) $id) === null) {
            apiFail(404, 'Laboratoire introuvable.');
        }

        $laboratoires->delete((int) $id);

        apiRespond(['message' => 'Laboratoire supprimé.']);

    default:
        header('Allow: GET, POST, PUT, DELETE');
        apiFail(405, 'Méthode HTTP non autorisée.');
}
