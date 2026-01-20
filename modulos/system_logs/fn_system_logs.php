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
    case 'muestraLogs':
        muestraLogs();
        break;
    
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function muestraLogs(){
    global $input;

    $fecha           = $input['fecha'];
    $fechaFormateada = date('Ymd', strtotime($fecha));
    $archivo         = "apifact_log_{$fechaFormateada}.log";

    $rutaLogURL = 'api/log/' . $archivo;

    // Armar ruta absoluta real, partiendo de la carpeta del proyecto
    $baseDir = dirname(__DIR__, 2); // Subir dos niveles desde "api/facturacion/"
    $rutaLogFS = $baseDir . '/' . $rutaLogURL;

    echo "<div class='row'>";
      echo "<div class='col-md-12 m-auto'>";
        echo "<div class='card border'>";
          echo "<div class='card-header'><b>Logs de sistema</b></div>";
          echo "<div class='card-body'>";
            // Mostrar la ruta absoluta (solo para debug, puedes quitar esta línea luego)
            //echo $rutaLogFS;
            if (file_exists($rutaLogFS)) {
                echo "<input type='hidden' id='rutaLog' value='{$rutaLogURL}'>";
            } else {
                echo "<div class='alert alert-danger'>Archivo no encontrado</div>";
                echo "<input type='hidden' id='rutaLog' value=''>";
            }
            echo "<div id='editor' style='height: 500px; width: 100%;'></div>";
          echo "</div>";
        echo "</div>";
      echo "</div>";
    echo "</div>";
}


?>