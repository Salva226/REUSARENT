<?php
// --- ARCHIVO: editar_articulo.php ---
// El formulario visual donde el usuario puede cambiar el nombre, precio, descripción o fotos de su anuncio.

session_start();

// 1. SEGURIDAD
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}

require "conexion/conexion.php";

// Si intentan entrar sin decirle a la página QUÉ artículo quieren editar, los echamos.
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_articulo = $_conexion->real_escape_string($_GET['id']);
$nombreUsuario = $_SESSION["usuario"];

// 2. VERIFICAR PROPIEDAD Y SACAR DATOS
// Comprobamos que el usuario que intenta editar sea el verdadero dueño del artículo.
$consulta = "SELECT art.* 
             FROM articulo art
             INNER JOIN arrendador a ON art.id_arrendador = a.id_arrendador
             INNER JOIN usuario u ON a.DNI = u.DNI
             WHERE art.id_articulo = '$id_articulo' AND u.usuario = '$nombreUsuario'";
             
$resultado = $_conexion->query($consulta);

if (!$resultado || $resultado->num_rows === 0) {
    // Si no es el dueño, a la calle.
    header("Location: index.php");
    exit();
}

// Me guardo los datos del artículo para rellenar los inputs del formulario
$articuloInfo = $resultado->fetch_assoc();

