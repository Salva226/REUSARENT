<?php
// --- ARCHIVO: accion_admin_crear_categoria.php ---
// Script "invisible" exclusivo para el administrador. Sirve para añadir nuevas categorías al menú (ej: "Mascotas").

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD DE ADMINISTRADOR
// Mismo filtro: Solo el admin puede ejecutar esto.
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"] !== "adminReusa" || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// 2. RECOGER DATOS DEL FORMULARIO DE ADMIN
// Limpio los datos para que no metan comillas raras o código SQL malicioso.
$nombre = $_conexion->real_escape_string($_POST['nombre_categoria']);
$descripcion = $_conexion->real_escape_string($_POST['descripcion_categoria'] ?? '');

// 3. INSERTAR EN LA BASE DE DATOS
$insert = "INSERT INTO categoria (nombre, descripcion) VALUES ('$nombre', '$descripcion')";

if ($_conexion->query($insert)) {
    // Si funciona, vuelvo al panel de admin con un mensaje de éxito.
    header("Location: admin.php?exito_categoria=1");
} else {
    // Si falla (por ejemplo, porque el nombre ya existe), vuelvo con mensaje de error.
    header("Location: admin.php?error=categoria");
}
exit();
?>
