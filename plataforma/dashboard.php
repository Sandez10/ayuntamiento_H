<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
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
$areasQuery = "SELECT nombre_area FROM areas";
$areasResult = $conn->query($areasQuery);

// Consulta para obtener las claves para la Estructura Programática
$clavesQuery = "SELECT id, clave_programa FROM programas";
$clavesResult = $conn->query($clavesQuery);

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
    ["title" => "Ver Avance", "link" => "../formularios/ProgramasPre.php"],
    ["title" => "Mostrar Reporte de Avance", "link" => "#"]
];

// Inicializar el menú con las opciones base para todos los usuarios
$menuOpciones = [
    'default' => $baseOpciones,  // Menú base para todos los usuarios
];

// Verificar si el usuario es admin y agregarle opciones adicionales
if ($rol == 'admin') {
    $menuOpciones['admin'] = array_merge([["title" => "Administrar Usuarios", "link" => "../sesiones_conexiones/actualizar_usr.php"]], $baseOpciones);
}

// Determinar las opciones del menú para el usuario según su rol
$opcionesMenu = $menuOpciones[$rol] ?? $menuOpciones['default'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="../img/ZIHUA_c.png" alt="Logo">
        </div>
        <div class="user-options">
            <a href="../sesiones_conexiones/editar_perfil.php" class="edit-profile-icon">
                <i class="fa-regular fa-user fa-2xl" style="color: #ffffff;"></i>
            </a>
            <a href="../sesiones_conexiones/destruir_sesion.php" class="logout-icon">
                <i class="fa-sharp fa-solid fa-arrow-right-from-bracket fa-2xl" style="color: #ffffff;"></i>
            </a>
        </div>
    </div>

    <div class="sidebar">
        <nav>
            <ul>
                <li><span class="menu-title">Menú</span></li>
                <?php foreach ($opcionesMenu as $opcion): ?>
                    <li><a href="<?= $opcion['link'] ?>"><?= $opcion['title'] ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>

    <div class="main-content">
        <div class="header-text">
            <h2>Municipio de Zihuatanejo de Azueta Guerrero</h2>
            <h3>Bienvenido, <?= htmlspecialchars($username) ?></h3>
        </div>

        <!-- Formulario para que el admin seleccione el área, formato y nivel de indicador -->
        <div class="selector-form">
            <h3>Selecciona el tipo de formato para generar el archivo</h3>
            <form action="../formatos/formatos.php" method="get">
                <!-- Solo el admin ve la opción de seleccionar área -->
                <?php if ($rol == 'admin'): ?>
                    <label for="area">Área:</label>
                    <select name="area" id="area" required>
                        <option value="">Seleccione un área</option>
                        <?php while ($area = $areasResult->fetch_assoc()): ?>
                            <option value="<?= $area['id'] ?>">
                                <?= htmlspecialchars($area['nombre_area']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label for="nivelIndicador">Nivel de Indicador:</label>
                    <select name="nivelIndicador" id="nivelIndicador" required>
                        <option value="actividad">Actividad</option>
                        <option value="componente">Componente</option>
                        <option value="fin">Fin</option>
                    </select>
                <?php endif; ?>

                <!-- Formulario -->
                <label for="formato">Formato:</label>
                <select name="tipo" id="tipo" required onchange="mostrarClaves()">
                    <option value="POA">POA</option>
                    <option value="PBR">PBR</option>
                    <option value="MIR">MIR</option>
                    <option value="EP">Estructura Programática</option>
                    <option value="AP">Árbol de Problemas</option>
                    <option value="AO">Árbol de Objetivos</option>
                </select>

                <!-- Solo cuando se selecciona "Estructura Programática", mostramos las claves -->
                <div id="clavesDiv" style="display: none;">
                    <label for="clave">Selecciona una clave:</label>
                    <select name="clave" id="clave" required>
                        <option value="">Seleccione una clave</option>
                        <?php while ($clave = $clavesResult->fetch_assoc()): ?>
                            <option value="<?= $clave['id'] ?>">
                                <?= htmlspecialchars($clave['clave_programa']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>


                <input type="submit" value="Generar Excel">
            </form>
        </div>
    </div>

    <script>
// Función para mostrar el campo de claves cuando se selecciona "Estructura Programática"
function mostrarClaves() {
    var tipo = document.getElementById("tipo").value;
    var clavesDiv = document.getElementById("clavesDiv");

    if (tipo == "EP") {
        clavesDiv.style.display = "block";  // Muestra el div con las claves
    } else {
        clavesDiv.style.display = "none";  // Oculta el div con las claves
    }
}


    </script>
    
</body>
</html>
