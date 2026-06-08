<?php
// --- ARCHIVO: buzon.php ---
// Aquí gestionamos todos los chats entre usuarios (como ocurre en Wallapop o Vinted).

// Evitar el típico error 500 si la gente sube fotos muy grandes o hay muchos mensajes, dándole más RAM a PHP.
ini_set('memory_limit', '256M'); 
ini_set('display_errors', 1); // Fuerzo a que salgan los errores en pantalla por si falla algo en producción
error_reporting(E_ALL);

session_start();

// 1. SEGURIDAD DE SESIÓN
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}

require "conexion/conexion.php";

// 2. OBTENER MI PROPIO DNI
// Todos los chats se asocian a un DNI de vendedor y un DNI de interesado.
$usuarioLogeado = $_SESSION["usuario"];
$usuarioEscaped = $_conexion->real_escape_string($usuarioLogeado);

$resDNI = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$usuarioEscaped'");
$miDNI = null;
if ($resDNI && $resDNI->num_rows > 0) {
    $miDNI = $resDNI->fetch_assoc()['DNI'];
}

// Si la BD se vuelve loca y no encuentra el DNI del usuario logueado, corto todo.
if (!$miDNI) {
    die("Error crítico: No se encuentra tu DNI en la base de datos.");
}

// 3. INICIAR UN CHAT NUEVO DESDE UN ARTÍCULO
// Si llego a esta página desde el botón "Contactar" de info_articulos.php, me llegarán estos dos parámetros por GET.
if (isset($_GET['nuevo_chat_articulo']) && isset($_GET['vendedor'])) {
    $idArt = (int) $_GET['nuevo_chat_articulo'];
    $dniVend = $_conexion->real_escape_string($_GET['vendedor']);
    $miDNIEscaped = $_conexion->real_escape_string($miDNI);

    // Primero busco si ya estaba hablando con este vendedor sobre este artículo concreto.
    // Uso LOWER() para comparar DNI y evitar fallos por mayúsculas/minúsculas.
    $consultaCheck = "SELECT id_conversacion FROM conversacion WHERE id_articulo = '$idArt' AND LOWER(DNI_interesado) = LOWER('$miDNIEscaped') AND LOWER(DNI_vendedor) = LOWER('$dniVend')";
    $check = $_conexion->query($consultaCheck);
    
    if ($check && $check->num_rows > 0) {
        // Si ya teníamos un chat abierto de este producto, te redirijo a ese chat directamente.
        $idConversacion = $check->fetch_assoc()['id_conversacion'];
        header("Location: buzon.php?c=$idConversacion");
        exit;
    } else {
        // Si nunca habíamos hablado de este artículo, creo una nueva fila en la tabla 'conversacion'.
        $sqlInsert = "INSERT INTO conversacion (id_articulo, DNI_interesado, DNI_vendedor) VALUES ('$idArt', '$miDNIEscaped', '$dniVend')";
        if ($_conexion->query($sqlInsert)) {
            // Pillo el ID de la conversación que acabo de crear y te mando ahí.
            $idConversacion = $_conexion->insert_id;
            header("Location: buzon.php?c=$idConversacion");
            exit;
        } else {
            // Error de BBDD al crear el chat
            header("Location: index.php");
            exit();
        }
    }
}

// 4. LISTAR MIS CONVERSACIONES (PANEL IZQUIERDO)
// Quiero sacar todos los chats donde yo participe, ya sea como interesado o como vendedor.
$miDNIEscaped = $_conexion->real_escape_string($miDNI);
$consultaConv = "
    SELECT c.id_conversacion, a.nombre, a.foto, 
           u_interesado.usuario as nombre_interesado, u_vendedor.usuario as nombre_vendedor,
           c.DNI_interesado, c.DNI_vendedor,
           (SELECT MAX(fecha) FROM mensaje WHERE id_conversacion = c.id_conversacion) as ultima_actividad
    FROM conversacion c
    INNER JOIN articulo a ON c.id_articulo = a.id_articulo
    INNER JOIN usuario u_interesado ON c.DNI_interesado = u_interesado.DNI
    INNER JOIN usuario u_vendedor ON c.DNI_vendedor = u_vendedor.DNI
    WHERE LOWER(c.DNI_interesado) = LOWER('$miDNIEscaped') 
       OR LOWER(c.DNI_vendedor) = LOWER('$miDNIEscaped')
    ORDER BY ultima_actividad DESC, c.id_conversacion DESC
"; // ORDER BY ultima_actividad DESC hace que el chat que ha recibido el último mensaje suba arriba del todo.

$resultadoConv = $_conexion->query($consultaConv);
$conversaciones = [];
if ($resultadoConv) {
    while ($row = $resultadoConv->fetch_assoc()) {
        $conversaciones[] = $row;
    }
}

