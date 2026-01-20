<?php
session_start();
include 'config_db_mysql.php';
global $link;

$idUsuario = $_SESSION['idUsuario_clinicloud'] ?? null;

// Obtener datos JSON
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validar existencia de parámetros
if (!isset($data['usuario'], $data['password'], $data['passwordConfirm'])) {
    echo "Faltan datos requeridos";
    exit;
}

$usuario         = $data['usuario'];
$passwordNueva   = $data['password'];
$passwordConfirm = $data['passwordConfirm'];

// Verificar que coincidan las nuevas contraseñas
if ($passwordNueva !== $passwordConfirm) {
    echo "Las contraseñas nuevas no coinciden";
    exit;
}

// Consultar la contraseña actual desde la BD
$query = "SELECT passwordUs FROM usuarios WHERE idUsuario = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "i", $idUsuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $passwordBD);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);



// Generar hash de la nueva contraseña en sha256
$passwordNuevaHash = hash('sha256', $passwordNueva);

// Actualizar contraseña
$update = "UPDATE usuarios SET passwordUs = ?, cambioPass = 'NO' WHERE idUsuario = ?";
$stmt2 = mysqli_prepare($link, $update);
mysqli_stmt_bind_param($stmt2, "si", $passwordNuevaHash, $idUsuario);
$result = mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

echo $result ? "success" : "Error al cambiar la contraseña";
?>
