/* ============================================================
   LabConnect – Tableau de bord administrateur
   ============================================================ */

'use strict';

let allAppointments = [];
let allUsers = [];
let currentAdmin = null;

const $ = (id) => document.getElementById(id);

const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;'
}[char]));

const statusClasses = {
  'en attente': 'attente',
  'confirmé': 'confirme',
  'annulé': 'annule'
};

const formatDate = (iso) => {
  if (!iso) return '—';
  const d = new Date(iso);
  return isNaN(d) ? esc(iso) : d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

/* === API helper === */
async function api(url, options) {
  const response = await fetch(url, options);

  if (response.status === 401) {
    location.href = '/admin/login.html';
    throw new Error('401');
  }

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.error || 'Erreur serveur');
  }

  return data;
}

/* === Tabs === */
document.querySelectorAll('.tab-btn').forEach((btn) => {
  btn.addEventListener('click', () => {
    const tab = btn.dataset.tab;
    document.querySelectorAll('.tab-btn').forEach((b) => {
      const isActive = b.dataset.tab === tab;
      b.classList.toggle('active', isActive);
      b.setAttribute('aria-selected', String(isActive));
    });
    $('panel-appointments').style.display = tab === 'appointments' ? '' : 'none';
    $('panel-users').style.display = tab === 'users' ? '' : 'none';
  });
});

/* === Data loading === */
async function load() {
  try {
    const [me, appointments, users] = await Promise.all([
      api('/api/admin/me'),
      api('/api/admin/appointments'),
      api('/api/admin/users')
    ]);
    currentAdmin = me.user || null;
    if (currentAdmin) {
      $('admin-identity').textContent = `${currentAdmin.name} (${currentAdmin.email})`;
    }
    allAppointments = appointments;
    allUsers = users;
    renderAppointments();
    renderUsers();
  } catch (error) {
    if (error.message !== '401') {
      console.error(error);
    }
  }
}

/* === Rendering Appointments === */
function renderAppointments() {
  const query = $('q').value.toLowerCase();
  const status = $('st').value;

  const list = allAppointments.filter((appointment) => {
    const matchesStatus = !status || appointment.status === status;
    const haystack = [
      appointment.name,
      appointment.email,
      appointment.phone,
      appointment.labo,
      appointment.exam
    ].join(' ').toLowerCase();

    return matchesStatus && (!query || haystack.includes(query));
  });

  $('tab-count-appts').textContent = allAppointments.length;
  $('s-all').textContent = allAppointments.length;
  $('s-w').textContent = allAppointments.filter((a) => a.status === 'en attente').length;
  $('s-o').textContent = allAppointments.filter((a) => a.status === 'confirmé').length;
  $('s-k').textContent = allAppointments.filter((a) => a.status === 'annulé').length;

  $('empty').style.display = list.length ? 'none' : 'block';

  $('rows').innerHTML = list.map((appointment) => {
    const statusClass = statusClasses[appointment.status] || '';

    return `
      <tr>
        <td>
          <b>${esc(appointment.name)}</b>
          <small>${esc(appointment.email)}</small>
          <small>${esc(appointment.phone)}</small>
          ${appointment.message ? `<small>“${esc(appointment.message)}”</small>` : ''}
        </td>
        <td>
          ${esc(appointment.date)}
          <small>${esc(appointment.labo)}</small>
          ${appointment.domicile ? '<span class="tag">À domicile</span>' : ''}
        </td>
        <td>${esc(appointment.exam)}</td>
        <td><span class="badge ${statusClass}">${esc(appointment.status)}</span></td>
        <td class="acts">
          <button class="act ok" data-id="${esc(appointment.id)}" data-s="confirmé">Confirmer</button>
          <button class="act ko" data-id="${esc(appointment.id)}" data-s="annulé">Annuler</button>
          <button class="act del" data-id="${esc(appointment.id)}" data-del="1">Supprimer</button>
        </td>
      </tr>`;
  }).join('');
}