// 5. CARGAR LOS MENSAJES DEL CHAT SELECCIONADO (PANEL DERECHO)
// Miro si por URL me están diciendo qué chat en concreto quiero leer (?c=XX)
$chatActivo = isset($_GET['c']) ? (int) $_GET['c'] : null;
$mensajesData = [];
$infoChat = null;

if ($chatActivo) {
    // Busco en el array de mis conversaciones a ver si la que quiero leer está ahí (Por seguridad)
    foreach ($conversaciones as $conv) {
        if ((int) $conv['id_conversacion'] === $chatActivo) {
            $infoChat = $conv;
            break;
        }
    }

    if ($infoChat) {
        // ACTUALIZAR A "LEÍDO": Si yo he abierto este chat, pongo todos los mensajes que no sean míos como leídos (leido = 1).
        $_conexion->query("UPDATE mensaje SET leido = 1 WHERE id_conversacion = '$chatActivo' AND DNI_remitente != '$miDNIEscaped'");

        // Saco todos los mensajes de este chat ordenados de más antiguo a más nuevo para pintarlos en pantalla.
        $resMensajes = $_conexion->query("SELECT m.*, u.usuario as remitente_nombre FROM mensaje m INNER JOIN usuario u ON m.DNI_remitente = u.DNI WHERE id_conversacion = '$chatActivo' ORDER BY m.fecha ASC");
        if ($resMensajes) {
            while ($m = $resMensajes->fetch_assoc()) {
                $mensajesData[] = $m;
            }
        }
        
        // 6. VINCULAR CON SOLICITUD DE ALQUILER
        // Miro si de esta conversación ya ha surgido un intento de alquiler para sacar la tarjetita de pago en el chat.
        $alquilerVinculado = null;
        $resAlq = $_conexion->query("SELECT * FROM alquiler WHERE id_conversacion = '$chatActivo' ORDER BY id_alquiler DESC LIMIT 1");
        if ($resAlq && $resAlq->num_rows > 0) {
            $alquilerVinculado = $resAlq->fetch_assoc();
        }
    } else {
        // Si intentan entrar a un ID de chat que no es suyo, les borro la variable para que salga el buzón vacío.
        $chatActivo = null; 
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Buzón | Reusarent</title>
    <?php include 'includes/head.php'; ?>
    <style>
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<!-- EL TRUCO ESTÁ AQUÍ: 100dvh para que los navegadores móviles no tapen la zona de abajo -->
<body class="bg-gray-50 flex flex-col overflow-hidden text-gray-900" style="height: 100vh; height: 100dvh;">

    <?php include 'includes/top_nav.php'; ?>

    <main class="flex-1 w-full max-w-7xl mx-auto flex overflow-hidden lg:p-6 relative bg-gray-50">

        <?php if (count($conversaciones) === 0): ?>
            <!-- ESTADO VACÍO GENERAL -->
            <div class="flex-1 flex flex-col items-center justify-center p-6 text-center w-full">
                <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mb-6">
                    <i class="ph-fill ph-chats-circle text-5xl text-gray-400"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Tu buzón está vacío</h2>
                <p class="text-gray-500 mb-8 max-w-sm mx-auto">Cuando contactes a compradores sobre un artículo, tus conversaciones aparecerán aquí.</p>
                <a href="index.php" class="bg-brand text-white px-8 py-3.5 rounded-full font-bold hover:bg-brand-dark transition-all shadow-md active:scale-95 text-sm">
                    Buscar artículos
                </a>
            </div>
        <?php else: ?>

            <!-- PANEL IZQUIERDO: BANDEJA DE ENTRADA -->
            <div class="<?= $chatActivo ? 'hidden lg:flex' : 'flex' ?> w-full lg:w-96 flex-col bg-white border-r border-gray-200 lg:rounded-l-3xl shadow-sm h-full z-10 relative">
                <div class="p-5 border-b border-gray-100 flex-shrink-0 bg-white lg:rounded-tl-3xl">
                    <h2 class="text-xl font-bold tracking-tight">Mensajes</h2>
                </div>

                <div class="flex-1 overflow-y-auto hide-scroll pb-24 lg:pb-0">
                    <?php foreach ($conversaciones as $c):
                        $soyVendedor = (strtolower($c['DNI_vendedor'] ?? '') === strtolower($miDNI ?? ''));
                        $nombrePartner = $soyVendedor ? $c['nombre_interesado'] : $c['nombre_vendedor'];
                        $isActivo = ($chatActivo == $c['id_conversacion']);
                        $img = !empty($c['foto']) ? 'data:image/jpeg;base64,' . base64_encode($c['foto']) : null;
                        ?>
                        <a href="buzon.php?c=<?= $c['id_conversacion'] ?>" class="flex items-center gap-4 p-4 border-b border-gray-50 hover:bg-brand/5 transition-colors <?= $isActivo ? 'bg-brand/10 relative overflow-hidden' : '' ?>">
                            <?php if ($isActivo): ?>
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-brand"></div>
                            <?php endif; ?>

                            <div class="relative">
                                <?php if ($img): ?>
                                    <div class="w-14 h-14 rounded-xl overflow-hidden bg-gray-100 shadow-sm border border-gray-200">
                                        <img src="<?= $img ?>" class="w-full h-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="w-14 h-14 bg-gray-200 rounded-xl flex items-center justify-center border border-gray-200">
                                        <i class="ph ph-image text-gray-400 text-xl"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute -bottom-2 -right-2 w-7 h-7 bg-white rounded-full flex items-center justify-center shadow-sm border border-gray-100">
                                    <i class="ph-fill ph-user text-gray-500 text-[10px]"></i>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-bold text-gray-900 mb-0.5 truncate"><?= htmlspecialchars($nombrePartner) ?></p>
                                <p class="text-xs font-semibold text-brand truncate leading-tight"><?= htmlspecialchars($c['nombre']) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PANEL DERECHO: ÁREA DE CHAT -->
            <div class="<?= !$chatActivo ? 'hidden lg:flex' : 'flex' ?> flex-1 flex-col bg-[#f0f2f5] lg:rounded-r-3xl h-full relative">

                <?php if ($chatActivo && $infoChat):
                    $soyVendedor = (strtolower($infoChat['DNI_vendedor'] ?? '') === strtolower($miDNI ?? ''));
                    $nombrePartner = $soyVendedor ? $infoChat['nombre_interesado'] : $infoChat['nombre_vendedor'];
                    ?>
                    <div class="h-[76px] bg-white border-b border-gray-200 px-4 flex items-center gap-4 flex-shrink-0 lg:rounded-tr-3xl shadow-sm z-10 w-full">
                        <a href="buzon.php" class="lg:hidden w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 active:scale-95 transition-all -ml-2">
                            <i class="ph-bold ph-arrow-left text-xl text-gray-600"></i>
                        </a>
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                            <i class="ph-fill ph-user text-gray-400 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 leading-tight"><?= htmlspecialchars($nombrePartner) ?></h3>
                            <a href="info_articulos.php?articulo=<?= urlencode($infoChat['nombre']) ?>" class="text-[11px] font-bold text-brand hover:underline mt-px inline-block"><?= htmlspecialchars($infoChat['nombre']) ?></a>
                        </div>
                    </div>

                    <?php if (isset($alquilerVinculado) && $alquilerVinculado): 
                        $colorState = "bg-gray-100 text-gray-700";
                        if($alquilerVinculado['estado'] == 'Pendiente') $colorState = "bg-yellow-100 text-yellow-700";
                        if($alquilerVinculado['estado'] == 'Aceptado') $colorState = "bg-green-100 text-green-700";
                        if($alquilerVinculado['estado'] == 'Rechazado') $colorState = "bg-red-100 text-red-700";
                    ?>
                    <div class="bg-brand/5 border-b border-brand/10 p-4 shrink-0 shadow-sm z-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Alquiler vinculado • <?= $alquilerVinculado['numero_seguimiento'] ?></p>
                                <p class="text-sm font-bold text-gray-900"><i class="ph ph-calendar-blank text-brand mr-1"></i><?= $alquilerVinculado['fecha_alquiler'] ?> al <?= $alquilerVinculado['vencimiento_alquiler'] ?> <span class="text-brand ml-2 text-base font-black"><?= $alquilerVinculado['precio_total'] ?>€</span></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $colorState ?> border border-current/20 shadow-sm"><?= $alquilerVinculado['estado'] ?></span>
                                
                                <?php if($soyVendedor && $alquilerVinculado['estado'] == 'Pendiente'): ?>
                                    <form action="accion_alquiler.php" method="POST" class="inline">
                                        <input type="hidden" name="id_alquiler" value="<?= $alquilerVinculado['id_alquiler'] ?>">
                                        <input type="hidden" name="accion" value="Aceptar">
                                        <button type="submit" class="bg-brand text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-brand-dark active:scale-95 transition-all shadow-md shadow-brand/30">Aceptar</button>
                                    </form>
                                    <form action="accion_alquiler.php" method="POST" class="inline">
                                        <input type="hidden" name="id_alquiler" value="<?= $alquilerVinculado['id_alquiler'] ?>">
                                        <input type="hidden" name="accion" value="Rechazar">
                                        <button type="submit" class="bg-white text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-50 active:scale-95 transition-all border border-gray-200 shadow-sm">Rechazar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Área de Mensajes -->
                    <div id="mensajes-container" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 pb-4">
                        <?php if (count($mensajesData) === 0): ?>
                            <div class="text-center text-xs font-semibold text-gray-400 bg-black/5 px-4 py-2 rounded-full w-fit mx-auto shadow-sm">
                                El historial de chat inicia aquí
                            </div>
                        <?php else: ?>
                            <?php foreach ($mensajesData as $m):
                                $esMio = (strtolower($m['DNI_remitente'] ?? '') === strtolower($miDNI ?? ''));
                                ?>
                                <div class="flex <?= $esMio ? 'justify-end' : 'justify-start' ?> w-full">
                                    <div class="max-w-[85%] lg:max-w-[65%] <?= $esMio ? 'bg-brand text-white rounded-t-2xl rounded-bl-2xl shadow-[0_2px_8px_rgba(9,182,162,0.3)]' : 'bg-white text-gray-800 rounded-t-2xl rounded-br-2xl shadow-sm border border-gray-100' ?> px-4 py-3 relative">
                                        <p class="text-sm leading-relaxed whitespace-pre-wrap <?= $esMio ? 'text-white' : 'text-gray-800' ?>"><?= htmlspecialchars($m['texto']) ?></p>
                                        <span class="text-[10px] <?= $esMio ? 'text-white/70' : 'text-gray-400' ?> block text-right mt-1.5 font-medium"><?= date("H:i", strtotime($m['fecha'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Base de Entrada de Texto -->
                    <!-- He añadido pb-4 para que despegue un poco del borde inferior del móvil -->
                    <div class="shrink-0 flex-none w-full bg-[#f0f2f5] p-3 pt-2 pb-4 lg:pb-3 border-t border-gray-200/50">
                        <form id="chatForm" onsubmit="enviarMensaje(event)" class="bg-white rounded-full flex shadow-[0_2px_12px_rgba(0,0,0,0.06)] border border-gray-200 overflow-hidden px-2 py-1.5 w-full items-center">
                            <input id="mensajeTexto" type="text" placeholder="Escribe un mensaje..." class="flex-1 border-none bg-transparent py-2.5 px-4 focus:ring-0 text-sm outline-none text-gray-800" autocomplete="off">
                            <button type="submit" id="btnEnviar" class="w-10 h-10 bg-brand rounded-full text-white flex items-center justify-center hover:bg-brand-dark active:scale-90 transition-transform disabled:opacity-50 flex-shrink-0">
                                <i class="ph-fill ph-paper-plane-right text-lg -ml-0.5"></i>
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <div class="flex-1 flex flex-col items-center justify-center text-center p-6 bg-white/50 h-full">
                        <i class="ph-fill ph-paper-plane-tilt text-6xl text-gray-200 mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-400">Selecciona un chat</h3>
                        <p class="text-sm text-gray-400 mt-1">Haz clic en una conversación a la izquierda para empezar a leer</p>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </main>

    <!-- AQUÍ ESTÁ LA MAGIA: Solo muestra el navbar inferior si NO estás dentro de un chat -->
    <?php if (!$chatActivo): ?>
    <div class="lg:hidden">
        <?php include 'includes/bottom_nav.php'; ?>
    </div>
    <?php endif; ?>

    <?php if ($chatActivo): ?>
        <script>
            const container = document.getElementById('mensajes-container');
            container.scrollTop = container.scrollHeight;

            async function enviarMensaje(e) {
                e.preventDefault();
                const input = document.getElementById('mensajeTexto');
                const texto = input.value.trim();
                const btn = document.getElementById('btnEnviar');

                if (!texto) return;

                const d = new Date();
                const horaStr = String(d.getHours()).padStart(2, '0') + ":" + String(d.getMinutes()).padStart(2, '0');

                const htmlMio = `
                <div class="flex justify-end w-full">
                    <div class="max-w-[85%] lg:max-w-[65%] bg-brand text-white rounded-t-2xl rounded-bl-2xl shadow-[0_2px_8px_rgba(9,182,162,0.3)] px-4 py-3 relative">
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">${texto}</p>
                        <span class="text-[10px] text-white/70 block text-right mt-1.5 font-medium">${horaStr} <i class="ph ph-clock ml-1"></i></span>
                    </div>
                </div>`;

                container.insertAdjacentHTML('beforeend', htmlMio);
                container.scrollTop = container.scrollHeight;

                input.value = '';
                btn.disabled = true;

                try {
                    await fetch('enviar_mensaje.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_conversacion: <?= $chatActivo ?>, texto: texto })
                    });
                } catch (error) {
                    console.error(error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un error al entregar el mensaje.', confirmButtonColor: '#09b6a2' });
                }
                btn.disabled = false;
                input.focus();
            }
        </script>
    <?php endif; ?>

</body>

</html>