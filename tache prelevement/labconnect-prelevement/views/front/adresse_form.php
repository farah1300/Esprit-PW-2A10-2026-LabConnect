<?php
$titrePage = $titre;
$menuActif = "adresses";
require __DIR__ . "/../partials/header_front.php";
?>
<h1><?= htmlspecialchars($titre) ?></h1>

<form id="formAdresse" class="formulaire" method="post" action="<?= $formAction ?>" novalidate>

    <label for="rue">Rue</label>
    <input type="text" id="rue" name="rue" value="<?= htmlspecialchars($data['rue']) ?>">
    <span class="erreur" id="err_rue"><?= $erreurs['rue'] ?? '' ?></span>

    <label for="ville">Ville</label>
    <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($data['ville']) ?>">
    <span class="erreur" id="err_ville"><?= $erreurs['ville'] ?? '' ?></span>

    <label for="code_postal">Code postal</label>
    <input type="text" id="code_postal" name="code_postal" value="<?= htmlspecialchars($data['code_postal']) ?>">
    <span class="erreur" id="err_code_postal"><?= $erreurs['code_postal'] ?? '' ?></span>

    <label for="gouvernorat">Gouvernorat</label>
    <select id="gouvernorat" name="gouvernorat">
        <option value="">-- Choisir --</option>
        <?php foreach ($gouvernorats as $g): ?>
            <option value="<?= $g ?>" <?= ($data['gouvernorat'] === $g) ? 'selected' : '' ?>><?= $g ?></option>
        <?php endforeach; ?>
    </select>
    <span class="erreur" id="err_gouvernorat"><?= $erreurs['gouvernorat'] ?? '' ?></span>

    <label for="complement">Complément (facultatif)</label>
    <input type="text" id="complement" name="complement" value="<?= htmlspecialchars($data['complement'] ?? '') ?>">
    <span class="erreur" id="err_complement"><?= $erreurs['complement'] ?? '' ?></span>

    <div class="actions">
        <button type="submit">Enregistrer</button>
        <a class="btn btn-sec" href="index.php?action=adresse_liste">Annuler</a>
    </div>
</form>

<?php
$scripts = ["public/js/adresse.js"];
require __DIR__ . "/../partials/footer_front.php";
?>