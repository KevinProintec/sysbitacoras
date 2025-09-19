<?php
session_start();
require '../../../includes/db.php'; // ✅ Correcto: sube 3 niveles → sysbitacoras/

// Verificar autenticación y rol
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

$rol_actual = $_SESSION['rol'] ?? '';
$roles_permitidos = ['admin', 'supervisor']; // ✅ Permite supervisor si lo deseas

if (!in_array($rol_actual, $roles_permitidos)) {
    $_SESSION['error'] = "Acceso denegado.";
    header('Location: ../../../index.php');
    exit;
}

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $rol_seleccionado = $_POST['rol'] ?? '';
    $roles_validos = ['admin', 'supervisor', 'piloto'];

    if (in_array($rol_seleccionado, $roles_validos)) {
        $rol = $rol_seleccionado;
    } else {
        $rol = 'piloto'; // valor por defecto
    }

    // Validaciones
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $_SESSION['error'] = "El nombre de usuario debe tener entre 3 y 50 caracteres.";
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "El nombre de usuario ya está en uso.";
        } else {
            // Hashear la contraseña
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, rol) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $hashed, $rol])) {
                $_SESSION['message'] = "Usuario '$username' creado con éxito como '$rol'.";
            } else {
                $_SESSION['error'] = "Error al crear el usuario.";
            }
        }
    }
}

// ✅ Redirigir al listado de usuarios, no al dashboard
header('Location: index.php'); // ✅ Misma carpeta: admin/mantenimientos/usuarios/
exit;