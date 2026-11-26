<?php
/**
 * Contrôleur du module Laboratoires.
 *
 * Lecture : Admin et Patient.
 * Création, modification, suppression : Admin uniquement.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Laboratoire.php';
require_once __DIR__ . '/../includes/LaboratoireValidator.php';

class LaboratoireController
{
    private Laboratoire $laboratoires;
    private LaboratoireValidator $validator;

    public function __construct(PDO $pdo)
    {
        $this->laboratoires = new Laboratoire($pdo);
        $this->validator = new LaboratoireValidator($this->laboratoires);
    }

    /**
     * Données de laboratoires.php : liste filtrée, villes et statistiques.
     * Accessible aux deux rôles.
     */
    public function index(): array
    {
        requireLogin();

        $search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
        $city   = isset($_GET['ville']) && is_string($_GET['ville']) ? trim($_GET['ville']) : '';

        return [
            'laboratoires' => $this->laboratoires->all($search, $city),
            'cities'       => $this->laboratoires->cities(),
            'stats'        => $this->laboratoires->stats(),
            'search'       => $search,
            'city'         => $city,
        ];
    }

    /**
     * Fiche détaillée : laboratoire + analyses proposées.
     */
    public function show(int $id): array
    {
        requireLogin();

        $laboratoire = $this->laboratoires->find($id);

        if ($laboratoire === null) {
            setFlash('danger', 'Laboratoire introuvable.');
            $this->redirect(BASE_URL . 'laboratoires.php');
        }

        return [
            'laboratoire' => $laboratoire,
            'analyses'    => $this->laboratoires->analyses($id),
        ];
    }

    /**
     * Création depuis la modale de laboratoires.php.
     * Retourne les erreurs pour les réafficher dans la modale.
     */
    public function create(): array
    {
        requireRole('Admin');

        if (!$this->isPost()) {
            return ['errors' => [], 'old' => []];
        }

        verifyCsrf();

        $data = $this->collectInput();
        $errors = $this->validator->validate($data);

        if ($errors !== []) {
            return ['errors' => $errors, 'old' => $data];
        }

        try {
            $id = $this->laboratoires->create($data);
        } catch (PDOException $e) {
            if ($this->isDuplicateKey($e)) {
                return [
                    'errors' => ['email' => 'Cette adresse e-mail est déjà utilisée par un laboratoire.'],
                    'old' => $data,
                ];
            }
            throw $e;
        }

        // Un laboratoire sans prix est inutilisable : on envoie directement
        // l'Admin vers la page de validation pour qu'il définisse ses tarifs.
        setFlash('success', 'Laboratoire ajouté. Définissez maintenant ses prix.');
        $this->redirect(
            BASE_URL . 'validation.php?laboratoire_id=' . $id . '&nouveau=1'
        );
    }

    /**
     * Modification — modifier-laboratoire.php.
     */
    public function edit(int $id): array
    {
        requireRole('Admin');

        $laboratoire = $this->laboratoires->find($id);

        if ($laboratoire === null) {
            setFlash('danger', 'Laboratoire introuvable.');
            $this->redirect(BASE_URL . 'laboratoires.php');
        }

        if (!$this->isPost()) {
            return [
                'laboratoire' => $laboratoire,
                'errors'      => [],
                'old'         => $laboratoire,
            ];
        }

        verifyCsrf();

        $data = $this->collectInput();
        $errors = $this->validator->validate($data, $id);

        if ($errors !== []) {
            return [
                'laboratoire' => $laboratoire,
                'errors'      => $errors,
                'old'         => $data,
            ];
        }

        try {
            $this->laboratoires->update($id, $data);
        } catch (PDOException $e) {
            if ($this->isDuplicateKey($e)) {
                return [
                    'laboratoire' => $laboratoire,
                    'errors' => ['email' => 'Cette adresse e-mail est déjà utilisée par un laboratoire.'],
                    'old' => $data,
                ];
            }
            throw $e;
        }

        setFlash('success', 'Le laboratoire a été modifié.');
        $this->redirect(BASE_URL . 'voir-laboratoire.php?id=' . $id);
    }

    /**
     * Suppression — supprimer-laboratoire.php (POST + CSRF uniquement).
     */
    public function destroy(int $id): void
    {
        requireRole('Admin');

        if (!$this->isPost()) {
            $this->redirectWithFlash(
                'warning',
                'Action invalide : utilisez le formulaire de confirmation.',
                BASE_URL . 'supprimer-laboratoire.php?id=' . $id
            );
        }

        verifyCsrf();

        $laboratoire = $this->laboratoires->find($id);

        if ($laboratoire === null) {
            $this->redirectWithFlash(
                'danger',
                'Laboratoire introuvable.',
                BASE_URL . 'laboratoires.php'
            );
        }

        $this->laboratoires->delete($id);

        $this->redirectWithFlash(
            'success',
            'Le laboratoire a été supprimé.',
            BASE_URL . 'laboratoires.php'
        );
    }

    // --- Aides internes -------------------------------------------------

    private function collectInput(): array
    {
        // La case "actif" non cochée n'est pas envoyée par le navigateur :
        // son absence signifie donc 0.
        return LaboratoireValidator::normalize([
            'nom'       => $this->postString('nom'),
            'ville'     => $this->postString('ville'),
            'adresse'   => $this->postString('adresse'),
            'telephone' => $this->postString('telephone'),
            'email'     => $this->postString('email'),
            'horaires'  => $this->postString('horaires'),
            'actif'     => isset($_POST['actif']) ? 1 : 0,
        ]);
    }

    private function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? trim($value) : '';
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
     * Détecte une violation de la contrainte UNIQUE sur l'e-mail.
     */
    private function isDuplicateKey(PDOException $e): bool
    {
        return (string) $e->getCode() === '23000'
            || (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
