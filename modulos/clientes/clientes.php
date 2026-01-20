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
<h1 class="subheader-title"> Clientes
    <small> Modulo de clientes del sistema </small>
</h1>

<div id='contenido'></div>



<script>
    $(document).ready(function(){

        listaClientes();

        function listaClientes(){
            $("#contenido").html(loader);
            fetch("modulos/clientes/fn_clientes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "listaClientes"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                // Configurar DataTable con procesamiento del lado del servidor
                $("#tableClientes").DataTable({
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        "url": "modulos/clientes/datatable_clientes.php",
                        "type": "POST"
                    },
                    "columns": [
                        { "data": 0, "name": "nombreRazonSocial" },
                        { "data": 1, "name": "codigoTipoDocumentoIdentidad" },
                        { "data": 2, "name": "numeroDocumento" },
                        { "data": 3, "name": "complemento" },
                        { "data": 4, "name": "codigoExcepcion" },
                        { "data": 5, "name": "correoElectronico" },
                        { "data": 6, "name": "celular" },
                        { "data": 7, "name": "acciones", "orderable": false, "searchable": false }
                    ],
                    "pageLength": 10,
                    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                    "order": [[0, 'asc']]
                });

                // Delegación de eventos para botones dinámicos
                $(document).on('click', '#btnFormNuevoCliente', function () { 
                    formNuevoCliente();
                });                
                $(document).on('click', '.btnEditarCliente', function () { 
                    let idCliente = $(this).attr('id');
                    formEditarCliente(idCliente);
                });
            });
        }

        function formNuevoCliente(){
            $("#contenido").html(loader);
            fetch("modulos/clientes/fn_clientes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formNuevoCliente"
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnRegistrarNuevoCliente").click(function () { 
                    let nombreRazonSocial = $('#nombreRazonSocial').val();
                    if(nombreRazonSocial.trim() == ''){
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Debe ingresar el nombre o razón social'
                        });
                        return;
                    }

                    let codigoTipoDocumentoIdentidad = $('#codigoTipoDocumentoIdentidad').val();
                    if(codigoTipoDocumentoIdentidad == ''){
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Debe seleccionar el tipo de documento'
                        });
                        return;
                    }

                    let numeroDocumento = $('#numeroDocumento').val();
                    if(numeroDocumento.trim() == ''){
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Debe ingresar el número de documento'
                        });
                        return;
                    }

                    if((codigoTipoDocumentoIdentidad == '5' || codigoTipoDocumentoIdentidad == '1') && !/^\d+$/.test(numeroDocumento)){
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'El número de documento debe contener solo números'
                        });
                        return;
                    }

                    let datosCliente = {
                        nombreRazonSocial: nombreRazonSocial,
                        codigoTipoDocumentoIdentidad: codigoTipoDocumentoIdentidad,
                        numeroDocumento: numeroDocumento,
                        complemento: $('#complemento').val(),
                        codigoExcepcion: $('#codigoExcepcion').val(),
                        correoElectronico: $('#correoElectronico').val(),
                        celular: $('#celular').val()
                    };

                    console.log(datosCliente);
                    $("#btnRegistrarNuevoCliente").prop("disabled", true);
                    if(codigoTipoDocumentoIdentidad == '5' && $('#codigoExcepcion').val() == 0){
                        validarNIT(datosCliente);
                    }
                    else{
                        registrarNuevoCliente(datosCliente);
                    }

                });
            });
        }

        function registrarNuevoCliente(datosCliente){
            
            $("#contenido").html(loader);
            fetch("modulos/clientes/fn_clientes.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "registrarNuevoCliente",
                    datosCliente: datosCliente
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#btnRegistrarNuevoCliente").prop("disabled", false);

                data = JSON.parse(data);

                if(data.estado == 'OK'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Cliente registrado correctamente'
                    });
                    listaClientes();
                }
                else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.mensaje
                    });
                }
            });
        }

        function validarNIT(datosCliente) {
            //$("#contenido").html(loader);

            fetch("api/codigos/verificarNIT.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ nitParaVerificacion: datosCliente.numeroDocumento })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta API NIT:', data);
                if (data.estado === 'ok' && data.transaccion === true && data.descripcion === 'NIT ACTIVO') {
                    // NIT válido y activo
                    datosCliente.codigoExcepcion = 0;
                    // Swal.fire({
                    //     icon: 'success',
                    //     title: 'NIT válido',
                    //     text: 'El NIT está activo'
                    // });
                    toastr["success"]("El NIT está activo", "Correcto!");
                } else {
                    // NIT inválido o no activo
                    datosCliente.codigoExcepcion = 1;
                    // Swal.fire({
                    //     icon: 'warning',
                    //     title: 'NIT inválido',
                    //     text: `El NIT no está activo (${data.descripcion}). Se registrará con excepción.`
                    // });
                    toastr["warning"]("El NIT no está activo. Se registrará con excepción 1.", "Correcto!");
                }
                registrarNuevoCliente(datosCliente);
            })
            .catch(err => {
                console.error('Error al validar NIT:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo verificar el NIT. Intente de nuevo más tarde.'
                });
            })
            .finally(() => {
                // Opcional: ocultar loader o restaurar contenido
            });
        }

        function formEditarCliente(idCliente){
            $("#contenido").html(loader);
            fetch("modulos/clientes/fn_clientes.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ 
                    funcion: "formEditarCliente", 
                    idCliente: idCliente 
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnActualizarCliente").click(function () { 

                    let nombreRazonSocial = $('#nombreRazonSocial').val();
                    if(nombreRazonSocial.trim() == ''){
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Debe ingresar el nombre o razón social'
                        });
                        return;
                    }

                    let codigoTipoDocumentoIdentidad = $('#codigoTipoDocumentoIdentidad').val();
                    if(codigoTipoDocumentoIdentidad == ''){
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Debe seleccionar el tipo de documento'
                        });
                        return;
                    }

                    let numeroDocumento = $('#numeroDocumento').val();
                    if(numeroDocumento.trim() == ''){
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Debe ingresar el número de documento'
                        });
                        return;
                    }

                    /*
                    nombreRazonSocial
codigoTipoDocumentoIdentidad
numeroDocumento
complemento
codigoExcepcion
correoElectronico
celular
                    */

                    let datosCliente = {
                        idCliente: idCliente,
                        nombreRazonSocial: nombreRazonSocial,
                        codigoTipoDocumentoIdentidad: codigoTipoDocumentoIdentidad,
                        numeroDocumento: numeroDocumento,
                        complemento: $('#complemento').val(),
                        codigoExcepcion: $('#codigoExcepcion').val(),
                        correoElectronico: $('#correoElectronico').val(),
                        celular: $('#celular').val()
                    };

                    console.log(datosCliente);
                    
                    editarCliente(datosCliente);
                });
            });
        }

        function editarCliente(datosCliente){
            $("#contenido").html(loader);
            fetch("modulos/clientes/fn_clientes.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ funcion: "editarCliente", datosCliente: datosCliente })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                data = JSON.parse(data);

                if(data.estado == 'OK'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Cliente actualizado correctamente'
                    });
                }
                else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.mensaje
                    });
                }
                listaClientes();
            });
        }

    });
</script>