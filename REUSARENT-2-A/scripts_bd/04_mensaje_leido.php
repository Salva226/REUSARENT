<?php
// --- ARCHIVO: scripts_bd/04_mensaje_leido.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
error_reporting(E_ALL);
ini_set("display_errors", 1);
require 'conexion/conexion.php';

// Añadir columna de estado de lectura a la tabla mensaje
$sql = "ALTER TABLE mensaje ADD COLUMN leido TINYINT(1) NOT NULL DEFAULT 0";

if ($_conexion->query($sql)) {
    echo "Columna leido añadida con éxito a la tabla mensaje.";
} else {
    echo "Error o ya existía la columna: " . $_conexion->error;
}
?>
