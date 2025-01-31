<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

include '../database/conexion.php';

// Obtener clave_area y rol del usuario activo
$queryUsuario = "SELECT clave_area, rol FROM usuarios WHERE usr = ?";
$stmtUsuario = $conn->prepare($queryUsuario);
if ($stmtUsuario === false) {
    die("Error en la consulta de usuario: " . $conn->error);
}
$stmtUsuario->bind_param("s", $_SESSION['usuario']);
$stmtUsuario->execute();
$stmtUsuario->bind_result($clave_area, $rol);
$stmtUsuario->fetch();
$stmtUsuario->close();

if (empty($clave_area)) {
    die("No se encontró una clave_area asociada al usuario activo.");
}

// Inicializar variables de búsqueda
$busqueda = isset($_GET['busqueda']) ? '%' . htmlspecialchars($_GET['busqueda'], ENT_QUOTES, 'UTF-8') . '%' : '%';
$claveProgramaP = isset($_GET['claveProgramaP']) ? $_GET['claveProgramaP'] : null;

// Construir consulta dependiendo del rol del usuario
if ($rol === 'admin') {
    if ($claveProgramaP) {
        // Mostrar todas las áreas relacionadas con el programa específico
        $queryProgramas = "SELECT l.claveProgramaP, l.nombreProgramaP, u.clave_area, u.nombre_area
                           FROM listapp l
                           JOIN unidadesresponsables u ON l.claveProgramaP = u.claveProgramaP
                           WHERE l.claveProgramaP = ?";
        $stmtProgramas = $conn->prepare($queryProgramas);
        $stmtProgramas->bind_param("s", $claveProgramaP);
    } else {
        // Mostrar todos los programas sin filtrar por área
        $queryProgramas = "SELECT l.claveProgramaP, l.nombreProgramaP, u.clave_area, u.nombre_area
                           FROM listapp l
                           JOIN unidadesresponsables u ON l.claveProgramaP = u.claveProgramaP
                           WHERE l.claveProgramaP LIKE ? OR u.nombre_area LIKE ? OR l.nombreProgramaP LIKE ?";
        $stmtProgramas = $conn->prepare($queryProgramas);
        $stmtProgramas->bind_param("sss", $busqueda, $busqueda, $busqueda);
    }
} else {
    // Para otros usuarios, se filtra por clave_area y búsqueda
    $queryProgramas = "SELECT l.claveProgramaP, l.nombreProgramaP, u.clave_area, u.nombre_area
                       FROM listapp l
                       JOIN unidadesresponsables u ON l.claveProgramaP = u.claveProgramaP
                       WHERE u.clave_area = ? AND (l.claveProgramaP LIKE ? OR u.nombre_area LIKE ? OR l.nombreProgramaP LIKE ?)";
    $stmtProgramas = $conn->prepare($queryProgramas);
    $stmtProgramas->bind_param("ssss", $clave_area, $busqueda, $busqueda, $busqueda);
}

// Ejecutar la consulta
$stmtProgramas->execute();
$resultado = $stmtProgramas->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avance de Actividades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/plan_pp.css">
</head>
<body>
    <div class="content">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-9">
                <input type="text" name="busqueda" class="form-control" 
                       placeholder="Busca un programa..." 
                       value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-50">Buscar</button>
            </div>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Nombre del Programa</th>
                    <th>Área</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
            <?php 
if ($resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['claveProgramaP'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td>" . htmlspecialchars($row['nombreProgramaP'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre_area'], ENT_QUOTES, 'UTF-8') . "</td>";
        if ($rol == 'admin') {
        echo "<td>
            <button class='view-button' onclick='location.href=\"programa_presupuestario/mas_info.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "&nombre_area=" . urlencode($row['nombre_area']) . "\";'>Mostrar</button>
            <button class='view-button' onclick='location.href=\"programa_presupuestario/registrarInfo.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "\";'>Registrar Avance</button>
                
            <form action='programa_presupuestario/eliminar_programas.php' method='POST'>
                <input type='hidden' name='claveProgramaP' value='" . htmlspecialchars($row['claveProgramaP'], ENT_QUOTES, 'UTF-8') . "'>
                <input type='hidden' name='nombre_area' value='" . htmlspecialchars($row['nombre_area'], ENT_QUOTES, 'UTF-8') ."'>
                <input type='hidden' name='clave_area' value='" . htmlspecialchars($row['clave_area'], ENT_QUOTES, 'UTF-8') ."'>

                <button type='submit' class='btn btn-danger'>Eliminar</button>
            </form>
            </td>";
        }
        else{echo "<td>
            <button class='view-button' onclick='location.href=\"programa_presupuestario/mas_info.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "&nombre_area=" . urlencode($row['nombre_area']) . "\";'>Mostrar</button>
            <button class='view-button' onclick='location.href=\"programa_presupuestario/registrarInfo.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "\";'>Registrar Avance</button>
        </td>";
            }

        
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4'>No se encontraron resultados.</td></tr>";
}
$stmtProgramas->close();
$conn->close();
?>
        <button type="button" class="btn btn-secondary" onclick="location.href='../plataforma/dashboard.php';">Regresar</button>
            </tbody>
        </table>
    </div>
</body>
</html>
