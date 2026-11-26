'use strict';

/* 
   resultats.js — TOUT dans un seul fichier (script classique, sans modules)
   Sections : 1) constantes  2) utilitaires  3) rendu du tableau  4) page
   Fonctions globales attendues (common.js) : api, can, init, qs, showAlert, statutBadge
   
*/

/* constantes.js */

const DELAI_RECHERCHE = 250; // ms avant de lancer la recherche après la dernière frappe

const MESSAGES = {
  valider: 'Résultat validé.',
  publier: 'Résultat publié : visible par le patient.',
  depublier: 'Résultat dépublié.',
  supprimer: 'Résultat supprimé.',
};

const CONFIRMATIONS = {
  publier: 'Publier ce résultat ? Le patient pourra le consulter.',
  supprimer: 'Supprimer définitivement ce résultat et son document joint ?',
};

/* utils.js  */

/** Crée un élément avec du texte (textContent : pas d'injection HTML, donc pas besoin de esc()). */
const creer = (balise, texte = '', classe = '') => {
  const noeud = document.createElement(balise);
  if (classe) noeud.className = classe;
  noeud.textContent = texte;
  return noeud;
};

/** Closure : `minuteur` reste privé et conservé entre les appels. */
const debounce = (fonction, delai) => {
  let minuteur = null;
  return (...args) => {
    clearTimeout(minuteur);
    minuteur = setTimeout(() => fonction(...args), delai);
  };
};

/*  rendu.js  */


const creerLien = (texte, href, classe = 'act') => {
  const lien = creer('a', texte, classe);
  lien.href = href;
  return lien;
};

const creerBouton = (texte, action, id, classe = 'act') => {
  const bouton = creer('button', texte, classe);
  bouton.type = 'button';
  bouton.dataset.id = id;       // data-id
  bouton.dataset.act = action;  // data-act
  return bouton;
};

/** Boutons / liens de la colonne « Actions » selon le statut et les droits. */
function actionsPour(r) {
  const noeuds = [
    creerLien('Détails', `/admin/details-resultat.html?id=${encodeURIComponent(r.id)}`, 'act ok2'),
  ];
  const estPublie = r.statut === 'publié';

  if (can('edit') && !estPublie) {
    noeuds.push(creerLien('Modifier', `/admin/modifier-resultat.html?id=${encodeURIComponent(r.id)}`));
  }

  if (can('review')) {
    switch (r.statut) {
      case 'brouillon':
        noeuds.push(creerBouton('Valider', 'valider', r.id, 'act ok'));
        break;
      case 'validé':
        noeuds.push(creerBouton('Publier', 'publier', r.id, 'act ok'));
        break;
      case 'publié':
        noeuds.push(creerBouton('Dépublier', 'depublier', r.id));
        break;
    }
    if (!estPublie) noeuds.push(creerBouton('Supprimer', 'supprimer', r.id, 'act del'));
  }
  return noeuds;
}

function cellulePaiement(r) {
  const td = document.createElement('td');
  if (r.statut !== 'publié') {
    td.append(creer('small', '—'));
  } else {
    td.append(r.paiement ? creer('span', 'payé', 'badge paye') : creer('span', 'à payer', 'badge impaye'));
  }
  return td;
}

function creerLigne(r) {
  const tr = document.createElement('tr');

  const tdRef = document.createElement('td');
  tdRef.append(creer('b', r.reference), creer('small', `Prélevé le ${r.datePrelevement}`));

  const tdPatient = document.createElement('td');
  tdPatient.append(creer('b', r.patient.nom), creer('small', r.patient.email));

  const horsNorme = r.lignes.some((l) => l.indicateur !== 'normal');
  const detail = creer('small', `${r.analyse.categorie} · ${r.lignes.length} paramètre(s)`);
  if (horsNorme) detail.append(' · ', creer('span', 'valeur hors norme', 'val-bad'));
  const tdAnalyse = document.createElement('td');
  tdAnalyse.append(r.analyse.nom, detail);

  const tdStatut = document.createElement('td');
  tdStatut.innerHTML = statutBadge(r.statut); // HTML du badge fourni par common.js
  if (r.validePar) tdStatut.append(creer('small', `par ${r.validePar.nom}`));

  const tdActions = creer('td', '', 'acts');
  tdActions.append(...actionsPour(r));

  tr.append(tdRef, tdPatient, tdAnalyse, tdStatut, cellulePaiement(r), tdActions);
  return tr;
}

