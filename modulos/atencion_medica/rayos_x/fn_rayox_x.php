<?php
session_start();

if (!isset($_SESSION['idUsuario_clinicloud'])) {
    header('Content-Type: application/json');
    echo json_encode(['sesion' => 'cerrada']);
    exit;
}

require_once "../../../config_db_mysql.php";

// Soporte para llamadas JSON (fetch) y multipart/form-data (subida de archivo)
$input = [];
$funcion = null;

if (isset($_POST['funcion'])) {
    $funcion = $_POST['funcion'];
    $input = $_POST;
} else {
    $inputJSON = file_get_contents("php://input");
    $input = json_decode($inputJSON, true) ?: [];
    $funcion = isset($input['funcion']) ? $input['funcion'] : null;
}

if (!$funcion) {
    header("Content-Type: application/json");
    echo json_encode(["estado" => "ERROR", "mensaje" => "No se especificó la función a ejecutar."]);
    exit;
}

switch ($funcion) {
    case "formularioRayosX":
        formularioRayosX();
        break;
    case "guardarRayosX":
        guardarRayosX();
        break;
    case "verRayosX":
        verRayosX();
        break;
    default:
        header("Content-Type: application/json");
        echo json_encode(["estado" => "ERROR", "mensaje" => "Función no reconocida."]);
        break;
}


function formularioRayosX(){
    global $input;

    $idAtencion = isset($input['idAtencion']) ? (int)$input['idAtencion'] : 0;

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Registro de radiografía</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
    echo "<form id='frmRayosX' enctype='multipart/form-data'>";

        echo "<input type='hidden' name='idAtencion' value='" . htmlspecialchars((string)$idAtencion, ENT_QUOTES, 'UTF-8') . "'>";

        echo "<div class='row'>";
            echo "<div class='col-12'>";
                echo "<div class='card'>";
                    echo "<div class='card-body'>";

                        echo "<div class='mb-3'>";
                        echo "<label class='form-label'>Descripción de la radiografía</label>";
                        echo "<textarea class='form-control' name='descripcion' rows='3' placeholder='Ej: Radiografía panorámica, pieza 26, control de tratamiento...'></textarea>";
                        echo "</div>";

                        echo "<div class='mb-0'>";
                        echo "<label class='form-label'>Archivo de imagen</label>";
                        echo "<input type='file' class='form-control' name='archivo' accept='image/*'>";
                        echo "<small class='text-muted'>Formatos permitidos: PNG, JPG, JPEG, GIF, WEBP.</small>";
                        echo "</div>";

                    echo "</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";

    echo "</form>";
    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnGuardarRayosX'><i class='fas fa-save'></i> Guardar</button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'><i class='fas fa-times'></i> Cerrar</button>";
    echo "</div>";
}


function guardarRayosX(){
    global $link;

    header("Content-Type: application/json");

    $idAtencion = isset($_POST['idAtencion']) ? (int)$_POST['idAtencion'] : 0;
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

    if ($idAtencion <= 0) {
        echo json_encode(["estado" => "ERROR", "mensaje" => "ID de atención inválido."]);
        return;
    }

    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["estado" => "ERROR", "mensaje" => "Debe seleccionar un archivo de imagen válido."]);
        return;
    }

    $archivo = $_FILES['archivo'];
    $nombreOriginal = $archivo['name'];
    $tmpName = $archivo['tmp_name'];

    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    $extPermitidas = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    if (!in_array($extension, $extPermitidas)) {
        echo json_encode(["estado" => "ERROR", "mensaje" => "Formato de imagen no permitido."]);
        return;
    }

    $dirFs = __DIR__ . "/../../../storage/rayos_x";
    if (!is_dir($dirFs)) {
        @mkdir($dirFs, 0775, true);
    }

    $nombreArchivo = "rayosx_" . $idAtencion . "_" . time() . "." . $extension;
    $rutaFs = $dirFs . "/" . $nombreArchivo;

    if (!move_uploaded_file($tmpName, $rutaFs)) {
        echo json_encode(["estado" => "ERROR", "mensaje" => "No se pudo guardar el archivo de imagen."]);
        return;
    }

    // Ruta que se guardará en la BD para poder mostrar la imagen en el navegador
    $rutaWeb = "storage/rayos_x/" . $nombreArchivo;

    $tipoAtencion = "RAYOS X";
    $jsonInfo = json_encode([
        'descripcion' => $descripcion,
        'rutaImagen'  => $rutaWeb
    ], JSON_UNESCAPED_UNICODE);

    if ($jsonInfo === false) {
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
        echo json_encode([
            "estado" => "OK",
            "mensaje" => "Radiografía registrada correctamente",
            "idCuaOdontologia" => (int)$idCuaOdontologia
        ]);
    } else {
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al guardar la radiografía."]);
    }
}


function verRayosX(){
    global $link;
    global $input;

    $idCuaOdontologia = isset($input['idCuaOdontologia']) ? (int)$input['idCuaOdontologia'] : 0;
    if ($idCuaOdontologia <= 0) {
        echo "<div class='modal-body'><p class='text-danger'>Registro no encontrado.</p></div>";
        return;
    }

    $idCuaEsc = mysqli_real_escape_string($link, (string)$idCuaOdontologia);
    $sql = "SELECT `jsonInfoCuaderno`, `fechaRegistro`
            FROM `cuaderno_odontologia` WHERE `idCuaOdontologia` = '$idCuaEsc'";
    $res = mysqli_query($link, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
        echo "<div class='modal-body'><p class='text-danger'>Registro no encontrado.</p></div>";
        return;
    }

    $row = mysqli_fetch_assoc($res);
    $jsonInfoCuaderno = $row['jsonInfoCuaderno'];
    $fechaRegistro = $row['fechaRegistro'];

    $datos = json_decode($jsonInfoCuaderno, true);
    if (!is_array($datos)) {
        $datos = [];
    }

    $descripcion = isset($datos['descripcion']) ? htmlspecialchars((string)$datos['descripcion'], ENT_QUOTES, "UTF-8") : "";
    $rutaImagen = isset($datos['rutaImagen']) ? htmlspecialchars((string)$datos['rutaImagen'], ENT_QUOTES, "UTF-8") : "";

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Detalle de radiografía</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";

    echo "<div class='modal-body'>";
        echo "<div class='mb-3'>";
            echo "<label class='form-label'>Descripción</label>";
            echo "<p>" . nl2br($descripcion) . "</p>";
        echo "</div>";

        if ($rutaImagen !== "") {
            echo "<div class='mb-0 text-center'>";
                echo "<img src='" . $rutaImagen . "' alt='Radiografía' class='img-fluid' style='max-height:70vh;'>";
            echo "</div>";
        } else {
            echo "<p class='text-warning'>No se encontró la imagen asociada.</p>";
        }

        echo "<div class='mt-3 text-end'><small class='text-muted'>Registrado el " . htmlspecialchars((string)$fechaRegistro, ENT_QUOTES, "UTF-8") . "</small></div>";
    echo "</div>";

    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";
}
