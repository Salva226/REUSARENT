<?php
// --- ARCHIVO: subir_articulo.php ---
// Aquí es donde los usuarios pueden subir sus cosas para poder alquilarlas.

session_start();

// 1. SEGURIDAD: COMPROBAR SESIÓN
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}

require "conexion/conexion.php";

$nombreUsuario = $_SESSION["usuario"];

// 2. BUSCAR EL ID DE ARRENDADOR DEL USUARIO
// Para subir un artículo, el usuario necesita tener un id_arrendador en la BD.
$consulta = "SELECT a.id_arrendador 
            FROM arrendador a 
            INNER JOIN usuario u ON a.DNI = u.DNI 
            WHERE u.usuario = '$nombreUsuario'";

$resultado = $_conexion->query($consulta);
$idArrendador = null;

if ($resultado && $fila = $resultado->fetch_assoc()) {
    $idArrendador = $fila['id_arrendador']; // Si lo encuentro, me lo guardo.
} else {
    // Autocorrección: Si el usuario existe pero por algún error del pasado no se guardó en la tabla 'arrendador', 
    // lo inserto ahora mismo sobre la marcha para que no le dé error al intentar subir algo.
    $resDni = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$nombreUsuario'");
    if ($resDni && $filaDni = $resDni->fetch_assoc()) {
        $miDNI = $filaDni['DNI'];
        $_conexion->query("INSERT INTO arrendador (DNI) VALUES ('$miDNI')");
        $idArrendador = $_conexion->insert_id; // Saco el ID que se acaba de crear mágicamente.
    }
}

$errores = false;
$mensajeExito = "";
$mensajeError = "";

// 3. CARGAR CATEGORÍAS PARA EL SELECT
// Saco las categorías de la BD para pintar el desplegable del formulario (<select>).
$categorias = [];
$consultaCat = "SELECT id_categoria, nombre FROM categoria";
$resultadoCat = $_conexion->query($consultaCat);
if ($resultadoCat) {
    while ($fila = $resultadoCat->fetch_assoc()) {
        $categorias[] = $fila;
    }
}

