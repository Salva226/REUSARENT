<?php
// --- ARCHIVO: comprobar_datos.php ---
// Este archivo lo llamo por AJAX desde el registro para ver si el usuario o email ya existen 
// antes de recargar la página.

// 1. INCLUYO LA CONEXIÓN
// Me traigo el archivo que me conecta a la base de datos.
require "conexion.php";

// 2. RECOJO LO QUE ME MANDA JAVASCRIPT
// Uso '??' (Null Coalescing) para que si no llega nada por POST, se guarde un texto vacío y PHP no se queje.
$usuario = $_POST['usuario'] ?? '';
$email = $_POST['email'] ?? '';
$dni = $_POST['dni'] ?? '';

// 3. PREPARO LA RESPUESTA
// Creo un array asociativo asumiendo por defecto que ni el usuario ni el email ni el dni existen (false).
$respuesta = [
    "usuarioExiste" => false,
    "emailExiste" => false,
    "dniExiste" => false
];

// 4. HAGO LAS COMPROBACIONES EN LA BASE DE DATOS
if ($usuario !== '' && $email !== '' && $dni !== '') {

    // Le pregunto a la BD: "¿Hay algún usuario con este nombre?"
    $queryUser = $_conexion->query("SELECT usuario FROM usuario WHERE usuario = '$usuario'");

    // Si num_rows es mayor que 0, significa que ha encontrado uno. ¡El usuario ya existe!
    if ($queryUser->num_rows > 0) {
        $respuesta["usuarioExiste"] = true; // Cambio mi respuesta a true
    }

    // Le pregunto a la BD: "¿Hay algún usuario con este correo electrónico?"
    $queryEmail = $_conexion->query("SELECT email FROM usuario WHERE email = '$email'");

    // Si encuentra el correo, marco que existe.
    if ($queryEmail->num_rows > 0) {
        $respuesta["emailExiste"] = true;
    }

    // Le pregunto a la BD: "¿Hay algún usuario con este DNI?"
    $queryDNI = $_conexion->query("SELECT DNI FROM usuario WHERE DNI = '$dni'");

    // Si encuentra el DNI, marco que existe.
    if ($queryDNI->num_rows > 0) {
        $respuesta["dniExiste"] = true;
    }
}

// 5. DEVUELVO LA RESPUESTA A JAVASCRIPT
// Le digo al navegador que lo que le voy a devolver es JSON puro, no HTML.
header('Content-Type: application/json');

// Con json_encode transformo mi array de PHP en un string JSON que el fetch de JS entenderá a la perfección.
echo json_encode($respuesta);
?>
