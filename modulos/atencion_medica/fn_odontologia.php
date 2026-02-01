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
    case "verPacientesConsultorio":
        verPacientesConsultorio();
        break;
    case 'modalSeleccionarPaciente':
        modalSeleccionarPaciente();
        break;
    case 'verAtencionClinica':
        verAtencionClinica();
        break;

    case 'crearAtencionClinica':
        crearAtencionClinica();
        break;
    case 'formularioExamenGeneral':
        formularioExamenGeneral();
        break;
    case 'listaRegistroCuaadernoOdontologia':
        listaRegistroCuaadernoOdontologia();
        break;
    case 'guardarExamenGeneral':
        guardarExamenGeneral();
        break;
    case 'imprimirRegistroCuaderno':
        imprimirRegistroCuaderno();
        break;
    case 'formularioRegistroTratamientos':
        formularioRegistroTratamientos();
        break;
    case 'guardarRegistroTratamientos':
        guardarRegistroTratamientos();
        break;
    
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function listaConsultorios(){
    global $link;
    global $input;

    $idUsuario = $_SESSION['idUsuario_clinicloud'];

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='d-flex flex-wrap gap-2'>";
                $sql = "SELECT `idConsultorio`, `codigo`, `descripcion`, `especialidad`, `listaMedicos` FROM `consultorios` WHERE `especialidad` = 'ODONTOLOGIA' ORDER BY `descripcion` ASC";
                $result = mysqli_query($link, $sql);
                $contadorConsultorios = 0;
                
                while ($row = mysqli_fetch_array($result)) {
                    // Decodificar el JSON de listaMedicos
                    $listaMedicos = json_decode($row['listaMedicos'], true);
                    
                    // Verificar si el usuario está en la lista de médicos
                    $usuarioEnLista = false;
                    if ($listaMedicos && isset($listaMedicos['medicos']) && is_array($listaMedicos['medicos'])) {
                        foreach ($listaMedicos['medicos'] as $medico) {
                            if (isset($medico['idUsuario']) && $medico['idUsuario'] == $idUsuario) {
                                $usuarioEnLista = true;
                                break;
                            }
                        }
                    }
                    
                    // Solo mostrar el botón si el usuario está en la lista de médicos
                    if ($usuarioEnLista) {
                        $contadorConsultorios++;
                        echo "<button class='btn btn-outline-primary btnVerPacientesConsultorio' id='" . $row['idConsultorio'] . "' title='Ingresar al consultorio'>";
                            echo "<strong>" . htmlspecialchars($row['codigo']) . "</strong> - " . htmlspecialchars($row['descripcion']);
                        echo "</button>";
                    }
                }
                
                // Mostrar mensaje si no tiene consultorios asignados
                if ($contadorConsultorios == 0) {
                    echo "<div class='alert alert-danger w-100' role='alert'>";
                        echo "<i class='fas fa-danger-circle me-2'></i>";
                        echo "No tiene consultorios asignados para la especialidad seleccionada.";
                    echo "</div>";
                }
            echo "</div>";
        echo "</div>";
    echo "</div>";
}


