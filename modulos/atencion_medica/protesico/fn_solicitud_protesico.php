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
    case "formularioSolicitudProtesico":
        formularioSolicitudProtesico();
        break;
    case "guardarSolicitudProtesico":
        guardarSolicitudProtesico();
        break;
    case "verSolicitudProtesico":
        verSolicitudProtesico();
        break;
}


function formularioSolicitudProtesico(){
    global $link;
    global $input;

    $idAtencion = isset($input['idAtencion']) ? (int)$input['idAtencion'] : 0;

    // Obtener lista de médicos (usuarios con perfil MEDICO activos)
    $listaMedicos = [];
    $sqlMedicos = "SELECT `idUsuario`, `nombreUs`, `primerApUs`, `segundoApUs` FROM `usuarios` 
                   WHERE `perfilUs` = 'MEDICO' AND (`estadoUs` = 'ACTIVO' OR `estadoUs` IS NULL OR `estadoUs` = '') 
                   ORDER BY `primerApUs`, `segundoApUs`, `nombreUs` ASC";
    $resMedicos = mysqli_query($link, $sqlMedicos);
    if ($resMedicos) {
        while ($rowMed = mysqli_fetch_assoc($resMedicos)) {
            $nombreCompleto = trim($rowMed['nombreUs'] . ' ' . $rowMed['primerApUs'] . ' ' . $rowMed['segundoApUs']);
            $listaMedicos[] = ['idUsuario' => $rowMed['idUsuario'], 'nombre' => $nombreCompleto];
        }
    }

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Solicitud de Protésico</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
    echo "<form id='frmSolicitudProtesico'>";

        echo "<div class='row'>";
            echo "<div class='col-12'>";
                echo "<div class='card'>";
                    echo "<div class='card-body'>";

                        echo "<div class='mb-3'>";
                        echo "<label class='form-label'>Médico que realizará el protésico</label>";
                        echo "<select class='form-select' name='id_medico_protesico' id='id_medico_protesico'>";
                        echo "<option value=''>-- Seleccione el médico --</option>";
                        foreach ($listaMedicos as $med) {
                            $idM = (int)$med['idUsuario'];
                            $nom = htmlspecialchars($med['nombre'], ENT_QUOTES, 'UTF-8');
                            echo "<option value='" . $idM . "'>" . $nom . "</option>";
                        }
                        echo "</select>";
                        echo "</div>";

                        echo "<div class='mb-0'>";
                        echo "<label class='form-label'>Detalle del protésico</label>";
                        echo "<textarea class='form-control' name='detalle_protesico' id='detalle_protesico' rows='6' placeholder='Describa el tipo de protésico, piezas, materiales, indicaciones...'></textarea>";
                        echo "</div>";

                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";

    echo "</form>";
    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnGuardarSolicitudProtesico'><i class='fas fa-save'></i> Guardar</button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'><i class='fas fa-times'></i> Cerrar</button>";
    echo "</div>";
}


function guardarSolicitudProtesico(){
    global $link;
    global $input;

    $idAtencion = $input['idAtencion'] ?? '';
    $datos = $input['datosSolicitudProtesico'] ?? [];

    $idMedicoProtesico = isset($datos['id_medico_protesico']) ? trim((string)$datos['id_medico_protesico']) : '';
    $detalleProtesico  = isset($datos['detalle_protesico']) ? trim($datos['detalle_protesico']) : '';

    // Obtener nombre del médico para el PDF
    $nombreMedico = '';
    if ($idMedicoProtesico !== '') {
        $idMedEsc = mysqli_real_escape_string($link, $idMedicoProtesico);
        $conMed = mysqli_query($link, "SELECT CONCAT(nombreUs, ' ', primerApUs, ' ', segundoApUs) AS nombreCompleto FROM usuarios WHERE idUsuario = '$idMedEsc'");
        if ($conMed && mysqli_num_rows($conMed) > 0) {
            $rowMed = mysqli_fetch_assoc($conMed);
            $nombreMedico = $rowMed['nombreCompleto'] ?? '';
        }
    }

    $tipoAtencion = "SOLICITUD PROTESICO";
    $jsonInfo = json_encode([
        'id_medico_protesico' => $idMedicoProtesico,
        'nombre_medico'       => $nombreMedico,
        'detalle_protesico'   => $detalleProtesico
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
            "mensaje" => "Solicitud protésico registrada correctamente",
            "idCuaOdontologia" => (int)$idCuaOdontologia
        ]);
    } else {
        header("Content-Type: application/json");
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al guardar la solicitud protésico."]);
    }
}


function verSolicitudProtesico(){
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
    $pdfFileName = "solicitud_protesico_" . $idCuaOdontologia . ".pdf";
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
        table.tbl-protesico { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.tbl-protesico .td-label { width: 28%; padding: 4px 8px 4px 0; vertical-align: top; font-size: 8.5pt; color: #444; }
        table.tbl-protesico .td-value { width: 72%; padding: 4px 0; vertical-align: top; font-weight: 500; }
        .detalle-text { white-space: pre-wrap; }
        .footer { margin-top: 14px; font-size: 8pt; text-align: right; color: #777; }
    </style>";
    $html .= "</head><body>";

    $html .= "<div class='header'>";
    if ($logoForHtml !== "") {
        $html .= "<img src='" . $logoForHtml . "' class='logo'>";
    }
    $html .= "<span class='title-main'>SOLICITUD DE PROTÉSICO</span><br>";
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
    $html .= "<div class='section-title'>Solicitud protésico</div>";
    $html .= "<table class='tbl-protesico'>";
    $html .= "<tr><td class='td-label'>Médico que realizará el protésico</td><td class='td-value'><strong>" . $v('nombre_medico') . "</strong></td></tr>";
    $html .= "<tr><td class='td-label'>Detalle del protésico</td><td class='td-value detalle-text'>" . nl2br($v('detalle_protesico')) . "</td></tr>";
    $html .= "</table></div>";
    $html .= "</td></tr></table>";

    $html .= "<div class='footer'>Generado el " . date("d/m/Y H:i") . "</div>";
    $html .= "</body></html>";

    $mpdf = new Mpdf(["format" => "A4"]);
    $mpdf->WriteHTML($html);
    $mpdf->Output($pdfPath, "F");

    $pdfWebPath = "storage/temp/" . $pdfFileName;

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Impresión - Solicitud de Protésico</h4>";
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
