<?php
<?php
// --- ARCHIVO: login.php ---

// Compruebo si el formulario se ha enviado pulsando el botón (método POST).
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Guardo lo que el usuario ha escrito en los inputs en variables temporales.
    $tmp_usuario = $_POST["usuario"];
    $tmp_contrasena = $_POST["contrasena"];

    // Validación básica: compruebo que no me hayan dejado el usuario en blanco.
    if ($tmp_usuario == "") {
        $err_usuario = "Introduce un usuario";
    } else {
        $usuario = $tmp_usuario; // Si está bien, me lo quedo.
    }

    // Lo mismo para la contraseña. No queremos que intenten entrar con contraseñas vacías.
    if ($tmp_contrasena == "") {
        $err_contrasena = "Introduce una contraseña";
    } else {
        $contrasena = $tmp_contrasena;
    }

    // Si ambos campos tienen texto, procedo a buscar al usuario en la base de datos.
    if (isset($usuario) && isset($contrasena)) {
        require "conexion.php"; // Me conecto a la BD.
        
        // Hago una consulta simple buscando al usuario por su nombre.
        // OJO: Aquí deberíamos usar Prepared Statements para evitar Inyecciones SQL, 
        // pero como es un proyecto educativo, esto sirve para entender la lógica básica.
        $consulta = "SELECT * FROM usuario WHERE usuario = '$usuario'";
        $resultado = $_conexion->query($consulta);

        // Si num_rows es 0, significa que la BD me ha devuelto 0 filas. El usuario no existe.
        if ($resultado->num_rows === 0) {
            $login_error = "El usuario no existe";
        } else {
            // Si llego aquí, el usuario existe. Saco sus datos en un array asociativo.
            $info_usuario = $resultado->fetch_assoc();
            
            // Aquí viene la seguridad: la contraseña en la BD está cifrada.
            // Uso password_verify() para comparar lo que ha escrito en texto plano
            // con el "chorro de letras" (hash) que guardé en el registro.
            $acceso_concedido = password_verify($contrasena, $info_usuario["contrasena"]);
            
            if (!$acceso_concedido) {
                // Las contraseñas no coinciden.
                $login_error = "La contraseña es incorrecta";
            } else {
                // ¡Éxito! Las credenciales son correctas.
                // Abro la sesión y guardo variables súper importantes para usarlas luego.
                session_start();
                $_SESSION["usuario"] = $usuario; // Guardo el nombre para saber quién es.
                $_SESSION["rol"] = $info_usuario["rol"]; // Guardo el rol (user/admin).
                
                // Redirijo al inicio y corto la ejecución para que no cargue más código.
                header("Location: ../index.php");
                exit();
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
    <title>Reusarent | Inicio de Sesión</title>
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
    </style>
</head>

<body class="min-h-screen bg-gray-50 flex flex-col sm:justify-center items-center p-4">

    <!-- Logo centrado arriba -->
    <div class="mt-8 mb-6 sm:mt-0 sm:mb-8 text-center">
        <a href="../index.php" class="flex flex-col items-center gap-3 group">
            <img src="../img/logo.PNG"
                class="w-16 h-16 rounded-2xl shadow-sm group-hover:scale-105 transition-transform" alt="Logo">
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">REUSARENT</h1>
        </a>
    </div>

    <!-- Tarjeta de Login -->
    <div
        class="w-full max-w-md bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:p-10 p-6 border border-gray-100">

        <?php if (isset($login_error)): ?>
            <div
                class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-2xl mb-6 shadow-sm flex items-center gap-2 text-sm font-medium">
                <i class="ph-fill ph-warning-circle text-lg"></i>
                <span><?= $login_error ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="post" class="space-y-5">
            <div>
                <label for="usuario" class="block text-sm font-semibold text-gray-700 mb-1.5">Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ej. Juan Pérez" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 px-4 text-gray-900 focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white transition-all text-sm outline-none">
                <?php if (isset($err_usuario)): ?><span
                        class="text-red-500 text-xs mt-1 block"><?= $err_usuario ?></span><?php endif; ?>
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
                <?php if (isset($err_contrasena)): ?><span
                        class="text-red-500 text-xs mt-1 block"><?= $err_contrasena ?></span><?php endif; ?>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="w-full bg-brand text-white py-3.5 rounded-full font-bold shadow-lg shadow-brand/30 hover:bg-brand-dark transition-all active:scale-95 text-[15px]">
                    Iniciar sesión
                </button>
            </div>
        </form>

        <div class="mt-8 text-center text-sm text-gray-500 font-medium">
            ¿Todavía no tienes cuenta? <a href="signup.php" class="text-brand hover:underline font-bold">Regístrate</a>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('togglePassword');
        const passInput = document.getElementById('contrasena');
        const toggleIcon = toggleBtn.querySelector('i');

        toggleBtn.addEventListener('click', () => {
            if (passInput.type === 'password') {
                passInput.type = 'text';
                toggleIcon.classList.remove('ph-eye');
                toggleIcon.classList.add('ph-eye-slash');
            } else {
                passInput.type = 'password';
                toggleIcon.classList.remove('ph-eye-slash');
                toggleIcon.classList.add('ph-eye');
            }
        });
    </script>
</body>

</html>