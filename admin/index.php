<?php
session_start();
require '../includes/db.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'admin' && $rol !== 'supervisor') {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Sistema Logístico</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: url('/sysbitacoras/assets/img/fondo-logistica.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .overlay {
            background-color: rgba(0, 0, 0, 0.7);
            min-height: 100vh;
        }
        .card-dashboard {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .welcome-card {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-light">
    
<?php include '../includes/header.php'; ?>


<?php include '../includes/footer.php'; ?>