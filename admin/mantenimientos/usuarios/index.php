<?php
session_start();
require '../../../includes/db.php';

// Verificar si está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Verificar si tiene cookie de "recordarme"
    if (isset($_COOKIE['admin_login'])) {
        $stmt = $pdo->prepare("SELECT id, username, rol FROM usuarios WHERE id = ?");
        $stmt->execute([$_COOKIE['admin_login']]);
        $user = $stmt->fetch();

        if ($user && $user['rol'] === 'admin') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol'] = $user['rol'];
        } else {
            header('Location: ../../../login.php');
            exit;
        }
    } else {
        header('Location: ../../../login.php');
        exit;
    }
}

// ✅ Verificación de rol
$rol_permitido = $_SESSION['rol'] ?? '';

if ($rol_permitido !== 'admin' && $rol_permitido !== 'supervisor') {
    $_SESSION['error'] = "Acceso denegado. No tienes permisos para acceder al panel.";
    header('Location: ../../../login.php'); // O a una página de acceso denegado
    exit;
}
?>

<?php include '../../../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h2 class="text-center mb-4">Panel de Administración Prointec</h2>
        <div class="alert alert-success">
            Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        </div>

<!-- Mensaje de éxito o error de contraseña -->
        <?php
       
        if (isset($_SESSION['password_error'])) {
            echo "<div class='alert alert-danger'>{$_SESSION['password_error']}</div>";
            unset($_SESSION['password_error']);
        }
        ?>




        <div class="row">
            <!-- Formulario para agregar usuario -->
            <div class="col-md-5 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Agregar Nuevo Usuario</h5>
                    </div>
                    <div class="card-body">
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
                        <form action="add_user.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="username" name="username" required minlength="3" maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            </div>


                            <select class="form-control" id="rol" name="rol">
                    <option value="piloto">Piloto</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="admin">Administrador</option>
                    </select>
                            <button type="submit" class="btn btn-success w-100">Agregar Usuario</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de usuarios -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Usuarios Registrados</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                        <th>Rol</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("SELECT id, username, created_at, rol FROM usuarios ORDER BY created_at DESC");
while ($row = $stmt->fetch()) {
    $is_me = $row['id'] == $_SESSION['user_id'];
    echo "<tr>
            <td>{$row['id']}</td>
            <td>" . htmlspecialchars($row['username']) . "</td>
            <td>{$row['created_at']}</td>
            <td>
                <span class='badge " . ($row['rol'] === 'admin' ? 'bg-danger' : 'bg-secondary') . "'>" . ucfirst($row['rol']) . "</span>
            </td>
            <td>";
    if ($is_me) {
        echo '<span class="badge bg-info">Tú</span>';
    } else {
        echo "<a href='delete_user.php?id={$row['id']}' 
                  class='btn btn-sm btn-danger' 
                  onclick=\"return confirm('¿Eliminar este usuario?')\">
                  Eliminar
              </a>";
    }
    echo "</td></tr>";
}
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

                <div class="text-center mt-4">
            <!-- Botón para cambiar contraseña -->
            <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                Cambiar Contraseña
            </button>
            <!-- Botón de cerrar sesión -->
            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>
</div>



<!-- Modal para Cambiar Contraseña -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="change_password.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <?php
                    if (isset($_SESSION['password_error'])) {
                        echo "<div class='alert alert-danger'>{$_SESSION['password_error']}</div>";
                        unset($_SESSION['password_error']);
                    }
                    if (isset($_SESSION['password_success'])) {
                        echo "<div class='alert alert-success'>{$_SESSION['password_success']}</div>";
                        unset($_SESSION['password_success']);
                    }
                    ?>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>




<?php include '../../../includes/footer.php'; ?>