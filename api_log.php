<?php
// api_log.php

date_default_timezone_set('America/La_Paz'); // UTC -4

function registrarLog($tipo, $detalle) {
    // Ruta desde este archivo a la carpeta log
    $rutaLog = __DIR__ . '/api/log';

    // Crea la carpeta si no existe
    if (!is_dir($rutaLog)) {
        mkdir($rutaLog, 0777, true);
    }

    // Formato del nombre del archivo: apifact_log_YYYYMMDD.log
    $fechaActual = date('Ymd');
    $horaActual = date('Y-m-d H:i:s');
    $archivoLog = "$rutaLog/apifact_log_$fechaActual.log";

    // Formato de línea: [fecha hora] - TIPO - detalle
    $linea = "[$horaActual] - $tipo - $detalle" . PHP_EOL;

    // Escribir en el archivo
    file_put_contents($archivoLog, $linea, FILE_APPEND);
}
?>
