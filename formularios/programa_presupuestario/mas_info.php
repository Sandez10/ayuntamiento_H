<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

include '../../database/conexion.php';

// Obtener clave_area y rol del usuario activo
$queryUsuario = "SELECT clave_area, rol FROM usuarios WHERE usr = ?";
$stmtUsuario = $conn->prepare($queryUsuario);
$stmtUsuario->bind_param("s", $_SESSION['usuario']);
$stmtUsuario->execute();
$stmtUsuario->bind_result($clave_area, $rol);
$stmtUsuario->fetch();
$stmtUsuario->close();

// Obtener los parámetros claveProgramaP y clave_area
$claveProgramaP = isset($_GET['claveProgramaP']) ? $_GET['claveProgramaP'] : null;
$clave_area = isset($_GET['clave_area']) ? $_GET['clave_area'] : null;

// Si el usuario es admin y hay un 'clave_area' en la URL, lo usamos
if ($rol === 'admin' && isset($_GET['claveProgramaP']) && !empty($_GET['clave_area'])) {
    $clave_area = htmlspecialchars($_GET['clave_area']);
    $claveProgramaP = htmlspecialchars($_GET['claveProgramaP']);
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
    <title>Programas Presupuestarios</title>
    <link rel="stylesheet" href="../../css/plan_pp.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <a href="../../plataforma/mas_info.php" target="_blank">
                <img src="../../img/zihua.png" alt="Logo">
            </a>
        </div>
        <nav>
            <ul>
                <li><span class="menu-title">Menú</span></li>
                <li><a href="#">Presupuesto Anual</a></li>
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
        
        <table>
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Área</th>
                    <th>Actividad</th>
                    <th>Enero</th>
                    <th>Evidencia</th>
                    <th>Febrero</th>
                    <th>Evidencia</th>
                    <th>Marzo</th>
                    <th>Evidencia</th>
                    <th>Abril</th>
                    <th>Evidencia</th>
                    <th>Mayo</th>
                    <th>Evidencia</th>
                    <th>Junio</th>
                    <th>Evidencia</th>
                    <th>Julio</th>
                    <th>Evidencia</th>
                    <th>Agosto</th>
                    <th>Evidencia</th>
                    <th>Septiembre</th>
                    <th>Evidencia</th>
                    <th>Octubre</th>
                    <th>Evidencia</th>
                    <th>Noviembre</th>
                    <th>Evidencia</th>
                    <th>Diciembre</th>
                    <th>Evidencia</th>
                    <th>Trimestre 1 (%)</th>
                    <th>Trimestre 2 (%)</th>
                    <th>Trimestre 3 (%)</th>
                    <th>Trimestre 4 (%)</th>
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
                                echo "<td>" . htmlspecialchars($actividad['claveProgramaP']) . "</td>";
                                echo "<td>" . htmlspecialchars($actividad['nombre_area']) . "</td>";
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
                                    $avanceTotal = "Avance:" . $avance['avance'] . " Beneficiarios: " . $avance['avanceBeneficiario'];
                                    echo "<td>" . htmlspecialchars($avanceTotal) . "</td>";

                                    if (!empty($avance['avanceEvidencia']) && file_exists($avance['avanceEvidencia'])) {
                                        echo "<td><a href='" . $avance['avanceEvidencia'] . "' target='_blank'>Ver Evidencia</a></td>";
                                    } else {
                                        echo "<td>Sin evidencia</td>";
                                    }
                                }

                                // Código para sumar avances y beneficiarios
                                $trim1 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 0, 3));
                                $trim2 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 3, 3));
                                $trim3 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 6, 3));
                                $trim4 = array_sum(array_slice(array_column($avancesPorMes, 'avance'), 9, 3));

                                $trim1Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 0, 3));
                                $trim2Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 3, 3));
                                $trim3Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 6, 3));
                                $trim4Benef = array_sum(array_slice(array_column($avancesPorMes, 'avanceBeneficiario'), 9, 3));

                                $metaAnual = $actividad['metaAnual'];

                                $trim1Percent = $metaAnual ? ($trim1 / $metaAnual) * 100 : 0;
                                $trim2Percent = $metaAnual ? ($trim2 / $metaAnual) * 100 : 0;
                                $trim3Percent = $metaAnual ? ($trim3 / $metaAnual) * 100 : 0;
                                $trim4Percent = $metaAnual ? ($trim4 / $metaAnual) * 100 : 0;

                                // Mostrar avances y beneficiarios acumulados
                                echo "<td>Avance: " . number_format($trim1Percent, 2) . "% | Beneficiarios: " . $trim1Benef . "</td>";
                                echo "<td>Avance: " . number_format($trim2Percent, 2) . "% | Beneficiarios: " . $trim2Benef . "</td>";
                                echo "<td>Avance: " . number_format($trim3Percent, 2) . "% | Beneficiarios: " . $trim3Benef . "</td>";
                                echo "<td>Avance: " . number_format($trim4Percent, 2) . "% | Beneficiarios: " . $trim4Benef . "</td>";

                                $stmtAvances->close();
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

        <button type="button" class="btn-regresar" onclick="location.href='../../formularios/pre_a.php';">Regresar</button>
    </div>
</body>
</html>

<?php
$stmtProgramas->close();
$conn->close();
?>
