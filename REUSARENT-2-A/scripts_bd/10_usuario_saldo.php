<?php
// --- ARCHIVO: scripts_bd/10_usuario_saldo.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
error_reporting(E_ALL);
ini_set("display_errors", 1);
require 'conexion/conexion.php';

// Añadir columna de saldo
$sql = "ALTER TABLE usuario ADD COLUMN saldo DECIMAL(10,2) NOT NULL DEFAULT 0.00";
$_conexion->query($sql);

echo "Columna Saldo añadida";
?>