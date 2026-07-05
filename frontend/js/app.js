/***********************************************
 * DOUALACLEAN — app.js
 * Backend: http://localhost:8000/api
 * Routes exactes du projet Laravel
 ***********************************************/
const API = 'http://localhost:8000/api';

/* ─── Helpers Storage ─── */
const getToken = ()    => localStorage.getItem('dc_token');
const getUser  = ()    => { try { return JSON.parse(localStorage.getItem('dc_user')); } catch { return null; } };
const setAuth  = (t,u) => { localStorage.setItem('dc_token', t); localStorage.setItem('dc_user', JSON.stringify(u)); };
const clearAuth= ()    => { localStorage.removeItem('dc_token'); localStorage.removeItem('dc_user'); };

/* ─── HTTP Headers ─── */
const jsonHeaders = () => ({
  'Content-Type': 'application/json',
  'Accept':       'application/json',
  'Authorization':'Bearer ' + getToken()
});
const authHeaders = () => ({
  'Accept':       'application/json',
  'Authorization':'Bearer ' + getToken()
});

/* ─── Toast ─── */
function toast(msg, type = 'success') {
  let el = document.getElementById('_toast');
  if (!el) {
    el = document.createElement('div');
    el.id = '_toast';
    el.className = 'toast';
    document.body.appendChild(el);
  }
  el.textContent = msg;
  el.className = `toast ${type} show`;
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 3500);
}

/* ─── Redirect ─── */
const go = page => { window.location.href = page; };

