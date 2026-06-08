<?php
// --- ARCHIVO: scripts_bd/08_crear_tabla_favorito.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
error_reporting(E_ALL);
ini_set("display_errors", 1);
require dirname(__FILE__) . '/conexion/conexion.php';

$sql = "CREATE TABLE IF NOT EXISTS favorito (
    id_favorito INT AUTO_INCREMENT PRIMARY KEY,
    id_articulo INT,
    DNI VARCHAR(9)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($_conexion->query($sql)) {
    echo "Favorites table initialized successfully.";
} else {
    echo "Error: " . $_conexion->error;
}
?>