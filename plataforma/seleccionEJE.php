<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Ejercicio Fiscal</title>
    <link rel="stylesheet" href="../css/selecionarEje.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../img/zihua.png" alt="Logo" class="logo">
            <h2>Sistema Administrativo y Planeación Ayuntamiento Municipal</h2>
        </div>
        <div class="content">
            <h3>Datos de trabajo</h3>
            <p>Selecciona el ejercicio fiscal con el que deseas trabajar.</p>
            <form action="../plataforma/dashboard.php" method="POST">
                <select name="ejercicio_fiscal" required>
                    <option value="" disabled selected>Selecciona el año</option>
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                </select>
                <button type="submit">Trabajar con este ejercicio</button>
            </form>
        </div>
    </div>
</body>
</html>
