<?php
/**
 * Laboratoires — liste réelle, recherche serveur et filtre par ville.
 *
 * Lecture : Admin et Patient.
 * Ajout : Admin uniquement, via une modale Bootstrap (formulaire POST +
 * CSRF). La liste, les villes et les statistiques viennent toutes de MySQL.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/controllers/LaboratoireController.php';

$controller = new LaboratoireController($pdo);

// --- Ajout d'un laboratoire (modale) ---------------------------------
$createResult = ['errors' => [], 'old' => []];
$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $action === 'create') {
    $createResult = $controller->create(); // requireRole('Admin') à l'intérieur
}

// --- Données de la page ----------------------------------------------
$data         = $controller->index(); // requireLogin() à l'intérieur
$laboratoires = $data['laboratoires'];
$cities       = $data['cities'];
$stats        = $data['stats'];
$search       = $data['search'];
$city         = $data['city'];

$me        = currentUser() ?? [];
$isAdmin   = ($me['role'] ?? '') === 'Admin';
$errors    = $createResult['errors'];
$old       = $createResult['old'];
$oldActif  = array_key_exists('actif', $old) ? (int) $old['actif'] : 1;
$pageTitle = 'Laboratoires';
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<?php require __DIR__ . '/includes/sidebar.php'; ?>

<main class="col-md-9 app-content" id="contenu">
  <div class="app-page-head">
    <div>
      <h1 class="app-page-title">Laboratoires</h1>
      <p class="app-page-sub">
        <?= $isAdmin
            ? 'Gérez les laboratoires partenaires, leurs coordonnées et leurs analyses.'
            : 'Recherchez un laboratoire partenaire par nom, ville, adresse ou e-mail.' ?>
      </p>
    </div>

    <?php if ($isAdmin): ?>
      <div class="app-head-actions">
        <button type="button" class="btn app-btn-primary"
                data-toggle="modal" data-target="#addLabModal">
          <i class="fa fa-plus" aria-hidden="true"></i> Ajouter un laboratoire
        </button>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">
      <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
      Le formulaire d'ajout contient des erreurs. Corrigez les champs indiqués.
    </div>
  <?php endif; ?>

  <!-- Statistiques réelles (une requête SQL agrégée) -->
  <div class="app-stats">
    <div class="app-stat">
      <small>Laboratoires</small>
      <strong><?= (int) $stats['labs'] ?></strong>
    </div>
    <div class="app-stat blue">
      <small>Villes couvertes</small>
      <strong><?= (int) $stats['cities'] ?></strong>
    </div>
    <div class="app-stat green">
      <small>Analyses au catalogue</small>
      <strong><?= (int) $stats['analyses'] ?></strong>
    </div>
    <div class="app-stat gold">
      <small>Laboratoires actifs</small>
      <strong><?= (int) $stats['active_labs'] ?></strong>
    </div>
  </div>

  <!-- Liste filtrée côté serveur -->
  <div class="app-card app-card-table">
    <form method="get" action="laboratoires.php" class="app-toolbar" role="search">
      <div class="app-search">
        <i class="fa fa-search" aria-hidden="true"></i>
        <input type="search" class="form-control" name="q"
               value="<?= e($search) ?>"
               placeholder="Rechercher (nom, ville, adresse, e-mail)…"
               aria-label="Rechercher un laboratoire">
      </div>

      <select class="form-control app-filter" name="ville" aria-label="Filtrer par ville">
        <option value="">Toutes les villes</option>
        <?php foreach ($cities as $cityOption): ?>
          <option value="<?= e($cityOption) ?>" <?= $city === $cityOption ? 'selected' : '' ?>>
            <?= e($cityOption) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="btn app-btn-primary">
        <i class="fa fa-filter" aria-hidden="true"></i> Filtrer
      </button>

      <?php if ($search !== '' || $city !== ''): ?>
        <a class="btn app-btn-outline" href="laboratoires.php">
          <i class="fa fa-refresh" aria-hidden="true"></i> Réinitialiser
        </a>
      <?php endif; ?>
    </form>

    <div class="table-responsive">
      <table class="table app-table">
        <thead>
          <tr>
            <th scope="col">Laboratoire</th>
            <th scope="col">Ville</th>
            <th scope="col" class="hide-sm">Adresse</th>
            <th scope="col">Téléphone</th>
            <th scope="col" class="hide-sm">Analyses</th>
            <th scope="col" class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($laboratoires === []): ?>
            <tr>
              <td colspan="6" class="app-empty">
                <i class="fa fa-search" aria-hidden="true"></i>
                <?= $search !== '' || $city !== ''
                    ? 'Aucun laboratoire ne correspond à votre recherche.'
                    : 'Aucun laboratoire enregistré pour le moment.' ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($laboratoires as $lab): ?>
              <?php
                $id = (int) $lab['id'];
                $isActive = (int) $lab['actif'] === 1;
              ?>
              <tr>
                <td>
                  <span class="app-lab-name">
                    <?= e($lab['nom']) ?>
                    <?php if (!$isActive): ?>
                      <span class="app-badge inactive">Inactif</span>
                    <?php endif; ?>
                  </span>
                  <span class="app-lab-email"><?= e($lab['email']) ?></span>
                </td>
                <td><?= e($lab['ville']) ?></td>
                <td class="hide-sm"><?= e($lab['adresse']) ?></td>
                <td class="app-lab-phone"><?= e(formatPhone((string) $lab['telephone'])) ?></td>
                <td class="hide-sm">
                  <span class="app-badge-count"><?= (int) $lab['nb_analyses'] ?></span>
                </td>
                <td class="text-right">
                  <div class="app-actions">
                    <a class="app-action" href="voir-laboratoire.php?id=<?= $id ?>"
                       title="Voir la fiche"
                       aria-label="Voir la fiche de <?= e($lab['nom']) ?>">
                      <i class="fa fa-eye" aria-hidden="true"></i>
                    </a>

                    <?php if ($isAdmin): ?>
                      <a class="app-action" href="modifier-laboratoire.php?id=<?= $id ?>"
                         title="Modifier"
                         aria-label="Modifier <?= e($lab['nom']) ?>">
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                      </a>

                      <a class="app-action danger" href="supprimer-laboratoire.php?id=<?= $id ?>"
                         title="Supprimer"
                         aria-label="Supprimer <?= e($lab['nom']) ?>">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                      </a>
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
      <?= count($laboratoires) ?> laboratoire<?= count($laboratoires) > 1 ? 's' : '' ?> affiché<?= count($laboratoires) > 1 ? 's' : '' ?>.
      Recherche et filtre sont exécutés côté serveur avec des requêtes préparées.
    </p>
  </div>
</main>

<?php if ($isAdmin): ?>
  <!-- Modale d'ajout : formulaire POST + CSRF, validation serveur -->
  <div class="modal fade" id="addLabModal" tabindex="-1" role="dialog"
       aria-labelledby="addLabModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="post" action="laboratoires.php" class="app-form">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="create">

          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
              <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" id="addLabModalLabel">Ajouter un laboratoire</h4>
          </div>

          <div class="modal-body">
            <div class="form-group<?= isset($errors['nom']) ? ' has-error' : '' ?>">
              <label class="control-label" for="add_nom">Nom</label>
              <input type="text" class="form-control" id="add_nom" name="nom"
                     value="<?= e($old['nom'] ?? '') ?>"
                     maxlength="150" required>
              <span class="help-block"><?= e($errors['nom'] ?? '') ?></span>
            </div>

            <div class="row">
              <div class="col-sm-6">
                <div class="form-group<?= isset($errors['ville']) ? ' has-error' : '' ?>">
                  <label class="control-label" for="add_ville">Ville</label>
                  <input type="text" class="form-control" id="add_ville" name="ville"
                         value="<?= e($old['ville'] ?? '') ?>"
                         maxlength="100" required>
                  <span class="help-block"><?= e($errors['ville'] ?? '') ?></span>
                </div>
              </div>

              <div class="col-sm-6">
                <div class="form-group<?= isset($errors['telephone']) ? ' has-error' : '' ?>">
                  <label class="control-label" for="add_telephone">Téléphone</label>
                  <input type="tel" class="form-control" id="add_telephone" name="telephone"
                         value="<?= e($old['telephone'] ?? '') ?>"
                         inputmode="numeric" maxlength="8" placeholder="Ex. 71234567" required>
                  <span class="help-block"><?= e($errors['telephone'] ?? '') ?></span>
                </div>
              </div>
            </div>

            <div class="form-group<?= isset($errors['adresse']) ? ' has-error' : '' ?>">
              <label class="control-label" for="add_adresse">Adresse</label>
              <input type="text" class="form-control" id="add_adresse" name="adresse"
                     value="<?= e($old['adresse'] ?? '') ?>"
                     maxlength="255" required>
              <span class="help-block"><?= e($errors['adresse'] ?? '') ?></span>
            </div>

            <div class="form-group<?= isset($errors['email']) ? ' has-error' : '' ?>">
              <label class="control-label" for="add_email">Adresse e-mail</label>
              <input type="email" class="form-control" id="add_email" name="email"
                     value="<?= e($old['email'] ?? '') ?>"
                     maxlength="190" required>
              <span class="help-block"><?= e($errors['email'] ?? '') ?></span>
            </div>

            <div class="form-group<?= isset($errors['horaires']) ? ' has-error' : '' ?>">
              <label class="control-label" for="add_horaires">
                Horaires <span class="text-muted">(facultatif)</span>
              </label>
              <input type="text" class="form-control" id="add_horaires" name="horaires"
                     value="<?= e($old['horaires'] ?? '') ?>"
                     maxlength="100" placeholder="Ex. Lun–Sam : 07h30–18h00">
              <span class="help-block"><?= e($errors['horaires'] ?? '') ?></span>
            </div>

            <div class="checkbox">
              <label>
                <input type="checkbox" name="actif" value="1" <?= $oldActif === 1 ? 'checked' : '' ?>>
                Laboratoire actif
              </label>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn app-btn-outline" data-dismiss="modal">Annuler</button>
            <button type="submit" class="btn app-btn-primary">
              <i class="fa fa-plus" aria-hidden="true"></i> Ajouter
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($isAdmin && $errors !== []): ?>
  <script>
    // Réouvre la modale après une erreur de validation serveur.
    document.addEventListener('DOMContentLoaded', function () {
      if (window.jQuery) {
        window.jQuery('#addLabModal').modal('show');
      }
    });
  </script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
