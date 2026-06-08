<?php
// --- ARCHIVO: accion_admin_resolver_reporte.php ---
// Script "invisible" exclusivo para el administrador. 
// Sirve para cambiar el estado de un reporte de "Pendiente" a "Revisado" cuando el admin decide que ya lo ha mirado (y no hace falta borrar el artículo).

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD DE ADMINISTRADOR
// Si no estás logueado, O tu nombre de usuario no es "adminReusa", O no vienes por POST, te echo fuera.
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"] !== "adminReusa" || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_reporte'])) {
    header("Location: index.php");
    exit();
}

// 2. RECOGER DATOS Y ACTUALIZAR
$id_reporte = $_conexion->real_escape_string($_POST['id_reporte']);

// Actualizamos el estado del reporte a 'Revisado' para que desaparezca de la tabla principal de reportes pendientes del admin.
$update = "UPDATE reportes SET estado = 'Revisado' WHERE id_reporte = '$id_reporte'";
$_conexion->query($update);

// Volvemos al panel de admin con un mensajito de éxito.
header("Location: admin.php?exito_revisado=1");
exit();
?>
