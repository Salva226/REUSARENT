<?php
// --- ARCHIVO: accion_editar_articulo.php ---
// Script "invisible" que se ejecuta al darle al botón "Guardar cambios" al editar un artículo.

session_start();
require "conexion/conexion.php";

// 1. SEGURIDAD BÁSICA: Comprobar sesión y que manden el formulario por POST
if (!isset($_SESSION["usuario"]) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_articulo'])) {
    header("Location: index.php");
    exit();
}

$id_articulo = $_conexion->real_escape_string($_POST['id_articulo']);
$nombreArticulo = trim($_POST["nombre"]);
$descripcion = trim($_POST["descripcion"]);
$precio = trim($_POST["precio"]);
$idCategoria = trim($_POST["categoria"]);
$usuario = $_SESSION['usuario'];

// 2. OBTENER DNI DEL USUARIO LOGUEADO
$resMio = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$usuario'");
if (!$resMio || $resMio->num_rows === 0) {
    header("Location: index.php");
    exit();
}
$miDNI = $resMio->fetch_assoc()['DNI'];

// 3. VERIFICAR QUE EL USUARIO ES EL DUEÑO DEL ARTÍCULO (Prevención ante posibles hackeos que puedan suceder).
$consultaDueño = "SELECT u.DNI 
                  FROM articulo art
                  INNER JOIN arrendador a ON art.id_arrendador = a.id_arrendador
                  INNER JOIN usuario u ON a.DNI = u.DNI
                  WHERE art.id_articulo = '$id_articulo'";
$resDueño = $_conexion->query($consultaDueño);

// Si no soy el dueño, fuera de aquí.
if (!$resDueño || $resDueño->num_rows === 0 || $resDueño->fetch_assoc()['DNI'] !== $miDNI) {
    header("Location: index.php");
    exit();
}

// 4. EXTRAER FOTOS NUEVAS (Si el usuario ha subido alguna).
$fotos = [];
if (isset($_FILES["img-articulos"]) && is_array($_FILES["img-articulos"]["name"])) {
    foreach ($_FILES["img-articulos"]["tmp_name"] as $key => $tmp_name) {
        if ($_FILES["img-articulos"]["error"][$key] == 0 && file_exists($tmp_name)) {
            $fotos[] = file_get_contents($tmp_name); // Convierto la imagen temporal a un chorro de bytes
        }
    }
}

// 5. INICIAR TRANSACCIÓN SQL
// Por si algo falla subiendo las fotos, que no se guarde el título a medias.
$_conexion->begin_transaction();

try {
    // 6. ACTUALIZAR BASE DE DATOS
    
    // OPCIÓN A: Ha subido fotos nuevas
    if (count($fotos) > 0) {
        $fotoPrincipal = $fotos[0];
        
        // Uso Prepared Statements (?) para evitar inyección de código al guardar fotos gigantes.
        $plantilla = "UPDATE articulo SET nombre = ?, descripcion = ?, precio = ?, id_categoria = ?, foto = ? WHERE id_articulo = ?";
        $stmt = $_conexion->prepare($plantilla);
        // "ssdisi" -> string, string, double, integer, string (blob), integer
        $stmt->bind_param("ssdisi", $nombreArticulo, $descripcion, $precio, $idCategoria, $fotoPrincipal, $id_articulo);
        $stmt->execute();
        $stmt->close();
        
        // Como ha subido fotos nuevas, borro las fotos extra antiguas.
        $_conexion->query("DELETE FROM fotos_articulo WHERE id_articulo = '$id_articulo'");
        
        // Y si ha subido más de una foto, las inserto como fotos extra nuevas.
        if (count($fotos) > 1) {
            $plantillaFotos = "INSERT INTO fotos_articulo (id_articulo, foto) VALUES (?, ?)";
            $stmtFotos = $_conexion->prepare($plantillaFotos);
            for ($i = 1; $i < count($fotos); $i++) {
                $stmtFotos->bind_param("is", $id_articulo, $fotos[$i]);
                $stmtFotos->execute();
            }
            $stmtFotos->close();
        }
        
    } else {
        // OPCIÓN B: NO ha subido fotos nuevas (solo ha cambiado título, precio, etc.)
        // Actualizo todo menos la columna `foto`.
        $plantilla = "UPDATE articulo SET nombre = ?, descripcion = ?, precio = ?, id_categoria = ? WHERE id_articulo = ?";
        $stmt = $_conexion->prepare($plantilla);
        $stmt->bind_param("ssdii", $nombreArticulo, $descripcion, $precio, $idCategoria, $id_articulo);
        $stmt->execute();
        $stmt->close();
    }

    // Si todo ha ido bien, cerramos la transacción con éxito (commit).
    $_conexion->commit();
    
    // Le devuelvo a la página del artículo con un mensajito de éxito.
    $nombreArticuloParaUrl = urlencode($nombreArticulo);
    header("Location: info_articulos.php?articulo=$nombreArticuloParaUrl&exito_edicion=1");
    exit();
    
} catch (Exception $e) {
    // Si ha saltado un error SQL de la base de datos, cancelamos todo (rollback)
    $_conexion->rollback();
    header("Location: editar_articulo.php?id=$id_articulo&error_edicion=1");
    exit();
}
?>
