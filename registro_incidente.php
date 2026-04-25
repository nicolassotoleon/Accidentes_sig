<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIG Cali — Registrar Incidente</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="styles.css"> <style>
        /* Ajustes específicos para que se vea igual al index */
        :root {
            --accent-red: #e8401c;
            --dark-blue: #12263a;
        }

        body { background: var(--bg); display: flex; height: 100vh; overflow: hidden; }
        
        .main-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            width: 100%;
            height: 100%;
        }

        /* Reutilizamos el estilo del sidebar del index */
        .sidebar-decor {
            background: var(--sidebar-bg);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .content-area {
            overflow-y: auto;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .form-card {
            background: white;
            width: 100%;
            max-width: 700px;
            padding: 40px;
            border-radius: 4px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-top: 4px solid var(--accent-2);
        }

        .form-header { margin-bottom: 30px; }
        .form-header h2 { font-size: 1.8rem; color: var(--sidebar-bg); margin-bottom: 8px; }
        .form-header p { color: var(--text-muted); font-size: 0.9rem; }

        .grid-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .field { margin-bottom: 20px; }
        .field label { 
            display: block; 
            font-weight: 600; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            color: var(--text-muted);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 4px;
            font-family: var(--sans);
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        input:focus, select:focus { border-color: var(--accent); outline: none; background: white; }

        #map-picker {
            height: 250px;
            border-radius: 4px;
            border: 1px solid var(--border);
            margin-top: 5px;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-primary {
            background: var(--accent-2);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            flex: 2;
            transition: 0.3s;
        }

        .btn-secondary {
            background: var(--sidebar-bg);
            color: white;
            text-decoration: none;
            padding: 14px 20px;
            border-radius: 4px;
            text-align: center;
            flex: 1;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary:hover { opacity: 0.9; transform: translateY(-2px); }
        
        .user-badge {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="sidebar-decor">
        <div>
            <div class="logo-mark" style="margin-bottom: 20px;"></div>
            <h1 style="font-size: 1.5rem; font-weight: 300;">Plataforma de<br><strong>Gestión Territorial</strong></h1>
            <p style="margin-top: 20px; opacity: 0.7; font-size: 0.9rem; line-height: 1.6;">
                Bienvenido al módulo de reporte ciudadano. Aquí puedes registrar incidentes georeferenciados para la ciudad de Cali.
            </p>
        </div>

        <div class="user-badge">
            <span style="display:block; opacity: 0.6; font-size: 0.7rem; text-transform: uppercase;">Sesión Iniciada</span>
            <strong><?php echo $_SESSION['usuario']; ?></strong>
            <br><br>
            <a href="cerrar_sesion.php" style="color: var(--accent-2); text-decoration: none; font-size: 0.8rem; font-weight: 600;">Cerrar Sesión</a>
        </div>
    </div>

    <div class="content-area">
        <div class="form-card">
            <div class="form-header">
                <h2>Registrar Incidente</h2>
                <p>Completa la información detallada para el reporte.</p>
            </div>

            <form id="formRegistro">
                <div class="grid-fields">
                    <div class="field">
                        <label>Tipo de Incidente</label>
                        <select name="tipo_incidente" required>
                            <option value="inundacion">🌊 Inundación</option>
                            <option value="alumbrado_publico">💡 Alumbrado Público</option>
                            <option value="huecos">🕳️ Huecos / Vías</option>
                            <option value="transito">🚦 Tránsito</option>
                            <option value="otro">📌 Otro</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Prioridad</label>
                        <select name="prioridad" required>
                            <option value="BAJA">Baja</option>
                            <option value="MEDIA">Media</option>
                            <option value="ALTA">Alta</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label>Ubicación Geográfica</label>
                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-bottom: 5px;">Ubica el puntero en el lugar exacto del incidente:</p>
                    <div id="map-picker"></div>
                    <input type="hidden" name="latitud" id="lat">
                    <input type="hidden" name="longitud" id="lng">
                </div>

                <div class="field">
                    <label>Dirección y Descripción</label>
                    <input type="text" name="direccion" placeholder="Dirección aproximada (Ej: Cl. 5 # 10-02)" style="margin-bottom: 10px;">
                    <textarea name="descripcion" rows="2" placeholder="Detalles adicionales del problema..."></textarea>
                </div>

                <div class="field">
                    <label>Evidencia Fotográfica</label>
                    <input type="file" name="fotografia" accept="image/*" style="padding: 8px;">
                </div>

                <div class="actions">
                    <a href="visualizacion.php" class="btn-secondary">← Ver Lista</a>
                    <button type="submit" class="btn-primary">Enviar Reporte Ciudadano</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Configuración del Mapa
    const map = L.map('map-picker', { zoomControl: false }).setView([3.4516, -76.5320], 13);
    L.control.zoom({ position: 'bottomright' }).addTo(map);
    
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '©OpenStreetMap'
    }).addTo(map);

    let marker;
    map.on('click', function(e) {
        const { lat, lng } = e.latlng;
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng]).addTo(map);
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
    });

    // Envío del formulario (Misma lógica funcional que ya tienes)
    document.getElementById('formRegistro').onsubmit = async (e) => {
        e.preventDefault();
        if (!document.getElementById('lat').value) {
            alert("Por favor selecciona un punto en el mapa.");
            return;
        }

        const formData = new FormData(e.target);
        try {
            const response = await fetch('guardar_incidente.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if(result.status === 'success') {
                alert('✅ Registro completado exitosamente.');
                e.target.reset();
                if(marker) map.removeLayer(marker);
            } else {
                alert('❌ Error: ' + result.message);
            }
        } catch (error) {
            alert("Error de conexión.");
        }
    };
</script>

</body>
</html>