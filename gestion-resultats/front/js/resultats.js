/* ============================================================
   LabConnect – Logique des pages patient (front office)
   Validation 100 % JavaScript (mêmes règles que le serveur,
   qui fait foi) : aucun attribut HTML5 de validation.
   Pages : resultats · inscription · details-resultat
   ============================================================ */
'use strict';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PHONE_RE = /^[0-9+().\s-]{6,30}$/;

function luhn(digits) {
  let sum = 0, alt = false;
  for (let i = digits.length - 1; i >= 0; i--) {
    let n = +digits[i];
    if (alt) { n *= 2; if (n > 9) n -= 9; }
    sum += n; alt = !alt;
  }
  return sum % 10 === 0;
}

const val = (id) => ($(id) ? $(id).value.trim() : '');
function markBad(ids) {
  document.querySelectorAll('input.bad').forEach((el) => el.classList.remove('bad'));
  ids.forEach((id) => $(id) && $(id).classList.add('bad'));
}

/* ============================================================
   Connexion (resultats.html)
   ============================================================ */
function validateLogin() {
  const errors = [], bad = [];
  if (!EMAIL_RE.test(val('l-email').toLowerCase())) { errors.push('E-mail invalide'); bad.push('l-email'); }
  if (!$('l-password').value) { errors.push('Mot de passe requis'); bad.push('l-password'); }
  return { errors, bad };
}

function initLogin() {
  $('login').hidden = false;
  $('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const { errors, bad } = validateLogin();
    markBad(bad);
    showAlert('login-alert', 'err', errors);
    if (errors.length) return;
    const btn = $('login-btn');
    btn.disabled = true;
    try {
      await api('/api/patient/login', jsonOpts('POST', { email: val('l-email').toLowerCase(), password: $('l-password').value }), { noRedirect: true });
      location.reload();
    } catch (err) {
      showAlert('login-alert', 'err', err.errors || err.message);
      btn.disabled = false;
    }
  });
}

/* ============================================================
   Mes résultats (liste)
   ============================================================ */
let LISTE = [];

function renderList() {
  const needle = val('q').toLowerCase();
  const filtre = $('filtre').value;
  const rows = LISTE.filter((r) => {
    if (filtre === 'payes' && !r.paiement) return false;
    if (filtre === 'apayer' && r.paiement) return false;
    return !needle || [r.reference, r.analyse.nom, r.analyse.categorie].join(' ').toLowerCase().includes(needle);
  });

  $('s-total').textContent = LISTE.length;
  $('s-payes').textContent = LISTE.filter((r) => r.paiement).length;
  $('s-apayer').textContent = LISTE.filter((r) => !r.paiement).length;

  if (!rows.length) {
    $('rows').innerHTML = `<tr><td colspan="5" class="empty">${LISTE.length ? 'Aucun résultat ne correspond à votre recherche.' : 'Aucun résultat publié pour le moment.'}</td></tr>`;
    return;
  }
  $('rows').innerHTML = rows.map((r) => {
    const n = (r.lignes || []).length;
    return `<tr>
      <td><b>${esc(r.reference)}</b><small>Prélevé le ${esc(r.datePrelevement)}</small></td>
      <td>${esc(r.analyse.nom)}<small>${esc(r.analyse.categorie)} · ${n} paramètre(s)${horsNorme(r) ? ' · <span class="flag">valeur hors norme</span>' : ''}</small></td>
      <td>${esc(fmtDate(r.publieLe))}</td>
      <td>${payBadge(!!r.paiement)}<small>${esc(r.paiement ? r.paiement.montant : r.montant)} TND</small></td>
      <td class="actions">
        <a class="btn sm" href="details-resultat.html?id=${r.id}">Détails</a>
        ${r.paiement
          ? `<a class="btn sm" href="/api/patient/resultats/${r.id}/pdf" download>PDF</a>`
          : `<a class="btn sm" href="telecharger-resultat.html?id=${r.id}">Payer</a>`}
      </td>
    </tr>`;
  }).join('');
}

async function initList() {
  renderShell('resultats');
  $('q').addEventListener('input', renderList);
  $('filtre').addEventListener('change', renderList);
  try {
    LISTE = (await api('/api/patient/resultats')).resultats;
  } catch (e) {
    showAlert('list-alert', 'err', e.errors || e.message);
  }
  renderList();
}

/* ============================================================
   Inscription (inscription.html)
   ============================================================ */
