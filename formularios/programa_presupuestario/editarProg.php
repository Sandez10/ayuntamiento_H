<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

include '../../database/conexion.php';

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

// Cerrar la conexión
$conn->close();
?>
 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avance de Actividades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../../css/editarProg.css">
    <style>
        /* Estilo para los efectos de fade-in y fade-out */
        .editable-row {
            display: none;
            transition: all 0.3s ease;
        }

        .editable-row.show {
            display: table-row;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <a href="../plataforma/dashboard.php" target="_blank">
                <img src="../../img/ZIHUA_C.png" alt="Logo">
            </a>
        </div>
        <nav>
            <ul>
                <li><span class="menu-title">Menú</span></li>
                <li><a href="../../formularios/pre_a.php">Ver Avances</a></li>
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
            <button type="button" class="btn-salir" onclick="location.href='../../sesiones_conexiones/destruir_sesion.php';">Cerrar sesión</button>
        </header>

        <form method="GET" class="row g-3 mb-4" id="search-form">
    <div class="col-md-9">
        <input type="text" name="busqueda" id="search-input" class="form-control" 
               placeholder="¿Quieres buscar algún programa? Escribe aquí..." 
               value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda'], ENT_QUOTES, 'UTF-8') : ''; ?>">
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-outline-primary w-50">Buscar</button>
    </div>
        </form>

        <div class="col-md-12 text-end mb-4">
            <button type="button" class="btn btn-primary" onclick="location.href='agregarPrograma.php';">Agregar</button>
        </div>
        <table>
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

        <button type="button" class="btn btn-secondary" onclick="location.href='../../plataforma/dashboard.php';">Regresar</button>
    </div>

    <script>
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



// Mostrar formulario para agregar
document.getElementById('btnAgregar').addEventListener('click', function() {
        document.getElementById('agregarFormulario').style.display = 'block';
    });

    // Cancelar la operación de agregar
    document.getElementById('cancelarAgregar').addEventListener('click', function() {
        document.getElementById('agregarFormulario').style.display = 'none';
    });

    // Enviar el formulario de agregar
    document.getElementById('formAgregar').addEventListener('submit', function(e) {
        e.preventDefault(); // Evitar el envío normal del formulario

        const claveProgramaP = document.getElementById('claveProgramaP').value;
        const nombreProgramaP = document.getElementById('nombreProgramaP').value;

        // Enviar los datos usando fetch (sin AJAX, para enviar al mismo archivo)
        fetch('agregarPrograma.php', {
            method: 'POST',
            body: new URLSearchParams({
                claveProgramaP: claveProgramaP,
                nombreProgramaP: nombreProgramaP
            })
        })
        .then(response => response.text())
        .then(data => {
            alert(data);  // Mostrar el mensaje de éxito o error
            if (data.includes('Éxito')) {
                location.reload(); // Recargar la página para ver los cambios
            }
        })
        .catch(error => console.error('Error al agregar programa:', error));
    });
    </script>
</body>
</html>
