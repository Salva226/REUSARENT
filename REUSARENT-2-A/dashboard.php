<?php
// --- ARCHIVO: dashboard.php ---
// Este es el perfil de usuario. Aquí puede ver sus alquileres, lo que ha subido, solicitudes y recargar el saldo.

session_start();

// 1. SEGURIDAD BÁSICA
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}
require "conexion/conexion.php";

// 2. RECARGA DE SALDO
// Si el formulario oculto de recarga me envía datos por POST...
if (isset($_POST["recargar_saldo"])) {
    // Cojo lo que haya elegido en los botones de radio (10, 20, 50...)
    $cantidadRadio = isset($_POST["cantidad"]) ? (float)$_POST["cantidad"] : 0;
    // O cojo lo que haya escrito a mano en el campo "Otra cantidad"
    $cantidadCustom = isset($_POST["cantidad_custom"]) ? (float)$_POST["cantidad_custom"] : 0;
    
    // Como solo puede usar uno de los dos métodos a la vez, me quedo con el mayor (el que no sea 0).
    $cantidad = max($cantidadRadio, $cantidadCustom);

    // Si la cantidad es válida (entre 1 céntimo y 10.000€) actualizo la BD.
    if ($cantidad > 0 && $cantidad <= 10000) { 
        $updateSaldo = "UPDATE usuario SET saldo = saldo + $cantidad WHERE usuario = '{$_SESSION["usuario"]}'";
        $_conexion->query($updateSaldo);
        // Recargo la página para que el nuevo saldo salga arriba del todo.
        header("Location: dashboard.php");
        exit();
    }
}

// 3. DATOS DEL PERFIL
// Saco los datos de mi usuario (foto, nombre, saldo total, DNI, etc.)
$consulta = "SELECT * FROM usuario WHERE usuario = '{$_SESSION["usuario"]}'";
$resultado = $_conexion->query($consulta);
$infoUsuario = $resultado->fetch_assoc();
$dni = $infoUsuario["DNI"];

// number_format pone el saldo bonito con 2 decimales (ej: 10.50 en vez de 10.5)
$saldoVisual = number_format($infoUsuario["saldo"] ?? 0, 2);

// 4. MIS ALQUILERES (Cosas que YO he pedido alquilar a otros)
// Hago unos INNER JOIN cruzando la tabla de alquileres con artículos para saber los nombres,
// y con arrendatario para que solo salgan los que he pedido yo.
$consultaAlquileres = "SELECT art.nombre, alq.fecha_alquiler, alq.vencimiento_alquiler, alq.estado, alq.numero_seguimiento, alq.precio_total, alq.id_conversacion 
    FROM alquiler alq
    INNER JOIN articulo art ON alq.id_articulo = art.id_articulo
    INNER JOIN arrendatario arr ON alq.id_arrendatario = arr.id_arrendatario
    WHERE arr.DNI = '$dni' ORDER BY alq.id_alquiler DESC";
$infoAnuncios = $_conexion->query($consultaAlquileres);

// 5. SOLICITUDES RECIBIDAS (Gente que quiere alquilar MIS cosas)
// Cruzo todo otra vez, pero esta vez filtro donde yo soy el dueño (arrendador) 
// y el estado del alquiler sigue siendo 'Pendiente' (tengo que aceptar o rechazar).
$consultaSolicitudes = "SELECT alq.id_alquiler, alq.id_conversacion, art.nombre, alq.fecha_alquiler, alq.vencimiento_alquiler, alq.precio_total, u.usuario as solicitante
    FROM alquiler alq
    INNER JOIN articulo art ON alq.id_articulo = art.id_articulo
    INNER JOIN arrendador a ON art.id_arrendador = a.id_arrendador
    INNER JOIN arrendatario arr ON alq.id_arrendatario = arr.id_arrendatario
    INNER JOIN usuario u ON arr.DNI = u.DNI
    WHERE a.DNI = '$dni' AND alq.estado = 'Pendiente' ORDER BY alq.id_alquiler ASC";
$resSolicitudes = $_conexion->query($consultaSolicitudes);

// 6. MIS ARTÍCULOS EN VENTA (Escaparate propio)
// Solo saco los que he subido yo.
$consultaMisArticulos = "
    SELECT art.id_articulo, art.nombre, art.precio, art.foto 
    FROM articulo art 
    INNER JOIN arrendador a ON art.id_arrendador = a.id_arrendador 
    WHERE a.DNI = '$dni'";
