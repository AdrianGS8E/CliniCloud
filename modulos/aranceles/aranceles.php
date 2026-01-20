<?php
session_start();

require_once "../../config_db_mysql.php";

?>

<h1 class="subheader-title"> Aranceles
    <small> Gestion de los aranceles del sistema </small>
</h1>

<div id='contenido'></div>

<script>
    $(document).ready(function(){

        listaAranceles();
        function listaAranceles() {
            console.log("listando aranceles");
            $("#contenido").html(loader);
            fetch("modulos/aranceles/fn_aranceles.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "listaAranceles"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnFormNuevoArancel").click(function () { 
                    formNuevoArancel();
                });

                $(".btnFormEditarArancel").click(function () { 
                    let idArancel = $(this).attr("id");
                    formEditarArancel(idArancel);
                });

            });
        }

        function formNuevoArancel() {
            console.log("formNuevoArancel");
            $("#contenido").html(loader);
            fetch("modulos/aranceles/fn_aranceles.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formNuevoArancel"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnRegistrarArancel").click(function () { 

                    let datosArancel = {
                        codigo: $("#codigo").val().toUpperCase(),
                        descripcion: $("#descripcion").val().toUpperCase(),
                        precio: $("#precio").val(),
                    }
                    
                    registrarArancel(datosArancel);
                });
            });
        }


        function registrarArancel(datosArancel){
            $("#contenido").html(loader);
            fetch("modulos/aranceles/fn_aranceles.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "registrarArancel",
                    datosArancel: datosArancel
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                console.log(data);
                if (data == 'OK') {
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Arancel registrado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    listaAranceles();
                } 
                else {
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al registrar el arancel!',
                        footer: 'Intente nuevamente'
                    })
                }
            });
        }

        function formEditarArancel(idArancel){
            console.log("formEditarArancel");
            $("#contenido").html(loader);
            fetch("modulos/aranceles/fn_aranceles.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formEditarArancel",
                    idArancel: idArancel
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#contenido").html(data);

                $("#btnEditarArancel").click(function () { 
                    let datosArancel = {
                        idArancel: idArancel,
                        codigo: $("#codigo").val().toUpperCase(),
                        descripcion: $("#descripcion").val().toUpperCase(),
                        precio: $("#precio").val(),
                    }
                    editarArancel(datosArancel);
                });
            });
        }

        function editarArancel(datosArancel){
            $("#contenido").html(loader);
            fetch("modulos/aranceles/fn_aranceles.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "editarArancel",
                    datosArancel: datosArancel
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                console.log(data);
                if (data == 'OK') {
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Arancel actualizado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    listaAranceles();
                }
                else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al actualizar el arancel!',
                        footer: 'Intente nuevamente'
                    })
                }
            });
        }
    });
</script>