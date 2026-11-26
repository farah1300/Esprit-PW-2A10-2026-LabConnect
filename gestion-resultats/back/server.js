'use strict';
/* ============================================================
   LabConnect – serveur unique (Express)
   Structure du back : server.js, admin/, data/, package.json
   ============================================================ */
const express = require('express');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

/* ============================================================
   Configuration
   ============================================================ */
const config = (() => {
  /* Configuration unique (variables d'environnement) : évite de redéfinir
     les mêmes constantes dans server.js et routes/comptes.js. */
  const DEFAULT_ADMIN_PASSWORD = 'admin123';

  return {
    PORT: process.env.PORT || 3000,
    ADMIN_USER: process.env.ADMIN_USER || 'admin',
    ADMIN_PASSWORD: process.env.ADMIN_PASSWORD || DEFAULT_ADMIN_PASSWORD,   // à changer !
    DEFAULT_ADMIN_PASSWORD
  };
})();

/* ============================================================
   Réponses d'erreur HTTP
   ============================================================ */
const httpLib = (() => {
  /* Réponses d'erreur communes : { ok:false, errors:[…], error:"première erreur" }
     (même format partout, le front lit `error` ou `errors`). */
  function sendErrors(res, errors, status = 400) {
    const list = [].concat(errors || []);
    return res.status(status).json({ ok: false, errors: list, error: list[0] });
  }

  return { sendErrors };
})();

/* ============================================================
   Stockage JSON
   ============================================================ */
const store = (() => {
  /* ============================================================
     Stockage JSON partagé (front + back lisent les mêmes fichiers)
     Écriture atomique : fichier temporaire puis rename.
     ============================================================ */

  const DATA_DIR = path.join(__dirname, 'data');
  const DOC_DIR = path.join(DATA_DIR, 'documents');
  fs.mkdirSync(DOC_DIR, { recursive: true });

  function file(name) {
    return path.join(DATA_DIR, name + '.json');
  }

  function read(name, fallback = []) {
    const f = file(name);
    if (!fs.existsSync(f)) {
      fs.writeFileSync(f, JSON.stringify(fallback, null, 2));
      return JSON.parse(JSON.stringify(fallback));
    }
    try {
      return JSON.parse(fs.readFileSync(f, 'utf8'));
    } catch (e) {
      throw new Error('Fichier de données corrompu : ' + name + '.json');
    }
  }

  function write(name, rows) {
    const f = file(name);
    const tmp = f + '.tmp';
    fs.writeFileSync(tmp, JSON.stringify(rows, null, 2));
    fs.renameSync(tmp, f);
  }

  const nextId = (rows) => (rows.length ? Math.max(...rows.map((r) => r.id)) + 1 : 1);

  return { read, write, nextId, DATA_DIR, DOC_DIR };
})();

/* ============================================================
   Validation
   ============================================================ */
