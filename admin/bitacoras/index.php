<?php
session_start();
require '../../includes/db.php';

// Verificar autenticación básica
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../login.php');
    exit;
}

// Obtener rol del usuario
$rol_nombre = $_SESSION['rol'] ?? '';

// Lista de roles permitidos (puedes expandirla)
$roles_permitidos = ['admin', 'supervisor'];

if (!in_array($rol_nombre, $roles_permitidos)) {
    $_SESSION['error'] = "Acceso denegado.";
    header('Location: ../../login.php');
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
        header('Location: ../../index.php'); // Volver al panel
        exit;
    }
} catch (Exception $e) {
    error_log("Error en verificación de permisos: " . $e->getMessage());
    $_SESSION['error'] = "Error de acceso.";
    header('Location: ../../index.php');
    exit;
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mt-4">
    <h2 class="text-center mb-4">Gestión de Bitácoras</h2>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filtrar Registros</h5>
        </div>

        <div class="card-body">
            <form id="formFiltros">

        <!-- Buscador minimalista -->
<div class="mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search text-muted" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.098zm-6.44-7.89a5 5 0 1 1 0 10 5 5 0 0 1 0-10"/>
            </svg>
        </span>
        <input 
            type="text" 
            name="search" 
            class="form-control border-start-0 ps-0" 
            placeholder="Buscar en todos los registros..." 
            autocomplete="off">
    </div>
</div>
        </div>
              <!-- Filtros -->  

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" name="fecha_inicio">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" name="fecha_fin">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Piloto</label>
                        <select class="form-control" name="piloto_id">
                            <option value="">Todos</option>
    
                        <?php
                        try{
                            $stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE rol = 'piloto' ORDER BY username");
                            $stmt->execute();
                            while ($piloto = $stmt->fetch()){
                                echo "<option value='{$piloto['id']}'>{$piloto['username']}</option>";
                            }
                        } catch (Exception $e) {
                           error_log("Error al cargar pilotos: " . $e->getMessage());
                           echo "<option value=''>Error al cargar pilotos</option>"; 

                        } 
                        ?>


                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Actividad</label>
                        <select class="form-control" name="actividad" id="actividad">
                            <option value="">Cargando...</option>
                    </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Estado de Confirmación</label>
                         <select class="form-control" name="confirmado" id="confirmado">
                            <option value="">Todos</option>
                            <option value="1">Confirmados</option>
                            <option value="0">No confirmados</option>
                        </select>
                        </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo de Vehículo</label>
                        <select class="form-control" name="tipo_vehiculo" id="tipo_vehiculo">
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Placa</label>
                        <select class="form-control" name="placa" id="placa">
                            <option value="">Todas</option>
                        </select>
                    </div>

                    <div class="col-md-6 offset-md-3 mb-3">


                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-primary">Generar Filtro</button>
                    </div>
                    <br>
                    
                </div>
            </form>
        </div>
    </div>
<!-- Contador -->
    <div id="contador" class="text-muted small mb-3" style="display: none;">
        Mostrando <strong><span id="mostrando">0</span></strong> de <strong><span id="total">0</span></strong> registros
            <span id="rango-fechas"></span>
    </div>

    

    <!-- Resultados -->
    <div id="resultados">
        <div class="alert alert-info">Selecciona filtros y haz clic en "Generar Filtro" para ver resultados.</div>
    </div>
</div>

<script>
// Cargar tipos de vehículo al iniciar
document.addEventListener('DOMContentLoaded', function () {
    fetch('ajax/obtener_tipos_vehiculo.php')
        .then(res => res.json())
        .then(tipos => {
            const select = document.getElementById('tipo_vehiculo');
            let options = '<option value="">Todos</option>';
            tipos.forEach(tipo => {
                options += `<option value="${tipo}">${tipo}</option>`;
            });
            select.innerHTML = options;
        })
        .catch(err => {
            console.error('Error cargando tipos de vehículo:', err);
            document.getElementById('tipo_vehiculo').innerHTML = '<option value="">Error</option>';
        });
});


// Cargar actividades al iniciar
fetch('ajax/obtener_actividades.php')
    .then(res => res.json())
    .then(actividades => {
        const select = document.getElementById('actividad');
        let options = '<option value="">Todas</option>';
        actividades.forEach(act => {
            options += `<option value="${act}">${act}</option>`;
        });
        select.innerHTML = options;
    })
    .catch(err => {
        console.error('Error cargando actividades:', err);
        document.getElementById('actividad').innerHTML = '<option value="">Error al cargar</option>';
    });



// Cargar placas según tipo de vehículo
document.getElementById('tipo_vehiculo').addEventListener('change', function () {
    const tipo = this.value;
    const placaSelect = document.getElementById('placa');
    placaSelect.innerHTML = '<option value="">Cargando...</option>';

    if (!tipo) {
        placaSelect.innerHTML = '<option value="">Todas</option>';
        return;
    }

    fetch('ajax/obtener_placas.php?tipo=' + tipo)
        .then(res => res.json())
        .then(placas => {
            let options = '<option value="">Todas</option>';
            placas.forEach(placa => {
                options += `<option value="${placa}">${placa}</option>`;
            });
            placaSelect.innerHTML = options;
        })
        .catch(err => {
            placaSelect.innerHTML = '<option value="">Error al cargar</option>';
            console.error('Error:', err);
        });
});


// Función para cargar resultados con filtros y paginación
function cargarFiltros(pagina = 1) {
    const formData = new FormData(document.getElementById('formFiltros'));
    const params = new URLSearchParams();
    for (let [key, value] of formData) {
        if (value) params.append(key, value);
    }
    params.append('pagina', pagina);

    fetch('ajax/cargar_bitacoras.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            document.getElementById('resultados').innerHTML = data.html;
                
            // Mostrar contador de filtros

if (data.total !== undefined) {
    const contador = document.getElementById('contador');
    const mostrando = document.getElementById('mostrando');
    const total = document.getElementById('total');
    const rangoFechas = document.getElementById('rango-fechas');

    mostrando.textContent = data.mostrando;
    total.textContent = data.total;

    // Obtener valores de fecha del formulario
    const fechaInicio = document.querySelector('input[name="fecha_inicio"]').value;
    const fechaFin = document.querySelector('input[name="fecha_fin"]').value;

    // Formatear fechas a DD/MM/AAAA
    const formatearFecha = (fecha) => {
        if (!fecha) return '';
        const [año, mes, dia] = fecha.split('-');
        return `${dia}/${mes}/${año}`;
    };

    // Mostrar rango de fechas si existe
    if (fechaInicio && fechaFin) {
        rangoFechas.innerHTML = ` <span class="text-secondary">(filtrado entre <strong>${formatearFecha(fechaInicio)}</strong> y <strong>${formatearFecha(fechaFin)}</strong>)</span>`;
    } else if (fechaInicio) {
        rangoFechas.innerHTML = ` <span class="text-secondary">(desde <strong>${formatearFecha(fechaInicio)}</strong>)</span>`;
    } else if (fechaFin) {
        rangoFechas.innerHTML = ` <span class="text-secondary">(hasta <strong>${formatearFecha(fechaFin)}</strong>)</span>`;
    } else {
        rangoFechas.innerHTML = ` <span class="text-secondary">(filtrado de un total de <strong>${data.total}</strong> registros)</span>`;
    }

    contador.style.display = 'block';
} else {
    document.getElementById('contador').style.display = 'none';
}
        })
        .catch(err => {
            document.getElementById('resultados').innerHTML = 
                '<div class="alert alert-danger">Error al cargar los datos.</div>';
            console.error('Error:', err);
        });
}

