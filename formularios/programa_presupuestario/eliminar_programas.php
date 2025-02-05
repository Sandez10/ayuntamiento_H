<?php
session_start();
include "../../database/conexion.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
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
