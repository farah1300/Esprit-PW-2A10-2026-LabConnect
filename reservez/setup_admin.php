<?php
// Ouvre ce fichier UNE FOIS dans le navigateur, puis SUPPRIME-LE.
require_once __DIR__ . '/models/User.php';
if (User::findByEmail('admin@labconnect.tn')) exit('Admin existe déjà. Supprime ce fichier.');
User::create([
    'nom' => 'Admin', 'prenom' => 'Super', 'email' => 'admin@labconnect.tn',
    'mot_de_passe' => 'admin123', 'telephone' => '', 'date_naissance' => '',
    'sexe' => '', 'adresse' => '', 'role' => 'ADMIN', 'actif' => 1,
]);
echo "Admin créé : admin@labconnect.tn / admin123 — SUPPRIME ce fichier maintenant.";
