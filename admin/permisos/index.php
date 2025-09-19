<?php
require '../../includes/db.php';

// Filtro Obtener roles
$stmt = $pdo->prepare("SELECT id, nombre FROM roles WHERE nombre IN ('admin', 'supervisor') ORDER BY nombre");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC); // Solo una vez

// Obtener permisos
$stmt = $pdo->query("SELECT id, nombre FROM permisos ORDER BY id");
$permisos = $stmt->fetchAll();

// Obtener relaciones rol-permiso
$stmt = $pdo->query("SELECT rol_id, permiso_id FROM roles_permisos");
$rolesPermisos = $stmt->fetchAll();

// Convertir a arreglo de búsqueda rápida
$relaciones = [];
foreach ($rolesPermisos as $rp) {
    $relaciones[$rp['rol_id']][$rp['permiso_id']] = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Permisos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="text-center mb-4">Gestión de Permisos por Rol</h2>

    <div class="alert alert-info">
        Marca los módulos a los que cada <strong>Rol</strong> puede acceder.
    </div>

    <!-- Resumen de permisos actuales -->
<div class="alert alert-light border rounded p-3 mb-4">
    <h5 class="mb-3">📋 Permisos Actuales</h5>
    <div class="row g-2">
        <?php foreach ($roles as $rol): ?>
            <div class="col-md-4">
                <div class="bg-primary text-white p-2 rounded-top">
                    <strong><?= htmlspecialchars($rol['nombre']) ?></strong>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($permisos as $permiso): ?>
                        <?php if (isset($relaciones[$rol['id']][$permiso['id']])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($permiso['nombre']) ?>
                                <span class="badge bg-success">✔</span>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</div>

    <form method="post" action="guardar_permisos.php">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th scope="col">Rol</th>
                        <?php foreach ($permisos as $permiso): ?>
                            <th scope="col"><?= htmlspecialchars($permiso['nombre']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $rol): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rol['nombre']) ?></strong></td>
                            <?php foreach ($permisos as $permiso): ?>
                                <?php $checked = '';
                                if (isset($relaciones[$rol['id']]) && isset($relaciones[$rol['id']][$permiso['id']])) {
                                $checked = 'checked';
                            } ?>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="permisos[<?= $rol['id'] ?>][]" 
                                               value="<?= $permiso['id'] ?>" 
                                               <?= $checked ?>>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Botón que abre el modal de confirmación -->
<div class="text-center mt-3">
    <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#confirmarGuardar">
        💾 Guardar Cambios
    </button>
    <a href="../" class="btn btn-secondary px-4">⬅ Volver</a>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="confirmarGuardar" tabindex="-1" aria-labelledby="confirmarGuardarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmarGuardarLabel">¿Guardar cambios?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas guardar los cambios en los permisos?</p>
                <p class="text-danger"><strong>Esta acción afectará el acceso de los usuarios.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Sí, Guardar</button>
            </div>
        </div>
    </div>
</div>

</form>  <!-- Cierra el formulario -->

<!-- Bootstrap JS (opcional si usarás interactividad extra) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
