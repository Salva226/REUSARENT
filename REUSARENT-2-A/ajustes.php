<?php
// --- ARCHIVO: ajustes.php ---
// Pantalla donde el usuario puede modificar su perfil: foto, contraseña, email, teléfono, etc.

session_start();
require "conexion/conexion.php";

// 1. COMPROBAR SESIÓN
if (!isset($_SESSION["usuario"])) {
    header("location: conexion/login.php");
    exit();
}

$usuario = $_SESSION["usuario"];

// 2. OBTENER DATOS ACTUALES
// Saco los datos del usuario para rellenar el formulario antes de que empiece a escribir.
$consulta = "SELECT * FROM usuario WHERE usuario = '$usuario'";
$resultado = $_conexion->query($consulta);
$datos = $resultado->fetch_assoc();

// 3. ACTUALIZAR DATOS PERSONALES (Formulario normal)
// Si le ha dado al botón de guardar (name="guardar").
if (isset($_POST["guardar"])) {
    $telefono = $_POST["telefono"];
    $email = $_POST["email"];
    $direccion = $_POST["direccion"];

    $update = "UPDATE usuario 
               SET telefono='$telefono', email='$email', direccion_facturacion='$direccion'
               WHERE usuario='$usuario'";

    $_conexion->query($update);
    // Recargo la página para que vea los cambios reflejados.
    header("Location: ajustes.php");
}

// 4. CAMBIAR CONTRASEÑA
if (isset($_POST["password"])) {
    $pass1 = $_POST["pass1"];
    $pass2 = $_POST["pass2"];

    if ($pass1 == $pass2) {
        // Encriptamos la nueva contraseña antes de guardarla.
        $passHash = password_hash($pass1, PASSWORD_DEFAULT);
        $_conexion->query("UPDATE usuario SET password='$passHash' WHERE usuario='$usuario'");
        $mensaje = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-sm font-medium'>Contraseña actualizada</div>";
    } else {
        $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm font-medium'>Las contraseñas no coinciden</div>";
    }
}

