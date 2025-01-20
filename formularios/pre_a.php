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
    // Para admin, no se filtra por clave_area
    $queryProgramas = "SELECT l.claveProgramaP, l.nombreProgramaP, u.clave_area, u.nombre_area, p.id
                       FROM listapp l
                       JOIN unidadesresponsables u ON l.claveProgramaP = u.claveProgramaP
                       JOIN programas p ON p.clave_area = u.clave_area
                       WHERE l.claveProgramaP LIKE ? OR u.nombre_area LIKE ? OR l.nombreProgramaP LIKE ?";
    $stmtProgramas = $conn->prepare($queryProgramas);
    $stmtProgramas->bind_param("sss", $busqueda, $busqueda, $busqueda);
} else {
    // Para otros usuarios, se filtra por clave_area y busqueda
    $queryProgramas = "SELECT l.claveProgramaP, l.nombreProgramaP, u.clave_area, u.nombre_area, p.id
                       FROM listapp l
                       JOIN unidadesresponsables u ON l.claveProgramaP = u.claveProgramaP
                       JOIN programas p ON p.clave_area = u.clave_area
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
    <title>Programas Presupuestarios</title>
    <link rel="stylesheet" href="../css/plan_pp.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <a href="../plataforma/dashboard.php" target="_blank">
                <img src="../img/zihua.png" alt="Logo">
            </a>
        </div>
        <nav>
            <ul>
                <li><span class="menu-title">Menú</span></li>
                <li><a href="pre_a.php">Presupuesto Anual</a></li>
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
        <form method="GET" class="search-form" id="search-form">
            <input type="text" name="busqueda" id="search-input" placeholder="Buscar programa..." value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            <button type="submit">Buscar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Nombre del Programa Presupuestario</th>
                    <th>Área Responsable</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody id="results">
            <?php 
if ($resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['claveProgramaP'], ENT_QUOTES, 'UTF-8') . "</td>"; // muestra clave programa
        echo "<td>" . htmlspecialchars($row['nombreProgramaP'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre_area'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td> 
            <button class='view-button' onclick='location.href=\"programa_presupuestario/mas_info.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "\";'>Ver</button>
            <button class='view-button' onclick='location.href=\"programa_presupuestario/registrarInfo.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "\";'>Registrar Avance</button>

        </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4'>No se encontraron resultados.</td></tr>";
}


// Cerrar conexiones
$stmtProgramas->close();
$conn->close();
?>
        <button type="button" class="btn-regresar" onclick="location.href='../plataforma/dashboard.php';">Regresar</button>
<html>