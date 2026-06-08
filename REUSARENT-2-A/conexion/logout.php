<?php 
// --- ARCHIVO: logout.php ---
// Este script se ejecuta cuando el usuario le da a "Cerrar sesión".

session_start(); // Arranco la sesión para poder acceder a ella.

// Vacío todo el array de $_SESSION para borrar mis variables (usuario, rol, etc.)
$_SESSION = []; 

// Destruyo la sesión por completo a nivel de servidor.
session_destroy();

// Lo mando de vuelta a la pantalla de login.
header("Location: login.php");
exit();
?>