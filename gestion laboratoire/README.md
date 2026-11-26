# LabConnect — Module 1 : Gestion des utilisateurs

Ce dossier contient le module **Gestion des utilisateurs** du projet LabConnect
(PHP / MySQL / PDO / MVC). Il couvre les 9 fonctionnalités demandées :

1. Ajouter un utilisateur
2. Lister les utilisateurs
3. Afficher la fiche d'un utilisateur
4. Modifier un utilisateur
5. Supprimer un utilisateur
6. Rechercher un utilisateur
7. Attribuer un rôle (Admin ou Patient)
8. Activer / désactiver un compte
9. Gérer les informations de connexion (e-mail, mot de passe)

---

## 1. Arborescence utile

```
gestion laboratoire/
├── config/
│   ├── config.php              # BASE_URL, nom de l'application, timezone
│   └── database.php            # connexion PDO (hôte, base, utilisateur, mot de passe)
├── includes/
│   ├── auth.php                # session, requireLogin(), requireRole(), CSRF, flash
│   ├── helpers.php             # e(), formatDateTime(), roleBadgeClass()
│   ├── Validator.php           # validation serveur commune
│   ├── header.php              # en-tête HTML + barre de navigation
│   ├── sidebar.php             # menu selon le rôle
│   └── footer.php              # pied de page + scripts
├── assets/
│   └── js/validation.js        # validation JavaScript (confort, pas sécurité)
├── gestion-utilisateur/
│   ├── utilisateurs.php        # liste + recherche + suppression + activation
│   ├── ajouter-utilisateur.php # ajout
│   ├── modifier-utilisateur.php# modification
│   ├── details-utilisateur.php # fiche
│   ├── roles.php               # rôles + attribution
│   ├── login.php               # connexion
│   ├── logout.php              # déconnexion
│   ├── inscription.php         # inscription publique (Patient)
│   ├── controllers/
│   │   └── UtilisateurController.php
│   ├── models/
│   │   ├── Utilisateur.php
│   │   └── Role.php
│   └── sql/
│       └── utilisateurs.sql    # schéma + données de référence
└── README.md                   # ce fichier
```

---

## 2. Prérequis

- **PHP 8.0 ou supérieur** (le code utilise les types `mixed`, les propriétés
  typées et `str_ends_with`).
- **MySQL 8.x** ou **MariaDB 10.4+** (XAMPP convient).
- Extensions PHP activées : `pdo_mysql`, `mbstring`, `openssl`.
- Un navigateur récent.

---

## 3. Installation

### 3.1 Placer le projet

Copier le dossier `gestion laboratoire` dans le répertoire web de XAMPP :

```
C:\xampp\htdocs\gestion laboratoire\
```

> Le nom contient un espace : `config/config.php` l'encode automatiquement en
> `%20` dans `BASE_URL`. Ne pas renommer le dossier `gestion-utilisateur`
> (sans espace), car `USER_MODULE_URL` s'appuie sur ce nom.

### 3.2 Démarrer les services

Dans le panneau XAMPP, démarrer **Apache** puis **MySQL**.

### 3.3 Créer la base et les tables

Deux méthodes possibles.

**A. phpMyAdmin**

1. Ouvrir `http://localhost/phpmyadmin`.
2. Onglet **Importer**.
3. Choisir `gestion-utilisateur/sql/utilisateurs.sql`.
4. Exécuter.

**B. Ligne de commande**

```bash
mysql -u root -p < "gestion-utilisateur/sql/utilisateurs.sql"
```

Sous PowerShell (Windows), la redirection `<` n'est pas supportée ; utiliser :

```powershell
Get-Content "gestion-utilisateur/sql/utilisateurs.sql" -Raw |
  & "C:\xampp\mysql\bin\mysql.exe" -u root
```

Le script est idempotent : il crée la base `labconnect` si besoin, crée
`roles` et `utilisateurs`, insère les rôles `Admin` et `Patient`, puis crée
un compte administrateur de démonstration **uniquement si aucun Admin
n'existe**.

### 3.4 Compte administrateur de démonstration

| Champ | Valeur |
|---|---|
| E-mail | `admin@labconnect.tn` |
| Mot de passe | `Admin1234` |

> **Important :** changer ce mot de passe après la première connexion
> (page `modifier-utilisateur.php`).
>
> Si le hash fourni ne fonctionne pas sur votre installation, générez-en un
> nouveau avec PHP :
>
> ```bash
> php -r "echo password_hash('Admin1234', PASSWORD_DEFAULT), PHP_EOL;"
> ```
>
> puis remplacez la valeur dans le `INSERT` du fichier SQL.

