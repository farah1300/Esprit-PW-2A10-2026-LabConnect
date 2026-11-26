CREATE DATABASE IF NOT EXISTS labconnect_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE labconnect_db;

CREATE TABLE IF NOT EXISTS adresse (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rue VARCHAR(150) NOT NULL,
  ville VARCHAR(80) NOT NULL,
  code_postal VARCHAR(10) NOT NULL,
  gouvernorat VARCHAR(50) NOT NULL,
  complement VARCHAR(150) NULL,
  id_utilisateur INT NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS prelevement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_adresse INT NOT NULL,
  date_prelevement DATE NOT NULL,
  heure_prelevement TIME NOT NULL,
  motif VARCHAR(150) NOT NULL,
  statut ENUM('en_attente','confirme','en_cours','effectue','refuse','annule')
         NOT NULL DEFAULT 'en_attente',
  motif_refus VARCHAR(255) NULL,
  date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_utilisateur INT NOT NULL DEFAULT 1,
  FOREIGN KEY (id_adresse) REFERENCES adresse(id)
);