function validateRegister() {
  const errors = [], bad = [];
  if (val('r-nom').length < 2) { errors.push('Nom invalide (2 caractères minimum)'); bad.push('r-nom'); }
  if (!EMAIL_RE.test(val('r-email').toLowerCase())) { errors.push('E-mail invalide'); bad.push('r-email'); }
  if (val('r-tel') && !PHONE_RE.test(val('r-tel'))) { errors.push('Téléphone invalide'); bad.push('r-tel'); }
  const pw = $('r-password').value;
  if (pw.length < 8 || !/[A-Za-z]/.test(pw) || !/\d/.test(pw)) { errors.push('Mot de passe : 8 caractères minimum, avec lettres et chiffres'); bad.push('r-password'); }
  else if (pw !== $('r-password2').value) { errors.push('Les deux mots de passe ne correspondent pas'); bad.push('r-password2'); }
  return { errors, bad };
}

function initRegister() {
  $('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const { errors, bad } = validateRegister();
    markBad(bad);
    showAlert('register-alert', 'err', errors);
    if (errors.length) return;
    const btn = $('register-btn');
    btn.disabled = true;
    try {
      await api('/api/patient/register', jsonOpts('POST', {
        nom: val('r-nom'), email: val('r-email').toLowerCase(), telephone: val('r-tel'), password: $('r-password').value
      }), { noRedirect: true });
      location.href = 'resultats.html';
    } catch (err) {
      showAlert('register-alert', 'err', err.errors || err.message);
      btn.disabled = false;
    }
  });
}

/* ============================================================
   Détails d'un résultat (details-resultat.html) + paiement
   ============================================================ */
let R = null;
let bannerPaiement = false;

const fmtSize = (n) => (n >= 1048576 ? (n / 1048576).toFixed(1) + ' Mo' : Math.max(1, Math.round(n / 1024)) + ' Ko');

function renderDetails() {
  const paye = !!R.paiement;
  $('banner').hidden = !(bannerPaiement && paye);

  $('d-ref').textContent = R.reference;
  $('d-sub').textContent = paye
    ? 'Résultat réglé : vous pouvez le télécharger.'
    : 'Les valeurs sont consultables ; le téléchargement est disponible après paiement.';
  $('d-action').innerHTML = paye
    ? `<a class="btn" href="/api/patient/resultats/${R.id}/pdf" download>Télécharger le PDF</a>`
    : '<button type="button" class="btn" id="open-pay">Payer pour télécharger</button>';
  if (!paye) $('open-pay').addEventListener('click', openPay);

  const p = R.paiement;
  $('d-info').innerHTML = `
    <dt>Analyse</dt><dd>${esc(R.analyse.nom)} <small>(${esc(R.analyse.categorie)})</small></dd>
    <dt>Prélèvement</dt><dd>${esc(R.datePrelevement)}</dd>
    <dt>Résultat daté du</dt><dd>${esc(R.dateResultat)}</dd>
    <dt>Médecin validateur</dt><dd>${esc(R.validePar ? R.validePar.nom : '—')}</dd>
    <dt>Paiement</dt><dd>${payBadge(paye)} ${p
      ? `${esc(p.montant)} ${esc(p.devise)} · ${esc(p.carte)} · ${esc(fmtDate(p.date))} <small>Réf. ${esc(p.reference)}</small>`
      : `${esc(R.montant)} TND`}</dd>`;

  $('d-lignes').innerHTML = (R.lignes || []).map((l) => {
    const ko = l.indicateur && l.indicateur !== 'normal';
    return `<tr>
      <td>${esc(l.parametre)}</td>
      <td class="val${ko ? ' ko' : ''}">${esc(l.valeur)}</td>
      <td>${esc(l.unite)}</td>
      <td>${esc(l.reference)}</td>
      <td class="${ko ? 'ind ko' : ''}">${esc(l.indicateur || 'normal')}</td>
    </tr>`;
  }).join('');
  $('d-comment').innerHTML = R.commentaire ? `<b>Commentaire du laboratoire :</b> ${esc(R.commentaire)}` : '';

  const d = R.document;
  $('d-doc').innerHTML = !d
    ? '<p class="muted">Aucun document joint.</p>'
    : `<div class="doc-row"><span>${esc(d.nom)} <small class="sub" style="display:inline">· ${esc(fmtSize(d.taille))}</small></span>${paye
      ? `<a class="btn sm" href="/api/patient/resultats/${R.id}/document" download>Télécharger</a>`
      : '<span class="muted">Disponible après paiement</span>'}</div>`;

  const hist = [];
  if (R.valideLe) hist.push(`<li><b>Validé</b>${R.validePar ? ' par ' + esc(R.validePar.nom) : ''} · ${esc(fmtDate(R.valideLe))}</li>`);
  if (R.publieLe) hist.push(`<li><b>Publié</b> · ${esc(fmtDate(R.publieLe))}</li>`);
  if (p) hist.push(`<li><b>Payé</b> · ${esc(fmtDate(p.date))}</li>`);
  $('d-hist').innerHTML = hist.join('');
}