const validate = (() => {
  /* ============================================================
     Validation serveur des saisies (la même logique existe côté
     client dans front/js/resultats.js et admin/resultat-form.js,
     mais seule celle-ci fait foi).
     Chaque fonction renvoie { errors: [], value: {...nettoyé} }.
     ============================================================ */

  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const PHONE_RE = /^[0-9+().\s-]{6,30}$/;
  const DATE_RE = /^\d{4}-\d{2}-\d{2}$/;
  const INDICATEURS = ['normal', 'bas', 'élevé', 'anormal'];

  const str = (v, max) => String(v ?? '').trim().slice(0, max);

  function localToday() {
    const d = new Date();
    return [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
  }

  function isRealDate(s) {
    if (!DATE_RE.test(s)) return false;
    const d = new Date(s + 'T00:00:00');
    return !isNaN(d) && d.getFullYear() === +s.slice(0, 4) && d.getMonth() + 1 === +s.slice(5, 7) && d.getDate() === +s.slice(8, 10);
  }

  const num = (v) => {
    if (v === '' || v === null || v === undefined) return null;
    const n = Number(String(v).replace(',', '.'));
    return Number.isFinite(n) ? n : NaN;
  };

  /* ---------- Résultat ---------- */
  function resultat(body, { patients, analyses }) {
    const errors = [];
    const b = body || {};
    const patientId = Number(b.patientId);
    const analyseId = Number(b.analyseId);
    const datePrelevement = str(b.datePrelevement, 10);
    const dateResultat = str(b.dateResultat, 10);
    const commentaire = str(b.commentaire, 1000);
    const today = localToday();

    if (!patients.some((p) => p.id === patientId)) errors.push('Patient requis (introuvable)');
    if (!analyses.some((a) => a.id === analyseId)) errors.push('Analyse requise (introuvable)');

    if (!isRealDate(datePrelevement)) errors.push('Date de prélèvement invalide');
    else if (datePrelevement > today) errors.push('La date de prélèvement ne peut pas être dans le futur');

    if (!isRealDate(dateResultat)) errors.push('Date du résultat invalide');
    else if (dateResultat > today) errors.push('La date du résultat ne peut pas être dans le futur');
    else if (isRealDate(datePrelevement) && dateResultat < datePrelevement) errors.push('Le résultat ne peut pas précéder le prélèvement');

    const rawLines = Array.isArray(b.lignes) ? b.lignes : [];
    if (rawLines.length < 1) errors.push('Au moins un paramètre est requis');
    if (rawLines.length > 30) errors.push('30 paramètres maximum');

    const lignes = rawLines.slice(0, 30).map((l, i) => {
      const n = i + 1;
      const parametre = str(l && l.parametre, 80);
      const valeur = str(l && l.valeur, 40);
      const unite = str(l && l.unite, 20);
      const refMin = num(l && l.refMin);
      const refMax = num(l && l.refMax);
      if (parametre.length < 2) errors.push(`Ligne ${n} : nom du paramètre requis`);
      if (!valeur) errors.push(`Ligne ${n} : valeur requise`);
      if (Number.isNaN(refMin) || Number.isNaN(refMax)) errors.push(`Ligne ${n} : bornes de référence invalides`);
      else if (refMin !== null && refMax !== null && refMin > refMax) errors.push(`Ligne ${n} : borne min > borne max`);

      // Indicateur : calculé automatiquement si la valeur est numérique et les bornes connues
      const v = num(valeur);
      let indicateur = INDICATEURS.includes(l && l.indicateur) ? l.indicateur : 'normal';
      if (v !== null && !Number.isNaN(v)) {
        if (refMin !== null && !Number.isNaN(refMin) && v < refMin) indicateur = 'bas';
        else if (refMax !== null && !Number.isNaN(refMax) && v > refMax) indicateur = 'élevé';
        else if ((refMin !== null && !Number.isNaN(refMin)) || (refMax !== null && !Number.isNaN(refMax))) indicateur = 'normal';
      }
      const hasMin = refMin !== null && !Number.isNaN(refMin);
      const hasMax = refMax !== null && !Number.isNaN(refMax);
      const reference = hasMin && hasMax ? `${refMin} - ${refMax}` : hasMin ? `≥ ${refMin}` : hasMax ? `≤ ${refMax}` : '';
      return { parametre, valeur, unite, refMin: hasMin ? refMin : null, refMax: hasMax ? refMax : null, reference, indicateur };
    });

    return { errors, value: { patientId, analyseId, datePrelevement, dateResultat, commentaire, lignes } };
  }

  /* ---------- Patient ---------- */
  function patient(body, { needPassword }) {
    const errors = [];
    const b = body || {};
    const nom = str(b.nom, 100);
    const email = str(b.email, 150).toLowerCase();
    const telephone = str(b.telephone, 30);
    const password = String(b.password ?? '');
    if (nom.length < 2) errors.push('Nom invalide (2 caractères minimum)');
    if (!EMAIL_RE.test(email)) errors.push('E-mail invalide');
    if (telephone && !PHONE_RE.test(telephone)) errors.push('Téléphone invalide');
    if (needPassword || password) {
      if (password.length < 8 || !/[A-Za-z]/.test(password) || !/\d/.test(password)) {
        errors.push('Mot de passe : 8 caractères minimum, avec lettres et chiffres');
      }
    }
    return { errors, value: { nom, email, telephone, password } };
  }

  /* ---------- Rendez-vous (formulaire public de l'accueil) ---------- */
  function rendezvous(body) {
    const errors = [];
    const b = body || {};
    const name = str(b.name, 100);
    const email = str(b.email, 150);
    const date = str(b.date, 10);
    const labo = str(b.labo, 100);
    const exam = str(b.exam, 100);
    const phone = str(b.phone, 30);
    const message = str(b.message, 1000);

    if (name.length < 2) errors.push('Nom invalide');
    if (!EMAIL_RE.test(email)) errors.push('E-mail invalide');
    if (phone && !PHONE_RE.test(phone)) errors.push('Téléphone invalide');
    if (!isRealDate(date)) errors.push('Date invalide');
    else if (date < localToday()) errors.push('La date est déjà passée');
    if (!labo) errors.push('Laboratoire requis');
    if (!exam) errors.push("Type d'examen requis");

    return { errors, value: { name, email, date, labo, exam, phone, domicile: !!b.domicile, message } };
  }

  /* ---------- Paiement (simulation – on ne garde JAMAIS le numéro complet) ---------- */
  function luhn(digits) {
    let sum = 0, alt = false;
    for (let i = digits.length - 1; i >= 0; i--) {
      let n = +digits[i];
      if (alt) { n *= 2; if (n > 9) n -= 9; }
      sum += n; alt = !alt;
    }
    return sum % 10 === 0;
  }

  function paiement(body) {
    const errors = [];
    const b = body || {};
    const titulaire = str(b.titulaire, 80);
    const numero = String(b.numero || '').replace(/[\s-]/g, '');
    const expiration = str(b.expiration, 5);
    const cvc = str(b.cvc, 4);
    if (titulaire.length < 2) errors.push('Nom du titulaire requis');
    if (!/^\d{13,19}$/.test(numero) || !luhn(numero)) errors.push('Numéro de carte invalide');
    const m = /^(\d{2})\/(\d{2})$/.exec(expiration);
    if (!m || +m[1] < 1 || +m[1] > 12) errors.push("Date d'expiration invalide (MM/AA)");
    else {
      const now = new Date();
      const yy = now.getFullYear() % 100, mm = now.getMonth() + 1;
      if (+m[2] < yy || (+m[2] === yy && +m[1] < mm)) errors.push('Carte expirée');
    }
    if (!/^\d{3,4}$/.test(cvc)) errors.push('CVC invalide');
    return { errors, value: { titulaire, numero, expiration } };
  }

  return { resultat, patient, rendezvous, paiement, localToday, isRealDate, EMAIL_RE };
})();

/* ============================================================
   Authentification et rôles
   ============================================================ */
const auth = (() => {
  /* ============================================================
     Authentification : mots de passe (scrypt) + sessions (cookie signé)
     Deux espaces séparés :
       - lc_admin   : personnel (admin, médecin, technicien)
       - lc_patient : patients
     ============================================================ */

  const SECRET = process.env.SECRET || crypto.randomBytes(32).toString('hex');

  /* ---------- Mots de passe ---------- */
  function hashPassword(password) {
    const salt = crypto.randomBytes(16).toString('hex');
    const hash = crypto.scryptSync(String(password), salt, 64).toString('hex');
    return salt + ':' + hash;
  }

  function checkPassword(password, stored) {
    if (!stored || !stored.includes(':')) return false;
    const [salt, hash] = stored.split(':');
    const test = crypto.scryptSync(String(password), salt, 64);
    const ref = Buffer.from(hash, 'hex');
    return ref.length === test.length && crypto.timingSafeEqual(ref, test);
  }

  /* ---------- Sessions ---------- */
  const sign = (v) => crypto.createHmac('sha256', SECRET).update(v).digest('hex');

  const safeEq = (a, b) => {
    const x = Buffer.from(String(a)), y = Buffer.from(String(b));
    return x.length === y.length && crypto.timingSafeEqual(x, y);
  };

  function makeToken(payload, hours) {
    const body = Buffer.from(JSON.stringify({ ...payload, exp: Date.now() + hours * 3600 * 1000 })).toString('base64url');
    return body + '.' + sign(body);
  }

  function readToken(req, cookieName) {
    const re = new RegExp('(?:^|;\\s*)' + cookieName + '=([^;]+)');
    const m = re.exec(req.headers.cookie || '');
    if (!m) return null;
    const [body, sig] = decodeURIComponent(m[1]).split('.');
    if (!body || !sig || !safeEq(sig, sign(body))) return null;
    try {
      const data = JSON.parse(Buffer.from(body, 'base64url').toString('utf8'));
      return Date.now() > Number(data.exp) ? null : data;
    } catch (e) {
      return null;
    }
  }

  function setCookie(res, name, token, hours) {
    res.setHeader('Set-Cookie',
      `${name}=${encodeURIComponent(token)}; HttpOnly; SameSite=Strict; Path=/; Max-Age=${hours * 3600}`);
  }

  function clearCookie(res, name) {
    res.setHeader('Set-Cookie', `${name}=; HttpOnly; SameSite=Strict; Path=/; Max-Age=0`);
  }

  /* ---------- Anti brute-force simple (par IP + espace) ---------- */
  const attempts = {};
  function tooMany(key) {
    const now = Date.now();
    const a = (attempts[key] = (attempts[key] || []).filter((t) => now - t < 10 * 60 * 1000));
    return a.length >= 5;
  }
  function fail(key) {
    (attempts[key] = attempts[key] || []).push(Date.now());
  }
  function reset(key) {
    delete attempts[key];
  }

  /* ---------- Middlewares ---------- */
  // Personnel connecté (n'importe quel rôle)
  function staffSession(req) {
    const s = readToken(req, 'lc_admin');
    return s && s.role ? s : null;   // ancien cookie sans rôle => invalide, reconnexion
  }

  const requireStaff = (req, res, next) => {
    const s = staffSession(req);
    if (!s) return res.status(401).json({ ok: false, error: 'Non autorisé' });
    req.staff = s;
    next();
  };

  // Rôles autorisés (admin passe toujours)
  const requireRole = (...roles) => (req, res, next) => {
    const s = staffSession(req);
    if (!s) return res.status(401).json({ ok: false, error: 'Non autorisé' });
    if (s.role !== 'admin' && !roles.includes(s.role)) {
      return res.status(403).json({ ok: false, error: "Votre rôle ne permet pas cette action" });
    }
    req.staff = s;
    next();
  };

  const requirePatient = (req, res, next) => {
    const s = readToken(req, 'lc_patient');
    if (!s || !s.pid) return res.status(401).json({ ok: false, error: 'Connexion patient requise' });
    req.patient = s;
    next();
  };

  return {
    hashPassword, checkPassword, makeToken, readToken, setCookie, clearCookie,
    tooMany, fail, reset, safeEq, staffSession, requireStaff, requireRole, requirePatient
  };
})();

/* ============================================================
   Génération du PDF
   ============================================================ */
const pdf = (() => {
  /* ============================================================
     Mini générateur PDF (sans dépendance) – A4, texte + rectangles.
     Suffisant pour le compte rendu de résultat.
     ============================================================ */

  const W = 595.28, H = 841.89;   // A4 en points

  // Texte -> octets WinAnsi (latin1 + quelques signes de cp1252)
  function enc(str) {
    const map = { '–': '-', '—': '-', '’': "'", '‘': "'", '“': '"', '”': '"', '…': '...', '≥': '>=', '≤': '<=', '€': 'EUR' };
    const s = String(str ?? '').replace(/[–—’‘“”…≥≤€]/g, (c) => map[c]).replace(/[^\x09\x0A\x0D\x20-\xFF]/g, '?');
    return s.replace(/\\/g, '\\\\').replace(/\(/g, '\\(').replace(/\)/g, '\\)');
  }

  const rgb = (hex) => {
    const n = parseInt(hex.replace('#', ''), 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255].map((v) => (v / 255).toFixed(3)).join(' ');
  };

  class Page {
    constructor() { this.ops = []; }
    rect(x, top, w, h, fill) {
      this.ops.push(`${rgb(fill)} rg ${x.toFixed(2)} ${(H - top - h).toFixed(2)} ${w.toFixed(2)} ${h.toFixed(2)} re f`);
    }
    hline(x1, x2, top, color = '#e4d9ea') {
      this.ops.push(`${rgb(color)} RG 0.6 w ${x1} ${(H - top).toFixed(2)} m ${x2} ${(H - top).toFixed(2)} l S`);
    }
    text(x, top, str, { size = 10, bold = false, color = '#3b2a42' } = {}) {
      this.ops.push(`BT /${bold ? 'F2' : 'F1'} ${size} Tf ${rgb(color)} rg ${x.toFixed(2)} ${(H - top - size).toFixed(2)} Td (${enc(str)}) Tj ET`);
    }
  }

  function wrap(str, maxChars) {
    const words = String(str ?? '').split(/\s+/);
    const lines = [];
    let cur = '';
    for (const w of words) {
      if ((cur + ' ' + w).trim().length > maxChars) { if (cur) lines.push(cur); cur = w; }
      else cur = (cur + ' ' + w).trim();
    }
    if (cur) lines.push(cur);
    return lines.length ? lines : [''];
  }

  function build(pages) {
    const objs = [];
    const add = (s) => { objs.push(s); return objs.length; };
    add('<< /Type /Catalog /Pages 2 0 R >>');           // 1
    add('PLACEHOLDER');                                   // 2 (Pages)
    const f1 = add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
    const f2 = add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');
    const kids = [];
    for (const p of pages) {
      const content = p.ops.join('\n');
      const c = add(`<< /Length ${Buffer.byteLength(content, 'latin1')} >>\nstream\n${content}\nendstream`);
      const pg = add(`<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${W} ${H}] /Contents ${c} 0 R /Resources << /Font << /F1 ${f1} 0 R /F2 ${f2} 0 R >> >> >>`);
      kids.push(pg + ' 0 R');
    }
    objs[1] = `<< /Type /Pages /Kids [${kids.join(' ')}] /Count ${kids.length} >>`;

    let out = '%PDF-1.4\n';
    const offsets = [];
    objs.forEach((o, i) => { offsets.push(Buffer.byteLength(out, 'latin1')); out += `${i + 1} 0 obj\n${o}\nendobj\n`; });
    const xref = Buffer.byteLength(out, 'latin1');
    out += `xref\n0 ${objs.length + 1}\n0000000000 65535 f \n`;
    offsets.forEach((o) => { out += String(o).padStart(10, '0') + ' 00000 n \n'; });
    out += `trailer\n<< /Size ${objs.length + 1} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF`;
    return Buffer.from(out, 'latin1');
  }

  /* ---------- Compte rendu de résultat ---------- */
  function resultatPdf(r) {
    const VIOLET = '#4A1E56', BLEU = '#A6CFF7', MUTED = '#666666';
    const pages = [];
    let p, y;

    const header = () => {
      p = new Page(); pages.push(p);
      p.rect(0, 0, W, 78, VIOLET);
      p.text(40, 24, 'LabConnect', { size: 22, bold: true, color: '#ffffff' });
      p.text(40, 52, 'Compte rendu de résultat d\'analyse', { size: 10, color: BLEU });
      p.text(W - 200, 30, 'Réf. ' + r.reference, { size: 10, bold: true, color: '#ffffff' });
      p.text(W - 200, 46, 'Édité le ' + new Date().toLocaleDateString('fr-FR'), { size: 9, color: BLEU });
      y = 108;
    };
    const footer = (pg, i, n) => {
      pg.hline(40, W - 40, H - 52);
      pg.text(40, H - 44, 'LabConnect – Document confidentiel. Ne remplace pas l\'avis de votre médecin.', { size: 8, color: MUTED });
      pg.text(W - 90, H - 44, `Page ${i + 1}/${n}`, { size: 8, color: MUTED });
    };

    header();
    p.text(40, y, r.analyse.nom, { size: 16, bold: true, color: VIOLET }); y += 26;

    const info = [
      ['Patient', r.patient.nom], ['E-mail', r.patient.email || '-'],
      ['Analyse', r.analyse.nom + ' (' + r.analyse.categorie + ')'],
      ['Date de prélèvement', r.datePrelevement], ['Date du résultat', r.dateResultat],
      ['Médecin responsable', r.validePar ? r.validePar.nom : 'En attente de validation'],
      ['Statut', r.statut]
    ];
    p.rect(40, y - 6, W - 80, info.length * 18 + 12, '#faf7fb');
    info.forEach(([k, v]) => {
      p.text(54, y, k, { size: 9, color: MUTED });
      p.text(200, y, v, { size: 10, bold: true });
      y += 18;
    });
    y += 24;

    // Tableau des paramètres
    const cols = [40, 230, 330, 400, 490];
    const th = () => {
      p.rect(40, y - 5, W - 80, 20, VIOLET);
      ['Paramètre', 'Valeur', 'Unité', 'Référence', 'Indicateur'].forEach((t, i) =>
        p.text(cols[i] + 6, y, t, { size: 9, bold: true, color: '#ffffff' }));
      y += 24;
    };
    th();
    r.lignes.forEach((l, i) => {
      if (y > H - 110) { header(); th(); }
      if (i % 2 === 0) p.rect(40, y - 5, W - 80, 20, '#f6f0f8');
      const abnormal = l.indicateur && l.indicateur !== 'normal';
      p.text(cols[0] + 6, y, l.parametre, { size: 10 });
      p.text(cols[1] + 6, y, String(l.valeur), { size: 10, bold: true, color: abnormal ? '#c0392b' : '#3b2a42' });
      p.text(cols[2] + 6, y, l.unite || '', { size: 10 });
      p.text(cols[3] + 6, y, l.reference || '-', { size: 9, color: MUTED });
      p.text(cols[4] + 6, y, l.indicateur || '-', { size: 9, bold: abnormal, color: abnormal ? '#c0392b' : MUTED });
      y += 20;
    });

    if (r.commentaire) {
      y += 14;
      if (y > H - 130) header();
      p.text(40, y, 'Commentaire du médecin', { size: 11, bold: true, color: VIOLET }); y += 20;
      wrap(r.commentaire, 95).forEach((ln) => {
        if (y > H - 80) header();
        p.text(40, y, ln, { size: 10 }); y += 15;
      });
    }

    pages.forEach((pg, i) => footer(pg, i, pages.length));
    return build(pages);
  }

  return { resultatPdf };
})();

/* ============================================================
   Modèle Analyse
   ============================================================ */
const Analyse = (() => {
  /* Catalogue des analyses (modèle partagé front/back). */

  const SEED = [
    { id: 1, nom: 'Numération formule sanguine (NFS)', categorie: 'Hématologie', prix: 25, parametres: [
      { parametre: 'Hémoglobine', unite: 'g/dL', refMin: 12, refMax: 16 },
      { parametre: 'Globules blancs', unite: '/mm³', refMin: 4000, refMax: 10000 },
      { parametre: 'Plaquettes', unite: 'G/L', refMin: 150, refMax: 400 } ] },
    { id: 2, nom: 'Glycémie à jeun', categorie: 'Biochimie', prix: 8, parametres: [
      { parametre: 'Glycémie', unite: 'g/L', refMin: 0.7, refMax: 1.1 } ] },
    { id: 3, nom: 'Bilan lipidique', categorie: 'Biochimie', prix: 30, parametres: [
      { parametre: 'Cholestérol total', unite: 'g/L', refMin: 1.2, refMax: 2 },
      { parametre: 'HDL', unite: 'g/L', refMin: 0.4, refMax: 0.8 },
      { parametre: 'LDL', unite: 'g/L', refMin: 0, refMax: 1.6 },
      { parametre: 'Triglycérides', unite: 'g/L', refMin: 0.35, refMax: 1.5 } ] },
    { id: 4, nom: 'TSH', categorie: 'Hormonologie', prix: 35, parametres: [
      { parametre: 'TSH', unite: 'mUI/L', refMin: 0.4, refMax: 4 } ] },
    { id: 5, nom: 'Créatinine', categorie: 'Biochimie', prix: 12, parametres: [
      { parametre: 'Créatinine', unite: 'µmol/L', refMin: 45, refMax: 104 } ] },
    { id: 6, nom: 'CRP', categorie: 'Immunologie', prix: 15, parametres: [
      { parametre: 'CRP', unite: 'mg/L', refMin: 0, refMax: 5 } ] }
  ];

  const all = () => store.read('analyses', SEED);
  const find = (id) => all().find((a) => a.id === Number(id)) || null;

  return { all, find };
})();

/* ============================================================
   Modèle Médecin
   ============================================================ */
const Medecin = (() => {
  /* Entité « medecin » : personnel de santé (rôles : medecin, technicien). */

  function seed() {
    return [
      { id: 1, username: 'dr.bensalah', nom: 'Dr Amel Ben Salah', specialite: 'Biologiste médicale', role: 'medecin',
        passwordHash: auth.hashPassword(process.env.MEDECIN_PASSWORD || 'medecin123') },
      { id: 2, username: 'tech.amine', nom: 'Amine Trabelsi', specialite: 'Technicien de laboratoire', role: 'technicien',
        passwordHash: auth.hashPassword(process.env.TECH_PASSWORD || 'technicien123') }
    ];
  }

  const all = () => store.read('medecins', seed());
  const find = (id) => all().find((m) => m.id === Number(id)) || null;
  const findByUsername = (u) => all().find((m) => m.username === String(u).toLowerCase()) || null;
  const publicView = (m) => m && ({ id: m.id, nom: m.nom, specialite: m.specialite, role: m.role });

  return { all, find, findByUsername, publicView };
})();

/* ============================================================
   Modèle Patient
   ============================================================ */
const Patient = (() => {
  /* Entité Patient : lue par le back office, utilisée par le front pour la connexion. */

  function seed() {
    return [{ id: 1, nom: 'Sarra Ben Ali', email: 'sarra.benali@example.com', telephone: '20 123 456',
      passwordHash: auth.hashPassword(process.env.PATIENT_PASSWORD || 'patient123'), createdAt: new Date().toISOString() }];
  }

  const all = () => store.read('patients', seed());
  const find = (id) => all().find((p) => p.id === Number(id)) || null;
  const findByEmail = (e) => all().find((p) => p.email === String(e).trim().toLowerCase()) || null;
  const publicView = (p) => p && ({ id: p.id, nom: p.nom, email: p.email, telephone: p.telephone, actif: !!p.passwordHash });

  /* Création par le personnel (sans mot de passe) ou inscription du patient.
     Si le personnel a déjà créé le dossier, l'inscription « réclame » ce dossier. */
  function register(body, { fromStaff = false } = {}) {
    const { errors, value } = validate.patient(body, { needPassword: !fromStaff });
    if (errors.length) return { errors };
    const rows = all();
    const existing = rows.find((p) => p.email === value.email);
    if (existing) {
      if (fromStaff || existing.passwordHash) return { errors: ['Un patient avec cet e-mail existe déjà'] };
      existing.nom = value.nom;
      existing.telephone = value.telephone || existing.telephone;
      existing.passwordHash = auth.hashPassword(value.password);
      store.write('patients', rows);
      return { patient: existing };
    }
    const p = {
      id: store.nextId(rows), nom: value.nom, email: value.email, telephone: value.telephone,
      passwordHash: value.password ? auth.hashPassword(value.password) : null, createdAt: new Date().toISOString()
    };
    rows.push(p);
    store.write('patients', rows);
    return { patient: p };
  }

  function verify(email, password) {
    const p = findByEmail(email);
    return p && p.passwordHash && auth.checkPassword(password, p.passwordHash) ? p : null;
  }

  return { all, find, findByEmail, publicView, register, verify };
})();

/* ============================================================
   Modèle DocumentResultat
   ============================================================ */
const DocumentResultat = (() => {
  /* ============================================================
     Document joint à un résultat (PDF scanné, image du compte rendu…)
     Vérifications avant écriture :
       1. taille max 5 Mo, non vide
       2. extension dans la liste blanche
       3. type MIME déclaré cohérent avec l'extension
       4. signature binaire (« magic bytes ») cohérente avec le type
     Stockage : nom aléatoire, hors du dossier public ; le nom d'origine
     n'est jamais utilisé comme chemin (anti path-traversal).
     ============================================================ */

  const MAX_SIZE = 5 * 1024 * 1024;
  const TYPES = {
    pdf: { mime: 'application/pdf', magic: (b) => b.slice(0, 5).toString('latin1') === '%PDF-' },
    png: { mime: 'image/png', magic: (b) => b.slice(0, 8).equals(Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a])) },
    jpg: { mime: 'image/jpeg', magic: (b) => b[0] === 0xff && b[1] === 0xd8 && b[2] === 0xff },
    jpeg: { mime: 'image/jpeg', magic: (b) => b[0] === 0xff && b[1] === 0xd8 && b[2] === 0xff }
  };

  const all = () => store.read('documents', []);
  const forResultat = (resultatId) => all().find((d) => d.resultatId === Number(resultatId)) || null;
  const publicView = (d) => d && ({ id: d.id, nom: d.originalName, mime: d.mime, taille: d.size, date: d.uploadedAt });
  const filePath = (d) => path.join(store.DOC_DIR, d.storedName);

  function verify(buffer, originalName, declaredMime) {
    if (!Buffer.isBuffer(buffer) || buffer.length === 0) return 'Fichier vide ou absent';
    if (buffer.length > MAX_SIZE) return 'Fichier trop volumineux (5 Mo maximum)';
    const ext = path.extname(String(originalName || '')).slice(1).toLowerCase();
    const type = TYPES[ext];
    if (!type) return 'Type non autorisé (PDF, PNG ou JPG uniquement)';
    if (String(declaredMime || '').split(';')[0].trim().toLowerCase() !== type.mime) return 'Le type MIME ne correspond pas à l\'extension';
    if (!type.magic(buffer)) return 'Le contenu du fichier ne correspond pas à son extension';
    return null;
  }

  function attach(resultatId, buffer, originalName, declaredMime, staffName) {
    const error = verify(buffer, originalName, declaredMime);
    if (error) return { error };
    const ext = path.extname(originalName).slice(1).toLowerCase();
    const rows = all();

    const old = rows.find((d) => d.resultatId === Number(resultatId));   // un seul document par résultat : remplacement
    if (old) { try { fs.unlinkSync(filePath(old)); } catch (e) { /* déjà absent */ } }

    const storedName = crypto.randomBytes(16).toString('hex') + '.' + ext;
    fs.writeFileSync(path.join(store.DOC_DIR, storedName), buffer, { mode: 0o600 });
    const doc = {
      id: old ? old.id : store.nextId(rows),
      resultatId: Number(resultatId),
      originalName: path.basename(originalName).replace(/[^\w.\- àâäéèêëîïôöùûüç]/gi, '_').slice(0, 120),
      storedName, mime: TYPES[ext].mime, size: buffer.length,
      uploadedBy: staffName, uploadedAt: new Date().toISOString()
    };
    store.write('documents', old ? rows.map((d) => (d.id === old.id ? doc : d)) : rows.concat(doc));
    return { document: doc };
  }

  function removeForResultat(resultatId) {
    const d = forResultat(resultatId);
    if (!d) return false;
    try { fs.unlinkSync(filePath(d)); } catch (e) { /* déjà absent */ }
    store.write('documents', all().filter((x) => x.id !== d.id));
    return true;
  }

  return { MAX_SIZE, all, forResultat, publicView, filePath, verify, attach, removeForResultat };
})();

