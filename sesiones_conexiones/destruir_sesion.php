<?php
session_start(); // Iniciar la sesión
session_unset(); // Despejar todas las variables de sesión
session_destroy(); // Destruir la sesión

// Redirige al inicio de sesión
header("Location: ../index.php");
exit();
?>
