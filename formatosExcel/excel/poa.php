<?php
session_start();

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear una nueva instancia de Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Agregar datos a la hoja
$sheet->setCellValue('A1', 'Hola');
$sheet->setCellValue('B1', 'Mundo');

// Crear un escritor para guardar el archivo
$writer = new Xlsx($spreadsheet);

// Configurar las cabeceras para descargar el archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="archivo.xlsx"');
header('Cache-Control: max-age=0');

// Guardar el archivo en la salida
$writer->save('php://output');
exit;