/* ============================================================
   Modèle Paiement
   ============================================================ */
const Paiement = (() => {
  /* Paiement en ligne – SIMULATION (aucune banque réelle).
     On ne conserve que les 4 derniers chiffres et la marque de la carte. */

  const all = () => store.read('paiements', []);
  const forResultat = (resultatId) => all().find((p) => p.resultatId === Number(resultatId) && p.statut === 'payé') || null;

  function brand(n) {
    if (/^4/.test(n)) return 'Visa';
    if (/^(5[1-5]|2[2-7])/.test(n)) return 'Mastercard';
    return 'Carte';
  }

  function pay(resultat, patientId, montant, body) {
    const { errors, value } = validate.paiement(body);
    if (errors.length) return { errors };
    // Carte de test refusée (comme les passerelles de paiement en mode « sandbox »)
    if (value.numero === '4000000000000002') return { errors: ['Paiement refusé par la banque (carte de test refusée)'], status: 402 };

    const rows = all();
    const p = {
      id: store.nextId(rows),
      reference: 'PAY-' + Date.now().toString(36).toUpperCase(),
      resultatId: resultat.id, patientId, montant, devise: 'TND',
      carte: brand(value.numero) + ' •••• ' + value.numero.slice(-4),
      statut: 'payé', date: new Date().toISOString()
    };
    rows.push(p);
    store.write('paiements', rows);
    return { paiement: p };
  }

  return { all, forResultat, pay };
})();

