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

// LOGIN
function handleLogin() {
    const usuario = document.getElementById("loginUser").value;
    const password = document.getElementById("loginPass").value;

    fetch("login.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `usuario=${usuario}&password=${password}`
    })
    .then(res => res.text())
    .then(data => {
        if(data === "ok"){
            window.location.href = "dashboard.php";
        } else {
            alert("Credenciales incorrectas");
        }
    });
}

// REGISTRO
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
        if(data === "ok"){
            alert("Usuario registrado correctamente");
        } else {
            alert("Error al registrar");
        }
    });
}