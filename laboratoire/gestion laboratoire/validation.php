<?php
/**
 * Validation — Admin uniquement.
 *
 * Associe une analyse à un laboratoire avec un prix, ou met à jour le prix
 * d'une offre existante. C'est la page cible du raccourci "Validation".
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/controllers/ValidationController.php';

$controller = new ValidationController($pdo);

$saveResult = ['errors' => [], 'old' => []];
$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if ($action === 'save') {
        $saveResult = $controller->save(); // requireRole('Admin') à l'intérieur
    } elseif ($action === 'delete') {
        $controller->destroy(); // redirige après suppression
    }
}

$data    = $controller->index(); // requireRole('Admin') à l'intérieur
$labs    = $data['laboratoires'];
$analyses = $data['analyses'];
$offres  = $data['offres'];

$errors = $saveResult['errors'];
$old    = $saveResult['old'];

// --- Laboratoire tout juste ajouté ----------------------------------
// Redirection depuis laboratoires.php :
//   validation.php?laboratoire_id=12&nouveau=1
// Les paramètres sont ignorés silencieusement si l'identifiant ne
// correspond à aucun laboratoire de la liste.
$newLabId = filter_input(INPUT_GET, 'laboratoire_id', FILTER_VALIDATE_INT);
$nouveau  = isset($_GET['nouveau'])
    && is_string($_GET['nouveau'])
    && $_GET['nouveau'] === '1';

$newLab = null;

if (is_int($newLabId) && $newLabId > 0) {
    foreach ($labs as $lab) {
        if ((int) $lab['id'] === $newLabId) {
            $newLab = $lab;
            break;
        }
    }
}

if ($newLab === null) {
    $newLabId = 0;
    $nouveau = false;
}

// En cas de formulaire resoumis avec des erreurs, la valeur saisie garde
// la priorité sur le laboratoire passé dans l'URL.
$selectedLabId = (int) ($old['laboratoire_id'] ?? $newLabId);

$pageTitle = 'Validation';
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<?php require __DIR__ . '/includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">Validation</h1>
      <p class="app-page-sub">
        Associez une analyse à un laboratoire avec son prix. Une offre déjà
        existante pour ce couple sera simplement mise à jour.
      </p>
    </div>

    <div class="app-head-actions">
      <a class="btn app-btn-outline" href="catalogue.php">
        <i class="fa fa-list-alt" aria-hidden="true"></i> Voir le catalogue
      </a>
    </div>
  </div>

  <?php if ($nouveau && $newLab !== null): ?>
    <div class="alert alert-success" role="alert">
      <i class="fa fa-check-circle" aria-hidden="true"></i>
      <?= e($newLab['nom']) ?> a été ajouté. Définissez maintenant le prix de ses analyses.
    </div>
  <?php endif; ?>

  <div class="app-card">
    <h2>Nouvelle association / mise à jour</h2>

    <?php if ($errors !== []): ?>
      <div class="alert alert-danger" role="alert">
        <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
        Corrigez les champs indiqués ci-dessous.
      </div>
    <?php endif; ?>

    <form method="post" action="validation.php" class="app-form">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save">

      <div class="row">
        <div class="col-sm-5">
          <div class="form-group<?= isset($errors['laboratoire_id']) ? ' has-error' : '' ?>">
            <label class="control-label" for="laboratoire_id">Laboratoire</label>
            <select class="form-control" id="laboratoire_id" name="laboratoire_id" required>
              <option value="">— Choisir un laboratoire —</option>
              <?php foreach ($labs as $lab): ?>
                <option value="<?= (int) $lab['id'] ?>"
                  <?= $selectedLabId === (int) $lab['id'] ? 'selected' : '' ?>>
                  <?= e($lab['nom']) ?> — <?= e($lab['ville']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="help-block"><?= e($errors['laboratoire_id'] ?? '') ?></span>
          </div>
        </div>

        <div class="col-sm-4">
          <div class="form-group<?= isset($errors['analyse_id']) ? ' has-error' : '' ?>">
            <label class="control-label" for="analyse_id">Analyse</label>
            <select class="form-control" id="analyse_id" name="analyse_id" required>
              <option value="">— Choisir une analyse —</option>
              <?php foreach ($analyses as $analyse): ?>
                <option value="<?= (int) $analyse['id'] ?>"
                  <?= (int) ($old['analyse_id'] ?? 0) === (int) $analyse['id'] ? 'selected' : '' ?>>
                  <?= e($analyse['nom']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="help-block"><?= e($errors['analyse_id'] ?? '') ?></span>
          </div>
        </div>

        <div class="col-sm-3">
          <div class="form-group<?= isset($errors['prix']) ? ' has-error' : '' ?>">
            <label class="control-label" for="prix">Prix (TND)</label>
            <input type="number" class="form-control" id="prix" name="prix"
                   value="<?= e($old['prix'] ?? '') ?>"
                   min="0.001" step="0.001" placeholder="Ex. 15.500" required>
            <span class="help-block"><?= e($errors['prix'] ?? '') ?></span>
          </div>
        </div>
      </div>

      <div class="app-form-actions">
        <button type="submit" class="btn app-btn-primary">
          <i class="fa fa-check" aria-hidden="true"></i> Enregistrer l'offre
        </button>
      </div>
    </form>
  </div>

  <!-- État des prix par laboratoire : les laboratoires sans offre sont
       affichés eux aussi, pour ne pas les oublier. -->
  <div class="app-card app-card-table">
    <div class="app-toolbar">
      <h2 class="app-page-title" style="font-size: 18px; margin: 0;">Laboratoires</h2>
      <span class="app-badge-count"
            aria-label="<?= count($labs) ?> laboratoire<?= count($labs) > 1 ? 's' : '' ?>">
        <?= count($labs) ?>
      </span>
    </div>

    <div class="table-responsive">
      <table class="table app-table">
        <thead>
          <tr>
            <th scope="col">Laboratoire</th>
            <th scope="col">Ville</th>
            <th scope="col">Offres</th>
            <th scope="col">Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($labs === []): ?>
            <tr>
              <td colspan="4" class="app-empty">
                <i class="fa fa-flask" aria-hidden="true"></i>
                Aucun laboratoire enregistré pour le moment.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($labs as $lab): ?>
              <?php
                $labId    = (int) $lab['id'];
                $nbOffres = (int) ($lab['nb_offres'] ?? 0);
                $isNewLab = $nouveau && $labId === (int) $newLabId;
              ?>
              <tr<?= $isNewLab ? ' class="success"' : '' ?>>
                <td>
                  <span class="app-lab-name">
                    <?= e($lab['nom']) ?>
                    <?php if ($isNewLab): ?>
                      <span class="app-badge admin">Nouveau</span>
                    <?php endif; ?>
                  </span>
                </td>
                <td><?= e($lab['ville']) ?></td>
                <td>
                  <span class="app-badge-count"><?= $nbOffres ?></span>
                </td>
                <td>
                  <?php if ($nbOffres === 0): ?>
                    <span class="app-badge inactive">Sans prix</span>
                  <?php else: ?>
                    <span class="app-badge active">Prix définis</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="app-card app-card-table">
    <div class="app-toolbar">
      <h2 class="app-page-title" style="font-size: 18px; margin: 0;">Offres existantes</h2>
    </div>

    <div class="table-responsive">
      <table class="table app-table">
        <thead>
          <tr>
            <th scope="col">Laboratoire</th>
            <th scope="col">Ville</th>
            <th scope="col">Analyse</th>
            <th scope="col" class="hide-sm">Catégorie</th>
            <th scope="col" class="text-right">Prix</th>
            <th scope="col" class="text-right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($offres === []): ?>
            <tr>
              <td colspan="6" class="app-empty">
                <i class="fa fa-list-alt" aria-hidden="true"></i>
                Aucune offre enregistrée pour le moment.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($offres as $offre): ?>
              <?php
                $labId = (int) $offre['laboratoire_id'];
                $analyseId = (int) $offre['analyse_id'];
              ?>
              <tr>
                <td><?= e($offre['laboratoire_nom']) ?></td>
                <td><?= e($offre['ville']) ?></td>
                <td><span class="app-lab-name"><?= e($offre['analyse_nom']) ?></span></td>
                <td class="hide-sm"><?= e($offre['categorie']) ?></td>
                <td class="text-right"><?= e(formatPrice($offre['prix'])) ?></td>
                <td class="text-right">
                  <div class="app-actions">
                    <form method="post" action="validation.php"
                          onsubmit="return confirm('Retirer cette offre du catalogue ?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="laboratoire_id" value="<?= $labId ?>">
                      <input type="hidden" name="analyse_id" value="<?= $analyseId ?>">
                      <button type="submit" class="app-action danger"
                              title="Retirer l'offre"
                              aria-label="Retirer <?= e($offre['analyse_nom']) ?> du laboratoire <?= e($offre['laboratoire_nom']) ?>">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p class="app-note">
      <?= count($offres) ?> offre<?= count($offres) > 1 ? 's' : '' ?>.
      Le prix est stocké en DECIMAL(10,3), adapté aux millimes tunisiens.
    </p>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