/* ---------- Fenêtre « Régler mon résultat » ---------- */
function openPay() {
  $('pay-form').reset();
  $('p-titulaire').value = ME ? ME.nom : '';
  $('pay-sub').textContent = `Montant à régler : ${R.montant} TND — ${R.analyse.nom}`;
  showAlert('pay-alert', 'err', []);
  markBad([]);
  $('pay-modal').classList.add('open');
  $('p-numero').focus();
}
function closePay() { $('pay-modal').classList.remove('open'); }

function validatePay() {
  const errors = [], bad = [];
  if (val('p-titulaire').length < 2) { errors.push('Nom du titulaire requis'); bad.push('p-titulaire'); }
  const num = $('p-numero').value.replace(/[\s-]/g, '');
  if (!/^\d{13,19}$/.test(num) || !luhn(num)) { errors.push('Numéro de carte invalide'); bad.push('p-numero'); }
  const m = /^(\d{2})\/(\d{2})$/.exec(val('p-exp'));
  if (!m || +m[1] < 1 || +m[1] > 12) { errors.push("Date d'expiration invalide (MM/AA)"); bad.push('p-exp'); }
  else {
    const now = new Date(), yy = now.getFullYear() % 100, mm = now.getMonth() + 1;
    if (+m[2] < yy || (+m[2] === yy && +m[1] < mm)) { errors.push('Carte expirée'); bad.push('p-exp'); }
  }
  if (!/^\d{3,4}$/.test(val('p-cvc'))) { errors.push('CVC invalide'); bad.push('p-cvc'); }
  return { errors, bad };
}

function initPayModal() {
  // Confort de saisie (n'est pas une validation) : groupes de 4 chiffres, « / » automatique
  $('p-numero').addEventListener('input', (e) => {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 19).replace(/(\d{4})(?=\d)/g, '$1 ');
  });
  $('p-exp').addEventListener('input', (e) => {
    const d = e.target.value.replace(/\D/g, '').slice(0, 4);
    e.target.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
  });
  $('p-cvc').addEventListener('input', (e) => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4); });

  $('pay-cancel').addEventListener('click', closePay);
  $('pay-modal').addEventListener('click', (e) => { if (e.target === $('pay-modal')) closePay(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePay(); });

  $('pay-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const { errors, bad } = validatePay();
    markBad(bad);
    showAlert('pay-alert', 'err', errors);
    if (errors.length) return;
    const btn = $('pay-btn');
    btn.disabled = true;
    try {
      await api(`/api/patient/resultats/${R.id}/payer`, jsonOpts('POST', {
        titulaire: val('p-titulaire'), numero: $('p-numero').value, expiration: val('p-exp'), cvc: val('p-cvc')
      }));
      closePay();
      R = await api(`/api/patient/resultats/${R.id}`);
      bannerPaiement = true;
      renderDetails();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (err) {
      if (err.status === 409) {                         // déjà réglé : on recharge l'état
        closePay();
        R = await api(`/api/patient/resultats/${R.id}`);
        renderDetails();
      } else showAlert('pay-alert', 'err', err.errors || err.message);
    } finally {
      btn.disabled = false;
    }
  });
}

async function initDetails() {
  renderShell('resultats');
  initPayModal();
  const id = Number(qs('id'));
  if (!id) { showAlert('page-alert', 'err', 'Résultat introuvable'); $('details').hidden = true; return; }
  try {
    R = await api(`/api/patient/resultats/${id}`);
  } catch (e) {
    $('details').hidden = true;
    showAlert('page-alert', 'err', e.status === 404 ? 'Résultat introuvable' : (e.errors || e.message));
    return;
  }
  renderDetails();
  if (qs('payer') === '1' && !R.paiement) openPay();
}

/* ============================================================
   Démarrage
   ============================================================ */
(async () => {
  const page = document.body.dataset.page;
  await loadMe();

  if (page === 'inscription') {
    if (ME) { location.replace('resultats.html'); return; }
    initRegister();
    return;
  }
  if (!ME) {                                            // pas connecté
    if (page === 'resultats') initLogin();
    else location.replace('resultats.html');
    return;
  }
  if (page === 'resultats') { $('login').hidden = true; initList(); }
  else if (page === 'details') initDetails();
})();
