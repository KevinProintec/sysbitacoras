<?php
session_start();
require '../../../includes/db.php';

header('Content-Type: application/json');

$tipo = $_GET['tipo'] ?? '';

if (empty($tipo)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT placa FROM vehiculos WHERE tipo = ? ORDER BY placa");
$stmt->execute([$tipo]);
$placas = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($placas);