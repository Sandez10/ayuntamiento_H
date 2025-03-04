<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

include '../../database/conexion.php';

// Obtener clave_area y rol del usuario activo
$queryUsuario = "
    SELECT 
        us.clave_area, 
        us.rol, 
        u.nombre_area
    FROM usuarios us
    JOIN unidadesresponsables u ON us.clave_area = u.clave_area
    WHERE us.usr = ?";
$stmtUsuario = $conn->prepare($queryUsuario);
$stmtUsuario->bind_param("s", $_SESSION['usuario']);
$stmtUsuario->execute();
$stmtUsuario->bind_result($clave_area, $rol, $nombre_area);
$stmtUsuario->fetch();
$stmtUsuario->close();

// Obtener los parámetros claveProgramaP y clave_area
$claveProgramaP = isset($_GET['claveProgramaP']) ? $_GET['claveProgramaP'] : null;
$clave_area = isset($_GET['clave_area']) ? $_GET['clave_area'] : null;
$nombre_area = isset($_GET['nombre_area']) ? $_GET['nombre_area'] : null;

// Si el usuario es admin y hay un 'clave_area' en la URL, lo usamos
if ($rol === 'admin' && isset($_GET['claveProgramaP']) && isset($_GET['clave_area']) && isset($_GET['nombre_area'])) {
    $clave_area = htmlspecialchars($_GET['clave_area']);
    $claveProgramaP = htmlspecialchars($_GET['claveProgramaP']);
    $dependeciaArea = htmlspecialchars($_GET['nombre_area']);
}

