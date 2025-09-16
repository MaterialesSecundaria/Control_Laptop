<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "control_laptops";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Registrar entrega
    if (isset($_POST['registrar_entrega'])) {
        $laptop = $conn->real_escape_string($_POST['laptop']);
        $fecha_entrega = $conn->real_escape_string($_POST['fecha_entrega']);
        $persona_id = intval($_POST['persona_id']);
        $mouse = isset($_POST['mouse']) ? 1 : 0;
        $estuche = isset($_POST['estuche']) ? 1 : 0;
        $cargador = isset($_POST['cargador']) ? 1 : 0;
        $completo = ($mouse && $estuche && $cargador) ? 1 : 0;
        
        $sql = "INSERT INTO entregas (laptop, fecha_entrega, persona_id, mouse, estuche, cargador, completo)
                VALUES ('$laptop', '$fecha_entrega', $persona_id, $mouse, $estuche, $cargador, $completo)";
        
        if ($conn->query($sql) === TRUE) {
            $success_msg = "Entrega registrada correctamente.";
        } else {
            $error_msg = "Error al registrar entrega: " . $conn->error;
        }
    }
    
    // Recepcionar equipo
    if (isset($_POST['recepcionar'])) {
        $id = intval($_POST['id']);
        $fecha_recepcion = $conn->real_escape_string($_POST['fecha_recepcion']);
        $persona_recepcion_id = intval($_POST['persona_recepcion_id']);
        
        $sql = "UPDATE entregas SET fecha_recepcion='$fecha_recepcion', 
                persona_recepcion_id=$persona_recepcion_id, recepcionado=TRUE 
                WHERE id=$id";
        
        if ($conn->query($sql) === TRUE) {
            $success_msg = "Recepción registrada correctamente.";
        } else {
            $error_msg = "Error al registrar recepción: " . $conn->error;
        }
    }
    
    // Eliminar registro
    if (isset($_POST['eliminar'])) {
        $id = intval($_POST['id']);
        
        $sql = "DELETE FROM entregas WHERE id=$id";
        
        if ($conn->query($sql) === TRUE) {
            $success_msg = "Registro eliminado correctamente.";
        } else {
            $error_msg = "Error al eliminar registro: " . $conn->error;
        }
    }
    
    // Exportar CSV
    if (isset($_POST['exportar_csv'])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=entregas_laptops.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Laptop', 'Fecha Entrega', 'Entregado a', 'Mouse', 'Estuche', 'Cargador', 'Completo', 'Fecha Recepción', 'Recepción por', 'Estado'));
        
        $sql = "SELECT e.laptop, e.fecha_entrega, p.nombre as persona, 
                e.mouse, e.estuche, e.cargador, e.completo, 
                e.fecha_recepcion, pr.nombre as persona_recepcion, 
                e.recepcionado
                FROM entregas e
                LEFT JOIN personas p ON e.persona_id = p.id
                LEFT JOIN personas pr ON e.persona_recepcion_id = pr.id
                ORDER BY e.fecha_entrega DESC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $estado = $row['recepcionado'] ? 'Recepcionado' : 'Pendiente';
            fputcsv($output, array(
                $row['laptop'],
                $row['fecha_entrega'],
                $row['persona'],
                $row['mouse'] ? 'Sí' : 'No',
                $row['estuche'] ? 'Sí' : 'No',
                $row['cargador'] ? 'Sí' : 'No',
                $row['completo'] ? 'Sí' : 'No',
                $row['fecha_recepcion'] ? $row['fecha_recepcion'] : 'N/A',
                $row['persona_recepcion'] ? $row['persona_recepcion'] : 'N/A',
                $estado
            ));
        }
        fclose($output);
        exit();
    }
    
    // Limpiar registros
    if (isset($_POST['limpiar_registros'])) {
        $sql = "DELETE FROM entregas";
        
        if ($conn->query($sql) === TRUE) {
            $success_msg = "Todos los registros han sido eliminados.";
        } else {
            $error_msg = "Error al limpiar registros: " . $conn->error;
        }
    }
    
    // Generar PDF
    if (isset($_POST['generar_pdf'])) {
        $fecha_reporte = $conn->real_escape_string($_POST['fecha_reporte']);
        header("Location: generar_pdf.php?fecha=$fecha_reporte");
        exit();
    }
}

