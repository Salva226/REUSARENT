<?php
// --- ARCHIVO: accion_comprar_plan.php ---
// Este script "invisible" se encarga de procesar la compra de un plan Premium o Platino.

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD BÁSICA
if (!isset($_SESSION["usuario"]) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['plan'])) {
    header("Location: index.php");
    exit();
}

$usuario = $_SESSION['usuario'];
$plan = $_POST['plan'];

// Si alguien intenta modificar el HTML con "Inspeccionar elemento" y manda un plan inventado, lo pillamos aquí.
if ($plan !== 'premium' && $plan !== 'platino') {
    header("Location: planes.php?error=plan_invalido");
    exit();
}

// Definir los precios según el plan
$precio = ($plan === 'premium') ? 9.99 : 24.99;

// 2. OBTENER DATOS ACTUALES DEL USUARIO
$consulta = "SELECT DNI, saldo, rol FROM usuario WHERE usuario = '$usuario'";
$resultado = $_conexion->query($consulta);

// Si por algún motivo el usuario no existe en BD, lo mandamos al login
if (!$resultado || $resultado->num_rows === 0) {
    header("Location: login.php");
    exit();
}

$fila = $resultado->fetch_assoc();
$dni = $fila['DNI'];
$saldo = (float)($fila['saldo'] ? $fila['saldo'] : 0);
$rolActual = $fila['rol'];

// 3. VALIDACIONES ANTES DE COBRAR
// Validar que no está comprando algo que ya tiene (Si ya es Platino, no le dejamos comprar nada porque es el máximo).
if ($rolActual == 'platino' || ($rolActual == 'premium' && $plan == 'premium')) {
    header("Location: planes.php?error=ya_tienes_el_plan");
    exit();
}

// Validar que tenga dinero suficiente en su cartera virtual
if ($saldo < $precio) {
    header("Location: planes.php?error=saldo_insuficiente");
    exit();
}

// 4. TRANSACCIÓN ECONÓMICA BANCARIA (Con transacciones SQL)
$_conexion->begin_transaction();

try {
    // 4.1. Restar saldo al comprador y ascenderle de rol
    $updateComprador = "UPDATE usuario SET saldo = saldo - $precio, rol = '$plan' WHERE DNI = '$dni'";
    $_conexion->query($updateComprador);

    // 4.2. Sumar saldo al administrador (Los beneficios de la web van directos a la cuenta del admin)
    // Usamos IFNULL(saldo, 0) por si fuera la primera vez y el saldo estuviese nulo.
    $updateAdmin = "UPDATE usuario SET saldo = IFNULL(saldo, 0) + $precio WHERE usuario = 'adminReusa'";
    $_conexion->query($updateAdmin);

    // Si ambos UPDATE funcionaron bien, guardamos definitivamente (commit).
    $_conexion->commit();
    
    header("Location: planes.php?exito=1");
    exit();

} catch (Exception $e) {
    // Si algo falla a medias (ej: le resté el dinero pero falló al dárselo al admin), deshago TODO (rollback) para no robarle el dinero.
    $_conexion->rollback();
    header("Location: planes.php?error=error_servidor");
    exit();
}
?>
