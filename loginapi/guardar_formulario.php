<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
if (!isset($data['piloto_id'], $data['vehiculo_id'], $data['cliente'], $data['lugar'], $data['tipo_documento'], $data['numero_documento'], $data['actividad'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=loginapp;charset=utf8mb4", "root", "admin");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insertar formulario
    $stmt = $pdo->prepare("
        INSERT INTO formularios (
            piloto_id, vehiculo_id, cliente_nombre, lugar_entrega,
            tipo_documento, numero_documento, actividad,
            medio_pago, numero_recibo, numero_contrasena, banco
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['piloto_id'],
        $data['vehiculo_id'],
        $data['cliente'],
        $data['lugar'],
        $data['tipo_documento'],
        $data['numero_documento'],
        $data['actividad'],
        $data['medio_pago'] ?? null,
        $data['numero_recibo'] ?? null,
        $data['numero_contrasena'] ?? null,
        $data['banco'] ?? null
    ]);

    $formularioId = $pdo->lastInsertId();

    // Insertar acompañantes
    if (!empty($data['acompanantes_ids']) && is_array($data['acompanantes_ids'])) {
        $stmt2 = $pdo->prepare("INSERT INTO formularios_acompanantes (formulario_id, acompanante_id) VALUES (?, ?)");
        foreach ($data['acompanantes_ids'] as $id) {
            if (is_numeric($id)) {
                $stmt2->execute([$formularioId, (int)$id]);
            }
        }
    }

    echo json_encode(["success" => true, "message" => "Formulario guardado"]);

} catch (Exception $e) {
    error_log("Error guardar_formulario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error en BD"]);
}
?>