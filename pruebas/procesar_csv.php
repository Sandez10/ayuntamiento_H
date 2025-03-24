<?php
include '../database/conexion.php'; // Conexión a la base de datos.

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    $nombreArchivo = $_FILES['archivo']['tmp_name'];

    if (($gestor = fopen($nombreArchivo, 'r')) !== false) {
        // Ignorar la primera fila (encabezados)
        fgetcsv($gestor);

        // Preparar el query para actualizar los datos
        $query = "UPDATE listaactividades SET 
                  clave_area = ?, 
                  claveProgramaP = ?, 
                  nombreProgramaP = ?, 
                  nombreActividad = ?, 
                  EjePMD = ?, 
                  ObjetivoPMD = ?, 
                  eje_PDE,
                  Indicador = ?, 
                  frecuenciaMedición = ?, 
                  unidadMedida = ?, 
                  metaAnual = ?, 
                  metaTrim1 = ?, 
                  metaTrim2 = ?, 
                  metaTrim3 = ?, 
                  metaTrim4 = ?, 
                  MediosVerifi = ?,
                  id_suinpac = ?,
                  codigo_indicador = ?,
                  nivel_indicador = ?, 
                  tendencia = ?,
                  variables = ?,
                  metodologia = ?,
                  formula = ?,
                  estrategia_PMD = ?,
                  supuestos = ?
                  WHERE id_actividades = ?";

        $stmt = $conn->prepare($query);

        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $conn->error;
            exit;
        }

        // Contadores de depuración
        $filasProcesadas = 0;
        $filasIgnoradas = 0;

        while (($datos = fgetcsv($gestor, 1000, ',')) !== false) {
            // Verificar que la fila tenga exactamente 16 columnas
            if (count($datos) == 25) {
                // Asignar valores desde el CSV
                $clave_area = $datos[0];
                $claveProgramaP = $datos[1];
                $nombreProgramaP = $datos[2];
                $nombreActividad = $datos[3];
                $id_actividades = (int)$datos[4];  // Asegurarse de que sea un número
                $EjePMD = $datos[5];
                $ObjetivoPMD = $datos[6];
                $eje_PDE = $datos[7];
                $Indicador = $datos[8];
                $unidadMedida = $datos[9];
                $frecuenciaMedición = $datos[10];
                $metaAnual = $datos[11];
                $metaTrim1 = $datos[12];
                $metaTrim2 = $datos[13];
                $metaTrim3 = $datos[14];
                $metaTrim4 = $datos[15];
                $MediosVerifi = $datos[16];
                $id_suinpac = $datos[17];
                $codigo_indicador = $datos[18];
                $nivel_indicador = $datos[19];
                $tendencia =$datos[20];
                $variables = $datos[21];
                $metodologia = $datos[22];
                $formula = $datos[23];
                $estrategia_PMD = $datos[24];
                $supuestos = $datos[25];


                // Vincular parámetros y ejecutar la actualización
                $stmt->bind_param(
                    'sssssssssiiiiisisissssssss',  // Tipos de datos: 's' para cadenas, 'i' para enteros
                    $clave_area,
                    $clavePrograma,
                    $nombrePrograma,
                    $nombreActividad,
                    $id_actividades,
                    $EjePM,
                    $ObjetivoPMD,
                    $eje_PDE,
                    $Indicado,
                    $unidadMedida,
                    $frecuenciaMedición,
                    $metaAnual,
                    $metaTrim1,
                    $metaTrim2,
                    $metaTrim3,
                    $metaTrim4,
                    $MediosVerifi,
                    $id_suinpac,
                    $codigo_indicador,
                    $nivel_indicador,
                    $tendencia,
                    $variables,
                    $metodologia,
                    $formula,
                    $estrategia_PMD,
                    $supuestos
                );

                if ($stmt->execute()) {
                    $filasProcesadas++;
                } else {
                    // Mostrar el error si la consulta no se ejecuta
                    echo "Error al ejecutar la consulta para id_actividades: $id_actividades. Error: " . $stmt->error . "<br>";
                }
            } else {
                // Fila ignorada por número incorrecto de columnas
                $filasIgnoradas++;
                echo "Fila ignorada (número de columnas incorrecto): " . implode(',', $datos) . "<br>";
            }
        }

        fclose($gestor);
        echo "Datos actualizados correctamente.<br>";
        echo "Filas procesadas: $filasProcesadas<br>";
        echo "Filas ignoradas: $filasIgnoradas<br>";
    } else {
        echo "Error al abrir el archivo.";
    }
} else {
    echo "No se recibió ningún archivo.";
}
?>