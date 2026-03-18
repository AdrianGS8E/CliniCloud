<?php
session_start();

if (!isset($_SESSION['idUsuario_clinicloud'])) {
    // Forzar JSON limpio si se trata de una petición AJAX (fetch)
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
    case "formularioExamenGeneral":
        formularioExamenGeneral();
        break;
    case "guardarExamenGeneral":
        guardarExamenGeneral();
        break;
    case "verFormularioExamenGeneral":
        verFormularioExamenGeneral();
        break;
}


function formularioExamenGeneral(){
    global $link;
    global $input;

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0' id=''>Formulario de Examen General</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
    echo "<form id='frmExamenGeneral'>";

        echo "<div class='row'>";

            // Columna izquierda: EXAMEN GENERAL
            echo "<div class='col-md-6'>";
                echo "<div class='card mb-3'>";
                    echo "<div class='card-header py-2'><strong>I. EXAMEN GENERAL</strong></div>";
                    echo "<div class='card-body p-3'>";

                        // 1. Intervenido quirúrgicamente
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Ha sido intervenido quirúrgicamente alguna vez?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='intervenido_quirurgicamente' id='iq_si' value='SI'><label class='form-check-label' for='iq_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='intervenido_quirurgicamente' id='iq_no' value='NO'><label class='form-check-label' for='iq_no'>NO</label></div>";
                        echo "</div>";
                        echo "<input type='text' class='form-control form-control-sm mt-1' name='intervenido_quirurgicamente_obs' placeholder='Especificar'>";
                        echo "</div>";

                        // 2. Problemas cardíacos
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Tiene problemas cardíacos?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='problemas_cardiacos' id='pc_si' value='SI'><label class='form-check-label' for='pc_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='problemas_cardiacos' id='pc_no' value='NO'><label class='form-check-label' for='pc_no'>NO</label></div>";
                        echo "</div>";
                        echo "<input type='text' class='form-control form-control-sm mt-1' name='problemas_cardiacos_obs' placeholder='Especificar'>";
                        echo "</div>";

                        // 3. Diabético
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Es diabético?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='diabetico' id='diab_si' value='SI'><label class='form-check-label' for='diab_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='diabetico' id='diab_no' value='NO'><label class='form-check-label' for='diab_no'>NO</label></div>";
                        echo "</div>";
                        echo "<input type='text' class='form-control form-control-sm mt-1' name='diabetico_obs' placeholder='Especificar'>";
                        echo "</div>";

                        // 4. Alergia medicamento
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Alergia a algún medicamento?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='alergia_medicamento' id='alerg_si' value='SI'><label class='form-check-label' for='alerg_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='alergia_medicamento' id='alerg_no' value='NO'><label class='form-check-label' for='alerg_no'>NO</label></div>";
                        echo "</div>";
                        echo "<input type='text' class='form-control form-control-sm mt-1' name='alergia_medicamento_obs' placeholder='Especificar'>";
                        echo "</div>";

                        // 5. Cicatrización de heridas
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Cómo es la cicatrización de sus heridas?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='cicatrizacion_heridas' id='ch_si' value='SI'><label class='form-check-label' for='ch_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='cicatrizacion_heridas' id='ch_no' value='NO'><label class='form-check-label' for='ch_no'>NO</label></div>";
                        echo "</div>";
                        echo "<input type='text' class='form-control form-control-sm mt-1' name='cicatrizacion_heridas_obs' placeholder='Especificar'>";
                        echo "</div>";

                        // 6. Problemas coagulación
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Tiene problemas en la coagulación?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='problemas_coagulacion' id='pcoag_si' value='SI'><label class='form-check-label' for='pcoag_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='problemas_coagulacion' id='pcoag_no' value='NO'><label class='form-check-label' for='pcoag_no'>NO</label></div>";
                        echo "</div>";
                        echo "<input type='text' class='form-control form-control-sm mt-1' name='problemas_coagulacion_obs' placeholder='Especificar'>";
                        echo "</div>";

                        // 7. Tratamiento médico actual
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Está actualmente en tratamiento médico?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='tratamiento_medico_actual' id='tma_si' value='SI'><label class='form-check-label' for='tma_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='tratamiento_medico_actual' id='tma_no' value='NO'><label class='form-check-label' for='tma_no'>NO</label></div>";
                        echo "</div>";
                        echo "<input type='text' class='form-control form-control-sm mt-1' name='tratamiento_medico_actual_obs' placeholder='Especificar'>";
                        echo "</div>";

                        // 8. Toma medicamentos
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Toma medicamentos actualmente?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='toma_medicamentos' id='tm_si' value='SI'><label class='form-check-label' for='tm_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='toma_medicamentos' id='tm_no' value='NO'><label class='form-check-label' for='tm_no'>NO</label></div>";
                        echo "</div>";
                        echo "<input type='text' class='form-control form-control-sm mt-1' name='toma_medicamentos_obs' placeholder='Especificar'>";
                        echo "</div>";

                        // 9. Embarazada
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Está embarazada?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='embarazada' id='emb_si' value='SI'><label class='form-check-label' for='emb_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='embarazada' id='emb_no' value='NO'><label class='form-check-label' for='emb_no'>NO</label></div>";
                        echo "</div>";
                        echo "<input type='text' class='form-control form-control-sm mt-1' name='embarazada_obs' placeholder='Especificar'>";
                        echo "</div>";

                        // 10. F.U.M.
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>F.U.M. (Fecha Última Menstruación)</label>";
                        echo "<input type='date' class='form-control form-control-sm' name='fum' placeholder='Fecha'>";
                        echo "</div>";

                        // 11. Motivo de consulta
                        echo "<div class='mb-0'>";
                        echo "<label class='form-label small mb-0'>MOTIVO DE CONSULTA</label>";
                        echo "<textarea class='form-control form-control-sm' name='motivo_consulta' rows='3' placeholder='Describa el motivo de consulta'></textarea>";
                        echo "</div>";

                    echo "</div>";
                echo "</div>";
            echo "</div>";

            // Columna derecha: EXAMEN BUCO DENTAL + HÁBITOS + Firma
            echo "<div class='col-md-6'>";

                echo "<div class='card mb-3'>";
                    echo "<div class='card-header py-2'><strong>II. EXAMEN BUCO DENTAL</strong></div>";
                    echo "<div class='card-body p-3'>";

                        // Higiene dental
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>Higiene dental</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='higiene_dental' id='hd_buena' value='BUENA'><label class='form-check-label' for='hd_buena'>BUENA</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='higiene_dental' id='hd_mala' value='MALA'><label class='form-check-label' for='hd_mala'>MALA</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='higiene_dental' id='hd_regular' value='REGULAR'><label class='form-check-label' for='hd_regular'>REGULAR</label></div>";
                        echo "</div>";
                        echo "</div>";

                        // Usa cepillo dental
                        echo "<div class='mb-2'>";
                        echo "<label class='form-label small mb-0'>¿Usa cepillo dental?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='usa_cepillo' id='uc_si' value='SI'><label class='form-check-label' for='uc_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='usa_cepillo' id='uc_no' value='NO'><label class='form-check-label' for='uc_no'>NO</label></div>";
                        echo "<span class='small'>Frecuencia:</span> <input type='text' class='form-control form-control-sm d-inline-block' style='width:100px' name='frecuencia_cepillo' placeholder='Ej: 2 veces/día'>";
                        echo "</div>";
                        echo "</div>";

                        // Usa hilo dental
                        echo "<div class='mb-0'>";
                        echo "<label class='form-label small mb-0'>¿Usa hilo dental?</label>";
                        echo "<div class='d-flex gap-3 align-items-center flex-wrap'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='usa_hilo_dental' id='uhd_si' value='SI'><label class='form-check-label' for='uhd_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='usa_hilo_dental' id='uhd_no' value='NO'><label class='form-check-label' for='uhd_no'>NO</label></div>";
                        echo "</div>";
                        echo "</div>";

                    echo "</div>";
                echo "</div>";

                echo "<div class='card mb-3'>";
                    echo "<div class='card-header py-2'><strong>III. HÁBITOS Y COSTUMBRES</strong></div>";
                    echo "<div class='card-body p-3'>";

                        echo "<div class='mb-2'><label class='form-label small mb-0'>Respirador bucal</label><div class='d-flex gap-3'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='respirador_bucal' id='rb_si' value='SI'><label class='form-check-label' for='rb_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='respirador_bucal' id='rb_no' value='NO'><label class='form-check-label' for='rb_no'>NO</label></div></div></div>";

                        echo "<div class='mb-2'><label class='form-label small mb-0'>Usa chupón</label><div class='d-flex gap-3'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='usa_chupon' id='uch_si' value='SI'><label class='form-check-label' for='uch_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='usa_chupon' id='uch_no' value='NO'><label class='form-check-label' for='uch_no'>NO</label></div></div></div>";

                        echo "<div class='mb-2'><label class='form-label small mb-0'>Fuma</label><div class='d-flex gap-3'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='fuma' id='fuma_si' value='SI'><label class='form-check-label' for='fuma_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='fuma' id='fuma_no' value='NO'><label class='form-check-label' for='fuma_no'>NO</label></div></div></div>";

                        echo "<div class='mb-2'><label class='form-label small mb-0'>Toma (alcohol)</label><div class='d-flex gap-3'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='toma_alcohol' id='ta_si' value='SI'><label class='form-check-label' for='ta_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='toma_alcohol' id='ta_no' value='NO'><label class='form-check-label' for='ta_no'>NO</label></div></div></div>";

                        echo "<div class='mb-0'><label class='form-label small mb-0'>Masca Coca</label><div class='d-flex gap-3'>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='masca_coca' id='mc_si' value='SI'><label class='form-check-label' for='mc_si'>SI</label></div>";
                        echo "<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='masca_coca' id='mc_no' value='NO'><label class='form-check-label' for='mc_no'>NO</label></div></div></div>";

                    echo "</div>";
                echo "</div>";


            echo "</div>";

        echo "</div>";

    echo "</form>";
    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnGuardarExamenGeneral'><i class='fas fa-save'></i> Guardar</button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'><i class='fas fa-times'></i> Cerrar</button>";
    echo "</div>";
}



