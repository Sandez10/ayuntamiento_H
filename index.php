<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIPSEPP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="css/login.css"> 
</head>
<body>
    <div id="contenedor">
        <div id="central" class="card shadow-lg p-4">
            <img src="img/SIPSEPP.png" alt="Sistema Integral de Planeación, Seguimiento y Evaluación de Políticas Públicas">
            <div class="titulo">Inicio de Sesión</div>
            <form action="./sesiones_conexiones/sesion.php" method="post"> 
                <input type="text" name="usuario" class="form-control mb-3" placeholder="Usuario" required>
                <div class="input-group mb-3">
                    <input type="password" id="clave" name="clave" class="form-control" placeholder="Contraseña" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-primary w-100" title="Ingresar" name="btnAcceder">Acceder</button>
            </form>
            <p>Sistema Integral de Planeación, Seguimiento y Evaluación de Políticas Públicas</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('clave');
            const toggleIcon = document.getElementById('toggleIcon');

            // Cambia el tipo de input entre "password" y "text"
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
