<?php
session_start();
require '../../../includes/db.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_POST) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['password_error'] = "Todos los campos son obligatorios.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['password_error'] = "La nueva contraseña debe tener al menos 6 caracteres.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['password_error'] = "Las contraseñas nuevas no coinciden.";
    } else {
        // Obtener la contraseña actual de la base de datos
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['password_error'] = "Usuario no encontrado.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $_SESSION['password_error'] = "La contraseña actual es incorrecta.";
        } else {
            // Hashear y actualizar la nueva contraseña
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                $_SESSION['password_success'] = "Contraseña actualizada con éxito.";
            } else {
                $_SESSION['password_error'] = "Error al actualizar la contraseña.";
            }
        }
    }
}

// Redirigir al panel
header('Location: index.php');
exit;