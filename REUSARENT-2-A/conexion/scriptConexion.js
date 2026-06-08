// --- ARCHIVO: scriptConexion.js ---
// Este archivo lo usaba en versiones anteriores del registro para limpiar campos.
// window.onload asegura que este código se ejecuta justo después de que la página haya cargado por completo.
window.onload = function(){

    // 1. Apuntar a los elementos del DOM (la web) usando sus IDs
    const usuario = document.getElementById("usuario");
    const email = document.getElementById("email");
    const passw = document.getElementById("contaseña"); // Nota mental: cuidado con usar 'ñ' en IDs
    const terminos = document.getElementById("terminos"); // Checkbox: devuelve true o false
    const mostrarContraseña = document.getElementById("mostrarContraseña"); // Checkbox del ojito

    // 2. Al cargar la página de nuevo, obligo a que se limpien todas las casillas.
    // Esto viene bien por si el navegador intenta autocompletar basura de sesiones anteriores.
    if(usuario) usuario.value = "";
    if(email) email.value = "";
    if(passw) passw.value = "";
    if(terminos) terminos.checked = false;
    if(mostrarContraseña) mostrarContraseña.checked = false;

    // 3. Lógica de la casilla para mostrar u ocultar la contraseña
    if(mostrarContraseña) {
        mostrarContraseña.addEventListener("click", function(){
            // Comparo si la casilla está marcada o desmarcada
            if(!mostrarContraseña.checked){
                // Si no está marcada, pongo el input en type="password" (salen bolitas)
                passw.type = "password";
            } else {
                // Si está marcada, pongo el input en type="text" (se ve lo que escribes)
                passw.type = "text";
            }
        });
    }

}