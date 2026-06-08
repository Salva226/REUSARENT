<?php
// --- ARCHIVO: scripts_bd/03_alquiler_chat_id.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
error_reporting(E_ALL);
ini_set("display_errors", 1);
require 'conexion/conexion.php';

// Añadir columna de id_conversacion a la tabla alquiler
$sql = "ALTER TABLE alquiler ADD COLUMN id_conversacion INT DEFAULT NULL";

if ($_conexion->query($sql)) {
    echo "Columna id_conversacion añadida con éxito a la tabla alquiler.";
} else {
    echo "Error o ya existía: " . $_conexion->error;
}
?>
