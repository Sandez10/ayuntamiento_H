<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}

include '../../database/conexion.php';

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

$programas = [];

// Preparar la consulta de búsqueda con los parámetros
$query = "SELECT * FROM listapp WHERE nombreProgramaP LIKE ? OR claveProgramaP LIKE ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $busqueda, $busqueda);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $programas[] = $row;
    }
}

// Obtener los programas desde la base de datos si no hay resultados de búsqueda
if (count($programas) === 0) {
    $query = "SELECT * FROM listapp";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $programas[] = $row;
        }
    }
}

$stmt->close();

// Realizar actualizaciones de programa
if (isset($_POST['claveProgramaP'], $_POST['nombreProgramaP'], $_POST['clavePrograma'])) {
    $claveProgramaP = $_POST['claveProgramaP'];
    $nombreProgramaP = $_POST['nombreProgramaP'];
    $clavePrograma = $_POST['clavePrograma'];

    // Preparar la consulta de actualización
    $queryUpdate = "UPDATE listapp SET claveProgramaP = ?, nombreProgramaP = ? WHERE claveProgramaP = ?";
    $stmtUpdate = $conn->prepare($queryUpdate);

    // Verificar si la consulta se preparó correctamente
    if ($stmtUpdate) {
        // Vincular los parámetros
        $stmtUpdate->bind_param("sss", $claveProgramaP, $nombreProgramaP, $clavePrograma);

        // Ejecutar la actualización
        if ($stmtUpdate->execute()) {
            $mensaje = 'Actualización exitosa.';
        } else {
            $mensaje = 'Error al ejecutar la actualización.';
        }

        // Cerrar el statement
        $stmtUpdate->close();
    } else {
        $mensaje = 'Error en la preparación de la consulta.';
    }
}

// Eliminar programa
if (isset($_POST['eliminar'])) {
    $claveProgramaP = $_POST['claveProgramaP'];

    // Preparar la consulta de eliminación
    $queryDelete = "DELETE FROM listapp WHERE claveProgramaP = ?";
    $stmtDelete = $conn->prepare($queryDelete);

    if ($stmtDelete) {
        // Vincular el parámetro
        $stmtDelete->bind_param("s", $claveProgramaP);

        // Ejecutar la eliminación
        if ($stmtDelete->execute()) {
            $mensaje = 'Programa eliminado con éxito.';
        } else {
            $mensaje = 'Error al eliminar el programa.';
        }

        // Cerrar el statement
        $stmtDelete->close();
    } else {
        $mensaje = 'Error en la preparación de la consulta de eliminación.';
    }
}

// Verificar si el rol es admin
if ($rol !== 'admin') {
    echo "<script>
        alert('No tienes permiso para acceder a esta página.');
        history.back();
    </script>";
    exit(); // Esto es opcional
}

// Estructura base de opciones
$baseOpciones = [
    ["title" => "Inicio", 
    "link" => "../../plataforma/dashboard.php",
    "icon" => "fas fa-home" // Ícono para Ver Programas
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
            "link" => "../procesar_csv.php",
            "icon" => "fas fa-file-csv" // Ícono para nuevas Actividades
        ],
        [
            "title" => "Avance General",
            "link" => "../avances.php",
            "icon" => "fas fa-chart-line" // Ícono para Avance General
        ]
    ]);
} else {
    $menuOpciones['usuario'] = array_merge($baseOpciones, []);
}

$opcionesMenu = $menuOpciones[$rol] ?? $menuOpciones['default'];
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPSEPP EDITAR PROGRAMAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="../../css/editar_prog.css"> <!-- Aquí se incluirá el CSS personalizado -->
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

    <!-- Contenido principal -->
    <div class="main-content">
        <!-- Botón de Agregar -->
        <div class="btn-agregar-container">
            <button type="button" class="btn btn-primary btn-agregar" onclick="location.href='agregarPrograma.php';">
                <i class="fas fa-plus"></i> Agregar
            </button>
        </div>

        <!-- Tabla de programas -->
        <table id="programasTable" class="table table-striped">
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Nombre del Programa Presupuestario</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody id="results">
                <?php if (isset($mensaje)): ?>
                    <tr><td colspan="3"><?php echo htmlspecialchars($mensaje); ?></td></tr>
                <?php endif; ?>

                <?php if (count($programas) > 0): ?>
                    <?php foreach ($programas as $programa): ?>
                        <tr data-clave="<?php echo htmlspecialchars($programa['claveProgramaP'], ENT_QUOTES, 'UTF-8'); ?>">
                            <form method="POST">
                                <td>
                                    <span class="programa-info"><?php echo htmlspecialchars($programa['claveProgramaP'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <input type="text" name="claveProgramaP" value="<?php echo htmlspecialchars($programa['claveProgramaP'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control editable-input" style="display:none;">
                                </td>
                                <td>
                                    <span class="programa-info"><?php echo htmlspecialchars($programa['nombreProgramaP'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <input type="text" name="nombreProgramaP" value="<?php echo htmlspecialchars($programa['nombreProgramaP'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control editable-input" style="display:none;">
                                </td>
                                <td>
                                    <input type="hidden" name="clavePrograma" value="<?php echo htmlspecialchars($programa['claveProgramaP'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="button" class="btn btn-success edit-btn">Editar</button>
                                    <button type="submit" class="btn btn-success save-btn" style="display:none;">Guardar</button>
                                    <button type="button" class="btn btn-info" onclick="location.href='../pre_a.php?claveProgramaP=<?php echo htmlspecialchars($programa['claveProgramaP'], ENT_QUOTES, 'UTF-8'); ?>'">Actividades</button>
                                    <button type="button" class="btn btn-primary" onclick="if (confirm('¿Estás seguro de asignar este programa?')) { location.href='../asignar_prog_area.php?claveProgramaP=<?php echo urlencode($programa['claveProgramaP']); ?>'; }">Asignar</button>
                                    <button type="submit" name="eliminar" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este programa?');">Eliminar</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No se encontraron resultados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary" onclick="window.location.href='../plataforma/dashboard.php';">Regresar</button>
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

        // Script para editar y mostrar los campos
        document.querySelectorAll('.edit-btn').forEach((button) => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                row.querySelectorAll('.editable-input').forEach(input => {
                    input.style.display = 'block';
                });
                row.querySelectorAll('.programa-info').forEach(span => {
                    span.style.display = 'none';
                });
                row.querySelector('.edit-btn').style.display = 'none';
                row.querySelector('.save-btn').style.display = 'inline-block';
            });
        });

        // Función de animación para mostrar el formulario
        document.querySelectorAll('.save-btn').forEach((button) => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                row.classList.add('editable-row');
                row.classList.toggle('show');
            });
        });
    </script>
</body>
</html>