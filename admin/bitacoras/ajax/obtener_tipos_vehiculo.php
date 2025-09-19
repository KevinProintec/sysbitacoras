<?php
session_start();
require '../../../includes/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT DISTINCT tipo FROM vehiculos WHERE tipo IS NOT NULL AND tipo != '' ORDER BY tipo");
    $stmt->execute();
    $tipos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($tipos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos']);
}