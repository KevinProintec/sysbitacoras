<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$nombre = $data['nombre'] ?? '';

if (empty($nombre)) {
    echo json_encode(["success" => false, "message" => "Nombre requerido"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=loginapp;charset=utf8mb4", "root", "admin");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO acompanantes (nombre) VALUES (?)");
    $stmt->execute([$nombre]);

    echo json_encode([
        "success" => true,
        "message" => "Acompañante agregado",
        "id" => $pdo->lastInsertId(),
        "nombre" => $nombre
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al guardar"]);
}
?>