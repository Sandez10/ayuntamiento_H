<?php
session_start();
include '../../database/conexion.php';

// Obtener el usuario desde la sesión
$username = $_SESSION['usuario'];

// Obtener información adicional del usuario
$query = "SELECT rol FROM usuarios WHERE usr = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($rol);
$stmt->fetch();
$stmt->close();

// Verificar permisos de usuario
if ($rol !== 'admin') {
    echo "<script>
        alert('No tienes permiso para acceder a esta página.');
        history.back();
    </script>";
    exit();
}

// Verificar si se recibe una solicitud POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los valores del formulario
    $tipo = $_POST["tipo"];
    $area = $_POST["area"] ?? null;
    $nivelIndicador = $_POST["nivelIndicador"] ?? null;
    $clave = $_POST["clave"] ?? null;

    // Validar tipo de formato
    $formatosValidos = ['POA', 'PBR', 'MIR', 'FT', 'EP', 'AP', 'AO'];
    if (!in_array($tipo, $formatosValidos)) {
        die("Error: Tipo de formato no válido.");
    }

    // Llamar a la función correspondiente para generar el Excel según el tipo
    $archivoGenerado = generarExcel($tipo, $area, $nivelIndicador, $clave);

    if ($archivoGenerado) {
        echo "El archivo Excel ha sido generado con éxito.";
    } else {
        echo "Error al generar el archivo.";
    }
}

/**
 * Función para generar el archivo Excel según el tipo de formato.
 */
function generarExcel($tipo, $area, $nivelIndicador, $clave) {
    $filePath = null;

    switch ($tipo) {
        case "POA":
            // Validar que se seleccione un área
            if (!$area) {
                die("Error: Debes seleccionar un área para POA.");
            }
            // Establecer la ruta del archivo
            $filePath = __DIR__ . '/poa.php';
            break;

        case "FT":
            // Validar que se seleccione un nivel de indicador y una clave
            if (!$nivelIndicador || !$clave) {

                die("Error: Debes seleccionar un nivel de indicador y una clave.");
            }
            // Aquí deberías agregar la lógica para generar el archivo de Fichas Técnicas
            $filePath = 'estructura.php';
            break;

        case "PBR":
            // Agregar la lógica correspondiente para PBR si es necesario
            break;

        case "MIR":
            // Validar que se seleccione una clave
            if (!$clave) {
                die("Error: Debes seleccionar una clave para MIR.");
            }
            // Establecer la ruta del archivo
            $filePath = 'mir.php';
            break;

        case "EP":
            // Agregar la lógica correspondiente para EP
            break;

        case "AP":
            // Agregar la lógica correspondiente para AP
            break;

        case "AO":
            // Validar que se seleccione una clave
            if (!$clave) {
                die("Error: Debes seleccionar una clave para AO.");
            }
            // Aquí deberías agregar la lógica para generar el archivo de Árbol de Objetivos
            $filePath = __DIR__ . '/arbol_objetivos.php';
            break;

        default:
            return false; // Si no se reconoce el tipo, retorna falso
    }

    // Verificar si el archivo existe y se puede incluir
    if (file_exists($filePath)) {
        include $filePath; // Incluir el archivo correspondiente
        return true;
    } else {
        die("Error: El archivo $tipo no existe.");
    }
}
?>
