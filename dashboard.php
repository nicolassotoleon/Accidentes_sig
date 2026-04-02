<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.html");
    exit();
}

// Datos del usuario en sesión
$usuario_sesion = $_SESSION['usuario'];
$nombre_sesion  = isset($_SESSION['nombre'])   ? $_SESSION['nombre']   : $usuario_sesion;
$rol_sesion     = isset($_SESSION['rol'])      ? $_SESSION['rol']      : 'ciudadano';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SIG Cali — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <link rel="stylesheet" href="styles.css"/>
  <link rel="stylesheet" href="dashboard.css"/>
</head>
<body class="dash-body">

<!-- ═══════════════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════════════ -->
<header class="navbar">

  <div class="navbar-brand">
    <div class="logo-mark small"></div>
    <div class="navbar-brand-text">
      <strong>SIG Cali</strong>
      <span class="nav-sub">Sistema de Gestión de Incidentes</span>
    </div>
  </div>

  <div class="navbar-center">
    <span class="nav-breadcrumb">
      <span class="nav-bc-item">Inicio</span>
      <span class="nav-bc-sep">/</span>
      <span class="nav-bc-item active">Dashboard</span>
    </span>
  </div>

  <div class="navbar-right">
    <div class="nav-user-block">
      <div class="nav-avatar"><?= strtoupper(substr($nombre_sesion, 0, 1)) ?></div>
      <div class="nav-user-info">
        <span class="nav-user-name"><?= htmlspecialchars($nombre_sesion) ?></span>
        <span class="nav-badge"><?= htmlspecialchars($rol_sesion) ?></span>
      </div>
    </div>
    <div class="nav-divider"></div>
    <a href="cerrar_sesion.php" class="btn-logout">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
      </svg>
      Cerrar sesión
    </a>
  </div>

</header>

<!-- ═══════════════════════════════════════════════════════
     LAYOUT PRINCIPAL (sidebar + main)