/* ============================================================
   Modèle Résultat
   ============================================================ */
const Resultat = (() => {
  /* ============================================================
     Modèle Resultat – données + règles métier
     Statuts : brouillon → validé → publié
       - modifier : interdit une fois publié ; une modification
         d'un résultat validé le repasse en brouillon
       - supprimer : interdit si publié
     ============================================================ */

  const STATUTS = ['brouillon', 'validé', 'publié'];

  const all = () => store.read('resultats', []);
  const find = (id) => all().find((r) => r.id === Number(id)) || null;

  /* Vue enrichie (patient, analyse, médecin, document, paiement) */
  function view(r) {
    if (!r) return null;
    const p = Patient.find(r.patientId);
    const a = Analyse.find(r.analyseId);
    const m = r.valideParId ? Medecin.find(r.valideParId) : null;
    const doc = DocumentResultat.forResultat(r.id);
    const pay = Paiement.forResultat(r.id);
    return {
      id: r.id, reference: r.reference, statut: r.statut,
      patient: p ? { id: p.id, nom: p.nom, email: p.email } : { id: r.patientId, nom: '(patient supprimé)', email: '' },
      analyse: a ? { id: a.id, nom: a.nom, categorie: a.categorie, prix: a.prix } : { id: r.analyseId, nom: '(analyse supprimée)', categorie: '', prix: 0 },
      datePrelevement: r.datePrelevement, dateResultat: r.dateResultat,
      lignes: r.lignes, commentaire: r.commentaire,
      validePar: m ? { id: m.id, nom: m.nom } : (r.valideParNom ? { nom: r.valideParNom } : null),
      valideLe: r.valideLe || null, publieLe: r.publieLe || null,
      creePar: r.creePar, createdAt: r.createdAt, updatedAt: r.updatedAt,
      document: DocumentResultat.publicView(doc),
      paiement: pay ? { reference: pay.reference, montant: pay.montant, devise: pay.devise, carte: pay.carte, date: pay.date } : null,
      montant: a ? a.prix : 0
    };
  }

  /* Recherche : texte (patient, e-mail, référence, analyse), statut, patient */
  function search({ q = '', statut = '', patientId = null, onlyPublished = false } = {}) {
    const needle = String(q).trim().toLowerCase();
    return all()
      .filter((r) => (!onlyPublished || r.statut === 'publié'))
      .filter((r) => (!statut || r.statut === statut))
      .filter((r) => (!patientId || r.patientId === Number(patientId)))
      .map(view)
      .filter((v) => !needle || [v.reference, v.patient.nom, v.patient.email, v.analyse.nom, v.analyse.categorie]
        .join(' ').toLowerCase().includes(needle))
      .sort((a, b) => b.id - a.id);
  }

  function reference(rows) {
    const year = new Date().getFullYear();
    return `RES-${year}-${String(store.nextId(rows)).padStart(4, '0')}`;
  }

  function create(body, staff) {
    const { errors, value } = validate.resultat(body, { patients: Patient.all(), analyses: Analyse.all() });
    if (errors.length) return { errors };
    const rows = all();
    const now = new Date().toISOString();
    const r = { id: store.nextId(rows), reference: reference(rows), ...value, statut: 'brouillon',
      creePar: staff.nom || staff.u, createdAt: now, updatedAt: now };
    rows.push(r);
    store.write('resultats', rows);
    return { resultat: r };
  }

  function update(id, body) {
    const rows = all();
    const r = rows.find((x) => x.id === Number(id));
    if (!r) return { notFound: true };
    if (r.statut === 'publié') return { errors: ['Un résultat publié ne peut pas être modifié : dépubliez-le d\'abord'], status: 409 };
    const { errors, value } = validate.resultat(body, { patients: Patient.all(), analyses: Analyse.all() });
    if (errors.length) return { errors };
    Object.assign(r, value, { updatedAt: new Date().toISOString() });
    if (r.statut === 'validé') {            // toute modification annule la validation
      r.statut = 'brouillon';
      delete r.valideParId; delete r.valideParNom; delete r.valideLe;
    }
    store.write('resultats', rows);
    return { resultat: r };
  }

  function remove(id) {
    const rows = all();
    const r = rows.find((x) => x.id === Number(id));
    if (!r) return { notFound: true };
    if (r.statut === 'publié') return { errors: ['Un résultat publié ne peut pas être supprimé : dépubliez-le d\'abord'], status: 409 };
    DocumentResultat.removeForResultat(r.id);
    store.write('resultats', rows.filter((x) => x.id !== r.id));
    return { ok: true };
  }

  /* Transitions d'état */
  function transition(id, from, to, patch) {
    const rows = all();
    const r = rows.find((x) => x.id === Number(id));
    if (!r) return { notFound: true };
    if (!from.includes(r.statut)) {
      return { errors: [`Action impossible : le résultat est « ${r.statut} »`], status: 409 };
    }
    r.statut = to;
    Object.assign(r, patch || {}, { updatedAt: new Date().toISOString() });
    store.write('resultats', rows);
    return { resultat: r };
  }

  const valider = (id, staff) => {
    const m = staff.mid ? Medecin.find(staff.mid) : null;
    return transition(id, ['brouillon'], 'validé', {
      valideParId: m ? m.id : null, valideParNom: staff.nom || staff.u, valideLe: new Date().toISOString()
    });
  };
  const publier = (id) => transition(id, ['validé'], 'publié', { publieLe: new Date().toISOString() });
  const depublier = (id) => transition(id, ['publié'], 'validé', { publieLe: null });

  function stats() {
    const rows = all();
    return { total: rows.length, ...Object.fromEntries(STATUTS.map((s) => [s, rows.filter((r) => r.statut === s).length])) };
  }

  return { STATUTS, all, find, view, search, create, update, remove, valider, publier, depublier, stats };
})();