/* ─── Responsive menu ─── */
function initHamburgerMenu() {
  document.querySelectorAll('header').forEach(header => {
    const nav = header.querySelector('nav');
    if (!nav || header.querySelector('.menu-toggle')) return;

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'menu-toggle';
    toggle.setAttribute('aria-label', 'Ouvrir le menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<span></span><span></span><span></span>';

    toggle.addEventListener('click', () => {
      const isOpen = nav.classList.toggle('open');
      toggle.classList.toggle('active', isOpen);
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    header.insertBefore(toggle, nav);
  });

  const sidebar = document.querySelector('.sidebar');
  if (sidebar && !document.querySelector('.sidebar-toggle')) {
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'sidebar-toggle';
    toggle.setAttribute('aria-label', 'Ouvrir la navigation');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<span></span><span></span><span></span>';

    toggle.addEventListener('click', () => {
      const isOpen = sidebar.classList.toggle('open');
      toggle.classList.toggle('active', isOpen);
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    const anchor = document.querySelector('.dash-topbar') || document.querySelector('.dash-main');
    if (anchor) {
      anchor.parentNode.insertBefore(toggle, anchor);
    }
  }
}

/* ─── Protect page ─── */
function requireAuth() {
  if (!getToken()) { go('login.html'); return false; }
  return true;
}

/* ─── Badge HTML ─── */
function badge(statut) {
  const map = {
    'En attente': ['badge-attente', 'En attente'],
    'En cours':   ['badge-cours',   'En cours'],
    'Traité':     ['badge-traite',  'Traité'],
  };
  const [cls, label] = map[statut] || ['', statut];
  return `<span class="badge ${cls}">${label}</span>`;
}

/* ─── Counter animation ─── */
function animCount(el, target) {
  if (!el) return;
  let cur = 0;
  const step = Math.max(1, Math.ceil(target / 50));
  const t = setInterval(() => {
    cur = Math.min(cur + step, target);
    el.textContent = cur;
    if (cur >= target) clearInterval(t);
  }, 25);
}

initHamburgerMenu();

/***********************************************
 * AUTH
 ***********************************************/
async function doLogin(e) {
  e.preventDefault();
  const errEl = document.getElementById('errMsg');
  errEl.style.display = 'none';

  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const btn      = e.target.querySelector('button[type=submit]');
  btn.innerHTML  = '<span class="loading"></span>';
  btn.disabled   = true;

  try {
    const res  = await fetch(`${API}/auth/login`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body:    JSON.stringify({ email, password }),
    });
    const data = await res.json();

    if (!res.ok) {
      errEl.textContent = data.message || 'Identifiants incorrects';
      errEl.style.display = 'block';
      btn.innerHTML = 'Se connecter'; btn.disabled = false;
      return;
    }
    setAuth(data.token, data.user);
    toast('Connexion réussie ! 🎉');
    setTimeout(() => {
      if (data.user.role === 'citoyen') go('signalement.html');
      else go('dashboard.html');
    }, 700);
  } catch {
    errEl.textContent = '❌ Serveur inaccessible. Vérifiez que Laravel tourne sur le port 8000.';
    errEl.style.display = 'block';
    btn.innerHTML = 'Se connecter'; btn.disabled = false;
  }
}

async function doRegister(e) {
  e.preventDefault();
  const errEl = document.getElementById('errMsg');
  errEl.style.display = 'none';

  const body = {
    nom:                   document.getElementById('nom').value.trim(),
    email:                 document.getElementById('email').value.trim(),
    password:              document.getElementById('password').value,
    password_confirmation: document.getElementById('password_confirmation').value,
    phone:                 document.getElementById('phone')?.value.trim() || null,
  };

  const btn = e.target.querySelector('button[type=submit]');
  btn.innerHTML = '<span class="loading"></span>'; btn.disabled = true;

  try {
    const res  = await fetch(`${API}/auth/register`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body:    JSON.stringify(body),
    });
    const data = await res.json();

    if (!res.ok) {
      const firstErr = data.errors ? Object.values(data.errors)[0][0] : data.message;
      errEl.textContent = firstErr || 'Erreur lors de l\'inscription';
      errEl.style.display = 'block';
      btn.innerHTML = 'S\'inscrire'; btn.disabled = false;
      return;
    }
    setAuth(data.token, data.user);
    toast('Inscription réussie ! Bienvenue 🎉');
    setTimeout(() => go('signalement.html'), 900);
  } catch {
    errEl.textContent = '❌ Serveur inaccessible.';
    errEl.style.display = 'block';
    btn.innerHTML = 'S\'inscrire'; btn.disabled = false;
  }
}

async function doLogout() {
  if (getToken()) {
    await fetch(`${API}/auth/logout`, { method: 'POST', headers: jsonHeaders() }).catch(() => {});
  }
  clearAuth();
  go('login.html');
}

/***********************************************
 * INDEX (accueil)
 ***********************************************/
function initIndex() {
  const user  = getUser();
  const navEl = document.getElementById('navAuth');
  if (!navEl) return;
  if (user) {
    navEl.innerHTML = `
      <span style="color:#fff;font-size:.85rem">👤 ${user.nom}</span>
      <button class="btn-logout" onclick="doLogout()">Déconnexion</button>`;
  } else {
    navEl.innerHTML = `<a href="login.html">Connexion</a><a href="register.html">Inscription</a>`;
  }
}

/***********************************************
 * SIGNALEMENT
 ***********************************************/
async function initSignalement() {
  if (!requireAuth()) return;
  const form = document.getElementById('signalForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type=submit]');
    btn.innerHTML = '<span class="loading"></span> Envoi…'; btn.disabled = true;

    const fd = new FormData();
    fd.append('lieu',        document.getElementById('lieu').value);
    fd.append('description', document.getElementById('description').value);
    fd.append('type_dechet', document.getElementById('type_dechet').value);
    fd.append('quartier',    document.getElementById('quartier').value);
    const photo = document.getElementById('photo').files[0];
    if (photo) fd.append('photo', photo);

    const submit = async () => {
      try {
        const res  = await fetch(`${API}/signalements`, {
          method:  'POST',
          headers: authHeaders(),
          body:    fd,
        });
        const data = await res.json();
        if (!res.ok) {
          const err = data.errors ? Object.values(data.errors)[0][0] : data.message;
          toast(err || 'Erreur', 'error');
          btn.innerHTML = '📤 Envoyer'; btn.disabled = false;
          return;
        }
        toast('✅ Signalement envoyé avec succès !');
        form.reset();
        setTimeout(() => go('dashboard.html'), 1200);
      } catch {
        toast('Serveur inaccessible.', 'error');
        btn.innerHTML = '📤 Envoyer'; btn.disabled = false;
      }
    };

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        pos => { fd.append('latitude', pos.coords.latitude); fd.append('longitude', pos.coords.longitude); submit(); },
        ()  => { submit(); }
      );
    } else { submit(); }
  });
}

