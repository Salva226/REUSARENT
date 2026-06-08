<?php
// --- ARCHIVO: admin.php ---
// El Panel de Control que es exclusivo para el administrador. 
// Aquí puede ver cuánto dinero ha ganado la plataforma y moderar los artículos reportados por la gente.

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD: Solo puede entrar el usuario llamado "adminReusa"
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"] !== "adminReusa") {
    // Si intenta entrar alguien que no es el admin, lo mandamos al inicio.
    header("location: index.php");
    exit();
}

// 2. MÉTICA FINANCIERA: Recaudación por Comisiones (10% de alquileres)
// Sumo todos los "pedacitos" que nos hemos llevado de cada alquiler que haya sido 'Aceptado'.
$queryTotal = "SELECT SUM(comision_plataforma) as total_comisiones FROM alquiler WHERE estado = 'Aceptado'";
$resTotal = $_conexion->query($queryTotal);
$totalRecaudado = 0.00;
if ($resTotal && $resTotal->num_rows > 0) {
    $totalRecaudado = $resTotal->fetch_assoc()['total_comisiones'] ?? 0;
}

// 3. MÉTICA FINANCIERA: Recaudación por Suscripciones
// Las suscripciones Premium y Platino van directas al saldo del usuario 'adminReusa'.
$querySuscripciones = "SELECT saldo FROM usuario WHERE usuario = 'adminReusa'";
$resSusc = $_conexion->query($querySuscripciones);
$totalSuscripciones = 0.00;
if ($resSusc && $resSusc->num_rows > 0) {
    $totalSuscripciones = $resSusc->fetch_assoc()['saldo'] ?? 0;
}

// 4. HISTORIAL DE TRANSACCIONES
// Cruzo un montón de tablas para enseñar de un vistazo qué se ha alquilado, quién a quién, y cuánto nos llevamos.
$queryHistorial = "
    SELECT alq.numero_seguimiento, alq.fecha_alquiler, alq.precio_total, alq.comision_plataforma, art.nombre,
           v.usuario as vendedor, c.usuario as comprador
    FROM alquiler alq
    INNER JOIN articulo art ON alq.id_articulo = art.id_articulo
    INNER JOIN arrendador a ON art.id_arrendador = a.id_arrendador
    INNER JOIN arrendatario arr ON alq.id_arrendatario = arr.id_arrendatario
    INNER JOIN usuario v ON a.DNI = v.DNI
    INNER JOIN usuario c ON arr.DNI = c.DNI
    WHERE alq.estado = 'Aceptado'
    ORDER BY alq.id_alquiler DESC
";
$historial = $_conexion->query($queryHistorial);

// 5. OBTENER REPORTES Y DENUNCIAS
// Muestro los artículos que la gente ha reportado (por falsos, ilegales, etc) para que el admin pueda actuar.
$queryReportes = "
    SELECT r.id_reporte, r.motivo, r.descripcion, r.fecha, r.estado, 
           art.id_articulo, art.nombre as nombre_articulo,
           u_denunciante.usuario as denunciante, u_denunciado.usuario as denunciado, u_denunciado.DNI as dni_denunciado
    FROM reportes r
    INNER JOIN articulo art ON r.id_articulo = art.id_articulo
    INNER JOIN arrendador a ON art.id_arrendador = a.id_arrendador
    INNER JOIN usuario u_denunciado ON a.DNI COLLATE utf8mb4_general_ci = u_denunciado.DNI COLLATE utf8mb4_general_ci
    INNER JOIN usuario u_denunciante ON r.DNI_denunciante COLLATE utf8mb4_general_ci = u_denunciante.DNI COLLATE utf8mb4_general_ci
    ORDER BY r.estado ASC, r.fecha DESC
