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
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Parametros del Reporte de Ingresos</b>";
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
                        echo "<label for='tipoReporte' class='form-label'>Tipo Venta</label>";
                        echo "<select class='form-select' id='tipoVenta' name='tipoVenta' required>";
                            echo "<option value='TICKET'>TICKET</option>";
                            echo "<option value='SOUVENIR'>SOUVENIR</option>";
                            echo "<option value='LIBRO'>LIBRO</option>";
                        echo "</select>";
                    echo "</div>";
                    echo "<div class='col-md-4 mb-2'>";
                        echo "<label for='tipoReporte' class='form-label'>Tipo de reporte</label>";
                        echo "<select class='form-select' id='tipoReporte' name='tipoReporte' required>";
                            echo "<option value='RESUMEN_INGRESOS'>Resumen Ingresos</option>";
                            echo "<option value='DETALLADO_INGRESOS'>Detallado Ingresos</option>";
                            echo "<option value='RESUMEN_PRODUCTOS'>Resumen  por Productos</option>";
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
    $tipoVenta = $datosReporte['tipoVenta'];
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
                            generarReporteHTML($fechaInicio, $fechaFinal, $usuario, $tipoVenta, $tipoReporte);
                            break;
                        case "PDF":
                            generarReportePDF($fechaInicio, $fechaFinal, $usuario, $tipoVenta, $tipoReporte);
                            break;
                        case "EXCEL":
                            generarReporteEXCEL($fechaInicio, $fechaFinal, $usuario, $tipoVenta, $tipoReporte);
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


