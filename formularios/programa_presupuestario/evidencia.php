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
$queryAvances = "SELECT nombreEvidencia, mes FROM avances_mensuales WHERE id_actividades = ?";
$stmtAvances = $conn->prepare($queryAvances);

if (!$stmtAvances) {
    die("Error al preparar la consulta de evidencias: " . $conn->error);
}

$stmtAvances->bind_param("i", $id_actividad);
$stmtAvances->execute();
$resultEvidencias = $stmtAvances->get_result();

// Inicializar arreglo para clasificar evidencias por mes
$evidenciasPorMes = array_fill(1, 12, []);

while ($evidencia = $resultEvidencias->fetch_assoc()) {
    $mes = (int)$evidencia['mes'];
    $evidenciasPorMes[$mes][] = $evidencia;
}
$stmtAvances->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidencias de Actividad</title>
    <link rel="stylesheet" href="../../css/evidencia.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <a href="../../plataforma/mas_info.php" target="_blank">
                <img src="../../img/ZIHUA_C.png" alt="Logo" style="max-width: 100%; height: auto;">
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <button type="button" class="btn-salir" onclick="location.href='../../sesiones_conexiones/destruir_sesion.php';">Cerrar sesión</button>
        </header>
        <h6>Actividad: <?php echo htmlspecialchars($nombreActividad); ?></h6>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Enero</th>
                    <th>Febrero</th>
                    <th>Marzo</th>
                    <th>Abril</th>
                    <th>Mayo</th>
                    <th>Junio</th>
                    <th>Julio</th>
                    <th>Agosto</th>
                    <th>Septiembre</th>
                    <th>Octubre</th>
                    <th>Noviembre</th>
                    <th>Diciembre</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                        <td>
                            <?php if (!empty($evidenciasPorMes[$mes])): ?>
                                <?php foreach ($evidenciasPorMes[$mes] as $evidencia): ?>
                                    <a href="../../uploads/<?php echo htmlspecialchars($evidencia['nombreEvidencia']); ?>" class="btn btn-info btn-sm" target="_blank" title="<?php echo htmlspecialchars($evidencia['nombreEvidencia']); ?>">
                                    <i class="fas fa-file-pdf fa-2x"></i></a>
                                    <a href="../../uploads/<?php echo htmlspecialchars($evidencia['nombreEvidencia']); ?>" class="btn btn-primary btn-sm" download title="<?php echo htmlspecialchars($evidencia['nombreEvidencia']); ?>">
                                    <i class="fas fa-cloud-download-alt fa-2x"></i></a>


                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Sin evidencias</p>
                            <?php endif; ?>
                        </td>
                    <?php endfor; ?>
                </tr>
            </tbody>
        </table>

        <button type="button" class="btn btn-secondary" onclick="location.href='javascript:history.back();'">Regresar</button>
    </div>
</body>
</html>

