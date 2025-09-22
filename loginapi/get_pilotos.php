<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

//  Muestra errores en pantalla (solo en desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=loginapp;charset=utf8mb4", "root", "admin");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, username FROM usuarios WHERE rol = 'piloto' ORDER BY username");
    
    // Esta línea para depurar
    error_log("Consulta ejecutada correctamente");

    $pilotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Número de pilotos encontrados: " . count($pilotos));

    if ($pilotos) {
        echo json_encode([
            "success" => true,
            "data" => $pilotos
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No hay pilotos registrados"
        ]);
    }

} catch (Exception $e) {
    // Mostrar el error en la respuesta
    error_log("Error get_pilotos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error en BD",
        "error" => $e->getMessage()  // El error real
    ]);
}