function generarReporteHTML($fechaInicio, $fechaFinal, $usuario, $tipoVenta, $tipoReporte){
    global $link;
    global $input;

    $fechaInicio = $fechaInicio;
    $fechaFinal = $fechaFinal;
    $usuario = $usuario;
    $tipoVenta = $tipoVenta;
    $tipoReporte = $tipoReporte;

    switch($tipoReporte){
        case "RESUMEN_INGRESOS":
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover'>";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th>Nro. Recibo</th>";
                            echo "<th>Fecha Emisión</th>";
                            echo "<th>Hora Emisión</th>";
                            echo "<th>Nombre/Razón Social</th>";
                            echo "<th>Nro. Documento</th>";
                            echo "<th>Tipo Recibo</th>";
                            echo "<th>Usuario</th>";
                            echo "<th>Total Recibo</th>";
                            //echo "<th>CUF</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                        /* SELECT `idRecibo`, `numeroRecibo`, `fechaEmision`, `horaEmision`, `nombreRazonSocial`, `numeroDocumento`, `fechaRegistro`, `tipoRecibo`, `idUsuario`, `cuf` FROM `recibos` WHERE `fechaEmision` >= '' AND `fechaEmision` <= '' AND `idUsuario` = '' AND `tipoRecibo` = '' */
                        $sql = "SELECT
                                    r.idRecibo,
                                    r.numeroRecibo,
                                    r.fechaEmision,
                                    r.horaEmision,
                                    r.nombreRazonSocial,
                                    r.numeroDocumento,
                                    r.fechaRegistro,
                                    r.tipoRecibo,
                                    r.idUsuario,
                                    r.cuf,
                                    COALESCE(d.totalRecibo, 0) AS totalRecibo
                                    FROM recibos AS r
                                    LEFT JOIN (
                                    SELECT idRecibo, SUM(subTotal) AS totalRecibo
                                    FROM recibos_det
                                    GROUP BY idRecibo
                                    ) AS d
                                    ON d.idRecibo = r.idRecibo
                                    WHERE r.fechaEmision >= '$fechaInicio'
                                    AND r.fechaEmision <= '$fechaFinal'
                                    AND r.idUsuario = '$usuario'
                                    AND r.tipoRecibo = '$tipoVenta';";

                        //echo $sql;

                        $conRecibos = mysqli_query($link, $sql)or die(mysqli_error($link));
                        if(mysqli_num_rows($conRecibos) > 0){
                            while($rowRecibo = mysqli_fetch_array($conRecibos)){
                                echo "<tr>";
                                    echo "<td>".$rowRecibo['numeroRecibo']."</td>";
                                    echo "<td>".$rowRecibo['fechaEmision']."</td>";
                                    echo "<td>".$rowRecibo['horaEmision']."</td>";
                                    echo "<td>".$rowRecibo['nombreRazonSocial']."</td>";
                                    echo "<td>".$rowRecibo['numeroDocumento']."</td>";
                                    echo "<td>".$rowRecibo['tipoRecibo']."</td>";
                                    echo "<td>".$rowRecibo['idUsuario']."</td>";
                                    echo "<td style='text-align: right;'>".number_format($rowRecibo['totalRecibo'], 2, '.', ',')."</td>";
                                    //echo "<td>".$rowRecibo['cuf']."</td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody>";
                echo "</table>";
            echo "</div>";
        break;

        case "DETALLADO_INGRESOS":
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover'>";
                    echo "<thead>";
                        echo "<tr>";

                            echo "<th>Nro. Recibo</th>";
                            echo "<th>Fecha Emisión</th>";
                            echo "<th>Hora Emisión</th>";
                            echo "<th>Nombre/Razón Social</th>";
                            echo "<th>Nro. Documento</th>";
                            echo "<th>Tipo Recibo</th>";
                            echo "<th>Usuario</th>";
                            echo "<th>Cantidad</th>";
                            echo "<th>Precio Unitario</th>";
                            echo "<th>Monto Descuento</th>";
                            echo "<th>Sub Total</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                        $sql = "SELECT
                                    d.idReciboDet,
                                    d.idRecibo,
                                    d.codigoProducto,
                                    d.descripcion,
                                    d.cantidad,
                                    d.precioUnitario,
                                    d.montoDescuento,
                                    d.subTotal,
                                    -- campos de la cabecera (útiles para contexto)
                                    r.numeroRecibo,
                                    r.fechaEmision,
                                    r.horaEmision,
                                    r.nombreRazonSocial,
                                    r.numeroDocumento,
                                    r.tipoRecibo,
                                    r.idUsuario,
                                    r.cuf
                                    FROM recibos_det AS d
                                    INNER JOIN recibos AS r
                                    ON r.idRecibo = d.idRecibo
                                    WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal'   -- 'YYYY-MM-DD' a 'YYYY-MM-DD'
                                    AND r.idUsuario = '$usuario'                   -- ej. 5
                                    AND r.tipoRecibo = '$tipoVenta'                 -- ej. 'TICKET'
                                    ORDER BY r.fechaEmision, r.numeroRecibo, d.idReciboDet;";

                        //echo $sql;
                        $conRecibosDet = mysqli_query($link, $sql)or die(mysqli_error($link));
                        if(mysqli_num_rows($conRecibosDet) > 0){
                            while($rowReciboDet = mysqli_fetch_array($conRecibosDet)){
                                echo "<tr>";
                                    echo "<td>".$rowReciboDet['numeroRecibo']."</td>";
                                    echo "<td>".$rowReciboDet['fechaEmision']."</td>";
                                    echo "<td>".$rowReciboDet['horaEmision']."</td>";
                                    echo "<td>".$rowReciboDet['nombreRazonSocial']."</td>";
                                    echo "<td>".$rowReciboDet['numeroDocumento']."</td>";
                                    echo "<td>".$rowReciboDet['tipoRecibo']."</td>";
                                    echo "<td>".$rowReciboDet['idUsuario']."</td>";
                                    echo "<td>".$rowReciboDet['cantidad']."</td>";
                                    echo "<td>".$rowReciboDet['precioUnitario']."</td>";
                                    echo "<td>".$rowReciboDet['montoDescuento']."</td>";
                                    echo "<td>".$rowReciboDet['subTotal']."</td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody>";
                echo "</table>";
            echo "</div>";
        break;
        case "RESUMEN_PRODUCTOS":
            echo "<div class='col-md-12 table-responsive'>";
                echo "<table class='table table-bordered table-striped table-sm table-hover'>";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th>Codigo Producto</th>";
                            echo "<th>Descripcion</th>";
                            echo "<th>Total Cantidad</th>";
                            echo "<th>Precio Promedio</th>";
                            echo "<th>Total Vendido</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                        $sql = "SELECT
                                    d.codigoProducto,
                                    d.descripcion,
                                    SUM(d.cantidad)       AS totalCantidad,
                                    AVG(d.precioUnitario) AS precioPromedio,
                                    SUM(d.subTotal)       AS totalVendido
                                    FROM recibos_det AS d
                                    INNER JOIN recibos AS r
                                    ON r.idRecibo = d.idRecibo
                                    WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal'   -- ej. '2025-09-01' AND '2025-09-19'
                                    AND r.idUsuario = '$usuario'                   -- ej. 5
                                    AND r.tipoRecibo = '$tipoVenta'                 -- ej. 'TICKET'
                                    GROUP BY d.codigoProducto, d.descripcion
                                    ORDER BY totalCantidad DESC;
                                    ";
                        $conResumenProductos = mysqli_query($link, $sql)or die(mysqli_error($link));
                        if(mysqli_num_rows($conResumenProductos) > 0){
                            while($rowResumenProducto = mysqli_fetch_array($conResumenProductos)){
                                echo "<tr>";
                                    echo "<td>".$rowResumenProducto['codigoProducto']."</td>";
                                    echo "<td>".$rowResumenProducto['descripcion']."</td>";
                                    echo "<td>".$rowResumenProducto['totalCantidad']."</td>";
                                    echo "<td>".$rowResumenProducto['precioPromedio']."</td>";
                                    echo "<td style='text-align: right;'>".number_format($rowResumenProducto['totalVendido'], 2, '.', ',')."</td>";
                                echo "</tr>";
                            }
                        }
                    echo "</tbody>";
                echo "</table>";
            echo "</div>";
        break;
    }
}

function generarReportePDF($fechaInicio, $fechaFinal, $usuario, $tipoVenta, $tipoReporte){
    global $link;
    
    require_once '../../vendor/autoload.php';
    
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'L',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'margin_header' => 5,
        'margin_footer' => 5,
    ]);
    
    $sqlUsuario = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario = '$usuario'";
    $resultUsuario = mysqli_query($link, $sqlUsuario);
    $nombreUsuario = mysqli_fetch_array($resultUsuario)['nombreCompleto'];
    
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte de Ingresos</title>
    <style>body{font-family:Arial,sans-serif;font-size:10px}.header{text-align:center;margin-bottom:20px}
    .header h1{margin:0;font-size:16px}.header p{margin:5px 0;font-size:10px}table{width:100%;border-collapse:collapse;margin-bottom:20px}
    th,td{border:1px solid #000;padding:4px;text-align:left}th{background-color:#f0f0f0;font-weight:bold}
    .text-right{text-align:right}.summary{margin-top:20px;padding:10px;background-color:#f9f9f9}</style></head><body>
    <div class="header"><h1>REPORTE DE INGRESOS</h1><p><strong>Tipo:</strong> '.$tipoReporte.'</p>
    <p><strong>Fecha:</strong> '.$fechaInicio.' - '.$fechaFinal.'</p><p><strong>Usuario:</strong> '.$nombreUsuario.'</p>
    <p><strong>Tipo Venta:</strong> '.$tipoVenta.'</p><p><strong>Generado:</strong> '.date('d/m/Y H:i:s').'</p></div>';
    
    switch($tipoReporte){
        case "RESUMEN_INGRESOS":
            $html .= '<table><thead><tr><th>Nro. Recibo</th><th>Fecha</th><th>Hora</th><th>Cliente</th><th>Doc</th><th>Tipo</th><th>Usuario</th><th>Total</th></tr></thead><tbody>';
            $sql = "SELECT r.numeroRecibo,r.fechaEmision,r.horaEmision,r.nombreRazonSocial,r.numeroDocumento,r.tipoRecibo,r.idUsuario,COALESCE(d.totalRecibo,0) AS totalRecibo FROM recibos AS r LEFT JOIN (SELECT idRecibo,SUM(subTotal) AS totalRecibo FROM recibos_det GROUP BY idRecibo) AS d ON d.idRecibo = r.idRecibo WHERE r.fechaEmision >= '$fechaInicio' AND r.fechaEmision <= '$fechaFinal' AND r.idUsuario = '$usuario' AND r.tipoRecibo = '$tipoVenta'";
            $conRecibos = mysqli_query($link, $sql) or die(mysqli_error($link));
            $totalGeneral = 0;
            if(mysqli_num_rows($conRecibos) > 0){
                while($rowRecibo = mysqli_fetch_array($conRecibos)){
                    $html .= '<tr><td>'.$rowRecibo['numeroRecibo'].'</td><td>'.$rowRecibo['fechaEmision'].'</td><td>'.$rowRecibo['horaEmision'].'</td><td>'.$rowRecibo['nombreRazonSocial'].'</td><td>'.$rowRecibo['numeroDocumento'].'</td><td>'.$rowRecibo['tipoRecibo'].'</td><td>'.$rowRecibo['idUsuario'].'</td><td class="text-right">'.number_format($rowRecibo['totalRecibo'],2,'.',',').'</td></tr>';
                    $totalGeneral += $rowRecibo['totalRecibo'];
                }
            }
            $html .= '</tbody></table><div class="summary"><strong>TOTAL: Bs. '.number_format($totalGeneral,2,'.',',').'</strong></div>';
            break;
        case "DETALLADO_INGRESOS":
            $html .= '<table><thead><tr><th>Recibo</th><th>Fecha</th><th>Cliente</th><th>Cantidad</th><th>Precio</th><th>Descuento</th><th>SubTotal</th></tr></thead><tbody>';
            $sql = "SELECT r.numeroRecibo,r.fechaEmision,r.nombreRazonSocial,d.cantidad,d.precioUnitario,d.montoDescuento,d.subTotal FROM recibos_det AS d INNER JOIN recibos AS r ON r.idRecibo = d.idRecibo WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal' AND r.idUsuario = '$usuario' AND r.tipoRecibo = '$tipoVenta' ORDER BY r.fechaEmision";
            $conRecibosDet = mysqli_query($link, $sql) or die(mysqli_error($link));
            $totalGeneral = 0;
            if(mysqli_num_rows($conRecibosDet) > 0){
                while($rowReciboDet = mysqli_fetch_array($conRecibosDet)){
                    $html .= '<tr><td>'.$rowReciboDet['numeroRecibo'].'</td><td>'.$rowReciboDet['fechaEmision'].'</td><td>'.$rowReciboDet['nombreRazonSocial'].'</td><td>'.$rowReciboDet['cantidad'].'</td><td>'.$rowReciboDet['precioUnitario'].'</td><td>'.$rowReciboDet['montoDescuento'].'</td><td class="text-right">'.$rowReciboDet['subTotal'].'</td></tr>';
                    $totalGeneral += $rowReciboDet['subTotal'];
                }
            }
            $html .= '</tbody></table><div class="summary"><strong>TOTAL: Bs. '.number_format($totalGeneral,2,'.',',').'</strong></div>';
            break;
        case "RESUMEN_PRODUCTOS":
            $html .= '<table><thead><tr><th>Código</th><th>Descripción</th><th>Cantidad</th><th>Precio Promedio</th><th>Total</th></tr></thead><tbody>';
            $sql = "SELECT d.codigoProducto,d.descripcion,SUM(d.cantidad) AS totalCantidad,AVG(d.precioUnitario) AS precioPromedio,SUM(d.subTotal) AS totalVendido FROM recibos_det AS d INNER JOIN recibos AS r ON r.idRecibo = d.idRecibo WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal' AND r.idUsuario = '$usuario' AND r.tipoRecibo = '$tipoVenta' GROUP BY d.codigoProducto,d.descripcion ORDER BY totalCantidad DESC";
            $conResumenProductos = mysqli_query($link, $sql) or die(mysqli_error($link));
            $totalGeneral = 0;
            if(mysqli_num_rows($conResumenProductos) > 0){
                while($rowResumenProducto = mysqli_fetch_array($conResumenProductos)){
                    $html .= '<tr><td>'.$rowResumenProducto['codigoProducto'].'</td><td>'.$rowResumenProducto['descripcion'].'</td><td>'.$rowResumenProducto['totalCantidad'].'</td><td>'.number_format($rowResumenProducto['precioPromedio'],2,'.',',').'</td><td class="text-right">'.number_format($rowResumenProducto['totalVendido'],2,'.',',').'</td></tr>';
                    $totalGeneral += $rowResumenProducto['totalVendido'];
                }
            }
            $html .= '</tbody></table><div class="summary"><strong>TOTAL: Bs. '.number_format($totalGeneral,2,'.',',').'</strong></div>';
            break;
    }
    
    $html .= '</body></html>';
    $mpdf->WriteHTML($html);
    
    $nombreArchivo = 'Reporte_'.$tipoReporte.'_'.date('Ymd_His').'.pdf';
    $rutaArchivo = '../../storage/temp/'.$nombreArchivo;
    $mpdf->Output($rutaArchivo, 'F');
    
    echo json_encode(['estado' => 'OK', 'mensaje' => 'Reporte PDF generado exitosamente', 'archivo' => $nombreArchivo, 'ruta' => 'storage/temp/'.$nombreArchivo, 'tipo' => 'pdf']);
}

function generarReporteEXCEL($fechaInicio, $fechaFinal, $usuario, $tipoVenta, $tipoReporte){
    global $link;
    
    require_once '../../vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sqlUsuario = "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) as nombreCompleto FROM usuarios WHERE idUsuario = '$usuario'";
    $resultUsuario = mysqli_query($link, $sqlUsuario);
    $nombreUsuario = mysqli_fetch_array($resultUsuario)['nombreCompleto'];
    
    $sheet->setTitle('Reporte de Ingresos');
    $sheet->setCellValue('A1', 'REPORTE DE INGRESOS');
    $sheet->setCellValue('A2', 'Tipo de Reporte: ' . $tipoReporte);
    $sheet->setCellValue('A3', 'Fecha: ' . $fechaInicio . ' - ' . $fechaFinal);
    $sheet->setCellValue('A4', 'Usuario: ' . $nombreUsuario);
    $sheet->setCellValue('A5', 'Tipo Venta: ' . $tipoVenta);
    $sheet->setCellValue('A6', 'Generado: ' . date('d/m/Y H:i:s'));
    
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2:A6')->getFont()->setBold(true);
    
    $filaInicio = 8;
    
    switch($tipoReporte){
        case "RESUMEN_INGRESOS":
            $sheet->setCellValue('A'.$filaInicio, 'Nro. Recibo');
            $sheet->setCellValue('B'.$filaInicio, 'Fecha');
            $sheet->setCellValue('C'.$filaInicio, 'Hora');
            $sheet->setCellValue('D'.$filaInicio, 'Cliente');
            $sheet->setCellValue('E'.$filaInicio, 'Documento');
            $sheet->setCellValue('F'.$filaInicio, 'Tipo');
            $sheet->setCellValue('G'.$filaInicio, 'Usuario');
            $sheet->setCellValue('H'.$filaInicio, 'Total');
            
            $sheet->getStyle('A'.$filaInicio.':H'.$filaInicio)->getFont()->setBold(true);
            $sheet->getStyle('A'.$filaInicio.':H'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            
            $fila = $filaInicio + 1;
            $totalGeneral = 0;
            
            $sql = "SELECT r.numeroRecibo,r.fechaEmision,r.horaEmision,r.nombreRazonSocial,r.numeroDocumento,r.tipoRecibo,r.idUsuario,COALESCE(d.totalRecibo,0) AS totalRecibo FROM recibos AS r LEFT JOIN (SELECT idRecibo,SUM(subTotal) AS totalRecibo FROM recibos_det GROUP BY idRecibo) AS d ON d.idRecibo = r.idRecibo WHERE r.fechaEmision >= '$fechaInicio' AND r.fechaEmision <= '$fechaFinal' AND r.idUsuario = '$usuario' AND r.tipoRecibo = '$tipoVenta'";
            $conRecibos = mysqli_query($link, $sql) or die(mysqli_error($link));
            
            if(mysqli_num_rows($conRecibos) > 0){
                while($rowRecibo = mysqli_fetch_array($conRecibos)){
                    $sheet->setCellValue('A'.$fila, $rowRecibo['numeroRecibo']);
                    $sheet->setCellValue('B'.$fila, $rowRecibo['fechaEmision']);
                    $sheet->setCellValue('C'.$fila, $rowRecibo['horaEmision']);
                    $sheet->setCellValue('D'.$fila, $rowRecibo['nombreRazonSocial']);
                    $sheet->setCellValue('E'.$fila, $rowRecibo['numeroDocumento']);
                    $sheet->setCellValue('F'.$fila, $rowRecibo['tipoRecibo']);
                    $sheet->setCellValue('G'.$fila, $rowRecibo['idUsuario']);
                    $sheet->setCellValue('H'.$fila, $rowRecibo['totalRecibo']);
                    $sheet->getStyle('H'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
                    $totalGeneral += $rowRecibo['totalRecibo'];
                    $fila++;
                }
            }
            
            $sheet->setCellValue('G'.$fila, 'TOTAL:');
            $sheet->setCellValue('H'.$fila, $totalGeneral);
            $sheet->getStyle('G'.$fila.':H'.$fila)->getFont()->setBold(true);
            $sheet->getStyle('H'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
            break;
            
        case "DETALLADO_INGRESOS":
            $sheet->setCellValue('A'.$filaInicio, 'Recibo');
            $sheet->setCellValue('B'.$filaInicio, 'Fecha');
            $sheet->setCellValue('C'.$filaInicio, 'Cliente');
            $sheet->setCellValue('D'.$filaInicio, 'Cantidad');
            $sheet->setCellValue('E'.$filaInicio, 'Precio');
            $sheet->setCellValue('F'.$filaInicio, 'Descuento');
            $sheet->setCellValue('G'.$filaInicio, 'SubTotal');
            
            $sheet->getStyle('A'.$filaInicio.':G'.$filaInicio)->getFont()->setBold(true);
            $sheet->getStyle('A'.$filaInicio.':G'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            
            $fila = $filaInicio + 1;
            $totalGeneral = 0;
            
            $sql = "SELECT r.numeroRecibo,r.fechaEmision,r.nombreRazonSocial,d.cantidad,d.precioUnitario,d.montoDescuento,d.subTotal FROM recibos_det AS d INNER JOIN recibos AS r ON r.idRecibo = d.idRecibo WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal' AND r.idUsuario = '$usuario' AND r.tipoRecibo = '$tipoVenta' ORDER BY r.fechaEmision";
            $conRecibosDet = mysqli_query($link, $sql) or die(mysqli_error($link));
            
            if(mysqli_num_rows($conRecibosDet) > 0){
                while($rowReciboDet = mysqli_fetch_array($conRecibosDet)){
                    $sheet->setCellValue('A'.$fila, $rowReciboDet['numeroRecibo']);
                    $sheet->setCellValue('B'.$fila, $rowReciboDet['fechaEmision']);
                    $sheet->setCellValue('C'.$fila, $rowReciboDet['nombreRazonSocial']);
                    $sheet->setCellValue('D'.$fila, $rowReciboDet['cantidad']);
                    $sheet->setCellValue('E'.$fila, $rowReciboDet['precioUnitario']);
                    $sheet->setCellValue('F'.$fila, $rowReciboDet['montoDescuento']);
                    $sheet->setCellValue('G'.$fila, $rowReciboDet['subTotal']);
                    $sheet->getStyle('E'.$fila.':G'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
                    $totalGeneral += $rowReciboDet['subTotal'];
                    $fila++;
                }
            }
            
            $sheet->setCellValue('F'.$fila, 'TOTAL:');
            $sheet->setCellValue('G'.$fila, $totalGeneral);
            $sheet->getStyle('F'.$fila.':G'.$fila)->getFont()->setBold(true);
            $sheet->getStyle('G'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
            break;
            
        case "RESUMEN_PRODUCTOS":
            $sheet->setCellValue('A'.$filaInicio, 'Código');
            $sheet->setCellValue('B'.$filaInicio, 'Descripción');
            $sheet->setCellValue('C'.$filaInicio, 'Cantidad');
            $sheet->setCellValue('D'.$filaInicio, 'Precio Promedio');
            $sheet->setCellValue('E'.$filaInicio, 'Total');
            
            $sheet->getStyle('A'.$filaInicio.':E'.$filaInicio)->getFont()->setBold(true);
            $sheet->getStyle('A'.$filaInicio.':E'.$filaInicio)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            
            $fila = $filaInicio + 1;
            $totalGeneral = 0;
            
            $sql = "SELECT d.codigoProducto,d.descripcion,SUM(d.cantidad) AS totalCantidad,AVG(d.precioUnitario) AS precioPromedio,SUM(d.subTotal) AS totalVendido FROM recibos_det AS d INNER JOIN recibos AS r ON r.idRecibo = d.idRecibo WHERE r.fechaEmision BETWEEN '$fechaInicio' AND '$fechaFinal' AND r.idUsuario = '$usuario' AND r.tipoRecibo = '$tipoVenta' GROUP BY d.codigoProducto,d.descripcion ORDER BY totalCantidad DESC";
            $conResumenProductos = mysqli_query($link, $sql) or die(mysqli_error($link));
            
            if(mysqli_num_rows($conResumenProductos) > 0){
                while($rowResumenProducto = mysqli_fetch_array($conResumenProductos)){
                    $sheet->setCellValue('A'.$fila, $rowResumenProducto['codigoProducto']);
                    $sheet->setCellValue('B'.$fila, $rowResumenProducto['descripcion']);
                    $sheet->setCellValue('C'.$fila, $rowResumenProducto['totalCantidad']);
                    $sheet->setCellValue('D'.$fila, $rowResumenProducto['precioPromedio']);
                    $sheet->setCellValue('E'.$fila, $rowResumenProducto['totalVendido']);
                    $sheet->getStyle('D'.$fila.':E'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
                    $totalGeneral += $rowResumenProducto['totalVendido'];
                    $fila++;
                }
            }
            
            $sheet->setCellValue('D'.$fila, 'TOTAL:');
            $sheet->setCellValue('E'.$fila, $totalGeneral);
            $sheet->getStyle('D'.$fila.':E'.$fila)->getFont()->setBold(true);
            $sheet->getStyle('E'.$fila)->getNumberFormat()->setFormatCode('#,##0.00');
            break;
    }
    
    foreach(range('A','H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $ultimaFila = $fila;
    $ultimaColumna = $tipoReporte == 'RESUMEN_INGRESOS' ? 'H' : ($tipoReporte == 'DETALLADO_INGRESOS' ? 'G' : 'E');
    $sheet->getStyle('A'.$filaInicio.':'.$ultimaColumna.$ultimaFila)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $nombreArchivo = 'Reporte_'.$tipoReporte.'_'.date('Ymd_His').'.xlsx';
    $rutaArchivo = '../../storage/temp/'.$nombreArchivo;
    $writer->save($rutaArchivo);
    
    echo json_encode(['estado' => 'OK', 'mensaje' => 'Reporte Excel generado exitosamente', 'archivo' => $nombreArchivo, 'ruta' => 'storage/temp/'.$nombreArchivo, 'tipo' => 'excel']);
}

?>
