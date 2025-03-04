<?php
session_start();
include "../../database/conexion.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}
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
// Verificar si el rol es admin
if ($rol !== 'admin') {
    echo "<script>
        alert('No tienes permiso para acceder a esta página.');
        history.back();
    </script>";
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claveProgramaP'], $_POST['clave_area'])) {
    $claveProgramaP = $_POST['claveProgramaP'];
    $clave_area = $_POST['clave_area'];

    // Eliminar todas las actividades asociadas al área y claveProgramaP
    $queryEliminarActividades = "DELETE FROM listaactividades WHERE nombre_area = ? AND claveProgramaP = ?";
    $stmtEliminarActividades = $conn->prepare($queryEliminarActividades);
    $stmtEliminarActividades->bind_param("ss", $clave_area, $claveProgramaP);
    $stmtEliminarActividades->execute();
    $stmtEliminarActividades->close();
    // Eliminar la relación del área específica con la claveProgramaP
    $queryEliminarUnidad = "DELETE FROM unidadesresponsables WHERE clave_area = ? AND claveProgramaP = ?";
    $stmtEliminarUnidad = $conn->prepare($queryEliminarUnidad);
    $stmtEliminarUnidad->bind_param("ss", $clave_area, $claveProgramaP);

    if ($stmtEliminarUnidad->execute()) {
        header("Location: ../pre_a.php"); // Ajusta la redirección si es necesario
        exit();
    } else {
        echo "Error al eliminar el registro.";
    }
    $stmtEliminarUnidad->close();
}

$conn->close();
?>