// 4. PROCESAR EL FORMULARIO CUANDO LE DAN A "PUBLICAR"
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recojo los datos de texto
    $nombreArticulo = trim($_POST["nombre"]);
    $categoria = trim($_POST["categoria"]);
    $precio = trim($_POST["precio"]);
    $descripcion = trim($_POST["descripcion"]);
    $idCategoria = $categoria;

    if (!$errores && $idArrendador !== null) {

        // 5. PROCESAR LAS FOTOS MÚLTIPLES
        $fotos = [];
        // Compruebo que haya llegado un array de archivos (porque el input tiene "multiple")
        if (isset($_FILES["img-articulos"]) && is_array($_FILES["img-articulos"]["name"])) {
            foreach ($_FILES["img-articulos"]["tmp_name"] as $key => $tmp_name) {
                // Si el error es 0, significa que esa foto subió bien al servidor temporalmente
                if ($_FILES["img-articulos"]["error"][$key] == 0) {
                    $fotos[] = file_get_contents($tmp_name); // Convierto la imagen a bytes
                }
            }
        }

        // Separo la primera foto del resto
        $fotoPrincipal = isset($fotos[0]) ? $fotos[0] : null;

        if ($fotoPrincipal !== null) {
            // 6. PREPARED STATEMENTS (La forma segura de meter datos y archivos gordos en la BD)
            // Uso '?' como comodines para que nadie pueda colar SQL Injection.
            $plantilla = "INSERT INTO articulo (nombre, precio, descripcion, foto, id_categoria, id_arrendador) VALUES (?,?,?,?,?,?)";
            $stmt = $_conexion->prepare($plantilla);
            $null = null; // Para la foto, primero paso un nulo
            
            // Le digo a PHP qué tipo de datos son: s(string) d(double) s(string) b(blob) i(integer) i(integer)
            $stmt->bind_param("sdsbii", $nombreArticulo, $precio, $descripcion, $null, $idCategoria, $idArrendador);
            
            // Envío la foto principal "por trozos" (send_long_data) para que no pete la memoria.
            $stmt->send_long_data(3, $fotoPrincipal);

            if ($stmt->execute()) {
                // Saco el ID del artículo que acabo de insertar
                $id_articulo_creado = $stmt->insert_id;

                // 7. GUARDAR FOTOS ADICIONALES (si hay más de 1)
                if (count($fotos) > 1) {
                    // Meto el resto de fotos en su propia tabla 'fotos_articulo', relacionándolas con este artículo.
                    $plantillaFotos = "INSERT INTO fotos_articulo (id_articulo, foto) VALUES (?, ?)";
                    $stmtFotos = $_conexion->prepare($plantillaFotos);
                    $stmtFotos->bind_param("ib", $id_articulo_creado, $null);
                    
                    for ($i = 1; $i < count($fotos); $i++) {
                        $stmtFotos->send_long_data(1, $fotos[$i]);
                        $stmtFotos->execute();
                    }
                    $stmtFotos->close();
                }

                // 8. TODO HA IDO BIEN
                $mensajeExito = "¡Tu artículo y sus fotos se han publicado con éxito!";
                // Limpio los campos para que el formulario vuelva a salir vacío.
                $nombreArticulo = "";
                $precio = "";
                $descripcion = "";
            } else {
                $mensajeError = "Ha ocurrido un error al subir el artículo. Por favor, revisa los datos e inténtalo de nuevo.";
            }
            $stmt->close();
        } else {
            $mensajeError = "Debes añadir al menos una foto principal.";
        }
    } elseif ($idArrendador === null) {
        $mensajeError = "No tienes permisos de arrendador. Por favor, actualiza tu perfil.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Subir Artículo | Reusarent</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 pb-safe">

    <?php include 'includes/top_nav.php'; ?>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-32">

        <div class="flex items-center gap-3 mb-6">
            <a href="index.php"
                class="text-gray-400 hover:text-brand transition-colors p-2 -ml-2 rounded-full hover:bg-gray-100">
                <i class="ph ph-x text-2xl"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Vender artículo</h1>
        </div>

        <?php if ($mensajeExito): ?>
            <div
                class="bg-[#e8f7f5] border border-brand/20 text-[#068f80] px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3">
                <i class="ph-fill ph-check-circle text-2xl mt-0.5"></i>
                <div>
                    <h4 class="font-bold">¡Publicado!</h4>
                    <p class="text-sm mt-0.5"><?= $mensajeExito ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mensajeError): ?>
            <div
                class="bg-red-50 border border-red-100 text-red-600 px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3">
                <i class="ph-fill ph-warning-circle text-2xl mt-0.5"></i>
                <div>
                    <h4 class="font-bold">Error</h4>
                    <p class="text-sm mt-0.5"><?= $mensajeError ?></p>
                </div>
            </div>
        <?php endif; ?>

        <form id="formPublicar" action="" method="post" enctype="multipart/form-data" class="space-y-6">

            <!-- El input real donde la data final se guarda para enviarse al backend. Está vacío por defecto y no es visible -->
            <input type="file" name="img-articulos[]" id="actualFileInput" class="hidden" multiple accept="image/jpeg, image/png, image/jpg">

            <!-- FOTOS -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-bold text-gray-900">Fotos (hasta 5)</h3>
                </div>
                <p class="text-sm text-gray-500 mb-4">La primera imagen será la foto principal del anuncio en el
                    escaparate.</p>

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
                            placeholder="Ej. Bici estática" required
                            value="<?= htmlspecialchars($nombreArticulo ?? '') ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Describe tu artículo</label>
                        <textarea name="descripcion" rows="4"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 px-4 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white text-sm transition-all resize-none"
                            placeholder="Menciona si tiene rayones, accesorios incluidos, etc."
                            required><?= htmlspecialchars($descripcion ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Categoría</label>
                        <div class="relative">
                            <select name="categoria"
                                class="w-full appearance-none bg-gray-50 border border-gray-200 rounded-xl py-3 pl-4 pr-10 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white text-sm transition-all"
                                required>
                                <option value="" disabled selected>Selecciona una categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat["id_categoria"]) ?>">
                                        <?= htmlspecialchars($cat["nombre"]) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i
                                class="ph ph-caret-down text-gray-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
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
                            placeholder="0.00" required value="<?= htmlspecialchars($precio ?? '') ?>">
                        <span
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-[15px]">€/día</span>
                    </div>
                </div>
            </div>

            <div
                class="fixed bottom-[calc(env(safe-area-inset-bottom)+70px)] sm:bottom-0 left-0 lg:static lg:bottom-auto w-full z-40 bg-white/90 lg:bg-transparent backdrop-blur-md pt-4 pb-4 px-4 shadow-[0_-10px_20px_rgba(0,0,0,0.05)] lg:shadow-none border-t border-gray-100 lg:border-none">
                <div class="max-w-3xl mx-auto relative lg:mr-0 lg:ml-0 lg:w-full">
                    <button type="submit"
                        class="w-full bg-brand text-white py-4 rounded-full font-bold shadow-lg shadow-brand/30 hover:bg-brand-dark transition-all active:scale-95 text-lg flex justify-center items-center gap-2">
                        <i class="ph ph-upload-simple text-xl"></i> Publicar anuncio
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
            renderPreviews(); // Pinto el botón "Añadir" inicial

            // Listener del input del navegador
            document.getElementById('browserFileInput').addEventListener('change', handleFiles);

            // Validacion final antes de ENVIAR form a PHP
            document.getElementById('formPublicar').addEventListener('submit', (e) => {
                if (selectedFiles.length === 0) {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    Swal.fire({ icon: 'warning', title: 'Atención', text: 'Añade al menos una foto principal del artículo.', confirmButtonColor: '#09b6a2' });
                }
            });
        });

        function triggerFileInput() {
            document.getElementById('browserFileInput').click();
        }

        async function handleFiles(event) {
            const files = Array.from(event.target.files);
            event.target.value = ''; // Limpiamos para poder elegir el mismo si quisieran

            const slotsRestantes = 5 - selectedFiles.length;
            if (files.length > slotsRestantes) {
                Swal.fire({ icon: 'error', title: 'Límite excedido', text: `Solo puedes subir un máximo de 5 fotos. Has intentado sumar ${files.length} a tus ${selectedFiles.length} fotos, lo que te pasaría del límite.`, confirmButtonColor: '#09b6a2' });
            }

            // Agarramos nomas los que quepan
            const filesPermitidos = files.slice(0, slotsRestantes);

            // Validacion de imagen, compresión y push
            document.body.style.opacity = '0.7'; // Indicador de carga
            document.body.style.pointerEvents = 'none';
            
            for (let f of filesPermitidos) {
                if (f.type.startsWith('image/')) {
                    try {
                        const compressedFile = await compressImageFile(f);
                        selectedFiles.push(compressedFile);
                    } catch (e) {
                        console.error('Error comprimiendo foto:', e);
                    }
                }
            }
            
            document.body.style.opacity = '1';
            document.body.style.pointerEvents = 'auto';

            renderPreviews();
            updateInputFiles();
        }

        // Compresor mágico de JS para saltarse los límites del servidor y problemas de iPhone
        function compressImageFile(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = new Image();
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        
                        // Máximo de 1200px para mantener calidad pero reducir peso drásticamente
                        if (width > height && width > 1200) {
                            height *= 1200 / width;
                            width = 1200;
                        } else if (height > 1200) {
                            width *= 1200 / height;
                            height = 1200;
                        }
                        
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        canvas.toBlob(function(blob) {
                            const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + "_opt.jpg", { type: "image/jpeg" });
                            resolve(newFile);
                        }, 'image/jpeg', 0.8);
                    };
                    img.onerror = reject;
                    img.src = event.target.result;
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            renderPreviews();
            updateInputFiles();
        }

        // Dibuja las vistas
        function renderPreviews() {
            const container = document.getElementById('preview-container');
            container.innerHTML = ''; // wipe UI

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

                const badgeHtml = index === 0 ? '<span class="absolute bottom-1 left-1 bg-black/60 text-white text-[9px] px-1.5 rounded uppercase font-bold z-10 pointer-events-none">Port.</span>' : '';

                imgDiv.appendChild(imgEl);
                imgDiv.appendChild(removeBtn);
                if (index === 0) imgDiv.insertAdjacentHTML('beforeend', badgeHtml);

                container.appendChild(imgDiv);
            });

            // "Añadir más" si aun no hay 5
            if (selectedFiles.length < 5) {
                const addMoreBox = document.createElement('div');
                addMoreBox.className = "w-24 h-24 md:w-28 md:h-28 flex-shrink-0 border-2 border-dashed border-gray-300 hover:border-brand hover:scale-[1.02] bg-gray-50 rounded-2xl flex flex-col justify-center items-center cursor-pointer transition-all relative overflow-hidden group";
                addMoreBox.onclick = triggerFileInput;
                addMoreBox.innerHTML = '<i class="ph ph-plus text-3xl text-gray-400 group-hover:text-brand mb-1"></i><span class="block text-xs font-medium text-gray-500 group-hover:text-brand px-2 text-center leading-tight">Añadir otra</span>';
                container.appendChild(addMoreBox);
            }
        }

        // Pasa las cosas al input verdader hidden para PHP POST
        function updateInputFiles() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            document.getElementById('actualFileInput').files = dt.files;
        }
    </script>
</body>

</html>