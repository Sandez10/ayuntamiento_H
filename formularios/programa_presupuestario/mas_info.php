<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

include '../../database/conexion.php';

// Obtener clave_area y rol del usuario activo
// Consulta para obtener clave_area, rol y nombre del área del usuario activo
$queryUsuario = "
    SELECT 
        us.clave_area, 
        us.rol, 
        u.nombre_area
    FROM usuarios us
    JOIN unidadesresponsables u ON us.clave_area = u.clave_area
    WHERE us.usr = ?";
$stmtUsuario = $conn->prepare($queryUsuario);
$stmtUsuario->bind_param("s", $_SESSION['usuario']);
$stmtUsuario->execute();
$stmtUsuario->bind_result($clave_area, $rol, $nombre_area);
$stmtUsuario->fetch();
$stmtUsuario->close();

// Obtener los parámetros claveProgramaP y clave_area
$claveProgramaP = isset($_GET['claveProgramaP']) ? $_GET['claveProgramaP'] : null;
$clave_area = isset($_GET['clave_area']) ? $_GET['clave_area'] : null;
$nombre_area = isset($_GET['nombre_area']) ? $_GET['nombre_area'] : null;

// Si el usuario es admin y hay un 'clave_area' en la URL, lo usamos
if ($rol === 'admin' && isset($_GET['claveProgramaP']) && isset($_GET['clave_area']) && isset($_GET['nombre_area'])) {
    $clave_area = htmlspecialchars($_GET['clave_area']);
    $claveProgramaP = htmlspecialchars($_GET['claveProgramaP']);
    $dependeciaArea = htmlspecialchars($_GET['nombre_area']);
}

