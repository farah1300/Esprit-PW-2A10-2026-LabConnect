<?php
/**
 * Modèle des analyses et des offres (tables analyses et
 * laboratoire_analyse).
 *
 * Le prix appartient au couple (laboratoire, analyse), pas à l'analyse :
 * c'est pourquoi les lectures du catalogue passent par la table de liaison.
 */

class Analyse
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Toutes les analyses du catalogue — validation.php.
     */
    public function all(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nom, categorie, description
             FROM analyses
             ORDER BY nom ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Une analyse par identifiant — validation.php.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nom, categorie, description
             FROM analyses
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $analyse = $stmt->fetch();

        return $analyse === false ? null : $analyse;
    }

    /**
     * Catalogue complet ou filtré — catalogue.php.
     *
     * $search : nom d'analyse (recherche partielle).
     * $min    : prix minimum inclus (null = pas de minimum).
     * $max    : prix maximum inclus (null = pas de maximum).
     *
     * Les conditions sont ajoutées uniquement sous forme de fragments fixes.
     * Aucune valeur utilisateur n'est concaténée au SQL.
     */
    public function offers(string $search = '', ?float $min = null, ?float $max = null): array
    {
        $sql = 'SELECT la.laboratoire_id,
                       la.analyse_id,
                       la.prix,
                       a.nom AS analyse_nom,
                       a.categorie,
                       l.nom AS laboratoire_nom,
                       l.ville,
                       l.actif AS laboratoire_actif
                FROM laboratoire_analyse AS la
                INNER JOIN analyses AS a ON a.id = la.analyse_id
                INNER JOIN laboratoires AS l ON l.id = la.laboratoire_id
                WHERE 1 = 1';

        $params = [];

        $search = trim($search);

        if ($search !== '') {
            $sql .= ' AND a.nom LIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        if ($min !== null) {
            $sql .= ' AND la.prix >= :prix_min';
            $params[':prix_min'] = $min;
        }

        if ($max !== null) {
            $sql .= ' AND la.prix <= :prix_max';
            $params[':prix_max'] = $max;
        }

        // Tri demandé par le filtre patient : prix croissant.
        $sql .= ' ORDER BY la.prix ASC, a.nom ASC, l.nom ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Crée ou met à jour le prix d'une analyse pour un laboratoire.
     * ON DUPLICATE KEY UPDATE s'appuie sur la clé primaire composite
     * (laboratoire_id, analyse_id) du schéma.
     */
    public function saveOffer(int $laboratoireId, int $analyseId, float $prix): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO laboratoire_analyse (laboratoire_id, analyse_id, prix)
             VALUES (:laboratoire_id, :analyse_id, :prix)
             ON DUPLICATE KEY UPDATE prix = VALUES(prix)'
        );

        return $stmt->execute([
            ':laboratoire_id' => $laboratoireId,
            ':analyse_id'     => $analyseId,
            ':prix'           => $prix,
        ]);
    }

    /**
     * Détache une analyse d'un laboratoire — catalogue.php et validation.php.
     */
    public function deleteOffer(int $laboratoireId, int $analyseId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM laboratoire_analyse
             WHERE laboratoire_id = :laboratoire_id
               AND analyse_id = :analyse_id'
        );

        return $stmt->execute([
            ':laboratoire_id' => $laboratoireId,
            ':analyse_id'     => $analyseId,
        ]);
    }

    /**
     * Vérifie qu'une offre existe avant une suppression (idempotence).
     */
    public function offerExists(int $laboratoireId, int $analyseId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM laboratoire_analyse
             WHERE laboratoire_id = :laboratoire_id
               AND analyse_id = :analyse_id
             LIMIT 1'
        );
        $stmt->execute([
            ':laboratoire_id' => $laboratoireId,
            ':analyse_id'     => $analyseId,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
