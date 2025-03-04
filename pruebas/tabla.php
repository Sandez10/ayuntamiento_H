<?php


include '../database/conexion.php';

// Obtener información adicional del usuario
$query = "ALTER TABLE listaactividades 
          ADD id_suinpac INT, 
          ADD codigo_indicador VARCHAR(255), 
          ADD nivel_indicador VARCHAR(255), 
          ADD tendencia VARCHAR(255), 
          ADD variables VARCHAR(255);";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($rol);
$stmt->fetch();
$stmt->close();


?>