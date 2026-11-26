<?php
/**
 * Contrôleur de la page Validation.
 *
 * Elle permet à l'Admin d'associer une analyse à un laboratoire avec un
 * prix, ou de mettre à jour ce prix. Le Patient n'y a jamais accès.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Laboratoire.php';
require_once __DIR__ . '/../models/Analyse.php';

class ValidationController
{
    private Laboratoire $laboratoires;
    private Analyse $analyses;

    public function __construct(PDO $pdo)
    {
        $this->laboratoires = new Laboratoire($pdo);
        $this->analyses = new Analyse($pdo);
    }

    /**
     * Données de la page : sélecteurs, laboratoires avec leur nombre
     * d'offres et offres existantes.
     */
    public function index(): array
    {
        requireRole('Admin');

        return [
            'laboratoires' => $this->laboratoires->allForSelect(),
            'analyses'     => $this->analyses->all(),
            'offres'       => $this->analyses->offers(),
        ];
    }

    /**
     * Crée ou met à jour une offre (laboratoire, analyse, prix).
     */
    public function save(): array
    {
        requireRole('Admin');

        if (!$this->isPost()) {
            return ['errors' => [], 'old' => []];
        }

        verifyCsrf();

        $old = [
            'laboratoire_id' => $this->postInt('laboratoire_id'),
            'analyse_id'     => $this->postInt('analyse_id'),
            'prix'           => $this->postString('prix'),
        ];

        $errors = [];

        // Les identifiants doivent être des entiers valides ET exister en base.
        $laboratoire = $this->laboratoires->find($old['laboratoire_id']);

        if ($laboratoire === null) {
            $errors['laboratoire_id'] = 'Sélectionnez un laboratoire valide.';
        }

        if ($this->analyses->find($old['analyse_id']) === null) {
            $errors['analyse_id'] = 'Sélectionnez une analyse valide.';
        }

        $prix = null;

        if ($old['prix'] === '') {
            $errors['prix'] = 'Le prix est obligatoire.';
        } else {
            // La virgule décimale française est acceptée puis normalisée.
            $prixSaisi = str_replace(',', '.', $old['prix']);

            // Format strict DECIMAL(10,3) : 7 chiffres maximum avant la
            // virgule et 3 décimales maximum. Quatre décimales sont donc
            // refusées plutôt qu'arrondies silencieusement.
            if (!preg_match('/^\d{1,7}(\.\d{1,3})?$/', $prixSaisi)) {
                $errors['prix'] = 'Le prix doit être un nombre positif, avec au maximum 7 chiffres avant la virgule et 3 décimales.';
            } else {
                $prix = (float) $prixSaisi;

                if ($prix <= 0) {
                    $errors['prix'] = 'Le prix doit être strictement positif.';
                }
            }
        }

        if ($errors !== []) {
            return ['errors' => $errors, 'old' => $old];
        }

        $this->analyses->saveOffer(
            $old['laboratoire_id'],
            $old['analyse_id'],
            (float) $prix
        );

        setFlash('success', "L'offre a été enregistrée ou mise à jour.");
        $this->redirect(BASE_URL . 'validation.php');
    }

    /**
     * Retire une offre — POST + CSRF.
     */
    public function destroy(): void
    {
        requireRole('Admin');

        if (!$this->isPost()) {
            $this->redirectWithFlash(
                'warning',
                'Action invalide : utilisez le bouton de suppression.',
                BASE_URL . 'validation.php'
            );
        }

        verifyCsrf();

        $laboratoireId = $this->postInt('laboratoire_id');
        $analyseId = $this->postInt('analyse_id');

        if (!$this->analyses->offerExists($laboratoireId, $analyseId)) {
            $this->redirectWithFlash(
                'warning',
                'Cette offre a déjà été retirée.',
                BASE_URL . 'validation.php'
            );
        }

        $this->analyses->deleteOffer($laboratoireId, $analyseId);

        $this->redirectWithFlash(
            'success',
            "L'offre a été retirée.",
            BASE_URL . 'validation.php'
        );
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

    private function postInt(string $key): int
    {
        $value = $_POST[$key] ?? null;

        if (!is_int($value) && !is_string($value)) {
            return 0;
        }

        // Un identifiant forgé ("1abc", "1.5", tableau...) est refusé :
        // il devient 0 et sera rejeté par la vérification d'existence.
        $int = filter_var(trim((string) $value), FILTER_VALIDATE_INT);

        return $int === false ? 0 : (int) $int;
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
}
