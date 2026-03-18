<?php
session_start();

// SI NO HAY SESIÓN, DETENER TODO ANTES DE INCLUIR O PROCESAR NADA
if (!isset($_SESSION['idUsuario_TikeartePOS'])) {
    // Forzar JSON limpio si se trata de una petición AJAX (fetch)
    header('Content-Type: application/json');
    echo json_encode(['sesion' => 'cerrada']);
    exit;
}

require_once "../../config_db_mysql.php";
require_once "../../get_parametros.php";

require_once '../../phpqrcode/qrlib.php';

require_once '../../api_log.php';

header("Content-Type: application/json");

// Leer datos enviados desde fetch
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

if (!isset($input['funcion'])) {
    echo json_encode(["estado" => "ERROR", "mensaje" => "No se especificó la función a ejecutar."]);
    exit;
}

switch ($input['funcion']) {
    case "formParametrosReporteTickets":
        registrarLog("INFO", $_SESSION['usuarioUs_TikeartePOS']." - Llamada a función: formParametrosReporteTickets");
        formParametrosReporteTickets();
        break;
    case "generarReporte":
        registrarLog("INFO", $_SESSION['usuarioUs_TikeartePOS']." - Llamada a función: generarReporte");
        generarReporte();
        break;
    case "verTicket":
        registrarLog("INFO", $_SESSION['usuarioUs_TikeartePOS']." - Llamada a función: verTicket");
        verTicket();
        break;
    default:
        registrarLog("ERROR", "Usuario: ".$_SESSION['usuarioUs_TikeartePOS']." - Función no reconocida: ".$input['funcion']);
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

/**
 * Función para obtener la URL base del sistema
 * Detecta automáticamente si está en localhost con subdirectorio o en un dominio/subdominio
 * @return string URL base completa (ejemplo: http://localhost/TikeArtePOS o https://mipos.dominio.com)
 */
function obtenerUrlBase() {
    // Detectar el protocolo (http o https)
    $protocol = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        $_SERVER['SERVER_PORT'] == 443 ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        $protocol = 'https';
    }
    
    // Obtener el host (ejemplo: localhost, mipos.dominio.com)
    $host = $_SERVER['HTTP_HOST'];
    
    // Obtener la ruta base del proyecto
    // $_SERVER['SCRIPT_NAME'] contiene algo como: /TikeArtePOS/modulos/reportes/fn_reporte_tickets.php
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    // Extraer el directorio base eliminando la parte del script específico
    // Removemos "/modulos/reportes/fn_reporte_tickets.php" para obtener "/TikeArtePOS"
    $pathParts = explode('/', trim($scriptName, '/'));
    
    // Si hay más de 2 niveles (modulos/reportes/archivo.php), el primer elemento es el subdirectorio base
    // Si hay 2 o menos niveles, no hay subdirectorio
    $baseDir = '';
    if (count($pathParts) > 2) {
        $baseDir = '/' . $pathParts[0];
    }
    
    // Construir la URL base completa
    $urlBase = $protocol . '://' . $host . $baseDir;
    
    return $urlBase;
}

function formParametrosReporteTickets(){
    global $link;
    global $input;

    date_default_timezone_set('America/La_Paz');

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border-0 shadow-sm' style='border-radius: 8px;'>";
                echo "<div class='card-header bg-primary text-white'>";
                    echo "<h6 class='mb-0'><i class='fas fa-filter me-2'></i>Parámetros del Reporte de Tickets</h6>";
                echo "</div>";
                echo "<div class='card-body p-3'>";
                    echo "<div class='row g-2'>";
                        // Primera fila - Fechas y Tipo de reporte
                        echo "<div class='col-md-3 col-sm-6'>";
                            echo "<label for='fechaInicio' class='form-label small mb-1'><i class='fas fa-calendar-alt me-1'></i>Fecha inicio</label>";
                            echo "<input type='date' class='form-control form-control-sm' id='fechaInicio' name='fechaInicio' value='".date('Y-m-d')."'>";
                        echo "</div>";
                        echo "<div class='col-md-3 col-sm-6'>";
                            echo "<label for='fechaFinal' class='form-label small mb-1'><i class='fas fa-calendar-check me-1'></i>Fecha final</label>";
                            echo "<input type='date' class='form-control form-control-sm' id='fechaFinal' name='fechaFinal' value='".date('Y-m-d')."'>";
                        echo "</div>";

                        echo "<div class='col-md-3 col-sm-6'>";
                            echo "<label for='tipoReporte' class='form-label small mb-1'><i class='fas fa-file-chart-line me-1'></i>Tipo de reporte</label>";
                            echo "<select class='form-select form-select-sm' id='tipoReporte' name='tipoReporte' required>";
                                echo "<option value='RESUMEN_TICKETS'>Resumen Tickets</option>";
                                echo "<option value='DETALLADO_TICKETS'>Detallado Tickets</option>";
                            echo "</select>";
                        echo "</div>";

                        echo "<div class='col-md-3 col-sm-6'>";
                            echo "<label for='usuario' class='form-label small mb-1'><i class='fas fa-user me-1'></i>Usuario</label>";
                            echo "<select class='form-select form-select-sm select2-multiple' id='usuario' name='usuario[]' multiple>";
                                $conUsuarios = mysqli_query($link, "SELECT DISTINCT t.usuario, u.usuarioUs, u.nombreUs, u.primerApUs, u.segundoApUs FROM tickets t LEFT JOIN usuarios u ON u.idUsuario = t.usuario ORDER BY u.usuarioUs ASC")or die(mysqli_error($link));
                                if(mysqli_num_rows($conUsuarios) > 0){
                                    while($rowUsuario = mysqli_fetch_array($conUsuarios)){
                                        $usuario = $rowUsuario['usuario'];
                                        $nombreUsuario = $rowUsuario['usuarioUs'] ? $rowUsuario['usuarioUs'] : "Usuario ".$usuario;
                                        echo "<option value='".$usuario."'>".$nombreUsuario."</option>";
                                    }
                                }
                            echo "</select>";
                        echo "</div>";

                        echo "<div class='col-md-3 col-sm-6'>";
                            echo "<label for='genero' class='form-label small mb-1'><i class='fas fa-venus-mars me-1'></i>Género</label>";
                            echo "<select class='form-select form-select-sm select2-multiple' id='genero' name='genero[]' multiple>";
                                $conGeneros = mysqli_query($link, "SELECT DISTINCT `generoTicket` FROM `tickets` WHERE `generoTicket` != '' GROUP BY `generoTicket` ORDER BY `generoTicket` ASC")or die(mysqli_error($link));
                                if(mysqli_num_rows($conGeneros) > 0){
                                    while($rowGenero = mysqli_fetch_array($conGeneros)){
                                        echo "<option value='".$rowGenero['generoTicket']."'>".$rowGenero['generoTicket']."</option>";
                                    }
                                }
                            echo "</select>";
                        echo "</div>";

                        echo "<div class='col-md-3 col-sm-6'>";
                            echo "<label for='nacionalidad' class='form-label small mb-1'><i class='fas fa-globe me-1'></i>Nacionalidad</label>";
                            echo "<select class='form-select form-select-sm select2-multiple' id='nacionalidad' name='nacionalidad[]' multiple>";
                                $conNacionalidades = mysqli_query($link, "SELECT DISTINCT `nacionalidadTIcket` FROM `tickets` WHERE `nacionalidadTIcket` != '' GROUP BY `nacionalidadTIcket` ORDER BY `nacionalidadTIcket` ASC")or die(mysqli_error($link));
                                if(mysqli_num_rows($conNacionalidades) > 0){
                                    while($rowNacionalidad = mysqli_fetch_array($conNacionalidades)){
                                        echo "<option value='".$rowNacionalidad['nacionalidadTIcket']."'>".$rowNacionalidad['nacionalidadTIcket']."</option>";
                                    }
                                }
                            echo "</select>";
                        echo "</div>";

                        echo "<div class='col-md-3 col-sm-6'>";
                            echo "<label for='controlIngreso' class='form-label small mb-1'><i class='fas fa-check-circle me-1'></i>Control Ingreso</label>";
                            echo "<select class='form-select form-select-sm select2-multiple' id='controlIngreso' name='controlIngreso[]' multiple>";
                                $conControl = mysqli_query($link, "SELECT DISTINCT `controlIngreso` FROM `tickets` WHERE `controlIngreso` != '' GROUP BY `controlIngreso` ORDER BY `controlIngreso` ASC")or die(mysqli_error($link));
                                if(mysqli_num_rows($conControl) > 0){
                                    while($rowControl = mysqli_fetch_array($conControl)){
                                        echo "<option value='".$rowControl['controlIngreso']."'>".$rowControl['controlIngreso']."</option>";
                                    }
                                }
                            echo "</select>";
                        echo "</div>";
                        echo "<div class='col-md-3 col-sm-6'>";
                            echo "<label for='nombreTicket' class='form-label small mb-1'><i class='fas fa-check-circle me-1'></i>Nombre Ticket</label>";
                            echo "<select class='form-select form-select-sm select2-multiple' id='nombreTicket' name='nombreTicket[]' multiple>";
                                $conNombreTicket = mysqli_query($link, "SELECT DISTINCT `nombreTicket` FROM `tickets` WHERE `nombreTicket` != '' GROUP BY `nombreTicket` ORDER BY `nombreTicket` ASC")or die(mysqli_error($link));
                                if(mysqli_num_rows($conNombreTicket) > 0){
                                    while($rowNombreTicket = mysqli_fetch_array($conNombreTicket)){
                                        echo "<option value='".$rowNombreTicket['nombreTicket']."'>".$rowNombreTicket['nombreTicket']."</option>";
                                    }
                                }
                            echo "</select>";
                        echo "</div>";
                        
                        // Botones de acción
                        echo "<div class='col-md-12 col-sm-12 text-center'>";
                            echo "<div class='btn-group' role='group'>";
                                echo "<button class='btn btn-sm btn-primary' id='btnGenerarReporteHtml' title='Generar HTML'><i class='fas fa-file-alt'></i> WEB</button>";
                                echo "<button class='btn btn-sm btn-success' id='btnGenerarReporteExcel' title='Generar Excel'><i class='fas fa-file-excel'></i> EXCEL</button>";
                                echo "<button class='btn btn-sm btn-pdf' id='btnGenerarReportePdf' title='Generar PDF'><i class='fas fa-file-pdf'></i> PDF</button>";
                            echo "</div>";
                        echo "</div>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
    echo "<div class='row mt-2' id='contenidoReporte'></div>";
}


