/* ============================================================
   LabConnect – Outils communs du back office (module Résultats)
   ============================================================ */
'use strict';

const $ = (id) => document.getElementById(id);

const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[c]));

const STATUT_CLASS = { 'brouillon': 'brouillon', 'validé': 'valide', 'publié': 'publie' };
const statutBadge = (s) => `<span class="badge ${STATUT_CLASS[s] || ''}">${esc(s)}</span>`;

/* Rôles : admin et médecin = personnel autorisé ; technicien = saisie seulement */
const ROLES = {
  edit: ['admin', 'medecin', 'technicien'],
  review: ['admin', 'medecin']     // valider, publier, supprimer
};
let ME = null;
const can = (action) => !!ME && ROLES[action].includes(ME.role);

async function api(url, options) {
  const response = await fetch(url, options);
  if (response.status === 401) {
    location.href = '/admin/login.html';
    throw new Error('401');
  }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const err = new Error(data.error || 'Erreur serveur');
    err.errors = data.errors || [err.message];
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

const fmtDate = (iso) => (iso ? new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' }) : '');
const qs = (name) => new URLSearchParams(location.search).get(name);
const ROLE_LABEL = { admin: 'Administrateur', medecin: 'Médecin', technicien: 'Technicien' };

/* Structure commune à toutes les pages du back office : menu latéral violet (logo + navigation)
   + barre du haut (« Administrateur » + logo). Définie ici une seule fois.
   Page : <header id="topbar" data-active="rendezvous"></header> suivi de <main>…</main> */
const ICONS = {
  res: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>',
  site: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14 21 3"/></svg>',
  out: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>'
};

function renderTopBar() {
  const mount = $('topbar');
  if (!mount) return;
  const on = (key) => (mount.dataset.active === key ? ' class="on" aria-current="page"' : '');
  const main = document.querySelector('main');
  mount.outerHTML = `
  <div class="shell">
    <aside class="side" id="side">
      <a class="brand" href="resultats.html" aria-label="LabConnect – accueil de l'administration">
        <img class="logo" src="logo.png" alt="" width="40" height="40">
        <span>LabConnect</span>
      </a>
      <nav class="side-nav" aria-label="Navigation du back office">
        <a href="resultats.html"${on('resultats')}>${ICONS.res}<span>Résultats</span></a>
      </nav>
      <div class="side-foot">
        <a href="/" target="_blank" rel="noopener noreferrer">${ICONS.site}<span>Voir le site patient</span></a>
        <button type="button" id="out">${ICONS.out}<span>Déconnexion</span></button>
      </div>
    </aside>
    <div class="content">
      <header class="topbar2">
        <span class="who" id="who"></span>
        <img class="logo sm" src="logo.png" alt="" width="34" height="34">
      </header>
      <div class="content-body" id="content-body"></div>
    </div>
  </div>`;
  if (main) $('content-body').append(main);
}
renderTopBar();   // les scripts sont chargés en fin de <body> : le point d'ancrage existe déjà

async function init() {
  ME = await api('/api/admin/me');
  const who = $('who');
  if (who) { const label = ROLE_LABEL[ME.role] || ME.role; who.textContent = ME.nom === label ? label : ME.nom + ' · ' + label; }
  const out = $('out');
  if (out) {
    out.addEventListener('click', async () => {
      await fetch('/api/admin/logout', { method: 'POST' });
      location.href = '/admin/login.html';
    });
  }
  return ME;
}
