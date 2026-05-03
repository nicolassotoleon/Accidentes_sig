<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración SIG Cali</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        body { background-color: var(--bg); font-family: 'IBM Plex Sans', sans-serif; margin: 0; padding-bottom: 50px; }
        .header-vis { background: var(--sidebar-bg); padding: 1.2rem 3rem; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .table-card { background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid var(--divider); }
        .table-header { padding: 24px; border-bottom: 1px solid var(--divider); }
        .styled-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .styled-table th, .styled-table td { padding: 16px 24px; border-bottom: 1px solid #f0f0f0; text-align: left; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-alta { background: var(--alta-light); color: var(--alta); }
        .badge-media { background: var(--media-light); color: var(--media); }
        .badge-baja { background: var(--baja-light); color: var(--baja); }
        
        .modal-admin { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(18, 38, 58, 0.7); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal-content-admin { background: white; border-radius: 16px; width: 100%; max-width: 400px; box-shadow: 0 25px 50px rgba(0,0,0,0.3); overflow: hidden; }
        .modal-header { background: var(--sidebar-bg); color: white; padding: 25px; text-align: center; }
        .modal-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; }
        .form-group select { width: 100%; padding: 12px; border: 2px solid #eef1f5; border-radius: 10px; background: #f8fafc; font-family: inherit; font-size: 0.95rem; }
        .modal-footer-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }
        .btn-save-modern { background: var(--sidebar-bg); color: white; border: none; padding: 14px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-save-modern:hover:not(:disabled) { background: var(--accent); transform: translateY(-2px); }
        .btn-save-modern:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-cancel-modern { background: #f1f5f9; color: #64748b; border: none; padding: 14px; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .btn-edit-action { border: 1px solid var(--accent); color: var(--accent); background: #fff; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-weight: 500; }
        .btn-edit-action:hover { background: var(--accent); color: #fff; }
    </style>
</head>
<body>

<div class="header-vis">
    <div class="brand"><h2 style="margin:0;">SIG <span style="font-weight:300">Cali</span></h2></div>
    <a href="visualizacion.php" style="color:white; text-decoration:none; font-size:0.9rem; border: 1px solid rgba(255,255,255,0.3); padding: 8px 16px; border-radius: 8px;">Volver al Dashboard</a>
</div>

<div class="admin-container">
    <div class="table-card">
        <div class="table-header"><h3>Panel de Control de Registros</h3></div>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Prioridad</th>
                    <th>Dirección</th>
                    <th style="text-align:right">Acción</th>
                </tr>
            </thead>
            <tbody id="tabla-admin"></tbody>
        </table>
    </div>
</div>

<div id="modalEdit" class="modal-admin">
    <div class="modal-content-admin">
        <div class="modal-header"><h3>Editar Registro #<span id="display-id"></span></h3></div>
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
                    <button type="button" class="btn-cancel-modern" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-save-modern" id="btnSubmit">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    async function cargarTabla() {
        try {
            const res = await fetch('consultar_incidentes.php');
            const data = await res.json();
            const tbody = document.getElementById('tabla-admin');
            tbody.innerHTML = '';

            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${item.id}</td>
                    <td style="text-transform:capitalize; font-weight:600;">${item.tipo_incidente.replace('_', ' ')}</td>
                    <td><span class="badge badge-${item.prioridad.toLowerCase()}">${item.prioridad}</span></td>
                    <td style="color:var(--text-muted)">${item.direccion || 'Cali, Valle'}</td>
                    <td style="text-align:right">
                        <button class="btn-edit-action" onclick='openModal(${JSON.stringify(item)})'>Editar</button>
                    </td>`;
                tbody.appendChild(tr);
            });
        } catch (e) { console.error("Error al cargar tabla:", e); }
    }

    function openModal(item) {
        document.getElementById('edit-id').value = item.id;
        document.getElementById('display-id').innerText = item.id;
        document.getElementById('edit-tipo').value = item.tipo_incidente;
        document.getElementById('edit-prioridad').value = item.prioridad;
        document.getElementById('modalEdit').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('modalEdit').style.display = 'none';
    }

    document.getElementById('formUpdate').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        const originalText = btn.innerText;

        btn.innerText = "Guardando...";
        btn.disabled = true;

        const formData = new FormData(this);

        try {
            const response = await fetch('actualizar_incidente.php', {
                method: 'POST',
                body: formData
            });

            const text = await response.text();

            let result;
            try {
                result = JSON.parse(text);
            } catch {
                alert("❌ Error de formato en la respuesta del servidor");
                return;
            }

            if(result.status === 'success') {
                alert("✅ Registro actualizado correctamente");
                closeModal();
                cargarTabla();
            } else {
                alert("❌ Error: " + (result.message || "No se pudo actualizar"));
            }

        } catch (error) {
            alert("❌ Error de conexión con el servidor");
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    });

    cargarTabla();
</script>

</body>
</html>