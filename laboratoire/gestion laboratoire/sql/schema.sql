CREATE DATABASE IF NOT EXISTS labconnect
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE labconnect;

-- Ordre inverse des dépendances : la table de liaison référence les deux
-- autres tables, elle doit donc être supprimée en premier.
DROP TABLE IF EXISTS laboratoire_analyse;
DROP TABLE IF EXISTS analyses;
DROP TABLE IF EXISTS laboratoires;

-- ---------------------------------------------------------------------
--  1) laboratoires
-- ---------------------------------------------------------------------
CREATE TABLE laboratoires (
    
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,

    nom         VARCHAR(150) NOT NULL,
    adresse     VARCHAR(255) NOT NULL,
    ville       VARCHAR(100) NOT NULL,

    telephone   CHAR(8) NOT NULL,

    email       VARCHAR(190) NOT NULL,


    horaires    VARCHAR(100) NULL,

    actif       TINYINT(1) NOT NULL DEFAULT 1,

    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_laboratoires_email (email),

 
    KEY idx_laboratoires_ville (ville),

    CONSTRAINT chk_laboratoires_telephone
        CHECK (telephone REGEXP '^[2-9][0-9]{7}$')
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analyses (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,

    nom         VARCHAR(150) NOT NULL,

    categorie   VARCHAR(100) NOT NULL,

    description TEXT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_analyses_nom (nom)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE laboratoire_analyse (
    laboratoire_id INT UNSIGNED NOT NULL,
    analyse_id     INT UNSIGNED NOT NULL,

    prix           DECIMAL(10,3) NOT NULL,

    PRIMARY KEY (laboratoire_id, analyse_id),

    KEY idx_la_analyse (analyse_id),

    CONSTRAINT fk_la_laboratoire
        FOREIGN KEY (laboratoire_id) REFERENCES laboratoires (id)
        ON DELETE CASCADE,

    CONSTRAINT fk_la_analyse
        FOREIGN KEY (analyse_id) REFERENCES analyses (id)
        ON DELETE CASCADE,

    CONSTRAINT chk_la_prix CHECK (prix > 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO laboratoires (id, nom, adresse, ville, telephone, email, horaires, actif) VALUES
(1, 'Laboratoire El Manar', '12 Rue de la Liberté, Tunis 1000',      'Tunis',  '71234567', 'contact@labo-elmanar.tn', 'Lun–Sam : 07h30–18h00', 1),
(2, 'Laboratoire Ibn Sina', '8 Avenue Habib Bourguiba, Sfax 3000',   'Sfax',   '74567890', 'contact@labo-ibnsina.tn', 'Lun–Sam : 08h00–17h30', 1),
(3, 'Laboratoire Pasteur',  '3 Rue du Docteur Moreau, Sousse 4000',  'Sousse', '73456789', 'contact@labo-pasteur.tn', 'Lun–Ven : 07h00–19h00, Sam : 08h00–13h00', 1),
(4, 'Laboratoire Nour',     '15 Avenue Taïeb Mhiri, Nabeul 8000',    'Nabeul', '72345678', 'contact@labo-nour.tn',    'Lun–Sam : 08h00–16h00', 1),
(5, 'Laboratoire Ariana',   '22 Rue de l''Indépendance, Ariana 2080','Ariana', '71987654', 'contact@labo-ariana.tn',  'Lun–Ven : 07h30–18h30', 1);

INSERT INTO analyses (id, nom, categorie, description) VALUES
(1, 'Numération formule sanguine (NFS)', 'Hématologie',   'Dosage des globules rouges, blancs et plaquettes.'),
(2, 'Glycémie à jeun',                   'Biochimie',     'Dosage du glucose sanguin après 8 heures de jeûne.'),
(3, 'Bilan lipidique',                   'Biochimie',     'Cholestérol total, HDL, LDL et triglycérides.'),
(4, 'TSH (hormone thyroïdienne)',        'Hormonologie',  'Dosage de la thyréostimuline (TSH).'),
(5, 'Créatinine',                        'Biochimie',     'Évaluation de la fonction rénale.'),
(6, 'Sérologie hépatite B',              'Immunologie',   'Recherche de l''antigène HBs.'),
(7, 'Test de grossesse (β-HCG)',         'Hormonologie',  'Dosage de l''hormone chorionique gonadotrope.'),
(8, 'Analyse d''urine (ECBU)',           'Microbiologie', 'Examen cytobactériologique des urines.');

INSERT INTO laboratoire_analyse (laboratoire_id, analyse_id, prix) VALUES
-- Laboratoire El Manar (Tunis) : 5 analyses
(1, 1,  15.500),
(1, 2,   6.000),
(1, 3,  25.000),
(1, 4,  32.000),
(1, 8,  18.000),
-- Laboratoire Ibn Sina (Sfax) : 4 analyses
(2, 1,  14.000),
(2, 5,   7.500),
(2, 6,  40.000),
(2, 8,  20.000),
-- Laboratoire Pasteur (Sousse) : 3 analyses
(3, 2,   5.500),
(3, 3,  27.500),
(3, 4,  35.000),
-- Laboratoire Nour (Nabeul) : 2 analyses
(4, 1,  16.000),
(4, 7,  22.000),
-- Laboratoire Ariana (Ariana) : 2 analyses
(5, 2,   6.500),
(5, 8,  19.500);


