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
    case "listaConsultorios":
        listaConsultorios();
        break;
    case 'formNuevoConsultorio':
        formNuevoConsultorio();
        break;
    case 'registrarConsultorio':
        registrarConsultorio();
        break;
    case 'formEditarConsultorio':
        formEditarConsultorio();
        break;
    case 'editarConsultorio':
        editarConsultorio();
        break;
    case 'modalListaUsuarios':
        modalListaUsuarios();
        break;
    case 'eliminarMedico':
        eliminarMedico();
        break;
    case 'asignarMedico':
        asignarMedico();
        break;
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function listaConsultorios(){
    global $link;
    global $input;

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Lista de consultorios</b>";
                echo "</div>";
                echo "<div class='card-body'>";
                    echo "<table class='table table-bordered'>";
                        echo "<thead>";
                            echo "<tr>";
                                echo "<th>Código</th>";
                                echo "<th>Descripción</th>";
                                echo "<th>Especialidad</th>";
                                echo "<th>Acciones</th>";
                            echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                            $sql = "SELECT `idConsultorio`, `codigo`, `descripcion`, `especialidad` FROM `consultorios` ORDER BY `codigo` ASC";
                            $result = mysqli_query($link, $sql);
                            while ($row = mysqli_fetch_array($result)) {
                                echo "<tr>";
                                    echo "<td>" . $row['codigo'] . "</td>";
                                    echo "<td>" . $row['descripcion'] . "</td>";
                                    echo "<td>" . $row['especialidad'] . "</td>";
                                    echo "<td class='text-center'>";
                                        echo "<div class='btn-group'>";
                                            echo "<button class='btn btn-xs btn-primary btnFormEditarConsultorio' id='" . $row['idConsultorio'] . "' title='Editar Consultorio'><i class='fas fa-edit'></i></button>";
                                            echo "<button class='btn btn-xs btn-warning btnModalListaUsuarios' id='" . $row['idConsultorio'] . "' title='Lista de Usuarios'><i class='fas fa-list'></i> Asignacion de Usuarios</button>";
                                        echo "</div>";
                                    echo "</td>";
                                echo "</tr>";
                            }

                        echo "</tbody>";
                    echo "</table>";


                echo "</div>";
                echo "<div class='card-footer text-right py-2 text-center'>";
                    echo "<button class='btn btn-primary' id='btnFormNuevoConsultorio'><i class='fas fa-plus'></i> Nuevo consultorio</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function formNuevoConsultorio(){
    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Nuevo Consultorio</b>";
                echo "</div>";
                echo "<div class='card-body row'>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='codigo' class='form-label'>Código</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-barcode'></i></span>";
                            echo "<input type='text' id='codigo' name='codigo' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-12 mb-2'>";
                        echo "<label for='descripcion' class='form-label'>Descripción</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-hospital'></i></span>";
                            echo "<input type='text' id='descripcion' name='descripcion' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='especialidad' class='form-label'>Especialidad</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-stethoscope'></i></span>";
                            echo "<select id='especialidad' name='especialidad' class='form-control'>";
                                echo "<option value=''>Seleccione una especialidad</option>";
                                echo "<option value='RAYOX X'>RAYOX X</option>";
                                echo "<option value='ODONTOLOGIA'>ODONTOLOGIA</option>";
                            echo "</select>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
                echo "<div class='card-footer text-center py-2'>";
                    echo "<button class='btn btn-primary' id='btnRegistrarConsultorio'><i class='fas fa-save'></i> Registrar Consultorio</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function registrarConsultorio(){
    global $link;
    global $input;

    $datosConsultorio = $input['datosConsultorio'];
    $codigo = $datosConsultorio['codigo'];
    $descripcion = $datosConsultorio['descripcion'];
    $especialidad = $datosConsultorio['especialidad'];

    $conRegistroConsultorio = mysqli_query($link, "INSERT INTO `consultorios`(`codigo`, `descripcion`, `especialidad`) 
                                                    VALUES ('$codigo','$descripcion','$especialidad')") or die(mysqli_error($link));
    if ($conRegistroConsultorio) {
        echo "OK";
    } else {
        echo "ERROR: " . mysqli_error($link);
    }
}

function formEditarConsultorio(){
    global $link;
    global $input;

    $idConsultorio = $input['idConsultorio'];

    $conConsultorio = mysqli_query($link, "SELECT `idConsultorio`, `codigo`, `descripcion`, `especialidad` 
                                            FROM `consultorios` WHERE `idConsultorio` = '$idConsultorio'") or die(mysqli_error($link));
    if(mysqli_num_rows($conConsultorio) > 0){
        $rowConsultorio = mysqli_fetch_array($conConsultorio);
        $codigo = $rowConsultorio['codigo'];
        $descripcion = $rowConsultorio['descripcion'];
        $especialidad = $rowConsultorio['especialidad'];
    }

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Editar Consultorio</b>";
                echo "</div>";
                echo "<div class='card-body row'>";
                    
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='codigo' class='form-label'>Código</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-barcode'></i></span>";
                            echo "<input type='text' id='codigo' name='codigo' class='form-control text-uppercase' value='$codigo'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-12 mb-2'>";
                        echo "<label for='descripcion' class='form-label'>Descripción</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-hospital'></i></span>";
                            echo "<input type='text' id='descripcion' name='descripcion' class='form-control text-uppercase' value='$descripcion'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='especialidad' class='form-label'>Especialidad</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-stethoscope'></i></span>";
                            echo "<select id='especialidad' name='especialidad' class='form-control'>";
                                echo "<option value=''>Seleccione una especialidad</option>";
                                $selectedRayos = ($especialidad == 'RAYOX X') ? 'selected' : '';
                                $selectedOdonto = ($especialidad == 'ODONTOLOGIA') ? 'selected' : '';
                                echo "<option value='RAYOX X' $selectedRayos>RAYOX X</option>";
                                echo "<option value='ODONTOLOGIA' $selectedOdonto>ODONTOLOGIA</option>";
                            echo "</select>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
                echo "<div class='card-footer text-center py-2'>";
                    echo "<button class='btn btn-primary' id='btnEditarConsultorio'><i class='fas fa-save'></i> Guardar Cambios</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function editarConsultorio(){
    global $link;
    global $input;

    $datosConsultorio = $input['datosConsultorio'];
    $idConsultorio = $datosConsultorio['idConsultorio'];
    $codigo = $datosConsultorio['codigo'];
    $descripcion = $datosConsultorio['descripcion'];
    $especialidad = $datosConsultorio['especialidad'];

    $conUpdate = mysqli_query($link, "UPDATE `consultorios` SET `codigo`='$codigo',`descripcion`='$descripcion',`especialidad`='$especialidad' WHERE `idConsultorio` = '$idConsultorio'") or die(mysqli_error($link));
    if ($conUpdate) {
        echo "OK";
    } else {
        echo "ERROR: " . mysqli_error($link);
    }
}

function modalListaUsuarios(){
    global $link;
    global $input;

    $idConsultorio = $input['idConsultorio'];

    $jsonListaMedicos = null;
    $conListaMedicos = mysqli_query($link, "SELECT `listaMedicos` FROM `consultorios` WHERE `idConsultorio` = '$idConsultorio'")or die(mysqli_error($link));
    if(mysqli_num_rows($conListaMedicos) > 0){
        $rowListaMedicos = mysqli_fetch_array($conListaMedicos);
        $listaMedicos = $rowListaMedicos['listaMedicos'];
        if(!empty($listaMedicos)){
            $jsonListaMedicos = json_decode($listaMedicos, true);
        }
    }

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0' id=''>Lista de Médicos Asignados al Consultorio</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body table-responsive'>";

        echo "<table class='table table-bordered'>";
            echo "<thead>";
                echo "<tr>";
                    echo "<th>CI</th>";
                    echo "<th>Nombre</th>";
                    echo "<th>Primer Apellido</th>";
                    echo "<th>Segundo Apellido</th>";
                    echo "<th>Celular</th>";
                    echo "<th>Email</th>";
                    echo "<th>Usuario</th>";
                    echo "<th>Acciones</th>";
                echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
                if($jsonListaMedicos !== null && isset($jsonListaMedicos['medicos']) && is_array($jsonListaMedicos['medicos']) && count($jsonListaMedicos['medicos']) > 0){
                    foreach($jsonListaMedicos['medicos'] as $medico){
                        $cedula = isset($medico['ciUs']) ? $medico['ciUs'] : '';
                        $nombre = isset($medico['nombreUs']) ? $medico['nombreUs'] : '';
                        $primerApellido = isset($medico['primerApUs']) ? $medico['primerApUs'] : '';
                        $segundoApellido = isset($medico['segundoApUs']) ? $medico['segundoApUs'] : '';
                        $celular = isset($medico['celularUs']) ? $medico['celularUs'] : '';
                        $email = isset($medico['emailUs']) ? $medico['emailUs'] : '';
                        $usuario = isset($medico['usuarioUs']) ? $medico['usuarioUs'] : '';
                        $idUsuario = isset($medico['idUsuario']) ? $medico['idUsuario'] : '';
                        
                        echo "<tr>";
                            echo "<td>" . htmlspecialchars($cedula) . "</td>";
                            echo "<td>" . htmlspecialchars($nombre) . "</td>";
                            echo "<td>" . htmlspecialchars($primerApellido) . "</td>";
                            echo "<td>" . htmlspecialchars($segundoApellido) . "</td>";
                            echo "<td>" . htmlspecialchars($celular) . "</td>";
                            echo "<td>" . htmlspecialchars($email) . "</td>";
                            echo "<td>" . htmlspecialchars($usuario) . "</td>";
                            echo "<td class='text-center'>";
                                echo "<button class='btn btn-xs btn-danger btnEliminarMedico' id='$idUsuario' data-consultorio='$idConsultorio' title='Eliminar Médico'><i class='fas fa-trash'></i></button>";
                            echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr>";
                        echo "<td colspan='8' class='text-center'>No hay médicos asignados a este consultorio</td>";
                    echo "</tr>";
                }
            echo "</tbody>";
        echo "</table>";

        echo "<div class='row'>";
            echo "<div class='col-md-8'>";
                echo "<label for='codigo' class='form-label'>Seleccionar Usuario</label>";
                echo "<select id='idUsuario' name='idUsuario' class='form-select'>";
                    echo "<option value=''>Seleccione un usuario</option>";
                    $conListaUsuarios = mysqli_query($link, "SELECT `idUsuario`, `nombreUs`, `primerApUs`, `segundoApUs`, `fechaNacUs`, `celularUs`, `ciUs`, `emailUs`, `usuarioUs`, `passwordUs`, `perfilUs`, `estadoUs`, `cambioPass` FROM `usuarios` ORDER BY `primerApUs`, `segundoApUs`,`nombreUs` ASC") or die(mysqli_error($link));
                    while($rowListaUsuarios = mysqli_fetch_array($conListaUsuarios)){
                        echo "<option value='" . $rowListaUsuarios['idUsuario'] . "'>" . $rowListaUsuarios['nombreUs'] . " " . $rowListaUsuarios['primerApUs'] . " " . $rowListaUsuarios['segundoApUs'] . "</option>";
                    }
                echo "</select>";
            echo "</div>";
            echo "<div class='col-md-4 d-flex justify-content-center align-items-center'>";
                echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnAsignarMedico' data-consultorio='$idConsultorio'><i class='fas fa-plus'></i> Asignar Usuario</button>";
            echo "</div>";
        echo "</div>";

    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";

    
}

function eliminarMedico(){
    global $link;
    global $input;

    $idUsuario = $input['idUsuario'];
    $idConsultorio = $input['idConsultorio'];

    // Obtener la lista actual de médicos
    $conListaMedicos = mysqli_query($link, "SELECT `listaMedicos` FROM `consultorios` WHERE `idConsultorio` = '$idConsultorio'") or die(mysqli_error($link));
    
    if(mysqli_num_rows($conListaMedicos) > 0){
        $rowListaMedicos = mysqli_fetch_array($conListaMedicos);
        $listaMedicos = $rowListaMedicos['listaMedicos'];
        
        $jsonListaMedicos = null;
        if(!empty($listaMedicos)){
            $jsonListaMedicos = json_decode($listaMedicos, true);
        }
        
        // Si hay médicos en la lista, eliminar el médico especificado
        if($jsonListaMedicos !== null && isset($jsonListaMedicos['medicos']) && is_array($jsonListaMedicos['medicos'])){
            $medicosFiltrados = array_filter($jsonListaMedicos['medicos'], function($medico) use ($idUsuario) {
                return isset($medico['idUsuario']) && $medico['idUsuario'] != $idUsuario;
            });
            
            $jsonListaMedicos['medicos'] = array_values($medicosFiltrados);
            
            // Si no quedan médicos, inicializar estructura vacía
            if(count($jsonListaMedicos['medicos']) == 0){
                $jsonListaMedicos = ['medicos' => []];
            }
        } else {
            $jsonListaMedicos = ['medicos' => []];
        }
        
        $listaMedicosActualizada = json_encode($jsonListaMedicos);
        
        // Actualizar en la base de datos
        $conUpdate = mysqli_query($link, "UPDATE `consultorios` SET `listaMedicos` = '$listaMedicosActualizada' WHERE `idConsultorio` = '$idConsultorio'") or die(mysqli_error($link));
        
        if ($conUpdate) {
            echo "OK";
        } else {
            echo "ERROR: " . mysqli_error($link);
        }
    } else {
        echo "ERROR: No se encontró el consultorio";
    }
}

function asignarMedico(){
    global $link;
    global $input;

    $idUsuario = $input['idUsuario'];
    $idConsultorio = $input['idConsultorio'];

    // Validar que se haya seleccionado un usuario
    if(empty($idUsuario)){
        echo "ERROR: Debe seleccionar un usuario";
        return;
    }

    // Obtener los datos del usuario
    $conUsuario = mysqli_query($link, "SELECT `idUsuario`, `nombreUs`, `primerApUs`, `segundoApUs`, `fechaNacUs`, `celularUs`, `ciUs`, `emailUs`, `usuarioUs`, `passwordUs`, `perfilUs`, `estadoUs`, `cambioPass` FROM `usuarios` WHERE `idUsuario` = '$idUsuario'") or die(mysqli_error($link));
    
    if(mysqli_num_rows($conUsuario) == 0){
        echo "ERROR: No se encontró el usuario";
        return;
    }
    
    $rowUsuario = mysqli_fetch_array($conUsuario);
    
    // Verificar si el usuario ya está asignado
    $conListaMedicos = mysqli_query($link, "SELECT `listaMedicos` FROM `consultorios` WHERE `idConsultorio` = '$idConsultorio'") or die(mysqli_error($link));
    
    $jsonListaMedicos = ['medicos' => []];
    
    if(mysqli_num_rows($conListaMedicos) > 0){
        $rowListaMedicos = mysqli_fetch_array($conListaMedicos);
        $listaMedicos = $rowListaMedicos['listaMedicos'];
        
        if(!empty($listaMedicos)){
            $jsonListaMedicos = json_decode($listaMedicos, true);
            if($jsonListaMedicos === null || !isset($jsonListaMedicos['medicos']) || !is_array($jsonListaMedicos['medicos'])){
                $jsonListaMedicos = ['medicos' => []];
            }
        }
    }
    
    // Verificar si el médico ya está asignado
    $yaAsignado = false;
    if(isset($jsonListaMedicos['medicos']) && is_array($jsonListaMedicos['medicos'])){
        foreach($jsonListaMedicos['medicos'] as $medico){
            if(isset($medico['idUsuario']) && $medico['idUsuario'] == $idUsuario){
                $yaAsignado = true;
                break;
            }
        }
    }
    
    if($yaAsignado){
        echo "ERROR: El usuario ya está asignado a este consultorio";
        return;
    }
    
    // Agregar el nuevo médico a la lista
    $nuevoMedico = [
        'idUsuario' => $rowUsuario['idUsuario'],
        'ciUs' => $rowUsuario['ciUs'],
        'nombreUs' => $rowUsuario['nombreUs'],
        'primerApUs' => $rowUsuario['primerApUs'],
        'segundoApUs' => $rowUsuario['segundoApUs'],
        'celularUs' => $rowUsuario['celularUs'],
        'emailUs' => $rowUsuario['emailUs'],
        'usuarioUs' => $rowUsuario['usuarioUs']
    ];
    
    $jsonListaMedicos['medicos'][] = $nuevoMedico;
    $listaMedicosActualizada = json_encode($jsonListaMedicos);
    
    // Actualizar en la base de datos
    $conUpdate = mysqli_query($link, "UPDATE `consultorios` SET `listaMedicos` = '$listaMedicosActualizada' WHERE `idConsultorio` = '$idConsultorio'") or die(mysqli_error($link));
    
    if ($conUpdate) {
        echo "OK";
    } else {
        echo "ERROR: " . mysqli_error($link);
    }
}

?>