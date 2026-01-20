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
    case "listaConsultorios":
        listaConsultorios();
        break;
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function listaConsultorios(){
    global $link;
    global $input;

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='d-flex flex-wrap gap-2'>";
                $sql = "SELECT `idConsultorio`, `codigo`, `descripcion` FROM `consultorios` ORDER BY `codigo` ASC";
                $result = mysqli_query($link, $sql);
                while ($row = mysqli_fetch_array($result)) {
                    echo "<button class='btn btn-outline-primary btnFormEditarConsultorio' id='" . $row['idConsultorio'] . "' title='Editar Consultorio'>";
                        echo "<strong>" . htmlspecialchars($row['codigo']) . "</strong> - " . htmlspecialchars($row['descripcion']);
                    echo "</button>";
                }
            echo "</div>";
        echo "</div>";
    echo "</div>";
}


?>