<?php
// --- ARCHIVO: alquilar.php ---
// Este archivo procesa todo lo que pasa cuando alguien pulsa "Reservar" en la pantallita de pago de un chat.

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD BÁSICA
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}

$usuarioActual = $_SESSION["usuario"];
$id_articulo = $_POST['id_articulo'] ?? '';
$inicio = $_POST['fecha_inicio'] ?? '';
$fin = $_POST['fecha_fin'] ?? '';

$mensajeTitulo = "";
$mensajeSub = "";
$exito = false;
$trackingID = "";
$necesitaRecarga = false;

// Si me llegan los datos del formulario por POST...
if ($id_articulo && $inicio && $fin) {
    
    // 2. OBTENER DATOS DEL COMPRADOR
    // Saco el saldo que tengo en mi cartera y mi DNI.
    $resUser = $_conexion->query("SELECT DNI, saldo FROM usuario WHERE usuario = '$usuarioActual'");
    $userData = $resUser->fetch_assoc();
    $miDNI = $userData['DNI'];
    $miSaldo = (float)$userData['saldo'];
    
    // Me aseguro de que estoy registrado en la tabla "arrendatario" (comprador).
    // Si no lo estoy, me inserto mágicamente para evitar fallos tontos.
    $resArr = $_conexion->query("SELECT id_arrendatario FROM arrendatario WHERE DNI = '$miDNI'");
    if ($resArr && $resArr->num_rows > 0) {
        $id_arrendatario = $resArr->fetch_assoc()['id_arrendatario'];
    } else {
        $_conexion->query("INSERT INTO arrendatario (DNI) VALUES ('$miDNI')");
        $id_arrendatario = $_conexion->insert_id;
    }

    // 3. OBTENER INFO DEL ARTÍCULO Y SU DUEÑO
    $resArt = $_conexion->query("SELECT precio, id_arrendador FROM articulo WHERE id_articulo = '$id_articulo'");
    $infoArt = $resArt->fetch_assoc();
    $precioDiario = (float)$infoArt['precio'];

    $dniVendedor = null;
    $resVen = $_conexion->query("SELECT DNI FROM arrendador WHERE id_arrendador = '{$infoArt['id_arrendador']}'");
    if($resVen) $dniVendedor = $resVen->fetch_assoc()['DNI'];

    // 4. EVITAR AUTO-ALQUILER
    // Si mi DNI es el mismo que el DNI del dueño, no me dejo alquilar mis propias cosas.
    if (strtolower($miDNI) === strtolower($dniVendedor)) {
        $mensajeTitulo = "Acción denegada";
        $mensajeSub = "No puedes alquilar tu propio artículo.";
        $necesitaRecarga = false;
        $id_articulo = null; // Fuerza a salir de la lógica y mostrar error
    }

    if ($id_articulo) {
        // 5. CÁLCULO DEL PRECIO
        // Cuento cuántos días van desde la fecha de inicio a la fecha de fin usando el objeto DateTime de PHP.
        $date1 = new DateTime($inicio);
        $date2 = new DateTime($fin);
        $interval = $date1->diff($date2);
        $dias = $interval->days;
        
        // Si lo alquilo para el mismo día, cuenta como 1 día.
        if ($dias == 0) $dias = 1;

        // Calculo lo que le voy a cobrar al usuario.
        $precioBase = $dias * $precioDiario;
        // La plataforma se queda un 10% de comisión por tramitar la reserva.
        $comision = $precioBase * 0.10; 
        $precioTotal = $precioBase + $comision;

        // 6. VERIFICAR SI HAY DINERO EN LA CARTERA
        if ($miSaldo < $precioTotal) {
            $mensajeTitulo = "Saldo insuficiente";
            $mensajeSub = "No tienes suficiente saldo en tu cartera. Tienes <strong>".number_format($miSaldo,2)."€</strong> y necesitas <strong>".number_format($precioTotal,2)."€</strong>.";
            // Si no tiene dinero, le diré a la vista que saque un botón para que vaya a recargar saldo.
            $necesitaRecarga = true;
        } else {
            // 7. GENERAR CÓDIGO DE SEGUIMIENTO (Tracking ID)
            // Genero un número aleatorio chulo tipo "RNT-A1B2C3" para que el usuario pueda hacer el seguimiento de su pedido.
            $trackingID = "RNT-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

            // 8. COMPROBAR DISPONIBILIDAD (No hacer Overbooking)
            // Reviso en la tabla alquiler si hay ALGUIEN que tenga este artículo reservado en estas fechas exactas.
            $sqlCheck = "SELECT * FROM alquiler 
                WHERE id_articulo = '$id_articulo' 
                AND estado != 'Rechazado'
                AND NOT (vencimiento_alquiler < '$inicio' OR fecha_alquiler > '$fin')";

            $res = $_conexion->query($sqlCheck);

            if ($res && $res->num_rows > 0) {
                // Hay solapamiento de fechas.
                $mensajeTitulo = "No disponible";
                $mensajeSub = "Este artículo ya está reservado o pendiente de reserva durante las fechas seleccionadas.";
            } else {
                
                // 9. PROCESAR PAGO (Retener dinero)
                // Le quito el dinero de la cartera al comprador. No se lo doy al vendedor todavía, lo guardo retenido.
                $_conexion->query("UPDATE usuario SET saldo = saldo - $precioTotal WHERE DNI = '$miDNI'");
                
                // 10. VINCULAR LA RESERVA CON EL CHAT
                // Localizo en qué chat (conversacion) estaban hablando comprador y vendedor, 
                // o lo creo nuevo si por alguna razón no existía.
                $idConversacionStr = 'NULL';
                if($dniVendedor) {
                    $chkConv = $_conexion->query("SELECT id_conversacion FROM conversacion WHERE id_articulo = '$id_articulo' AND LOWER(DNI_interesado) = LOWER('$miDNI') AND LOWER(DNI_vendedor) = LOWER('$dniVendedor')");
                    if($chkConv && $chkConv->num_rows > 0) {
                        $idConversacionStr = "'" . $chkConv->fetch_assoc()['id_conversacion'] . "'";
                    } else {
                        $_conexion->query("INSERT INTO conversacion (id_articulo, DNI_interesado, DNI_vendedor) VALUES ('$id_articulo', '$miDNI', '$dniVendedor')");
                        $idConversacionStr = "'" . $_conexion->insert_id . "'";
                    }
                }

                // 11. CREAR EL ALQUILER (¡Hecho!)
                // Inserto la reserva en estado "Pendiente". El vendedor tendrá que aceptarla ahora.
                $insert = "INSERT INTO alquiler (id_articulo, id_arrendatario, fecha_alquiler, vencimiento_alquiler, estado, precio_total, comision_plataforma, numero_seguimiento, id_conversacion) 
                           VALUES ('$id_articulo', '$id_arrendatario', '$inicio', '$fin', 'Pendiente', '$precioTotal', '$comision', '$trackingID', $idConversacionStr)";

                if ($_conexion->query($insert)) {
                    $exito = true;
                    $mensajeTitulo = "¡Solicitud de alquiler enviada!";
                    $mensajeSub = "Se han retenido <strong>".number_format($precioTotal,2)."€</strong> de tu saldo de forma segura. El propietario ha sido notificado.";
                } else {
                    // Si ha fallado la base de datos al hacer el INSERT, le devuelvo el dinero a su cartera por precaución.
                    $_conexion->query("UPDATE usuario SET saldo = saldo + $precioTotal WHERE DNI = '$miDNI'");
                    $mensajeTitulo = "Error del sistema";
                    $mensajeSub = "Tuvimos un problema procesando tu alquiler. Por favor, inténtalo de nuevo más tarde.";
                }
            }
        }
    }
} else {
    // Si intentaron entrar aquí borrando parámetros por URL o por un error del formulario
    if (!$mensajeTitulo) {
        $mensajeTitulo = "Error";
        $mensajeSub = "No se recibieron datos correctos para realizar la operación.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Estado de Solicitud | Reusarent</title>
    <?php include 'includes/head.php'; ?>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <?php include 'includes/top_nav.php'; ?>
    <main class="flex-1 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-8 sm:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 max-w-md w-full text-center relative overflow-hidden">

            <?php if ($exito): ?>
                <div class="w-24 h-24 bg-[#e8f7f5] rounded-full mx-auto flex items-center justify-center mb-6 relative">
                    <i class="ph-fill ph-paper-plane-tilt text-5xl text-[#068f80]"></i>
                    <div class="absolute inset-0 border-4 border-[#09b6a2] rounded-full animate-ping opacity-20"></div>
                </div>

                <div class="inline-block bg-brand/10 text-brand font-bold px-4 py-1.5 rounded-full mb-3 uppercase tracking-widest text-xs">
                    <?= $trackingID ?>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-3 tracking-tight"><?= $mensajeTitulo ?></h1>
                <p class="text-gray-500 mb-8 leading-relaxed"><?= $mensajeSub ?></p>

                <div class="space-y-3">
                    <?php if(isset($idConversacionStr) && $idConversacionStr != 'NULL'): ?>
                    <a href="buzon.php?c=<?= str_replace("'", "", $idConversacionStr) ?>" class="block w-full bg-brand text-white py-3.5 rounded-full font-bold shadow-lg shadow-brand/30 hover:bg-brand-dark transition-all active:scale-95">
                        Ir al chat con el vendedor
                    </a>
                    <?php endif; ?>
                    <a href="dashboard.php" class="block w-full bg-gray-50 text-gray-700 py-3.5 rounded-full font-bold hover:bg-gray-100 transition-all border border-gray-200">
                        Ir al Panel de Usuario
                    </a>
                </div>

            <?php else: ?>

                <div class="w-24 h-24 <?= $necesitaRecarga ? 'bg-orange-50' : 'bg-red-50' ?> rounded-full mx-auto flex items-center justify-center mb-6">
                    <i class="ph-fill <?= $necesitaRecarga ? 'ph-wallet text-orange-500' : 'ph-warning-circle text-red-500' ?> text-6xl"></i>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-3"><?= $mensajeTitulo ?></h1>
                <p class="text-gray-500 mb-8"><?= $mensajeSub ?></p>

                <?php if($necesitaRecarga): ?>
                <a href="dashboard.php" class="block w-full bg-brand text-white py-3.5 rounded-full font-bold shadow-md hover:bg-brand-dark transition-all active:scale-95 mb-3">
                    Ir a recargar saldo
                </a>
                <?php endif; ?>
                
                <button onclick="window.history.back()" class="w-full bg-gray-900 text-white py-3.5 rounded-full font-bold shadow-md hover:bg-black transition-all active:scale-95">
                    Volver al artículo
                </button>

            <?php endif; ?>

        </div>
    </main>
</body>
</html>