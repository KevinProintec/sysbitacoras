<?php
session_start();
require '../../../includes/db.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'supervisor'])) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para agregar comentarios']);
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['comentario'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id = (int)$_POST['id'];
$comentario = trim($_POST['comentario']);

try {
    // Verificar que el registro exista
    $stmt = $pdo->prepare("SELECT confirmado FROM formularios WHERE id = ?");
    $stmt->execute([$id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
        exit;
    }

    if ($registro['confirmado']) {
        echo json_encode(['success' => false, 'message' => 'No se puede modificar el comentario de un registro ya confirmado']);
        exit;
    }

    // Guardar comentario
    $stmt = $pdo->prepare("UPDATE formularios SET comentario = ? WHERE id = ?");
    if ($stmt->execute([$comentario ?: null, $id])) {
        echo json_encode([
            'success' => true,
            'message' => 'Comentario guardado correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar']);
    }
} catch (Exception $e) {
    error_log("Error en guardar_comentario.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}