// Obtener programas asociados a la clave_area
$queryProgramas = "SELECT id FROM programas WHERE clave_area = ?";
$stmtProgramas = $conn->prepare($queryProgramas);
$stmtProgramas->bind_param("s", $clave_area);
$stmtProgramas->execute();
$result = $stmtProgramas->get_result();
// Estructura base de opciones
$baseOpciones = [
    ["title" => "Inicio", 
    "link" => "../plataforma/dashboard.php",
    "icon" => "fas fa-home" // Ícono para Ver Programas
    ],
    [
        "title" => "Registrar Avance",
        "link" => "registrar_info.php?claveProgramaP=" . urlencode($claveProgramaP) . "&clave_area=" . urlencode($clave_area),
        "icon" => "fas fa-clipboard-list" // Ícono para Registrar avance mensual.
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
        ],
        [
            "title" => "Avance General",
            "link" => "../formularios/avances.php",
            "icon" => "fas fa-chart-line" // Ícono para Avance General
        ]
    ]);
} else {
    $menuOpciones['usuario'] = array_merge($baseOpciones, []);
}
$opcionesMenu = $menuOpciones[$rol] ?? $menuOpciones['default'];
// Inicializar variables de búsqueda
$busqueda = isset($_GET['busqueda']) ? '%' . htmlspecialchars($_GET['busqueda'], ENT_QUOTES, 'UTF-8') . '%' : '%';
$claveProgramaP = isset($_GET['claveProgramaP']) ? $_GET['claveProgramaP'] : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPSEPP Información</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.2.2/js/dataTables.fixedColumns.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/mas_info.css">
    <script src="../../js/datatables.js"></script>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <div class="logo">
            <img src="../../img/ZIHUA_C.png" alt="Logo del Municipio">
        </div>
        <div class="user-options">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <a href="../../sesiones_conexiones/destruir_sesion.php" class="btn btn-danger logout-btnw">
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

    <!-- Main Content -->
    <div class="main-content">
        <h6>Área: <?php echo htmlspecialchars($nombre_area); ?></h6>
        <h6>Clave del Programa: <?php echo htmlspecialchars($claveProgramaP); ?></h6>
        <table id="avanceTable" class="display">
        <thead>
        <tr>
            <th>Actividad</th>
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
            <th>Avance Trimestre 1</th>
            <th>Avance Trimestre 2</th>
            <th>Avance Trimestre 3</th>
            <th>Avance Trimestre 4</th>
            <th>% Trimestre (1-4)</th>
            <th>Opciones</th>
        </tr>
    </thead>
            <tbody id="results">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $queryActividades = "SELECT DISTINCT la.id_actividades, la.claveProgramaP, la.nombreActividad, ur.nombre_area, la.metaAnual
                            FROM listaactividades la 
                            INNER JOIN unidadesresponsables ur ON la.nombre_area = ur.clave_area
                            WHERE la.claveProgramaP LIKE ? AND ur.clave_area = ?";
                        $stmtActividades = $conn->prepare($queryActividades);
                        $stmtActividades->bind_param("ss", $claveProgramaP, $clave_area);
                        $stmtActividades->execute();
                        $resultActividades = $stmtActividades->get_result();

                        if ($resultActividades->num_rows > 0) {
                            while ($actividad = $resultActividades->fetch_assoc()) {
                                echo "<tr>";
                                if ($rol !== 'admin') {
                                    if (
                                        ($actividad['nombreActividad'] === "Control y administración de recursos humanos " ||
                                         $actividad['nombreActividad'] === "Control y administración de recursos humanos" ||
                                         $actividad['nombreActividad'] === "Control y administracción de recursos humanos") 
                                    ) {
//                                        continue; // Omitir esta actividad para usuarios que no sean admin
                                    }
                                }

                                echo "<td>" . htmlspecialchars($actividad['nombreActividad']) . "</td>";
                                $queryAvances = "SELECT mes, avance, avanceBeneficiario, avanceEvidencia 
                                    FROM avances_mensuales 
                                    WHERE clave_area = ? AND id_actividades = ?";
                                $stmtAvances = $conn->prepare($queryAvances);
                                $stmtAvances->bind_param("si", $clave_area, $actividad['id_actividades']);
                                $stmtAvances->execute();
                                $resultAvances = $stmtAvances->get_result();

                                $avancesPorMes = array_fill(1, 12, ['avance' => 0, 'avanceBeneficiario' => 0, 'avanceEvidencia' => '']);
                                while ($avance = $resultAvances->fetch_assoc()) {
                                    $avancesPorMes[$avance['mes']]['avance'] = $avance['avance'];
                                    $avancesPorMes[$avance['mes']]['avanceBeneficiario'] = $avance['avanceBeneficiario'];
                                    $avancesPorMes[$avance['mes']]['avanceEvidencia'] = $avance['avanceEvidencia'];
                                }

                                foreach ($avancesPorMes as $mes => $avance) {
                                    echo "<td><div><strong>Avance:</strong> " . htmlspecialchars($avance['avance']) . "</div><div><strong>Beneficiarios:</strong> " . htmlspecialchars($avance['avanceBeneficiario']) . "</div></td>";
                                }

                                // Calcular los avances acumulados para cada trimestre
                                $trim1 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 0, 3));
                                $trim2 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 3, 3));
                                $trim3 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 6, 3));
                                $trim4 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 9, 3));

                                $trim1Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 0, 3));
                                $trim2Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 3, 3));
                                $trim3Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 6, 3));
                                $trim4Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 9, 3));

                                // Mostrar los valores acumulados de los trimestres
                                echo "<td><strong> Avance total: " . $trim1 . " | Beneficiarios: " . $trim1Benef . "</strong></td>";
                                echo "<td><strong> Avance total: " . $trim2 . " | Beneficiarios: " . $trim2Benef . "</strong></td>";
                                echo "<td><strong> Avance total: " . $trim3 . " | Beneficiarios: " . $trim3Benef . "</strong></td>";
                                echo "<td><strong> Avance total: " . $trim4 . " | Beneficiarios: " . $trim4Benef . "</strong></td>";

                                // Calcular el porcentaje acumulado
                                $metaAnual = $actividad['metaAnual'];
                                $avanceAcumulado = $trim4;
                                $porcentajeAcumulado = $metaAnual ? ($avanceAcumulado / $metaAnual) * 100 : 0;
                                echo "<td><strong>" . number_format($porcentajeAcumulado, 2) . "%</strong></td>";
                                echo "<td>
                                <a href='evidencia.php?claveProgramaP=" . urlencode($claveProgramaP) . "&nombre_area=" . urlencode($nombre_area)."&clave_area=" . urlencode($clave_area) . "&id_actividad=" . $actividad['id_actividades'] . "' class='btn btn-primary btn-sm'>
                                    <i class='fas fa-folder-open'></i> Ver Evidencias
                                </a>
                                <a href='../../graficas/graficas_actividades.php?claveProgramaP=" . urlencode($claveProgramaP) . "&nombre_area=" . urlencode($nombre_area)."&clave_area=" . urlencode($clave_area) . "&id_actividades=" . $actividad['id_actividades'] . "' class='btn btn-success btn-sm'>
                                    <i class='fas fa-chart-line'></i> Ver Gráficas
                                </a>";
                            
                            if ($rol == 'admin') {
                                echo "<form action='eliminarAvance.php' method='POST' style='display:inline;'>
                                        <input type='hidden' name='id_actividad' value='" . $actividad['id_actividades'] . "'>
                                        <button type='submit' class='btn btn-danger btn-sm'>Eliminar</button>
                                      </form>";
                            }
                            
                            echo "</td>";
                            

                                $stmtAvances->close();
                            }
                        } else {
                            echo "<tr><td colspan='20'>No se encontraron actividades para este programa.</td></tr>";
                        }
                        $stmtActividades->close();
                    }
                } else {
                    echo "<tr><td colspan='20'>No se encontraron resultados.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    <button type="button" class="btn btn-secondary" onclick="location.href='../pre_a.php?claveProgramaP=<?= urlencode($claveProgramaP); ?>';">
        <i class="fas fa-arrow-left"></i> Regresar
    </button>

    </div>
    <!-- Scripts -->

    <script>
        // Función para mostrar/ocultar el sidebar en móviles
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        $(document).ready(function () {
    console.log("Iniciando DataTable...");
    $('#avanceTable').DataTable({
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
        },
        "scrollX": true,
        "paging": true,
        "lengthMenu": [[5, 10, 20, 50, -1], [5, 10, 20, 50, "Todos"]],
        "responsive": true,
        "fixedColumns": {
            leftColumns: 1 // Fija la primera columna (Actividad)
        }
    });
});




        // JavaScript para ocultar el sidebar al desplazar horizontalmente
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            let isSidebarHidden = false;

            window.addEventListener('scroll', function () {
                if (window.scrollX > 50 && !isSidebarHidden) {
                    sidebar.classList.add('hidden');
                    mainContent.classList.add('expanded');
                    isSidebarHidden = true;
                } else if (window.scrollX <= 50 && isSidebarHidden) {
                    sidebar.classList.remove('hidden');
                    mainContent.classList.remove('expanded');
                    isSidebarHidden = false;
                }
            });
        });
    </script>
</body>
</html>