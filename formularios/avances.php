<?php
// Conexión a la base de datos con mysqli
include "../database/conexion.php"; // Asegúrate de que tu conexión esté bien configurada

// Obtener el término de búsqueda (si existe)
$buscar = isset($_GET['search']) ? $_GET['search'] : '';

// Consultar los datos de la tabla 'avances_mensuales' y obtener el nombre de la actividad de la tabla 'listaactividades'
// Ahora la búsqueda se realiza en todos los campos relevantes
$query = "
    SELECT am.*, la.nombreActividad, ur.nombre_area
    FROM avances_mensuales am
    JOIN listaactividades la ON am.id_actividades = la.id_actividades
    JOIN unidadesresponsables ur ON am.clave_area = ur.clave_area
    WHERE am.clave_area LIKE '%$buscar%'
    OR am.mes LIKE '%$buscar%'
    OR am.avance LIKE '%$buscar%'
    OR am.avanceBeneficiario LIKE '%$buscar%'
    OR am.nombreEvidencia LIKE '%$buscar%'
    OR la.nombreActividad LIKE '%$buscar%'
    OR ur.nombre_area LIKE '%$buscar%'
";
$result = $conn->query($query);

// Comprobar si hay resultados
if ($result->num_rows > 0) {
    $unidades = [];
    while ($row = $result->fetch_assoc()) {
        $unidades[] = $row;
    }
} else {
    $unidades = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avances General</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="../css/avance.css">
</head>
<body>

    <div class="container mt-4">
        <h1 class="text-center">Avance General</h1>

        <!-- Motor de búsqueda -->
        <form method="GET" action="avances.php" class="d-flex justify-content-center mb-4">
            <input type="text" name="search" class="form-control w-50" placeholder="Buscar por nombre de área" value="<?php echo htmlspecialchars($buscar); ?>">
            <button type="submit" class="btn btn-primary ms-2"><i class="fas fa-search"></i> Buscar</button>
        </form>

        <div class="text-center mb-4">
            <a href="../plataforma/dashboard.php" class="btn btn-secondary">Regresar</a>
        </div>

        <!-- Mostrar los datos en una tabla -->
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Clave Área</th>
                    <th>Área</th>
                    <th>Nombre de la Actividad</th>
                    <th>Mes</th>
                    <th>Avance</th>
                    <th>Beneficiarios</th>
                    <th>Evidencia</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Array para controlar registros ya mostrados
                $mostrados = [];

                if (count($unidades) > 0):
                    foreach ($unidades as $unidad):
                        // Generar una clave única basada en los campos clave_area y nombreActividad
                        $clave_unica = $unidad['clave_area'] . '-' . $unidad['nombreActividad'];

                        // Verificar si la combinación ya ha sido mostrada
                        if (!in_array($clave_unica, $mostrados)):
                            // Agregar al array de mostrados
                            $mostrados[] = $clave_unica;
                ?>
                            <tr>
                                <td><?php echo htmlspecialchars($unidad['clave_area']); ?></td>
                                <td><?php echo htmlspecialchars($unidad['nombre_area']); ?></td>
                                <td><?php echo htmlspecialchars($unidad['nombreActividad']); ?></td>
                                <td><?php echo htmlspecialchars($unidad['mes']); ?></td>
                                <td><?php echo htmlspecialchars($unidad['avance']); ?></td>
                                <td><?php echo htmlspecialchars($unidad['avanceBeneficiario']); ?></td>
                                <td>
                                    <?php if (isset($unidad['nombreEvidencia']) && pathinfo($unidad['nombreEvidencia'], PATHINFO_EXTENSION) == 'pdf'): ?>
                                        <!-- Enlace para ver el PDF -->
                                        <a href="../uploads/<?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>" class="btn btn-info btn-sm" target="_blank" title="Ver archivo: <?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>">
                                            <i class="fas fa-file-pdf fa-2x"></i>
                                        </a>
                                        <!-- Enlace para descargar el PDF -->
                                        <a href="../uploads/<?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>" class="btn btn-primary btn-sm" download title="Descargar archivo: <?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>">
                                            <i class="fas fa-cloud-download-alt fa-2x"></i>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                <?php
                        endif; // Fin de la comprobación de repetidos
                    endforeach;
                else:
                ?>
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron resultados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
