<?php
session_start();
include '../database/conexion.php';

// Obtener clave_area y rol del usuario activo
$queryUsuario = "SELECT clave_area, rol FROM usuarios WHERE usr = ?";
$stmtUsuario = $conn->prepare($queryUsuario);
$stmtUsuario->bind_param("s", $_SESSION['usuario']);
$stmtUsuario->execute();
$stmtUsuario->bind_result($clave_area, $rol);
$stmtUsuario->fetch();
$stmtUsuario->close();

if ($rol !== 'admin') {
    echo "<script>alert('No tienes permiso para acceder a esta página.'); history.back();</script>";
    exit();
}

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$queryProgramas = "SELECT claveProgramaP FROM listapp";
$resultProgramas = $conn->query($queryProgramas);

// Procesar el formulario cuando se envíe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_actividades = $_POST["id_actividades"];
    $claveProgramaP = $_POST["claveProgramaP"];
    $codigo_indicador = $_POST["codigo_indicador"];
    $nivel_indicador = $_POST["nivel_indicador"];
    $tendencia = $_POST["tendencia"];
    $variables = $_POST["variables"];
    $metodologia = $_POST["metodologia"];
    $formula = $_POST["formula"];
    $suinpac = $_POST["suinpac"];

    // Consulta SQL para actualizar datos
    $query = "UPDATE listaactividades 
              SET claveProgramaP = ?, codigo_indicador = ?, nivel_indicador = ?, tendencia = ?, variables = ?, metodologia = ?, formula = ?, id_suinpac = ?
              WHERE id_actividades = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssii", $claveProgramaP, $codigo_indicador, $nivel_indicador, $tendencia, $variables, $metodologia, $formula, $suinpac, $id_actividades);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>Datos actualizados correctamente.</p>";
    } else {
        echo "<p style='color: red;'>Error al actualizar datos: " . $stmt->error . "</p>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Actividades</title>
    <link rel="stylesheet" href="tabla.css">
    <script>
        function cargarActividades() {
            let programaClave = document.getElementById("claveProgramaP").value;
            fetch(`cargar_area.php?clave=${programaClave}`)
                .then(response => response.json())
                .then(data => {
                    let selectActividades = document.getElementById("id_actividades");
                    selectActividades.innerHTML = '<option value="">-- Seleccione una actividad --</option>';
                    data.forEach(actividad => {
                        selectActividades.innerHTML += `<option value="${actividad.id}">${actividad.nombre}</option>`;
                    });
                })
                .catch(error => console.error("Error al cargar actividades:", error));
        }
    </script>
</head>
<body>
    <h1>Formulario de Actividades</h1>
    <form method="POST" action="">
        <label for="claveProgramaP">Seleccionar Programa:</label>
        <select id="claveProgramaP" name="claveProgramaP" onchange="cargarActividades()" >
            <option value="">-- Seleccione un programa --</option>
            <?php while ($row = $resultProgramas->fetch_assoc()) {
                echo "<option value='{$row['claveProgramaP']}'>{$row['claveProgramaP']}</option>";
            } ?>
        </select>

        <label for="id_actividades">Seleccionar Actividad:</label>
        <select id="id_actividades" name="id_actividades" >
            <option value="">-- Primero seleccione un programa --</option>
        </select>

        <label for="codigo_indicador">Código Indicador:</label>
        <input type="text" id="codigo_indicador" name="codigo_indicador" >

        <label for="nivel_indicador">Nivel Indicador:</label>
        <select id="nivel_indicador" name="nivel_indicador" >
            <option value="">-- Seleccione un nivel --</option>
            <option value="FIN">FIN</option>
            <option value="PROPÓSITO">PROPÓSITO</option>
            <option value="ACTIVIDAD">ACTIVIDAD</option>
            <option value="COMPONENTE">COMPONENTE</option>
            <option value="NULO">NULO</option>
        </select>

        <label for="tendencia">Tendencia:</label>
        <select id="tendencia" name="tendencia" >
            <option value="">-- Seleccione una tendencia --</option>
            <option value="ASCENDENTE">ASCENDENTE</option>
            <option value="DESCENDENTE">DESCENDENTE</option>
            <option value="NULO">NULO</option>
        </select>

        <label for="variables">Variables:</label>
        <input type="text" id="variables" name="variables" >

        <label for="metodologia">Metodología:</label>
        <input type="text" id="metodologia" name="metodologia" >

        <label for="formula">Fórmula:</label>
        <input type="text" id="formula" name="formula" >

        <label for="suinpac">id Suinpac:</label>
        <input type="number" id="suinpac" name="suinpac" >

        <input type="submit" value="Actualizar">
    </form>
</body>
</html>