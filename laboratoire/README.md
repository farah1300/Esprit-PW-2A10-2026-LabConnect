# LabConnect — Gestion des laboratoires

Ce document décrit uniquement le module **Gestion des laboratoires** du projet
LabConnect (PHP / MySQL / PDO / MVC). L'authentification, les rôles, le CSRF et
la configuration viennent du module Gestion des utilisateurs.

Fonctionnalités :

1. Lister les laboratoires
2. Rechercher (nom, ville, adresse, e-mail) et filtrer par ville
3. Ajouter un laboratoire (modale)
4. Afficher la fiche d'un laboratoire
5. Modifier un laboratoire
6. Supprimer un laboratoire (avec confirmation)

---

## 1. Fichiers du module

```
gestion laboratoire/
├── controllers/
│   └── LaboratoireController.php   # index, create, show, edit, destroy
├── laboratoires.php                # liste + recherche + modale d'ajout
├── voir-laboratoire.php            # fiche
├── modifier-laboratoire.php        # modification (Admin)
├── supprimer-laboratoire.php       # confirmation + suppression (Admin)
├── laboratoires.html               # maquette statique (référence)
└── ajouter-laboratoire.html        # maquette statique (référence)
```

Il n'y a pas de page `ajouter-laboratoire.php` : l'ajout se fait dans une
**modale Bootstrap** de `laboratoires.php`.

---

## 2. Droits d'accès

| Page / action | Patient | Admin |
|---|:---:|:---:|
| Liste, recherche, filtre | oui | oui |
| Fiche (`voir-laboratoire.php`) | oui | oui |
| Ajouter | non | oui |
| Modifier | non (403) | oui |
| Supprimer | non (403) | oui |

Les boutons d'administration sont masqués pour le Patient, et le serveur
refuse aussi l'action si la requête est forgée (`requireRole('Admin')` en haut
de page puis dans le contrôleur).

---

## 3. Pages et méthodes du contrôleur

| # | Fonctionnalité | Page | Méthode |
|---|---|---|---|
| 1 | Lister / rechercher / filtrer | `laboratoires.php` | `index()` |
| 2 | Ajouter | modale de `laboratoires.php` (`action=create`) | `create()` |
| 3 | Fiche | `voir-laboratoire.php?id=` | `show()` |
| 4 | Modifier | `modifier-laboratoire.php?id=` | `edit()` |
| 5 | Supprimer | `supprimer-laboratoire.php?id=` | `destroy()` |

### Liste (`laboratoires.php`)
- Paramètres GET : `q` (recherche) et `ville` (filtre).
- Quatre statistiques : laboratoires, villes couvertes, analyses au catalogue,
  laboratoires actifs.
- Colonnes : laboratoire (nom + e-mail), ville, adresse, téléphone, nombre
  d'analyses, actions.
- Un laboratoire inactif porte un badge « Inactif ».
- Après une erreur dans la modale d'ajout, elle se rouvre automatiquement avec
  les valeurs saisies.

### Fiche (`voir-laboratoire.php`)
Nom, ville, adresse, téléphone, e-mail, horaires, nombre d'analyses proposées,
date d'enregistrement, statut, et la liste des analyses avec leurs prix
(lecture seule).

### Modification (`modifier-laboratoire.php`)
Même formulaire que l'ajout, pré-rempli. L'e-mail doit rester unique.

### Suppression (`supprimer-laboratoire.php`)
- **GET** : affiche une page de confirmation avec le nombre d'offres liées.
- **POST** : vérifie le rôle, la méthode et le jeton CSRF, puis supprime.
- Les offres du laboratoire sont supprimées en cascade ; le catalogue global des
  analyses n'est pas modifié.

---

## 4. Données (schéma attendu)

D'après les champs utilisés par les pages (noms exacts à confirmer avec le
modèle et le script SQL) :

| Colonne | Description |
|---|---|
| `id` | clé primaire |
| `nom` | nom du laboratoire |
| `ville` | ville |
| `adresse` | adresse |
| `telephone` | 8 chiffres |
| `email` | unique |
| `horaires` | facultatif |
| `actif` | 1 = actif, 0 = inactif |
| `created_at` | date d'enregistrement |