/***********************************************
 * DASHBOARD
 ***********************************************/
async function initDashboard() {
  if (!requireAuth()) return;
  const user = getUser();

  // Pill utilisateur
  const pill = document.getElementById('userPill');
  if (pill) pill.textContent = `👤 ${user?.nom || ''} — ${user?.role || ''}`;

  // Marquer lien actif
  document.querySelectorAll('.sidebar a').forEach(a => {
    if (a.href.endsWith('dashboard.html')) a.classList.add('active');
  });

  if (user?.role === 'admin' || user?.role === 'agent') {
    await loadAdminDashboard();
  } else {
    await loadCitoyenDashboard();
  }
  await loadChartsData();
}

async function loadAdminDashboard() {
  try {
    const [statsRes, listRes] = await Promise.all([
      fetch(`${API}/admin/dashboard`, { headers: jsonHeaders() }),
      fetch(`${API}/signalements?per_page=50`, { headers: jsonHeaders() }),
    ]);
    if (statsRes.ok) {
      const d = await statsRes.json();
      animCount(document.getElementById('statTotal'),   d.signalements.total);
      animCount(document.getElementById('statAttente'), d.signalements.en_attente);
      animCount(document.getElementById('statCours'),   d.signalements.en_cours);
      animCount(document.getElementById('statTraite'),  d.signalements.traites);
    }
    if (listRes.ok) {
      const d = await listRes.json();
      renderTable(d.data || []);
    }
  } catch { toast('Erreur chargement dashboard', 'error'); }
}

async function loadCitoyenDashboard() {
  try {
    const res = await fetch(`${API}/signalements`, { headers: jsonHeaders() });
    const d   = await res.json();
    const lst = d.data || [];
    animCount(document.getElementById('statTotal'),   lst.length);
    animCount(document.getElementById('statAttente'), lst.filter(s => s.statut === 'En attente').length);
    animCount(document.getElementById('statCours'),   lst.filter(s => s.statut === 'En cours').length);
    animCount(document.getElementById('statTraite'),  lst.filter(s => s.statut === 'Traité').length);
    renderTable(lst);
  } catch { toast('Erreur chargement', 'error'); }
}

function renderTable(list) {
  const tbody = document.getElementById('tableBody');
  if (!tbody) return;
  const user = getUser();
  const isStaff = user?.role === 'admin' || user?.role === 'agent';

  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:28px;color:#aaa">Aucun signalement</td></tr>';
    return;
  }

  tbody.innerHTML = list.map(s => `
    <tr>
      <td><strong>${s.lieu}</strong></td>
      <td>${s.quartier}</td>
      <td>${s.type_dechet}</td>
      <td>${badge(s.statut)}</td>
      <td>${s.user?.nom || s.utilisateur || '—'}</td>
      <td>${s.created_at ? new Date(s.created_at).toLocaleDateString('fr-FR') : '—'}</td>
      <td>
        ${isStaff ? `
          <button class="act-btn act-blue"  onclick="changerStatut(${s.id},'En cours')">En cours</button>
          <button class="act-btn act-green" onclick="changerStatut(${s.id},'Traité')">✔ Traité</button>
          ${user?.role === 'admin' ? `<button class="act-btn act-red" onclick="supprimerSignalement(${s.id})">🗑</button>` : ''}
        ` : ''}
        ${s.latitude ? `<button class="act-btn act-gray" onclick="voirCarte(${s.latitude},${s.longitude})">📍</button>` : ''}
      </td>
    </tr>`).join('');
}

async function changerStatut(id, statut) {
  try {
    const res = await fetch(`${API}/signalements/${id}/statut`, {
      method:  'PATCH',
      headers: jsonHeaders(),
      body:    JSON.stringify({ statut }),
    });
    if (res.ok) { toast('Statut mis à jour ✅'); await initDashboard(); }
    else { const d = await res.json(); toast(d.message || 'Erreur', 'error'); }
  } catch { toast('Erreur réseau', 'error'); }
}

