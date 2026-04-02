/* ============================================================
   SIG Cali — dashboard.js  (stack PHP)
   ============================================================ */

/* ── Helpers ────────────────────────────────────────────── */
function showToast(msg, type = '') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast ' + type;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3800);
}

function setErr(msgId, elId, show) {
  const msg = document.getElementById(msgId);
  if (msg) msg.classList.toggle('show', show);
  if (elId) {
    const el = document.getElementById(elId);
    if (el) el.classList.toggle('err', show);
  }
  return show;
}

const TIPO_LABEL = {
  inundacion:        '🌊 Inundación',
  alumbrado_publico: '💡 Alumbrado público',
  huecos:            '🕳️ Huecos / Vías',
  transito:          '🚦 Tránsito',
  derrumbe:          '⛰️ Derrumbe',
  otro:              '📌 Otro',
};

const ESTADO_LABEL = {
  pendiente:  'Pendiente',
  en_proceso: 'En proceso',
  resuelto:   'Resuelto',
};

const PRI_COLOR = { alta: '#b52b2b', media: '#a86209', baja: '#196b40' };

function formatFecha(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

/* ── Mapas Leaflet ──────────────────────────────────────── */
let mapViewer  = null;
let mapPicker  = null;
let pickerMark = null;
let mainMarkers = [];
let incidentesCache = [];

function initMapViewer() {
  mapViewer = L.map('mapViewer').setView([3.4516, -76.5320], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(mapViewer);
}

function initMapPicker() {
  mapPicker = L.map('mapPicker').setView([3.4516, -76.5320], 14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 19,
  }).addTo(mapPicker);

  mapPicker.on('click', (e) => {
    const { lat, lng } = e.latlng;

    document.getElementById('incLatitud').value  = lat.toFixed(7);
    document.getElementById('incLongitud').value = lng.toFixed(7);
    document.getElementById('dispLat').textContent = lat.toFixed(6);
    document.getElementById('dispLng').textContent = lng.toFixed(6);

    // Actualizar estado visual
    const status = document.getElementById('coordStatus');
    if (status) {
      status.textContent = 'Ubicación capturada';
      status.classList.add('active');
    }

    if (pickerMark) {
      pickerMark.setLatLng(e.latlng);
    } else {
      pickerMark = L.circleMarker(e.latlng, {
        radius: 9, fillColor: '#1c5f8a', color: '#fff',
        weight: 2.5, fillOpacity: 1,
      }).addTo(mapPicker).bindPopup('Ubicación seleccionada').openPopup();
    }

    document.getElementById('errCoordenada').classList.remove('show');
  });
}

/* ── Icono de pin según prioridad ───────────────────────── */
function createPin(prioridad) {
  const color = PRI_COLOR[prioridad] || '#6b7b8d';
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="26" height="36" viewBox="0 0 26 36">
    <path d="M13 0C5.82 0 0 5.82 0 13c0 9.1 13 23 13 23S26 22.1 26 13C26 5.82 20.18 0 13 0z" fill="${color}" opacity=".9"/>
    <circle cx="13" cy="13" r="5.5" fill="white" opacity=".92"/>
  </svg>`;
  return L.divIcon({ html: svg, iconSize: [26, 36], iconAnchor: [13, 36], popupAnchor: [0, -34], className: '' });
}

/* ── Cargar y renderizar incidentes ─────────────────────── */
async function cargarIncidentes() {
  try {
    const res  = await fetch('incidentes.php', { credentials: 'include' });
    if (!res.ok) throw new Error('No autorizado');
    const data = await res.json();
    incidentesCache = data;

    actualizarKPIs(data);
    actualizarMapa(data);
    actualizarTabla(data);

    const cnt = document.getElementById('mapIncCount');
    if (cnt) cnt.textContent = `${data.length} incidente${data.length !== 1 ? 's' : ''} visible${data.length !== 1 ? 's' : ''}`;

    const tc = document.getElementById('tableCount');
    if (tc) tc.textContent = `${data.length} registro${data.length !== 1 ? 's' : ''}`;

  } catch (e) {
    showToast('Error al cargar los incidentes', 'error');
  }
}

function actualizarKPIs(data) {
  const total = data.length;
  const alta  = data.filter(d => d.prioridad === 'alta').length;
  const media = data.filter(d => d.prioridad === 'media').length;
  const baja  = data.filter(d => d.prioridad === 'baja').length;

  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  set('kpiTotal', total);
  set('kpiAlta',  alta);
  set('kpiMedia', media);
  set('kpiBaja',  baja);
}

function actualizarMapa(data) {
  mainMarkers.forEach(m => mapViewer.removeLayer(m));
  mainMarkers = [];

  data.forEach(inc => {
    if (!inc.latitud || !inc.longitud) return;
    const mk = L.marker([parseFloat(inc.latitud), parseFloat(inc.longitud)], {
      icon: createPin(inc.prioridad),
    }).addTo(mapViewer).bindPopup(`
      <div style="font-family:sans-serif;font-size:12px;min-width:170px">
        <strong style="font-size:13px">${TIPO_LABEL[inc.tipo] || inc.tipo}</strong><br/>
        <span style="color:#6b7b8d;font-size:11px">${inc.direccion || 'Sin dirección'}</span>
        <div style="margin-top:7px;display:flex;gap:8px;align-items:center">
          <span style="color:${PRI_COLOR[inc.prioridad]};font-weight:600;font-size:10px;text-transform:uppercase">${inc.prioridad}</span>
          <span style="color:#c5cdd6">·</span>
          <span style="color:#9aabb8;font-size:10px">${formatFecha(inc.fecha_reporte)}</span>
        </div>
      </div>
    `);
    mk.on('click', () => abrirDetalle(inc));
    mainMarkers.push(mk);
  });
}

function actualizarTabla(data) {
  const tbody = document.getElementById('tbodyIncidentes');
  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="table-empty">
      <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="opacity:.3;display:block;margin:0 auto 8px">
        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      No hay incidentes registrados.
    </td></tr>`;
    return;
  }
  tbody.innerHTML = data.map(inc => `
    <tr onclick="abrirDetalle(${JSON.stringify(inc).replace(/"/g, '&quot;')})">
      <td><span style="font-family:var(--mono);font-size:.7rem;color:var(--text-muted)">#${inc.id}</span></td>
      <td>${TIPO_LABEL[inc.tipo] || inc.tipo}</td>
      <td><span class="badge badge-${inc.prioridad}">${inc.prioridad}</span></td>
      <td>${inc.comuna || '—'}</td>
      <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${inc.direccion || '—'}</td>
      <td><span class="badge badge-${inc.estado === 'pendiente' ? 'pend' : inc.estado === 'en_proceso' ? 'proc' : 'res'}">${ESTADO_LABEL[inc.estado] || inc.estado}</span></td>
      <td style="font-size:.76rem;color:var(--text-muted);white-space:nowrap">${formatFecha(inc.fecha_reporte)}</td>
    </tr>
  `).join('');
}

