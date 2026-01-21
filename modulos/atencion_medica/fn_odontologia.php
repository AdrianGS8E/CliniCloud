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
                                    echo "<th>Tipo Atención</th>";
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
                                    u.`usuarioUs`,
                                    ac.`tipoAtencion`
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

                                        $badgeTipoAtencion = '';
                                        switch(strtoupper($rowAtencion['tipoAtencion'])) {
                                            case 'EXAMEN GENERAL':
                                                $badgeTipoAtencion = '<span class="badge bg-primary">EXAMEN GENERAL</span>';
                                                break;
                                            case 'REGISTRO CLINICO':
                                                $badgeTipoAtencion = '<span class="badge bg-danger">EXAMEN BUCODENTAL</span>';
                                                break;
                                        }
                                        
                                        echo "<tr>";
                                            echo "<td>" . htmlspecialchars($rowAtencion['idAtencion']) . "</td>";
                                            echo "<td>" . $badgeEstado . "</td>";
                                            echo "<td>" . $badgeTipoAtencion . "</td>";
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
                                                
                                                echo "<button class='btn btn-xs mb-2 btn-warning btnVerAtencionClinica' id='" . $rowAtencion['idAtencion'] . "' title='Ver Atencion'><i class='fas fa-eye'></i> Ver Atención</button> ";
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


function verAtencionClinica(){
    global $link;
    global $input;

    $idAtencion = $input['idAtencion'];

    $conAtencionClinica = mysqli_query($link, "SELECT `idAtencion`, `idPaciente`, `idConsultorio`, `fechaAtencion`, `idUsuario`, `fechaRegistro`, `estadoAtencion`, `especialidad`, `tipoAtencion` FROM `atencion_clinica` WHERE `idAtencion` = '$idAtencion'")or die(mysqli_error($link));
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
        $tipoAtencion = $rowAtencionClinica['tipoAtencion'];
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
                    echo "<div class='d-flex align-items-center gap-2'>";
                        echo "<label for='fechaConsulta' class='mb-0'>Fecha:</label>";
                        echo "<input type='date' id='fechaConsulta' name='fechaConsulta' class='form-control form-control-sm' style='width: auto;' value='" . date('Y-m-d') . "'>";
                    echo "</div>";
                echo "</div>";
                echo "<div class='card-body row'>";

                    echo "<div class='col-md-6'>";
                        echo "<h5 class='card-title'>Paciente</h5>";
                        echo "<p>" . $ci . " - " . $nombres . " " . $apellidoPat . " " . $apellidoMat . "</p>";
                    echo "</div>";

                    echo "<div class='col-md-12 text-center'>";
                        echo "<button class='btn btn-primary m-2' id='btnFormExamenGeneral'><i class='fas fa-print'></i> Examen General</button>";
                        echo "<button class='btn btn-danger m-2' id='btnFormExamenBucodental'><i class='fas fa-print'></i> Examen Bucodental</button>";
                        echo "<button class='btn btn-info m-2' id='btnFormExamenRayoxX'><i class='fas fa-print'></i> Registro de Tratamientos</button>";
                        echo "<button class='btn btn-warning m-2' id='btnFormExamenRayoxX'><i class='fas fa-print'></i> Rayox X</button>";
                    echo "</div>";


                

                echo "</div>";
            echo "</div>";

            echo "</div>";
        echo "</div>";
    echo "</div>";



}