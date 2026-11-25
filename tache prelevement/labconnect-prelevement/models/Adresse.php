<?php
require_once __DIR__ . "/../config/database.php";

class Adresse {
    private $db;

    public function __construct() {
        $this->db = Database::getConnexion();
    }

    public function getAll($idUtilisateur) {
        $q = $this->db->prepare("SELECT * FROM adresse WHERE id_utilisateur = ? ORDER BY id DESC");
        $q->execute([$idUtilisateur]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $idUtilisateur) {
        $q = $this->db->prepare("SELECT * FROM adresse WHERE id = ? AND id_utilisateur = ?");
        $q->execute([$id, $idUtilisateur]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    public function ajouter($d, $idUtilisateur) {
        $q = $this->db->prepare(
            "INSERT INTO adresse (rue, ville, code_postal, gouvernorat, complement, id_utilisateur)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $q->execute([$d['rue'], $d['ville'], $d['code_postal'], $d['gouvernorat'],
                            $d['complement'] === '' ? null : $d['complement'], $idUtilisateur]);
    }

    public function modifier($id, $d, $idUtilisateur) {
        $q = $this->db->prepare(
            "UPDATE adresse SET rue = ?, ville = ?, code_postal = ?, gouvernorat = ?, complement = ?
             WHERE id = ? AND id_utilisateur = ?"
        );
        return $q->execute([$d['rue'], $d['ville'], $d['code_postal'], $d['gouvernorat'],
                            $d['complement'] === '' ? null : $d['complement'], $id, $idUtilisateur]);
    }

    public function supprimer($id, $idUtilisateur) {
        $q = $this->db->prepare("DELETE FROM adresse WHERE id = ? AND id_utilisateur = ?");
        return $q->execute([$id, $idUtilisateur]);
    }

    // Regle de gestion: adresse liee a un prelevement actif = suppression interdite
    public function estUtilisee($id) {
        $q = $this->db->prepare(
            "SELECT COUNT(*) FROM prelevement
             WHERE id_adresse = ? AND statut IN ('en_attente','confirme','en_cours')"
        );
        $q->execute([$id]);
        return $q->fetchColumn() > 0;
    }
}