/* ── Filtro de tabla ────────────────────────────────────── */
function filterTable(q) {
  const lq = q.toLowerCase();
  const filtered = incidentesCache.filter(inc =>
    Object.values(inc).some(v => String(v).toLowerCase().includes(lq))
  );
  actualizarTabla(filtered);
}

/* ── Selector de prioridad ──────────────────────────────── */
function selectPriority(id) {
  ['priAlt', 'priMed', 'priBaj'].forEach(pid => {
    const el = document.getElementById(pid);
    if (!el) return;
    // Quitar todas las clases sel-*
    el.classList.remove('sel-alta', 'sel-media', 'sel-baja');
  });
  const val = document.getElementById(id)?.dataset.val;
  if (val) document.getElementById(id).classList.add(`sel-${val}`);
  const errPri = document.getElementById('errPrioridad');
  if (errPri) errPri.classList.remove('show');
}

/* ── Preview de fotografía ──────────────────────────────── */
function previewFoto(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('fotoImg').src = e.target.result;
    document.getElementById('fotoPreview').style.display = 'block';
    document.getElementById('fileLabel').textContent = input.files[0].name;
  };
  reader.readAsDataURL(input.files[0]);
}

function removeFoto() {
  document.getElementById('incFoto').value = '';
  document.getElementById('fotoPreview').style.display = 'none';
  document.getElementById('fileLabel').textContent = 'Arrastra la imagen o haz clic aquí';
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('fileDrop').classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (!file) return;
  const dt = new DataTransfer();
  dt.items.add(file);
  const input = document.getElementById('incFoto');
  input.files = dt.files;
  previewFoto(input);
}

