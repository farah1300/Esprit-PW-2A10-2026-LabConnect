/* ============================================================
   LabConnect – interactions de l'espace connecté
   ------------------------------------------------------------
   Ce fichier ne gère QUE l'interface (menu mobile).
   Il ne remplace jamais la sécurité côté PHP : les contrôles
   d'accès et la validation réelle sont dans auth.php et dans
   les contrôleurs.
   ============================================================ */
(function () {
  'use strict';

  var toggle = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('appSidebar');

  if (!toggle || !sidebar) {
    return;
  }

  function setOpen(open) {
    sidebar.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  // Ouvre / ferme le menu latéral sur mobile.
  toggle.addEventListener('click', function () {
    setOpen(!sidebar.classList.contains('open'));
  });

  // Referme le menu après le choix d'un lien (uniquement sur mobile).
  sidebar.addEventListener('click', function (event) {
    if (event.target.closest('a') && window.matchMedia('(max-width: 991px)').matches) {
      setOpen(false);
    }
  });
})();