";
$reportes = $_conexion->query($queryReportes);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Panel de Administración | Reusarent</title>
    <?php include 'includes/head.php'; ?>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">

    <?php include 'includes/top_nav.php'; ?>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        
        <div class="mb-8 flex items-center gap-4">
            <div class="w-14 h-14 bg-gray-900 rounded-2xl flex items-center justify-center text-white shadow-lg">
                <i class="ph-bold ph-shield-check text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Panel de Administración</h1>
                <p class="text-gray-500 font-medium mt-1">Supervisión de métricas financieras de Reusarent</p>
            </div>
        </div>

        <!-- Tarjetas Metricas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gradient-to-br from-brand to-brand-dark rounded-3xl p-8 text-white shadow-xl shadow-brand/20 relative overflow-hidden">
                <i class="ph-fill ph-chart-line-up absolute -right-6 -bottom-6 text-9xl text-white/10"></i>
                <h3 class="text-white/80 font-bold uppercase tracking-widest text-xs mb-2">Ingresos por Comisiones</h3>
                <div class="text-5xl font-bold"><?= number_format($totalRecaudado, 2) ?> €</div>
                <p class="text-white/70 text-sm mt-3 flex items-center gap-1">
                    <i class="ph-fill ph-check-circle"></i> Comisiones del 10% cobradas
                </p>
            </div>

            <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-3xl p-8 text-white shadow-xl shadow-yellow-500/20 relative overflow-hidden">
                <i class="ph-fill ph-crown absolute -right-6 -bottom-6 text-9xl text-white/10"></i>
                <h3 class="text-white/80 font-bold uppercase tracking-widest text-xs mb-2">Ingresos por Suscripciones</h3>
                <div class="text-5xl font-bold"><?= number_format($totalSuscripciones, 2) ?> €</div>
                <p class="text-white/70 text-sm mt-3 flex items-center gap-1">
                    <i class="ph-fill ph-check-circle"></i> Planes Premium y Platino
                </p>
            </div>
        </div>

        <?php if (isset($_GET['exito_borrar'])): ?>
            <div class="bg-[#e8f7f5] border border-brand/20 text-[#068f80] px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3">
                <i class="ph-fill ph-check-circle text-2xl mt-0.5"></i>
                <div><h4 class="font-bold">Artículo eliminado</h4><p class="text-sm mt-0.5">Se ha forzado el borrado del artículo y sus alquileres correctamente.</p></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['exito_revisado'])): ?>
            <div class="bg-[#e8f7f5] border border-brand/20 text-[#068f80] px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3">
                <i class="ph-fill ph-check-circle text-2xl mt-0.5"></i>
                <div><h4 class="font-bold">Reporte cerrado</h4><p class="text-sm mt-0.5">El reporte ha sido marcado como revisado.</p></div>
            </div>
        <?php endif; ?>

        <!-- BANDEJA DE REPORTES -->
        <div class="bg-white rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-warning-octagon text-red-500 text-xl"></i> Bandeja de Reportes
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 font-bold">
                            <th class="p-4 pl-6">Estado</th>
                            <th class="p-4">Detalles del Reporte</th>
                            <th class="p-4">Usuarios</th>
                            <th class="p-4 text-right pr-6">Acciones (Admin)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if ($reportes && $reportes->num_rows > 0): ?>
                            <?php while($rep = $reportes->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50/80 transition-colors <?= $rep['estado'] == 'Revisado' ? 'opacity-60' : '' ?>">
                                    <td class="p-4 pl-6">
                                        <?php if ($rep['estado'] == 'Pendiente'): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 font-bold text-[10px] rounded-md tracking-widest uppercase">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Pendiente
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-500 font-bold text-[10px] rounded-md tracking-widest uppercase">
                                                Revisado
                                            </span>
                                        <?php endif; ?>
                                        <p class="text-[10px] text-gray-400 mt-2 font-medium"><?= date("d/m/Y H:i", strtotime($rep['fecha'])) ?></p>
                                    </td>
                                    <td class="p-4 max-w-xs">
                                        <div class="flex items-center gap-2 mb-1">
                                            <p class="font-bold text-gray-900 text-sm truncate"><?= htmlspecialchars($rep['nombre_articulo']) ?></p>
                                            <a href="info_articulos.php?articulo=<?= urlencode($rep['nombre_articulo']) ?>" target="_blank" class="text-brand hover:underline text-xs flex items-center gap-1"><i class="ph ph-arrow-square-out"></i> Ver</a>
                                        </div>
                                        <p class="text-xs font-bold text-red-500 mb-1"><?= htmlspecialchars($rep['motivo']) ?></p>
                                        <?php if(!empty($rep['descripcion'])): ?>
                                            <p class="text-xs text-gray-500 italic">"<?= htmlspecialchars($rep['descripcion']) ?>"</p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-xs text-gray-500 mb-1">Denunciado: <span class="font-bold text-gray-800"><?= htmlspecialchars($rep['denunciado']) ?></span></p>
                                        <p class="text-xs text-gray-400">Por: <?= htmlspecialchars($rep['denunciante']) ?></p>
                                    </td>
                                    <td class="p-4 pr-6">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Enviar Aviso (Chat) -->
                                            <form action="buzon.php" method="GET" target="_blank">
                                                <input type="hidden" name="nuevo_chat_articulo" value="<?= $rep['id_articulo'] ?>">
                                                <input type="hidden" name="vendedor" value="<?= $rep['dni_denunciado'] ?>">
                                                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-1" title="Mandar mensaje de advertencia">
                                                    <i class="ph-fill ph-chat-circle-dots"></i> Avisar
                                                </button>
                                            </form>

                                            <?php if ($rep['estado'] == 'Pendiente'): ?>
                                                <!-- Borrar Artículo (Solo si no está revisado) -->
                                                <form action="accion_admin_borrar_articulo.php" method="POST" onsubmit="confirmarBorrado(event, this);">
                                                    <input type="hidden" name="id_articulo" value="<?= $rep['id_articulo'] ?>">
                                                    <input type="hidden" name="id_reporte" value="<?= $rep['id_reporte'] ?>">
                                                    <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-1">
                                                        <i class="ph-bold ph-trash"></i> Borrar
                                                    </button>
                                                </form>

                                                <!-- Marcar Revisado -->
                                                <form action="accion_admin_resolver_reporte.php" method="POST">
                                                    <input type="hidden" name="id_reporte" value="<?= $rep['id_reporte'] ?>">
                                                    <button type="submit" class="bg-brand/10 hover:bg-brand/20 text-brand px-3 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-1" title="Ignorar o cerrar reporte">
                                                        <i class="ph-bold ph-check"></i> Cerrar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-12 text-center text-gray-400 font-medium">
                                    <i class="ph-fill ph-shield-check text-4xl mb-2 text-green-400"></i>
                                    <p>Todo está en orden. No hay reportes pendientes.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GESTIÓN DE CATEGORÍAS -->
        <div class="bg-white rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-tag text-brand text-xl"></i> Añadir Nueva Categoría
                </h2>
            </div>
            
            <div class="p-6">
                <?php if (isset($_GET['exito_categoria'])): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 flex items-center gap-2">
                        <i class="ph-fill ph-check-circle text-lg"></i> Categoría añadida exitosamente.
                    </div>
                <?php endif; ?>
                
                <form action="accion_admin_crear_categoria.php" method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="w-full sm:w-1/3">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 ml-1">Nombre</label>
                        <input type="text" name="nombre_categoria" placeholder="Ej: Herramientas" required class="w-full bg-gray-50 border border-gray-200 rounded-xl py-2.5 px-3 outline-none focus:ring-2 focus:ring-brand/20">
                    </div>
                    <div class="w-full sm:w-1/2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1.5 ml-1">Descripción</label>
                        <input type="text" name="descripcion_categoria" placeholder="Breve descripción..." class="w-full bg-gray-50 border border-gray-200 rounded-xl py-2.5 px-3 outline-none focus:ring-2 focus:ring-brand/20">
                    </div>
                    <div class="w-full sm:w-auto">
                        <button type="submit" class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-sm">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Historial -->
        <div class="bg-white rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-receipt text-brand text-xl"></i> Historial de Liquidaciones
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 font-bold">
                            <th class="p-4 pl-6">ID Seguimiento</th>
                            <th class="p-4">Artículo</th>
                            <th class="p-4">Transacción</th>
                            <th class="p-4 text-right">Monto Total</th>
                            <th class="p-4 pr-6 text-right text-brand">Comisión (10%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if ($historial && $historial->num_rows > 0): ?>
                            <?php while($row = $historial->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50/80 transition-colors">
                                    <td class="p-4 pl-6">
                                        <span class="inline-block px-2.5 py-1 bg-gray-100 text-gray-600 font-bold text-[10px] rounded-md tracking-widest uppercase">
                                            <?= htmlspecialchars($row['numero_seguimiento']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($row['nombre']) ?></p>
                                        <p class="text-xs text-gray-400"><?= date("d/m/Y", strtotime($row['fecha_alquiler'])) ?></p>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-2 text-xs font-medium text-gray-500">
                                            <span class="text-red-500 bg-red-50 px-2 py-0.5 rounded"><?= htmlspecialchars($row['comprador']) ?></span>
                                            <i class="ph ph-arrow-right"></i>
                                            <span class="text-green-600 bg-green-50 px-2 py-0.5 rounded"><?= htmlspecialchars($row['vendedor']) ?></span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right font-semibold text-gray-900">
                                        <?= number_format($row['precio_total'], 2) ?> €
                                    </td>
                                    <td class="p-4 pr-6 text-right font-bold text-brand bg-brand/5">
                                        + <?= number_format($row['comision_plataforma'], 2) ?> €
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-12 text-center text-gray-400 font-medium">
                                    <i class="ph-fill ph-empty text-4xl mb-2"></i>
                                    <p>No hay alquileres finalizados que hayan generado comisiones.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
    
    <?php include 'includes/bottom_nav.php'; ?>
    <script>
        function confirmarBorrado(e, form) {
            e.preventDefault();
            Swal.fire({
                title: '¿Estás seguro?',
                text: '¿Borrar definitivamente este artículo y todas sus reservas?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Sí, borrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
