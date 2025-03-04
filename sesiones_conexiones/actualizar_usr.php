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
        die(json_encode(["success" => false, "message" => "Error en la consulta de usuario: " . $conn->error]));
    }
    $stmtUsuario->bind_param("s", $_SESSION['usuario']);
    $stmtUsuario->execute();
    $stmtUsuario->bind_result($clave_area, $rol);
    $stmtUsuario->fetch();
    $stmtUsuario->close();

if (empty($clave_area)) {
    die(json_encode(["success" => false, "message" => "No se encontró una clave_area asociada al usuario activo."]));
}

// Obtener la lista de usuarios
$queryUsuarios = "SELECT * FROM usuarios";
$resultUsuarios = $conn->query($queryUsuarios);
$usuarios = [];
if ($resultUsuarios->num_rows > 0) {
    while ($row = $resultUsuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// Manejo de acciones (eliminar, agregar, editar)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Respuesta en JSON

    // Eliminar usuario
    if (isset($_POST['delete']) && isset($_POST['usuario_id'])) {
        $usuarioId = $_POST['usuario_id'];
        $sql = "DELETE FROM usuarios WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $usuarioId);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Usuario eliminado correctamente."]);
            } else {
                echo json_encode(["success" => false, "message" => "Error al eliminar el usuario: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Error en la consulta: " . $conn->error]);
        }
        exit();
    }

    // Verificar si los campos requeridos están presentes
    if (!isset($_POST['usuario']) || !isset($_POST['correo']) || !isset($_POST['area']) || !isset($_POST['clave_area']) || !isset($_POST['rol'])) {
        echo json_encode(["success" => false, "message" => "Todos los campos son obligatorios."]);
        exit();
    }

    // Asignar valores desde $_POST
    $usuarioId = $_POST['usuarioId'] ?? "";
    $usuario = $_POST['usuario'];
    $correo = $_POST['correo'];
    $area = $_POST['area'];
    $clave_area = $_POST['clave_area'];
    $rol = $_POST['rol'];
    $clave_prog = $_POST['clave_prog'] ?? "";

    // Validar correo electrónico
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Correo electrónico no válido."]);
        exit();
    }

    // Verificar si se proporcionó una contraseña temporal al agregar un nuevo usuario
    if (empty($usuarioId) && empty($_POST['passtemp'])) {
        echo json_encode(["success" => false, "message" => "La contraseña temporal es obligatoria."]);
        exit();
    }

    // Hashear la contraseña temporal si se proporcionó
    $passtemp = !empty($_POST['passtemp']) ? password_hash($_POST['passtemp'], PASSWORD_BCRYPT) : null;

    if (!empty($usuarioId)) {
        // Editar usuario
        $sql = "UPDATE usuarios SET usr = ?, correo = ?, dependenciaArea = ?, clave_area = ?, rol = ?";
        if ($passtemp) {
            $sql .= ", clave = ?, password_reset_required = 1";
        }
        $sql .= " WHERE id = ?";

        if ($stmt = $conn->prepare($sql)) {
            if ($passtemp) {
                $stmt->bind_param("ssssssi", $usuario, $correo, $area, $clave_area, $rol, $passtemp, $usuarioId);
            } else {
                $stmt->bind_param("sssssi", $usuario, $correo, $area, $clave_area, $rol, $usuarioId);
            }

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Usuario actualizado correctamente."]);
            } else {
                echo json_encode(["success" => false, "message" => "Error al actualizar usuario: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Error en la consulta: " . $conn->error]);
        }
    } else {
        // Insertar usuario
        $clave_encriptada = password_hash($_POST['passtemp'], PASSWORD_BCRYPT);

        $queryCreate = "INSERT INTO usuarios (usr, clave, correo, rol, dependenciaArea, clave_area, password_reset_required) 
                        VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmtCreate = $conn->prepare($queryCreate);

        if ($stmtCreate) {
            $stmtCreate->bind_param("ssssss", $usuario, $clave_encriptada, $correo, $rol, $area, $clave_area);
        
            if ($stmtCreate->execute()) {
                // Insertar en unidadesresponsables
                $queryInsertUnidad = "INSERT INTO unidadesresponsables (clave_area, nombre_area, claveProgramaP) VALUES (?, ?, ?)";
                $stmtInsertUnidad = $conn->prepare($queryInsertUnidad);
                if ($stmtInsertUnidad) {
                    $stmtInsertUnidad->bind_param("sss", $clave_area, $area, $clave_prog);
        
                    if ($stmtInsertUnidad->execute()) {
                        // Insertar en programas
                        $queryInsertarPrograma = "INSERT INTO programas (clave_area) VALUES (?)";
                        $stmtInsertPrograma = $conn->prepare($queryInsertarPrograma);
                        if ($stmtInsertPrograma) {
                            $stmtInsertPrograma->bind_param("s", $clave_area);
        
                            if ($stmtInsertPrograma->execute()) {
                                // Copiar actividades con la misma claveProgramaP pero asociadas a la nueva área
                                $queryCopiarActividades = "INSERT INTO listaactividades (
                                    nombre_area, claveProgramaP, nombreProgramaP, nombreActividad, 
                                    EjePMD, ObjetivoPMD, Indicador, frecuenciaMedición, unidadMedida, metaAnual, 
                                    metaTrim1, metaTrim2, metaTrim3, metaTrim4, MediosVerifi
                                ) SELECT ?, claveProgramaP, nombreProgramaP, nombreActividad, 
                                    EjePMD, ObjetivoPMD, Indicador, frecuenciaMedición, unidadMedida, metaAnual, 
                                    metaTrim1, metaTrim2, metaTrim3, metaTrim4, MediosVerifi
                                FROM listaactividades 
                                WHERE claveProgramaP = ?";
                                $stmtCopiarActividades = $conn->prepare($queryCopiarActividades);
        
                                if ($stmtCopiarActividades) {
                                    $stmtCopiarActividades->bind_param("ss", $clave_area, $clave_prog);
        
                                    if ($stmtCopiarActividades->execute()) {
                                        echo json_encode(["success" => true, "message" => "Usuario agregado y actividades copiadas correctamente."]);
                                    } else {
                                        echo json_encode(["success" => false, "message" => "Error al copiar actividades: " . $stmtCopiarActividades->error]);
                                    }
                                    $stmtCopiarActividades->close();
                                } else {
                                    echo json_encode(["success" => false, "message" => "Error en la consulta de copiar actividades: " . $conn->error]);
                                }
                            } else {
                                echo json_encode(["success" => false, "message" => "Error al insertar en programas: " . $stmtInsertPrograma->error]);
                            }
                        } else {
                            echo json_encode(["success" => false, "message" => "Error en la consulta de programas: " . $conn->error]);
                        }
                    } else {
                        echo json_encode(["success" => false, "message" => "Error al insertar en unidadesresponsables: " . $stmtInsertUnidad->error]);
                    }
                    $stmtInsertUnidad->close();
                } else {
                    echo json_encode(["success" => false, "message" => "Error en la consulta de unidadesresponsables: " . $conn->error]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Error al agregar usuario: " . $stmtCreate->error]);
            }
            $stmtCreate->close();
        } else {
            echo json_encode(["success" => false, "message" => "Error en la consulta: " . $conn->error]);
        }
    }
    exit();
}

