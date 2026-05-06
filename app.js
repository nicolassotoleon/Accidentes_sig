// CAMBIO DE TABS
function switchTab(tab) {
    // Panels
    document.getElementById("loginPanel").classList.remove("active");
    document.getElementById("registerPanel").classList.remove("active");

    // Tabs (botones)
    const tabs = document.querySelectorAll(".tab");
    tabs.forEach(t => t.classList.remove("active"));

    if (tab === "login") {
        document.getElementById("loginPanel").classList.add("active");
        tabs[0].classList.add("active");
    } else {
        document.getElementById("registerPanel").classList.add("active");
        tabs[1].classList.add("active");
    }
}

// LOGIN CORREGIDO
function handleLogin() {
    // 1. Capturamos los valores reales de los campos
    const usuario = document.getElementById("loginUser").value;
    const pass = document.getElementById("loginPass").value;

    if (!usuario || !pass) {
        alert("Por favor, completa todos los campos.");
        return;
    }

    // 2. Definimos los parámetros (esto reemplaza al formData que causaba error)
    const params = `usuario=${encodeURIComponent(usuario)}&password=${encodeURIComponent(pass)}`;

    // 3. Petición al servidor
    fetch('login.php', {
        method: 'POST',
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: params // <--- Aquí usamos 'params', NO 'formData'
    })
    .then(response => response.text())
    .then(data => {
        const rol = data.trim();

        // 4. Redirección por roles
        if (rol === "operador") {
            window.location.href = "admin_incidentes.php";
        } else if (rol === "ciudadano") {
            window.location.href = "registro_incidente.php";
        } else {
            alert("Usuario o contraseña incorrectos.");
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// REGISTRO (Sin cambios, ya está capturando bien los datos)
function handleRegister() {
    const nombres = document.getElementById("regFirst").value;
    const apellidos = document.getElementById("regLast").value;
    const correo = document.getElementById("regEmail").value;
    const password = document.getElementById("regPass").value;
    const rol = document.querySelector('input[name="role"]:checked').value;

    fetch("registro.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `nombres=${nombres}&apellidos=${apellidos}&correo=${correo}&password=${password}&rol=${rol}`
    })
    .then(res => res.text())
    .then(data => {
        if(data.trim() === "ok"){
            alert("Usuario registrado correctamente");
            switchTab('login'); // Sugerencia: llevar al usuario al login tras registrarse
        } else {
            alert("Error al registrar");
        }
    });
}

// FUNCIÓN DE SELECCIÓN DE ROL
function selectRole(selectedId, otherId) {
    const selected = document.getElementById(selectedId);
    const other = document.getElementById(otherId);

    if (!selected || !other) return;

    selected.classList.add('selected');
    other.classList.remove('selected');
}