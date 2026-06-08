<?php
// --- ARCHIVO: signup.php ---
// --- 1. LÓGICA DE REGISTRO PHP (SOLO SE EJECUTA SI JAVASCRIPT DA PERMISO) ---
// Activo los errores para ver si algo falla mientras programo.
error_reporting(E_ALL);
ini_set("display_errors", 1);

require "conexion.php";
require "../funciones/depurar.php"; // Archivo con funciones propias para limpiar inputs.

$mensaje_servidor = ""; // Variable donde guardaré el mensaje de éxito o de error.

// Compruebo si el usuario le ha dado al botón de registrarse (POST).
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Limpio los datos usando las funciones de 'depurar.php' para evitar inyección de código.
    // El '?? ""' es por si acaso algún campo viene nulo, que le asigne un string vacío.
    $usuario = depurarUsuario($_POST["usuario"] ?? "");
    $dni = depurar($_POST["dni"] ?? "");
    $telefono = depurar($_POST["telefono"] ?? "");
    $email = $_POST["email"] ?? "";
    $passw = depurarContrasena($_POST["contrasena"] ?? "");

    // Si todo está lleno (es decir, ningún campo se ha quedado en blanco), voy a guardar.
    if ($usuario != "" && $dni != "" && $telefono != "" && $email != "" && $passw != "") {

        // Ciframos la contraseña SIEMPRE antes de meterla en la base de datos.
        // Nunca, jamás se guardan contraseñas en texto plano.
        $passw_cifrada = password_hash($passw, PASSWORD_DEFAULT);

        // Preparo la orden SQL para crear al nuevo usuario. Por defecto, le pongo el rol 'no-premium'.
        $consulta = "INSERT INTO usuario (DNI, usuario, contrasena, rol, telefono, direccion_facturacion, email) 
                     VALUES ('$dni', '$usuario', '$passw_cifrada', 'no-premium', '$telefono', null, '$email')";

        // Ejecuto la orden.
        if ($_conexion->query($consulta)) {
            // Si el usuario se crea bien, también inserto su DNI en las tablas 'arrendador' y 'arrendatario'.
            // Esto es porque en Reusarent todo el mundo puede tanto alquilar como subir artículos.
            $_conexion->query("INSERT INTO arrendador (DNI) VALUES ('$dni')");
            $_conexion->query("INSERT INTO arrendatario (DNI) VALUES ('$dni')");
            
            // Guardo un mensaje HTML bonito de éxito.
            $mensaje_servidor = "<div class='bg-[#e8f7f5] border border-brand/20 text-[#068f80] px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3'><i class='ph-fill ph-check-circle text-2xl mt-0.5'></i><div><h4 class='font-bold'>¡Registro completado!</h4><p class='text-sm mt-0.5'>Tu cuenta ha sido creada correctamente. Ya puedes <a href='login.php' class='underline font-bold'>iniciar sesión</a>.</p></div></div>";
        } else {
            // Si algo falla (por ejemplo, clave primaria duplicada), devuelvo un error.
            if ($_conexion->errno === 1062) {
                $mensaje_servidor = "<div class='bg-red-50 border border-red-100 text-red-600 px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3'><i class='ph-fill ph-warning-circle text-2xl mt-0.5'></i><div><h4 class='font-bold'>Error</h4><p class='text-sm mt-0.5'>Ese DNI o nombre de usuario ya están registrados.</p></div></div>";
            } else {
                $mensaje_servidor = "<div class='bg-red-50 border border-red-100 text-red-600 px-4 py-4 rounded-2xl mb-6 shadow-sm flex items-start gap-3'><i class='ph-fill ph-warning-circle text-2xl mt-0.5'></i><div><h4 class='font-bold'>Error</h4><p class='text-sm mt-0.5'>Ocurrió un error al procesar tu registro. Por favor, inténtalo de nuevo.</p></div></div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reusarent | Registro</title>
    <link rel="icon" href="../img/logo.PNG">

    <!-- Iconos Phosphor -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- CSS de Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Fuentes de Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'], },
                    colors: { brand: { DEFAULT: '#09b6a2', dark: '#068f80' } }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .error-js {
            color: #ef4444;
            /* red-500 */
            font-size: 0.75rem;
            margin-top: 0.375rem;
            display: none;
            font-weight: 500;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50 flex flex-col sm:justify-center items-center p-4 py-10">

    <div class="mb-8 text-center">
        <a href="../index.php" class="flex flex-col items-center gap-3 group">
            <img src="../img/logo.PNG"
                class="w-16 h-16 rounded-2xl shadow-sm group-hover:scale-105 transition-transform" alt="Logo">
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">REUSARENT</h1>
        </a>
    </div>

    <div
        class="w-full max-w-lg bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:p-10 p-6 border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-900 mb-2 text-center">Crea tu cuenta</h2>

        <?php echo $mensaje_servidor; ?>

        <form id="formRegistro" action="" method="post" class="space-y-5">

            <div>
                <label for="usuario" class="block text-sm font-semibold text-gray-700 mb-1.5">Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ej. Juan Pérez" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 px-4 text-gray-900 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white transition-all text-sm outline-none">
                <div id="err-usuario" class="error-js"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="dni" class="block text-sm font-semibold text-gray-700 mb-1.5">DNI</label>
                    <input type="text" id="dni" name="dni" placeholder="12345678W" required
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 px-4 text-gray-900 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white transition-all text-sm outline-none">
                    <div id="err-dni" class="error-js"></div>
                </div>

                <div>
                    <label for="telefono" class="block text-sm font-semibold text-gray-700 mb-1.5">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" placeholder="687544950" required
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 px-4 text-gray-900 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white transition-all text-sm outline-none">
                    <div id="err-telefono" class="error-js"></div>
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Correo electrónico</label>
                <input type="email" id="email" name="email" placeholder="nombre@ejemplo.com" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 px-4 text-gray-900 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white transition-all text-sm outline-none">
                <div id="err-email" class="error-js"></div>
            </div>

            <div>
                <label for="contrasena" class="block text-sm font-semibold text-gray-700 mb-1.5">Contraseña</label>
                <div class="relative">
                    <input type="password" id="contrasena" name="contrasena" placeholder="••••••••" required
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 px-4 text-gray-900 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white transition-all text-sm outline-none pr-12">
                    <button type="button" id="togglePassword"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="ph ph-eye text-xl"></i>
                    </button>
                </div>
                <div id="err-contrasena" class="error-js"></div>
                <p class="text-[11px] text-gray-500 mt-2">Mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1
                    especial.</p>
            </div>

            <div class="flex items-start gap-3 pt-2">
                <input type="checkbox" id="terminos" name="terminos" required
                    class="mt-1 w-4 h-4 text-brand bg-gray-100 border-gray-300 rounded focus:ring-brand focus:ring-2">
                <label for="terminos" class="text-sm text-gray-600 leading-snug">
                    Acepto los <a href="#" class="text-brand font-semibold hover:underline">términos y condiciones</a> y
                    la <a href="#" class="text-brand font-semibold hover:underline">política de privacidad</a>.
                </label>
            </div>

            <div class="pt-4">
                <button type="submit"
                    class="w-full bg-brand text-white py-3.5 rounded-full font-bold shadow-lg shadow-brand/30 hover:bg-brand-dark transition-all active:scale-95 text-[15px]">
                    Crear cuenta
                </button>
            </div>
        </form>

        <div class="mt-8 text-center text-sm text-gray-500 font-medium">
            ¿Ya tienes cuenta? <a href="login.php" class="text-brand hover:underline font-bold">Inicia sesión</a>
        </div>
    </div>

    <script>
        // --- SCRIPTS DE JAVASCRIPT ---
        
        // 1. Mostrar/Ocultar contraseña (el icono del ojito)
        const toggleBtn = document.getElementById('togglePassword');
        const passInput = document.getElementById('contrasena');
        const toggleIcon = toggleBtn.querySelector('i');

        toggleBtn.addEventListener('click', () => {
            // Si el input es de tipo password (bolitas), lo paso a texto y cambio el icono al ojo tachado.
            if (passInput.type === 'password') {
                passInput.type = 'text';
                toggleIcon.classList.remove('ph-eye');
                toggleIcon.classList.add('ph-eye-slash');
            } else {
                // Si no, lo devuelvo a password y pongo el ojito normal.
                passInput.type = 'password';
                toggleIcon.classList.remove('ph-eye-slash');
                toggleIcon.classList.add('ph-eye');
            }
        });

        // 2. Validación del formulario antes de enviarlo a PHP
        // DOMContentLoaded asegura que el HTML ha cargado entero antes de ejecutar el script.
        document.addEventListener("DOMContentLoaded", function () {
            const formulario = document.getElementById("formRegistro");

            // Intercepto el momento en que el usuario le da a "Crear cuenta"
            formulario.addEventListener("submit", async function (evento) {
                // Freno el envío del formulario temporalmente
                evento.preventDefault();

                // Limpio todos los mensajes de error antiguos que estuvieran visibles
                document.querySelectorAll(".error-js").forEach(div => {
                    div.style.display = "none";
                    div.textContent = "";
                });

                // Recojo los valores de los inputs. Uso trim() para quitar espacios inútiles al principio y final.
                const usuario = document.getElementById("usuario").value.trim();
                const dni = document.getElementById("dni").value.trim().toUpperCase();
                const telefono = document.getElementById("telefono").value.trim();
                const email = document.getElementById("email").value.trim();
                const password = document.getElementById("contrasena").value;

                let hayErrores = false; // Bandera para saber si puedo o no continuar

                // Función auxiliar que pilla el div de error correspondiente y le pone el mensaje.
                function mostrarError(idElemento, mensaje) {
                    const divError = document.getElementById(idElemento);
                    divError.textContent = mensaje;
                    divError.style.display = "block";
                    hayErrores = true;
                }

                // --- A. COMPROBACIONES DE FORMATO CON EXPRESIONES REGULARES (REGEX) ---
                
                // Regex para el DNI: 8 números y 1 letra mayúscula
                const regexDNI = /^[0-9]{8}[A-Z]$/;
                if (!regexDNI.test(dni)) {
                    mostrarError("err-dni", "DNI inválido (Ej: 12345678W).");
                } else {
                    // Si el formato está bien, compruebo si la letra del DNI tiene sentido matemático
                    const numeroDNI = dni.substring(0, 8);
                    const letraDNI = dni.charAt(8);
                    const letrasValidas = "TRWAGMYFPDXBNJZSQVHLCKE";
                    // El algoritmo español del DNI es coger el número, dividirlo entre 23 y mirar el resto
                    if (letraDNI !== letrasValidas.charAt(numeroDNI % 23)) {
                        mostrarError("err-dni", "La letra del DNI es incorrecta.");
                    }
                }

                // Regex para Teléfono: que empiece por 6, 7, 8 o 9, y tenga 8 números más detrás
                if (!/^[6789]\d{8}$/.test(telefono)) {
                    mostrarError("err-telefono", "Debe ser un teléfono español válido.");
                }

                // Regex para Email: algo, una arroba, algo, un punto, y algo.
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    mostrarError("err-email", "Formato de correo electrónico inválido.");
                }

                // Regex para Contraseña: Mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 símbolo especial.
                if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(password)) {
                    mostrarError("err-contrasena", "La contraseña no cumple los requisitos.");
                }

                // Si detecto un solo error en formato, corto aquí y no hago la petición al servidor.
                if (hayErrores) return;

                // --- B. COMPROBAR POR AJAX SI EL USUARIO YA EXISTE ---
                try {
                    // Empaqueto el usuario y el email como si fueran un formulario virtual
                    const formData = new FormData();
                    formData.append("usuario", usuario);
                    formData.append("email", email);
                    formData.append("dni", dni);

                    // Hago la llamada en segundo plano (Fetch) a comprobar_datos.php
                    // Uso await para esperar a que el servidor conteste.
                    const respuesta = await fetch("comprobar_datos.php", {
                        method: "POST",
                        body: formData
                    });

                    // Parseo lo que me escupe PHP (que viene en formato JSON) a un objeto JS.
                    const datos = await respuesta.json();

                    // Compruebo las flags del JSON
                    if (datos.usuarioExiste) {
                        mostrarError("err-usuario", "El usuario ya existe. Elige otro.");
                    }
                    if (datos.emailExiste) {
                        mostrarError("err-email", "Ese correo ya está registrado.");
                    }
                    if (datos.dniExiste) {
                        mostrarError("err-dni", "Ese DNI ya está registrado en el sistema.");
                    }

                    // Si llego hasta aquí y NO hay errores, entonces sí, envío el formulario de verdad a signup.php
                    if (!datos.usuarioExiste && !datos.emailExiste && !datos.dniExiste) {
                        formulario.submit();
                    }

                } catch (error) {
                    console.error("Error Fetch AJAX:", error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al conectar con la base de datos.', confirmButtonColor: '#09b6a2' });
                }
            });
        });
    </script>
</body>

</html>