// 3. CARGAR CATEGORÍAS
// Necesitamos saber qué categorías existen para pintar el `<select>` desplegable.
$categorias = [];
$consultaCat = "SELECT id_categoria, nombre FROM categoria";
$resultadoCat = $_conexion->query($consultaCat);
if ($resultadoCat) {
    while ($fila = $resultadoCat->fetch_assoc()) {
        $categorias[] = $fila;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Editar Artículo | Reusarent</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 pb-safe">

    <?php include 'includes/top_nav.php'; ?>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-32">

        <div class="flex items-center gap-3 mb-6">
            <a href="info_articulos.php?articulo=<?= urlencode($articuloInfo['nombre']) ?>"
                class="text-gray-400 hover:text-brand transition-colors p-2 -ml-2 rounded-full hover:bg-gray-100">
                <i class="ph ph-arrow-left text-2xl"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Editar artículo</h1>
        </div>

        <form id="formEditar" action="accion_editar_articulo.php" method="post" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="id_articulo" value="<?= $articuloInfo['id_articulo'] ?>">

            <!-- FOTOS -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-bold text-gray-900">Fotos (hasta 5)</h3>
                </div>
                <div class="bg-blue-50 border border-blue-100 text-blue-700 px-3 py-2 rounded-xl text-xs mb-4 flex items-center gap-2">
                    <i class="ph-fill ph-info text-lg"></i> Si no seleccionas ninguna foto, se mantendrán las actuales. Si seleccionas nuevas, se borrarán las antiguas.
                </div>

                <!-- El input real donde la data final se guarda -->
                <input type="file" name="img-articulos[]" id="actualFileInput" class="hidden" multiple accept="image/*">

                <div id="preview-container" class="flex gap-3 overflow-x-auto hide-scrollbar pb-4 pt-2 px-1">
                    <!-- Renderizado desde Javascript -->
                </div>

                <!-- Boton escondido solo para cargar del sistema operativo -->
                <input type="file" id="browserFileInput" class="hidden" accept="image/*" multiple>
            </div>

            <!-- DETALLES -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100">
                <h3 class="text-base font-bold text-gray-900 mb-5">Detalles del artículo</h3>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Título del anuncio</label>
                        <input type="text" name="nombre"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 px-4 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white text-sm transition-all"
                            required
                            value="<?= htmlspecialchars($articuloInfo['nombre']) ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Describe tu artículo</label>
                        <textarea name="descripcion" rows="4"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 px-4 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white text-sm transition-all resize-none"
                            required><?= htmlspecialchars($articuloInfo['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Categoría</label>
                        <div class="relative">
                            <select name="categoria"
                                class="w-full appearance-none bg-gray-50 border border-gray-200 rounded-xl py-3 pl-4 pr-10 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white text-sm transition-all"
                                required>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat["id_categoria"]) ?>" <?= ($cat["id_categoria"] == $articuloInfo['id_categoria']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat["nombre"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="ph ph-caret-down text-gray-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRECIO -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100">
                <h3 class="text-base font-bold text-gray-900 mb-5">Precio de alquiler</h3>
                <div>
                    <div class="relative max-w-xs">
                        <input type="number" step="0.01" name="precio"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-4 pr-16 text-gray-900 font-bold text-lg focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white transition-all text-right"
                            required value="<?= htmlspecialchars($articuloInfo['precio']) ?>">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-[15px]">€/día</span>
                    </div>
                </div>
            </div>

            <div class="fixed bottom-[calc(env(safe-area-inset-bottom)+70px)] sm:bottom-0 left-0 lg:static lg:bottom-auto w-full z-40 bg-white/90 lg:bg-transparent backdrop-blur-md pt-4 pb-4 px-4 shadow-[0_-10px_20px_rgba(0,0,0,0.05)] lg:shadow-none border-t border-gray-100 lg:border-none">
                <div class="max-w-3xl mx-auto relative lg:mr-0 lg:ml-0 lg:w-full">
                    <button type="submit"
                        class="w-full bg-brand text-white py-4 rounded-full font-bold shadow-lg shadow-brand/30 hover:bg-brand-dark transition-all active:scale-95 text-lg flex justify-center items-center gap-2">
                        <i class="ph ph-floppy-disk text-xl"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </form>
    </main>

    <?php include 'includes/bottom_nav.php'; ?>

    <!-- JS FOTOS MULTIPLES -->
    <script>
        let selectedFiles = [];

        document.addEventListener("DOMContentLoaded", () => {
            renderPreviews();

            document.getElementById('browserFileInput').addEventListener('change', handleFiles);
        });

        function triggerFileInput() {
            document.getElementById('browserFileInput').click();
        }

        function handleFiles(event) {
            const files = Array.from(event.target.files);
            event.target.value = '';

            const slotsRestantes = 5 - selectedFiles.length;
            if (files.length > slotsRestantes) {
                Swal.fire({ icon: 'warning', title: 'Límite alcanzado', text: 'Solo puedes subir un máximo de 5 fotos.', confirmButtonColor: '#09b6a2' });
            }

            const filesPermitidos = files.slice(0, slotsRestantes);

            filesPermitidos.forEach(f => {
                if (f.type.startsWith('image/')) {
                    selectedFiles.push(f);
                }
            });

            renderPreviews();
            updateInputFiles();
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            renderPreviews();
            updateInputFiles();
        }

        function renderPreviews() {
            const container = document.getElementById('preview-container');
            container.innerHTML = ''; 

            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();

                const imgDiv = document.createElement('div');
                imgDiv.className = "w-24 h-24 md:w-28 md:h-28 flex-shrink-0 rounded-2xl overflow-hidden relative shadow-sm border border-gray-200 group transition-all";

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = "absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10 hover:bg-red-600 scale-90";
                removeBtn.innerHTML = '<i class="ph-bold ph-x text-xs"></i>';
                removeBtn.onclick = () => removeFile(index);

                const imgEl = document.createElement('img');
                imgEl.className = "w-full h-full object-cover";

                reader.onload = (e) => {
                    imgEl.src = e.target.result;
                }
                reader.readAsDataURL(file);

                imgDiv.appendChild(imgEl);
                imgDiv.appendChild(removeBtn);
                container.appendChild(imgDiv);
            });

            if (selectedFiles.length < 5) {
                const addMoreBox = document.createElement('div');
                addMoreBox.className = "w-24 h-24 md:w-28 md:h-28 flex-shrink-0 border-2 border-dashed border-gray-300 hover:border-brand hover:scale-[1.02] bg-gray-50 rounded-2xl flex flex-col justify-center items-center cursor-pointer transition-all relative overflow-hidden group";
                addMoreBox.onclick = triggerFileInput;
                addMoreBox.innerHTML = '<i class="ph ph-plus text-3xl text-gray-400 group-hover:text-brand mb-1"></i><span class="block text-xs font-medium text-gray-500 group-hover:text-brand px-2 text-center leading-tight">Añadir otra</span>';
                container.appendChild(addMoreBox);
            }
        }

        function updateInputFiles() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            document.getElementById('actualFileInput').files = dt.files;
        }
    </script>
</body>
</html>