function generarReporte(){
    global $link;
    global $input;

    $datosReporte = $input['datosReporte'];

    $fechaInicio = $datosReporte['fechaInicio'];
    $fechaFinal = $datosReporte['fechaFinal'];
    $usuario = $datosReporte['usuario'];
    $tipoReporte = $datosReporte['tipoReporte'];
    $formato = $datosReporte['formato'];
    $genero = $datosReporte['genero'];
    $nacionalidad = $datosReporte['nacionalidad'];
    $controlIngreso = $datosReporte['controlIngreso'];
    $nombreTicket = isset($datosReporte['nombreTicket']) ? $datosReporte['nombreTicket'] : array();

    registrarLog("INFO", $_SESSION['usuarioUs_TikeartePOS']." - Generando reporte: ".$tipoReporte." - Formato: ".$formato." - Período: ".$fechaInicio." a ".$fechaFinal);

    echo "<div class='col-md-12'>";
        echo "<div class='card border'>";
            echo "<div class='card-header'>";
                echo "<b>Generando Reporte</b>";
            echo "</div>";
            echo "<div class='card-body'>";
                echo "<div class='row'>";
                    switch($formato){
                        case "HTML":
                            generarReporteHTML($fechaInicio, $fechaFinal, $usuario, $tipoReporte, $genero, $nacionalidad, $controlIngreso, $nombreTicket);
                            break;
                        case "PDF":
                            generarReportePDF($fechaInicio, $fechaFinal, $usuario, $tipoReporte, $genero, $nacionalidad, $controlIngreso, $nombreTicket);
                            break;
                        case "EXCEL":
                            generarReporteEXCEL($fechaInicio, $fechaFinal, $usuario, $tipoReporte, $genero, $nacionalidad, $controlIngreso, $nombreTicket);
                            break;
                    }
                
                echo "</div>";
            echo "</div>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}


function generarReporteHTML($fechaInicio, $fechaFinal, $usuario, $tipoReporte, $genero, $nacionalidad, $controlIngreso, $nombreTicket = array()){
    global $link;
    global $input;

    switch($tipoReporte){
        case "RESUMEN_TICKETS":
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover' id='tablaReporte'>";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th>Nro. Ticket</th>";
                            echo "<th>Gestión</th>";
                            echo "<th>Mes</th>";
                            echo "<th>Fecha Registro</th>";
                            echo "<th>Hora Registro</th>";
                            echo "<th>Código Ticket</th>";
                            echo "<th>Nombre Ticket</th>";
                            echo "<th>Usuario</th>";
                            echo "<th>Precio</th>";
                            echo "<th>Descuento</th>";
                            echo "<th>Total</th>";
                            echo "<th>Género</th>";
                            echo "<th>Edad</th>";
                            echo "<th>Nacionalidad</th>";
                            echo "<th>Control Ingreso</th>";
                            echo "<th>Acciones</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";

                        $condicionUsuario = "";
                        if(is_array($usuario) && count($usuario) > 0){
                            $usuariosEscapados = array_map(function($u) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $u) . "'"; 
                            }, $usuario);
                            $condicionUsuario = " AND t.usuario IN (" . implode(',', $usuariosEscapados) . ") ";
                        } else if(!is_array($usuario) && $usuario != "0" && $usuario != ""){
                            $condicionUsuario = " AND t.usuario = '" . mysqli_real_escape_string($link, $usuario) . "' ";
                        }

                        $condicionGenero = "";
                        if(is_array($genero) && count($genero) > 0){
                            $generosEscapados = array_map(function($g) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $g) . "'"; 
                            }, $genero);
                            $condicionGenero = " AND t.generoTicket IN (" . implode(',', $generosEscapados) . ") ";
                        } else if(!is_array($genero) && $genero != "0" && $genero != ""){
                            $condicionGenero = " AND t.generoTicket = '" . mysqli_real_escape_string($link, $genero) . "' ";
                        }

                        $condicionNacionalidad = "";
                        if(is_array($nacionalidad) && count($nacionalidad) > 0){
                            $nacionalidadesEscapadas = array_map(function($n) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $n) . "'"; 
                            }, $nacionalidad);
                            $condicionNacionalidad = " AND t.nacionalidadTIcket IN (" . implode(',', $nacionalidadesEscapadas) . ") ";
                        } else if(!is_array($nacionalidad) && $nacionalidad != "0" && $nacionalidad != ""){
                            $condicionNacionalidad = " AND t.nacionalidadTIcket = '" . mysqli_real_escape_string($link, $nacionalidad) . "' ";
                        }

                        $condicionControlIngreso = "";
                        if(is_array($controlIngreso) && count($controlIngreso) > 0){
                            $controlesEscapados = array_map(function($c) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $c) . "'"; 
                            }, $controlIngreso);
                            $condicionControlIngreso = " AND t.controlIngreso IN (" . implode(',', $controlesEscapados) . ") ";
                        } else if(!is_array($controlIngreso) && $controlIngreso != "0" && $controlIngreso != ""){
                            $condicionControlIngreso = " AND t.controlIngreso = '" . mysqli_real_escape_string($link, $controlIngreso) . "' ";
                        }

                        $condicionNombreTicket = "";
                        if(is_array($nombreTicket) && count($nombreTicket) > 0){
                            $nombresTicketEscapados = array_map(function($n) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $n) . "'"; 
                            }, $nombreTicket);
                            $condicionNombreTicket = " AND t.nombreTicket IN (" . implode(',', $nombresTicketEscapados) . ") ";
                        } else if(!is_array($nombreTicket) && $nombreTicket != "0" && $nombreTicket != ""){
                            $condicionNombreTicket = " AND t.nombreTicket = '" . mysqli_real_escape_string($link, $nombreTicket) . "' ";
                        }

                        $cantidadTickets = 0;
                        $sumaTotalTickets = 0;
                        
                        $sql = "SELECT
                                    t.idTicket,
                                    t.numeroTicket,
                                    t.codTicket,
                                    t.nombreTicket,
                                    t.precio,
                                    t.descuento,
                                    t.fechaReg,
                                    t.horaReg,
                                    t.usuario,
                                    t.hash,
                                    t.guiaTicket,
                                    t.generoTicket,
                                    t.edadTicket,
                                    t.nacionalidadTIcket,
                                    t.controlIngreso,
                                    u.usuarioUs
                                    FROM tickets AS t
                                    LEFT JOIN usuarios AS u ON u.idUsuario = t.usuario
                                    WHERE t.fechaReg >= '$fechaInicio'
                                    AND t.fechaReg <= '$fechaFinal'
                                    $condicionUsuario
                                    $condicionGenero
                                    $condicionNacionalidad
                                    $condicionControlIngreso
                                    $condicionNombreTicket
                                    ORDER BY t.fechaReg DESC, t.horaReg DESC";

                        $conTickets = mysqli_query($link, $sql)or die(mysqli_error($link));
                        if(mysqli_num_rows($conTickets) > 0){
                            while($rowTicket = mysqli_fetch_array($conTickets)){
                                $cantidadTickets++;
                                $totalTicket = $rowTicket['precio'] - $rowTicket['descuento'];
                                $sumaTotalTickets += $totalTicket;
                                echo "<tr>";
                                    echo "<td>".$rowTicket['numeroTicket']."</td>";
                                    $gestion = date('Y', strtotime($rowTicket['fechaReg']));
                                    echo "<td>".$gestion."</td>";
                                    $fechaReg = strtotime($rowTicket['fechaReg']);
                                    $meses = [
                                        1 => 'ENERO',
                                        2 => 'FEBRERO',
                                        3 => 'MARZO',
                                        4 => 'ABRIL',
                                        5 => 'MAYO',
                                        6 => 'JUNIO',
                                        7 => 'JULIO',
                                        8 => 'AGOSTO',
                                        9 => 'SEPTIEMBRE',
                                        10 => 'OCTUBRE',
                                        11 => 'NOVIEMBRE',
                                        12 => 'DICIEMBRE'
                                    ];
                                    $numeroMes = (int)date('n', $fechaReg);
                                    $mes = $meses[$numeroMes];
                                    echo "<td>".$mes."</td>";
                                    echo "<td>".$rowTicket['fechaReg']."</td>";
                                    echo "<td>".$rowTicket['horaReg']."</td>";
                                    echo "<td>".$rowTicket['codTicket']."</td>";
                                    echo "<td>".$rowTicket['nombreTicket']."</td>";
                                    echo "<td>".($rowTicket['usuarioUs'] ? $rowTicket['usuarioUs'] : "Usuario ".$rowTicket['usuario'])."</td>";
                                    echo "<td class='text-end'>".number_format($rowTicket['precio'], 2, '.', ',')."</td>";
                                    echo "<td class='text-end'>".number_format($rowTicket['descuento'], 2, '.', ',')."</td>";
                                    echo "<td class='text-end'><strong>".number_format($totalTicket, 2, '.', ',')."</strong></td>";
                                    echo "<td>".$rowTicket['generoTicket']."</td>";
                                    echo "<td>".$rowTicket['edadTicket']."</td>";
                                    echo "<td>".$rowTicket['nacionalidadTIcket']."</td>";
                                    echo "<td>".$rowTicket['controlIngreso']."</td>";
                                    echo "<td class='text-center'>";
                                        echo "<button class='btn btn-primary btn-xs btnVerTicket' id='".$rowTicket['hash']."' title='Ver Ticket'><i class='fas fa-file-alt'></i></button>";
                                    echo "</td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody>";
                echo "</table>";

                
            echo "</div>";
            
            echo "<div class='col-md-12 m-auto'>";
                echo "<div class='row mt-3 mb-2'>";
                    echo "<div class='col-md-3 col-6 mb-2'>";
                        echo "<div class='card border-primary h-100'>";
                            echo "<div class='card-body text-center'>";
                                echo "<h6 class='card-title mb-1'>Total Tickets</h6>";
                                echo "<span class='display-6 text-primary'>".$cantidadTickets."</span>";
                            echo "</div>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-3 col-6 mb-2'>";
                        echo "<div class='card border-success h-100'>";
                            echo "<div class='card-body text-center'>";
                                echo "<h6 class='card-title mb-1'>Total Ingresos</h6>";
                                echo "<span class='display-6 text-success'>".number_format($sumaTotalTickets, 2, '.', ',')."</span>";
                            echo "</div>";
                        echo "</div>";  
                    echo "</div>";
                echo "</div>";
            echo "</div>";


        break;

        case "DETALLADO_TICKETS":
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover' id='tablaReporte'>";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th>Nro. Ticket</th>";
                            echo "<th>Gestión</th>";
                            echo "<th>Mes</th>";
                            echo "<th>Fecha Registro</th>";
                            echo "<th>Hora Registro</th>";
                            echo "<th>Código Ticket</th>";
                            echo "<th>Nombre Ticket</th>";
                            echo "<th>Usuario</th>";
                            echo "<th>Precio</th>";
                            echo "<th>Descuento</th>";
                            echo "<th>Total</th>";
                            echo "<th>Género</th>";
                            echo "<th>Edad</th>";
                            echo "<th>Nacionalidad</th>";
                            echo "<th>Guía Ticket</th>";
                            echo "<th>Control Ingreso</th>";
                            echo "<th>Hash</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";

                        $condicionUsuario = "";
                        if(is_array($usuario) && count($usuario) > 0){
                            $usuariosEscapados = array_map(function($u) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $u) . "'"; 
                            }, $usuario);
                            $condicionUsuario = " AND t.usuario IN (" . implode(',', $usuariosEscapados) . ") ";
                        } else if(!is_array($usuario) && $usuario != "0" && $usuario != ""){
                            $condicionUsuario = " AND t.usuario = '" . mysqli_real_escape_string($link, $usuario) . "' ";
                        }

                        $condicionGenero = "";
                        if(is_array($genero) && count($genero) > 0){
                            $generosEscapados = array_map(function($g) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $g) . "'"; 
                            }, $genero);
                            $condicionGenero = " AND t.generoTicket IN (" . implode(',', $generosEscapados) . ") ";
                        } else if(!is_array($genero) && $genero != "0" && $genero != ""){
                            $condicionGenero = " AND t.generoTicket = '" . mysqli_real_escape_string($link, $genero) . "' ";
                        }

                        $condicionNacionalidad = "";
                        if(is_array($nacionalidad) && count($nacionalidad) > 0){
                            $nacionalidadesEscapadas = array_map(function($n) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $n) . "'"; 
                            }, $nacionalidad);
                            $condicionNacionalidad = " AND t.nacionalidadTIcket IN (" . implode(',', $nacionalidadesEscapadas) . ") ";
                        } else if(!is_array($nacionalidad) && $nacionalidad != "0" && $nacionalidad != ""){
                            $condicionNacionalidad = " AND t.nacionalidadTIcket = '" . mysqli_real_escape_string($link, $nacionalidad) . "' ";
                        }

                        $condicionControlIngreso = "";
                        if(is_array($controlIngreso) && count($controlIngreso) > 0){
                            $controlesEscapados = array_map(function($c) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $c) . "'"; 
                            }, $controlIngreso);
                            $condicionControlIngreso = " AND t.controlIngreso IN (" . implode(',', $controlesEscapados) . ") ";
                        } else if(!is_array($controlIngreso) && $controlIngreso != "0" && $controlIngreso != ""){
                            $condicionControlIngreso = " AND t.controlIngreso = '" . mysqli_real_escape_string($link, $controlIngreso) . "' ";
                        }

                        $condicionNombreTicket = "";
                        if(is_array($nombreTicket) && count($nombreTicket) > 0){
                            $nombresTicketEscapados = array_map(function($n) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $n) . "'"; 
                            }, $nombreTicket);
                            $condicionNombreTicket = " AND t.nombreTicket IN (" . implode(',', $nombresTicketEscapados) . ") ";
                        } else if(!is_array($nombreTicket) && $nombreTicket != "0" && $nombreTicket != ""){
                            $condicionNombreTicket = " AND t.nombreTicket = '" . mysqli_real_escape_string($link, $nombreTicket) . "' ";
                        }

                        $cantidadTickets = 0;
                        $sumaTotalTickets = 0;
                        
                        $sql = "SELECT
                                    t.idTicket,
                                    t.numeroTicket,
                                    t.codTicket,
                                    t.nombreTicket,
                                    t.precio,
                                    t.descuento,
                                    t.fechaReg,
                                    t.horaReg,
                                    t.usuario,
                                    t.hash,
                                    t.guiaTicket,
                                    t.generoTicket,
                                    t.edadTicket,
                                    t.nacionalidadTIcket,
                                    t.controlIngreso,
                                    u.usuarioUs
                                    FROM tickets AS t
                                    LEFT JOIN usuarios AS u ON u.idUsuario = t.usuario
                                    WHERE t.fechaReg >= '$fechaInicio'
                                    AND t.fechaReg <= '$fechaFinal'
                                    $condicionUsuario
                                    $condicionGenero
                                    $condicionNacionalidad
                                    $condicionControlIngreso
                                    $condicionNombreTicket
                                    ORDER BY t.fechaReg DESC, t.horaReg DESC";

                        $conTickets = mysqli_query($link, $sql)or die(mysqli_error($link));
                        if(mysqli_num_rows($conTickets) > 0){
                            while($rowTicket = mysqli_fetch_array($conTickets)){
                                $cantidadTickets++;
                                $totalTicket = $rowTicket['precio'] - $rowTicket['descuento'];
                                $sumaTotalTickets += $totalTicket;
                                echo "<tr>";
                                    echo "<td>".$rowTicket['numeroTicket']."</td>";
                                    $gestion = date('Y', strtotime($rowTicket['fechaReg']));
                                    echo "<td>".$gestion."</td>";
                                    $fechaReg = strtotime($rowTicket['fechaReg']);
                                    $meses = [
                                        1 => 'ENERO',
                                        2 => 'FEBRERO',
                                        3 => 'MARZO',
                                        4 => 'ABRIL',
                                        5 => 'MAYO',
                                        6 => 'JUNIO',
                                        7 => 'JULIO',
                                        8 => 'AGOSTO',
                                        9 => 'SEPTIEMBRE',
                                        10 => 'OCTUBRE',
                                        11 => 'NOVIEMBRE',
                                        12 => 'DICIEMBRE'
                                    ];
                                    $numeroMes = (int)date('n', $fechaReg);
                                    $mes = $meses[$numeroMes];
                                    echo "<td>".$mes."</td>";
                                    echo "<td>".$rowTicket['fechaReg']."</td>";
                                    echo "<td>".$rowTicket['horaReg']."</td>";
                                    echo "<td>".$rowTicket['codTicket']."</td>";
                                    echo "<td>".$rowTicket['nombreTicket']."</td>";
                                    echo "<td>".($rowTicket['usuarioUs'] ? $rowTicket['usuarioUs'] : "Usuario ".$rowTicket['usuario'])."</td>";
                                    echo "<td class='text-end'>".number_format($rowTicket['precio'], 2, '.', ',')."</td>";
                                    echo "<td class='text-end'>".number_format($rowTicket['descuento'], 2, '.', ',')."</td>";
                                    echo "<td class='text-end'><strong>".number_format($totalTicket, 2, '.', ',')."</strong></td>";
                                    echo "<td>".$rowTicket['generoTicket']."</td>";
                                    echo "<td>".$rowTicket['edadTicket']."</td>";
                                    echo "<td>".$rowTicket['nacionalidadTIcket']."</td>";
                                    echo "<td>".$rowTicket['guiaTicket']."</td>";
                                    echo "<td>".$rowTicket['controlIngreso']."</td>";
                                    echo "<td><small>".substr($rowTicket['hash'], 0, 10)."...</small></td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody>";
                echo "</table>";

                echo "<div class='col-md-12 m-auto'>";
                    echo "<div class='row mt-3 mb-2'>";
                        echo "<div class='col-md-3 col-6 mb-2'>";
                            echo "<div class='card border-primary h-100'>";
                                echo "<div class='card-body text-center'>";
                                    echo "<h6 class='card-title mb-1'>Total Tickets</h6>";
                                    echo "<span class='display-6 text-primary'>".$cantidadTickets."</span>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-3 col-6 mb-2'>";
                            echo "<div class='card border-success h-100'>";
                                echo "<div class='card-body text-center'>";
                                    echo "<h6 class='card-title mb-1'>Total Ingresos</h6>";
                                    echo "<span class='display-6 text-success'>".number_format($sumaTotalTickets, 2, '.', ',')."</span>";
                                echo "</div>";
                            echo "</div>";  
                        echo "</div>";
                    echo "</div>";
                echo "</div>";

            echo "</div>";
        break;
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover' id='tablaReporte'>";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th>Repositorio</th>";
                            echo "<th>Caja</th>";
                            echo "<th>Codigo Producto</th>";
                            echo "<th>Descripcion</th>";
                            echo "<th>Total Cantidad</th>";
                            echo "<th>Precio Promedio</th>";
                            //echo "<th>Total Vendido</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                        $condicionUsuario = "";
                        if(is_array($idUsuario) && count($idUsuario) > 0){
                            $usuariosEscapados = array_map(function($u) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $u) . "'"; 
                            }, $idUsuario);
                            $condicionUsuario = " AND r.idUsuario IN (" . implode(',', $usuariosEscapados) . ") ";
                        } else if(!is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){
                            $condicionUsuario = " AND r.idUsuario = '" . mysqli_real_escape_string($link, $idUsuario) . "' ";
                        }

                        $condicionTipoVenta = "";
                        if($tipoVenta != "0"){
                            $condicionTipoVenta = " AND r.tipoRecibo = '$tipoVenta' ";
                        }

                        $condicionCaja = "";
                        if(is_array($caja) && count($caja) > 0){
                            $cajasEscapadas = array_map(function($c) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $c) . "'"; 
                            }, $caja);
                            $condicionCaja = " AND r.nombreCaja IN (" . implode(',', $cajasEscapadas) . ") ";
                        } else if(!is_array($caja) && $caja != "0" && $caja != ""){
                            $condicionCaja = " AND r.nombreCaja = '" . mysqli_real_escape_string($link, $caja) . "' ";
                        }


                        $condicionMetodoPago = "";
                        if(is_array($metodoPago) && count($metodoPago) > 0){
                            $metodosEscapados = array_map(function($m) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $m) . "'"; 
                            }, $metodoPago);
                            $condicionMetodoPago = " AND r.metodoPago IN (" . implode(',', $metodosEscapados) . ") ";
                        } else if(!is_array($metodoPago) && $metodoPago != "0" && $metodoPago != ""){
                            $condicionMetodoPago = " AND r.metodoPago = '" . mysqli_real_escape_string($link, $metodoPago) . "' ";
                        }


                        $condicionEstadoFactura = "";
                        if(is_array($estadoFactura) && count($estadoFactura) > 0){
                            $estadosEscapados = array_map(function($e) use ($link) { 
                                return "'" . mysqli_real_escape_string($link, $e) . "'"; 
                            }, $estadoFactura);
                            $condicionEstadoFactura = " AND r.estadoFactura IN (" . implode(',', $estadosEscapados) . ") ";
                        } else if(!is_array($estadoFactura) && $estadoFactura != "0" && $estadoFactura != ""){
                            $condicionEstadoFactura = " AND r.estadoFactura = '" . mysqli_real_escape_string($link, $estadoFactura) . "' ";
                        }

                        $cantidadFacturas = 0;
                        $sumaTotalFacturas = 0;
                        $sql = "SELECT
                                    d.codigoProducto,
                                    d.descripcion,
                                    d.repositorio,
                                    r.nombreCaja,
                                    SUM(d.cantidad)       AS totalCantidad,
                                    AVG(d.precioUnitario) AS precioPromedio,
                                    SUM(d.subTotal)       AS totalVendido
                                    FROM recibos_det AS d
                                    INNER JOIN recibos AS r
                                    ON r.idRecibo = d.idRecibo
                                    WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal'
                                    $condicionUsuario
                                    $condicionTipoVenta
                                    $condicionCaja
                                    $condicionMetodoPago
                                    $condicionEstadoFactura
                                    GROUP BY d.repositorio, d.codigoProducto
                                    ORDER BY totalCantidad DESC;
                                    ";
                        $conResumenProductos = mysqli_query($link, $sql)or die(mysqli_error($link));
                        if(mysqli_num_rows($conResumenProductos) > 0){
                            while($rowResumenProducto = mysqli_fetch_array($conResumenProductos)){
                                echo "<tr>";
                                    echo "<td>".$rowResumenProducto['repositorio']."</td>";
                                    echo "<td>".$rowResumenProducto['nombreCaja']."</td>";
                                    echo "<td>".$rowResumenProducto['codigoProducto']."</td>";
                                    echo "<td>".$rowResumenProducto['descripcion']."</td>";
                                    echo "<td>".$rowResumenProducto['totalCantidad']."</td>";
                                    echo "<td>".$rowResumenProducto['precioPromedio']."</td>";
                                    //echo "<td style='text-align: right;'>".number_format($rowResumenProducto['totalVendido'], 2, '.', ',')."</td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody>";
                echo "</table>";
            echo "</div>";
        break;
    }




}


