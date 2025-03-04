<?php
session_start();
include '../database/conexion.php';

if (isset($_GET['clave'])) {
    $claveProgramaP = $_GET['clave'];

    $query = "SELECT la.id_actividades AS id, la.nombreActividad AS nombre 
              FROM listaactividades la
              INNER JOIN listapp lp ON la.claveProgramaP = lp.claveProgramaP
              WHERE la.claveProgramaP = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $claveProgramaP);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $actividades = [];
    while ($row = $result->fetch_assoc()) {
        $actividades[] = $row;
    }

    echo json_encode($actividades);
    $stmt->close();
}

$conn->close();
?>