/* === Rendering Users (avec / sans réservation) === */
function buildUserRow(user, withReservation) {
  const isSelf = currentAdmin && currentAdmin.id === user.id;
  const roleBadge = user.role === 'admin'
    ? '<span class="badge role-admin">Admin</span>'
    : '<span class="badge role-user">Patient</span>';
  const activeBadge = user.active
    ? '<span class="badge confirme">Actif</span>'
    : '<span class="badge annule">Désactivé</span>';

  let thirdCell = '';
  if (withReservation) {
    const preview = (user.appointments || []).slice(0, 3).map((a) =>
      `<li>${esc(a.date)} · ${esc(a.labo)} (${esc(a.exam)}) — <b>${esc(a.status)}</b></li>`
    ).join('');
    const extra = user.reservationsCount > 3 ? `<li>+${user.reservationsCount - 3} autre(s)…</li>` : '';
    thirdCell = `
      <td>
        <span class="badge confirme">${user.reservationsCount} réservation(s)</span>
        <ul class="res-list">${preview}${extra}</ul>
      </td>`;
  } else {
    thirdCell = `
      <td>
        <span class="badge attente">Aucune réservation</span>
        <small>Inscrit le ${formatDate(user.createdAt)}</small>
      </td>`;
  }

  return `
    <tr>
      <td>
        <b>${esc(user.name)}</b>
        <small>${esc(user.email)}</small>
        ${user.phone ? `<small>${esc(user.phone)}</small>` : ''}
      </td>
      <td>
        ${roleBadge} ${activeBadge}
      </td>
      ${thirdCell}
      <td class="acts">
        <button class="act" data-uid="${esc(user.id)}" data-uact="view">Voir</button>
        <button class="act edit" data-uid="${esc(user.id)}" data-uact="edit">Modifier</button>
        ${!isSelf ? `<button class="act ${user.active ? 'ko' : 'ok'}" data-uid="${esc(user.id)}" data-uact="toggle">${user.active ? 'Désactiver' : 'Activer'}</button>` : ''}
        ${!isSelf ? `<button class="act del" data-uid="${esc(user.id)}" data-uact="delete">Supprimer</button>` : ''}
      </td>
    </tr>`;
}

function renderUsers() {
  const query = $('uq').value.toLowerCase();
  const roleFilter = $('urole').value;
  const statusFilter = $('ustatus').value;
  const resFilter = $('ures').value;

  const filtered = allUsers.filter((u) => {
    if (roleFilter && u.role !== roleFilter) return false;
    if (statusFilter === 'active' && !u.active) return false;
    if (statusFilter === 'inactive' && u.active) return false;
    if (query) {
      const hay = [u.name, u.email, u.phone].join(' ').toLowerCase();
      if (!hay.includes(query)) return false;
    }
    return true;
  });

  const withResAll = allUsers.filter((u) => u.hasReservation);
  const withoutResAll = allUsers.filter((u) => !u.hasReservation);

  $('tab-count-users').textContent = allUsers.length;
  $('u-all').textContent = allUsers.length;
  $('u-with').textContent = withResAll.length;
  $('u-without').textContent = withoutResAll.length;
  $('u-admins').textContent = allUsers.filter((u) => u.role === 'admin').length;

  const withRes = filtered.filter((u) => u.hasReservation);
  const withoutRes = filtered.filter((u) => !u.hasReservation);

  $('badge-with-count').textContent = withRes.length;
  $('badge-without-count').textContent = withoutRes.length;

  $('section-with-res').style.display = resFilter === 'without' ? 'none' : '';
  $('section-without-res').style.display = resFilter === 'with' ? 'none' : '';

  $('empty-users-with').style.display = withRes.length ? 'none' : 'block';
  $('empty-users-without').style.display = withoutRes.length ? 'none' : 'block';

  $('rows-users-with').innerHTML = withRes.map((u) => buildUserRow(u, true)).join('');
  $('rows-users-without').innerHTML = withoutRes.map((u) => buildUserRow(u, false)).join('');
}

/* === Appointment Actions === */
$('rows').addEventListener('click', async (event) => {
  const button = event.target.closest('button');
  if (!button) return;
  const id = button.dataset.id;

  try {
    if (button.dataset.del) {
      if (!confirm('Supprimer définitivement ce rendez-vous ?')) return;
      await api('/api/admin/appointments/' + id, { method: 'DELETE' });
    } else {
      await api('/api/admin/appointments/' + id, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: button.dataset.s })
      });
    }
    load();
  } catch (error) {
    if (error.message !== '401') alert(error.message);
  }
});