/* ── Envío del formulario de incidente ──────────────────── */
async function handleSubmitIncidente(e) {
  e.preventDefault();

  const tipo      = document.getElementById('incTipo').value;
  const prioridad = document.querySelector('input[name="prioridad"]:checked')?.value;
  const latitud   = document.getElementById('incLatitud').value;
  const longitud  = document.getElementById('incLongitud').value;

  const e1 = setErr('errTipo',       'incTipo', !tipo);
  const e2 = setErr('errPrioridad',  null,      !prioridad);
  const e3 = setErr('errCoordenada', null,      !latitud || !longitud);
  if (e1 || e2 || e3) return;

  const btn = document.getElementById('btnSubmit');
  btn.disabled = true;
  btn.innerHTML = 'Guardando…';

  const fd = new FormData(document.getElementById('formIncidente'));

  try {
    const res  = await fetch('guardar_incidente.php', {
      method: 'POST',
      credentials: 'include',
      body: fd,
    });
    const data = await res.json();

    if (!res.ok || data.error) {
      showToast(data.error || 'Error al registrar el incidente', 'error');
    } else {
      showToast(`✓ Incidente registrado correctamente`, 'success');
      document.getElementById('formIncidente').reset();
      ['priAlt', 'priMed', 'priBaj'].forEach(p => {
        const el = document.getElementById(p);
        if (el) el.classList.remove('sel-alta', 'sel-media', 'sel-baja');
      });
      removeFoto();
      if (pickerMark) { mapPicker.removeLayer(pickerMark); pickerMark = null; }
      document.getElementById('dispLat').textContent = '—';
      document.getElementById('dispLng').textContent = '—';
      const status = document.getElementById('coordStatus');
      if (status) { status.textContent = 'Sin selección'; status.classList.remove('active'); }
      await cargarIncidentes();
    }
  } catch {
    showToast('Error de conexión con el servidor', 'error');
  }

  btn.disabled = false;
  btn.innerHTML = `<svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/></svg> Registrar incidente`;
}

/* ── Modal detalle ──────────────────────────────────────── */
function abrirDetalle(inc) {
  if (typeof inc === 'string') {
    try { inc = JSON.parse(inc); } catch { return; }
  }
  document.getElementById('detalleTipo').textContent = TIPO_LABEL[inc.tipo] || inc.tipo;
  document.getElementById('detalleDir').textContent  = inc.direccion || 'Sin dirección registrada';

  document.getElementById('detalleGrid').innerHTML = `
    <div class="detalle-item"><label>Prioridad</label><p><span class="badge badge-${inc.prioridad}">${inc.prioridad}</span></p></div>
    <div class="detalle-item"><label>Estado</label><p>${ESTADO_LABEL[inc.estado] || inc.estado}</p></div>
    <div class="detalle-item"><label>Comuna</label><p>${inc.comuna || '—'}</p></div>
    <div class="detalle-item"><label>Barrio</label><p>${inc.barrio || '—'}</p></div>
    <div class="detalle-item"><label>Latitud</label><p style="font-family:var(--mono);font-size:.78rem">${inc.latitud}</p></div>
    <div class="detalle-item"><label>Longitud</label><p style="font-family:var(--mono);font-size:.78rem">${inc.longitud}</p></div>
    <div class="detalle-item"><label>Fecha reporte</label><p>${formatFecha(inc.fecha_reporte)}</p></div>
    ${inc.descripcion ? `<div class="detalle-item" style="grid-column:1/-1"><label>Descripción</label><p>${inc.descripcion}</p></div>` : ''}
  `;

  const fotoDiv = document.getElementById('detalleFoto');
  if (inc.fotografia) {
    document.getElementById('detalleFotoImg').src = 'uploads/' + inc.fotografia;
    fotoDiv.style.display = 'block';
  } else {
    fotoDiv.style.display = 'none';
  }

  document.getElementById('detalleOverlay').classList.add('open');
}

function closeDetalle() {
  document.getElementById('detalleOverlay').classList.remove('open');
}

document.getElementById('detalleOverlay').addEventListener('click', e => {
  if (e.target === document.getElementById('detalleOverlay')) closeDetalle();
});

/* ── Init ───────────────────────────────────────────────── */
function init() {
  initMapViewer();
  initMapPicker();
  cargarIncidentes();
  // Refresco automático cada 60 s
  setInterval(cargarIncidentes, 60000);
}

init();