async function supprimerSignalement(id) {
  if (!confirm('Supprimer ce signalement ?')) return;
  try {
    const res = await fetch(`${API}/signalements/${id}`, { method: 'DELETE', headers: jsonHeaders() });
    if (res.ok) { toast('Signalement supprimé'); await initDashboard(); }
    else toast('Erreur suppression', 'error');
  } catch { toast('Erreur réseau', 'error'); }
}

function voirCarte(lat, lng) {
  sessionStorage.setItem('dc_lat', lat);
  sessionStorage.setItem('dc_lng', lng);
  go('carte.html');
}

async function appliquerFiltres() {
  const statut   = document.getElementById('filtreStatut')?.value  || '';
  const quartier = document.getElementById('filtreQuartier')?.value || '';
  const type     = document.getElementById('filtreType')?.value     || '';
  let url = `${API}/signalements?`;
  if (statut)   url += `statut=${encodeURIComponent(statut)}&`;
  if (quartier) url += `quartier=${encodeURIComponent(quartier)}&`;
  if (type)     url += `type_dechet=${encodeURIComponent(type)}&`;

  try {
    const res = await fetch(url, { headers: jsonHeaders() });
    const d   = await res.json();
    renderTable(d.data || []);
  } catch { toast('Erreur filtre', 'error'); }
}

