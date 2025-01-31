<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Incluir archivo de conexión
include '../database/conexion.php';

// Obtener y validar el parámetro 'claveProgramaP'
if (!isset($_GET['claveProgramaP']) || empty($_GET['claveProgramaP'])) {
    die("Error: Clave de programa no válida.");
}

$claveProgramaP = htmlspecialchars($_GET['claveProgramaP'], ENT_QUOTES, 'UTF-8');

// Recuperar nombre del programa
$queryPrograma = "SELECT nombreProgramaP FROM listapp WHERE claveProgramaP = ?";
$stmtPrograma = $conn->prepare($queryPrograma);
$stmtPrograma->bind_param("s", $claveProgramaP);
$stmtPrograma->execute();
$stmtPrograma->bind_result($nombreProgramaP);
$stmtPrograma->fetch();
$stmtPrograma->close();

if (empty($nombreProgramaP)) {
    die("Error: La clave de programa no existe.");
}

// Recuperar las áreas disponibles
$queryAreas = "SELECT clave_area, nombre_area FROM unidadesresponsables";
$stmtAreas = $conn->prepare($queryAreas);
$stmtAreas->execute();
$resultadoAreas = $stmtAreas->get_result();

// Recuperar actividades del programa seleccionado
$queryActividades = "SELECT * FROM listaactividades WHERE claveProgramaP = ?";
$stmtActividades = $conn->prepare($queryActividades);
$stmtActividades->bind_param("s", $claveProgramaP);
$stmtActividades->execute();
$resultadoActividades = $stmtActividades->get_result();
$actividades = $resultadoActividades->fetch_all(MYSQLI_ASSOC);
$stmtActividades->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Área y Actividades</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Asignar Área y Actividades para el Programa: <?php echo htmlspecialchars($nombreProgramaP, ENT_QUOTES, 'UTF-8'); ?></h1>

    <!-- Formulario para seleccionar el área y las actividades -->
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-6">
            <label for="clave_area" class="form-label">Seleccionar Área</label>
            <select name="clave_area" id="clave_area" class="form-select" required>
                <option value="">Seleccione un área</option>
                <?php while ($row = $resultadoAreas->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['clave_area'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($row['nombre_area'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <!-- Lista de actividades con checkboxes -->
        <div class="col-12">
            <h3>Seleccionar Actividades:</h3>
            <?php foreach ($actividades as $actividad): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="actividades[]" value="<?php echo htmlspecialchars($actividad['id_actividades'], ENT_QUOTES, 'UTF-8'); ?>" id="actividad_<?php echo htmlspecialchars($actividad['id_actividades'], ENT_QUOTES, 'UTF-8'); ?>">
                    <label class="form-check-label" for="actividad_<?php echo htmlspecialchars($actividad['id_actividades'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($actividad['nombreActividad'], ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-primary">Asignar Área y Actividades Seleccionadas</button>
        </div>
    </form>

    <?php
    // Validar la selección de área y asignar actividades seleccionadas
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clave_area']) && isset($_POST['actividades'])) {
        $claveAreaSeleccionada = $_POST['clave_area'];
        $actividadesSeleccionadas = $_POST['actividades'];

        // Obtener el nombre del área
        $queryNombreArea = "SELECT nombre_area FROM unidadesresponsables WHERE clave_area = ?";
        $stmtNombreArea = $conn->prepare($queryNombreArea);
        $stmtNombreArea->bind_param('s', $claveAreaSeleccionada);
        $stmtNombreArea->execute();
        $stmtNombreArea->bind_result($nombreArea);
        $stmtNombreArea->fetch();
        $stmtNombreArea->close();

        if (empty($nombreArea)) {
            die("Error: El área seleccionada no es válida.");
        }

        // Insertar en unidadesresponsables
        $queryInsertarUnidad = "INSERT INTO unidadesresponsables (clave_area, nombre_area, claveProgramaP) VALUES (?, ?, ?)";
        $stmtInsertarUnidad = $conn->prepare($queryInsertarUnidad);
        $stmtInsertarUnidad->bind_param("sss", $claveAreaSeleccionada, $nombreArea, $claveProgramaP);

        if (!$stmtInsertarUnidad->execute()) {
            die("Error al asignar el área: " . $stmtInsertarUnidad->error);
        }

        // Insertar actividades seleccionadas
        foreach ($actividadesSeleccionadas as $idActividad) {
            // Obtener los datos de la actividad seleccionada
            $queryActividad = "SELECT * FROM listaactividades WHERE id_actividades = ?";
            $stmtActividad = $conn->prepare($queryActividad);
            $stmtActividad->bind_param("i", $idActividad);
            $stmtActividad->execute();
            $resultadoActividad = $stmtActividad->get_result();
            $actividad = $resultadoActividad->fetch_assoc();
            $stmtActividad->close();

            // Insertar la actividad seleccionada
            $queryInsertarActividad = "INSERT INTO listaactividades (
                nombre_area, claveProgramaP, nombreProgramaP, nombreActividad, 
                EjePMD, ObjetivoPMD, Indicador, frecuenciaMedición, unidadMedida, metaAnual, 
                metaTrim1, metaTrim2, metaTrim3, metaTrim4, MediosVerifi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmtInsertar = $conn->prepare($queryInsertarActividad);
            $stmtInsertar->bind_param(
                "sssssssssiiiiis", 
                $claveAreaSeleccionada, $claveProgramaP, $nombreProgramaP, $actividad['nombreActividad'],
                $actividad['EjePMD'], $actividad['ObjetivoPMD'], $actividad['Indicador'],
                $actividad['frecuenciaMedición'], $actividad['unidadMedida'], $actividad['metaAnual'],
                $actividad['metaTrim1'], $actividad['metaTrim2'], $actividad['metaTrim3'], 
                $actividad['metaTrim4'], $actividad['MediosVerifi']
            );

            if (!$stmtInsertar->execute()) {
                die("Error al asignar la actividad: " . $stmtInsertar->error);
            }
        }

        echo "<div class='alert alert-success text-center'>Área y actividades asignadas con éxito.</div>";
    }
    ?>

    <div class="text-center mt-4">
        <a href="../plataforma/dashboard.php" class="btn btn-secondary">Regresar</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>