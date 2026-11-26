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
      .forEach(tr => {
        const hay = tr.dataset.statut !== undefined ? tr.dataset.statut : tr.textContent.toLowerCase();
        tr.style.display = hay.includes(q) ? '' : 'none';
      });
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

// ===== Rendez-vous : partagés entre l'espace patient et l'espace admin (localStorage) =====
(function () {
  const KEY = 'labconnect_mes_rdv';
  const LABEL = { en_attente: 'En attente', confirme: 'Confirmé', annule: 'Annulé', present: 'Présent', absent: 'Absent' };
  const COLOR = { en_attente: 'warning', confirme: 'success', annule: 'danger', present: 'primary', absent: 'secondary' };
  const INFO = {
    'Bilan sanguin complet': { prix: 45, delai: 1, consignes: 'À jeun depuis 8 à 12 heures.' },
    "Analyse d'urine":       { prix: 20, delai: 1, consignes: 'Urine du matin, premier jet, flacon stérile.' },
    'Bilan hormonal':        { prix: 70, delai: 3, consignes: 'Prélèvement le matin ; jour du cycle à préciser.' },
    'Sérologie hépatite B':  { prix: 55, delai: 2, consignes: '' }
  };
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const load = () => { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; } };
  const save = list => { try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {} };
  const norm = r => { if (!LABEL[r.statut]) r.statut = Object.keys(LABEL).find(k => LABEL[k] === r.statut) || 'en_attente'; return r; };
  const all = () => load().map(norm);
  const badge = st => '<span class="badge bg-' + COLOR[st] + '">' + LABEL[st] + '</span>';
  const dash = '<span class="text-muted">—</span>';
  const hasQr = r => r.statut === 'confirme' || r.statut === 'present';
  const qrSmall = r => hasQr(r) ? '<i class="bi bi-qr-code text-primary"></i>' : dash;
  const setStatut = (id, st) => { const l = all(); const r = l.find(x => x.id === id); if (r) { r.statut = st; save(l); } };
  const remove = id => save(all().filter(r => r.id !== id));
  const pad = n => String(n).padStart(2, '0');
  const now = () => { const d = new Date(); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()); };
  const myName = () => { try { const n = localStorage.getItem('labconnect_user_nom'); return n ? n.replace(/(^|\s)\S/g, c => c.toUpperCase()) : null; } catch (e) { return null; } };
  const prix = a => ((INFO[a] || {}).prix || 0).toFixed(2).replace('.', ',') + ' DT';
  const isAdmin = !!document.querySelector('.sidebar');

  // 1) Création d'un rendez-vous (formulaire patient ou formulaire admin)
  const sel = document.querySelector('form select[name="id_analyse"]');
  if (sel) {
    const f = sel.closest('form');
    f.addEventListener('submit', e => {
      e.preventDefault();
      const txt = n => { const x = f.querySelector('[name="' + n + '"]'); return x.options[x.selectedIndex].text; };
      const [date, horaire] = txt('id_creneau').replace(/\s*\(.*\)\s*$/, '').split(' · ');
      const adminForm = !!f.querySelector('[name="id_utilisateur"]');
      const file = f.querySelector('[name="ordonnance"]');
      const list = all();
      list.push({
        id: list.reduce((m, r) => Math.max(m, r.id), 100) + 1,
        patient: adminForm ? txt('id_utilisateur') : (myName() || 'Patient'),
        analyse: txt('id_analyse').split(' — ')[0],
        date: date, horaire: horaire,
        statut: adminForm ? f.querySelector('[name="statut"]').value : 'en_attente',
        ordonnance: file && file.files[0] ? file.files[0].name : '',
        created: now()
      });
      save(list);
      location.href = adminForm ? 'rendezvous.html' : 'rdv_mine.html';
    });
  }

  // 2) Espace patient : "Mes RDV"
  function renderMine() {
    const tb = document.querySelector('#mineTable tbody');
    if (!tb || !myName()) return;           // sans compte : on garde les lignes d'exemple
    const rows = all().filter(r => r.patient === myName()).sort((a, b) => b.id - a.id);
    tb.innerHTML = rows.map(r =>
      '<tr><td>' + r.id + '</td><td>' + esc(r.analyse) + '</td><td>' + esc(r.date) + '</td><td>' + esc(r.horaire) + '</td>' +
      '<td>' + badge(r.statut) + '</td><td>' + qrSmall(r) + '</td>' +
      '<td><div class="text-end text-nowrap"><a href="rdv_detail.html?id=' + r.id + '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Détails</a>' +
      (r.statut === 'en_attente' || r.statut === 'confirme' ? ' <button type="button" class="btn btn-sm btn-outline-danger" data-act="annule" data-id="' + r.id + '" title="Annuler"><i class="bi bi-x-circle"></i></button>' : '') +
      '</div></td></tr>').join('') ||
      '<tr><td colspan="7" class="text-center text-muted py-4">Aucun rendez-vous pour le moment.</td></tr>';
  }

  // 3) Espace admin : tous les rendez-vous réellement pris
  function renderAdmin() {
    const tb = document.querySelector('#rdvTable tbody');
    if (!tb || !isAdmin) return;
    const btn = (r, act, cls, icon, title) =>
      '<button type="button" class="btn btn-sm btn-' + cls + '" data-act="' + act + '" data-id="' + r.id + '" title="' + title + '"' +
      (r.statut === act ? ' disabled' : '') + '><i class="bi ' + icon + '"></i></button> ';
    tb.innerHTML = all().sort((a, b) => b.id - a.id).map(r =>
      '<tr data-statut="' + LABEL[r.statut].toLowerCase() + '"><td>' + r.id + '</td><td>' + esc(r.patient) + '</td><td>' + esc(r.analyse) + '</td>' +
      '<td>' + esc(r.date) + '</td><td>' + esc(r.horaire) + '</td><td>' + badge(r.statut) + '</td><td>' + qrSmall(r) + '</td>' +
      '<td>' + (r.ordonnance ? '<i class="bi bi-file-earmark-medical"></i> ' + esc(r.ordonnance) : dash) + '</td>' +
      '<td><span class="small">' + esc(r.created || '') + '</span></td>' +
      '<td><div class="text-end text-nowrap"><a href="rendezvous_detail.html?id=' + r.id + '" class="btn btn-sm btn-outline-primary" title="Détails"><i class="bi bi-eye"></i></a> ' +
      btn(r, 'confirme', 'success', 'bi-check-lg', 'Confirmer') + btn(r, 'present', 'primary', 'bi-person-check', 'Marquer présent') +
      btn(r, 'absent', 'secondary', 'bi-person-x', 'Marquer absent') + btn(r, 'annule', 'warning', 'bi-x-lg', 'Annuler') +
      '<button type="button" class="btn btn-sm btn-danger" data-act="delete" data-id="' + r.id + '" title="Supprimer"><i class="bi bi-trash"></i></button></div></td></tr>').join('') ||
      '<tr><td colspan="10" class="text-center text-muted py-4">Aucun rendez-vous pour le moment. Ils apparaîtront ici dès qu\'un patient réserve.</td></tr>';
    const active = document.querySelector('[data-filter-statut][data-target="#rdvTable"].btn-primary');
    if (active) active.click();
  }

  // 4) Page détail (patient ou admin) : ?id=...
  function renderDetail() {
    const box = document.getElementById('rdvDetail');
    if (!box) return;
    const back = isAdmin ? 'rendezvous.html' : 'rdv_mine.html';
    const r = all().find(x => x.id === Number(new URLSearchParams(location.search).get('id')));
    if (!r) { box.innerHTML = '<div class="card p-4 text-center"><p>Rendez-vous introuvable.</p><a href="' + back + '" class="btn btn-primary mx-auto">Retour</a></div>'; return; }
    const info = INFO[r.analyse] || {};
    const st = (act, cls, label) => '<button type="button" class="btn btn-' + cls + '" data-act="' + act + '" data-id="' + r.id + '"' + (r.statut === act ? ' disabled' : '') + '>' + label + '</button>';
    const actions = isAdmin
      ? st('confirme', 'success', 'Confirmer') + st('present', 'primary', 'Présent') + st('absent', 'secondary', 'Absent') + st('annule', 'warning', 'Annuler') +
        '<button type="button" class="btn btn-danger" data-act="delete" data-id="' + r.id + '">Supprimer</button>'
      : (r.statut === 'en_attente' || r.statut === 'confirme' ? st('annule', 'outline-danger', 'Annuler le rendez-vous') : '');
    box.innerHTML = '<div class="card p-4 p-md-5"><div class="d-flex justify-content-between align-items-center mb-4"><h2 class="text-primary fw-bold mb-0">Rendez-vous #' + r.id + '</h2>' + badge(r.statut) + '</div>' +
      '<div class="row g-4"><div class="col-md-7"><dl class="row mb-0">' +
      (isAdmin ? '<dt class="col-5">Patient</dt><dd class="col-7">' + esc(r.patient) + '</dd>' : '') +
      '<dt class="col-5">Analyse</dt><dd class="col-7">' + esc(r.analyse) + '</dd><dt class="col-5">Date</dt><dd class="col-7">' + esc(r.date) + '</dd>' +
      '<dt class="col-5">Horaire</dt><dd class="col-7">' + esc(r.horaire) + '</dd><dt class="col-5">Prix</dt><dd class="col-7">' + prix(r.analyse) + '</dd>' +
      '<dt class="col-5">Résultat sous</dt><dd class="col-7">' + (info.delai || '—') + ' jour(s)</dd>' +
      '<dt class="col-5">Ordonnance</dt><dd class="col-7">' + (r.ordonnance ? esc(r.ordonnance) : 'Aucune') + '</dd>' +
      '<dt class="col-5">Créé le</dt><dd class="col-7">' + esc(r.created || '') + '</dd></dl>' +
      (info.consignes ? '<div class="alert alert-info mt-4 mb-0 small"><i class="bi bi-info-circle"></i> <strong>Consignes :</strong> ' + esc(info.consignes) + '</div>' : '') + '</div>' +
      '<div class="col-md-5 text-center"><div class="border rounded-4 p-4">' +
      (hasQr(r) ? '<i class="bi bi-qr-code text-primary" style="font-size:7rem"></i><p class="small text-muted mb-0">QR code du rendez-vous</p>'
                : '<i class="bi bi-hourglass-split text-muted" style="font-size:4rem"></i><p class="small text-muted mb-0">Le QR code est disponible une fois le rendez-vous confirmé.</p>') +
      '</div></div></div><div class="d-flex flex-wrap gap-2 mt-4"><a href="' + back + '" class="btn btn-outline-primary">Retour</a>' + actions + '</div></div>';
  }

  // 5) Actions (confirmer, présent, absent, annuler, supprimer)
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-act]');
    if (!b) return;
    e.preventDefault();
    const id = Number(b.dataset.id), act = b.dataset.act;
    if (act === 'delete') {
      if (!confirm('Supprimer ce rendez-vous ?')) return;
      remove(id);
      if (document.getElementById('rdvDetail')) { location.href = 'rendezvous.html'; return; }
    } else {
      if (act === 'annule' && !confirm('Annuler ce rendez-vous ?')) return;
      setStatut(id, act);
    }
    renderMine(); renderAdmin(); renderDetail();
  });

  renderMine(); renderAdmin(); renderDetail();
})();

// ===== Espace administration : connexion / déconnexion (démo sans serveur) =====
(function () {
  const ADMIN_EMAIL = 'admin@labconnect.tn', ADMIN_PASS = 'admin123';
  document.querySelectorAll('[data-form="admin-login"]').forEach(f =>
    f.addEventListener('submit', e => {
      e.preventDefault();
      const d = new FormData(f);
      if (String(d.get('email')).trim().toLowerCase() === ADMIN_EMAIL && d.get('mot_de_passe') === ADMIN_PASS) {
        try { sessionStorage.setItem('labconnect_admin', '1'); } catch (err) {}
        location.href = 'rendezvous.html';
      } else {
        document.getElementById('adminError').classList.remove('d-none');
      }
    }));
  document.querySelectorAll('[data-logout]').forEach(a =>
    a.addEventListener('click', e => {
      e.preventDefault();
      try { sessionStorage.removeItem('labconnect_admin'); } catch (err) {}
      location.href = 'login.html';
    }));
})();