// 5. SUBIR FOTO DE PERFIL
if (isset($_POST["foto"])) {
    if ($_FILES["imagen"]["error"] == 0) {
        $tipoArchivo = $_FILES["imagen"]["type"];
        
        // Validación extra: me aseguro de que de verdad están subiendo una imagen y no un virus oculto (.php, .exe).
        if (strpos($tipoArchivo, "image/") === 0) { 
            $contenidoFoto = file_get_contents($_FILES["imagen"]["tmp_name"]);
            
            // Usamos Prepared Statements aquí.
            // Porque subir una imagen (BLOB) en una consulta de texto puede romper MySQL o dar problemas de caracteres.
            $stmt = $_conexion->prepare("UPDATE usuario SET foto_perfil = ? WHERE usuario = ?");
            if ($stmt) {
                // 'b' significa BLOB (datos binarios grandes), 's' significa string.
                $stmt->bind_param("bs", $null, $usuario);
                // Le pasamos los datos binarios a la 'b'
                $stmt->send_long_data(0, $contenidoFoto);
                if ($stmt->execute()) {
                    header("Location: ajustes.php");
                    exit();
                }
                $stmt->close();
            }
        } else {
            $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm font-medium'>El formato no es válido.</div>";
        }
    } elseif ($_FILES["imagen"]["error"] == UPLOAD_ERR_INI_SIZE || $_FILES["imagen"]["error"] == UPLOAD_ERR_FORM_SIZE) {
        // Errores estándar de PHP si la foto pesa más de lo que permite el 'php.ini' del servidor
        $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm font-medium'>La foto es demasiado grande para este servidor.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Ajustes | Reusarent</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 pb-safe">

    <?php include 'includes/top_nav.php'; ?>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <div class="flex items-center gap-3 mb-6">
            <a href="dashboard.php"
                class="text-gray-400 hover:text-brand transition-colors p-2 -ml-2 rounded-full hover:bg-gray-100">
                <i class="ph ph-arrow-left text-2xl"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Ajustes</h1>
        </div>

        <!-- FOTO PERFIL -->
        <div
            class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 mb-6 text-center">
            <?php
            if (!empty($datos["foto_perfil"])) {
                $fotoBase64 = base64_encode($datos["foto_perfil"]);
                $imagenSrc = "data:image/jpeg;base64," . $fotoBase64;
            } else {
                $imagenSrc = "img/perfiles/default.png";
            }
            ?>
            <div class="relative inline-block mb-4 group">
                <img src="<?php echo $imagenSrc; ?>"
                    class="w-32 h-32 rounded-full object-cover border-4 border-gray-50 shadow-sm" alt="Foto de perfil">
                <form method="POST" enctype="multipart/form-data" class="absolute bottom-0 right-0" id="fotoForm">
                    <label for="imagenInput"
                        class="w-10 h-10 bg-brand text-white rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:bg-brand-dark transition-transform active:scale-90">
                        <i class="ph ph-camera text-xl"></i>
                    </label>
                    <input type="file" name="imagen" id="imagenInput" class="hidden" accept="image/*" required>
                    <button type="submit" name="foto" id="btnSubmitFoto" class="hidden"></button>
                </form>
            </div>
            
            <script>
            // MAGIA ANTIBLOQUEOS DE IPHONE: Comprimir imagen antes de subir
            document.getElementById('imagenInput').addEventListener('change', function(e) {
                if (!e.target.files || !e.target.files.length) return;
                
                // Mostrar un pequeño estado de carga
                document.getElementById('imagenInput').parentElement.style.opacity = '0.5';
                
                const file = e.target.files[0];
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = new Image();
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        
                        // Reducir la imagen a un máximo de 800px (Suficiente para perfil)
                        if (width > height && width > 800) {
                            height *= 800 / width;
                            width = 800;
                        } else if (height > 800) {
                            width *= 800 / height;
                            height = 800;
                        }
                        
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        // Convertir a JPEG ligero
                        canvas.toBlob(function(blob) {
                            const newFile = new File([blob], "perfil_optimizado.jpg", { type: "image/jpeg" });
                            const dt = new DataTransfer();
                            dt.items.add(newFile);
                            document.getElementById('imagenInput').files = dt.files;
                            
                            // Subir al servidor
                            document.getElementById('fotoForm').submit();
                        }, 'image/jpeg', 0.8); // 80% de calidad, pesa muy poco
                    };
                    img.src = event.target.result;
                };
                reader.readAsDataURL(file);
            });
            </script>
            <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($datos["usuario"]); ?></h2>
            <p class="text-gray-500 text-sm mt-1">Sube una foto para que los demás te reconozcan</p>
        </div>

        <!-- DATOS PERSONALES -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
                <i class="ph-fill ph-user-circle text-brand text-xl"></i> Datos Personales
            </h3>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de usuario</label>
                    <input type="text"
                        class="w-full bg-gray-100 border-transparent rounded-xl py-3 px-4 text-gray-500 cursor-not-allowed text-sm"
                        value="<?php echo htmlspecialchars($datos["usuario"]); ?>" disabled>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="telefono"
                        class="w-full bg-white border border-gray-200 rounded-xl py-3 px-4 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand text-sm transition-all"
                        value="<?php echo htmlspecialchars($datos["telefono"]); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                    <input type="email" name="email"
                        class="w-full bg-white border border-gray-200 rounded-xl py-3 px-4 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand text-sm transition-all"
                        value="<?php echo htmlspecialchars($datos["email"]); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección de facturación</label>
                    <input type="text" name="direccion"
                        class="w-full bg-white border border-gray-200 rounded-xl py-3 px-4 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand text-sm transition-all"
                        value="<?php echo htmlspecialchars($datos["direccion_facturacion"]); ?>">
                </div>

                <div class="pt-2">
                    <button type="submit" name="guardar"
                        class="w-full sm:w-auto px-8 py-3 bg-gray-900 text-white rounded-full font-semibold hover:bg-black transition-transform active:scale-95 shadow-sm text-sm">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>

        <!-- CONTRASEÑA -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_2px_12px_rgba(0,0,0,0.03)] border border-gray-100 mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
                <i class="ph-fill ph-lock-key text-gray-600 text-xl"></i> Seguridad
            </h3>

            <?php if (isset($mensaje))
                echo $mensaje; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
                    <input type="password" name="pass1"
                        class="w-full bg-white border border-gray-200 rounded-xl py-3 px-4 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand text-sm transition-all"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Repetir contraseña</label>
                    <input type="password" name="pass2"
                        class="w-full bg-white border border-gray-200 rounded-xl py-3 px-4 text-gray-800 focus:ring-2 focus:ring-brand/20 focus:border-brand text-sm transition-all"
                        required>
                </div>

                <div class="pt-2">
                    <button type="submit" name="password"
                        class="w-full sm:w-auto px-8 py-3 bg-red-50 text-red-600 rounded-full font-semibold hover:bg-red-100 transition-transform active:scale-95 shadow-sm text-sm">
                        Actualizar contraseña
                    </button>
                </div>
            </form>
        </div>

    </main>

    <br class="hidden sm:block">
    <?php include 'includes/bottom_nav.php'; ?>

</body>

</html>