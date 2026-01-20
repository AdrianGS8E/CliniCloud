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
    case "listaPacientes":
        listaPacientes();
        break;
    case 'formNuevoPaciente':
        formNuevoPaciente();
        break;
    case 'registrarPaciente':
        registrarPaciente();
        break;
    case 'formEditarPaciente':
        formEditarPaciente();
        break;
    case 'editarPaciente':
        editarPaciente();
        break;
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function listaPacientes(){
    global $link;
    global $input;

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Lista de pacientes</b>";
                echo "</div>";
                echo "<div class='card-body'>";
                    echo "<table class='table table-bordered'>";
                        echo "<thead>";
                            echo "<tr>";
                                echo "<th>C.I.</th>";
                                echo "<th>Apellido Paterno</th>";
                                echo "<th>Apellido Materno</th>";
                                echo "<th>Nombres</th>";
                                echo "<th>Fecha Nacimiento</th>";
                                echo "<th>Celular</th>";
                                echo "<th>Acciones</th>";
                            echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                            $sql = "SELECT `idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `email`, `direccion`, `procedencia`, `residencia`, `nombreTutor`, `celularTutor` FROM `pacientes` ORDER BY `apellidoPat`,`apellidoMat`,`nombres` ASC";
                            $result = mysqli_query($link, $sql);
                            while ($row = mysqli_fetch_array($result)) {
                                echo "<tr>";
                                    echo "<td>" . $row['ci'] . "</td>";
                                    echo "<td>" . $row['apellidoPat'] . "</td>";
                                    echo "<td>" . $row['apellidoMat'] . "</td>";
                                    echo "<td>" . $row['nombres'] . "</td>";
                                    echo "<td>" . $row['fechaNacimiento'] . "</td>";
                                    echo "<td>" . $row['celular'] . "</td>";
                                    echo "<td class='text-center'>";
                                        echo "<div class='btn-group'>";
                                            echo "<button class='btn btn-xs btn-primary btnFormEditarPaciente' id='" . $row['idPaciente'] . "' title='Editar Paciente'><i class='fas fa-edit'></i></button>";
                                        echo "</div>";
                                    echo "</td>";
                                echo "</tr>";
                            }

                        echo "</tbody>";
                    echo "</table>";


                echo "</div>";
                echo "<div class='card-footer text-right py-2 text-center'>";
                    echo "<button class='btn btn-primary' id='btnFormNuevoPaciente'><i class='fas fa-plus'></i> Nuevo paciente</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function formNuevoPaciente(){
    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Nuevo Paciente</b>";
                echo "</div>";
                echo "<div class='card-body row'>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='ci' class='form-label'>C.I.</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-id-card'></i></span>";
                            echo "<input type='text' id='ci' name='ci' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='apellidoPat' class='form-label'>Apellido Paterno</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='apellidoPat' name='apellidoPat' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='apellidoMat' class='form-label'>Apellido Materno</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='apellidoMat' name='apellidoMat' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='nombres' class='form-label'>Nombres</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='nombres' name='nombres' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='fechaNacimiento' class='form-label'>Fecha de Nacimiento</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-calendar'></i></span>";
                            echo "<input type='date' id='fechaNacimiento' name='fechaNacimiento' class='form-control'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='celular' class='form-label'>Celular</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-phone'></i></span>";
                            echo "<input type='text' id='celular' name='celular' class='form-control'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='email' class='form-label'>Correo electrónico</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-envelope'></i></span>";
                            echo "<input type='email' id='email' name='email' class='form-control'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='direccion' class='form-label'>Dirección</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-map-marker-alt'></i></span>";
                            echo "<input type='text' id='direccion' name='direccion' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='procedencia' class='form-label'>Procedencia</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-map'></i></span>";
                            echo "<input type='text' id='procedencia' name='procedencia' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='residencia' class='form-label'>Residencia</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-home'></i></span>";
                            echo "<input type='text' id='residencia' name='residencia' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='nombreTutor' class='form-label'>Nombre del Tutor</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user-shield'></i></span>";
                            echo "<input type='text' id='nombreTutor' name='nombreTutor' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='celularTutor' class='form-label'>Celular del Tutor</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-phone'></i></span>";
                            echo "<input type='text' id='celularTutor' name='celularTutor' class='form-control'>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
                echo "<div class='card-footer text-center py-2'>";
                    echo "<button class='btn btn-primary' id='btnRegistrarPaciente'><i class='fas fa-user-plus'></i> Registrar Paciente</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function registrarPaciente(){
    global $link;
    global $input;

    $datosPaciente = $input['datosPaciente'];
    $ci = $datosPaciente['ci'];
    $apellidoPat = $datosPaciente['apellidoPat'];
    $apellidoMat = $datosPaciente['apellidoMat'];
    $nombres = $datosPaciente['nombres'];
    $fechaNacimiento = $datosPaciente['fechaNacimiento'];
    $celular = $datosPaciente['celular'];
    $email = $datosPaciente['email'];
    $direccion = $datosPaciente['direccion'];
    $procedencia = $datosPaciente['procedencia'];
    $residencia = $datosPaciente['residencia'];
    $nombreTutor = $datosPaciente['nombreTutor'];
    $celularTutor = $datosPaciente['celularTutor'];

    $conRegistroPaciente = mysqli_query($link, "INSERT INTO `pacientes`(`ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `email`, `direccion`, `procedencia`, `residencia`, `nombreTutor`, `celularTutor`) 
                                                    VALUES ('$ci','$apellidoPat','$apellidoMat','$nombres','$fechaNacimiento','$celular','$email','$direccion','$procedencia','$residencia','$nombreTutor','$celularTutor')") or die(mysqli_error($link));
    if ($conRegistroPaciente) {
        echo "OK";
    } else {
        echo "ERROR: " . mysqli_error($link);
    }
}

function formEditarPaciente(){
    global $link;
    global $input;

    $idPaciente = $input['idPaciente'];

    $conPaciente = mysqli_query($link, "SELECT `idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `email`, `direccion`, `procedencia`, `residencia`, `nombreTutor`, `celularTutor` 
                                        FROM `pacientes` WHERE `idPaciente` = '$idPaciente'") or die(mysqli_error($link));
    if(mysqli_num_rows($conPaciente) > 0){
        $rowPaciente = mysqli_fetch_array($conPaciente);
        $ci = $rowPaciente['ci'];
        $apellidoPat = $rowPaciente['apellidoPat'];
        $apellidoMat = $rowPaciente['apellidoMat'];
        $nombres = $rowPaciente['nombres'];
        $fechaNacimiento = $rowPaciente['fechaNacimiento'];
        $celular = $rowPaciente['celular'];
        $email = $rowPaciente['email'];
        $direccion = $rowPaciente['direccion'];
        $procedencia = $rowPaciente['procedencia'];
        $residencia = $rowPaciente['residencia'];
        $nombreTutor = $rowPaciente['nombreTutor'];
        $celularTutor = $rowPaciente['celularTutor'];
    }

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Editar Paciente</b>";
                echo "</div>";
                echo "<div class='card-body row'>";
                    
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='ci' class='form-label'>C.I.</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-id-card'></i></span>";
                            echo "<input type='text' id='ci' name='ci' class='form-control text-uppercase' value='$ci'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='apellidoPat' class='form-label'>Apellido Paterno</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='apellidoPat' name='apellidoPat' class='form-control text-uppercase' value='$apellidoPat'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='apellidoMat' class='form-label'>Apellido Materno</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='apellidoMat' name='apellidoMat' class='form-control text-uppercase' value='$apellidoMat'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='nombres' class='form-label'>Nombres</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                            echo "<input type='text' id='nombres' name='nombres' class='form-control text-uppercase' value='$nombres'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='fechaNacimiento' class='form-label'>Fecha de Nacimiento</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-calendar'></i></span>";
                            echo "<input type='date' id='fechaNacimiento' name='fechaNacimiento' class='form-control' value='$fechaNacimiento'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='celular' class='form-label'>Celular</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-phone'></i></span>";
                            echo "<input type='text' id='celular' name='celular' class='form-control' value='$celular'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='email' class='form-label'>Correo electrónico</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-envelope'></i></span>";
                            echo "<input type='email' id='email' name='email' class='form-control' value='$email'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='direccion' class='form-label'>Dirección</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-map-marker-alt'></i></span>";
                            echo "<input type='text' id='direccion' name='direccion' class='form-control text-uppercase' value='$direccion'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='procedencia' class='form-label'>Procedencia</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-map'></i></span>";
                            echo "<input type='text' id='procedencia' name='procedencia' class='form-control text-uppercase' value='$procedencia'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='residencia' class='form-label'>Residencia</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-home'></i></span>";
                            echo "<input type='text' id='residencia' name='residencia' class='form-control text-uppercase' value='$residencia'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='nombreTutor' class='form-label'>Nombre del Tutor</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-user-shield'></i></span>";
                            echo "<input type='text' id='nombreTutor' name='nombreTutor' class='form-control text-uppercase' value='$nombreTutor'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='celularTutor' class='form-label'>Celular del Tutor</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-phone'></i></span>";
                            echo "<input type='text' id='celularTutor' name='celularTutor' class='form-control' value='$celularTutor'>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
                echo "<div class='card-footer text-center py-2'>";
                    echo "<button class='btn btn-primary' id='btnEditarPaciente'><i class='fas fa-save'></i> Guardar Cambios</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function editarPaciente(){
    global $link;
    global $input;

    $datosPaciente = $input['datosPaciente'];
    $idPaciente = $datosPaciente['idPaciente'];
    $ci = $datosPaciente['ci'];
    $apellidoPat = $datosPaciente['apellidoPat'];
    $apellidoMat = $datosPaciente['apellidoMat'];
    $nombres = $datosPaciente['nombres'];
    $fechaNacimiento = $datosPaciente['fechaNacimiento'];
    $celular = $datosPaciente['celular'];
    $email = $datosPaciente['email'];
    $direccion = $datosPaciente['direccion'];
    $procedencia = $datosPaciente['procedencia'];
    $residencia = $datosPaciente['residencia'];
    $nombreTutor = $datosPaciente['nombreTutor'];
    $celularTutor = $datosPaciente['celularTutor'];

    $conUpdate = mysqli_query($link, "UPDATE `pacientes` SET `ci`='$ci',`apellidoPat`='$apellidoPat',`apellidoMat`='$apellidoMat',`nombres`='$nombres',`fechaNacimiento`='$fechaNacimiento',`celular`='$celular',`email`='$email',`direccion`='$direccion',`procedencia`='$procedencia',`residencia`='$residencia',`nombreTutor`='$nombreTutor',`celularTutor`='$celularTutor' WHERE `idPaciente` = '$idPaciente'") or die(mysqli_error($link));
    if ($conUpdate) {
        echo "OK";
    } else {
        echo "ERROR: " . mysqli_error($link);
    }
}

?>