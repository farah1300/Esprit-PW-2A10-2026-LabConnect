const express = require('express');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;
const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_EMAIL = (process.env.ADMIN_EMAIL || 'admin@labconnect.tn').toLowerCase();
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'admin123'; // à changer !
const SECRET = process.env.SECRET || crypto.randomBytes(32).toString('hex');

const DATA_DIR = path.join(__dirname, 'data');
const DB_FILE = path.join(DATA_DIR, 'appointments.json');
const USERS_FILE = path.join(DATA_DIR, 'users.json');

const STATUSES = ['en attente', 'confirmé', 'annulé'];
const ROLES = ['user', 'admin'];
const SESSION_TTL_MS = 8 * 3600 * 1000;

fs.mkdirSync(DATA_DIR, { recursive: true });
if (!fs.existsSync(DB_FILE)) fs.writeFileSync(DB_FILE, '[]');
if (!fs.existsSync(USERS_FILE)) fs.writeFileSync(USERS_FILE, '[]');

const readJson = (file) => JSON.parse(fs.readFileSync(file, 'utf8'));
const writeJson = (file, rows) => fs.writeFileSync(file, JSON.stringify(rows, null, 2));

const readDb = () => readJson(DB_FILE);
const writeDb = (rows) => writeJson(DB_FILE, rows);
const readUsers = () => readJson(USERS_FILE);
const writeUsers = (rows) => writeJson(USERS_FILE, rows);

app.use(express.json());

/* ---------- Crypto & Password helpers ---------- */
function hashPassword(password) {
  const salt = crypto.randomBytes(16).toString('hex');
  const derived = crypto.scryptSync(String(password), salt, 64).toString('hex');
  return `${salt}:${derived}`;
}

function verifyPassword(password, storedHash) {
  const parts = String(storedHash || '').split(':');
  if (parts.length !== 2) return false;
  const [salt, keyHex] = parts;
  const keyBuf = Buffer.from(keyHex, 'hex');
  const derivedBuf = crypto.scryptSync(String(password), salt, 64);
  return keyBuf.length === derivedBuf.length && crypto.timingSafeEqual(keyBuf, derivedBuf);
}

function validatePasswordStrength(password) {
  const pwd = String(password || '');
  return pwd.length >= 8 && /[A-Za-z]/.test(pwd) && /\d/.test(pwd);
}

function sanitizeUser(user) {
  return {
    id: user.id,
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    role: user.role,
    active: user.active !== false,
    createdAt: user.createdAt,
    lastLogin: user.lastLogin || null
  };
}

function seedDefaultAdmin() {
  const users = readUsers();
  const hasAdmin = users.some((u) => u.role === 'admin');
  if (!hasAdmin) {
    const nextId = users.length ? Math.max(...users.map((u) => u.id)) + 1 : 1;
    users.push({
      id: nextId,
      name: 'Administrateur LabConnect',
      username: ADMIN_USER,
      email: ADMIN_EMAIL,
      phone: '',
      role: 'admin',
      active: true,
      passwordHash: hashPassword(ADMIN_PASSWORD),
      createdAt: new Date().toISOString(),
      lastLogin: null
    });
    writeUsers(users);
  }
}

seedDefaultAdmin();

/* ---------- Auth session (cookie signé, 8h) ---------- */
const sign = (v) => crypto.createHmac('sha256', SECRET).update(v).digest('hex');
const safeEq = (a, b) => {
  const x = Buffer.from(String(a));
  const y = Buffer.from(String(b));
  return x.length === y.length && crypto.timingSafeEqual(x, y);
};

function createSessionCookie(user) {
  const payload = Buffer.from(JSON.stringify({
    id: user.id,
    role: user.role,
    exp: Date.now() + SESSION_TTL_MS
  })).toString('base64url');
  return `${payload}.${sign(payload)}`;
}

function setSessionCookie(res, user) {
  const token = createSessionCookie(user);
  res.setHeader(
    'Set-Cookie',
    `lc_session=${encodeURIComponent(token)}; HttpOnly; SameSite=Lax; Path=/; Max-Age=${SESSION_TTL_MS / 1000}`
  );
}

