# Module Gestion des utilisateurs — LabConnect

Module PHP de gestion des comptes de la plateforme **LabConnect** (laboratoires d'analyses médicales) : authentification, inscription, administration des utilisateurs et attribution des rôles.

---

## Sommaire

1. [Fonctionnalités](#fonctionnalités)
2. [Rôles et droits d'accès](#rôles-et-droits-daccès)
3. [Pages du module](#pages-du-module)
4. [Arborescence](#arborescence)
5. [Règles de validation](#règles-de-validation)
6. [Règles métier](#règles-métier)
7. [Sécurité](#sécurité)
8. [Dépendances](#dépendances)
9. [Installation](#installation)
10. [Parcours utilisateur](#parcours-utilisateur)

---

## Fonctionnalités

**Espace public**
- Connexion par e-mail et mot de passe
- Inscription d'un compte **Patient** (le rôle est forcé côté serveur)
- Déconnexion (destruction de la session et du cookie)

**Espace administrateur**
- Liste des utilisateurs avec statistiques, recherche et filtre par rôle
- Ajout d'un utilisateur (Admin ou Patient)
- Consultation de la fiche détaillée d'un compte
- Modification d'un utilisateur (mot de passe facultatif)
- Activation / désactivation d'un compte
- Suppression d'un compte
- Gestion des rôles : effectif par rôle et changement de rôle d'un utilisateur

---

## Rôles et droits d'accès

| Rôle | Droits |
|------|--------|
| **Admin** | Accès complet : utilisateurs, rôles, laboratoires et analyses |
| **Patient** | Consultation des laboratoires et du catalogue des analyses |

Les rôles sont des **données de référence** : ils ne peuvent pas être supprimés depuis l'interface.

---

## Pages du module

| Fichier | Accès | Description |
|---------|-------|-------------|
| `login.php` | Public | Formulaire de connexion |
| `inscription.php` | Public | Création d'un compte Patient |
| `logout.php` | Connecté | Déconnexion |
| `utilisateurs.php` | Admin | Liste, recherche, filtre, activation/désactivation, suppression |
| `ajouter-utilisateur.php` | Admin | Création d'un compte (rôle au choix, Patient par défaut) |
| `details-utilisateur.php?id=` | Admin | Fiche détaillée d'un utilisateur |
| `modifier-utilisateur.php?id=` | Admin | Modification d'un utilisateur |
| `roles.php` | Admin | Effectif par rôle et attribution des rôles |

### Informations affichées dans la fiche utilisateur
Nom complet, e-mail, téléphone, rôle, statut (Actif / Désactivé), dernière connexion, date de création et identifiant interne.

### Statistiques de la liste (indépendantes du filtre)
Total, comptes actifs, comptes inactifs, patients, administrateurs actifs.

### Recherche et filtres (`utilisateurs.php`)
- `q` : recherche par nom, prénom ou e-mail (100 caractères maximum)
- `role` : filtre par identifiant de rôle

---

## Arborescence

```
projet/
├── includes/
│   ├── auth.php          # Session, requireRole(), currentUser(), $pdo
│   ├── helpers.php       # e(), csrfField(), setFlash()/getFlash(), formatDateTime(), roleBadgeClass()
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── assets/
│   ├── css/              # bootstrap, font-awesome, tooplate-style, labconnect
│   ├── js/               # jquery, bootstrap, validation.js
│   └── img/
└── utilisateurs/         # (dossier du module — USER_MODULE_URL)
    ├── controllers/
    │   └── UtilisateurController.php
    ├── login.php
    ├── logout.php
    ├── inscription.php
    ├── utilisateurs.php
    ├── ajouter-utilisateur.php
    ├── details-utilisateur.php
    ├── modifier-utilisateur.php
    └── roles.php
```

> Les pages ne contiennent que l'affichage. Toute la logique (validation, accès base de données, règles métier) est centralisée dans `UtilisateurController`.

### Méthodes du contrôleur utilisées par les pages

| Méthode | Utilisée par | Rôle |
|---------|--------------|------|
| `login()` | `login.php` | Vérifie e-mail, mot de passe, compte actif ; régénère la session |
| `logout()` | `logout.php` | Détruit la session et supprime le cookie |
| `register()` | `inscription.php` | Crée un compte Patient |
| `index()` | `utilisateurs.php` | Liste, recherche, filtre, statistiques |
| `create()` | `ajouter-utilisateur.php` | Crée un utilisateur |
| `show($id)` | `details-utilisateur.php` | Retourne la fiche d'un utilisateur |
| `edit($id)` | `modifier-utilisateur.php` | Affiche et enregistre les modifications |
| `toggleActive($id)` | `utilisateurs.php` | Active ou désactive un compte |
| `destroy($id)` | `utilisateurs.php` | Supprime un compte |
| `rolesPage()` | `roles.php` | Rôles, effectifs, utilisateurs |
| `changeRole($id)` | `roles.php` | Change le rôle d'un utilisateur |

---

## Règles de validation

| Champ | Règle |
|-------|-------|
| Prénom | Obligatoire, 60 caractères maximum |
| Nom | Obligatoire, 60 caractères maximum |
| E-mail | Obligatoire, format valide, 190 caractères maximum |
| Téléphone | Facultatif, 8 chiffres (ex. `98123456`) |
| Rôle | Obligatoire, doit exister en base |
| Mot de passe | 8 caractères minimum, au moins une lettre et un chiffre |
| Confirmation | Identique au mot de passe |

- À la **création**, le mot de passe est obligatoire.
- À la **modification**, le mot de passe est facultatif : s'il est vide, l'ancien hash est conservé.

La validation est faite **côté client** (`assets/js/validation.js`, via les attributs `data-validate="user | login | role | search"`) et **côté serveur** (contrôleur). Seule la validation serveur fait foi.

---

## Règles métier

- **Protection du dernier administrateur actif** : il ne peut être ni supprimé, ni désactivé, ni rétrogradé. Les boutons correspondants sont désactivés dans l'interface, et le serveur refuse l'action même si la requête est forgée.
- **Auto-protection** : un administrateur ne peut pas désactiver ni supprimer son propre compte.
- **Inscription publique** : le rôle n'est jamais demandé dans le formulaire. Le contrôleur force le rôle `Patient`, même si un `role_id` est injecté dans la requête.
- **Compte désactivé** : la connexion est refusée.
- **Dernière connexion** : affichée dans la liste et la fiche (« Jamais » si le compte ne s'est jamais connecté).

---

## Sécurité

| Mesure | Détail |
|--------|--------|
| Mots de passe | Hachés par le contrôleur, jamais stockés en clair |
| Contrôle d'accès | `requireRole('Admin')` en début de page **et** dans le contrôleur (défense en profondeur) |
| CSRF | Jeton `csrfField()` sur tous les formulaires POST |
| Actions sensibles | Suppression, activation/désactivation et changement de rôle uniquement en **POST** (jamais par simple lien) |
| XSS | Toutes les sorties passent par `e()` (échappement HTML) |
| Identifiants | Validés par `filter_input(FILTER_VALIDATE_INT)` avant tout traitement |
| Session | Régénération de l'identifiant de session à la connexion ; destruction complète à la déconnexion |
| Ordre des contrôles | Le contrôle d'accès est fait **avant** la lecture de l'`id`, afin qu'un visiteur non connecté soit redirigé vers la connexion plutôt que de voir « Utilisateur introuvable » |
| Confirmation | Boîte de confirmation avant suppression |

---

## Dépendances

**Backend**
- PHP 7.4 ou supérieur (8.x recommandé)
- PDO (MySQL / MariaDB)
- Constantes globales : `BASE_URL`, `APP_NAME`, `USER_MODULE_URL`

**Frontend**
- Bootstrap 3 (classes `col-md-*`, `form-group`, `has-error`, `help-block`)
- Font Awesome 4
- jQuery
- Polices Google Fonts : Inter et Poppins

---

## Installation

1. Copier le dossier du module dans le projet, au même niveau que `includes/` et `assets/`.
2. Vérifier que `includes/auth.php` expose bien `$pdo`, `requireRole()` et `currentUser()`.
3. Vérifier que `includes/helpers.php` définit `e()`, `csrfField()`, `setFlash()`, `getFlash()`, `formatDateTime()` et `roleBadgeClass()`.
4. Définir les constantes `BASE_URL`, `APP_NAME` et `USER_MODULE_URL`.
5. Créer les tables (voir ci-dessous) et insérer les rôles `Admin` et `Patient`.
6. Créer un premier compte administrateur directement en base, avec un mot de passe haché (`password_hash()`).

### Structure de données attendue

D'après les champs utilisés par les pages :

**Table `roles`** : `id`, `nom`

**Table `utilisateurs`** : `id`, `prenom`, `nom`, `email` (unique), `telephone` (nullable), `password` (hash), `role_id`, `is_active`, `last_login` (nullable), `created_at`

> Les noms exacts des tables et colonnes sont à confirmer avec le modèle et le script SQL du projet.

---

## Parcours utilisateur

```
Visiteur ──► inscription.php ──► compte Patient créé ──► login.php
                                                            │
                          ┌─────────────────────────────────┤
                          ▼                                 ▼
                       Patient                            Admin
              (laboratoires, catalogue)                     │
                                       ┌───────────┬────────┼──────────────┐
                                       ▼           ▼        ▼              ▼
                                utilisateurs.php  roles.php  ajouter-    modifier-
                                       │                     utilisateur   utilisateur
                                       ▼
                              details-utilisateur.php
```

---

## Auteur

Module réalisé dans le cadre du projet **LabConnect**.