// Confirmar un registro
function confirmar(id) {
    if (confirm('¿Estás seguro de confirmar este registro? Esta acción no se puede deshacer.')) {
        const formData = new FormData();
        formData.append('id', id);

        fetch('ajax/confirmar_registro.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ Registro confirmado exitosamente.');
                cargarFiltros(); // Recargar la tabla
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(err => {
            alert('❌ Error de conexión.');
            console.error('Error:', err);
        });
    }
}


// Eliminar un registro
function eliminarRegistro(id) {
    if (confirm('¿Estás seguro de eliminar este registro? Esta acción no se puede deshacer.')) {
        fetch('ajax/eliminar_registro.php', {
            method: 'POST',
            body: new FormData(Object.assign(document.createElement('form'), {
                innerHTML: `<input name="id" value="${id}">`
            }))
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ Registro eliminado exitosamente.');
                cargarFiltros(); // Recargar la tabla
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(err => {
            alert('❌ Error de conexión.');
            console.error('Error:', err);
        });
    }
}


// Guardar comentario
function guardarComentario(id) {
    const textarea = document.getElementById(`comentario-${id}`);
    const comentario = textarea.value.trim();

    if (comentario === '') {
        alert('El comentario no puede estar vacío');
        return;
    }

    // Usar FormData en lugar de JSON
    const formData = new FormData();
    formData.append('id', id);
    formData.append('comentario', comentario);

    fetch('ajax/guardar_comentario.php', {
        method: 'POST',
        body: formData  // Así PHP lo recibirá en $_POST
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ Comentario guardado');
            cargarFiltros(); // Recargar para ver cambios
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(err => {
        alert('❌ Error de conexión');
        console.error(err);
    });
}

// Enviar el formulario de filtros
document.getElementById('formFiltros').addEventListener('submit', function (e) {
    e.preventDefault();
    cargarFiltros(1);
});
</script>

<?php include '../../includes/footer.php'; ?>