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
        // Validación de correo
        $patron_correo = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
        $correo_valido = preg_match($patron_correo, $nuevo_correo);
    $usuario_id = $_POST['usuario_id'];
    $nuevo_usr = $_POST['usuario'];
    $nuevo_correo = $_POST['correo'];
    $nueva_clave_temporal = $_POST['clave_temporal'];
    $nuevo_rol = $_POST['rol'];
    $nueva_area = $_POST['dependeciaArea'];
    $nueva_ClaveArea = $_POST['clave_area'];

    if (!$correo_valido) {
        echo "<div class='alert alert-warning'>Por favor, use un correo válido.</div>";
    } else {
        $queryUpdate = "UPDATE usuarios SET usr = ?, correo = ?, rol = ?, clave = ?, password_reset_required = 1 WHERE id = ?";
        $stmtUpdate = $conn->prepare($queryUpdate);

        // Encriptar contraseña temporal si se asigna
        $clave_temporal_encriptada = password_hash($nueva_clave_temporal, PASSWORD_DEFAULT);

        $stmtUpdate->bind_param("ssssi", $nuevo_usr, $nuevo_correo, $nuevo_rol, $clave_temporal_encriptada, $usuario_id);
        $stmtUpdate->execute();
        echo "<p style='color: green;'>Usuario actualizado correctamente con contraseña temporal.</p>";
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
    $nuevo_usr = $_POST['usuario']; // Nombre de usuario
    $nuevo_correo = $_POST['correo']; // Correo electrónico
    $nueva_clave_temporal = $_POST['clave_temporal']; // Contraseña temporal
    $nuevo_rol = $_POST['rol']; // Rol del usuario
    $nueva_area = $_POST['dependeciaArea']; // Área de dependencia
    $nueva_clave_area = $_POST['clave_area']; // Clave del área

    // Encriptar contraseña
    $clave_encriptada = password_hash($nueva_clave_temporal, PASSWORD_DEFAULT);

    // Consulta para crear usuario
    $queryCreate = "INSERT INTO usuarios (usr, clave, correo, rol, dependenciaArea, clave_area, password_reset_required) 
                    VALUES (?, ?, ?, ?, ?, ?, 1)";
    $stmtCreate = $conn->prepare($queryCreate);

    // Asignar parámetros
    $stmtCreate->bind_param("ssssss", $nuevo_usr, $clave_encriptada, $nuevo_correo, $nuevo_rol, $nueva_area, $nueva_clave_area);

    // Ejecutar y verificar
    if ($stmtCreate->execute()) {
        echo "<p style='color: green;'>Usuario creado correctamente.</p>";
    } else {
        echo "<p style='color: red;'>Error al crear el usuario: " . $stmtCreate->error . "</p>";
    }

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            $queryDetalle = "SELECT id, usr, correo, rol, password_reset_required, dependenciaArea FROM usuarios WHERE usr = ?";
            $stmtDetalle = $conn->prepare($queryDetalle);
            $stmtDetalle->bind_param("s", $usuario);
            $stmtDetalle->execute();
            $stmtDetalle->bind_result($id, $usr, $correo, $rol, $password_reset_required, $dependencia);  // Coincide con las columnas seleccionadas
            $stmtDetalle->fetch();
            
        ?>
            <h3>Detalles del Usuario</h3>
            <form method="POST" action="actualizar_usr.php">
                <input type="hidden" name="usuario_id" value="<?= $id ?>">
                <label>Usuario: <input type="text" name="usuario" value="<?= $usr ?>"></label><br>
                <label>Correo: <input type="text" name="correo" value="<?= $correo ?>"></label><br> 
                <label>Asignar Contraseña Temporal: <input type="password" name="clave_temporal" placeholder="Nueva contraseña temporal"></label><br>
                <label>Rol: 
                    <select name="rol">
                        <option value="admin" <?= ($rol == 'admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="user" <?= ($rol == 'user') ? 'selected' : '' ?>>Usuario</option>
                    </select>
                </label><br>
                <label>Área: <input type="text" name="area" value="<?= $dependencia ?>"></label><br> 
                <button type="submit" class="btn btn-success" name="update">Actualizar</button>
                <button type="submit" class="btn btn-danger" name="delete" onclick="return confirmarEliminacion()">Eliminar Usuario</button>
            </form>
        <?php 
            $stmtDetalle->close();
        endif; ?>

<!-- Formulario para agregar un nuevo usuario -->
<div id="addUserForm" style="display: none;">
    <h3>Agregar Nuevo Usuario</h3>
    <form method="POST" action="actualizar_usr.php">
        <!-- Usuario -->
        <label>Usuario: 
            <input type="text" name="usuario" required placeholder="Nombre de usuario">
        </label><br>
        
        <!-- Contraseña Temporal -->
        <label>Asignar Contraseña Temporal: 
            <input type="password" name="clave_temporal" required placeholder="Nueva contraseña temporal">
            <button type="button" onclick="togglePassword()">👁️</button>
        </label><br>

        <!-- Correo -->
        <label>Correo: 
            <input type="email" name="correo" required placeholder="Correo electrónico">
        </label><br>

        <!-- Rol -->
        <label>Rol: 
            <select name="rol" required>
                <option value="admin">Admin</option>
                <option value="user">Usuario</option>
            </select>
        </label><br>

        <!-- Área -->
        <label>Área: 
            <input type="text" name="clave_area" required placeholder="Clave del área">
        </label><br>

        <!-- Clave del Área -->
        <label>Clave del Área: 
            <input type="text" name="clave_area" required placeholder="Clave del área">
        </label><br>

        <!-- Botón para enviar -->
        <button type="submit" class="btn btn-primary" name="create">Crear Usuario</button>
    </form>
</div>
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


    // Mostrar/ocultar contraseña
    function togglePassword() {
        const passwordField = document.querySelector('input[name="clave_temporal"]');
        if (passwordField.type === "password") {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
    </script>
</body>
</html>
