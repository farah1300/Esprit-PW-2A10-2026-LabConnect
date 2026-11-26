<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de la demande</title>
    <link rel="stylesheet" href="public/css/prelevement.css">
</head>
<body>
<div class="conteneur">
    <h1>Détails de la demande #<?= (int)$p['id'] ?></h1>

    <table>
        <tr><th>Statut</th><td><?= htmlspecialchars(str_replace('_', ' ', $p['statut'])) ?></td></tr>
        <tr><th>Date du prélèvement</th><td><?= htmlspecialchars($p['date_prelevement']) ?></td></tr>
        <tr><th>Heure</th><td><?= htmlspecialchars(substr($p['heure_prelevement'], 0, 5)) ?></td></tr>
        <tr><th>Motif</th><td><?= htmlspecialchars($p['motif']) ?></td></tr>
        <tr>
            <th>Adresse</th>
            <td>
                <?= htmlspecialchars($p['rue']) ?><br>
                <?= htmlspecialchars($p['code_postal'] . ' ' . $p['ville']) ?>, <?= htmlspecialchars($p['gouvernorat']) ?>
                <?php if (!empty($p['complement'])): ?>
                    <br><?= htmlspecialchars($p['complement']) ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>Demande faite le</th><td><?= htmlspecialchars($p['date_demande']) ?></td></tr>
        <?php if ($p['statut'] === 'refuse' && !empty($p['motif_refus'])): ?>
            <tr><th>Motif du refus</th><td class="ko"><?= htmlspecialchars($p['motif_refus']) ?></td></tr>
        <?php endif; ?>
    </table>

    <p>
        <a href="index.php?action=prelevement_liste">Retour à la liste</a>
        <?php if ($p['statut'] === 'en_attente'): ?>
            <a href="index.php?action=prelevement_modifier&id=<?= (int)$p['id'] ?>">Modifier</a>
        <?php endif; ?>
    </p>
</div>
</body>
</html>