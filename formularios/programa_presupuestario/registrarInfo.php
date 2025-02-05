<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

include '../../database/conexion.php';


// Configurar el idioma para que los meses se muestren en español
setlocale(LC_TIME, 'es_ES.UTF-8'); // Puedes probar 'Spanish' si no funciona
$Meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');

// Obtener la fecha actual
$mesActual = date('n'); // Número del mes actual (1-12)
$diaActual = date('j'); // Día del mes actual

// Obtener clave_area y es_admin del usuario activo
$queryUsuario = "SELECT clave_area, rol FROM usuarios WHERE usr = ?";
$stmtUsuario = $conn->prepare($queryUsuario);
$stmtUsuario->bind_param("s", $_SESSION['usuario']);
$stmtUsuario->execute();
$stmtUsuario->bind_result($clave_area, $rol);
$stmtUsuario->fetch();
$stmtUsuario->close();


// Obtener los parámetros claveProgramaP y clave_area
$claveProgramaP = isset($_GET['claveProgramaP']) ? $_GET['claveProgramaP'] : null;
$clave_area = isset($_GET['clave_area']) ? $_GET['clave_area'] : $clave_area;

// Procesar formulario si se envían avances
// Procesar formulario si se envían avances
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si el usuario es administrador
    $esAdmin = ($rol === 'admin');

$actividad = isset($_POST['id_actividades']) ? intval($_POST['id_actividades']) : null;
    $mesSeleccionado = isset($_POST['mes']) ? intval($_POST['mes']) : $mesActual;
    $avanceMes = intval($_POST['avance']);
    $beneficiarios = htmlspecialchars($_POST['beneficiarios']);
    $mesAnterior = ($mesActual == 1) ? 12 : $mesActual - 1; // Si es enero, el mes anterior es diciembre

    // Manejo del archivo PDF
    if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
        $archivoTmp = $_FILES['evidencia']['tmp_name'];
        $nombreArchivo = basename($_FILES['evidencia']['name']);
        $tipoArchivo = $_FILES['evidencia']['type'];

        // Validar el tipo MIME
        if ($tipoArchivo !== 'application/pdf') {
            echo "<script>alert('El archivo debe ser un PDF.');</script>";
            exit();
        }

        // Definir la ruta para guardar el archivo
        $directorioSubida = '../../uploads/';
        if (!is_dir($directorioSubida)) {
            mkdir($directorioSubida, 0777, true); // Crear el directorio si no existe
        }
        $rutaDestino = $directorioSubida . $nombreArchivo;

        // Mover el archivo al directorio definido
        if (!move_uploaded_file($archivoTmp, $rutaDestino)) {
            echo "<script>alert('Error al guardar el archivo.');</script>";
            exit();
        }
    } else {
        echo "<script>alert('Por favor, sube un archivo válido.');</script>";
        exit();
    }

    if ($claveProgramaP === null || $clave_area === null) {
        die("Faltan parámetros requeridos.");
    }
    if (!$mesSeleccionado || !$avanceMes || !$beneficiarios) {
        echo "<script>alert('Faltan datos para registrar el avance.');</script>";
    }


    // Calcular el mes anterior

    // Validar el mes seleccionado para usuarios no admin
    if (!$esAdmin && !($mesSeleccionado === $mesAnterior || ($mesSeleccionado === $mesActual && $diaActual <= 7))) {
        echo "<script>alert('Solo se puede registrar avance para el mes anterior o el mes actual.');</script>";
        exit(); // Detener la ejecución del script
    }
    // Solo aplicar la validación del mes actual si el usuario NO es admin
/*    elseif (!$esAdmin && $mesSeleccionado !== $mesActual) {
        echo "<script>alert('Solo se puede registrar avance para el mes actual.');</script>";
    } */else {
        // Insertar el avance en la tabla avances_mensuales
        $queryInsertAvance = "INSERT INTO avances_mensuales (clave_area, mes, avance, avanceBeneficiario, avanceEvidencia, nombreEvidencia, id_actividades) 
                            VALUES (?,?, ?, ?, ?, ?,?)";
        $stmtInsertAvance = $conn->prepare($queryInsertAvance);
        $stmtInsertAvance->bind_param("sissssi", $clave_area, $mesSeleccionado, $avanceMes, $beneficiarios, $rutaDestino, $nombreArchivo, $actividad);

        if ($stmtInsertAvance->execute()) {
            echo "<script>alert('Avance mensual registrado correctamente.');</script>";
        } else {
            echo "<script>alert('Error al insertar el avance mensual.');</script>";
        }
    }
}

