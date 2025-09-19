<?php
session_start();
require '../../../includes/db.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Obtener ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id == $_SESSION['user_id']) {
    $_SESSION['error'] = "No puedes eliminar tu propia cuenta.";
} else {
    // Verificar que exista
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Usuario eliminado correctamente.";
    } else {
        $_SESSION['error'] = "Usuario no encontrado.";
    }
}

header('Location: index.php');
exit;