<?php
$titrePage = "Refuser la demande #" . (int)$p['id'];
$menuActif = "demandes";
require __DIR__ . "/../partials/header_back.php";
?>
<h1>Refuser la demande #<?= (int)$p['id'] ?></h1>

<p>
    Patient <?= (int)$p['id_utilisateur'] ?> :
    <?= htmlspecialchars($p['date_prelevement']) ?> à <?= htmlspecialchars(substr($p['heure_prelevement'], 0, 5)) ?>,
    motif « <?= htmlspecialchars($p['motif']) ?> »
</p>

<form id="formRefus" class="formulaire" method="post" action="index.php?action=back_refuser&id=<?= (int)$p['id'] ?>" novalidate>

    <label for="motif_refus">Motif du refus</label>
    <input type="text" id="motif_refus" name="motif_refus" value="<?= htmlspecialchars($motifRefus) ?>">
    <span class="erreur" id="err_motif_refus"><?= $erreurs['motif_refus'] ?? '' ?></span>

    <div class="actions">
        <button type="submit" class="btn-danger">Confirmer le refus</button>
        <a class="btn btn-sec" href="index.php?action=back_detail&id=<?= (int)$p['id'] ?>">Annuler</a>
    </div>
</form>

<?php
$scripts = ["public/js/back_prelevement.js"];
require __DIR__ . "/../partials/footer_back.php";
?>