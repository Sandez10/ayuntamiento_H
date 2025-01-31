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

<!-- Mostrar los datos en una tabla -->
<table>
    <thead>
        <tr>
            <th>Clave Área</th>
            <th>Nombre de la Actividad</th>
            <th>Mes</th>
            <th>Avance</th>
            <th>Beneficiarios</th>
            <th>Evidencia</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($unidades) > 0): ?>
            <?php foreach ($unidades as $unidad): ?>
                <tr>
                    <td><?php echo htmlspecialchars($unidad['clave_area']); ?></td>
                    <td><?php echo htmlspecialchars($unidad['nombreActividad']); ?></td>
                    <td><?php echo htmlspecialchars($unidad['mes']); ?></td>
                    <td><?php echo htmlspecialchars($unidad['avance']); ?></td>
                    <td><?php echo htmlspecialchars($unidad['avanceBeneficiario']); ?></td>
                    <td>
                        <?php if (isset($unidad['nombreEvidencia']) && pathinfo($unidad['nombreEvidencia'], PATHINFO_EXTENSION) == 'pdf'): ?>
                            <!-- Enlace para ver el PDF -->
                            <a href="../../uploads/?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>" class="btn btn-info btn-sm" target="_blank" title="Ver archivo: <?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>">
                                <i class="fas fa-file-pdf fa-2x"></i>
                            </a>
                            <!-- Enlace para descargar el PDF -->
                            <a href="../../uploads/<?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>" class="btn btn-primary btn-sm" download title="Descargar archivo: <?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>">
                                <i class="fas fa-cloud-download-alt fa-2x"></i>
                            </a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>
                        <?php endif; ?>
                    </td>
                    <td><a href="#">Ver Detalles</a></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">No se encontraron resultados.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>


        <button type="button" class="btn btn-secondary" onclick="location.href='javascript:history.back();'">Regresar</button>
    </div>
</body>
</html>
