<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="../css/editar_perfil.css">
    <script>
        // Función para redirigir al dashboard
        function cancelar() {
            window.location.href = '../plataforma/dashboard.php';
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../img/zihua.png" alt="#" class="logo">
            <h2>Sistema Administrativo y Planeación Ayuntamiento Municipal</h2>
        </div>

        <div class="sidebar">
            <ul>
                <li><a href="#">Planeación</a></li>
                <li><a href="#">Planeación 2024</a></li>
            </ul>
        </div>

        <div class="content">
            <h3>Mi Perfil</h3>
            <form action="actualizar_perfil.php" method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre"  >
                </div>
                <div class="form-group">
                    <label for="area">Área</label>
                    <input type="text" id="area" name="area">
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono">
                </div>
                <div class="form-group">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo">
                </div>
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" >
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password">
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn-save">Guardar</button>
                    <button type="button" class="btn-cancel" onclick="cancelar()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
