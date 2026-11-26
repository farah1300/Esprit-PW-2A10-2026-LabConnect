<?php
$titrePage = "Mes adresses";
$menuActif = "adresses";
require __DIR__ . "/../partials/header_front.php";
?>
<h1>Mes adresses</h1>

<?php if ($message === 'ajoute'): ?><p class="ok">Adresse ajoutée.</p><?php endif; ?>
<?php if ($message === 'modifie'): ?><p class="ok">Adresse modifiée.</p><?php endif; ?>
<?php if ($message === 'supprime'): ?><p class="ok">Adresse supprimée.</p><?php endif; ?>
<?php if ($message === 'utilisee'): ?>
    <p class="ko">Suppression impossible : cette adresse est liée à un prélèvement actif.</p>
<?php endif; ?>

<p><a class="btn" href="index.php?action=adresse_ajouter">+ Ajouter une adresse</a></p>

<table>
    <tr>
        <th>Rue</th><th>Ville</th><th>Code postal</th><th>Gouvernorat</th><th>Complément</th><th>Actions</th>
    </tr>
    <?php if (empty($adresses)): ?>
        <tr><td colspan="6">Aucune adresse enregistrée.</td></tr>
    <?php endif; ?>
    <?php foreach ($adresses as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a['rue']) ?></td>
        <td><?= htmlspecialchars($a['ville']) ?></td>
        <td><?= htmlspecialchars($a['code_postal']) ?></td>
        <td><?= htmlspecialchars($a['gouvernorat']) ?></td>
        <td><?= htmlspecialchars($a['complement'] ?? '') ?></td>
        <td>
            <a class="btn btn-sec" href="index.php?action=adresse_modifier&id=<?= $a['id'] ?>">Modifier</a>
            <form method="post" action="index.php?action=adresse_supprimer&id=<?= $a['id'] ?>"
                  style="display:inline" onsubmit="return confirm('Supprimer cette adresse ?');">
                <button type="submit" class="btn-danger">Supprimer</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require __DIR__ . "/../partials/footer_front.php"; ?>