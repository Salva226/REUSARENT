<?php
// --- ARCHIVO: scripts_bd/02_alquiler_pagos.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
error_reporting(E_ALL);
ini_set("display_errors", 1);
require 'conexion/conexion.php';

// Añadir columnas de estado, economía y seguimiento a la tabla alquiler
$sql = "ALTER TABLE alquiler 
    ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
    ADD COLUMN precio_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN comision_plataforma DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN numero_seguimiento VARCHAR(20) DEFAULT NULL";

if ($_conexion->query($sql)) {
    echo "Columnas añadidas con éxito a la tabla alquiler.";
} else {
    echo "Error o ya existían las columnas: " . $_conexion->error;
}
?>
