<?php
/**
 * Configuration générale de LabConnect.
 *
 * Ce fichier est inclus par includes/auth.php, donc par toutes les pages.
 * Il ne contient aucun secret : les identifiants MySQL sont dans
 * config/database.php.
 */

// Les dates (création, rendez-vous...) doivent utiliser l'heure tunisienne.
date_default_timezone_set('Africa/Tunis');

// Nom affiché dans les titres et le pied de page.
define('APP_NAME', 'LabConnect');

/**
 * BASE_URL = adresse de la racine du projet, terminée par un "/".
 *
 * Pourquoi ne pas écrire href="css/style.css" ?
 * Parce qu'un chemin relatif change selon la page appelée. Avec BASE_URL,
 * un lien fonctionne aussi bien à la racine que dans un sous-dossier.
 *
 * Les pages du module Utilisateurs sont dans "gestion-utilisateur/".
 * Quand l'une de ces pages s'exécute, dirname(SCRIPT_NAME) contient ce
 * segment : on le retire pour que BASE_URL reste la racine du projet.
 * Sans cela, les liens vers assets/, includes/ et index.php seraient cassés.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath   = str_replace('\\', '/', dirname($scriptName));

$moduleDir    = 'gestion-utilisateur';
$moduleSuffix = '/' . $moduleDir;

if ($basePath === $moduleSuffix || str_ends_with($basePath, $moduleSuffix)) {
    $basePath = substr($basePath, 0, -strlen($moduleSuffix));
}

// dirname('/index.php') renvoie '/', dirname('/labconnect/x.php') renvoie '/labconnect'.
$basePath = ($basePath === '' || $basePath === '.' || $basePath === '/') ? '/' : rtrim($basePath, '/') . '/';

// Un espace dans un nom de dossier doit être encodé pour rester une URL valide.
$basePath = str_replace(' ', '%20', $basePath);

define('BASE_URL', $basePath);

// URL des pages du module Utilisateurs (utilisateurs.php, login.php...).
// Le dossier ne contient ni espace ni apostrophe : aucun encodage spécial.
define('USER_MODULE_URL', BASE_URL . $moduleDir . '/');
