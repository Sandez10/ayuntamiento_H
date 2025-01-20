<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIPMEPP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="css/login.css"> 
</head>
<body>
    <div id="contenedor">
        <div id="central" class="card shadow-lg p-4">
            <img src="/img/SIPMEPP.png" alt="Logo">
            <div class="titulo">Inicio de Sesión</div>
            <form action="./sesiones_conexiones/sesion.php" method="post"> 
                <input type="text" name="usuario" class="form-control mb-3" placeholder="Usuario" required>
                <input type="password" name="clave" class="form-control mb-3" placeholder="Contraseña" required>
                <button type="submit" class="btn btn-primary w-100" title="Ingresar" name="btnAcceder">Acceder</button>
            </form>
            <p>Sistema Integral de Planeación, Monitoreo y Evaluación de Políticas Públicas</p>
        </div>
    </div>

    <!-- Bootstrap Bundle (incluye Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
