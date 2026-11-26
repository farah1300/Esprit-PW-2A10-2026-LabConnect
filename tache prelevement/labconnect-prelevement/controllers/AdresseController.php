<?php
require_once __DIR__ . "/../models/Adresse.php";

class AdresseController {
    private $model;
    private $idUtilisateur = 1; // patient unique en attendant le module utilisateurs

    private $gouvernorats = [
        "Ariana", "Béja", "Ben Arous", "Bizerte", "Gabès", "Gafsa", "Jendouba", "Kairouan",
        "Kasserine", "Kébili", "Le Kef", "Mahdia", "La Manouba", "Médenine", "Monastir",
        "Nabeul", "Sfax", "Sidi Bouzid", "Siliana", "Sousse", "Tataouine", "Tozeur",
        "Tunis", "Zaghouan"
    ];

    public function __construct() {
        $this->model = new Adresse();
    }

    public function liste() {
        $adresses = $this->model->getAll($this->idUtilisateur);
        $message = $_GET['msg'] ?? '';
        require __DIR__ . "/../views/front/adresse_liste.php";
    }

    public function ajouter() {
        $erreurs = [];
        $data = ['rue' => '', 'ville' => '', 'code_postal' => '', 'gouvernorat' => '', 'complement' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->lireDonnees();
            $erreurs = $this->valider($data);
            if (empty($erreurs)) {
                $this->model->ajouter($data, $this->idUtilisateur);
                header("Location: index.php?action=adresse_liste&msg=ajoute");
                exit;
            }
        }
        $titre = "Ajouter une adresse";
        $formAction = "index.php?action=adresse_ajouter";
        $gouvernorats = $this->gouvernorats;
        require __DIR__ . "/../views/front/adresse_form.php";
    }

    public function modifier($id) {
        $adresse = $this->model->getById($id, $this->idUtilisateur);
        if (!$adresse) {
            die("Adresse introuvable.");
        }
        $erreurs = [];
        $data = $adresse;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->lireDonnees();
            $erreurs = $this->valider($data);
            if (empty($erreurs)) {
                $this->model->modifier($id, $data, $this->idUtilisateur);
                header("Location: index.php?action=adresse_liste&msg=modifie");
                exit;
            }
        }
        $titre = "Modifier l'adresse";
        $formAction = "index.php?action=adresse_modifier&id=" . $id;
        $gouvernorats = $this->gouvernorats;
        require __DIR__ . "/../views/front/adresse_form.php";
    }

    public function supprimer($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=adresse_liste");
            exit;
        }
        if ($this->model->estUtilisee($id)) {
            header("Location: index.php?action=adresse_liste&msg=utilisee");
            exit;
        }
        $this->model->supprimer($id, $this->idUtilisateur);
        header("Location: index.php?action=adresse_liste&msg=supprime");
        exit;
    }

    private function lireDonnees() {
        return [
            'rue'         => trim($_POST['rue'] ?? ''),
            'ville'       => trim($_POST['ville'] ?? ''),
            'code_postal' => trim($_POST['code_postal'] ?? ''),
            'gouvernorat' => trim($_POST['gouvernorat'] ?? ''),
            'complement'  => trim($_POST['complement'] ?? ''),
        ];
    }

    private function valider($d) {
        $e = [];
        if (strlen($d['rue']) < 5) {
            $e['rue'] = "La rue doit contenir au moins 5 caractères.";
        }
        if (!preg_match("/^[\p{L}\s'\-]{2,80}$/u", $d['ville'])) {
            $e['ville'] = "La ville doit contenir seulement des lettres (2 à 80).";
        }
        if (!preg_match("/^[0-9]{4}$/", $d['code_postal'])) {
            $e['code_postal'] = "Le code postal doit contenir exactement 4 chiffres.";
        }
        if (!in_array($d['gouvernorat'], $this->gouvernorats)) {
            $e['gouvernorat'] = "Veuillez choisir un gouvernorat.";
        }
        if (strlen($d['complement']) > 150) {
            $e['complement'] = "Le complément ne doit pas dépasser 150 caractères.";
        }
        return $e;
    }
}