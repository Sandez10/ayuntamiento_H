<?php
include '../database/conexion.php';

// Verificar si se ha subido el archivo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    $nombreArchivo = $_FILES['archivo']['tmp_name'];

    // Comprobar si el archivo existe
    if (!file_exists($nombreArchivo)) {
        echo "Error: No se recibió un archivo válido.";
        exit;
    }

    // Abrir el archivo CSV
    if (($gestor = fopen($nombreArchivo, 'r')) !== false) {
        fgetcsv($gestor);  // Leer la primera línea (cabecera) y saltarla

        // Consulta de inserción
        $query = "INSERT INTO listaactividades (
            nombre_area, 
            claveProgramaP, 
            nombreProgramaP, 
            nombreActividad, 
            EjePMD, 
            ObjetivoPMD, 
            Indicador, 
            unidadMedida, 
            frecuenciaMedición, 
            metaAnual, 
            metaTrim1, 
            metaTrim2, 
            metaTrim3, 
            metaTrim4, 
            MediosVerifi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Preparar la consulta
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $conn->error;
            exit;
        }

        $filasProcesadas = 0;
        $filasIgnoradas = 0;

        // Procesar el archivo CSV línea por línea
        while (($datos = fgetcsv($gestor, 1000, ',')) !== false) {
            // Verificar que la fila tenga exactamente 15 columnas
            if (count($datos) == 15) {
                // Asignar valores desde el CSV
                $nombre_area = $datos[0];
                $claveProgramaP = $datos[1];
                $nombreProgramaP = $datos[2];
                $nombreActividad = $datos[3];
                $EjePMD = $datos[4];
                $ObjetivoPMD = $datos[5];
                $Indicador = $datos[6];
                $unidadMedida = $datos[7];
                $frecuenciaMedición = $datos[8];
                $metaAnual = (int)$datos[9]; // Asegurarse de que sea un número
                $metaTrim1 = (int)$datos[10];
                $metaTrim2 = (int)$datos[11];
                $metaTrim3 = (int)$datos[12];
                $metaTrim4 = (int)$datos[13];
                $MediosVerifi = $datos[14];

                // Vincular parámetros y ejecutar la inserción
                $stmt->bind_param(
                    'sssssssssiiiiis',  // El formato de los datos
                    $nombre_area,
                    $claveProgramaP,
                    $nombreProgramaP,
                    $nombreActividad,
                    $EjePMD,
                    $ObjetivoPMD,
                    $Indicador,
                    $unidadMedida,
                    $frecuenciaMedición,
                    $metaAnual,
                    $metaTrim1,
                    $metaTrim2,
                    $metaTrim3,
                    $metaTrim4,
                    $MediosVerifi
                );

                // Ejecutar la consulta y verificar si fue exitosa
                if ($stmt->execute()) {
                    $filasProcesadas++;
                } else {
                    echo "Error al ejecutar la consulta para la fila: " . implode(',', $datos) . ". Error: " . $stmt->error . "<br>";
                }
            } else {
                $filasIgnoradas++;
                echo "Fila ignorada (número de columnas incorrecto): " . implode(',', $datos) . "<br>";
            }
        }

        // Cerrar el archivo y mostrar el resumen
        fclose($gestor);
        echo "<p style='color: green;'>Importación finalizada.</p>";
        echo "Filas procesadas: $filasProcesadas<br>";
        echo "Filas ignoradas: $filasIgnoradas<br>";
    } else {
        echo "Error al abrir el archivo.";
    }
} else {
    echo "Error: No se ha subido un archivo.";
}
?>
