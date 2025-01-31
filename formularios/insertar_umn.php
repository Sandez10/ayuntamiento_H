<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Incluir archivo de conexión
include '../database/conexion.php';

// Valores a insertar (pueden venir de un formulario o ser dinámicos)
$clave_area = 'UNM';
$nombre_area = 'Unidad de Normatividad Municipal';
$claveProgramaP = 'F008';

// Consulta preparada para insertar el avance
$queryInsertAvance = "INSERT INTO unidadesresponsables (clave_area, nombre_area, claveProgramaP) VALUES (?, ?, ?)";
$stmtInsertAvance = $conn->prepare($queryInsertAvance);

if ($stmtInsertAvance) {
    // Vincular los parámetros
    $stmtInsertAvance->bind_param("sss", $clave_area, $nombre_area, $claveProgramaP);

    // Ejecutar la consulta
    if ($stmtInsertAvance->execute()) {
        echo "Registro insertado correctamente.";
    } else {
        // Verificar si el error es por duplicado
        if ($conn->errno === 1062) { // Código de error para duplicados en MySQL
            echo "El registro ya existe. No se puede insertar nuevamente.";
        } else {
            echo "Error al insertar el registro: " . $stmtInsertAvance->error;
        }
    }

    // Cerrar la consulta preparada
    $stmtInsertAvance->close();
} else {
    echo "Error en la preparación de la consulta: " . $conn->error;
}

// Cerrar la conexión a la base de datos
$conn->close();
?>