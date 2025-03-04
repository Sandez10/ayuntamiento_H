<?php
session_start();

include '../database/conexion.php';

if (isset($_GET['id'])) {
    $id_actividades = $_GET['id'];

    // Consulta para obtener los datos de la actividad seleccionada
    $query = "SELECT id_actividades, nombre_area, claveProgramaP, nombreProgramaP, nombreActividad 
              FROM listaactividades 
              WHERE id_actividades = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_actividades);
    $stmt->execute();
    $stmt->bind_result($id_actividades, $nombre_area, $claveProgramaP, $nombreProgramaP, $nombreActividad);
    $stmt->fetch();
    $stmt->close();

    // Devolver los datos en formato JSON
    echo json_encode([
        "id_actividades" => $id_actividades,
        "nombre_area" => $nombre_area,
        "claveProgramaP" => $claveProgramaP,
        "nombreProgramaP" => $nombreProgramaP,
        "nombreActividad" => $nombreActividad
    ]);
}
?>