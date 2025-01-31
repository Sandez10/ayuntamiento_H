<?php
session_start();
include "../../database/conexion.php";
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claveProgramaP'], $_POST['nombre_area'],$_POST['clave_area'])) {
    $claveProgramaP = $_POST['claveProgramaP'];
    $nombre_area = $_POST['nombre_area'];
    $clave_area = $_POST['clave_area'];

    // Eliminar actividades asociadas SOLO para el área específica
    $queryEliminarActividades = "DELETE FROM listaactividades WHERE claveProgramaP = ? AND nombre_area = ?";
    $stmtEliminarActividades = $conn->prepare($queryEliminarActividades);
    $stmtEliminarActividades->bind_param("ss", $claveProgramaP,$clave_area);
    $stmtEliminarActividades->execute();
    $stmtEliminarActividades->close();

    // Eliminar la relación del área específica con la claveProgramaP
    $queryEliminarUnidad = "DELETE FROM unidadesresponsables WHERE claveProgramaP = ? AND nombre_area = ?";
    $stmtEliminarUnidad = $conn->prepare($queryEliminarUnidad);
    $stmtEliminarUnidad->bind_param("ss", $claveProgramaP, $nombre_area);

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
