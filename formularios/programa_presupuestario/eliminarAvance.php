<?php
session_start();
include '../../database/conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_actividad'])) {
    $id_actividad = $_POST['id_actividad'];

    // Eliminar los registros de avance de la actividad seleccionada
    $queryEliminar = "DELETE FROM avances_mensuales WHERE id_actividades = ?";
    $stmtEliminar = $conn->prepare($queryEliminar);
    $stmtEliminar->bind_param("i", $id_actividad);
    
    if ($stmtEliminar->execute()) {
        header("Location: mas_info.php?claveProgramaP=" . $_GET['claveProgramaP']);
    } else {
        echo "Error al eliminar el registro.";
    }

    $stmtEliminar->close();
}

$conn->close();
?>
