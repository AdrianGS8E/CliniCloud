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

// Leer datos enviados desde fetch
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

if (!isset($input['funcion'])) {
    header("Content-Type: application/json");
    echo json_encode(["estado" => "ERROR", "mensaje" => "No se especificó la función a ejecutar."]);
    exit;
}

// Funciones que devuelven HTML en lugar de JSON (examen general y registro clínico están en sus propios módulos)
$funcionesHTML = ['listaConsultorios', 'verPacientesConsultorio', 'modalSeleccionarPaciente', 'verAtencionClinica', 'detalleCuentaPaciente', 'verRecibo'];
if (in_array($input['funcion'], $funcionesHTML)) {
    header("Content-Type: text/html; charset=utf-8");
} else {
    header("Content-Type: application/json");
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
    case 'detalleCuentaPaciente':
        detalleCuentaPaciente();
        break;
    case 'pagarOrden':
        pagarOrden();
        break;
    case 'verRecibo':
        verRecibo();
        break;
    
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function listaConsultorios(){
    global $link;
    global $input;

    date_default_timezone_set('America/La_Paz');
    $idUsuario = $_SESSION['idUsuario_clinicloud'];

    // Obtener conteos de atenciones por consultorio (hoy, mañana, esta semana, próxima semana)
    $sqlCounts = "SELECT 
                    ac.`idConsultorio`,
                    SUM(CASE WHEN DATE(ac.`fechaAtencion`) = CURDATE() THEN 1 ELSE 0 END) AS atenciones_hoy,
                    SUM(CASE WHEN DATE(ac.`fechaAtencion`) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS atenciones_manana,
                    SUM(CASE WHEN ac.`fechaAtencion` >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) 
                              AND ac.`fechaAtencion` < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS atenciones_esta_semana,
                    SUM(CASE WHEN ac.`fechaAtencion` >= DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY) 
                              AND ac.`fechaAtencion` < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 14 DAY) THEN 1 ELSE 0 END) AS atenciones_proxima_semana
                  FROM `atencion_clinica` ac
                  WHERE ac.`especialidad` = 'ODONTOLOGIA'
                  GROUP BY ac.`idConsultorio`";
    $resultCounts = mysqli_query($link, $sqlCounts);
    $countsPorConsultorio = [];
    if ($resultCounts) {
        while ($r = mysqli_fetch_assoc($resultCounts)) {
            $countsPorConsultorio[$r['idConsultorio']] = $r;
        }
    }

    echo "<div class='row'>";
        echo "<div class='col-12'>";
            echo "<div class='row g-3' id='lista-consultorios-odontologia'>";
                $sql = "SELECT `idConsultorio`, `codigo`, `descripcion`, `especialidad`, `listaMedicos` FROM `consultorios` WHERE `especialidad` = 'ODONTOLOGIA' ORDER BY `descripcion` ASC";
                $result = mysqli_query($link, $sql);
                $contadorConsultorios = 0;
                
                while ($row = mysqli_fetch_array($result)) {
                    $listaMedicos = json_decode($row['listaMedicos'], true);
                    $usuarioEnLista = false;
                    if ($listaMedicos && isset($listaMedicos['medicos']) && is_array($listaMedicos['medicos'])) {
                        foreach ($listaMedicos['medicos'] as $medico) {
                            if (isset($medico['idUsuario']) && $medico['idUsuario'] == $idUsuario) {
                                $usuarioEnLista = true;
                                break;
                            }
                        }
                    }
                    
                    if ($usuarioEnLista) {
                        $contadorConsultorios++;
                        $idC = (int)$row['idConsultorio'];
                        $stats = isset($countsPorConsultorio[$idC]) ? $countsPorConsultorio[$idC] : [
                            'atenciones_hoy' => 0, 'atenciones_manana' => 0,
                            'atenciones_esta_semana' => 0, 'atenciones_proxima_semana' => 0
                        ];
                        $hoy = (int)($stats['atenciones_hoy'] ?? 0);
                        $manana = (int)($stats['atenciones_manana'] ?? 0);
                        $estaSemana = (int)($stats['atenciones_esta_semana'] ?? 0);
                        $proxSemana = (int)($stats['atenciones_proxima_semana'] ?? 0);

                        echo "<div class='col-12 col-sm-6 col-lg-4 col-xl-3'>";
                            echo "<div class='card h-100 border-primary shadow-sm consultorio-card'>";
                                echo "<div class='card-header bg-primary text-white py-2 d-flex align-items-center'>";
                                    echo "<span class='badge bg-light text-primary me-2'>" . htmlspecialchars($row['codigo']) . "</span>";
                                    echo "<span class='small flex-grow-1 text-truncate' title='" . htmlspecialchars($row['descripcion']) . "'>" . htmlspecialchars($row['descripcion']) . "</span>";
                                echo "</div>";
                                echo "<div class='card-body py-2'>";
                                    echo "<div class='row g-1 small text-muted mb-2'>";
                                        echo "<div class='col-6'><i class='fas fa-calendar-day me-1'></i> Hoy: <strong class='text-dark'>" . $hoy . "</strong></div>";
                                        echo "<div class='col-6'><i class='fas fa-calendar-plus me-1'></i> Mañana: <strong class='text-dark'>" . $manana . "</strong></div>";
                                        echo "<div class='col-6'><i class='fas fa-calendar-week me-1'></i> Esta semana: <strong class='text-dark'>" . $estaSemana . "</strong></div>";
                                        echo "<div class='col-6'><i class='fas fa-calendar-alt me-1'></i> Próx. semana: <strong class='text-dark'>" . $proxSemana . "</strong></div>";
                                    echo "</div>";
                                    echo "<button type='button' class='btn btn-primary btn-sm w-100 btnVerPacientesConsultorio' id='" . $row['idConsultorio'] . "' title='Ver atenciones del consultorio'>";
                                        echo "<i class='fas fa-door-open me-1'></i> Ingresar al consultorio";
                                    echo "</button>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";
                    }
                }
                
                if ($contadorConsultorios == 0) {
                    echo "<div class='col-12'>";
                        echo "<div class='alert alert-warning mb-0' role='alert'>";
                            echo "<i class='fas fa-exclamation-triangle me-2'></i>";
                            echo "No tiene consultorios asignados para la especialidad Odontología.";
                        echo "</div>";
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
    $fechaConsulta = isset($input['fechaConsulta']) ? $input['fechaConsulta'] : date('Y-m-d');

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card'>";
                echo "<div class='card-header d-flex justify-content-between align-items-center'>";
                    echo "<b>Lista de Atenciones</b>";
                    echo "<div class='d-flex align-items-center gap-2'>";
                        echo "<label for='fechaConsulta' class='mb-0'>Fecha:</label>";
                        echo "<input type='date' id='fechaConsulta' name='fechaConsulta' class='form-control form-control-sm' style='width: auto;' value='" . $fechaConsulta . "'>";
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
                                    //echo "<th>Estado</th>";
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
                                            //echo "<td>" . $badgeEstado . "</td>";
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


    // Estadísticas económicas del paciente (para el panel)
    $saldoPendiente = 0;
    $totalAtenciones = 0;
    $totalCotizaciones = 0;

    $sqlSaldoPendiente = "SELECT COALESCE(SUM(`saldoPendiente`),0) AS `saldoPendiente`
                           FROM `orden_atencion`
                           WHERE `idPaciente` = '$idPaciente'
                           AND `estado` = 'ORDEN ATENCION'";
    $resSaldoPendiente = mysqli_query($link, $sqlSaldoPendiente) or die(mysqli_error($link));
    if(mysqli_num_rows($resSaldoPendiente) > 0){
        $rowSaldo = mysqli_fetch_array($resSaldoPendiente);
        $saldoPendiente = (float) $rowSaldo['saldoPendiente'];
    }

    $sqlAtenciones = "SELECT COUNT(`idOrdenAtencion`) AS `totalAtenciones`
                      FROM `orden_atencion`
                      WHERE `idPaciente` = '$idPaciente'
                      AND `estado` = 'ORDEN ATENCION'";
    $resAtenciones = mysqli_query($link, $sqlAtenciones) or die(mysqli_error($link));
    if(mysqli_num_rows($resAtenciones) > 0){
        $rowAtenciones = mysqli_fetch_array($resAtenciones);
        $totalAtenciones = (int) $rowAtenciones['totalAtenciones'];
    }

    $sqlCotizaciones = "SELECT COUNT(`idOrdenAtencion`) AS `totalCotizaciones`
                        FROM `orden_atencion`
                        WHERE `idPaciente` = '$idPaciente'
                        AND `estado` = 'COTIZACION'";
    $resCotizaciones = mysqli_query($link, $sqlCotizaciones) or die(mysqli_error($link));
    if(mysqli_num_rows($resCotizaciones) > 0){
        $rowCotizaciones = mysqli_fetch_array($resCotizaciones);
        $totalCotizaciones = (int) $rowCotizaciones['totalCotizaciones'];
    }

    $nombreCompleto = trim($nombres . " " . $apellidoPat . " " . $apellidoMat);
    $fechaNacimientoFmt = (!empty($fechaNacimiento) && $fechaNacimiento !== '0000-00-00')
        ? date('d/m/Y', strtotime($fechaNacimiento))
        : '-';
    $fechaAtencionFmt = (!empty($fechaAtencion) && $fechaAtencion !== '0000-00-00 00:00:00')
        ? date('d/m/Y H:i', strtotime($fechaAtencion))
        : '-';
    $fechaRegistroFmt = (!empty($fechaRegistro) && $fechaRegistro !== '0000-00-00 00:00:00')
        ? date('d/m/Y H:i', strtotime($fechaRegistro))
        : '-';
    $saldoPendienteFmt = number_format((float) $saldoPendiente, 2, '.', ',');

    //lista de registro del cuaderno de odontologia 

    echo "<div class='row g-3'>";
        echo "<div class='col-md-8'>";
            echo "<div class='card shadow-sm border-0'>";
                echo "<div class='card-header bg-primary text-white'>";
                    echo "<div class='d-flex justify-content-between align-items-start'>";
                        echo "<div>";
                            echo "<div class='fw-bold'>".htmlspecialchars($nombreCompleto)."</div>";
                            echo "<div class='small opacity-75'>CI: ".htmlspecialchars($ci)." | Nac.: ".htmlspecialchars($fechaNacimientoFmt)."</div>";
                        echo "</div>";
                        echo "<div class='text-end'>";
                            echo "<span class='badge bg-light text-dark me-1'>Especialidad: ".htmlspecialchars($especialidad)."</span>";
                            //echo "<span class='badge bg-warning text-dark'>Estado: ".htmlspecialchars($estadoAtencion)."</span>";
                        echo "</div>";
                    echo "</div>";
                echo "</div>";

                echo "<div class='card-body'>";
                    echo "<div class='row g-3'>";
                        echo "<div class='col-md-12'>";
                            echo "<h6 class='text-uppercase text-muted mb-2'>Datos del paciente</h6>";
                            echo "<div class='row g-2'>";
                                echo "<div class='col-md-6'>";
                                    echo "<div class='p-2 border rounded bg-light'>";
                                        echo "<div class='small text-muted'>Telefono</div>";
                                        echo "<div class='fw-semibold small'>".htmlspecialchars($celular)."</div>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-md-6'>";
                                    echo "<div class='p-2 border rounded bg-light'>";
                                        echo "<div class='small text-muted'>Email</div>";
                                        echo "<div class='fw-semibold small'>".htmlspecialchars($email)."</div>";
                                    echo "</div>";
                                echo "</div>";

                                echo "<div class='col-md-6'>";
                                    echo "<div class='p-2 border rounded bg-light'>";
                                        echo "<div class='small text-muted'>Procedencia</div>";
                                        echo "<div class='fw-semibold small'>".htmlspecialchars($procedencia)."</div>";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class='col-md-6'>";
                                    echo "<div class='p-2 border rounded bg-light'>";
                                        echo "<div class='small text-muted'>Residencia</div>";
                                        echo "<div class='fw-semibold small'>".htmlspecialchars($residencia)."</div>";
                                    echo "</div>";
                                echo "</div>";

                                echo "<div class='col-md-12'>";
                                    echo "<div class='p-2 border rounded bg-light'>";
                                        echo "<div class='small text-muted'>Direccion</div>";
                                        echo "<div class='fw-semibold small'>".htmlspecialchars($direccion)."</div>";
                                    echo "</div>";
                                echo "</div>";

                                echo "<div class='col-md-12'>";
                                    echo "<div class='p-2 border rounded bg-light'>";
                                        echo "<div class='small text-muted'>Tutor</div>";
                                        echo "<div class='fw-semibold small'>".htmlspecialchars($nombreTutor)."</div>";
                                        echo "<div class='small text-muted'>".htmlspecialchars($celularTutor)."</div>";
                                    echo "</div>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";
                    echo "</div>";

                    echo "<hr class='my-3'/>";

                    echo "<h6 class='text-uppercase text-muted mb-2'>Acciones clinicas</h6>";
                    echo "<div class='row g-2'>";
                        echo "<div class='col-6'>";
                            echo "<button class='btn btn-primary btn-sm w-100' id='btnFormExamenGeneral'><i class='fas fa-print'></i> Examen General</button>";
                        echo "</div>";
                        echo "<div class='col-6'>";
                            echo "<button class='btn btn-danger btn-sm w-100' id='btnFormRegistroTratamientos'><i class='fas fa-print'></i> Registro Odontologico</button>";
                        echo "</div>";
                        echo "<div class='col-6'>";
                            echo "<button class='btn btn-info btn-sm w-100' id='btnFormSolicitudProtesico'><i class='fas fa-print'></i> Solicitud Protesico</button>";
                        echo "</div>";
                        echo "<div class='col-6'>";
                            echo "<button class='btn btn-warning btn-sm w-100' id='btnFormRayoxX'><i class='fas fa-print'></i> Rayox X</button>";
                        echo "</div>";
                        echo "<div class='col-12'>";
                            echo "<button class='btn btn-outline-secondary btn-sm w-100' id='btnHistorialOdontologico'><i class='fas fa-list'></i> Historial Odontologico</button>";
                        echo "</div>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";

        echo "<div class='col-md-4'>";
            echo "<div class='card shadow-sm border-0 h-100'>";
                echo "<div class='card-header bg-white d-flex justify-content-between align-items-center'>";
                    echo "<b>Modulo Economico</b>";
                    echo "<span class='badge bg-warning text-dark'>Pendiente: Bs. ".htmlspecialchars($saldoPendienteFmt)."</span>";
                echo "</div>";
                echo "<div class='card-body'>";
                    echo "<h6 class='text-uppercase text-muted mb-2'>Resumen de cuenta</h6>";
                    echo "<div class='p-3 bg-light rounded mb-3'>";
                        echo "<div class='d-flex justify-content-between align-items-center'>";
                            echo "<span class='text-muted'>Saldo pendiente</span>";
                            echo "<span class='fw-bold'>Bs. ".htmlspecialchars($saldoPendienteFmt)."</span>";
                        echo "</div>";
                        echo "<div class='mt-3 d-flex gap-2 flex-wrap'>";
                            echo "<span class='badge bg-success'>Ordenes: ".htmlspecialchars((string)$totalAtenciones)."</span>";
                            echo "<span class='badge bg-info text-dark'>Cotizaciones: ".htmlspecialchars((string)$totalCotizaciones)."</span>";
                        echo "</div>";
                        echo "<hr class='my-3'/>";
                        echo "<div class='small text-muted'>";
                            echo "Fecha atencion: ".htmlspecialchars($fechaAtencionFmt)."<br/>";
                            echo "Fecha registro: ".htmlspecialchars($fechaRegistroFmt)."<br/>";
                            echo "Especialidad: ".htmlspecialchars($especialidad)."<br/>";
                        echo "</div>";
                    echo "</div>";

                    echo "<div class='d-grid gap-2'>";
                        // Cotizaciones: disponible desde el backend por estados (ORDEN ATENCION y COTIZACION)
                        echo "<button type='button' class='btn btn-outline-primary btn-sm' id='btnRegistroPrestaciones'><i class='fas fa-money-bill'></i> Registro Prestaciones</button>";
                        echo "<button type='button' class='btn btn-outline-danger btn-sm' id='btnRegistroPago'><i class='fas fa-money-bill'></i> Registro Pago</button>";
                    echo "</div>";

                    echo "<hr class='my-3'>";
                    echo "<div class='small text-muted'>";
                        echo "Las estadisticas se calculan segun estados actuales del paciente.";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
    
    echo "<div id='divCuadernoOdontologia'>";
    echo "</div>";



}


function detalleCuentaPaciente(){
    global $link;
    global $input;

    $idPaciente = isset($input['idPaciente']) ? mysqli_real_escape_string($link, $input['idPaciente']) : '';

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Detalle de cuenta / Historial de pagos</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body table-responsive'>";

    if ($idPaciente === '') {
        echo "<div class='alert alert-warning mb-0'>No se recibió el paciente.</div>";
        return;
    }

    $sql = "SELECT `idOrdenAtencion`, `fechaHoraRegistro`, `codTratamiento`, `descripcionTratamiento`, `precioTratamiento`, `estado`, `fechaHoraPago` 
            FROM `orden_atencion` 
            WHERE `idPaciente` = '$idPaciente' 
            ORDER BY `fechaHoraRegistro` DESC";
    $result = mysqli_query($link, $sql);
    if (!$result) {
        echo "<div class='alert alert-danger mb-0'>Error al cargar el historial.</div>";
        return;
    }

    $totalPendiente = 0;
    $filas = [];
    while ($row = mysqli_fetch_array($result)) {
        $filas[] = $row;
        if ($row['estado'] === 'PENDIENTE' && (empty($row['fechaHoraPago']) || $row['fechaHoraPago'] === null)) {
            $totalPendiente += (float) $row['precioTratamiento'];
        }
    }

    if (count($filas) === 0) {
        echo "<p class='text-muted mb-0'>No hay registros de órdenes de atención para este paciente.</p>";
        echo "</div>";
        return;
    }

    echo "<p class='mb-2'><strong>Saldo pendiente: Bs. " . number_format($totalPendiente, 2) . "</strong></p>";
    echo "<table class='table table-sm table-striped table-hover align-middle mb-0'>";
        echo "<thead class='table-light'>";
            echo "<tr>";
                echo "<th>Fecha registro</th>";
                echo "<th>Código</th>";
                echo "<th>Descripción</th>";
                echo "<th class='text-end'>Monto (Bs.)</th>";
                echo "<th>Estado</th>";
                echo "<th>Fecha pago</th>";
                echo "<th>Acciones</th>";
            echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        foreach ($filas as $row) {
            $estadoBadge = $row['estado'] === 'PAGADO' ? 'bg-success' : 'bg-warning text-dark';
            $fechaPago = (!empty($row['fechaHoraPago']) && $row['fechaHoraPago'] !== '0000-00-00 00:00:00')
                ? date('d/m/Y H:i', strtotime($row['fechaHoraPago']))
                : '-';
            echo "<tr>";
                echo "<td>" . date('d/m/Y H:i', strtotime($row['fechaHoraRegistro'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['codTratamiento']) . "</td>";
                echo "<td>" . htmlspecialchars($row['descripcionTratamiento']) . "</td>";
                echo "<td class='text-end'>" . number_format((float) $row['precioTratamiento'], 2) . "</td>";
                echo "<td><span class='badge " . $estadoBadge . "'>" . htmlspecialchars($row['estado']) . "</span></td>";
                echo "<td>" . $fechaPago . "</td>";
                echo "<td>";
                    if($row['estado'] === 'PENDIENTE'){
                        echo "<button class='btn btn-xs btn-danger btnPagarOrden' id='" . htmlspecialchars($row['idOrdenAtencion']) . "'><i class='fas fa-money-bill'></i> Pagar</button>";
                    }
                    else{
                        $conIdRecibo = mysqli_query($link, "SELECT `idRecibo` FROM `recibos` WHERE `idOrdenAtencion` = '" . htmlspecialchars($row['idOrdenAtencion']) . "'")or die(mysqli_error($link));
                        if(mysqli_num_rows($conIdRecibo) > 0){
                            $rowIdRecibo = mysqli_fetch_array($conIdRecibo);
                            $idRecibo = $rowIdRecibo['idRecibo'];
                        }
                        echo "<button class='btn btn-xs btn-success btnVerRecibo' id='" . htmlspecialchars($idRecibo) . "'><i class='fas fa-eye'></i> Ver Recibo</button>";
                    }
                echo "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
    echo "</table>";
    echo "</div>";
}


function pagarOrden(){
    date_default_timezone_set('America/La_Paz');
    global $link;
    global $input;
    $idOrdenAtencion = isset($input['idOrdenAtencion']) ? mysqli_real_escape_string($link, $input['idOrdenAtencion']) : '';
    $idPaciente = isset($input['idPaciente']) ? mysqli_real_escape_string($link, $input['idPaciente']) : '';
    $idUsuario = $_SESSION['idUsuario_clinicloud'];
    $fechaHoraPago = date('Y-m-d H:i:s');

    $updateOrdenAtencion = mysqli_query($link, "UPDATE `orden_atencion` SET `estado`='PAGADO',`fechaHoraPago`='$fechaHoraPago',`idUsuarioPago`='$idUsuario' WHERE `idOrdenAtencion` = '$idOrdenAtencion'") or die(mysqli_error($link));
    if($updateOrdenAtencion){

        //registrar recibo
        /*INSERT INTO `recibos`(`idPaciente`, `idUsuario`, `codTratamiento`, `descripcionTratamiento`, `montoPagado`, `fechaRegistro`, `estadoRecibo`, `idOrdenAtencion`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]')*/
        $idUsuario = $_SESSION['idUsuario_clinicloud'];
        $conOrdenAtencion = mysqli_query($link, "SELECT `codTratamiento`, `descripcionTratamiento`, `precioTratamiento` FROM `orden_atencion` WHERE `idOrdenAtencion` = '$idOrdenAtencion'") or die(mysqli_error($link));
        if(mysqli_num_rows($conOrdenAtencion) > 0){
            $rowOrdenAtencion = mysqli_fetch_array($conOrdenAtencion);
            $codTratamiento = $rowOrdenAtencion['codTratamiento'];
            $descripcionTratamiento = $rowOrdenAtencion['descripcionTratamiento'];
            $precioTratamiento = $rowOrdenAtencion['precioTratamiento'];
        }
        $insertRecibo = mysqli_query($link, "INSERT INTO `recibos`(`idPaciente`, `idUsuario`, `codTratamiento`, `descripcionTratamiento`, `montoPagado`, `fechaRegistro`, `estadoRecibo`, `idOrdenAtencion`) VALUES ('$idPaciente', '$idUsuario', '$codTratamiento', '$descripcionTratamiento', '$precioTratamiento', '$fechaHoraPago', 'PAGADO', '$idOrdenAtencion')") or die(mysqli_error($link));
        
        if($insertRecibo){
            $idRecibo = mysqli_insert_id($link);
            echo json_encode(["estado" => "OK", "mensaje" => "Recibo registrado correctamente", "idRecibo" => $idRecibo]);
        }
        else{
            echo json_encode(["estado" => "ERROR", "mensaje" => "Error al registrar el recibo", "idRecibo" => '']);
        }
    }
    else{
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al obtener la orden de atención", "idRecibo" => '']);
    }
}


function verRecibo(){
    date_default_timezone_set('America/La_Paz');
    global $link;
    global $input;
    $idRecibo = isset($input['idRecibo']) ? mysqli_real_escape_string($link, $input['idRecibo']) : '';

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Recibo</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";

    if ($idRecibo === '') {
        echo "<div class='alert alert-warning mb-0'>No se recibió el ID del recibo.</div>";
        echo "</div>";
        echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
        echo "</div>";
        return;
    }

    // Consulta recibo
    $sqlRecibo = "SELECT `idRecibo`, `idPaciente`, `idUsuario`, `codTratamiento`, `descripcionTratamiento`, `montoPagado`, `fechaRegistro`, `estadoRecibo`, `idOrdenAtencion` FROM `recibos` WHERE `idRecibo` = '$idRecibo'";
    $resRecibo = mysqli_query($link, $sqlRecibo);
    if (!$resRecibo || mysqli_num_rows($resRecibo) === 0) {
        echo "<div class='alert alert-warning mb-0'>No se encontró el recibo.</div>";
        echo "</div>";
        echo "<div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cerrar</button></div>";
        return;
    }
    $rowRecibo = mysqli_fetch_array($resRecibo);
    $idPaciente = $rowRecibo['idPaciente'];
    $idUsuarioRecibo = $rowRecibo['idUsuario'];
    $codTratamiento = htmlspecialchars($rowRecibo['codTratamiento'] ?? '', ENT_QUOTES, 'UTF-8');
    $descripcionTratamiento = htmlspecialchars($rowRecibo['descripcionTratamiento'] ?? '', ENT_QUOTES, 'UTF-8');
    $montoPagado = (float) ($rowRecibo['montoPagado'] ?? 0);
    $fechaRegistro = $rowRecibo['fechaRegistro'] ?? '';
    $fechaRegistroFmt = $fechaRegistro ? date('d/m/Y H:i', strtotime($fechaRegistro)) : '-';
    $idOrdenAtencion = $rowRecibo['idOrdenAtencion'];

    // Consulta paciente
    $sqlPaciente = "SELECT `idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `email`, `direccion` FROM `pacientes` WHERE `idPaciente` = '$idPaciente'";
    $resPaciente = mysqli_query($link, $sqlPaciente);
    $nombrePaciente = '-';
    $ciPaciente = '-';
    $celularPaciente = '-';
    if ($resPaciente && mysqli_num_rows($resPaciente) > 0) {
        $rowPac = mysqli_fetch_array($resPaciente);
        $ap = trim($rowPac['apellidoPat'] ?? '');
        $am = trim($rowPac['apellidoMat'] ?? '');
        $nom = trim($rowPac['nombres'] ?? '');
        $nombrePaciente = trim($ap . ' ' . $am . ' ' . $nom) ?: '-';
        $ciPaciente = htmlspecialchars(trim($rowPac['ci'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-';
        $celularPaciente = htmlspecialchars(trim($rowPac['celular'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-';
    }

    // Consulta usuario (quien registró el pago)
    $sqlUsuario = "SELECT `nombreUs`, `primerApUs`, `segundoApUs` FROM `usuarios` WHERE `idUsuario` = '$idUsuarioRecibo'";
    $resUsuario = mysqli_query($link, $sqlUsuario);
    $nombreUsuario = '-';
    if ($resUsuario && mysqli_num_rows($resUsuario) > 0) {
        $rowUs = mysqli_fetch_array($resUsuario);
        $nombreUsuario = trim(($rowUs['nombreUs'] ?? '') . ' ' . ($rowUs['primerApUs'] ?? '') . ' ' . ($rowUs['segundoApUs'] ?? ''));
        if ($nombreUsuario === '') $nombreUsuario = '-';
    }

    // HTML compacto para el PDF (media hoja carta: 215.9 x 139.7 mm)
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; margin: 0; padding: 8px; color: #333; }
            .titulo { font-size: 12pt; font-weight: bold; text-align: center; margin-bottom: 6px; border-bottom: 1px solid #333; padding-bottom: 4px; }
            .bloque { margin-bottom: 6px; }
            .bloque label { font-weight: bold; display: inline-block; width: 28%; }
            .bloque span { display: inline-block; width: 70%; }
            .detalle { margin-top: 8px; border: 1px solid #ccc; padding: 6px; }
            .detalle table { width: 100%; border-collapse: collapse; font-size: 8pt; }
            .detalle th { text-align: left; padding: 3px 4px; border-bottom: 1px solid #ddd; }
            .detalle td { padding: 3px 4px; }
            .total { text-align: right; font-weight: bold; font-size: 10pt; margin-top: 6px; }
            .pie { margin-top: 8px; font-size: 7pt; color: #666; text-align: center; }
        </style>
    </head>
    <body>
        <div class="titulo">RECIBO DE PAGO</div>
        <div class="bloque"><label>Paciente:</label><span>' . $nombrePaciente . '</span></div>
        <div class="bloque"><label>CI:</label><span>' . $ciPaciente . '</span></div>
        <div class="bloque"><label>Celular:</label><span>' . $celularPaciente . '</span></div>
        <div class="bloque"><label>Fecha pago:</label><span>' . $fechaRegistroFmt . '</span></div>
        <div class="detalle">
            <table>
                <tr><th>Código</th><th>Descripción</th><th style="text-align:right;">Monto (Bs.)</th></tr>
                <tr><td>' . $codTratamiento . '</td><td>' . $descripcionTratamiento . '</td><td style="text-align:right;">' . number_format($montoPagado, 2) . '</td></tr>
            </table>
            <div class="total">Total: Bs. ' . number_format($montoPagado, 2) . '</div>
        </div>
        <div class="pie">Recibo Nº ' . (int)$idRecibo . ' &ndash; Atendido por: ' . $nombreUsuario . ' &ndash; CliniCloud</div>
    </body>
    </html>';

    $rutaPDF = '';
    $dirTemp = __DIR__ . '/../../storage/temp';
    if (!is_dir($dirTemp)) {
        @mkdir($dirTemp, 0755, true);
    }
    require_once __DIR__ . '/../../vendor/autoload.php';
    try {
        // Media hoja carta: 215.9 x 139.7 mm
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [215.9, 139.7],
            'orientation' => 'P',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => $dirTemp
        ]);
        $mpdf->WriteHTML($html);
        $nombreArchivo = 'recibo_' . $idRecibo . '_' . time() . '.pdf';
        $rutaArchivo = $dirTemp . '/' . $nombreArchivo;
        $mpdf->Output($rutaArchivo, 'F');
        $rutaPDF = obtenerRutaBaseProyecto() . '/storage/temp/' . $nombreArchivo;
    } catch (\Exception $e) {
        echo "<div class='alert alert-danger mb-0'>Error al generar PDF: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "</div>";
        echo "<div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cerrar</button></div>";
        return;
    }

    echo "<iframe src='" . htmlspecialchars($rutaPDF) . "' style='width:100%; height:420px; border:1px solid #ddd;' frameborder='0'></iframe>";
    echo "<p class='mt-2 mb-0 small text-muted'>Puede imprimir o guardar desde el visor del PDF.</p>";
    echo "</div>";
    echo "<div class='modal-footer'>";
    echo "<a href='" . htmlspecialchars($rutaPDF) . "' target='_blank' class='btn btn-primary waves-effect waves-light'><i class='fas fa-external-link-alt'></i> Abrir / Descargar</a>";
    echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";
}


