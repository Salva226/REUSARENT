<?php
// --- ARCHIVO: scripts_bd/05_articulo_descripcion.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
error_reporting(E_ALL);
require "conexion/conexion.php";

echo "<h1>Actualización de Base de Datos - Fase 16 (Descripciones)</h1>";

// 1. Añadir columna 'descripcion' a la tabla 'articulo'
$sql = "ALTER TABLE articulo ADD COLUMN descripcion VARCHAR(500) DEFAULT NULL";

if ($_conexion->query($sql) === TRUE) {
    echo "<p style='color:green;'>Exito: Columna 'descripcion' añadida a la tabla 'articulo'.</p>";
} else {
    echo "<p style='color:red;'>Aviso: " . $_conexion->error . " (Puede que la columna ya exista)</p>";
}

echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>
