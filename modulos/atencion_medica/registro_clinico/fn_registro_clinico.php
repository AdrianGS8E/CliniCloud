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
    case "formularioRegistroClinico":
        formularioRegistroClinico();
        break;
    case "guardarRegistroClinico":
        guardarRegistroClinico();
        break;
    case "verRegistroClinico":
        verRegistroClinico();
        break;
}


function formularioRegistroClinico(){
    global $input;

    $idAtencion = isset($input['idAtencion']) ? (int)$input['idAtencion'] : 0;

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Registro de Tratamiento Médico</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
    echo "<form id='frmRegistroClinico'>";

        echo "<div class='row'>";
            echo "<div class='col-12'>";
                echo "<div class='card'>";
                    echo "<div class='card-body'>";

                        echo "<div class='mb-3'>";
                        echo "<label class='form-label'>Diagnóstico</label>";
                        echo "<input type='text' class='form-control' name='diagnostico' placeholder='Ej: Caries dental, Gingivitis...'>";
                        echo "</div>";

                        echo "<div class='row'>";
                            echo "<div class='col-md-6'>";
                                echo "<div class='mb-3'>";
                                echo "<label class='form-label'>Pieza</label>";
                                echo "<input type='text' class='form-control' name='pieza' placeholder='Ej: 11, 26, 36...'>";
                                echo "</div>";
                            echo "</div>";
                            echo "<div class='col-md-6'>";
                                echo "<div class='mb-3'>";
                                echo "<label class='form-label'>Medición</label>";
                                echo "<input type='text' class='form-control' name='medicion' placeholder='Ej: 3mm, 5mm...'>";
                                echo "</div>";
                            echo "</div>";
                        echo "</div>";

                        echo "<div class='mb-0'>";
                        echo "<label class='form-label'>Tratamiento</label>";
                        echo "<textarea class='form-control' name='tratamiento' rows='6' placeholder='Describa el tratamiento realizado, procedimiento, medicación, indicaciones...'></textarea>";
                        echo "</div>";

                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";

    echo "</form>";
    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnGuardarRegistroClinico'><i class='fas fa-save'></i> Guardar</button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'><i class='fas fa-times'></i> Cerrar</button>";
    echo "</div>";
}


function guardarRegistroClinico(){
    global $link;
    global $input;

    $idAtencion = $input['idAtencion'] ?? '';
    $datos = $input['datosRegistroClinico'] ?? [];

    $diagnostico = isset($datos['diagnostico']) ? trim($datos['diagnostico']) : '';
    $pieza      = isset($datos['pieza'])      ? trim($datos['pieza'])      : '';
    $medicion   = isset($datos['medicion'])   ? trim($datos['medicion'])   : '';
    $tratamiento= isset($datos['tratamiento'])? trim($datos['tratamiento']): '';

    $tipoAtencion = "TRATAMIENTO MEDICO";
    $jsonInfo = json_encode([
        'diagnostico' => $diagnostico,
        'pieza'       => $pieza,
        'medicion'    => $medicion,
        'tratamiento' => $tratamiento
    ], JSON_UNESCAPED_UNICODE);

    if ($jsonInfo === false) {
        header("Content-Type: application/json");
        echo json_encode(["estado" => "ERROR", "mensaje" => "No se pudo preparar los datos."]);
        return;
    }

    $fechaRegistro = date("Y-m-d H:i:s");
    $idUsuario = $_SESSION['idUsuario_clinicloud'];

    $idAtencion     = mysqli_real_escape_string($link, (string)$idAtencion);
    $tipoAtencion   = mysqli_real_escape_string($link, $tipoAtencion);
    $jsonInfo       = mysqli_real_escape_string($link, $jsonInfo);
    $fechaRegistro  = mysqli_real_escape_string($link, $fechaRegistro);
    $idUsuario      = mysqli_real_escape_string($link, (string)$idUsuario);

    $sql = "INSERT INTO `cuaderno_odontologia`(`idAtencion`, `tipoAtencion`, `jsonInfoCuaderno`, `fechaRegistro`, `idUsuario`) 
            VALUES ('$idAtencion', '$tipoAtencion', '$jsonInfo', '$fechaRegistro', '$idUsuario')";
    $result = mysqli_query($link, $sql);

    if ($result) {
        $idCuaOdontologia = mysqli_insert_id($link);
        header("Content-Type: application/json");
        echo json_encode([
            "estado" => "OK",
            "mensaje" => "Tratamiento médico registrado correctamente",
            "idCuaOdontologia" => (int)$idCuaOdontologia
        ]);
    } else {
        header("Content-Type: application/json");
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al guardar el registro clínico."]);
    }
}


