<?php
/**
 * Fiche d'un laboratoire — accessible aux deux rôles.
 * Les actions Modifier / Supprimer ne sont affichées qu'à l'Admin.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/controllers/LaboratoireController.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    setFlash('danger', 'Laboratoire introuvable.');
    header('Location: ' . BASE_URL . 'laboratoires.php');
    exit;
}

$controller = new LaboratoireController($pdo);
$data       = $controller->show((int) $id); // requireLogin() à l'intérieur
$lab        = $data['laboratoire'];
$analyses   = $data['analyses'];

$me        = currentUser() ?? [];
$isAdmin   = ($me['role'] ?? '') === 'Admin';
$isActive  = (int) $lab['actif'] === 1;
$pageTitle = $lab['nom'];
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<?php require __DIR__ . '/includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">
        <?= e($lab['nom']) ?>
        <span class="app-badge <?= $isActive ? 'active' : 'inactive' ?>">
          <?= $isActive ? 'Actif' : 'Inactif' ?>
        </span>
      </h1>
      <p class="app-page-sub">Fiche détaillée du laboratoire et de ses analyses.</p>
    </div>

    <div class="app-head-actions">
      <?php if ($isAdmin): ?>
        <a class="btn app-btn-primary" href="modifier-laboratoire.php?id=<?= (int) $lab['id'] ?>">
          <i class="fa fa-pencil" aria-hidden="true"></i> Modifier
        </a>
        <a class="btn app-btn-outline" href="supprimer-laboratoire.php?id=<?= (int) $lab['id'] ?>">
          <i class="fa fa-trash" aria-hidden="true"></i> Supprimer
        </a>
      <?php endif; ?>

      <a class="btn app-btn-outline" href="laboratoires.php">
        <i class="fa fa-arrow-left" aria-hidden="true"></i> Retour à la liste
      </a>
    </div>
  </div>

  <div class="app-card">
    <div class="app-detail-grid">
      <div class="app-detail-item">
        <span class="app-detail-label">Nom</span>
        <span class="app-detail-value"><?= e($lab['nom']) ?></span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Ville</span>
        <span class="app-detail-value"><?= e($lab['ville']) ?></span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Adresse</span>
        <span class="app-detail-value"><?= e($lab['adresse']) ?></span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Téléphone</span>
        <span class="app-detail-value"><?= e(formatPhone((string) $lab['telephone'])) ?></span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Adresse e-mail</span>
        <span class="app-detail-value"><?= e($lab['email']) ?></span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Horaires</span>
        <span class="app-detail-value">
          <?= $lab['horaires'] !== null && $lab['horaires'] !== ''
              ? e($lab['horaires'])
              : '—' ?>
        </span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Analyses proposées</span>
        <span class="app-detail-value">
          <span class="app-badge-count"><?= (int) $lab['nb_analyses'] ?></span>
        </span>
      </div>

      <div class="app-detail-item">
        <span class="app-detail-label">Enregistré le</span>
        <span class="app-detail-value"><?= e(formatDateTime($lab['created_at'])) ?></span>
      </div>
    </div>
  </div>

  <div class="app-card app-card-table">
    <div class="app-toolbar">
      <h2 class="app-page-title" style="font-size: 18px; margin: 0;">Analyses et prix</h2>
    </div>

    <div class="table-responsive">
      <table class="table app-table">
        <thead>
          <tr>
            <th scope="col">Analyse</th>
            <th scope="col" class="hide-sm">Catégorie</th>
            <th scope="col" class="text-right">Prix</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($analyses === []): ?>
            <tr>
              <td colspan="3" class="app-empty">
                <i class="fa fa-list-alt" aria-hidden="true"></i>
                Aucune analyse n'est encore associée à ce laboratoire.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($analyses as $analyse): ?>
              <tr>
                <td>
                  <span class="app-lab-name"><?= e($analyse['nom']) ?></span>
                  <?php if (!empty($analyse['description'])): ?>
                    <span class="app-lab-email"><?= e($analyse['description']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="hide-sm"><?= e($analyse['categorie']) ?></td>
                <td class="text-right"><?= e(formatPrice($analyse['prix'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p class="app-note">
      Les prix sont propres à chaque laboratoire. Un Patient les consulte en lecture seule.
    </p>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
