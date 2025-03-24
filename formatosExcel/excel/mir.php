<?php
session_start();

include '../../database/conexion.php';
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
// Verificar si el rol es admin
if ($rol !== 'admin') {
    echo "<script>
        alert('No tienes permiso para acceder a esta página.');
        history.back();
    </script>";
    exit();
}

if (!file_exists('../vendor/autoload.php')) {
    die('Error: No se encontró autoload.php en la ruta especificada.');
}
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/*-----------------------------------------------------*/

// Obtener la clave seleccionada en el formulario
$clave = $_POST['clave'] ?? null;

// Verificar si se recibió la clave correctamente
if (!$clave) {
    die("Error: Debes seleccionar una clave para generar el archivo MIR.");
}

$clave = $_POST['clave'] ?? null;
if (!$clave) {
    die("Error: Debes seleccionar una clave para generar el archivo MIR.");
}

$query = "SELECT  
    la.*, 
    a.clave_area, 
    a.nombre_area AS unidad_responsable,
    GROUP_CONCAT(DISTINCT ur.nombre_area SEPARATOR ', ') AS unidades_participantes
FROM listaactividades AS la  
INNER JOIN areas AS a ON la.clave_area = a.clave_area  
INNER JOIN unidadesresponsables AS ur ON la.claveProgramaP = ur.claveProgramaP  
WHERE la.claveProgramaP = ?  
GROUP BY la.claveProgramaP, a.nombre_area 
LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $clave);
$stmt->execute();
$result = $stmt->get_result();

// Consulta para el resto de los datos (sin LIMIT)
$queryNoLimit = "SELECT  
    la.codigo_indicador, 
    la.nivel_indicador, 
    la.nombreActividad, 
    la.formula, 
    la.unidadMedida, 
    la.MediosVerifi, 
    la.supuestos
FROM listaactividades AS la
WHERE la.claveProgramaP = ?";

$stmt = $conn->prepare($queryNoLimit);
$stmt->bind_param("s", $clave);
$stmt->execute();
$result2 = $stmt->get_result();


    // Crear un nuevo objeto Spreadsheet
    $spreadsheet = new Spreadsheet();
    // Establecer la hoja activa (por defecto es la primera)
    $sheet = $spreadsheet->getActiveSheet();
    // Establecer la fuente global como Arial
    $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');

    // Establecer los encabezados de las columnas
    $sheet->mergeCells('C1:I1');
    $sheet->setCellValue('C1', 'MUNICIPIO DE ZIHUATANEJO DE AZUETA');
    $sheet->mergeCells('C2:I2');
    $sheet->setCellValue('C2', 'MATRIZ DE INDICADORES PARA RESULTADOS');

    // Alineación y formato de las celdas de encabezado
    $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('C1:C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
// Verificar si se encontraron resultados
if ($result->num_rows > 0) {


    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getStyle('C4:C10')->getAlignment()->setWrapText(true);
    // Establecer otras celdas con información adicional
    $sheet->setCellValue('C4', 'PROGRAMA PRESUPUESTARIO');
    $sheet->setCellValue('C5', 'EJE RECTOR PMD:');
    $sheet->setCellValue('C6', 'EJE RECTOR ESTATAL');
    $sheet->setCellValue('C7', 'UNIDADES ADMINISTRATIVAS PARTICIPANTES:');
    $sheet->setCellValue('C8', 'UNIDAD RESPONSABLE:');
    $sheet->setCellValue('C9', 'EJERCICIO FISCAL:');
    $sheet->setCellValue('C10', 'CÓDIGO DE LA MIR:');
        // Inicializar el contador de filas (la fila 2 porque la 1 es para encabezados)
        $row = 4;
        while ($row_data = $result->fetch_assoc()) {
            $sheet->setCellValue('D' . $row, $row_data['claveProgramaP'] . ' - ' . $row_data['nombreProgramaP']);
            $row++;
            $sheet->setCellValue('D' . $row, $row_data['Estrategia_PMD']);
            $row++;
            $sheet->setCellValue('D' . $row, $row_data['eje_PDE']);
            $row++;
            $sheet->setCellValue('D' . $row, $row_data['unidades_participantes']);
            $row++;
            $sheet->setCellValue('D' . $row, $row_data['unidad_responsable']);
            $row++;
            $sheet->setCellValue('D' . $row, '2025');
            $row++;
            $sheet->setCellValue('D' . $row, $row_data['codigo_indicador'] ?? 'N/D');
            $row++;
        }
    
}
if ($result2->num_rows > 0){
    // Ajustar el ancho de las columnas y activar el ajuste de texto
    $sheet->getColumnDimension('B')->setAutoSize(true); 
    $sheet->getColumnDimension('C')->setWidth(50); 
    $sheet->getColumnDimension('D')->setAutoSize(true);

    // Activar el ajuste de texto para las celdas que pueden tener contenido largo
    $sheet->getStyle('C')->getAlignment()->setWrapText(true);

    // Encabezados de indicadores centrados
    $sheet->mergeCells('A13:A15')->setCellValue('A13', 'CÓDIGO');
    $sheet->mergeCells('B13:B15')->setCellValue('B13', 'RESUMEN NARRATIVO');
    $sheet->mergeCells('C13:E13')->setCellValue('C13', 'INDICADORES');

    $sheet->mergeCells('C14:C15')->setCellValue('C14', 'NOMBRE DEL INDICADOR');
    $sheet->mergeCells('D14:D15')->setCellValue('D14', 'FÓRMULA');
    $sheet->mergeCells('E14:E15')->setCellValue('E14', 'FRECUENCIA DE MEDICIÓN');

    $sheet->mergeCells('F14:F15')->setCellValue('F14', 'MEDIOS DE VERIFICACIÓN');
    $sheet->mergeCells('G14:G15')->setCellValue('G15', 'SUPUESTOS');
    $sheet->getStyle('A13:G15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $row = 17;
    while ($row_data = $result2->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $row_data['codigo_indicador'] ?? 'N/D');
        $sheet->setCellValue('B' . $row, $row_data['nivel_indicador'] ?? 'N/D');
        $sheet->setCellValue('C' . $row, $row_data['nombreActividad']);
        $sheet->setCellValue('D' . $row, $row_data['formula']);
        $sheet->setCellValue('E' . $row, $row_data['unidadMedida']);
        $sheet->setCellValue('F' . $row, $row_data['MediosVerifi']);
        $sheet->setCellValue('G' . $row, $row_data['supuestos']);
        $row ++;
    }

    // Definir el nombre del archivo y la ruta de salida
    $filename = 'MIR-' . $clave . '.xlsx';

    // Crear un escritor para guardar el archivo
    $writer = new Xlsx($spreadsheet);

    ob_end_clean();

    // Configurar las cabeceras para descargar el archivo
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Guardar el archivo en la salida
    $writer->save('php://output');
    exit;

}else {
    echo "No se encontraron resultados para la clave: $clave";
    exit();
}