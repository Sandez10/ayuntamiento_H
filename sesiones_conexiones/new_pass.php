<?php
session_start();
include '../database/conexion.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Asegúrate de que las variables POST existan antes de usarlas
    if (isset($_POST['nueva_clave']) && isset($_POST['confirmar_clave'])) {
        $nueva_clave = $_POST['nueva_clave'];
        $confirmar_clave = $_POST['confirmar_clave'];
        $usuario = $_SESSION['usuario'];

        // Verifica que las contraseñas coincidan
        if ($nueva_clave === $confirmar_clave) {
            // Aquí se debe encriptar la contraseña antes de guardarla
            $clave_encriptada = password_hash($nueva_clave, PASSWORD_DEFAULT);

            // Prepara la consulta para actualizar la contraseña
            $stmt = $conn->prepare("UPDATE usuarios SET clave = ?, password_reset_required = 0 WHERE usr = ?");
            $stmt->bind_param('ss', $clave_encriptada, $usuario);

            if ($stmt->execute()) {
                echo "<script>alert('Contraseña cambiada exitosamente.'); window.location.href='../plataforma/seleccionEJE.php';</script>";
            } else {
                echo "<script>alert('Hubo un error. Intenta nuevamente.');</script>";
            }

            $stmt->close();
        } else {
            echo "<script>alert('Las contraseñas no coinciden.');</script>";
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/newPass.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-lg p-4">
            <h1 class="text-center">Cambiar Contraseña</h1>
            <form method="POST">
                <div class="mb-3">
                    <label for="nueva_clave" class="form-label">Nueva Contraseña:</label>
                    <div class="input-group">
                        <input type="password" name="nueva_clave" id="nueva_clave" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary" id="toggleNuevaClave" onclick="togglePassword('nueva_clave', 'toggleNuevaClave')">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                    <div id="passwordHelp" class="form-text">
                        La contraseña debe tener al menos 8 caracteres, una letra mayúscula, una minúscula, un número y un carácter especial.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirmar_clave" class="form-label">Confirmar Contraseña:</label>
                    <div class="input-group">
                        <input type="password" name="confirmar_clave" id="confirmar_clave" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary" id="toggleConfirmarClave" onclick="togglePassword('confirmar_clave', 'toggleConfirmarClave')">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="guardarBtn">Guardar</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.js"></script> <!-- Iconos de Bootstrap -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nuevaClaveInput = document.getElementById('nueva_clave');
            const confirmarClaveInput = document.getElementById('confirmar_clave');
            const form = document.querySelector('form');  // Usamos el formulario directamente

            // Validar nueva contraseña
            // Validar nueva contraseña
            function validarContraseña() {
            const password = nuevaClaveInput.value.trim();  // Eliminar espacios al inicio y final
            const regex = /^(?=.*[a-zA-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+={}[\]|\\:;'"<>,.?/~`])[A-Za-z\d!@#$%^&*()_\-+={}[\]|\\:;'"<>,.?/~`]{8,}$/;

            // Verificar si la contraseña cumple con la expresión regular
            if (regex.test(password)) {
                nuevaClaveInput.classList.remove('is-invalid');
                nuevaClaveInput.classList.add('is-valid');
            } else {
                nuevaClaveInput.classList.remove('is-valid');
                nuevaClaveInput.classList.add('is-invalid');
            }
        }


            // Verificar que las contraseñas coincidan
            function verificarCoincidencia() {
                if (nuevaClaveInput.value === confirmarClaveInput.value) {
                    confirmarClaveInput.classList.remove('is-invalid');
                    confirmarClaveInput.classList.add('is-valid');
                } else {
                    confirmarClaveInput.classList.remove('is-valid');
                    confirmarClaveInput.classList.add('is-invalid');
                }
            }

            // Event listeners para los inputs
            nuevaClaveInput.addEventListener('input', validarContraseña);
            confirmarClaveInput.addEventListener('input', verificarCoincidencia);

            form.addEventListener('submit', function (event) {
                // Prevenir el envío si las contraseñas no son válidas
                if (!nuevaClaveInput.classList.contains('is-valid') || !confirmarClaveInput.classList.contains('is-valid')) {
                    event.preventDefault();
                    alert('Asegúrate de que las contraseñas cumplan con los requisitos y coincidan.');
                }
            });
        });

        // Función para alternar la visibilidad de las contraseñas
        function togglePassword(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            const type = input.type === "password" ? "text" : "password";
            input.type = type;

            const icon = button.querySelector("i");
            if (type === "password") {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        }
    </script>
</body>
</html>
