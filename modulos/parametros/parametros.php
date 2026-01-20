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
<h1 class="subheader-title"> Parametros
    <small> Parametros de configuracion del sistema</small>
</h1>

<div id='contenido'></div>

<!-- <div class="row">
    <div class="col-md-4">
        <div class='card border'>
            <div class='card-header'>
                <b>Lista de usuarios</b>
            </div>
            <div class='card-body'>
                
            </div>
            <div class='card-footer text-right py-2 text-center'>
                <button class='btn btn-primary' id='btnBuscarSocios'><i class='fas fa-search'></i> Buscar</a>
            </div>
        </div>
    </div>
</div> -->



<script>
    $(document).ready(function(){

        listaParamtros();
        function listaParamtros() {
            console.log("listando usuarios");
            $("#contenido").html(loader);
            fetch("modulos/parametros/fn_parametros.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "listaParamtros"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $(".btnFormEditarParametro").click(function () { 
                    let idParametro = $(this).attr("id");
                    formEditarParametro(idParametro);
                });

                $("#btnFormFirmaDigital").click(function () { 
                    formFirmaDigital();
                });

            });
        }

        function formEditarParametro(idParametro){
            $("#modal-content").html(loader);
            $("#modal").modal("show");
            fetch("modulos/parametros/fn_parametros.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formEditarParametro",
                    idParametro: idParametro
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#modal-content").html(data);

                $("#btnEditarParametro").click(function () { 
                    let clave = $("#clave").val();
                    let valor = $("#valor").val();
                    let descripcion = $("#descripcion").val();
                    let datosEditarParametro = {
                        idParametro: idParametro,
                        clave: clave,
                        valor: valor,
                        descripcion: descripcion
                    }
                    editarParametro(datosEditarParametro);
                });

            });
        }

        function editarParametro(datosEditarParametro){
            $("#btnEditarParametro").attr("disabled", true);
            console.log(datosEditarParametro);
            fetch("modulos/parametros/fn_parametros.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "editarParametro",
                    datosEditarParametro: datosEditarParametro
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                $("#btnEditarParametro").attr("disabled", false);

                if(data == "OK"){
                    listaParamtros();
                    toastr["success"]("Parametro Modificado", "Correcto!");
                    $("#modal").modal("hide");
                }
                else{
                    toastr["error"]("Error al modificar el parametro", "Error!");
                    console.log(data);
                }
            });

        }

        function formFirmaDigital(){
            $("#modal-content").html(loader);
            $("#modal").modal("show");
            fetch("modulos/parametros/fn_parametros.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formFirmaDigital"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#modal-content").html(data);

                $("#btnCargarFirmaDigital").click(function () { 
                    var formData = new FormData();
                    formData.append("funcion", "cargarFirmaDigital");
                    formData.append("archivoFirmaDigital", $("#archivoFirmaDigital")[0].files[0]);
                    formData.append("clave", $("#clave").val());

                    cargarFirmaDigital(formData);
                });

            });
        }

        function cargarFirmaDigital(formData){
            $("#btnCargarFirmaDigital").attr("disabled", true);
            fetch("modulos/parametros/fn_parametros.php", {
                method: "POST",
                headers: {
                    "Content-Type": "multipart/form-data"
                },
                body: formData
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                $("#btnCargarFirmaDigital").attr("disabled", false);

                if(data == "OK"){
                    listaParamtros();
                    toastr["success"]("Firma Digital Cargada", "Correcto!");
                    $("#modal").modal("hide");
                }
                else{
                    toastr["error"]("Error al cargar la firma digital", "Error!");
                    console.log(data);
                }
            });
        }


    });
</script>