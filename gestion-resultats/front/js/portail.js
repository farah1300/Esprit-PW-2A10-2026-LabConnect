/* ============================================================
   LabConnect – Outils communs de l'espace patient (front office)
   ============================================================ */
'use strict';

const $ = (id) => document.getElementById(id);

const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[c]));

const fmtDate = (iso) => (iso ? new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' }) : '');
const qs = (name) => new URLSearchParams(location.search).get(name);

/* Appel API : 401 => retour à la page de connexion (sauf noRedirect). */
async function api(url, options, { noRedirect = false } = {}) {
  const response = await fetch(url, options);
  if (response.status === 401 && !noRedirect) {
    location.href = 'resultats.html';
    throw new Error('401');
  }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const err = new Error(data.error || 'Erreur serveur');
    err.errors = data.errors || [err.message];
    err.status = response.status;
    throw err;
  }
  return data;
}

const jsonOpts = (method, body) => ({
  method,
  headers: { 'Content-Type': 'application/json' },
  body: body === undefined ? undefined : JSON.stringify(body)
});

function showAlert(id, kind, messages) {
  const el = $(id);
  if (!el) return;
  const list = [].concat(messages || []).filter(Boolean);
  el.className = 'alert ' + kind + (list.length ? ' show' : '');
  el.innerHTML = list.length === 1 ? esc(list[0]) : '<ul>' + list.map((m) => `<li>${esc(m)}</li>`).join('') + '</ul>';
  if (list.length) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

const payBadge = (paye) => (paye
  ? '<span class="badge paye">payé</span>'
  : '<span class="badge impaye">à payer</span>');

/* Résultat hors norme : au moins un indicateur différent de « normal ». */
const horsNorme = (r) => (r.lignes || []).some((l) => l.indicateur && l.indicateur !== 'normal');

/* ---------- Menu latéral + barre du haut (définis une seule fois) ---------- */
const svg = (d) => `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${d}</svg>`;
const ICONS = {
  res: svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>'),
  rdv: svg('<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'),
  home: svg('<path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>'),
  out: svg('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>')
};

let ME = null;

/* Place le <main> de la page dans la structure commune. */
function renderShell(active) {
  const main = document.querySelector('main');
  const on = (key) => (active === key ? ' class="on" aria-current="page"' : '');
  const shell = document.createElement('div');
  shell.className = 'shell';
  shell.innerHTML = `
    <aside class="side">
      <a class="brand" href="resultats.html" aria-label="LabConnect – mes résultats">
        <img class="logo" src="images/logo.png" alt="" width="40" height="40">
        <span>LabConnect</span>
      </a>
      <nav class="side-nav" aria-label="Navigation espace patient">
        <a href="resultats.html"${on('resultats')}>${ICONS.res}<span>Mes résultats</span></a>
        <a href="/#appointment">${ICONS.rdv}<span>Prendre rendez-vous</span></a>
      </nav>
      <div class="side-foot">
        <a href="/">${ICONS.home}<span>Accueil du site</span></a>
        <button type="button" id="out">${ICONS.out}<span>Déconnexion</span></button>
      </div>
    </aside>
    <div class="content">
      <header class="topbar2">
        <span class="who" id="who"></span>
        <img class="logo sm" src="images/logo.png" alt="" width="34" height="34">
      </header>
      <div class="content-body" id="content-body"></div>
    </div>`;
  document.body.prepend(shell);
  $('content-body').append(main);
  main.hidden = false;
  $('who').textContent = (ME ? ME.nom : '') + ' · Patient';
  $('out').addEventListener('click', async () => {
    await fetch('/api/patient/logout', { method: 'POST' });
    location.href = 'resultats.html';
  });
}

/* Patient connecté ? (renvoie null sinon, sans redirection) */
async function loadMe() {
  try {
    const data = await api('/api/patient/me', undefined, { noRedirect: true });
    ME = data.patient;
  } catch (e) {
    ME = null;
  }
  return ME;
}
