<?php
session_start();

include '../../database/conexion.php';

// Verificar autenticación y permisos
$username = $_SESSION['usuario'] ?? null;

$query = "SELECT rol FROM usuarios WHERE usr = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($rol);
$stmt->fetch();
$stmt->close();

if ($rol !== 'admin') {
    echo "<script>
        alert('No tienes permiso para acceder a esta página.');
        history.back();
    </script>";
    exit();
}

// Verificar la existencia del autoload
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Obtener datos del formulario
$clave = $_POST['clave'] ?? null;
$nivelIndicador = $_POST["nivelIndicador"] ?? null;

if (!$clave) {
    die('Error: No se proporcionó una clave válida.');
}

// Crear hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

/*Empieza cabecera... */
$sheet->mergeCells('A1:G1');
$sheet->mergeCells('A2:G2');
$sheet->mergeCells('A3:G3');
$sheet->mergeCells('A4:G4');
$sheet->mergeCells('A5:G5');
$sheet->mergeCells('A6:G6');
$sheet->setCellValue('A1', 'MUNICIPIO DE ZIHUATANEJO DE AZUETA');
$sheet->setCellValue('A2', 'Ejercicio Fiscal 2025');
$sheet->setCellValue('A3', 'SISTEMA MUNICIPAL DE EVALUACIÓN AL DESEMPEÑO');
$sheet->setCellValue('A4', 'Ficha Técnica del Indicador de Desempeño');
$sheet->setCellValue('A5', 'Porcentaje de acciones de coordinación, vinculación y corresponsabilidad con actores públicos gubernamentales');

$sheet->getStyle('A1:G1')->getFont()->setSize(11);  // Ajustar hasta la fila que necesites
$sheet->getStyle('A3')->getFont()->setBold(true);
$sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2:G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3:G3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('A5:G5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getColumnDimension('G')->setWidth(40);
$sheet->getStyle('A6:G6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
/*Termina cabecera cabecera... */

/*Inicia Datos de Identificación del Programa */
$sheet->mergeCells('A8:G8');
$sheet->setCellValue('A8', 'DATOS DE IDENTIFICACIÓN DEL PROGRAMA');
$sheet->setCellValue('A9', 'Clave');
$sheet->setCellValue('B9', 'Nombre');
$sheet->setCellValue('F9', 'Eje Estratégico');
$sheet->mergeCells('B9:E9');
$sheet->mergeCells('F9:G9');
$sheet->getStyle('A8')->getFont()->setBold(true);
$sheet->getStyle('A9:G9')->getFont()->setBold(true);
$sheet->getStyle('A8:G8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A9:C9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('F9:G9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


$sheet->setCellValue('A11','Unidad Responsable del Programa');
$sheet->setCellValue('E11','Clasificación del Programática');
$sheet->mergeCells('A11:D11');
$sheet->mergeCells('E11:G11');
$sheet->getStyle('A11:G11')->getFont()->setBold(true);
$sheet->getStyle('A11:G11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
/*Termina Datos de Identificación del Programa */


/*Inicia extracción de datos MySQL para llenar apartado de Datos de Identificación del Programa */

$query = "SELECT  
    la.*, 
    a.clave_area, 
    a.nombre_area AS unidad_responsable
FROM listaactividades AS la
INNER JOIN areas AS a ON la.clave_area = a.clave_area  
INNER JOIN unidadesresponsables AS ur ON la.claveProgramaP = ur.claveProgramaP  
WHERE la.claveProgramaP = ? AND la.nivel_indicador = ?
GROUP BY la.claveProgramaP, la.nivel_indicador, a.clave_area, a.nombre_area
LIMIT 1";


$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $clave, $nivelIndicador);
$stmt->execute();
$result = $stmt->get_result();


$row = 10;
while ($row_data = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $row_data['claveProgramaP'] ?? 'N/D');
    $sheet->setCellValue('B' . $row, $row_data['nombreProgramaP'] ?? 'N/D');
    $sheet->setCellValue('F' . $row, $row_data['unidad_responsable'] ?? 'N/D');
    $sheet->mergeCells('B10:E10');
    $sheet->getStyle('F10:F10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->mergeCells('F10:G10');

    $sheet->setCellValue('A' . $row, $row_data['unidad_responsable'] ?? 'N/D');
    $sheet->setCellValue('A' . $row, $row_data[''] ?? 'N/D');
    $row++;
}

// Generar y descargar el archivo
$filename = "Ficha Técnica - $clave.xlsx";

$writer = new Xlsx($spreadsheet);
ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>
