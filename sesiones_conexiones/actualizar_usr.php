<?php
session_start();
include '../database/conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Obtener todos los usuarios
$query = "SELECT id, usr FROM usuarios";
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bind_result($id, $usr);
$stmt->store_result();

$usuarios = [];
while ($stmt->fetch()) {
    $usuarios[] = ['id' => $id, 'usr' => $usr];
}

// Actualizar el usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $usuario_id = $_POST['usuario_id'];
    $nuevo_usr = $_POST['usuario'];
    $nueva_clave = $_POST['clave'];
    $nueva_clave_temporal = $_POST['clave_temporal'];
    $nuevo_correo = $_POST['correo'];
    $nuevo_area_admin = $_POST['area_admin'];
    $nuevo_rol = $_POST['rol'];
    $nuevo_dependencia_area = isset($_POST['dependencia_area']) ? $_POST['dependencia_area'] : NULL;
    $nuevo_clave_area = isset($_POST['clave_area']) ? $_POST['clave_area'] : NULL;

    // Validación de correo
    $patron_correo = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    $correo_valido = preg_match($patron_correo, $nuevo_correo);

    if (!$correo_valido) {
        echo "<div class='alert alert-warning'>Por favor, use un correo válido.</div>";
    } else {
        $queryUpdate = "UPDATE usuarios SET usr = ?, clave = ?, correo = ?, rol = ?, dependenciaArea = ?, clave_area = ?, password_reset_required = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($queryUpdate);

        // Encriptar contraseñas
        $clave_encriptada = password_hash($nueva_clave, PASSWORD_DEFAULT);
        $clave_temporal_encriptada = !empty($nueva_clave_temporal) ? password_hash($nueva_clave_temporal, PASSWORD_DEFAULT) : NULL;

        // Si el admin asigna una contraseña temporal, marcar password_reset_required = 1
        if (!empty($nueva_clave_temporal)) {
            $stmtUpdate->bind_param("sssssisi", $nuevo_usr, $clave_temporal_encriptada, $nuevo_correo, $nuevo_rol, $nuevo_dependencia_area, $nuevo_clave_area, $reset_required = 1, $usuario_id);
        } else {
            $stmtUpdate->bind_param("sssssisi", $nuevo_usr, $clave_encriptada, $nuevo_correo, $nuevo_rol, $nuevo_dependencia_area, $nuevo_clave_area, $reset_required = 0, $usuario_id);
        }

        $stmtUpdate->execute();
        echo "<p style='color: green;'>Usuario actualizado correctamente.</p>";
        $stmtUpdate->close();
    }
}

// Eliminar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $usuario_id = $_POST['usuario_id'];
    $queryDelete = "DELETE FROM usuarios WHERE id = ?";
    $stmtDelete = $conn->prepare($queryDelete);
    $stmtDelete->bind_param("i", $usuario_id);
    if ($stmtDelete->execute()) {
        echo "<p style='color: red;'>Usuario eliminado correctamente.</p>";
    } else {
        echo "<p style='color: red;'>Error al eliminar el usuario: " . $stmtDelete->error . "</p>";
    }
    $stmtDelete->close();
}

// Crear usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $nuevo_usr = $_POST['new_usuario'];
    $nueva_clave = $_POST['new_clave'];
    $nuevo_correo = $_POST['new_correo'];
    $nuevo_area_admin = $_POST['new_area_admin'];
    $nuevo_rol = $_POST['new_rol'];

    // Encriptar contraseña
    $clave_encriptada = password_hash($nueva_clave, PASSWORD_DEFAULT);

    $queryCreate = "INSERT INTO usuarios (usr, clave, correo, dependenciaArea, rol) VALUES (?, ?, ?, ?, ?)";
    $stmtCreate = $conn->prepare($queryCreate);
    $stmtCreate->bind_param("sssss", $nuevo_usr, $clave_encriptada, $nuevo_correo, $nuevo_area_admin, $nuevo_rol);
    $stmtCreate->execute();
    echo "<p style='color: green;'>Usuario creado correctamente.</p>";
    $stmtCreate->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/actualizar_usr.css">

