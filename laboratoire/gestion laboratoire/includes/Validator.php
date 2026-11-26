<?php
/**
 * Validation serveur réutilisable — Module 1 (Utilisateurs).
 *
 * Le JavaScript de TASK 4 ne sert qu'au confort de l'utilisateur : c'est
 * ce fichier, exécuté en PHP, qui constitue la vraie barrière de sécurité.
 * Les règles sont centralisées ici pour être identiques à la création,
 * à l'inscription et à la modification.
 *
 * Les modèles Utilisateur et Role doivent être chargés avant d'instancier
 * cette classe (le contrôleur s'en charge).
 */

class Validator
{
    public function __construct(
        private Utilisateur $utilisateurs,
        private Role $roles
    ) {
    }

    /**
     * Valide les champs d'un utilisateur.
     *
     * @param array    $data             Données du formulaire (déjà récupérées).
     * @param int|null $ignoreId         Utilisateur à ignorer pour l'unicité de l'e-mail.
     * @param bool     $passwordRequired true à la création/inscription, false en
     *                                   modification si le mot de passe est vide.
     *
     * @return array ['champ' => 'message'] ; tableau vide si tout est valide.
     */
    public function validateUtilisateur(
        array $data,
        ?int $ignoreId = null,
        bool $passwordRequired = true
    ): array {
        $errors = [];

        $this->validateName($data['nom'] ?? '', 'nom', 'Le nom', $errors);
        $this->validateName($data['prenom'] ?? '', 'prenom', 'Le prénom', $errors);

        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '') {
            $errors['email'] = "L'adresse e-mail est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // FILTER_VALIDATE_EMAIL refuse les chaînes qui ne respectent pas
            // la syntaxe d'une adresse (pas d'espace, un seul @, domaine...).
            $errors['email'] = "L'adresse e-mail est invalide.";
        } elseif (mb_strlen($email, 'UTF-8') > 190) {
            // Même limite que la colonne VARCHAR(190).
            $errors['email'] = "L'adresse e-mail est trop longue (190 caractères maximum).";
        } elseif ($this->utilisateurs->emailExists($email, $ignoreId)) {
            $errors['email'] = "Cette adresse e-mail est déjà utilisée.";
        }

        $telephone = trim((string) ($data['telephone'] ?? ''));

        // Téléphone facultatif, mais s'il est rempli il doit respecter le
        // format tunisien : 8 chiffres commençant par 2, 3, ... ou 9.
        if ($telephone !== '' && !preg_match('/^[2-9][0-9]{7}$/', $telephone)) {
            $errors['telephone'] = 'Le téléphone doit contenir exactement 8 chiffres '
                . 'et commencer par 2, 3, 4, 5, 6, 7, 8 ou 9.';
        }

        $password = (string) ($data['password'] ?? '');
        $passwordConfirm = (string) ($data['password_confirm'] ?? '');

        // En modification, un mot de passe vide signifie « conserver l'ancien ».
        if ($passwordRequired || $password !== '') {
            if ($password === '') {
                $errors['password'] = 'Le mot de passe est obligatoire.';
            } else {
                if (mb_strlen($password, 'UTF-8') < 8) {
                    $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
                } elseif (!preg_match('/\p{L}/u', $password)) {
                    $errors['password'] = 'Le mot de passe doit contenir au moins une lettre.';
                } elseif (!preg_match('/[0-9]/', $password)) {
                    $errors['password'] = 'Le mot de passe doit contenir au moins un chiffre.';
                }

                if ($password !== $passwordConfirm) {
                    $errors['password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
                }
            }
        }

        $roleId = (int) ($data['role_id'] ?? 0);

        if ($roleId <= 0) {
            $errors['role_id'] = 'Le rôle est obligatoire.';
        } elseif ($this->roles->find($roleId) === null) {
            // Un identifiant forgé dans le HTML ne suffit pas : le rôle doit
            // réellement exister dans la table roles.
            $errors['role_id'] = 'Le rôle sélectionné est invalide.';
        }

        return $errors;
    }

    /**
     * Valide un nom ou un prénom : obligatoire, 2 à 60 caractères.
     *
     * mb_strlen() compte les caractères Unicode (accents) et non les octets.
     */
    private function validateName(string $value, string $field, string $label, array &$errors): void
    {
        $value = trim($value);

        if ($value === '') {
            $errors[$field] = $label . ' est obligatoire.';
            return;
        }

        $length = mb_strlen($value, 'UTF-8');

        if ($length < 2 || $length > 60) {
            $errors[$field] = $label . ' doit contenir entre 2 et 60 caractères.';
        }
    }
}
