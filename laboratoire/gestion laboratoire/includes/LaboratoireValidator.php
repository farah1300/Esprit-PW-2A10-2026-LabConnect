<?php
/**
 * Validation serveur des laboratoires.
 *
 * Les mêmes règles sont utilisées par les formulaires HTML et par l'API
 * JSON : une seule source de vérité, donc des messages et des contraintes
 * identiques quel que soit le point d'entrée.
 */

class LaboratoireValidator
{
    public function __construct(private Laboratoire $laboratoires)
    {
    }

    /**
     * Nettoie les valeurs avant validation.
     * Le téléphone est débarrassé des espaces, points et tirets de saisie ;
     * il doit ensuite contenir exactement 8 chiffres.
     */
    public static function normalize(array $data): array
    {
        $telephone = preg_replace('/[\s.\-]+/u', '', (string) ($data['telephone'] ?? ''));

        return [
            'nom'      => trim((string) ($data['nom'] ?? '')),
            'ville'    => trim((string) ($data['ville'] ?? '')),
            'adresse'  => trim((string) ($data['adresse'] ?? '')),
            'telephone'=> $telephone ?? '',
            'email'    => trim((string) ($data['email'] ?? '')),
            'horaires' => trim((string) ($data['horaires'] ?? '')),
            'actif'    => isset($data['actif']) ? ((int) $data['actif'] === 1 ? 1 : 0) : 1,
        ];
    }

    /**
     * @param int|null $ignoreId Laboratoire à ignorer pour l'unicité de l'e-mail.
     * @return array ['champ' => 'message'] ; tableau vide si tout est valide.
     */
    public function validate(array $data, ?int $ignoreId = null): array
    {
        $errors = [];

        $this->validateLength($data['nom'] ?? '', 'nom', 'Le nom', 2, 150, $errors);
        $this->validateLength($data['ville'] ?? '', 'ville', 'La ville', 2, 100, $errors);
        $this->validateLength($data['adresse'] ?? '', 'adresse', "L'adresse", 5, 255, $errors);

        $telephone = (string) ($data['telephone'] ?? '');

        if ($telephone === '') {
            $errors['telephone'] = 'Le téléphone est obligatoire.';
        } elseif (!preg_match('/^[2-9][0-9]{7}$/', $telephone)) {
            $errors['telephone'] = 'Le téléphone doit contenir exactement 8 chiffres '
                . 'et commencer par 2, 3, 4, 5, 6, 7, 8 ou 9.';
        }

        $email = (string) ($data['email'] ?? '');

        if ($email === '') {
            $errors['email'] = "L'adresse e-mail est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'adresse e-mail est invalide.";
        } elseif (mb_strlen($email, 'UTF-8') > 190) {
            $errors['email'] = "L'adresse e-mail est trop longue (190 caractères maximum).";
        } elseif ($this->laboratoires->emailExists($email, $ignoreId)) {
            $errors['email'] = 'Cette adresse e-mail est déjà utilisée par un laboratoire.';
        }

        $horaires = (string) ($data['horaires'] ?? '');

        if ($horaires !== '' && mb_strlen($horaires, 'UTF-8') > 100) {
            $errors['horaires'] = 'Les horaires ne doivent pas dépasser 100 caractères.';
        }

        if (!in_array((int) ($data['actif'] ?? 1), [0, 1], true)) {
            $errors['actif'] = 'Le statut du laboratoire est invalide.';
        }

        return $errors;
    }

    /**
     * Champs obligatoires avec longueur minimale et maximale.
     * mb_strlen() compte les caractères Unicode (accents) et non les octets.
     */
    private function validateLength(
        string $value,
        string $field,
        string $label,
        int $min,
        int $max,
        array &$errors
    ): void {
        if ($value === '') {
            $errors[$field] = $label . ' est obligatoire.';
            return;
        }

        $length = mb_strlen($value, 'UTF-8');

        if ($length < $min || $length > $max) {
            $errors[$field] = $label . ' doit contenir entre ' . $min . ' et ' . $max . ' caractères.';
        }
    }
}
