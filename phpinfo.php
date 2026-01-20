<?php
$micro = microtime(true);
    $fecha = DateTime::createFromFormat('U.u', sprintf('%.6f', $micro));
    $fecha->setTimezone(new DateTimeZone('America/La_Paz'));
    $fechaHoraInicioEvento = $fecha->format('Y-m-d\TH:i:s.v');

    echo $fechaHoraInicioEvento;

    ?>