// Obtener las actividades
$queryActividades = "SELECT id_actividades, nombreActividad FROM listaactividades WHERE claveProgramaP = ? AND nombre_area = ?";
$stmtActividades = $conn->prepare($queryActividades);
$stmtActividades->bind_param("ss", $claveProgramaP, $clave_area);
$stmtActividades->execute();
$resultActividades = $stmtActividades->get_result();

// Obtener los datos generales (una vez)
$queryDatosGenerales = "SELECT nombreActividad, EjePMD, ObjetivoPMD, Indicador, frecuenciaMedición, unidadMedida,  metaAnual, metaTrim1, metaTrim2, metaTrim3, metaTrim4, MediosVerifi FROM listaactividades WHERE claveProgramaP = ? AND nombre_area = ? LIMIT 1";
$stmtDatosGenerales = $conn->prepare($queryDatosGenerales);
$stmtDatosGenerales->bind_param("ss", $claveProgramaP, $clave_area);
$stmtDatosGenerales->execute();
$resultDatosGenerales = $stmtDatosGenerales->get_result();
$datosGenerales = $resultDatosGenerales->fetch_assoc();

// Si se detecta una solicitud AJAX, devolver los datos en formato JSON
if (isset($_GET['id_actividad'])) {
    $actividadId = $_GET['id_actividad'];
    
    // Obtener los datos de la actividad
    $query = "SELECT nombreActividad, EjePMD, ObjetivoPMD, Indicador, frecuenciaMedición, unidadMedida, metaAnual,  metaAnual, metaTrim1, metaTrim2, metaTrim3, metaTrim4, MediosVerifi 
              FROM listaactividades WHERE id_actividades = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $actividadId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        echo json_encode([
            'success' => true,
            'datosGenerales' => $datos
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron datos para esta actividad'
        ]);
    }
    exit(); // Terminar la ejecución para evitar que se muestre otra salida.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Actividades</title>
    <link rel="stylesheet" href="../../css/registrarInfo.css">
