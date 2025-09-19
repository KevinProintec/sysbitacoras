<?php
session_start();
require '../../includes/db.php';

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

if ($_POST && isset($_POST['permisos'])) {
    try {
        $pdo->beginTransaction();

        // Limpiar todos los permisos
        $pdo->exec("DELETE FROM roles_permisos");

        // Insertar nuevos
        foreach ($_POST['permisos'] as $rol_id => $permiso_ids) {
            $rol_id = (int)$rol_id;
            foreach ($permiso_ids as $permiso_id) {
                $permiso_id = (int)$permiso_id;
                $stmt = $pdo->prepare("INSERT INTO roles_permisos (rol_id, permiso_id) VALUES (?, ?)");
                $stmt->execute([$rol_id, $permiso_id]);
            }
        }

        $pdo->commit();
        $_SESSION['message'] = "✅ Permisos actualizados correctamente.";
    } catch (Exception $e) {
        $pdo->rollback();
        $_SESSION['error'] = "❌ Error al guardar permisos: " . $e->getMessage();
    }
}

header('Location: index.php');
exit;