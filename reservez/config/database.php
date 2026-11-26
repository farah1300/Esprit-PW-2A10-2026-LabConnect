<?php
define('BASE_URL', '/labconnect'); // changer selon ton dossier dans htdocs

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=labconnect;charset=utf8mb4',
            'root',   // utilisateur MySQL
            '',       // mot de passe MySQL
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}