function clearSessionCookies(res) {
  res.setHeader('Set-Cookie', [
    'lc_session=; HttpOnly; SameSite=Lax; Path=/; Max-Age=0',
    'lc_admin=; HttpOnly; SameSite=Strict; Path=/; Max-Age=0'
  ]);
}

function getSessionUser(req) {
  const m = /(?:^|;\s*)lc_session=([^;]+)/.exec(req.headers.cookie || '');
  if (!m) return null;
  const parts = decodeURIComponent(m[1]).split('.');
  if (parts.length !== 2) return null;
  const [payload, sig] = parts;
  if (!payload || !sig || !safeEq(sig, sign(payload))) return null;

  let parsed;
  try {
    parsed = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8'));
  } catch {
    return null;
  }
  if (!parsed || !parsed.id || Date.now() > Number(parsed.exp)) return null;

  const user = readUsers().find((u) => u.id === Number(parsed.id));
  if (!user || user.active === false) return null;
  return user;
}

const requireAuth = (req, res, next) => {
  const user = getSessionUser(req);
  if (!user) {
    return res.status(401).json({ ok: false, error: 'Veuillez vous connecter pour accéder à cette fonctionnalité.' });
  }
  req.user = user;
  next();
};

const requireAdmin = (req, res, next) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'admin') {
    return res.status(401).json({ ok: false, error: 'Non autorisé' });
  }
  req.user = user;
  next();
};

/* ---------- Rate limiter simple par IP ---------- */
const attempts = {};
function checkRateLimit(ip) {
  const now = Date.now();
  const list = (attempts[ip] = (attempts[ip] || []).filter((t) => now - t < 10 * 60 * 1000));
  return { limited: list.length >= 5, list, now };
}

/* ---------- Auth API (Front & Admin) ---------- */
app.post('/api/auth/register', (req, res) => {
  const { name, email, phone, password, confirmPassword } = req.body || {};
  const nameText = String(name || '').trim();
  const emailText = String(email || '').trim().toLowerCase();
  const phoneText = String(phone || '').trim();
  const pwdText = String(password || '');
  const errors = [];

  if (nameText.length < 2) errors.push('Nom complet invalide (2 caractères minimum).');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailText)) errors.push('Adresse e-mail invalide.');
  if (phoneText && !/^[0-9+\s().-]{6,20}$/.test(phoneText)) errors.push('Numéro de téléphone invalide.');
  if (!validatePasswordStrength(pwdText)) {
    errors.push('Le mot de passe doit contenir au moins 8 caractères, avec des lettres et des chiffres.');
  }
  if (confirmPassword !== undefined && pwdText !== String(confirmPassword)) {
    errors.push('Les mots de passe ne correspondent pas.');
  }

  const users = readUsers();
  if (users.some((u) => u.email.toLowerCase() === emailText)) {
    errors.push('Cette adresse e-mail est déjà utilisée.');
  }

  if (errors.length) {
    return res.status(400).json({ ok: false, errors, error: errors[0] });
  }

  const newUser = {
    id: users.length ? Math.max(...users.map((u) => u.id)) + 1 : 1,
    name: nameText.slice(0, 100),
    email: emailText.slice(0, 150),
    phone: phoneText.slice(0, 30),
    role: 'user',
    active: true,
    passwordHash: hashPassword(pwdText),
    createdAt: new Date().toISOString(),
    lastLogin: null
  };

  users.push(newUser);
  writeUsers(users);
  res.status(201).json({ ok: true, user: sanitizeUser(newUser) });
});

function handleLogin(req, res, { adminOnly = false } = {}) {
  const rate = checkRateLimit(req.ip);
  if (rate.limited) {
    return res.status(429).json({ ok: false, error: 'Trop de tentatives, réessayez dans 10 min' });
  }

  const body = req.body || {};
  const identifier = String(body.email || body.username || '').trim().toLowerCase();
  const password = String(body.password || '');

  const users = readUsers();
  const user = users.find(
    (u) => u.email.toLowerCase() === identifier || (u.username && u.username.toLowerCase() === identifier)
  );

  if (!user || !verifyPassword(password, user.passwordHash)) {
    rate.list.push(rate.now);
    return res.status(401).json({ ok: false, error: 'Identifiants incorrects' });
  }

  if (user.active === false) {
    return res.status(403).json({ ok: false, error: 'Ce compte est désactivé. Contactez un administrateur.' });
  }

  if (adminOnly && user.role !== 'admin') {
    return res.status(403).json({ ok: false, error: 'Accès réservé aux administrateurs.' });
  }

  user.lastLogin = new Date().toISOString();
  writeUsers(users);

  setSessionCookie(res, user);
  const redirect = user.role === 'admin' ? '/admin/' : '/index.html#appointment';
  return res.json({ ok: true, user: sanitizeUser(user), redirect });
}

