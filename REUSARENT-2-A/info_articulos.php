<?php
// --- ARCHIVO: info_articulos.php ---
// Esta página muestra todos los detalles de un artículo cuando pinchas en él desde el index.

session_start();

// 1. SEGURIDAD: COMPROBAR SESIÓN Y PARÁMETROS
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}

// Si alguien intenta entrar a esta página sin pasarle por la URL el nombre del artículo (ej: info_articulos.php?articulo=Bici), lo echo.
if (!isset($_GET["articulo"])) {
    header("Location: index.php");
    exit;
}

require "conexion/conexion.php";

// Escapo el nombre del artículo que viene por la URL para evitar ataques.
$nombreArticulo = $_conexion->real_escape_string($_GET["articulo"]);

// 2. SACAR TODA LA INFO DEL ARTÍCULO PRINCIPAL
$consulta = "SELECT * FROM articulo WHERE nombre = '$nombreArticulo'";
$resultado = $_conexion->query($consulta);
$infoArticulo = $resultado->fetch_assoc();

// Si el artículo no existe (por ejemplo, porque lo han borrado justo ahora o la URL está mal), lo devuelvo al index.
if (!$infoArticulo) {
    header("Location: index.php");
    exit;
}

// 3. SACAR EL NOMBRE DE LA CATEGORÍA
// Como en la tabla 'articulo' solo guardo el id_categoria (un número), tengo que ir a la tabla 'categoria' a por el nombre (ej: "Vehículos").
$consulta = "SELECT nombre FROM categoria WHERE id_categoria = '{$infoArticulo["id_categoria"]}'";
$resultado = $_conexion->query($consulta);
$categoriaArticulo = $resultado->fetch_assoc()["nombre"] ?? "Sin categoría";

// 4. COMPROBAR SI ESTE ARTÍCULO ESTÁ EN LOS FAVORITOS DEL USUARIO
$esFavorito = false;
$usuarioLogeado = $_SESSION["usuario"];

// Primero necesito saber mi DNI, porque los favoritos se guardan por DNI, no por nombre de usuario.
$resMio = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$usuarioLogeado'");
$miDNI = null;
if ($resMio && $resMio->num_rows > 0) {
    $miDNI = $resMio->fetch_assoc()['DNI'];
}

// Ahora miro si existe una fila en la tabla 'favorito' con mi DNI y el ID de este artículo.
$resFa = $_conexion->query("SELECT f.id_articulo FROM favorito f INNER JOIN usuario u ON f.DNI = u.DNI WHERE u.usuario = '$usuarioLogeado' AND f.id_articulo = '{$infoArticulo['id_articulo']}'");
if ($resFa && $resFa->num_rows > 0) {
    $esFavorito = true; // Si existe, pongo esto a true para luego pintar el corazón de rojo.
}

// 5. PREPARAR LAS FOTOS (CAROUSEL)
// Voy a meter todas las fotos del artículo en un array para luego mostrarlas pasándolas con el dedo/ratón.
$todasLasFotos = [];

// Primero meto la foto principal (la que está en la tabla 'articulo').
if (!empty($infoArticulo["foto"])) {
    // La foto está guardada como BLOB (un chorro de bytes) en la BD.
    // La convierto a Base64 para poder ponerla directamente en el atributo 'src' de la etiqueta <img> en HTML.
    $imgConvertida = base64_encode($infoArticulo["foto"]);
    $todasLasFotos[] = "data:image/jpeg;base64," . $imgConvertida;
}

// Luego voy a buscar si hay fotos extra en la tabla 'fotos_articulo' y las meto también en el array.
$idArtP = $infoArticulo['id_articulo'];
$consultaFotosExtra = "SELECT foto FROM fotos_articulo WHERE id_articulo = '$idArtP'";
$resEx = $_conexion->query($consultaFotosExtra);
if ($resEx) {
    while ($row = $resEx->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $todasLasFotos[] = "data:image/jpeg;base64," . base64_encode($row['foto']);
        }
    }
}