async function loadChartsData() {
  if (typeof Chart === 'undefined') return;
  const user = getUser();
  try {
    let en_attente = 0, en_cours = 0, traites = 0, quartiers = {};

    if (user?.role === 'admin' || user?.role === 'agent') {
      const res = await fetch(`${API}/admin/dashboard`, { headers: jsonHeaders() });
      if (res.ok) {
        const d = await res.json();
        en_attente = d.signalements.en_attente;
        en_cours   = d.signalements.en_cours;
        traites    = d.signalements.traites;
        d.signalements.par_quartier?.forEach(q => { quartiers[q.quartier] = q.total; });
      }
    } else {
      const res = await fetch(`${API}/signalements`, { headers: jsonHeaders() });
      if (res.ok) {
        const d = await res.json();
        const lst = d.data || [];
        en_attente = lst.filter(s => s.statut === 'En attente').length;
        en_cours   = lst.filter(s => s.statut === 'En cours').length;
        traites    = lst.filter(s => s.statut === 'Traité').length;
        lst.forEach(s => { quartiers[s.quartier] = (quartiers[s.quartier] || 0) + 1; });
      }
    }

    const sc = document.getElementById('statusChart');
    if (sc) new Chart(sc, {
      type: 'doughnut',
      data: {
        labels: ['En attente', 'En cours', 'Traités'],
        datasets: [{ data: [en_attente, en_cours, traites], backgroundColor: ['#e65100','#1565c0','#2e7d32'] }]
      },
      options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
    });

    const zc = document.getElementById('zoneChart');
    if (zc && Object.keys(quartiers).length) new Chart(zc, {
      type: 'bar',
      data: {
        labels: Object.keys(quartiers),
        datasets: [{ label: 'Signalements', data: Object.values(quartiers), backgroundColor: '#2e7d32', borderRadius: 5 }]
      },
      options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
  } catch {}
}

/***********************************************
 * CARTE (Leaflet + OpenStreetMap, sans clé API)
 ***********************************************/
async function initCarte() {
  if (!requireAuth()) return;

  const focusLat = parseFloat(sessionStorage.getItem('dc_lat')) || 4.0511;
  const focusLng = parseFloat(sessionStorage.getItem('dc_lng')) || 9.7679;
  sessionStorage.removeItem('dc_lat');
  sessionStorage.removeItem('dc_lng');

  const map = L.map('map').setView([focusLat, focusLng], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>'
  }).addTo(map);

  // Icônes colorées par statut
  const icons = {
    'En attente': L.divIcon({ className:'', html:'<div style="background:#e65100;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>' }),
    'En cours':   L.divIcon({ className:'', html:'<div style="background:#1565c0;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>' }),
    'Traité':     L.divIcon({ className:'', html:'<div style="background:#2e7d32;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>' }),
  };

  try {
    // Route publique pour la carte
    const res  = await fetch(`${API}/signalements/carte`);
    const list = await res.json();
    list.forEach(s => {
      if (s.latitude && s.longitude) {
        L.marker([s.latitude, s.longitude], { icon: icons[s.statut] || icons['En attente'] })
          .addTo(map)
          .bindPopup(`
            <b>${s.lieu}</b><br>
            Quartier : ${s.quartier}<br>
            Type : ${s.type}<br>
            Statut : <strong>${s.statut}</strong><br>
            <small>${s.date}</small>
          `);
      }
    });
    toast(`${list.length} signalement(s) affiché(s) sur la carte`);
  } catch { toast('Erreur chargement carte', 'error'); }
}

/***********************************************
 * ABONNEMENT
 ***********************************************/
async function initAbonnement() {
  if (!requireAuth()) return;
  // Afficher le plan actuel
  try {
    const res = await fetch(`${API}/abonnements/mon-abonnement`, { headers: jsonHeaders() });
    if (res.ok) {
      const d = await res.json();
      const el = document.getElementById('planActuel');
      if (el && d.plan_actuel) el.textContent = `Plan actuel : ${d.plan_actuel}`;
    }
  } catch {}
}

async function subscribe(plan) {
  if (!requireAuth()) return;
  if (!confirm(`Souscrire au plan "${plan}" ?`)) return;
  try {
    const res  = await fetch(`${API}/abonnements`, {
      method:  'POST',
      headers: jsonHeaders(),
      body:    JSON.stringify({ plan }),
    });
    const data = await res.json();
    if (res.ok) {
      toast(`✅ Abonnement "${plan}" activé !`);
      // Mettre à jour le user en localStorage
      const u = getUser();
      if (u) { u.abonnement = plan; localStorage.setItem('dc_user', JSON.stringify(u)); }
      setTimeout(() => go('ramassage.html'), 1200);
    } else { toast(data.message || 'Erreur', 'error'); }
  } catch { toast('Serveur inaccessible.', 'error'); }
}

/***********************************************
 * RAMASSAGE
 ***********************************************/
let ramPrix = 0;
function selectRam(el, prix) {
  document.querySelectorAll('.ram-option').forEach(e => e.classList.remove('active'));
  el.classList.add('active');
  ramPrix = prix;
  const freq = prix === 2000 ? '1_semaine' : '2_semaine';
  el.dataset.freq = freq;
  document.getElementById('planLabel').textContent = `Plan sélectionné : ${prix} FCFA`;
}

async function payerRamassage() {
  if (!requireAuth()) return;
  const adresse = document.getElementById('adresse').value.trim();
  const desc    = document.getElementById('descDomicile').value.trim();
  const phone   = document.getElementById('phonePay').value.trim();

  if (!adresse || !phone || ramPrix === 0) {
    toast('Remplissez tous les champs et sélectionnez un plan.', 'error');
    return;
  }
  if (!/^6[0-9]{8}$/.test(phone)) {
    toast('Numéro Orange Money invalide (doit commencer par 6 et avoir 9 chiffres).', 'error');
    return;
  }

  const freq = ramPrix === 2000 ? '1_semaine' : '2_semaine';
  const btn  = document.getElementById('btnPayer');
  btn.innerHTML = '<span class="loading"></span> Traitement…'; btn.disabled = true;

  try {
    const res  = await fetch(`${API}/ramassage`, {
      method:  'POST',
      headers: jsonHeaders(),
      body:    JSON.stringify({ adresse, description_domicile: desc, frequence: freq, phone_paiement: phone }),
    });
    const data = await res.json();
    if (res.ok) {
      document.getElementById('successBox').style.display = 'block';
      document.getElementById('successBox').innerHTML = `
        ✅ Ramassage planifié !<br>
        <small>Référence paiement : <strong>${data.reference}</strong> — ${data.prix}</small>`;
      toast('Ramassage souscrit avec succès !');
    } else {
      toast(data.message || 'Erreur', 'error');
      btn.innerHTML = '💳 Payer maintenant'; btn.disabled = false;
    }
  } catch {
    toast('Serveur inaccessible.', 'error');
    btn.innerHTML = '💳 Payer maintenant'; btn.disabled = false;
  }
}

/***********************************************
 * STATISTIQUES
 ***********************************************/
async function initStatistiques() {
  if (!requireAuth()) return;
  const user = getUser();
  try {
    if (user?.role === 'admin' || user?.role === 'agent') {
      const res = await fetch(`${API}/signalements/stats/globales`, { headers: jsonHeaders() });
      if (res.ok) {
        const d = await res.json();
        animCount(document.getElementById('statTotal'),   d.total);
        animCount(document.getElementById('statAttente'), d.en_attente);
        animCount(document.getElementById('statCours'),   d.en_cours);
        animCount(document.getElementById('statTraite'),  d.traites);
        if (typeof Chart !== 'undefined') {
          new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: { labels: ['En attente','En cours','Traités'], datasets: [{ data: [d.en_attente, d.en_cours, d.traites], backgroundColor: ['#e65100','#1565c0','#2e7d32'] }] },
            options: { plugins: { legend: { position: 'bottom' } } }
          });
          if (d.par_quartier?.length) new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: { labels: d.par_quartier.map(q => q.quartier), datasets: [{ label: 'Signalements', data: d.par_quartier.map(q => q.total), backgroundColor: '#2e7d32', borderRadius: 5 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
          });
        }
      }
    } else {
      const res = await fetch(`${API}/signalements`, { headers: jsonHeaders() });
      if (res.ok) {
        const d   = await res.json();
        const lst = d.data || [];
        animCount(document.getElementById('statTotal'),   lst.length);
        animCount(document.getElementById('statAttente'), lst.filter(s => s.statut === 'En attente').length);
        animCount(document.getElementById('statCours'),   lst.filter(s => s.statut === 'En cours').length);
        animCount(document.getElementById('statTraite'),  lst.filter(s => s.statut === 'Traité').length);
      }
    }
  } catch { toast('Erreur chargement stats', 'error'); }
}

