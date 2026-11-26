<?php
$titrePage = "Demandes de prélèvement";
$menuActif = "demandes";
require __DIR__ . "/../partials/header_back.php";
?>
<h1>Demandes de prélèvement</h1>

<?php if ($message === 'refuse'): ?><p class="ok">Demande refusée.</p><?php endif; ?>
<?php if ($message === 'impossible'): ?>
    <p class="ko">Action impossible pour cette demande.</p>
<?php endif; ?>

<form id="formRecherche" class="recherche" method="get" action="index.php" novalidate>
    <input type="hidden" name="action" value="back_liste">

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
        <label for="patient">N° patient</label>
        <input type="text" id="patient" name="patient" placeholder="1"
               value="<?= htmlspecialchars($filtres['patient']) ?>">
        <span class="erreur" id="err_patient"><?= $erreursFiltre['patient'] ?? '' ?></span>
    </div>

    <div>
        <button type="submit">Rechercher</button>
        <a class="btn btn-sec" href="index.php?action=back_liste">Réinitialiser</a>
    </div>
</form>

<table>
    <tr>
        <th>#</th><th>Patient</th><th>Date</th><th>Heure</th><th>Motif</th>
        <th>Adresse</th><th>Statut</th><th>Actions</th>
    </tr>
    <?php if (empty($prelevements)): ?>
        <tr><td colspan="8">Aucune demande trouvée.</td></tr>
    <?php endif; ?>
    <?php foreach ($prelevements as $p): ?>
    <tr>
        <td><?= (int)$p['id'] ?></td>
        <td>Patient <?= (int)$p['id_utilisateur'] ?></td>
        <td><?= htmlspecialchars($p['date_prelevement']) ?></td>
        <td><?= htmlspecialchars(substr($p['heure_prelevement'], 0, 5)) ?></td>
        <td><?= htmlspecialchars($p['motif']) ?></td>
        <td><?= htmlspecialchars($p['rue'] . ', ' . $p['ville']) ?></td>
        <td><span class="badge badge-<?= htmlspecialchars($p['statut']) ?>"><?= htmlspecialchars(str_replace('_', ' ', $p['statut'])) ?></span></td>
        <td><a class="btn btn-sec" href="index.php?action=back_detail&id=<?= (int)$p['id'] ?>">Détails</a></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php
$scripts = ["public/js/recherche.js"];
require __DIR__ . "/../partials/footer_back.php";
?>