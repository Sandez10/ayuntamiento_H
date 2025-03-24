<?php
session_start();
// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Conexión a la base de datos con mysqli
include "../database/conexion.php"; // Asegúrate de que tu conexión esté bien configurada

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

// Obtener el término de búsqueda (si existe)
$buscar = isset($_GET['search']) ? $_GET['search'] : '';
// Array para mapear números con nombres de meses
$mesesNombres = [
    1 => "Enero",
    2 => "Febrero",
    3 => "Marzo",
    4 => "Abril",
    5 => "Mayo",
    6 => "Junio",
    7 => "Julio",
    8 => "Agosto",
    9 => "Septiembre",
    10 => "Octubre",
    11 => "Noviembre",
    12 => "Diciembre"
];

// Verificar el rol y ajustar la consulta
if ($rol == 'admin') {
    // Si es admin, obtener los avances de todas las áreas
    $query = "
        SELECT am.*, la.nombreActividad, ur.nombre_area, la.claveProgramaP
        FROM avances_mensuales am
        JOIN listaactividades la ON am.id_actividades = la.id_actividades
        JOIN unidadesresponsables ur ON am.clave_area = ur.clave_area
        WHERE am.clave_area LIKE '%$buscar%'
        OR am.mes LIKE '%$buscar%'
        OR am.avance LIKE '%$buscar%'
        OR am.avanceBeneficiario LIKE '%$buscar%'
        OR am.nombreEvidencia LIKE '%$buscar%'
        OR la.nombreActividad LIKE '%$buscar%'
        OR la.claveProgramaP LIKE '%$buscar%'
        OR ur.nombre_area LIKE '%$buscar%'
    ";
} else {
    // Si es un usuario normal, obtener solo los avances de su clave_area
    $query = "
        SELECT am.*, la.nombreActividad, ur.nombre_area, la.claveProgramaP
        FROM avances_mensuales am
        JOIN listaactividades la ON am.id_actividades = la.id_actividades
        JOIN unidadesresponsables ur ON am.clave_area = ur.clave_area
        WHERE am.clave_area = ? 
        AND (
            am.clave_area LIKE '%$buscar%'
            OR am.mes LIKE '%$buscar%'
            OR am.avance LIKE '%$buscar%'
            OR am.avanceBeneficiario LIKE '%$buscar%'
            OR am.nombreEvidencia LIKE '%$buscar%'
            OR la.nombreActividad LIKE '%$buscar%'
            OR la.claveProgramaP LIKE '%$buscar%'
            OR ur.nombre_area LIKE '%$buscar%'
        )
    ";
}

// Preparar la consulta
$stmt = $conn->prepare($query);

// Si no es admin, se pasa la clave_area del usuario
if ($rol != 'admin') {
    $stmt->bind_param("s", $clave_area);
}

// Ejecutar la consulta
$stmt->execute();
$result = $stmt->get_result();

// Comprobar si hay resultados
if ($result->num_rows > 0) {
    $unidades = [];
    while ($row = $result->fetch_assoc()) {
        $unidades[] = $row;
    }
} else {
    $unidades = [];
}
$stmt->close();


// Estructura base de opciones
$baseOpciones = [
    ["title" => "Inicio", 
    "link" => "../plataforma/dashboard.php",
    "icon" => "fas fa-home" // Ícono para Ver Programas
],
["title" => "Ver Programas", 
"link" => "pre_a.php",
"icon" => "fas fa-list-alt" // Ícono para Ver Programas]
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
            "link" => "subir_csv.html",
            "icon" => "fas fa-file-csv" // Ícono para nuevas Actividades
        ],
        [
            "title" => "Editar/Borrar Programas",
            "link" => "programa_presupuestario/editar_prog.php",
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
    <title>Avances General</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="../css/avance.css">
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
            <a href="../sesiones_conexiones/destruir_sesion.php" class="btn btn-danger logout-btnw">
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
    <div class="container mt-4">
        <h1 class="text-center">Avance General</h1>
        <!-- Mostrar los datos en una tabla -->
        <table id="avancesTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Clave Área</th>
                    <th>Clave del Programa</th>
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
                                <td><?php echo htmlspecialchars($unidad['claveProgramaP']); ?></td>
                                <td><?php echo htmlspecialchars($unidad['nombre_area']); ?></td>
                                <td><?php echo htmlspecialchars($unidad['nombreActividad']); ?></td>
                                <td>
                                    <?php 
                                    // Verificar si el mes está en el array
                                    if (isset($mesesNombres[(int)$unidad['mes']])) {
                                        echo htmlspecialchars($mesesNombres[(int)$unidad['mes']]);
                                    } else {
                                        echo "Mes no válido";
                                    }
                                    ?>
                                </td>
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

                                        <!-- Enlace para ver la Gráfica -->
                                        <a href="../graficas/graficas_actividades.php?claveProgramaP=<?php echo urlencode($unidad['claveProgramaP']); ?>&nombre_area=<?php echo urlencode($unidad['nombre_area']); ?>&clave_area=<?php echo urlencode($unidad['clave_area']); ?>&id_actividades=<?php echo urlencode($unidad['id_actividades']); ?>" class="btn btn-primary btn-sm" title="Ver gráfica: <?php echo htmlspecialchars($unidad['nombreEvidencia']); ?>">
                                        <i class='fas fa-chart-line'></i>
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
                        <td colspan="8" class="text-center">No se encontraron resultados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="text-center mb-4">
            <a href="../plataforma/dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Regresar
            </a>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Inicializar DataTables -->
    <script>
$(document).ready(function() {
    $('#avancesTable').DataTable({
        "language": {
            "search": "Buscar",
            "sLengthMenu": "Mostrar _MENU_ entradas",
            "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ entradas", 
            "sInfoEmpty": "Mostrando 0 a 0 de 0 entradas",
            "sInfoFiltered": "(filtrado de _MAX_ entradas)", 
            "sLoadingRecords": "Cargando...", 
            "oPaginate": {
                "sFirst": "Primera",
                "sLast": "Última", 
                "sNext": "Siguiente", 
                "sPrevious": "Anterior"
            }
        }
    });
});

</script>

</body>
</html>