/***********************************************
 * PARAMÈTRES
 ***********************************************/
async function initParam() {
  if (!requireAuth()) return;
  const user = getUser();
  if (user) {
    const n = document.getElementById('nomUser');
    const p = document.getElementById('phoneUser');
    if (n) n.value = user.nom || '';
    if (p) p.value = user.phone || '';
  }
}

async function saveProfile() {
  const nom   = document.getElementById('nomUser').value.trim();
  const phone = document.getElementById('phoneUser')?.value.trim();
  if (!nom) { toast('Le nom est requis.', 'error'); return; }

  try {
    const res  = await fetch(`${API}/auth/profile`, {
      method:  'PUT',
      headers: jsonHeaders(),
      body:    JSON.stringify({ nom, phone }),
    });
    const data = await res.json();
    if (res.ok) {
      const u = getUser();
      if (u) { u.nom = data.user.nom; u.phone = data.user.phone; localStorage.setItem('dc_user', JSON.stringify(u)); }
      toast('✅ Profil mis à jour !');
    } else { toast(data.message || 'Erreur', 'error'); }
  } catch { toast('Serveur inaccessible.', 'error'); }
}

async function changePassword() {
  const curr = document.getElementById('currentPwd').value;
  const nw   = document.getElementById('newPwd').value;
  const conf = document.getElementById('confirmPwd').value;
  if (!curr || !nw || !conf) { toast('Remplissez tous les champs.', 'error'); return; }
  if (nw !== conf) { toast('Les mots de passe ne correspondent pas.', 'error'); return; }

  try {
    const res  = await fetch(`${API}/auth/password`, {
      method:  'PUT',
      headers: jsonHeaders(),
      body:    JSON.stringify({ current_password: curr, password: nw, password_confirmation: conf }),
    });
    const data = await res.json();
    if (res.ok) { toast('✅ Mot de passe modifié !'); document.getElementById('currentPwd').value = document.getElementById('newPwd').value = document.getElementById('confirmPwd').value = ''; }
    else { toast(data.message || 'Erreur', 'error'); }
  } catch { toast('Serveur inaccessible.', 'error'); }
}

/***********************************************
 * ADMIN — GESTION UTILISATEURS
 ***********************************************/
async function initAdminUsers() {
  if (!requireAuth()) return;
  const user = getUser();
  if (!user || user.role !== 'admin') { toast('Accès réservé aux administrateurs.', 'error'); go('dashboard.html'); return; }

  await loadUsers();
}

