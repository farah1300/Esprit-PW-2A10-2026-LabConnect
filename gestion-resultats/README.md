# LabConnect

- `front/` : site patient (accueil, laboratoires, prise de rendez-vous, conseils)
- `back/`  : espace admin (login + dashboard) + API + serveur

## Lancer

    cd back
    npm install
    npm start

- Patient : http://localhost:3000
- Admin   : http://localhost:3000/admin   (par défaut admin / admin123)

## Changer le mot de passe admin

macOS / Linux :

    ADMIN_USER=monuser ADMIN_PASSWORD=monmotdepasse SECRET=une-longue-chaine npm start

Windows PowerShell :

    $env:ADMIN_USER="monuser"; $env:ADMIN_PASSWORD="monmotdepasse"; $env:SECRET="une-longue-chaine"; npm start

Les rendez-vous sont stockés dans `back/data/appointments.json`.

> Sans `SECRET` défini, les sessions sont invalidées à chaque redémarrage du serveur (il faut se reconnecter).

## Personnaliser le template

Le contenu de démonstration a été remplacé par des placeholders entre crochets
(`[Titre de section]`, `[Texte de présentation]`, etc.). Pour adapter le site :

1. **Textes** : remplacez les placeholders dans `front/index.html` et `front/conseil.html`.
2. **Images** : remplacez les images de `front/images/`. Les images de démonstration
   sont signalées par des commentaires `TODO` dans le HTML et dans
   `front/css/tooplate-style.css` (slider).
3. **Couleurs et polices** : modifiez les variables `:root` en haut de
   `front/css/labconnect.css` (`--lc-violet`, `--lc-bleu`, `--lc-font-*`, etc.).
4. **Carte** : remplacez la requête de l'iframe Google Maps dans `front/index.html`.
5. **Espace admin** : les styles sont dans `back/admin/admin.css` et la logique
   dans `back/admin/common.js` et les scripts de chaque page (`resultats.js`, …).

### Organisation des fichiers