`nb_analyses` est calculé par la requête (nombre d'offres du laboratoire).

---

## 5. Validation

| Champ | Règle |
|---|---|
| Nom | obligatoire, 150 caractères max |
| Ville | obligatoire, 100 caractères max |
| Adresse | obligatoire, 255 caractères max |
| Téléphone | obligatoire, 8 chiffres (ex. `71234567`) |
| E-mail | obligatoire, valide, 190 caractères max, unique |
| Horaires | facultatif, 100 caractères max |
| Actif | case à cocher (cochée par défaut à l'ajout) |

La validation fait foi côté serveur. Les attributs HTML (`required`,
`maxlength`) ne sont qu'un confort.

---

## 6. Sécurité

- **Accès** : `requireRole('Admin')` avant la lecture de l'`id` (un Patient reçoit
  un 403) ; `requireLogin()` dans le contrôleur pour la liste et la fiche.
- **CSRF** : jeton sur tous les formulaires POST (ajout, modification, suppression).
- **Suppression** : jamais par simple lien. Le lien GET n'affiche qu'une
  confirmation ; la suppression se fait en POST + CSRF + `confirm()`.
- **Identifiant** : `filter_input(FILTER_VALIDATE_INT)` ; un `id` invalide
  redirige avec « Laboratoire introuvable ».
- **XSS** : toute sortie passe par `e()`.
- **SQL** : requêtes préparées ; jokers `LIKE` neutralisés dans la recherche.
- **Format** : `formatPhone()` pour l'affichage des téléphones.

---

## 7. Tests manuels

### Accès
- [ ] Sans connexion, `laboratoires.php` redirige vers la connexion.
- [ ] Patient : voit la liste et les fiches, sans boutons Ajouter / Modifier / Supprimer.
- [ ] Patient : `modifier-laboratoire.php?id=1` et `supprimer-laboratoire.php?id=1` sont refusés (403).

### Ajout
- [ ] Ajout valide : le laboratoire apparaît dans la liste.
- [ ] E-mail déjà utilisé : erreur, la modale se rouvre avec les valeurs saisies.
- [ ] Téléphone de 7 chiffres ou avec des lettres : erreur.
- [ ] Nom, ville ou adresse vides : erreur.

### Recherche et filtre
- [ ] Recherche par nom, ville, adresse et e-mail : résultats corrects.
- [ ] Filtre par ville, puis « Réinitialiser » : la liste complète revient.
- [ ] Recherche `%` : aucun résultat parasite.
- [ ] Aucun résultat : message « Aucun laboratoire ne correspond ».

### Fiche et modification
- [ ] `voir-laboratoire.php?id=999999` et `?id=abc` : « Laboratoire introuvable ».
- [ ] Modifier sans changer l'e-mail : pas d'erreur « déjà utilisé ».
- [ ] Décocher « actif » : badge « Inactif » dans la liste et sur la fiche.

### Suppression
- [ ] Ouvrir `supprimer-laboratoire.php?id=…` : rien n'est supprimé avant la confirmation.
- [ ] La confirmation indique le bon nombre d'offres liées.
- [ ] Après confirmation, le laboratoire disparaît de la liste.
- [ ] POST sans jeton CSRF : erreur 403.

---

## 8. Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| Page blanche | contrôleur ou modèle manquant | vérifier `controllers/LaboratoireController.php` et ses `require` |
| Liste des villes vide | aucun laboratoire en base | ajouter un laboratoire |
| « Table … doesn't exist » | script SQL non importé | importer les tables du module |
| Erreur de clé étrangère à la suppression | cascade absente | ajouter `ON DELETE CASCADE` sur la table des offres |
| La modale d'ajout ne s'ouvre pas | jQuery ou Bootstrap non chargés | vérifier `footer.php` et `assets/js` |

---

## 9. Limites connues

- `laboratoires.php` utilise un lien GET vers `supprimer-laboratoire.php` : il
  n'affiche qu'une confirmation, la suppression réelle reste en POST + CSRF.
- Les maquettes `.html` sont conservées pour référence et ne sont plus utilisées.
