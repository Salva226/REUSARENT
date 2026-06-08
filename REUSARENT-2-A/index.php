<?php
// --- ARCHIVO: index.php ---
// Esta es la página principal (El "Home" o escaparate de la tienda).

session_start();

// 1. COMPROBAR SESIÓN
// Si no hay ninguna sesión de usuario guardada, significa que no se ha logueado.
// Lo echo a la pantalla de login directamente.
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}

// Me traigo mi conexión a la BD.
require "conexion/conexion.php";

// 2. SACAR LAS CATEGORÍAS PARA LOS BOTONES
// Le pido a la BD todas las categorías (Vehículos, Herramientas, etc.) para pintar los botoncitos redondos arriba.
$consulta_cat = "SELECT * FROM categoria";
$resultado_cat = $_conexion->query($consulta_cat);
$categorias = [];
if ($resultado_cat && $resultado_cat->num_rows > 0) {
    while ($row = $resultado_cat->fetch_assoc()) {
        $categorias[] = $row; // Guardo cada categoría en un array.
    }
}

// 3. SISTEMA DE FILTROS (Búsqueda y Categorías)
// Creo un array vacío de condiciones 'WHERE' para ir montando la consulta SQL poco a poco.
$where = [];

// Si me llega algo por la barra de búsqueda (por GET)
if (isset($_GET['buscar']) && $_GET['buscar'] !== "") {
    // Escapo los caracteres raros para evitar inyecciones SQL
    $busqueda = $_conexion->real_escape_string($_GET['buscar']);
    // Añado la condición de que el nombre del artículo se parezca a lo que ha escrito el usuario.
    $where[] = "nombre LIKE '%$busqueda%'";
}

// Si me llega una categoría por GET (el usuario ha pulsado uno de los botoncitos de categorías)
if (isset($_GET['cat']) && $_GET['cat'] !== "") {
    $cat = (int) $_GET['cat'];
    $where[] = "id_categoria = $cat";
}

// Aquí junto todas las condiciones con un "AND". Si no hay filtros, el WHERE se queda vacío.
$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// 4. SISTEMA DE ORDENACIÓN (Más baratos / Más caros)
// Por defecto, ordeno por id_articulo DESC para que siempre salgan los últimos que se han subido.
$orden_sql = "ORDER BY id_articulo DESC"; 
if (isset($_GET['orden'])) {
    if ($_GET['orden'] == 'asc')
        $orden_sql = "ORDER BY precio ASC"; // De más barato a más caro
    else if ($_GET['orden'] == 'desc')
        $orden_sql = "ORDER BY precio DESC"; // De más caro a más barato
}

// 5. SACAR LOS ARTÍCULOS DEFINITIVOS
// Ya tengo el WHERE y el ORDER BY, así que armo la consulta final y la ejecuto. (Límite 20 artículos por ahora).
$consulta = "SELECT id_articulo, nombre, precio, foto FROM articulo $where_sql $orden_sql LIMIT 20";
$resultado = $_conexion->query($consulta);

// 6. FAVORITOS DEL USUARIO (Corazones rojos)
// Necesito saber qué artículos tiene este usuario en favoritos para pintarles el corazón relleno en rojo.
$favoritos_usuario = [];
$usuarioLogeado = $_SESSION["usuario"];