/* ============================================================
   Contrôleur Résultat
   ============================================================ */
const ResultatController = (() => {
  /* ============================================================
     ResultatController – reçoit la requête HTTP, applique les règles
     d'accès, appelle les modèles, renvoie la réponse (JSON / PDF / fichier).
     ============================================================ */
  const { sendErrors } = httpLib;

  const fail = (res, out, fallbackStatus = 400) => {
    if (out.notFound) return res.status(404).json({ ok: false, error: 'Résultat introuvable' });
    return sendErrors(res, out.errors, out.status || fallbackStatus);
  };

  /* Un patient ne voit QUE ses résultats publiés. 404 (et non 403) pour ne pas
     révéler l'existence d'un résultat appartenant à quelqu'un d'autre. */
  function ownPublished(req) {
    const r = Resultat.find(req.params.id);
    if (!r || r.patientId !== Number(req.patient.pid) || r.statut !== 'publié') return null;
    return Resultat.view(r);
  }

  const sendPdf = (res, v) => {
    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Content-Disposition', `attachment; filename="${v.reference}.pdf"`);
    res.setHeader('Cache-Control', 'private, no-store');
    res.send(pdf.resultatPdf(v));
  };

  const sendDocument = (res, v) => {
    const d = DocumentResultat.forResultat(v.id);
    if (!d || !fs.existsSync(DocumentResultat.filePath(d))) return res.status(404).json({ ok: false, error: 'Aucun document joint' });
    res.setHeader('Content-Type', d.mime);
    res.setHeader('Content-Disposition', `attachment; filename="${v.reference}-${encodeURIComponent(d.originalName)}"`);
    res.setHeader('X-Content-Type-Options', 'nosniff');
    res.setHeader('Cache-Control', 'private, no-store');
    res.sendFile(DocumentResultat.filePath(d));
  };

  return {
    /* ---------- Personnel (back office) ---------- */
    referentiels(req, res) {
      res.json({
        analyses: Analyse.all(),
        patients: Patient.all().map(Patient.publicView),
        statuts: Resultat.STATUTS
      });
    },

    list(req, res) {
      const { q, statut, patientId } = req.query;
      res.json({ stats: Resultat.stats(), resultats: Resultat.search({ q, statut, patientId }) });
    },

    show(req, res) {
      const r = Resultat.find(req.params.id);
      if (!r) return fail(res, { notFound: true });
      res.json(Resultat.view(r));
    },

    create(req, res) {
      const out = Resultat.create(req.body, req.staff);
      if (out.errors) return fail(res, out);
      res.status(201).json({ ok: true, id: out.resultat.id });
    },

    update(req, res) {
      const out = Resultat.update(req.params.id, req.body);
      if (out.errors || out.notFound) return fail(res, out);
      res.json({ ok: true, id: out.resultat.id });
    },

    destroy(req, res) {
      const out = Resultat.remove(req.params.id);
      if (out.errors || out.notFound) return fail(res, out);
      res.json({ ok: true });
    },

    valider(req, res) {
      const out = Resultat.valider(req.params.id, req.staff);
      if (out.errors || out.notFound) return fail(res, out);
      res.json({ ok: true, statut: out.resultat.statut });
    },

    publier(req, res) {
      const out = Resultat.publier(req.params.id);
      if (out.errors || out.notFound) return fail(res, out);
      res.json({ ok: true, statut: out.resultat.statut });
    },

    depublier(req, res) {
      const out = Resultat.depublier(req.params.id);
      if (out.errors || out.notFound) return fail(res, out);
      res.json({ ok: true, statut: out.resultat.statut });
    },

    createPatient(req, res) {
      const out = Patient.register(req.body, { fromStaff: true });
      if (out.errors) return sendErrors(res, out.errors);
      res.status(201).json({ ok: true, patient: Patient.publicView(out.patient) });
    },

    uploadDocument(req, res) {
      const r = Resultat.find(req.params.id);
      if (!r) return fail(res, { notFound: true });
      if (r.statut === 'publié') return fail(res, { errors: ['Dépubliez le résultat avant de changer le document'], status: 409 });
      const out = DocumentResultat.attach(r.id, req.body, req.get('X-Filename'), req.get('Content-Type'), req.staff.nom || req.staff.u);
      if (out.error) return sendErrors(res, out.error);
      res.status(201).json({ ok: true, document: DocumentResultat.publicView(out.document) });
    },

    deleteDocument(req, res) {
      const r = Resultat.find(req.params.id);
      if (!r) return fail(res, { notFound: true });
      if (r.statut === 'publié') return fail(res, { errors: ['Dépubliez le résultat avant de supprimer le document'], status: 409 });
      res.json({ ok: DocumentResultat.removeForResultat(r.id) });
    },

    staffDocument(req, res) {
      const r = Resultat.find(req.params.id);
      if (!r) return fail(res, { notFound: true });
      sendDocument(res, Resultat.view(r));
    },

    staffPdf(req, res) {
      const r = Resultat.find(req.params.id);
      if (!r) return fail(res, { notFound: true });
      sendPdf(res, Resultat.view(r));
    },

    /* ---------- Patient (front office) ---------- */
    patientList(req, res) {
      const list = Resultat.search({ q: req.query.q, patientId: req.patient.pid, onlyPublished: true });
      res.json({ resultats: list });
    },

    patientShow(req, res) {
      const v = ownPublished(req);
      if (!v) return fail(res, { notFound: true });
      res.json(v);
    },

    patientPay(req, res) {
      const v = ownPublished(req);
      if (!v) return fail(res, { notFound: true });
      if (v.paiement) return res.status(409).json({ ok: false, error: 'Ce résultat est déjà réglé' });
      const out = Paiement.pay(v, req.patient.pid, v.montant, req.body);
      if (out.errors) return sendErrors(res, out.errors, out.status || 400);
      res.status(201).json({ ok: true, paiement: { reference: out.paiement.reference, montant: out.paiement.montant, devise: out.paiement.devise, carte: out.paiement.carte } });
    },

    /* Téléchargement : réservé au patient propriétaire ET après paiement */
    patientPdf(req, res) {
      const v = ownPublished(req);
      if (!v) return fail(res, { notFound: true });
      if (!v.paiement) return res.status(402).json({ ok: false, error: 'Paiement requis avant le téléchargement' });
      sendPdf(res, v);
    },

    patientDocument(req, res) {
      const v = ownPublished(req);
      if (!v) return fail(res, { notFound: true });
      if (!v.paiement) return res.status(402).json({ ok: false, error: 'Paiement requis avant le téléchargement' });
      sendDocument(res, v);
    }
  };
})();