function generarReportePDF($fechaInicio, $fechaFinal, $usuario, $tipoReporte, $genero, $nacionalidad, $controlIngreso, $nombreTicket = array()){
    global $link;
    
    require_once '../../vendor/autoload.php';
    
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'L',
        'margin_left' => 5,
        'margin_right' => 5,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'margin_header' => 5,
        'margin_footer' => 5,
    ]);
    
    $esReporteTickets = ($tipoReporte === 'RESUMEN_TICKETS' || $tipoReporte === 'DETALLADO_TICKETS');
    
    // Obtener nombres de usuarios (para tickets usa $usuario; para ingresos usa $idUsuario si existe)
    $nombreUsuario = "TODOS";
    $idsParaUsuario = $esReporteTickets ? $usuario : (isset($idUsuario) ? $idUsuario : []);
    if(is_array($idsParaUsuario) && count($idsParaUsuario) > 0){
        $idsEscapados = array_map(function($u) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $u) . "'"; 
        }, $idsParaUsuario);
        $sqlUsuarios = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario IN (" . implode(',', $idsEscapados) . ")";
        $resultUsuarios = mysqli_query($link, $sqlUsuarios);
        $nombres = [];
        while($rowUsuario = mysqli_fetch_array($resultUsuarios)){
            $nombres[] = $rowUsuario['nombreCompleto'];
        }
        if(count($nombres) > 0){
            $nombreUsuario = implode(', ', $nombres);
        }
    } else if(!is_array($idsParaUsuario) && $idsParaUsuario != "0" && $idsParaUsuario != ""){
        $sqlUsuario = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario = '" . mysqli_real_escape_string($link, $idsParaUsuario) . "'";
        $resultUsuario = mysqli_query($link, $sqlUsuario);
        if($rowUsuario = mysqli_fetch_array($resultUsuario)){
            $nombreUsuario = $rowUsuario['nombreCompleto'];
        }
    }
    
    $metodoPagoTexto = isset($metodoPago) && is_array($metodoPago) && count($metodoPago) > 0 ? implode(', ', $metodoPago) : "TODOS";
    $cajaTexto = isset($caja) && is_array($caja) && count($caja) > 0 ? implode(', ', $caja) : "TODOS";
    $repositorioTexto = isset($repositorio) && is_array($repositorio) && count($repositorio) > 0 ? implode(', ', $repositorio) : "TODOS";
    $estadoFacturaTexto = isset($estadoFactura) && is_array($estadoFactura) && count($estadoFactura) > 0 ? implode(', ', $estadoFactura) : "TODOS";
    $generoTexto = is_array($genero) && count($genero) > 0 ? implode(', ', $genero) : "TODOS";
    $nacionalidadTexto = is_array($nacionalidad) && count($nacionalidad) > 0 ? implode(', ', $nacionalidad) : "TODOS";
    $controlIngresoTexto = is_array($controlIngreso) && count($controlIngreso) > 0 ? implode(', ', $controlIngreso) : "TODOS";
    $nombreTicketTexto = is_array($nombreTicket) && count($nombreTicket) > 0 ? implode(', ', $nombreTicket) : "TODOS";
    
    if($esReporteTickets){
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte de Tickets</title>
    <style>body{font-family:Arial,sans-serif;font-size:7px}.header{text-align:center;margin-bottom:15px}
    .header h1{margin:0;font-size:14px}.header p{margin:3px 0;font-size:8px}table{width:100%;border-collapse:collapse;margin-bottom:15px}
    th,td{border:1px solid #000;padding:2px;text-align:left;font-size:7px}th{background-color:#f0f0f0;font-weight:bold}
    .text-right{text-align:right}.summary{margin-top:15px;padding:8px;background-color:#f9f9f9;font-size:10px;font-weight:bold}</style></head><body>
    <div class="header"><h1>REPORTE DE TICKETS</h1><p><strong>Tipo:</strong> '.$tipoReporte.'</p>
    <p><strong>Fecha:</strong> '.$fechaInicio.' - '.$fechaFinal.'</p><p><strong>Usuario:</strong> '.$nombreUsuario.'</p>
    <p><strong>Género:</strong> '.$generoTexto.'</p><p><strong>Nacionalidad:</strong> '.$nacionalidadTexto.'</p>
    <p><strong>Control Ingreso:</strong> '.$controlIngresoTexto.'</p><p><strong>Nombre Ticket:</strong> '.$nombreTicketTexto.'</p>
    <p><strong>Generado:</strong> '.date('d/m/Y H:i:s').'</p></div>';
    } else {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte de Ingresos</title>
    <style>body{font-family:Arial,sans-serif;font-size:7px}.header{text-align:center;margin-bottom:15px}
    .header h1{margin:0;font-size:14px}.header p{margin:3px 0;font-size:8px}table{width:100%;border-collapse:collapse;margin-bottom:15px}
    th,td{border:1px solid #000;padding:2px;text-align:left;font-size:7px}th{background-color:#f0f0f0;font-weight:bold}
    .text-right{text-align:right}.summary{margin-top:15px;padding:8px;background-color:#f9f9f9;font-size:10px;font-weight:bold}</style></head><body>
    <div class="header"><h1>REPORTE DE INGRESOS</h1><p><strong>Tipo:</strong> '.$tipoReporte.'</p>
    <p><strong>Fecha:</strong> '.$fechaInicio.' - '.$fechaFinal.'</p><p><strong>Usuario:</strong> '.$nombreUsuario.'</p>
    <p><strong>Tipo Venta:</strong> '.(isset($tipoVenta) && $tipoVenta == "0" ? "TODOS" : (isset($tipoVenta) ? $tipoVenta : 'TODOS')).'</p>
    <p><strong>Método Pago:</strong> '.$metodoPagoTexto.'</p>
    <p><strong>Caja:</strong> '.$cajaTexto.'</p>
    <p><strong>Repositorio:</strong> '.$repositorioTexto.'</p>
    <p><strong>Estado Factura:</strong> '.$estadoFacturaTexto.'</p>
    <p><strong>Generado:</strong> '.date('d/m/Y H:i:s').'</p></div>';
    }
    
    // Construir condiciones para recibos (ingresos)
    $condicionUsuario = "";
    if(!$esReporteTickets && isset($idUsuario) && is_array($idUsuario) && count($idUsuario) > 0){
        $usuariosEscapados = array_map(function($u) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $u) . "'"; 
        }, $idUsuario);
        $condicionUsuario = " AND r.idUsuario IN (" . implode(',', $usuariosEscapados) . ") ";
    } else if(!$esReporteTickets && isset($idUsuario) && !is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){
        $condicionUsuario = " AND r.idUsuario = '" . mysqli_real_escape_string($link, $idUsuario) . "' ";
    }
    
    $condicionTipoVenta = "";
    if(isset($tipoVenta) && $tipoVenta != "0"){
        $condicionTipoVenta = " AND r.tipoRecibo = '" . mysqli_real_escape_string($link, $tipoVenta) . "' ";
    }
    
    $condicionCaja = "";
    if(isset($caja) && is_array($caja) && count($caja) > 0){
        $cajasEscapadas = array_map(function($c) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $c) . "'"; 
        }, $caja);
        $condicionCaja = " AND r.nombreCaja IN (" . implode(',', $cajasEscapadas) . ") ";
    } else if(isset($caja) && !is_array($caja) && $caja != "0" && $caja != ""){
        $condicionCaja = " AND r.nombreCaja = '" . mysqli_real_escape_string($link, $caja) . "' ";
    }
    
    $condicionMetodoPago = "";
    if(isset($metodoPago) && is_array($metodoPago) && count($metodoPago) > 0){
        $metodosEscapados = array_map(function($m) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $m) . "'"; 
        }, $metodoPago);
        $condicionMetodoPago = " AND r.metodoPago IN (" . implode(',', $metodosEscapados) . ") ";
    } else if(isset($metodoPago) && !is_array($metodoPago) && $metodoPago != "0" && $metodoPago != ""){
        $condicionMetodoPago = " AND r.metodoPago = '" . mysqli_real_escape_string($link, $metodoPago) . "' ";
    }
    
    $condicionRepositorio = "";
    if(isset($repositorio) && is_array($repositorio) && count($repositorio) > 0){
        $repositoriosEscapados = array_map(function($r) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $r) . "'"; 
        }, $repositorio);
        $condicionRepositorio = " AND d.repositorio IN (" . implode(',', $repositoriosEscapados) . ") ";
    } else if(isset($repositorio) && !is_array($repositorio) && $repositorio != "0" && $repositorio != ""){
        $condicionRepositorio = " AND d.repositorio = '" . mysqli_real_escape_string($link, $repositorio) . "' ";
    }
    
    $condicionEstadoFactura = "";
    if(isset($estadoFactura) && is_array($estadoFactura) && count($estadoFactura) > 0){
        $estadosEscapados = array_map(function($e) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $e) . "'"; 
        }, $estadoFactura);
        $condicionEstadoFactura = " AND r.estadoFactura IN (" . implode(',', $estadosEscapados) . ") ";
    } else if(isset($estadoFactura) && !is_array($estadoFactura) && $estadoFactura != "0" && $estadoFactura != ""){
        $condicionEstadoFactura = " AND r.estadoFactura = '" . mysqli_real_escape_string($link, $estadoFactura) . "' ";
    }
    
    // Condiciones para reporte de tickets (tabla t)
    $condicionUsuarioTkt = "";
    if($esReporteTickets && is_array($usuario) && count($usuario) > 0){
        $usuariosEscapados = array_map(function($u) use ($link) { return "'" . mysqli_real_escape_string($link, $u) . "'"; }, $usuario);
        $condicionUsuarioTkt = " AND t.usuario IN (" . implode(',', $usuariosEscapados) . ") ";
    } else if($esReporteTickets && !is_array($usuario) && $usuario != "0" && $usuario != ""){
        $condicionUsuarioTkt = " AND t.usuario = '" . mysqli_real_escape_string($link, $usuario) . "' ";
    }
    $condicionGeneroTkt = "";
    if($esReporteTickets && is_array($genero) && count($genero) > 0){
        $generosEscapados = array_map(function($g) use ($link) { return "'" . mysqli_real_escape_string($link, $g) . "'"; }, $genero);
        $condicionGeneroTkt = " AND t.generoTicket IN (" . implode(',', $generosEscapados) . ") ";
    } else if($esReporteTickets && !is_array($genero) && $genero != "0" && $genero != ""){
        $condicionGeneroTkt = " AND t.generoTicket = '" . mysqli_real_escape_string($link, $genero) . "' ";
    }
    $condicionNacionalidadTkt = "";
    if($esReporteTickets && is_array($nacionalidad) && count($nacionalidad) > 0){
        $nacionalidadesEscapadas = array_map(function($n) use ($link) { return "'" . mysqli_real_escape_string($link, $n) . "'"; }, $nacionalidad);
        $condicionNacionalidadTkt = " AND t.nacionalidadTIcket IN (" . implode(',', $nacionalidadesEscapadas) . ") ";
    } else if($esReporteTickets && !is_array($nacionalidad) && $nacionalidad != "0" && $nacionalidad != ""){
        $condicionNacionalidadTkt = " AND t.nacionalidadTIcket = '" . mysqli_real_escape_string($link, $nacionalidad) . "' ";
    }
    $condicionControlIngresoTkt = "";
    if($esReporteTickets && is_array($controlIngreso) && count($controlIngreso) > 0){
        $controlesEscapados = array_map(function($c) use ($link) { return "'" . mysqli_real_escape_string($link, $c) . "'"; }, $controlIngreso);
        $condicionControlIngresoTkt = " AND t.controlIngreso IN (" . implode(',', $controlesEscapados) . ") ";
    } else if($esReporteTickets && !is_array($controlIngreso) && $controlIngreso != "0" && $controlIngreso != ""){
        $condicionControlIngresoTkt = " AND t.controlIngreso = '" . mysqli_real_escape_string($link, $controlIngreso) . "' ";
    }
    $condicionNombreTicket = "";
    if($esReporteTickets && is_array($nombreTicket) && count($nombreTicket) > 0){
        $nombresTicketEscapados = array_map(function($n) use ($link) { return "'" . mysqli_real_escape_string($link, $n) . "'"; }, $nombreTicket);
        $condicionNombreTicket = " AND t.nombreTicket IN (" . implode(',', $nombresTicketEscapados) . ") ";
    } else if($esReporteTickets && !is_array($nombreTicket) && $nombreTicket != "0" && $nombreTicket != ""){
        $condicionNombreTicket = " AND t.nombreTicket = '" . mysqli_real_escape_string($link, $nombreTicket) . "' ";
    }

    switch($tipoReporte){
        case "RESUMEN_TICKETS":
            $html .= '<table><thead><tr><th>Nro. Ticket</th><th>Gestión</th><th>Mes</th><th>Fecha Registro</th><th>Hora</th><th>Código</th><th>Nombre Ticket</th><th>Usuario</th><th>Precio</th><th>Descuento</th><th>Total</th><th>Género</th><th>Edad</th><th>Nacionalidad</th><th>Control Ingreso</th></tr></thead><tbody>';
            $sqlTickets = "SELECT t.idTicket, t.numeroTicket, t.codTicket, t.nombreTicket, t.precio, t.descuento, t.fechaReg, t.horaReg, t.usuario, t.generoTicket, t.edadTicket, t.nacionalidadTIcket, t.controlIngreso, u.usuarioUs
                FROM tickets AS t LEFT JOIN usuarios AS u ON u.idUsuario = t.usuario
                WHERE t.fechaReg >= '$fechaInicio' AND t.fechaReg <= '$fechaFinal' $condicionUsuarioTkt $condicionGeneroTkt $condicionNacionalidadTkt $condicionControlIngresoTkt $condicionNombreTicket
                ORDER BY t.fechaReg DESC, t.horaReg DESC";
            $conTickets = mysqli_query($link, $sqlTickets) or die(mysqli_error($link));
            $cantidadTickets = 0;
            $sumaTotalTickets = 0;
            $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
            while($row = mysqli_fetch_array($conTickets)){
                $cantidadTickets++;
                $totalTicket = $row['precio'] - $row['descuento'];
                $sumaTotalTickets += $totalTicket;
                $gestion = date('Y', strtotime($row['fechaReg']));
                $numeroMes = (int)date('n', strtotime($row['fechaReg']));
                $mes = $meses[$numeroMes];
                $usuarioUs = $row['usuarioUs'] ? $row['usuarioUs'] : 'Usuario '.$row['usuario'];
                $html .= '<tr><td>'.$row['numeroTicket'].'</td><td>'.$gestion.'</td><td>'.$mes.'</td><td>'.$row['fechaReg'].'</td><td>'.$row['horaReg'].'</td><td>'.$row['codTicket'].'</td><td>'.htmlspecialchars($row['nombreTicket']).'</td><td>'.htmlspecialchars($usuarioUs).'</td><td class="text-right">'.number_format($row['precio'],2,'.',',').'</td><td class="text-right">'.number_format($row['descuento'],2,'.',',').'</td><td class="text-right">'.number_format($totalTicket,2,'.',',').'</td><td>'.htmlspecialchars($row['generoTicket']).'</td><td>'.htmlspecialchars($row['edadTicket']).'</td><td>'.htmlspecialchars($row['nacionalidadTIcket']).'</td><td>'.htmlspecialchars($row['controlIngreso']).'</td></tr>';
            }
            $html .= '</tbody></table><div class="summary">Total Tickets: '.$cantidadTickets.' | TOTAL: Bs. '.number_format($sumaTotalTickets,2,'.',',').'</div>';
            break;
            
        case "DETALLADO_TICKETS":
            $html .= '<table><thead><tr><th>Nro. Ticket</th><th>Gestión</th><th>Mes</th><th>Fecha</th><th>Hora</th><th>Código</th><th>Nombre Ticket</th><th>Usuario</th><th>Precio</th><th>Descuento</th><th>Total</th><th>Género</th><th>Edad</th><th>Nacionalidad</th><th>Guía</th><th>Control Ingreso</th></tr></thead><tbody>';
            $sqlTickets = "SELECT t.idTicket, t.numeroTicket, t.codTicket, t.nombreTicket, t.precio, t.descuento, t.fechaReg, t.horaReg, t.usuario, t.guiaTicket, t.generoTicket, t.edadTicket, t.nacionalidadTIcket, t.controlIngreso, u.usuarioUs
                FROM tickets AS t LEFT JOIN usuarios AS u ON u.idUsuario = t.usuario
                WHERE t.fechaReg >= '$fechaInicio' AND t.fechaReg <= '$fechaFinal' $condicionUsuarioTkt $condicionGeneroTkt $condicionNacionalidadTkt $condicionControlIngresoTkt $condicionNombreTicket
                ORDER BY t.fechaReg DESC, t.horaReg DESC";
            $conTickets = mysqli_query($link, $sqlTickets) or die(mysqli_error($link));
            $cantidadTickets = 0;
            $sumaTotalTickets = 0;
            $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
            while($row = mysqli_fetch_array($conTickets)){
                $cantidadTickets++;
                $totalTicket = $row['precio'] - $row['descuento'];
                $sumaTotalTickets += $totalTicket;
                $gestion = date('Y', strtotime($row['fechaReg']));
                $numeroMes = (int)date('n', strtotime($row['fechaReg']));
                $mes = $meses[$numeroMes];
                $usuarioUs = $row['usuarioUs'] ? $row['usuarioUs'] : 'Usuario '.$row['usuario'];
                $html .= '<tr><td>'.$row['numeroTicket'].'</td><td>'.$gestion.'</td><td>'.$mes.'</td><td>'.$row['fechaReg'].'</td><td>'.$row['horaReg'].'</td><td>'.$row['codTicket'].'</td><td>'.htmlspecialchars($row['nombreTicket']).'</td><td>'.htmlspecialchars($usuarioUs).'</td><td class="text-right">'.number_format($row['precio'],2,'.',',').'</td><td class="text-right">'.number_format($row['descuento'],2,'.',',').'</td><td class="text-right">'.number_format($totalTicket,2,'.',',').'</td><td>'.htmlspecialchars($row['generoTicket']).'</td><td>'.htmlspecialchars($row['edadTicket']).'</td><td>'.htmlspecialchars($row['nacionalidadTIcket']).'</td><td>'.htmlspecialchars($row['guiaTicket']).'</td><td>'.htmlspecialchars($row['controlIngreso']).'</td></tr>';
            }
            $html .= '</tbody></table><div class="summary">Total Tickets: '.$cantidadTickets.' | TOTAL: Bs. '.number_format($sumaTotalTickets,2,'.',',').'</div>';
            break;
            
        case "RESUMEN_INGRESOS":
            $html .= '<table><thead><tr><th>Nro. Factura</th><th>Gestión</th><th>Mes</th><th>Fecha Emisión</th><th>Nombre/Razón Social</th><th>Nro. Doc</th><th>Tipo</th><th>Usuario</th><th>Método Pago</th><th>Caja</th><th>Estado</th><th>Total Bs.</th></tr></thead><tbody>';
            
            $sql = "SELECT
                        r.idRecibo,
                        r.numeroFactura,
                        r.fechaEmision,
                        r.horaEmision,
                        r.nombreRazonSocial,
                        r.numeroDocumento,
                        r.fechaRegistro,
                        r.tipoRecibo,
                        r.idUsuario,
                        u.usuarioUs,
                        r.cuf,
                        r.nombreCaja,
                        r.metodoPago,
                        r.estadoFactura,
                        r.montoTotal,
                        r.descuentoAdicional
                    FROM recibos AS r
                    LEFT JOIN usuarios AS u ON u.idUsuario = r.idUsuario
                    WHERE r.fechaEmision >= '$fechaInicio'
                    AND r.fechaEmision <= '$fechaFinal'
                    $condicionUsuario
                    $condicionTipoVenta
                    $condicionCaja
                    $condicionMetodoPago
                    $condicionEstadoFactura";
            
            $conRecibos = mysqli_query($link, $sql) or die(mysqli_error($link));
            $cantidadFacturas = 0;
            $sumaTotalFacturas = 0;
            
            $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
                      7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
            
            if(mysqli_num_rows($conRecibos) > 0){
                while($rowRecibo = mysqli_fetch_array($conRecibos)){
                    $cantidadFacturas++;
                    $sumaTotalFacturas += $rowRecibo['montoTotal'];
                    $gestion = date('Y', strtotime($rowRecibo['fechaEmision']));
                    $fechaEmision = strtotime($rowRecibo['fechaEmision']);
                    $numeroMes = (int)date('n', $fechaEmision);
                    $mes = $meses[$numeroMes];
                    
                    $html .= '<tr>
                        <td>'.$rowRecibo['numeroFactura'].'</td>
                        <td>'.$gestion.'</td>
                        <td>'.$mes.'</td>
                        <td>'.$rowRecibo['fechaEmision'].' '.$rowRecibo['horaEmision'].'</td>
                        <td>'.$rowRecibo['nombreRazonSocial'].'</td>
                        <td>'.$rowRecibo['numeroDocumento'].'</td>
                        <td>'.$rowRecibo['tipoRecibo'].'</td>
                        <td>'.$rowRecibo['usuarioUs'].'</td>
                        <td>'.$rowRecibo['metodoPago'].'</td>
                        <td>'.$rowRecibo['nombreCaja'].'</td>
                        <td>'.$rowRecibo['estadoFactura'].'</td>
                        <td class="text-right">'.number_format($rowRecibo['montoTotal'],2,'.',',').'</td>
                    </tr>';
                }
            }
            $html .= '</tbody></table><div class="summary">Total Facturas: '.$cantidadFacturas.' | TOTAL INGRESOS: Bs. '.number_format($sumaTotalFacturas,2,'.',',').'</div>';
            break;
            
        case "DETALLADO_INGRESOS":
            $html .= '<table><thead><tr><th>Nro. Factura</th><th>Gestión</th><th>Mes</th><th>Fecha</th><th>Nombre/Razón Social</th><th>Nro. Doc</th><th>Producto</th><th>Tipo</th><th>Método Pago</th><th>Usuario</th><th>Caja</th><th>Repositorio</th><th>Cant</th><th>Precio Unit</th><th>Monto Desc</th><th>Sub Total</th><th>Desc Adic</th><th>Total</th></tr></thead><tbody>';
            
            $sql = "SELECT
                        r.idRecibo,
                        r.numeroFactura,
                        r.fechaEmision,
                        r.horaEmision,
                        r.nombreRazonSocial,
                        COALESCE(p.descripcion, d.descripcion) AS nombreProducto,
                        r.numeroDocumento,
                        r.tipoRecibo,
                        d.repositorio,
                        r.metodoPago,
                        r.nombreCaja,
                        u.usuarioUs,
                        d.cantidad,
                        d.precioUnitario,
                        d.montoDescuento,
                        d.subTotal,
                        r.descuentoAdicional,
                        r.montoTotal,
                        (SELECT COUNT(*) FROM recibos_det WHERE idRecibo = r.idRecibo) AS totalProductosRecibo
                    FROM recibos_det AS d
                    INNER JOIN recibos AS r ON r.idRecibo = d.idRecibo
                    LEFT JOIN productos AS p ON p.codigoProducto = d.codigoProducto
                    LEFT JOIN usuarios AS u ON u.idUsuario = r.idUsuario
                    WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal'
                        $condicionUsuario
                        $condicionTipoVenta
                        $condicionCaja
                        $condicionMetodoPago
                        $condicionRepositorio
                    ORDER BY r.fechaEmision, r.numeroFactura, d.idReciboDet";
            
            $conRecibosDet = mysqli_query($link, $sql) or die(mysqli_error($link));
            $cantidadProductos = 0;
            $sumaTotalVendido = 0;
            $sumaTotalConDescuentoAdicional = 0;
            
            $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
                      7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
            
            if(mysqli_num_rows($conRecibosDet) > 0){
                while($rowReciboDet = mysqli_fetch_array($conRecibosDet)){
                    $cantidadProductos += $rowReciboDet['cantidad'];
                    $sumaTotalVendido += $rowReciboDet['subTotal'];
                    
                    // Calcular el descuento adicional por producto
                    $totalProductosRecibo = $rowReciboDet['totalProductosRecibo'];
                    $descuentoAdicionalTotal = $rowReciboDet['descuentoAdicional'];
                    $descuentoAdicionalPorProducto = 0;
                    
                    if($totalProductosRecibo > 0 && $descuentoAdicionalTotal > 0){
                        $descuentoAdicionalPorProducto = $descuentoAdicionalTotal / $totalProductosRecibo;
                    }
                    
                    // Calcular el total
                    $total = $rowReciboDet['subTotal'] - $descuentoAdicionalPorProducto;
                    $sumaTotalConDescuentoAdicional += $total;
                    
                    $gestion = date('Y', strtotime($rowReciboDet['fechaEmision']));
                    $fechaEmision = strtotime($rowReciboDet['fechaEmision']);
                    $numeroMes = (int)date('n', $fechaEmision);
                    $mes = $meses[$numeroMes];
                    
                    $html .= '<tr>
                        <td>'.$rowReciboDet['numeroFactura'].'</td>
                        <td>'.$gestion.'</td>
                        <td>'.$mes.'</td>
                        <td>'.$rowReciboDet['fechaEmision'].' '.$rowReciboDet['horaEmision'].'</td>
                        <td>'.$rowReciboDet['nombreRazonSocial'].'</td>
                        <td>'.$rowReciboDet['numeroDocumento'].'</td>
                        <td>'.$rowReciboDet['nombreProducto'].'</td>
                        <td>'.$rowReciboDet['tipoRecibo'].'</td>
                        <td>'.$rowReciboDet['metodoPago'].'</td>
                        <td>'.$rowReciboDet['usuarioUs'].'</td>
                        <td>'.$rowReciboDet['nombreCaja'].'</td>
                        <td>'.$rowReciboDet['repositorio'].'</td>
                        <td>'.$rowReciboDet['cantidad'].'</td>
                        <td class="text-right">'.number_format($rowReciboDet['precioUnitario'],2,'.',',').'</td>
                        <td class="text-right">'.number_format($rowReciboDet['montoDescuento'],2,'.',',').'</td>
                        <td class="text-right">'.number_format($rowReciboDet['subTotal'],2,'.',',').'</td>
                        <td class="text-right">'.number_format($descuentoAdicionalPorProducto,2,'.',',').'</td>
                        <td class="text-right">'.number_format($total,2,'.',',').'</td>
                    </tr>';
                }
            }
            $html .= '</tbody></table><div class="summary">Total Productos: '.$cantidadProductos.' | TOTAL VENDIDO: Bs. '.number_format($sumaTotalVendido,2,'.',',').'</div>';
            break;
            
        case "RESUMEN_PRODUCTOS":
            $html .= '<table><thead><tr><th>Repositorio</th><th>Caja</th><th>Código Producto</th><th>Descripción</th><th>Total Cantidad</th><th>Precio Promedio</th></tr></thead><tbody>';
            
            $sql = "SELECT
                        d.codigoProducto,
                        d.descripcion,
                        d.repositorio,
                        r.nombreCaja,
                        SUM(d.cantidad) AS totalCantidad,
                        AVG(d.precioUnitario) AS precioPromedio,
                        SUM(d.subTotal) AS totalVendido
                    FROM recibos_det AS d
                    INNER JOIN recibos AS r ON r.idRecibo = d.idRecibo
                    WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal'
                        $condicionUsuario
                        $condicionTipoVenta
                        $condicionCaja
                        $condicionMetodoPago
                        $condicionEstadoFactura
                    GROUP BY d.repositorio, d.codigoProducto
                    ORDER BY totalCantidad DESC";
            
            $conResumenProductos = mysqli_query($link, $sql) or die(mysqli_error($link));
            
            if(mysqli_num_rows($conResumenProductos) > 0){
                while($rowResumenProducto = mysqli_fetch_array($conResumenProductos)){
                    $html .= '<tr>
                        <td>'.$rowResumenProducto['repositorio'].'</td>
                        <td>'.$rowResumenProducto['nombreCaja'].'</td>
                        <td>'.$rowResumenProducto['codigoProducto'].'</td>
                        <td>'.$rowResumenProducto['descripcion'].'</td>
                        <td>'.$rowResumenProducto['totalCantidad'].'</td>
                        <td class="text-right">'.number_format($rowResumenProducto['precioPromedio'],2,'.',',').'</td>
                    </tr>';
                }
            }
            $html .= '</tbody></table>';
            break;
    }
    
    $html .= '</body></html>';
    $mpdf->WriteHTML($html);
    
    $nombreArchivo = 'Reporte_'.$tipoReporte.'_'.date('Ymd_His').'.pdf';
    $rutaArchivo = '../../storage/temp/'.$nombreArchivo;
    $mpdf->Output($rutaArchivo, 'F');

    $rutaDescarga = 'storage/temp/'.$nombreArchivo;

    echo "<div class='col-md-12 text-center'>";
        echo "<embed src='".$rutaDescarga."' type='application/pdf' width='100%' height='500px'>";
    echo "</div>";
}

