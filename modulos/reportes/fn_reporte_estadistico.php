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

header("Content-Type: application/json");

// Leer datos enviados desde fetch
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

if (!isset($input['funcion'])) {
    echo json_encode(["estado" => "ERROR", "mensaje" => "No se especificó la función a ejecutar."]);
    exit;
}

switch ($input['funcion']) {
    case "formParametrosReporteEstadistico":
        formParametrosReporteEstadistico();
        break;
    case "generarReporte":
        generarReporte();
        break;
    
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function formParametrosReporteEstadistico(){
    global $link;
    global $input;

    date_default_timezone_set('America/La_Paz');

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Parametros del Reporte Estadistico</b>";
                echo "</div>";
                echo "<div class='card-body row'>";

                    echo "<div class='col-md-3 mb-2'>";
                        echo "<label for='fechaInicio' class='form-label'>Fecha inicio</label>";
                        echo "<input type='date' class='form-control' id='fechaInicio' name='fechaInicio' value='".date('Y-m-d')."'>";
                    echo "</div>";
                    echo "<div class='col-md-3 mb-2'>";
                        echo "<label for='fechaFinal' class='form-label'>Fecha final</label>";
                        echo "<input type='date' class='form-control' id='fechaFinal' name='fechaFinal' value='".date('Y-m-d')."'>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='usuario' class='form-label'>Usuario</label>";
                        echo "<select class='form-select' id='usuario' name='usuario' required>";
                            $conUsuarios = mysqli_query($link, "SELECT `idUsuario`, `nombreUs`, `primerApUs`, `segundoApUs`, `usuarioUs` FROM `usuarios` ")or die(mysqli_error($link));
                            if(mysqli_num_rows($conUsuarios) > 0){
                                while($rowUsuario = mysqli_fetch_array($conUsuarios)){
                                    $idUsuario = $rowUsuario['idUsuario'];
                                    echo "<option value='".$idUsuario."'>".$rowUsuario['nombreUs']." ".$rowUsuario['primerApUs']." ".$rowUsuario['segundoApUs']." (".$rowUsuario['usuarioUs'].")</option>";
                                }
                            }
                        echo "</select>";
                    echo "</div>";
                    echo "<div class='col-md-4 mb-2'>";
                        echo "<label for='tipoReporte' class='form-label'>Tipo de reporte</label>";
                        echo "<select class='form-select' id='tipoReporte' name='tipoReporte' required>";
                            echo "<option value='RESUMEN_DIARIO'>Resumen Diario</option>";
                        echo "</select>";
                    echo "</div>";

                    echo "<div class='col-md-4 mb-2 d-flex justify-content-center align-items-center'>";
                        echo "<button class='btn btn-primary me-2' id='btnGenerarReporteHtml'><i class='fas fa-file-alt'></i> HTML</button>";
                        echo "<button class='btn btn-excel me-2' id='btnGenerarReporteExcel'><i class='fas fa-file-excel'></i> Excel</button>";
                        echo "<button class='btn btn-pdf' id='btnGenerarReportePdf'><i class='fas fa-file-pdf'></i> PDF</button>";
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

    echo "<div class='col-md-12'>";
        echo "<div class='card border'>";
            echo "<div class='card-header'>";
                echo "<b>Generando Reporte</b>";
            echo "</div>";
            echo "<div class='card-body'>";
                echo "<div class='row'>";
                    switch($formato){
                        case "HTML":
                            generarReporteHTML($fechaInicio, $fechaFinal, $usuario, $tipoReporte);
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


function generarReporteHTML($fechaInicio, $fechaFinal, $usuario, $tipoReporte){
    global $link;

    $fechaInicio = $fechaInicio;
    $fechaFinal = $fechaFinal;
    $usuario = $usuario;
    $tipoReporte = $tipoReporte;

    switch($tipoReporte){
        case "RESUMEN_DIARIO":
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover'>";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th>Fecha</th>";
                            echo "<th>Usuario (guía)</th>";
                            echo "<th>Nombre Ticket</th>";
                            echo "<th>Total Tickets</th>";
                            echo "<th>Género M</th>";
                            echo "<th>Género F</th>";
                            echo "<th>Edad 0-10</th>";
                            echo "<th>Edad 11-20</th>";
                            echo "<th>Edad 21-50</th>";
                            echo "<th>Edad 50+</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                        /*
                         * Resumen diario de tickets por guía (usuario), nombre de ticket,
                         * género y grupos etáreos dentro del rango de fechas seleccionado.
                         */
                        $sql = "
                            SELECT
                                DATE(t.fechaReg) AS fecha,
                                t.guiaTicket AS idGuia,
                                CONCAT(u.nombreUs, ' ', u.primerApUs, ' ', u.segundoApUs) AS nombreGuia,
                                t.nombreTicket,
                                COUNT(*) AS totalTickets,
                                SUM(CASE WHEN t.generoTicket = 'Masculino' THEN 1 ELSE 0 END) AS generoM,
                                SUM(CASE WHEN t.generoTicket = 'Femenino' THEN 1 ELSE 0 END) AS generoF,
                                SUM(CASE WHEN t.edadTicket BETWEEN 0 AND 10 THEN 1 ELSE 0 END) AS edad_0_10,
                                SUM(CASE WHEN t.edadTicket BETWEEN 11 AND 20 THEN 1 ELSE 0 END) AS edad_11_20,
                                SUM(CASE WHEN t.edadTicket BETWEEN 21 AND 50 THEN 1 ELSE 0 END) AS edad_21_50,
                                SUM(CASE WHEN t.edadTicket > 50 THEN 1 ELSE 0 END) AS edad_50_mas
                            FROM tickets AS t
                            LEFT JOIN usuarios AS u ON u.idUsuario = t.guiaTicket
                            WHERE t.fechaReg >= '$fechaInicio'
                            AND t.fechaReg <= '$fechaFinal'
                            AND t.guiaTicket = '$usuario'
                            GROUP BY DATE(t.fechaReg), t.guiaTicket, t.nombreTicket
                            ORDER BY DATE(t.fechaReg) ASC, nombreGuia ASC, t.nombreTicket ASC
                        ";

                        $conTickets = mysqli_query($link, $sql) or die(mysqli_error($link));
                        if(mysqli_num_rows($conTickets) > 0){
                            while($row = mysqli_fetch_array($conTickets)){
                                echo "<tr>";
                                    echo "<td>".$row['fecha']."</td>";
                                    echo "<td>".$row['nombreGuia']."</td>";
                                    echo "<td>".$row['nombreTicket']."</td>";
                                    echo "<td style='text-align: right;'>".$row['totalTickets']."</td>";
                                    echo "<td style='text-align: right;'>".$row['generoM']."</td>";
                                    echo "<td style='text-align: right;'>".$row['generoF']."</td>";
                                    echo "<td style='text-align: right;'>".$row['edad_0_10']."</td>";
                                    echo "<td style='text-align: right;'>".$row['edad_11_20']."</td>";
                                    echo "<td style='text-align: right;'>".$row['edad_21_50']."</td>";
                                    echo "<td style='text-align: right;'>".$row['edad_50_mas']."</td>";
                                echo "</tr>";
                            }
                        }else{
                            echo "<tr><td colspan='10' class='text-center'>No se encontraron registros en el rango seleccionado.</td></tr>";
                        }
                    echo "</tbody>";
                echo "</table>";
            echo "</div>";
        break;

    }




}

?>