// 6. OBTENER LOS DATOS DEL VENDEDOR (PROPIETARIO)
// Necesito saber quién es el dueño para que el usuario pueda mandarle un mensaje o ver que es suyo.
$idArrendador = $infoArticulo["id_arrendador"];
$dniVendedor = null;
$nombreVendedor = "Vendedor";
// Hago un JOIN para sacar el nombre de usuario y foto del arrendador a partir de su ID.
$resVen = $_conexion->query("SELECT u.DNI, u.usuario, u.foto_perfil FROM usuario u INNER JOIN arrendador a ON u.DNI = a.DNI WHERE a.id_arrendador = '$idArrendador'");
$fotoVendedor = null;
if ($resVen && $resVen->num_rows > 0) {
    $rowVen = $resVen->fetch_assoc();
    $dniVendedor = $rowVen['DNI'];
    $nombreVendedor = $rowVen['usuario'];
    if (!empty($rowVen['foto_perfil'])) {
        $fotoVendedor = "data:image/jpeg;base64," . base64_encode($rowVen['foto_perfil']);
    }
}

// 7. OBTENER FECHAS OCUPADAS (Para el calendario Flatpickr)
// Saco las fechas en las que este artículo ya está alquilado (cuyo estado no sea 'Rechazado').
$fechasOcupadas = [];
$resAlq = $_conexion->query("SELECT fecha_alquiler, vencimiento_alquiler FROM alquiler WHERE id_articulo = '{$infoArticulo['id_articulo']}' AND estado != 'Rechazado'");
if ($resAlq) {
    while($alq = $resAlq->fetch_assoc()) {
        // Flatpickr necesita un formato específico de "from" y "to" para bloquear rangos.
        $fechasOcupadas[] = [
            "from" => $alq['fecha_alquiler'],
            "to" => $alq['vencimiento_alquiler']
        ];
    }
}
// Convierto mi array de PHP a JSON para podérselo pasar a la librería de JavaScript del calendario más abajo.
$fechasOcupadasJSON = json_encode($fechasOcupadas);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title><?= htmlspecialchars($infoArticulo['nombre']) ?> | Reusarent</title>
    <?php include 'includes/head.php'; ?>
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Flatpickr Tema personalizado para coincidir un poco más con el blanco/gris -->
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/light.css">
    
    <script>
        // Funcionalidad Carousel simple
        let currentImageIndex = 0;
        function switchImage(index) {
            const track = document.getElementById('carousel-track');
            const items = document.querySelectorAll('.carousel-item');
            if (!items[index]) return;
            track.style.transform = `translateX(-${index * 100}%)`;
            currentImageIndex = index;

            document.querySelectorAll('.carousel-dot').forEach((dot, i) => {
                dot.classList.toggle('bg-brand', i === index);
                dot.classList.toggle('bg-gray-300', i !== index);
                dot.classList.toggle('w-4', i === index);
                dot.classList.toggle('w-2', i !== index);
            });
        }
    </script>
</head>

