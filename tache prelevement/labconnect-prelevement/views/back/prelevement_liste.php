<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BackOffice - Demandes de prélèvement</title>
    <link rel="stylesheet" href="public/css/prelevement.css">
</head>
<body>
<div class="conteneur">
    <h1>BackOffice : demandes de prélèvement</h1>

    <?php if ($message === 'refuse'): ?><p class="ok">Demande refusée.</p><?php endif; ?>
    <?php if ($message === 'impossible'): ?>
        <p class="ko">Action impossible pour cette demande.</p>
    <?php endif; ?>

    <table>
        <tr>
            <th>#</th><th>Patient</th><th>Date</th><th>Heure</th><th>Motif</th>
            <th>Adresse</th><th>Statut</th><th>Actions</th>
        </tr>
        <?php if (empty($prelevements)): ?>
            <tr><td colspan="8">Aucune demande enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($prelevements as $p): ?>
        <tr>
            <td><?= (int)$p['id'] ?></td>
            <td>Patient <?= (int)$p['id_utilisateur'] ?></td>
            <td><?= htmlspecialchars($p['date_prelevement']) ?></td>
            <td><?= htmlspecialchars(substr($p['heure_prelevement'], 0, 5)) ?></td>
            <td><?= htmlspecialchars($p['motif']) ?></td>
            <td><?= htmlspecialchars($p['rue'] . ', ' . $p['ville']) ?></td>
            <td><?= htmlspecialchars(str_replace('_', ' ', $p['statut'])) ?></td>
            <td>
                <a href="index.php?action=back_detail&id=<?= (int)$p['id'] ?>">Détails</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>