════════════════════════════════════════════════════════ -->
<div class="dash-layout">

  <!-- ─────────────────────────────────────────────────────
       SIDEBAR — Formulario de reporte
  ───────────────────────────────────────────────────── -->
  <aside class="dash-sidebar">

    <div class="panel-header">
      <div class="panel-tag">Nuevo reporte</div>
      <h2 class="panel-title">Registrar incidente</h2>
      <p class="panel-sub">Completa el formulario y haz clic en el mapa para capturar la ubicación.</p>
    </div>

    <form id="formIncidente" enctype="multipart/form-data" onsubmit="handleSubmitIncidente(event)">

      <!-- Tipo de incidente -->
      <div class="field">
        <label>Tipo de incidente</label>
        <div class="select-wrap">
          <select id="incTipo" name="tipo" required>
            <option value="">— Selecciona —</option>
            <option value="inundacion">🌊  Inundación</option>
            <option value="alumbrado_publico">💡  Alumbrado público</option>
            <option value="huecos">🕳️  Huecos / Vías</option>
            <option value="transito">🚦  Tránsito</option>
            <option value="derrumbe">⛰️  Derrumbe</option>
            <option value="otro">📌  Otro</option>
          </select>
          <span class="select-caret">
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path d="M6 9l6 6 6-6"/>
            </svg>
          </span>
        </div>
        <p class="err-msg" id="errTipo">Selecciona el tipo de incidente.</p>
      </div>

      <!-- Prioridad -->
      <div class="field">
        <label>Prioridad</label>
        <div class="priority-row">
          <label class="priority-opt" id="priAlt" data-val="alta">
            <input type="radio" name="prioridad" value="alta" onchange="selectPriority('priAlt')"/>
            <span class="pri-dot"></span>
            <span class="pri-label">Alta</span>
          </label>
          <label class="priority-opt" id="priMed" data-val="media">
            <input type="radio" name="prioridad" value="media" onchange="selectPriority('priMed')"/>
            <span class="pri-dot"></span>
            <span class="pri-label">Media</span>
          </label>
          <label class="priority-opt" id="priBaj" data-val="baja">
            <input type="radio" name="prioridad" value="baja" onchange="selectPriority('priBaj')"/>
            <span class="pri-dot"></span>
            <span class="pri-label">Baja</span>
          </label>
        </div>
        <p class="err-msg" id="errPrioridad">Selecciona la prioridad.</p>
      </div>

      <!-- Descripción -->
      <div class="field">
        <label>Descripción</label>
        <textarea id="incDescripcion" name="descripcion" rows="3"
                  placeholder="Describe brevemente el incidente…"></textarea>
      </div>

      <!-- Separador ubicación -->
      <div class="form-section-sep">
        <span class="form-section-label">Ubicación geográfica</span>
      </div>

      <!-- Mapa selector de coordenada -->
      <div class="field">
        <label>
          Coordenada
          <span class="label-hint">— haz clic en el mapa</span>
        </label>
        <div id="mapPicker" class="map-picker"></div>
        <div class="coord-bar">
          <span class="coord-pill">
            <span class="coord-key">LAT</span>
            <span class="coord-val" id="dispLat">—</span>
          </span>
          <span class="coord-pill">
            <span class="coord-key">LNG</span>
            <span class="coord-val" id="dispLng">—</span>
          </span>
          <span class="coord-status" id="coordStatus">Sin selección</span>
        </div>
        <input type="hidden" id="incLatitud"  name="latitud"/>
        <input type="hidden" id="incLongitud" name="longitud"/>
        <p class="err-msg" id="errCoordenada">Haz clic en el mapa para capturar la ubicación.</p>
      </div>

      <!-- Comuna + Barrio -->
      <div class="row-2">
        <div class="field">
          <label>Comuna</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
              </svg>
            </span>
            <input type="text" id="incComuna" name="comuna" placeholder="Ej: Comuna 2"/>
          </div>
        </div>
        <div class="field">
          <label>Barrio</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="10" r="3"/>
                <path d="M12 2a8 8 0 010 16 8 8 0 010-16z"/>
              </svg>
            </span>
            <input type="text" id="incBarrio" name="barrio" placeholder="Ej: San Nicolás"/>
          </div>
        </div>
      </div>

      <!-- Dirección -->
      <div class="field">
        <label>Dirección</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
            </svg>
          </span>
          <input type="text" id="incDireccion" name="direccion" placeholder="Ej: Cra 5 # 12-34"/>
        </div>
      </div>

      <!-- Separador fotografía -->
      <div class="form-section-sep">
        <span class="form-section-label">Evidencia fotográfica</span>
      </div>

      <!-- Fotografía -->
      <div class="field">
        <label>
          Fotografía
          <span class="label-hint">— JPG / PNG · máx. 10 MB</span>
        </label>
        <div class="file-drop" id="fileDrop"
             onclick="document.getElementById('incFoto').click()"
             ondragover="event.preventDefault(); this.classList.add('drag-over')"
             ondragleave="this.classList.remove('drag-over')"
             ondrop="handleDrop(event)">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <path d="M21 15l-5-5L5 21"/>
          </svg>
          <span id="fileLabel">Arrastra la imagen o haz clic aquí</span>
        </div>
        <input type="file" id="incFoto" name="fotografia" accept="image/*"
               style="display:none" onchange="previewFoto(this)"/>
        <div id="fotoPreview" class="foto-preview" style="display:none">
          <img id="fotoImg" src="" alt="Vista previa"/>
          <button type="button" class="foto-remove" onclick="removeFoto()" title="Eliminar foto">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Botón enviar -->
      <button type="submit" class="btn-submit" id="btnSubmit">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z"/>
        </svg>
        Registrar incidente
      </button>

    </form>
  </aside>

  <!-- ─────────────────────────────────────────────────────
       MAIN — Estadísticas, mapa y tabla
  ───────────────────────────────────────────────────── -->
  <main class="dash-main">

    <!-- KPIs -->
    <div class="kpi-strip">

      <div class="kpi-card kpi-total">
        <div class="kpi-icon">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
        </div>
        <div class="kpi-body">
          <div class="kpi-value" id="kpiTotal">—</div>
          <div class="kpi-label">Total registrados</div>
        </div>
      </div>

      <div class="kpi-card kpi-alta">
        <div class="kpi-icon">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
        </div>
        <div class="kpi-body">
          <div class="kpi-value" id="kpiAlta">—</div>
          <div class="kpi-label">Alta prioridad</div>
        </div>
      </div>

      <div class="kpi-card kpi-media">
        <div class="kpi-icon">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 8v4M12 16h.01"/>
          </svg>
        </div>
        <div class="kpi-body">
          <div class="kpi-value" id="kpiMedia">—</div>
          <div class="kpi-label">Media prioridad</div>
        </div>
      </div>

      <div class="kpi-card kpi-baja">
        <div class="kpi-icon">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path d="M22 11.08V12a10 10 0 11-5.93-9.14M22 4L12 14.01l-3-3"/>
          </svg>
        </div>
        <div class="kpi-body">
          <div class="kpi-value" id="kpiBaja">—</div>
          <div class="kpi-label">Baja prioridad</div>
        </div>
      </div>

    </div><!-- /kpi-strip -->

    <!-- Mapa principal -->
    <div class="map-card">
      <div class="map-card-header">
        <div class="map-card-title">
          <span class="panel-tag">Cartografía</span>
          <span class="map-card-subtitle">Incidentes georeferenciados — Cali, Valle del Cauca</span>
        </div>
        <div class="map-legend">
          <span class="leg-item"><span class="leg-dot alta"></span>Alta</span>
          <span class="leg-item"><span class="leg-dot media"></span>Media</span>
          <span class="leg-item"><span class="leg-dot baja"></span>Baja</span>
        </div>
      </div>
      <div id="mapViewer" class="map-view"></div>
      <div class="map-footer">
        <span class="map-footer-item" id="mapIncCount">0 incidentes visibles</span>
        <span class="map-footer-sep">·</span>
        <span class="map-footer-item">OpenStreetMap · Leaflet 1.9</span>
      </div>
    </div>

    <!-- Tabla de incidentes -->
    <div class="table-card">

      <div class="table-card-header">
        <div class="table-card-title">
          <span class="panel-tag">Registros</span>
          <span class="table-count" id="tableCount">—</span>
        </div>
        <div class="table-controls">
          <div class="search-wrap">
            <svg class="search-icon" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" class="table-search" id="tableSearch"
                   placeholder="Filtrar registros…" oninput="filterTable(this.value)"/>
          </div>
        </div>
      </div>

      <div class="table-wrap">
        <table id="tablaIncidentes">
          <thead>
            <tr>
              <th>#</th>
              <th>Tipo</th>
              <th>Prioridad</th>
              <th>Comuna</th>
              <th>Dirección</th>
              <th>Estado</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody id="tbodyIncidentes">
            <tr>
              <td colspan="7" class="table-empty">
                <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="opacity:.3;margin-bottom:8px">
                  <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <br/>Cargando registros…
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div><!-- /table-card -->

  </main><!-- /dash-main -->

</div><!-- /dash-layout -->


<!-- ═══════════════════════════════════════════════════════
     MODAL — Detalle de incidente
════════════════════════════════════════════════════════ -->
<div class="overlay" id="detalleOverlay">
  <div class="modal modal-detalle">
    <button class="modal-close" onclick="closeDetalle()" title="Cerrar">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
        <path d="M18 6L6 18M6 6l12 12"/>
      </svg>
    </button>
    <div class="modal-tag" id="detalleTipo">—</div>
    <h3 id="detalleDir">—</h3>
    <div class="detalle-grid" id="detalleGrid"></div>
    <div id="detalleFoto" class="detalle-foto" style="display:none">
      <img id="detalleFotoImg" src="" alt="Fotografía del incidente"/>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ─────────────────────────────────────────────────────────
   Variables de sesión disponibles en JS
───────────────────────────────────────────────────────── */
const SESSION_USER = <?= json_encode($usuario_sesion) ?>;
const SESSION_ROL  = <?= json_encode($rol_sesion) ?>;
</script>
<script src="dashboard.js"></script>

</body>
</html>