app.post('/api/auth/login', (req, res) => handleLogin(req, res, { adminOnly: false }));

app.post('/api/auth/logout', (req, res) => {
  clearSessionCookies(res);
  res.json({ ok: true });
});

app.get('/api/auth/me', (req, res) => {
  const user = getSessionUser(req);
  if (!user) {
    return res.json({ ok: true, authenticated: false, user: null });
  }
  return res.json({ ok: true, authenticated: true, user: sanitizeUser(user) });
});

/* Aliases admin auth */
app.post('/api/admin/login', (req, res) => handleLogin(req, res, { adminOnly: true }));
app.post('/api/admin/logout', (req, res) => {
  clearSessionCookies(res);
  res.json({ ok: true });
});
app.get('/api/admin/me', requireAdmin, (req, res) => res.json({ ok: true, user: sanitizeUser(req.user) }));

/* ---------- API patient (authentifiée pour la prise de rendez-vous) ---------- */
app.get('/api/appointments/my', requireAuth, (req, res) => {
  const emailLower = req.user.email.toLowerCase();
  const mine = readDb()
    .filter((a) => a.userId === req.user.id || (a.email && a.email.toLowerCase() === emailLower))
    .reverse();
  res.json({ ok: true, appointments: mine });
});

app.post('/api/appointments', requireAuth, (req, res) => {
  const { name, email, date, labo, exam, phone, domicile, message } = req.body || {};
  const errors = [];
  const nameText = String(name || req.user.name || '').trim();
  const emailText = String(email || req.user.email || '').trim().toLowerCase();
  const phoneText = String(phone || req.user.phone || '').trim();
  const messageText = String(message || '').trim();

  // Compare dates as YYYY-MM-DD strings to avoid timezone issues.
  const dateOnly = String(date || '').slice(0, 10);
  const parsedDate = new Date(dateOnly + 'T00:00:00');
  const isRealDate = /^\d{4}-\d{2}-\d{2}$/.test(dateOnly) &&
    !isNaN(parsedDate) &&
    parsedDate.getFullYear() === Number(dateOnly.slice(0, 4)) &&
    parsedDate.getMonth() + 1 === Number(dateOnly.slice(5, 7)) &&
    parsedDate.getDate() === Number(dateOnly.slice(8, 10));
  const today = new Date();
  const todayOnly = [
    today.getFullYear(),
    String(today.getMonth() + 1).padStart(2, '0'),
    String(today.getDate()).padStart(2, '0')
  ].join('-');

  if (nameText.length < 2) errors.push('Nom invalide');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailText)) errors.push('E-mail invalide');
  if (!isRealDate) {
    errors.push('Date invalide');
  } else if (dateOnly < todayOnly) {
    errors.push('La date est déjà passée');
  }
  if (!labo) errors.push('Laboratoire requis');
  if (!exam) errors.push("Type d'examen requis");
  if (errors.length) return res.status(400).json({ ok: false, errors });

  const rows = readDb();
  const row = {
    id: rows.length ? Math.max(...rows.map((r) => r.id)) + 1 : 1,
    userId: req.user.id,
    name: nameText.slice(0, 100),
    email: emailText.slice(0, 150),
    date: dateOnly,
    labo: String(labo).slice(0, 100),
    exam: String(exam).slice(0, 100),
    phone: phoneText.slice(0, 30),
    domicile: !!domicile,
    message: messageText.slice(0, 1000),
    status: 'en attente',
    createdAt: new Date().toISOString()
  };
  rows.push(row);
  writeDb(rows);
  res.status(201).json({ ok: true, id: row.id, appointment: row });
});

/* ---------- API admin : Rendez-vous ---------- */
app.get('/api/admin/appointments', requireAdmin, (req, res) => res.json(readDb().reverse()));

