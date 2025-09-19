<?php
session_start();
require '../../../includes/db.php';

header('Content-Type: application/json');

// Verificar autenticación y rol permitido
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$rol = $_SESSION['rol'] ?? '';
$roles_permitidos = ['admin', 'supervisor'];

if (!in_array($rol, $roles_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para confirmar registros']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = (int)$_POST['id'];

try {
    // Verificar que el registro exista
    $stmt = $pdo->prepare("SELECT id FROM formularios WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
        exit;
    }

    // Eliminar el registro
    $stmt = $pdo->prepare("DELETE FROM formularios WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Registro eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}