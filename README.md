# LabConnect - Plateforme de Gestion d'Analyses Médicales

Projet développé à l'**École Supérieure Privée d'Ingénierie et de Technologies (Esprit)** - TP Projet Technologies Web, 2ème année cycle ingénieur (2A10), année académique 2026-2027.

## Description

LabConnect est une plateforme web qui accompagne le patient avant, pendant et après une analyse médicale : recherche de laboratoire, réservation de rendez-vous, résultats en ligne avec suivi de santé, notifications et réclamations.

- **Objectif** : simplifier le parcours du patient et la gestion des laboratoires.
- **Problème résolu** : éviter les déplacements et l'attente inutiles en permettant de comparer les laboratoires, réserver un créneau et consulter ses résultats en ligne.

## Table des Matières

- [Description](#description)
- [Fonctionnalités](#fonctionnalités)
- [Stack Technique](#stack-technique)
- [Architecture](#architecture)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Contributions](#contributions)
- [Licence](#licence)

## Fonctionnalités

### Front Office (Espace Patient)

- Recherche de laboratoires et comparaison des prix
- Réservation de rendez-vous par créneau horaire (avec code QR)
- Consultation des résultats en ligne et suivi de l'évolution de la santé
- Gestion et suivi des réclamations
- Demandes de prélèvement à domicile

### Back Office (Espace Administration)

- Gestion des laboratoires et des analyses médicales
- Validation et gestion des créneaux horaires
- Suivi des rendez-vous des patients
- Gestion des notifications et des rappels automatiques par e-mail

## Stack Technique

### Frontend

- HTML, CSS, Bootstrap, Tailwind CSS, JavaScript

### Backend

- PHP (Architecture MVC)
- MySQL

## Architecture

Architecture MVC : `Controllers`, `Models`, `Views` (partials, layout, pages), avec une séparation claire entre le **Front Office** et le **Back Office**.

## Installation

1. Clonez le repository :

   ```bash
   git clone https://github.com/farah1300/Esprit-PW-2A10-2026-LabConnect.git
   cd Esprit-PW-2A10-2026-LabConnect
   ```

2. Si vous utilisez WAMP ou XAMPP :

   * Placez le projet dans le dossier `www` (WAMP) ou `htdocs` (XAMPP).
   * Démarrez **Apache** et **MySQL** depuis l'interface de WAMP/XAMPP.
   * Accédez au projet via [http://localhost/Esprit-PW-2A10-2026-LabConnect](http://localhost/Esprit-PW-2A10-2026-LabConnect).

## Utilisation

### Prérequis

Pour utiliser ce projet, vous devez installer **PHP** (inclus dans XAMPP et WAMP) :

1. Téléchargez XAMPP : [XAMPP](https://www.apachefriends.org/fr/index.html) ou WampServer : [WampServer](https://www.wampserver.com/).
2. Vérifiez l'installation de PHP dans un terminal :

   ```bash
   php -v
   ```

### Module Rendez-vous (maquette HTML/CSS)

Le dossier `labconnect-rdv/` contient la maquette du module **Gestion des rendez-vous**. Pour la consulter, ouvrez simplement `labconnect-rdv/index.html` dans un navigateur.

- `front/` : `rdv_start`, `login`, `register`, `rdv_create`, `rdv_mine`, `rdv_detail`
- `back/` : `rendezvous` (liste + filtre par statut), `rendezvous_form`, `rendezvous_detail`
- `public/` : `css`, `js`, `img`

Parcours patient : **inscription** → **prise de rendez-vous** (analyse + créneau + ordonnance optionnelle) → **Mes RDV** (statut *En attente* jusqu'à confirmation, QR code remis une fois confirmé, annulation possible).

## Contributions

Nous remercions tous ceux qui ont contribué à ce projet !

### Contributeurs

| Nom | Module |
|-----|--------|
| [Farah Ghanney](https://github.com/farah1300) | Rendez-vous |
| Meriam Filali | Laboratoires |
| Iyed Mhadhbi | Résultats |
| Amine Dorai | Réclamations |
| Ahmed Bnmakki | |
| Yessine Ben Taleb | Prélèvement à domicile |

### Comment contribuer ?

1. **Fork le projet** : allez sur la page GitHub du projet et cliquez sur le bouton **Fork** pour créer une copie dans votre compte.
2. **Clonez votre fork** :

   ```bash
   git clone https://github.com/votre-utilisateur/Esprit-PW-2A10-2026-LabConnect.git
   cd Esprit-PW-2A10-2026-LabConnect
   ```

3. **Créez une branche** : `git checkout -b ma-fonctionnalite`
4. **Validez vos modifications** : `git add .` puis `git commit -m "description"`
5. **Poussez** : `git push origin ma-fonctionnalite`, puis ouvrez une **pull request**.

## Licence

Projet académique réalisé à Esprit (2026-2027), à usage pédagogique uniquement. Aucune licence open source n'est appliquée pour le moment.
