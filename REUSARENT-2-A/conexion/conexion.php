<?php
// --- ARCHIVO: conexion.php ---
// Desactivar excepciones automáticas de MySQLi para poder manejarlas con if/else (evita "Fatal error: Uncaught mysqli_sql_exception")
mysqli_report(MYSQLI_REPORT_OFF);

// Este es el corazón de la app. Aquí me conecto a la base de datos.
// Si esto falla, ninguna otra página va a funcionar porque todas incluyen este archivo.

// Aquí defino las credenciales. Estas son para cuando uso Docker o XAMPP.
$_servidor = "db";
$_usuario = "root";
$_passw = "root";
$_bd = "alquiler_bd";

/*
// He dejado comentadas las credenciales de los distintos entornos por si tengo que cambiar rápido.
Para local
$_servidor = "localhost";
$_usuario = "root";
$_passw = "";
$_bd = "alquiler_bd";

Para web (InfinityFree)
$_servidor = "sql100.infinityfree.com";
$_usuario = "if0_41482112";
$_passw = "UNYzlDe5HhAZEfL";
$_bd = "if0_41482112_alquiler_bd";
*/

// Instancio el objeto mysqli pasándole mis credenciales. ¡Cruzamos los dedos!
$_conexion = new mysqli($_servidor, $_usuario, $_passw, $_bd);

// Le digo que use UTF-8 para que las ñ y los acentos no se vean como símbolos raros de interrogación en la BD.
mysqli_set_charset($_conexion, "utf8");

// Si falla la conexión (por ejemplo la contraseña está mal), mato la página del tirón con die()
// y saco el mensaje de error para enterarme de qué ha pasado.
if ($_conexion->connect_error) {
    die("Error de conexión: " . $_conexion->connect_error);
}

// Para evitar problemas con la hora de los alquileres y los chats, 
// fuerzo a PHP a usar la hora de España.
date_default_timezone_set('Europe/Madrid');

// MAGIA: PHP calcula automáticamente si estamos en horario de invierno (+01:00) o de verano (+02:00)
$offset = (new DateTime())->format('P'); 

// Le paso ese cálculo exacto a MySQL para que los inserts guarden la hora exacta en la que estamos aquí.
$_conexion->query("SET time_zone = '$offset'");
?>