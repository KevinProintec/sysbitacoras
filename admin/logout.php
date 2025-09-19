<?php
session_start();

// Destruir sesión
$_SESSION = array();
session_destroy();

// Eliminar cookie de "recordarme"
if (isset($_COOKIE['admin_login'])) {
    setcookie('admin_login', '', time() - 3600, '/');
}

header('Location: /sysbitacoras/login.php');
exit;
?>