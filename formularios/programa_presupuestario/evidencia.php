<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

include '../../database/conexion.php';

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
// Obtener los parámetros claveProgramaP y clave_area
$claveProgramaP = isset($_GET['claveProgramaP']) ? $_GET['claveProgramaP'] : null;
$clave_area = isset($_GET['clave_area']) ? $_GET['clave_area'] : null;
$nombre_area = isset($_GET['nombre_area']) ? $_GET['nombre_area'] : null;
// Estructura base de opciones
$baseOpciones = [
    ["title" => "Inicio", 
    "link" => "../../plataforma/dashboard.php",
    "icon" => "fas fa-home" // Ícono para Ver Programas
    ],
    ["title" => "Actividades", 
    "link" => "mas_info.php?claveProgramaP=" . urlencode($claveProgramaP) . "&clave_area=" . urlencode($clave_area) . "&nombre_area=" . urlencode($nombre_area),
    "icon" => "fas fa-tasks" // Ícono para Actividades
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
            "link" => "../../sesiones_conexiones/actualizar_usr.php",
            "icon" => "fas fa-users-cog" // Ícono para Administrar Usuarios
        ],
        [
            "title" => "Cargar nuevas actividades",
            "link" => "../formularios/subir_csv.html",
            "icon" => "fas fa-file-csv" // Ícono para nuevas Actividades
        ],
        [
            "title" => "Editar/Borrar Programas",
            "link" => "editar_prog.php",
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

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidencias Cargadas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.2.2/js/dataTables.fixedColumns.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/evidencia.css">
</head>
<body>
    <!-- Contenedor Principal -->
    <div class="container-fluid d-flex p-0">

        <!-- Sidebar -->
        <div class="sidebar bg-dark text-white col-2 p-3">
            <div class="logo">
                <img src="../../img/ZIHUA_C.png" alt="Logo del Municipio" class="img-fluid">
            </div>
            <ul class="list-unstyled">
                <li><span class="menu-title">Menú <i class="fas fa-home"></i></span></li>
                <?php foreach ($opcionesMenu as $opcion): ?>
                    <li>
                        <a href="<?= $opcion['link'] ?>" class="text-white">
                            <i class="<?= $opcion['icon'] ?>"></i>
                            <?= $opcion['title'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content col-10 p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6>Actividad: <?php echo htmlspecialchars($nombreActividad); ?></h6>
                <a href="../../sesiones_conexiones/destruir_sesion.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>

            <!-- Tabla -->
            <table class="table table-striped table-bordered">
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
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
