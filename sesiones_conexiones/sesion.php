<?php
session_start();
include '../database/conexion.php';

if (isset($_POST['btnAcceder'])) {
    // Recibir datos del formulario
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];

    // Preparar y ejecutar consulta para evitar SQL Injection
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usr = ?");
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verificar si el usuario existe
    if ($resultado->num_rows > 0) {
        $usuarioData = $resultado->fetch_assoc();

// Verificar la contraseña encriptada
if (password_verify($clave, $usuarioData['clave'])) {
    $_SESSION['usuario'] = $usuario; // Guardar el nombre de usuario en la sesión

    // Verificar si necesita cambiar su contraseña
    if ($usuarioData['password_reset_required'] == 1) {
        // Redirigir a la página para cambiar contraseña
        header("Location: new_pass.php");
        exit();
    }

    // Redirigir al archivo combinado si no necesita cambiar contraseña
    header("Location: ../plataforma/seleccionEJE.php");
    exit();
}
 else {
            // Contraseña incorrecta
            echo "<script>alert('Credenciales incorrectas. Por favor, intenta de nuevo.'); window.location.href='../index.php';</script>";
        }
    } else {
        // Usuario no encontrado
        echo "<script>alert('Credenciales incorrectas. Por favor, intenta de nuevo.'); window.location.href='../index.php';</script>";
    }

    // Liberar recursos
    $stmt->close();
    $conn->close();
} else {
    // Redirigir si no se envió el formulario
    header("Location: ../index.php");
    exit();
}
?>

