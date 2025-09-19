<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistema'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="/sysbitacoras/assets/css/styles.css">
</head>
<body class="bg-light">

<?php
// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ajusta la ruta según tu estructura
require 'db.php';

// Detectar rol
$es_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$rol_nombre = $es_admin ? ($_SESSION['rol'] ?? '') : '';
$roles_permitidos = ['admin', 'supervisor'];

$modulos = [];

if ($es_admin && in_array($rol_nombre, $roles_permitidos)) {
    try {
        // Obtener permisos desde BD
        $stmt = $pdo->prepare("
            SELECT p.clave, p.nombre, p.url 
            FROM permisos p
            INNER JOIN roles_permisos rp ON p.id = rp.permiso_id
            INNER JOIN roles r ON r.id = rp.rol_id
            WHERE r.nombre = ?
            ORDER BY p.nombre
        ");
        $stmt->execute([$rol_nombre]);
        $permisos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($permisos_db as $p) {
    $item = ['label' => $p['nombre'], 'url' => $p['url']];

    switch ($p['clave']) {
    case 'bitacoras':
        $modulos['bitacoras'] = $item;
        break;

    case 'mantenimientos':
        // Si ya existe el grupo por 'usuarios', actualiza el label
        if (isset($modulos['mantenimientos'])) {
            $modulos['mantenimientos']['label'] = $p['nombre'];
        } else {
            // Si no existe, créalo
            $modulos['mantenimientos'] = [
                'label' => $p['nombre'],
                'dropdown' => []
            ];
        }
        break;

    case 'usuarios':
        // Aseguramos que el grupo exista
        if (!isset($modulos['mantenimientos'])) {
            $modulos['mantenimientos'] = [
                'label' => 'Mantenimientos', // Nombre por defecto
                'dropdown' => []
            ];
        }
        $modulos['mantenimientos']['dropdown'][] = $item;
        break;

    case 'permisos':
        if ($rol_nombre === 'admin') {
            $modulos['permisos'] = $item;
        }
        break;

    default:
        $key = preg_replace('/[^a-z0-9]/', '', strtolower($p['clave']));
        $modulos[$key] = $item;
        break;
}
}
    } catch (Exception $e) {
        error_log("Error en header.php: " . $e->getMessage());
        $modulos = [];
    }
} else {
    $modulos = [];
}
?>


<!-- Menú de navegación -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="/sysbitacoras/admin/">
            <img src="/sysbitacoras/assets/img/logo.png" alt="Logo" width="24" height="24" class="d-inline-block align-text-top me-2">
            Admin
        </a>

        <!-- Botón móvil -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menú -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($modulos as $key => $modulo): ?>
                    <?php if (isset($modulo['dropdown'])): ?>
                        <!-- Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown_<?php echo $key; ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo htmlspecialchars($modulo['label']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown_<?php echo $key; ?>">
                                <?php foreach ($modulo['dropdown'] as $sub): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo $sub['url']; ?>">
                                            <?php echo htmlspecialchars($sub['label']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Menú simple -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $modulo['url']; ?>">
                                <?php echo htmlspecialchars($modulo['label']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <!-- Cerrar sesión -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="/sysbitacoras/admin/logout.php">Cerrar Sesión</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Contenedor principal -->
<div class="container mt-4">