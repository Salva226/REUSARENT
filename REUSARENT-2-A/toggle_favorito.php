<?php
// --- ARCHIVO: toggle_favorito.php ---
// Script API (JSON) que se llama sin recargar la página cuando le das al corazoncito en un artículo.
// Se llama "toggle" porque actúa como un interruptor: si no es favorito lo añade, y si ya lo es, lo quita.

session_start();
// Le decimos al navegador que vamos a devolver JSON para que JavaScript lo entienda bien.
header('Content-Type: application/json');

// 1. SEGURIDAD
if (!isset($_SESSION["usuario"])) {
    echo json_encode(['error' => 'No session']);
    exit;
}

require "conexion/conexion.php";

// 2. LEER DATOS (Fetch)
// Leemos el ID del artículo que nos manda JavaScript en formato JSON.
$data = json_decode(file_get_contents('php://input'), true);
$id_articulo = $data['id_articulo'] ?? null;

if (!$id_articulo) {
    echo json_encode(['error' => 'No article attached']);
    exit;
}

// 3. IDENTIFICAR AL USUARIO
$usuario = $_SESSION["usuario"];
$resDNI = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$usuario'");

if ($resDNI && $resDNI->num_rows > 0) {
    $dni = $resDNI->fetch_assoc()['DNI'];

    // 4. COMPROBAR ESTADO ACTUAL (El interruptor)
    $check = $_conexion->query("SELECT * FROM favorito WHERE id_articulo = '$id_articulo' AND DNI = '$dni'");

    if ($check && $check->num_rows > 0) {
        // OPCIÓN A: Ya era favorito. Lo quito de la base de datos.
        $_conexion->query("DELETE FROM favorito WHERE id_articulo = '$id_articulo' AND DNI = '$dni'");
        // Le digo a JS que lo he quitado para que el corazón se ponga vacío.
        echo json_encode(['status' => 'removed']);
    } else {
        // OPCIÓN B: No era favorito. Lo añado a la base de datos.
        $_conexion->query("INSERT INTO favorito (id_articulo, DNI) VALUES ('$id_articulo', '$dni')");
        // Le digo a JS que lo he añadido para que el corazón se pinte de rojo.
        echo json_encode(['status' => 'added']);
    }
} else {
    // Si algo raro pasa con el DNI del usuario (muy raro).
    echo json_encode(['error' => 'Internal error getting user DNI']);
}
?>