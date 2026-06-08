<?php
// --- ARCHIVO: scripts_bd/07_usuario_foto_perfil.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
error_reporting(E_ALL);
require "conexion/conexion.php";

echo "<h1>Configuración Fase 19: Perfil</h1>";

// Añadir columna foto_perfil a la tabla usuario
$sql = "ALTER TABLE usuario ADD COLUMN foto_perfil LONGBLOB DEFAULT NULL";

if ($_conexion->query($sql) === TRUE) {
    echo "<p style='color:green;'>Éxito: Columna 'foto_perfil' añadida a la tabla 'usuario'.</p>";
} else {
    echo "<p style='color:orange;'>Aviso: " . $_conexion->error . " (Probablemente ya existía la columna)</p>";
}

echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>
