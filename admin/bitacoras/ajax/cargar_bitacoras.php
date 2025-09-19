<?php
session_start();
require '../../../includes/db.php';

header('Content-Type: application/json');

$pagina = (int)($_GET['pagina'] ?? 1);
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$piloto_id = $_GET['piloto_id'] ?? '';
$actividad = $_GET['actividad'] ?? '';
$confirmado = $_GET['confirmado'] ?? '';
$placa = $_GET['placa'] ?? '';
$search = trim($_GET['search'] ?? '');

// Consulta base con JOINs
$sql = "SELECT 
    f.id,
    u.username AS piloto,
    v.placa,
    f.fecha_registro,
    f.cliente_nombre,
    f.lugar_entrega,
    f.tipo_documento,
    f.actividad,
    f.numero_documento,
    f.medio_pago,
    f.banco,
    f.numero_recibo,
    f.numero_contrasena,
    f.confirmado,
    f.fecha_confirmacion,
    f.comentario,
    uc.username AS confirmado_por_nombre
FROM formularios f
JOIN usuarios u ON f.piloto_id = u.id
JOIN vehiculos v ON f.vehiculo_id = v.id
LEFT JOIN usuarios uc ON f.confirmado_por = uc.id
WHERE 1=1";

$params = [];

// Filtros
if ($fecha_inicio) {
    $sql .= " AND DATE(f.fecha_registro) >= ?";
    $params[] = $fecha_inicio;
}
if ($fecha_fin) {
    $sql .= " AND DATE(f.fecha_registro) <= ?";
    $params[] = $fecha_fin;
}
if ($piloto_id) {
    $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'piloto'");
    $stmt_check->execute([$piloto_id]);
    if ($stmt_check->fetch()) {
        $sql .= " AND f.piloto_id = ?";
        $params[] = $piloto_id;
    }
}
if ($actividad) {
    $sql .= " AND f.actividad = ?";
    $params[] = $actividad;
}
if ($confirmado === '1') {
    $sql .= " AND f.confirmado = 1";
} elseif ($confirmado === '0') {
    $sql .= " AND (f.confirmado = 0 OR f.confirmado IS NULL)";
}
if ($placa) {
    $sql .= " AND v.placa = ?";
    $params[] = $placa;
}

// Búsqueda global
if (!empty($search)) {
    $sql .= " AND (
        u.username LIKE ? OR
        v.placa LIKE ? OR
        f.cliente_nombre LIKE ? OR
        f.lugar_entrega LIKE ? OR
        f.numero_documento LIKE ? OR
        f.numero_recibo LIKE ? OR
        f.numero_contrasena LIKE ? OR
        f.banco LIKE ?
    )";
    for ($i = 0; $i < 8; $i++) {
        $params[] = '%' . $search . '%';
    }
}

// Total para paginación
$total_stmt = $pdo->prepare($sql);
$total_stmt->execute($params);
$total_registros = $total_stmt->rowCount();
$total_paginas = ceil($total_registros / $por_pagina);

// Agregar LIMIT y OFFSET
$sql .= " ORDER BY f.fecha_registro DESC LIMIT ? OFFSET ?";
$params[] = $por_pagina;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll();
} catch (Exception $e) {
    echo json_encode([
        'html' => '<div class="alert alert-danger">Error en la consulta: ' . $e->getMessage() . '</div>',
        'total' => 0,
        'mostrando' => 0
    ]);
    exit;
}

