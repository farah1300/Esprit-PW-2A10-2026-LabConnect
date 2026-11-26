<?php
/**
 * Confirmation de suppression d'un laboratoire — Admin uniquement.
 *
 * GET  : affiche la confirmation.
 * POST : vérifie le CSRF puis supprime réellement.
 * Un lien GET ne supprime donc jamais de données.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/controllers/LaboratoireController.php';

requireRole('Admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    setFlash('danger', 'Laboratoire introuvable.');
    header('Location: ' . BASE_URL . 'laboratoires.php');
    exit;
}

$controller = new LaboratoireController($pdo);

// POST : le contrôleur vérifie à nouveau le rôle, la méthode et le CSRF.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $controller->destroy((int) $id);
}

$data      = $controller->show((int) $id);
$lab       = $data['laboratoire'];
$nbAnalyses = (int) $lab['nb_analyses'];
$pageTitle = 'Supprimer un laboratoire';
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<?php require __DIR__ . '/includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">Supprimer un laboratoire</h1>
      <p class="app-page-sub">Cette action est définitive et ne peut pas être annulée.</p>
    </div>

    <div class="app-head-actions">
      <a class="btn app-btn-outline" href="voir-laboratoire.php?id=<?= (int) $lab['id'] ?>">
        <i class="fa fa-arrow-left" aria-hidden="true"></i> Annuler
      </a>
    </div>
  </div>

  <div class="app-card">
    <h2><?= e($lab['nom']) ?></h2>
    <p class="app-page-sub">
      <?= e($lab['adresse']) ?>, <?= e($lab['ville']) ?> —
      <?= e(formatPhone((string) $lab['telephone'])) ?>
    </p>

    <div class="alert alert-warning" role="alert">
      <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
      La suppression retire aussi
      <strong><?= $nbAnalyses ?></strong>
      offre<?= $nbAnalyses > 1 ? 's' : '' ?> d'analyse liée<?= $nbAnalyses > 1 ? 's' : '' ?>
      (cascade prévue par le schéma). Le catalogue global des analyses n'est pas modifié.
    </div>

    <form method="post" action="supprimer-laboratoire.php?id=<?= (int) $lab['id'] ?>"
          onsubmit="return confirm('Supprimer définitivement ce laboratoire ?');">
      <?= csrfField() ?>

      <div class="app-form-actions">
        <button type="submit" class="btn app-btn-primary">
          <i class="fa fa-trash" aria-hidden="true"></i> Confirmer la suppression
        </button>
        <a class="btn app-btn-outline" href="laboratoires.php">Annuler</a>
      </div>
    </form>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
