<?php
/**
 * Modèle des utilisateurs (table utilisateurs).
 *
 * La base utilise les noms de colonnes officiels du module :
 *   utilisateurs(id, nom, prenom, email, telephone, mot_de_passe,
 *                id_role, statut, date_creation, last_login)
 *
 * Le reste de l'application (contrôleur, vues) continue d'utiliser des noms
 * courts et explicites : password_hash, role_id, is_active, created_at.
 * La correspondance est faite ici, dans la couche modèle, avec des alias SQL.
 * Ainsi, une colonne de la base ne fuit pas dans le HTML et une future
 * renommage de colonne ne casse qu'un seul fichier.
 *
 * Ce modèle ne fait que des requêtes préparées : aucune valeur reçue du
 * formulaire n'est concaténée dans le SQL. Les règles métier (dernier Admin,
 * suppression de soi-même, rôle autorisé...) sont vérifiées par
 * UtilisateurController avant d'appeler create/update/delete.
 */

class Utilisateur
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Liste filtrable des utilisateurs — page utilisateurs.php.
     *
     * $search  : texte cherché dans nom, prénom ou e-mail (vide = tous).
     * $roleId  : identifiant du rôle (null = tous les rôles).
     *
     * Les paramètres sont distincts (:search_nom, :search_prenom...) car
     * avec EMULATE_PREPARES = false, MySQL refuse de réutiliser un même
     * paramètre nommé plusieurs fois dans la requête.
     */
    public function all(string $search = '', ?int $roleId = null): array
    {
        $search = trim($search);
        $searchLike = $this->likePattern($search);
        $roleFilter = ($roleId !== null && $roleId > 0) ? $roleId : null;

        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.nom, u.prenom, u.email, u.telephone,
                    u.id_role AS role_id, u.statut AS is_active,
                    u.last_login, u.date_creation AS created_at, r.nom AS role
             FROM utilisateurs AS u
             INNER JOIN roles AS r ON r.id = u.id_role
             WHERE (:search_empty = 1
                    OR u.nom LIKE :search_nom
                    OR u.prenom LIKE :search_prenom
                    OR u.email LIKE :search_email)
               AND (:role_all = 1 OR u.id_role = :role_id)
             ORDER BY u.nom ASC, u.prenom ASC'
        );

        $stmt->execute([
            ':search_empty'  => $search === '' ? 1 : 0,
            ':search_nom'    => $searchLike,
            ':search_prenom' => $searchLike,
            ':search_email'  => $searchLike,
            ':role_all'      => $roleFilter === null ? 1 : 0,
            ':role_id'       => $roleFilter ?? 0,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Un utilisateur par son identifiant — details-utilisateur.php,
     * modifier-utilisateur.php et les contrôles de UtilisateurController.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.nom, u.prenom, u.email, u.telephone,
                    u.id_role AS role_id, u.statut AS is_active,
                    u.last_login, u.date_creation AS created_at, r.nom AS role
             FROM utilisateurs AS u
             INNER JOIN roles AS r ON r.id = u.id_role
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    /**
     * Un utilisateur par son e-mail — login.php (password_verify) et
     * contrôles d'authentification.
     *
     * mot_de_passe est inclus uniquement ici : les autres méthodes ne
     * l'exposent pas aux vues.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.nom, u.prenom, u.email, u.telephone,
                    u.mot_de_passe AS password_hash, u.id_role AS role_id,
                    u.statut AS is_active, r.nom AS role
             FROM utilisateurs AS u
             INNER JOIN roles AS r ON r.id = u.id_role
             WHERE u.email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    /**
     * Crée un utilisateur et retourne son identifiant — inscription.php
     * et ajouter-utilisateur.php.
     *
     * $data attend : nom, prenom, email, telephone (optionnel),
     * password_hash (déjà calculé par le contrôleur), role_id,
     * is_active (optionnel, 1 par défaut).
     */
    public function create(array $data): int
    {
        $telephone = trim((string) ($data['telephone'] ?? ''));
        $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;

        $stmt = $this->pdo->prepare(
            'INSERT INTO utilisateurs
                (nom, prenom, email, telephone, mot_de_passe, id_role, statut)
             VALUES
                (:nom, :prenom, :email, :telephone, :password_hash, :role_id, :is_active)'
        );

        $stmt->execute([
            ':nom'           => $data['nom'],
            ':prenom'        => $data['prenom'],
            ':email'         => $data['email'],
            ':telephone'     => $telephone === '' ? null : $telephone,
            ':password_hash' => $data['password_hash'],
            ':role_id'       => (int) $data['role_id'],
            ':is_active'     => $isActive,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour un utilisateur — modifier-utilisateur.php.
     *
     * Le mot de passe est optionnel : si $data['password_hash'] est vide,
     * l'ancien hash est conservé. Le statut actif/inactif se change via
     * setActive(), pas ici, pour éviter une modification accidentelle.
     */
    public function update(int $id, array $data): bool
    {
        $telephone = trim((string) ($data['telephone'] ?? ''));

        $params = [
            ':id'        => $id,
            ':nom'       => $data['nom'],
            ':prenom'    => $data['prenom'],
            ':email'     => $data['email'],
            ':telephone' => $telephone === '' ? null : $telephone,
            ':role_id'   => (int) $data['role_id'],
        ];

        $newHash = $data['password_hash'] ?? '';

        if ($newHash !== '') {
            // Deux requêtes fixes plutôt qu'une concaténation conditionnelle :
            // la liste des colonnes reste explicite et sûre.
            $stmt = $this->pdo->prepare(
                'UPDATE utilisateurs
                 SET nom = :nom, prenom = :prenom, email = :email,
                     telephone = :telephone, id_role = :role_id,
                     mot_de_passe = :password_hash
                 WHERE id = :id'
            );
            $params[':password_hash'] = $newHash;
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE utilisateurs
                 SET nom = :nom, prenom = :prenom, email = :email,
                     telephone = :telephone, id_role = :role_id
                 WHERE id = :id'
            );
        }

        return $stmt->execute($params);
    }

    /**
     * Supprime un utilisateur — bouton Supprimer de utilisateurs.php
     * (les règles « soi-même » et « dernier Admin » sont contrôlées avant).
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM utilisateurs WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Active ou désactive un compte — bouton Activer/Désactiver de
     * utilisateurs.php (UtilisateurController::toggleActive).
     */
    public function setActive(int $id, bool $active): bool
    {
        $stmt = $this->pdo->prepare('UPDATE utilisateurs SET statut = :is_active WHERE id = :id');

        return $stmt->execute([
            ':is_active' => $active ? 1 : 0,
            ':id'        => $id,
        ]);
    }

    /**
     * Change le rôle d'un utilisateur — sélecteur de roles.php
     * (UtilisateurController::changeRole).
     */
    public function setRole(int $id, int $roleId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE utilisateurs SET id_role = :role_id WHERE id = :id');

        return $stmt->execute([
            ':role_id' => $roleId,
            ':id'      => $id,
        ]);
    }

    /**
     * Vérifie si un e-mail est déjà pris — Validator et pages
     * ajouter/modifier-utilisateur.php.
     *
     * $ignoreId permet d'ignorer l'utilisateur en cours de modification.
     * Deux paramètres distincts sont utilisés pour rester compatible avec
     * les requêtes préparées natives de MySQL.
     */
    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM utilisateurs
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
     * Compte les administrateurs actifs — avant suppression, désactivation
     * ou rétrogradation (UtilisateurController) pour protéger le dernier.
     */
    public function countActiveAdmins(): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM utilisateurs AS u
             INNER JOIN roles AS r ON r.id = u.id_role
             WHERE u.statut = 1 AND r.nom = 'Admin'"
        );
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Met à jour la date de dernière connexion — UtilisateurController::login()
     * après un mot de passe correct.
     */
    public function touchLastLogin(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE utilisateurs SET last_login = NOW() WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Construit le motif LIKE en neutralisant les jokers saisis par
     * l'utilisateur. Sans cela, taper « % » afficherait tous les comptes.
     * La valeur reste un paramètre lié : ce n'est pas une protection SQL,
     * seulement une correction du comportement de recherche.
     */
    private function likePattern(string $search): string
    {
        $escaped = str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $search
        );

        return '%' . $escaped . '%';
    }
}
