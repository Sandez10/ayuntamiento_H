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
    ["title" => "Ver Programas", 
    "link" => "../formularios/pre_a.php",
    "icon" => "fas fa-list-alt" // Ícono para Ver Programas]
    ],
    [
        "title" => "Última Actividad Registrada",
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
        ],
        [
            "title" => "Reportes",
            "link" => "../formatosExcel/formatos.php",
            "icon" => "fas fa-file-excel" // Ícono de Excel
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SIPSEPP INICIO</title>
    <!-- Estilos e íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <div class="logo">
            <img src="../img/ZIHUA_C.png" alt="Logo del Municipio">
        </div>
        <div class="user-options">
        <a href="../sesiones_conexiones/destruir_sesion.php" class="btn btn-danger logout-btn">
            <i class="fas fa-sign-out-alt"></i> 
            <span class="d-none d-md-inline">Cerrar Sesión</span>
        </a>

        </div>
    </div>

    <div class="d-flex">
    <div class="sidebar">
    <ul>
        <li><span class="menu-title">Menú <i class="fas fa-home"></i> </span></li>
        <?php foreach ($opcionesMenu as $opcion): ?>
            <li>
                <a href="<?= $opcion['link'] ?>">
                    <i class="<?= $opcion['icon'] ?>"></i> <!-- Ícono dinámico -->
                    <?= $opcion['title'] ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    </div>

    <!-- Contenido principal -->
    <div class="main-content w-100">
        <div class="header-text">
            <h2 class="text-center">Municipio de Zihuatanejo de Azueta Guerrero</h2>
            <h3 class="text-center text-secondary">Bienvenido, <?= htmlspecialchars($username) ?></h3>
        </div>


    <script>
        function mostrarClaves() {
            const tipo = document.getElementById("tipo").value;
            const clavesDiv = document.getElementById("clavesDiv");
            clavesDiv.style.display = (tipo === "EP") ? "block" : "none";
        }
    </script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".sidebar");
    const toggleBtn = document.createElement("button");
    toggleBtn.innerHTML = "<i class='fas fa-bars'></i>";
    toggleBtn.classList.add("sidebar-toggle");
    
    document.querySelector(".header").prepend(toggleBtn);

    toggleBtn.addEventListener("click", function () {
        sidebar.classList.toggle("active");
    });
    });
</script>
</body>
</html>