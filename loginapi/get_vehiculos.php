<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$tipo = $_GET['tipo'] ?? '';

if (empty($tipo) || !in_array($tipo, ['Moto', 'Panel', 'Camión'])) {
    echo json_encode(["success" => false, "message" => "Tipo inválido"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=loginapp;charset=utf8mb4", "root", "admin");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT id, placa FROM vehiculos WHERE tipo = ?");
    $stmt->execute([$tipo]);
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $vehiculos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error en BD"]);
}
?>