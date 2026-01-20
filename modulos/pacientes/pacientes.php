<?php
session_start();

require_once "../../config_db_mysql.php";

?>

<h1 class="subheader-title"> Pacientes
    <small> Gestion de los pacientes del sistema </small>
</h1>

<div id='contenido'></div>

<script>
    $(document).ready(function(){

        listaPacientes();
        function listaPacientes() {
            console.log("listando pacientes");
            $("#contenido").html(loader);
            fetch("modulos/pacientes/fn_pacientes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "listaPacientes"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnFormNuevoPaciente").click(function () { 
                    formNuevoPaciente();
                });

                $(".btnFormEditarPaciente").click(function () { 
                    let idPaciente = $(this).attr("id");
                    formEditarPaciente(idPaciente);
                });

            });
        }

        function formNuevoPaciente() {
            console.log("formNuevoPaciente");
            $("#contenido").html(loader);
            fetch("modulos/pacientes/fn_pacientes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formNuevoPaciente"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnRegistrarPaciente").click(function () { 

                    let datosPaciente = {
                        ci: $("#ci").val().toUpperCase(),
                        apellidoPat: $("#apellidoPat").val().toUpperCase(),
                        apellidoMat: $("#apellidoMat").val().toUpperCase(),
                        nombres: $("#nombres").val().toUpperCase(),
                        fechaNacimiento: $("#fechaNacimiento").val(),
                        celular: $("#celular").val(),
                        email: $("#email").val(),
                        direccion: $("#direccion").val().toUpperCase(),
                        procedencia: $("#procedencia").val().toUpperCase(),
                        residencia: $("#residencia").val().toUpperCase(),
                        nombreTutor: $("#nombreTutor").val().toUpperCase(),
                        celularTutor: $("#celularTutor").val(),
                    }
                    
                    registrarPaciente(datosPaciente);
                });
            });
        }


        function registrarPaciente(datosPaciente){
            $("#contenido").html(loader);
            fetch("modulos/pacientes/fn_pacientes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "registrarPaciente",
                    datosPaciente: datosPaciente
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
                        title: 'Paciente registrado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    listaPacientes();
                } 
                else {
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al registrar el paciente!',
                        footer: 'Intente nuevamente'
                    })
                }
            });
        }

        function formEditarPaciente(idPaciente){
            console.log("formEditarPaciente");
            $("#contenido").html(loader);
            fetch("modulos/pacientes/fn_pacientes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formEditarPaciente",
                    idPaciente: idPaciente
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#contenido").html(data);

                $("#btnEditarPaciente").click(function () { 
                    let datosPaciente = {
                        idPaciente: idPaciente,
                        ci: $("#ci").val().toUpperCase(),
                        apellidoPat: $("#apellidoPat").val().toUpperCase(),
                        apellidoMat: $("#apellidoMat").val().toUpperCase(),
                        nombres: $("#nombres").val().toUpperCase(),
                        fechaNacimiento: $("#fechaNacimiento").val(),
                        celular: $("#celular").val(),
                        email: $("#email").val(),
                        direccion: $("#direccion").val().toUpperCase(),
                        procedencia: $("#procedencia").val().toUpperCase(),
                        residencia: $("#residencia").val().toUpperCase(),
                        nombreTutor: $("#nombreTutor").val().toUpperCase(),
                        celularTutor: $("#celularTutor").val(),
                    }
                    editarPaciente(datosPaciente);
                });
            });
        }

        function editarPaciente(datosPaciente){
            $("#contenido").html(loader);
            fetch("modulos/pacientes/fn_pacientes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "editarPaciente",
                    datosPaciente: datosPaciente
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
                        title: 'Paciente actualizado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    listaPacientes();
                }
                else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al actualizar el paciente!',
                        footer: 'Intente nuevamente'
                    })
                }
            });
        }
    });
</script>