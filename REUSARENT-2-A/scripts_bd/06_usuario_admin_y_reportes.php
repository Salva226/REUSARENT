<?php
// --- ARCHIVO: scripts_bd/06_usuario_admin_y_reportes.php ---
// Script de migración de base de datos. Estos archivos son consultas (queries) sueltas que se ejecutan una sola vez para actualizar la estructura de la base de datos (por ejemplo, añadir una tabla nueva o una columna que faltaba).
error_reporting(E_ALL);
require "conexion/conexion.php";

echo "<h1>Configuración Fase 18: Admin y Reportes</h1>";

// 1. Insertar el usuario adminReusa si no existe
$usuario = 'adminReusa';
$passw = 'reusaAdmin123!';
$passw_cifrada = password_hash($passw, PASSWORD_DEFAULT);
$dni_admin = '00000000A';
$email_admin = 'admin@reusarent.com';

$checkAdmin = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$usuario'");
if ($checkAdmin && $checkAdmin->num_rows == 0) {
    $insertAdmin = "INSERT INTO usuario (DNI, usuario, contrasena, rol, telefono, email, saldo) 
                    VALUES ('$dni_admin', '$usuario', '$passw_cifrada', 'admin', '600000000', '$email_admin', 0.00)";
    if ($_conexion->query($insertAdmin)) {
        echo "<p style='color:green;'>Usuario $usuario creado correctamente.</p>";
    } else {
        echo "<p style='color:red;'>Error creando admin: " . $_conexion->error . "</p>";
    }
} else {
    // Si ya existe, le forzamos la contraseña para que no haya errores
    $updateAdmin = "UPDATE usuario SET contrasena = '$passw_cifrada' WHERE usuario = '$usuario'";
    if ($_conexion->query($updateAdmin)) {
        echo "<p style='color:orange;'>El usuario $usuario ya existía. Se ha reseteado su contraseña a la correcta.</p>";
    }
}

// 2. Crear tabla reportes
$sqlReportes = "
CREATE TABLE IF NOT EXISTS reportes (
    id_reporte INT AUTO_INCREMENT PRIMARY KEY,
    id_articulo INT NOT NULL,
    DNI_denunciante VARCHAR(9) NOT NULL,
    motivo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) DEFAULT 'Pendiente',
    FOREIGN KEY (id_articulo) REFERENCES articulo(id_articulo) ON DELETE CASCADE
) ENGINE=InnoDB;
";

if ($_conexion->query($sqlReportes)) {
    echo "<p style='color:green;'>Tabla 'reportes' creada o verificada correctamente.</p>";
} else {
    echo "<p style='color:red;'>Error creando tabla reportes: " . $_conexion->error . "</p>";
}

echo "<h3>Configuración completada.</h3>";
?>