/* === User Modal (Create / Edit) === */
function openUserModal(user = null) {
  $('um-err').textContent = '';
  if (user) {
    $('user-modal-title').textContent = 'Modifier l’utilisateur';
    $('um-id').value = user.id;
    $('um-name').value = user.name || '';
    $('um-email').value = user.email || '';
    $('um-phone').value = user.phone || '';
    $('um-role').value = user.role || 'user';
    $('um-active').value = user.active ? 'true' : 'false';
    $('um-password').value = '';
    $('um-password').required = false;
    $('um-password-label').textContent = 'Nouveau mot de passe (laisser vide pour ne pas modifier)';
  } else {
    $('user-modal-title').textContent = 'Ajouter un utilisateur';
    $('um-id').value = '';
    $('user-form').reset();
    $('um-role').value = 'user';
    $('um-active').value = 'true';
    $('um-password').required = true;
    $('um-password-label').textContent = 'Mot de passe (8 caractères min., lettres + chiffres)';
  }
  $('user-modal').hidden = false;
}

function closeUserModal() {
  $('user-modal').hidden = true;
}

$('btn-add-user').addEventListener('click', () => openUserModal(null));
$('um-close').addEventListener('click', closeUserModal);
$('um-cancel').addEventListener('click', closeUserModal);

$('user-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  $('um-err').textContent = '';

  const id = $('um-id').value;
  const payload = {
    name: $('um-name').value.trim(),
    email: $('um-email').value.trim(),
    phone: $('um-phone').value.trim(),
    role: $('um-role').value,
    active: $('um-active').value === 'true'
  };
  const pwd = $('um-password').value;
  if (!id || pwd) {
    payload.password = pwd;
  }

  try {
    if (id) {
      await api('/api/admin/users/' + id, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
    } else {
      await api('/api/admin/users', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
    }
    closeUserModal();
    load();
  } catch (error) {
    if (error.message !== '401') {
      $('um-err').textContent = error.message;
    }
  }
});

/* === User Detail Modal === */
function openDetailModal(user) {
  const appointmentsHtml = (user.appointments && user.appointments.length)
    ? `<ul class="res-list">${user.appointments.map((a) =>
        `<li><b>${esc(a.date)}</b> — ${esc(a.labo)} (${esc(a.exam)}) · <span class="badge ${statusClasses[a.status] || ''}">${esc(a.status)}</span></li>`
      ).join('')}</ul>`
    : '<p>Aucune réservation enregistrée pour ce compte.</p>';

  $('ud-body').innerHTML = `
    <p><b>Nom complet :</b> ${esc(user.name)}</p>
    <p><b>E-mail :</b> ${esc(user.email)}</p>
    <p><b>Téléphone :</b> ${esc(user.phone || 'Non renseigné')}</p>
    <p><b>Rôle :</b> ${user.role === 'admin' ? 'Administrateur' : 'Patient'}</p>
    <p><b>Statut :</b> ${user.active ? 'Actif' : 'Désactivé'}</p>
    <p><b>Date d’inscription :</b> ${formatDate(user.createdAt)}</p>
    <p><b>Dernière connexion :</b> ${formatDate(user.lastLogin)}</p>
    <h4>Réservations (${user.reservationsCount})</h4>
    ${appointmentsHtml}
  `;
  $('user-detail-modal').hidden = false;
}

$('ud-close').addEventListener('click', () => { $('user-detail-modal').hidden = true; });
$('ud-ok').addEventListener('click', () => { $('user-detail-modal').hidden = true; });

/* === User Table Actions === */
async function handleUserTableClick(event) {
  const button = event.target.closest('button[data-uid]');
  if (!button) return;

  const id = Number(button.dataset.uid);
  const action = button.dataset.uact;
  const user = allUsers.find((u) => u.id === id);
  if (!user) return;

  try {
    if (action === 'view') {
      openDetailModal(user);
    } else if (action === 'edit') {
      openUserModal(user);
    } else if (action === 'toggle') {
      await api('/api/admin/users/' + id, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ active: !user.active })
      });
      load();
    } else if (action === 'delete') {
      if (!confirm(`Supprimer définitivement le compte de ${user.name} (${user.email}) ?`)) return;
      await api('/api/admin/users/' + id, { method: 'DELETE' });
      load();
    }
  } catch (error) {
    if (error.message !== '401') alert(error.message);
  }
}

$('rows-users-with').addEventListener('click', handleUserTableClick);
$('rows-users-without').addEventListener('click', handleUserTableClick);

/* === Filters === */
$('q').addEventListener('input', renderAppointments);
$('st').addEventListener('change', renderAppointments);
$('uq').addEventListener('input', renderUsers);
$('urole').addEventListener('change', renderUsers);
$('ustatus').addEventListener('change', renderUsers);
$('ures').addEventListener('change', renderUsers);

/* === Logout === */
$('out').addEventListener('click', async () => {
  await fetch('/api/auth/logout', { method: 'POST' });
  location.href = '/connexion.html';
});

/* === Init === */
load();
