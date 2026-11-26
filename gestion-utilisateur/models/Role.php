<?php
/**
 * Modèle des rôles (table roles).
 *
 * Les rôles sont des données de référence : 'Admin' et 'Patient'.
 * Aucune règle métier ici (ex. « dernier Admin actif ») : ces contrôles
 * appartiennent à UtilisateurController, avant toute écriture en base.
 */

class Role
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Tous les rôles triés par nom — filtre de utilisateurs.php,
     * formulaires ajouter/modifier-utilisateur.php et page roles.php.
     */
    public function all(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nom FROM roles ORDER BY nom ASC');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Un rôle par son identifiant — validation des formulaires et
     * UtilisateurController::changeRole().
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nom FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $role = $stmt->fetch();

        return $role === false ? null : $role;
    }

    /**
     * Un rôle par son nom ('Admin', 'Patient') — inscription.php et
     * UtilisateurController pour forcer le rôle Patient à l'inscription.
     */
    public function findByName(string $nom): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nom FROM roles WHERE nom = :nom LIMIT 1');
        $stmt->execute([':nom' => $nom]);
        $role = $stmt->fetch();

        return $role === false ? null : $role;
    }

    /**
     * Nombre d'utilisateurs rattachés à un rôle — page roles.php.
     */
    public function countUsers(int $roleId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE id_role = :role_id');
        $stmt->execute([':role_id' => $roleId]);

        return (int) $stmt->fetchColumn();
    }
}
