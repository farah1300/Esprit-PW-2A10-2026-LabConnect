<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BackOffice - Détails de la demande</title>
    <link rel="stylesheet" href="public/css/prelevement.css">
</head>
<body>
<div class="conteneur">
    <h1>Demande #<?= (int)$p['id'] ?></h1>

    <?php if ($message === 'confirme'): ?><p class="ok">Demande confirmée.</p><?php endif; ?>
    <?php if ($message === 'avance'): ?><p class="ok">Statut mis à jour.</p><?php endif; ?>
    <?php if ($message === 'impossible'): ?>
        <p class="ko">Action impossible pour cette demande.</p>
    <?php endif; ?>

    <table>
        <tr><th>Patient</th><td>Patient <?= (int)$p['id_utilisateur'] ?></td></tr>
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
        <a href="index.php?action=back_liste">Retour à la liste</a>

        <?php if ($p['statut'] === 'en_attente'): ?>
            <form method="post" action="index.php?action=back_confirmer&id=<?= (int)$p['id'] ?>"
                  style="display:inline" onsubmit="return confirm('Confirmer cette demande ?');">
                <button type="submit">Confirmer</button>
            </form>
            <a class="btn" href="index.php?action=back_refuser&id=<?= (int)$p['id'] ?>">Refuser</a>
        <?php endif; ?>

        <?php if ($suivant !== null): ?>
            <form method="post" action="index.php?action=back_avancer&id=<?= (int)$p['id'] ?>"
                  style="display:inline"
                  onsubmit="return confirm('Passer le statut à : <?= htmlspecialchars(str_replace('_', ' ', $suivant)) ?> ?');">
                <button type="submit">Passer à : <?= htmlspecialchars(str_replace('_', ' ', $suivant)) ?></button>
            </form>
        <?php endif; ?>
    </p>
</div>
</body>
</html>