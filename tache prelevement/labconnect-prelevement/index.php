<?php
require_once "config/database.php";

$db = Database::getConnexion();
echo "Connexion réussie à la base LabConnect !";