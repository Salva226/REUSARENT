<?php
// --- ARCHIVO: accion_reportar.php ---
// Este script "invisible" recibe los datos cuando un usuario le da a "Reportar" en un artículo porque le parece falso, ilegal, etc.

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD BÁSICA
if (!isset($_SESSION["usuario"]) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_articulo']) || !isset($_POST['motivo'])) {
    header("Location: index.php");
    exit();
}

// 2. RECOGER DATOS
$id_articulo = $_conexion->real_escape_string($_POST['id_articulo']);
$motivo = $_conexion->real_escape_string($_POST['motivo']);
$descripcion = $_conexion->real_escape_string($_POST['descripcion'] ?? '');
$usuario = $_SESSION['usuario'];

// 3. OBTENER DNI DEL DENUNCIANTE
// Necesitamos saber quién es el chivato para guardarlo en la base de datos (y evitar abusos).
$resDNI = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$usuario'");
if (!$resDNI || $resDNI->num_rows === 0) {
    header("Location: index.php");
    exit();
}
$dniDenunciante = $resDNI->fetch_assoc()['DNI'];

// 4. OBTENER NOMBRE DEL ARTÍCULO (Para la redirección)
// Necesito saber cómo se llamaba el artículo para devolver al usuario a su página URL original (ej: ?articulo=Bici).
$resArt = $_conexion->query("SELECT nombre FROM articulo WHERE id_articulo = '$id_articulo'");
if (!$resArt || $resArt->num_rows === 0) {
    header("Location: index.php");
    exit();
}
$nombreArticulo = urlencode($resArt->fetch_assoc()['nombre']);

// 5. GUARDAR REPORTE EN LA BD
// Se inserta por defecto en estado 'Pendiente' para que el administrador lo vea luego.
$insertReporte = "INSERT INTO reportes (id_articulo, DNI_denunciante, motivo, descripcion, estado) 
                  VALUES ('$id_articulo', '$dniDenunciante', '$motivo', '$descripcion', 'Pendiente')";

if ($_conexion->query($insertReporte)) {
    // Si funciona, vuelvo al artículo avisando de que todo fue bien.
    header("Location: info_articulos.php?articulo=$nombreArticulo&exito_reporte=1");
} else {
    // Si falla, vuelvo al artículo sin más.
    header("Location: info_articulos.php?articulo=$nombreArticulo");
}
exit();
?>
