<?php
/**
 * Contrôleur du catalogue des analyses.
 *
 * Lecture : Admin et Patient.
 * Retrait d'une offre (analyse + laboratoire) : Admin uniquement.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Analyse.php';

class CatalogueController
{
    private Analyse $analyses;

    public function __construct(PDO $pdo)
    {
        $this->analyses = new Analyse($pdo);
    }

    /**
     * Liste filtrée par nom d'analyse et fourchette de prix.
     * Le tri prix croissant est fixé dans Analyse::offers().
     */
    public function index(): array
    {
        requireLogin();

        $search  = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
        $minRaw  = isset($_GET['min']) && is_string($_GET['min']) ? trim($_GET['min']) : '';
        $maxRaw  = isset($_GET['max']) && is_string($_GET['max']) ? trim($_GET['max']) : '';
        $errors  = [];
        $min     = null;
        $max     = null;

        if ($minRaw !== '') {
            if (!is_numeric($minRaw) || (float) $minRaw < 0) {
                $errors['min'] = 'Le prix minimum doit être un nombre positif.';
            } else {
                $min = round((float) $minRaw, 3);
            }
        }

        if ($maxRaw !== '') {
            if (!is_numeric($maxRaw) || (float) $maxRaw < 0) {
                $errors['max'] = 'Le prix maximum doit être un nombre positif.';
            } else {
                $max = round((float) $maxRaw, 3);
            }
        }

        if ($min !== null && $max !== null && $min > $max) {
            $errors['price'] = 'Le prix minimum ne peut pas dépasser le prix maximum.';
            // On ignore les deux bornes pour ne pas afficher un résultat
            // silencieusement vide après une saisie incohérente.
            $min = null;
            $max = null;
        }

        return [
            'offres'  => $this->analyses->offers($search, $min, $max),
            'filters' => ['q' => $search, 'min' => $minRaw, 'max' => $maxRaw],
            'errors'  => $errors,
        ];
    }

    /**
     * Retire une analyse du catalogue d'un laboratoire — Admin uniquement.
     * L'action est un POST protégé par CSRF, jamais un lien GET.
     */
    public function deleteOffer(int $laboratoireId, int $analyseId): void
    {
        requireRole('Admin');

        if (!$this->isPost()) {
            $this->redirectWithFlash(
                'warning',
                'Action invalide : utilisez le bouton de suppression.',
                BASE_URL . 'catalogue.php'
            );
        }

        verifyCsrf();

        if ($laboratoireId <= 0 || $analyseId <= 0) {
            $this->redirectWithFlash(
                'danger',
                'Offre introuvable.',
                BASE_URL . 'catalogue.php'
            );
        }

        if (!$this->analyses->offerExists($laboratoireId, $analyseId)) {
            $this->redirectWithFlash(
                'warning',
                'Cette offre a déjà été retirée.',
                BASE_URL . 'catalogue.php'
            );
        }

        $this->analyses->deleteOffer($laboratoireId, $analyseId);

        $this->redirectWithFlash(
            'success',
            "L'analyse a été retirée du catalogue du laboratoire.",
            BASE_URL . 'catalogue.php'
        );
    }

    private function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    private function redirectWithFlash(string $type, string $message, string $url): void
    {
        setFlash($type, $message);
        header('Location: ' . $url);
        exit;
    }
}
