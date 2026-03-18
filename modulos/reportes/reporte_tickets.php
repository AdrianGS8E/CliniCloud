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
<h1 class="subheader-title"> Reporte de Tickets
    <small> Módulo de reporte de tickets del sistema </small>
</h1>

<div id='contenido'></div>



<script>
    $(document).ready(function(){

        formParametrosReporteTickets();

        function formParametrosReporteTickets(){
            $("#contenido").html(loader);
            fetch("modulos/reportes/fn_reporte_tickets.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formParametrosReporteTickets"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                // Inicializar los select2 múltiples
                $('.select2-multiple').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Seleccionar opciones',
                    allowClear: true,
                    closeOnSelect: false,
                    language: {
                        noResults: function() {
                            return "No se encontraron resultados";
                        }
                    }
                });

                $("#btnGenerarReporteHtml").click(function () { 
                    let datosReporte = {
                        fechaInicio: $("#fechaInicio").val(),
                        fechaFinal: $("#fechaFinal").val(),
                        usuario: $("#usuario").val(),
                        tipoReporte: $("#tipoReporte").val(),
                        genero: $("#genero").val(),
                        nacionalidad: $("#nacionalidad").val(),
                        controlIngreso: $("#controlIngreso").val(),
                        nombreTicket: $("#nombreTicket").val(),
                        formato: "HTML"
                    }
                    generarReporte(datosReporte);
                });

                $("#btnGenerarReporteExcel").click(function () { 
                    let datosReporte = {
                        fechaInicio: $("#fechaInicio").val(),
                        fechaFinal: $("#fechaFinal").val(),
                        usuario: $("#usuario").val(),
                        tipoReporte: $("#tipoReporte").val(),
                        genero: $("#genero").val(),
                        nacionalidad: $("#nacionalidad").val(),
                        controlIngreso: $("#controlIngreso").val(),
                        nombreTicket: $("#nombreTicket").val(),
                        formato: "EXCEL"
                    }
                    generarReporte(datosReporte);
                });

                $("#btnGenerarReportePdf").click(function () { 
                    let datosReporte = {
                        fechaInicio: $("#fechaInicio").val(),
                        fechaFinal: $("#fechaFinal").val(),
                        usuario: $("#usuario").val(),
                        tipoReporte: $("#tipoReporte").val(),
                        genero: $("#genero").val(),
                        nacionalidad: $("#nacionalidad").val(),
                        controlIngreso: $("#controlIngreso").val(),
                        nombreTicket: $("#nombreTicket").val(),
                        formato: "PDF"
                    }
                    generarReporte(datosReporte);
                });

                

            });
        }


        function generarReporte(datosReporte){
            $("#contenidoReporte").html(loader);
            console.log(datosReporte);
            fetch("modulos/reportes/fn_reporte_tickets.php", {
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

                // Inicializar DataTable y guardar la referencia
                let tablaTickets = $("#tablaReporte").DataTable();

                // Vincular eventos cada vez que la tabla se redibuja (cambio de página, filtrado, etc)
                tablaTickets.on('draw.dt', function () {
                    vincularEventosTablaTickets();
                });

                // Vincular eventos inicialmente
                vincularEventosTablaTickets();

                function vincularEventosTablaTickets() {
                    $(".btnVerTicket").off('click').on('click', function () { 
                        let hash = $(this).attr("id");
                        console.log(hash);
                        verTicket(hash);
                    });
                }
            });
        
        }


        function verTicket(hash){
            console.log("verTicket");
            $("#modal-lg-content").html(loader);
            $("#modal-lg").modal("show");

            fetch("modulos/reportes/fn_reporte_tickets.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "verTicket",
                    hash: hash
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#modal-lg-content").html(data);
            });
            
        }



    });
</script>