/* ============================================================
   Routes : comptes
   ============================================================ */
const comptesRouter = (() => {
  /* Connexion du personnel (admin / médecin / technicien) et des patients. */
  const { ADMIN_USER, ADMIN_PASSWORD } = config;
  const { sendErrors } = httpLib;

  const router = express.Router();

  /* ----- Personnel ----- */
  router.post('/admin/login', (req, res) => {
    const key = 'staff:' + req.ip;
    if (auth.tooMany(key)) return res.status(429).json({ ok: false, error: 'Trop de tentatives, réessayez dans 10 min' });
    const { username = '', password = '' } = req.body || {};

    let session = null;
    if (auth.safeEq(String(username), ADMIN_USER) && auth.safeEq(String(password), ADMIN_PASSWORD)) {
      session = { u: ADMIN_USER, nom: 'Administrateur', role: 'admin' };
    } else {
      const m = Medecin.findByUsername(username);
      if (m && auth.checkPassword(password, m.passwordHash)) session = { u: m.username, nom: m.nom, role: m.role, mid: m.id };
    }
    if (!session) {
      auth.fail(key);
      return res.status(401).json({ ok: false, error: 'Identifiants incorrects' });
    }
    auth.reset(key);
    auth.setCookie(res, 'lc_admin', auth.makeToken(session, 8), 8);
    res.json({ ok: true, role: session.role });
  });

  router.post('/admin/logout', (req, res) => { auth.clearCookie(res, 'lc_admin'); res.json({ ok: true }); });
  router.get('/admin/me', auth.requireStaff, (req, res) =>
    res.json({ ok: true, user: req.staff.u, nom: req.staff.nom, role: req.staff.role }));

  /* ----- Patients ----- */
  router.post('/patient/register', (req, res) => {
    const out = Patient.register(req.body);
    if (out.errors) return sendErrors(res, out.errors);
    auth.setCookie(res, 'lc_patient', auth.makeToken({ pid: out.patient.id, nom: out.patient.nom }, 4), 4);
    res.status(201).json({ ok: true });
  });

  router.post('/patient/login', (req, res) => {
    const key = 'patient:' + req.ip;
    if (auth.tooMany(key)) return res.status(429).json({ ok: false, error: 'Trop de tentatives, réessayez dans 10 min' });
    const { email = '', password = '' } = req.body || {};
    const p = Patient.verify(email, password);
    if (!p) {
      auth.fail(key);
      return res.status(401).json({ ok: false, error: 'E-mail ou mot de passe incorrect' });
    }
    auth.reset(key);
    auth.setCookie(res, 'lc_patient', auth.makeToken({ pid: p.id, nom: p.nom }, 4), 4);
    res.json({ ok: true });
  });

  router.post('/patient/logout', (req, res) => { auth.clearCookie(res, 'lc_patient'); res.json({ ok: true }); });
  router.get('/patient/me', auth.requirePatient, (req, res) => {
    const p = Patient.find(req.patient.pid);
    if (!p) return res.status(401).json({ ok: false, error: 'Compte introuvable' });
    res.json({ ok: true, patient: Patient.publicView(p) });
  });

  return router;
})();

