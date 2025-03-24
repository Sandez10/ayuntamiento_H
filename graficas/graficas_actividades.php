<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

include '../database/conexion.php';
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

// Verificar si el parámetro id_actividad está presente en la URL
if (!isset($_GET['id_actividades'])) {
    die("Error: No se ha proporcionado un ID de actividad.");
}

// Obtener el id_actividad de la URL
$id_actividad = htmlspecialchars($_GET['id_actividades']);
$claveProgramaP = htmlspecialchars($_GET['claveProgramaP']);
$clave_area = htmlspecialchars($_GET['clave_area']);
$nombre_area = htmlspecialchars($_GET['nombre_area']);
// Obtener el nombre de la actividad
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

// Obtener la meta anual desde la tabla listaactividades
$queryMetaAnual = "SELECT metaAnual FROM listaactividades WHERE id_actividades = ?";
$stmtMetaAnual = $conn->prepare($queryMetaAnual);
$stmtMetaAnual->bind_param("i", $id_actividad);
$stmtMetaAnual->execute();
$stmtMetaAnual->bind_result($metaAnual);
$stmtMetaAnual->fetch();
$stmtMetaAnual->close();

// Verificar si se encontró la meta anual
if (!$metaAnual) {
    die("Error: No se encontró la meta anual para esta actividad.");
}

// Obtener los avances de la actividad
$queryAvances = "SELECT mes, avance FROM avances_mensuales WHERE id_actividades = ?";
$stmtAvances = $conn->prepare($queryAvances);
$stmtAvances->bind_param("i", $id_actividad);
$stmtAvances->execute();
$resultAvances = $stmtAvances->get_result();

// Inicializar el arreglo con ceros
$avancesMensuales = array_fill(0, 12, 0);

// Llenar los avances mensuales con los valores correspondientes
while ($avance = $resultAvances->fetch_assoc()) {
    $avancesMensuales[$avance['mes'] - 1] = $avance['avance'];  // Ajuste al índice de los meses (1-12)
}
$stmtAvances->close();

// Agrupar los avances por trimestres con acumulado progresivo
$avancesTrimestrales = [];
$labels = ["1er Trimestre", "2do Trimestre", "3er Trimestre", "4to Trimestre"];

$acumulado = 0;
for ($i = 0; $i < 4; $i++) {
    // Sumar los avances del trimestre actual
    $trimestreTotal = array_sum(array_slice($avancesMensuales, $i * 3, 3)); // (Enero-Marzo, Abr-Jun, etc.)
    
    // Si hay avances en el trimestre, los acumulamos
    if ($trimestreTotal > 0) {
        // Calcular el porcentaje con respecto a la meta anual
        $porcentajeTrimestre = $metaAnual ? ($trimestreTotal / $metaAnual) * 100 : 0;

        // Acumulamos el porcentaje
        $acumulado += $porcentajeTrimestre;
        if ($acumulado>100){
            $avancesTrimestrales[] = number_format($acumulado, 2);
        }

// Guardamos el porcentaje acumulado por trimestre


    }
}

// Estructura base de opciones
$baseOpciones = [
    ["title" => "Inicio", 
    "link" => "../plataforma/dashboard.php",
    "icon" => "fas fa-home" // Ícono para Ver Programas
    ],
    ["title" => "Actividades", 
    "link" => "../formularios/programa_presupuestario/mas_info.php?claveProgramaP=" . urlencode($claveProgramaP) . "&clave_area=" . urlencode($clave_area) . "&nombre_area=" . urlencode($nombre_area),
    "icon" => "fas fa-tasks" // Ícono para Actividades
    ],    
    [
        "title" => "Registrar Avance",
        "link" => "../formularios/programa_presupuestario/registrar_info.php?claveProgramaP=" . urlencode($claveProgramaP) . "&clave_area=" . urlencode($clave_area),
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
            "link" => "../formularios/programa_presupuestario/editar_prog.php",
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
    <title>Gráfica de Actividad</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <link rel="stylesheet" href="../css/graficas_actividades.css">
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
    <div style="display: flex; justify-content: center; align-items: center; width: calc(100vw - 250px); height: calc(100vh - 100px); margin-left: 200px; margin-top: 80px; position: relative;">
    <canvas id="grafica_actividad" style="max-width: 1000px; width: 100%; height: auto;"></canvas>
</div>


<script>
    var ctx = document.getElementById('grafica_actividad').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Avance de <?= htmlspecialchars($nombreActividad) ?> ',
                data: <?= json_encode($avancesTrimestrales) ?>,
                borderColor: 'rgba(30, 144, 255, 1)', // Azul claro
                borderWidth: 2,
                pointBackgroundColor: 'rgba(30, 144, 255, 1)', // Color de los puntos
                pointRadius: 5, // Tamaño de los puntos
                pointHoverRadius: 7, // Tamaño al pasar el mouse
                borderJoinStyle: 'round', // Bordes más suaves
                fill: false // Sin relleno bajo la línea
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    offset: true,
                    grid: {
                        display: false
                    }
                },
                y: {
                    min: 0, // Establece el inicio en 0
                    suggestedMax: 100,
                    ticks: {
                        stepSize: 25,
                        callback: function(value) { // Formateamos las etiquetas del eje Y
                            return value + '%'; // Añadimos el porcentaje en las etiquetas
                        }
                    },
                    grid: {
                        drawBorder: false,
                        color: "rgba(0, 0, 0, 0.1)"
                    }
                }
            },
            elements: {
                line: {
                    tension: 0.2 // Agrega una leve curva a la línea
                }
            }
        }
    });
</script>



</body>
</html>
