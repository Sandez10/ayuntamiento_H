<?php

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../index.php");
    exit();
}
include '../database/conexion.php';

// Verificar conexión a la base de datos
if ($conn->connect_error) {
    die("Error en la conexión a la base de datos: " . $conn->connect_error);
}

// Obtener el usuario desde la sesión
$username = $_SESSION['usuario'];

// Obtener información adicional del usuario
$query = "SELECT rol FROM usuarios WHERE usr = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($rol);
$stmt->fetch();
$stmt->close();

// Consulta para obtener las áreas desde la tabla "areas"
$areasQuery = "SELECT id_areas, nombre_area FROM areas";
$areasResult = $conn->query($areasQuery);

// Consulta para obtener las claves para la Estructura Programática
$clavesQuery = "SELECT id, clave_programa FROM programas";
$clavesResult = $conn->query($clavesQuery);

$claveProgramaPQuery = "SELECT claveProgramaP, nombreProgramaP FROM listapp";
$claveProgramaPResult = $conn->query($claveProgramaPQuery);

// Verificar si la consulta de claves fue exitosa
if ($clavesResult === false) {
    die("Error en la consulta de claves: " . $conn->error);
}

// Asegurarse de que la consulta retorna resultados
if ($clavesResult->num_rows == 0) {
    echo "No se encontraron claves para la Estructura Programática.";
}

// Estructura base de opciones
$baseOpciones = [
    ["title" => "Ver Programas", 
    "link" => "../formularios/pre_a.php",
    "icon" => "fas fa-list-alt" // Ícono para Ver Programas]
    ],
    [
        "title" => "Última Activid Registrada",
        "link" => "../formularios/avances.php",
        "icon" => "fas fa-chart-line" // Ícono para Avance General
    ]
];

// Inicializar el menú con las opciones base para todos los usuarios
$menuOpciones = [
    'default' => $baseOpciones,  // Menú base para todos los usuarios

];

// Verificar si el usuario es admin y agregarle opciones adicionales
if ($rol == 'admin') {
    $menuOpciones['admin'] = array_merge([
        [
            "title" => "Administrar Usuarios",
            "link" => "../sesiones_conexiones/actualizar_usr.php",
            "icon" => "fas fa-users-cog" // Ícono para Administrar Usuarios
        ],
        [
            "title" => "Cargar nuevas actividades",
            "link" => "../formularios/subir_csv.html",
            "icon" => "fas fa-file-csv  " // Ícono para nuevas Actividades
        ],
        [
            "title" => "Modificar Programas",
            "link" => "../formularios/programa_presupuestario/editar_prog.php",
            "icon" => "fas fa-edit" // Ícono para modificar Programas
        ],
        [
            "title" => "Ver Programas",
            "link" => "../formularios/programas_pre.php",
            "icon" => "fas fa-list-alt" // Ícono para Ver Programas
        ],
        [
            "title" => "Última Actividad Registrada ",
            "link" => "../formularios/avances.php",
            "icon" => "fas fa-history" // Ícono para Avance General
        ]        
/*        [
            "title" => "Áreas",
            "link" => "../formularios/areas.php",
            "icon" => "fas fa-cogs"  // Ícono para Áreas

        ]*/
    ]);
}
else{
    $menuOpciones['usuario'] = array_merge([],
         $baseOpciones);
}

// Determinar las opciones del menú para el usuario según su rol
$opcionesMenu = $menuOpciones[$rol] ?? $menuOpciones['default'];

