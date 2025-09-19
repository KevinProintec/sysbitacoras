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

if ($_POST) {
    $tipo = trim($_POST['tipo']);
    $placa = strtoupper(trim($_POST['placa'])); // Normalizar a mayúsculas

    if (empty($tipo) || empty($placa)) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
    } elseif (strlen($tipo) < 3) {
        $_SESSION['error'] = "El tipo debe tener al menos 3 caracteres.";
    } elseif (strlen($placa) > 20) {
        $_SESSION['error'] = "La placa no puede exceder 20 caracteres.";
    } else {
        // Verificar si la placa ya existe
        $stmt = $pdo->prepare("SELECT id FROM vehiculos WHERE placa = ?");
        $stmt->execute([$placa]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Ya existe un vehículo con la placa '$placa'.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO vehiculos (tipo, placa) VALUES (?, ?)");
            if ($stmt->execute([$tipo, $placa])) {
                $_SESSION['message'] = "Vehículo agregado correctamente.";
            } else {
                $_SESSION['error'] = "Error al agregar el vehículo.";
            }
        }
    }
}

header('Location: index.php');
exit;