$resMisArticulos = $_conexion->query($consultaMisArticulos);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Perfil | Reusarent</title>
    <?php include 'includes/head.php'; ?>
    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(tabId).classList.remove('hidden');

            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('border-brand', 'text-brand');
                el.classList.add('border-transparent', 'text-gray-500');
            });
            event.currentTarget.classList.remove('border-transparent', 'text-gray-500');
            event.currentTarget.classList.add('border-brand', 'text-brand');
        }

        function handleRadioRecarga(elem) {
            document.getElementById('monto_custom').value = '';
            document.querySelectorAll('.radio-box-recarga').forEach(box => {
                box.classList.remove('border-brand', 'bg-brand/10', 'text-brand');
                box.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-800');
            });
            elem.classList.remove('border-gray-200', 'bg-gray-50', 'text-gray-800');
            elem.classList.add('border-brand', 'bg-brand/10', 'text-brand');
        }

        function handleFocusCustomInput() {
            document.querySelectorAll('input[type=radio][name=cantidad]').forEach(r => r.checked = false);
            document.querySelectorAll('.radio-box-recarga').forEach(box => {
                box.classList.remove('border-brand', 'bg-brand/10', 'text-brand');
                box.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-800');
            });
        }
    </script>
</head>

<body class="bg-gray-50 pb-safe">

    <?php include 'includes/top_nav.php'; ?>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <?php if ($infoUsuario['usuario'] !== 'adminReusa'): ?>
        <!-- Sección de Cabecera del Perfil -->
        <div class="bg-white rounded-3xl p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 mb-6 flex flex-col sm:flex-row items-center sm:items-start gap-6 relative">
            <a href="ajustes.php" class="absolute top-4 right-4 text-gray-400 hover:text-brand bg-gray-50 p-2 rounded-full transition-colors">
                <i class="ph ph-gear text-2xl"></i>
            </a>

            <div class="relative group">
                <?php
                if (!empty($infoUsuario["foto_perfil"])) {
                    $fotoBase64 = base64_encode($infoUsuario["foto_perfil"]);
                    $imagenSrc = "data:image/jpeg;base64," . $fotoBase64;
                } else {
                    $imagenSrc = "img/perfiles/default.png";
                }
                ?>
                <img src="<?php echo $imagenSrc; ?>" class="w-24 h-24 rounded-full object-cover border-4 border-gray-50 shadow-sm">
            </div>

            <div class="flex-1 text-center sm:text-left">
                <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($infoUsuario["usuario"]) ?></h1>
                <div class="flex items-center justify-center sm:justify-start gap-1 text-yellow-400 mt-1 mb-2">
                    <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph ph-star"></i>
                    <span class="text-gray-500 text-sm ml-1">(4 opiniones)</span>
                </div>

                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-4 mt-4">
                    
                    <!-- BOTÓN DE SALDO INTERACTIVO -->
                    <button type="button" onclick="document.getElementById('modal-recarga').classList.remove('hidden')" class="bg-brand/10 text-brand px-4 py-2 rounded-2xl flex items-center gap-2 text-left hover:bg-brand/20 hover:scale-[1.02] active:scale-95 transition-all outline-none">
                        <i class="ph-fill ph-wallet text-xl"></i>
                        <div>
                            <span class="block text-[10px] uppercase font-bold tracking-wider leading-none">Mi Saldo</span>
                            <span class="block text-lg font-bold leading-none mt-1"><?= $saldoVisual ?> €</span>
                        </div>
                        <i class="ph-bold ph-plus text-sm ml-1 opacity-70 bg-brand/20 p-1 rounded-full"></i>
                    </button>

                    <a href="planes.php" class="
                        <?php 
                            if ($infoUsuario['rol'] == 'platino') echo 'bg-gradient-to-r from-gray-900 to-gray-800 text-yellow-400 border-gray-700 shadow-sm';
                            else if ($infoUsuario['rol'] == 'premium') echo 'bg-gradient-to-r from-brand to-teal-500 text-white shadow-sm';
                            else echo 'bg-yellow-50 text-yellow-600 border border-yellow-100';
                        ?> px-4 py-2 rounded-2xl flex items-center gap-2 text-left hover:scale-[1.02] active:scale-95 transition-all outline-none">
                        <i class="ph-fill ph-crown text-xl"></i>
                        <div>
                            <span class="block text-[10px] uppercase font-bold tracking-wider leading-none opacity-80">Plan Actual</span>
                            <span class="block text-sm font-bold leading-none mt-1 capitalize">
                                <?= $infoUsuario['rol'] == 'no-premium' ? 'Básico' : $infoUsuario['rol'] ?>
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($infoUsuario['usuario'] === 'adminReusa'): ?>
            <!-- Panel Exclusivo Administrador -->
            <div class="mt-8 bg-white rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 p-10 text-center">
                <div class="w-20 h-20 bg-red-50 text-red-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                    <i class="ph-fill ph-shield-check text-4xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-3 tracking-tight">Gestor de la Plataforma</h2>
                <p class="text-gray-500 mb-8 max-w-md mx-auto text-lg leading-relaxed">Estás en el modo de Súper Administrador. Tienes acceso completo a la tesorería y a las herramientas de moderación.</p>
                <a href="admin.php" class="bg-gradient-to-r from-red-600 to-red-500 text-white px-8 py-4 rounded-full font-bold shadow-lg shadow-red-500/30 hover:scale-105 transition-all active:scale-95 text-[15px] inline-flex items-center gap-2">
                    <i class="ph-bold ph-key"></i> Entrar al Panel de Control Admin
                </a>
            </div>
        <?php else: ?>
        <!-- Pestañas de Gestión -->
        <div class="bg-white rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 overflow-hidden">

            <!-- Cabeceras de Pestañas -->
            <div class="flex overflow-x-auto hide-scrollbar border-b border-gray-100 px-2 pt-2">
                <button onclick="showTab('tab-mis-articulos')" class="tab-btn px-6 py-4 border-b-2 border-brand text-brand font-semibold text-sm whitespace-nowrap transition-colors">Mis artículos</button>
                <button onclick="showTab('tab-alquilados')" class="tab-btn px-6 py-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap transition-colors">Mis alquileres</button>
                <button onclick="showTab('tab-solicitudes')" class="tab-btn px-6 py-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm whitespace-nowrap transition-colors">Solicitudes Recibidas</button>
            </div>

            <!-- Pestaña 1: Mis Artículos -->
            <div id="tab-mis-articulos" class="tab-content p-4">
                <?php if ($resMisArticulos && $resMisArticulos->num_rows > 0): ?>
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                        <?php while ($art = $resMisArticulos->fetch_assoc()): ?>
                            <a href="info_articulos.php?articulo=<?= urlencode($art['nombre']) ?>" class="bg-white rounded-2xl border border-gray-100 overflow-hidden relative group hover:shadow-md transition-shadow">
                                <div class="aspect-square bg-gray-100 relative">
                                    <?php if(!empty($art['foto'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($art['foto']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <i class="ph ph-image text-3xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3">
                                    <h4 class="font-bold text-gray-900 leading-tight mb-0.5 truncate"><?= htmlspecialchars($art['nombre']) ?></h4>
                                    <p class="text-brand font-bold text-sm"><?= htmlspecialchars($art['precio']) ?> €/día</p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center text-center py-12">
                        <div class="w-16 h-16 bg-brand/10 rounded-full flex items-center justify-center mb-4">
                            <i class="ph ph-tag text-3xl text-brand"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">No tienes artículos en venta</h3>
                        <p class="text-gray-500 text-sm max-w-sm mb-6">Sube artículos que ya no uses y empieza a ganar dinero de manera fácil y rápida.</p>
                        <a href="subir_articulo.php" class="bg-brand text-white px-6 py-2.5 rounded-full font-semibold hover:bg-brand-dark transition-all">Subir artículo</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pestaña 2: Mis Alquileres (Comprador) -->
            <div id="tab-alquilados" class="tab-content hidden divide-y divide-gray-100">
                <?php if ($infoAnuncios && $infoAnuncios->num_rows > 0): ?>
                    <?php while ($fila = $infoAnuncios->fetch_assoc()): 
                        $colorBadge = "bg-gray-100 text-gray-600";
                        if($fila['estado'] == 'Pendiente') $colorBadge = "bg-yellow-100 text-yellow-700";
                        if($fila['estado'] == 'Aceptado') $colorBadge = "bg-green-100 text-green-700";
                        if($fila['estado'] == 'Rechazado') $colorBadge = "bg-red-100 text-red-700";
                    ?>
                        <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center gap-4 hover:bg-gray-50 transition-colors">
                            <div class="w-16 h-16 bg-gray-200 rounded-xl flex-shrink-0 flex items-center justify-center">
                                <i class="ph ph-package text-2xl text-gray-400"></i>
                            </div>
                            <div class="flex-1 w-full">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="text-[10px] font-bold tracking-widest text-gray-400 uppercase"><?= $fila['numero_seguimiento'] ?></span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider <?= $colorBadge ?>"><?= $fila['estado'] ?></span>
                                </div>
                                <h4 class="font-bold text-gray-900"><?= htmlspecialchars($fila["nombre"]) ?> <span class="font-normal text-brand ml-1"><?= $fila['precio_total'] ?>€</span></h4>
                                <div class="text-sm text-gray-500 mt-1 flex flex-col sm:flex-row sm:gap-4">
                                    <span><i class="ph ph-calendar-blank inline mr-1"></i><?= $fila["fecha_alquiler"] ?></span>
                                    <span><i class="ph ph-calendar-check inline mr-1"></i><?= $fila["vencimiento_alquiler"] ?></span>
                                </div>
                            </div>
                            <?php if(!empty($fila['id_conversacion'])): ?>
                            <a href="buzon.php?c=<?= $fila['id_conversacion'] ?>" class="text-brand bg-brand/10 p-2 rounded-full hover:bg-brand hover:text-white transition-colors" title="Ver Alquiler en el Chat">
                                <i class="ph ph-chat-circle-dots text-xl"></i>
                            </a>
                            <?php else: ?>
                            <a href="info_articulos.php?articulo=<?= urlencode($fila["nombre"]) ?>" class="text-brand bg-brand/10 p-2 rounded-full hover:bg-brand hover:text-white transition-colors">
                                <i class="ph ph-arrow-right text-xl"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-12 flex flex-col items-center justify-center text-center">
                        <i class="ph ph-calendar-slash text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 font-medium max-w-xs">No has alquilado ni comprado ningún artículo aún.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pestaña 3: Solicitudes Recibidas (Vendedor) -->
            <div id="tab-solicitudes" class="tab-content hidden divide-y divide-gray-100">
                <?php if ($resSolicitudes && $resSolicitudes->num_rows > 0): ?>
                    <?php while ($sol = $resSolicitudes->fetch_assoc()): ?>
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center gap-4 bg-yellow-50/50">
                            <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="ph-fill ph-bell-ringing text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-600 mb-0.5"><strong><?= htmlspecialchars($sol['solicitante']) ?></strong> quiere alquilar:</p>
                                <h4 class="font-bold text-gray-900 leading-tight"><?= htmlspecialchars($sol["nombre"]) ?></h4>
                                <p class="text-xs font-semibold text-brand mt-1"><?= $sol['fecha_alquiler'] ?> al <?= $sol['vencimiento_alquiler'] ?> • <span class="text-gray-800"><?= $sol['precio_total'] ?>€ totales</span></p>
                            </div>
                            <div class="flex gap-2 w-full sm:w-auto mt-2 sm:mt-0 flex-wrap">
                                <?php if(!empty($sol['id_conversacion'])): ?>
                                <a href="buzon.php?c=<?= $sol['id_conversacion'] ?>" class="w-full sm:w-auto bg-gray-100 text-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm hover:bg-gray-200 active:scale-95 transition-all text-sm flex items-center justify-center gap-2">
                                    <i class="ph-fill ph-chat-circle-dots"></i> Chat
                                </a>
                                <?php endif; ?>
                                <form action="accion_alquiler.php" method="POST" class="flex-1 sm:flex-none">
                                    <input type="hidden" name="id_alquiler" value="<?= $sol['id_alquiler'] ?>">
                                    <input type="hidden" name="accion" value="Aceptar">
                                    <button type="submit" class="w-full bg-brand text-white px-4 py-2 rounded-xl font-bold shadow-sm shadow-brand/30 hover:bg-brand-dark active:scale-95 transition-all text-sm">
                                        Aceptar
                                    </button>
                                </form>
                                <form action="accion_alquiler.php" method="POST" class="flex-1 sm:flex-none">
                                    <input type="hidden" name="id_alquiler" value="<?= $sol['id_alquiler'] ?>">
                                    <input type="hidden" name="accion" value="Rechazar">
                                    <button type="submit" class="w-full bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm hover:bg-gray-50 active:scale-95 transition-all text-sm">
                                        Rechazar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-12 flex flex-col items-center justify-center text-center">
                        <i class="ph ph-tray text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 font-medium max-w-xs">No tienes ninguna solicitud de alquiler pendiente.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Botón de Cerrar Sesión para móvil -->
        <div class="mt-8 mb-4 flex justify-center">
            <a href="conexion/logout.php" class="text-red-500 font-medium flex items-center gap-2 hover:bg-red-50 px-4 py-2 rounded-full transition-colors">
                <i class="ph ph-sign-out"></i> Cerrar sesión
            </a>
        </div>

    </main>

    <!-- Modal Recarga de Saldo -->
    <div id="modal-recarga" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" onclick="document.getElementById('modal-recarga').classList.add('hidden')"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] max-w-sm bg-white rounded-[2rem] p-6 shadow-2xl overflow-hidden animate-[pulse_0.2s_ease-out_1]">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-brand/10 text-brand rounded-full flex items-center justify-center">
                    <i class="ph-fill ph-wallet text-2xl"></i>
                </div>
                <button onclick="document.getElementById('modal-recarga').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-800 rounded-full hover:bg-gray-100 transition-colors">
                    <i class="ph-bold ph-x text-lg"></i>
                </button>
            </div>
            
            <h3 class="text-xl font-bold text-gray-900 mb-1">Recargar Saldo</h3>
            <p class="text-sm text-gray-500 mb-6">Añade fondos a tu cartera de Reusarent para futuros alquileres de manera instantánea.</p>
            
            <script>
            function validateAndSubmit(e) {
                const radio = document.querySelector('input[name="cantidad"]:checked');
                const custom = document.getElementById('monto_custom').value;
                if(!radio && !custom) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Atención', text: 'Por favor, selecciona un monto numérico o escribe uno propio.', confirmButtonColor: '#09b6a2' });
                }
            }
            </script>
            <form action="" method="POST" id="form-recarga" onsubmit="validateAndSubmit(event)">
                <input type="hidden" name="recargar_saldo" value="1">
                
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <label class="radio-box-recarga block text-center bg-gray-50 border border-gray-200 rounded-2xl py-3.5 cursor-pointer transition-all font-bold text-gray-800" onclick="handleRadioRecarga(this)">
                        <input type="radio" name="cantidad" value="5" class="hidden">
                        + 5€
                    </label>
                    <label class="radio-box-recarga block text-center bg-gray-50 border border-gray-200 rounded-2xl py-3.5 cursor-pointer transition-all font-bold text-gray-800" onclick="handleRadioRecarga(this)">
                        <input type="radio" name="cantidad" value="10" class="hidden">
                        + 10€
                    </label>
                    <label class="radio-box-recarga block text-center bg-gray-50 border border-gray-200 rounded-2xl py-3.5 cursor-pointer transition-all font-bold text-gray-800" onclick="handleRadioRecarga(this)">
                        <input type="radio" name="cantidad" value="50" class="hidden">
                        + 50€
                    </label>
                </div>
                
                <div class="mb-6 relative">
                    <input type="number" id="monto_custom" name="cantidad_custom" step="0.50" min="0.50" max="5000" placeholder="Otro importe personalizado..." 
                           class="w-full bg-gray-50 border border-gray-200 rounded-2xl py-3.5 pl-4 pr-8 outline-none focus:ring-2 focus:ring-brand/30 transition-all text-sm font-semibold text-gray-800"
                           onfocus="handleFocusCustomInput()">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">€</span>
                </div>
                
                <button type="submit" class="w-full bg-gray-900 text-white py-4 rounded-full font-bold hover:bg-black active:scale-95 transition-all shadow-md flex items-center justify-center gap-2">
                    <i class="ph-bold ph-plus"></i> Procesar recarga
                </button>
                <div class="text-center mt-3 flex items-center justify-center gap-1.5 opacity-70">
                    <i class="ph-fill ph-lock-key text-gray-400 text-xs"></i>
                    <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Transacción simulada segura</p>
                </div>
            </form>
        </div>
    </div>

    <!-- Espacio inferior para la navegación móvil -->
    <br class="hidden sm:block"><br class="hidden sm:block">
    <?php include 'includes/bottom_nav.php'; ?>

</body>
</html>