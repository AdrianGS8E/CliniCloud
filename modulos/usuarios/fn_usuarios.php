<?php
session_start();

// SI NO HAY SESIÓN, DETENER TODO ANTES DE INCLUIR O PROCESAR NADA
if (!isset($_SESSION['idUsuario_clinicloud'])) {
    // Forzar JSON limpio si se trata de una petición AJAX (fetch)
    header('Content-Type: application/json');
    echo json_encode(['sesion' => 'cerrada']);
    exit;
}

require_once "../../config_db_mysql.php";
header("Content-Type: application/json");

// Leer datos enviados desde fetch
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

if (!isset($input['funcion'])) {
    echo json_encode(["estado" => "ERROR", "mensaje" => "No se especificó la función a ejecutar."]);
    exit;
}

switch ($input['funcion']) {
    case "listaUsuarios":
        listaUsuarios();
        break;
    case 'formNuevoUsuario':
        formNuevoUsuario();
        break;
    case 'resetPassword':
        resetPassword();
        break;
    case 'registrarUsuario':
        registrarUsuario();
        break;
    case 'formEditarUsuario':
        formEditarUsuario();
        break;
    case 'editarUsuario':
        editarUsuario();
        break;
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function listaUsuarios(){
    global $link;
    global $input;


    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Lista de usuarios</b>";
                echo "</div>";
                echo "<div class='card-body'>";
                    echo "<table class='table table-bordered'>";
                        echo "<thead>";
                            echo "<tr>";
                                echo "<th>Apellido Paterno</th>";
                                echo "<th>Apellido Materno</th>";
                                echo "<th>Nombre</th>";
                                echo "<th>CI</th>";
                                echo "<th>Usuario</th>";
                                echo "<th>Perfil</th>";
                                echo "<th>Acciones</th>";
                            echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                            //SELECT `idUsuario`, `nombreUs`, `primerApUs`, `segundoApUs`, `fechaNacUs`, `celularUs`, `ciUs`, `emailUs`, `usuarioUs`, `passwordUs`, `perfilUs`, `estadoUs`, `agenciaUS` FROM `usuarios` ORDER BY `primerApUs`,`segundoApUs`,`nombreUs` ASC
                            $sql = "SELECT `idUsuario`, `nombreUs`, `primerApUs`, `segundoApUs`, `fechaNacUs`, `celularUs`, `ciUs`, `emailUs`, `usuarioUs`, `passwordUs`, `perfilUs`, `estadoUs` FROM `usuarios` ORDER BY `primerApUs`,`segundoApUs`,`nombreUs` ASC";
                            $result = mysqli_query($link, $sql);
                            while ($row = mysqli_fetch_array($result)) {
                                echo "<tr>";
                                    echo "<td>" . $row['primerApUs'] . "</td>";
                                    echo "<td>" . $row['segundoApUs'] . "</td>";
                                    echo "<td>" . $row['nombreUs'] . "</td>";
                                    echo "<td>" . $row['ciUs'] . "</td>";
                                    echo "<td>" . $row['usuarioUs'] . "</td>";
                                    echo "<td>" . $row['perfilUs'] . "</td>";
                                    echo "<td class='text-center'>";
                                        echo "<div class='btn-group'>";
                                            echo "<button class='btn btn-xs btn-primary btnFormEditarUsuario' id='" . $row['idUsuario'] . "' title='Editar Usuario'><i class='fas fa-edit'></i></button>";
                                            echo "<button class='btn btn-xs btn-danger btnResetPassword' id='" . $row['idUsuario'] . "' title='Resetear Contraseña'><i class='fas fa-key'></i></button>";
                                        echo "</div>";
                                    echo "</td>";
                                echo "</tr>";
                            }

                        echo "</tbody>";
                    echo "</table>";


                echo "</div>";
                echo "<div class='card-footer text-right py-2 text-center'>";
                    echo "<button class='btn btn-primary' id='btnFormNuevoUsuario'><i class='fas fa-plus'></i> Nuevo usuario</a>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function resetPassword(){
    global $link;
    global $input;

    $idUsuario = $input['idUsuario'];

    $conUsuario = mysqli_query($link, "SELECT `ciUs` FROM `usuarios` WHERE `idUsuario` = '$idUsuario'")or die(mysqli_error($link));
    if(mysqli_num_rows($conUsuario) > 0){
        $rowUsuario = mysqli_fetch_array($conUsuario);
        $ciUs = $rowUsuario['ciUs'];
    }

    //ciUs in sha 256
    $password = hash('sha256', $ciUs);

    $conUpdate = mysqli_query($link, "UPDATE `usuarios` SET `passwordUs`='$password',`cambioPass`='SI' WHERE `idUsuario` = '$idUsuario'")or die(mysqli_error($link));
    if($conUpdate){
        echo "OK";
    } else {
        echo "ERROR";
    }
    
}

function formNuevoUsuario(){
    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Nuevo Usuario</b>";
                echo "</div>";
                echo "<div class='card-body row'>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='nombreUs' class='form-label'>Nombre(s)</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='nombreUs' name='nombreUs' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='primerApUs' class='form-label'>Primer Apellido</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='primerApUs' name='primerApUs' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='segundoApUs' class='form-label'>Segundo Apellido</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='segundoApUs' name='segundoApUs' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='fechaNacUs' class='form-label'>Fecha de Nacimiento</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-calendar'></i></span>";
                            echo "<input type='date' id='fechaNacUs' name='fechaNacUs' class='form-control'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='celularUs' class='form-label'>Celular</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-phone'></i></span>";
                            echo "<input type='text' id='celularUs' name='celularUs' class='form-control'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='ciUs' class='form-label'>C.I.</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-id-card'></i></span>";
                            echo "<input type='text' id='ciUs' name='ciUs' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='emailUs' class='form-label'>Correo electrónico</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-envelope'></i></span>";
                            echo "<input type='email' id='emailUs' name='emailUs' class='form-control'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='usuarioUs' class='form-label'>Usuario</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user-circle'></i></span>";
                            echo "<input type='text' id='usuarioUs' name='usuarioUs' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='perfilUs' class='form-label'>Perfil</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-users-cog'></i></span>";
                            echo "<select id='perfilUs' name='perfilUs' class='form-select'>";
                                echo "<option value=''>- SELECCIONAR -</option>";
                                echo "<option value='ADMINISTRADOR'>ADMINISTRADOR</option>";
                                echo "<option value='MEDICO'>MEDICO</option>";
                                echo "<option value='CAJERO'>CAJERO</option>";
                            echo "</select>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='estadoUs' class='form-label'>Estado</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-check-circle'></i></span>";
                            echo "<select id='estadoUs' name='estadoUs' class='form-select'>";
                                echo "<option value='ACTIVO'>ACTIVO</option>";
                                echo "<option value='INACTIVO'>INACTIVO</option>";
                            echo "</select>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
                echo "<div class='card-footer text-center py-2'>";
                    echo "<button class='btn btn-primary' id='btnRegistrarUsuario'><i class='fas fa-user-plus'></i> Registrar Usuario</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function registrarUsuario(){
    global $link;
    global $input;

    //INSERT INTO `usuarios`(`nombreUs`, `primerApUs`, `segundoApUs`, `fechaNacUs`, `celularUs`, `ciUs`, `emailUs`, `usuarioUs`, `passwordUs`, `perfilUs`, `estadoUs`, `cambioPass`) VALUES ('[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]','[value-13]')
    $datosUsuario = $input['datosUsuario'];
    $nombreUs = $datosUsuario['nombreUs'];
    $primerApUs = $datosUsuario['primerApUs'];
    $segundoApUs = $datosUsuario['segundoApUs'];
    $fechaNacUs = $datosUsuario['fechaNacUs'];
    $celularUs = $datosUsuario['celularUs'];
    $ciUs = $datosUsuario['ciUs'];
    $emailUs = $datosUsuario['emailUs'];
    $usuarioUs = $datosUsuario['usuarioUs'];
    $passwordUs = hash('sha256', $datosUsuario['ciUs']);
    $perfilUs = $datosUsuario['perfilUs'];
    $estadoUs = $datosUsuario['estadoUs'];
    $cambioPass = 'SI';

    // $sql = "INSERT INTO `usuarios`(`nombreUs`, `primerApUs`, `segundoApUs`, `fechaNacUs`, `celularUs`, `ciUs`, `emailUs`, `usuarioUs`, `passwordUs`, `perfilUs`, `estadoUs`, `cambioPass`) VALUES ('$nombreUs','$primerApUs','$segundoApUs','$fechaNacUs','$celularUs','$ciUs','$emailUs','$usuarioUs','$passwordUs','$perfilUs','$estadoUs','$cambioPass')";
    // $result = mysqli_query($link, $sql);
    // if ($result) {
    //     echo "OK";
    // } 

    $conRegistroUsuario = mysqli_query($link, "INSERT INTO `usuarios`(`nombreUs`, `primerApUs`, `segundoApUs`, `fechaNacUs`, `celularUs`, `ciUs`, 
                                                                `emailUs`, `usuarioUs`, `passwordUs`, `perfilUs`, `estadoUs`, `cambioPass`) 
                                                VALUES ('$nombreUs','$primerApUs','$segundoApUs','$fechaNacUs','$celularUs','$ciUs',
                                                    '$emailUs','$usuarioUs','$passwordUs','$perfilUs','$estadoUs','$cambioPass')") or die(mysqli_error($link));
    if ($conRegistroUsuario) {
        echo "OK";
    } else {
        echo "ERROR: " . mysqli_error($link);
    }
}

function formEditarUsuario(){
    global $link;
    global $input;

    $idUsuario = $input['idUsuario'];

    $conUsuario = mysqli_query($link, "SELECT `idUsuario`, `nombreUs`, `primerApUs`, `segundoApUs`, `fechaNacUs`, `celularUs`, 
    `ciUs`, `emailUs`, `usuarioUs`, `passwordUs`, `perfilUs`, `estadoUs`, `cambioPass` 
    FROM `usuarios` WHERE `idUsuario` = '$idUsuario'") or die(mysqli_error($link));
    if(mysqli_num_rows($conUsuario) > 0){
        $rowUsuario = mysqli_fetch_array($conUsuario);
        $nombreUs = $rowUsuario['nombreUs'];
        $primerApUs = $rowUsuario['primerApUs'];
        $segundoApUs = $rowUsuario['segundoApUs'];
        $fechaNacUs = $rowUsuario['fechaNacUs'];
        $celularUs = $rowUsuario['celularUs'];
        $ciUs = $rowUsuario['ciUs'];
        $emailUs = $rowUsuario['emailUs'];
        $usuarioUs = $rowUsuario['usuarioUs'];
        $passwordUs = $rowUsuario['passwordUs'];
        $perfilUs = $rowUsuario['perfilUs'];
        $estadoUs = $rowUsuario['estadoUs'];
        $cambioPass = $rowUsuario['cambioPass'];
    }

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Editar Usuario</b>";
                echo "</div>";
                echo "<div class='card-body row'>";
                    
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='nombreUs' class='form-label'>Nombre(s)</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='nombreUs' name='nombreUs' class='form-control text-uppercase' value='$nombreUs'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='primerApUs' class='form-label'>Primer Apellido</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='primerApUs' name='primerApUs' class='form-control text-uppercase' value='$primerApUs'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='segundoApUs' class='form-label'>Segundo Apellido</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='segundoApUs' name='segundoApUs' class='form-control text-uppercase' value='$segundoApUs'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='fechaNacUs' class='form-label'>Fecha de Nacimiento</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-calendar'></i></span>";
                            echo "<input type='date' id='fechaNacUs' name='fechaNacUs' class='form-control' value='$fechaNacUs'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='celularUs' class='form-label'>Celular</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-phone'></i></span>";
                            echo "<input type='text' id='celularUs' name='celularUs' class='form-control' value='$celularUs'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='ciUs' class='form-label'>C.I.</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-id-card'></i></span>";
                            echo "<input type='text' id='ciUs' name='ciUs' class='form-control text-uppercase' value='$ciUs'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='emailUs' class='form-label'>Correo electrónico</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-envelope'></i></span>";
                            echo "<input type='email' id='emailUs' name='emailUs' class='form-control' value='$emailUs'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='usuarioUs' class='form-label'>Usuario</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user-circle'></i></span>";
                            echo "<input type='text' id='usuarioUs' name='usuarioUs' class='form-control text-uppercase' value='$usuarioUs'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='perfilUs' class='form-label'>Perfil</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-users-cog'></i></span>";
                            echo "<select id='perfilUs' name='perfilUs' class='form-select' value='$perfilUs'>";
                                echo "<option value=''>- SELECCIONAR -</option>";
                                echo "<option value='ADMINISTRADOR' " . ($perfilUs == 'ADMINISTRADOR' ? 'selected' : '') . ">ADMINISTRADOR</option>";
                                echo "<option value='MEDICO' " . ($perfilUs == 'MEDICO' ? 'selected' : '') . ">MEDICO</option>";
                                echo "<option value='CAJERO' " . ($perfilUs == 'CAJERO' ? 'selected' : '') . ">CAJERO</option>";
                            echo "</select>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='estadoUs' class='form-label'>Estado</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-check-circle'></i></span>";
                            echo "<select id='estadoUs' name='estadoUs' class='form-select' value='$estadoUs'>";
                                echo "<option value='ACTIVO' " . ($estadoUs == 'ACTIVO' ? 'selected' : '') . ">ACTIVO</option>";
                                echo "<option value='INACTIVO' " . ($estadoUs == 'INACTIVO' ? 'selected' : '') . ">INACTIVO</option>";
                            echo "</select>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
                echo "<div class='card-footer text-center py-2'>";
                    echo "<button class='btn btn-primary' id='btnEditarUsuario'><i class='fas fa-save'></i> Guardar Cambios</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function editarUsuario(){
    global $link;
    global $input;

    $datosUsuario = $input['datosUsuario'];
    $idUsuario = $datosUsuario['idUsuario'];
    $nombreUs = $datosUsuario['nombreUs'];
    $primerApUs = $datosUsuario['primerApUs'];
    $segundoApUs = $datosUsuario['segundoApUs'];
    $fechaNacUs = $datosUsuario['fechaNacUs'];
    $celularUs = $datosUsuario['celularUs'];
    $ciUs = $datosUsuario['ciUs'];
    $emailUs = $datosUsuario['emailUs'];
    $usuarioUs = $datosUsuario['usuarioUs'];
    $perfilUs = $datosUsuario['perfilUs'];
    $estadoUs = $datosUsuario['estadoUs'];

    $conUpdate = mysqli_query($link, "UPDATE `usuarios` SET `nombreUs`='$nombreUs',`primerApUs`='$primerApUs',`segundoApUs`='$segundoApUs',`fechaNacUs`='$fechaNacUs',`celularUs`='$celularUs',`ciUs`='$ciUs',`emailUs`='$emailUs',`usuarioUs`='$usuarioUs',`perfilUs`='$perfilUs',`estadoUs`='$estadoUs' WHERE `idUsuario` = '$idUsuario'") or die(mysqli_error($link));
    if ($conUpdate) {
        echo "OK";
    }

}

?>