/* ============================================================
   Routes : résultats
   ============================================================ */
const resultatsRouter = (() => {
  /* Routes du module Résultats (staff = back office, patient = front office). */
  const C = ResultatController;

  const router = express.Router();

  // Rôles : admin (tout) · medecin (tout sur les résultats) · technicien (saisie, pas de validation/publication/suppression)
  const saisie = auth.requireRole('medecin', 'technicien');
  const autorise = auth.requireRole('medecin');   // « personnel autorisé » : valider / publier / supprimer

  /* ----- Back office ----- */
  router.get('/admin/referentiels', auth.requireStaff, C.referentiels);
  router.post('/admin/patients', saisie, C.createPatient);

  router.get('/admin/resultats', auth.requireStaff, C.list);
  router.get('/admin/resultats/:id', auth.requireStaff, C.show);
  router.post('/admin/resultats', saisie, C.create);
  router.put('/admin/resultats/:id', saisie, C.update);
  router.delete('/admin/resultats/:id', autorise, C.destroy);

  router.post('/admin/resultats/:id/valider', autorise, C.valider);
  router.post('/admin/resultats/:id/publier', autorise, C.publier);
  router.post('/admin/resultats/:id/depublier', autorise, C.depublier);

  router.post('/admin/resultats/:id/document',
    saisie, express.raw({ type: () => true, limit: '5mb' }), C.uploadDocument);
  router.delete('/admin/resultats/:id/document', saisie, C.deleteDocument);
  router.get('/admin/resultats/:id/document', auth.requireStaff, C.staffDocument);
  router.get('/admin/resultats/:id/pdf', auth.requireStaff, C.staffPdf);

  /* ----- Front office (patient connecté, ses résultats uniquement) ----- */
  router.get('/patient/resultats', auth.requirePatient, C.patientList);
  router.get('/patient/resultats/:id', auth.requirePatient, C.patientShow);
  router.post('/patient/resultats/:id/payer', auth.requirePatient, C.patientPay);
  router.get('/patient/resultats/:id/pdf', auth.requirePatient, C.patientPdf);
  router.get('/patient/resultats/:id/document', auth.requirePatient, C.patientDocument);

  return router;
})();

