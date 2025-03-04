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

// Verificar si el rol es admin
// Ejecutar la consulta
$stmtProgramas->execute();
$resultado = $stmtProgramas->get_result();

// Estructura base de opciones
$baseOpciones = [
    ["title" => "Inicio", 
    "link" => "../plataforma/dashboard.php",
    "icon" => "fas fa-home" // Ícono para Ver Programas
    ],
    [
        "title" => "Última Activid Registrada",
        "link" => "../formularios/avances.php",
        "icon" => "fas fa-chart-line" // Ícono para Avance General
    ]
];

// Inicializar el menú con las opciones base para todos los usuarios
$menuOpciones = [
    'default' => $baseOpciones,  // Menú base para todos los usuarios
];
// Verificar si el rol es admin y agregarle opciones adicionales
if ($rol == 'admin') {
    $menuOpciones['admin'] = array_merge($baseOpciones, [
        [
            "title" => "Administrar Usuarios",
            "link" => "../sesiones_conexiones/actualizar_usr.php",
            "icon" => "fas fa-users-cog" // Ícono para Administrar Usuarios
        ],
        [
            "title" => "Cargar nuevas actividades",
            "link" => "../formularios/subir_csv.html",
            "icon" => "fas fa-file-csv" // Ícono para nuevas Actividades
        ],
        [
            "title" => "Editar/Borrar Programas",
            "link" => "../formularios/programa_presupuestario/editarProg.php",
            "icon" => "fas fa-edit" // Ícono para Editar/Borrar Programas
        ]
    ]);
} else {
    $menuOpciones['usuario'] = array_merge($baseOpciones, []);
}
$opcionesMenu = $menuOpciones[$rol] ?? $menuOpciones['default'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avance de Actividades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="../css/pre_a.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- Encabezado -->
    <div class="header">
        <div class="logo">
            <img src="../img/ZIHUA_C.png" alt="Logo del Municipio">
        </div>
        <div class="user-options">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <a href="../sesiones_conexiones/destruir_sesion.php" class="btn btn-danger logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-md-inline">Cerrar Sesión</span>
            </a>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul>
            <li><span class="menu-title">Menú <i class="fas fa-home"></i></span></li>
            <?php foreach ($opcionesMenu as $opcion): ?>
                <li>
                    <a href="<?= $opcion['link'] ?>">
                        <i class="<?= $opcion['icon'] ?>"></i>
                        <?= $opcion['title'] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="main-content">
        <div class="content">
            <table id="programasTable" class="display">
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
                                    <button class='btn btn-primary btn-sm' onclick='location.href=\"programa_presupuestario/mas_info.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "&nombre_area=" . urlencode($row['nombre_area']) . "\";'>Mostrar</button>
                                    <button class='btn btn-success btn-sm' onclick='location.href=\"programa_presupuestario/registrar_info.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "\";'>Registrar Avance</button>
                                    <form action='programa_presupuestario/eliminar_programas.php' method='POST' class='d-inline'>
                                        <input type='hidden' name='claveProgramaP' value='" . htmlspecialchars($row['claveProgramaP'], ENT_QUOTES, 'UTF-8') . "'>
                                        <input type='hidden' name='nombre_area' value='" . htmlspecialchars($row['nombre_area'], ENT_QUOTES, 'UTF-8') . "'>
                                        <input type='hidden' name='clave_area' value='" . htmlspecialchars($row['clave_area'], ENT_QUOTES, 'UTF-8') . "'>
                                        <button type='submit' class='btn btn-danger btn-sm'>Eliminar</button>
                                    </form>
                                </td>";
                            } else {
                                echo "<td>
                                    <button class='btn btn-primary btn-sm' onclick='location.href=\"programa_presupuestario/mas_info.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "&nombre_area=" . urlencode($row['nombre_area']) . "\";'>Mostrar</button>
                                    <button class='btn btn-success btn-sm' onclick='location.href=\"programa_presupuestario/registrar_info.php?claveProgramaP=" . urlencode($row['claveProgramaP']) . "&clave_area=" . urlencode($row['clave_area']) . "\";'>Registrar Avance</button>
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
                </tbody>
            </table>
            <button class="btn btn-secondary mb-3" onclick="location.href='../plataforma/dashboard.php';">
                <i class="fas fa-arrow-left"></i> Regresar
            </button>

            
        </div>
    </div>

    <script>
        // Función para mostrar/ocultar el sidebar en móviles
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        // Inicializar DataTables
        $(document).ready(function() {
            $('#programasTable').DataTable({
                "language": {
                    "sSearch": "Buscar:",
                    "sLengthMenu": "Mostrar _MENU_ registros por página",
                    "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "sInfoFiltered": "(filtrado de _MAX_ registros)",
                    "oPaginate": {
                        "sPrevious": "Anterior",
                        "sNext": "Siguiente"
                    }
                }
            });
        });
    </script>
</body>
</html>

