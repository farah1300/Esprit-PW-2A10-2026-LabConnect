<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes demandes de prélèvement</title>
    <link rel="stylesheet" href="public/css/prelevement.css">
</head>
<body>
<div class="conteneur">
    <h1>Mes demandes de prélèvement</h1>

    <?php if ($message === 'ajoute'): ?><p class="ok">Demande envoyée.</p><?php endif; ?>
    <?php if ($message === 'modifie'): ?><p class="ok">Demande modifiée.</p><?php endif; ?>
    <?php if ($message === 'annule'): ?><p class="ok">Demande annulée.</p><?php endif; ?>
    <?php if ($message === 'non_modifiable'): ?>
        <p class="ko">Modification impossible : la demande n'est plus en attente.</p>
    <?php endif; ?>
    <?php if ($message === 'non_annulable'): ?>
        <p class="ko">Annulation impossible : la demande n'est plus en attente.</p>
    <?php endif; ?>

    <p>
        <a class="btn" href="index.php?action=prelevement_ajouter">+ Demander un prélèvement</a>
        <a href="index.php?action=adresse_liste">Mes adresses</a>
    </p>

    <table>
        <tr>
            <th>Date</th><th>Heure</th><th>Motif</th><th>Adresse</th><th>Statut</th><th>Actions</th>
        </tr>
        <?php if (empty($prelevements)): ?>
            <tr><td colspan="6">Aucune demande enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($prelevements as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['date_prelevement']) ?></td>
            <td><?= htmlspecialchars(substr($p['heure_prelevement'], 0, 5)) ?></td>
            <td><?= htmlspecialchars($p['motif']) ?></td>
            <td><?= htmlspecialchars($p['rue'] . ', ' . $p['ville']) ?></td>
            <td><?= htmlspecialchars(str_replace('_', ' ', $p['statut'])) ?></td>
            <td>
                <a href="index.php?action=prelevement_detail&id=<?= $p['id'] ?>">Détails</a>
                <?php if ($p['statut'] === 'en_attente'): ?>
                    <a href="index.php?action=prelevement_modifier&id=<?= $p['id'] ?>">Modifier</a>
                    <form method="post" action="index.php?action=prelevement_annuler&id=<?= $p['id'] ?>"
                          style="display:inline" onsubmit="return confirm('Annuler cette demande ?');">
                        <button type="submit">Annuler</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>