function remplirPatients(select, patients) {
  const options = patients.map((p) => {
    const option = document.createElement('option');
    option.value = p.id;
    option.textContent = p.nom;
    return option;
  });
  select.append(...options);
}

/* ############################ main.js ############################ */


/* --------------------------- références au DOM --------------------------- */
const formRecherche = document.getElementById('search');
const champRecherche = formRecherche.elements.q;
const filtreStatut = formRecherche.elements.st;
const filtrePatient = formRecherche.elements.pt;
const tbody = document.getElementById('rows');
const messageVide = document.getElementById('empty');
const boutonAjout = document.getElementById('add');

const compteurs = {
  total: document.getElementById('s-all'),
  brouillon: document.getElementById('s-b'),
  'validé': document.getElementById('s-v'),
  'publié': document.getElementById('s-p'),
};

/* -------------------------------- erreurs -------------------------------- */
function gererErreur(erreur) {
  console.error(erreur);                                      // Ch.5 : ne jamais étouffer une erreur
  if (erreur.message === '401') return;                       // redirection gérée par api()
  showAlert('msg', 'err', erreur.errors || erreur.message);
}

/* ------------------------------- affichage ------------------------------- */
function afficher({ stats, resultats }) {
  Object.keys(compteurs).forEach((cle) => {
    compteurs[cle].textContent = stats[cle];
  });
  messageVide.style.display = resultats.length ? 'none' : 'block';
  tbody.textContent = '';
  tbody.append(...resultats.map(creerLigne));
}

/* -------------------------------- données -------------------------------- */
async function charger() {
  const params = new URLSearchParams();
  const terme = champRecherche.value.trim();
  if (terme) params.set('q', terme);
  if (filtreStatut.value) params.set('statut', filtreStatut.value);
  if (filtrePatient.value) params.set('patientId', filtrePatient.value);

  tbody.setAttribute('aria-busy', 'true');
  try {
    afficher(await api('/api/admin/resultats?' + params));
  } catch (erreur) {
    gererErreur(erreur);
  } finally {
    tbody.removeAttribute('aria-busy');
  }
}

/** Retourne false si l'utilisateur annule la confirmation. */
async function executerAction(id, act) {
  if (CONFIRMATIONS[act] && !confirm(CONFIRMATIONS[act])) return false;

  const url = `/api/admin/resultats/${encodeURIComponent(id)}`;
  if (act === 'supprimer') {
    await api(url, { method: 'DELETE' });
  } else {
    await api(`${url}/${act}`, { method: 'POST' });
  }
  return true;
}

/* ------------------------------- événements ------------------------------ */
// Délégation d'événements (Ch.4) : un seul écouteur sur <tbody>
tbody.addEventListener('click', async (event) => {
  const bouton = event.target.closest('button[data-act]');
  if (!bouton) return;

  const { id, act } = bouton.dataset;
  bouton.disabled = true;
  try {
    if (await executerAction(id, act)) {
      showAlert('msg', 'good', MESSAGES[act]);
      await charger();
    }
  } catch (erreur) {
    gererErreur(erreur);
  } finally {
    bouton.disabled = false;
  }
});

formRecherche.addEventListener('submit', (event) => {
  event.preventDefault();
  charger();
});

champRecherche.addEventListener('input', debounce(charger, DELAI_RECHERCHE));

[filtreStatut, filtrePatient].forEach((champ) => {
  champ.addEventListener('change', charger);
});

/* ------------------------------- démarrage ------------------------------- */
async function demarrer() {
  try {
    await init();
    if (!can('edit')) boutonAjout.style.display = 'none';
    if (qs('msg')) showAlert('msg', 'good', qs('msg'));

    await Promise.all([
      api('/api/admin/referentiels').then((ref) => remplirPatients(filtrePatient, ref.patients)),
      charger(),
    ]);
  } catch (erreur) {
    gererErreur(erreur);
  }
}

demarrer(); // script chargé avec defer (ou en bas de <body>) : le DOM est prêt