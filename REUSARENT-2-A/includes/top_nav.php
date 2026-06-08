<?php
// --- ARCHIVO: includes/top_nav.php ---
// Esta es la barra de navegación superior que sale en todas las páginas.
// Uso basename para saber exactamente en qué página de la web estoy ahora mismo.
$current_page = basename($_SERVER['PHP_SELF']);

// 1. SISTEMA DE NOTIFICACIONES DE CHAT
// Inicializo el circulito rojo (badge) vacío por defecto.
$unreadBadgeHtml = '';

// Si el usuario está logueado y la conexión a la BD existe...
if(isset($_SESSION['usuario']) && isset($_conexion)) {
    $usuTemp = $_SESSION['usuario'];
    
    // Primero, saco mi propio DNI usando mi nombre de usuario.
    $rDNI = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$usuTemp'");
    if($rDNI && $rDNI->num_rows > 0) {
        $miDNITemp = $rDNI->fetch_assoc()['DNI'];
        
        // Hago una consulta cruzada (INNER JOIN) entre mensajes y conversaciones.
        // Busco mensajes donde yo NO soy el remitente (me los han enviado a mí),
        // donde yo participo en la conversación (soy interesado o vendedor),
        // y cuyo estado de lectura sea '0' (no leídos).
        $sqlUnread = "SELECT COUNT(*) as unread_count 
                      FROM mensaje m 
                      INNER JOIN conversacion c ON m.id_conversacion = c.id_conversacion 
                      WHERE m.DNI_remitente != '$miDNITemp' 
                      AND (LOWER(c.DNI_interesado) = LOWER('$miDNITemp') OR LOWER(c.DNI_vendedor) = LOWER('$miDNITemp')) 
                      AND m.leido = 0";
                      
        $resUnread = $_conexion->query($sqlUnread);
        if($resUnread && $resUnread->num_rows > 0) {
            $count = (int)$resUnread->fetch_assoc()['unread_count'];
            
            // Si tengo más de 0 mensajes sin leer, creo la bolita roja con el número.
            if($count > 0) {
                // Si tengo 10 o más, pongo '9+' para que no se deforme el circulito CSS.
                $displayCount = $count > 9 ? '9+' : $count;
                $unreadBadgeHtml = '<span class="absolute top-[2px] right-[2px] bg-red-500 text-white text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full border border-white shadow-sm">'.$displayCount.'</span>';
            }
        }
    }
}
?>
<!-- Navegación Superior para PC / Tablet -->
<header class="bg-white sticky top-0 z-[100] shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logotipo -->
            <div class="flex items-center gap-3">
                <img src="img/logo.PNG" alt="Reusarent Logo" class="w-10 h-10 object-contain rounded-xl">
                <a href="index.php" class="text-brand text-2xl font-bold tracking-tight">REUSARENT</a>
            </div>

            <!-- Búsqueda Global (solo en PC) -->
            <?php if ($current_page != 'index.php'): ?>
                <div class="hidden md:flex flex-1 max-w-lg mx-8 relative">
                    <form action="index.php" method="GET" class="w-full">
                        <input type="text" name="buscar" placeholder="Busca artículos, marcas..."
                            class="w-full bg-gray-100 border-transparent focus:bg-white focus:border-brand focus:ring-2 focus:ring-brand/20 rounded-full py-2.5 pl-12 pr-4 text-sm transition-all duration-300"
                            value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                        <i
                            class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Elementos de Navegación de PC -->
            <div class="hidden lg:flex items-center gap-6">
                <!-- Saludo de usuario -->
                <span class="text-sm font-medium text-gray-700">Hola,
                    <?= htmlspecialchars($_SESSION["usuario"] ?? 'Usuario') ?></span>

                <?php if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"] !== 'adminReusa'): ?>
                <a href="favoritos.php"
                    class="text-gray-500 hover:text-brand transition-colors p-2 rounded-full hover:bg-brand/5">
                    <i class="ph ph-heart text-2xl"></i>
                </a>
                
                <a href="planes.php"
                    class="text-yellow-500 hover:text-yellow-600 transition-colors p-2 rounded-full hover:bg-yellow-50">
                    <i class="ph-fill ph-crown text-2xl"></i>
                </a>

                <?php endif; ?>

                <a href="buzon.php"
                    class="text-gray-500 hover:text-brand transition-colors p-2 rounded-full hover:bg-brand/5 relative">
                    <?= $unreadBadgeHtml ?>
                    <i class="ph ph-chat-circle-dots text-2xl"></i>
                </a>

                <a href="dashboard.php"
                    class="text-gray-500 hover:text-brand transition-colors p-2 rounded-full hover:bg-brand/5">
                    <i class="ph ph-user text-2xl"></i>
                </a>

                <?php if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"] !== 'adminReusa'): ?>
                <a href="subir_articulo.php"
                    class="bg-brand text-white px-5 py-2 rounded-full font-medium hover:bg-brand-dark transition-colors shadow-sm ml-2 flex items-center gap-2">
                    <i class="ph ph-plus-circle text-lg"></i> Subir artículo
                </a>
                <?php endif; ?>
            </div>

            <!-- Acciones Rápidas Móvil -->
            <div class="lg:hidden flex items-center gap-3">
                <!-- Se ha movido el icono a la barra inferior -->
            </div>
        </div>
    </div>
</header>