async function loadUsers(search = '', role = '') {
  let url = `${API}/admin/users?`;
  if (search) url += `search=${encodeURIComponent(search)}&`;
  if (role)   url += `role=${role}&`;

  try {
    const res = await fetch(url, { headers: jsonHeaders() });
    const d   = await res.json();
    const tbody = document.getElementById('usersTable');
    if (!tbody) return;
    const list = d.data || [];

    tbody.innerHTML = list.map(u => `
      <tr>
        <td>${u.nom}</td>
        <td>${u.email}</td>
        <td>${u.role}</td>
        <td>${u.abonnement || '—'}</td>
        <td>${u.phone || '—'}</td>
        <td>${u.signalements_count || 0}</td>
        <td>
          <span class="badge ${u.is_active ? 'badge-traite' : 'badge-attente'}">${u.is_active ? 'Actif' : 'Inactif'}</span>
        </td>
        <td>
          <button class="act-btn act-blue" onclick="toggleUser(${u.id})">${u.is_active ? 'Désactiver' : 'Activer'}</button>
          <button class="act-btn act-red"  onclick="deleteUser(${u.id})">🗑</button>
        </td>
      </tr>`).join('') || '<tr><td colspan="8" style="text-align:center;padding:20px;color:#aaa">Aucun utilisateur</td></tr>';
  } catch { toast('Erreur chargement utilisateurs', 'error'); }
}

async function toggleUser(id) {
  try {
    const res = await fetch(`${API}/admin/users/${id}/toggle`, { method: 'PATCH', headers: jsonHeaders() });
    const d   = await res.json();
    if (res.ok) { toast(d.message); await loadUsers(); }
    else toast(d.message || 'Erreur', 'error');
  } catch { toast('Erreur réseau', 'error'); }
}

async function deleteUser(id) {
  if (!confirm('Supprimer cet utilisateur définitivement ?')) return;
  try {
    const res = await fetch(`${API}/admin/users/${id}`, { method: 'DELETE', headers: jsonHeaders() });
    if (res.ok) { toast('Utilisateur supprimé'); await loadUsers(); }
    else toast('Erreur suppression', 'error');
  } catch { toast('Erreur réseau', 'error'); }
}

async function creerAgent(e) {
  e.preventDefault();
  const body = {
    nom:      document.getElementById('agentNom').value.trim(),
    email:    document.getElementById('agentEmail').value.trim(),
    password: document.getElementById('agentPassword').value,
    role:     document.getElementById('agentRole').value,
    phone:    document.getElementById('agentPhone').value.trim(),
  };
  try {
    const res  = await fetch(`${API}/admin/users`, { method: 'POST', headers: jsonHeaders(), body: JSON.stringify(body) });
    const data = await res.json();
    if (res.ok) {
      toast(`✅ ${data.user.nom} créé avec succès !`);
      e.target.reset();
      await loadUsers();
    } else {
      const err = data.errors ? Object.values(data.errors)[0][0] : data.message;
      toast(err || 'Erreur', 'error');
    }
  } catch { toast('Serveur inaccessible.', 'error'); }
}

/***********************************************
 * INIT GLOBAL — détecte la page et lance la bonne fonction
 ***********************************************/
document.addEventListener('DOMContentLoaded', () => {
  const page = location.pathname.split('/').pop() || 'index.html';

  // Bouton déconnexion global (onclick="doLogout()")
  document.querySelectorAll('[data-logout]').forEach(b => b.addEventListener('click', doLogout));

  switch (page) {
    case '':
    case 'index.html':      initIndex(); break;
    case 'login.html':
    case 'login-user.html': document.getElementById('loginForm')?.addEventListener('submit', doLogin); break;
    case 'register.html':   document.getElementById('registerForm')?.addEventListener('submit', doRegister); break;
    case 'signalement.html':initSignalement(); break;
    case 'dashboard.html':  initDashboard(); break;
    case 'carte.html':      initCarte(); break;
    case 'abonnement.html': initAbonnement(); break;
    case 'statistic.html':  initStatistiques(); break;
    case 'param.html':      initParam(); break;
    case 'admin-users.html':initAdminUsers(); break;
  }
});
