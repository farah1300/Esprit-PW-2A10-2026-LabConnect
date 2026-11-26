<?php
/**
 * Catalogue des analyses.
 *
 * Patient : lecture seule, recherche par nom et filtre de prix.
 * Admin   : mêmes filtres + actions de gestion (voir/modifier le laboratoire,
 *           retirer une offre). Le tri est toujours le prix croissant.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/controllers/CatalogueController.php';

$controller = new CatalogueController($pdo);

// --- Retrait d'une offre (POST + CSRF) -------------------------------
$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $action === 'deleteOffer') {
    $controller->deleteOffer(
        isset($_POST['laboratoire_id']) ? (int) $_POST['laboratoire_id'] : 0,
        isset($_POST['analyse_id']) ? (int) $_POST['analyse_id'] : 0
    );
}

// --- Données de la page ----------------------------------------------
$data    = $controller->index(); // requireLogin() à l'intérieur
$offres  = $data['offres'];
$filters = $data['filters'];
$errors  = $data['errors'];

$me        = currentUser() ?? [];
$isAdmin   = ($me['role'] ?? '') === 'Admin';
$pageTitle = 'Catalogue des analyses';
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<?php require __DIR__ . '/includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">Catalogue des analyses</h1>
      <p class="app-page-sub">
        Les analyses sont triées par prix croissant. Filtrez par nom et par fourchette de prix.
      </p>
    </div>

    <?php if ($isAdmin): ?>
      <div class="app-head-actions">
        <a class="btn app-btn-primary" href="validation.php">
          <i class="fa fa-plus" aria-hidden="true"></i> Ajouter / modifier une offre
        </a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">
      <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
      <?php foreach ($errors as $error): ?>
        <div><?= e($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="app-card app-card-table">
    <form method="get" action="catalogue.php" class="app-toolbar" role="search">
      <div class="app-search">
        <i class="fa fa-search" aria-hidden="true"></i>
        <input type="search" class="form-control" name="q"
               value="<?= e($filters['q']) ?>"
               placeholder="Rechercher une analyse par son nom…"
               aria-label="Rechercher une analyse">
      </div>

      <input type="number" class="form-control app-filter" name="min"
             value="<?= e($filters['min']) ?>"
             min="0" step="0.001" style="width: 130px;"
             placeholder="Prix min" aria-label="Prix minimum">

      <input type="number" class="form-control app-filter" name="max"
             value="<?= e($filters['max']) ?>"
             min="0" step="0.001" style="width: 130px;"
             placeholder="Prix max" aria-label="Prix maximum">

      <button type="submit" class="btn app-btn-primary">
        <i class="fa fa-filter" aria-hidden="true"></i> Filtrer
      </button>

      <a class="btn app-btn-outline" href="catalogue.php">
        <i class="fa fa-refresh" aria-hidden="true"></i> Réinitialiser
      </a>
    </form>

    <div class="table-responsive">
      <table class="table app-table">
        <thead>
          <tr>
            <th scope="col">Analyse</th>
            <th scope="col" class="hide-sm">Catégorie</th>
            <th scope="col">Laboratoire</th>
            <th scope="col">Ville</th>
            <th scope="col" class="text-right">Prix</th>
            <th scope="col" class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($offres === []): ?>
            <tr>
              <td colspan="6" class="app-empty">
                <i class="fa fa-search" aria-hidden="true"></i>
                Aucune analyse ne correspond aux filtres sélectionnés.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($offres as $offre): ?>
              <?php
                $labId = (int) $offre['laboratoire_id'];
                $analyseId = (int) $offre['analyse_id'];
              ?>
              <tr>
                <td>
                  <span class="app-lab-name"><?= e($offre['analyse_nom']) ?></span>
                  <?php if ((int) $offre['laboratoire_actif'] !== 1): ?>
                    <span class="app-badge inactive">Laboratoire inactif</span>
                  <?php endif; ?>
                </td>
                <td class="hide-sm"><?= e($offre['categorie']) ?></td>
                <td><?= e($offre['laboratoire_nom']) ?></td>
                <td><?= e($offre['ville']) ?></td>
                <td class="text-right"><?= e(formatPrice($offre['prix'])) ?></td>
                <td class="text-right">
                  <div class="app-actions">
                    <a class="app-action" href="voir-laboratoire.php?id=<?= $labId ?>"
                       title="Voir le laboratoire"
                       aria-label="Voir le laboratoire <?= e($offre['laboratoire_nom']) ?>">
                      <i class="fa fa-eye" aria-hidden="true"></i>
                    </a>

                    <?php if ($isAdmin): ?>
                      <a class="app-action" href="modifier-laboratoire.php?id=<?= $labId ?>"
                         title="Modifier le laboratoire"
                         aria-label="Modifier le laboratoire <?= e($offre['laboratoire_nom']) ?>">
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                      </a>

                      <form method="post" action="catalogue.php"
                            onsubmit="return confirm('Retirer cette analyse du catalogue de ce laboratoire ?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="deleteOffer">
                        <input type="hidden" name="laboratoire_id" value="<?= $labId ?>">
                        <input type="hidden" name="analyse_id" value="<?= $analyseId ?>">
                        <button type="submit" class="app-action danger"
                                title="Retirer cette offre"
                                aria-label="Retirer <?= e($offre['analyse_nom']) ?> du laboratoire <?= e($offre['laboratoire_nom']) ?>">
                          <i class="fa fa-trash" aria-hidden="true"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p class="app-note">
      <?= count($offres) ?> offre<?= count($offres) > 1 ? 's' : '' ?> affichée<?= count($offres) > 1 ? 's' : '' ?>.
      <?= $isAdmin
          ? 'Les actions de gestion sont réservées à l’administrateur.'
          : 'Consultation en lecture seule.' ?>
    </p>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
