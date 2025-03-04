<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}
// Incluir archivo de conexión
include '../database/conexion.php';

// Inicializar variables para mensajes
$message = '';
$message_type = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar los datos del formulario
    $clave_area = htmlspecialchars(trim($_POST['clave_area']));
    $nombre_area = htmlspecialchars(trim($_POST['nombre_area']));
    $claveProgramaP = htmlspecialchars(trim($_POST['claveProgramaP']));

    // Validar que los campos no estén vacíos
    if (empty($clave_area) || empty($nombre_area) || empty($claveProgramaP)) {
        $message = "Todos los campos son obligatorios.";
        $message_type = "error";
    } else {
// Consulta preparada para insertar en unidadesresponsables
$queryInsertAvance = "INSERT INTO unidadesresponsables (clave_area, nombre_area, claveProgramaP) VALUES (?, ?, ?)";
$stmtInsertAvance = $conn->prepare($queryInsertAvance);

if ($stmtInsertAvance) {
    // Vincular los parámetros
    $stmtInsertAvance->bind_param("sss", $clave_area, $nombre_area, $claveProgramaP);

    // Ejecutar la consulta
    if ($stmtInsertAvance->execute()) {
        // Si la inserción en unidadesresponsables fue exitosa, proceder con programas
        $queryInsertProgramas = "INSERT INTO programas (clave_area) VALUES (?)";
        $stmtInsertProgramas = $conn->prepare($queryInsertProgramas);

        if ($stmtInsertProgramas) {
            // Vincular el parámetro
            $stmtInsertProgramas->bind_param("s", $clave_area);

            // Ejecutar la consulta
            if ($stmtInsertProgramas->execute()) {
                $message = "Registro insertado correctamente en ambas tablas.";
                $message_type = "success";
            } else {
                // Verificar si el error es por duplicado
                if ($conn->errno === 1062) { // Código de error para duplicados en MySQL
                    $message = "El registro ya existe en la tabla 'programas'.";
                    $message_type = "error";
                } else {
                    $message = "Error al insertar en la tabla 'programas': " . $stmtInsertProgramas->error;
                    $message_type = "error";
                }
            }

            // Cerrar la consulta preparada para programas
            $stmtInsertProgramas->close();
        } else {
            $message = "Error en la preparación de la consulta para 'programas': " . $conn->error;
            $message_type = "error";
        }
    } else {
        // Verificar si el error es por duplicado
        if ($conn->errno === 1062) { // Código de error para duplicados en MySQL
            $message = "El registro ya existe en la tabla 'unidadesresponsables'. No se puede insertar nuevamente.";
            $message_type = "error";
        } else {
            $message = "Error al insertar en la tabla 'unidadesresponsables': " . $stmtInsertAvance->error;
            $message_type = "error";
        }
    }

    // Cerrar la consulta preparada para unidadesresponsables
    $stmtInsertAvance->close();
} else {
    $message = "Error en la preparación de la consulta para 'unidadesresponsables': " . $conn->error;
    $message_type = "error";
}

// Mostrar mensaje de resultado
echo "<div class='alert alert-{$message_type}'>{$message}</div>";

    }
}

// Cerrar la conexión a la base de datos
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insertar Datos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="../css/unidades.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <!-- Botón de regresar -->
            <a href="../plataforma/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>

            <!-- Botón de cerrar sesión -->
            <a href="../sesiones_conexiones/destruir_sesion.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>

        <div class="form-container shadow-sm p-4">
            <h2 class="text-primary text-center mb-4">Insertar Datos</h2>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="clave_area" class="form-label">Clave del Área:</label>
                    <input type="text" id="clave_area" name="clave_area" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="nombre_area" class="form-label">Nombre del Área:</label>
                    <input type="text" id="nombre_area" name="nombre_area" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="claveProgramaP" class="form-label">Clave del Programa:</label>
                    <input type="text" id="claveProgramaP" name="claveProgramaP" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Insertar</button>
            </form>

            <?php if (isset($message)): ?>
                <div class="mt-3 alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Validación básica -->
    <script>
        document.querySelector("form").addEventListener("submit", function (event) {
            const claveArea = document.getElementById("clave_area").value.trim();
            const nombreArea = document.getElementById("nombre_area").value.trim();
            const clavePrograma = document.getElementById("claveProgramaP").value.trim();

            if (!claveArea || !nombreArea || !clavePrograma) {
                event.preventDefault();
                alert("Todos los campos son obligatorios. Por favor, complétalos.");
            }
        });
    </script>
</body>
</html>