<?php
// --- ARCHIVO: includes/bottom_nav.php ---
// Esta es la barra de navegación inferior (estilo app nativa) que solo se ve en móviles.
// Uso basename para saber en qué página estoy y pintar el icono de esa página con el color activo (relleno).
$current_page = basename($_SERVER['PHP_SELF']);

// Reutilizo la variable $unreadBadgeHtml que ya calculé arriba en top_nav.php.
// Así no tengo que repetir la consulta a la base de datos de los mensajes no leídos dos veces.
$bottomBadgeHtml = '';
if (!empty($unreadBadgeHtml)) {
    // Como el botón de abajo es distinto, le ajusto un poco las clases CSS con str_replace 
    // para que la bolita roja caiga en el sitio exacto del icono del móvil.
    $bottomBadgeHtml = str_replace("top-[2px] right-[2px]", "top-[5px] right-[15px] sm:right-[30%]", $unreadBadgeHtml);
}
?>
<!-- Navegación Inferior Móvil (solo visible en pantallas pequeñas) -->
<div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-100 lg:hidden z-[100] shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] pb-[env(safe-area-inset-bottom)]">
    <div class="flex justify-around items-center h-[4.5rem] px-2">
        <a href="index.php" class="flex flex-col items-center justify-center w-full h-full transition-colors <?php echo ($current_page == 'index.php') ? 'text-brand' : 'text-gray-400 hover:text-brand'; ?>">
            <i class="<?php echo ($current_page == 'index.php') ? 'ph-fill' : 'ph'; ?> ph-house text-[1.65rem]"></i>
            <span class="text-[0.65rem] mt-1 font-medium">Inicio</span>
        </a>
        <?php if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"] !== "adminReusa"): ?>
        <a href="favoritos.php" class="flex flex-col items-center justify-center w-full h-full transition-colors <?php echo ($current_page == 'favoritos.php') ? 'text-brand' : 'text-gray-400 hover:text-brand'; ?>">
            <i class="<?php echo ($current_page == 'favoritos.php') ? 'ph-fill' : 'ph'; ?> ph-heart text-[1.65rem]"></i>
            <span class="text-[0.65rem] mt-1 font-medium">Favoritos</span>
        </a>
        
        <!-- Botón de Acción Flotante Central -->
        <a href="subir_articulo.php" class="flex flex-col items-center justify-center w-full h-full relative transition-colors text-gray-500 hover:text-brand group">
            <!-- Icono invisible para forzar que el texto 'Publicar' se alinee exactamente igual que los demás botones -->
            <i class="ph ph-plus text-[1.65rem] opacity-0"></i>
            <span class="text-[0.65rem] mt-1 font-medium group-hover:text-brand transition-colors">Publicar</span>
            
            <!-- Botón flotante real -->
            <div class="absolute -top-5 left-1/2 -translate-x-1/2 bg-brand text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg shadow-brand/40 transform transition-transform active:scale-95 border-4 border-white z-10">
                <i class="ph ph-plus text-2xl font-bold"></i>
            </div>
        </a>

        <?php endif; ?>

        <a href="buzon.php" class="flex flex-col items-center justify-center w-full h-full relative transition-colors <?php echo ($current_page == 'buzon.php') ? 'text-brand' : 'text-gray-400 hover:text-brand'; ?>">
            <?= $bottomBadgeHtml ?>
            <i class="<?php echo ($current_page == 'buzon.php') ? 'ph-fill' : 'ph'; ?> ph-chat-circle-dots text-[1.65rem]"></i>
            <span class="text-[0.65rem] mt-1 font-medium">Buzón</span>
        </a>
        
        <a href="dashboard.php" class="flex flex-col items-center justify-center w-full h-full transition-colors <?php echo ($current_page == 'dashboard.php' || $current_page == 'ajustes.php' || $current_page == 'admin.php') ? 'text-brand' : 'text-gray-400 hover:text-brand'; ?>">
            <i class="<?php echo ($current_page == 'dashboard.php' || $current_page == 'ajustes.php' || $current_page == 'admin.php') ? 'ph-fill' : 'ph'; ?> ph-user text-[1.65rem]"></i>
            <span class="text-[0.65rem] mt-1 font-medium">Perfil</span>
        </a>
    </div>
</div>
