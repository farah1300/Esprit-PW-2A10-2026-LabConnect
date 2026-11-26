<?php
$titrePage = $titre;
$menuActif = "prelevements";
require __DIR__ . "/../partials/header_front.php";
?>
<h1><?= htmlspecialchars($titre) ?></h1>

<?php if (empty($adresses)): ?>
    <p class="ko">Vous n'avez aucune adresse. Ajoutez d'abord une adresse pour demander un prélèvement.</p>
    <p><a class="btn" href="index.php?action=adresse_ajouter">+ Ajouter une adresse</a></p>
<?php else: ?>

<form id="formPrelevement" class="formulaire" method="post" action="<?= $formAction ?>" novalidate>

    <label for="id_adresse">Adresse</label>
    <select id="id_adresse" name="id_adresse">
        <option value="">-- Choisir une adresse --</option>
        <?php foreach ($adresses as $a): ?>
            <option value="<?= $a['id'] ?>" <?= ((int)$data['id_adresse'] === (int)$a['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($a['rue'] . ', ' . $a['ville']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <span class="erreur" id="err_id_adresse"><?= $erreurs['id_adresse'] ?? '' ?></span>

    <label for="date_prelevement">Date (AAAA-MM-JJ)</label>
    <input type="text" id="date_prelevement" name="date_prelevement" placeholder="2026-12-15"
           value="<?= htmlspecialchars($data['date_prelevement']) ?>">
    <span class="erreur" id="err_date_prelevement"><?= $erreurs['date_prelevement'] ?? '' ?></span>

    <label for="heure_prelevement">Heure (HH:MM)</label>
    <input type="text" id="heure_prelevement" name="heure_prelevement" placeholder="08:30"
           value="<?= htmlspecialchars($data['heure_prelevement']) ?>">
    <span class="erreur" id="err_heure_prelevement"><?= $erreurs['heure_prelevement'] ?? '' ?></span>

    <label for="motif">Motif (type d'analyse)</label>
    <input type="text" id="motif" name="motif" placeholder="Bilan sanguin"
           value="<?= htmlspecialchars($data['motif']) ?>">
    <span class="erreur" id="err_motif"><?= $erreurs['motif'] ?? '' ?></span>

    <div class="actions">
        <button type="submit">Enregistrer</button>
        <a class="btn btn-sec" href="index.php?action=prelevement_liste">Annuler</a>
    </div>
</form>

<?php endif; ?>

<?php
$scripts = ["public/js/prelevement.js"];
require __DIR__ . "/../partials/footer_front.php";
?>