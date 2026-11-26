# Module Prélèvement à domicile (LabConnect)

Module 6, responsable : Yessine Ben Taleb. PHP natif (architecture MVC), MySQL (PDO).

## Entités
- **Adresse** (rue, ville, code postal, gouvernorat, complément)
- **Prelevement** (adresse, date, heure, motif, statut, motif de refus)
- Relation : une adresse peut servir à plusieurs prélèvements (clé étrangère `id_adresse`).

## Fonctionnalités
FrontOffice (patient) : demander, afficher, consulter, modifier, annuler un prélèvement ;
ajouter, modifier, supprimer une adresse ; rechercher et suivre une demande.
BackOffice (responsable) : afficher et rechercher toutes les demandes, confirmer, refuser (avec motif),
faire avancer le statut (confirmé, en cours, effectué).

## Règles de gestion
- Une demande n'est modifiable ou annulable que si son statut est `en_attente`.
- Une adresse liée à un prélèvement actif ne peut pas être supprimée.
- La date d'un prélèvement doit être dans le futur.
- Un refus exige un motif.
- Le statut n'avance que dans l'ordre : `confirme`, `en_cours`, `effectue`.
- Contrôle de saisie en JavaScript et en PHP (aucun contrôle HTML5).

## Installation
1. Démarrer Apache et MySQL (XAMPP).
2. Importer `database/prelevement.sql` dans phpMyAdmin.
3. Ouvrir `http://localhost/Esprit-PW-2A10-2026-LabConnect/tache%20prelevement/labconnect-prelevement/`

## Pages
- Patient : `index.php?action=prelevement_liste`, `index.php?action=adresse_liste`
- BackOffice : `index.php?action=back_liste`

## Remarque
Le patient est fixé à `id_utilisateur = 1` en attendant le module utilisateurs.