<?php
// --- ARCHIVO: scripts_bd/01_crear_tablas_chat.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
error_reporting(E_ALL);
ini_set("display_errors", 1);
require 'conexion/conexion.php';

$sql1 = "CREATE TABLE IF NOT EXISTS fotos_articulo (id_foto INT AUTO_INCREMENT PRIMARY KEY, id_articulo INT, foto LONGBLOB) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$_conexion->query($sql1);

$sql2 = "CREATE TABLE IF NOT EXISTS conversacion (id_conversacion INT AUTO_INCREMENT PRIMARY KEY, id_articulo INT, DNI_interesado VARCHAR(9), DNI_vendedor VARCHAR(9)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$_conexion->query($sql2);

$sql3 = "CREATE TABLE IF NOT EXISTS mensaje (id_mensaje INT AUTO_INCREMENT PRIMARY KEY, id_conversacion INT, DNI_remitente VARCHAR(9), texto TEXT, fecha DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$_conexion->query($sql3);

echo "Tablas creadas";
?>