function guardarExamenGeneral(){
    global $link;
    global $input;

    $idAtencion = $input['idAtencion'];
    $tipoAtencion = "EXAMEN GENERAL";
    $datosExamenGeneral = $input['datosExamenGeneral'] ?? [];
    $datosExamenGeneralJson = json_encode($datosExamenGeneral, JSON_UNESCAPED_UNICODE);
    if ($datosExamenGeneralJson === false) {
        echo json_encode(["estado" => "ERROR", "mensaje" => "No se pudo convertir el examen general a JSON"]);
        return;
    }
    $fechaRegistro = date("Y-m-d H:i:s");
    $idUsuario = $_SESSION['idUsuario_clinicloud'];

    $idAtencion = mysqli_real_escape_string($link, (string)$idAtencion);
    $tipoAtencion = mysqli_real_escape_string($link, $tipoAtencion);
    $datosExamenGeneralJson = mysqli_real_escape_string($link, $datosExamenGeneralJson);
    $fechaRegistro = mysqli_real_escape_string($link, $fechaRegistro);
    $idUsuario = mysqli_real_escape_string($link, (string)$idUsuario);

    $sql = "INSERT INTO `cuaderno_odontologia`(`idAtencion`, `tipoAtencion`, `jsonInfoCuaderno`, `fechaRegistro`, `idUsuario`) 
    VALUES ('$idAtencion', '$tipoAtencion', '$datosExamenGeneralJson', '$fechaRegistro', '$idUsuario')";
    $result = mysqli_query($link, $sql);
    if ($result) {
        $idCuaOdontologia = mysqli_insert_id($link);
        echo json_encode(["estado" => "OK", "mensaje" => "Examen general guardado correctamente", "idCuaOdontologia" => $idCuaOdontologia]);
    } else {
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al guardar el examen general"]);
    }

}


