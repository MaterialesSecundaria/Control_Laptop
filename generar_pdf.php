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

// Obtener fecha del reporte
$fecha_reporte = isset($_GET['fecha']) ? $conn->real_escape_string($_GET['fecha']) : date('Y-m-d');

// Consulta a la base de datos
$sql = "SELECT e.laptop, e.fecha_entrega, p.nombre as persona, 
        e.mouse, e.estuche, e.cargador, e.completo,
        e.fecha_recepcion, pr.nombre as persona_recepcion
        FROM entregas e
        LEFT JOIN personas p ON e.persona_id = p.id
        LEFT JOIN personas pr ON e.persona_recepcion_id = pr.id
        WHERE e.recepcionado = TRUE AND e.fecha_recepcion = '$fecha_reporte'
        ORDER BY e.laptop";

$result = $conn->query($sql);

// Cerrar conexión a la base de datos
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Laptops Recepcionadas - <?php echo $fecha_reporte; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #3498db;
            margin: 0;
        }
        .header p {
            color: #555;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 14px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .no-data {
            text-align: center;
            color: #e74c3c;
            font-style: italic;
            margin: 40px 0;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Laptops Recepcionadas</h1>
        <p><strong>Fecha del reporte:</strong> <?php echo $fecha_reporte; ?></p>
        <p><strong>Generado el:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
    
    <?php if ($result->num_rows > 0) { ?>
    <table>
        <thead>
            <tr>
                <th>Laptop</th>
                <th>Fecha Entrega</th>
                <th>Entregado a</th>
                <th>Accesorios</th>
                <th>Completo</th>
                <th>Recepción por</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while($row = $result->fetch_assoc()) {
                $accesorios = [];
                if ($row['mouse']) $accesorios[] = "Mouse";
                if ($row['estuche']) $accesorios[] = "Estuche";
                if ($row['cargador']) $accesorios[] = "Cargador";
                
                echo '
                <tr>
                    <td>' . htmlspecialchars($row['laptop']) . '</td>
                    <td>' . htmlspecialchars($row['fecha_entrega']) . '</td>
                    <td>' . htmlspecialchars($row['persona']) . '</td>
                    <td>' . implode(', ', $accesorios) . '</td>
                    <td>' . ($row['completo'] ? 'Sí' : 'No') . '</td>
                    <td>' . htmlspecialchars($row['persona_recepcion']) . '</td>
                </tr>';
            }
            ?>
        </tbody>
    </table>
    <?php } else { ?>
    <div class="no-data">
        <p>No hay registros de recepción para la fecha seleccionada.</p>
    </div>
    <?php } ?>
    
    <div class="footer">
        <p>Sistema de Registro de Laptops - <?php echo date('Y'); ?></p>
    </div>
    
    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Imprimir Reporte
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background-color: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
            Cerrar Ventana
        </button>
    </div>
</body>
</html>