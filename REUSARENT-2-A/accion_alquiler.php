<?php
// --- ARCHIVO: accion_alquiler.php ---
// Este script "invisible" procesa los botones de Aceptar o Rechazar 
// que le salen al vendedor cuando alguien quiere alquilarle algo.

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD BÁSICA
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}

// 2. RECOGER DATOS
// Pillo el ID del alquiler que queremos procesar y qué botón ha pulsado ('Aceptar' o 'Rechazar')
$id_alquiler = $_POST['id_alquiler'] ?? '';
$accion = $_POST['accion'] ?? ''; 
$usuario = $_SESSION["usuario"];

// Si tengo ID y la acción es válida, sigo.
if ($id_alquiler && in_array($accion, ['Aceptar', 'Rechazar'])) {
    
    // 3. SEGURIDAD AVANZADA (Check Ownership)
    // Hago esta mega consulta cruzando 6 tablas para asegurarme al 100% 
    // de que la persona que ha pulsado el botón "Aceptar" es REALMENTE 
    // el dueño del artículo, y no un hacker inyectando IDs falsos por POST.
    $checkOwnership = "SELECT alq.id_alquiler, alq.precio_total, alq.comision_plataforma, u_comprador.DNI as dni_comprador, u_vendedor.DNI as dni_vendedor, alq.estado 
                       FROM alquiler alq
                       INNER JOIN articulo art ON alq.id_articulo = art.id_articulo
                       INNER JOIN arrendador a ON art.id_arrendador = a.id_arrendador
                       INNER JOIN usuario u_vendedor ON a.DNI = u_vendedor.DNI
                       INNER JOIN arrendatario arr ON alq.id_arrendatario = arr.id_arrendatario
                       INNER JOIN usuario u_comprador ON arr.DNI = u_comprador.DNI
                       WHERE u_vendedor.usuario = '$usuario' AND alq.id_alquiler = '$id_alquiler'";
                       
    $resOwner = $_conexion->query($checkOwnership);
    
    // Si la base de datos me devuelve resultados, eso significa que este usuario SÍ es el dueño.
    if ($resOwner && $resOwner->num_rows > 0) {
        $datosAlquiler = $resOwner->fetch_assoc();
        
        // 4. EVITAR DOBLES CLICS O BUGS
        // Solo dejo que procesen las solicitudes que sigan estando en estado 'Pendiente'.
        // Si ya lo aceptó ayer y recarga la página, lo ignoro.
        if ($datosAlquiler['estado'] == 'Pendiente') {
            $nuevoEstado = $accion == 'Aceptar' ? 'Aceptado' : 'Rechazado';
            
            // Actualizo el estado del alquiler en la BD.
            $update = "UPDATE alquiler SET estado = '$nuevoEstado' WHERE id_alquiler = '$id_alquiler'";
            
            if($_conexion->query($update)) {
                
                // 5. DEVOLUCIÓN DE DINERO AL COMPRADOR (Si rechaza)
                // Ojo aquí: Cuando alguien solicita un alquiler, se le retiene el saldo.
                // Si el vendedor rechaza la solicitud, le tengo que devolver su dinero íntegro al interesado.
                if ($nuevoEstado == 'Rechazado') {
                    $precioTotal = (float)$datosAlquiler['precio_total'];
                    $dniComprador = $datosAlquiler['dni_comprador'];
                    $_conexion->query("UPDATE usuario SET saldo = saldo + $precioTotal WHERE DNI = '$dniComprador'");
                }
                
                // 6. INGRESO DE DINERO AL VENDEDOR (Si acepta)
                // Si acepta, el vendedor se lleva la pasta, PERO...
                // Yo me quedo con mi comisión (2%, 5% o 10% dependiendo de su plan).
                else if ($nuevoEstado == 'Aceptado') {
                    $neto = (float)$datosAlquiler['precio_total'] - (float)$datosAlquiler['comision_plataforma'];
                    $dniVendedor = $datosAlquiler['dni_vendedor'];
                    $_conexion->query("UPDATE usuario SET saldo = saldo + $neto WHERE DNI = '$dniVendedor'");
                }
            }
        }
    }
}

// 7. REDIRECCIÓN INTELIGENTE
// Miro de qué página venía (dashboard o chat) y lo mando de vuelta al mismo sitio exacto.
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'buzon.php') !== false) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    // Por si acaso no sé de dónde venía, lo mando a su panel de control por defecto.
    header("Location: dashboard.php");
}
exit();
?>
