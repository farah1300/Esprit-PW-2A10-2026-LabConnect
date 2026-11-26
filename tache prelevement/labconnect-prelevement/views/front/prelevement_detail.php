<?php
// Chronologie du suivi
$etapes = [
    'en_attente' => 'En attente',
    'confirme'   => 'Confirmée',
    'en_cours'   => 'En cours',
    'effectue'   => 'Effectuée',
];
$ordre = array_keys($etapes);
$pos = array_search($p['statut'], $ordre);
$termine = ($p['statut'] === 'refuse' || $p['statut'] === 'annule');

$titrePage = "Suivi de la demande #" . (int)$p['id'];
$menuActif = "prelevements";
require __DIR__ . "/../partials/header_front.php";
?>
<h1>Suivi de la demande #<?= (int)$p['id'] ?></h1>

<ol class="suivi">
    <?php foreach ($ordre as $i => $cle): ?>
        <?php
        $classe = '';
        if ($termine) {
            $classe = ($i === 0) ? 'fait' : '';
        } elseif ($pos !== false && $i < $pos) {
            $classe = 'fait';
        } elseif ($pos !== false && $i === $pos) {
            $classe = 'actuel';
        }
        ?>
        <li class="<?= $classe ?>"><?= $etapes[$cle] ?></li>
    <?php endforeach; ?>
    <?php if ($termine): ?>
        <li class="actuel ko-etape"><?= $p['statut'] === 'refuse' ? 'Refusée' : 'Annulée' ?></li>
    <?php endif; ?>
</ol>

<table>
    <tr><th>Statut</th><td><span class="badge badge-<?= htmlspecialchars($p['statut']) ?>"><?= htmlspecialchars(str_replace('_', ' ', $p['statut'])) ?></span></td></tr>
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

<div class="actions">
    <a class="btn btn-sec" href="index.php?action=prelevement_liste">Retour à la liste</a>
    <?php if ($p['statut'] === 'en_attente'): ?>
        <a class="btn" href="index.php?action=prelevement_modifier&id=<?= (int)$p['id'] ?>">Modifier</a>
    <?php endif; ?>
</div>

<?php require __DIR__ . "/../partials/footer_front.php"; ?>