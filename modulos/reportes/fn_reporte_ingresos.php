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
    case "formParametrosReporteIngresos":
        formParametrosReporteIngresos();
        break;
    case "generarReporte":
        generarReporte();
        break;
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}


function formParametrosReporteIngresos(){
    global $link;
    global $input;

    date_default_timezone_set('America/La_Paz');

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border-0 shadow-sm' style='border-radius: 8px;'>";
                echo "<div class='card-header bg-primary text-white'>";
                    echo "<h6 class='mb-0'><i class='fas fa-filter me-2'></i>Parámetros del Reporte de Ingresos</h6>";
                echo "</div>";
                echo "<div class='card-body p-3'>";
                    echo "<div class='row g-2'>";
                        // Primera fila - Fechas, Usuario y Tipo Venta
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
                                echo "<option value='RESUMEN_INGRESOS'>Resumen Ingresos (Recibos)</option>";
                                echo "<option value='DETALLADO_INGRESOS'>Detallado Ingresos (Recibos)</option>";
                                echo "<option value='RESUMEN_TRATAMIENTOS'>Resumen por Tratamientos</option>";
                                echo "<option value='PENDIENTES_COBRO'>Pendientes de Cobro (Deudas)</option>";
                            echo "</select>";
                        echo "</div>";

                        echo "<div class='col-md-3 col-sm-6'>";
                            echo "<label for='estadoRecibo' class='form-label small mb-1'><i class='fas fa-receipt me-1'></i>Estado Recibo</label>";
                            echo "<select class='form-select form-select-sm select2-multiple' id='estadoRecibo' name='estadoRecibo[]' multiple>";
                                echo "<option value=''>TODOS</option>";
                                $conEstadosRec = mysqli_query($link, "SELECT `estadoRecibo` FROM `recibos` WHERE `estadoRecibo` IS NOT NULL AND `estadoRecibo` != '' GROUP BY `estadoRecibo` ORDER BY `estadoRecibo` ASC") or die(mysqli_error($link));
                                if($conEstadosRec && mysqli_num_rows($conEstadosRec) > 0){
                                    while($rowEst = mysqli_fetch_array($conEstadosRec)){
                                        echo "<option value='".htmlspecialchars($rowEst['estadoRecibo'])."'>".htmlspecialchars($rowEst['estadoRecibo'])."</option>";
                                    }
                                } else {
                                    echo "<option value='PAGADO'>PAGADO</option>";
                                }
                            echo "</select>";
                        echo "</div>";

                        echo "<div class='col-md-6 col-sm-6'>";
                            echo "<label for='idUsuario' class='form-label small mb-1'><i class='fas fa-user me-1'></i>Usuario (quien cobró)</label>";
                            echo "<select class='form-select form-select-sm select2-multiple' id='idUsuario' name='idUsuario[]' multiple>";
                                $conUsuarios = mysqli_query($link, "SELECT `idUsuario`, `nombreUs`, `primerApUs`, `segundoApUs`, `usuarioUs` FROM `usuarios` ") or die(mysqli_error($link));
                                if(mysqli_num_rows($conUsuarios) > 0){
                                    while($rowUsuario = mysqli_fetch_array($conUsuarios)){
                                        $idUsuario = $rowUsuario['idUsuario'];
                                        echo "<option value='".$idUsuario."'>".$rowUsuario['nombreUs']." ".$rowUsuario['primerApUs']." ".$rowUsuario['segundoApUs']." (".$rowUsuario['usuarioUs'].")</option>";
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

    $fechaInicio = isset($datosReporte['fechaInicio']) ? $datosReporte['fechaInicio'] : date('Y-m-d');
    $fechaFinal = isset($datosReporte['fechaFinal']) ? $datosReporte['fechaFinal'] : date('Y-m-d');
    $idUsuario = isset($datosReporte['idUsuario']) ? $datosReporte['idUsuario'] : [];
    $tipoReporte = isset($datosReporte['tipoReporte']) ? $datosReporte['tipoReporte'] : 'RESUMEN_INGRESOS';
    $formato = isset($datosReporte['formato']) ? $datosReporte['formato'] : 'HTML';
    $estadoRecibo = isset($datosReporte['estadoRecibo']) ? $datosReporte['estadoRecibo'] : [];

    $usuarioLog = isset($_SESSION['usuarioUs_clinicloud']) ? $_SESSION['usuarioUs_clinicloud'] : (isset($_SESSION['usuarioUs_TikeartePOS']) ? $_SESSION['usuarioUs_TikeartePOS'] : 'Sistema');
    if (function_exists('registrarLog')) {
        registrarLog("INFO", $usuarioLog." - Generando reporte: ".$tipoReporte." - Formato: ".$formato." - Período: ".$fechaInicio." a ".$fechaFinal);
    }

    echo "<div class='col-md-12'>";
        echo "<div class='card border'>";
            echo "<div class='card-header'>";
                echo "<b>Generando Reporte</b>";
            echo "</div>";
            echo "<div class='card-body'>";
                echo "<div class='row'>";
                    switch($formato){
                        case "HTML":
                            generarReporteHTML($fechaInicio, $fechaFinal, $idUsuario, $tipoReporte, $estadoRecibo);
                            break;
                        case "PDF":
                            generarReportePDF($fechaInicio, $fechaFinal, $idUsuario, $tipoReporte, $estadoRecibo);
                            break;
                        case "EXCEL":
                            generarReporteEXCEL($fechaInicio, $fechaFinal, $idUsuario, $tipoReporte, $estadoRecibo);
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


function generarReporteHTML($fechaInicio, $fechaFinal, $idUsuario, $tipoReporte, $estadoRecibo){
    global $link;

    $condicionUsuario = "";
    if(is_array($idUsuario) && count($idUsuario) > 0){
        $usuariosEscapados = array_map(function($u) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $u) . "'"; 
        }, $idUsuario);
        $condicionUsuario = " AND r.idUsuario IN (" . implode(',', $usuariosEscapados) . ") ";
    } else if(!is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){
        $condicionUsuario = " AND r.idUsuario = '" . mysqli_real_escape_string($link, $idUsuario) . "' ";
    }

    $condicionEstadoRecibo = "";
    if(is_array($estadoRecibo) && count($estadoRecibo) > 0){
        $estadosEscapados = array_map(function($e) use ($link) { 
            return "'" . mysqli_real_escape_string($link, $e) . "'"; 
        }, array_filter($estadoRecibo));
        if(count($estadosEscapados) > 0){
            $condicionEstadoRecibo = " AND r.estadoRecibo IN (" . implode(',', $estadosEscapados) . ") ";
        }
    }

    $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
              7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];

    switch($tipoReporte){
        case "RESUMEN_INGRESOS":
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover' id='tablaReporte'>";
                    echo "<thead><tr>";
                    echo "<th>Nro. Recibo</th><th>Gestión</th><th>Mes</th><th>Fecha Registro</th>";
                    echo "<th>Paciente</th><th>CI</th><th>Tratamiento</th><th>Usuario Cobró</th><th>Estado</th><th>Monto Bs.</th>";
                    echo "</tr></thead><tbody>";

                        $cantidadRecibos = 0;
                        $sumaTotal = 0;
                        $sql = "SELECT r.idRecibo, r.idPaciente, r.idUsuario, r.codTratamiento, r.descripcionTratamiento,
                                r.montoPagado, r.fechaRegistro, r.estadoRecibo, r.idOrdenAtencion,
                                p.apellidoPat, p.apellidoMat, p.nombres, p.ci,
                                CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreUsuario
                                FROM recibos r
                                LEFT JOIN pacientes p ON p.idPaciente = r.idPaciente
                                LEFT JOIN usuarios u ON u.idUsuario = r.idUsuario
                                WHERE DATE(r.fechaRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."'
                                AND DATE(r.fechaRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."'
                                $condicionUsuario $condicionEstadoRecibo
                                ORDER BY r.fechaRegistro DESC";
                        $conRecibos = mysqli_query($link, $sql) or die(mysqli_error($link));
                        if($conRecibos && mysqli_num_rows($conRecibos) > 0){
                            while($row = mysqli_fetch_array($conRecibos)){
                                $cantidadRecibos++;
                                $sumaTotal += (float)$row['montoPagado'];
                                $nombrePac = trim(($row['apellidoPat'] ?? '').' '.($row['apellidoMat'] ?? '').' '.($row['nombres'] ?? ''));
                                $gestion = date('Y', strtotime($row['fechaRegistro']));
                                $numeroMes = (int)date('n', strtotime($row['fechaRegistro']));
                                $mes = $meses[$numeroMes];
                                $estado = strtoupper($row['estadoRecibo'] ?? '');
                                $badge = $estado === 'PAGADO' ? "<span class='badge bg-success'>PAGADO</span>" : "<span class='badge bg-secondary'>".htmlspecialchars($row['estadoRecibo'])."</span>";
                                echo "<tr>";
                                echo "<td>".(int)$row['idRecibo']."</td><td>".$gestion."</td><td>".$mes."</td>";
                                echo "<td>".htmlspecialchars($row['fechaRegistro'])."</td>";
                                echo "<td>".htmlspecialchars($nombrePac)."</td><td>".htmlspecialchars($row['ci'] ?? '')."</td>";
                                echo "<td>".htmlspecialchars($row['descripcionTratamiento'] ?? $row['codTratamiento'])."</td>";
                                echo "<td>".htmlspecialchars($row['nombreUsuario'] ?? '')."</td><td>".$badge."</td>";
                                echo "<td style='text-align:right'>".number_format((float)$row['montoPagado'], 2, '.', ',')."</td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody></table>";
            echo "</div>";
            echo "<div class='col-md-12 m-auto'><div class='row mt-3 mb-2'>";
            echo "<div class='col-md-3 col-6 mb-2'><div class='card border-primary h-100'><div class='card-body text-center'>";
            echo "<h6 class='card-title mb-1'>Total Recibos</h6><span class='display-6 text-primary'>".$cantidadRecibos."</span></div></div></div>";
            echo "<div class='col-md-3 col-6 mb-2'><div class='card border-success h-100'><div class='card-body text-center'>";
            echo "<h6 class='card-title mb-1'>Total Ingresos Bs.</h6><span class='display-6 text-success'>".number_format($sumaTotal, 2, '.', ',')."</span></div></div></div>";
            echo "</div></div>";
        break;

        case "DETALLADO_INGRESOS":
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover' id='tablaReporte'>";
                    echo "<thead><tr>";
                    echo "<th>Nro. Recibo</th><th>Fecha Registro</th><th>Paciente</th><th>CI</th><th>Celular</th>";
                    echo "<th>Cód. Tratamiento</th><th>Descripción Tratamiento</th><th>Usuario Cobró</th><th>Estado</th><th>Monto Bs.</th><th>Orden Atención</th>";
                    echo "</tr></thead><tbody>";
                        $sumaDet = 0;
                        $sqlDet = "SELECT r.idRecibo, r.idOrdenAtencion, r.fechaRegistro, r.codTratamiento, r.descripcionTratamiento, r.montoPagado, r.estadoRecibo,
                                p.apellidoPat, p.apellidoMat, p.nombres, p.ci, p.celular,
                                CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreUsuario
                                FROM recibos r
                                LEFT JOIN pacientes p ON p.idPaciente = r.idPaciente
                                LEFT JOIN usuarios u ON u.idUsuario = r.idUsuario
                                WHERE DATE(r.fechaRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."'
                                AND DATE(r.fechaRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."'
                                $condicionUsuario $condicionEstadoRecibo
                                ORDER BY r.fechaRegistro DESC";
                        $conDet = mysqli_query($link, $sqlDet) or die(mysqli_error($link));
                        if($conDet && mysqli_num_rows($conDet) > 0){
                            while($row = mysqli_fetch_array($conDet)){
                                $sumaDet += (float)$row['montoPagado'];
                                $nombrePac = trim(($row['apellidoPat'] ?? '').' '.($row['apellidoMat'] ?? '').' '.($row['nombres'] ?? ''));
                                $estado = strtoupper($row['estadoRecibo'] ?? '');
                                $badge = $estado === 'PAGADO' ? "<span class='badge bg-success'>PAGADO</span>" : "<span class='badge bg-secondary'>".htmlspecialchars($row['estadoRecibo'])."</span>";
                                echo "<tr>";
                                echo "<td>".(int)$row['idRecibo']."</td><td>".htmlspecialchars($row['fechaRegistro'])."</td>";
                                echo "<td>".htmlspecialchars($nombrePac)."</td><td>".htmlspecialchars($row['ci'] ?? '')."</td><td>".htmlspecialchars($row['celular'] ?? '')."</td>";
                                echo "<td>".htmlspecialchars($row['codTratamiento'] ?? '')."</td><td>".htmlspecialchars($row['descripcionTratamiento'] ?? '')."</td>";
                                echo "<td>".htmlspecialchars($row['nombreUsuario'] ?? '')."</td><td>".$badge."</td>";
                                echo "<td style='text-align:right'>".number_format((float)$row['montoPagado'], 2, '.', ',')."</td>";
                                echo "<td>".(int)($row['idOrdenAtencion'] ?? 0)."</td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody></table>";
                echo "<div class='row mt-3 mb-2'><div class='col-md-3 col-6 mb-2'><div class='card border-success h-100'><div class='card-body text-center'>";
                echo "<h6 class='card-title mb-1'>Total Ingresos Bs.</h6><span class='display-6 text-success'>".number_format($sumaDet, 2, '.', ',')."</span></div></div></div></div>";
            echo "</div>";
        break;
        case "RESUMEN_TRATAMIENTOS":
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover' id='tablaReporte'>";
                    echo "<thead><tr>";
                    echo "<th>Cód. Tratamiento</th><th>Descripción Tratamiento</th><th>Cantidad Cobrada</th><th>Monto Total Bs.</th><th>Precio Promedio Bs.</th>";
                    echo "</tr></thead><tbody>";
                        $sqlTrat = "SELECT r.codTratamiento, r.descripcionTratamiento,
                                COUNT(*) AS cantidad, SUM(r.montoPagado) AS montoTotal, AVG(r.montoPagado) AS precioPromedio
                                FROM recibos r
                                WHERE DATE(r.fechaRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."'
                                AND DATE(r.fechaRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."'
                                $condicionUsuario $condicionEstadoRecibo
                                GROUP BY r.codTratamiento, r.descripcionTratamiento
                                ORDER BY montoTotal DESC";
                        $conTrat = mysqli_query($link, $sqlTrat) or die(mysqli_error($link));
                        if($conTrat && mysqli_num_rows($conTrat) > 0){
                            while($row = mysqli_fetch_array($conTrat)){
                                echo "<tr>";
                                echo "<td>".htmlspecialchars($row['codTratamiento'] ?? '')."</td>";
                                echo "<td>".htmlspecialchars($row['descripcionTratamiento'] ?? '')."</td>";
                                echo "<td>".(int)$row['cantidad']."</td>";
                                echo "<td style='text-align:right'>".number_format((float)$row['montoTotal'], 2, '.', ',')."</td>";
                                echo "<td style='text-align:right'>".number_format((float)$row['precioPromedio'], 2, '.', ',')."</td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody></table>";
            echo "</div>";
        break;
        case "PENDIENTES_COBRO":
            $condicionUsuarioRegistro = "";
            if(is_array($idUsuario) && count($idUsuario) > 0){
                $usuariosEscapados = array_map(function($u) use ($link) { return "'" . mysqli_real_escape_string($link, $u) . "'"; }, $idUsuario);
                $condicionUsuarioRegistro = " AND o.idUsuarioRegistro IN (" . implode(',', $usuariosEscapados) . ") ";
            } else if(!is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){
                $condicionUsuarioRegistro = " AND o.idUsuarioRegistro = '" . mysqli_real_escape_string($link, $idUsuario) . "' ";
            }
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover' id='tablaReporte'>";
                    echo "<thead><tr>";
                    echo "<th>Nro. Orden</th><th>Fecha Registro</th><th>Paciente</th><th>CI</th><th>Celular</th>";
                    echo "<th>Tratamiento</th><th>Precio Bs.</th><th>Estado</th><th>Usuario Registró</th>";
                    echo "</tr></thead><tbody>";
                        $sumaPend = 0;
                        $condicionFechaPend = "";
                        if($fechaInicio && $fechaFinal){
                            $condicionFechaPend = " AND DATE(o.fechaHoraRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."'
                                AND DATE(o.fechaHoraRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."' ";
                        }
                        $sqlPend = "SELECT o.idOrdenAtencion, o.fechaHoraRegistro, o.codTratamiento, o.descripcionTratamiento, o.precioTratamiento, o.estado,
                                p.apellidoPat, p.apellidoMat, p.nombres, p.ci, p.celular,
                                CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreUsuarioRegistro
                                FROM orden_atencion o
                                LEFT JOIN pacientes p ON p.idPaciente = o.idPaciente
                                LEFT JOIN usuarios u ON u.idUsuario = o.idUsuarioRegistro
                                WHERE o.estado = 'PENDIENTE' $condicionFechaPend $condicionUsuarioRegistro
                                ORDER BY o.fechaHoraRegistro DESC";
                        $conPend = mysqli_query($link, $sqlPend) or die(mysqli_error($link));
                        if($conPend && mysqli_num_rows($conPend) > 0){
                            while($row = mysqli_fetch_array($conPend)){
                                $sumaPend += (float)$row['precioTratamiento'];
                                $nombrePac = trim(($row['apellidoPat'] ?? '').' '.($row['apellidoMat'] ?? '').' '.($row['nombres'] ?? ''));
                                echo "<tr>";
                                echo "<td>".(int)$row['idOrdenAtencion']."</td><td>".htmlspecialchars($row['fechaHoraRegistro'])."</td>";
                                echo "<td>".htmlspecialchars($nombrePac)."</td><td>".htmlspecialchars($row['ci'] ?? '')."</td><td>".htmlspecialchars($row['celular'] ?? '')."</td>";
                                echo "<td>".htmlspecialchars($row['descripcionTratamiento'] ?? $row['codTratamiento'])."</td>";
                                echo "<td style='text-align:right'>".number_format((float)$row['precioTratamiento'], 2, '.', ',')."</td>";
                                echo "<td><span class='badge bg-warning text-dark'>".htmlspecialchars($row['estado'])."</span></td>";
                                echo "<td>".htmlspecialchars($row['nombreUsuarioRegistro'] ?? '')."</td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody></table>";
                echo "<div class='row mt-3 mb-2'><div class='col-md-3 col-6 mb-2'><div class='card border-warning h-100'><div class='card-body text-center'>";
                echo "<h6 class='card-title mb-1'>Total Deuda Pendiente Bs.</h6><span class='display-6 text-warning'>".number_format($sumaPend, 2, '.', ',')."</span></div></div></div></div>";
            echo "</div>";
        break;
    }




}


function generarReportePDF($fechaInicio, $fechaFinal, $idUsuario, $tipoReporte, $estadoRecibo){
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
    
    $nombreUsuario = "TODOS";
    if(is_array($idUsuario) && count($idUsuario) > 0){
        $idsEscapados = array_map(function($u) use ($link) { return "'" . mysqli_real_escape_string($link, $u) . "'"; }, $idUsuario);
        $sqlUsuarios = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario IN (" . implode(',', $idsEscapados) . ")";
        $resultUsuarios = mysqli_query($link, $sqlUsuarios);
        $nombres = [];
        while($rowUsuario = mysqli_fetch_array($resultUsuarios)){ $nombres[] = $rowUsuario['nombreCompleto']; }
        if(count($nombres) > 0){ $nombreUsuario = implode(', ', $nombres); }
    } else if(!is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){
        $sqlUsuario = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario = '".mysqli_real_escape_string($link, $idUsuario)."'";
        $resultUsuario = mysqli_query($link, $sqlUsuario);
        if($rowUsuario = mysqli_fetch_array($resultUsuario)){ $nombreUsuario = $rowUsuario['nombreCompleto']; }
    }
    
    $condicionUsuario = "";
    if(is_array($idUsuario) && count($idUsuario) > 0){
        $usuariosEscapados = array_map(function($u) use ($link) { return "'" . mysqli_real_escape_string($link, $u) . "'"; }, $idUsuario);
        $condicionUsuario = " AND r.idUsuario IN (" . implode(',', $usuariosEscapados) . ") ";
    } else if(!is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){
        $condicionUsuario = " AND r.idUsuario = '" . mysqli_real_escape_string($link, $idUsuario) . "' ";
    }
    $condicionEstadoRecibo = "";
    if(is_array($estadoRecibo) && count($estadoRecibo) > 0){
        $estadosEscapados = array_map(function($e) use ($link) { return "'" . mysqli_real_escape_string($link, $e) . "'"; }, array_filter($estadoRecibo));
        if(count($estadosEscapados) > 0){ $condicionEstadoRecibo = " AND r.estadoRecibo IN (" . implode(',', $estadosEscapados) . ") "; }
    }
    
    $estadoReciboTexto = "TODOS";
    if(is_array($estadoRecibo) && count(array_filter($estadoRecibo)) > 0){ $estadoReciboTexto = implode(', ', $estadoRecibo); }
    
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte de Ingresos</title>
    <style>body{font-family:Arial,sans-serif;font-size:7px}.header{text-align:center;margin-bottom:15px}
    .header h1{margin:0;font-size:14px}.header p{margin:3px 0;font-size:8px}table{width:100%;border-collapse:collapse;margin-bottom:15px}
    th,td{border:1px solid #000;padding:2px;text-align:left;font-size:7px}th{background-color:#f0f0f0;font-weight:bold}
    .text-right{text-align:right}.summary{margin-top:15px;padding:8px;background-color:#f9f9f9;font-size:10px;font-weight:bold}</style></head><body>
    <div class="header"><h1>REPORTE DE INGRESOS - CLINICLOUD</h1><p><strong>Tipo:</strong> '.$tipoReporte.'</p>
    <p><strong>Fecha:</strong> '.$fechaInicio.' - '.$fechaFinal.'</p><p><strong>Usuario:</strong> '.$nombreUsuario.'</p>
    <p><strong>Estado Recibo:</strong> '.$estadoReciboTexto.'</p><p><strong>Generado:</strong> '.date('d/m/Y H:i:s').'</p></div>';
    
    $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
              7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];

    switch($tipoReporte){
        case "RESUMEN_INGRESOS":
            $html .= '<table><thead><tr><th>Nro.Recibo</th><th>Gestión</th><th>Mes</th><th>Fecha</th><th>Paciente</th><th>CI</th><th>Tratamiento</th><th>Usuario Cobró</th><th>Estado</th><th>Monto Bs.</th></tr></thead><tbody>';
            $sql = "SELECT r.idRecibo, r.fechaRegistro, r.codTratamiento, r.descripcionTratamiento, r.montoPagado, r.estadoRecibo,
                    p.apellidoPat, p.apellidoMat, p.nombres, p.ci, CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreUsuario
                    FROM recibos r LEFT JOIN pacientes p ON p.idPaciente = r.idPaciente LEFT JOIN usuarios u ON u.idUsuario = r.idUsuario
                    WHERE DATE(r.fechaRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."' AND DATE(r.fechaRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."' $condicionUsuario $condicionEstadoRecibo ORDER BY r.fechaRegistro DESC";
            $conRecibos = mysqli_query($link, $sql) or die(mysqli_error($link));
            $cantidadRecibos = 0; $sumaTotal = 0;
            if($conRecibos && mysqli_num_rows($conRecibos) > 0){
                while($row = mysqli_fetch_array($conRecibos)){
                    $cantidadRecibos++; $sumaTotal += (float)$row['montoPagado'];
                    $nombrePac = trim(($row['apellidoPat'] ?? '').' '.($row['apellidoMat'] ?? '').' '.($row['nombres'] ?? ''));
                    $gestion = date('Y', strtotime($row['fechaRegistro'])); $numeroMes = (int)date('n', strtotime($row['fechaRegistro'])); $mes = $meses[$numeroMes];
                    $html .= '<tr><td>'.(int)$row['idRecibo'].'</td><td>'.$gestion.'</td><td>'.$mes.'</td><td>'.htmlspecialchars($row['fechaRegistro']).'</td><td>'.htmlspecialchars($nombrePac).'</td><td>'.htmlspecialchars($row['ci'] ?? '').'</td><td>'.htmlspecialchars($row['descripcionTratamiento'] ?? $row['codTratamiento']).'</td><td>'.htmlspecialchars($row['nombreUsuario'] ?? '').'</td><td>'.htmlspecialchars($row['estadoRecibo'] ?? '').'</td><td class="text-right">'.number_format((float)$row['montoPagado'],2,'.',',').'</td></tr>';
                }
            }
            $html .= '</tbody></table><div class="summary">Total Recibos: '.$cantidadRecibos.' | TOTAL INGRESOS: Bs. '.number_format($sumaTotal,2,'.',',').'</div>';
            break;
        case "DETALLADO_INGRESOS":
            $html .= '<table><thead><tr><th>Nro.Recibo</th><th>Fecha</th><th>Paciente</th><th>CI</th><th>Tratamiento</th><th>Usuario Cobró</th><th>Estado</th><th>Monto Bs.</th></tr></thead><tbody>';
            $sql = "SELECT r.idRecibo, r.fechaRegistro, r.descripcionTratamiento, r.codTratamiento, r.montoPagado, r.estadoRecibo,
                    p.apellidoPat, p.apellidoMat, p.nombres, p.ci, CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreUsuario
                    FROM recibos r LEFT JOIN pacientes p ON p.idPaciente = r.idPaciente LEFT JOIN usuarios u ON u.idUsuario = r.idUsuario
                    WHERE DATE(r.fechaRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."' AND DATE(r.fechaRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."' $condicionUsuario $condicionEstadoRecibo ORDER BY r.fechaRegistro DESC";
            $conDet = mysqli_query($link, $sql) or die(mysqli_error($link));
            $sumaDet = 0;
            if($conDet && mysqli_num_rows($conDet) > 0){
                while($row = mysqli_fetch_array($conDet)){
                    $sumaDet += (float)$row['montoPagado'];
                    $nombrePac = trim(($row['apellidoPat'] ?? '').' '.($row['apellidoMat'] ?? '').' '.($row['nombres'] ?? ''));
                    $html .= '<tr><td>'.(int)$row['idRecibo'].'</td><td>'.htmlspecialchars($row['fechaRegistro']).'</td><td>'.htmlspecialchars($nombrePac).'</td><td>'.htmlspecialchars($row['ci'] ?? '').'</td><td>'.htmlspecialchars($row['descripcionTratamiento'] ?? $row['codTratamiento']).'</td><td>'.htmlspecialchars($row['nombreUsuario'] ?? '').'</td><td>'.htmlspecialchars($row['estadoRecibo'] ?? '').'</td><td class="text-right">'.number_format((float)$row['montoPagado'],2,'.',',').'</td></tr>';
                }
            }
            $html .= '</tbody></table><div class="summary">TOTAL INGRESOS: Bs. '.number_format($sumaDet,2,'.',',').'</div>';
            break;
        case "RESUMEN_TRATAMIENTOS":
            $html .= '<table><thead><tr><th>Cód.Tratamiento</th><th>Descripción</th><th>Cantidad</th><th>Monto Total Bs.</th><th>Precio Promedio Bs.</th></tr></thead><tbody>';
            $sql = "SELECT r.codTratamiento, r.descripcionTratamiento, COUNT(*) AS cantidad, SUM(r.montoPagado) AS montoTotal, AVG(r.montoPagado) AS precioPromedio
                    FROM recibos r WHERE DATE(r.fechaRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."' AND DATE(r.fechaRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."' $condicionUsuario $condicionEstadoRecibo GROUP BY r.codTratamiento, r.descripcionTratamiento ORDER BY montoTotal DESC";
            $conTrat = mysqli_query($link, $sql) or die(mysqli_error($link));
            if($conTrat && mysqli_num_rows($conTrat) > 0){
                while($row = mysqli_fetch_array($conTrat)){
                    $html .= '<tr><td>'.htmlspecialchars($row['codTratamiento'] ?? '').'</td><td>'.htmlspecialchars($row['descripcionTratamiento'] ?? '').'</td><td>'.(int)$row['cantidad'].'</td><td class="text-right">'.number_format((float)$row['montoTotal'],2,'.',',').'</td><td class="text-right">'.number_format((float)$row['precioPromedio'],2,'.',',').'</td></tr>';
                }
            }
            $html .= '</tbody></table>';
            break;
        case "PENDIENTES_COBRO":
            $condicionUsuarioRegistro = "";
            if(is_array($idUsuario) && count($idUsuario) > 0){
                $usuariosEscapados = array_map(function($u) use ($link) { return "'" . mysqli_real_escape_string($link, $u) . "'"; }, $idUsuario);
                $condicionUsuarioRegistro = " AND o.idUsuarioRegistro IN (" . implode(',', $usuariosEscapados) . ") ";
            } else if(!is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){ $condicionUsuarioRegistro = " AND o.idUsuarioRegistro = '" . mysqli_real_escape_string($link, $idUsuario) . "' "; }
            $condicionFechaPend = ($fechaInicio && $fechaFinal) ? " AND DATE(o.fechaHoraRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."' AND DATE(o.fechaHoraRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."' " : "";
            $html .= '<table><thead><tr><th>Nro.Orden</th><th>Fecha</th><th>Paciente</th><th>CI</th><th>Tratamiento</th><th>Precio Bs.</th><th>Estado</th><th>Usuario Registró</th></tr></thead><tbody>';
            $sqlPend = "SELECT o.idOrdenAtencion, o.fechaHoraRegistro, o.descripcionTratamiento, o.codTratamiento, o.precioTratamiento, o.estado,
                    p.apellidoPat, p.apellidoMat, p.nombres, p.ci, CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreUsuarioRegistro
                    FROM orden_atencion o LEFT JOIN pacientes p ON p.idPaciente = o.idPaciente LEFT JOIN usuarios u ON u.idUsuario = o.idUsuarioRegistro
                    WHERE o.estado = 'PENDIENTE' $condicionFechaPend $condicionUsuarioRegistro ORDER BY o.fechaHoraRegistro DESC";
            $conPend = mysqli_query($link, $sqlPend) or die(mysqli_error($link));
            $sumaPend = 0;
            if($conPend && mysqli_num_rows($conPend) > 0){
                while($row = mysqli_fetch_array($conPend)){
                    $sumaPend += (float)$row['precioTratamiento'];
                    $nombrePac = trim(($row['apellidoPat'] ?? '').' '.($row['apellidoMat'] ?? '').' '.($row['nombres'] ?? ''));
                    $html .= '<tr><td>'.(int)$row['idOrdenAtencion'].'</td><td>'.htmlspecialchars($row['fechaHoraRegistro']).'</td><td>'.htmlspecialchars($nombrePac).'</td><td>'.htmlspecialchars($row['ci'] ?? '').'</td><td>'.htmlspecialchars($row['descripcionTratamiento'] ?? $row['codTratamiento']).'</td><td class="text-right">'.number_format((float)$row['precioTratamiento'],2,'.',',').'</td><td>'.htmlspecialchars($row['estado']).'</td><td>'.htmlspecialchars($row['nombreUsuarioRegistro'] ?? '').'</td></tr>';
                }
            }
            $html .= '</tbody></table><div class="summary">Total Deuda Pendiente: Bs. '.number_format($sumaPend,2,'.',',').'</div>';
            break;
    }
    
    $html .= '</body></html>';
    $mpdf->WriteHTML($html);
    $nombreArchivo = 'Reporte_'.$tipoReporte.'_'.date('Ymd_His').'.pdf';
    $rutaArchivo = '../../storage/temp/'.$nombreArchivo;
    $mpdf->Output($rutaArchivo, 'F');
    $rutaDescarga = 'storage/temp/'.$nombreArchivo;
    echo "<div class='col-md-12 text-center'><embed src='".$rutaDescarga."' type='application/pdf' width='100%' height='500px'></div>";
}

function generarReporteEXCEL($fechaInicio, $fechaFinal, $idUsuario, $tipoReporte, $estadoRecibo){
    global $link;
    
    require_once '../../vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $nombreUsuario = "TODOS";
    if(is_array($idUsuario) && count($idUsuario) > 0){
        $idsEscapados = array_map(function($u) use ($link) { return "'" . mysqli_real_escape_string($link, $u) . "'"; }, $idUsuario);
        $sqlUsuarios = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario IN (" . implode(',', $idsEscapados) . ")";
        $resultUsuarios = mysqli_query($link, $sqlUsuarios);
        $nombres = [];
        while($rowUsuario = mysqli_fetch_array($resultUsuarios)){ $nombres[] = $rowUsuario['nombreCompleto']; }
        if(count($nombres) > 0){ $nombreUsuario = implode(', ', $nombres); }
    } else if(!is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){
        $sqlUsuario = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario = '".mysqli_real_escape_string($link, $idUsuario)."'";
        $resultUsuario = mysqli_query($link, $sqlUsuario);
        if($rowUsuario = mysqli_fetch_array($resultUsuario)){ $nombreUsuario = $rowUsuario['nombreCompleto']; }
    }
    
    $condicionUsuario = "";
    if(is_array($idUsuario) && count($idUsuario) > 0){
        $usuariosEscapados = array_map(function($u) use ($link) { return "'" . mysqli_real_escape_string($link, $u) . "'"; }, $idUsuario);
        $condicionUsuario = " AND r.idUsuario IN (" . implode(',', $usuariosEscapados) . ") ";
    } else if(!is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){
        $condicionUsuario = " AND r.idUsuario = '" . mysqli_real_escape_string($link, $idUsuario) . "' ";
    }
    $condicionEstadoRecibo = "";
    if(is_array($estadoRecibo) && count($estadoRecibo) > 0){
        $estadosEscapados = array_map(function($e) use ($link) { return "'" . mysqli_real_escape_string($link, $e) . "'"; }, array_filter($estadoRecibo));
        if(count($estadosEscapados) > 0){ $condicionEstadoRecibo = " AND r.estadoRecibo IN (" . implode(',', $estadosEscapados) . ") "; }
    }
    
    $sheet->setTitle('Reporte de Ingresos');
    $sheet->setCellValue('A1', 'REPORTE DE INGRESOS - CLINICLOUD');
    $sheet->setCellValue('A2', 'Tipo: ' . $tipoReporte);
    $sheet->setCellValue('A3', 'Fecha: ' . $fechaInicio . ' - ' . $fechaFinal);
    $sheet->setCellValue('A4', 'Usuario: ' . $nombreUsuario);
    $sheet->setCellValue('A5', 'Generado: ' . date('d/m/Y H:i:s'));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2:A5')->getFont()->setBold(true);
    
    $filaInicio = 7;
    $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
              7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
    
    switch($tipoReporte){
        case "RESUMEN_INGRESOS":
            $sheet->setCellValue('A'.$filaInicio, 'Nro.Recibo'); $sheet->setCellValue('B'.$filaInicio, 'Gestión'); $sheet->setCellValue('C'.$filaInicio, 'Mes'); $sheet->setCellValue('D'.$filaInicio, 'Fecha'); $sheet->setCellValue('E'.$filaInicio, 'Paciente'); $sheet->setCellValue('F'.$filaInicio, 'CI'); $sheet->setCellValue('G'.$filaInicio, 'Tratamiento'); $sheet->setCellValue('H'.$filaInicio, 'Usuario Cobró'); $sheet->setCellValue('I'.$filaInicio, 'Estado'); $sheet->setCellValue('J'.$filaInicio, 'Monto Bs.');
            $sheet->getStyle('A'.$filaInicio.':J'.$filaInicio)->getFont()->setBold(true); $sheet->getStyle('A'.$filaInicio.':J'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            $fila = $filaInicio + 1;
            $sql = "SELECT r.idRecibo, r.fechaRegistro, r.codTratamiento, r.descripcionTratamiento, r.montoPagado, r.estadoRecibo, p.apellidoPat, p.apellidoMat, p.nombres, p.ci, CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreUsuario FROM recibos r LEFT JOIN pacientes p ON p.idPaciente = r.idPaciente LEFT JOIN usuarios u ON u.idUsuario = r.idUsuario WHERE DATE(r.fechaRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."' AND DATE(r.fechaRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."' $condicionUsuario $condicionEstadoRecibo ORDER BY r.fechaRegistro DESC";
            $conRecibos = mysqli_query($link, $sql) or die(mysqli_error($link));
            $cantidadRecibos = 0; $sumaTotal = 0;
            if($conRecibos && mysqli_num_rows($conRecibos) > 0){
                while($row = mysqli_fetch_array($conRecibos)){
                    $cantidadRecibos++; $sumaTotal += (float)$row['montoPagado'];
                    $nombrePac = trim(($row['apellidoPat'] ?? '').' '.($row['apellidoMat'] ?? '').' '.($row['nombres'] ?? ''));
                    $gestion = date('Y', strtotime($row['fechaRegistro'])); $numeroMes = (int)date('n', strtotime($row['fechaRegistro'])); $mes = $meses[$numeroMes];
                    $sheet->setCellValue('A'.$fila, (int)$row['idRecibo']); $sheet->setCellValue('B'.$fila, $gestion); $sheet->setCellValue('C'.$fila, $mes); $sheet->setCellValue('D'.$fila, $row['fechaRegistro']); $sheet->setCellValue('E'.$fila, $nombrePac); $sheet->setCellValue('F'.$fila, $row['ci'] ?? ''); $sheet->setCellValue('G'.$fila, $row['descripcionTratamiento'] ?? $row['codTratamiento']); $sheet->setCellValue('H'.$fila, $row['nombreUsuario'] ?? ''); $sheet->setCellValue('I'.$fila, $row['estadoRecibo'] ?? ''); $sheet->setCellValue('J'.$fila, (float)$row['montoPagado']);
                    $sheet->getStyle('J'.$fila)->getNumberFormat()->setFormatCode('#,##0.00'); $fila++;
                }
            }
            $sheet->setCellValue('I'.$fila, 'Total Recibos:'); $sheet->setCellValue('J'.$fila, $cantidadRecibos); $sheet->getStyle('I'.$fila.':J'.$fila)->getFont()->setBold(true); $fila++;
            $sheet->setCellValue('I'.$fila, 'TOTAL INGRESOS:'); $sheet->setCellValue('J'.$fila, $sumaTotal); $sheet->getStyle('I'.$fila.':J'.$fila)->getFont()->setBold(true); $sheet->getStyle('J'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
            break;
        case "DETALLADO_INGRESOS":
            $sheet->setCellValue('A'.$filaInicio, 'Nro.Recibo'); $sheet->setCellValue('B'.$filaInicio, 'Fecha'); $sheet->setCellValue('C'.$filaInicio, 'Paciente'); $sheet->setCellValue('D'.$filaInicio, 'CI'); $sheet->setCellValue('E'.$filaInicio, 'Tratamiento'); $sheet->setCellValue('F'.$filaInicio, 'Usuario Cobró'); $sheet->setCellValue('G'.$filaInicio, 'Estado'); $sheet->setCellValue('H'.$filaInicio, 'Monto Bs.');
            $sheet->getStyle('A'.$filaInicio.':H'.$filaInicio)->getFont()->setBold(true); $sheet->getStyle('A'.$filaInicio.':H'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            $fila = $filaInicio + 1;
            $sql = "SELECT r.idRecibo, r.fechaRegistro, r.descripcionTratamiento, r.codTratamiento, r.montoPagado, r.estadoRecibo, p.apellidoPat, p.apellidoMat, p.nombres, p.ci, CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreUsuario FROM recibos r LEFT JOIN pacientes p ON p.idPaciente = r.idPaciente LEFT JOIN usuarios u ON u.idUsuario = r.idUsuario WHERE DATE(r.fechaRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."' AND DATE(r.fechaRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."' $condicionUsuario $condicionEstadoRecibo ORDER BY r.fechaRegistro DESC";
            $conDet = mysqli_query($link, $sql) or die(mysqli_error($link));
            if($conDet && mysqli_num_rows($conDet) > 0){
                while($row = mysqli_fetch_array($conDet)){
                    $nombrePac = trim(($row['apellidoPat'] ?? '').' '.($row['apellidoMat'] ?? '').' '.($row['nombres'] ?? ''));
                    $sheet->setCellValue('A'.$fila, (int)$row['idRecibo']); $sheet->setCellValue('B'.$fila, $row['fechaRegistro']); $sheet->setCellValue('C'.$fila, $nombrePac); $sheet->setCellValue('D'.$fila, $row['ci'] ?? ''); $sheet->setCellValue('E'.$fila, $row['descripcionTratamiento'] ?? $row['codTratamiento']); $sheet->setCellValue('F'.$fila, $row['nombreUsuario'] ?? ''); $sheet->setCellValue('G'.$fila, $row['estadoRecibo'] ?? ''); $sheet->setCellValue('H'.$fila, (float)$row['montoPagado']);
                    $sheet->getStyle('H'.$fila)->getNumberFormat()->setFormatCode('#,##0.00'); $fila++;
                }
            }
            break;
        case "RESUMEN_TRATAMIENTOS":
            $sheet->setCellValue('A'.$filaInicio, 'Cód.Tratamiento'); $sheet->setCellValue('B'.$filaInicio, 'Descripción'); $sheet->setCellValue('C'.$filaInicio, 'Cantidad'); $sheet->setCellValue('D'.$filaInicio, 'Monto Total Bs.'); $sheet->setCellValue('E'.$filaInicio, 'Precio Promedio Bs.');
            $sheet->getStyle('A'.$filaInicio.':E'.$filaInicio)->getFont()->setBold(true); $sheet->getStyle('A'.$filaInicio.':E'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            $fila = $filaInicio + 1;
            $sql = "SELECT r.codTratamiento, r.descripcionTratamiento, COUNT(*) AS cantidad, SUM(r.montoPagado) AS montoTotal, AVG(r.montoPagado) AS precioPromedio FROM recibos r WHERE DATE(r.fechaRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."' AND DATE(r.fechaRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."' $condicionUsuario $condicionEstadoRecibo GROUP BY r.codTratamiento, r.descripcionTratamiento ORDER BY montoTotal DESC";
            $conTrat = mysqli_query($link, $sql) or die(mysqli_error($link));
            if($conTrat && mysqli_num_rows($conTrat) > 0){
                while($row = mysqli_fetch_array($conTrat)){
                    $sheet->setCellValue('A'.$fila, $row['codTratamiento'] ?? ''); $sheet->setCellValue('B'.$fila, $row['descripcionTratamiento'] ?? ''); $sheet->setCellValue('C'.$fila, (int)$row['cantidad']); $sheet->setCellValue('D'.$fila, (float)$row['montoTotal']); $sheet->setCellValue('E'.$fila, (float)$row['precioPromedio']);
                    $sheet->getStyle('D'.$fila.':E'.$fila)->getNumberFormat()->setFormatCode('#,##0.00'); $fila++;
                }
            }
            break;
        case "PENDIENTES_COBRO":
            $condicionUsuarioRegistro = "";
            if(is_array($idUsuario) && count($idUsuario) > 0){ $usuariosEscapados = array_map(function($u) use ($link) { return "'" . mysqli_real_escape_string($link, $u) . "'"; }, $idUsuario); $condicionUsuarioRegistro = " AND o.idUsuarioRegistro IN (" . implode(',', $usuariosEscapados) . ") "; }
            else if(!is_array($idUsuario) && $idUsuario != "0" && $idUsuario != ""){ $condicionUsuarioRegistro = " AND o.idUsuarioRegistro = '" . mysqli_real_escape_string($link, $idUsuario) . "' "; }
            $condicionFechaPend = ($fechaInicio && $fechaFinal) ? " AND DATE(o.fechaHoraRegistro) >= '".mysqli_real_escape_string($link, $fechaInicio)."' AND DATE(o.fechaHoraRegistro) <= '".mysqli_real_escape_string($link, $fechaFinal)."' " : "";
            $sheet->setCellValue('A'.$filaInicio, 'Nro.Orden'); $sheet->setCellValue('B'.$filaInicio, 'Fecha'); $sheet->setCellValue('C'.$filaInicio, 'Paciente'); $sheet->setCellValue('D'.$filaInicio, 'CI'); $sheet->setCellValue('E'.$filaInicio, 'Tratamiento'); $sheet->setCellValue('F'.$filaInicio, 'Precio Bs.'); $sheet->setCellValue('G'.$filaInicio, 'Estado'); $sheet->setCellValue('H'.$filaInicio, 'Usuario Registró');
            $sheet->getStyle('A'.$filaInicio.':H'.$filaInicio)->getFont()->setBold(true); $sheet->getStyle('A'.$filaInicio.':H'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            $fila = $filaInicio + 1;
            $sqlPend = "SELECT o.idOrdenAtencion, o.fechaHoraRegistro, o.descripcionTratamiento, o.codTratamiento, o.precioTratamiento, o.estado, p.apellidoPat, p.apellidoMat, p.nombres, p.ci, CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreUsuarioRegistro FROM orden_atencion o LEFT JOIN pacientes p ON p.idPaciente = o.idPaciente LEFT JOIN usuarios u ON u.idUsuario = o.idUsuarioRegistro WHERE o.estado = 'PENDIENTE' $condicionFechaPend $condicionUsuarioRegistro ORDER BY o.fechaHoraRegistro DESC";
            $conPend = mysqli_query($link, $sqlPend) or die(mysqli_error($link));
            if($conPend && mysqli_num_rows($conPend) > 0){
                while($row = mysqli_fetch_array($conPend)){
                    $nombrePac = trim(($row['apellidoPat'] ?? '').' '.($row['apellidoMat'] ?? '').' '.($row['nombres'] ?? ''));
                    $sheet->setCellValue('A'.$fila, (int)$row['idOrdenAtencion']); $sheet->setCellValue('B'.$fila, $row['fechaHoraRegistro']); $sheet->setCellValue('C'.$fila, $nombrePac); $sheet->setCellValue('D'.$fila, $row['ci'] ?? ''); $sheet->setCellValue('E'.$fila, $row['descripcionTratamiento'] ?? $row['codTratamiento']); $sheet->setCellValue('F'.$fila, (float)$row['precioTratamiento']); $sheet->setCellValue('G'.$fila, $row['estado']); $sheet->setCellValue('H'.$fila, $row['nombreUsuarioRegistro'] ?? '');
                    $sheet->getStyle('F'.$fila)->getNumberFormat()->setFormatCode('#,##0.00'); $fila++;
                }
            }
            break;
    }
    
    foreach(range('A','J') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
    $ultimaFila = isset($fila) ? $fila : $filaInicio + 1;
    $ultimaColumna = ($tipoReporte == 'RESUMEN_INGRESOS') ? 'J' : (($tipoReporte == 'DETALLADO_INGRESOS' || $tipoReporte == 'PENDIENTES_COBRO') ? 'H' : 'E');
    $sheet->getStyle('A'.$filaInicio.':'.$ultimaColumna.$ultimaFila)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $nombreArchivo = 'Reporte_'.$tipoReporte.'_'.date('Ymd_His').'.xlsx';
    $rutaArchivo = '../../storage/temp/'.$nombreArchivo;
    $writer->save($rutaArchivo);
    $rutaDescarga = 'storage/temp/'.$nombreArchivo;
    echo "<div class='col-md-12 text-center'><a href='".$rutaDescarga."' class='btn btn-excel' target='_blank'>Descargar Reporte Excel</a></div>";
}


?>