</head>
<body>
    <div class="header">
        <div class="logo"><img src="../img/zihua.png" alt="Logo"></div>
        <div class="user-options">
            <p>Bienvenido, <?= $_SESSION['usuario']; ?>!</p>
            <a href="../sesiones_conexiones/destruir_sesion.php" class="logout-icon">
                <i class="fa-sharp fa-solid fa-arrow-right-from-bracket fa-2xl" style="color: #ffffff;"></i>
            </a>
        </div>
    </div>

    <div class="sidebar">
        <nav>
            <ul><li><a href="../plataforma/dashboard.php">Dashboard</a></li></ul>
            <ul><li><a href="../formularios/pre_a.php">Mostrar Actividades</a></li></ul>
        </nav>
    </div>

    <div class="main-content">
        <h2 class="header-text">Administrar Usuarios</h2>

        <!-- Buscador de Usuario -->
        <form method="POST" action="actualizar_usr.php" style="display: inline-block; width: 100%;">
            <label for="buscar_usuario">Buscar usuario:</label>
            <input type="text" id="buscar_usuario" oninput="filtrarUsuarios()" placeholder="Escribe el usuario que deseas buscar" class="form-control mb-2">
            
            <!-- Contenedor de resultados -->
            <ul id="resultados" class="list-group mb-2" style="max-height: 200px; overflow-y: auto;">
                <?php foreach ($usuarios as $usuario): ?>
                    <li class="list-group-item resultado-item" onclick="seleccionarUsuario('<?= $usuario['usr']; ?>')">
                        <?= $usuario['usr']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Campo oculto para enviar el usuario seleccionado -->
            <input type="hidden" name="usuario" id="usuario_seleccionado">
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Seleccionar Usuario</button>
                <button type="button" class="btn btn-success" onclick="toggleAddUserForm()">Agregar Usuario</button>
            </div>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'])): 
            $usuario = $_POST['usuario'];
            $queryDetalle = "SELECT * FROM usuarios WHERE usr = ?";
            $stmtDetalle = $conn->prepare($queryDetalle);
            $stmtDetalle->bind_param("s", $usuario);
            $stmtDetalle->execute();
            $stmtDetalle->bind_result($id, $usr, $clave, $correo, $dependenciaArea, $rol, $clave_area, $password_reset_required);

            $stmtDetalle->fetch();
        ?>
            <h3>Detalles del Usuario</h3>
            <form method="POST" action="actualizar_usr.php">
                <input type="hidden" name="usuario_id" value="<?= $id ?>">
                <label>Usuario: <input type="text" name="usuario" value="<?= $usr ?>"></label><br>
                <label>Contraseña Actual: <input type="password" name="clave" value="<?= $clave ?>" readonly></label><br>
                <label>Asignar Contraseña Temporal: <input type="password" name="clave_temporal" placeholder="Nueva contraseña temporal"></label><br>
                <label>Correo: <input type="text" name="correo" value="<?= $correo ?>"></label><br> 
                <label>Área: <input type="text" name="dependencia_area" value="<?= $dependenciaArea ?>"></label><br>
                <label>Clave: <input type="text" name="clave_area" value="<?= $clave_area ?>"></label><br>
                <label>Rol: 
                    <select name="rol">
                        <option value="admin" <?= ($rol == 'admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="user" <?= ($rol == 'user') ? 'selected' : '' ?>>Usuario</option>
                    </select>
                </label><br>
                <button type="submit" class="btn btn-success" name="update">Actualizar</button>
                <button type="submit" class="btn btn-danger" name="delete" onclick="return confirmarEliminacion()">Eliminar Usuario</button>
            </form>
            
        <?php 
            $stmtDetalle->close();
        endif; ?>
    </div>
    <script>
        function toggleAddUserForm() {
            const form = document.getElementById('addUserForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function filtrarUsuarios() {
            const input = document.getElementById('buscar_usuario');
            const filter = input.value.toLowerCase();
            const resultados = document.getElementById('resultados');
            const items = resultados.getElementsByClassName('resultado-item');

            // Filtrar resultados
            for (let i = 0; i < items.length; i++) {
                const text = items[i].textContent || items[i].innerText;
                items[i].style.display = text.toLowerCase().includes(filter) ? '' : 'none';
            }
        }

        function seleccionarUsuario(usuario) {
            // Asignar el usuario seleccionado al campo oculto
            document.getElementById('usuario_seleccionado').value = usuario;

            // Mostrar el usuario seleccionado en el cuadro de texto
            document.getElementById('buscar_usuario').value = usuario;

            // Limpiar los resultados
            const resultados = document.getElementById('resultados');
            resultados.innerHTML = '';
        }

        // Confirmar eliminación de usuario
        function confirmarEliminacion() {
            return confirm('¿Estás seguro de que deseas eliminar este usuario?');
        }
    </script>
</body>
</html>
