<?php
// --- ARCHIVO: planes.php ---
// La página de "Pricing" donde explicamos las ventajas de ser Premium o Platino y dejamos que el usuario los compre.

session_start();
require "conexion/conexion.php";

$logged_in = isset($_SESSION['usuario']);

// 1. VALORES POR DEFECTO
$rolActual = 'no-premium';
$saldo = 0;

// 2. OBTENER DATOS DEL USUARIO LOGUEADO
// Si está conectado, miro a ver qué plan tiene ya comprado y cuánto saldo tiene en la cartera.
if ($logged_in) {
    $usuario = $_SESSION['usuario'];
    $consulta = "SELECT rol, saldo FROM usuario WHERE usuario = '$usuario'";
    $resultado = $_conexion->query($consulta);
    
    if ($resultado && $resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        $rolActual = $fila['rol'];
        // Si el saldo es null (ej: usuario nuevo), lo pongo a 0 para que no dé errores de matemáticas en JS.
        $saldo = $fila['saldo'] ? $fila['saldo'] : 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Planes de Suscripción | Reusarent</title>
    <?php include 'includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50 pb-safe">

    <?php include 'includes/top_nav.php'; ?>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 pb-32">
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-5xl font-bold text-gray-900 mb-4 tracking-tight">Mejora tu experiencia en <span class="text-brand">Reusarent</span></h1>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto">Vende más rápido, paga menos comisiones y destaca tus productos en nuestra plataforma. Elige el plan que mejor se adapte a ti.</p>
        </div>

        <?php if (isset($_GET['exito'])): ?>
            <div class="max-w-3xl mx-auto bg-[#e8f7f5] border border-brand/20 text-[#068f80] px-4 py-4 rounded-2xl mb-8 shadow-sm flex items-start gap-3">
                <i class="ph-fill ph-check-circle text-2xl mt-0.5"></i>
                <div>
                    <h4 class="font-bold">¡Plan adquirido!</h4>
                    <p class="text-sm mt-0.5">Ya disfrutas de todos los beneficios de tu nueva suscripción.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="max-w-3xl mx-auto bg-red-50 border border-red-200 text-red-600 px-4 py-4 rounded-2xl mb-8 shadow-sm flex items-start gap-3">
                <i class="ph-fill ph-warning-circle text-2xl mt-0.5"></i>
                <div>
                    <h4 class="font-bold">No se pudo adquirir el plan</h4>
                    <p class="text-sm mt-0.5">
                        <?php 
                        if($_GET['error'] == 'saldo_insuficiente') echo 'No tienes saldo suficiente en tu monedero. Por favor, recarga tu saldo en tu perfil.';
                        else echo 'Ha ocurrido un error procesando tu solicitud.';
                        ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto items-start">
            
            <!-- PLAN BÁSICO -->
            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm flex flex-col h-full relative">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No Premium</h3>
                    <div class="flex items-end gap-1 mb-2">
                        <span class="text-4xl font-bold text-gray-900">0€</span>
                        <span class="text-gray-500 font-medium pb-1">/mes</span>
                    </div>
                    <p class="text-sm text-gray-500">Comisión del 10% respecto al precio del alquiler para el arrendador.</p>
                </div>

                <ul class="space-y-4 mb-8 flex-1">
                    <li class="flex items-start gap-3 text-sm text-gray-600">
                        <i class="ph-bold ph-check text-green-500 mt-0.5 text-base"></i>
                        <span>Acceso a las funciones básicas de la aplicación.</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-600">
                        <i class="ph-bold ph-info text-gray-400 mt-0.5 text-base"></i>
                        <span>Se aplica más comisión que en suscripciones superiores.</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-600">
                        <i class="ph-bold ph-info text-gray-400 mt-0.5 text-base"></i>
                        <span>Visibilidad de los anuncios normal.</span>
                    </li>
                </ul>

                <?php if ($rolActual == 'no-premium' || $rolActual == 'usuario'): ?>
                    <button class="w-full py-3.5 rounded-xl font-bold bg-gray-100 text-gray-500 cursor-not-allowed border border-gray-200" disabled>
                        Plan Actual
                    </button>
                <?php else: ?>
                    <button class="w-full py-3.5 rounded-xl font-bold bg-gray-50 text-gray-400 cursor-not-allowed border border-gray-100" disabled>
                        Incluido en tu plan
                    </button>
                <?php endif; ?>
            </div>

            <!-- PLAN PREMIUM -->
            <div class="bg-white rounded-3xl p-8 border-2 border-brand shadow-xl flex flex-col h-full relative transform md:-translate-y-4">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-brand text-white px-4 py-1 rounded-full text-xs font-bold tracking-wider uppercase">
                    Más popular
                </div>
                
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-brand mb-2 flex items-center gap-2">
                        Premium <i class="ph-fill ph-star"></i>
                    </h3>
                    <div class="flex items-end gap-1 mb-2">
                        <span class="text-4xl font-bold text-gray-900">9,99€</span>
                        <span class="text-gray-500 font-medium pb-1">/mes</span>
                    </div>
                    <p class="text-sm text-gray-500">Perfecto para usuarios frecuentes.</p>
                </div>

                <ul class="space-y-4 mb-8 flex-1">
                    <li class="flex items-start gap-3 text-sm text-gray-800 font-medium">
                        <i class="ph-bold ph-check text-brand mt-0.5 text-base"></i>
                        <span>Reducción de comisión por alquiler.</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-800 font-medium">
                        <i class="ph-bold ph-check text-brand mt-0.5 text-base"></i>
                        <span>Mayor visibilidad a los productos publicados.</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-800 font-medium">
                        <i class="ph-bold ph-check text-brand mt-0.5 text-base"></i>
                        <span>Tiene menos frecuencia de anuncios.</span>
                    </li>
                </ul>

                <?php if ($rolActual == 'premium'): ?>
                    <button class="w-full py-3.5 rounded-xl font-bold bg-brand/10 text-brand cursor-not-allowed border border-brand/20" disabled>
                        Plan Actual
                    </button>
                <?php elseif ($rolActual == 'platino'): ?>
                    <button class="w-full py-3.5 rounded-xl font-bold bg-gray-50 text-gray-400 cursor-not-allowed border border-gray-100" disabled>
                        Tienes un plan superior
                    </button>
                <?php else: ?>
                    <form action="accion_comprar_plan.php" method="POST" onsubmit="return confirmarCompra(event, 'premium', 9.99)">
                        <input type="hidden" name="plan" value="premium">
                        <button type="submit" class="w-full py-3.5 rounded-xl font-bold bg-brand text-white hover:bg-brand-dark shadow-lg shadow-brand/30 transition-all active:scale-95">
                            Adquirir Premium
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- PLAN PLATINO -->
            <div class="bg-gradient-to-b from-gray-900 to-gray-800 rounded-3xl p-8 shadow-xl flex flex-col h-full relative border border-gray-700">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-yellow-400 mb-2 flex items-center gap-2">
                        Platino <i class="ph-fill ph-crown"></i>
                    </h3>
                    <div class="flex items-end gap-1 mb-2">
                        <span class="text-4xl font-bold text-white">24,99€</span>
                        <span class="text-gray-400 font-medium pb-1">/mes</span>
                    </div>
                    <p class="text-sm text-gray-400">Para los profesionales de Reusarent.</p>
                </div>

                <ul class="space-y-4 mb-8 flex-1">
                    <li class="flex items-start gap-3 text-sm text-gray-200">
                        <i class="ph-bold ph-check text-yellow-400 mt-0.5 text-base"></i>
                        <span>No tiene comisión por alquiler.</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-200">
                        <i class="ph-bold ph-check text-yellow-400 mt-0.5 text-base"></i>
                        <span>Máxima visibilidad de productos en la plataforma.</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-200">
                        <i class="ph-bold ph-check text-yellow-400 mt-0.5 text-base"></i>
                        <span>Acceso prioritario a promociones y descuentos.</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-gray-200">
                        <i class="ph-bold ph-check text-yellow-400 mt-0.5 text-base"></i>
                        <span>No tiene anuncios.</span>
                    </li>
                </ul>

                <?php if ($rolActual == 'platino'): ?>
                    <button class="w-full py-3.5 rounded-xl font-bold bg-white/10 text-yellow-400 cursor-not-allowed border border-white/10" disabled>
                        Plan Actual
                    </button>
                <?php else: ?>
                    <form action="accion_comprar_plan.php" method="POST" onsubmit="return confirmarCompra(event, 'platino', 24.99)">
                        <input type="hidden" name="plan" value="platino">
                        <button type="submit" class="w-full py-3.5 rounded-xl font-bold bg-yellow-400 text-gray-900 hover:bg-yellow-300 shadow-lg shadow-yellow-400/20 transition-all active:scale-95">
                            Adquirir Platino
                        </button>
                    </form>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <?php include 'includes/bottom_nav.php'; ?>

    <script>
        function confirmarCompra(e, plan, precio) {
            e.preventDefault();
            const saldoActual = <?= $saldo ?>;
            const form = e.target;
            const nombrePlan = plan === 'premium' ? 'Premium' : 'Platino';
            const colorPlan = plan === 'premium' ? '#068f80' : '#facc15';

            if (saldoActual < precio) {
                Swal.fire({
                    title: 'Saldo insuficiente',
                    text: `Necesitas ${precio}€ para adquirir el plan ${nombrePlan}. Tu saldo actual es de ${saldoActual.toFixed(2)}€.`,
                    icon: 'error',
                    confirmButtonColor: '#068f80',
                    confirmButtonText: 'Ir a mi perfil para recargar',
                    customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold' }
                }).then((r) => {
                    if (r.isConfirmed) window.location.href = 'dashboard.php';
                });
                return false;
            }

            Swal.fire({
                title: `Adquirir Plan ${nombrePlan}`,
                html: `Se descontarán <b>${precio}€</b> de tu monedero.<br>Tu saldo actual: ${saldoActual.toFixed(2)}€`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: colorPlan,
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Confirmar pago',
                cancelButtonText: 'Cancelar',
                customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl font-bold text-gray-900', cancelButton: 'rounded-xl font-bold' }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            })
            return false;
        }
    </script>
</body>
</html>