// Obtener parámetros de filtrado
$filtro = "";
if (isset($_GET['filtro_fecha']) && !empty($_GET['filtro_fecha'])) {
    $filtro_fecha = $conn->real_escape_string($_GET['filtro_fecha']);
    $filtro = " WHERE e.fecha_entrega = '$filtro_fecha'";
}

// Consulta para la tabla de registros
$sql = "SELECT e.id, e.laptop, e.fecha_entrega, p.nombre as persona, 
        e.mouse, e.estuche, e.cargador, e.completo, 
        e.fecha_recepcion, pr.nombre as persona_recepcion, 
        e.recepcionado
        FROM entregas e
        LEFT JOIN personas p ON e.persona_id = p.id
        LEFT JOIN personas pr ON e.persona_recepcion_id = pr.id
        $filtro
        ORDER BY e.fecha_entrega DESC";
$result = $conn->query($sql);

// Consulta para personas activas
$sql_personas = "SELECT id, nombre FROM personas WHERE activo = TRUE ORDER BY nombre";
$result_personas = $conn->query($sql_personas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Registro de Laptops</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        
        .navbar {
            background-color: var(--secondary-color);
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border: none;
        }
        
        .table th {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .badge-delivered {
            background-color: #28a745;
            color: white;
        }
        
        .badge-pending {
            background-color: #ffc107;
            color: black;
        }
        
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-laptop-code me-2"></i>Sistema de Registro de Laptops
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-pencil-alt me-2"></i>Registrar Entrega</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="laptop" class="form-label">Laptop</label>
                                <select class="form-select" id="laptop" name="laptop" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="LAP-01">LAP-01</option>
                                    <option value="LAP-02">LAP-02</option>
                                    <option value="LAP-03">LAP-03</option>
                                    <option value="LAP-04">LAP-04</option>
                                    <option value="LAP-05">LAP-05</option>
                                    <option value="LAP-06">LAP-06</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fecha_entrega" class="form-label">Fecha de entrega</label>
                                <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="persona_id" class="form-label">Persona a quien se entrega</label>
                                <select class="form-select" id="persona_id" name="persona_id" required>
                                    <option value="">Seleccionar...</option>
                                    <?php
                                    if ($result_personas->num_rows > 0) {
                                        while($row = $result_personas->fetch_assoc()) {
                                            echo '<option value="' . $row["id"] . '">' . $row["nombre"] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Accesorios entregados</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="mouse" name="mouse">
                                    <label class="form-check-label" for="mouse">Mouse</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="estuche" name="estuche">
                                    <label class="form-check-label" for="estuche">Estuche</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cargador" name="cargador">
                                    <label class="form-check-label" for="cargador">Cargador</label>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="registrar_entrega" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Registrar entrega
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <form method="POST" action="" class="me-md-2">
                                <button type="submit" name="exportar_csv" class="btn btn-outline-success">
                                    <i class="fas fa-file-csv me-2"></i>Exportar CSV
                                </button>
                            </form>
                            
                            <form method="POST" action="" onsubmit="return confirm('¿Está seguro de que desea eliminar todos los registros? Esta acción no se puede deshacer.');">
                                <button type="submit" name="limpiar_registros" class="btn btn-outline-danger">
                                    <i class="fas fa-trash me-2"></i>Limpiar registros
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-pdf me-2"></i>Reporte por fecha</h5>
                    </div>
                    <div class="card-body">
                        <p>Seleccione una fecha para generar un reporte PDF solo de los dispositivos recepcionados.</p>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="fecha_reporte" class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="fecha_reporte" name="fecha_reporte" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="generar_pdf" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Generar PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Registros</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label for="filtro_fecha" class="form-label">Filtrar por fecha:</label>
                                <input type="date" class="form-control" id="filtro_fecha" name="filtro_fecha" 
                                    value="<?php echo isset($_GET['filtro_fecha']) ? htmlspecialchars($_GET['filtro_fecha']) : ''; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="d-grid gap-2 w-100">
                                    <button type="submit" class="btn btn-primary">Filtrar</button>
                                    <a href="?" class="btn btn-outline-secondary">Limpiar filtro</a>
                                </div>
                            </div>
                        </form>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Laptop</th>
                                        <th>Fecha entrega</th>
                                        <th>Entregado a</th>
                                        <th>Accesorios</th>
                                        <th>Recepción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            $accesorios = [];
                                            if ($row['mouse']) $accesorios[] = "Mouse";
                                            if ($row['estuche']) $accesorios[] = "Estuche";
                                            if ($row['cargador']) $accesorios[] = "Cargador";
                                            
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($row['laptop']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['fecha_entrega']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['persona']) . '</td>';
                                            echo '<td>' . implode(', ', $accesorios) . '</td>';
                                            
                                            if ($row['recepcionado']) {
                                                echo '<td>Fecha: ' . htmlspecialchars($row['fecha_recepcion']) . '<br>Por: ' . htmlspecialchars($row['persona_recepcion']) . '<br>Completo: ' . ($row['completo'] ? 'Sí' : 'No') . '</td>';
                                                echo '<td><span class="status-badge badge-delivered">Recepcionado</span></td>';
                                            } else {
                                                echo '<td>-</td>';
                                                echo '<td><span class="status-badge badge-pending">Pendiente</span></td>';
                                            }
                                            
                                            echo '<td>';
                                            if (!$row['recepcionado']) {
                                                echo '<button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalRecepcion' . $row['id'] . '">
                                                        <i class="fas fa-check"></i>
                                                      </button>';
                                            }
                                            echo '<button class="btn btn-sm btn-danger ms-1" data-bs-toggle="modal" data-bs-target="#modalEliminar' . $row['id'] . '">
                                                    <i class="fas fa-trash"></i>
                                                  </button>';
                                            echo '</td>';
                                            echo '</tr>';
                                            
                                            // Modal para recepción
                                            echo '<div class="modal fade" id="modalRecepcion' . $row['id'] . '" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Registrar Recepción</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id" value="' . $row['id'] . '">
                                                                    <div class="mb-3">
                                                                        <label for="fecha_recepcion" class="form-label">Fecha de recepción</label>
                                                                        <input type="date" class="form-control" id="fecha_recepcion" name="fecha_recepcion" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="persona_recepcion_id" class="form-label">Persona que recibe</label>
                                                                        <select class="form-select" id="persona_recepcion_id" name="persona_recepcion_id" required>
                                                                            <option value="">Seleccionar...</option>';
                                                                            $result_personas2 = $conn->query($sql_personas);
                                                                            if ($result_personas2->num_rows > 0) {
                                                                                while($persona = $result_personas2->fetch_assoc()) {
                                                                                    echo '<option value="' . $persona["id"] . '">' . htmlspecialchars($persona["nombre"]) . '</option>';
                                                                                }
                                                                            }
                                                                            echo '</select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" name="recepcionar" class="btn btn-primary">Registrar recepción</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>';
                                            
                                            // Modal para eliminar
                                            echo '<div class="modal fade" id="modalEliminar' . $row['id'] . '" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Confirmar Eliminación</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                ¿Está seguro de que desea eliminar el registro de la laptop ' . htmlspecialchars($row['laptop']) . ' entregada el ' . htmlspecialchars($row['fecha_entrega']) . '?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="id" value="' . $row['id'] . '">
                                                                    <button type="submit" name="eliminar" class="btn btn-danger">Eliminar</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center">No hay registros</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Establecer la fecha actual como valor por defecto en los campos de fecha
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('fecha_entrega').value = today;
            
            // Si el campo de filtro de fecha está vacío, establecerlo en la fecha actual
            const filtroFecha = document.getElementById('filtro_fecha');
            if (filtroFecha && !filtroFecha.value) {
                filtroFecha.value = today;
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>