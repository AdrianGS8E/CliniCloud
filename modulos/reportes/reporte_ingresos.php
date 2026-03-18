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
<h1 class="subheader-title"> Reporte de Ingresos
    <small> Modulo de reporte de ingresos del sistema </small>
</h1>

<div id='contenido'></div>



<script>
    $(document).ready(function(){

        formParametrosReporteIngresos();

        function formParametrosReporteIngresos(){
            $("#contenido").html(loader);
            fetch("modulos/reportes/fn_reporte_ingresos.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formParametrosReporteIngresos"
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

                function obtenerDatosReporte(formato) {
                    return {
                        fechaInicio: $("#fechaInicio").val(),
                        fechaFinal: $("#fechaFinal").val(),
                        idUsuario: $("#idUsuario").val(),
                        tipoReporte: $("#tipoReporte").val(),
                        estadoRecibo: $("#estadoRecibo").val(),
                        formato: formato
                    };
                }

                $("#btnGenerarReporteHtml").click(function () { 
                    generarReporte(obtenerDatosReporte("HTML"));
                });

                $("#btnGenerarReporteExcel").click(function () { 
                    generarReporte(obtenerDatosReporte("EXCEL"));
                });

                $("#btnGenerarReportePdf").click(function () { 
                    generarReporte(obtenerDatosReporte("PDF"));
                });

                

            });
        }


        function generarReporte(datosReporte){
            $("#contenidoReporte").html(loader);
            console.log(datosReporte);
            fetch("modulos/reportes/fn_reporte_ingresos.php", {
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

                // Inicializar DataTable si existe la tabla de reporte
                if ($("#tablaReporte").length && $.fn.DataTable) {
                    if (!$.fn.DataTable.isDataTable("#tablaReporte")) {
                        $("#tablaReporte").DataTable({
                            language: { url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" },
                            order: [[3, "desc"]],
                            pageLength: 25
                        });
                    }
                }
            });
        
        }




    });
</script>