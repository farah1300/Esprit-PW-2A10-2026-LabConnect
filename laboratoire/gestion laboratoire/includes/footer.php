<?php
/**
 * Pied de page commun.
 *
 * Il ferme la grille ouverte dans header.php, affiche un footer compact
 * (adapté à une application de gestion) puis charge les scripts.
 */
?>
    </div><!-- /.row -->
  </div><!-- /.container-fluid app-shell -->

  <footer class="app-footer">
    <div class="container-fluid">
      <div class="app-footer-inner">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> — Gestion des laboratoires d'analyses médicales</p>
        <p class="app-footer-links">
          <a href="<?= BASE_URL ?>index.php">Accueil</a>
          <span aria-hidden="true">·</span>
          <a href="<?= BASE_URL ?>laboratoires.php">Laboratoires</a>
          <span aria-hidden="true">·</span>
          <a href="<?= BASE_URL ?>catalogue.php">Catalogue</a>
        </p>
      </div>
    </div>
  </footer>

  <!-- jQuery est requis par Bootstrap 3 (fermeture des alertes, modales...) -->
  <script src="<?= BASE_URL ?>assets/js/jquery.js"></script>
  <script src="<?= BASE_URL ?>assets/js/bootstrap.min.js"></script>
  <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