/* ============================================================
   Routes : rendez-vous (formulaire public)
   ============================================================ */
const rendezvousRouter = (() => {
  /* Rendez-vous : formulaire public (accueil) uniquement.
     Plus de gestion dans le back office. */
  const { sendErrors } = httpLib;

  const router = express.Router();
  const all = () => store.read('appointments', []);

  router.post('/appointments', (req, res) => {
    const { errors, value } = validate.rendezvous(req.body);
    if (errors.length) return sendErrors(res, errors);
    const rows = all();
    const row = { id: store.nextId(rows), ...value, status: 'en attente', createdAt: new Date().toISOString() };
    rows.push(row);
    store.write('appointments', rows);
    res.status(201).json({ ok: true, id: row.id });
  });

  return router;
})();

/* ============================================================
   Application Express
   ============================================================ */

const app = express();

app.disable('x-powered-by');
app.use((req, res, next) => {            // en-têtes de sécurité
  res.setHeader('X-Content-Type-Options', 'nosniff');
  res.setHeader('X-Frame-Options', 'DENY');
  res.setHeader('Referrer-Policy', 'same-origin');
  next();
});
app.use(express.json());

/* ---------- API : comptes (personnel + patients), résultats, rendez-vous (formulaire public) ---------- */
app.use('/api', comptesRouter);
app.use('/api', resultatsRouter);
app.use('/api', rendezvousRouter);

/* ---------- Pages ---------- */
// Toutes les pages HTML du back office (sauf login) exigent une session personnel
app.use('/admin', (req, res, next) => {
  const isPage = req.path === '/' || /\.html$/.test(req.path);
  if (isPage && req.path !== '/login.html' && !auth.staffSession(req)) return res.redirect('/admin/login.html');
  next();
});
app.get(['/admin', '/admin/'], (req, res) => res.redirect('/admin/resultats.html'));
app.use('/admin', express.static(path.join(__dirname, 'admin'), { index: false }));  // espace admin
app.use(express.static(path.join(__dirname, '..', 'front')));                       // site patient

// Erreurs (fichier trop gros, JSON invalide…) toujours en JSON
app.use((err, req, res, next) => {
  if (err.type === 'entity.too.large') return res.status(413).json({ ok: false, error: 'Fichier trop volumineux (5 Mo maximum)' });
  if (err.type === 'entity.parse.failed') return res.status(400).json({ ok: false, error: 'Requête invalide' });
  console.error(err);
  res.status(500).json({ ok: false, error: 'Erreur serveur' });
});

app.listen(config.PORT, () => {
  const pw = config.ADMIN_PASSWORD === config.DEFAULT_ADMIN_PASSWORD ? config.DEFAULT_ADMIN_PASSWORD + ' – À CHANGER' : '***';
  console.log(`Patient : http://localhost:${config.PORT}`);
  console.log(`Admin   : http://localhost:${config.PORT}/admin  (${config.ADMIN_USER} / ${pw})`);
});
