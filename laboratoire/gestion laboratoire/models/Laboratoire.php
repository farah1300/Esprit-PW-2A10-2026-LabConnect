<?php
/**
 * Modèle des laboratoires (tables laboratoires et laboratoire_analyse).
 *
 * Toutes les valeurs venant d'un formulaire ou de l'API sont liées par
 * des requêtes préparées. Les fragments SQL ajoutés dynamiquement dans
 * all() sont des constantes du code, jamais des valeurs utilisateur.
 */

class Laboratoire
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Liste filtrable utilisée par laboratoires.php.
     *
     * $search : texte cherché dans le nom, la ville, l'adresse ou l'e-mail.
     * $city   : filtre exact sur la ville (chaîne vide = toutes).
     *
     * Les paramètres sont distincts (:q_nom, :q_ville...) car MySQL, avec
     * EMULATE_PREPARES = false, refuse de réutiliser un paramètre nommé.
     */
    public function all(string $search = '', string $city = ''): array
    {
        $search    = trim($search);
        $city      = trim($city);
        $searchLike = '%' . $search . '%';

        $stmt = $this->pdo->prepare(
            'SELECT l.id, l.nom, l.adresse, l.ville, l.telephone, l.email,
                    l.horaires, l.actif, l.created_at,
                    COUNT(la.analyse_id) AS nb_analyses
             FROM laboratoires AS l
             LEFT JOIN laboratoire_analyse AS la ON la.laboratoire_id = l.id
             WHERE (:search_empty = 1
                    OR l.nom LIKE :q_nom
                    OR l.ville LIKE :q_ville
                    OR l.adresse LIKE :q_adresse
                    OR l.email LIKE :q_email)
               AND (:city_all = 1 OR l.ville = :city)
             GROUP BY l.id, l.nom, l.adresse, l.ville, l.telephone,
                      l.email, l.horaires, l.actif, l.created_at
             ORDER BY l.nom ASC'
        );

        $stmt->execute([
            ':search_empty' => $search === '' ? 1 : 0,
            ':q_nom'        => $searchLike,
            ':q_ville'      => $searchLike,
            ':q_adresse'    => $searchLike,
            ':q_email'      => $searchLike,
            ':city_all'     => $city === '' ? 1 : 0,
            ':city'         => $city,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Villes distinctes pour le filtre de laboratoires.php.
     */
    public function cities(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT ville FROM laboratoires ORDER BY ville ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Compteurs réels des cartes de statistiques.
     * Une seule requête évite quatre allers-retours vers MySQL.
     */
    public function stats(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM laboratoires) AS labs,
                (SELECT COUNT(DISTINCT ville) FROM laboratoires) AS cities,
                (SELECT COUNT(*) FROM analyses) AS analyses,
                (SELECT COUNT(*) FROM laboratoires WHERE actif = 1) AS active_labs'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return [
            'labs'        => (int) ($row['labs'] ?? 0),
            'cities'      => (int) ($row['cities'] ?? 0),
            'analyses'    => (int) ($row['analyses'] ?? 0),
            'active_labs' => (int) ($row['active_labs'] ?? 0),
        ];
    }

    /**
     * Un laboratoire par identifiant, avec le nombre d'analyses proposées.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.id, l.nom, l.adresse, l.ville, l.telephone, l.email,
                    l.horaires, l.actif, l.created_at,
                    (SELECT COUNT(*)
                     FROM laboratoire_analyse AS la
                     WHERE la.laboratoire_id = l.id) AS nb_analyses
             FROM laboratoires AS l
             WHERE l.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $lab = $stmt->fetch();

        return $lab === false ? null : $lab;
    }

    /**
     * Analyses (et prix) proposées par un laboratoire — voir-laboratoire.php.
     */
    public function analyses(int $id): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.nom, a.categorie, a.description, la.prix
             FROM laboratoire_analyse AS la
             INNER JOIN analyses AS a ON a.id = la.analyse_id
             WHERE la.laboratoire_id = :id
             ORDER BY a.nom ASC'
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetchAll();
    }

    /**
     * Liste des laboratoires avec leur nombre d'offres — validation.php.
     *
     * Le LEFT JOIN garantit qu'un laboratoire sans prix (aucune offre)
     * apparaît tout de même dans la liste, avec un compteur à 0.
     */
    public function allForSelect(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.id, l.nom, l.ville, COUNT(la.analyse_id) AS nb_offres
             FROM laboratoires AS l
             LEFT JOIN laboratoire_analyse AS la ON la.laboratoire_id = l.id
             GROUP BY l.id, l.nom, l.ville
             ORDER BY l.nom ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Crée un laboratoire et retourne son identifiant.
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO laboratoires
                (nom, adresse, ville, telephone, email, horaires, actif)
             VALUES
                (:nom, :adresse, :ville, :telephone, :email, :horaires, :actif)'
        );

        $stmt->execute($this->params($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour un laboratoire.
     */
    public function update(int $id, array $data): bool
    {
        $params = $this->params($data);
        $params[':id'] = $id;

        $stmt = $this->pdo->prepare(
            'UPDATE laboratoires
             SET nom = :nom,
                 adresse = :adresse,
                 ville = :ville,
                 telephone = :telephone,
                 email = :email,
                 horaires = :horaires,
                 actif = :actif
             WHERE id = :id'
        );

        return $stmt->execute($params);
    }

    /**
     * Supprime un laboratoire. Les offres liées partent en cascade
     * (contrainte fk_la_laboratoire ON DELETE CASCADE du schéma).
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM laboratoires WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Vérifie l'unicité de l'e-mail, en ignorant le laboratoire en cours
     * de modification. Deux paramètres distincts pour MySQL natif.
     */
    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM laboratoires
             WHERE email = :email
               AND (:ignore_all = 1 OR id <> :ignore_id)
             LIMIT 1'
        );

        $stmt->execute([
            ':email'      => $email,
            ':ignore_all' => $ignoreId === null ? 1 : 0,
            ':ignore_id'  => $ignoreId ?? 0,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Paramètres communs à create() et update().
     * Les horaires vides sont stockés NULL, comme prévu par le schéma.
     */
    private function params(array $data): array
    {
        $horaires = trim((string) ($data['horaires'] ?? ''));

        return [
            ':nom'       => (string) ($data['nom'] ?? ''),
            ':adresse'   => (string) ($data['adresse'] ?? ''),
            ':ville'     => (string) ($data['ville'] ?? ''),
            ':telephone' => (string) ($data['telephone'] ?? ''),
            ':email'     => (string) ($data['email'] ?? ''),
            ':horaires'  => $horaires === '' ? null : $horaires,
            ':actif'     => (int) ($data['actif'] ?? 1) === 1 ? 1 : 0,
        ];
    }
}
