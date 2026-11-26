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

    <form id="formRecherche" class="recherche" method="get" action="index.php" novalidate>
        <input type="hidden" name="action" value="prelevement_liste">

        <div>
            <label for="statut">Statut</label>
            <select id="statut" name="statut">
                <option value="">Tous</option>
                <?php foreach ($statuts as $s): ?>
                    <option value="<?= $s ?>" <?= ($filtres['statut'] === $s) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(str_replace('_', ' ', $s)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="erreur" id="err_statut"><?= $erreursFiltre['statut'] ?? '' ?></span>
        </div>

        <div>
            <label for="date">Date (AAAA-MM-JJ)</label>
            <input type="text" id="date" name="date" placeholder="2026-12-15"
                   value="<?= htmlspecialchars($filtres['date']) ?>">
            <span class="erreur" id="err_date"><?= $erreursFiltre['date'] ?? '' ?></span>
        </div>

        <div>
            <label for="motif">Motif (mot-clé)</label>
            <input type="text" id="motif" name="motif" placeholder="Bilan"
                   value="<?= htmlspecialchars($filtres['motif']) ?>">
            <span class="erreur" id="err_motif"><?= $erreursFiltre['motif'] ?? '' ?></span>
        </div>

        <div>
            <button type="submit">Rechercher</button>
            <a href="index.php?action=prelevement_liste">Réinitialiser</a>
        </div>
    </form>

    <table>
        <tr>
            <th>Date</th><th>Heure</th><th>Motif</th><th>Adresse</th><th>Statut</th><th>Actions</th>
        </tr>
        <?php if (empty($prelevements)): ?>
            <tr><td colspan="6">Aucune demande trouvée.</td></tr>
        <?php endif; ?>
        <?php foreach ($prelevements as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['date_prelevement']) ?></td>
            <td><?= htmlspecialchars(substr($p['heure_prelevement'], 0, 5)) ?></td>
            <td><?= htmlspecialchars($p['motif']) ?></td>
            <td><?= htmlspecialchars($p['rue'] . ', ' . $p['ville']) ?></td>
            <td><?= htmlspecialchars(str_replace('_', ' ', $p['statut'])) ?></td>
            <td>
                <a href="index.php?action=prelevement_detail&id=<?= $p['id'] ?>">Suivre / Détails</a>
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
<script src="public/js/recherche.js"></script>
</body>
</html>