function verFormularioExamenGeneral(){
    global $link;
    global $input;

    $idCuaOdontologia = $input['idCuaOdontologia'];

    $sql = "SELECT `idCuaOdontologia`, `idAtencion`, `tipoAtencion`, `jsonInfoCuaderno`, `fechaRegistro`, `idUsuario` FROM `cuaderno_odontologia`
    WHERE `idCuaOdontologia` = '$idCuaOdontologia'";
    $conCuadernoOdontologia = mysqli_query($link, $sql)or die(mysqli_error($link));
    if(mysqli_num_rows($conCuadernoOdontologia) > 0){
        $rowCuadernoOdontologia = mysqli_fetch_array($conCuadernoOdontologia);
        $idCuaOdontologia = $rowCuadernoOdontologia['idCuaOdontologia'];
        $idAtencion = $rowCuadernoOdontologia['idAtencion'];
        $tipoAtencion = $rowCuadernoOdontologia['tipoAtencion'];
        $jsonInfoCuaderno = $rowCuadernoOdontologia['jsonInfoCuaderno'];
        $fechaRegistro_CuaOdontologia = $rowCuadernoOdontologia['fechaRegistro'];
        $idUsuario = $rowCuadernoOdontologia['idUsuario'];
    }

    $conAtencion = mysqli_query($link, "SELECT `idAtencion`, `idPaciente`, `idConsultorio`, `fechaAtencion`, `idUsuario`, `fechaRegistro`, `estadoAtencion`, `especialidad` FROM `atencion_clinica` 
    WHERE `idAtencion` = '$idAtencion'")or die(mysqli_error($link));
    if(mysqli_num_rows($conAtencion) > 0){
        $rowAtencion = mysqli_fetch_array($conAtencion);
        $idPaciente = $rowAtencion['idPaciente'];
        $idConsultorio = $rowAtencion['idConsultorio'];
        $fechaAtencion = $rowAtencion['fechaAtencion'];
        $idUsuario = $rowAtencion['idUsuario'];
        $fechaRegistro_Atencion = $rowAtencion['fechaRegistro'];
        $estadoAtencion = $rowAtencion['estadoAtencion'];
        $especialidad = $rowAtencion['especialidad'];
    }

    $conPaciente = mysqli_query($link, "SELECT `idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `email`, `direccion`, `procedencia`, `residencia`, `nombreTutor`, `celularTutor` FROM `pacientes` 
    WHERE `idPaciente` = '$idPaciente'")or die(mysqli_error($link));
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
        $procedencia   = $rowPaciente['procedencia'];
        $residencia   = $rowPaciente['residencia'];
        $nombreTutor  = $rowPaciente['nombreTutor'];
        $celularTutor = $rowPaciente['celularTutor'];
    }

    // Decodificar datos del examen general
    $datosExamen = json_decode($jsonInfoCuaderno, true);
    if (!is_array($datosExamen)) {
        $datosExamen = [];
    }

    $v = function($key) use ($datosExamen) {
        return isset($datosExamen[$key]) ? htmlspecialchars((string)$datosExamen[$key], ENT_QUOTES, "UTF-8") : "";
    };

    // Rutas de archivos
    $logoPathFs  = __DIR__ . "/../../../storage/logo/logo.png";
    $logoForHtml = is_file($logoPathFs) ? "file://" . $logoPathFs : "";

    $pdfDir = __DIR__ . "/../../../storage/temp";
    if (!is_dir($pdfDir)) {
        @mkdir($pdfDir, 0775, true);
    }

    $pdfFileName = "examen_general_" . $idCuaOdontologia . ".pdf";
    $pdfPath     = $pdfDir . "/" . $pdfFileName;

    // Helper: fila compacta pregunta + valor; solo muestra detalle si hay contenido
    $fila = function($label, $valorKey, $obsKey = null) use ($v) {
        $val = $v($valorKey);
        $obs = $obsKey !== null ? trim($v($obsKey)) : "";
        $celdaValor = $val;
        if ($obs !== "") {
            $celdaValor .= " — <span class='obs'>" . $obs . "</span>";
        }
        return "<tr><td class='td-label'>" . $label . "</td><td class='td-value'>" . $celdaValor . "</td></tr>";
    };
    $filaSimple = function($label, $valorKey) use ($v) {
        return "<tr><td class='td-label'>" . $label . "</td><td class='td-value'>" . $v($valorKey) . "</td></tr>";
    };

    $tutorTexto = trim((string)$nombreTutor);
    $celTutor   = trim((string)$celularTutor);
    if ($celTutor !== "") {
        $tutorTexto = $tutorTexto !== "" ? $tutorTexto . " (" . $celTutor . ")" : $celTutor;
    }

    // Construir HTML minimalista para PDF — 2 columnas: izquierda = exámenes, derecha = datos paciente
    $html  = "<!DOCTYPE html>";
    $html .= "<html lang='es'><head><meta charset='UTF-8'>";
    $html .= "<style>
        body { font-family: sans-serif; font-size: 10pt; color: #222; margin: 12px; }
        .header { border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 10px; }
        .logo { width: 70px; height: auto; margin-right: 12px; vertical-align: middle; }
        .title-main { font-size: 14pt; font-weight: bold; margin: 0; }
        .title-sub { font-size: 9pt; color: #555; margin: 2px 0 0 0; }
        table.layout-cols { width: 100%; border-collapse: collapse; }
        table.layout-cols td { vertical-align: top; padding: 0 6px 0 0; }
        table.layout-cols td.col-right { padding: 0 0 0 6px; }
        .section { margin-bottom: 10px; }
        .section-title { font-size: 9.5pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 2px; margin-bottom: 4px; }
        .label { font-size: 8pt; color: #555; }
        .value { font-size: 9pt; font-weight: 500; }
        .fila-pac { margin-bottom: 4px; }
        table.tbl-examen { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.tbl-examen .td-label { width: 48%; padding: 2px 6px 2px 0; vertical-align: top; font-size: 8.5pt; color: #444; }
        table.tbl-examen .td-value { width: 52%; padding: 2px 0; vertical-align: top; font-weight: 500; }
        table.tbl-examen .obs { font-size: 8pt; color: #555; font-weight: normal; }
        .footer { margin-top: 12px; font-size: 8pt; text-align: right; color: #777; }
    </style>";
    $html .= "</head><body>";

    $html .= "<div class='header'>";
    if ($logoForHtml !== "") {
        $html .= "<img src='" . $logoForHtml . "' class='logo'>";
    }
    $html .= "<span class='title-main'>EXAMEN GENERAL ODONTOLÓGICO</span><br>";
    $html .= "<span class='title-sub'>Fecha atención: " . htmlspecialchars((string)$fechaAtencion, ENT_QUOTES, "UTF-8") . "</span>";
    $html .= "</div>";

    // Contenedor 2 filas de 2 columnas:
    //  - Fila 1: izquierda = Datos paciente, derecha = I. Examen general
    //  - Fila 2: izquierda = II. Examen buco dental, derecha = III. Hábitos y costumbres
    $html .= "<table class='layout-cols'>";

    // Fila 1
    $html .= "<tr>";
    // Columna izquierda: Datos del paciente
    $html .= "<td class='col-left' width='50%'>";
    $html .= "<div class='section'>";
    $html .= "<div class='section-title'>Datos del paciente</div>";
    $html .= "<div class='fila-pac'><span class='label'>Nombres y apellidos</span><br><span class='value'>" .
        htmlspecialchars(trim($nombres . " " . $apellidoPat . " " . $apellidoMat), ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>CI</span><br><span class='value'>" . htmlspecialchars((string)$ci, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>Fecha de nacimiento</span><br><span class='value'>" .
        htmlspecialchars((string)$fechaNacimiento, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>Teléfono / Celular</span><br><span class='value'>" . htmlspecialchars((string)$celular, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>Dirección</span><br><span class='value'>" .
        htmlspecialchars((string)$direccion, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "<div class='fila-pac'><span class='label'>Tutor / Apoderado</span><br><span class='value'>" . htmlspecialchars($tutorTexto, ENT_QUOTES, "UTF-8") . "</span></div>";
    $html .= "</div>";
    $html .= "</td>";

    // Columna derecha: I. Examen general
    $html .= "<td class='col-right' width='50%'>";
    $html .= "<div class='section'>";
    $html .= "<div class='section-title'>I. Examen general</div>";
    $html .= "<table class='tbl-examen'>";
    $html .= $fila("Intervenido quirúrgicamente", "intervenido_quirurgicamente", "intervenido_quirurgicamente_obs");
    $html .= $fila("Problemas cardíacos", "problemas_cardiacos", "problemas_cardiacos_obs");
    $html .= $fila("Diabético", "diabetico", "diabetico_obs");
    $html .= $fila("Alergia a medicamentos", "alergia_medicamento", "alergia_medicamento_obs");
    $html .= $fila("Cicatrización de heridas", "cicatrizacion_heridas", "cicatrizacion_heridas_obs");
    $html .= $fila("Problemas de coagulación", "problemas_coagulacion", "problemas_coagulacion_obs");
    $html .= $fila("Tratamiento médico actual", "tratamiento_medico_actual", "tratamiento_medico_actual_obs");
    $html .= $fila("Toma medicamentos", "toma_medicamentos", "toma_medicamentos_obs");
    $html .= $fila("Embarazada", "embarazada", "embarazada_obs");
    $html .= $filaSimple("F.U.M. (Fecha última menstruación)", "fum");
    $html .= "<tr><td class='td-label'>Motivo de consulta</td><td class='td-value'>" . nl2br($v('motivo_consulta')) . "</td></tr>";
    $html .= "</table></div>";
    $html .= "</td>";
    $html .= "</tr>";

    // Fila 2
    $html .= "<tr>";
    // Columna izquierda: II. Examen buco dental
    $html .= "<td class='col-left' width='50%'>";
    $html .= "<div class='section'>";
    $html .= "<div class='section-title'>II. Examen buco dental</div>";
    $html .= "<table class='tbl-examen'>";
    $html .= $filaSimple("Higiene dental", "higiene_dental");
    $html .= $fila("Usa cepillo dental", "usa_cepillo", "frecuencia_cepillo");
    $html .= $filaSimple("Usa hilo dental", "usa_hilo_dental");
    $html .= "</table></div>";
    $html .= "</td>";

    // Columna derecha: III. Hábitos y costumbres
    $html .= "<td class='col-right' width='50%'>";
    $html .= "<div class='section'>";
    $html .= "<div class='section-title'>III. Hábitos y costumbres</div>";
    $html .= "<table class='tbl-examen'>";
    $html .= $filaSimple("Respirador bucal", "respirador_bucal");
    $html .= $filaSimple("Usa chupón", "usa_chupon");
    $html .= $filaSimple("Fuma", "fuma");
    $html .= $filaSimple("Toma (alcohol)", "toma_alcohol");
    $html .= $filaSimple("Masca coca", "masca_coca");
    $html .= "</table></div>";
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "</table>";

    $html .= "<div class='footer'>Generado el " . date("d/m/Y H:i") . "</div>";

    $html .= "</body></html>";

    // Generar PDF
    $mpdf = new Mpdf(["format" => "A4"]);
    $mpdf->WriteHTML($html);
    $mpdf->Output($pdfPath, "F");

    $pdfWebPath = "storage/temp/" . $pdfFileName;

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0' id=''>Impresión de Examen General</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
        echo "<iframe src='" . $pdfWebPath . "' style='width:100%;height:70vh;border:0;'></iframe>";
    echo "</div>";
    echo "<div class='modal-footer'>";
        //echo "<a href='" . $pdfWebPath . "' target='_blank' class='btn btn-primary waves-effect waves-light'><i class='fas fa-print'></i> Abrir / Imprimir</a>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";

}