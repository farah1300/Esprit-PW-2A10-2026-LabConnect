/* ============================================================
   LabConnect – Détails d'un résultat (back office)
   ============================================================ */
'use strict';

const ID = qs('id');
const size = (n) => (n > 1048576 ? (n / 1048576).toFixed(1) + ' Mo' : Math.max(1, Math.round(n / 1024)) + ' Ko');

function render(r) {
  const canEdit = can('edit') && r.statut !== 'publié';
  const rev = can('review');

  const buttons = [];
  if (canEdit) buttons.push(`<a class="btn alt" href="/admin/modifier-resultat.html?id=${r.id}">Modifier</a>`);
  if (rev && r.statut === 'brouillon') buttons.push('<button class="btn ok" data-act="valider">Valider le résultat</button>');
  if (rev && r.statut === 'validé') buttons.push('<button class="btn ok" data-act="publier">Publier au patient</button>');
  if (rev && r.statut === 'publié') buttons.push('<button class="btn alt" data-act="depublier">Dépublier</button>');
  buttons.push(`<a class="btn alt" href="/api/admin/resultats/${r.id}/pdf">Télécharger en PDF</a>`);
  if (rev && r.statut !== 'publié') buttons.push('<button class="btn ko" data-act="supprimer">Supprimer</button>');

  const hint = r.statut === 'brouillon'
    ? (rev ? 'Relisez les valeurs puis validez.' : 'En attente de validation par un médecin.')
    : r.statut === 'validé'
      ? (rev ? 'Validé : vous pouvez maintenant le publier.' : 'En attente de publication par un médecin.')
      : 'Visible par le patient dans son espace.';

  $('detail').innerHTML = `
    <div class="page-head">
      <div>
        <h1>${esc(r.reference)} ${statutBadge(r.statut)}</h1>
        <p>${esc(hint)}</p>
      </div>
      <div class="form-actions" style="margin:0">${buttons.join('')}</div>
    </div>

    <div class="card">
      <h2>Patient et analyse</h2>
      <dl class="kv">
        <dt>Patient</dt><dd>${esc(r.patient.nom)} <small>${esc(r.patient.email)}</small></dd>
        <dt>Analyse</dt><dd>${esc(r.analyse.nom)} <small>(${esc(r.analyse.categorie)})</small></dd>
        <dt>Prélèvement</dt><dd>${esc(r.datePrelevement)}</dd>
        <dt>Résultat daté du</dt><dd>${esc(r.dateResultat)}</dd>
        <dt>Médecin validateur</dt><dd>${r.validePar ? esc(r.validePar.nom) : '—'}</dd>
        <dt>Paiement patient</dt><dd>${r.statut !== 'publié' ? '—'
          : r.paiement ? `<span class="badge paye">payé</span> ${esc(r.paiement.montant)} ${esc(r.paiement.devise)} · ${esc(r.paiement.carte)} · ${esc(fmtDate(r.paiement.date))}`
          : `<span class="badge impaye">à payer</span> ${esc(r.montant)} TND`}</dd>
      </dl>
    </div>

    <div class="card">
      <h2>Valeurs mesurées</h2>
      <div class="table-wrap" style="border-radius:8px">
        <table>
          <thead><tr><th>Paramètre</th><th>Valeur</th><th>Unité</th><th>Référence</th><th>Indicateur</th></tr></thead>
          <tbody>${r.lignes.map((l) => {
            const bad = l.indicateur !== 'normal';
            return `<tr><td>${esc(l.parametre)}</td><td class="${bad ? 'val-bad' : ''}"><b>${esc(l.valeur)}</b></td><td>${esc(l.unite)}</td><td>${esc(l.reference || '—')}</td><td class="${bad ? 'val-bad' : ''}">${esc(l.indicateur)}</td></tr>`;
          }).join('')}</tbody>
        </table>
      </div>
      ${r.commentaire ? `<p><b>Commentaire :</b> ${esc(r.commentaire)}</p>` : ''}
    </div>

    <div class="card">
      <h2>Document joint</h2>
      <div class="doc-box">
        <div>${r.document
          ? `<b>${esc(r.document.nom)}</b> <small>· ${esc(size(r.document.taille))} · ${esc(fmtDate(r.document.date))}</small>`
          : '<span class="hint">Aucun document joint.</span>'}</div>
        <div class="form-actions" style="margin:0">
          ${r.document ? `<a class="btn alt sm" href="/api/admin/resultats/${r.id}/document">Télécharger</a>` : ''}
          ${canEdit ? `<label class="btn alt sm" style="margin:0;cursor:pointer;color:var(--p)">${r.document ? 'Remplacer' : 'Joindre un fichier'}<input id="up" type="file" accept=".pdf,.png,.jpg,.jpeg" hidden></label>` : ''}
          ${canEdit && r.document ? '<button class="btn ko sm" data-act="rmdoc">Retirer</button>' : ''}
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Historique</h2>
      <ul class="timeline">
        <li><b>Créé</b> par ${esc(r.creePar)} · ${esc(fmtDate(r.createdAt))}</li>
        ${r.valideLe ? `<li><b>Validé</b> par ${esc(r.validePar ? r.validePar.nom : '—')} · ${esc(fmtDate(r.valideLe))}</li>` : ''}
        ${r.publieLe ? `<li><b>Publié</b> · ${esc(fmtDate(r.publieLe))}</li>` : ''}
        ${r.paiement ? `<li><b>Payé</b> par le patient · ${esc(fmtDate(r.paiement.date))}</li>` : ''}
      </ul>
    </div>`;
}

