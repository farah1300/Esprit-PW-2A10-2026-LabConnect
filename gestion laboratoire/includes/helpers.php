<?php
/**
 * Petites fonctions d'affichage utilisées par les pages du module
 * Utilisateurs.
 *
 * Elles ne contiennent aucune règle métier : le contrôleur et les modèles
 * restent seuls responsables des décisions (rôle, statut, dernier Admin...).
 */

if (!function_exists('e')) {
    /**
     * Échappe une valeur avant de l'afficher dans du HTML.
     *
     * ENT_QUOTES protège aussi les apostrophes et les guillemets : une
     * valeur saisie dans un formulaire ne peut donc pas casser un attribut
     * ni injecter une balise <script>.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatDateTime')) {
    /**
     * Formate une date MySQL (Y-m-d H:i:s) pour l'affichage français.
     * Retourne un tiret quand la valeur est absente (ex. jamais connecté).
     */
    function formatDateTime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? '—' : date('d/m/Y à H:i', $timestamp);
    }
}

if (!function_exists('roleBadgeClass')) {
    /**
     * Classe CSS du badge de rôle : violet pour Admin, bleu pour Patient.
     */
    function roleBadgeClass(string $role): string
    {
        return $role === 'Admin' ? 'admin' : 'patient';
    }
}
