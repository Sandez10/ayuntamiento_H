<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

include '../../database/conexion.php';

// Variables para mostrar mensajes
$mensaje = '';

// Procesar el formulario para agregar nuevo programa
if (isset($_POST['clave_area'], $_POST['claveProgramaP'], $_POST['nombreProgramaP'], $_POST['actividad'], $_POST['ejercicioPMD'], $_POST['objetivoPMD'], $_POST['indicador'], $_POST['frecuencia'], $_POST['unidadMedida'], $_POST['metaAnual'], $_POST['trimestre1'], $_POST['trimestre2'], $_POST['trimestre3'], $_POST['trimestre4'], $_POST['mediosVerificacion'])) {
    $clave_area = $_POST['clave_area'];
    $nombre_area = $_POST['nombre_area'];  // Campo "Área"
    $claveProgramaP = $_POST['claveProgramaP'];
    $nombreProgramaP = $_POST['nombreProgramaP'];
    $actividad = $_POST['actividad'];
    $ejercicioPMD = $_POST['ejercicioPMD'];
    $objetivoPMD = $_POST['objetivoPMD'];
    $indicador = $_POST['indicador'];
    $frecuencia = $_POST['frecuencia'];
    $unidadMedida = $_POST['unidadMedida'];
    $metaAnual = $_POST['metaAnual'];
    $trimestre1 = $_POST['trimestre1'];
    $trimestre2 = $_POST['trimestre2'];
    $trimestre3 = $_POST['trimestre3'];
    $trimestre4 = $_POST['trimestre4'];
    $mediosVerificacion = $_POST['mediosVerificacion'];

// Insertar en la tabla listapp
$queryInsertListapp = "INSERT INTO listapp (claveProgramaP, nombreProgramaP) VALUES (?, ?)";
$stmtInsertListapp = $conn->prepare($queryInsertListapp);

if ($stmtInsertListapp) {
    $stmtInsertListapp->bind_param("ss", $claveProgramaP, $nombreProgramaP);
    if ($stmtInsertListapp->execute()) {
        
        // Insertar también en la tabla programa en el campo clave_area
        $queryInsertPrograma = "INSERT INTO programa (clave_area) VALUES (?)";
        $stmtInsertPrograma = $conn->prepare($queryInsertPrograma);

        if ($stmtInsertPrograma) {
            $stmtInsertPrograma->bind_param("s", $clave_area);
            if (!$stmtInsertPrograma->execute()) {
                $mensaje = 'Error al insertar la clave_area en la tabla programa.';
            }
            $stmtInsertPrograma->close();
        } else {
            $mensaje = 'Error en la preparación de la consulta de inserción en la tabla programa.';
        }

        // Insertar en la tabla unidadesresponsables
        $queryInsertUnidades = "INSERT INTO unidadesresponsables (clave_area, nombre_area, claveProgramaP) VALUES (?, ?, ?)";
        $stmtInsertUnidades = $conn->prepare($queryInsertUnidades);

        if ($stmtInsertUnidades) {
            $stmtInsertUnidades->bind_param("sss", $clave_area, $nombre_area, $claveProgramaP);
            if ($stmtInsertUnidades->execute()) {
                // Insertar en la tabla listaactividades
                $queryInsertActividades = "INSERT INTO listaactividades (nombre_area, claveProgramaP, nombreActividad, EjePMD, ObjetivoPMD, Indicador, frecuenciaMedición, unidadMedida, metaAnual, metaTrim1, metaTrim2, metaTrim3, metaTrim4, MediosVerifi) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtInsertActividades = $conn->prepare($queryInsertActividades);

                if ($stmtInsertActividades) {
                    $stmtInsertActividades->bind_param("ssssssssssssss", $nombre_area, $claveProgramaP, $actividad, $ejercicioPMD, $objetivoPMD, $indicador, $frecuencia, $unidadMedida, $metaAnual, $trimestre1, $trimestre2, $trimestre3, $trimestre4, $mediosVerificacion);
                    if ($stmtInsertActividades->execute()) {
                        $mensaje = 'Programa y actividades agregadas con éxito.';
                    } else {
                        $mensaje = 'Error al agregar las actividades.';
                    }
                    $stmtInsertActividades->close();
                } else {
                    $mensaje = 'Error en la preparación de la consulta de inserción de actividades.';
                }
            } else {
                $mensaje = 'Error al agregar la unidad responsable.';
            }
            $stmtInsertUnidades->close();
        } else {
            $mensaje = 'Error en la preparación de la consulta de inserción de unidad responsable.';
        }
    } else {
        $mensaje = 'Error al agregar el programa.';
    }
    $stmtInsertListapp->close();
} else {
    $mensaje = 'Error en la preparación de la consulta de inserción de programa.';
}

}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Programa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/agregarPrograma.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Gestionar Programas</h1>

        <!-- Mensaje de éxito o error -->
        <?php if ($mensaje): ?>
            <div class="alert alert-info text-center"><?= $mensaje ?></div>
        <?php endif; ?>

        <!-- Botón para mostrar el formulario -->
        <div class="d-flex justify-content-center mb-4">
            <button class="btn btn-primary" id="btnAgregar">Agregar Programa</button>
        </div>

        <!-- Formulario para agregar programa -->
        <div id="agregarFormulario" class="mt-4" style="display: none;">
            <form method="POST" id="formAgregar" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <label for="nombre_area" class="form-label">Área</label>
                        <input type="text" class="form-control" id="nombre_area" name="nombre_area" required>
                        <div class="invalid-feedback">Este campo es obligatorio.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="clave_area" class="form-label">Clave Área</label>
                        <input type="text" class="form-control" id="clave_area" name="clave_area" required>
                        <div class="invalid-feedback">Este campo es obligatorio.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="claveProgramaP" class="form-label">Clave del Programa</label>
                        <input type="text" class="form-control" id="claveProgramaP" name="claveProgramaP" required>
                        <div class="invalid-feedback">Este campo es obligatorio.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="nombreProgramaP" class="form-label">Nombre del Programa</label>
                        <input type="text" class="form-control" id="nombreProgramaP" name="nombreProgramaP" required>
                        <div class="invalid-feedback">Este campo es obligatorio.</div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="actividad" class="form-label">Nombre de la Actividad</label>
                        <input type="text" class="form-control" id="actividad" name="actividad" required>
                    </div>
                    <div class="col-md-6">
                        <label for="ejercicioPMD" class="form-label">Ejercicio PMD</label>
                        <input type="text" class="form-control" id="ejercicioPMD" name="ejercicioPMD" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="objetivoPMD" class="form-label">Objetivo PMD</label>
                        <input type="text" class="form-control" id="objetivoPMD" name="objetivoPMD" required>
                    </div>
                    <div class="col-md-6">
                        <label for="indicador" class="form-label">Indicador</label>
                        <input type="text" class="form-control" id="indicador" name="indicador" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="frecuencia" class="form-label">Frecuencia de Medición</label>
                        <input type="text" class="form-control" id="frecuencia" name="frecuencia" required>
                    </div>
                    <div class="col-md-6">
                        <label for="unidadMedida" class="form-label">Unidad de Medida</label>
                        <input type="text" class="form-control" id="unidadMedida" name="unidadMedida" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="metaAnual" class="form-label">Meta Anual</label>
                        <input type="text" class="form-control" id="metaAnual" name="metaAnual" required>
                    </div>
                    <div class="col-md-6">
                        <label for="mediosVerificacion" class="form-label">Medios de Verificación</label>
                        <input type="text" class="form-control" id="mediosVerificacion" name="mediosVerificacion" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="trimestre1" class="form-label">Trimestre 1</label>
                        <input type="text" class="form-control" id="trimestre1" name="trimestre1" required>
                    </div>
                    <div class="col-md-3">
                        <label for="trimestre2" class="form-label">Trimestre 2</label>
                        <input type="text" class="form-control" id="trimestre2" name="trimestre2" required>
                    </div>
                    <div class="col-md-3">
                        <label for="trimestre3" class="form-label">Trimestre 3</label>
                        <input type="text" class="form-control" id="trimestre3" name="trimestre3" required>
                    </div>
                    <div class="col-md-3">
                        <label for="trimestre4" class="form-label">Trimestre 4</label>
                        <input type="text" class="form-control" id="trimestre4" name="trimestre4" required>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <button type="submit" class="btn btn-success me-3">Agregar</button>
                    <button type="button" id="cancelarAgregar" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mostrar formulario para agregar
        document.getElementById('btnAgregar').addEventListener('click', function() {
            document.getElementById('agregarFormulario').style.display = 'block';
        });

        // Cancelar la operación de agregar y redirigir
        document.getElementById('cancelarAgregar').addEventListener('click', function() {
            window.location.href = 'editarProg.php';  // Redirige a editarProg.php
        });

        // Validación de formulario
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })();
    </script>
</body>
</html>
