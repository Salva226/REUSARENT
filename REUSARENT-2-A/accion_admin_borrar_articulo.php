<?php
// --- ARCHIVO: accion_admin_borrar_articulo.php ---
// Script "invisible" exclusivo para el administrador. Permite borrar artículos de la plataforma (por ejemplo, si incumplen las normas).

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD DE ADMINISTRADOR
// Si no estás logueado, O tu nombre de usuario no es "adminReusa", O no vienes por POST, te echo fuera.
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"] !== "adminReusa" || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_articulo'])) {
    header("Location: index.php");
    exit();
}

$id_articulo = $_conexion->real_escape_string($_POST['id_articulo']);
$id_reporte = isset($_POST['id_reporte']) ? $_conexion->real_escape_string($_POST['id_reporte']) : null;

// 2. BORRADO EXTREMO EN CASCADA (Transacción)
// Como admin, borro sin miramientos. Si hay alquileres, los borro. Si hay chats, los borro.
$_conexion->begin_transaction();

try {
    // 2.1. Borrar de favoritos 
    // Uso un bloque try/catch vacío por si acaso la tabla no existiera temporalmente o diera error leve, que no aborte todo.
    try { $_conexion->query("DELETE FROM favorito WHERE id_articulo = '$id_articulo'"); } catch (Exception $e) {}
    
    // 2.2. Borrar conversaciones y todos los mensajes de esas conversaciones
    try {
        $resConv = $_conexion->query("SELECT id_conversacion FROM conversacion WHERE id_articulo = '$id_articulo'");
        if ($resConv) {
            while ($row = $resConv->fetch_assoc()) {
                $idConv = $row['id_conversacion'];
                try { $_conexion->query("DELETE FROM mensaje WHERE id_conversacion = '$idConv'"); } catch (Exception $e) {}
            }
        }
        $_conexion->query("DELETE FROM conversacion WHERE id_articulo = '$id_articulo'");
    } catch (Exception $e) {}
    
    // 2.3. Borrar fotos extra de la galería
    try { $_conexion->query("DELETE FROM fotos_articulo WHERE id_articulo = '$id_articulo'"); } catch (Exception $e) {}
    
    // 2.4. Borrar alquileres vinculados (Al ser admin, cancela alquileres activos a la fuerza)
    try { $_conexion->query("DELETE FROM alquiler WHERE id_articulo = '$id_articulo'"); } catch (Exception $e) {}
    
    // 2.5 Borrar reportes asociados a este artículo (si alguien lo reportó, al borrar el artículo el reporte ya no tiene sentido)
    try { $_conexion->query("DELETE FROM reportes WHERE id_articulo = '$id_articulo'"); } catch (Exception $e) {}
    
    // 2.6. Borrar el artículo principal (Si esto falla, SÍ que salta al CATCH principal y aborta todo)
    $_conexion->query("DELETE FROM articulo WHERE id_articulo = '$id_articulo'");

    // Si todo ha ido bien, cerramos la transacción.
    $_conexion->commit();
    header("Location: admin.php?exito_borrar=1");
    exit();

} catch (Exception $e) {
    // Si falla el borrado del artículo, deshacemos todo para evitar bases de datos rotas.
    $_conexion->rollback();
    die("Error fatal al borrar: " . $e->getMessage());
}
?>
