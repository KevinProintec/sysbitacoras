<?php
session_start();
require __DIR__ . '/includes/db.php'; // Asegúrate de que esta ruta también esté bien

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Buscar al usuario
    $stmt = $pdo->prepare("SELECT id, username, password, rol FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Iniciar sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['admin_logged_in'] = true;

        // Cookie "recordarme"
        if ($remember) {
            setcookie('admin_login', $user['id'], time() + (86400 * 7), "/");
        }

        // Redirigir según rol
        if ($user['rol'] === 'admin' || $user['rol'] === 'supervisor') {
            // ✅ Ruta absoluta para evitar errores
            header('Location: /sysbitacoras/admin/index.php');
            exit;
        } else {
            // Rol no permitido en el panel
            $_SESSION['error'] = "Acceso denegado. Esta área es solo para administradores.";
            header('Location: /sysbitacoras/login.php');
            exit;
        }
    } else {
        $_SESSION['error'] = "Usuario o contraseña incorrectos.";
        header('Location: /sysbitacoras/login.php');
        exit;
    }
} else {
    header('Location: /sysbitacoras/login.php');
    exit;
}