function generarReporteEXCEL($fechaInicio, $fechaFinal, $usuario, $tipoReporte, $genero, $nacionalidad, $controlIngreso, $nombreTicket = array()){
    global $link;
    
    require_once '../../vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $esReporteTickets = ($tipoReporte === 'RESUMEN_TICKETS' || $tipoReporte === 'DETALLADO_TICKETS');
    
    // Obtener nombres de usuarios (para tickets usa $usuario; para ingresos usa $idUsuario si existe)
    $nombreUsuario = "TODOS";
    $idsParaUsuario = $esReporteTickets ? $usuario : (isset($idUsuario) ? $idUsuario : []);
    if(is_array($idsParaUsuario) && count($idsParaUsuario) > 0){
        $idsEscapados = array_map(function($u) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $u) . "'"; 
        }, $idsParaUsuario);
        $sqlUsuarios = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario IN (" . implode(',', $idsEscapados) . ")";
        $resultUsuarios = mysqli_query($link, $sqlUsuarios);
        $nombres = [];
        while($rowUsuario = mysqli_fetch_array($resultUsuarios)){
            $nombres[] = $rowUsuario['nombreCompleto'];
        }
        if(count($nombres) > 0){
            $nombreUsuario = implode(', ', $nombres);
        }
    } else if(!is_array($idsParaUsuario) && $idsParaUsuario != "0" && $idsParaUsuario != ""){
        $sqlUsuario = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario = '" . mysqli_real_escape_string($link, $idsParaUsuario) . "'";
        $resultUsuario = mysqli_query($link, $sqlUsuario);
        if($rowUsuario = mysqli_fetch_array($resultUsuario)){
            $nombreUsuario = $rowUsuario['nombreCompleto'];
        }
    }
    
    $metodoPagoTexto = isset($metodoPago) && is_array($metodoPago) && count($metodoPago) > 0 ? implode(', ', $metodoPago) : "TODOS";
    $cajaTexto = isset($caja) && is_array($caja) && count($caja) > 0 ? implode(', ', $caja) : "TODOS";
    $repositorioTexto = isset($repositorio) && is_array($repositorio) && count($repositorio) > 0 ? implode(', ', $repositorio) : "TODOS";
    $estadoFacturaTexto = isset($estadoFactura) && is_array($estadoFactura) && count($estadoFactura) > 0 ? implode(', ', $estadoFactura) : "TODOS";
    
    $sheet->setTitle($esReporteTickets ? 'Reporte de Tickets' : 'Reporte de Ingresos');
    $sheet->setCellValue('A1', $esReporteTickets ? 'REPORTE DE TICKETS' : 'REPORTE DE INGRESOS');
    $sheet->setCellValue('A2', 'Tipo de Reporte: ' . $tipoReporte);
    $sheet->setCellValue('A3', 'Fecha: ' . $fechaInicio . ' - ' . $fechaFinal);
    $sheet->setCellValue('A4', 'Usuario: ' . $nombreUsuario);
    if($esReporteTickets){
        $sheet->setCellValue('A5', 'Género: ' . (is_array($genero) && count($genero) > 0 ? implode(', ', $genero) : 'TODOS'));
        $sheet->setCellValue('A6', 'Nacionalidad: ' . (is_array($nacionalidad) && count($nacionalidad) > 0 ? implode(', ', $nacionalidad) : 'TODOS'));
        $sheet->setCellValue('A7', 'Control Ingreso: ' . (is_array($controlIngreso) && count($controlIngreso) > 0 ? implode(', ', $controlIngreso) : 'TODOS'));
        $sheet->setCellValue('A8', 'Nombre Ticket: ' . (is_array($nombreTicket) && count($nombreTicket) > 0 ? implode(', ', $nombreTicket) : 'TODOS'));
        $sheet->setCellValue('A9', 'Generado: ' . date('d/m/Y H:i:s'));
        $filaInicio = 11;
    } else {
        $sheet->setCellValue('A5', 'Tipo Venta: ' . (isset($tipoVenta) && $tipoVenta == "0" ? "TODOS" : (isset($tipoVenta) ? $tipoVenta : 'TODOS')));
        $sheet->setCellValue('A6', 'Método Pago: ' . $metodoPagoTexto);
        $sheet->setCellValue('A7', 'Caja: ' . $cajaTexto);
        $sheet->setCellValue('A8', 'Repositorio: ' . $repositorioTexto);
        $sheet->setCellValue('A9', 'Estado Factura: ' . $estadoFacturaTexto);
        $sheet->setCellValue('A10', 'Generado: ' . date('d/m/Y H:i:s'));
        $filaInicio = 12;
    }
    
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2:A'.($esReporteTickets ? '9' : '10'))->getFont()->setBold(true);
    
    // Condiciones para recibos (ingresos)
    $condicionUsuario = "";
    if(!$esReporteTickets && isset($idUsuario) && is_array($idUsuario) && count($idUsuario) > 0){
        $usuariosEscapados = array_map(function($u) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $u) . "'"; 
        }, $idUsuario);
        $condicionUsuario = " AND r.idUsuario IN (" . implode(',', $usuariosEscapados) . ") ";
    } else if(!$esReporteTickets && isset($idUsuario) && !is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){
        $condicionUsuario = " AND r.idUsuario = '" . mysqli_real_escape_string($link, $idUsuario) . "' ";
    }
    
    $condicionTipoVenta = "";
    if(isset($tipoVenta) && $tipoVenta != "0"){
        $condicionTipoVenta = " AND r.tipoRecibo = '" . mysqli_real_escape_string($link, $tipoVenta) . "' ";
    }
    
    $condicionCaja = "";
    if(isset($caja) && is_array($caja) && count($caja) > 0){
        $cajasEscapadas = array_map(function($c) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $c) . "'"; 
        }, $caja);
        $condicionCaja = " AND r.nombreCaja IN (" . implode(',', $cajasEscapadas) . ") ";
    } else if(isset($caja) && !is_array($caja) && $caja != "0" && $caja != ""){
        $condicionCaja = " AND r.nombreCaja = '" . mysqli_real_escape_string($link, $caja) . "' ";
    }
    
    $condicionMetodoPago = "";
    if(isset($metodoPago) && is_array($metodoPago) && count($metodoPago) > 0){
        $metodosEscapados = array_map(function($m) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $m) . "'"; 
        }, $metodoPago);
        $condicionMetodoPago = " AND r.metodoPago IN (" . implode(',', $metodosEscapados) . ") ";
    } else if(isset($metodoPago) && !is_array($metodoPago) && $metodoPago != "0" && $metodoPago != ""){
        $condicionMetodoPago = " AND r.metodoPago = '" . mysqli_real_escape_string($link, $metodoPago) . "' ";
    }
    
    $condicionRepositorio = "";
    if(isset($repositorio) && is_array($repositorio) && count($repositorio) > 0){
        $repositoriosEscapados = array_map(function($r) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $r) . "'"; 
        }, $repositorio);
        $condicionRepositorio = " AND d.repositorio IN (" . implode(',', $repositoriosEscapados) . ") ";
    } else if(isset($repositorio) && !is_array($repositorio) && $repositorio != "0" && $repositorio != ""){
        $condicionRepositorio = " AND d.repositorio = '" . mysqli_real_escape_string($link, $repositorio) . "' ";
    }
    
    $condicionEstadoFactura = "";
    if(isset($estadoFactura) && is_array($estadoFactura) && count($estadoFactura) > 0){
        $estadosEscapados = array_map(function($e) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $e) . "'"; 
        }, $estadoFactura);
        $condicionEstadoFactura = " AND r.estadoFactura IN (" . implode(',', $estadosEscapados) . ") ";
    } else if(isset($estadoFactura) && !is_array($estadoFactura) && $estadoFactura != "0" && $estadoFactura != ""){
        $condicionEstadoFactura = " AND r.estadoFactura = '" . mysqli_real_escape_string($link, $estadoFactura) . "' ";
    }
    
    // Condiciones para reporte de tickets (tabla t)
    $condicionUsuarioTkt = "";
    if($esReporteTickets && is_array($usuario) && count($usuario) > 0){
        $usuariosEscapados = array_map(function($u) use ($link) { return "'" . mysqli_real_escape_string($link, $u) . "'"; }, $usuario);
        $condicionUsuarioTkt = " AND t.usuario IN (" . implode(',', $usuariosEscapados) . ") ";
    } else if($esReporteTickets && !is_array($usuario) && $usuario != "0" && $usuario != ""){
        $condicionUsuarioTkt = " AND t.usuario = '" . mysqli_real_escape_string($link, $usuario) . "' ";
    }
    $condicionGeneroTkt = "";
    if($esReporteTickets && is_array($genero) && count($genero) > 0){
        $generosEscapados = array_map(function($g) use ($link) { return "'" . mysqli_real_escape_string($link, $g) . "'"; }, $genero);
        $condicionGeneroTkt = " AND t.generoTicket IN (" . implode(',', $generosEscapados) . ") ";
    } else if($esReporteTickets && !is_array($genero) && $genero != "0" && $genero != ""){
        $condicionGeneroTkt = " AND t.generoTicket = '" . mysqli_real_escape_string($link, $genero) . "' ";
    }
    $condicionNacionalidadTkt = "";
    if($esReporteTickets && is_array($nacionalidad) && count($nacionalidad) > 0){
        $nacionalidadesEscapadas = array_map(function($n) use ($link) { return "'" . mysqli_real_escape_string($link, $n) . "'"; }, $nacionalidad);
        $condicionNacionalidadTkt = " AND t.nacionalidadTIcket IN (" . implode(',', $nacionalidadesEscapadas) . ") ";
    } else if($esReporteTickets && !is_array($nacionalidad) && $nacionalidad != "0" && $nacionalidad != ""){
        $condicionNacionalidadTkt = " AND t.nacionalidadTIcket = '" . mysqli_real_escape_string($link, $nacionalidad) . "' ";
    }
    $condicionControlIngresoTkt = "";
    if($esReporteTickets && is_array($controlIngreso) && count($controlIngreso) > 0){
        $controlesEscapados = array_map(function($c) use ($link) { return "'" . mysqli_real_escape_string($link, $c) . "'"; }, $controlIngreso);
        $condicionControlIngresoTkt = " AND t.controlIngreso IN (" . implode(',', $controlesEscapados) . ") ";
    } else if($esReporteTickets && !is_array($controlIngreso) && $controlIngreso != "0" && $controlIngreso != ""){
        $condicionControlIngresoTkt = " AND t.controlIngreso = '" . mysqli_real_escape_string($link, $controlIngreso) . "' ";
    }
    $condicionNombreTicket = "";
    if($esReporteTickets && is_array($nombreTicket) && count($nombreTicket) > 0){
        $nombresTicketEscapados = array_map(function($n) use ($link) { return "'" . mysqli_real_escape_string($link, $n) . "'"; }, $nombreTicket);
        $condicionNombreTicket = " AND t.nombreTicket IN (" . implode(',', $nombresTicketEscapados) . ") ";
    } else if($esReporteTickets && !is_array($nombreTicket) && $nombreTicket != "0" && $nombreTicket != ""){
        $condicionNombreTicket = " AND t.nombreTicket = '" . mysqli_real_escape_string($link, $nombreTicket) . "' ";
    }

    $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
              7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
    
    switch($tipoReporte){
        case "RESUMEN_TICKETS":
            $cols = ['A'=>'Nro. Ticket','B'=>'Gestión','C'=>'Mes','D'=>'Fecha Reg.','E'=>'Hora','F'=>'Código','G'=>'Nombre Ticket','H'=>'Usuario','I'=>'Precio','J'=>'Descuento','K'=>'Total','L'=>'Género','M'=>'Edad','N'=>'Nacionalidad','O'=>'Control Ingreso'];
            foreach($cols as $col => $titulo) $sheet->setCellValue($col.$filaInicio, $titulo);
            $sheet->getStyle('A'.$filaInicio.':O'.$filaInicio)->getFont()->setBold(true);
            $sheet->getStyle('A'.$filaInicio.':O'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            $fila = $filaInicio + 1;
            $cantidadTickets = 0;
            $sumaTotalTickets = 0;
            $sqlTickets = "SELECT t.numeroTicket, t.codTicket, t.nombreTicket, t.precio, t.descuento, t.fechaReg, t.horaReg, t.usuario, t.generoTicket, t.edadTicket, t.nacionalidadTIcket, t.controlIngreso, u.usuarioUs
                FROM tickets AS t LEFT JOIN usuarios AS u ON u.idUsuario = t.usuario
                WHERE t.fechaReg >= '$fechaInicio' AND t.fechaReg <= '$fechaFinal' $condicionUsuarioTkt $condicionGeneroTkt $condicionNacionalidadTkt $condicionControlIngresoTkt $condicionNombreTicket
                ORDER BY t.fechaReg DESC, t.horaReg DESC";
            $conTickets = mysqli_query($link, $sqlTickets) or die(mysqli_error($link));
            while($row = mysqli_fetch_array($conTickets)){
                $cantidadTickets++;
                $totalTicket = $row['precio'] - $row['descuento'];
                $sumaTotalTickets += $totalTicket;
                $gestion = date('Y', strtotime($row['fechaReg']));
                $mes = $meses[(int)date('n', strtotime($row['fechaReg']))];
                $usuarioUs = $row['usuarioUs'] ? $row['usuarioUs'] : 'Usuario '.$row['usuario'];
                $sheet->setCellValue('A'.$fila, $row['numeroTicket']);
                $sheet->setCellValue('B'.$fila, $gestion);
                $sheet->setCellValue('C'.$fila, $mes);
                $sheet->setCellValue('D'.$fila, $row['fechaReg']);
                $sheet->setCellValue('E'.$fila, $row['horaReg']);
                $sheet->setCellValue('F'.$fila, $row['codTicket']);
                $sheet->setCellValue('G'.$fila, $row['nombreTicket']);
                $sheet->setCellValue('H'.$fila, $usuarioUs);
                $sheet->setCellValue('I'.$fila, $row['precio']);
                $sheet->setCellValue('J'.$fila, $row['descuento']);
                $sheet->setCellValue('K'.$fila, $totalTicket);
                $sheet->setCellValue('L'.$fila, $row['generoTicket']);
                $sheet->setCellValue('M'.$fila, $row['edadTicket']);
                $sheet->setCellValue('N'.$fila, $row['nacionalidadTIcket']);
                $sheet->setCellValue('O'.$fila, $row['controlIngreso']);
                $sheet->getStyle('I'.$fila.':K'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
                $fila++;
            }
            $sheet->setCellValue('J'.$fila, 'Total Tickets:');
            $sheet->setCellValue('K'.$fila, $cantidadTickets);
            $sheet->setCellValue('J'.($fila+1), 'TOTAL Bs.:');
            $sheet->setCellValue('K'.($fila+1), $sumaTotalTickets);
            $sheet->getStyle('J'.$fila.':K'.($fila+1))->getFont()->setBold(true);
            $sheet->getStyle('K'.($fila+1))->getNumberFormat()->setFormatCode('#,##0.00');
            break;
            
        case "DETALLADO_TICKETS":
            $cols = ['A'=>'Nro. Ticket','B'=>'Gestión','C'=>'Mes','D'=>'Fecha','E'=>'Hora','F'=>'Código','G'=>'Nombre Ticket','H'=>'Usuario','I'=>'Precio','J'=>'Descuento','K'=>'Total','L'=>'Género','M'=>'Edad','N'=>'Nacionalidad','O'=>'Guía','P'=>'Control Ingreso'];
            foreach($cols as $col => $titulo) $sheet->setCellValue($col.$filaInicio, $titulo);
            $sheet->getStyle('A'.$filaInicio.':P'.$filaInicio)->getFont()->setBold(true);
            $sheet->getStyle('A'.$filaInicio.':P'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            $fila = $filaInicio + 1;
            $cantidadTickets = 0;
            $sumaTotalTickets = 0;
            $sqlTickets = "SELECT t.numeroTicket, t.codTicket, t.nombreTicket, t.precio, t.descuento, t.fechaReg, t.horaReg, t.usuario, t.guiaTicket, t.generoTicket, t.edadTicket, t.nacionalidadTIcket, t.controlIngreso, u.usuarioUs
                FROM tickets AS t LEFT JOIN usuarios AS u ON u.idUsuario = t.usuario
                WHERE t.fechaReg >= '$fechaInicio' AND t.fechaReg <= '$fechaFinal' $condicionUsuarioTkt $condicionGeneroTkt $condicionNacionalidadTkt $condicionControlIngresoTkt $condicionNombreTicket
                ORDER BY t.fechaReg DESC, t.horaReg DESC";
            $conTickets = mysqli_query($link, $sqlTickets) or die(mysqli_error($link));
            while($row = mysqli_fetch_array($conTickets)){
                $cantidadTickets++;
                $totalTicket = $row['precio'] - $row['descuento'];
                $sumaTotalTickets += $totalTicket;
                $gestion = date('Y', strtotime($row['fechaReg']));
                $mes = $meses[(int)date('n', strtotime($row['fechaReg']))];
                $usuarioUs = $row['usuarioUs'] ? $row['usuarioUs'] : 'Usuario '.$row['usuario'];
                $sheet->setCellValue('A'.$fila, $row['numeroTicket']);
                $sheet->setCellValue('B'.$fila, $gestion);
                $sheet->setCellValue('C'.$fila, $mes);
                $sheet->setCellValue('D'.$fila, $row['fechaReg']);
                $sheet->setCellValue('E'.$fila, $row['horaReg']);
                $sheet->setCellValue('F'.$fila, $row['codTicket']);
                $sheet->setCellValue('G'.$fila, $row['nombreTicket']);
                $sheet->setCellValue('H'.$fila, $usuarioUs);
                $sheet->setCellValue('I'.$fila, $row['precio']);
                $sheet->setCellValue('J'.$fila, $row['descuento']);
                $sheet->setCellValue('K'.$fila, $totalTicket);
                $sheet->setCellValue('L'.$fila, $row['generoTicket']);
                $sheet->setCellValue('M'.$fila, $row['edadTicket']);
                $sheet->setCellValue('N'.$fila, $row['nacionalidadTIcket']);
                $sheet->setCellValue('O'.$fila, $row['guiaTicket']);
                $sheet->setCellValue('P'.$fila, $row['controlIngreso']);
                $sheet->getStyle('I'.$fila.':K'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
                $fila++;
            }
            $sheet->setCellValue('J'.$fila, 'Total Tickets:');
            $sheet->setCellValue('K'.$fila, $cantidadTickets);
            $sheet->setCellValue('J'.($fila+1), 'TOTAL Bs.:');
            $sheet->setCellValue('K'.($fila+1), $sumaTotalTickets);
            $sheet->getStyle('J'.$fila.':K'.($fila+1))->getFont()->setBold(true);
            $sheet->getStyle('K'.($fila+1))->getNumberFormat()->setFormatCode('#,##0.00');
            break;
            
        case "RESUMEN_INGRESOS":
            $sheet->setCellValue('A'.$filaInicio, 'Nro. Factura');
            $sheet->setCellValue('B'.$filaInicio, 'Gestión');
            $sheet->setCellValue('C'.$filaInicio, 'Mes');
            $sheet->setCellValue('D'.$filaInicio, 'Fecha Emisión');
            $sheet->setCellValue('E'.$filaInicio, 'Nombre/Razón Social');
            $sheet->setCellValue('F'.$filaInicio, 'Nro. Documento');
            $sheet->setCellValue('G'.$filaInicio, 'Tipo Venta');
            $sheet->setCellValue('H'.$filaInicio, 'Usuario');
            $sheet->setCellValue('I'.$filaInicio, 'Método de Pago');
            $sheet->setCellValue('J'.$filaInicio, 'Caja');
            $sheet->setCellValue('K'.$filaInicio, 'Estado');
            $sheet->setCellValue('L'.$filaInicio, 'Total Bs.');
            
            $sheet->getStyle('A'.$filaInicio.':L'.$filaInicio)->getFont()->setBold(true);
            $sheet->getStyle('A'.$filaInicio.':L'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            
            $fila = $filaInicio + 1;
            $cantidadFacturas = 0;
            $sumaTotalFacturas = 0;
            
            $sql = "SELECT
                        r.idRecibo,
                        r.numeroFactura,
                        r.fechaEmision,
                        r.horaEmision,
                        r.nombreRazonSocial,
                        r.numeroDocumento,
                        r.fechaRegistro,
                        r.tipoRecibo,
                        r.idUsuario,
                        u.usuarioUs,
                        r.cuf,
                        r.nombreCaja,
                        r.metodoPago,
                        r.estadoFactura,
                        r.montoTotal,
                        r.descuentoAdicional
                    FROM recibos AS r
                    LEFT JOIN usuarios AS u ON u.idUsuario = r.idUsuario
                    WHERE r.fechaEmision >= '$fechaInicio'
                    AND r.fechaEmision <= '$fechaFinal'
                    $condicionUsuario
                    $condicionTipoVenta
                    $condicionCaja
                    $condicionMetodoPago
                    $condicionEstadoFactura";
            $conRecibos = mysqli_query($link, $sql) or die(mysqli_error($link));
            
            if(mysqli_num_rows($conRecibos) > 0){
                while($rowRecibo = mysqli_fetch_array($conRecibos)){
                    $cantidadFacturas++;
                    $sumaTotalFacturas += $rowRecibo['montoTotal'];
                    $gestion = date('Y', strtotime($rowRecibo['fechaEmision']));
                    $fechaEmision = strtotime($rowRecibo['fechaEmision']);
                    $numeroMes = (int)date('n', $fechaEmision);
                    $mes = $meses[$numeroMes];
                    
                    $sheet->setCellValue('A'.$fila, $rowRecibo['numeroFactura']);
                    $sheet->setCellValue('B'.$fila, $gestion);
                    $sheet->setCellValue('C'.$fila, $mes);
                    $sheet->setCellValue('D'.$fila, $rowRecibo['fechaEmision'].' '.$rowRecibo['horaEmision']);
                    $sheet->setCellValue('E'.$fila, $rowRecibo['nombreRazonSocial']);
                    $sheet->setCellValue('F'.$fila, $rowRecibo['numeroDocumento']);
                    $sheet->setCellValue('G'.$fila, $rowRecibo['tipoRecibo']);
                    $sheet->setCellValue('H'.$fila, $rowRecibo['usuarioUs']);
                    $sheet->setCellValue('I'.$fila, $rowRecibo['metodoPago']);
                    $sheet->setCellValue('J'.$fila, $rowRecibo['nombreCaja']);
                    $sheet->setCellValue('K'.$fila, $rowRecibo['estadoFactura']);
                    $sheet->setCellValue('L'.$fila, $rowRecibo['montoTotal']);
                    $sheet->getStyle('L'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
                    $fila++;
                }
            }
            
            $sheet->setCellValue('K'.$fila, 'Total Facturas:');
            $sheet->setCellValue('L'.$fila, $cantidadFacturas);
            $sheet->getStyle('K'.$fila.':L'.$fila)->getFont()->setBold(true);
            
            $fila++;
            $sheet->setCellValue('K'.$fila, 'TOTAL INGRESOS:');
            $sheet->setCellValue('L'.$fila, $sumaTotalFacturas);
            $sheet->getStyle('K'.$fila.':L'.$fila)->getFont()->setBold(true);
            $sheet->getStyle('L'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
            break;
            
        case "DETALLADO_INGRESOS":
            $sheet->setCellValue('A'.$filaInicio, 'Nro. Factura');
            $sheet->setCellValue('B'.$filaInicio, 'Gestión');
            $sheet->setCellValue('C'.$filaInicio, 'Mes');
            $sheet->setCellValue('D'.$filaInicio, 'Fecha Emisión');
            $sheet->setCellValue('E'.$filaInicio, 'Hora Emisión');
            $sheet->setCellValue('F'.$filaInicio, 'Nombre/Razón Social');
            $sheet->setCellValue('G'.$filaInicio, 'Nro. Documento');
            $sheet->setCellValue('H'.$filaInicio, 'Producto');
            $sheet->setCellValue('I'.$filaInicio, 'Tipo Venta');
            $sheet->setCellValue('J'.$filaInicio, 'Método de Pago');
            $sheet->setCellValue('K'.$filaInicio, 'Usuario');
            $sheet->setCellValue('L'.$filaInicio, 'Caja');
            $sheet->setCellValue('M'.$filaInicio, 'Repositorio');
            $sheet->setCellValue('N'.$filaInicio, 'Cantidad');
            $sheet->setCellValue('O'.$filaInicio, 'Precio Unitario');
            $sheet->setCellValue('P'.$filaInicio, 'Monto Descuento');
            $sheet->setCellValue('Q'.$filaInicio, 'Sub Total');
            $sheet->setCellValue('R'.$filaInicio, 'Descuento Adicional');
            $sheet->setCellValue('S'.$filaInicio, 'Total');
            
            $sheet->getStyle('A'.$filaInicio.':S'.$filaInicio)->getFont()->setBold(true);
            $sheet->getStyle('A'.$filaInicio.':S'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            
            $fila = $filaInicio + 1;
            $cantidadProductos = 0;
            $sumaTotalVendido = 0;
            $sumaTotalConDescuentoAdicional = 0;
            
            $sql = "SELECT
                        r.idRecibo,
                        r.numeroFactura,
                        r.fechaEmision,
                        r.horaEmision,
                        r.nombreRazonSocial,
                        COALESCE(p.descripcion, d.descripcion) AS nombreProducto,
                        r.numeroDocumento,
                        r.tipoRecibo,
                        d.repositorio,
                        r.metodoPago,
                        r.nombreCaja,
                        u.usuarioUs,
                        d.cantidad,
                        d.precioUnitario,
                        d.montoDescuento,
                        d.subTotal,
                        r.descuentoAdicional,
                        r.montoTotal,
                        (SELECT COUNT(*) FROM recibos_det WHERE idRecibo = r.idRecibo) AS totalProductosRecibo
                    FROM recibos_det AS d
                    INNER JOIN recibos AS r ON r.idRecibo = d.idRecibo
                    LEFT JOIN productos AS p ON p.codigoProducto = d.codigoProducto
                    LEFT JOIN usuarios AS u ON u.idUsuario = r.idUsuario
                    WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal'
                        $condicionUsuario
                        $condicionTipoVenta
                        $condicionCaja
                        $condicionMetodoPago
                        $condicionRepositorio
                        $condicionEstadoFactura
                    ORDER BY r.fechaEmision, r.numeroFactura, d.idReciboDet";
            $conRecibosDet = mysqli_query($link, $sql) or die(mysqli_error($link));
            
            if(mysqli_num_rows($conRecibosDet) > 0){
                while($rowReciboDet = mysqli_fetch_array($conRecibosDet)){
                    $cantidadProductos += $rowReciboDet['cantidad'];
                    $sumaTotalVendido += $rowReciboDet['subTotal'];
                    
                    // Calcular el descuento adicional por producto
                    $totalProductosRecibo = $rowReciboDet['totalProductosRecibo'];
                    $descuentoAdicionalTotal = $rowReciboDet['descuentoAdicional'];
                    $descuentoAdicionalPorProducto = 0;
                    
                    if($totalProductosRecibo > 0 && $descuentoAdicionalTotal > 0){
                        $descuentoAdicionalPorProducto = $descuentoAdicionalTotal / $totalProductosRecibo;
                    }
                    
                    // Calcular el total
                    $total = $rowReciboDet['subTotal'] - $descuentoAdicionalPorProducto;
                    $sumaTotalConDescuentoAdicional += $total;
                    
                    $gestion = date('Y', strtotime($rowReciboDet['fechaEmision']));
                    $fechaEmision = strtotime($rowReciboDet['fechaEmision']);
                    $numeroMes = (int)date('n', $fechaEmision);
                    $mes = $meses[$numeroMes];
                    
                    $sheet->setCellValue('A'.$fila, $rowReciboDet['numeroFactura']);
                    $sheet->setCellValue('B'.$fila, $gestion);
                    $sheet->setCellValue('C'.$fila, $mes);
                    $sheet->setCellValue('D'.$fila, $rowReciboDet['fechaEmision']);
                    $sheet->setCellValue('E'.$fila, $rowReciboDet['horaEmision']);
                    $sheet->setCellValue('F'.$fila, $rowReciboDet['nombreRazonSocial']);
                    $sheet->setCellValue('G'.$fila, $rowReciboDet['numeroDocumento']);
                    $sheet->setCellValue('H'.$fila, $rowReciboDet['nombreProducto']);
                    $sheet->setCellValue('I'.$fila, $rowReciboDet['tipoRecibo']);
                    $sheet->setCellValue('J'.$fila, $rowReciboDet['metodoPago']);
                    $sheet->setCellValue('K'.$fila, $rowReciboDet['usuarioUs']);
                    $sheet->setCellValue('L'.$fila, $rowReciboDet['nombreCaja']);
                    $sheet->setCellValue('M'.$fila, $rowReciboDet['repositorio']);
                    $sheet->setCellValue('N'.$fila, $rowReciboDet['cantidad']);
                    $sheet->setCellValue('O'.$fila, $rowReciboDet['precioUnitario']);
                    $sheet->setCellValue('P'.$fila, $rowReciboDet['montoDescuento']);
                    $sheet->setCellValue('Q'.$fila, $rowReciboDet['subTotal']);
                    $sheet->setCellValue('R'.$fila, $descuentoAdicionalPorProducto);
                    $sheet->setCellValue('S'.$fila, $total);
                    $sheet->getStyle('O'.$fila.':S'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
                    $fila++;
                }
            }
            
            // $sheet->setCellValue('R'.$fila, 'Total Productos:');
            // $sheet->setCellValue('S'.$fila, $cantidadProductos);
            // $sheet->getStyle('R'.$fila.':S'.$fila)->getFont()->setBold(true);
            
            // $fila++;
            // $sheet->setCellValue('R'.$fila, 'TOTAL VENDIDO:');
            // $sheet->setCellValue('S'.$fila, $sumaTotalVendido);
            // $sheet->getStyle('R'.$fila.':S'.$fila)->getFont()->setBold(true);
            // $sheet->getStyle('S'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
            break;
            
        case "RESUMEN_PRODUCTOS":
            $sheet->setCellValue('A'.$filaInicio, 'Repositorio');
            $sheet->setCellValue('B'.$filaInicio, 'Caja');
            $sheet->setCellValue('C'.$filaInicio, 'Código Producto');
            $sheet->setCellValue('D'.$filaInicio, 'Descripción');
            $sheet->setCellValue('E'.$filaInicio, 'Total Cantidad');
            $sheet->setCellValue('F'.$filaInicio, 'Precio Promedio');
            
            $sheet->getStyle('A'.$filaInicio.':F'.$filaInicio)->getFont()->setBold(true);
            $sheet->getStyle('A'.$filaInicio.':F'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            
            $fila = $filaInicio + 1;
            
            $sql = "SELECT
                        d.codigoProducto,
                        d.descripcion,
                        d.repositorio,
                        r.nombreCaja,
                        SUM(d.cantidad) AS totalCantidad,
                        AVG(d.precioUnitario) AS precioPromedio,
                        SUM(d.subTotal) AS totalVendido
                    FROM recibos_det AS d
                    INNER JOIN recibos AS r ON r.idRecibo = d.idRecibo
                    WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal'
                        $condicionUsuario
                        $condicionTipoVenta
                        $condicionCaja
                        $condicionMetodoPago
                        $condicionEstadoFactura
                    GROUP BY d.repositorio, d.codigoProducto
                    ORDER BY totalCantidad DESC";
            $conResumenProductos = mysqli_query($link, $sql) or die(mysqli_error($link));
            
            if(mysqli_num_rows($conResumenProductos) > 0){
                while($rowResumenProducto = mysqli_fetch_array($conResumenProductos)){
                    $sheet->setCellValue('A'.$fila, $rowResumenProducto['repositorio']);
                    $sheet->setCellValue('B'.$fila, $rowResumenProducto['nombreCaja']);
                    $sheet->setCellValue('C'.$fila, $rowResumenProducto['codigoProducto']);
                    $sheet->setCellValue('D'.$fila, $rowResumenProducto['descripcion']);
                    $sheet->setCellValue('E'.$fila, $rowResumenProducto['totalCantidad']);
                    $sheet->setCellValue('F'.$fila, $rowResumenProducto['precioPromedio']);
                    $sheet->getStyle('F'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
                    $fila++;
                }
            }
            break;
    }
    
    // Ajustar el ancho de todas las columnas posibles
    foreach(range('A','R') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $ultimaFila = $fila;
    $ultimaColumna = 'F';
    if($tipoReporte == 'RESUMEN_INGRESOS') $ultimaColumna = 'L';
    else if($tipoReporte == 'DETALLADO_INGRESOS') $ultimaColumna = 'R';
    else if($tipoReporte == 'RESUMEN_TICKETS') $ultimaColumna = 'O';
    else if($tipoReporte == 'DETALLADO_TICKETS') $ultimaColumna = 'P';
    $sheet->getStyle('A'.$filaInicio.':'.$ultimaColumna.$ultimaFila)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $nombreArchivo = 'Reporte_'.$tipoReporte.'_'.date('Ymd_His').'.xlsx';
    $rutaArchivo = '../../storage/temp/'.$nombreArchivo;
    $writer->save($rutaArchivo);

    $rutaDescarga = 'storage/temp/'.$nombreArchivo;
    
    echo "<div class='col-md-12 text-center'>";
        echo "<a href='".$rutaDescarga."' class='btn btn-excel' target='_blank'>Descargar Reporte Excel</a>";
    echo "</div>";
    
}


function verTicket(){
    global $link;
    global $input;

    // Cambiar header a HTML ya que devolvemos HTML del modal
    header("Content-Type: text/html; charset=UTF-8");

    $hash = $input['hash'];

    $sql = "SELECT 
                t.*,
                u.usuarioUs
            FROM tickets AS t
            LEFT JOIN usuarios AS u ON u.idUsuario = t.usuario
            WHERE t.hash = '".mysqli_real_escape_string($link, $hash)."'";
    
    $conTicket = mysqli_query($link, $sql) or die(mysqli_error($link));
    
    if(mysqli_num_rows($conTicket) > 0){
        $rowTicket = mysqli_fetch_array($conTicket);
        
        // Generar PDF del ticket
        require_once '../../vendor/autoload.php';
        
        // Crear directorio de tickets si no existe
        $directorioTickets = '../../storage/tickets';
        if (!file_exists($directorioTickets)) {
            mkdir($directorioTickets, 0755, true);
        }
        
        // Crear directorio para códigos QR si no existe
        $directorioQR = '../../storage/temp/QR';
        if (!file_exists($directorioQR)) {
            mkdir($directorioQR, 0755, true);
        }
        
        // Generar código QR del hash
        $nombreArchivoQR = 'qr_' . $hash . '.png';
        $rutaQR = $directorioQR . '/' . $nombreArchivoQR;
        if (!file_exists($rutaQR)) {
            QRcode::png($hash, $rutaQR, QR_ECLEVEL_L, 8, 2);
        }
        
        // Convertir ruta relativa a absoluta para mPDF
        $rutaQRAbsoluta = realpath($rutaQR);
        if ($rutaQRAbsoluta === false) {
            // Si realpath falla, construir ruta absoluta manualmente
            $rutaQRAbsoluta = __DIR__ . '/../../storage/temp/QR/' . $nombreArchivoQR;
        }
        
        // Calcular total
        $total = $rowTicket['precio'] - $rowTicket['descuento'];
        $usuarioNombre = $rowTicket['usuarioUs'] ? $rowTicket['usuarioUs'] : "Usuario ".$rowTicket['usuario'];
        
        // Configurar mPDF similar a genPDF (formato ROLLO)
        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 297],
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
        ]);
        
        // Contenido del PDF del ticket
        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Tahoma, Geneva, Verdana, sans-serif; font-size: 9px; }
                .ctr { text-align: center; }
                .lh { line-height: 1.3; }
                .sep { border-top: 1px dashed #000; margin: 4px 0; }
                .small { font-size: 7px; word-break: break-all; }
                .title { font-size: 12px; font-weight: bold; color: #28a745; }
                img { display: block; margin: 4px auto; }
            </style>
        </head>
        <body>
            <div class="ctr title">TICKET</div>
            <div class="sep"></div>
            <div class="lh">
                <div class="ctr" style="font-size: 11px;">
                    <strong>Nro Ticket:</strong> ' . $rowTicket['numeroTicket'] . '
                </div>
                <strong>Producto:</strong> ' . htmlspecialchars($rowTicket['nombreTicket']) . ' (' . htmlspecialchars($rowTicket['codTicket']) . ')<br>
                <strong>Precio:</strong> Bs. ' . number_format($rowTicket['precio'], 2) . '<br>
                <strong>Descuento:</strong> Bs. ' . number_format($rowTicket['descuento'], 2) . '<br>
                <strong>Total:</strong> Bs. ' . number_format($total, 2) . '<br>
                <strong>Fecha:</strong> ' . $rowTicket['fechaReg'] . ' - <strong>Hora:</strong> ' . $rowTicket['horaReg'] . '<br>
                <strong>Usuario:</strong> ' . htmlspecialchars($usuarioNombre);
        
        // Agregar campos adicionales si existen
        if (!empty($rowTicket['generoTicket'])) {
            $html .= '<br><strong>Género:</strong> ' . htmlspecialchars($rowTicket['generoTicket']);
        }
        if (!empty($rowTicket['edadTicket'])) {
            $html .= '<br><strong>Edad:</strong> ' . htmlspecialchars($rowTicket['edadTicket']);
        }
        if (!empty($rowTicket['nacionalidadTIcket'])) {
            $html .= '<br><strong>Nacionalidad:</strong> ' . htmlspecialchars($rowTicket['nacionalidadTIcket']);
        }
        if (!empty($rowTicket['guiaTicket'])) {
            $html .= '<br><strong>Guía:</strong> ' . htmlspecialchars($rowTicket['guiaTicket']);
        }
        if (!empty($rowTicket['controlIngreso'])) {
            $html .= '<br><strong>Control Ingreso:</strong> ' . htmlspecialchars($rowTicket['controlIngreso']);
        }
        
        $html .= '
            </div>
            <div class="sep"></div>
            <img src="' . $rutaQRAbsoluta . '" width="100" height="100" />
            <div class="sep"></div>
            <div class="small"><strong>Hash:</strong> ' . $hash . '</div>
        </body>
        </html>';

        // Escribir HTML completo
        $pdf->WriteHTML($html);

        // Nombre del archivo PDF
        $nombreArchivo = 'ticket_'.$hash.'.pdf';
        $rutaCompleta = $directorioTickets . '/' . $nombreArchivo;
        $rutaPDF = 'storage/tickets/' . $nombreArchivo;
        
        // Generar URL completa
        $urlBase = obtenerUrlBase();
        $urlCompletaPDF = $urlBase . '/' . $rutaPDF;

        // Guardar el PDF en el directorio
        $pdf->Output($rutaCompleta, 'F');
        
        // Mostrar modal con iframe del PDF
        echo "<div class='modal-header'>";
            echo "<h4 class='modal-title mt-0' id=''>Detalle del Ticket #".$rowTicket['numeroTicket']."</h4>";
            echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
                echo "<i class='fas fa-times'></i>";
            echo "</button>";
        echo "</div>";
        echo "<div class='modal-body'>";
            echo "<div class='row'>";
                echo "<div class='col-md-12'>";
                    echo "<iframe src='".$urlCompletaPDF."' style='width: 100%; height: 600px; border: 1px solid #ddd;' frameborder='0'></iframe>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
        echo "<div class='modal-footer'>";
            echo "<a href='".$urlCompletaPDF."' target='_blank' class='btn btn-primary waves-effect'>Abrir en nueva pestaña</a>";
            echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
        echo "</div>";
    } else {
        echo "<div class='modal-header'>";
            echo "<h4 class='modal-title mt-0'>Ticket no encontrado</h4>";
            echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
                echo "<i class='fas fa-times'></i>";
            echo "</button>";
        echo "</div>";
        echo "<div class='modal-body'>";
            echo "<p>No se encontró el ticket con el hash proporcionado.</p>";
        echo "</div>";
        echo "<div class='modal-footer'>";
            echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
        echo "</div>";
    }
}

