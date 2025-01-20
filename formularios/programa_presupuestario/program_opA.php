<?php

include '../../database/conexion.php';


$dato = 1;
$queryUsuario = "SELECT claveProgramaP, nombreProgramaP, nombreActividad, id_actividades FROM listaactividades WHERE id_actividades >= ?";
$stmtUsuario = $conn->prepare($queryUsuario);
$stmtUsuario->bind_param("i", $dato); // Especificamos el tipo "i" para integer
$stmtUsuario->execute();
$stmtUsuario->bind_result($claveProgramaP, $nombreProgramaP, $nombreActividad, $id_actividades);

// Mostrar los resultados
while ($stmtUsuario->fetch()) {
    echo "Clave Programa: " . htmlspecialchars($claveProgramaP) . "<br>";
    echo "Nombre Programa: " . htmlspecialchars($nombreProgramaP) . "<br>";
    echo "Nombre Actividad: " . htmlspecialchars($nombreActividad) . "<br>";
    echo "ID Actividades: " . htmlspecialchars($id_actividades) . "<br>";
    echo "<hr>"; // Separador para cada fila
}

$stmtUsuario->close();

?>