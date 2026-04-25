<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: index.html"); exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SIG Cali — Visualización de Datos</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="styles.css">
    <style>
        body { display: block; overflow-y: auto; background: var(--bg); padding-bottom: 50px; }
        .header-vis { background: var(--sidebar-bg); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        
        /* Estadísticas */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 4px; border-left: 5px solid var(--accent); box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .stat-card h3 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 10px; }
        .stat-card .value { font-size: 2.2rem; font-weight: 600; color: var(--sidebar-bg); }

        /* Mapa */
        #map-view { height: 500px; background: #e5e7eb; border-radius: 4px; margin-bottom: 30px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }

        /* Tabla */
        .table-card { background: white; border-radius: 4px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; }
        .table-header { padding: 20px; border-bottom: 1px solid var(--border); background: #fafafa; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid var(--border); }
        td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 0.9rem; color: var(--text); }
        tr:hover { background: var(--input-bg); }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .badge-alta { background: #fee2e2; color: #b91c1c; }
        .badge-media { background: #fef3c7; color: #92400e; }
        .badge-baja { background: #dcfce7; color: #15803d; }
    </style>
</head>
<body>

<header class="header-vis">
    <div style="display:flex; align-items:center; gap:15px;">
        <div class="logo-mark small" style="background-color:var(--accent-2); width:30px; height:30px;"></div>
        <h1 style="font-size:1.4rem; font-weight:400;">Panel de <strong>Resultados</strong></h1>
    </div>
    <div style="display:flex; gap:20px; align-items:center;">
        <a href="registro_incidente.php" style="color:white; text-decoration:none; font-size:0.9rem;">+ Registrar Nuevo</a>
        <a href="cerrar_sesion.php" style="background:var(--accent-2); color:white; padding:8px 15px; border-radius:4px; text-decoration:none; font-size:0.8rem; font-weight:600;">Cerrar Sesión</a>
    </div>
</header>

<div class="container">
    <div class="stats-row">
        <div class="stat-card">
            <h3>Total Reportes</h3>
            <div class="value" id="stat-total">0</div>
        </div>
        <div class="stat-card" style="border-left-color: var(--accent-2);">
            <h3>Inundaciones</h3>
            <div class="value" id="stat-inundacion">0</div>
        </div>
        <div class="stat-card" style="border-left-color: #f59e0b;">
            <h3>Huecos/Vías</h3>
            <div class="value" id="stat-huecos">0</div>
        </div>
        <div class="stat-card" style="border-left-color: #6366f1;">
            <h3>Otros</h3>
            <div class="value" id="stat-otros">0</div>
        </div>
    </div>

    <div id="map-view"></div>

    <div class="table-card">
        <div class="table-header">
            <h2 style="font-size:1.1rem; color:var(--sidebar-bg);">Detalle de Incidentes Registrados</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Incidente</th>
                    <th>Prioridad</th>
                    <th>Ubicación</th>
                    <th>Fecha de Registro</th>
                    <th>Evidencia</th>
                </tr>
            </thead>
            <tbody id="tabla-body">
                </tbody>
        </table>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('map-view', { zoomControl: false }).setView([3.4516, -76.5320], 13);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);

    async function cargarDashboard() {
        try {
            const response = await fetch('consultar_incidentes.php');
            const data = await response.json();
            
            const tbody = document.getElementById('tabla-body');
            let stats = { total: 0, inundacion: 0, huecos: 0, otros: 0 };

            data.forEach(item => {
                stats.total++;
                
                // Contar para estadísticas
                if(item.tipo_incidente === 'inundacion') stats.inundacion++;
                else if(item.tipo_incidente === 'huecos' || item.tipo_incidente === 'vias') stats.huecos++;
                else stats.otros++;

                // Marcador en Mapa
                const icon = item.prioridad === 'ALTA' ? '🔴' : (item.prioridad === 'MEDIA' ? '🟠' : '🟢');
                const marker = L.marker([item.latitud, item.longitud]).addTo(map);
                marker.bindPopup(`
                    <strong style="color:var(--accent-2)">${item.tipo_incidente.toUpperCase()}</strong><br>
                    ${item.direccion || 'Sin dirección'}<br>
                    <small>${item.descripcion}</small>
                `);

                // Fila en Tabla
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${item.tipo_incidente.replace('_', ' ')}</strong></td>
                    <td><span class="badge badge-${item.prioridad.toLowerCase()}">${item.prioridad}</span></td>
                    <td>${item.direccion || 'Ver en mapa'}</td>
                    <td>${new Date(item.fecha_registro).toLocaleDateString()}</td>
                    <td>${item.fotografia ? `<a href="${item.fotografia}" target="_blank" style="color:var(--accent); font-weight:600;">Ver Foto</a>` : '<span style="color:#ccc">N/A</span>'}</td>
                `;
                tbody.appendChild(tr);
            });

            // Actualizar Cards
            document.getElementById('stat-total').innerText = stats.total;
            document.getElementById('stat-inundacion').innerText = stats.inundacion;
            document.getElementById('stat-huecos').innerText = stats.huecos;
            document.getElementById('stat-otros').innerText = stats.otros;

        } catch (error) {
            console.error("Error cargando dashboard:", error);
        }
    }

    cargarDashboard();
</script>

</body>
</html>