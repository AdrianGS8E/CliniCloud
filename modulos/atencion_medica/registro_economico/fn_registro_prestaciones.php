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
    case "formularioRegistroPrestaciones":
        formularioRegistroPrestaciones();
        break;
    case "registrarPrestaciones":
        registrarPrestaciones();
        break;
    case "verOrdenAtencion":
        verOrdenAtencion();
        break;
}


function formularioRegistroPrestaciones(){
    global $input;

    $idAtencion = isset($input['idAtencion']) ? (int)$input['idAtencion'] : 0;

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Registro de Prestaciones</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
    echo "<form id='frmRegistroPrestaciones'>";
        echo "<input type='hidden' name='idAtencion' id='idAtencionRegistroPrestaciones' value='{$idAtencion}'>";
        echo "<input type='hidden' name='prestaciones_json' id='prestaciones_json' value='[]'>";

        echo "<div class='row'>";
            echo "<div class='col-12'>";
                echo "<div class='card'>";
                    echo "<div class='card-body'>";
                        

                        echo "<div id='hotPrestaciones' class='ht-theme-horizon'></div>";
                        echo "<div class='d-flex flex-wrap gap-2 mb-3'>";
                            echo "<button type='button' class='btn btn-sm btn-outline-primary' id='btnAgregarFilaPrestacion'>";
                                echo "<i class='fas fa-plus'></i>";
                            echo "</button>";
                            echo "<button type='button' class='btn btn-sm btn-outline-danger' id='btnEliminarFilaPrestacion'>";
                                echo "<i class='fas fa-trash'></i>";
                            echo "</button>";
                        echo "</div>";
                        echo "<div class='d-flex justify-content-end mt-2'>";
                            echo "<div class='fw-semibold fs-4'>Total: <span id='totalPrestacionesValor' class='fs-3'>0.00</span></div>";
                        echo "</div>";

                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";

    echo "</form>";
    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnRegistrarPrestaciones'><i class='fas fa-save'></i> Registrar Prestaciones</button>";
        echo "<button type='button' class='btn btn-warning waves-effect waves-light' id='btnRegistrarCotizacion'><i class='fas fa-calculator'></i> Registrar Cotizacion</button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'><i class='fas fa-times'></i> Cerrar</button>";
    echo "</div>";
}


