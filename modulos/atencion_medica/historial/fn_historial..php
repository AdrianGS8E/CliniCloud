<?php
session_start();

if (!isset($_SESSION['idUsuario_clinicloud'])) {
    header('Content-Type: application/json');
    echo json_encode(['sesion' => 'cerrada']);
    exit;
}

require_once "../../../config_db_mysql.php";

// Soporte para llamadas JSON (fetch) y multipart/form-data
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
    case "listarHistorial":
        header("Content-Type: text/html; charset=utf-8");
        listarHistorial();
        break;
    default:
        header("Content-Type: application/json");
        echo json_encode(["estado" => "ERROR", "mensaje" => "Función no reconocida."]);
        break;
}

function listarHistorial(){
    global $link;
    global $input;

    $idAtencion = isset($input['idAtencion']) ? (int)$input['idAtencion'] : 0;
    if ($idAtencion <= 0) {
        echo "<div class='alert alert-warning mb-0'>ID de atención inválido.</div>";
        return;
    }

    $idAtencionEsc = mysqli_real_escape_string($link, (string)$idAtencion);
    $sql = "SELECT `idCuaOdontologia`, `idAtencion`, `tipoAtencion`, `fechaRegistro`, `idUsuario`
            FROM `cuaderno_odontologia`
            WHERE `idAtencion` = '$idAtencionEsc'
            ORDER BY `idCuaOdontologia` DESC";
    $res = mysqli_query($link, $sql);

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0'>Historial de registros</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";

    echo "<div class='modal-body'>";
        echo "<div class='table-responsive'>";
            echo "<table class='table table-bordered table-hover mb-0'>";
                echo "<thead class='table-light'>";
                    echo "<tr>";
                        echo "<th>ID Cuaderno</th>";
                        echo "<th>ID Atención</th>";
                        echo "<th>Tipo</th>";
                        echo "<th>Fecha registro</th>";
                        echo "<th>Acción</th>";
                    echo "</tr>";
                echo "</thead>";
                echo "<tbody>";

                if ($res && mysqli_num_rows($res) > 0) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $idCua = (int)$row['idCuaOdontologia'];
                        $idAte = (int)$row['idAtencion'];
                        $tipo  = (string)$row['tipoAtencion'];
                        $fecha = (string)$row['fechaRegistro'];

                        echo "<tr>";
                            echo "<td>" . $idCua . "</td>";
                            echo "<td>" . $idAte . "</td>";
                            echo "<td>" . htmlspecialchars($tipo, ENT_QUOTES, "UTF-8") . "</td>";
                            echo "<td>" . htmlspecialchars($fecha, ENT_QUOTES, "UTF-8") . "</td>";
                            echo "<td class='text-center'>";
                                echo "<button type='button' class='btn btn-sm btn-primary btnVerHistorial' data-idcua='" . $idCua . "' data-tipo='" . htmlspecialchars($tipo, ENT_QUOTES, "UTF-8") . "'>";
                                    echo "<i class='fas fa-eye'></i> Ver";
                                echo "</button>";
                            echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center text-muted'>No hay registros para esta atención.</td></tr>";
                }

                echo "</tbody>";
            echo "</table>";
        echo "</div>";
    echo "</div>";

    echo "<div class='modal-footer'>";
        //echo "<button type='button' class='btn btn-primary waves-effect' id='btnAtrasHistorial'><i class='fas fa-arrow-left'></i> Atrás</button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'><i class='fas fa-times'></i> Cerrar</button>";
    echo "</div>";
}
