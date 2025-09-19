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
?>

<?php include '../../../includes/header.php'; ?>

<div class="container mt-4">
    <h2 class="text-center mb-4">Gestión de Vehículos</h2>

    <!-- Mensajes de éxito/error -->
    <?php
    if (isset($_SESSION['message'])) {
        echo "<div class='alert alert-success'>{$_SESSION['message']}</div>";
        unset($_SESSION['message']);
    }
    if (isset($_SESSION['error'])) {
        echo "<div class='alert alert-danger'>{$_SESSION['error']}</div>";
        unset($_SESSION['error']);
    }
    ?>

    <!-- Formulario para agregar vehículo -->
    <div class="card mb-4">
         <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Agregar Nuevo Vehículo</h5>
        </div>
        <div class="card-body">
            <form action="add.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Vehículo</label>
                        <input type="text" class="form-control" name="tipo" required minlength="3" maxlength="50" placeholder="Ej: Moto, Carro, Camión, Furgón">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Placa</label>
                        <input type="text" class="form-control" name="placa" required maxlength="20" placeholder="Ej: ABC-123">
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-success">Agregar Vehículo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de vehículos -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Vehículos Registrados</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Placa</th>
                            <th>Fecha de Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT id, tipo, placa, created_at FROM vehiculos ORDER BY tipo, placa");
                        while ($v = $stmt->fetch()) {
                            echo "<tr>
                                    <td>{$v['id']}</td>
                                    <td>" . htmlspecialchars($v['tipo']) . "</td>
                                    <td>" . htmlspecialchars($v['placa']) . "</td>
                                    <td>{$v['created_at']}</td>
                                    <td>
                                        <a href='delete.php?id={$v['id']}' 
                                           class='btn btn-sm btn-outline-danger'
                                           onclick=\"return confirm('¿Eliminar este vehículo?')\">
                                            <i class='bi bi-trash'></i>
                                        </a>
                                    </td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>