<?php
require_once __DIR__ . '/../config/database.php';

class User
{
    // 1. Ajouter un utilisateur
    public static function create(array $d): int
    {
        $sql = "INSERT INTO users (nom, prenom, email, mot_de_passe, telephone, date_naissance, sexe, adresse, role, actif)
                VALUES (:nom, :prenom, :email, :mdp, :tel, :dn, :sexe, :adresse, :role, :actif)";
        getPDO()->prepare($sql)->execute([
            ':nom'    => trim($d['nom']),
            ':prenom' => trim($d['prenom']),
            ':email'  => strtolower(trim($d['email'])),
            ':mdp'    => password_hash($d['mot_de_passe'], PASSWORD_BCRYPT),
            ':tel'    => $d['telephone'] ?: null,
            ':dn'     => $d['date_naissance'] ?: null,
            ':sexe'   => $d['sexe'] ?: null,
            ':adresse'=> $d['adresse'] ?: null,
            ':role'   => in_array($d['role'] ?? 'PATIENT', ['ADMIN','PATIENT'], true) ? $d['role'] : 'PATIENT',
            ':actif'  => isset($d['actif']) ? (int)$d['actif'] : 1,
        ]);
        return (int)getPDO()->lastInsertId();
    }

    // 2 + 6. Afficher la liste / Rechercher
    public static function all(string $q = ''): array
    {
        if ($q !== '') {
            $st = getPDO()->prepare(
                "SELECT * FROM users
                 WHERE nom LIKE :q OR prenom LIKE :q OR email LIKE :q OR telephone LIKE :q
                 ORDER BY id DESC");
            $st->execute([':q' => "%$q%"]);
        } else {
            $st = getPDO()->query("SELECT * FROM users ORDER BY id DESC");
        }
        return $st->fetchAll();
    }

    // 3. Consulter les détails
    public static function find(int $id): ?array
    {
        $st = getPDO()->prepare("SELECT * FROM users WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $st = getPDO()->prepare("SELECT * FROM users WHERE email = ?");
        $st->execute([strtolower(trim($email))]);
        return $st->fetch() ?: null;
    }

    // 4. Modifier (mot de passe changé seulement s'il est rempli)
    public static function update(int $id, array $d): void
    {
        $sql = "UPDATE users SET nom=:nom, prenom=:prenom, email=:email, telephone=:tel,
                date_naissance=:dn, sexe=:sexe, adresse=:adresse WHERE id=:id";
        getPDO()->prepare($sql)->execute([
            ':nom' => trim($d['nom']), ':prenom' => trim($d['prenom']),
            ':email' => strtolower(trim($d['email'])),
            ':tel' => $d['telephone'] ?: null, ':dn' => $d['date_naissance'] ?: null,
            ':sexe' => $d['sexe'] ?: null, ':adresse' => $d['adresse'] ?: null,
            ':id' => $id,
        ]);
        if (!empty($d['mot_de_passe'])) {
            self::updatePassword($id, $d['mot_de_passe']);
        }
    }

    // 5. Supprimer
    public static function delete(int $id): void
    {
        getPDO()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }

    // 7. Attribuer un rôle
    public static function setRole(int $id, string $role): void
    {
        if (!in_array($role, ['ADMIN', 'PATIENT'], true)) return;
        getPDO()->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $id]);
    }

    // 8. Activer / désactiver
    public static function toggleActif(int $id): void
    {
        getPDO()->prepare("UPDATE users SET actif = 1 - actif WHERE id = ?")->execute([$id]);
    }

    // 9. Gérer les informations de connexion
    public static function updatePassword(int $id, string $password): void
    {
        getPDO()->prepare("UPDATE users SET mot_de_passe = ? WHERE id = ?")
            ->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
    }

    // 10. Mot de passe oublié : créer un token (valable 1h)
    public static function createResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        getPDO()->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$userId]);
        getPDO()->prepare(
            "INSERT INTO password_resets (user_id, token_hash, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
        )->execute([$userId, hash('sha256', $token)]);
        return $token;
    }

    public static function findByResetToken(string $token): ?array
    {
        $st = getPDO()->prepare(
            "SELECT u.* FROM password_resets r JOIN users u ON u.id = r.user_id
             WHERE r.token_hash = ? AND r.used = 0 AND r.expires_at > NOW()");
        $st->execute([hash('sha256', $token)]);
        return $st->fetch() ?: null;
    }

    public static function consumeResetToken(string $token): void
    {
        getPDO()->prepare("UPDATE password_resets SET used = 1 WHERE token_hash = ?")
            ->execute([hash('sha256', $token)]);
    }

    public static function emailExists(string $email, int $exceptId = 0): bool
    {
        $st = getPDO()->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?");
        $st->execute([strtolower(trim($email)), $exceptId]);
        return (bool)$st->fetchColumn();
    }

    // Validation commune
    public static function validate(array $d, bool $passwordRequired, int $exceptId = 0): array
    {
        $e = [];
        if (trim($d['nom'] ?? '') === '')    $e[] = "Le nom est obligatoire.";
        if (trim($d['prenom'] ?? '') === '') $e[] = "Le prénom est obligatoire.";
        if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) $e[] = "Email invalide.";
        elseif (self::emailExists($d['email'], $exceptId))         $e[] = "Cet email existe déjà.";
        $pw = $d['mot_de_passe'] ?? '';
        if (($passwordRequired || $pw !== '') && strlen($pw) < 6)  $e[] = "Mot de passe : 6 caractères minimum.";
        if (!empty($d['telephone']) && !preg_match('/^[0-9+ ]{8,15}$/', $d['telephone'])) $e[] = "Téléphone invalide.";
        return $e;
    }
}
