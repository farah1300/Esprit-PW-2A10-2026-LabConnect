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
   dans `back/admin/admin.js`.

### Organisation des fichiers

- `front/css/tooplate-style.css` : structure de base du template.
- `front/css/labconnect.css` : thème LabConnect (couleurs, composants, responsive).
- `front/js/custom.js` : préloader, navigation, carrousel, smooth scroll et formulaire.
- `back/server.js` : API Express et protection de l'espace admin.
