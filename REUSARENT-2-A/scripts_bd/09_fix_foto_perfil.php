<?php
// --- ARCHIVO: scripts_bd/09_fix_foto_perfil.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
require 'conexion/conexion.php';
$_conexion->query("ALTER TABLE usuario ADD COLUMN foto_perfil LONGBLOB;");
echo "Columna foto_perfil agregada a la tabla usuario.";
?>