// Hago un cruce de tablas (INNER JOIN) entre favorito y usuario.
$resFa = $_conexion->query("SELECT id_articulo FROM favorito f INNER JOIN usuario u ON f.DNI = u.DNI WHERE u.usuario = '$usuarioLogeado'");
if ($resFa) {
    while ($f = $resFa->fetch_assoc()) {
        $favoritos_usuario[] = $f['id_articulo']; // Guardo los IDs de los artículos que le gustan.
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Inicio | Reusarent</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 pb-safe">

    <?php include 'includes/top_nav.php'; ?>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Búsqueda Móvil (Solo visible en móviles) -->
        <div class="lg:hidden w-full mb-6 relative z-10">
            <form method="GET" action="index.php"
                class="w-full relative shadow-[0_4px_12px_rgba(0,0,0,0.04)] rounded-full overflow-hidden">
                <input type="text" name="buscar"
                    class="w-full bg-white border-none py-3.5 pl-12 pr-4 text-[15px] focus:ring-0 text-gray-800 placeholder-gray-400"
                    placeholder="Busca en Reusarent..."
                    value="<?php if (isset($_GET["buscar"]))
                        echo htmlspecialchars($_GET["buscar"]); ?>">
                <?php if (isset($_GET['cat'])): ?><input type="hidden" name="cat"
                        value="<?= htmlspecialchars($_GET['cat']) ?>"><?php endif; ?>
                <i
                    class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl font-bold"></i>
            </form>
        </div>

        <!-- Filtros de Categoría -->
        <div class="flex overflow-x-auto hide-scrollbar gap-3 mb-8 pb-1">
            <?php $catActual = $_GET['cat'] ?? ''; ?>

            <a href="index.php<?= isset($_GET['buscar']) ? '?buscar=' . urlencode($_GET['buscar']) : '' ?>"
                class="whitespace-nowrap px-5 py-2.5 rounded-full text-sm font-semibold shadow-sm active:scale-95 transition-transform flex items-center gap-1.5 <?= ($catActual == '') ? 'bg-brand text-white shadow-brand/20' : 'bg-white text-gray-600 border border-gray-200 hover:!border-brand hover:text-brand' ?>">
                <i class="ph ph-sparkle"></i> Para ti
            </a>

            <?php foreach ($categorias as $cat):
                $esActual = ($catActual == $cat['id_categoria']);
                ?>
                <!-- Mantener término de búsqueda al cambiar categoría -->
                <?php
                $params = ['cat' => $cat['id_categoria']];
                if (isset($_GET['buscar']))
                    $params['buscar'] = $_GET['buscar'];
                $urlCat = "index.php?" . http_build_query($params);
                ?>
                <a href="<?= $urlCat ?>"
                    class="whitespace-nowrap px-5 py-2.5 rounded-full text-sm font-semibold shadow-sm active:scale-95 transition-all <?= $esActual ? 'bg-brand text-white border-transparent' : 'bg-white text-gray-600 border border-gray-200 hover:border-brand hover:text-brand' ?>">
                    <?= htmlspecialchars($cat['nombre']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- FEED DE PRODUCTOS -->
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h1 class="text-[1.3rem] font-bold text-gray-900 tracking-tight">Novedades <span
                    class="text-gray-400 font-normal text-sm ml-2 relative -top-0.5"><?= $resultado ? $resultado->num_rows : 0 ?>
                    resultados</span></h1>

            <!-- Formulario de Ordenación -->
            <form method="GET" action="index.php" class="relative">
                <?php if (isset($_GET['buscar'])): ?><input type="hidden" name="buscar"
                        value="<?= htmlspecialchars($_GET['buscar']) ?>"><?php endif; ?>
                <?php if (isset($_GET['cat'])): ?><input type="hidden" name="cat"
                        value="<?= htmlspecialchars($_GET['cat']) ?>"><?php endif; ?>
                <select name="orden" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-gray-200 text-gray-700 py-2 pl-4 pr-10 rounded-full text-sm font-medium focus:ring-2 focus:ring-brand shadow-sm font-sans w-full sm:w-auto">
                    <option value="">Ordenar</option>
                    <option value="asc" <?= (isset($_GET['orden']) && $_GET['orden'] == 'asc') ? 'selected' : '' ?>>Más
                        barato</option>
                    <option value="desc" <?= (isset($_GET['orden']) && $_GET['orden'] == 'desc') ? 'selected' : '' ?>>Más
                        caro</option>
                </select>
                <i
                    class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none"></i>
            </form>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-5">
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while ($fila = $resultado->fetch_assoc()): ?>
                    <div
                        class="bg-white rounded-3xl shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100/50 overflow-hidden hover:shadow-[0_8px_24px_rgba(0,0,0,0.06)] transition-all duration-300 group relative flex flex-col">

                        <!-- Botón de Me Gusta (AJAX) -->
                        <?php $esFavorito = in_array((int) $fila['id_articulo'], $favoritos_usuario); ?>
                        <button onclick="toggleFavorito(event, this, <?= (int) $fila['id_articulo'] ?>)"
                            class="absolute top-3 right-3 p-1.5 <?= $esFavorito ? 'text-red-500' : 'text-gray-400' ?> bg-white/70 backdrop-blur-md rounded-full hover:text-red-500 hover:bg-white shadow-sm transition-all z-10 active:scale-90 heart-btn">
                            <i
                                class="<?= $esFavorito ? 'ph-fill' : 'ph' ?> ph-heart text-xl leading-none transition-colors"></i>
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
                                        Nuevo
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
            <?php else: ?>
                <div class="col-span-full">
                    <div
                        class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-gray-100 border-dashed text-center">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="ph ph-package text-4xl text-gray-400"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 mb-2">No se encontraron artículos</h2>
                        <p class="text-gray-500 max-w-sm">Prueba eliminando algunos filtros o buscando con otros términos.
                        </p>
                        <a href="index.php" class="mt-4 text-brand font-semibold hover:underline">Limpiar todos los
                            filtros</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <br class="hidden sm:block"><br class="hidden sm:block">

    <?php include 'includes/bottom_nav.php'; ?>

    <script>
        async function toggleFavorito(event, btn, idArticulo) {
            // Al ser un evento dentro del area del ancla <a> no queremos entrar al enlace
            event.preventDefault();
            event.stopPropagation();

            // Toggle visual state instantly para que sientan  que la APP  va rápida  y sin lageos (Optimistic UI Update)
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

            // LLamo al servidor para que persista 
            try {
                const response = await fetch('toggle_favorito.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_articulo: idArticulo })
                });
                const data = await response.json();

                if (data.error) {
                    console.error("Favorito error:", data.error);
                }
            } catch (e) {
                console.error("Fetch error:", e);
            }
        }
    </script>

</body>

</html>