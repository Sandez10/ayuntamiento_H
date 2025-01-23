<?php
include 'database/conexion.php'; // Conexión a la base de datos.

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    $nombreArchivo = $_FILES['archivo']['tmp_name'];

    if (($gestor = fopen($nombreArchivo, 'r')) !== false) {
        // Ignorar la primera fila (encabezados)
        fgetcsv($gestor);

        // Preparar el query para actualizar los datos
        $query = "UPDATE listaactividades SET 
                  MediosVerifi = ?
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
            if (count($datos) == 16) {
                // Asignar valores desde el CSV
                $id_actividades = (int)$datos[4];  // Asegurarse de que sea un número
                $MediosVerifi = $datos[15];

                // Verificar el valor de MediosVerifi
                echo "MediosVerifi: " . htmlspecialchars($MediosVerifi) . "<br>";

                // Vincular parámetros y ejecutar la actualización
                $stmt->bind_param(
                    'si',  // 's' para cadenas, 'i' para enteros
                    $MediosVerifi,  // Campo MediosVerifi
                    $id_actividades  // Campo id_actividades
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