---

## 4. Configuration

### 4.1 Base de données

Fichier : `config/database.php`

```php
$dbHost = 'localhost';
$dbName = 'labconnect';
$dbUser = 'root';
$dbPass = ''; // XAMPP : vide par défaut
```

Adapter `$dbUser` / `$dbPass` si votre MySQL possède un mot de passe.

Le fichier fixe aussi :

- `PDO::ATTR_ERRMODE => ERRMODE_EXCEPTION` : les erreurs SQL ne sont pas
  silencieuses ;
- `PDO::ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC` ;
- `PDO::ATTR_EMULATE_PREPARES => false` : vraies requêtes préparées MySQL.

### 4.2 URL de base

Fichier : `config/config.php`

`BASE_URL` est calculé automatiquement à partir de `$_SERVER['SCRIPT_NAME']`.
Il n'y a rien à modifier si le projet est dans `htdocs`. Le module
`gestion-utilisateur` est retiré du chemin pour que les liens vers `assets/`
et `index.php` restent corrects.

### 4.3 Schéma de la table `utilisateurs`

| Colonne | Type | Rôle |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | clé primaire |
| `nom` | VARCHAR(60) | nom |
| `prenom` | VARCHAR(60) | prénom |
| `email` | VARCHAR(190) UNIQUE | identifiant de connexion |
| `telephone` | CHAR(8) NULL | 8 chiffres, facultatif |
| `mot_de_passe` | VARCHAR(255) | hash `password_hash()` |
| `id_role` | INT UNSIGNED FK → `roles.id` | Admin ou Patient |
| `statut` | TINYINT(1) | 1 = actif, 0 = désactivé |
| `date_creation` | TIMESTAMP | date de création |
| `last_login` | TIMESTAMP NULL | dernière connexion (extra d'affichage) |

**Correspondance dans le code :** le modèle `Utilisateur.php` expose des
alias (`password_hash`, `role_id`, `is_active`, `created_at`) pour que le
contrôleur et les vues restent lisibles. La correspondance est faite
uniquement dans les requêtes SQL du modèle et dans `includes/auth.php`.

---

## 5. Lancement

1. Ouvrir :
   `http://localhost/gestion%20laboratoire/gestion-utilisateur/login.php`
2. Se connecter avec le compte Admin de démonstration.
3. Le tableau de bord puis le menu **Module 1 — Utilisateurs** donnent accès
   à `utilisateurs.php` et `roles.php`.

Si Apache n'utilise pas le port 80, remplacer `localhost` par
`localhost:8080` (ou le port configuré).

---

## 6. Fonctionnalités et pages

| # | Fonctionnalité | Page / action |
|---|---|---|
| 1 | Ajouter | `ajouter-utilisateur.php` → `UtilisateurController::create()` |
| 2 | Lister | `utilisateurs.php` → `index()` |
| 3 | Détails | `details-utilisateur.php` → `show()` |
| 4 | Modifier | `modifier-utilisateur.php` → `edit()` |
| 5 | Supprimer | bouton POST dans `utilisateurs.php` → `destroy()` |
| 6 | Rechercher | formulaire GET `q` + filtre `role` dans `utilisateurs.php` |
| 7 | Attribuer un rôle | `roles.php` → `changeRole()` |
| 8 | Activer / désactiver | bouton POST dans `utilisateurs.php` → `toggleActive()` |
| 9 | Gérer la connexion | e-mail + mot de passe dans `modifier-utilisateur.php` |

---

## 7. Sécurité mise en place

- **Contrôle d'accès** : `requireRole('Admin')` en haut de chaque page Admin
  **et** au début de chaque méthode du contrôleur (défense en profondeur).
- **Mots de passe** : `password_hash(..., PASSWORD_DEFAULT)` à la création et
  à la modification ; `password_verify()` à la connexion. Jamais de mot de
  passe en clair en base ni dans le HTML.
- **Comptes désactivés** : refusés à la connexion (`statut = 0`) et
  déconnectés immédiatement par `requireLogin()` si le statut change.
- **E-mail unique** : contrainte `UNIQUE` en base + validation PHP +
  interception de l'erreur SQL `1062` en cas de course entre deux requêtes.
- **CSRF** : jeton `random_bytes(32)` + `hash_equals()` sur tous les POST.
- **Session** : `use_strict_mode`, cookie `HttpOnly`, `SameSite=Lax`,
  `session_regenerate_id(true)` à la connexion.
- **SQL** : uniquement des requêtes préparées ; aucune concaténation de
  valeur utilisateur.
- **XSS** : toute sortie dynamique passe par `e()` / `htmlspecialchars()`.
- **Règles métier** : un Admin ne peut ni supprimer ni désactiver son propre
  compte ; le dernier Admin actif ne peut être ni supprimé, ni désactivé, ni
  rétrogradé.

---

## 8. Validation PHP + JavaScript

| Formulaire | Validation PHP | Validation JS |
|---|---|---|
| Ajout (`ajouter-utilisateur.php`) | `Validator::validateUtilisateur()` | `data-validate="user"` |
| Modification (`modifier-utilisateur.php`) | `Validator::validateUtilisateur()` + règles dernier Admin | `data-validate="user"` + `data-password-optional="1"` |
| Recherche (`utilisateurs.php`) | `UtilisateurController::index()` : type, trim, 100 caractères max, rôle existant | `data-validate="search"` + règle `q` (100 caractères) |
| Attribution de rôle (`roles.php`) | `changeRole()` : rôle existant, dernier Admin actif | `data-validate="role"` + `required` |
| Connexion (`login.php`) | `login()` : champs obligatoires, `password_verify` | `data-validate="login"` |
| Inscription (`inscription.php`) | `Validator` + rôle Patient forcé | `data-validate="user"` |

Le JavaScript améliore le confort mais **ne remplace jamais** PHP : il peut
être désactivé par l'utilisateur.

---

## 9. Tests manuels (checklist)

### Installation / accès
- [ ] La base `labconnect` existe et contient `roles` + `utilisateurs`.
- [ ] `admin@labconnect.tn` / `Admin1234` permet de se connecter.
- [ ] Sans connexion, `utilisateurs.php` redirige vers `login.php`.
- [ ] Connecté en **Patient**, `utilisateurs.php` affiche « Accès refusé » et
      redirige vers le tableau de bord.
- [ ] Connecté en **Admin**, le menu affiche Utilisateurs et Rôles.

### CRUD
- [ ] Ajouter un Patient avec des données valides : le compte apparaît dans
      la liste et peut se connecter.
- [ ] Ajouter avec un e-mail déjà utilisé : message « déjà utilisée ».
- [ ] Ajouter avec un mot de passe de 7 caractères : message d'erreur.
- [ ] Ajouter avec un téléphone invalide (`123`) : message d'erreur.
- [ ] Modifier le nom, le prénom, l'e-mail, le téléphone : la fiche est à jour.
- [ ] Modifier sans mot de passe : l'ancien mot de passe fonctionne encore.
- [ ] Modifier avec un nouveau mot de passe : l'ancien ne fonctionne plus.
- [ ] Supprimer un Patient : il disparaît de la liste.
- [ ] Ouvrir `details-utilisateur.php?id=999999` : message « introuvable ».
- [ ] Ouvrir `modifier-utilisateur.php?id=abc` : message « introuvable ».

### Recherche
- [ ] Rechercher par nom, prénom et e-mail : les résultats correspondent.
- [ ] Rechercher `%` : aucun résultat parasite (les jokers sont neutralisés).
- [ ] Filtrer par rôle Admin / Patient : seuls les bons comptes s'affichent.
- [ ] Saisir plus de 100 caractères : message d'erreur (JS) ; en désactivant
      JS, le serveur tronque et affiche aussi le message.
- [ ] Le compteur « X utilisateurs affichés » est correct.

### Rôles
- [ ] Changer un Patient en Admin : le badge et l'accès changent.
- [ ] Changer un Admin en Patient alors qu'un autre Admin actif existe :
      autorisé.
- [ ] Tenter de rétrograder le **dernier Admin actif** : refusé (bouton
      désactivé + contrôle serveur).
- [ ] Forger un POST `role_id=999` : message « rôle invalide ».

### Activation / désactivation
- [ ] Désactiver un Patient : badge « Désactivé ».
- [ ] Ce Patient ne peut plus se connecter : message « compte désactivé ».
- [ ] Un Patient déjà connecté est déconnecté à la requête suivante.
- [ ] Réactiver le compte : la connexion fonctionne à nouveau.
- [ ] Tenter de désactiver son propre compte Admin : refusé.
- [ ] Tenter de désactiver le dernier Admin actif : refusé.

### Sécurité
- [ ] Supprimer son propre compte Admin : refusé.
- [ ] Soumettre un formulaire sans jeton CSRF : erreur 403.
- [ ] Taper une URL Admin en étant Patient : accès refusé.
- [ ] Vérifier qu'aucun mot de passe en clair n'apparaît dans la base
      (`SELECT mot_de_passe FROM utilisateurs`).
- [ ] Vérifier qu'un e-mail en double est refusé même par requête forgée.

---

## 10. Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| `Table 'labconnect.users' doesn't exist` | ancienne version du code | ce module utilise désormais `utilisateurs` ; réimporter `sql/utilisateurs.sql` |
| `Table 'labconnect.utilisateurs' doesn't exist` | SQL non importé | importer `sql/utilisateurs.sql` |
| `Access denied for user 'root'` | mot de passe MySQL | renseigner `$dbPass` dans `config/database.php` |
| Page blanche | erreur PHP | activer `display_errors` en local ou consulter `C:\xampp\apache\logs\error.log` |
| Connexion impossible avec `Admin1234` | hash de démonstration | régénérer le hash (voir §3.4) |
| Le menu ne s'affiche pas | rôle incorrect en session | se déconnecter puis se reconnecter |
| Liens cassés | dossier renommé | garder `gestion-utilisateur` et vérifier `BASE_URL` |

---

## 11. Transparence sur l'utilisation de l'IA

Ce module a été construit avec l'aide d'un assistant IA. Pour être honnête
avec l'équipe pédagogique, voici la liste des fichiers concernés.

### 11.1 Fichiers écrits ou modifiés par l'IA dans cette session

| Fichier | Nature de l'intervention |
|---|---|
| `gestion-utilisateur/models/Utilisateur.php` | réécriture des requêtes SQL pour la table officielle `utilisateurs` (alias `password_hash`, `role_id`, `is_active`, `created_at`), échappement des jokers `LIKE` |
| `gestion-utilisateur/models/Role.php` | `countUsers()` adapté à `utilisateurs.id_role` |
| `includes/auth.php` | requête de `requireLogin()` adaptée à `utilisateurs` (`statut`, `id_role`) |
| `gestion-utilisateur/sql/utilisateurs.sql` | schéma officiel `utilisateurs` + migration commentée depuis `users` |
| `gestion-utilisateur/controllers/UtilisateurController.php` | validation serveur de la recherche (type, 100 caractères) |
| `gestion-utilisateur/utilisateurs.php` | `requireRole('Admin')` en haut de page, `data-validate="search"`, `maxlength="100"`, affichage de l'erreur |
| `gestion-utilisateur/ajouter-utilisateur.php` | `requireRole('Admin')` en haut de page |
| `gestion-utilisateur/modifier-utilisateur.php` | `requireRole('Admin')` avant la lecture de l'ID |
| `gestion-utilisateur/details-utilisateur.php` | `requireRole('Admin')` avant la lecture de l'ID |
| `gestion-utilisateur/roles.php` | `requireRole('Admin')` en haut de page, `data-validate="role"`, `required`, emplacement d'erreur |
| `assets/js/validation.js` | règle de recherche `q`, prise en charge des erreurs hors `.form-group` |
| `README.md` | ce document |
| `AUDIT.md` | audit détaillé, corrections et checklist |

### 11.2 Fichiers préexistants non modifiés dans cette session

`login.php`, `inscription.php`, `logout.php`, `config/config.php`,
`config/database.php`, `includes/helpers.php`, `includes/Validator.php`,
`includes/header.php`, `includes/sidebar.php`, `includes/footer.php`,
`index.php`.

> Les membres de l'équipe doivent vérifier et savoir expliquer chaque ligne
> listée ci-dessus. L'IA ne remplace pas la compréhension du code.

---

## 12. Limites connues / choix assumés

- `inscription.php` (auto-inscription Patient) n'est pas dans les 9
  fonctionnalités officielles, mais elle est conservée car la page de
  connexion y renvoie. Le rôle est forcé à `Patient`.
- `logout.php` est appelé par un lien GET. Un CSRF de déconnexion est peu
  dangereux (il ne fait que déconnecter), mais il pourrait être transformé en
  POST + jeton si l'enseignant l'exige.
- `last_login` est une colonne d'affichage supplémentaire, absente du schéma
  minimal officiel ; elle ne participe à aucune règle métier.
- Les rôles sont des données de référence : aucune page ne permet de créer ou
  supprimer un rôle, conformément au périmètre.