// Verificar si el rol es admin
if ($rol !== 'admin') {
    echo "<script>
        alert('No tienes permiso para acceder a esta página.');
        history.back();
    </script>";
    exit();
}

// Estructura base de opciones
$baseOpciones = [
    ["title" => "Inicio", 
    "link" => "../plataforma/dashboard.php",
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
            "title" => "Cargar nuevas actividades",
            "link" => "../formularios/procesar_csv.php",
            "icon" => "fas fa-users-cog" // Ícono para Administrar Usuarios
        ],
        [
            "title" => "Modificar Programas",
            "link" => "../formularios/programa_presupuestario/editar_prog.php",
            "icon" => "fas fa-file-csv" // Ícono para nuevas Actividades
        ],
        [
            "title" => "Ver Programas",
            "link" => "../formularios/programas_pre.php",
            "icon" => "fas fa-list-alt" // Ícono para Ver Programas
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
    <title>Administrar Usuarios</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/YOUR_KIT_CODE.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="../js/usuarios.js"></script>
    <script src="../js/datatables.js"></script>
    <script src="../js/eventos.js"></script>
    <link rel="stylesheet" href="../css/actualizar_usr.css">
    
</head>
<body>
<!-- Encabezado -->
<div class="header d-flex justify-content-between align-items-center p-3">
    <div class="logo">
        <img src="../img/ZIHUA_C.png" alt="Logo del Municipio" class="img-fluid">
    </div>
    <div class="user-options">
        <a href="../sesiones_conexiones/destruir_sesion.php" class="btn btn-danger logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span class="d-none d-md-inline">Cerrar Sesión</span>
        </a>
    </div>
</div>

<!-- Barra lateral -->
<div class="sidebar">
    <ul class="list-unstyled">
        <li><span class="menu-title">Menú <i class="fas fa-home"></i></span></li>
        <?php foreach ($opcionesMenu as $opcion): ?>
            <li>
                <a href="<?= $opcion['link'] ?>" class="d-block py-2 px-3">
                    <i class="<?= $opcion['icon'] ?>"></i>
                    <?= $opcion['title'] ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Contenido principal -->
<div class="content">
    <div class="container mt-4">
        <h2 class="text-center">Administrar Usuarios</h2>
        <button class="btn btn-success mb-3" id="btnAgregarUsuario" data-bs-toggle="modal" data-bs-target="#modalUsuario" data-action="agregar">
            <i class="fas fa-plus"></i> Agregar Usuario
        </button>
        <!-- Tabla de usuarios -->
        <div class="table-responsive">
            <table id="generalTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>Clave Área</th>
                        <th>Usuario</th>
                        <th>Área</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($usuarios)): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['clave_area']) ?></td>
                                <td><?= htmlspecialchars($usuario['usr']) ?></td>
                                <td><?= htmlspecialchars($usuario['dependenciaArea']) ?></td>
                                <td><?= htmlspecialchars($usuario['rol']) ?></td>
                                <td>
                                <button 
                                    class="btn btn-warning btnEditar" 
                                    data-usuario='<?= json_encode($usuario, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalUsuario"
                                    data-action="editar"
                                >Editar
                                </button>
                                <button class="btn btn-danger btn-sm btnEliminar" data-usuario="<?= htmlspecialchars($usuario['id']) ?>">
                                   <i class="fas fa-trash"></i> Eliminar
                                </button>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Modal para Agregar/Editar Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioLabel">Agregar Usuario</h5>
    
                <button class="btn btn-success mb-3" id="btnAgregarUsuario" data-bs-toggle="modal" data-bs-target="#modalUsuario" data-action="agregar">
                    <i class="fas fa-plus"></i> Agregar Usuario
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulario -->
                <form id="formUsuario" method="POST" action="actualizar_usr.php">
                    <!-- Campo oculto para ID de usuario (vacío en Agregar y con valor en Editar) -->
                    <input type="hidden" id="usuarioId" name="usuarioId" value="">

                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required value="">
                    </div>

                    <div class="mb-3">
                        <label for="area" class="form-label">Área</label>
                        <input type="text" class="form-control" id="area" name="area" required value="">
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo</label>
                        <input type="text" class="form-control" id="correo" name="correo" required value="">
                    </div>

                    <div class="mb-3">
                        <label for="passtemp" class="form-label">Asignar Contraseña Temporal:</label>
                        <input type="password" class="form-control" id="passtemp" name="passtemp">
                    </div>

                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol">
                            <option value="" disabled>Selecciona un rol</option>
                            <option value="usuario">Usuario</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="clave_area" class="form-label">Clave Área</label>
                        <input type="text" class="form-control" id="clave_area" name="clave_area" required value="">
                    </div>

                    <div class="mb-3">
                        <label for="clave_prog" class="form-label">Clave Programa</label>
                        <input type="text" class="form-control" id="clave_prog" name="clave_prog" value="">
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
