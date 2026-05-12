<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración SIG Cali</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        body { background-color: var(--bg); font-family: 'IBM Plex Sans', sans-serif; margin: 0; padding-bottom: 50px; }

        /* ── Header ─────────────────────────────────────────── */
        .header-vis {
            background: var(--sidebar-bg);
            padding: 1.2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* ── Contenedor principal ───────────────────────────── */
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        /* ── Tabla ──────────────────────────────────────────── */
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid var(--divider);
        }
        .table-header { padding: 24px; border-bottom: 1px solid var(--divider); }
        .styled-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .styled-table th,
        .styled-table td { padding: 16px 24px; border-bottom: 1px solid #f0f0f0; text-align: left; }
        .styled-table tr:last-child td { border-bottom: none; }
        .styled-table tr:hover td { background: #fafbfc; }

        /* ── Badges de prioridad ────────────────────────────── */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-alta  { background: #fee2e2; color: #b91c1c; }
        .badge-media { background: #fef3c7; color: #92400e; }
        .badge-baja  { background: #dcfce7; color: #15803d; }

        /* ── Botones de acción ──────────────────────────────── */
        .action-btns { display: flex; gap: 8px; justify-content: flex-end; }

        .btn-edit-action {
            border: 1px solid var(--accent);
            color: var(--accent);
            background: #fff;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.82rem;
            transition: all 0.2s;
        }
        .btn-edit-action:hover { background: var(--accent); color: #fff; }

        .btn-map-action {
            border: 1px solid #6366f1;
            color: #6366f1;
            background: #fff;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.82rem;
            transition: all 0.2s;
        }
        .btn-map-action:hover { background: #6366f1; color: #fff; }

        .btn-delete-action {
            border: 1px solid var(--accent-2);
            color: var(--accent-2);
            background: #fff;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.82rem;
            transition: all 0.2s;
        }
        .btn-delete-action:hover { background: var(--accent-2); color: #fff; }

        /* ── Base modal ─────────────────────────────────────── */
        .modal-admin {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(18, 38, 58, 0.7);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .modal-content-admin {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .modal-header { background: var(--sidebar-bg); color: white; padding: 25px; text-align: center; }
        .modal-header.danger { background: var(--accent-2); }
        .modal-header.map-header { background: #3730a3; }
        .modal-body { padding: 30px; }

        /* ── Formulario edit ────────────────────────────────── */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #eef1f5;
            border-radius: 10px;
            background: #f8fafc;
            font-family: inherit;
            font-size: 0.95rem;
        }

        /* ── Footer botones ─────────────────────────────────── */
        .modal-footer-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }

        .btn-save-modern {
            background: var(--sidebar-bg);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-save-modern:hover:not(:disabled) { background: var(--accent); transform: translateY(-2px); }
        .btn-save-modern:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-save-modern.danger { background: var(--accent-2); }
        .btn-save-modern.danger:hover:not(:disabled) { background: #c0351a; }
        .btn-save-modern.map-save { background: #4f46e5; }
        .btn-save-modern.map-save:hover:not(:disabled) { background: #3730a3; }

        .btn-cancel-modern {
            background: #f1f5f9;
            color: #64748b;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-cancel-modern:hover { background: #e2e8f0; }

        /* ── Modal confirmación eliminar ────────────────────── */
        .delete-info {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #7f1d1d;
        }
        .delete-info strong { display: block; margin-bottom: 4px; font-size: 1rem; }

        /* ── Modal mapa ─────────────────────────────────────── */
        .modal-content-map {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        #map-edit {
            height: 320px;
            width: 100%;
        }
        .map-hint {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #f0f4ff;
            border-radius: 8px;
            border-left: 3px solid #6366f1;
        }
        .coords-display {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 12px;
        }
        .coord-box {
            background: #f8fafc;
            border: 1px solid #eef1f5;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.82rem;
        }
        .coord-box span { display: block; color: var(--text-muted); font-size: 0.72rem; text-transform: uppercase; margin-bottom: 3px; }
        .coord-box strong { font-family: monospace; color: var(--sidebar-bg); }
    </style>
</head>
<body>

<!-- Header -->
<div class="header-vis">
    <div class="brand"><h2 style="margin:0;">SIG <span style="font-weight:300">Cali</span></h2></div>
    <a href="visualizacion.php" style="color:white; text-decoration:none; font-size:0.9rem; border: 1px solid rgba(255,255,255,0.3); padding: 8px 16px; border-radius: 8px;">Volver al Dashboard</a>
</div>

<!-- Tabla principal -->
<div class="admin-container">
    <div class="table-card">
        <div class="table-header">
            <h3 style="margin:0; color:var(--sidebar-bg);">Panel de Control de Registros</h3>
        </div>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Prioridad</th>
                    <th>Dirección</th>
                    <th>Coordenadas</th>
                    <th style="text-align:right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-admin"></tbody>
        </table>
    </div>
</div>


<!-- ══════════════════════════════════════════
     MODAL 1 — Editar tipo y prioridad
     ══════════════════════════════════════════ -->
<div id="modalEdit" class="modal-admin">
    <div class="modal-content-admin">
        <div class="modal-header">
            <h3 style="margin:0;">Editar Registro #<span id="display-id"></span></h3>
        </div>
        <div class="modal-body">
            <form id="formUpdate">
                <input type="hidden" id="edit-id" name="id">

                <div class="form-group">
                    <label>Tipo de Incidente</label>
                    <select name="tipo_incidente" id="edit-tipo">
                        <option value="inundacion">Inundación</option>
                        <option value="alumbrado_publico">Alumbrado Público</option>
                        <option value="huecos">Huecos / Vías</option>
                        <option value="transito">Tránsito</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Prioridad</label>
                    <select name="prioridad" id="edit-prioridad">
                        <option value="ALTA">Alta</option>
                        <option value="MEDIA">Media</option>
                        <option value="BAJA">Baja</option>
                    </select>
                </div>

                <div class="modal-footer-btns">
                    <button type="button" class="btn-cancel-modern" onclick="closeModal('modalEdit')">Cancelar</button>
                    <button type="submit" class="btn-save-modern" id="btnSubmit">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════
     MODAL 2 — Confirmar eliminación
     ══════════════════════════════════════════ -->
<div id="modalDelete" class="modal-admin">
    <div class="modal-content-admin">
        <div class="modal-header danger">
            <h3 style="margin:0;">⚠️ Eliminar Registro</h3>
        </div>
        <div class="modal-body">
            <div class="delete-info">
                <strong id="delete-label">Cargando...</strong>
                Esta acción es <strong>permanente</strong> y no se puede deshacer.
            </div>
            <div class="modal-footer-btns">
                <button type="button" class="btn-cancel-modern" onclick="closeModal('modalDelete')">Cancelar</button>
                <button type="button" class="btn-save-modern danger" id="btnConfirmDelete">Sí, Eliminar</button>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════
     MODAL 3 — Editar ubicación en mapa
     ══════════════════════════════════════════ -->
<div id="modalMap" class="modal-admin">
    <div class="modal-content-map" style="background:white; border-radius:16px; width:100%; max-width:600px; box-shadow:0 25px 50px rgba(0,0,0,0.3); overflow:hidden;">
        <div class="modal-header map-header">
            <h3 style="margin:0;">📍 Editar Ubicación — Registro #<span id="map-display-id"></span></h3>
        </div>
        <div class="modal-body">
            <p class="map-hint">Haz clic en el mapa para mover el marcador a la nueva ubicación del incidente.</p>
            <div id="map-edit"></div>
            <div class="coords-display">
                <div class="coord-box">
                    <span>Latitud</span>
                    <strong id="display-lat">—</strong>
                </div>
                <div class="coord-box">
                    <span>Longitud</span>
                    <strong id="display-lng">—</strong>
                </div>
            </div>
            <div class="modal-footer-btns" style="margin-top:20px;">
                <button type="button" class="btn-cancel-modern" onclick="closeModal('modalMap')">Cancelar</button>
                <button type="button" class="btn-save-modern map-save" id="btnSaveLocation">Guardar Ubicación</button>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════
     Scripts
     ══════════════════════════════════════════ -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>

/* ─── Estado compartido ─────────────────────────────── */
let currentItem  = null;   // registro activo
let mapEdit      = null;   // instancia Leaflet del modal mapa
let editMarker   = null;   // marcador arrastrable
let newLat       = null;   // coordenadas seleccionadas
let newLng       = null;

/* ─── Cargar tabla ──────────────────────────────────── */
async function cargarTabla() {
    try {
        const res  = await fetch('consultar_incidentes.php');
        const data = await res.json();
        const tbody = document.getElementById('tabla-admin');
        tbody.innerHTML = '';

        data.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>#${item.id}</strong></td>
                <td style="text-transform:capitalize; font-weight:600;">
                    ${item.tipo_incidente.replace('_', ' ')}
                </td>
                <td>
                    <span class="badge badge-${item.prioridad.toLowerCase()}">${item.prioridad}</span>
                </td>
                <td style="color:var(--text-muted); font-size:0.85rem;">
                    ${item.direccion || 'Cali, Valle'}
                </td>
                <td style="font-family:monospace; font-size:0.78rem; color:var(--text-muted);">
                    ${parseFloat(item.latitud).toFixed(5)}, ${parseFloat(item.longitud).toFixed(5)}
                </td>
                <td>
                    <div class="action-btns">
                        <button class="btn-edit-action"   onclick='openModalEdit(${JSON.stringify(item)})'>Editar</button>
                        <button class="btn-map-action"    onclick='openModalMap(${JSON.stringify(item)})'>Ubicación</button>
                        <button class="btn-delete-action" onclick='openModalDelete(${JSON.stringify(item)})'>Eliminar</button>
                    </div>
                </td>`;
            tbody.appendChild(tr);
        });
    } catch (e) {
        console.error("Error al cargar tabla:", e);
    }
}

/* ─── Cerrar cualquier modal ────────────────────────── */
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

/* Cerrar al click fuera del modal */
document.querySelectorAll('.modal-admin').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.style.display = 'none'; });
});


/* ══════════════════════════════════════════
   MODAL 1 — Editar tipo y prioridad
   ══════════════════════════════════════════ */
function openModalEdit(item) {
    currentItem = item;
    document.getElementById('edit-id').value        = item.id;
    document.getElementById('display-id').innerText = item.id;
    document.getElementById('edit-tipo').value      = item.tipo_incidente;
    document.getElementById('edit-prioridad').value = item.prioridad;
    document.getElementById('modalEdit').style.display = 'flex';
}

document.getElementById('formUpdate').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    btn.innerText = "Guardando...";
    btn.disabled  = true;

    try {
        const response = await fetch('actualizar_incidente.php', {
            method: 'POST',
            body: new FormData(this)
        });
        const result = await response.json();

        if (result.status === 'success') {
            showToast('✅ Registro actualizado correctamente', 'success');
            closeModal('modalEdit');
            cargarTabla();
        } else {
            showToast('❌ ' + (result.message || 'No se pudo actualizar'), 'error');
        }
    } catch {
        showToast('❌ Error de conexión con el servidor', 'error');
    } finally {
        btn.innerText = "Guardar Cambios";
        btn.disabled  = false;
    }
});


/* ══════════════════════════════════════════
   MODAL 2 — Confirmar eliminación
   ══════════════════════════════════════════ */
function openModalDelete(item) {
    currentItem = item;
    document.getElementById('delete-label').innerText =
        `Registro #${item.id} — ${item.tipo_incidente.replace('_',' ')} (${item.prioridad})`;
    document.getElementById('modalDelete').style.display = 'flex';
}

document.getElementById('btnConfirmDelete').addEventListener('click', async function() {
    if (!currentItem) return;
    this.innerText = "Eliminando...";
    this.disabled  = true;

    try {
        const fd = new FormData();
        fd.append('id', currentItem.id);

        const response = await fetch('eliminar_incidente.php', { method: 'POST', body: fd });
        const result   = await response.json();

        if (result.status === 'success') {
            showToast('🗑️ Registro eliminado correctamente', 'success');
            closeModal('modalDelete');
            cargarTabla();
        } else {
            showToast('❌ ' + (result.message || 'No se pudo eliminar'), 'error');
        }
    } catch {
        showToast('❌ Error de conexión con el servidor', 'error');
    } finally {
        this.innerText = "Sí, Eliminar";
        this.disabled  = false;
    }
});


/* ══════════════════════════════════════════
   MODAL 3 — Editar ubicación con mapa
   ══════════════════════════════════════════ */
function openModalMap(item) {
    currentItem = item;
    newLat = parseFloat(item.latitud);
    newLng = parseFloat(item.longitud);

    document.getElementById('map-display-id').innerText = item.id;
    document.getElementById('display-lat').innerText    = newLat.toFixed(6);
    document.getElementById('display-lng').innerText    = newLng.toFixed(6);
    document.getElementById('modalMap').style.display   = 'flex';

    // Inicializar o reusar el mapa
    setTimeout(() => {
        if (!mapEdit) {
            mapEdit = L.map('map-edit', { zoomControl: false }).setView([newLat, newLng], 15);
            L.control.zoom({ position: 'bottomright' }).addTo(mapEdit);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '©OpenStreetMap'
            }).addTo(mapEdit);

            mapEdit.on('click', function(e) {
                const { lat, lng } = e.latlng;
                newLat = lat;
                newLng = lng;
                document.getElementById('display-lat').innerText = lat.toFixed(6);
                document.getElementById('display-lng').innerText = lng.toFixed(6);
                if (editMarker) mapEdit.removeLayer(editMarker);
                editMarker = L.marker([lat, lng]).addTo(mapEdit);
            });
        } else {
            mapEdit.setView([newLat, newLng], 15);
            if (editMarker) mapEdit.removeLayer(editMarker);
        }

        editMarker = L.marker([newLat, newLng]).addTo(mapEdit);
        mapEdit.invalidateSize(); // forzar render correcto dentro del modal
    }, 150);
}

document.getElementById('btnSaveLocation').addEventListener('click', async function() {
    if (!currentItem) return;

    this.innerText = "Guardando...";
    this.disabled  = true;

    try {
        const fd = new FormData();
        fd.append('id',             currentItem.id);
        fd.append('tipo_incidente', currentItem.tipo_incidente);
        fd.append('prioridad',      currentItem.prioridad);
        fd.append('latitud',        newLat);
        fd.append('longitud',       newLng);

        const response = await fetch('actualizar_incidente.php', { method: 'POST', body: fd });
        const result   = await response.json();

        if (result.status === 'success') {
            showToast('📍 Ubicación actualizada correctamente', 'success');
            closeModal('modalMap');
            cargarTabla();
        } else {
            showToast('❌ ' + (result.message || 'No se pudo actualizar la ubicación'), 'error');
        }
    } catch {
        showToast('❌ Error de conexión con el servidor', 'error');
    } finally {
        this.innerText = "Guardar Ubicación";
        this.disabled  = false;
    }
});


/* ─── Toast de notificación ─────────────────────────── */
function showToast(msg, type = 'success') {
    let toast = document.getElementById('toast-global');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-global';
        toast.style.cssText = `
            position:fixed; bottom:28px; right:28px; z-index:9999;
            background:var(--sidebar-bg); color:white;
            padding:14px 22px; border-radius:10px;
            font-size:0.88rem; font-weight:500;
            box-shadow:0 8px 24px rgba(0,0,0,0.18);
            border-left:4px solid var(--accent);
            opacity:0; transform:translateY(10px);
            transition:opacity .3s, transform .3s;
            max-width:320px;
        `;
        document.body.appendChild(toast);
    }
    toast.style.borderLeftColor = type === 'success' ? '#1a7a4a' : '#e8401c';
    toast.innerText = msg;
    toast.style.opacity   = '1';
    toast.style.transform = 'translateY(0)';
    setTimeout(() => {
        toast.style.opacity   = '0';
        toast.style.transform = 'translateY(10px)';
    }, 3200);
}


/* ─── Iniciar ───────────────────────────────────────── */
cargarTabla();

</script>

</body>
</html>
