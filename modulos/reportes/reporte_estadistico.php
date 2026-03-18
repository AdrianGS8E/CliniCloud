<?php
session_start();

require_once "../../config_db_mysql.php";

?>

<!-- <nav class="app-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb ms-0">
        <li class="breadcrumb-item">Design</li>
        <li class="breadcrumb-item">Documentation</li>
        <li class="breadcrumb-item active" aria-current="page">Core Plugins</li>
    </ol>
</nav> -->
<h1 class="subheader-title"> Reporte Estadistico
    <small> Modulo de reporte de ingresos del sistema </small>
</h1>

<div id='contenido'></div>



<script>
    $(document).ready(function(){

        formParametrosReporteEstadistico();

        function formParametrosReporteEstadistico(){
            $("#contenido").html(loader);
            fetch("modulos/reportes/fn_reporte_estadistico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formParametrosReporteEstadistico"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnGenerarReporteHtml").click(function () { 
                    let datosReporte = {
                        fechaInicio: $("#fechaInicio").val(),
                        fechaFinal: $("#fechaFinal").val(),
                        usuario: $("#usuario").val(),
                        tipoReporte: $("#tipoReporte").val(),
                        formato: "HTML"
                    }
                    generarReporte(datosReporte);
                });

            });
        }


        function generarReporte(datosReporte){
            $("#contenidoReporte").html(loader);
            fetch("modulos/reportes/fn_reporte_estadistico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "generarReporte",
                    datosReporte: datosReporte
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenidoReporte").html(data);
            });
        
        }





    });
</script>