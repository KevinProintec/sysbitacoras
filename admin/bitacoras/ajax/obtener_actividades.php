<?php
session_start();
require '../../../includes/db.php';

header('Content-Type: application/json');

try {
    // Obtener actividades únicas, excluyendo valores vacíos
    $stmt = $pdo->prepare("
        SELECT DISTINCT actividad 
        FROM formularios 
        WHERE actividad IS NOT NULL AND actividad != '' 
        ORDER BY actividad
    ");
    $stmt->execute();
    $actividades = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($actividades);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar actividades']);
}