// Obtener programas asociados a la clave_area
$queryProgramas = "SELECT id FROM programas WHERE clave_area = ?";
$stmtProgramas = $conn->prepare($queryProgramas);
$stmtProgramas->bind_param("s", $clave_area);
$stmtProgramas->execute();
$result = $stmtProgramas->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avance por Mes</title>
    <link rel="stylesheet" href="../../css/plan_pp.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <a href="../../plataforma/mas_info.php" target="_blank">
                <img src="../../img/ZIHUA_C.png" alt="Logo">
            </a>
        </div>
        <nav>
            <ul>
                <li><span class="menu-title">Menú</span></li>
                <li><a href="../pre_a.php">Mostrar Programas</a></li>
                <li><span class="menu-title">Reportes</span></li>
                <li><a href="#">Programa Operativo Anual</a></li>
                <li><a href="#">Reporte de Avance</a></li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <button type="button" class="btn-salir" onclick="location.href='../../sesiones_conexiones/destruir_sesion.php';">Cerrar sesión</button>
        </header>
        <h6>Área: <?php echo htmlspecialchars($nombre_area); ?></h6>
        <h6>Clave del Programa: <?php echo htmlspecialchars($claveProgramaP); ?></h6>
        <table>
            <thead>
                <tr>
                    <th>Actividad</th>
                    <th>Enero</th>
                    <th>Febrero</th>
                    <th>Marzo</th>
                    <th>Abril</th>
                    <th>Mayo</th>
                    <th>Junio</th>
                    <th>Julio</th>
                    <th>Agosto</th>
                    <th>Septiembre</th>
                    <th>Octubre</th>
                    <th>Noviembre</th>
                    <th>Diciembre</th>
                    <th>Avance Trimestre 1</th>
                    <th>Avance Trimestre 2</th>
                    <th>Avance Trimestre 3</th>
                    <th>Avance Trimestre 4</th>
                    <th>% Trimestre (1-4)</th>
                    <th>Evidencia</th>
                    <th>Opciones</th>

                </tr>
            </thead>
            <tbody id="results">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $queryActividades = "SELECT DISTINCT la.id_actividades, la.claveProgramaP, la.nombreActividad, ur.nombre_area, la.metaAnual
                            FROM listaactividades la 
                            INNER JOIN unidadesresponsables ur ON la.nombre_area = ur.clave_area
                            WHERE la.claveProgramaP LIKE ? AND ur.clave_area = ?";
                        $stmtActividades = $conn->prepare($queryActividades);
                        $stmtActividades->bind_param("ss", $claveProgramaP, $clave_area);
                        $stmtActividades->execute();
                        $resultActividades = $stmtActividades->get_result();

                        if ($resultActividades->num_rows > 0) {
                            while ($actividad = $resultActividades->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($actividad['nombreActividad']) . "</td>";

                                $queryAvances = "SELECT mes, avance, avanceBeneficiario, avanceEvidencia 
                                    FROM avances_mensuales 
                                    WHERE clave_area = ? AND id_actividades = ?";
                                $stmtAvances = $conn->prepare($queryAvances);
                                $stmtAvances->bind_param("si", $clave_area, $actividad['id_actividades']);
                                $stmtAvances->execute();
                                $resultAvances = $stmtAvances->get_result();

                                $avancesPorMes = array_fill(1, 12, ['avance' => 0, 'avanceBeneficiario' => 0, 'avanceEvidencia' => '']);
                                while ($avance = $resultAvances->fetch_assoc()) {
                                    $avancesPorMes[$avance['mes']]['avance'] = $avance['avance'];
                                    $avancesPorMes[$avance['mes']]['avanceBeneficiario'] = $avance['avanceBeneficiario'];
                                    $avancesPorMes[$avance['mes']]['avanceEvidencia'] = $avance['avanceEvidencia'];
                                }

                                foreach ($avancesPorMes as $mes => $avance) {
                                    // Generar una celda con dos partes: avance y beneficiarios
                                    $avanceTotal = "Avance: " . $avance['avance'] . " 
                                    Beneficiarios: " . $avance['avanceBeneficiario'];
                                    echo "<td><div><strong>Avance:</strong> " . htmlspecialchars($avance['avance']) . "</div><div><strong>Beneficiarios:</strong> " . htmlspecialchars($avance['avanceBeneficiario']) . "</div></td>";
                                }                                

                                    // Calcular los avances acumulados para cada trimestre
                                    $trim1 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 0, 3));
                                    $trim2 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 3, 3)) + $trim1;
                                    $trim3 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 6, 3)) + $trim2;
                                    $trim4 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 9, 3)) + $trim3;

                                    // Calcular los avances acumulados para los beneficiarios
                                    $trim1Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 0, 3));
                                    $trim2Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 3, 3)) + $trim1Benef;
                                    $trim3Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 6, 3)) + $trim2Benef;
                                    $trim4Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 9, 3)) + $trim3Benef;

                                    // Obtener la meta anual
                                    $metaAnual = $actividad['metaAnual'];
                                    // Calcular el porcentaje acumulado de los avances en los 4 trimestres
                                    $avanceAcumulado = $trim4;  // El avance acumulado total es el de los 4 trimestres
                                    $porcentajeAcumulado = $metaAnual ? ($avanceAcumulado / $metaAnual) * 100 : 0;

                                $trim1 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 0, 3));
                                $trim2 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 3, 3)) + $trim1;
                                $trim3 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 6, 3)) + $trim2;
                                $trim4 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 9, 3)) + $trim3;

                                $trim1Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 0, 3));
                                $trim2Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 3, 3)) + $trim1Benef;
                                $trim3Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 6, 3)) + $trim2Benef;
                                $trim4Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 9, 3)) + $trim3Benef;

                                // Mostrar los valores acumulados de los trimestres
                                echo "<td><strong> Avance total: " . $trim1 . " | Beneficiarios: " . $trim1Benef . "</strong></td>";
                                echo "<td><strong> Avance total: " . $trim2 . " | Beneficiarios: " . $trim2Benef . "</strong></td>";
                                echo "<td><strong> Avance total: " . $trim3 . " | Beneficiarios: " . $trim3Benef . "</strong></td>";
                                echo "<td><strong> Avance total: " . $trim4 . " | Beneficiarios: " . $trim4Benef . "</strong></td>";
                                // Mostrar el porcentaje acumulado en la columna "% Trimestre (1-4)"
                                echo "<td><strong>" . number_format($porcentajeAcumulado, 2) . "%</strong></td>";

                                // Botón "Ver Evidencias"
                                echo "<td><a href='evidencia.php?id_actividad=" . $actividad['id_actividades'] . "' class='btn btn-primary'>Ver Evidencias</a></td>";
                                // Formulario de eliminación
                                if ($rol == 'admin'){
                                echo "<td>
                                
                                        <form action='eliminarAvance.php' method='POST'>
                                            <input type='hidden' name='id_actividad' value='" . $actividad['id_actividades'] . "'>
                                            <button type='submit' class='btn btn-danger'>Eliminar</button>
                                        </form>
                                    </td>";
                                    
                                $stmtAvances->close();
                                }
                            }
                        } else {
                            echo "<tr><td colspan='15'>No se encontraron actividades para este programa.</td></tr>";
                        }
                        $stmtActividades->close();
                    }
                } else {
                    echo "<tr><td colspan='15'>No se encontraron resultados.</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary" onclick="location.href='../pre_a.php?claveProgramaP=<?= urlencode($claveProgramaP); ?>';">Regresar</button>
    </div>
</body>
</html>

<?php
$stmtProgramas->close();
$conn->close();
?>
