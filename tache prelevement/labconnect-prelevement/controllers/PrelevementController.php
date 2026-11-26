<?php
require_once __DIR__ . "/../models/Prelevement.php";
require_once __DIR__ . "/../models/Adresse.php";

class PrelevementController {
    private $model;
    private $adresseModel;
    private $idUtilisateur = 1; // patient unique en attendant le module utilisateurs

    public function __construct() {
        $this->model = new Prelevement();
        $this->adresseModel = new Adresse();
    }

    public function liste() {
        $prelevements = $this->model->getAll($this->idUtilisateur);
        $message = $_GET['msg'] ?? '';
        require __DIR__ . "/../views/front/prelevement_liste.php";
    }

    public function detail($id) {
        $p = $this->model->getById($id, $this->idUtilisateur);
        if (!$p) {
            die("Demande introuvable.");
        }
        require __DIR__ . "/../views/front/prelevement_detail.php";
    }

    public function ajouter() {
        $adresses = $this->adresseModel->getAll($this->idUtilisateur);
        $erreurs = [];
        $data = ['id_adresse' => '', 'date_prelevement' => '', 'heure_prelevement' => '', 'motif' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->lireDonnees();
            $erreurs = $this->valider($data, $adresses);
            if (empty($erreurs)) {
                $this->model->ajouter($data, $this->idUtilisateur);
                header("Location: index.php?action=prelevement_liste&msg=ajoute");
                exit;
            }
        }
        $titre = "Demander un prélèvement à domicile";
        $formAction = "index.php?action=prelevement_ajouter";
        require __DIR__ . "/../views/front/prelevement_form.php";
    }

    public function modifier($id) {
        $p = $this->model->getById($id, $this->idUtilisateur);
        if (!$p) {
            die("Demande introuvable.");
        }
        if ($p['statut'] !== 'en_attente') {
            header("Location: index.php?action=prelevement_liste&msg=non_modifiable");
            exit;
        }

        $adresses = $this->adresseModel->getAll($this->idUtilisateur);
        $erreurs = [];
        $data = [
            'id_adresse'        => $p['id_adresse'],
            'date_prelevement'  => $p['date_prelevement'],
            'heure_prelevement' => substr($p['heure_prelevement'], 0, 5),
            'motif'             => $p['motif'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->lireDonnees();
            $erreurs = $this->valider($data, $adresses);
            if (empty($erreurs)) {
                $this->model->modifier($id, $data, $this->idUtilisateur);
                header("Location: index.php?action=prelevement_liste&msg=modifie");
                exit;
            }
        }
        $titre = "Modifier la demande";
        $formAction = "index.php?action=prelevement_modifier&id=" . $id;
        require __DIR__ . "/../views/front/prelevement_form.php";
    }

    public function annuler($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=prelevement_liste");
            exit;
        }
        $p = $this->model->getById($id, $this->idUtilisateur);
        if (!$p || $p['statut'] !== 'en_attente') {
            header("Location: index.php?action=prelevement_liste&msg=non_annulable");
            exit;
        }
        $this->model->annuler($id, $this->idUtilisateur);
        header("Location: index.php?action=prelevement_liste&msg=annule");
        exit;
    }

    private function lireDonnees() {
        return [
            'id_adresse'        => (int)($_POST['id_adresse'] ?? 0),
            'date_prelevement'  => trim($_POST['date_prelevement'] ?? ''),
            'heure_prelevement' => trim($_POST['heure_prelevement'] ?? ''),
            'motif'             => trim($_POST['motif'] ?? ''),
        ];
    }

    private function valider($d, $adresses) {
        $e = [];

        // L'adresse doit exister et appartenir au patient
        $ids = array_column($adresses, 'id');
        if (!in_array($d['id_adresse'], $ids)) {
            $e['id_adresse'] = "Veuillez choisir une adresse.";
        }

        // Date au format AAAA-MM-JJ et strictement dans le futur
        $date = DateTime::createFromFormat('Y-m-d', $d['date_prelevement']);
        if (!$date || $date->format('Y-m-d') !== $d['date_prelevement']) {
            $e['date_prelevement'] = "La date doit être au format AAAA-MM-JJ.";
        } elseif ($date <= new DateTime('today')) {
            $e['date_prelevement'] = "La date doit être dans le futur.";
        }

        // Heure au format HH:MM
        if (!preg_match("/^([01][0-9]|2[0-3]):[0-5][0-9]$/", $d['heure_prelevement'])) {
            $e['heure_prelevement'] = "L'heure doit être au format HH:MM (ex: 08:30).";
        }

        if (strlen($d['motif']) < 3 || strlen($d['motif']) > 150) {
            $e['motif'] = "Le motif doit contenir entre 3 et 150 caractères.";
        }
        return $e;
    }
}