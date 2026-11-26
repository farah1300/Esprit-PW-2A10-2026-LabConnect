<?php
require_once __DIR__ . "/../models/Prelevement.php";

class BackPrelevementController {
    private $model;

    public function __construct() {
        $this->model = new Prelevement();
    }

    // Liste de toutes les demandes + recherche (statut, date, motif, patient)
    public function liste() {
        $filtres = [
            'statut'  => trim($_GET['statut'] ?? ''),
            'date'    => trim($_GET['date'] ?? ''),
            'motif'   => trim($_GET['motif'] ?? ''),
            'patient' => trim($_GET['patient'] ?? ''),
        ];
        $statuts = $this->model->getStatuts();
        $erreursFiltre = $this->validerFiltres($filtres, $statuts);

        // Un filtre invalide est ignore dans la requete
        $filtresValides = $filtres;
        foreach ($erreursFiltre as $champ => $msg) {
            $filtresValides[$champ] = '';
        }

        $prelevements = $this->model->rechercherAdmin($filtresValides);
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

    // Validation des filtres de recherche (tous facultatifs)
    private function validerFiltres($f, $statuts) {
        $e = [];
        if ($f['statut'] !== '' && !in_array($f['statut'], $statuts)) {
            $e['statut'] = "Statut invalide.";
        }
        if ($f['date'] !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $f['date']);
            if (!$d || $d->format('Y-m-d') !== $f['date']) {
                $e['date'] = "La date doit être valide, au format AAAA-MM-JJ.";
            }
        }
        if (strlen($f['motif']) > 100) {
            $e['motif'] = "Le mot-clé ne doit pas dépasser 100 caractères.";
        }
        if ($f['patient'] !== '' && !preg_match("/^[0-9]{1,6}$/", $f['patient'])) {
            $e['patient'] = "Le numéro de patient doit contenir uniquement des chiffres.";
        }
        return $e;
    }
}