function verRegistroClinico(){
    global $link;
    global $input;

    $idCuaOdontologia = isset($input['idCuaOdontologia']) ? (int)$input['idCuaOdontologia'] : 0;
    if ($idCuaOdontologia <= 0) {
        echo "<div class='modal-body'><p class='text-danger'>Registro no encontrado.</p></div>";
        return;
    }

    $idCuaEsc = mysqli_real_escape_string($link, (string)$idCuaOdontologia);
    $sql = "SELECT `idCuaOdontologia`, `idAtencion`, `tipoAtencion`, `jsonInfoCuaderno`, `fechaRegistro`
            FROM `cuaderno_odontologia` WHERE `idCuaOdontologia` = '$idCuaEsc'";
    $res = mysqli_query($link, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
        echo "<div class='modal-body'><p class='text-danger'>Registro no encontrado.</p></div>";
        return;
    }

    $row = mysqli_fetch_assoc($res);
    $idAtencion = $row['idAtencion'];
    $jsonInfoCuaderno = $row['jsonInfoCuaderno'];
    $fechaRegistro = $row['fechaRegistro'];

    // Datos de atención
    $idAtencionEsc = mysqli_real_escape_string($link, (string)$idAtencion);
    $conAtencion = mysqli_query($link, "SELECT `idAtencion`, `idPaciente`, `fechaAtencion` FROM `atencion_clinica` WHERE `idAtencion` = '$idAtencionEsc'");
    $idPaciente = null;
    $fechaAtencion = "";
    if ($conAtencion && mysqli_num_rows($conAtencion) > 0) {
        $rowAtencion = mysqli_fetch_assoc($conAtencion);
        $idPaciente = $rowAtencion['idPaciente'];
        $fechaAtencion = $rowAtencion['fechaAtencion'];
    }

    // Datos del paciente
    $nombres = $apellidoPat = $apellidoMat = $ci = $fechaNacimiento = $celular = $direccion = $nombreTutor = $celularTutor = "";
    if ($idPaciente) {
        $idPacienteEsc = mysqli_real_escape_string($link, (string)$idPaciente);
        $conPaciente = mysqli_query($link, "SELECT `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `direccion`, `nombreTutor`, `celularTutor` FROM `pacientes` WHERE `idPaciente` = '$idPacienteEsc'");
        if ($conPaciente && mysqli_num_rows($conPaciente) > 0) {
            $rowPaciente = mysqli_fetch_assoc($conPaciente);
            $ci = $rowPaciente['ci'];
            $apellidoPat = $rowPaciente['apellidoPat'];
            $apellidoMat = $rowPaciente['apellidoMat'];
            $nombres = $rowPaciente['nombres'];
            $fechaNacimiento = $rowPaciente['fechaNacimiento'];
            $celular = $rowPaciente['celular'];
            $direccion = $rowPaciente['direccion'];
            $nombreTutor = $rowPaciente['nombreTutor'];
            $celularTutor = $rowPaciente['celularTutor'];
        }
    }

    $datos = json_decode($jsonInfoCuaderno, true);
    if (!is_array($datos)) {
        $datos = [];
    }
    $v = function($key) use ($datos) {
        return isset($datos[$key]) ? htmlspecialchars((string)$datos[$key], ENT_QUOTES, "UTF-8") : "";
    };

    // Logo y ruta del PDF
    $logoPathFs = __DIR__ . "/../../../storage/logo/logo.png";
    $logoForHtml = is_file($logoPathFs) ? "file://" . $logoPathFs : "";
    $pdfDir = __DIR__ . "/../../../storage/temp";
    if (!is_dir($pdfDir)) {
        @mkdir($pdfDir, 0775, true);
    }
    $pdfFileName = "registro_clinico_" . $idCuaOdontologia . ".pdf";
    $pdfPath = $pdfDir . "/" . $pdfFileName;

    $tutorTexto = trim((string)$nombreTutor);
    $celTutor = trim((string)$celularTutor);
    if ($celTutor !== "") {
        $tutorTexto = $tutorTexto !== "" ? $tutorTexto . " (" . $celTutor . ")" : $celTutor;
    }

    // HTML para el PDF
    $html = "<!DOCTYPE html>";
    $html .= "<html lang='es'><head><meta charset='UTF-8'>";
    $html .= "<style>
        body { font-family: sans-serif; font-size: 10pt; color: #222; margin: 12px; }
        .header { border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 10px; }
        .logo { width: 70px; height: auto; margin-right: 12px; vertical-align: middle; }
        .title-main { font-size: 14pt; font-weight: bold; margin: 0; }
        .title-sub { font-size: 9pt; color: #555; margin: 2px 0 0 0; }
        .section { margin-bottom: 12px; }
        .section-title { font-size: 9.5pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 2px; margin-bottom: 6px; }
        .label { font-size: 8pt; color: #555; }
        .value { font-size: 9pt; font-weight: 500; }
        .fila-pac { margin-bottom: 4px; }
        table.tbl-tratamiento { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.tbl-tratamiento .td-label { width: 22%; padding: 4px 8px 4px 0; vertical-align: top; font-size: 8.5pt; color: #444; }
        table.tbl-tratamiento .td-value { width: 78%; padding: 4px 0; vertical-align: top; font-weight: 500; }
        .tratamiento-text { white-space: pre-wrap; }
        .footer { margin-top: 14px; font-size: 8pt; text-align: right; color: #777; }
    </style>";
    $html .= "</head><body>";

    $html .= "<div class='header'>";
    if ($logoForHtml !== "") {
        $html .= "<img src='" . $logoForHtml . "' class='logo'>";
    }
    $html .= "<span class='title-main'>REGISTRO DE TRATAMIENTO MÉDICO</span><br>";
    $html .= "<span class='title-sub'>Fecha de atención: " . htmlspecialchars((string)$fechaAtencion, ENT_QUOTES, "UTF-8") . " — Registrado: " . htmlspecialchars((string)$fechaRegistro, ENT_QUOTES, "UTF-8") . "</span>";
    $html .= "</div>";

    $html .= "<table style='width:100%; border-collapse:collapse;'><tr><td style='vertical-align:top; width:50%; padding-right:12px;'>";
    $html .= "<div class='section'>";
    $html .= "<div class='section-title'>Datos del paciente</div>";
    $html .= "<div class='fila-pac'><span class='label'>Nombres y apellidos</span><br><span class='value'>" . htmlspecialchars(trim($nombres . " " . $apellidoPat . " " . $apellidoMat), ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>CI</span><br><span class='value'>" . htmlspecialchars((string)$ci, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>Fecha de nacimiento</span><br><span class='value'>" . htmlspecialchars((string)$fechaNacimiento, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>Teléfono / Celular</span><br><span class='value'>" . htmlspecialchars((string)$celular, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>Dirección</span><br><span class='value'>" . htmlspecialchars((string)$direccion, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>Tutor / Apoderado</span><br><span class='value'>" . htmlspecialchars($tutorTexto, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "</div>";
    $html .= "</td><td style='vertical-align:top; width:50%;'>";
    $html .= "<div class='section'>";
    $html .= "<div class='section-title'>Tratamiento registrado</div>";
    $html .= "<table class='tbl-tratamiento'>";
    $html .= "<tr><td class='td-label'>Diagnóstico</td><td class='td-value'><strong>" . $v('diagnostico') . "</strong></td></tr>";
    $html .= "<tr><td class='td-label'>Pieza</td><td class='td-value'>" . $v('pieza') . "</td></tr>";
    $html .= "<tr><td class='td-label'>Medición</td><td class='td-value'>" . $v('medicion') . "</td></tr>";
    $html .= "<tr><td class='td-label'>Tratamiento</td><td class='td-value tratamiento-text'>" . nl2br($v('tratamiento')) . "</td></tr>";
    $html .= "</table></div>";
    $html .= "</td></tr></table>";

    $html .= "<div class='footer'>Generado el " . date("d/m/Y H:i") . "</div>";
    $html .= "</body></html>";

    $mpdf = new Mpdf(["format" => "A4"]);
    $mpdf->WriteHTML($html);
    $mpdf->Output($pdfPath, "F");

    $pdfWebPath = "storage/temp/" . $pdfFileName;

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Impresión de Registro de Tratamiento Médico</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
        echo "<iframe src='" . $pdfWebPath . "' style='width:100%;height:70vh;border:0;'></iframe>";
    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";
}