// Verificar si el rol es admin
if ($rol !== 'admin') {
    echo "<script>
        alert('No tienes permiso para acceder a esta página.');
        history.back();
    </script>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPSEP FORMATOS</title>
    <!-- Estilos e íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/formatos.css">
</head>
<body>
    <!-- Encabezado -->
    <div class="header d-flex justify-content-between align-items-center px-3">
        <img src="../img/ZIHUA_C.png" alt="Logo del Municipio" style="height: 50px;">
        <a href="../sesiones_conexiones/destruir_sesion.php" class="btn btn-danger">
            <i class="fas fa-sign-out-alt"></i> <span class="d-none d-md-inline">Cerrar Sesión</span>
        </a>
    </div>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar p-3">
            <ul class="list-unstyled">
            <li>
                <a href="../plataforma/dashboard.php" class="menu-link">
                    <span class="menu-title">Inicio <i class="fas fa-home"></i></span>
                </a>
            </li>
                <?php foreach ($opcionesMenu as $opcion): ?>
                    <li>
                        <a href="<?= $opcion['link'] ?>" class="d-flex align-items-center">
                            <i class="<?= $opcion['icon'] ?> me-2"></i>
                            <?= $opcion['title'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Contenido principal -->
        <div class="content-wrapper">
            <div class="selector-form">
                <h3>Selecciona el tipo de formato para generar el archivo</h3>
                <form action="excel/generar_excel.php" method="post">
                    <label for="formato">Formato:</label>
                    <select name="tipo" id="tipo" required onchange="mostrarTipo()">
                        <option value="POA">POA</option>
                        <option value="PBR">PBR</option>
                        <option value="MIR">MIR</option>
                        <option value="FT">Fichas Técnicas</option>
                        <option value="EP">Estructura Programática</option>
                        <option value="AP">Árbol de Problemas</option>
                        <option value="AO">Árbol de Objetivos</option>
                    </select>

                    <!-- ÁREA (Solo visible si se elige POA) -->
                    <div id="campo_area" style="display:none;">
                        <label for="area">Área:</label>
                        <select name="area" id="area">
                            <option value="">Seleccione un área</option>
                            <?php while ($area = $areasResult->fetch_assoc()): ?>
                                <option value="<?= $area['id_areas'] ?>"><?= htmlspecialchars($area['nombre_area']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- NIVEL INDICADOR (Solo visible si se elige Fichas Técnicas) -->
                    <div id="campo_nivel" style="display:none;">
                        <label for="nivelIndicador">Nivel de Indicador:</label>
                        <select name="nivelIndicador" id="nivelIndicador">
                            <option value="actividad">Actividad</option>
                            <option value="componente">Componente</option>
                            <option value="fin">Fin</option>
                        </select>
                    </div>

                    <!-- CLAVE PROGRAMA (Visible si se elige PBR, MIR, EP, AP, AO) -->
                    <div id="campo_clave" style="display:none;">
                        <label for="clave">Selecciona una clave:</label>
                        <select name="clave" id="clave">
                            <option value="">Seleccione una clave</option>
                            <?php while ($clave = $claveProgramaPResult->fetch_assoc()): ?>
                                <option value="<?= $clave['claveProgramaP'] ?>">
                                    <?= htmlspecialchars($clave['claveProgramaP'] . "-" . $clave['nombreProgramaP']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <input type="submit" value="Generar Excel">
                </form>



            </div>
        </div>
    </div>
    <script>
        function mostrarTipo() {
            let tipo = document.getElementById("tipo").value;

            let campoArea = document.getElementById("campo_area");
            let campoNivel = document.getElementById("campo_nivel");
            let campoClave = document.getElementById("campo_clave");

            let area = document.getElementById("area");
            let nivelIndicador = document.getElementById("nivelIndicador");
            let clave = document.getElementById("clave");

            // Ocultar todos los campos
            campoArea.style.display = "none";
            campoNivel.style.display = "none";
            campoClave.style.display = "none";

            // Deshabilitar los campos para que solo se envíe el necesario
            area.disabled = true;
            nivelIndicador.disabled = true;
            clave.disabled = true;

            // Mostrar y habilitar el campo correspondiente
            if (tipo === "POA") {
                campoArea.style.display = "block";
                area.disabled = false;
            } else if (tipo === "FT") {
                campoNivel.style.display = "block";
                campoClave.style.display = "block";
                nivelIndicador.disabled = false;
                clave.disabled = false;
            } else if (["PBR", "MIR", "EP", "AP", "AO"].includes(tipo)) {
                campoClave.style.display = "block";
                clave.disabled = false;
            }
        }

        // Ejecutar la función al cargar la página
        window.onload = function() {
            mostrarTipo();
        };

    </script>
</body>
</html>
