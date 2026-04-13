/* ── Helpers y Configuración ────────────────────────────── */
function showToast(msg, type = '') {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.className = 'toast ' + type + ' show';
  setTimeout(() => t.classList.remove('show'), 3800);
}

const TIPO_LABEL = {
  inundacion: '🌊 Inundación',
  alumbrado_publico: '💡 Alumbrado público',
  huecos: '🕳️ Huecos / Vías',
  transito: '🚦 Tránsito',
  derrumbe: '⛰️ Derrumbe',
  otro: '📌 Otro'
};

const PRI_COLOR = { alta: '#b52b2b', media: '#a86209', baja: '#196b40' };

// Variables globales del mapa
let mapMain, mapPicker, markerGroup, pickerMark, wmsIncidentes;
let incidentesData = [];

document.addEventListener('DOMContentLoaded', () => {
  initMaps();
  cargarIncidentes();

  const form = document.getElementById('formIncidente');
  if (form) form.addEventListener('submit', handleSubmitIncidente);
  
  // Exponer filterTable para uso global
  window.filterTableCallback = filterTable;
});

function initMaps() {
  const cali = [3.4516, -76.5320];
  
  // ── MAPA PRINCIPAL ──────────────────────────────────────
  mapMain = L.map('mapViewer', { zoomControl: false }).setView(cali, 13);
  
  // Capa Base (CartoDB)
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>'
  }).addTo(mapMain);

  // NUEVA CAPA WMS (GeoServer)
  // Esta capa proyectará los datos directamente desde tu GeoServer
  wmsIncidentes = L.tileLayer.wms("http://localhost:8080/geoserver/incidentes_cali/wms", {
    layers: 'incidentes_cali:registros', // Verifica que este nombre coincida en GeoServer
    format: 'image/png',
    transparent: true,
    version: '1.1.1',
    attribution: "GeoServer Local - Incidentes Cali"
  }).addTo(mapMain);

  markerGroup = L.layerGroup().addTo(mapMain);

  // ── MAPA SELECTOR (Para registro) ───────────────────────
  mapPicker = L.map('mapPicker').setView(cali, 13);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>'
  }).addTo(mapPicker);

  mapPicker.on('click', e => {
    if (pickerMark) mapPicker.removeLayer(pickerMark);
    pickerMark = L.marker(e.latlng).addTo(mapPicker);
    document.getElementById('incLatitud').value = e.latlng.lat.toFixed(6);
    document.getElementById('incLongitud').value = e.latlng.lng.toFixed(6);
    document.getElementById('dispLat').textContent = e.latlng.lat.toFixed(6);
    document.getElementById('dispLng').textContent = e.latlng.lng.toFixed(6);
    document.getElementById('coordStatus').textContent = '✓ Coordenada capturada';
    document.getElementById('coordStatus').classList.add('active');
  });
}

/* ── Acciones de Servidor ───────────────────────────────── */

async function cargarIncidentes() {
  try {
    const res = await fetch('consultar_incidente.php');
    if (!res.ok) throw new Error('Error en el servidor');
    const data = await res.json();
    
    incidentesData = data || [];
    
    // Mantenemos la actualización de la lógica visual
    actualizarMapa(incidentesData); 
    actualizarTabla(incidentesData);
    actualizarKPIs(incidentesData);
    
    const mapIncCount = document.getElementById('mapIncCount');
    if (mapIncCount) mapIncCount.textContent = incidentesData.length + ' incidentes visibles';
    
    const tableCount = document.getElementById('tableCount');
    if (tableCount) tableCount.textContent = incidentesData.length;
    
  } catch (e) {
    console.error(e);
    showToast('Error al cargar incidentes', 'error');
  }
}

async function handleSubmitIncidente(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSubmit');
  btn.disabled = true;
  btn.textContent = 'Guardando...';

  const fd = new FormData(e.target);

  // Validaciones existentes
  if (!fd.get('latitud') || fd.get('latitud') === '') {
    showToast('❌ Debes seleccionar una ubicación en el mapa', 'error');
    btn.disabled = false;
    btn.textContent = 'Registrar incidente';
    return;
  }

  try {
    const res = await fetch('guardar_incidente.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.status === 'ok') {
      showToast('✓ Incidente registrado correctamente', 'success');
      e.target.reset();
      limpiarFormulario();
      
      // Recargar datos locales
      await cargarIncidentes();
      
      // FORZAR REFRESCO DE CAPA WMS
      // Añadimos un parámetro aleatorio para evitar que el navegador use la imagen en caché
      if (wmsIncidentes) {
        wmsIncidentes.setParams({ _refresh: Date.now() });
      }
      
    } else {
      throw new Error(data.message || 'Error al guardar');
    }
  } catch (err) {
    showToast('Error: ' + err.message, 'error');
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Registrar incidente';
  }
}

/* ── Renderizado de UI ──────────────────────────────────── */

function actualizarMapa(data) {
  if (!markerGroup) return;
  markerGroup.clearLayers();
  
  // Nota: Estos marcadores son adicionales a los del WMS. 
  // Si prefieres ver SOLO los del WMS, puedes comentar este ciclo forEach.
  data.forEach(inc => {
    const p = inc.prioridad ? inc.prioridad.toLowerCase() : 'baja';
    const color = PRI_COLOR[p] || PRI_COLOR.baja;
    
    const marker = L.circleMarker([parseFloat(inc.latitud), parseFloat(inc.longitud)], {
      radius: 10,
      fillColor: color,
      color: '#fff',
      weight: 2,
      fillOpacity: 0.9
    }).addTo(markerGroup);
    
    marker.bindPopup(`
      <strong>${TIPO_LABEL[inc.tipo_incidente] || inc.tipo_incidente}</strong><br/>
      Prioridad: <span style="color:${color}">${inc.prioridad}</span><br/>
      ${inc.direccion || ''}<br/>
      <button onclick="verDetalle(${JSON.stringify(inc).replace(/"/g, '&quot;')})" style="margin-top:5px;padding:4px 8px;background:#1c5f8a;color:#fff;border:none;border-radius:3px;cursor:pointer;">Ver detalles</button>
    `);
  });
}

