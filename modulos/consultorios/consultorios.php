<?php
session_start();

require_once "../../config_db_mysql.php";

?>

<h1 class="subheader-title"> Consultorios
    <small> Gestion de los consultorios del sistema </small>
</h1>

<div id='contenido'></div>

<script>
    $(document).ready(function(){

        listaConsultorios();
        function listaConsultorios() {
            console.log("listando consultorios");
            $("#contenido").html(loader);
            fetch("modulos/consultorios/fn_consultorios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "listaConsultorios"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnFormNuevoConsultorio").click(function () { 
                    formNuevoConsultorio();
                });

                $(".btnFormEditarConsultorio").click(function () { 
                    let idConsultorio = $(this).attr("id");
                    formEditarConsultorio(idConsultorio);
                });

                $(".btnModalListaUsuarios").click(function () { 
                    let idConsultorio = $(this).attr("id");
                    modalListaUsuarios(idConsultorio);
                });

            });
        }

        function formNuevoConsultorio() {
            console.log("formNuevoConsultorio");
            $("#contenido").html(loader);
            fetch("modulos/consultorios/fn_consultorios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formNuevoConsultorio"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnRegistrarConsultorio").click(function () { 

                    let datosConsultorio = {
                        codigo: $("#codigo").val().toUpperCase(),
                        descripcion: $("#descripcion").val().toUpperCase(),
                        especialidad: $("#especialidad").val(),
                    }
                    
                    registrarConsultorio(datosConsultorio);
                });
            });
        }


        function registrarConsultorio(datosConsultorio){
            $("#contenido").html(loader);
            fetch("modulos/consultorios/fn_consultorios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "registrarConsultorio",
                    datosConsultorio: datosConsultorio
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
                        title: 'Consultorio registrado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    listaConsultorios();
                } 
                else {
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al registrar el consultorio!',
                        footer: 'Intente nuevamente'
                    })
                }
            });
        }

        function formEditarConsultorio(idConsultorio){
            console.log("formEditarConsultorio");
            $("#contenido").html(loader);
            fetch("modulos/consultorios/fn_consultorios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formEditarConsultorio",
                    idConsultorio: idConsultorio
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#contenido").html(data);

                $("#btnEditarConsultorio").click(function () { 
                    let datosConsultorio = {
                        idConsultorio: idConsultorio,
                        codigo: $("#codigo").val().toUpperCase(),
                        descripcion: $("#descripcion").val().toUpperCase(),
                        especialidad: $("#especialidad").val(),
                    }
                    editarConsultorio(datosConsultorio);
                });
            });
        }

        function editarConsultorio(datosConsultorio){
            $("#contenido").html(loader);
            fetch("modulos/consultorios/fn_consultorios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "editarConsultorio",
                    datosConsultorio: datosConsultorio
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
                        title: 'Consultorio actualizado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    listaConsultorios();
                }
                else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al actualizar el consultorio!',
                        footer: 'Intente nuevamente'
                    })
                }
            });
        }

        function modalListaUsuarios(idConsultorio){
            console.log("modalListaMedicos");
            $("#modal-lg-content").html(loader);
            fetch("modulos/consultorios/fn_consultorios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "modalListaUsuarios",
                    idConsultorio: idConsultorio
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                //console.log(data);
                if (!verificarSesion(data)) return;
                $("#modal-lg").modal("show");
                $("#modal-lg-content").html(data);
                
                // Guardar idConsultorio en los botones para usarlo en las funciones
                $(".btnEliminarMedico").attr("data-consultorio", idConsultorio);
                $("#btnAsignarMedico").attr("data-consultorio", idConsultorio);


                $(".btnEliminarMedico").click(function () { 
                    let idUsuario = $(this).attr("id");
                    let idConsultorio = $(this).data("consultorio");
                    eliminarMedico(idUsuario, idConsultorio);
                });

                $("#btnAsignarMedico").click(function () { 
                    console.log("btnAsignarMedico");
                    let idUsuario = $("#idUsuario").val();
                    let idConsultorio = $(this).data("consultorio");
                    asignarMedico(idUsuario, idConsultorio);
                });
            });
        }

        function eliminarMedico(idUsuario, idConsultorio) {
            Swal.fire({
                title: '¿Está seguro?',
                text: "Esta acción eliminará al médico del consultorio",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch("modulos/consultorios/fn_consultorios.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ 
                            funcion: "eliminarMedico",
                            idUsuario: idUsuario,
                            idConsultorio: idConsultorio
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
                                title: 'Médico eliminado con éxito!',
                                showConfirmButton: false,
                                timer: 1500
                            })
                            modalListaUsuarios(idConsultorio);
                        } 
                        else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Hubo un error al eliminar el médico!',
                                footer: 'Intente nuevamente'
                            })
                        }
                    });
                }
            });
        }

        function asignarMedico(idUsuario, idConsultorio) {
            if (!idUsuario || idUsuario === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor seleccione un usuario'
                });
                return;
            }

            fetch("modulos/consultorios/fn_consultorios.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "asignarMedico",
                    idUsuario: idUsuario,
                    idConsultorio: idConsultorio
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
                        title: 'Médico asignado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    modalListaUsuarios(idConsultorio);
                } 
                else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data || 'Hubo un error al asignar el médico!',
                        footer: 'Intente nuevamente'
                    })
                }
            });
        }
    });
</script>