<?php
require_once __DIR__ . "/../models/Prelevement.php";

class BackPrelevementController {
    private $model;

    public function __construct() {
        $this->model = new Prelevement();
    }

    public function liste() {
        $prelevements = $this->model->getAllAdmin();
        $message = $_GET['msg'] ?? '';
        require __DIR__ . "/../views/back/prelevement_liste.php";
    }

    public function detail($id) {
        $p = $this->model->getByIdAdmin($id);
        if (!$p) {
            die("Demande introuvable.");
        }
        $suivant = $this->model->getStatutSuivant($p['statut']);
        $message = $_GET['msg'] ?? '';
        require __DIR__ . "/../views/back/prelevement_detail.php";
    }

    public function confirmer($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=back_liste");
            exit;
        }
        $ok = $this->model->confirmer($id);
        header("Location: index.php?action=back_detail&id=" . $id . "&msg=" . ($ok ? "confirme" : "impossible"));
        exit;
    }

    public function refuser($id) {
        $p = $this->model->getByIdAdmin($id);
        if (!$p) {
            die("Demande introuvable.");
        }
        if ($p['statut'] !== 'en_attente') {
            header("Location: index.php?action=back_detail&id=" . $id . "&msg=impossible");
            exit;
        }

        $erreurs = [];
        $motifRefus = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $motifRefus = trim($_POST['motif_refus'] ?? '');
            if (strlen($motifRefus) < 3 || strlen($motifRefus) > 255) {
                $erreurs['motif_refus'] = "Le motif du refus doit contenir entre 3 et 255 caractères.";
            }
            if (empty($erreurs)) {
                $ok = $this->model->refuser($id, $motifRefus);
                header("Location: index.php?action=back_liste&msg=" . ($ok ? "refuse" : "impossible"));
                exit;
            }
        }
        require __DIR__ . "/../views/back/prelevement_refus.php";
    }

    public function avancer($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=back_liste");
            exit;
        }
        $ok = $this->model->avancerStatut($id);
        header("Location: index.php?action=back_detail&id=" . $id . "&msg=" . ($ok ? "avance" : "impossible"));
        exit;
    }
}