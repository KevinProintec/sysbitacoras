<?php
session_start();
require '../../../includes/db.php';

// Verificar autenticación básica
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Obtener rol del usuario
$rol_nombre = $_SESSION['rol'] ?? '';

// Lista de roles permitidos (puedes expandirla)
$roles_permitidos = ['admin', 'supervisor'];

if (!in_array($rol_nombre, $roles_permitidos)) {
    $_SESSION['error'] = "Acceso denegado.";
    header('Location: ../../../login.php');
    exit;
}

// Verificar que tenga permiso para este módulo (opcional, más avanzado)
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM permisos p
        INNER JOIN roles_permisos rp ON p.id = rp.permiso_id
        INNER JOIN roles r ON r.id = rp.rol_id
        WHERE r.nombre = ? AND p.clave = 'bitacoras'
    ");
    $stmt->execute([$rol_nombre]);
    $tiene_permiso = (int)$stmt->fetchColumn();

    if (!$tiene_permiso) {
        $_SESSION['error'] = "No tienes permiso para acceder a Gestión de Bitácoras.";
        header('Location: ../../../index.php'); // Volver al panel
        exit;
    }
} catch (Exception $e) {
    error_log("Error en verificación de permisos: " . $e->getMessage());
    $_SESSION['error'] = "Error de acceso.";
    header('Location: ../../../index.php');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = "ID no válido.";
} else {
    // Verificar que exista
    $stmt = $pdo->prepare("SELECT id FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        // Verificar que no esté usado en formularios
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM formularios WHERE vehiculo_id = ?");
        $stmt->execute([$id]);
        $usado = $stmt->fetchColumn();

        if ($usado > 0) {
            $_SESSION['error'] = "No se puede eliminar: este vehículo ya tiene registros asociados.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM vehiculos WHERE id = ?");
            if ($stmt->execute([$id])) {
                $_SESSION['message'] = "Vehículo eliminado correctamente.";
            } else {
                $_SESSION['error'] = "Error al eliminar el vehículo.";
            }
        }
    } else {
        $_SESSION['error'] = "Vehículo no encontrado.";
    }
}

header('Location: index.php');
exit;