<body class="bg-gray-50 pb-safe md:pb-8">

    <?php include 'includes/top_nav.php'; ?>

    <div class="lg:hidden fixed top-4 left-4 z-50">
        <a href="index.php"
            class="w-10 h-10 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-gray-800 shadow-sm border border-gray-100 hover:bg-gray-100 active:scale-95 transition-all">
            <i class="ph ph-arrow-left text-xl"></i>
        </a>
    </div>

    <main class="max-w-6xl mx-auto px-0 sm:px-6 lg:px-8 py-0 sm:py-6 relative">
        <?php if (isset($_GET['error_borrado'])): ?>
            <div class="mx-4 sm:mx-0 bg-red-50 border border-red-200 text-red-600 px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3 mt-4 sm:mt-0">
                <i class="ph-fill ph-warning-circle text-2xl mt-0.5"></i>
                <div>
                    <h4 class="font-bold">No se pudo borrar</h4>
                    <p class="text-sm mt-0.5">El artículo tiene solicitudes de alquiler Pendientes o Aceptadas. Por favor, recházalas o espera a que finalicen antes de eliminarlo.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['exito_reporte'])): ?>
            <div class="mx-4 sm:mx-0 bg-green-50 border border-green-200 text-green-700 px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3 mt-4 sm:mt-0">
                <i class="ph-fill ph-check-circle text-2xl mt-0.5"></i>
                <div>
                    <h4 class="font-bold">Reporte enviado</h4>
                    <p class="text-sm mt-0.5">Gracias por ayudarnos a mantener segura la comunidad de Reusarent. El administrador revisará tu reporte pronto.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['exito_edicion'])): ?>
            <div class="mx-4 sm:mx-0 bg-[#e8f7f5] border border-brand/20 text-[#068f80] px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3 mt-4 sm:mt-0">
                <i class="ph-fill ph-check-circle text-2xl mt-0.5"></i>
                <div>
                    <h4 class="font-bold">¡Actualizado!</h4>
                    <p class="text-sm mt-0.5">Los detalles del artículo han sido guardados correctamente.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-0 sm:gap-8 items-start">

            <!-- SECCIÓN DEL CARRUSEL DE IMÁGENES -->
            <div
                class="bg-white sm:rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border-b sm:border border-gray-100 overflow-hidden relative">
                <div
                    class="aspect-square lg:aspect-[4/3] w-full bg-gray-100 relative group flex items-center justify-center overflow-hidden">

                    <?php if (count($todasLasFotos) > 0): ?>
                        <div id="carousel-track" class="flex w-full h-full transition-transform duration-500 ease-out"
                            style="transform: translateX(0%);">
                            <?php foreach ($todasLasFotos as $img): ?>
                                <div class="carousel-item w-full h-full flex-shrink-0">
                                    <img src="<?= $img ?>" class="w-full h-full object-cover">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Puntos indicadores -->
                        <?php if (count($todasLasFotos) > 1): ?>
                            <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-1.5 z-10">
                                <?php for ($i = 0; $i < count($todasLasFotos); $i++): ?>
                                    <button onclick="switchImage(<?= $i ?>)"
                                        class="carousel-dot h-2 rounded-full transition-all duration-300 <?= $i === 0 ? 'bg-brand w-4' : 'bg-white/70 backdrop-blur w-2' ?> shadow"></button>
                                <?php endfor; ?>
                            </div>

                            <!-- Controles de flecha para PC -->
                            <button onclick="switchImage(Math.max(0, currentImageIndex - 1))"
                                class="hidden sm:flex absolute left-4 w-10 h-10 bg-white/80 hover:bg-white backdrop-blur rounded-full items-center justify-center shadow-lg text-gray-700 active:scale-95 transition-all opacity-0 group-hover:opacity-100">
                                <i class="ph-bold ph-caret-left"></i>
                            </button>
                            <button onclick="switchImage(Math.min(<?= count($todasLasFotos) - 1 ?>, currentImageIndex + 1))"
                                class="hidden sm:flex absolute right-4 w-10 h-10 bg-white/80 hover:bg-white backdrop-blur rounded-full items-center justify-center shadow-lg text-gray-700 active:scale-95 transition-all opacity-0 group-hover:opacity-100">
                                <i class="ph-bold ph-caret-right"></i>
                            </button>
                        <?php endif; ?>

                    <?php else: ?>
                        <i class="ph ph-image text-6xl text-gray-300"></i>
                    <?php endif; ?>

                    <!-- Botón de Favorito (Corazón) -->
                    <button onclick="toggleFavorito(event, this, <?= (int) $infoArticulo['id_articulo'] ?>)"
                        class="absolute top-4 right-4 lg:top-6 lg:right-6 w-12 h-12 <?= $esFavorito ? 'text-red-500' : 'text-gray-400' ?> bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center hover:text-red-500 shadow-sm transition-all hover:scale-105 active:scale-95 z-20 heart-btn">
                        <i class="<?= $esFavorito ? 'ph-fill' : 'ph' ?> ph-heart text-2xl mt-0.5 transition-colors"></i>
                    </button>
                </div>
            </div>

            <!-- SECCIÓN DE DETALLES Y ALQUILER -->
            <div class="p-6 sm:p-0">
                <div
                    class="bg-white sm:rounded-3xl sm:p-8 sm:shadow-[0_2px_12px_rgba(0,0,0,0.03)] sm:border border-gray-100 relative lg:sticky lg:top-24">

                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="bg-brand/10 text-brand text-xs uppercase font-bold px-2 py-1 rounded-md">
                                <?= htmlspecialchars($categoriaArticulo) ?>
                            </span>
                            <span class="text-gray-400 text-sm flex items-center"><i
                                    class="ph-fill ph-check-circle mr-1 text-brand"></i> Verificado</span>
                        </div>

                        <h1 class="text-[1.75rem] font-bold text-gray-900 leading-tight tracking-tight mb-2">
                            <?= htmlspecialchars($infoArticulo['nombre']) ?>
                        </h1>

                        <div class="text-3xl font-bold text-brand flex items-baseline gap-1">
                            <?= htmlspecialchars($infoArticulo["precio"]) ?> <span class="text-xl">€</span><span
                                class="text-base text-gray-500 font-medium">/día</span>
                        </div>
                        
                        <?php if (!empty($infoArticulo["descripcion"])): ?>
                            <div class="mt-5 text-gray-600 text-[15px] leading-relaxed whitespace-pre-line">
                                <?= htmlspecialchars($infoArticulo["descripcion"]) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr class="border-gray-100 mb-6">

                    <!-- Vendedor & Chat -->
                    <div class="mb-6">
                        <div
                            class="flex items-center justify-between p-4 bg-gray-50/50 border border-gray-100 rounded-2xl">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($fotoVendedor)): ?>
                                    <img src="<?= $fotoVendedor ?>" alt="Foto del vendedor" class="w-12 h-12 rounded-full object-cover border border-gray-200 shadow-sm">
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                        <i class="ph ph-user text-gray-400 text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($nombreVendedor) ?>
                                    </p>
                                    <div class="flex text-yellow-400 text-xs mt-0.5">
                                        <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i
                                            class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i
                                            class="ph-fill ph-star"></i>
                                    </div>
                                </div>
                            </div>

                            <?php if ($dniVendedor && $dniVendedor !== $miDNI): ?>
                                <!-- Botón Iniciar Chat: Transformado en enlace directo -->
                                <a href="buzon.php?nuevo_chat_articulo=<?= urlencode($infoArticulo['id_articulo']) ?>&vendedor=<?= urlencode($dniVendedor) ?>"
                                    class="bg-white border border-gray-200 text-gray-800 hover:text-brand hover:border-brand/30 px-4 py-2.5 rounded-xl font-bold text-sm shadow-sm transition-all active:scale-95 flex items-center gap-2">
                                    <i class="ph-fill ph-chat-circle-dots text-lg text-brand"></i> Contactar
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>

                    <?php if ($dniVendedor === $miDNI): ?>
                        <!-- Panel de Gestión de Propietario -->
                        <div class="bg-brand/5 rounded-2xl p-5 border border-brand/20 mb-5">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <i class="ph-fill ph-gear-six text-brand text-xl"></i> Gestionar Anuncio
                            </h3>
                            <div class="flex flex-col gap-3">
                                <a href="editar_articulo.php?id=<?= $infoArticulo['id_articulo'] ?>" class="w-full bg-brand text-white py-3.5 rounded-xl font-bold shadow-lg shadow-brand/30 hover:bg-brand-dark transition-all text-[15px] flex justify-center items-center gap-2">
                                    <i class="ph-bold ph-pencil-simple text-lg"></i> Editar Anuncio
                                </a>
                                <form id="formBorrarArticulo" action="accion_borrar_articulo.php" method="POST">
                                    <input type="hidden" name="id_articulo" value="<?= $infoArticulo['id_articulo'] ?>">
                                    <button type="button" onclick="confirmarBorrado()" class="w-full bg-white border border-red-200 text-red-500 hover:bg-red-50 py-3.5 rounded-xl font-bold transition-all text-[15px] flex justify-center items-center gap-2">
                                        <i class="ph-bold ph-trash text-lg"></i> Eliminar Artículo
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                    
                    <?php if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"] !== 'adminReusa'): ?>
                    <!-- Formulario de Reserva -->
                    <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 mb-5">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="ph-fill ph-calendar-check text-brand text-xl"></i> Reservar Fechas
                        </h3>

                        <div id="mensajeError"></div>

                        <!-- Añadido id="formReserva" para encontrarlo bien con JS -->
                        <form id="formReserva" action="alquilar.php" method="POST" class="space-y-4">
                            <input type="hidden" name="id_articulo" value="<?= $infoArticulo['id_articulo']; ?>">

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5 ml-1">Inicio</label>
                                    <div class="relative">
                                        <input type="text" name="fecha_inicio" id="fecha_inicio" placeholder="Selecciona..."
                                            class="w-full bg-white border border-gray-200 rounded-xl py-3 pl-3 pr-1 text-gray-800 text-sm focus:ring-2 focus:ring-brand/20 outline-none"
                                            required>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5 ml-1">Devolución</label>
                                    <div class="relative">
                                        <input type="text" name="fecha_fin" id="fecha_fin" placeholder="Selecciona..."
                                            class="w-full bg-white border border-gray-200 rounded-xl py-3 pl-3 pr-1 text-gray-800 text-sm focus:ring-2 focus:ring-brand/20 outline-none"
                                            required>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="presupuesto-box" class="hidden mt-4 p-4 bg-brand/5 border border-brand/20 rounded-xl text-sm transition-all">
                                <div class="flex justify-between mb-1.5 text-gray-600"><span id="txt-dias" class="font-medium">0 días x 0€</span><span id="txt-base" class="font-bold">0.00€</span></div>
                                <div class="flex justify-between mb-3 text-gray-500 text-xs"><span>Comisión de plataforma (10%)</span><span id="txt-comision" class="font-bold">0.00€</span></div>
                                <hr class="border-brand/20 mb-3">
                                <div class="flex justify-between font-bold text-gray-900 items-end"><span>Total a pagar</span><span id="txt-total" class="text-brand text-xl leading-none">0.00€</span></div>
                            </div>

                            <button type="submit"
                                class="w-full bg-brand text-white py-4 rounded-xl font-bold shadow-lg shadow-brand/30 hover:bg-brand-dark transition-all active:scale-95 text-[15px] flex items-center justify-center gap-2 mt-2">
                                Solicitar alquiler <i class="ph-bold ph-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- Vista de Administrador -->
                    <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100 mb-5 text-center">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="ph-fill ph-shield-check text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Modo Administrador</h3>
                        <p class="text-sm text-gray-600">Puedes ver este artículo y contactar al propietario, pero no puedes alquilarlo.</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Botón Reportar Anuncio -->
                    <?php if (isset($_SESSION['usuario'])): ?>
                    <div class="mt-4 flex justify-center">
                        <button type="button" onclick="abrirReporte(<?= $infoArticulo['id_articulo'] ?>)" class="text-xs text-red-500 hover:text-red-600 font-medium flex items-center gap-1 transition-colors">
                            <i class="ph-fill ph-flag"></i> Reportar este anuncio
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>

    <br class="hidden lg:block">

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <!-- LIBRERIA SWEETALERT2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmarBorrado() {
            Swal.fire({
                title: '¿Eliminar artículo?',
                text: "No podrás recuperar este anuncio una vez borrado.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'rounded-3xl',
                    confirmButton: 'rounded-xl font-bold px-6 py-3',
                    cancelButton: 'rounded-xl font-bold px-6 py-3'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formBorrarArticulo').submit();
                }
            })
        }
        
        function abrirReporte(idArticulo) {
            Swal.fire({
                title: 'Reportar Anuncio',
                html: `
                    <form id="formReporte" action="accion_reportar.php" method="POST" class="text-left mt-2">
                        <input type="hidden" name="id_articulo" value="${idArticulo}">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Motivo</label>
                            <select name="motivo" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-2 px-3 outline-none" required>
                                <option value="">Selecciona un motivo...</option>
                                <option value="Contenido ofensivo">Contenido ofensivo</option>
                                <option value="Falso / Estafa">Falso / Estafa</option>
                                <option value="Artículo prohibido">Artículo prohibido</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Descripción (Opcional)</label>
                            <textarea name="descripcion" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-2 px-3 outline-none resize-none" placeholder="Danos más detalles..."></textarea>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Enviar Reporte',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'rounded-3xl',
                    confirmButton: 'rounded-xl font-bold px-5 py-2.5',
                    cancelButton: 'rounded-xl font-bold px-5 py-2.5'
                },
                preConfirm: () => {
                    const form = document.getElementById('formReporte');
                    if (!form.motivo.value) {
                        Swal.showValidationMessage('Debes seleccionar un motivo');
                        return false;
                    }
                    form.submit();
                }
            });
        }
        
        document.addEventListener("DOMContentLoaded", function () {
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaFin = document.getElementById('fecha_fin');

            const precioDiario = <?= (float)$infoArticulo["precio"] ?>;
            const cajaPresupuesto = document.getElementById('presupuesto-box');
            const txtDias = document.getElementById('txt-dias');
            const txtBase = document.getElementById('txt-base');
            const txtComision = document.getElementById('txt-comision');
            const txtTotal = document.getElementById('txt-total');

            // Obtener fechas ocupadas desde PHP
            const fechasOcupadas = <?= $fechasOcupadasJSON ?>;

            function isRangoValido(inicioStr, finStr) {
                const start = new Date(inicioStr).setHours(0,0,0,0);
                const end = new Date(finStr).setHours(0,0,0,0);
                
                for (let i = 0; i < fechasOcupadas.length; i++) {
                    const from = new Date(fechasOcupadas[i].from).setHours(0,0,0,0);
                    const to = new Date(fechasOcupadas[i].to).setHours(0,0,0,0);
                    
                    if (from <= end && to >= start) {
                        return false;
                    }
                }
                return true;
            }

            function calcularPresupuesto() {
                const contenedor = document.getElementById("mensajeError");
                contenedor.innerHTML = "";

                if (fechaInicio.value && fechaFin.value) {
                    const d1 = new Date(fechaInicio.value);
                    const d2 = new Date(fechaFin.value);
                    if (d2 >= d1) {
                        
                        if (!isRangoValido(fechaInicio.value, fechaFin.value)) {
                             contenedor.innerHTML = `
                                <div class="bg-red-50 border border-red-100 text-red-600 px-3 py-2 rounded-xl mb-4 flex items-center text-sm font-medium gap-2">
                                    <i class="ph-fill ph-warning-circle text-lg"></i> Hay fechas reservadas en medio de tu selección.
                                </div>
                             `;
                             cajaPresupuesto.classList.add('hidden');
                             return;
                        }
                        const diffTime = Math.abs(d2 - d1);
                        let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        if (diffDays === 0) diffDays = 1; // Mínimo 1 día de alquiler

                        const base = diffDays * precioDiario;
                        const comision = base * 0.10;
                        const total = base + comision;

                        txtDias.textContent = diffDays + " día(s) x " + precioDiario.toFixed(2) + "€";
                        txtBase.textContent = base.toFixed(2) + "€";
                        txtComision.textContent = comision.toFixed(2) + "€";
                        txtTotal.textContent = total.toFixed(2) + "€";
                        cajaPresupuesto.classList.remove('hidden');
                    } else {
                        cajaPresupuesto.classList.add('hidden');
                    }
                } else {
                    cajaPresupuesto.classList.add('hidden');
                }
            }

            const configFlatpickr = {
                locale: "es",
                minDate: "today",
                disable: fechasOcupadas,
                dateFormat: "Y-m-d",
                disableMobile: true, // Fuerza UI personalizada incluso en móviles para ver bloqueos grises
                onChange: function(selectedDates, dateStr, instance) {
                    if (instance.element.id === 'fecha_inicio') {
                        fpFin.set('minDate', dateStr || "today");
                    }
                    calcularPresupuesto();
                }
            };

            const fpInicio = flatpickr("#fecha_inicio", configFlatpickr);
            const fpFin = flatpickr("#fecha_fin", configFlatpickr);

            // Seleccionar el form por su ID directamente
            const reservaForm = document.getElementById("formReserva");

            if (reservaForm) {
                reservaForm.addEventListener("submit", function (e) {
                    const inicio = new Date(fechaInicio.value);
                    const fin = new Date(fechaFin.value);
                    const contenedor = document.getElementById("mensajeError");

                    contenedor.innerHTML = "";

                    if (fin < inicio) {
                        e.preventDefault();
                        contenedor.innerHTML = `
                        <div class="bg-red-50 border border-red-100 text-red-600 px-3 py-2 rounded-xl mb-4 flex items-center text-sm font-medium gap-2">
                            <i class="ph-fill ph-warning-circle text-lg"></i> La fecha de devolución no puede ser anterior.
                        </div>
                        `;
                        return;
                    }
                    
                    if (!isRangoValido(fechaInicio.value, fechaFin.value)) {
                        e.preventDefault();
                        contenedor.innerHTML = `
                            <div class="bg-red-50 border border-red-100 text-red-600 px-3 py-2 rounded-xl mb-4 flex items-center text-sm font-medium gap-2">
                                <i class="ph-fill ph-warning-circle text-lg"></i> Hay fechas reservadas en medio de tu selección.
                            </div>
                        `;
                    }
                });
            }
        });

        async function toggleFavorito(event, btn, idArticulo) {
            event.preventDefault();
            event.stopPropagation();

            const icon = btn.querySelector('i');
            const isFav = btn.classList.contains('text-red-500');

            if (isFav) {
                btn.classList.remove('text-red-500');
                btn.classList.add('text-gray-400');
                icon.classList.remove('ph-fill');
                icon.classList.add('ph');
            } else {
                btn.classList.remove('text-gray-400');
                btn.classList.add('text-red-500');
                icon.classList.remove('ph');
                icon.classList.add('ph-fill');
            }

            try {
                await fetch('toggle_favorito.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_articulo: idArticulo })
                });
            } catch (e) {
                console.error("Fetch error:", e);
            }
        }
    </script>

    <?php include 'includes/bottom_nav.php'; ?>

</body>

</html>