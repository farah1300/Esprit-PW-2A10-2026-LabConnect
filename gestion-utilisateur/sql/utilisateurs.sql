CREATE DATABASE IF NOT EXISTS labconnect
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE labconnect;

CREATE TABLE IF NOT EXISTS roles (

    id  INT UNSIGNED NOT NULL AUTO_INCREMENT,


    nom VARCHAR(50) NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nom (nom)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS utilisateurs (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,

    nom           VARCHAR(60)  NOT NULL,
    prenom        VARCHAR(60)  NOT NULL,

    email         VARCHAR(190) NOT NULL,

  
    telephone     CHAR(8)      NULL,

    
    mot_de_passe  VARCHAR(255) NOT NULL,

    id_role       INT UNSIGNED NOT NULL,

    statut        TINYINT(1)   NOT NULL DEFAULT 1,

    date_creation TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    last_login    TIMESTAMP    NULL DEFAULT NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_utilisateurs_email (email),

    KEY idx_utilisateurs_id_role (id_role),

    CONSTRAINT fk_utilisateurs_role
        FOREIGN KEY (id_role) REFERENCES roles (id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT chk_utilisateurs_telephone
        CHECK (telephone IS NULL OR telephone REGEXP '^[2-9][0-9]{7}$')
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (nom) VALUES ('Admin'), ('Patient');

INSERT INTO utilisateurs
    (nom, prenom, email, telephone, mot_de_passe, id_role, statut)
SELECT 'Admin', 'LabConnect', 'admin@labconnect.tn', NULL,
       '$2y$10$FdRT6bRc/wjI9I01QFGrcOuVm7rfJC0WBOnn1NWYw52smRbefwczG',
       r.id, 1
FROM roles AS r
WHERE r.nom = 'Admin'
  AND NOT EXISTS (
      SELECT 1
      FROM utilisateurs AS u
      INNER JOIN roles AS ur ON ur.id = u.id_role
      WHERE ur.nom = 'Admin'
  );
