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
<h1 class="subheader-title"> Usuarios
    <small> Gestion de los usuarios del sistema </small>
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

        listaUsuarios();
        function listaUsuarios(listaSocios) {
            console.log("listando usuarios");
            $("#contenido").html(loader);
            fetch("modulos/usuarios/fn_usuarios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "listaUsuarios"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnFormNuevoUsuario").click(function () { 
                    formNuevoUsuario();
                });

                $(".btnResetPassword").click(function () { 
                    let idUsuario = $(this).attr("id");
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "¡No podrás revertir esto!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Si, resetear!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            resetPassword(idUsuario);
                        }
                    });

                });

                $(".btnFormEditarUsuario").click(function () { 
                    let idUsuario = $(this).attr("id");
                    formEditarUsuario(idUsuario);
                });

            });
        }

        function resetPassword(idUsuario){
            fetch("modulos/usuarios/fn_usuarios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "resetPassword",
                    idUsuario: idUsuario
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                if (data == 'OK') {
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Contraseña reseteada con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                } 
                else {
                    console.log(data);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al resetear la contraseña!',
                        footer: 'Intente nuevamente'
                    })
                }
            });

        }

        function formNuevoUsuario() {
            console.log("formNuevoUsuario");
            $("#contenido").html(loader);
            fetch("modulos/usuarios/fn_usuarios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formNuevoUsuario"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnRegistrarUsuario").click(function () { 

                    let datosUsuario = {
                        nombreUs: $("#nombreUs").val().toUpperCase(),
                        primerApUs: $("#primerApUs").val().toUpperCase(),
                        segundoApUs: $("#segundoApUs").val().toUpperCase(),
                        fechaNacUs: $("#fechaNacUs").val(),
                        celularUs: $("#celularUs").val(),
                        ciUs: $("#ciUs").val(),
                        emailUs: $("#emailUs").val(),
                        usuarioUs: $("#usuarioUs").val().toUpperCase(),
                        perfilUs: $("#perfilUs").val(),
                        estadoUs: $("#estadoUs").val(),
                    }
                    
                    registrarUsuario(datosUsuario);
                });
            });
        }


        function registrarUsuario(datosUsuario){
            $("#contenido").html(loader);
            fetch("modulos/usuarios/fn_usuarios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "registrarUsuario",
                    datosUsuario: datosUsuario
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
                        title: 'Usuario registrado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    listaUsuarios();
                } 
                else {
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al registrar el usuario!',
                        footer: 'Intente nuevamente'
                    })
                }
            });
        }

        function formEditarUsuario(idUsuario){
            console.log("formEditarUsuario");
            $("#contenido").html(loader);
            fetch("modulos/usuarios/fn_usuarios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formEditarUsuario",
                    idUsuario: idUsuario
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#contenido").html(data);

                $("#btnEditarUsuario").click(function () { 
                    let datosUsuario = {
                        idUsuario: idUsuario,
                        nombreUs: $("#nombreUs").val().toUpperCase(),
                        primerApUs: $("#primerApUs").val().toUpperCase(),
                        segundoApUs: $("#segundoApUs").val().toUpperCase(),
                        fechaNacUs: $("#fechaNacUs").val(),
                        celularUs: $("#celularUs").val(),
                        ciUs: $("#ciUs").val(),
                        emailUs: $("#emailUs").val(),
                        usuarioUs: $("#usuarioUs").val().toUpperCase(),
                        perfilUs: $("#perfilUs").val(),
                        estadoUs: $("#estadoUs").val(),
                    }
                    editarUsuario(datosUsuario);
                });
            });
        }

        function editarUsuario(datosUsuario){
            $("#contenido").html(loader);
            fetch("modulos/usuarios/fn_usuarios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "editarUsuario",
                    datosUsuario: datosUsuario
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
                        title: 'Usuario actualizado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                }
                else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al actualizar el usuario!',
                        footer: 'Intente nuevamente'
                    })
                }
            });
        }
    });
</script>