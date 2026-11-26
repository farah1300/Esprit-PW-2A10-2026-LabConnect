/* ============================================================
   LabConnect – Formulaire résultat (ajout + modification)
   Validation côté client = miroir de lib/validate.js (le serveur
   revalide toujours).
   ============================================================ */
'use strict';

const EDIT_ID = qs('id');           // null => création
let REF = { analyses: [], patients: [] };

const todayStr = () => {
  const d = new Date();
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
};

const toNum = (v) => {
  if (v === '' || v == null) return null;
  const n = Number(String(v).replace(',', '.'));
  return Number.isFinite(n) ? n : NaN;
};

function indicateur(valeur, min, max) {
  const v = toNum(valeur), a = toNum(min), b = toNum(max);
  if (v === null || Number.isNaN(v)) return '';
  if (a !== null && !Number.isNaN(a) && v < a) return 'bas';
  if (b !== null && !Number.isNaN(b) && v > b) return 'élevé';
  if ((a !== null && !Number.isNaN(a)) || (b !== null && !Number.isNaN(b))) return 'normal';
  return '';
}

function lineRow(l = {}) {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input name="parametre" value="${esc(l.parametre)}" placeholder="Paramètre" aria-label="Paramètre"></td>
    <td><input name="valeur" class="w-s" value="${esc(l.valeur)}" placeholder="Valeur" aria-label="Valeur"></td>
    <td><input name="unite" class="w-s" value="${esc(l.unite)}" placeholder="Unité" aria-label="Unité"></td>
    <td><input name="refMin" class="w-s" value="${esc(l.refMin ?? '')}" placeholder="Min" aria-label="Borne min" inputmode="decimal"></td>
    <td><input name="refMax" class="w-s" value="${esc(l.refMax ?? '')}" placeholder="Max" aria-label="Borne max" inputmode="decimal"></td>
    <td class="ind"></td>
    <td><button type="button" class="rm" aria-label="Retirer cette ligne" title="Retirer">×</button></td>`;
  const refresh = () => {
    const f = (n) => tr.querySelector(`[name=${n}]`).value;
    const i = indicateur(f('valeur'), f('refMin'), f('refMax'));
    const cell = tr.querySelector('.ind');
    cell.textContent = i;
    cell.className = 'ind' + (i && i !== 'normal' ? ' val-bad' : '');
  };
  tr.addEventListener('input', refresh);
  tr.querySelector('.rm').addEventListener('click', () => tr.remove());
  refresh();
  return tr;
}

function buildForm() {
  $('form-mount').innerHTML = `
  <form id="f" novalidate>
    <div class="card">
      <h2>1. Patient et analyse</h2>
      <div class="grid2">
        <div>
          <label for="patientId">Patient associé *</label>
          <select id="patientId" name="patientId">
            <option value="">— Choisir un patient —</option>
            ${REF.patients.map((p) => `<option value="${esc(p.id)}">${esc(p.nom)} (${esc(p.email)})</option>`).join('')}
          </select>
          <button type="button" class="link-btn" id="np-toggle">+ Nouveau patient</button>
          <div class="inline-form" id="np">
            <label for="np-nom">Nom complet</label><input id="np-nom">
            <label for="np-email">E-mail</label><input id="np-email" type="text" inputmode="email">
            <label for="np-tel">Téléphone (facultatif)</label><input id="np-tel">
            <p class="hint">Le patient activera son compte en s'inscrivant avec ce même e-mail sur le site.</p>
            <button type="button" class="btn sm" id="np-save">Créer le patient</button>
          </div>
        </div>
        <div>
          <label for="analyseId">Analyse associée *</label>
          <select id="analyseId" name="analyseId">
            <option value="">— Choisir une analyse —</option>
            ${REF.analyses.map((a) => `<option value="${esc(a.id)}">${esc(a.nom)} — ${esc(a.prix)} TND</option>`).join('')}
          </select>
          <p class="hint">Les paramètres usuels et leurs valeurs de référence sont pré-remplis.</p>
        </div>
        <div>
          <label for="datePrelevement">Date de prélèvement *</label>
          <input id="datePrelevement" name="datePrelevement" type="date">
        </div>
        <div>
          <label for="dateResultat">Date du résultat *</label>
          <input id="dateResultat" name="dateResultat" type="date" value="${todayStr()}">
        </div>
      </div>
    </div>

    <div class="card">
      <h2>2. Valeurs mesurées</h2>
      <div class="table-wrap" style="border-radius:8px">
        <table class="lines">
          <thead><tr><th>Paramètre</th><th>Valeur</th><th>Unité</th><th>Réf. min</th><th>Réf. max</th><th>Indicateur</th><th></th></tr></thead>
          <tbody id="lines"></tbody>
        </table>
      </div>
      <p><button type="button" class="link-btn" id="add-line">+ Ajouter un paramètre</button></p>
      <label for="commentaire">Commentaire / interprétation</label>
      <textarea id="commentaire" name="commentaire"></textarea>
    </div>

    <div class="card">
      <h2>3. Document joint (facultatif)</h2>
      <input id="doc" type="file" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg">
      <p class="hint" id="doc-hint">PDF, PNG ou JPG — 5 Mo maximum. Le contenu est vérifié par le serveur.</p>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit" id="save">${EDIT_ID ? 'Enregistrer les modifications' : 'Enregistrer le résultat'}</button>
      <a class="btn alt" href="${EDIT_ID ? '/admin/details-resultat.html?id=' + esc(EDIT_ID) : '/admin/resultats.html'}">Annuler</a>
    </div>
  </form>`;

  $('add-line').addEventListener('click', () => $('lines').appendChild(lineRow()));

  $('analyseId').addEventListener('change', () => {
    const a = REF.analyses.find((x) => String(x.id) === $('analyseId').value);
    if (!a) return;
    const tbody = $('lines');
    const filled = [...tbody.querySelectorAll('[name=valeur]')].some((i) => i.value.trim());
    if (filled && !confirm('Remplacer les lignes saisies par les paramètres de cette analyse ?')) return;
    tbody.innerHTML = '';
    a.parametres.forEach((p) => tbody.appendChild(lineRow({ ...p, valeur: '' })));
  });

  $('np-toggle').addEventListener('click', () => $('np').classList.toggle('open'));
  $('np-save').addEventListener('click', createPatient);
  $('f').addEventListener('submit', submit);
}

async function createPatient() {
  const errors = [];
  const nom = $('np-nom').value.trim(), email = $('np-email').value.trim(), telephone = $('np-tel').value.trim();
  if (nom.length < 2 || nom.length > 100) errors.push('Nom du patient invalide (2 à 100 caractères)');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('E-mail du patient invalide');
  if (telephone && !/^[0-9+().\s-]{6,30}$/.test(telephone)) errors.push('Téléphone invalide');
  if (errors.length) return showAlert('err', 'err', errors);
  try {
    const out = await api('/api/admin/patients', jsonOpts('POST', { nom, email, telephone }));
    const p = out.patient;
    $('patientId').insertAdjacentHTML('beforeend', `<option value="${esc(p.id)}">${esc(p.nom)} (${esc(p.email)})</option>`);
    $('patientId').value = p.id;
    $('np').classList.remove('open');
    showAlert('err', 'err', []);
  } catch (e) {
    if (e.message !== '401') showAlert('err', 'err', e.errors);
  }
}

function collect() {
  const lignes = [...$('lines').querySelectorAll('tr')].map((tr) => {
    const g = (n) => tr.querySelector(`[name=${n}]`).value.trim();
    return { parametre: g('parametre'), valeur: g('valeur'), unite: g('unite'), refMin: g('refMin'), refMax: g('refMax') };
  });
  return {
    patientId: Number($('patientId').value),
    analyseId: Number($('analyseId').value),
    datePrelevement: $('datePrelevement').value,
    dateResultat: $('dateResultat').value,
    commentaire: $('commentaire').value.trim(),
    lignes
  };
}

function checkClient(d, file) {
  const e = [];
  const today = todayStr();
  if (!d.patientId) e.push('Choisissez un patient');
  if (!d.analyseId) e.push('Choisissez une analyse');
  if (!d.datePrelevement) e.push('Date de prélèvement requise');
  else if (d.datePrelevement > today) e.push('La date de prélèvement ne peut pas être dans le futur');
  if (!d.dateResultat) e.push('Date du résultat requise');
  else if (d.dateResultat > today) e.push('La date du résultat ne peut pas être dans le futur');
  else if (d.datePrelevement && d.dateResultat < d.datePrelevement) e.push('Le résultat ne peut pas précéder le prélèvement');
  if (d.commentaire.length > 1000) e.push('Commentaire : 1000 caractères maximum');
  if (!d.lignes.length) e.push('Ajoutez au moins un paramètre');
  d.lignes.forEach((l, i) => {
    if (l.parametre.length < 2) e.push(`Ligne ${i + 1} : nom du paramètre requis`);
    else if (l.parametre.length > 80) e.push(`Ligne ${i + 1} : paramètre trop long (80 max)`);
    if (!l.valeur) e.push(`Ligne ${i + 1} : valeur requise`);
    else if (l.valeur.length > 40) e.push(`Ligne ${i + 1} : valeur trop longue (40 max)`);
    if (l.unite.length > 20) e.push(`Ligne ${i + 1} : unité trop longue (20 max)`);
    const a = toNum(l.refMin), b = toNum(l.refMax);
    if (Number.isNaN(a) || Number.isNaN(b)) e.push(`Ligne ${i + 1} : bornes invalides`);
    else if (a !== null && b !== null && a > b) e.push(`Ligne ${i + 1} : min > max`);
  });
  if (file) {
    if (!/\.(pdf|png|jpe?g)$/i.test(file.name)) e.push('Document : PDF, PNG ou JPG uniquement');
    if (file.size > 5 * 1024 * 1024) e.push('Document : 5 Mo maximum');
    if (file.size === 0) e.push('Document vide');
  }
  return e;
}

async function submit(event) {
  event.preventDefault();
  const file = $('doc').files[0];
  const data = collect();
  const errors = checkClient(data, file);
  if (errors.length) return showAlert('err', 'err', errors);
  showAlert('err', 'err', []);

  $('save').disabled = true;
  try {
    const out = EDIT_ID
      ? await api('/api/admin/resultats/' + EDIT_ID, jsonOpts('PUT', data))
      : await api('/api/admin/resultats', jsonOpts('POST', data));
    let warn = '';
    if (file) {
      try {
        const buf = await file.arrayBuffer();
        await api(`/api/admin/resultats/${out.id}/document`, {
          method: 'POST',
          headers: { 'Content-Type': file.type || 'application/octet-stream', 'X-Filename': file.name },
          body: buf
        });
      } catch (e) {
        warn = '&warn=' + encodeURIComponent('Résultat enregistré, mais le document a été refusé : ' + e.message);
      }
    }
    location.href = `/admin/details-resultat.html?id=${out.id}&msg=${encodeURIComponent(EDIT_ID ? 'Résultat modifié.' : 'Résultat ajouté.')}${warn}`;
  } catch (e) {
    $('save').disabled = false;
    if (e.message !== '401') showAlert('err', 'err', e.errors);
  }
}

(async function start() {
  await init();
  if (!can('edit')) { location.href = '/admin/resultats.html'; return; }
  REF = await api('/api/admin/referentiels');
  buildForm();
  if (!EDIT_ID) {
    const pre = qs('patientId');
    if (pre) $('patientId').value = pre;
    return;
  }
  // Modification : pré-remplissage
  try {
    const r = await api('/api/admin/resultats/' + EDIT_ID);
    if (r.statut === 'publié') {
      location.href = `/admin/details-resultat.html?id=${r.id}&warn=` + encodeURIComponent('Dépubliez le résultat avant de le modifier.');
      return;
    }
    $('patientId').value = r.patient.id;
    $('analyseId').value = r.analyse.id;
    $('datePrelevement').value = r.datePrelevement;
    $('dateResultat').value = r.dateResultat;
    $('commentaire').value = r.commentaire || '';
    r.lignes.forEach((l) => $('lines').appendChild(lineRow(l)));
    if (r.document) $('doc-hint').textContent = `Document actuel : ${r.document.nom}. Choisir un fichier le remplacera.`;
  } catch (e) {
    if (e.message !== '401') showAlert('err', 'err', e.errors);
  }
})();
