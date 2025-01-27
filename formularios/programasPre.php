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

// Inicializar variable de búsqueda
$busqueda = isset($_GET['busqueda']) ? '%' . htmlspecialchars($_GET['busqueda'], ENT_QUOTES, 'UTF-8') . '%' : '%';

// Construir consulta dependiendo del rol del usuario
if ($rol === 'admin') {
    $queryProgramas = "SELECT l.claveProgramaP, l.nombreProgramaP, u.clave_area, u.nombre_area, p.id
                       FROM listapp l
                       LEFT JOIN unidadesresponsables u ON l.claveProgramaP = u.claveProgramaP
                       LEFT JOIN programas p ON p.clave_area = u.clave_area
                       WHERE l.claveProgramaP LIKE ? OR u.nombre_area LIKE ? OR l.nombreProgramaP LIKE ?
                       GROUP BY l.claveProgramaP, l.nombreProgramaP";

    $stmtProgramas = $conn->prepare($queryProgramas);
    $stmtProgramas->bind_param("sss", $busqueda, $busqueda, $busqueda);
} else {
    $queryProgramas = "SELECT l.claveProgramaP, l.nombreProgramaP, u.clave_area, u.nombre_area, p.id
                       FROM listapp l
                       JOIN unidadesresponsables u ON l.claveProgramaP = u.claveProgramaP
                       JOIN programas p ON p.clave_area = u.clave_area
                       WHERE u.clave_area = ? AND (l.claveProgramaP LIKE ? OR u.nombre_area LIKE ? OR l.nombreProgramaP LIKE ?)
                       GROUP BY l.claveProgramaP, l.nombreProgramaP";

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
    <div class="sidebar">
        <div class="logo">
            <a href="../plataforma/dashboard.php" target="_blank">
                <img src="../img/ZIHUA_C.png" alt="Logo">
            </a>
        </div>
        <nav>
            <ul>
                <li><span class="menu-title">Menú</span></li>
                <li><a href="pre_a.php">Ver Avances</a></li>
                <li><span class="menu-title">Reportes</span></li>
                <li><a href="#">POA</a></li>
                <li><a href="#">PBR</a></li>
                <li><a href="#">MIR</a></li>
                <li><a href="#">Estructura Programática</a></li>
                <li><a href="#">Árbol de Problemas</a></li>
                <li><a href="#">Árbol de Objetivos</a></li>
            </ul>
        </nav>
    </div>
    <div class="content">
        <header>
            <button type="button" class="btn-salir" onclick="location.href='../sesiones_conexiones/destruir_sesion.php';">Cerrar sesión</button>
        </header>

        <form method="GET" class="row g-3 mb-4" id="search-form">
            <div class="col-md-9">
                <input type="text" name="busqueda" id="search-input" class="form-control" 
                       placeholder="¿Quieres buscar algún programa? Escribe aquí..." 
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
                    <th>Nombre del Programa Presupuestario</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody id="results">
                <?php 
                if ($resultado->num_rows > 0) {
                    while ($row = $resultado->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['claveProgramaP'], ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td>" . htmlspecialchars($row['nombreProgramaP'], ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td>";
                            
                        if ($rol === 'admin') {
                            echo "<button class='view-button' onclick='location.href=\"pre_a.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "\";'>Mostrar</button>";

                            }
                        else{
                            echo "<button class='view-button' onclick='location.href=\"programa_presupuestario/mas_info.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "\";'>ver Avance</button>";
                            echo "<button class='view-button' onclick='location.href=\"programa_presupuestario/registrarInfo.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "\";'>Registrar Avance</button>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No se encontraron resultados.</td></tr>";
                }

                // Cerrar conexiones
                $stmtProgramas->close();
                $conn->close();
                ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary" onclick="location.href='../plataforma/dashboard.php';">Regresar</button>
    </div>
</body>
</html>
