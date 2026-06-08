<?php
// --- ARCHIVO: enviar_mensaje.php ---
// Este script NO devuelve una página web (HTML), sino que es una API (devuelve JSON).
// Se llama por debajo usando AJAX/Fetch desde JavaScript cuando alguien le da a "Enviar" en el chat.

session_start();
// Le decimos al navegador que lo que vamos a devolver es formato JSON, no HTML normal.
header('Content-Type: application/json');

// 1. SEGURIDAD
if (!isset($_SESSION["usuario"])) {
    echo json_encode(['error' => 'No session']);
    exit();
}

require "conexion/conexion.php";

// 2. LEER DATOS QUE LLEGAN POR FETCH (JSON)
// En vez de usar $_POST (porque JavaScript lo mandó como JSON), usamos file_get_contents.
$data = json_decode(file_get_contents('php://input'), true);
$id_conversacion = $data['id_conversacion'] ?? null;
$texto = trim($data['texto'] ?? '');

// Si faltan datos o el texto está vacío, devuelvo un error en JSON.
if (!$id_conversacion || empty($texto)) {
    echo json_encode(['error' => 'Data incompleta']);
    exit();
}

$usuarioLogeado = $_SESSION["usuario"];
$resDNI = $_conexion->query("SELECT DNI FROM usuario WHERE usuario = '$usuarioLogeado'");
if ($resDNI && $resDNI->num_rows > 0) {
    $miDNI = $resDNI->fetch_assoc()['DNI'];

    // 3. VALIDAR QUE SOY PARTICIPANTE DE ESTA CONVERSACIÓN
    // Evita que un hacker envíe mensajes a conversaciones de otras personas.
    $check = $_conexion->query("SELECT id_conversacion FROM conversacion WHERE id_conversacion = '$id_conversacion' AND (DNI_interesado = '$miDNI' OR DNI_vendedor = '$miDNI')");
    
    if ($check && $check->num_rows > 0) {
        $texto_seguro = $_conexion->real_escape_string($texto); // Protejo el texto
        
        // 4. INSERTAR MENSAJE
        $insert = $_conexion->query("INSERT INTO mensaje (id_conversacion, DNI_remitente, texto) VALUES ('$id_conversacion', '$miDNI', '$texto_seguro')");
        
        if ($insert) {
            // Si todo ha ido bien, le digo a Javascript que 'ok'.
            echo json_encode(['status' => 'ok']);
            exit();
        }
    }
}

// Si llego hasta aquí, es que algo ha fallado.
echo json_encode(['error' => 'Operacion fallida']);
?>