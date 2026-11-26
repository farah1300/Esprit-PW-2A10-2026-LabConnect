<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BackOffice - Refuser la demande</title>
    <link rel="stylesheet" href="public/css/prelevement.css">
</head>
<body>
<div class="conteneur">
    <h1>Refuser la demande #<?= (int)$p['id'] ?></h1>

    <p>
        Patient <?= (int)$p['id_utilisateur'] ?> :
        <?= htmlspecialchars($p['date_prelevement']) ?> à <?= htmlspecialchars(substr($p['heure_prelevement'], 0, 5)) ?>,
        motif « <?= htmlspecialchars($p['motif']) ?> »
    </p>

    <form id="formRefus" method="post" action="index.php?action=back_refuser&id=<?= (int)$p['id'] ?>" novalidate>

        <label for="motif_refus">Motif du refus</label>
        <input type="text" id="motif_refus" name="motif_refus" value="<?= htmlspecialchars($motifRefus) ?>">
        <span class="erreur" id="err_motif_refus"><?= $erreurs['motif_refus'] ?? '' ?></span>

        <button type="submit">Confirmer le refus</button>
        <a href="index.php?action=back_detail&id=<?= (int)$p['id'] ?>">Annuler</a>
    </form>
</div>
<script src="public/js/back_prelevement.js"></script>
</body>
</html>