- `front/css/tooplate-style.css` : structure de base du template.
- `front/css/labconnect.css` : thème LabConnect (couleurs, composants, responsive).
- `front/js/layout.js` : **en-tête, menu et pied de page du site patient, définis une seule fois** (chaque page ne contient que `<div data-include="header|footer">`). C'est ici qu'on remplace `[Slogan]`, `[Téléphone]`, `[Horaires]`…
- `front/js/custom.js` : préloader, navigation, carrousel, smooth scroll et formulaire de rendez-vous.
- `back/server.js` : **serveur unique** (config, stockage JSON, auth, validation, PDF, modèles, contrôleur, routes, pages protégées de l'espace admin), organisé en sections.
  Le formulaire public de rendez-vous y reste (section « Routes : rendez-vous »), sans gestion dans le back office.
- `back/admin/common.js` : outils communs du back office, dont la barre du haut (`renderTopBar`).


---

# Module 3 – Responsable des résultats

Le module relie **le site patient (front)** et **l'espace médecin / admin (back)** par le **même stockage**
(`back/data/*.json`) : ce que le personnel saisit au back office est exactement ce que le patient voit au front.

## Pages

| Fonction demandée | Back office (personnel) | Front office (patient) |
|---|---|---|
| `resultats` | `/admin/resultats.html` (liste, stats, filtres, recherche) | `/resultats.html` (connexion + mes résultats + recherche) |
| `inscription` | – | `/inscription.html` (création du compte patient, **séparée** de la page résultats) |
| `ajouter-resultat` | `/admin/ajouter-resultat.html` | – |
| `modifier-resultat` | `/admin/modifier-resultat.html?id=` | – |
| `details-resultat` | `/admin/details-resultat.html?id=` | `/details-resultat.html?id=` |
| `telecharger-resultat` | bouton « Télécharger en PDF » (page détails) | `/telecharger-resultat.html?id=` (paiement puis PDF) |

> Le projet LabConnect fourni est en **Node/Express + stockage JSON** : les pages `.php` du cahier des charges sont
> donc réalisées en `.html` + JavaScript (front) et Node (serveur). La structure MVC est conservée à l'identique.

## Architecture MVC

```
back/
  package.json · package-lock.json · node_modules/
  server.js     tout le serveur, en sections dans cet ordre :
                config · http · store (JSON) · validate · auth (sessions, scrypt) · pdf
                modèles (Analyse, Medecin, Patient, DocumentResultat, Paiement, Resultat)
                ResultatController (reçoit la requête, applique les droits, répond)
                routes (comptes, résultats, rendez-vous public : URL -> contrôleur + rôle exigé)
  data/         fichiers JSON + documents/
  admin/        vues back office (HTML + common.js, resultats.js, resultat-form.js, details-resultat.js)
front/          vues patient (resultats.html, inscription.html, details-resultat.html, telecharger-resultat.html + js/resultats.js)
```

Entités : **Resultat** (`data/resultats.json`) et **medecin** (`data/medecins.json`), plus Patient, Analyse,
Paiement et DocumentResultat. Les fichiers JSON sont créés au premier lancement (avec des données de départ).

## Les 12 fonctionnalités

1. **Ajouter** un résultat : `ajouter-resultat.html` · 2. **Afficher** : `resultats.html` (back et front)
3. **Détails** : `details-resultat.html` · 4. **Modifier** : `modifier-resultat.html` (une modification annule la validation)
5. **Supprimer** : impossible si publié · 6. **Associer à un patient** (liste + création rapide d'un patient)
7. **Associer à une analyse** (catalogue ; paramètres et valeurs de référence pré-remplis ; indicateur bas / normal / élevé calculé)
8. **Valider** (brouillon → validé) · 9. **Publier** (validé → publié, « dépublier » possible)
10. **Paiement en ligne** : **simulation** (aucune banque) – carte de test `4242 4242 4242 4242`, `4000 0000 0000 0002` = refusée.
   Seuls les 4 derniers chiffres sont conservés.
11. **PDF** : compte rendu généré par le serveur (section PDF de `server.js`), téléchargeable après paiement (patient) ou directement (personnel)
12. **Recherche** : par nom, e-mail, référence, analyse, statut ou patient (back) ; par analyse / référence (front)

## Comptes de démonstration (à changer)

| Rôle | Identifiant | Mot de passe | Droits |
|---|---|---|---|
| admin | `admin` | `admin123` | tout |
| médecin | `dr.bensalah` | `medecin123` | saisir, modifier, **valider, publier, supprimer** |
| technicien | `tech.amine` | `technicien123` | saisir et modifier **seulement** |
| patient | `sarra.benali@example.com` | `patient123` | ses résultats publiés uniquement |

Variables d'environnement : `ADMIN_USER`, `ADMIN_PASSWORD`, `MEDECIN_PASSWORD`, `TECH_PASSWORD`, `PATIENT_PASSWORD`
(appliquées à la création des comptes, c'est-à-dire au premier lancement), `SECRET`, `PORT`.
Un patient peut aussi créer son compte sur `/inscription.html` (lien depuis la page de connexion de `/resultats.html`) ; si le laboratoire a déjà créé son dossier, il l'active avec le même e-mail.

## Sécurité et validation

- **Fichiers téléchargés** (section `DocumentResultat` de `server.js`) : PDF / PNG / JPG uniquement, 5 Mo max, extension + type MIME + signature binaire
  vérifiés, nom aléatoire, stockage hors dossier public (`back/data/documents/`), nom d'origine jamais utilisé comme chemin.
- **Rôles** : middleware `requireRole` côté serveur (le masquage des boutons n'est qu'un confort). Seuls admin et médecin valident / publient / suppriment.
- **Un patient ne consulte que ses résultats** : filtrés par identifiant de session ; un résultat d'autrui répond « introuvable » (404).
  Les résultats non publiés restent invisibles pour le patient.
- **Sessions** : cookies signés HMAC, `HttpOnly`, `SameSite=Strict`, deux espaces séparés (`lc_admin`, `lc_patient`) ; mots de passe hachés (scrypt) ;
  limitation des tentatives de connexion ; pages du back office protégées côté serveur.
- **Validation des saisies** : côté serveur (section `validate` de `server.js`, fait foi) **et** côté client (mêmes règles dans `admin/resultat-form.js`
  et `front/js/resultats.js`) pour tous les formulaires : résultat, patient, inscription, connexion, paiement, fichier.
- **Aucune validation HTML5** (exigence du cours) : pas de `required`, `maxlength`, `pattern`, `min/max` ni `type="email"` ; tous les formulaires sont en `novalidate` et contrôlés en JavaScript (rendez-vous : `custom.js`, connexion admin : `login.html`, patient / paiement : `js/resultats.js`, résultat : `admin/resultat-form.js`).
- Sorties HTML échappées (anti-XSS), en-têtes `nosniff` / `X-Frame-Options`.

## Données partagées

| Fichier | Contenu |
|---|---|
| `resultats.json` | résultats (statut, lignes, validé par, dates) |
| `patients.json` · `medecins.json` · `analyses.json` | référentiels |
| `documents.json` + `documents/` | documents joints |
| `paiements.json` | paiements (réf., montant, 4 derniers chiffres) |
| `appointments.json` | rendez-vous (module existant, même stockage JSON, section `store` de `server.js`) |

## Git / GitHub

`.gitignore` exclut `node_modules/` et les données (`back/data/*.json`, `back/data/documents/`) car elles contiennent des
mots de passe hachés et des données de santé. Conseil : une branche par fonctionnalité, des commits réguliers, une *pull request*
par membre, et le tableau GitHub Project pour répartir les tâches.

## Usage de l'IA générative (à compléter par chaque membre)

Ce module a été développé avec l'aide d'un assistant d'IA. Chaque membre doit indiquer ici, honnêtement : les parties
générées par l'IA, ce qu'il a relu / corrigé / adapté, et ce qu'il peut expliquer lors de la validation.


## Correspondance avec la grille de validation

| Critère | Où le montrer |
|---|---|
| 1.1 CRUD | `ajouter-resultat` (Create) · `resultats` / `details-resultat` (Read) · `modifier-resultat` (Update) · bouton Supprimer (Delete) |
| 1.2 Rôles | `server.js` (routes résultats) : admin / médecin / technicien ; patient limité à ses résultats publiés |
| 1.3 Sécurité & sessions | `server.js` (section `auth`) : scrypt + sel, cookies signés HttpOnly/SameSite, anti brute-force, routes protégées côté serveur |
| 1.4 Compréhension | Flux : vue → routes → `ResultatController` → modèles → `store` (tout dans `server.js`) |
| 2.1 Validation des saisies | `server.js` section `validate` (serveur) + `admin/resultat-form.js`, `js/resultats.js` (client) |
| 2.2 Intégration / UX | Parcours complet : saisie → validation → publication → paiement → PDF |
| 3.x Git / board / README | Branches par fonctionnalité, tableau GitHub Project, ce README |
| 4.1 IA générative | Section ci-dessus, à compléter honnêtement par chaque membre |

## Limites connues

- Le cahier des charges parle de pages `.php` ; ce projet est en Node/Express (voir plus haut). Les noms de pages et la structure MVC sont conservés.
- L'en-tête et le pied de page du front contiennent encore les placeholders du template (`[Slogan]`, `[Téléphone]`, …) : à remplacer **une seule fois**, dans `front/js/layout.js`.
- L'en-tête et le pied de page sont injectés par JavaScript : sans JavaScript, ils n'apparaissent pas (le site patient en a de toute façon besoin).
