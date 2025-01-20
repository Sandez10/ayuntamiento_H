<?php

session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Carga la librería PhpSpreadsheet
require 'vendor/autoload.php';
include '../database/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Función para generar un archivo Excel con datos específicos
function generarExcel($tipo) {
    global $conn;

    // Obtener clave_area y rol del usuario activo
    $queryUsuario = "SELECT clave_area, rol FROM usuarios WHERE usr = ?";
    $stmtUsuario = $conn->prepare($queryUsuario);
    $stmtUsuario->bind_param("s", $_SESSION['usuario']);
    $stmtUsuario->execute();
    $stmtUsuario->bind_result($clave_area, $rol);
    $stmtUsuario->fetch();
    $stmtUsuario->close();

    if (empty($clave_area)) {
        die("No se encontró una clave_area asociada al usuario activo.");
    }

    // Obtener el nivel de indicador (puede venir como parámetro GET)
    $nivelIndicador = isset($_GET['nivelIndicador']) ? $_GET['nivelIndicador'] : ''; // Deja vacío si no se pasa el parámetro

    // Validación de valores posibles para nivelIndicador QUITAR COMENTARIOS SI EN ALGÚN FUTURO SE DESEA ELEGIR EL TIPO DE INDICADOR
    $valoresPermitidos = ['actividad', 'componente', 'fin'];
    if (!empty($nivelIndicador) && !in_array($nivelIndicador, $valoresPermitidos)) {
        die("Error: Nivel de indicador no válido.");
    }

    // Inicializar variable de búsqueda
    $busqueda = isset($_GET['busqueda']) ? '%' . $_GET['busqueda'] . '%' : '%';

    // Construir consulta dependiendo del rol del usuario
    if ($rol === 'admin') {
        // Si es admin, mostrar todos los programas
        $queryProgramas = "SELECT p.clave_programa, a.nombre_area, p.fechaInicio, p.proyecto, p.nivelIndicador
                           FROM programas p
                           JOIN areas a ON p.clave_area = a.clave_area
                           WHERE p.proyecto LIKE ?";

        // Si el nivelIndicador no está vacío, agregar la condición correspondiente
        if (!empty($nivelIndicador)) {
            $queryProgramas .= " AND p.nivelIndicador = ?";
        }

        // Preparar la sentencia
        $stmtProgramas = $conn->prepare($queryProgramas);
        if (!empty($nivelIndicador)) {
            $stmtProgramas->bind_param("ss", $busqueda, $nivelIndicador); // Solo bind para los parámetros correctos
        } else {
            $stmtProgramas->bind_param("s", $busqueda); // Solo bind para la búsqueda si no se filtra por nivelIndicador
        }
    } else {
        // Si no es admin, mostrar solo los programas asociados a su área
        $queryProgramas = "SELECT p.clave_programa, a.nombre_area, p.fechaInicio, p.proyecto, p.nivelIndicador
                           FROM programas p
                           JOIN areas a ON p.clave_area = a.clave_area
                           WHERE p.clave_area = ? AND p.proyecto LIKE ?";

        // Si el nivelIndicador no está vacío, agregar la condición correspondiente
        if (!empty($nivelIndicador)) {
            $queryProgramas .= " AND p.nivelIndicador = ?";
        }

        // Preparar la sentencia
        $stmtProgramas = $conn->prepare($queryProgramas);
        if (!empty($nivelIndicador)) {
            $stmtProgramas->bind_param("sss", $clave_area, $busqueda, $nivelIndicador); // Bind para los tres parámetros
        } else {
            $stmtProgramas->bind_param("ss", $clave_area, $busqueda); // Bind solo para clave_area y busqueda si no se filtra por nivelIndicador
        }
    }

    // Ejecutar la consulta
    $stmtProgramas->execute();
    $result = $stmtProgramas->get_result();

    // Crear la hoja de Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Datos específicos según el tipo de archivo
    $data = [];
    $formato_archivo = '';


    if ($tipo === 'POA') {
        $formato_archivo = 'Plan Operativo Anual'; // Nombre completo para POA
        $data = [
            ['Plan Operativo Anual', 'Descripción', 'Dirección', 'Meta', 'Indicador', 'Valor'],
        ];
    } elseif ($tipo === 'PBR') {
        $formato_archivo = 'Presupuesto Basado en Resultados'; // Nombre completo para PBR
        $data = [
            ['Presupuesto Basado en Resultados', 'Descripción', 'Área Responsable', 'Objetivo', 'Indicador', 'Eficiencia'],
        ];
    } elseif ($tipo === 'MIR') {
        $formato_archivo = 'Matriz de Indicadores para Resultados'; // Nombre completo para MIR
        $data = [
            ['Matriz de Indicadores para Resultados', 'Descripción', 'Dependencia', 'Resultado', 'Indicador', 'Impacto'],
        ];
    } elseif ($tipo === 'EP') {
        $formato_archivo = 'Estructura Programática'; // Nombre completo para EP


        // Función para configurar celdas con valor, fusión opcional y alineación
function setCellValueAndMerge($sheet, $cell, $value, $mergeRange = null, $alignment = null) {
    $sheet->setCellValue($cell, $value);
    if ($mergeRange) {
        $sheet->mergeCells($mergeRange);
    }
    if ($alignment) {
        $sheet->getStyle($cell)->getAlignment()->setHorizontal($alignment);
    }
}

// Función para aplicar estilos (negritas y color de relleno) a un rango
function applyBoldAndFill($sheet, $range, $color = 'CDCDCD') {
    $sheet->getStyle($range)->getFont()->setBold(true);
    $sheet->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
          ->getStartColor()->setARGB($color);
}

// Encabezados principales
setCellValueAndMerge($sheet, 'E1', 'CLAVE:');
setCellValueAndMerge($sheet, 'F1', $clave_area);  // Valor obtenido de la base de datos

setCellValueAndMerge($sheet, 'J1', 'FECHA DE APROBACIÓN:', 'J1:K1');
//setCellValueAndMerge($sheet, 'K1', $fechaInicio);  // Valor obtenido de la base de datos
setCellValueAndMerge($sheet, 'E2', 'ENTIDAD FISCALIZABLE:');
setCellValueAndMerge($sheet, 'E3', 'AÑO:');

// Programa Presupuestario
setCellValueAndMerge($sheet, 'A6', 'PROGRAMA PRESUPUESTARIO', 'A6:K6', 'center');
applyBoldAndFill($sheet, 'A6:K6');
setCellValueAndMerge($sheet, 'A7', 'Nombre del Programa', 'A7:B7');
setCellValueAndMerge($sheet, 'A8', 'Clasificación Programática', 'A8:B8');
setCellValueAndMerge($sheet, 'A9', 'Unidad(es) Responsable(s):', 'A9:B9');
setCellValueAndMerge($sheet, 'A10', 'Periodo de Ejecución:', 'A10:B10');

// Clasificación funcional
setCellValueAndMerge($sheet, 'A11', 'CLASIFICACIÓN FUNCIONAL DEL GASTO', 'A11:K11', 'center');
applyBoldAndFill($sheet, 'A11:K11');
setCellValueAndMerge($sheet, 'A12', 'CLASIFICACIÓN', 'A12:B12');
setCellValueAndMerge($sheet, 'C12', 'CONCEPTO', 'C12:K12', 'center');
setCellValueAndMerge($sheet, 'A13', 'Finalidad', 'A13:B13');
setCellValueAndMerge($sheet, 'A14', 'Función', 'A14:B14');
setCellValueAndMerge($sheet, 'A15', 'Subfunción', 'A15:B15');
setCellValueAndMerge($sheet, 'A16', 'Tipo de Gasto', 'A16:B16');
setCellValueAndMerge($sheet, 'G16', 'Fuente de Financiamiento', 'G16:H16');

// Vinculación PMD
setCellValueAndMerge($sheet, 'A18', 'VINCULACIÓN AL PLAN MUNICIPAL DE DESARROLLO (PMD)', 'A18:K18', 'center');
applyBoldAndFill($sheet, 'A18:K18');
$sheet->setCellValue('A19', 'Eje estratégico:');
$sheet->setCellValue('A20', 'Objetivo:');
$sheet->setCellValue('A21', 'Estrategia');

// Vinculación PED
setCellValueAndMerge($sheet, 'A22', 'VINCULACIÓN AL PLAN ESTATAL DE DESARROLLO (PED)', 'A22:K22', 'center');
applyBoldAndFill($sheet, 'A22:K22');
$sheet->setCellValue('A23', 'Eje estratégico:');
$sheet->setCellValue('A24', 'Objetivo:');

// Vinculación ODS
setCellValueAndMerge($sheet, 'A25', 'VINCULACIÓN A LOS OBJETIVOS DE DESARROLLO SOSTENIBLE', 'A25:K25', 'center');
applyBoldAndFill($sheet, 'A25:K25');
setCellValueAndMerge($sheet, 'A26', 'Número:');
setCellValueAndMerge($sheet, 'B26', 'Nombre del ODS:', 'B26:E26', 'center');
setCellValueAndMerge($sheet, 'F26', 'Nombre del ODS:', 'F26:K26', 'center');

// FIN
setCellValueAndMerge($sheet, 'A32', 'FIN', 'A32:K32', 'center');
applyBoldAndFill($sheet, 'A32:K32');
setCellValueAndMerge($sheet, 'A33', 'Resumen Narrativo:');

// INDICADOR
setCellValueAndMerge($sheet, 'A34', 'INDICADOR', 'A34:K34', 'center');
applyBoldAndFill($sheet, 'A34:K34');
setCellValueAndMerge($sheet, 'A35', 'Nombre:');

//PROPOSITO
setCellValueAndMerge($sheet, 'A36', 'PROPÓSITO', 'A36:K36', 'center');
applyBoldAndFill($sheet, 'A36:K36');
setCellValueAndMerge($sheet, 'A37', 'Resumen Narrativo:');

// INDICADOR
setCellValueAndMerge($sheet, 'A38', 'INDICADOR', 'A38:K38', 'center');
applyBoldAndFill($sheet, 'A38:K38');
setCellValueAndMerge($sheet, 'A39', 'Nombre:');

// INDICADOR
setCellValueAndMerge($sheet, 'A40', 'COMPONENTES', 'A40:K40', 'center');
applyBoldAndFill($sheet, 'A40:K40');


        
        // Si la consulta tiene resultados, añaJdirlos a los datos del archivo Excel
        /*while ($row = $result->fetch_assoc()) {
            $data[] = [
                $row['clave_programa'],
                $row['nombre_area'],
                $row['fechaInicio'],
                $row['proyecto'],
                $row['nivelIndicador'],
            ];
        }*/
    } elseif ($tipo === 'AP') {
        $formato_archivo = 'Arbol de Problemas'; // Nombre completo para AP
        $data = [
            ['Matriz de Indicadores para Resultados', 'Descripción', 'Dependencia', 'Resultado', 'Indicador', 'Impacto'],
        ];
    } elseif ($tipo === 'AO') {
        $formato_archivo = 'Arbol Objetivos'; // Nombre completo para AO
        $data = [
            ['Matriz de Indicadores para Resultados', 'Descripción', 'Dependencia', 'Resultado', 'Indicador', 'Impacto'],
        ];
    }

    // Configuración general del Excel
/*    $sheet->setCellValue('E1', strtoupper($formato_archivo));
    $sheet->mergeCells('F1:I1');
    $sheet->getStyle('E1')->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle('E1')->getAlignment()->setHorizontal('center');
    */



    // Insertar los datos en la hoja
    $row = 5;
    foreach ($data as $item) {
        $sheet->fromArray($item, null, "A{$row}");
        $row++;
    }

    // Autoajustar las columnas
    foreach (range('A', 'F') as $col) {


        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Aplicar bordes a la tabla
    $tableRange = "A4:F" . ($row - 1);
    //$sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Forzar la descarga del archivo Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . strtolower($formato_archivo) . '2025.xlsx"');
    ob_clean(); // Limpieza del búfer
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Lógica para determinar qué archivo generar
if (isset($_GET['tipo'])) {
    $tipo = strtoupper($_GET['tipo']); // Convertir a mayúsculas para estandarizar
    if (in_array($tipo, ['POA', 'PBR', 'MIR', 'EP', 'AP', 'AO'])) {
        generarExcel($tipo);
    } else {
        die("Error: Tipo de formato no reconocido.");
    }
} else {
    die("Error: No se especificó un tipo de formato.");
}
?>