function actualizarTabla(data) {
  const tbody = document.getElementById('tbodyIncidentes');
  if (!tbody) return;
  
  if (!data || data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="table-empty">No hay incidentes registrados</td></tr>`;
    return;
  }

  tbody.innerHTML = data.map((inc, index) => {
    const p = inc.prioridad ? inc.prioridad.toLowerCase() : 'baja';
    const fecha = inc.fecha_reporte ? new Date(inc.fecha_reporte).toLocaleDateString('es-CO') : '—';
    const tipoMostrar = TIPO_LABEL[inc.tipo_incidente] || inc.tipo_incidente || '—';
    const estado = inc.estado || 'Pendiente';
    
    let estadoClass = 'badge-pend';
    if (estado.toLowerCase() === 'en proceso') estadoClass = 'badge-proc';
    if (estado.toLowerCase() === 'resuelto') estadoClass = 'badge-res';
    
    return `
      <tr onclick='verDetalle(${JSON.stringify(inc).replace(/'/g, "&#39;")})' style="cursor:pointer">
        <td>${index + 1}</td>
        <td>${tipoMostrar}</td>
        <td><span class="badge badge-${p}">${inc.prioridad || 'Baja'}</span></td>
        <td>${inc.comuna || '—'}</td>
        <td>${inc.direccion || '—'}</td>
        <td><span class="badge ${estadoClass}">${estado}</span></td>
        <td>${fecha}</td>
      </tr>
    `;
  }).join('');
}

function actualizarKPIs(data) {
  const total = data.length;
  const alta = data.filter(i => i.prioridad && i.prioridad.toLowerCase() === 'alta').length;
  const media = data.filter(i => i.prioridad && i.prioridad.toLowerCase() === 'media').length;
  const baja = data.filter(i => i.prioridad && i.prioridad.toLowerCase() === 'baja').length;
  
  const kpiTotal = document.getElementById('kpiTotal');
  const kpiAlta = document.getElementById('kpiAlta');
  const kpiMedia = document.getElementById('kpiMedia');
  const kpiBaja = document.getElementById('kpiBaja');
  
  if (kpiTotal) kpiTotal.textContent = total;
  if (kpiAlta) kpiAlta.textContent = alta;
  if (kpiMedia) kpiMedia.textContent = media;
  if (kpiBaja) kpiBaja.textContent = baja;
}

function limpiarFormulario() {
  if (pickerMark) mapPicker.removeLayer(pickerMark);
  document.getElementById('dispLat').textContent = '—';
  document.getElementById('dispLng').textContent = '—';
  document.getElementById('coordStatus').textContent = 'Sin selección';
  document.getElementById('coordStatus').classList.remove('active');
  document.getElementById('incLatitud').value = '';
  document.getElementById('incLongitud').value = '';
  if (window.removeFoto) window.removeFoto();
}

function verDetalle(inc) {
  const p = inc.prioridad ? inc.prioridad.toLowerCase() : 'baja';
  const tipoLabel = TIPO_LABEL[inc.tipo_incidente] || inc.tipo_incidente || '—';
  
  const detalleTipo = document.getElementById('detalleTipo');
  const detalleDir = document.getElementById('detalleDir');
  const detalleGrid = document.getElementById('detalleGrid');
  const detalleFoto = document.getElementById('detalleFoto');
  const detalleFotoImg = document.getElementById('detalleFotoImg');
  
  if (detalleTipo) {
    detalleTipo.textContent = tipoLabel;
    detalleTipo.className = `modal-tag badge-${p}`;
  }
  if (detalleDir) detalleDir.textContent = inc.direccion || 'Ubicación no especificada';
  
  if (detalleGrid) {
    detalleGrid.innerHTML = `
      <div class="detalle-item"><label>Coordenadas</label><p>${inc.latitud}, ${inc.longitud}</p></div>
      <div class="detalle-item"><label>Prioridad</label><p><span class="badge badge-${p}">${inc.prioridad}</span></p></div>
      <div class="detalle-item"><label>Fecha</label><p>${inc.fecha_reporte}</p></div>
      <div class="detalle-item" style="grid-column:1/-1"><label>Descripción</label><p>${inc.descripcion || 'Sin descripción'}</p></div>
    `;
  }

  if (detalleFoto && detalleFotoImg) {
    if (inc.fotografia && inc.fotografia !== 'null' && inc.fotografia !== '') {
      detalleFotoImg.src = inc.fotografia;
      detalleFoto.style.display = 'block';
    } else {
      detalleFoto.style.display = 'none';
    }
  }
  
  document.getElementById('detalleOverlay')?.classList.add('open');
}

function closeDetalle() {
  document.getElementById('detalleOverlay')?.classList.remove('open');
}

function filterTable(searchText) {
  if (!incidentesData) return;
  const filtered = incidentesData.filter(inc => {
    const tipo = TIPO_LABEL[inc.tipo_incidente] || inc.tipo_incidente || '';
    return tipo.toLowerCase().includes(searchText.toLowerCase()) || 
           (inc.direccion || '').toLowerCase().includes(searchText.toLowerCase());
  });
  actualizarTabla(filtered);
}

// Ventana global
window.verDetalle = verDetalle;
window.closeDetalle = closeDetalle;
window.filterTable = filterTable;