// Generar tabla
ob_start();
if (count($resultados) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Piloto</th>
                    <th>Placa</th>
                    <th>Fecha y Hora</th>
                    <th>Cliente</th>
                    <th>L. Entrega</th>
                    <th>Actividad</th>
                    <th>Tipo Doc</th>
                    <th>No Doc</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $r): ?>
                <tr>
                    <td>
                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#detalles-<?php echo $r['id']; ?>">
                            #<?php echo $r['id']; ?>
                        </button>
                    </td>
                    <td><?php echo htmlspecialchars($r['piloto']); ?></td>
                    <td><?php echo htmlspecialchars($r['placa']); ?></td>
                    <td><?php echo $r['fecha_registro']; ?></td>
                    <td><?php echo htmlspecialchars($r['cliente_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($r['lugar_entrega']); ?></td>
                    <td><?php echo htmlspecialchars($r['actividad']); ?></td>
                    <td><?php echo htmlspecialchars($r['tipo_documento']); ?></td>
                    <td><?php echo htmlspecialchars($r['numero_documento']); ?></td>
                    <td>
                        <div class="btn-group-vertical" role="group">
                           
<!-- Detalles colapsables -->
<div class="collapse" id="detalles-<?php echo $r['id']; ?>">
    <div class="bg-light p-2 mb-2 rounded">

        <strong>Medio de Pago:</strong> <?php echo htmlspecialchars($r['medio_pago'] ?? 'No especificado'); ?><br>

        <?php if (!empty($r['banco'])): ?>
            <strong>Banco:</strong> <?php echo htmlspecialchars($r['banco']); ?><br>
        <?php endif; ?>

        <?php if (!empty($r['numero_recibo'])): ?>
            <strong>N° Recibo:</strong> <?php echo htmlspecialchars($r['numero_recibo']); ?><br>
        <?php endif; ?>

        <?php if (!empty($r['numero_contrasena'])): ?>
            <strong>N° Contraseña:</strong> <?php echo htmlspecialchars($r['numero_contrasena']); ?><br>
        <?php endif; ?>

        <!-- Comentario -->
<?php if (!empty($r['comentario'])): ?>
    <div class="alert alert-info mt-2 mb-0 p-2">
        <strong><i class="bi bi-chat-left-text"></i> Comentario:</strong>
        <?php echo nl2br(htmlspecialchars($r['comentario'])); ?>
    </div>
<?php endif; ?>

<!-- Formulario para agregar/editar comentario (solo si no está confirmado) -->
<?php if (!$r['confirmado']): ?>
    <div class="mt-3">
        <label for="comentario-<?php echo $r['id']; ?>" class="form-label"><strong><?php echo $r['comentario'] ? 'Editar' : 'Agregar'; ?> Comentario</strong></label>
        <textarea 
            id="comentario-<?php echo $r['id']; ?>" 
            class="form-control form-control-sm" 
            rows="2"
            placeholder="Escribe un comentario sobre este registro..."><?php echo htmlspecialchars($r['comentario'] ?? ''); ?></textarea>
        <button 
            class="btn btn-sm btn-outline-primary mt-1"
            onclick="guardarComentario(<?php echo $r['id']; ?>)">
            <?php echo $r['comentario'] ? 'Actualizar' : 'Guardar'; ?> Comentario
        </button>
    </div>
<?php elseif ($r['comentario']): ?>
    <small class="text-muted">Este comentario fue agregado antes de la confirmación.</small>
<?php else: ?>
    <small class="text-muted">Sin comentarios.</small>
<?php endif; ?>

        <!-- Estado de confirmación -->
        <?php if ($r['confirmado']): ?>
            <strong>Estado:</strong> 
            <span class="text-success">
                ✔ Confirmado por 
                <strong><?php echo htmlspecialchars($r['confirmado_por_nombre'] ?? 'Desconocido'); ?></strong><br>
                el <?php echo $r['fecha_confirmacion'] 
                    ? date('d/m/Y H:i', strtotime($r['fecha_confirmacion'])) 
                    : 'Fecha no registrada'; ?>
            </span>
        <?php else: ?>
            <strong>Estado:</strong> 
            <span class="text-danger">❌ No confirmado</span>
        <?php endif; ?>
    </div>
</div>


                            <!-- Botón Confirmar -->
                            <button 
                                class="btn btn-sm <?php echo $r['confirmado'] ? 'btn-success' : 'btn-outline-success'; ?>" 
                                onclick="confirmar(<?php echo $r['id']; ?>)" 
                                <?php echo $r['confirmado'] ? 'disabled' : ''; 
                                      echo $r['confirmado'] ? ' title="Ya confirmado"' : ' title="Haga clic para confirmar"'; ?>>
                                <?php echo $r['confirmado'] ? '✔ Confirmado' : 'Confirmar'; ?>
                            </button>

                                <!-- Botón Eliminar -->
                            <button 
                                class="btn btn-sm btn-outline-danger mt-1"
                                onclick="eliminarRegistro(<?php echo $r['id']; ?>)"
                                title="Eliminar este registro">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>





    <!-- Paginación -->
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                    <a class="page-link" href="#" onclick="cargarFiltros(<?php echo $i; ?>); return false;"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php else: ?>
    <div class="alert alert-warning">No se encontraron registros.</div>
<?php endif;

$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'total' => $total_registros,
    'mostrando' => count($resultados)
]);