function verPacientesConsultorio(){

    date_default_timezone_set('America/La_Paz');

    global $link;
    global $input;

    $idConsultorio = $input['idConsultorio'];

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card'>";
                echo "<div class='card-header d-flex justify-content-between align-items-center'>";
                    echo "<b>Lista de Atenciones</b>";
                    echo "<div class='d-flex align-items-center gap-2'>";
                        echo "<label for='fechaConsulta' class='mb-0'>Fecha:</label>";
                        echo "<input type='date' id='fechaConsulta' name='fechaConsulta' class='form-control form-control-sm' style='width: auto;' value='" . date('Y-m-d') . "'>";
                    echo "</div>";
                echo "</div>";
                echo "<div class='card-body'>";
                
                    echo "<div class='row'>";
                        echo "<div class='col-md-12 text-center'>";
                            echo "<button class='btn btn-primary' id='btnSeleccionarPaciente'><i class='fas fa-plus'></i> Nueva Atencion</button>";
                        echo "</div>";
                    echo "</div>";
                
                    echo "<div class='table-responsive mt-2'>";
                        echo "<table class='table table-bordered table-hover'>";
                            echo "<thead class='table-light'>";
                                echo "<tr>";
                                    echo "<th>ID Atención</th>";
                                    echo "<th>Estado</th>";
                                    //echo "<th>Tipo Atención</th>";
                                    echo "<th>C.I.</th>";
                                    echo "<th>Paciente</th>";
                                    echo "<th>Consultorio</th>";
                                    echo "<th>Médico</th>";
                                    echo "<th>Fecha Atención</th>";
                                    echo "<th>Acciones</th>";
                                echo "</tr>";
                            echo "</thead>";
                            echo "<tbody>";
                                // Consulta con JOINs para obtener toda la información
                                $fechaConsulta = isset($input['fechaConsulta']) ? mysqli_real_escape_string($link, $input['fechaConsulta']) : date('Y-m-d');
                                
                                $sqlAtenciones = "SELECT 
                                    ac.`idAtencion`,
                                    ac.`fechaAtencion`,
                                    ac.`fechaRegistro`,
                                    ac.`estadoAtencion`,
                                    p.`idPaciente`,
                                    p.`ci`,
                                    p.`apellidoPat`,
                                    p.`apellidoMat`,
                                    p.`nombres`,
                                    c.`idConsultorio`,
                                    c.`codigo` as codigoConsultorio,
                                    c.`descripcion` as descripcionConsultorio,
                                    u.`idUsuario`,
                                    u.`nombreUs`,
                                    u.`primerApUs`,
                                    u.`segundoApUs`,
                                    u.`usuarioUs`
                                FROM `atencion_clinica` ac
                                INNER JOIN `pacientes` p ON ac.`idPaciente` = p.`idPaciente`
                                INNER JOIN `consultorios` c ON ac.`idConsultorio` = c.`idConsultorio`
                                INNER JOIN `usuarios` u ON ac.`idUsuario` = u.`idUsuario`
                                WHERE ac.`idConsultorio` = '" . mysqli_real_escape_string($link, $idConsultorio) . "' 
                                AND ac.`especialidad` = 'ODONTOLOGIA'
                                AND DATE(ac.`fechaAtencion`) = '" . $fechaConsulta . "'
                                ORDER BY ac.`fechaAtencion` ASC";
                                
                                $resultAtenciones = mysqli_query($link, $sqlAtenciones);
                                
                                if ($resultAtenciones && mysqli_num_rows($resultAtenciones) > 0) {
                                    while ($rowAtencion = mysqli_fetch_array($resultAtenciones)) {
                                        $nombreCompletoPaciente = trim($rowAtencion['nombres'] . ' ' . $rowAtencion['apellidoPat'] . ' ' . $rowAtencion['apellidoMat']);
                                        $nombreCompletoMedico = trim($rowAtencion['nombreUs'] . ' ' . $rowAtencion['primerApUs'] . ' ' . $rowAtencion['segundoApUs']);
                                        
                                        // Formatear fecha
                                        $fechaAtencion = new DateTime($rowAtencion['fechaAtencion']);
                                        $fechaFormateada = $fechaAtencion->format('d/m/Y H:i');
                                        
                                        // Badge para estado
                                        $badgeEstado = '';
                                        switch(strtoupper($rowAtencion['estadoAtencion'])) {
                                            case 'ATENDIDO':
                                                $badgeEstado = '<span class="badge bg-success">ATENDIDO</span>';
                                                break;
                                            case 'PENDIENTE':
                                                $badgeEstado = '<span class="badge bg-warning">PENDIENTE</span>';
                                                break;
                                            case 'CANCELADO':
                                                $badgeEstado = '<span class="badge bg-secondary">CANCELADO</span>';
                                                break;
                                            default:
                                                $badgeEstado = '<span class="badge bg-secondary">' . htmlspecialchars($rowAtencion['estadoAtencion']) . '</span>';
                                        }

                                        // $badgeTipoAtencion = '';
                                        // switch(strtoupper($rowAtencion['tipoAtencion'])) {
                                        //     case 'EXAMEN GENERAL':
                                        //         $badgeTipoAtencion = '<span class="badge bg-primary">EXAMEN GENERAL</span>';
                                        //         break;
                                        //     case 'REGISTRO CLINICO':
                                        //         $badgeTipoAtencion = '<span class="badge bg-danger">EXAMEN BUCODENTAL</span>';
                                        //         break;
                                        // }
                                        
                                        echo "<tr>";
                                            echo "<td>" . htmlspecialchars($rowAtencion['idAtencion']) . "</td>";
                                            echo "<td>" . $badgeEstado . "</td>";
                                            //echo "<td>" . $badgeTipoAtencion . "</td>";
                                            echo "<td>" . htmlspecialchars($rowAtencion['ci']) . "</td>";
                                            echo "<td>" . htmlspecialchars($nombreCompletoPaciente) . "</td>";
                                            echo "<td><small>" . htmlspecialchars($rowAtencion['codigoConsultorio']) . "</small><br><strong>" . htmlspecialchars($rowAtencion['descripcionConsultorio']) . "</strong></td>";
                                            echo "<td>" . htmlspecialchars($rowAtencion['usuarioUs']) . "</td>";
                                            echo "<td>" . $fechaFormateada . "</td>";
                                            
                                            echo "<td class='text-center'>";
                                                // echo "<button class='btn btn-xs btn-primary btnModalImprimirAtencion' id='" . $rowAtencion['idAtencion'] . "' title='Ver Atencion'><i class='fas fa-print'></i></button> ";
                                                // echo "<button class='btn btn-xs btn-info btnEditarAtencion' id='" . $rowAtencion['idAtencion'] . "' title='Editar Atencion'><i class='fas fa-edit'></i></button>";

                                                // echo "<button class='btn btn-xs mb-2 btn-primary btnModalImprimirAtencion' id='" . $rowAtencion['idAtencion'] . "' title='Ver Atencion'>Examen General</button> ";
                                                // echo "<button class='btn btn-xs mb-2 btn-danger btnModalImprimirAtencion' id='" . $rowAtencion['idAtencion'] . "' title='Ver Atencion'>Registro Clinico</button> ";
                                                // echo "<button class='btn btn-xs mb-2 btn-warning btnModalImprimirAtencion' id='" . $rowAtencion['idAtencion'] . "' title='Ver Atencion'>Rayox X</button> ";
                                                
                                                echo "<button class='btn btn-xs mb-2 btn-primary btnVerAtencionClinica' id='" . $rowAtencion['idAtencion'] . "' title='Ver Atencion'><i class='fas fa-eye'></i> Ver Atención</button> ";
                                            echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr>";
                                        echo "<td colspan='8' class='text-center text-muted py-3'>";
                                            echo "<i class='fas fa-info-circle me-2'></i>No hay atenciones registradas para esta fecha.";
                                        echo "</td>";
                                    echo "</tr>";
                                }
                            echo "</tbody>";
                        echo "</table>";
                    echo "</div>";


                echo "</div>";
            echo "</div>";

            echo "</div>";
        echo "</div>";
    echo "</div>";
}


function modalSeleccionarPaciente(){
    global $link;
    global $input;


    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0' id=''>Lista de pacientes</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body table-responsive'>";
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
                $conPacientes = mysqli_query($link, "SELECT `idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `email`, `direccion`, `procedencia`, `residencia`, `nombreTutor`, `celularTutor` FROM `pacientes` ORDER BY `apellidoPat`,`apellidoMat`,`nombres` ASC")or die(mysqli_error($link));
                if(mysqli_num_rows($conPacientes) > 0){
                    while($rowPaciente = mysqli_fetch_array($conPacientes)){
                        echo "<tr>";
                            echo "<td>" . $rowPaciente['ci'] . "</td>";
                            echo "<td>" . $rowPaciente['apellidoPat'] . "</td>";
                            echo "<td>" . $rowPaciente['apellidoMat'] . "</td>";
                            echo "<td>" . $rowPaciente['nombres'] . "</td>";
                            echo "<td>" . $rowPaciente['fechaNacimiento'] . "</td>";
                            echo "<td>" . $rowPaciente['celular'] . "</td>";
                            echo "<td class='text-center'>";
                                // echo "<button class='btn btn-primary btn-sm btnFormExamenGeneral' id='" . $rowPaciente['idPaciente'] . "' title='Examen General'><i class='fas fa-stethoscope'></i> Examen General</button>";
                                // echo "<button class='btn btn-danger btn-sm btnFormRegistroTratamientos' id='" . $rowPaciente['idPaciente'] . "' title='Registro de Tratamientos'><i class='fas fa-tooth'></i> Registro de Tratamientos</button>";
                                echo "<button class='btn btn-primary btn-sm btnFormCrearAtencionClinica' id='" . $rowPaciente['idPaciente'] . "' title='Crear Atención Clínica'><i class='fas fa-plus'></i> Crear Atención Clínica</button>";
                            echo "</td>";
                        echo "</tr>";
                    }
                }
                else{
                    echo "No hay datos";
                }
            echo "</tbody>";
        echo "</table>";

    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";
}

function crearAtencionClinica(){

    date_default_timezone_set('America/La_Paz');
    global $link;
    global $input;

    $idConsultorio = $input['idConsultorio'];
    $idPaciente = $input['idPaciente'];

    $fechaAtencion = date('Y-m-d H:i:s');
    $fechaRegistro = date('Y-m-d H:i:s');
    $estadoAtencion = 'PENDIENTE';
    $especialidad = 'ODONTOLOGIA';
    $idUsuario = $_SESSION['idUsuario_clinicloud'];

    $conInsertAtencionClinica = mysqli_query($link, "INSERT INTO `atencion_clinica`(`idPaciente`, `idConsultorio`, `fechaAtencion`, `idUsuario`, `fechaRegistro`, `estadoAtencion`, `especialidad`) 
    VALUES ('$idPaciente','$idConsultorio','$fechaAtencion','$idUsuario','$fechaRegistro','$estadoAtencion','$especialidad')")or die(mysqli_error($link));

    if($conInsertAtencionClinica){
        echo json_encode(["estado" => "OK", "mensaje" => "Atención clínica creada correctamente"]);
    }
    else{
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al crear la atención clínica"]);
    }

}


function verAtencionClinica(){
    global $link;
    global $input;

    $idAtencion = $input['idAtencion'];

    $conAtencionClinica = mysqli_query($link, "SELECT `idAtencion`, `idPaciente`, `idConsultorio`, `fechaAtencion`, `idUsuario`, `fechaRegistro`, `estadoAtencion`, `especialidad` FROM `atencion_clinica` WHERE `idAtencion` = '$idAtencion'")or die(mysqli_error($link));
    if(mysqli_num_rows($conAtencionClinica) > 0){
        $rowAtencionClinica = mysqli_fetch_array($conAtencionClinica);
        $idAtencion = $rowAtencionClinica['idAtencion'];
        $idPaciente = $rowAtencionClinica['idPaciente'];
        $idConsultorio = $rowAtencionClinica['idConsultorio'];
        $fechaAtencion = $rowAtencionClinica['fechaAtencion'];
        $idUsuario = $rowAtencionClinica['idUsuario'];
        $fechaRegistro = $rowAtencionClinica['fechaRegistro'];
        $estadoAtencion = $rowAtencionClinica['estadoAtencion'];
        $especialidad = $rowAtencionClinica['especialidad'];
    }

    $conPacientes = mysqli_query($link, "SELECT `idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, 
    `fechaNacimiento`, `celular`, `email`, `direccion`, `procedencia`, `residencia`, `nombreTutor`, `celularTutor` 
    FROM `pacientes` WHERE `idPaciente` = '$idPaciente'")or die(mysqli_error($link));
    if(mysqli_num_rows($conPacientes) > 0){
        $rowPaciente = mysqli_fetch_array($conPacientes);
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


    //lista de registro del cuaderno de odontologia 

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card'>";
                echo "<div class='card-header d-flex justify-content-between align-items-center'>";
                    echo "<b>Atencion Clínica</b>";
                echo "</div>";
                echo "<div class='card-body row'>";

                    echo "<div class='col-md-6'>";
                        echo "<h5 class='card-title'>Paciente</h5>";
                        echo "<p>" . $ci . " - " . $nombres . " " . $apellidoPat . " " . $apellidoMat . "</p>";
                    echo "</div>";

                    echo "<div class='col-md-12 text-center'>";
                        echo "<button class='btn btn-primary btn-sm m-2' id='btnFormExamenGeneral'><i class='fas fa-print'></i> Examen General</button>";
                        //echo "<button class='btn btn-danger btn-sm m-2' id='btnFormExamenBucodental'><i class='fas fa-print'></i> Examen Bucodental</button>";
                        echo "<button class='btn btn-danger btn-sm m-2' id='btnFormRegistroTratamientos'><i class='fas fa-print'></i> Registro de Tratamientos</button>";
                        echo "<button class='btn btn-warning btn-sm m-2' id='btnFormRayoxX'><i class='fas fa-print'></i> Rayox X</button>";
                        echo "<button class='btn btn-fusion btn-sm m-2' id='btnListaRegistroCuaadernoOdontologia'><i class='fas fa-list'></i> Lista de Registro</button>";
                    echo "</div>";


                

                echo "</div>";
            echo "</div>";

            echo "</div>";
        echo "</div>";
    echo "</div>";
    
    echo "<div id='divCuadernoOdontologia'>";
    echo "</div>";



}


function listaRegistroCuaadernoOdontologia(){
    date_default_timezone_set('America/La_Paz');
    global $link;
    global $input;

    $idAtencion = isset($input['idAtencion']) ? mysqli_real_escape_string($link, $input['idAtencion']) : '';

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card'>";
                echo "<div class='card-header d-flex justify-content-between align-items-center'>";
                    echo "<b>Atencion Clínica</b>";
                echo "</div>";
                echo "<div class='card-body table-responsive'>";
                    if ($idAtencion == '') {
                        echo "<div class='alert alert-warning mb-0'>No se recibió el ID de atención para listar el registro.</div>";
                    } else {
                        $sql = "SELECT idCuaOdontologia, `tipoAtencion`, `fechaRegistro`, `idUsuario` 
                                FROM `cuaderno_odontologia` 
                                WHERE `idAtencion` = '$idAtencion'
                                ORDER BY `fechaRegistro` DESC";
                        $result = mysqli_query($link, $sql) or die(mysqli_error($link));

                        echo "<table class='table table-sm table-striped table-hover align-middle mb-0'>";
                            echo "<thead class='table-light'>";
                                echo "<tr>";
                                    echo "<th style='width:60px;'>#</th>";
                                    echo "<th>Tipo de atención</th>";
                                    echo "<th style='width:200px;'>Fecha registro</th>";
                                    echo "<th style='width:120px;'>Usuario</th>";
                                    echo "<th style='width:120px;'>Acciones</th>";
                                echo "</tr>";
                            echo "</thead>";
                            echo "<tbody>";

                                if ($result && mysqli_num_rows($result) > 0) {
                                    $n = 0;
                                    while ($row = mysqli_fetch_array($result)) {
                                        $n++;
                                        $tipoAtencion = htmlspecialchars($row['tipoAtencion'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $fechaRegistroRaw = $row['fechaRegistro'] ?? '';
                                        $fechaRegistro = $fechaRegistroRaw ? date('d/m/Y H:i', strtotime($fechaRegistroRaw)) : '';
                                        $idUsuario = htmlspecialchars($row['idUsuario'] ?? '', ENT_QUOTES, 'UTF-8');

                                        echo "<tr>";
                                            echo "<td>" . $n . "</td>";
                                            echo "<td>" . $tipoAtencion . "</td>";
                                            echo "<td>" . htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8') . "</td>";
                                            echo "<td class='text-muted'>" . $idUsuario . "</td>";
                                            echo "<td class='text-center'>";
                                                echo "<button class='btn btn-xs mb-2 btn-primary btnImprimirRegistroCuaderno' id='" . $row['idCuaOdontologia'] . "' title='Imprimir Registro'><i class='fas fa-print'></i> Imprimir</button>";
                                                echo "<button class='btn btn-xs mb-2 btn-warning btnModificarRegistroCuaderno' id='" . $row['idCuaOdontologia'] . "' title='Modificar Registro'><i class='fas fa-edit'></i> Modificar</button>";
                                                echo "<button class='btn btn-xs mb-2 btn-danger btnEliminarRegistroCuaderno' id='" . $row['idCuaOdontologia'] . "' title='Eliminar Registro'><i class='fas fa-trash'></i> Eliminar</button>";
                                            echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr>";
                                        echo "<td colspan='5' class='text-center text-muted'>Sin registros para esta atención.</td>";
                                    echo "</tr>";
                                }

                            echo "</tbody>";
                        echo "</table>";
                    }
                echo "</div>";
            echo "</div>";

            echo "</div>";
        echo "</div>";
    echo "</div>";

}



function formularioExamenGeneral(){
    global $link;
    global $input;

    $idAtencion = $input['idAtencion'];

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card'>";
                echo "<div class='card-header d-flex justify-content-between align-items-center'>";
                    echo "<b>Examen General</b>";
                echo "</div>";
                echo "<div class='card-body row'>";
                    
                    // Examen General
                    echo "<div class='col-md-12 mb-3'>";
                        echo "<h6 class='text-primary mb-3'><strong>2️⃣ Examen general</strong></h6>";
                        echo "<div class='row g-2'>";
                            
                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Intervenido quirúrgicamente</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='intervenido_quirurgicamente' id='intervenido_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='intervenido_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='intervenido_quirurgicamente' id='intervenido_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='intervenido_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Problemas cardíacos</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='problemas_cardiacos' id='cardiacos_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='cardiacos_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='problemas_cardiacos' id='cardiacos_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='cardiacos_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Diabético</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='diabetico' id='diabetico_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='diabetico_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='diabetico' id='diabetico_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='diabetico_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Alergia a medicamentos</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='alergia_medicamentos' id='alergia_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='alergia_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='alergia_medicamentos' id='alergia_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='alergia_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Cicatrización normal</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='cicatrizacion_normal' id='cicatrizacion_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='cicatrizacion_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='cicatrizacion_normal' id='cicatrizacion_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='cicatrizacion_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Problemas de coagulación</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='problemas_coagulacion' id='coagulacion_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='coagulacion_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='problemas_coagulacion' id='coagulacion_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='coagulacion_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>En tratamiento médico actualmente</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='tratamiento_medico' id='tratamiento_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='tratamiento_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='tratamiento_medico' id='tratamiento_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='tratamiento_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Toma medicamentos actualmente</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='toma_medicamentos' id='medicamentos_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='medicamentos_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='toma_medicamentos' id='medicamentos_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='medicamentos_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Embarazo</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='embarazo' id='embarazo_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='embarazo_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='embarazo' id='embarazo_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='embarazo_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small'>F.U.M (fecha)</label>";
                                echo "<input type='date' class='form-control form-control-sm' name='fum' value=''>";
                            echo "</div>";

                        echo "</div>";
                    echo "</div>";

                    // Examen Bucodental
                    echo "<div class='col-md-12 mb-3'>";
                        echo "<h6 class='text-primary mb-3'><strong>3️⃣ Examen bucodental</strong></h6>";
                        echo "<div class='row g-2'>";
                            
                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small'>Higiene dental</label>";
                                echo "<select class='form-select form-select-sm' name='higiene_dental'>";
                                    echo "<option value=''>Seleccione...</option>";
                                    echo "<option value='Buena'>Buena</option>";
                                    echo "<option value='Regular'>Regular</option>";
                                    echo "<option value='Mala'>Mala</option>";
                                echo "</select>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Usa cepillo dental</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='usa_cepillo' id='cepillo_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='cepillo_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='usa_cepillo' id='cepillo_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='cepillo_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small'>Frecuencia de cepillado</label>";
                                echo "<input type='text' class='form-control form-control-sm' name='frecuencia_cepillado' placeholder='Ej: 2 veces al día' value=''>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Usa hilo dental</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='usa_hilo_dental' id='hilo_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='hilo_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='usa_hilo_dental' id='hilo_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='hilo_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                        echo "</div>";
                    echo "</div>";

                    // Hábitos y costumbres
                    echo "<div class='col-md-12 mb-3'>";
                        echo "<h6 class='text-primary mb-3'><strong>4️⃣ Hábitos y costumbres</strong></h6>";
                        echo "<div class='row g-2'>";
                            
                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Respirador bucal</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='respirador_bucal' id='respirador_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='respirador_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='respirador_bucal' id='respirador_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='respirador_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Usa chupón</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='usa_chupon' id='chupon_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='chupon_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='usa_chupon' id='chupon_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='chupon_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Fuma</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='fuma' id='fuma_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='fuma_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='fuma' id='fuma_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='fuma_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Toma alcohol</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='toma_alcohol' id='alcohol_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='alcohol_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='toma_alcohol' id='alcohol_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='alcohol_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                            echo "<div class='col-md-6 col-lg-4'>";
                                echo "<label class='form-label small d-block'>Masca coca</label>";
                                echo "<div class='btn-group' role='group'>";
                                    echo "<input type='radio' class='btn-check' name='masca_coca' id='coca_si' value='Si'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='coca_si'>Sí</label>";
                                    echo "<input type='radio' class='btn-check' name='masca_coca' id='coca_no' value='No'>";
                                    echo "<label class='btn btn-outline-primary btn-sm' for='coca_no'>No</label>";
                                echo "</div>";
                            echo "</div>";

                        echo "</div>";
                    echo "</div>";

                echo "</div>";

                echo "<div class='card-footer text-right'>";
                    echo "<button class='btn btn-primary btn-sm' id='btnGuardarExamenGeneral'><i class='fas fa-save'></i> Guardar</button>";
                echo "</div>";


            echo "</div>";

        echo "</div>";
    echo "</div>";
}



function guardarExamenGeneral(){

    date_default_timezone_set('America/La_Paz');

    global $link;
    global $input;

    $idAtencion = $input['idAtencion'];
    $jsonDatosExamenGeneral = $input['jsonDatosExamenGeneral'];
    $fechaRegistro = date('Y-m-d H:i:s');
    $idUsuario = $_SESSION['idUsuario_clinicloud'];

    $registrarExamenGeneral = mysqli_query($link, "INSERT INTO `cuaderno_odontologia`(`idAtencion`, `tipoAtencion`, `jsonInfoCuaderno`, `fechaRegistro`, `idUsuario`) 
    VALUES ('$idAtencion','EXAMEN GENERAL','$jsonDatosExamenGeneral','$fechaRegistro','$idUsuario')")or die(mysqli_error($link));
    if($registrarExamenGeneral){
        echo json_encode(["estado" => "OK", "mensaje" => "Examen general guardado correctamente"]);
    }
    else{
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al guardar el examen general"]);
    }


}

function imprimirRegistroCuaderno(){

    global $link;
    global $input;

    $idAtencion = $input['idAtencion'];
    $idCuaOdontologia = $input['idCuaOdontologia'];

    $conCuadernoOdontologia = mysqli_query($link, "SELECT `idCuaOdontologia`, `idAtencion`, `tipoAtencion`, `jsonInfoCuaderno`, `fechaRegistro`, `idUsuario` 
    FROM `cuaderno_odontologia` WHERE `idCuaOdontologia` = '$idCuaOdontologia'")or die(mysqli_error($link));
    if(mysqli_num_rows($conCuadernoOdontologia) > 0){
        $rowCuadernoOdontologia = mysqli_fetch_array($conCuadernoOdontologia);
        
        $idCuaOdontologia = $rowCuadernoOdontologia['idCuaOdontologia'];
        $idAtencion = $rowCuadernoOdontologia['idAtencion'];
        $tipoAtencion = $rowCuadernoOdontologia['tipoAtencion'];
        $jsonInfoCuaderno = $rowCuadernoOdontologia['jsonInfoCuaderno'];
        $fechaRegistro = $rowCuadernoOdontologia['fechaRegistro'];
        $idUsuario = $rowCuadernoOdontologia['idUsuario'];
    }

    $rutaPDF = '';
    
    switch($tipoAtencion){
        case 'EXAMEN GENERAL':
            $resultado = generarPDFExamenGeneral($idCuaOdontologia, $idAtencion, $tipoAtencion, $jsonInfoCuaderno, $fechaRegistro, $idUsuario);
            if($resultado && isset($resultado['ruta'])){
                $rutaPDF = $resultado['ruta'];
            }
            break;
        case 'REGISTRO DE TRATAMIENTOS':
            $resultado = generarPDFRegistroTratamientos($idCuaOdontologia, $idAtencion, $tipoAtencion, $jsonInfoCuaderno, $fechaRegistro, $idUsuario);
            if($resultado && isset($resultado['ruta'])){
                $rutaPDF = $resultado['ruta'];
            }
            break;
    }

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0' id=''>Impresión de Registro</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
        if($rutaPDF != ''){
            echo "<iframe src='" . htmlspecialchars($rutaPDF) . "' style='width: 100%; height: 600px; border: 1px solid #ddd;' frameborder='0'></iframe>";
        } else {
            echo "<div class='alert alert-warning'>No se pudo generar el PDF.</div>";
        }
    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";


}

function obtenerRutaBaseProyecto(){
    // Obtener el protocolo y host
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Obtener la ruta física de la raíz del proyecto (2 niveles arriba desde este archivo)
    $rutaFisicaRaiz = dirname(__DIR__, 2);
    
    // Obtener la ruta del documento raíz del servidor web
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    
    // Normalizar las rutas (convertir \ a / en Windows)
    $rutaFisicaRaiz = str_replace('\\', '/', $rutaFisicaRaiz);
    $documentRoot = str_replace('\\', '/', $documentRoot);
    
    // Obtener la ruta relativa desde el document root hasta la raíz del proyecto
    if(strpos($rutaFisicaRaiz, $documentRoot) === 0){
        // La raíz del proyecto está dentro del document root
        $rutaRelativa = str_replace($documentRoot, '', $rutaFisicaRaiz);
        $rutaRelativa = trim($rutaRelativa, '/');
        
        if($rutaRelativa != ''){
            $rutaCompleta = $protocolo . $host . '/' . $rutaRelativa;
        } else {
            // Si está en la raíz del servidor
            $rutaCompleta = $protocolo . $host;
        }
    } else {
        // Si no está dentro del document root, usar SCRIPT_NAME como fallback
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $directorioScript = dirname($scriptPath);
        $partes = array_filter(explode('/', $directorioScript));
        $partes = array_values($partes);
        
        if(count($partes) > 0){
            $nombreProyecto = $partes[0];
            $rutaCompleta = $protocolo . $host . '/' . $nombreProyecto;
        } else {
            $rutaCompleta = $protocolo . $host;
        }
    }
    
    return $rutaCompleta;
}

function generarPDFExamenGeneral($idCuaOdontologia, $idAtencion, $tipoAtencion, $jsonInfoCuaderno, $fechaRegistro, $idUsuario){
    
    global $link;
    
    // Obtener información de la atención
    $conAtencionClinica = mysqli_query($link, "SELECT `idAtencion`, `idPaciente`, `idConsultorio`, `fechaAtencion`, `idUsuario` 
        FROM `atencion_clinica` WHERE `idAtencion` = '$idAtencion'") or die(mysqli_error($link));
    
    if(mysqli_num_rows($conAtencionClinica) > 0){
        $rowAtencionClinica = mysqli_fetch_array($conAtencionClinica);
        $idPaciente = $rowAtencionClinica['idPaciente'];
        $fechaAtencion = $rowAtencionClinica['fechaAtencion'];
    } else {
        echo json_encode(["estado" => "ERROR", "mensaje" => "No se encontró la atención clínica"]);
        return;
    }
    
    // Obtener información del paciente
    $conPacientes = mysqli_query($link, "SELECT `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular` 
        FROM `pacientes` WHERE `idPaciente` = '$idPaciente'") or die(mysqli_error($link));
    
    if(mysqli_num_rows($conPacientes) > 0){
        $rowPaciente = mysqli_fetch_array($conPacientes);
        $ci = $rowPaciente['ci'];
        $apellidoPat = $rowPaciente['apellidoPat'];
        $apellidoMat = $rowPaciente['apellidoMat'];
        $nombres = $rowPaciente['nombres'];
        $fechaNacimiento = $rowPaciente['fechaNacimiento'];
        $celular = $rowPaciente['celular'];
        $nombreCompleto = trim($nombres . ' ' . $apellidoPat . ' ' . $apellidoMat);
    } else {
        echo json_encode(["estado" => "ERROR", "mensaje" => "No se encontró el paciente"]);
        return;
    }
    
    // Obtener información del usuario/médico
    $conUsuario = mysqli_query($link, "SELECT `nombreUs`, `primerApUs`, `segundoApUs` 
        FROM `usuarios` WHERE `idUsuario` = '$idUsuario'") or die(mysqli_error($link));
    
    $nombreMedico = '';
    if(mysqli_num_rows($conUsuario) > 0){
        $rowUsuario = mysqli_fetch_array($conUsuario);
        $nombreMedico = trim($rowUsuario['nombreUs'] . ' ' . $rowUsuario['primerApUs'] . ' ' . $rowUsuario['segundoApUs']);
    }
    
    // Decodificar JSON del examen general
    $datosExamen = json_decode($jsonInfoCuaderno, true);
    
    // Calcular edad
    $edad = '';
    if($fechaNacimiento){
        $fechaNac = new DateTime($fechaNacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fechaNac)->y;
    }
    
    // Formatear fechas
    $fechaAtencionFormateada = $fechaAtencion ? date('d/m/Y H:i', strtotime($fechaAtencion)) : '';
    $fechaRegistroFormateada = $fechaRegistro ? date('d/m/Y H:i', strtotime($fechaRegistro)) : '';
    
    // Función auxiliar para mostrar Si/No
    function mostrarSiNo($valor) {
        if(empty($valor) || $valor == '') return '-';
        return $valor == 'Si' ? '✓ Sí' : '✗ No';
    }
    
    // Procesar todos los valores del examen
    $intervenido_quirurgicamente = mostrarSiNo($datosExamen['intervenido_quirurgicamente'] ?? '');
    $problemas_cardiacos = mostrarSiNo($datosExamen['problemas_cardiacos'] ?? '');
    $diabetico = mostrarSiNo($datosExamen['diabetico'] ?? '');
    $alergia_medicamentos = mostrarSiNo($datosExamen['alergia_medicamentos'] ?? '');
    $cicatrizacion_normal = mostrarSiNo($datosExamen['cicatrizacion_normal'] ?? '');
    $problemas_coagulacion = (isset($datosExamen['problemas_coagulacion']) && $datosExamen['problemas_coagulacion'] != '') ? mostrarSiNo($datosExamen['problemas_coagulacion']) : '-';
    $tratamiento_medico = (isset($datosExamen['tratamiento_medico']) && $datosExamen['tratamiento_medico'] != '') ? mostrarSiNo($datosExamen['tratamiento_medico']) : '-';
    $toma_medicamentos = (isset($datosExamen['toma_medicamentos']) && $datosExamen['toma_medicamentos'] != '') ? mostrarSiNo($datosExamen['toma_medicamentos']) : '-';
    $embarazo = mostrarSiNo($datosExamen['embarazo'] ?? '');
    $fum = (isset($datosExamen['fum']) && $datosExamen['fum'] != '') ? htmlspecialchars($datosExamen['fum']) : '-';
    
    $higiene_dental = (isset($datosExamen['higiene_dental']) && $datosExamen['higiene_dental'] != '') ? htmlspecialchars($datosExamen['higiene_dental']) : '-';
    $usa_cepillo = (isset($datosExamen['usa_cepillo']) && $datosExamen['usa_cepillo'] != '') ? mostrarSiNo($datosExamen['usa_cepillo']) : '-';
    $frecuencia_cepillado = (isset($datosExamen['frecuencia_cepillado']) && $datosExamen['frecuencia_cepillado'] != '') ? htmlspecialchars($datosExamen['frecuencia_cepillado']) : '-';
    $usa_hilo_dental = mostrarSiNo($datosExamen['usa_hilo_dental'] ?? '');
    
    $respirador_bucal = mostrarSiNo($datosExamen['respirador_bucal'] ?? '');
    $usa_chupon = mostrarSiNo($datosExamen['usa_chupon'] ?? '');
    $fuma = mostrarSiNo($datosExamen['fuma'] ?? '');
    $toma_alcohol = mostrarSiNo($datosExamen['toma_alcohol'] ?? '');
    $masca_coca = (isset($datosExamen['masca_coca']) && $datosExamen['masca_coca'] != '') ? mostrarSiNo($datosExamen['masca_coca']) : '-';
    
    // Generar HTML compacto
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 9pt;
                margin: 0;
                padding: 10px;
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #000;
                padding-bottom: 5px;
                margin-bottom: 10px;
            }
            .header h1 {
                margin: 0;
                font-size: 14pt;
                font-weight: bold;
            }
            .header h2 {
                margin: 5px 0;
                font-size: 11pt;
            }
            .info-paciente {
                margin-bottom: 10px;
                font-size: 8pt;
            }
            .info-paciente table {
                width: 100%;
                border-collapse: collapse;
            }
            .info-paciente td {
                padding: 2px 5px;
                border: 1px solid #ccc;
            }
            .info-paciente td.label {
                background-color: #f0f0f0;
                font-weight: bold;
                width: 25%;
            }
            .seccion {
                margin-bottom: 8px;
                page-break-inside: avoid;
            }
            .seccion-titulo {
                background-color: #333;
                color: white;
                padding: 3px 5px;
                font-weight: bold;
                font-size: 9pt;
                margin-bottom: 5px;
            }
            .grid-2 {
                display: table;
                width: 100%;
                border-collapse: collapse;
            }
            .grid-2 .row {
                display: table-row;
            }
            .grid-2 .cell {
                display: table-cell;
                padding: 3px 5px;
                border: 1px solid #ccc;
                width: 50%;
                font-size: 8pt;
            }
            .grid-2 .cell.label {
                background-color: #f5f5f5;
                font-weight: bold;
                width: 40%;
            }
            .footer {
                margin-top: 15px;
                padding-top: 10px;
                border-top: 1px solid #ccc;
                font-size: 7pt;
                text-align: center;
            }
            .fecha-registro {
                text-align: right;
                font-size: 7pt;
                margin-bottom: 5px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>EXAMEN GENERAL</h1>
            <h2>Cuaderno de Odontología</h2>
        </div>
        
        <div class="fecha-registro">
            <strong>Fecha de Registro:</strong> ' . htmlspecialchars($fechaRegistroFormateada) . '
        </div>
        
        <div class="info-paciente">
            <table>
                <tr>
                    <td class="label">Paciente:</td>
                    <td>' . htmlspecialchars($nombreCompleto) . '</td>
                    <td class="label">C.I.:</td>
                    <td>' . htmlspecialchars($ci) . '</td>
                </tr>
                <tr>
                    <td class="label">Edad:</td>
                    <td>' . ($edad ? $edad . ' años' : '-') . '</td>
                    <td class="label">Celular:</td>
                    <td>' . htmlspecialchars($celular ? $celular : '-') . '</td>
                </tr>
                <tr>
                    <td class="label">Fecha Atención:</td>
                    <td>' . htmlspecialchars($fechaAtencionFormateada) . '</td>
                    <td class="label">Médico:</td>
                    <td>' . htmlspecialchars($nombreMedico) . '</td>
                </tr>
            </table>
        </div>
        
        <div class="seccion">
            <div class="seccion-titulo">EXAMEN GENERAL</div>
            <div class="grid-2">
                <div class="row">
                    <div class="cell label">Intervenido quirúrgicamente:</div>
                    <div class="cell">' . $intervenido_quirurgicamente . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Problemas cardíacos:</div>
                    <div class="cell">' . $problemas_cardiacos . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Diabético:</div>
                    <div class="cell">' . $diabetico . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Alergia a medicamentos:</div>
                    <div class="cell">' . $alergia_medicamentos . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Cicatrización normal:</div>
                    <div class="cell">' . $cicatrizacion_normal . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Problemas de coagulación:</div>
                    <div class="cell">' . $problemas_coagulacion . '</div>
                </div>
                <div class="row">
                    <div class="cell label">En tratamiento médico:</div>
                    <div class="cell">' . $tratamiento_medico . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Toma medicamentos:</div>
                    <div class="cell">' . $toma_medicamentos . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Embarazo:</div>
                    <div class="cell">' . $embarazo . '</div>
                </div>
                <div class="row">
                    <div class="cell label">F.U.M. (fecha):</div>
                    <div class="cell">' . $fum . '</div>
                </div>
            </div>
        </div>
        
        <div class="seccion">
            <div class="seccion-titulo">EXAMEN BUCODENTAL</div>
            <div class="grid-2">
                <div class="row">
                    <div class="cell label">Higiene dental:</div>
                    <div class="cell">' . $higiene_dental . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Usa cepillo dental:</div>
                    <div class="cell">' . $usa_cepillo . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Frecuencia de cepillado:</div>
                    <div class="cell">' . $frecuencia_cepillado . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Usa hilo dental:</div>
                    <div class="cell">' . $usa_hilo_dental . '</div>
                </div>
            </div>
        </div>
        
        <div class="seccion">
            <div class="seccion-titulo">HÁBITOS Y COSTUMBRES</div>
            <div class="grid-2">
                <div class="row">
                    <div class="cell label">Respirador bucal:</div>
                    <div class="cell">' . $respirador_bucal . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Usa chupón:</div>
                    <div class="cell">' . $usa_chupon . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Fuma:</div>
                    <div class="cell">' . $fuma . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Toma alcohol:</div>
                    <div class="cell">' . $toma_alcohol . '</div>
                </div>
                <div class="row">
                    <div class="cell label">Masca coca:</div>
                    <div class="cell">' . $masca_coca . '</div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Documento generado el ' . date('d/m/Y H:i') . ' - CliniCloud</p>
        </div>
    </body>
    </html>';
    
    // Incluir autoload de composer
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    try {
        // Configurar mPDF con diseño compacto
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 5,
            'margin_footer' => 5,
            'tempDir' => __DIR__ . '/../../storage/temp'
        ]);
        
        // Escribir HTML
        $mpdf->WriteHTML($html);
        
        // Generar nombre de archivo único
        $nombreArchivo = 'examen_general_' . $idCuaOdontologia . '_' . time() . '.pdf';
        $rutaArchivo = __DIR__ . '/../../storage/temp/' . $nombreArchivo;
        
        // Guardar PDF
        $mpdf->Output($rutaArchivo, 'F');
        
        // Calcular la ruta base del proyecto de forma dinámica
        $rutaBase = obtenerRutaBaseProyecto();
        $rutaPDF = $rutaBase . '/storage/temp/' . $nombreArchivo;
        
        // Retornar array con la ruta del archivo
        return [
            "estado" => "OK", 
            "mensaje" => "PDF generado correctamente",
            "archivo" => $nombreArchivo,
            "ruta" => $rutaPDF
        ];
        
    } catch (\Exception $e) {
        return [
            "estado" => "ERROR", 
            "mensaje" => "Error al generar PDF: " . $e->getMessage()
        ];
    }
}

function generarPDFRegistroTratamientos($idCuaOdontologia, $idAtencion, $tipoAtencion, $jsonInfoCuaderno, $fechaRegistro, $idUsuario){
    
    global $link;
    
    // Obtener información de la atención
    $conAtencionClinica = mysqli_query($link, "SELECT `idAtencion`, `idPaciente`, `idConsultorio`, `fechaAtencion`, `idUsuario` 
        FROM `atencion_clinica` WHERE `idAtencion` = '$idAtencion'") or die(mysqli_error($link));
    
    if(mysqli_num_rows($conAtencionClinica) > 0){
        $rowAtencionClinica = mysqli_fetch_array($conAtencionClinica);
        $idPaciente = $rowAtencionClinica['idPaciente'];
        $fechaAtencion = $rowAtencionClinica['fechaAtencion'];
    } else {
        return [
            "estado" => "ERROR", 
            "mensaje" => "No se encontró la atención clínica"
        ];
    }
    
    // Obtener información del paciente
    $conPacientes = mysqli_query($link, "SELECT `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular` 
        FROM `pacientes` WHERE `idPaciente` = '$idPaciente'") or die(mysqli_error($link));
    
    if(mysqli_num_rows($conPacientes) > 0){
        $rowPaciente = mysqli_fetch_array($conPacientes);
        $ci = $rowPaciente['ci'];
        $apellidoPat = $rowPaciente['apellidoPat'];
        $apellidoMat = $rowPaciente['apellidoMat'];
        $nombres = $rowPaciente['nombres'];
        $fechaNacimiento = $rowPaciente['fechaNacimiento'];
        $celular = $rowPaciente['celular'];
        $nombreCompleto = trim($nombres . ' ' . $apellidoPat . ' ' . $apellidoMat);
    } else {
        return [
            "estado" => "ERROR", 
            "mensaje" => "No se encontró el paciente"
        ];
    }
    
    // Obtener información del usuario/médico
    $conUsuario = mysqli_query($link, "SELECT `nombreUs`, `primerApUs`, `segundoApUs` 
        FROM `usuarios` WHERE `idUsuario` = '$idUsuario'") or die(mysqli_error($link));
    
    $nombreMedico = '';
    if(mysqli_num_rows($conUsuario) > 0){
        $rowUsuario = mysqli_fetch_array($conUsuario);
        $nombreMedico = trim($rowUsuario['nombreUs'] . ' ' . $rowUsuario['primerApUs'] . ' ' . $rowUsuario['segundoApUs']);
    }
    
    // Decodificar JSON del registro de tratamientos
    $datosTratamientos = json_decode($jsonInfoCuaderno, true);
    
    if(!$datosTratamientos || !isset($datosTratamientos['registros'])){
        return [
            "estado" => "ERROR", 
            "mensaje" => "No se encontraron datos de tratamientos"
        ];
    }
    
    // Calcular edad
    $edad = '';
    if($fechaNacimiento){
        $fechaNac = new DateTime($fechaNacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fechaNac)->y;
    }
    
    // Formatear fechas
    $fechaAtencionFormateada = $fechaAtencion ? date('d/m/Y H:i', strtotime($fechaAtencion)) : '';
    $fechaRegistroFormateada = $fechaRegistro ? date('d/m/Y H:i', strtotime($fechaRegistro)) : '';
    
    // Procesar registros de tratamientos
    $registros = $datosTratamientos['registros'] ?? [];
    $totalGeneral = isset($datosTratamientos['totalGeneral']) ? floatval($datosTratamientos['totalGeneral']) : 0;
    
    // Generar tabla de tratamientos
    $tablaTratamientos = '';
    if(!empty($registros)){
        $tablaTratamientos = '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background-color: #333; color: white;">
                    <th style="border: 1px solid #ccc; padding: 5px; text-align: left; font-size: 8pt;">#</th>
                    <th style="border: 1px solid #ccc; padding: 5px; text-align: left; font-size: 8pt;">Fecha</th>
                    <th style="border: 1px solid #ccc; padding: 5px; text-align: left; font-size: 8pt;">Diagnóstico</th>
                    <th style="border: 1px solid #ccc; padding: 5px; text-align: center; font-size: 8pt;">Pieza</th>
                    <th style="border: 1px solid #ccc; padding: 5px; text-align: left; font-size: 8pt;">Código</th>
                    <th style="border: 1px solid #ccc; padding: 5px; text-align: left; font-size: 8pt;">Tratamiento</th>
                    <th style="border: 1px solid #ccc; padding: 5px; text-align: left; font-size: 8pt;">Medición</th>
                    <th style="border: 1px solid #ccc; padding: 5px; text-align: right; font-size: 8pt;">Precio (Bs.)</th>
                    <th style="border: 1px solid #ccc; padding: 5px; text-align: right; font-size: 8pt;">Total (Bs.)</th>
                </tr>
            </thead>
            <tbody>';
        
        $contador = 0;
        foreach($registros as $registro){
            $contador++;
            $fechaTratamiento = isset($registro['fecha']) && $registro['fecha'] != '' ? date('d/m/Y', strtotime($registro['fecha'])) : '-';
            $diagnostico = htmlspecialchars($registro['diagnostico'] ?? '-', ENT_QUOTES, 'UTF-8');
            $pieza = htmlspecialchars($registro['pieza'] ?? '-', ENT_QUOTES, 'UTF-8');
            $codigoArancel = htmlspecialchars($registro['codigoArancel'] ?? '-', ENT_QUOTES, 'UTF-8');
            $descripcionArancel = htmlspecialchars($registro['descripcionArancel'] ?? '-', ENT_QUOTES, 'UTF-8');
            $medicion = htmlspecialchars($registro['medicion'] ?? '-', ENT_QUOTES, 'UTF-8');
            $precio = isset($registro['precio']) ? number_format(floatval($registro['precio']), 2, '.', ',') : '0.00';
            $total = isset($registro['total']) ? number_format(floatval($registro['total']), 2, '.', ',') : '0.00';
            
            $tablaTratamientos .= '<tr>
                <td style="border: 1px solid #ccc; padding: 4px; font-size: 8pt;">' . $contador . '</td>
                <td style="border: 1px solid #ccc; padding: 4px; font-size: 8pt;">' . $fechaTratamiento . '</td>
                <td style="border: 1px solid #ccc; padding: 4px; font-size: 8pt;">' . $diagnostico . '</td>
                <td style="border: 1px solid #ccc; padding: 4px; text-align: center; font-size: 8pt;">' . $pieza . '</td>
                <td style="border: 1px solid #ccc; padding: 4px; font-size: 8pt;">' . $codigoArancel . '</td>
                <td style="border: 1px solid #ccc; padding: 4px; font-size: 8pt;">' . $descripcionArancel . '</td>
                <td style="border: 1px solid #ccc; padding: 4px; font-size: 8pt;">' . $medicion . '</td>
                <td style="border: 1px solid #ccc; padding: 4px; text-align: right; font-size: 8pt;">' . $precio . '</td>
                <td style="border: 1px solid #ccc; padding: 4px; text-align: right; font-size: 8pt; font-weight: bold;">' . $total . '</td>
            </tr>';
        }
        
        $tablaTratamientos .= '</tbody>
            <tfoot>
                <tr style="background-color: #f5f5f5; font-weight: bold;">
                    <td colspan="8" style="border: 1px solid #ccc; padding: 5px; text-align: right; font-size: 9pt;">TOTAL GENERAL:</td>
                    <td style="border: 1px solid #ccc; padding: 5px; text-align: right; font-size: 9pt;">' . number_format($totalGeneral, 2, '.', ',') . '</td>
                </tr>
            </tfoot>
        </table>';
    } else {
        $tablaTratamientos = '<p style="text-align: center; color: #999; font-style: italic; margin: 20px 0;">No hay tratamientos registrados.</p>';
    }
    
    // Generar HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 9pt;
                margin: 0;
                padding: 10px;
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #000;
                padding-bottom: 5px;
                margin-bottom: 10px;
            }
            .header h1 {
                margin: 0;
                font-size: 14pt;
                font-weight: bold;
            }
            .header h2 {
                margin: 5px 0;
                font-size: 11pt;
            }
            .info-paciente {
                margin-bottom: 10px;
                font-size: 8pt;
            }
            .info-paciente table {
                width: 100%;
                border-collapse: collapse;
            }
            .info-paciente td {
                padding: 2px 5px;
                border: 1px solid #ccc;
            }
            .info-paciente td.label {
                background-color: #f0f0f0;
                font-weight: bold;
                width: 25%;
            }
            .seccion {
                margin-bottom: 8px;
                page-break-inside: avoid;
            }
            .seccion-titulo {
                background-color: #333;
                color: white;
                padding: 3px 5px;
                font-weight: bold;
                font-size: 9pt;
                margin-bottom: 5px;
            }
            .footer {
                margin-top: 15px;
                padding-top: 10px;
                border-top: 1px solid #ccc;
                font-size: 7pt;
                text-align: center;
            }
            .fecha-registro {
                text-align: right;
                font-size: 7pt;
                margin-bottom: 5px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>REGISTRO DE TRATAMIENTOS</h1>
            <h2>Cuaderno de Odontología</h2>
        </div>
        
        <div class="fecha-registro">
            <strong>Fecha de Registro:</strong> ' . htmlspecialchars($fechaRegistroFormateada) . '
        </div>
        
        <div class="info-paciente">
            <table>
                <tr>
                    <td class="label">Paciente:</td>
                    <td>' . htmlspecialchars($nombreCompleto) . '</td>
                    <td class="label">C.I.:</td>
                    <td>' . htmlspecialchars($ci) . '</td>
                </tr>
                <tr>
                    <td class="label">Edad:</td>
                    <td>' . ($edad ? $edad . ' años' : '-') . '</td>
                    <td class="label">Celular:</td>
                    <td>' . htmlspecialchars($celular ? $celular : '-') . '</td>
                </tr>
                <tr>
                    <td class="label">Fecha Atención:</td>
                    <td>' . htmlspecialchars($fechaAtencionFormateada) . '</td>
                    <td class="label">Médico:</td>
                    <td>' . htmlspecialchars($nombreMedico) . '</td>
                </tr>
            </table>
        </div>
        
        <div class="seccion">
            <div class="seccion-titulo">TRATAMIENTOS REALIZADOS</div>
            ' . $tablaTratamientos . '
        </div>
        
        <div class="footer">
            <p>Documento generado el ' . date('d/m/Y H:i') . ' - CliniCloud</p>
        </div>
    </body>
    </html>';
    
    // Incluir autoload de composer
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    try {
        // Configurar mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'L', // Landscape para la tabla ancha
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 5,
            'margin_footer' => 5,
            'tempDir' => __DIR__ . '/../../storage/temp'
        ]);
        
        // Escribir HTML
        $mpdf->WriteHTML($html);
        
        // Generar nombre de archivo único
        $nombreArchivo = 'registro_tratamientos_' . $idCuaOdontologia . '_' . time() . '.pdf';
        $rutaArchivo = __DIR__ . '/../../storage/temp/' . $nombreArchivo;
        
        // Guardar PDF
        $mpdf->Output($rutaArchivo, 'F');
        
        // Calcular la ruta base del proyecto de forma dinámica
        $rutaBase = obtenerRutaBaseProyecto();
        $rutaPDF = $rutaBase . '/storage/temp/' . $nombreArchivo;
        
        // Retornar array con la ruta del archivo
        return [
            "estado" => "OK", 
            "mensaje" => "PDF generado correctamente",
            "archivo" => $nombreArchivo,
            "ruta" => $rutaPDF
        ];
        
    } catch (\Exception $e) {
        return [
            "estado" => "ERROR", 
            "mensaje" => "Error al generar PDF: " . $e->getMessage()
        ];
    }
}

function formularioRegistroTratamientos(){
    date_default_timezone_set('America/La_Paz');
    global $link;
    global $input;

    $idAtencion = $input['idAtencion'];

    // Obtener lista de aranceles
    $sqlAranceles = "SELECT `idArancel`, `codigo`, `descripcion`, `precio` FROM `aranceles` ORDER BY `descripcion` ASC";
    $resultAranceles = mysqli_query($link, $sqlAranceles) or die(mysqli_error($link));
    
    // Guardar las opciones en un array para reutilizarlas
    $opcionesAranceles = [];
    if($resultAranceles && mysqli_num_rows($resultAranceles) > 0){
        while($rowArancel = mysqli_fetch_array($resultAranceles)){
            $opcionesAranceles[] = $rowArancel;
        }
    }

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card'>";
                echo "<div class='card-header d-flex justify-content-between align-items-center'>";
                    echo "<b>Registro Clínico por Pieza Dental</b>";
                echo "</div>";
                echo "<div class='card-body'>";
                    
                    // Contenedor para los registros dinámicos
                    echo "<div id='contenedorRegistrosTratamientos'>";
                        // El primer registro se agregará automáticamente
                    echo "</div>";

                    // Botón para agregar nuevo registro
                    echo "<div class='text-center mt-3 mb-3'>";
                        echo "<button type='button' class='btn btn-success btn-sm' id='btnAgregarRegistro'><i class='fas fa-plus'></i> Agregar Registro</button>";
                    echo "</div>";

                    // Resumen de totales
                    echo "<div class='row mt-3'>";
                        echo "<div class='col-md-12'>";
                            echo "<div class='card bg-light'>";
                                echo "<div class='card-body'>";
                                    echo "<div class='row'>";
                                        echo "<div class='col-md-6'>";
                                            echo "<h5 class='mb-0'>Total General: <span id='totalGeneral' class='text-primary'>0.00</span> Bs.</h5>";
                                        echo "</div>";
                                        echo "<div class='col-md-6 text-end'>";
                                            echo "<button type='button' class='btn btn-primary' id='btnGuardarRegistroTratamiento'><i class='fas fa-save'></i> Guardar Registro</button>";
                                        echo "</div>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";

    // Template HTML para un registro (se usará en JavaScript)
    echo "<script type='text/template' id='templateRegistro'>";
        echo "<div class='registro-tratamiento card mb-3' data-indice='{{INDICE}}'>";
            echo "<div class='card-header bg-primary text-white d-flex justify-content-between align-items-center'>";
                echo "<strong>Registro #<span class='numero-registro'>{{NUMERO}}</span></strong>";
                echo "<button type='button' class='btn btn-sm btn-danger btnEliminarRegistro'><i class='fas fa-trash'></i></button>";
            echo "</div>";
            echo "<div class='card-body'>";
                echo "<div class='row g-3'>";
                    
                    // Fecha
                    echo "<div class='col-md-3'>";
                        echo "<label class='form-label small'>Fecha <span class='text-danger'>*</span></label>";
                        echo "<input type='date' class='form-control form-control-sm campo-fecha' name='fecha[]' value='" . date('Y-m-d') . "' required>";
                    echo "</div>";

                    // Diagnóstico
                    echo "<div class='col-md-3'>";
                        echo "<label class='form-label small'>Diagnóstico <span class='text-danger'>*</span></label>";
                        echo "<input type='text' class='form-control form-control-sm campo-diagnostico' name='diagnostico[]' placeholder='Ej: Caries' required>";
                    echo "</div>";

                    // Pieza N°
                    echo "<div class='col-md-2'>";
                        echo "<label class='form-label small'>Pieza N° <span class='text-danger'>*</span></label>";
                        echo "<input type='text' class='form-control form-control-sm campo-pieza' name='pieza[]' placeholder='Ej: 16' required>";
                    echo "</div>";

                    // Tratamiento (Select con aranceles)
                    echo "<div class='col-md-4'>";
                        echo "<label class='form-label small'>Tratamiento <span class='text-danger'>*</span></label>";
                        echo "<select class='form-select form-select-sm campo-tratamiento' name='tratamiento[]' required>";
                            echo "<option value=''>Seleccione tratamiento...</option>";
                            if(!empty($opcionesAranceles)){
                                foreach($opcionesAranceles as $rowArancel){
                                    $idArancel = htmlspecialchars($rowArancel['idArancel'], ENT_QUOTES, 'UTF-8');
                                    $codigo = htmlspecialchars($rowArancel['codigo'], ENT_QUOTES, 'UTF-8');
                                    $descripcion = htmlspecialchars($rowArancel['descripcion'], ENT_QUOTES, 'UTF-8');
                                    $precio = htmlspecialchars($rowArancel['precio'], ENT_QUOTES, 'UTF-8');
                                    echo "<option value='" . $idArancel . "' data-precio='" . $precio . "' data-codigo='" . $codigo . "'>" . $codigo . " - " . $descripcion . " (Bs. " . number_format($precio, 2) . ")</option>";
                                }
                            }
                        echo "</select>";
                    echo "</div>";

                    // Medición
                    echo "<div class='col-md-3'>";
                        echo "<label class='form-label small'>Medición</label>";
                        echo "<input type='text' class='form-control form-control-sm campo-medicion' name='medicion[]' placeholder='Ej: 2mm'>";
                    echo "</div>";

                    // Total (calculado automáticamente)
                    echo "<div class='col-md-3'>";
                        echo "<label class='form-label small'>Total</label>";
                        echo "<input type='text' class='form-control form-control-sm campo-total' name='total[]' readonly value='0.00'>";
                    echo "</div>";

                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</script>";
}

function guardarRegistroTratamientos(){
    date_default_timezone_set('America/La_Paz');
    global $link;
    global $input;

    $idAtencion = $input['idAtencion'];
    $jsonDatosRegistroTratamientos = $input['jsonDatosRegistroTratamientos'];
    $fechaRegistro = date('Y-m-d H:i:s');
    $idUsuario = $_SESSION['idUsuario_clinicloud'];

    $registrarTratamientos = mysqli_query($link, "INSERT INTO `cuaderno_odontologia`(`idAtencion`, `tipoAtencion`, `jsonInfoCuaderno`, `fechaRegistro`, `idUsuario`) 
    VALUES ('$idAtencion','REGISTRO DE TRATAMIENTOS','$jsonDatosRegistroTratamientos','$fechaRegistro','$idUsuario')")or die(mysqli_error($link));
    
    if($registrarTratamientos){
        echo json_encode(["estado" => "OK", "mensaje" => "Registro de tratamientos guardado correctamente"]);
    }
    else{
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al guardar el registro de tratamientos"]);
    }
}