function registrarPrestaciones(){

    global $input;
    global $link;

    $idAtencion = $input['idAtencion'];
    $jsonDetallePrestaciones = $input['jsonDetallePrestaciones'] ?? "[]";
    $montoTotal = $input['montoTotal'] ?? 0;
    $estado = $input['estado'];

    // Normalizar y limpiar detalle (guardar solo filas llenas)
    $itemsRaw = json_decode((string)$jsonDetallePrestaciones, true);
    if (!is_array($itemsRaw)) {
        $itemsRaw = [];
    }

    $items = [];
    foreach ($itemsRaw as $row) {
        if (!is_array($row)) continue;

        $prestacion = "";
        $monto = null;

        // Aceptar tanto formato [{prestacion,monto}] como [[prestacion,monto]]
        if (array_key_exists("prestacion", $row) || array_key_exists("monto", $row)) {
            $prestacion = isset($row["prestacion"]) ? trim((string)$row["prestacion"]) : "";
            $monto = $row["monto"] ?? null;
        } else {
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

    $jsonDetallePrestaciones = json_encode($items, JSON_UNESCAPED_UNICODE);

    // Total / saldo: asegurar numérico
    if (!is_numeric($montoTotal)) {
        $montoTotal = (float)str_replace(",", ".", (string)$montoTotal);
    }
    $montoTotal = (float)$montoTotal;
    $saldoPendiente = $montoTotal;
    $fechaHoraRegistro = date('Y-m-d H:i:s');
    $idUsuarioRegistro = $_SESSION['idUsuario_clinicloud'];


    $conPaciente = mysqli_query($link, "SELECT `idAtencion`, `idPaciente`, `idConsultorio`, `fechaAtencion`, `idUsuario`, `fechaRegistro`, `estadoAtencion`, `especialidad` 
    FROM `atencion_clinica` WHERE `idAtencion` = '{$idAtencion}'")or die(mysqli_error($link));
    if(mysqli_num_rows($conPaciente) > 0){
        $rowPaciente = mysqli_fetch_array($conPaciente);
        $idPaciente = $rowPaciente['idPaciente'];
    }



    $sql = "INSERT INTO `orden_atencion`(`idPaciente`, `jsonDetallePrestaciones`, `estado`, `montoTotal`, `saldoPendiente`, `fechaHoraRegistro`, `idUsuarioRegistro`) 
    VALUES ('{$idPaciente}','{$jsonDetallePrestaciones}','{$estado}','{$montoTotal}','{$saldoPendiente}','{$fechaHoraRegistro}','{$idUsuarioRegistro}')";

    $consulta = mysqli_query($link, $sql)or die(mysqli_error($link));
    if($consulta){
        echo json_encode(['estado' => 'OK', 'mensaje' => 'Prestaciones registradas con éxito', 'idOrdenAtencion' => mysqli_insert_id($link)]);
    } else {
        echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Error al registrar las prestaciones', 'idOrdenAtencion' => 0]);
    }

}


function verOrdenAtencion(){

    global $input;
    global $link;

    $idOrdenAtencion = $input['idOrdenAtencion'];

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

    $conPaciente = mysqli_query($link, "SELECT `idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `email`, `direccion`, `procedencia`, `residencia`, `nombreTutor`, `celularTutor` FROM `pacientes` 
    WHERE `idPaciente` = '{$idPaciente}'")or die(mysqli_error($link));
    if(mysqli_num_rows($conPaciente) > 0){
        $rowPaciente = mysqli_fetch_array($conPaciente);
        $idPaciente = $rowPaciente['idPaciente'];
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

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0' id=''>Orden Atencion</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";

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

        $pdfFileName = "orden_atencion_" . (int)$idOrdenAtencion . ".pdf";
        $pdfPath     = $pdfDir . "/" . $pdfFileName;

        $pacienteNombre = trim((string)($nombres ?? "") . " " . (string)($apellidoPat ?? "") . " " . (string)($apellidoMat ?? ""));
        $ciTxt = (string)($ci ?? "");
        $celTxt = (string)($celular ?? "");

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

        $html .= "<div class='center h1'>{$estado}</div>";
        $html .= "<div class='center muted'>CliniCloud</div>";
        $html .= "<div class='center muted'>N° " . (int)$idOrdenAtencion . "</div>";
        $html .= "<div class='line'></div>";

        $html .= "<div class='row'><span class='k'>Fecha:</span> " . htmlspecialchars((string)$fechaHoraRegistro, ENT_QUOTES, "UTF-8") . "</div>";
        $html .= "<div class='row'><span class='k'>Paciente:</span> " . htmlspecialchars($pacienteNombre, ENT_QUOTES, "UTF-8") . "</div>";
        if ($ciTxt !== "") $html .= "<div class='row'><span class='k'>CI:</span> " . htmlspecialchars($ciTxt, ENT_QUOTES, "UTF-8") . "</div>";
        if ($celTxt !== "") $html .= "<div class='row'><span class='k'>Cel:</span> " . htmlspecialchars($celTxt, ENT_QUOTES, "UTF-8") . "</div>";

        $html .= "<div class='line'></div>";
        $html .= "<table><thead><tr><th class='td-desc'>Prestación</th><th class='td-amt'>Monto</th></tr></thead><tbody>";

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

        $totalTxt = number_format((float)($montoTotal ?? $sumItems), 2, ".", "");
        $saldoTxt = number_format((float)($saldoPendiente ?? 0), 2, ".", "");
        $html .= "<table>";
        $html .= "<tr><td class='td-desc k tot'>TOTAL</td><td class='td-amt tot'>" . $totalTxt . "</td></tr>";
        $html .= "<tr><td class='td-desc k'>Saldo pendiente</td><td class='td-amt'>" . $saldoTxt . "</td></tr>";
        $html .= "</table>";

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
        //echo "<button type='button' class='btn btn-primary waves-effect waves-light'></button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";

}