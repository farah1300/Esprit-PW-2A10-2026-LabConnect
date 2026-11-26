<?php
require_once __DIR__ . "/../config/database.php";

class Prelevement {
    private $db;

    // Ordre des statuts que le responsable peut faire avancer
    private $statutSuivant = [
        'confirme' => 'en_cours',
        'en_cours' => 'effectue',
    ];

    public function __construct() {
        $this->db = Database::getConnexion();
    }

    /* ===================== FRONTOFFICE (patient) ===================== */

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

    /* ===================== BACKOFFICE (responsable) ===================== */

    // Toutes les demandes, de tous les patients
    public function getAllAdmin() {
        $q = $this->db->query(
            "SELECT p.*, a.rue, a.ville
             FROM prelevement p
             JOIN adresse a ON a.id = p.id_adresse
             ORDER BY p.id DESC"
        );
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdAdmin($id) {
        $q = $this->db->prepare(
            "SELECT p.*, a.rue, a.ville, a.code_postal, a.gouvernorat, a.complement
             FROM prelevement p
             JOIN adresse a ON a.id = p.id_adresse
             WHERE p.id = ?"
        );
        $q->execute([$id]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    // en_attente -> confirme
    public function confirmer($id) {
        $q = $this->db->prepare(
            "UPDATE prelevement SET statut = 'confirme', motif_refus = NULL
             WHERE id = ? AND statut = 'en_attente'"
        );
        $q->execute([$id]);
        return $q->rowCount() > 0;
    }

    // en_attente -> refuse (avec motif)
    public function refuser($id, $motifRefus) {
        $q = $this->db->prepare(
            "UPDATE prelevement SET statut = 'refuse', motif_refus = ?
             WHERE id = ? AND statut = 'en_attente'"
        );
        $q->execute([$motifRefus, $id]);
        return $q->rowCount() > 0;
    }

    // Statut suivant autorise pour un statut donne (ou null)
    public function getStatutSuivant($statut) {
        return $this->statutSuivant[$statut] ?? null;
    }

    // confirme -> en_cours -> effectue
    public function avancerStatut($id) {
        $p = $this->getByIdAdmin($id);
        if (!$p) {
            return false;
        }
        $suivant = $this->getStatutSuivant($p['statut']);
        if ($suivant === null) {
            return false;
        }
        $q = $this->db->prepare(
            "UPDATE prelevement SET statut = ? WHERE id = ? AND statut = ?"
        );
        $q->execute([$suivant, $id, $p['statut']]);
        return $q->rowCount() > 0;
    }
}