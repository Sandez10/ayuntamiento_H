<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

include '../../database/conexion.php';

// Verificar si el parámetro id_actividad está presente en la URL
if (!isset($_GET['id_actividad'])) {
    die("Error: No se ha proporcionado un ID de actividad.");
}

// Obtener el id_actividad de la URL
$id_actividad = htmlspecialchars($_GET['id_actividad']);

// Obtener detalles de la actividad
$queryActividad = "SELECT nombreActividad FROM listaactividades WHERE id_actividades = ?";
$stmtActividad = $conn->prepare($queryActividad);

if (!$stmtActividad) {
    die("Error al preparar la consulta: " . $conn->error);
}

$stmtActividad->bind_param("i", $id_actividad);
$stmtActividad->execute();
$stmtActividad->bind_result($nombreActividad);
$stmtActividad->fetch();
$stmtActividad->close();

// Verificar si se encontró la actividad
if (!$nombreActividad) {
    die("Error: No se encontró la actividad especificada.");
}

// Consultar las evidencias asociadas a la actividad
$queryAvances = "SELECT nombreEvidencia, mes, avance, avanceBeneficiario, avanceEvidencia 
FROM avances_mensuales 
WHERE id_actividades = ?";
$stmtAvances = $conn->prepare($queryAvances);

if (!$stmtAvances) {
    die("Error al preparar la consulta de evidencias: " . $conn->error);
}

$stmtAvances->bind_param("i", $id_actividad);
$stmtAvances->execute();
$resultEvidencias = $stmtAvances->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidencias de Actividad</title>
    <link rel="stylesheet" href="../../css/evidencia.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <a href="../../plataforma/mas_info.php" target="_blank">
                <img src="../../img/ZIHUA_C.png" alt="Logo" style="max-width: 100%; height: auto;">
            </a>
        </div>
<!--        <nav>
            <ul>
                <li><span class="menu-title">Menú</span></li>
                <li><a href="#">Presupuesto Anual</a></li>
                <li><span class="menu-title">Reportes</span></li>
                <li><a href="#">Programa Operativo Anual</a></li>
                <li><a href="#">Reporte de Avance</a></li>
            </ul>
        </nav>-->
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <button type="button" class="btn-salir" onclick="location.href='../../sesiones_conexiones/destruir_sesion.php';">Cerrar sesión</button>
        </header>
        <h6>Actividad: <?php echo htmlspecialchars($nombreActividad); ?></h6>

        <?php if ($resultEvidencias->num_rows > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre de la Evidencia</th>
                        <th>Archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($evidencia = $resultEvidencias->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($evidencia['nombreEvidencia']); ?></td> <!-- Corregido -->
                            <td><a href="../../uploads/<?php echo htmlspecialchars($evidencia['nombreEvidencia']); ?>" class="btn btn-info" target="_blank">Ver archivo</a></td>
                            <td><a href="../../uploads/<?php echo htmlspecialchars($evidencia['nombreEvidencia']); ?>" class="btn btn-primary" download>Descargar</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se han encontrado evidencias para esta actividad.</p>
        <?php endif; ?>

        <button type="button" class="btn btn-secondary" onclick="location.href='javascript:history.back();'">Regresar</button>
    </div>
</body>
</html>

<?php
$stmtAvances->close();
$conn->close();
?>