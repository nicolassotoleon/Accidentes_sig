<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: index.html"); exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIG Cali — Visualización de Datos</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="styles.css">

    <style>
        :root {
            --bg:         #f0f2f5;
            --panel-bg:   #ffffff;
            --sidebar-bg: #12263a;
            --accent:     #1c5f8a;
            --accent-2:   #e8401c;
            --text:       #1a2535;
            --text-muted: #6b7b8d;
            --border:     #dce1e8;
            --input-bg:   #f7f9fb;
            --sans:       'IBM Plex Sans', sans-serif;
            --mono:       'IBM Plex Mono', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--sans);
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ── HEADER ── */
        .header-vis {
            background: var(--sidebar-bg);
            color: white;
            padding: 0 40px;
            height: 64px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.25);
        }

        .header-left { display: flex; align-items: center; gap: 16px; }

        .logo-mark {
            width: 32px; height: 32px;
            border: 2px solid rgba(255,255,255,0.25);
            position: relative;
            display: flex; align-items: center; justify-content: center;
        }
        .logo-mark::before { content:''; width:10px; height:10px; background: var(--accent-2); position:absolute; }
        .logo-mark::after  { content:''; width:22px; height:22px; border:1.5px solid rgba(255,255,255,0.2); position:absolute; }

        .header-title { font-size: 1rem; font-weight: 300; }
        .header-title strong { font-weight: 600; }

        .header-right { display: flex; gap: 20px; align-items: center; }

        .header-right a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        .header-right a:hover { color: white; }

        .btn-logout {
            background: var(--accent-2);
            color: white !important;
            padding: 7px 16px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ── STATS ROW ── */
        .container { max-width: 1400px; margin: 0 auto; padding: 28px 24px 60px; }

        .section-label {
            font-family: var(--mono);
            font-size: 0.65rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--accent-2);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-label::before { content:''; width:18px; height:1px; background: var(--accent-2); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: white;
            padding: 22px 20px;
            border-radius: 4px;
            border-left: 4px solid var(--accent);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card:nth-child(2) { border-left-color: var(--accent-2); }
        .stat-card:nth-child(3) { border-left-color: #f59e0b; }
        .stat-card:nth-child(4) { border-left-color: #6366f1; }
        .stat-card:nth-child(5) { border-left-color: #10b981; }
        .stat-card:nth-child(6) { border-left-color: #ec4899; }

        .stat-card h3 {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-family: var(--mono);
            font-size: 2rem;
            font-weight: 500;
            color: var(--sidebar-bg);
            line-height: 1;
        }

        /* ── MAP TABS ── */
        .maps-section { margin-bottom: 36px; }

        /* Barra de pestañas superior */
        .map-tab-bar {
            display: flex;
            gap: 6px;
            margin-bottom: 0;
            overflow: hidden;
            flex-wrap: wrap;
            padding: 16px 20px 0;
            background: var(--sidebar-bg);
            border-radius: 6px 6px 0 0;
        }

        .map-tab {
            padding: 10px 20px 12px;
            font-size: 0.76rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: rgba(255,255,255,0.45);
            border: none;
            background: rgba(255,255,255,0.07);
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            transition: all 0.22s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 7px;
            position: relative;
            bottom: -1px;
        }
        .map-tab:hover:not(.active) {
            color: rgba(255,255,255,0.8);
            background: rgba(255,255,255,0.12);
        }
        .map-tab.active {
            color: var(--sidebar-bg);
            background: var(--bg);
            font-weight: 600;
        }
        .map-tab .tab-icon { font-size: 1rem; }

        /* Panel wrapper con borde decorativo lateral */
        .map-panel { display: none; }
        .map-panel.active { display: block; }

        /* Controles separados visualmente del mapa */
        .map-controls {
            background: var(--bg);
            border: 1px solid var(--border);
            border-bottom: none;
            padding: 16px 24px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .map-controls-label {
            font-family: var(--mono);
            font-size: 0.62rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-right: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .map-controls-label::after {
            content: '';
            width: 1px;
            height: 18px;
            background: var(--border);
            display: inline-block;
        }

        /* Botones de control rediseñados */
        .filter-btn {
            padding: 7px 16px;
            border-radius: 4px;
            border: 1.5px solid var(--border);
            background: white;
            font-family: var(--sans);
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.18s;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .filter-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(28,95,138,0.04);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(28,95,138,0.12);
        }
        .filter-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
            box-shadow: 0 3px 10px rgba(28,95,138,0.3);
            transform: translateY(-1px);
        }

        /* Variantes de color por tipo */
        .filter-btn.inundacion.active  { background: #2563eb; border-color: #2563eb; box-shadow: 0 3px 10px rgba(37,99,235,0.35); }
        .filter-btn.alumbrado.active   { background: #d97706; border-color: #d97706; box-shadow: 0 3px 10px rgba(217,119,6,0.35); }
        .filter-btn.huecos.active      { background: #7c3aed; border-color: #7c3aed; box-shadow: 0 3px 10px rgba(124,58,237,0.35); }
        .filter-btn.transito.active    { background: #059669; border-color: #059669; box-shadow: 0 3px 10px rgba(5,150,105,0.35); }
        .filter-btn.otro.active        { background: #4b5563; border-color: #4b5563; box-shadow: 0 3px 10px rgba(75,85,99,0.35); }

        /* Botones de zoom del mapa de calor con preview de color */
        .heat-btn {
            padding: 7px 16px 7px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .heat-btn .heat-preview {
            width: 28px;
            height: 10px;
            border-radius: 3px;
            flex-shrink: 0;
        }
        .heat-btn-close  .heat-preview { background: linear-gradient(to right, #0000ff 0%, #00ffff 40%, #00ff00 65%, #ffff00 85%, #ff0000 100%); opacity: 0.55; }
        .heat-btn-medium .heat-preview { background: linear-gradient(to right, #0000ff 0%, #00ffff 40%, #00ff00 65%, #ffff00 85%, #ff0000 100%); opacity: 0.75; }
        .heat-btn-global .heat-preview { background: linear-gradient(to right, #0000ff 0%, #00ffff 40%, #00ff00 65%, #ffff00 85%, #ff0000 100%); opacity: 1; }
        .heat-btn-close.active  .heat-preview { opacity: 1; width: 20px; }
        .heat-btn-medium.active .heat-preview { opacity: 1; width: 24px; }
        .heat-btn-global.active .heat-preview { opacity: 1; width: 28px; }

        .map-container {
            height: 520px;
            border: 1px solid var(--border);
            border-top: none;
            position: relative;
            border-radius: 0 0 4px 4px;
        }

        .map-container .leaflet-container { height: 100%; border-radius: 0 0 4px 4px; }

        .map-legend {
            position: absolute;
            bottom: 28px;
            left: 12px;
            z-index: 900;
            background: rgba(255,255,255,0.95);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 12px 16px;
            font-size: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            pointer-events: none;
        }
        .map-legend h4 {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .legend-item { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
        .legend-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
        .legend-square { width: 24px; height: 10px; border-radius: 2px; flex-shrink: 0; }

        /* ── TABLE SECTION ── */
        .table-card {
            background: white;
            border-radius: 4px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .table-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            background: #fafbfc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 { font-size: 0.95rem; color: var(--sidebar-bg); }

        .table-search {
            padding: 7px 14px;
            border: 1px solid var(--border);
            border-radius: 3px;
            font-family: var(--sans);
            font-size: 0.82rem;
            width: 220px;
            background: var(--input-bg);
            transition: border-color 0.2s;
        }
        .table-search:focus { outline: none; border-color: var(--accent); background: white; }

        table { width: 100%; border-collapse: collapse; }
        th {
            background: #f4f6f8;
            padding: 12px 16px;
            text-align: left;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }
        td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
            color: var(--text);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--input-bg); }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-alta   { background: #fee2e2; color: #b91c1c; }
        .badge-media  { background: #fef3c7; color: #92400e; }
        .badge-baja   { background: #dcfce7; color: #15803d; }

        .tipo-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 500;
            background: var(--input-bg);
            color: var(--text);
        }

        /* ── LOADING ── */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(18, 38, 58, 0.85);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
            gap: 16px;
        }
        .spinner {
            width: 40px; height: 40px;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: var(--accent-2);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .header-vis { padding: 0 16px; }
            .container { padding: 16px 12px 40px; }
            .map-container { height: 380px; }
        }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
    <span style="font-family: var(--mono); font-size:0.8rem; letter-spacing:0.1em; opacity:0.7;">CARGANDO DATOS...</span>
</div>

<!-- HEADER -->
<header class="header-vis">
    <div class="header-left">
        <div class="logo-mark"></div>
        <span class="header-title">Plataforma de <strong>Gestión Territorial</strong></span>
    </div>
    <div class="header-right">
        <a href="registro_incidente.php">+ Registrar Nuevo</a>
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'operador'): ?>
        <a href="admin_incidentes.php">Panel Admin</a>
        <?php endif; ?>
        <a href="cerrar_sesion.php" class="btn-logout">Cerrar Sesión</a>
    </div>
</header>

<div class="container">

    <!-- ESTADÍSTICAS -->
    <div class="section-label">Resumen General</div>
    <div class="stats-grid">
        <div class="stat-card"><h3>Total Reportes</h3><div class="value" id="stat-total">—</div></div>
        <div class="stat-card"><h3>Inundaciones</h3><div class="value" id="stat-inundacion">—</div></div>
        <div class="stat-card"><h3>Alumbrado</h3><div class="value" id="stat-alumbrado">—</div></div>
        <div class="stat-card"><h3>Huecos / Vías</h3><div class="value" id="stat-huecos">—</div></div>
        <div class="stat-card"><h3>Tránsito</h3><div class="value" id="stat-transito">—</div></div>
        <div class="stat-card"><h3>Otros</h3><div class="value" id="stat-otros">—</div></div>
    </div>

    <!-- MAPAS -->
    <div class="maps-section">
        <div class="section-label">Análisis Espacial</div>

        <div class="map-tab-bar">
            <button class="map-tab active" onclick="switchMap('tematico', this)">
                <span class="tab-icon">🗺️</span>Mapa Temático
            </button>
            <button class="map-tab" onclick="switchMap('calor-total', this)">
                <span class="tab-icon">🔥</span>Mapa de Calor — Total
            </button>
            <button class="map-tab" onclick="switchMap('calor-tipo', this)">
                <span class="tab-icon">🌡️</span>Mapa de Calor — Por Tipo
            </button>
            <button class="map-tab" onclick="switchMap('cluster', this)">
                <span class="tab-icon">📍</span>Clúster de Puntos
            </button>
        </div>

        <!-- MAPA 1: TEMÁTICO (puntos por prioridad) -->
        <div class="map-panel active" id="panel-tematico">
            <div class="map-controls">
                <span class="map-controls-label">Filtrar por prioridad</span>
                <button class="filter-btn active" onclick="filterTematico('todas', this)">Todas</button>
                <button class="filter-btn" onclick="filterTematico('ALTA', this)">🔴 Alta</button>
                <button class="filter-btn" onclick="filterTematico('MEDIA', this)">🟠 Media</button>
                <button class="filter-btn" onclick="filterTematico('BAJA', this)">🟢 Baja</button>
            </div>
            <div class="map-container" id="map-tematico">
                <div class="map-legend" id="legend-tematico">
                    <h4>Prioridad</h4>
                    <div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div> Alta</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#f97316"></div> Media</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#22c55e"></div> Baja</div>
                </div>
            </div>
        </div>

        <!-- MAPA 2: CALOR TOTAL -->
        <div class="map-panel" id="panel-calor-total">
            <div class="map-controls">
                <span class="map-controls-label">Radio de influencia</span>
                <button class="filter-btn heat-btn heat-btn-close active" onclick="setHeatRadius('close', this)">
                    <div class="heat-preview"></div>Zoom cercano
                </button>
                <button class="filter-btn heat-btn heat-btn-medium" onclick="setHeatRadius('medium', this)">
                    <div class="heat-preview"></div>Zoom medio
                </button>
                <button class="filter-btn heat-btn heat-btn-global" onclick="setHeatRadius('global', this)">
                    <div class="heat-preview"></div>Vista global
                </button>
            </div>
            <div class="map-container" id="map-calor-total">
                <div class="map-legend">
                    <h4>Densidad</h4>
                    <div class="legend-item"><div class="legend-square" style="background: linear-gradient(to right, #0000ff, #00ffff, #00ff00, #ffff00, #ff0000)"></div></div>
                    <div style="display:flex; justify-content:space-between; font-size:0.65rem; color: var(--text-muted);">
                        <span>Baja</span><span>Alta</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAPA 3: CALOR POR TIPO -->
        <div class="map-panel" id="panel-calor-tipo">
            <div class="map-controls">
                <span class="map-controls-label">Tipo de incidente</span>
                <button class="filter-btn inundacion active" data-tipo="inundacion" onclick="switchHeatType('inundacion', this)">🌊 Inundación</button>
                <button class="filter-btn alumbrado" data-tipo="alumbrado_publico" onclick="switchHeatType('alumbrado_publico', this)">💡 Alumbrado</button>
                <button class="filter-btn huecos" data-tipo="huecos" onclick="switchHeatType('huecos', this)">🕳️ Huecos</button>
                <button class="filter-btn transito" data-tipo="transito" onclick="switchHeatType('transito', this)">🚦 Tránsito</button>
                <button class="filter-btn otro" data-tipo="otro" onclick="switchHeatType('otro', this)">📌 Otro</button>
            </div>
            <div class="map-container" id="map-calor-tipo">
                <div class="map-legend" id="legend-calor-tipo">
                    <h4 id="legend-tipo-title">Inundación</h4>
                    <div class="legend-item"><div class="legend-square" style="background: linear-gradient(to right, rgba(59,130,246,0), rgba(59,130,246,0.5), #3b82f6)"></div></div>
                    <div style="display:flex; justify-content:space-between; font-size:0.65rem; color: var(--text-muted);">
                        <span>Baja</span><span>Alta</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAPA 4: CLUSTER -->
        <div class="map-panel" id="panel-cluster">
            <div class="map-controls">
                <span class="map-controls-label">Modo de visualización</span>
                <button class="filter-btn active" onclick="setClusterMode('cluster', this)">📍 Clúster agrupado</button>
                <button class="filter-btn" onclick="setClusterMode('individual', this)">🔵 Puntos individuales</button>
            </div>
            <div class="map-container" id="map-cluster">
                <div class="map-legend">
                    <h4>Tipo de Incidente</h4>
                    <div class="legend-item"><div class="legend-dot" style="background:#3b82f6"></div> Inundación</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#f59e0b"></div> Alumbrado</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#8b5cf6"></div> Huecos</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#10b981"></div> Tránsito</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#6b7280"></div> Otro</div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN GRÁFICOS ESTADÍSTICOS -->
    <div style="margin-top: 36px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
        <div class="section-label" style="margin-bottom: 0;">Análisis Estadístico</div>
        <button id="btn-toggle-graficos" onclick="toggleGraficos()"
            style="display:flex; align-items:center; gap:8px; padding:9px 20px;
                   background:var(--sidebar-bg); color:white; border:none; border-radius:4px;
                   font-family:var(--sans); font-size:0.8rem; font-weight:600;
                   letter-spacing:0.04em; text-transform:uppercase; cursor:pointer;
                   transition:background 0.2s;">
            <span style="font-size:1rem;">📊</span> Ver Gráficos
        </button>
    </div>

    <div id="graficos-section" style="display:none;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:36px;">

            <!-- Gráfico por Comunas -->
            <div style="background:white; border-radius:4px; border:1px solid var(--border);
                        box-shadow:0 2px 10px rgba(0,0,0,0.04); overflow:hidden;">
                <div style="padding:16px 20px; border-bottom:1px solid var(--border); background:#fafbfc;">
                    <h3 style="font-size:0.88rem; color:var(--sidebar-bg); font-weight:600;">Reportes por Comuna</h3>
                    <p style="font-size:0.72rem; color:var(--text-muted); margin-top:3px;">Solo comunas con al menos un reporte registrado</p>
                </div>
                <div style="padding:20px;">
                    <canvas id="grafico-comunas" height="260"></canvas>
                </div>
            </div>

            <!-- Gráfico por Barrios -->
            <div style="background:white; border-radius:4px; border:1px solid var(--border);
                        box-shadow:0 2px 10px rgba(0,0,0,0.04); overflow:hidden;">
                <div style="padding:16px 20px; border-bottom:1px solid var(--border); background:#fafbfc;">
                    <h3 style="font-size:0.88rem; color:var(--sidebar-bg); font-weight:600;">Reportes por Barrio</h3>
                    <p style="font-size:0.72rem; color:var(--text-muted); margin-top:3px;">Solo barrios con al menos un reporte registrado</p>
                </div>
                <div style="padding:20px;">
                    <canvas id="grafico-barrios" height="260"></canvas>
                </div>
            </div>

        </div>
    </div>

    <!-- TABLA DE INCIDENTES -->
    <div class="table-card" style="margin-top: 36px;">
        <div class="table-header">
            <h2>Detalle de Incidentes Registrados</h2>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Incidente</th>
                        <th>Prioridad</th>
                        <th>Ubicación</th>
                        <th>Comuna</th>
                        <th>Barrio</th>
                        <th>Fecha de Registro</th>
                        <th>Evidencia</th>
                    </tr>
                </thead>
                <tbody id="tabla-body"></tbody>
            </table>
        </div>
    </div>

</div><!-- /container -->

<!-- SCRIPTS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// ═══════════════════════════════════════════════
// CONFIGURACIÓN
// ═══════════════════════════════════════════════
const CALI_CENTER = [3.4516, -76.5320];
const TILE_URL    = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
const TILE_DARK   = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';

const TIPO_COLORS = {
    inundacion:       '#3b82f6',
    alumbrado_publico:'#f59e0b',
    huecos:           '#8b5cf6',
    transito:         '#10b981',
    otro:             '#6b7280'
};

const TIPO_GRADIENTS = {
    inundacion:       { 0.4: '#bfdbfe', 0.65: '#60a5fa', 1: '#1d4ed8' },
    alumbrado_publico:{ 0.4: '#fde68a', 0.65: '#fbbf24', 1: '#b45309' },
    huecos:           { 0.4: '#ddd6fe', 0.65: '#a78bfa', 1: '#5b21b6' },
    transito:         { 0.4: '#a7f3d0', 0.65: '#34d399', 1: '#065f46' },
    otro:             { 0.4: '#e5e7eb', 0.65: '#9ca3af', 1: '#374151' }
};

const TIPO_LABELS = {
    inundacion: 'Inundación', alumbrado_publico: 'Alumbrado Público',
    huecos: 'Huecos / Vías', transito: 'Tránsito', otro: 'Otro'
};

const PRIORIDAD_COLORS = { ALTA: '#ef4444', MEDIA: '#f97316', BAJA: '#22c55e' };

// ═══════════════════════════════════════════════
// MAPAS — inicialización
// ═══════════════════════════════════════════════
function makeMap(id, dark = false) {
    const m = L.map(id, { zoomControl: false }).setView(CALI_CENTER, 13);
    L.control.zoom({ position: 'bottomright' }).addTo(m);
    L.tileLayer(dark ? TILE_DARK : TILE_URL, { attribution: '©OpenStreetMap ©CARTO' }).addTo(m);
    return m;
}

const mapTematico   = makeMap('map-tematico');
const mapCalorTotal = makeMap('map-calor-total', true);
const mapCalorTipo  = makeMap('map-calor-tipo', true);
const mapCluster    = makeMap('map-cluster');

// ═══════════════════════════════════════════════
// DATOS GLOBALES
// ═══════════════════════════════════════════════
let allData   = [];
let tableRows = [];

// Referencias a capas activas
let tematicoMarkers   = {};   // clave: prioridad → LayerGroup
let heatTotal         = null;
let heatTipo          = null;
let currentHeatTipo   = 'inundacion';
let clusterGroup      = null;
let individualGroup   = null;
let clusterMode       = 'cluster';

// ═══════════════════════════════════════════════
// CARGA DE DATOS
// ═══════════════════════════════════════════════
async function cargarDatos() {
    try {
        const response = await fetch('consultar_incidentes.php');
        const raw = await response.json();

        // Verificar que sea un array válido y no un objeto de error
        if (!Array.isArray(raw)) {
            console.error('consultar_incidentes.php devolvio:', raw);
            allData = [];
        } else {
            allData = raw;
        }
    } catch (err) {
        console.error('Error cargando datos desde el servidor:', err);
        allData = [];
    }

    // Cada seccion se ejecuta de forma independiente para que un
    // fallo en los mapas no impida mostrar la tabla
    try { procesarEstadisticas(); }          catch(e) { console.error('estadisticas:', e); }
    try { construirMapaTematico(); }         catch(e) { console.error('mapa tematico:', e); }
    try { construirCalorTotal(); }           catch(e) { console.error('calor total:', e); }
    try { construirCalorTipo('inundacion'); } catch(e) { console.error('calor tipo:', e); }
    try { construirCluster(); }              catch(e) { console.error('cluster:', e); }
    try { construirTabla(); }                catch(e) { console.error('tabla:', e); }

    document.getElementById('loadingOverlay').style.display = 'none';
}

// ═══════════════════════════════════════════════
// ESTADÍSTICAS
// ═══════════════════════════════════════════════
function procesarEstadisticas() {
    const counts = { total: allData.length, inundacion:0, alumbrado_publico:0, huecos:0, transito:0, otro:0 };
    allData.forEach(d => {
        if (counts[d.tipo_incidente] !== undefined) counts[d.tipo_incidente]++;
        else counts.otro++;
    });
    document.getElementById('stat-total').textContent       = counts.total;
    document.getElementById('stat-inundacion').textContent  = counts.inundacion;
    document.getElementById('stat-alumbrado').textContent   = counts.alumbrado_publico;
    document.getElementById('stat-huecos').textContent      = counts.huecos;
    document.getElementById('stat-transito').textContent    = counts.transito;
    document.getElementById('stat-otros').textContent       = counts.otro;
}

// ═══════════════════════════════════════════════
// MAPA 1 — TEMÁTICO (por prioridad, coloreado)
// ═══════════════════════════════════════════════
function construirMapaTematico() {
    tematicoMarkers = { ALTA: L.layerGroup(), MEDIA: L.layerGroup(), BAJA: L.layerGroup() };

    allData.forEach(d => {
        if (!d.latitud || !d.longitud) return;
        const color   = PRIORIDAD_COLORS[d.prioridad] || '#6b7280';
        const tipoClr = TIPO_COLORS[d.tipo_incidente]  || '#6b7280';

        const icon = L.divIcon({
            html: `<div style="
                width:14px; height:14px; border-radius:50%;
                background:${color}; border:2px solid white;
                box-shadow:0 2px 6px rgba(0,0,0,0.35);
            "></div>`,
            className: '', iconAnchor: [7, 7]
        });

        const marker = L.marker([d.latitud, d.longitud], { icon })
            .bindPopup(`
                <div style="font-family:'IBM Plex Sans',sans-serif; min-width:180px; padding:4px">
                    <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:#6b7b8d; margin-bottom:4px">Incidente</div>
                    <strong style="color:${tipoClr}; font-size:0.95rem;">${TIPO_LABELS[d.tipo_incidente] || d.tipo_incidente}</strong>
                    <div style="margin:8px 0; font-size:0.8rem;">
                        <span style="background:${color}20; color:${color}; padding:2px 8px; border-radius:10px; font-weight:600;">
                            ${d.prioridad}
                        </span>
                    </div>
                    <div style="font-size:0.78rem; color:#6b7b8d;">${d.direccion || 'Sin dirección'}</div>
                    ${d.descripcion ? `<div style="margin-top:6px; font-size:0.78rem;">${d.descripcion}</div>` : ''}
                    <div style="margin-top:6px; font-size:0.7rem; color:#aab4be;">${new Date(d.fecha_registro).toLocaleDateString('es-CO')}</div>
                </div>
            `);

        const p = d.prioridad in tematicoMarkers ? d.prioridad : 'BAJA';
        tematicoMarkers[p].addLayer(marker);
    });

    Object.values(tematicoMarkers).forEach(g => g.addTo(mapTematico));
}

function filterTematico(prioridad, btn) {
    document.querySelectorAll('#panel-tematico .filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    Object.entries(tematicoMarkers).forEach(([key, group]) => {
        if (prioridad === 'todas' || key === prioridad) {
            mapTematico.addLayer(group);
        } else {
            mapTematico.removeLayer(group);
        }
    });
}

// ═══════════════════════════════════════════════
// MAPA 2 — CALOR TOTAL
// ═══════════════════════════════════════════════
// Configuraciones por modo: [radius, blur, minOpacity, max]
const HEAT_CONFIGS = {
    close:  { radius: 15, blur: 12,  minOpacity: 0.05, max: 0.6 },
    medium: { radius: 35, blur: 25,  minOpacity: 0.2,  max: 0.8 },
    global: { radius: 65, blur: 50,  minOpacity: 0.4,  max: 1.0 }
};
let currentHeatMode = 'close';

function construirCalorTotal() {
    const points = allData
        .filter(d => d.latitud && d.longitud)
        .map(d => [parseFloat(d.latitud), parseFloat(d.longitud), 1]);

    const cfg = HEAT_CONFIGS[currentHeatMode];
    heatTotal = L.heatLayer(points, {
        radius:     cfg.radius,
        blur:       cfg.blur,
        minOpacity: cfg.minOpacity,
        max:        cfg.max,
        maxZoom:    17,
        gradient:   { 0.2: '#0000ff', 0.4: '#00ffff', 0.6: '#00ff00', 0.8: '#ffff00', 1.0: '#ff0000' }
    }).addTo(mapCalorTotal);
}

function setHeatRadius(mode, btn) {
    document.querySelectorAll('#panel-calor-total .filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentHeatMode = mode;
    if (heatTotal) {
        mapCalorTotal.removeLayer(heatTotal);
        heatTotal = null;
    }
    construirCalorTotal();
}

// ═══════════════════════════════════════════════
// MAPA 3 — CALOR POR TIPO
// ═══════════════════════════════════════════════
function construirCalorTipo(tipo) {
    if (heatTipo) mapCalorTipo.removeLayer(heatTipo);

    const points = allData
        .filter(d => d.tipo_incidente === tipo && d.latitud && d.longitud)
        .map(d => [parseFloat(d.latitud), parseFloat(d.longitud), 1]);

    if (points.length === 0) {
        // Vacío: mostrar igual sin error
        heatTipo = L.heatLayer([], { radius: 20 }).addTo(mapCalorTipo);
        return;
    }

    heatTipo = L.heatLayer(points, {
        radius:   25,
        blur:     20,
        maxZoom:  17,
        gradient: TIPO_GRADIENTS[tipo] || TIPO_GRADIENTS.otro
    }).addTo(mapCalorTipo);
}

function switchHeatType(tipo, btn) {
    document.querySelectorAll('#panel-calor-tipo .filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.getElementById('legend-tipo-title').textContent = TIPO_LABELS[tipo] || tipo;
    const g = TIPO_GRADIENTS[tipo] || TIPO_GRADIENTS.otro;
    const colors = Object.values(g);
    const legendBar = document.querySelector('#legend-calor-tipo .legend-square');
    if (legendBar) legendBar.style.background = `linear-gradient(to right, ${colors[0]}, ${colors[1]}, ${colors[2]})`;

    currentHeatTipo = tipo;
    construirCalorTipo(tipo);
    invalidarMapa(mapCalorTipo);
}

// ═══════════════════════════════════════════════
// MAPA 4 — CLÚSTER
// ═══════════════════════════════════════════════
function construirCluster() {
    // maxClusterRadius grande + sin límite de zoom → siempre agrupa
    clusterGroup = L.markerClusterGroup({
        maxClusterRadius: 80,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        // Con pocos puntos, forzar agrupación desde zoom bajo
        disableClusteringAtZoom: 18,
        iconCreateFunction: function(cluster) {
            const count = cluster.getChildCount();
            const size  = count < 5 ? 36 : count < 20 ? 44 : 52;
            return L.divIcon({
                html: `<div style="
                    width:${size}px; height:${size}px; border-radius:50%;
                    background: var(--sidebar-bg);
                    border: 3px solid var(--accent-2);
                    display:flex; align-items:center; justify-content:center;
                    color:white; font-family:'IBM Plex Mono',monospace;
                    font-size:${count < 10 ? '0.85' : '0.75'}rem; font-weight:600;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.3);
                ">${count}</div>`,
                className: '',
                iconSize: [size, size],
                iconAnchor: [size/2, size/2]
            });
        }
    });

    individualGroup = L.layerGroup();

    allData.forEach(d => {
        if (!d.latitud || !d.longitud) return;
        const color = TIPO_COLORS[d.tipo_incidente] || '#6b7280';

        const icon = L.divIcon({
            html: `<div style="
                width:14px; height:14px; border-radius:50%;
                background:${color}; border:2.5px solid white;
                box-shadow:0 2px 8px rgba(0,0,0,0.35);
            "></div>`,
            className: '', iconAnchor: [7, 7]
        });

        const popup = `
            <div style="font-family:'IBM Plex Sans',sans-serif; padding:4px; min-width:160px">
                <strong style="color:${color}">${TIPO_LABELS[d.tipo_incidente] || d.tipo_incidente}</strong><br>
                <small style="color:#6b7b8d">${d.direccion || 'Sin dirección'}</small>
            </div>`;

        const m1 = L.marker([d.latitud, d.longitud], { icon }).bindPopup(popup);
        const m2 = L.marker([d.latitud, d.longitud], { icon }).bindPopup(popup);

        clusterGroup.addLayer(m1);
        individualGroup.addLayer(m2);
    });

    clusterGroup.addTo(mapCluster);

    // Si hay datos, ajustar vista para mostrarlos todos
    if (allData.filter(d => d.latitud && d.longitud).length > 0) {
        try {
            mapCluster.fitBounds(clusterGroup.getBounds(), { padding: [40, 40], maxZoom: 14 });
        } catch(e) { /* sin bounds válidos */ }
    }
}

function setClusterMode(mode, btn) {
    document.querySelectorAll('#panel-cluster .filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    clusterMode = mode;

    if (mode === 'cluster') {
        if (mapCluster.hasLayer(individualGroup)) mapCluster.removeLayer(individualGroup);
        if (!mapCluster.hasLayer(clusterGroup))   clusterGroup.addTo(mapCluster);
    } else {
        if (mapCluster.hasLayer(clusterGroup))    mapCluster.removeLayer(clusterGroup);
        if (!mapCluster.hasLayer(individualGroup)) individualGroup.addTo(mapCluster);
    }
}

// ═══════════════════════════════════════════════
// TABLA
// ═══════════════════════════════════════════════
function construirTabla() {
    const tbody = document.getElementById('tabla-body');
    tableRows   = [];

    allData.forEach((d, i) => {
        const color = TIPO_COLORS[d.tipo_incidente] || '#6b7280';
        const tr    = document.createElement('tr');
        const fecha = d.fecha_registro ? new Date(d.fecha_registro).toLocaleDateString('es-CO') : '—';

        // Nombre legible del tipo (reemplazar guiones bajos por espacios)
        const tipoLabel = (TIPO_LABELS[d.tipo_incidente] || d.tipo_incidente || '—')
            .replace(/_/g, ' ');

        const comuna = d.comuna || '—';
        const barrio = d.barrio || '—';

        tr.innerHTML = `
            <td>
                <span style="display:inline-flex; align-items:center; gap:6px; font-weight:600; font-size:0.88rem; color:var(--text);">
                    <span style="width:9px;height:9px;border-radius:50%;background:${color};flex-shrink:0;display:inline-block;"></span>
                    ${tipoLabel}
                </span>
            </td>
            <td><span class="badge badge-${(d.prioridad||'').toLowerCase()}">${d.prioridad || '—'}</span></td>
            <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:0.875rem;">${d.direccion || '—'}</td>
            <td style="font-size:0.875rem; color:var(--text-muted);">${comuna}</td>
            <td style="font-size:0.875rem; color:var(--text-muted);">${barrio}</td>
            <td style="font-size:0.875rem; color:var(--text-muted);">${fecha}</td>
            <td>${d.fotografia
                ? `<a href="${d.fotografia}" target="_blank" style="color:var(--accent); font-weight:600; font-size:0.85rem; text-decoration:none;">Ver Foto ↗</a>`
                : '<span style="color:#c8d0d8; font-size:0.85rem;">N/A</span>'
            }</td>
        `;

        const searchText = `${d.tipo_incidente} ${d.prioridad} ${d.direccion || ''} ${d.descripcion || ''} ${comuna} ${barrio}`.toLowerCase();
        tableRows.push({ el: tr, text: searchText });
        tbody.appendChild(tr);
    });
}

// ═══════════════════════════════════════════════
// SWITCH TABS
// ═══════════════════════════════════════════════
function switchMap(name, btn) {
    document.querySelectorAll('.map-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.map-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + name).classList.add('active');

    // Forzar re-render del mapa activo tras mostrar
    setTimeout(() => {
        if (name === 'tematico')    invalidarMapa(mapTematico);
        if (name === 'calor-total') invalidarMapa(mapCalorTotal);
        if (name === 'calor-tipo')  invalidarMapa(mapCalorTipo);
        if (name === 'cluster')     invalidarMapa(mapCluster);
    }, 100);
}

function invalidarMapa(mapa) {
    mapa.invalidateSize();
}

// ═══════════════════════════════════════════════
// GRÁFICOS ESTADÍSTICOS
// ═══════════════════════════════════════════════
let graficosCreados = false;
let chartComunas = null;
let chartBarrios = null;

function toggleGraficos() {
    const section = document.getElementById('graficos-section');
    const btn     = document.getElementById('btn-toggle-graficos');
    const visible = section.style.display !== 'none';

    if (visible) {
        section.style.display = 'none';
        btn.innerHTML = '<span style="font-size:1rem;">📊</span> Ver Gráficos';
        btn.style.background = 'var(--sidebar-bg)';
    } else {
        section.style.display = 'block';
        btn.innerHTML = '<span style="font-size:1rem;">✕</span> Ocultar Gráficos';
        btn.style.background = 'var(--accent-2)';
        if (!graficosCreados) {
            construirGraficos();
            graficosCreados = true;
        }
    }
}

function construirGraficos() {
    // ── Agrupar por comuna (ignorar nulos/vacíos) ──
    const comunaMap = {};
    const barrioMap = {};

    allData.forEach(d => {
        const c = (d.comuna || '').trim();
        const b = (d.barrio  || '').trim();
        if (c && c !== '—') comunaMap[c] = (comunaMap[c] || 0) + 1;
        if (b && b !== '—') barrioMap[b] = (barrioMap[b] || 0) + 1;
    });

    // Ordenar de mayor a menor
    const sortDesc = obj => Object.entries(obj).sort((a, b) => b[1] - a[1]);

    const comunaEntries = sortDesc(comunaMap);
    const barrioEntries = sortDesc(barrioMap);

    const PALETTE = [
        '#1c5f8a','#e8401c','#f59e0b','#10b981','#8b5cf6',
        '#3b82f6','#ec4899','#06b6d4','#84cc16','#f97316',
        '#6366f1','#14b8a6','#ef4444','#a855f7','#22c55e'
    ];

    const makeColors = n => Array.from({length: n}, (_, i) => PALETTE[i % PALETTE.length]);

    const chartOpts = (label) => ({
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.parsed.x} reporte${ctx.parsed.x !== 1 ? 's' : ''}`
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font: { family: "'IBM Plex Sans', sans-serif", size: 11 },
                    color: '#6b7b8d'
                },
                grid: { color: '#f0f2f5' }
            },
            y: {
                ticks: {
                    font: { family: "'IBM Plex Sans', sans-serif", size: 11 },
                    color: '#1a2535'
                },
                grid: { display: false }
            }
        }
    });

    // ── Gráfico Comunas ──
    const ctxC = document.getElementById('grafico-comunas');
    ctxC.height = Math.max(180, comunaEntries.length * 36);
    chartComunas = new Chart(ctxC, {
        type: 'bar',
        data: {
            labels: comunaEntries.map(e => e[0]),
            datasets: [{
                label: 'Reportes',
                data: comunaEntries.map(e => e[1]),
                backgroundColor: makeColors(comunaEntries.length),
                borderRadius: 4,
                borderSkipped: false,
                barThickness: 18
            }]
        },
        options: chartOpts('Reportes por Comuna')
    });

    // ── Gráfico Barrios ──
    const ctxB = document.getElementById('grafico-barrios');
    ctxB.height = Math.max(180, barrioEntries.length * 36);
    chartBarrios = new Chart(ctxB, {
        type: 'bar',
        data: {
            labels: barrioEntries.map(e => e[0]),
            datasets: [{
                label: 'Reportes',
                data: barrioEntries.map(e => e[1]),
                backgroundColor: makeColors(barrioEntries.length),
                borderRadius: 4,
                borderSkipped: false,
                barThickness: 18
            }]
        },
        options: chartOpts('Reportes por Barrio')
    });
}

// ═══════════════════════════════════════════════
// INICIO
// ═══════════════════════════════════════════════
cargarDatos();
</script>
</body>
</html>
