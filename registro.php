<?php
session_start();
// Incluir archivo con la conexión a la base de datos
include 'config_db_mysql.php';
global $link;

// Obtener los datos enviados por fetch
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['usuario']) && isset($data['password'])) {
    $usuario = $data['usuario'];
    $password = hash('sha256', $data['password']); // Encriptar la contraseña con SHA256

    // Consulta para verificar el usuario
    $sql = "SELECT idUsuario, nombreUs, primerApUs, segundoApUs, fechaNacUs, celularUs, ciUs, emailUs, usuarioUs, passwordUs, perfilUs, estadoUs, cambioPass
            FROM usuarios 
            WHERE usuarioUs = ? AND passwordUs = ? AND estadoUs = 'ACTIVO'"; 
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $usuario, $password);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Verificar si el usuario existe
        if (mysqli_num_rows($result) > 0) {
            // Usuario y contraseña correctos

            $datosUsuario = mysqli_fetch_array($result);

            $_SESSION['idUsuario_clinicloud'] = $datosUsuario['idUsuario'];
            $_SESSION['nombreUs_clinicloud'] = $datosUsuario['nombreUs'];
            $_SESSION['primerApUs_clinicloud'] = $datosUsuario['primerApUs'];
            $_SESSION['segundoApUs_clinicloud'] = $datosUsuario['segundoApUs'];
            $_SESSION['fechaNacUs_clinicloud'] = $datosUsuario['fechaNacUs'];
            $_SESSION['celularUs_clinicloud'] = $datosUsuario['celularUs'];
            $_SESSION['ciUs_clinicloud'] = $datosUsuario['ciUs'];
            $_SESSION['emailUs_clinicloud'] = $datosUsuario['emailUs'];
            $_SESSION['usuarioUs_clinicloud'] = $datosUsuario['usuarioUs'];
            $_SESSION['perfilUs_clinicloud'] = $datosUsuario['perfilUs'];

            $cambioPass = $datosUsuario['cambioPass'];

            if($cambioPass == "NO"){
                echo "success"; // Respuesta de éxito
            }
            else{
                echo "cambioPass";
            }

            


        } else {
            // Usuario o contraseña incorrectos
            echo "Error: Usuario o contraseña incorrectos";
        }

        // Liberar la consulta
        mysqli_stmt_close($stmt);
    } else {
        // Error en la consulta
        echo "Error: No se pudo preparar la consulta";
    }
} else {
    // Si faltan los datos
    echo "Error: Faltan datos";
}
?>
