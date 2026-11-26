<?php
/**
 * Modification d'un laboratoire — Admin uniquement.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/controllers/LaboratoireController.php';

// Rôle vérifié avant même la validation de l'identifiant : un Patient qui
// tape l'URL reçoit un 403, pas une redirection ambiguë.
requireRole('Admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    setFlash('danger', 'Laboratoire introuvable.');
    header('Location: ' . BASE_URL . 'laboratoires.php');
    exit;
}

$controller = new LaboratoireController($pdo);
$data       = $controller->edit((int) $id); // requireRole('Admin') à l'intérieur

$lab    = $data['laboratoire'];
$errors = $data['errors'];
$old    = $data['old'];
$actif  = array_key_exists('actif', $old) ? (int) $old['actif'] : (int) $lab['actif'];

$pageTitle = 'Modifier un laboratoire';
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<?php require __DIR__ . '/includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">Modifier <?= e($lab['nom']) ?></h1>
      <p class="app-page-sub">
        Mettez à jour les coordonnées du laboratoire. L'adresse e-mail doit rester unique.
      </p>
    </div>

    <div class="app-head-actions">
      <a class="btn app-btn-outline" href="voir-laboratoire.php?id=<?= (int) $lab['id'] ?>">
        <i class="fa fa-eye" aria-hidden="true"></i> Voir la fiche
      </a>
      <a class="btn app-btn-outline" href="laboratoires.php">
        <i class="fa fa-arrow-left" aria-hidden="true"></i> Retour
      </a>
    </div>
  </div>

  <div class="app-card">
    <form method="post" action="modifier-laboratoire.php?id=<?= (int) $lab['id'] ?>" class="app-form">
      <?= csrfField() ?>

      <div class="form-group<?= isset($errors['nom']) ? ' has-error' : '' ?>">
        <label class="control-label" for="nom">Nom</label>
        <input type="text" class="form-control" id="nom" name="nom"
               value="<?= e($old['nom'] ?? '') ?>"
               maxlength="150" required>
        <span class="help-block"><?= e($errors['nom'] ?? '') ?></span>
      </div>

      <div class="row">
        <div class="col-sm-6">
          <div class="form-group<?= isset($errors['ville']) ? ' has-error' : '' ?>">
            <label class="control-label" for="ville">Ville</label>
            <input type="text" class="form-control" id="ville" name="ville"
                   value="<?= e($old['ville'] ?? '') ?>"
                   maxlength="100" required>
            <span class="help-block"><?= e($errors['ville'] ?? '') ?></span>
          </div>
        </div>

        <div class="col-sm-6">
          <div class="form-group<?= isset($errors['telephone']) ? ' has-error' : '' ?>">
            <label class="control-label" for="telephone">Téléphone</label>
            <input type="tel" class="form-control" id="telephone" name="telephone"
                   value="<?= e($old['telephone'] ?? '') ?>"
                   inputmode="numeric" maxlength="8" placeholder="Ex. 71234567" required>
            <span class="help-block"><?= e($errors['telephone'] ?? '') ?></span>
          </div>
        </div>
      </div>

      <div class="form-group<?= isset($errors['adresse']) ? ' has-error' : '' ?>">
        <label class="control-label" for="adresse">Adresse</label>
        <input type="text" class="form-control" id="adresse" name="adresse"
               value="<?= e($old['adresse'] ?? '') ?>"
               maxlength="255" required>
        <span class="help-block"><?= e($errors['adresse'] ?? '') ?></span>
      </div>

      <div class="form-group<?= isset($errors['email']) ? ' has-error' : '' ?>">
        <label class="control-label" for="email">Adresse e-mail</label>
        <input type="email" class="form-control" id="email" name="email"
               value="<?= e($old['email'] ?? '') ?>"
               maxlength="190" required>
        <span class="help-block"><?= e($errors['email'] ?? '') ?></span>
      </div>

      <div class="form-group<?= isset($errors['horaires']) ? ' has-error' : '' ?>">
        <label class="control-label" for="horaires">
          Horaires <span class="text-muted">(facultatif)</span>
        </label>
        <input type="text" class="form-control" id="horaires" name="horaires"
               value="<?= e($old['horaires'] ?? '') ?>"
               maxlength="100" placeholder="Ex. Lun–Sam : 07h30–18h00">
        <span class="help-block"><?= e($errors['horaires'] ?? '') ?></span>
      </div>

      <div class="form-group<?= isset($errors['actif']) ? ' has-error' : '' ?>">
        <div class="checkbox">
          <label>
            <input type="checkbox" name="actif" value="1" <?= $actif === 1 ? 'checked' : '' ?>>
            Laboratoire actif
          </label>
        </div>
        <span class="help-block"><?= e($errors['actif'] ?? '') ?></span>
      </div>

      <div class="app-form-actions">
        <button type="submit" class="btn app-btn-primary">
          <i class="fa fa-save" aria-hidden="true"></i> Enregistrer les modifications
        </button>
        <a class="btn app-btn-outline" href="voir-laboratoire.php?id=<?= (int) $lab['id'] ?>">Annuler</a>
      </div>
    </form>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
