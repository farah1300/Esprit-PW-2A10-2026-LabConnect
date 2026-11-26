<?php
/**
 * Connexion PDO unique à la base de données LabConnect.
 *
 * Ce fichier est inclus par includes/auth.php et par les contrôleurs.
 * Il est volontairement séparé de config.php : config.php ne contient que
 * la configuration publique (BASE_URL, nom de l'application), tandis que
 * les identifiants MySQL restent ici.
 *
 * Adaptez $dbUser / $dbPass à votre installation XAMPP si nécessaire.
 */

$dbHost = 'localhost';
$dbName = 'labconnect';
$dbUser = 'root';
$dbPass = ''; // XAMPP : mot de passe root vide par défaut.

$dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';

$pdo = new PDO($dsn, $dbUser, $dbPass, [
    // ERRMODE_EXCEPTION : une requête SQL invalide lève une exception au
    // lieu de renvoyer false silencieusement. On détecte donc les erreurs
    // pendant le développement au lieu de servir une page vide.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    // FETCH_ASSOC : les lignes sont des tableaux associatifs (pas de
    // doublons index numérique + nom de colonne), plus lisibles dans les vues.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    // EMULATE_PREPARES = false : MySQL reçoit de vraies requêtes préparées.
    // Les valeurs restent des données et ne peuvent jamais être interprétées
    // comme du SQL (protection contre l'injection), même dans les cas limites.
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
