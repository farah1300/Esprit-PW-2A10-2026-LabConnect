<?php
require_once __DIR__ . "/../config/database.php";

class Prelevement {
    private $db;

    public function __construct() {
        $this->db = Database::getConnexion();
    }

    public function getAll($idUtilisateur) {
        $q = $this->db->prepare(
            "SELECT p.*, a.rue, a.ville
             FROM prelevement p
             JOIN adresse a ON a.id = p.id_adresse
             WHERE p.id_utilisateur = ?
             ORDER BY p.id DESC"
        );
        $q->execute([$idUtilisateur]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $idUtilisateur) {
        $q = $this->db->prepare(
            "SELECT p.*, a.rue, a.ville, a.code_postal, a.gouvernorat, a.complement
             FROM prelevement p
             JOIN adresse a ON a.id = p.id_adresse
             WHERE p.id = ? AND p.id_utilisateur = ?"
        );
        $q->execute([$id, $idUtilisateur]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    public function ajouter($d, $idUtilisateur) {
        $q = $this->db->prepare(
            "INSERT INTO prelevement (id_adresse, date_prelevement, heure_prelevement, motif, id_utilisateur)
             VALUES (?, ?, ?, ?, ?)"
        );
        return $q->execute([$d['id_adresse'], $d['date_prelevement'], $d['heure_prelevement'],
                            $d['motif'], $idUtilisateur]);
    }

    // Modifiable seulement si la demande est encore en attente
    public function modifier($id, $d, $idUtilisateur) {
        $q = $this->db->prepare(
            "UPDATE prelevement
             SET id_adresse = ?, date_prelevement = ?, heure_prelevement = ?, motif = ?
             WHERE id = ? AND id_utilisateur = ? AND statut = 'en_attente'"
        );
        return $q->execute([$d['id_adresse'], $d['date_prelevement'], $d['heure_prelevement'],
                            $d['motif'], $id, $idUtilisateur]);
    }

    // Annulable seulement si la demande est encore en attente
    public function annuler($id, $idUtilisateur) {
        $q = $this->db->prepare(
            "UPDATE prelevement SET statut = 'annule'
             WHERE id = ? AND id_utilisateur = ? AND statut = 'en_attente'"
        );
        return $q->execute([$id, $idUtilisateur]);
    }
}