app.patch('/api/admin/appointments/:id', requireAdmin, (req, res) => {
  const { status, name, email, phone, date, labo, exam, domicile, message } = req.body || {};
  const rows = readDb();
  const row = rows.find((r) => r.id === Number(req.params.id));
  if (!row) return res.status(404).json({ ok: false, error: 'Introuvable' });

  if (status !== undefined) {
    if (!STATUSES.includes(status)) return res.status(400).json({ ok: false, error: 'Statut invalide' });
    row.status = status;
  }
  if (name !== undefined) row.name = String(name).trim().slice(0, 100) || row.name;
  if (email !== undefined) row.email = String(email).trim().toLowerCase().slice(0, 150) || row.email;
  if (phone !== undefined) row.phone = String(phone).trim().slice(0, 30);
  if (date !== undefined) row.date = String(date).slice(0, 10);
  if (labo !== undefined) row.labo = String(labo).slice(0, 100);
  if (exam !== undefined) row.exam = String(exam).slice(0, 100);
  if (domicile !== undefined) row.domicile = !!domicile;
  if (message !== undefined) row.message = String(message).trim().slice(0, 1000);

  writeDb(rows);
  res.json({ ok: true, appointment: row });
});

app.delete('/api/admin/appointments/:id', requireAdmin, (req, res) => {
  const rows = readDb();
  const next = rows.filter((r) => r.id !== Number(req.params.id));
  if (next.length === rows.length) return res.status(404).json({ ok: false, error: 'Introuvable' });
  writeDb(next);
  res.json({ ok: true });
});

/* ---------- API admin : Gestion des utilisateurs (CRUD) ---------- */
function attachReservations(user, appointments) {
  const emailLower = user.email.toLowerCase();
  const userAppointments = appointments.filter(
    (a) => a.userId === user.id || (a.email && a.email.toLowerCase() === emailLower)
  );
  return {
    ...sanitizeUser(user),
    reservationsCount: userAppointments.length,
    hasReservation: userAppointments.length > 0,
    appointments: userAppointments.map((a) => ({
      id: a.id,
      date: a.date,
      labo: a.labo,
      exam: a.exam,
      status: a.status,
      domicile: !!a.domicile,
      createdAt: a.createdAt
    }))
  };
}

app.get('/api/admin/users', requireAdmin, (req, res) => {
  const users = readUsers().slice().reverse();
  const appointments = readDb().slice().reverse();
  res.json(users.map((u) => attachReservations(u, appointments)));
});

app.post('/api/admin/users', requireAdmin, (req, res) => {
  const { name, email, phone, password, role = 'user', active = true } = req.body || {};
  const nameText = String(name || '').trim();
  const emailText = String(email || '').trim().toLowerCase();
  const phoneText = String(phone || '').trim();
  const pwdText = String(password || '');

  if (nameText.length < 2) return res.status(400).json({ ok: false, error: 'Nom complet invalide (2 caractères min.).' });
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailText)) {
    return res.status(400).json({ ok: false, error: 'Adresse e-mail invalide.' });
  }
  if (phoneText && !/^[0-9+\s().-]{6,20}$/.test(phoneText)) {
    return res.status(400).json({ ok: false, error: 'Numéro de téléphone invalide.' });
  }
  if (!validatePasswordStrength(pwdText)) {
    return res.status(400).json({ ok: false, error: 'Mot de passe invalide (8 caractères min., lettres + chiffres).' });
  }
  if (!ROLES.includes(role)) {
    return res.status(400).json({ ok: false, error: 'Rôle invalide.' });
  }

  const users = readUsers();
  if (users.some((u) => u.email.toLowerCase() === emailText)) {
    return res.status(400).json({ ok: false, error: 'Cette adresse e-mail est déjà utilisée.' });
  }

  const created = {
    id: users.length ? Math.max(...users.map((u) => u.id)) + 1 : 1,
    name: nameText.slice(0, 100),
    email: emailText.slice(0, 150),
    phone: phoneText.slice(0, 30),
    role,
    active: active !== false,
    passwordHash: hashPassword(pwdText),
    createdAt: new Date().toISOString(),
    lastLogin: null
  };

  users.push(created);
  writeUsers(users);
  res.status(201).json({ ok: true, user: attachReservations(created, readDb()) });
});

