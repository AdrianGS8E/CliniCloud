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
    case 'formularioExamenGeneral':
        formularioExamenGeneral();
        break;
    case 'guardarExamenGeneral':
        guardarExamenGeneral();
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

    global $link;
    global $input;

    $idConsultorio = $input['idConsultorio'];

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card'>";
                echo "<div class='card-header d-flex justify-content-between align-items-center'>";
                    echo "<b>Lista de pacientes</b>";
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
                        echo "<table class='table table-bordered'>";
                            echo "<thead>";
                                echo "<tr>";
                                    echo "<th>C.I.</th>";
                                    echo "<th>Nombre</th>";
                                echo "</tr>";
                            echo "</thead>";
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
                                echo "<button class='btn btn-primary btn-sm btnFormExamenGeneral' id='" . $rowPaciente['idPaciente'] . "' title='Examen General'><i class='fas fa-stethoscope'></i> Examen General</button>";
                                echo "<button class='btn btn-danger btn-sm btnFormRegistroTratamientos' id='" . $rowPaciente['idPaciente'] . "' title='Registro de Tratamientos'><i class='fas fa-tooth'></i> Registro de Tratamientos</button>";
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


function formularioExamenGeneral(){
    global $link;
    global $input;

    $idPaciente = $input['idPaciente'];
    $idConsultorio = $input['idConsultorio'];

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card shadow-sm'>";
                echo "<div class='card-header bg-primary text-white py-2'>";
                    echo "<h6 class='mb-0'><i class='fas fa-tooth me-2'></i>Formulario de Atención Clínica Odontológica</h6>";
                echo "</div>";
                echo "<div class='card-body p-3'>";

                    // Información del paciente (compacta)
                    $idPaciente = $input['idPaciente'];
                    $sqlPaciente = "SELECT `idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `email`, `direccion`, `procedencia`, `residencia`, `nombreTutor`, `celularTutor` FROM `pacientes` WHERE `idPaciente` = '" . mysqli_real_escape_string($link, $idPaciente) . "'";
                    $resultPaciente = mysqli_query($link, $sqlPaciente);
                    
                    if ($resultPaciente && mysqli_num_rows($resultPaciente) > 0) {
                        $paciente = mysqli_fetch_array($resultPaciente);
                        
                        $fechaNac = new DateTime($paciente['fechaNacimiento']);
                        $hoy = new DateTime();
                        $edad = $hoy->diff($fechaNac)->y;
                        
                        echo "<div class='alert alert-light border mb-3 py-2'>";
                            echo "<div class='row g-2 align-items-center'>";
                                echo "<div class='col-md-4'>";
                                    echo "<small class='text-muted d-block mb-0'>Paciente:</small>";
                                    echo "<strong class='text-primary'>" . htmlspecialchars($paciente['nombres'] . " " . $paciente['apellidoPat'] . " " . $paciente['apellidoMat']) . "</strong>";
                                echo "</div>";
                                echo "<div class='col-md-2'>";
                                    echo "<small class='text-muted d-block mb-0'>C.I.:</small>";
                                    echo "<span>" . htmlspecialchars($paciente['ci']) . "</span>";
                                echo "</div>";
                                echo "<div class='col-md-2'>";
                                    echo "<small class='text-muted d-block mb-0'>Edad:</small>";
                                    echo "<span>" . $edad . " años</span>";
                                echo "</div>";
                                echo "<div class='col-md-2'>";
                                    echo "<small class='text-muted d-block mb-0'>Celular:</small>";
                                    echo "<span>" . htmlspecialchars($paciente['celular']) . "</span>";
                                echo "</div>";
                                if (!empty($paciente['nombreTutor']) || !empty($paciente['celularTutor'])) {
                                    echo "<div class='col-md-2'>";
                                        echo "<small class='text-muted d-block mb-0'><i class='fas fa-user-shield'></i> Tutor:</small>";
                                        echo "<small>" . htmlspecialchars($paciente['nombreTutor'] ? $paciente['nombreTutor'] : '-') . "</small>";
                                    echo "</div>";
                                }
                            echo "</div>";
                            echo "<input type='hidden' id='idPaciente' name='idPaciente' value='" . htmlspecialchars($paciente['idPaciente']) . "'>";
                        echo "</div>";
                    } else {
                        echo "<div class='alert alert-warning py-2 mb-3'>";
                            echo "<small><i class='fas fa-exclamation-triangle me-1'></i>No se encontró información del paciente.</small>";
                        echo "</div>";
                        echo "<input type='hidden' id='idPaciente' name='idPaciente' value='" . htmlspecialchars($idPaciente) . "'>";
                    }

                    // Examen general (compacto)
                    echo "<div class='card border-primary mb-2'>";
                        echo "<div class='card-header bg-primary bg-opacity-10 py-1 px-2'>";
                            echo "<small class='fw-bold text-primary'><i class='fas fa-stethoscope me-1'></i>Examen General</small>";
                        echo "</div>";
                        echo "<div class='card-body p-2'>";
                            echo "<div class='row g-2'>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Intervenido quirúrgicamente</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='intervenido_quirurgicamente' id='intervenido_quirurgicamente_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='intervenido_quirurgicamente_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='intervenido_quirurgicamente' id='intervenido_quirurgicamente_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='intervenido_quirurgicamente_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Problemas cardíacos</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='problemas_cardiacos' id='problemas_cardiacos_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='problemas_cardiacos_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='problemas_cardiacos' id='problemas_cardiacos_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='problemas_cardiacos_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Diabético</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='diabetico' id='diabetico_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='diabetico_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='diabetico' id='diabetico_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='diabetico_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Alergia medicamentos</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='alergia_medicamentos' id='alergia_medicamentos_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='alergia_medicamentos_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='alergia_medicamentos' id='alergia_medicamentos_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='alergia_medicamentos_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Cicatrización normal</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='cicatrizacion_normal' id='cicatrizacion_normal_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='cicatrizacion_normal_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='cicatrizacion_normal' id='cicatrizacion_normal_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='cicatrizacion_normal_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Problemas coagulación</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='problemas_coagulacion' id='problemas_coagulacion_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='problemas_coagulacion_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='problemas_coagulacion' id='problemas_coagulacion_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='problemas_coagulacion_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Tratamiento médico</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='tratamiento_medico' id='tratamiento_medico_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='tratamiento_medico_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='tratamiento_medico' id='tratamiento_medico_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='tratamiento_medico_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Toma medicamentos</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='toma_medicamentos' id='toma_medicamentos_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='toma_medicamentos_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='toma_medicamentos' id='toma_medicamentos_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='toma_medicamentos_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Embarazo</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='embarazo' id='embarazo_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='embarazo_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='embarazo' id='embarazo_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='embarazo_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>F.U.M</label>";
                                    echo "<input type='date' class='form-control form-control-sm mt-1' name='fum' id='fum' value=''>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";
                    echo "</div>";

                    // Examen bucodental (compacto)
                    echo "<div class='card border-info mb-2'>";
                        echo "<div class='card-header bg-info bg-opacity-10 py-1 px-2'>";
                            echo "<small class='fw-bold text-info'><i class='fas fa-tooth me-1'></i>Examen Bucodental</small>";
                        echo "</div>";
                        echo "<div class='card-body p-2'>";
                            echo "<div class='row g-2'>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Higiene dental</label>";
                                    echo "<select class='form-select form-select-sm mt-1' name='higiene_dental' id='higiene_dental'>";
                                        echo "<option value='' selected>Seleccione...</option>";
                                        echo "<option value='Buena'>Buena</option>";
                                        echo "<option value='Regular'>Regular</option>";
                                        echo "<option value='Mala'>Mala</option>";
                                    echo "</select>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Usa cepillo dental</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='usa_cepillo_dental' id='usa_cepillo_dental_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='usa_cepillo_dental_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='usa_cepillo_dental' id='usa_cepillo_dental_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='usa_cepillo_dental_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Frecuencia cepillado</label>";
                                    echo "<input type='text' class='form-control form-control-sm mt-1' name='frecuencia_cepillado' id='frecuencia_cepillado' placeholder='Ej: 2 veces/día' value=''>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Usa hilo dental</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='usa_hilo_dental' id='usa_hilo_dental_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='usa_hilo_dental_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='usa_hilo_dental' id='usa_hilo_dental_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='usa_hilo_dental_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";
                    echo "</div>";

                    // Hábitos y costumbres (compacto)
                    echo "<div class='card border-warning mb-2'>";
                        echo "<div class='card-header bg-warning bg-opacity-10 py-1 px-2'>";
                            echo "<small class='fw-bold text-warning'><i class='fas fa-clipboard-list me-1'></i>Hábitos y Costumbres</small>";
                        echo "</div>";
                        echo "<div class='card-body p-2'>";
                            echo "<div class='row g-2'>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Respirador bucal</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='respirador_bucal' id='respirador_bucal_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='respirador_bucal_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='respirador_bucal' id='respirador_bucal_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='respirador_bucal_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Usa chupón</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='usa_chupon' id='usa_chupon_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='usa_chupon_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='usa_chupon' id='usa_chupon_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='usa_chupon_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Fuma</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='fuma' id='fuma_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='fuma_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='fuma' id='fuma_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='fuma_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Toma alcohol</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='toma_alcohol' id='toma_alcohol_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='toma_alcohol_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='toma_alcohol' id='toma_alcohol_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='toma_alcohol_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-6 col-md-4 col-lg-3 col-xl-2'>";
                                    echo "<label class='form-label small mb-0'>Masca coca</label>";
                                    echo "<div class='btn-group btn-group-sm w-100 mt-1' role='group'>";
                                        echo "<input type='radio' class='btn-check' name='masca_coca' id='masca_coca_si' value='Sí'>";
                                        echo "<label class='btn btn-outline-primary' for='masca_coca_si'>Sí</label>";
                                        echo "<input type='radio' class='btn-check' name='masca_coca' id='masca_coca_no' value='No'>";
                                        echo "<label class='btn btn-outline-primary' for='masca_coca_no'>No</label>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";
                    echo "</div>";

                    // Motivo de consulta (compacto)
                    echo "<div class='card border-success mb-2'>";
                        echo "<div class='card-header bg-success bg-opacity-10 py-1 px-2'>";
                            echo "<small class='fw-bold text-success'><i class='fas fa-comment-medical me-1'></i>Motivo de Consulta</small>";
                        echo "</div>";
                        echo "<div class='card-body p-2'>";
                            echo "<textarea class='form-control form-control-sm' name='motivo_consulta' id='motivo_consulta' rows='2' placeholder='Describa el motivo de la consulta...'></textarea>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";

                echo "<div class='card-footer'>";
                    echo "<button class='btn btn-primary' id='btnGuardarExamenGeneral'><i class='fas fa-save'></i> Guardar</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function guardarExamenGeneral(){
    global $link;
    global $input;

    $idConsultorio = isset($input['idConsultorio']) ? mysqli_real_escape_string($link, $input['idConsultorio']) : '';
    $idPaciente = isset($input['idPaciente']) ? mysqli_real_escape_string($link, $input['idPaciente']) : '';
    $examenGeneral = isset($input['examenGeneral']) ? $input['examenGeneral'] : array();
    $examenBucoDental = isset($input['examenBucoDental']) ? $input['examenBucoDental'] : array();
    $habitosCostumbres = isset($input['habitosCostumbres']) ? $input['habitosCostumbres'] : array();
    $motivoConsulta = isset($input['motivoConsulta']) ? mysqli_real_escape_string($link, $input['motivoConsulta']) : '';

    // Validar datos requeridos
    if (empty($idConsultorio) || empty($idPaciente)) {
        echo json_encode(["estado" => "ERROR", "mensaje" => "Faltan datos requeridos (idConsultorio o idPaciente)."]);
        return;
    }

    // Obtener idUsuario de la sesión
    $idUsuario = isset($_SESSION['idUsuario_clinicloud']) ? $_SESSION['idUsuario_clinicloud'] : '';

    if (empty($idUsuario)) {
        echo json_encode(["estado" => "ERROR", "mensaje" => "Sesión no válida."]);
        return;
    }

    // Verificar si existe una atención médica para este paciente y consultorio
    // Si no existe, crear una nueva atención
    $fechaAtencion = date('Y-m-d H:i:s');
    
    // Buscar si existe una atención médica reciente (mismo día)
    $sqlBuscarAtencion = "SELECT `idAtencion` FROM `atenciones_medicas` 
                          WHERE `idPaciente` = '$idPaciente' 
                          AND `idConsultorio` = '$idConsultorio' 
                          AND DATE(`fechaAtencion`) = CURDATE()
                          ORDER BY `fechaAtencion` DESC 
                          LIMIT 1";
    
    $resultAtencion = mysqli_query($link, $sqlBuscarAtencion);
    
    if ($resultAtencion && mysqli_num_rows($resultAtencion) > 0) {
        $rowAtencion = mysqli_fetch_array($resultAtencion);
        $idAtencion = $rowAtencion['idAtencion'];
    } else {
        // Crear nueva atención médica
        $sqlCrearAtencion = "INSERT INTO `atenciones_medicas` 
                            (`idPaciente`, `idConsultorio`, `idUsuario`, `fechaAtencion`, `tipoAtencion`, `estado`) 
                            VALUES ('$idPaciente', '$idConsultorio', '$idUsuario', '$fechaAtencion', 'ODONTOLOGIA', 'ACTIVA')";
        
        if (mysqli_query($link, $sqlCrearAtencion)) {
            $idAtencion = mysqli_insert_id($link);
        } else {
            echo json_encode(["estado" => "ERROR", "mensaje" => "Error al crear la atención médica: " . mysqli_error($link)]);
            return;
        }
    }

    // Convertir arrays a JSON
    $jsonExamenGeneral = json_encode($examenGeneral, JSON_UNESCAPED_UNICODE);
    $jsonExamenBucoDental = json_encode($examenBucoDental, JSON_UNESCAPED_UNICODE);
    $jsonHabitosCostumbres = json_encode($habitosCostumbres, JSON_UNESCAPED_UNICODE);

    // Verificar si ya existe un registro de cuaderno_odontologia para esta atención
    $sqlVerificar = "SELECT `idCuaOdontologia` FROM `cuaderno_odontologia` WHERE `idAtencion` = '$idAtencion'";
    $resultVerificar = mysqli_query($link, $sqlVerificar);

    if ($resultVerificar && mysqli_num_rows($resultVerificar) > 0) {
        // Actualizar registro existente
        $rowVerificar = mysqli_fetch_array($resultVerificar);
        $idCuaOdontologia = $rowVerificar['idCuaOdontologia'];
        
        $sqlUpdate = "UPDATE `cuaderno_odontologia` 
                      SET `jsonExamenGenral` = '" . mysqli_real_escape_string($link, $jsonExamenGeneral) . "',
                          `jsonExamenBucoDental` = '" . mysqli_real_escape_string($link, $jsonExamenBucoDental) . "',
                          `jsonHabitosCostumbres` = '" . mysqli_real_escape_string($link, $jsonHabitosCostumbres) . "',
                          `motivoConsulta` = '" . $motivoConsulta . "'
                      WHERE `idCuaOdontologia` = '$idCuaOdontologia'";
        
        if (mysqli_query($link, $sqlUpdate)) {
            echo json_encode(["estado" => "OK", "mensaje" => "Examen general actualizado correctamente.", "idCuaOdontologia" => $idCuaOdontologia]);
        } else {
            echo json_encode(["estado" => "ERROR", "mensaje" => "Error al actualizar: " . mysqli_error($link)]);
        }
    } else {
        // Insertar nuevo registro
        $sqlInsert = "INSERT INTO `cuaderno_odontologia` 
                     (`idAtencion`, `tipoAtencion`, `jsonExamenGenral`, `jsonExamenBucoDental`, `jsonHabitosCostumbres`, `motivoConsulta`) 
                     VALUES ('$idAtencion', 'ODONTOLOGIA', 
                             '" . mysqli_real_escape_string($link, $jsonExamenGeneral) . "', 
                             '" . mysqli_real_escape_string($link, $jsonExamenBucoDental) . "', 
                             '" . mysqli_real_escape_string($link, $jsonHabitosCostumbres) . "', 
                             '" . $motivoConsulta . "')";
        
        if (mysqli_query($link, $sqlInsert)) {
            $idCuaOdontologia = mysqli_insert_id($link);
            echo json_encode(["estado" => "OK", "mensaje" => "Examen general guardado correctamente.", "idCuaOdontologia" => $idCuaOdontologia]);
        } else {
            echo json_encode(["estado" => "ERROR", "mensaje" => "Error al guardar: " . mysqli_error($link)]);
        }
    }
}