</head>
<body>
    <div class="content">
        <header>
            <button type="button" class="btn-salir" onclick="location.href='../../sesiones_conexiones/destruir_sesion.php';">Cerrar sesión</button>
        </header>

        <main>
            <form method="POST" enctype="multipart/form-data" action="" class="form-avance">
                <input type="hidden" name="claveProgramaP" value="<?php echo htmlspecialchars($claveProgramaP); ?>">
                <input type="hidden" name="clave_area" value="<?php echo htmlspecialchars($clave_area); ?>">
                <div class="container">
                    <h3>Datos Generales</h3>
                    <button id="toggleBtn" type="button">Mostrar Datos Generales</button>
                    <div id="datosGenerales" style="display: none;">
                        <?php if ($datosGenerales): ?>
                            <div class="row">
                                <div class="label">Nombre de la Actividad:</div>
                                <div class="input">
                                    <textarea id="nombreActividad" rows="3" disabled><?= htmlspecialchars($datosGenerales['nombreActividad']) ?></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="label">Eje del PMD:</div>
                                <div class="input">
                                    <textarea id="EjePMD" rows="3" disabled><?= htmlspecialchars($datosGenerales['EjePMD']) ?></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="label">Objetivo del PMD:</div>
                                <div class="input">
                                    <textarea id="ObjetivoPMD" rows="3" disabled><?= htmlspecialchars($datosGenerales['ObjetivoPMD']) ?></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="label">Indicador:</div>
                                <div class="input">
                                    <textarea id="Indicador" rows="3" disabled><?= htmlspecialchars($datosGenerales['Indicador']) ?></textarea>
                                </div>
                            </div>
                            <div class="row-group">
                                <div class="column">
                                    <div class="label">Frecuencia de Medición:</div>
                                    <div class="input">
                                        <textarea id="unidadMedida" rows="3" disabled><?= htmlspecialchars($datosGenerales['unidadMedida']) ?></textarea>
                                    </div>
                                </div>
                                <div class="column">
                                    <div class="label">Unidad de Medida:</div>
                                    <div class="input">
                                        <textarea id="frecuenciaMedición" rows="3" disabled><?= htmlspecialchars($datosGenerales['frecuenciaMedición']) ?></textarea>
                                    </div>
                                </div>
                                <div class="column">
                                    <div class="label">Meta Anual:</div>
                                    <div class="input">
                                        <textarea id="metaAnual" rows="3" disabled><?= htmlspecialchars($datosGenerales['metaAnual']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="label">Medio de Verificación:</div>
                                <div class="input">
                                    <textarea id="MediosVerifi" rows="3" disabled><?= htmlspecialchars($datosGenerales['MediosVerifi']) ?></textarea>
                                </div>
                            </div>
                        <?php else: ?>
                            <p>No se encontraron datos generales para mostrar.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <h3>Registrar Avance Mensual</h3>
                <label for="actividad">Actividad:</label>
                <select name="id_actividades" id="actividad" required>
                    <?php
                    if ($resultActividades->num_rows > 0) {
                        foreach ($resultActividades as $actividad) {
                            if ($rol !== 'admin') {
                                // Nombre exacto de la actividad que quieres ocultar
                                $actividadOculta = "Control y administración de recursos humanos";

                                // Comparar el nombre de la actividad actual con el nombre que quieres ocultar
                                if (trim($actividad['nombreActividad']) === $actividadOculta) {
                                    continue; // Omitir esta actividad para usuarios que no sean admin
                                }
                            }
                            echo "<option value='" . htmlspecialchars($actividad['id_actividades']) . "'>" . htmlspecialchars($actividad['nombreActividad']) . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No se encontraron actividades.</option>";
                    }
                    ?>
                </select>
                <div class="row-group2">
                    <div class="meta-container">Meta Trimestrales</div>
                    <div class="row-group">
                        <div class="label">Trimestre 1</div>
                        <div class="label">Trimestre 2</div>
                        <div class="label">Trimestre 3</div>
                        <div class="label">Trimestre 4</div>
                    </div>
                    <div class="row-group">
                        <input type="text" id="trim1" disabled value="<?php echo htmlspecialchars($datosGenerales['metaTrim1']); ?>">
                        <input type="text" id="trim2" disabled value="<?php echo htmlspecialchars($datosGenerales['metaTrim2']); ?>">
                        <input type="text" id="trim3" disabled value="<?php echo htmlspecialchars($datosGenerales['metaTrim3']); ?>">
                        <input type="text" id="trim4" disabled value="<?php echo htmlspecialchars($datosGenerales['metaTrim4']); ?>">
                    </div>
                    <label for="mes">Mes:</label>
                    <select name="mes" id="mes" required>
                    <?php
                    $mesAnterior = ($mesActual == 1) ? 12 : $mesActual - 1; // Si es enero, el mes anterior es diciembre

                    // Verifica si el usuario es admin
                    $esAdmin = isset($_SESSION['usuario']) && $rol === 'admin';

                    for ($mes = 1; $mes <= 12; $mes++) {
                        $nombreMes = $Meses[$mes - 1]; // Tomar el nombre desde el array

                        // Para usuarios no admin: Solo pueden seleccionar el mes anterior o el mes actual (si aún están dentro del límite del día 6)
                        $habilitadoParaUsuario = ($mes === $mesAnterior || ($mes === $mesActual && $diaActual <= 6));

                        // Si no es admin y el mes no está permitido, se deshabilita
                        $disabled = (!$esAdmin && !$habilitadoParaUsuario) ? 'disabled' : '';
                        $selected = ($mes == $mesActual) ? 'selected' : '';

                        echo "<option value='$mes' $selected $disabled>$nombreMes</option>";
                    }
                    ?>
                </select>
                    <label for="avance">Avance:</label>
                    <input type="number" name="avance" id="avance" min="0" placeholder="Ingresa el avance">

                    <label for="beneficiarios">Beneficiarios:</label>
                    <input type="number" name="beneficiarios" id="beneficiarios" min="0" required placeholder="Ingresa la cantidad de los beneficiarios">

                    <label for="evidencia">Evidencia:</label>
                    <input type="file" name="evidencia" id="evidencia" required accept="application/pdf">

                    <button type="submit" class="btn-submit">Registrar Avance</button>
                </div>
            </form>
        </main>

        <button type="button" class="btn-regresar" onclick="location.href='../../formularios/pre_a.php';">Regresar</button>
    </div>

    <script>
document.addEventListener("DOMContentLoaded", () => {
    const selectActividad = document.getElementById("actividad");

    selectActividad.addEventListener("change", () => {
        const actividadId = selectActividad.value;

        // Limpiar los campos antes de realizar la solicitud
        document.getElementById('nombreActividad').value = '';
        document.getElementById('EjePMD').value = '';
        document.getElementById('ObjetivoPMD').value = '';
        document.getElementById('Indicador').value = '';
        document.getElementById('frecuenciaMedición').value = '';
        document.getElementById('unidadMedida').value = '';
        document.getElementById('metaAnual').value = '';
        document.getElementById('trim1').value = '';
        document.getElementById('trim2').value = '';
        document.getElementById('trim3').value = '';
        document.getElementById('trim4').value = '';
        document.getElementById('MediosVerifi').value = '';

        // Realizar la solicitud para obtener los datos de la actividad
        fetch(`?id_actividad=${actividadId}`, {


            method: "GET",
        })
        .then(response => {
            if (!response.ok) {
                throw new Error("Error en la respuesta del servidor");
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('nombreActividad').value = data.datosGenerales.nombreActividad;
                document.getElementById('EjePMD').value = data.datosGenerales.EjePMD;
                document.getElementById('ObjetivoPMD').value = data.datosGenerales.ObjetivoPMD;
                document.getElementById('Indicador').value = data.datosGenerales.Indicador;
                document.getElementById('frecuenciaMedición').value = data.datosGenerales.frecuenciaMedición;
                document.getElementById('unidadMedida').value = data.datosGenerales.unidadMedida;
                document.getElementById('metaAnual').value = data.datosGenerales.metaAnual;
                document.getElementById('trim1').value = data.datosGenerales.metaTrim1;
                document.getElementById('trim2').value = data.datosGenerales.metaTrim2;
                document.getElementById('trim3').value = data.datosGenerales.metaTrim3;
                document.getElementById('trim4').value = data.datosGenerales.metaTrim4;
                document.getElementById('MediosVerifi').value = data.datosGenerales.MediosVerifi;
            } else {
                alert('No se encontraron datos para esta actividad');
            }
        })
        .catch(error => {
            console.error('Error al obtener los datos:', error);
            alert('Hubo un error al cargar los datos.');
        });
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.getElementById("toggleBtn");
    const datosGenerales = document.getElementById("datosGenerales");

    toggleBtn.addEventListener("click", () => {
        // Alternar la visibilidad de los datos generales
        if (datosGenerales.style.display === "none") {
            datosGenerales.style.display = "block";
            toggleBtn.textContent = "Ocultar Datos Generales"; // Cambiar texto del botón
        } else {
            datosGenerales.style.display = "none";
            toggleBtn.textContent = "Mostrar Datos Generales"; // Cambiar texto del botón
        }
    });
});


// Función para deshabilitar o habilitar el botón según las condicionesfunction verificarTrimestresYInsertar() {
  // Mes actual (en formato 1-12)
  const mesActual = new Date().getMonth() + 1;  // getMonth() devuelve un valor de 0-11
  
  // Definir trimestres y su valor con actividades específicas (esto puede variar según la actividad)
  const trimestres = [
    { valor: trim1, mes: 1, trimestre: "Trim1" },  // trim1 en el mes 1 (enero)
    { valor: trim2, mes: 4, trimestre: "Trim2" },  // trim2 en el mes 4 (abril)
    { valor: trim3, mes: 7, trimestre: "Trim3" },  // trim3 en el mes 7 (julio)
    { valor: trim4, mes: 10, trimestre: "Trim4" }, // trim4 en el mes 10 (octubre)
    // Agregar más trimestres si es necesario
  ];

  // Bucle para revisar cada trimestre
  trimestres.forEach(function(trim) {
    const boton = document.getElementById('registrarAvance');  // ID del botón "Registrar avance"
    
    // Si el trimestre tiene valor 0 y coincide con el mes actual, deshabilitar el botón
    if (trim.valor == 0 && trim.mes == mesActual) {
      boton.disabled = true;

      // Aquí insertamos el dato, por ejemplo, a través de una llamada a la base de datos o actualizando una variable
      insertarDato(trim.trimestre, mesActual);  // Insertar el dato cuando el trimestre tiene valor 0
    }
    // Si el trimestre tiene valor diferente a 0 y coincide con el mes actual, habilitar el botón
    else if (trim.valor != 0 && trim.mes == mesActual) {
      boton.disabled = false;
    }
  });


// Función de inserción de datos
function insertarDato(trimestre, mes) {
  console.log(`Insertando dato para ${trimestre} en el mes ${mes}`);
  
}

verificarTrimestresYInsertar();

    </script>
</body>
</html>