app.patch('/api/admin/users/:id', requireAdmin, (req, res) => {
  const id = Number(req.params.id);
  const users = readUsers();
  const user = users.find((u) => u.id === id);
  if (!user) return res.status(404).json({ ok: false, error: 'Utilisateur introuvable.' });

  const { name, email, phone, password, role, active } = req.body || {};
  const nextRole = role !== undefined ? role : user.role;
  const nextActive = active !== undefined ? !!active : (user.active !== false);

  if (!ROLES.includes(nextRole)) {
    return res.status(400).json({ ok: false, error: 'Rôle invalide.' });
  }

  if (req.user.id === user.id && (!nextActive || nextRole !== 'admin')) {
    return res.status(400).json({ ok: false, error: 'Vous ne pouvez pas désactiver ou rétrograder votre propre compte.' });
  }

  const activeAdminsAfter = users.filter((u) => {
    const r = u.id === user.id ? nextRole : u.role;
    const a = u.id === user.id ? nextActive : (u.active !== false);
    return r === 'admin' && a;
  });
  if (activeAdminsAfter.length === 0) {
    return res.status(400).json({ ok: false, error: 'Impossible de supprimer le dernier administrateur actif.' });
  }

  if (name !== undefined) {
    const nameText = String(name).trim();
    if (nameText.length < 2) return res.status(400).json({ ok: false, error: 'Nom complet invalide.' });
    user.name = nameText.slice(0, 100);
  }

  if (email !== undefined) {
    const emailText = String(email).trim().toLowerCase();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailText)) {
      return res.status(400).json({ ok: false, error: 'Adresse e-mail invalide.' });
    }
    if (users.some((u) => u.id !== user.id && u.email.toLowerCase() === emailText)) {
      return res.status(400).json({ ok: false, error: 'Cette adresse e-mail est déjà utilisée.' });
    }
    user.email = emailText.slice(0, 150);
  }

  if (phone !== undefined) {
    const phoneText = String(phone).trim();
    if (phoneText && !/^[0-9+\s().-]{6,20}$/.test(phoneText)) {
      return res.status(400).json({ ok: false, error: 'Numéro de téléphone invalide.' });
    }
    user.phone = phoneText.slice(0, 30);
  }

  if (password !== undefined && String(password) !== '') {
    if (!validatePasswordStrength(password)) {
      return res.status(400).json({ ok: false, error: 'Mot de passe invalide (8 caractères min., lettres + chiffres).' });
    }
    user.passwordHash = hashPassword(password);
  }

  user.role = nextRole;
  user.active = nextActive;

  writeUsers(users);
  res.json({ ok: true, user: attachReservations(user, readDb()) });
});

app.delete('/api/admin/users/:id', requireAdmin, (req, res) => {
  const id = Number(req.params.id);
  const users = readUsers();
  const target = users.find((u) => u.id === id);
  if (!target) return res.status(404).json({ ok: false, error: 'Utilisateur introuvable.' });

  if (req.user.id === id) {
    return res.status(400).json({ ok: false, error: 'Vous ne pouvez pas supprimer votre propre compte.' });
  }

  const activeAdminsRemaining = users.filter(
    (u) => u.id !== id && u.role === 'admin' && u.active !== false
  );
  if (target.role === 'admin' && activeAdminsRemaining.length === 0) {
    return res.status(400).json({ ok: false, error: 'Impossible de supprimer le dernier administrateur actif.' });
  }

  writeUsers(users.filter((u) => u.id !== id));
  res.json({ ok: true });
});

/* ---------- Pages ---------- */
app.get(['/admin', '/admin/', '/admin/index.html'], (req, res) => {
  const user = getSessionUser(req);
  if (user && user.role === 'admin') {
    return res.sendFile(path.join(__dirname, 'admin', 'index.html'));
  }
  return res.redirect('/admin/login.html');
});
app.use('/admin', express.static(path.join(__dirname, 'admin'), { index: false }));  // espace admin
app.use(express.static(path.join(__dirname, '..', 'front')));            // site patient

app.listen(PORT, () => {
  console.log(`Patient : http://localhost:${PORT}`);
  console.log(`Admin   : http://localhost:${PORT}/admin  (${ADMIN_EMAIL} / ${ADMIN_PASSWORD === 'admin123' ? 'admin123 – À CHANGER' : '***'})`);
});
