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
$confirmado_por = (int)$_SESSION['user_id'];

try {
    // Verificar que el registro exista y no esté ya confirmado
    $stmt = $pdo->prepare("SELECT confirmado FROM formularios WHERE id = ?");
    $stmt->execute([$id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
        exit;
    }

    if ($registro['confirmado']) {
        echo json_encode(['success' => false, 'message' => 'Este registro ya está confirmado']);
        exit;
    }

    // Confirmar con auditoría
    $stmt = $pdo->prepare("
        UPDATE formularios 
        SET 
            confirmado = 1,
            confirmado_por = ?,
            fecha_confirmacion = NOW()
        WHERE id = ?
    ");
    
    if ($stmt->execute([$confirmado_por, $id])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Registro confirmado exitosamente',
            'confirmado_por' => $_SESSION['username'],
            'fecha_confirmacion' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al confirmar']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}