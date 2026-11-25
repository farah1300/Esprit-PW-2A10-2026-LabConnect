// Confirmation avant suppression
document.querySelectorAll('[data-confirm]').forEach(el =>
  el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); }));

// Recherche rapide dans un tableau
document.querySelectorAll('[data-table-filter]').forEach(input =>
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    document.querySelectorAll(input.dataset.tableFilter + ' tbody tr')
      .forEach(tr => tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none');
  }));

// Filtre par statut (boutons Tous / En attente / Confirmé ...)
document.querySelectorAll('[data-filter-statut]').forEach(btn =>
  btn.addEventListener('click', () => {
    const q = btn.dataset.filterStatut.toLowerCase();
    document.querySelectorAll('[data-filter-statut][data-target="' + btn.dataset.target + '"]').forEach(b => {
      b.classList.toggle('btn-primary', b === btn);
      b.classList.toggle('btn-outline-primary', b !== btn);
    });
    document.querySelectorAll(btn.dataset.target + ' tbody tr')
      .forEach(tr => tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none');
  }));

// Nom de l'utilisateur affiché dans la barre de navigation (espace patient)
(function () {
  const params = new URLSearchParams(location.search);
  const nom = (params.get('nom') || '').trim();
  const prenom = (params.get('prenom') || '').trim();
  try {
    // Après l'inscription : on mémorise le nom saisi
    if (nom || prenom) {
      const email = (params.get('email') || '').trim().toLowerCase();
      // Nouveau compte (email différent) : on repart avec une liste de RDV vide
      if (email !== localStorage.getItem('labconnect_user_email')) localStorage.removeItem('labconnect_mes_rdv');
      localStorage.setItem('labconnect_user_email', email);
      localStorage.setItem('labconnect_user_nom', (nom + ' ' + prenom).trim());
    }
    const fullName = localStorage.getItem('labconnect_user_nom');
    if (!fullName) return;
    const cap = fullName.replace(/(^|\s)\S/g, c => c.toUpperCase());
    document.querySelectorAll('.navbar-front span.d-flex').forEach(span => {
      const last = span.lastChild;
      if (last && last.nodeType === Node.TEXT_NODE) last.textContent = ' ' + cap;
    });
  } catch (e) { /* localStorage indisponible : on garde le nom par défaut */ }
})();

// Prise de rendez-vous : on enregistre le RDV puis on l'affiche dans "Mes RDV"
(function () {
  const KEY = 'labconnect_mes_rdv';
  const load = () => { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; } };
  const save = list => { try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {} };
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  // 1) Page "Prendre un RDV" : à la validation du formulaire
  const form = document.querySelector('form select[name="id_analyse"]');
  if (form) {
    const f = form.closest('form');
    f.addEventListener('submit', e => {
      e.preventDefault();
      const analyse = f.querySelector('[name="id_analyse"]');
      const creneau = f.querySelector('[name="id_creneau"]');
      const [date, horaire] = creneau.options[creneau.selectedIndex].text.replace(/\s*\(.*\)\s*$/, '').split(' · ');
      const list = load();
      const id = list.reduce((m, r) => Math.max(m, r.id), 105) + 1;
      list.push({
        id: id,
        analyse: analyse.options[analyse.selectedIndex].text.split(' — ')[0],
        date: date,
        horaire: horaire,
        statut: 'En attente'
      });
      save(list);
      location.href = 'rdv_mine.html';
    });
  }

  // 2) Page "Mes RDV" : on ajoute les RDV enregistrés dans le tableau
  const tbody = document.querySelector('#mineTable tbody');
  if (tbody) {
    // Un compte a été créé : on retire les RDV d'exemple (ils n'appartiennent pas à ce patient)
    try { if (localStorage.getItem('labconnect_user_nom')) tbody.innerHTML = ''; } catch (e) {}
    load().forEach(r => {
      const tr = document.createElement('tr');
      tr.dataset.rdvId = r.id;
      tr.innerHTML = '<td>' + r.id + '</td><td>' + esc(r.analyse) + '</td><td>' + esc(r.date) + '</td><td>' + esc(r.horaire) + '</td>' +
        '<td><span class="badge bg-warning">' + esc(r.statut) + '</span></td><td><span class="text-muted">—</span></td>' +
        '<td><div class="text-end text-nowrap"><a href="rdv_detail.html" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Détails</a> ' +
        '<a href="#" class="btn btn-sm btn-outline-danger" data-cancel-rdv="' + r.id + '"><i class="bi bi-x-circle"></i></a></div></td>';
      tbody.appendChild(tr);
    });
    tbody.querySelectorAll('[data-cancel-rdv]').forEach(a =>
      a.addEventListener('click', e => {
        e.preventDefault();
        if (!confirm('Annuler ce rendez-vous ?')) return;
        const id = Number(a.dataset.cancelRdv);
        save(load().filter(r => r.id !== id));
        a.closest('tr').remove();
      }));
  }
})();
