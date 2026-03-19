<?php
session_start();

if (!isset($_SESSION['idUsuario_clinicloud'])) {
    header('Content-Type: application/json');
    echo json_encode(['sesion' => 'cerrada']);
    exit;
}

require_once "../../../config_db_mysql.php";
require_once __DIR__ . "/../../../vendor/autoload.php";

use Mpdf\Mpdf;

// Leer datos enviados desde fetch
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

if (!isset($input['funcion'])) {
    header("Content-Type: application/json");
    echo json_encode(["estado" => "ERROR", "mensaje" => "No se especificó la función a ejecutar."]);
    exit;
}

switch ($input['funcion']) {
    case "listaOrdenesAtencion":
        listaOrdenesAtencion();
        break;
    case "formularioRegistrarPago":
        formularioRegistrarPago();
        break;
    case "registrarPago":
        registrarPago();
        break;
    case "verReciboPago":
        verReciboPago();
        break;
    case "procesarCotizacion":
        procesarCotizacion();
        break;
}


function listaOrdenesAtencion(){
    global $input;
    global $link;

    $idAtencion = isset($input['idAtencion']) ? (int)$input['idAtencion'] : 0;

    $conPaciente = mysqli_query($link, "SELECT `idAtencion`, `idPaciente`, `idConsultorio`, `fechaAtencion`, `idUsuario`, `fechaRegistro`, `estadoAtencion`, `especialidad` FROM `atencion_clinica` 
    WHERE `idAtencion` = '{$idAtencion}'")or die(mysqli_error($link));
    if(mysqli_num_rows($conPaciente) > 0){
        $rowPaciente = mysqli_fetch_array($conPaciente);
        $idPaciente = $rowPaciente['idPaciente'];
    }

    

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Lista de Ordenes de Atencion</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";

        $conOrdenesAtencion = mysqli_query($link, "SELECT `idOrdenAtencion`, `idPaciente`, `jsonDetallePrestaciones`, `estado`, `montoTotal`, `saldoPendiente`, `fechaHoraRegistro`, `idUsuarioRegistro` FROM `orden_atencion` 
        WHERE `idPaciente` = '{$idPaciente}'")or die(mysqli_error($link));
        if(mysqli_num_rows($conOrdenesAtencion) > 0){
            echo "<div class='table-responsive'>";
            echo "<table class='table table-sm table-striped table-bordered align-middle mb-0'>";
                echo "<thead>";
                    echo "<tr>";
                        echo "<th style='width: 90px;'>#</th>";
                        echo "<th>Estado</th>";
                        echo "<th style='width: 140px;' class='text-end'>Monto Total</th>";
                        echo "<th style='width: 160px;' class='text-end'>Saldo Pendiente</th>";
                        echo "<th style='width: 190px;'>Fecha/Hora</th>";
                        echo "<th>Detalle</th>";
                        echo "<th>Acciones</th>";
                    echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
            while($rowOrdenAtencion = mysqli_fetch_array($conOrdenesAtencion)){
                $idOrdenAtencion = (int)$rowOrdenAtencion['idOrdenAtencion'];
                $estado = htmlspecialchars((string)$rowOrdenAtencion['estado'], ENT_QUOTES, 'UTF-8');
                $montoTotal = is_numeric($rowOrdenAtencion['montoTotal']) ? (float)$rowOrdenAtencion['montoTotal'] : 0.0;
                $saldoPendiente = is_numeric($rowOrdenAtencion['saldoPendiente']) ? (float)$rowOrdenAtencion['saldoPendiente'] : 0.0;
                $fechaHoraRegistro = htmlspecialchars((string)$rowOrdenAtencion['fechaHoraRegistro'], ENT_QUOTES, 'UTF-8');

                $detalleStr = (string)($rowOrdenAtencion['jsonDetallePrestaciones'] ?? '[]');
                $detalle = json_decode($detalleStr, true);
                $detalleHtml = "";
                if (is_array($detalle) && count($detalle) > 0) {
                    $detalleHtml .= "<ul class='mb-0 ps-3'>";
                    foreach ($detalle as $item) {
                        $prestacion = "";
                        $monto = "";
                        if (is_array($item)) {
                            $prestacion = $item['prestacion'] ?? ($item[0] ?? "");
                            $monto = $item['monto'] ?? ($item[1] ?? "");
                        }
                        $prestacion = htmlspecialchars(trim((string)$prestacion), ENT_QUOTES, 'UTF-8');
                        $montoNum = is_numeric($monto) ? (float)$monto : null;
                        $montoTxt = $montoNum === null ? htmlspecialchars((string)$monto, ENT_QUOTES, 'UTF-8') : number_format($montoNum, 2);
                        if ($prestacion === "" && $montoTxt === "") continue;
                        $detalleHtml .= "<li>{$prestacion}" . ($montoTxt !== "" ? " - {$montoTxt}" : "") . "</li>";
                    }
                    $detalleHtml .= "</ul>";
                } else {
                    $detalleHtml = "<span class='text-muted'>Sin detalle</span>";
                }

                echo "<tr>";
                    echo "<td class='text-nowrap'>{$idOrdenAtencion}</td>";
                    echo "<td>{$estado}</td>";
                    echo "<td class='text-end'>" . number_format($montoTotal, 2) . "</td>";
                    echo "<td class='text-end'>" . number_format($saldoPendiente, 2) . "</td>";
                    echo "<td class='text-nowrap'>{$fechaHoraRegistro}</td>";
                    echo "<td>{$detalleHtml}</td>";
                    echo "<td>";
                        if($estado == 'ORDEN ATENCION'){
                            echo "<button type='button' class='btn btn-sm btn-danger waves-effect waves-light btnFormularioRegistrarPago' id='{$idOrdenAtencion}'><i class='fas fa-money-bill'></i> Registrar Pago</button>";
                        }
                        if($estado == 'COTIZACION'){
                            echo "<button type='button' class='btn btn-sm btn-success waves-effect waves-light btnProcesarCotizacion' id='{$idOrdenAtencion}'><i class='fas fa-money-bill'></i> Procesar Cotizacion</button>";
                        }
                    echo "</td>";
                echo "</tr>";
            }
                echo "</tbody>";
            echo "</table>";
            echo "</div>";
        }
        else{
            echo "No hay datos";
        }

    echo "</div>";
    echo "<div class='modal-footer'>";
        //echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnRegistrarPrestaciones'><i class='fas fa-save'></i> Registrar Prestaciones</button>";
        //echo "<button type='button' class='btn btn-warning waves-effect waves-light' id='btnRegistrarCotizacion'><i class='fas fa-calculator'></i> Registrar Cotizacion</button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'><i class='fas fa-times'></i> Cerrar</button>";
    echo "</div>";
}

function formularioRegistrarPago(){
    global $input;
    global $link;

    $idOrdenAtencion = isset($input['idOrdenAtencion']) ? (int)$input['idOrdenAtencion'] : 0;

    $conOrdenAtencion = mysqli_query(
        $link,
        "SELECT `idOrdenAtencion`, `idPaciente`, `jsonDetallePrestaciones`, `estado`, `montoTotal`, `saldoPendiente`, `fechaHoraRegistro`, `idUsuarioRegistro`
        FROM `orden_atencion`
        WHERE `idOrdenAtencion` = '{$idOrdenAtencion}'"
    ) or die(mysqli_error($link));

    if (!$conOrdenAtencion || mysqli_num_rows($conOrdenAtencion) === 0) {
        echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Registro de Pago</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
        echo "<i class='fas fa-times'></i>";
        echo "</button>";
        echo "</div>";
        echo "<div class='modal-body'>";
        echo "<div class='alert alert-warning mb-0'>No se encontró la orden de atención.</div>";
        echo "</div>";
        echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
        echo "</div>";
        return;
    }

    $rowOrdenAtencion = mysqli_fetch_array($conOrdenAtencion);
    $idPaciente = isset($rowOrdenAtencion['idPaciente']) ? (int)$rowOrdenAtencion['idPaciente'] : 0;
    $jsonDetallePrestaciones = (string)($rowOrdenAtencion['jsonDetallePrestaciones'] ?? '[]');
    $estado = (string)($rowOrdenAtencion['estado'] ?? '');
    $montoTotal = is_numeric($rowOrdenAtencion['montoTotal'] ?? null) ? (float)$rowOrdenAtencion['montoTotal'] : 0.0;
    $saldoPendiente = is_numeric($rowOrdenAtencion['saldoPendiente'] ?? null) ? (float)$rowOrdenAtencion['saldoPendiente'] : 0.0;
    $fechaHoraRegistro = (string)($rowOrdenAtencion['fechaHoraRegistro'] ?? '');
    $idUsuarioRegistro = isset($rowOrdenAtencion['idUsuarioRegistro']) ? (int)$rowOrdenAtencion['idUsuarioRegistro'] : 0;

    $estadoBadgeClass = ($estado === 'PAGADO') ? 'bg-success' : (($estado === 'ORDEN ATENCION') ? 'bg-danger' : 'bg-warning text-dark');
    $estadoBadgeText = htmlspecialchars(trim($estado), ENT_QUOTES, 'UTF-8');

    $fechaHoraRegistroFmt = '-';
    if (!empty($fechaHoraRegistro) && $fechaHoraRegistro !== '0000-00-00 00:00:00') {
        $ts = strtotime($fechaHoraRegistro);
        if ($ts !== false) {
            $fechaHoraRegistroFmt = date('d/m/Y H:i', $ts);
        }
    }

    // Datos del paciente (para mostrar info completa)
    $nombrePaciente = '-';
    $ciPaciente = '-';
    $celularPaciente = '-';
    $emailPaciente = '-';

    $conPaciente = mysqli_query(
        $link,
        "SELECT `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `celular`, `email`
        FROM `pacientes`
        WHERE `idPaciente` = '{$idPaciente}'"
    ) or die(mysqli_error($link));

    if ($conPaciente && mysqli_num_rows($conPaciente) > 0) {
        $rowPaciente = mysqli_fetch_array($conPaciente);
        $ap = trim((string)($rowPaciente['apellidoPat'] ?? ''));
        $am = trim((string)($rowPaciente['apellidoMat'] ?? ''));
        $nom = trim((string)($rowPaciente['nombres'] ?? ''));
        $nombrePaciente = trim($ap . ' ' . $am . ' ' . $nom) ?: '-';
        $ciPaciente = (string)($rowPaciente['ci'] ?? '-');
        $celularPaciente = (string)($rowPaciente['celular'] ?? '-');
        $emailPaciente = (string)($rowPaciente['email'] ?? '-');
    }

    $nombreUsuarioRegistro = '-';
    $conUsuarioRegistro = mysqli_query(
        $link,
        "SELECT `nombreUs`, `primerApUs`, `segundoApUs`
        FROM `usuarios`
        WHERE `idUsuario` = '{$idUsuarioRegistro}'"
    ) or die(mysqli_error($link));

    if ($conUsuarioRegistro && mysqli_num_rows($conUsuarioRegistro) > 0) {
        $rowUsuario = mysqli_fetch_array($conUsuarioRegistro);
        $n1 = trim((string)($rowUsuario['nombreUs'] ?? ''));
        $n2 = trim((string)($rowUsuario['primerApUs'] ?? ''));
        $n3 = trim((string)($rowUsuario['segundoApUs'] ?? ''));
        $nombreUsuarioRegistro = trim($n1 . ' ' . $n2 . ' ' . $n3) ?: '-';
    }

    // Detalle de prestaciones
    $detalle = json_decode($jsonDetallePrestaciones, true);
    $detalleItemsHtml = '';
    if (is_array($detalle) && count($detalle) > 0) {
        foreach ($detalle as $item) {
            $prestacion = '';
            $monto = '';
            if (is_array($item)) {
                $prestacion = $item['prestacion'] ?? ($item[0] ?? '');
                $monto = $item['monto'] ?? ($item[1] ?? '');
            }

            $prestacion = trim((string)$prestacion);
            if ($prestacion === '') continue;

            $montoNum = is_numeric($monto) ? (float)$monto : null;
            $montoTxt = $montoNum === null
                ? (string)$monto
                : number_format($montoNum, 2);

            $detalleItemsHtml .=
                "<div class='list-group-item d-flex justify-content-between align-items-center py-2'>" .
                    "<span>" . htmlspecialchars($prestacion, ENT_QUOTES, 'UTF-8') . "</span>" .
                    "<strong class='text-end'>Bs. " . htmlspecialchars($montoTxt, ENT_QUOTES, 'UTF-8') . "</strong>" .
                "</div>";
        }
    }

    if ($detalleItemsHtml === '') {
        $detalleItemsHtml = "<div class='list-group-item text-muted'>Sin detalle</div>";
    }

    $saldoPendienteTxt = number_format($saldoPendiente, 2, '.', '');
    $montoTotalTxt = number_format($montoTotal, 2, '.', '');
    $saldoPendienteFmt = number_format($saldoPendiente, 2);

    $saldoRestanteInicial = $saldoPendiente;
    $badgeSaldoInicial = ($saldoRestanteInicial <= 0.000001)
        ? "<span id='badgeSaldoPendiente' class='badge bg-success'>Pago completo (sin saldo pendiente)</span>"
        : "<span id='badgeSaldoPendiente' class='badge bg-warning text-dark'>Aún hay saldo pendiente</span>";

    echo "<div class='modal-header'>";
    echo "<h4 class='modal-title mt-0'>Registro de Pago</h4>";
    echo "<div class='ms-3'>";
    echo "<span class='badge {$estadoBadgeClass}'>{$estadoBadgeText}</span>";
    echo "</div>";
    echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
    echo "<i class='fas fa-times'></i>";
    echo "</button>";
    echo "</div>";

    echo "<div class='modal-body'>";
    echo "<input type='hidden' id='idOrdenAtencionRegistroPago' value='" . (int)$idOrdenAtencion . "'>";
    echo "<input type='hidden' id='hdnMontoTotal' value='{$montoTotalTxt}'>";
    echo "<input type='hidden' id='hdnSaldoPendiente' value='{$saldoPendienteTxt}'>";
    echo "<input type='hidden' id='hdnTieneSaldoPendiente' value='" . ($saldoPendiente > 0 ? '1' : '0') . "'>";

    echo "<div class='row g-3'>";

    echo "<div class='col-lg-7'>";
    echo "<div class='mb-3'>";
    echo "<div class='d-flex justify-content-between align-items-center'>";
    echo "<h5 class='mb-0'>Información de la Orden</h5>";
    echo "</div>";
    echo "<div class='table-responsive mt-2'>";
    echo "<table class='table table-sm mb-0'>";
    echo "<tbody>";
    echo "<tr><th style='width:160px;'>Orden</th><td class='fw-semibold'>#" . (int)$idOrdenAtencion . "</td></tr>";
    echo "<tr><th>Paciente</th><td>";
    echo "<div class='fw-semibold'>" . htmlspecialchars($nombrePaciente, ENT_QUOTES, 'UTF-8') . "</div>";
    echo "<div class='text-muted'>CI: " . htmlspecialchars($ciPaciente, ENT_QUOTES, 'UTF-8') . "</div>";
    echo "</td></tr>";
    echo "<tr><th>Fecha/Hora</th><td>" . htmlspecialchars($fechaHoraRegistroFmt, ENT_QUOTES, 'UTF-8') . "</td></tr>";
    echo "<tr><th>Registrado por</th><td>" . htmlspecialchars($nombreUsuarioRegistro, ENT_QUOTES, 'UTF-8') . "</td></tr>";
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    echo "</div>";

    echo "<div class='mb-3'>";
    echo "<h5 class='mb-2'>Detalle de Prestaciones</h5>";
    echo "<div class='list-group list-group-flush'>";
    echo $detalleItemsHtml;
    echo "</div>";
    echo "</div>";

    echo "</div>";

    echo "<div class='col-lg-5'>";
    echo "<div class='mb-3'>";
    echo "<h5 class='mb-2'>Resumen Económico</h5>";
    echo "<div class='border rounded p-3 bg-light'>";
    echo "<div class='d-flex justify-content-between align-items-center'>";
    echo "<span class='text-muted'>Monto total</span>";
    echo "<strong class='text-end'>Bs. " . number_format($montoTotal, 2) . "</strong>";
    echo "</div>";
    echo "<div class='d-flex justify-content-between align-items-center mt-2'>";
    echo "<span class='text-muted'>Saldo pendiente</span>";
    echo "<strong class='text-end text-danger' id='txtSaldoPendiente'>" . $saldoPendienteFmt . "</strong>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    echo "<div class='mb-2'>";
    echo "<label for='txtMontoPago'>Monto del Pago (anticipo o pago total)</label>";
    echo "<div class='input-group'>";
    echo "<span class='input-group-text'>Bs.</span>";
    echo "<input type='number' class='form-control' id='txtMontoPago' name='txtMontoPago' value='0.00' min='0' step='0.01' inputmode='decimal' autocomplete='off'>";
    echo "</div>";
    echo "<small class='text-muted'>Ingrese cuánto abona el cliente ahora. El saldo restante se calcula automáticamente.</small>";
    echo "</div>";

    echo "<div class='mt-3'>";
    echo "<label for='txtSaldoRestante'>Saldo restante después del pago</label>";
    echo "<div class='d-flex align-items-center gap-2'>";
    echo "<input type='text' class='form-control' id='txtSaldoRestante' readonly value='" . number_format($saldoRestanteInicial, 2, '.', '') . "'>";
    echo $badgeSaldoInicial;
    echo "</div>";
    echo "</div>";

    echo "</div>";

    echo "</div>"; // row
    echo "</div>"; // modal-body

    echo "<div class='modal-footer'>";
    echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnRegistrarPago'><i class='fas fa-save'></i> Registrar pago</button>";
    echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";
}

function registrarPago(){
    global $input;
    global $link;

    $idOrdenAtencion = isset($input['idOrdenAtencion']) ? (int)$input['idOrdenAtencion'] : 0;
    $montoPago = isset($input['montoPago']) ? (float)$input['montoPago'] : 0.0;

    $conOrdenAtencion = mysqli_query($link, "SELECT `idOrdenAtencion`, `idPaciente`, `jsonDetallePrestaciones`, `estado`, `montoTotal`, `saldoPendiente`, `fechaHoraRegistro`, `idUsuarioRegistro` FROM `orden_atencion` 
    WHERE `idOrdenAtencion` = '{$idOrdenAtencion}'")or die(mysqli_error($link));
    if(mysqli_num_rows($conOrdenAtencion) > 0){
        $rowOrdenAtencion = mysqli_fetch_array($conOrdenAtencion);
        $idPaciente = $rowOrdenAtencion['idPaciente'];
        $jsonDetallePrestaciones = $rowOrdenAtencion['jsonDetallePrestaciones'];
        $estado = $rowOrdenAtencion['estado'];
        $montoTotal = $rowOrdenAtencion['montoTotal'];
        $saldoPendiente = $rowOrdenAtencion['saldoPendiente'];
        $fechaHoraRegistro = $rowOrdenAtencion['fechaHoraRegistro'];
        $idUsuarioRegistro = $rowOrdenAtencion['idUsuarioRegistro'];
    }

    $idUsuario = $_SESSION['idUsuario_clinicloud'];
    $fechaRegistro = date('Y-m-d H:i:s');
    $estadoRecibo = 'PAGADO';

    $nuevoSaldoPendiente = $saldoPendiente - $montoPago;

    $insertarRecibo = mysqli_query($link, "INSERT INTO `recibos`(`idPaciente`, `idUsuario`, `idOrdenAtencion`, `montoPagado`, `saldoPendiente`, `fechaRegistro`, `estadoRecibo`) 
    VALUES ('{$idPaciente}','{$idUsuario}','{$idOrdenAtencion}','{$montoPago}','{$nuevoSaldoPendiente}','{$fechaRegistro}','{$estadoRecibo}')")or die(mysqli_error($link));
    if($insertarRecibo){
        $idRecibo = mysqli_insert_id($link);
        echo json_encode(['estado' => 'OK', 'idRecibo' => $idRecibo]);        

        $sql = "UPDATE `orden_atencion` SET `saldoPendiente`='{$nuevoSaldoPendiente}' WHERE `idOrdenAtencion` = '{$idOrdenAtencion}'";
        $actualizarOrdenAtencion = mysqli_query($link, $sql)or die(mysqli_error($link));
        
    }
    else{
        echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Error al registrar el recibo de pago']);
    }
}

function verReciboPago(){
    global $input;
    global $link;

    $idRecibo = isset($input['idRecibo']) ? (int)$input['idRecibo'] : 0;

    $idPaciente = 0;
    $idOrdenAtencion = 0;
    $montoPagado = 0.0;
    $saldoPendiente = 0.0;
    $fechaRegistro = "";
    $estadoRecibo = "";
    $detalleItems = [];
    $jsonDetallePrestaciones = "[]";
    $estadoOrdenAtencion = "";
    $fechaHoraRegistroOrden = "";
    $pacienteNombre = "";
    $ciTxt = "";
    $celTxt = "";

    $conRecibo = mysqli_query($link, "SELECT `idRecibo`, `idPaciente`, `idUsuario`, `idOrdenAtencion`, `montoPagado`, `saldoPendiente`, `fechaRegistro`, `estadoRecibo` FROM `recibos` 
    WHERE `idRecibo` = '{$idRecibo}'")or die(mysqli_error($link));

    if(mysqli_num_rows($conRecibo) > 0){
        $rowRecibo = mysqli_fetch_array($conRecibo);
        $idRecibo = (int)$rowRecibo['idRecibo'];
        $idPaciente = (int)$rowRecibo['idPaciente'];
        $idOrdenAtencion = (int)$rowRecibo['idOrdenAtencion'];
        $montoPagado = is_numeric($rowRecibo['montoPagado']) ? (float)$rowRecibo['montoPagado'] : 0.0;
        $saldoPendiente = is_numeric($rowRecibo['saldoPendiente']) ? (float)$rowRecibo['saldoPendiente'] : 0.0;
        $fechaRegistro = (string)($rowRecibo['fechaRegistro'] ?? "");
        $estadoRecibo = (string)($rowRecibo['estadoRecibo'] ?? "");
    }

    // Siempre armamos el modal (si no existe el recibo, mostramos un mensaje).
    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0' id=''>Recibo de Pago</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";

    if($idRecibo <= 0){
        echo "<div class='alert alert-danger mb-0'>No se encontró el recibo de pago.</div>";
        echo "</div>";
        echo "<div class='modal-footer'>";
            echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
        echo "</div>";
        return;
    }

    // Datos del paciente
    $conPaciente = mysqli_query($link, "SELECT `idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `celular` FROM `pacientes` 
    WHERE `idPaciente` = '{$idPaciente}'")or die(mysqli_error($link));
    if(mysqli_num_rows($conPaciente) > 0){
        $rowPaciente = mysqli_fetch_array($conPaciente);
        $pacienteNombre = trim((string)($rowPaciente['nombres'] ?? "") . " " . (string)($rowPaciente['apellidoPat'] ?? "") . " " . (string)($rowPaciente['apellidoMat'] ?? ""));
        $ciTxt = (string)($rowPaciente['ci'] ?? "");
        $celTxt = (string)($rowPaciente['celular'] ?? "");
    }

    // Detalle de la orden (para mostrar líneas en el recibo)
    $conOrdenAtencion = mysqli_query($link, "SELECT `idOrdenAtencion`, `jsonDetallePrestaciones`, `estado`, `montoTotal`, `saldoPendiente`, `fechaHoraRegistro` FROM `orden_atencion` 
    WHERE `idOrdenAtencion` = '{$idOrdenAtencion}'")or die(mysqli_error($link));
    if(mysqli_num_rows($conOrdenAtencion) > 0){
        $rowOrdenAtencion = mysqli_fetch_array($conOrdenAtencion);
        $jsonDetallePrestaciones = (string)($rowOrdenAtencion['jsonDetallePrestaciones'] ?? "[]");
        $estadoOrdenAtencion = (string)($rowOrdenAtencion['estado'] ?? "");
        $fechaHoraRegistroOrden = (string)($rowOrdenAtencion['fechaHoraRegistro'] ?? "");
    }

    // Normalizar items desde jsonDetallePrestaciones (puede venir como lista de arrays o lista de objetos)
    $itemsRaw = json_decode((string)$jsonDetallePrestaciones, true);
    if (!is_array($itemsRaw)) {
        $itemsRaw = [];
    }

    $items = [];
    foreach ($itemsRaw as $row) {
        if (!is_array($row)) continue;

        $prestacion = "";
        $monto = null;

        if (array_key_exists("prestacion", $row) || array_key_exists("monto", $row)) {
            $prestacion = isset($row["prestacion"]) ? trim((string)$row["prestacion"]) : "";
            $monto = $row["monto"] ?? null;
        } else {
            // Formato Handsontable: [prestacion, monto]
            $prestacion = isset($row[0]) ? trim((string)$row[0]) : "";
            $monto = $row[1] ?? null;
        }

        if ($monto === "" || $monto === null) {
            $montoNum = null;
        } elseif (is_numeric($monto)) {
            $montoNum = (float)$monto;
        } else {
            $montoNum = (float)str_replace(",", ".", (string)$monto);
            if (!is_finite($montoNum)) $montoNum = null;
        }

        if ($prestacion === "" && $montoNum === null) continue;
        $items[] = ["prestacion" => $prestacion, "monto" => $montoNum];
    }

    $sumItems = 0.0;
    foreach ($items as $it) {
        $sumItems += (float)($it["monto"] ?? 0);
    }

    // Rutas de archivos
    $logoPathFs  = __DIR__ . "/../../../storage/logo/logo.png";
    $logoForHtml = is_file($logoPathFs) ? "file://" . $logoPathFs : "";

    $pdfDir = __DIR__ . "/../../../storage/temp";
    if (!is_dir($pdfDir)) {
        @mkdir($pdfDir, 0775, true);
    }

    $pdfFileName = "recibo_pago_" . (int)$idRecibo . ".pdf";
    $pdfPath     = $pdfDir . "/" . $pdfFileName;

    $pacienteNombreHtml = $pacienteNombre !== "" ? $pacienteNombre : "Paciente";
    $estadoReciboHtml = $estadoRecibo !== "" ? $estadoRecibo : "RECIBO";

    // HTML tipo recibo (80mm)
    $html  = "<!doctype html><html lang='es'><head><meta charset='utf-8'>";
    $html .= "<style>
        body{font-family: sans-serif; font-size: 9.5pt; color:#111;}
        .wrap{width:100%;}
        .center{text-align:center;}
        .muted{color:#555;}
        .h1{font-size:12pt; font-weight:bold; letter-spacing:0.3px;}
        .line{border-top:1px dashed #666; margin:6px 0;}
        .row{display:block; margin:2px 0;}
        .k{font-weight:bold;}
        table{width:100%; border-collapse:collapse;}
        th,td{padding:2px 0; vertical-align:top;}
        th{font-size:8.5pt; text-transform:uppercase; color:#444;}
        .td-desc{width:72%; padding-right:6px;}
        .td-amt{width:28%; text-align:right; white-space:nowrap;}
        .tot{font-size:10.5pt; font-weight:bold;}
        .logo{max-width:55px; height:auto; margin:0 auto 4px auto; display:block;}
    </style></head><body><div class='wrap'>";

    if ($logoForHtml !== "") {
        $html .= "<img class='logo' src='" . $logoForHtml . "'>";
    }

    $html .= "<div class='center h1'>" . htmlspecialchars($estadoReciboHtml, ENT_QUOTES, "UTF-8") . "</div>";
    $html .= "<div class='center muted'>CliniCloud</div>";
    $html .= "<div class='center muted'>Recibo N° " . (int)$idRecibo . "</div>";

    if ($idOrdenAtencion > 0) {
        $html .= "<div class='center muted'>Orden N° " . (int)$idOrdenAtencion . "</div>";
    }

    $html .= "<div class='line'></div>";
    $html .= "<div class='row'><span class='k'>Fecha:</span> " . htmlspecialchars((string)$fechaRegistro, ENT_QUOTES, "UTF-8") . "</div>";
    $html .= "<div class='row'><span class='k'>Paciente:</span> " . htmlspecialchars($pacienteNombreHtml, ENT_QUOTES, "UTF-8") . "</div>";

    if ($ciTxt !== "") $html .= "<div class='row'><span class='k'>CI:</span> " . htmlspecialchars($ciTxt, ENT_QUOTES, "UTF-8") . "</div>";
    if ($celTxt !== "") $html .= "<div class='row'><span class='k'>Cel:</span> " . htmlspecialchars($celTxt, ENT_QUOTES, "UTF-8") . "</div>";

    $html .= "<div class='line'></div>";
    $html .= "<table><thead><tr><th class='td-desc'>Detalle</th><th class='td-amt'>Monto</th></tr></thead><tbody>";

    if (count($items) === 0) {
        $html .= "<tr><td class='td-desc muted'>Sin detalle registrado</td><td class='td-amt'>0.00</td></tr>";
    } else {
        foreach ($items as $it) {
            $desc = htmlspecialchars((string)$it["prestacion"], ENT_QUOTES, "UTF-8");
            $amt  = number_format((float)($it["monto"] ?? 0), 2, ".", "");
            if ($desc === "") $desc = "<span class='muted'>(Sin descripción)</span>";
            $html .= "<tr><td class='td-desc'>" . $desc . "</td><td class='td-amt'>" . $amt . "</td></tr>";
        }
    }

    $html .= "</tbody></table>";
    $html .= "<div class='line'></div>";

    $totalPagadoTxt = number_format((float)($montoPagado ?? 0), 2, ".", "");
    $saldoPendienteTxt = number_format((float)($saldoPendiente ?? 0), 2, ".", "");
    $html .= "<table>";
    $html .= "<tr><td class='td-desc k tot'>TOTAL PAGADO</td><td class='td-amt tot'>" . $totalPagadoTxt . "</td></tr>";
    $html .= "<tr><td class='td-desc k'>Saldo pendiente</td><td class='td-amt'>" . $saldoPendienteTxt . "</td></tr>";
    $html .= "</table>";

    // fallback si el recibo viene sin detalle monetario por líneas
    if ($montoPagado > 0 && ($sumItems <= 0.000001) && $saldoPendiente <= 0.000001) {
        // No agregamos texto extra para no romper el formato ticket
    }

    $html .= "<div class='line'></div>";
    $html .= "<div class='center muted'>Generado el " . date("d/m/Y H:i") . "</div>";

    $html .= "</div></body></html>";

    // Generar PDF (ancho tipo ticket 80mm)
    $mpdf = new Mpdf([
        "mode" => "utf-8",
        "format" => [80, 200],
        "margin_left" => 4,
        "margin_right" => 4,
        "margin_top" => 4,
        "margin_bottom" => 4
    ]);
    $mpdf->WriteHTML($html);
    $mpdf->Output($pdfPath, "F");

    $pdfWebPath = "storage/temp/" . $pdfFileName;

    echo "<iframe src='" . $pdfWebPath . "' style='width:100%;height:70vh;border:0;'></iframe>";

    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";
}



function procesarCotizacion(){
    global $input;
    global $link;

    $idOrdenAtencion = isset($input['idOrdenAtencion']) ? (int)$input['idOrdenAtencion'] : 0;

    $sql = "UPDATE `orden_atencion` SET `estado`='ORDEN ATENCION' WHERE `idOrdenAtencion` = '{$idOrdenAtencion}'";
    $actualizarOrdenAtencion = mysqli_query($link, $sql)or die(mysqli_error($link));
    if($actualizarOrdenAtencion){
        echo json_encode(['estado' => 'OK', 'mensaje' => 'Cotización procesada correctamente']);
    }
    else{
        echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Error al procesar la cotización']);
    }
}