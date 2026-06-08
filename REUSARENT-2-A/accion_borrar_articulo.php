<?php
// --- ARCHIVO: accion_borrar_articulo.php ---
// Este script "invisible" se encarga de eliminar un artículo de la base de datos de forma segura.

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD: Comprobar sesión, que vengamos por POST y que exista el ID del artículo.
if (!isset($_SESSION["usuario"]) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_articulo'])) {
    header("Location: index.php");
    exit();
}

$id_articulo = $_conexion->real_escape_string($_POST['id_articulo']);
$usuario = $_SESSION['usuario'];

// 2. OBTENER MI DNI
$resMio = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$usuario'");
if (!$resMio || $resMio->num_rows === 0) {
    header("Location: index.php");
    exit();
}
$miDNI = $resMio->fetch_assoc()['DNI'];

// 3. VERIFICAR PROPIEDAD DEL ARTÍCULO (Evitar hackeos)
// Cruzo tablas para ver de quién es realmente este artículo.
$consultaDueño = "SELECT u.DNI 
                  FROM articulo art
                  INNER JOIN arrendador a ON art.id_arrendador = a.id_arrendador
                  INNER JOIN usuario u ON a.DNI = u.DNI
                  WHERE art.id_articulo = '$id_articulo'";
$resDueño = $_conexion->query($consultaDueño);

if (!$resDueño || $resDueño->num_rows === 0) {
    header("Location: index.php");
    exit();
}

// Si el DNI del dueño no coincide con el mío, le echo fuera.
$dniVendedor = $resDueño->fetch_assoc()['DNI'];
if ($miDNI !== $dniVendedor) {
    // Intento de hackeo, no es el dueño
    header("Location: index.php");
    exit();
}

// 4. REGLA DE NEGOCIO: No borrar si hay alquileres en curso
// Si tienes el artículo alquilado a alguien o hay una petición pendiente, NO TE DEJO BORRARLO.
// Primero tienes que rechazarla o esperar a que termine el alquiler.
$consultaAlquileres = "SELECT id_alquiler FROM alquiler WHERE id_articulo = '$id_articulo' AND estado IN ('Pendiente', 'Aceptado')";
$resAlquileres = $_conexion->query($consultaAlquileres);

if ($resAlquileres && $resAlquileres->num_rows > 0) {
    // Tiene alquileres en curso, bloqueamos el borrado y le mandamos un mensaje de error a la vista.
    $nombreArticuloParaUrl = urlencode($_conexion->query("SELECT nombre FROM articulo WHERE id_articulo='$id_articulo'")->fetch_assoc()['nombre']);
    header("Location: info_articulos.php?articulo=$nombreArticuloParaUrl&error_borrado=1");
    exit();
}

// 5. BORRADO EN CASCADA MANUAL CON TRANSACCIONES
// Una transacción hace que si algo falla a medias, se deshaga todo (como si no hubiera pasado nada).
// Esto evita que se borre un artículo pero se queden sus fotos "fantasma" en la BD.
$_conexion->begin_transaction();

try {
    // 5.1. Borrar de la lista de favoritos de la gente
    $_conexion->query("DELETE FROM favorito WHERE id_articulo = '$id_articulo'");
    
    // 5.2. Borrar las fotos secundarias de la galería
    $_conexion->query("DELETE FROM fotos_articulo WHERE id_articulo = '$id_articulo'");
    
    // 5.3. Borrar todos los mensajes de todas las conversaciones relacionadas con este artículo
    $resConv = $_conexion->query("SELECT id_conversacion FROM conversacion WHERE id_articulo = '$id_articulo'");
    if ($resConv) {
        while ($conv = $resConv->fetch_assoc()) {
            $idC = $conv['id_conversacion'];
            $_conexion->query("DELETE FROM mensaje WHERE id_conversacion = '$idC'");
        }
    }
    
    // 5.4. Borrar las conversaciones vacías
    $_conexion->query("DELETE FROM conversacion WHERE id_articulo = '$id_articulo'");
    
    // 5.5. Borrar el historial de alquileres (Solo los rechazados o viejos, porque los pendientes los bloqueamos antes)
    $_conexion->query("DELETE FROM alquiler WHERE id_articulo = '$id_articulo'");
    
    // 5.6. Finalmente, borrar el artículo principal
    $_conexion->query("DELETE FROM articulo WHERE id_articulo = '$id_articulo'");
    
    // Si llegamos hasta aquí sin errores, confirmamos (commit) todos los DELETE juntos.
    $_conexion->commit();
    header("Location: dashboard.php?msg=articulo_borrado");
    exit();

} catch (Exception $e) {
    // Si ha saltado cualquier error SQL, deshacemos todos los DELETE (rollback).
    $_conexion->rollback();
    header("Location: dashboard.php?error=error_borrando_articulo");
    exit();
}
?>
