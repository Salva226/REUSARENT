<?php
// --- ARCHIVO: favoritos.php ---
// Aquí mostramos una cuadrícula (grid) con todos los artículos que el usuario ha marcado con el corazón.

session_start();

// 1. COMPROBAR SESIÓN
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}

require "conexion/conexion.php";

$usuarioLogeado = $_SESSION["usuario"];

// 2. OBTENER LOS ARTÍCULOS FAVORITOS DEL USUARIO
// Busco en la tabla 'favorito' usando el DNI de este usuario para poder sacar qué artículos le gustan.
$consulta = "SELECT a.id_articulo, a.nombre, a.precio, a.foto 
             FROM articulo a 
             INNER JOIN favorito f ON a.id_articulo = f.id_articulo 
             INNER JOIN usuario u ON f.DNI = u.DNI 
             WHERE u.usuario = '$usuarioLogeado'";

$resultado = $_conexion->query($consulta);

// 3. PREPARAR ICONOS ROJOS
// Guardo los IDs de los favoritos en un array. Como estamos literalmente en la página de "Mis Favoritos", 
// obviamente todos los corazones tienen que salir en rojo desde el principio.
$favoritos_usuario = [];
if ($resultado && $resultado->num_rows > 0) {
    // Recorro los resultados una vez para sacar los IDs
    $resultado->data_seek(0);
    while ($fila = $resultado->fetch_assoc()) {
        $favoritos_usuario[] = $fila['id_articulo'];
    }
    // Vuelvo a poner el "cursor" de resultados al principio para que el HTML de abajo pueda recorrerlos de nuevo y pintarlos.
    $resultado->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Favoritos | Reusarent</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 pb-safe">

    <?php include 'includes/top_nav.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 border-b border-brand inline-block pb-1">Mis Favoritos</h1>
        </div>

        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-5" id="favoritos-grid">
                <?php while ($fila = $resultado->fetch_assoc()): ?>
                    <div class="bg-white rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100/50 overflow-hidden hover:shadow-[0_8px_24px_rgba(0,0,0,0.06)] transition-all duration-300 group relative flex flex-col"
                        id="card-<?= $fila['id_articulo'] ?>">

                        <!-- Botón de Me Gusta (AJAX) -->
                        <button onclick="toggleFavorito(event, this, <?= (int) $fila['id_articulo'] ?>)"
                            class="absolute top-3 right-3 p-1.5 text-red-500 bg-white/70 backdrop-blur-md rounded-full hover:text-red-500 hover:bg-white shadow-sm transition-all z-10 active:scale-90 heart-btn">
                            <i class="ph-fill ph-heart text-xl leading-none transition-colors"></i>
                        </button>

                        <a href="info_articulos.php?articulo=<?php echo urlencode($fila["nombre"]); ?>"
                            class="flex-1 flex flex-col">
                            <div class="aspect-[4/5] w-full bg-gray-100 overflow-hidden relative">
                                <?php if (!empty($fila['foto'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($fila['foto']); ?>"
                                        class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-500"
                                        alt="<?php echo htmlspecialchars($fila["nombre"]); ?>">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                                        <i class="ph ph-image text-4xl"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Etiquetas -->
                                <div class="absolute bottom-2 left-2 flex gap-1">
                                    <span
                                        class="bg-white/90 backdrop-blur text-gray-800 text-[10px] uppercase font-bold px-2 py-1 rounded-md shadow-sm">
                                        Guardado
                                    </span>
                                </div>
                            </div>
                            <div class="p-4 flex-1 flex flex-col">
                                <h2 class="text-xl font-bold text-gray-900 tracking-tight leading-none mb-1">
                                    <?php echo htmlspecialchars($fila["precio"]); ?> €
                                </h2>
                                <h3 class="text-[0.95rem] text-gray-600 line-clamp-2 leading-snug break-words">
                                    <?php echo htmlspecialchars($fila["nombre"]); ?>
                                </h3>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- Diseño de Estado Vacío -->
            <div
                class="flex flex-col items-center justify-center py-24 bg-white rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 text-center px-4">
                <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mb-6 relative">
                    <i class="ph ph-heart text-5xl text-red-400 opacity-60"></i>
                    <div class="absolute -right-1 -top-1 w-8 h-8 bg-white rounded-full flex items-center justify-center">
                        <i class="ph-fill ph-heart text-red-500 text-xl"></i>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Aún no tienes favoritos</h2>
                <p class="text-gray-500 mb-8 max-w-sm mx-auto">Guarda los artículos que más te gusten haciendo clic en el
                    corazón para no perderlos de vista.</p>
                <a href="index.php"
                    class="bg-brand text-white px-8 py-3.5 rounded-full font-bold hover:bg-brand-dark transition-all shadow-md shadow-brand/20 active:scale-95 text-sm uppercase tracking-wide">
                    Explorar artículos
                </a>
            </div>
        <?php endif; ?>
    </main>

    <br class="hidden sm:block"><br class="hidden sm:block">
    <?php include 'includes/bottom_nav.php'; ?>

    <script>
        async function toggleFavorito(event, btn, idArticulo) {
            event.preventDefault();
            event.stopPropagation();

            const icon = btn.querySelector('i');
            const isFav = btn.classList.contains('text-red-500');

            // Lo quitamos de la vista porque estamos en "Mis Favoritos" y queremos desaparecerlo si lo quita
            if (isFav) {
                document.getElementById(`card-${idArticulo}`).style.display = 'none';

                // Count visible cards
                const visibleCards = document.querySelectorAll('[id^="card-"]:not([style*="display: none"])');
                if (visibleCards.length === 0) {
                    window.location.reload(); // Reload to show the empty placeholder properly
                }
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

</body>

</html>