async function load() {
  try {
    render(await api('/api/admin/resultats/' + ID));
  } catch (e) {
    if (e.message !== '401') $('detail').innerHTML = `<div class="alert err show">${esc(e.message)}</div>`;
  }
}

document.addEventListener('click', async (event) => {
  const b = event.target.closest('button[data-act]');
  if (!b) return;
  const act = b.dataset.act;
  try {
    if (act === 'supprimer') {
      if (!confirm('Supprimer définitivement ce résultat et son document joint ?')) return;
      await api('/api/admin/resultats/' + ID, { method: 'DELETE' });
      location.href = '/admin/resultats.html?msg=' + encodeURIComponent('Résultat supprimé.');
      return;
    }
    if (act === 'rmdoc') {
      if (!confirm('Retirer le document joint ?')) return;
      await api(`/api/admin/resultats/${ID}/document`, { method: 'DELETE' });
    } else {
      if (act === 'publier' && !confirm('Publier ce résultat ? Le patient pourra le consulter.')) return;
      await api(`/api/admin/resultats/${ID}/${act}`, { method: 'POST' });
    }
    showAlert('msg', 'good', { valider: 'Résultat validé.', publier: 'Résultat publié : visible par le patient.', depublier: 'Résultat dépublié.', rmdoc: 'Document retiré.' }[act]);
    load();
  } catch (e) {
    if (e.message !== '401') showAlert('msg', 'err', e.errors);
  }
});

document.addEventListener('change', async (event) => {
  if (event.target.id !== 'up') return;
  const file = event.target.files[0];
  if (!file) return;
  if (!/\.(pdf|png|jpe?g)$/i.test(file.name)) return showAlert('msg', 'err', 'Document : PDF, PNG ou JPG uniquement');
  if (file.size > 5 * 1024 * 1024) return showAlert('msg', 'err', 'Document : 5 Mo maximum');
  try {
    await api(`/api/admin/resultats/${ID}/document`, {
      method: 'POST',
      headers: { 'Content-Type': file.type || 'application/octet-stream', 'X-Filename': file.name },
      body: await file.arrayBuffer()
    });
    showAlert('msg', 'good', 'Document joint.');
    load();
  } catch (e) {
    if (e.message !== '401') showAlert('msg', 'err', e.errors);
  }
});

(async function start() {
  await init();
  if (!ID) { location.href = '/admin/resultats.html'; return; }
  if (qs('msg')) showAlert('msg', 'good', qs('msg'));
  if (qs('warn')) showAlert('msg', 'err', qs('warn'));
  load();
})();
