<?php
session_start();

require_once "../../config_db_mysql.php";

?>

<h1 class="subheader-title"> Atencion Medica - Odontologia
    <small> Gestion de la atencion medica - Odontologia </small>
</h1>

<div id='contenido'></div>

<script>
    $(document).ready(function(){

        listaConsultorios();
        function listaConsultorios() {
            console.log("listando consultorios");
            $("#contenido").html(loader);
            fetch("modulos/atencion_medica/fn_atencion_clinica.php", {
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

                $(".btnVerPacientesConsultorio").click(function () { 
                    let idConsultorio = $(this).attr("id");
                    verPacientesConsultorio(idConsultorio);
                });

            });
        }

        function verPacientesConsultorio(idConsultorio, fechaConsulta = null){
            $("#contenido").html(loader);
            let bodyData = { 
                funcion: "verPacientesConsultorio",
                idConsultorio: idConsultorio
            };
            if (fechaConsulta) {
                bodyData.fechaConsulta = fechaConsulta;
            }
            fetch("modulos/atencion_medica/fn_atencion_clinica.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(bodyData)
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnSeleccionarPaciente").click(function () { 
                    modalSeleccionarPaciente(idConsultorio);
                });

                // $(".btnModalImprimirAtencion").click(function () { 
                //     let idAtencion = $(this).attr("id");
                //     modalImprimirAtencion(idAtencion, idConsultorio, idConsultorio, fechaConsulta);
                // });

                // $(".btnEditarAtencion").click(function () { 
                //     let idAtencion = $(this).attr("id");
                //     editarAtencion(idAtencion);
                // });

                $(".btnVerAtencionClinica").click(function () { 
                    let idAtencion = $(this).attr("id");
                    verAtencionClinica(idAtencion);
                });

                $("#fechaConsulta").change
                (function () {
                   
                    let fechaConsulta = $(this).val(

                    );
                    verPacientesConsultorio(idConsultorio, fechaConsulta);
                });

            });
        }

        function modalSeleccionarPaciente(idConsultorio){
            $("#modal-xl").modal("show");
            $("#modal-xl-content").html(loader);
            fetch("modulos/atencion_medica/fn_atencion_clinica.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "modalSeleccionarPaciente",
                    idConsultorio: idConsultorio
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#modal-xl-content").html(data);


                /*$(".btnFormExamenGeneral").click(function () { 
                    let idPaciente = $(this).attr("id");
                    formularioExamenGeneral(idConsultorio, idPaciente);
                    $("#modal-xl").modal("hide");
                });*/

                $(".btnFormCrearAtencionClinica").click(function () { 
                    let idPaciente = $(this).attr("id");
                    crearAtencionClinica(idConsultorio, idPaciente);
                });


            });
        }

        function crearAtencionClinica(idConsultorio, idPaciente){
            $("#contenido").html(loader);
            fetch("modulos/atencion_medica/fn_atencion_clinica.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "crearAtencionClinica",
                    idConsultorio: idConsultorio,
                    idPaciente: idPaciente
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                data = JSON.parse(data);
                if(data.estado == "OK"){
                    verPacientesConsultorio( idConsultorio );
                    Swal.fire({
                        icon: 'success',
                        title: 'Atención clínica creada correctamente'
                    });
                }
                else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.mensaje
                    });
                }
                $("#modal-xl").modal("hide");
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al crear la atención clínica");
            });
        }

        function verAtencionClinica(idAtencion){
            $("#contenido").html(loader);
            fetch("modulos/atencion_medica/fn_atencion_clinica.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "verAtencionClinica",
                    idAtencion: idAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#contenido").html(data);

                listaRegistroCuaadernoOdontologia(idAtencion);


                $("#btnFormExamenGeneral").click(function () { 
                    $("#modal-xl").modal("show");
                    $("#modal-xl-content").html(loader);
                    $("#modal-xl-content").load("modulos/atencion_medica/examen_general/examen_general.php", { idAtencion: idAtencion });
                });

                $("#btnFormRegistroTratamientos").click(function () { 
                    $("#modal-xl").modal("show");
                    $("#modal-xl-content").html(loader);
                    $("#modal-xl-content").load("modulos/atencion_medica/registro_clinico/registro_clinico.php", { idAtencion: idAtencion });
                });

                $("#btnFormRayoxX").click(function () { 
                    $("#modal-xl").modal("show");
                    $("#modal-xl-content").html(loader);
                    $("#modal-xl-content").load("modulos/atencion_medica/rayos_x/rayos_x.php", { idAtencion: idAtencion });
                });

                $("#btnFormSolicitudProtesico").click(function () { 
                    $("#modal-xl").modal("show");
                    $("#modal-xl-content").html(loader);
                    $("#modal-xl-content").load("modulos/atencion_medica/protesico/solicitud_protesico.php", { idAtencion: idAtencion });
                });

                $("#btnHistorialOdontologico").click(function () { 
                    $("#modal-xl").modal("show");
                    $("#modal-xl-content").html(loader);
                    $("#modal-xl-content").load("modulos/atencion_medica/historial/historial.php", { idAtencion: idAtencion });
                });

                $("#btnRegistroPrestaciones").click(function () { 
                    $("#modal").modal("show");
                    $("#modal-content").html(loader);
                    $("#modal-content").load("modulos/atencion_medica/registro_economico/registro_prestaciones.php", { idAtencion: idAtencion });
                });

                $("#btnRegistroPago").click(function () { 
                    $("#modal-xl").modal("show");
                    $("#modal-xl-content").html(loader);
                    $("#modal-xl-content").load("modulos/atencion_medica/registro_economico/registro_pago.php", { idAtencion: idAtencion });
                });

            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar la atención clínica");
            });
        }

        function listaRegistroCuaadernoOdontologia(idAtencion){
            $("#divCuadernoOdontologia").html(loader);
            fetch("modulos/atencion_medica/registro_clinico/fn_registro_clinico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "listaRegistroCuaadernoOdontologia",
                    idAtencion: idAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#divCuadernoOdontologia").html(data);


                $(".btnImprimirRegistroCuaderno").click(function () { 
                    let idCuaOdontologia = $(this).attr("id");
                    imprimirRegistroCuaderno(idAtencion, idCuaOdontologia);
                });


            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el registro de cuaderno odontologia");
            });
        }

        function formularioExamenGeneral(idAtencion){
            $("#divCuadernoOdontologia").html(loader);
            fetch("modulos/atencion_medica/examen_general/fn_examen_general.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formularioExamenGeneral",
                    idAtencion: idAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#divCuadernoOdontologia").html(data);

                $("#btnGuardarExamenGeneral").click(function () { 
                    // Extraer datos del examen general
                    let datosExamenGeneral = {
                        // Examen General
                        intervenido_quirurgicamente: $("input[name='intervenido_quirurgicamente']:checked").val() || "",
                        problemas_cardiacos: $("input[name='problemas_cardiacos']:checked").val() || "",
                        diabetico: $("input[name='diabetico']:checked").val() || "",
                        alergia_medicamentos: $("input[name='alergia_medicamentos']:checked").val() || "",
                        cicatrizacion_normal: $("input[name='cicatrizacion_normal']:checked").val() || "",
                        problemas_coagulacion: $("input[name='problemas_coagulacion']:checked").val() || "",
                        tratamiento_medico: $("input[name='tratamiento_medico']:checked").val() || "",
                        toma_medicamentos: $("input[name='toma_medicamentos']:checked").val() || "",
                        embarazo: $("input[name='embarazo']:checked").val() || "",
                        fum: $("input[name='fum']").val() || "",
                        // Examen Bucodental
                        higiene_dental: $("select[name='higiene_dental']").val() || "",
                        usa_cepillo: $("input[name='usa_cepillo']:checked").val() || "",
                        frecuencia_cepillado: $("input[name='frecuencia_cepillado']").val() || "",
                        usa_hilo_dental: $("input[name='usa_hilo_dental']:checked").val() || "",
                        // Hábitos y costumbres
                        respirador_bucal: $("input[name='respirador_bucal']:checked").val() || "",
                        usa_chupon: $("input[name='usa_chupon']:checked").val() || "",
                        fuma: $("input[name='fuma']:checked").val() || "",
                        toma_alcohol: $("input[name='toma_alcohol']:checked").val() || "",
                        masca_coca: $("input[name='masca_coca']:checked").val() || ""
                    };
                    
                    let jsonDatosExamenGeneral = JSON.stringify(datosExamenGeneral);
                    console.log("Datos del examen general:", jsonDatosExamenGeneral);
                    guardarExamenGeneral(idAtencion, jsonDatosExamenGeneral);
                });
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el formulario de examen general");
            });

            
        }

        function guardarExamenGeneral(idAtencion, jsonDatosExamenGeneral){
            $("#divCuadernoOdontologia").html(loader);
            fetch("modulos/atencion_medica/examen_general/fn_examen_general.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "guardarExamenGeneral",
                    idAtencion: idAtencion,
                    jsonDatosExamenGeneral: jsonDatosExamenGeneral
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                data = JSON.parse(data);

                

                if(data.estado == "OK"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Examen general guardado correctamente'
                    });
                }
                else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.mensaje
                    });
                }

                listaRegistroCuaadernoOdontologia(idAtencion);
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al guardar el examen general");
            });
        }


        function modalDetalleCuentaPaciente(idPaciente){
            $("#modal-xl-content").html(loader);
            $("#modal-xl").modal("show");
            fetch("modulos/atencion_medica/fn_atencion_clinica.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "detalleCuentaPaciente",
                    idPaciente: idPaciente
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#modal-xl-content").html(data);

                $(".btnPagarOrden").click(function () { 
                    let idOrdenAtencion = $(this).attr("id");
                    //pagarOrden(idOrdenAtencion);
                    Swal.fire({
                        icon: 'warning',
                        title: '¿Estás seguro de querer pagar esta orden?',
                        text: 'Esta acción no se puede deshacer',
                        showCancelButton: true,
                        confirmButtonText: 'Si, pagar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            pagarOrden(idOrdenAtencion, idPaciente);
                        }
                    });
                });

                $(".btnVerRecibo").click(function () { 
                    let idRecibo = $(this).attr("id");
                    verRecibo(idRecibo);
                });


            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el detalle de la cuenta");
            });
        }

        function pagarOrden(idOrdenAtencion, idPaciente){
            $("#modal-xl-content").html(loader);
            fetch("modulos/atencion_medica/fn_atencion_clinica.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "pagarOrden",
                    idOrdenAtencion: idOrdenAtencion,
                    idPaciente: idPaciente
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                data = JSON.parse(data);
                if(data.estado == "OK"){
                    let idRecibo = data.idRecibo;
                    verRecibo(idRecibo);
                }
                else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.mensaje
                    });
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al pagar la orden");
            });
        }

        function verRecibo(idRecibo){
            $("#modal-xl-content").html(loader);
            fetch("modulos/atencion_medica/fn_atencion_clinica.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "verRecibo",
                    idRecibo: idRecibo
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#modal-xl-content").html(data);
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el recibo");
            });
        }

        function imprimirRegistroCuaderno(idAtencion, idCuaOdontologia){
            $("#modal-xl-content").html(loader);
            $("#modal-xl").modal("show");
            fetch("modulos/atencion_medica/registro_clinico/fn_registro_clinico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "imprimirRegistroCuaderno",
                    idAtencion: idAtencion,
                    idCuaOdontologia: idCuaOdontologia
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#modal-xl-content").html(data);
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al imprimir el registro de cuaderno odontologia");
            });
        }
        

        function formularioRegistroTratamientos(idAtencion){
            $("#divCuadernoOdontologia").html(loader);
            fetch("modulos/atencion_medica/registro_clinico/fn_registro_clinico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formularioRegistroTratamientos",
                    idAtencion: idAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#divCuadernoOdontologia").html(data);
                try {
                    // Calcular total al cambiar el select de costo tratamiento
                    $(document).on("change", ".campo-tratamiento", function () {
                        calcularTotalRegistro($(this).closest(".registro-tratamiento"));
                    });

                    // Estado de pago: alternar entre Pendiente y Pagado al hacer clic en los botones
                    $(document).on("click", ".btn-estado-pago", function () {
                        let $btn = $(this);
                        let valor = $btn.data("value");
                        let $grupo = $btn.closest(".btn-group");
                        let $input = $grupo.closest(".col-md-3").find(".campo-estado-pago");
                        $grupo.find(".btn-estado-pago").removeClass("active");
                        $btn.addClass("active");
                        $input.val(valor);
                    });

                    // Evento para guardar
                    $("#btnGuardarRegistroTratamiento").click(function () {
                        guardarRegistroTratamientos(idAtencion);
                    });
                } catch (e) {
                    console.error("Error al configurar eventos del formulario:", e);
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                // Solo mostrar alert si el formulario no llegó a cargarse
                if (!$("#formularioRegistroUnico").length && !$("#divCuadernoOdontologia .registro-tratamiento").length) {
                    alert("Error al cargar el formulario de registro de tratamientos");
                }
            });
        }

        function calcularTotalRegistro($registro){
            let $selectTratamiento = $registro.find(".campo-tratamiento");
            let precio = 0;
            
            if($selectTratamiento.val() != ''){
                let $optionSeleccionada = $selectTratamiento.find("option:selected");
                precio = parseFloat($optionSeleccionada.data("precio")) || 0;
            }
            
            $registro.find(".campo-total").val(precio.toFixed(2));
        }

        function guardarRegistroTratamientos(idAtencion){
            let $registro = $("#formularioRegistroUnico");
            if($registro.length == 0){
                $registro = $(".registro-tratamiento").first();
            }

            let fecha = $registro.find(".campo-fecha").val();
            let motivoConsulta = $registro.find(".campo-motivo").val();
            let pieza = $registro.find(".campo-pieza").val();
            let diagnostico = $registro.find(".campo-diagnostico").val();
            let tratamientoRealizado = $registro.find(".campo-tratamiento-realizado").val();
            let tratamiento = $registro.find(".campo-tratamiento").val();
            let estadoPago = $registro.find(".campo-estado-pago").val();

            if(!fecha || fecha.trim() == ''){
                Swal.fire({ icon: 'error', title: 'Error de validación', text: 'El campo Fecha es requerido' });
                return;
            }
            if(!motivoConsulta || motivoConsulta.trim() == ''){
                Swal.fire({ icon: 'error', title: 'Error de validación', text: 'El campo Motivo de consulta es requerido' });
                return;
            }
            if(!pieza || pieza.trim() == ''){
                Swal.fire({ icon: 'error', title: 'Error de validación', text: 'El campo Pieza dental tratada es requerido' });
                return;
            }
            if(!diagnostico || diagnostico.trim() == ''){
                Swal.fire({ icon: 'error', title: 'Error de validación', text: 'El campo Diagnóstico es requerido' });
                return;
            }
            if(!tratamientoRealizado || tratamientoRealizado.trim() == ''){
                Swal.fire({ icon: 'error', title: 'Error de validación', text: 'El campo Tratamiento realizado es requerido' });
                return;
            }
            if(!tratamiento || tratamiento == ''){
                Swal.fire({ icon: 'error', title: 'Error de validación', text: 'Debe seleccionar un Costo tratamiento' });
                return;
            }
            if(!estadoPago || estadoPago == ''){
                Swal.fire({ icon: 'error', title: 'Error de validación', text: 'Debe seleccionar Estado de pago (Pendiente o Pagado)' });
                return;
            }

            let $selectTratamiento = $registro.find(".campo-tratamiento");
            let $optionSeleccionada = $selectTratamiento.find("option:selected");
            let total = parseFloat($registro.find(".campo-total").val()) || 0;

            let registro = {
                fecha: fecha,
                motivoConsulta: motivoConsulta.trim(),
                pieza: pieza.trim(),
                diagnostico: diagnostico.trim(),
                tratamientoRealizado: tratamientoRealizado.trim(),
                idArancel: $selectTratamiento.val(),
                codigoArancel: $optionSeleccionada.data("codigo") || "",
                descripcionArancel: $optionSeleccionada.text().split(" - ")[1]?.split(" (Bs.")[0] || "",
                precio: parseFloat($optionSeleccionada.data("precio")) || 0,
                total: total,
                estadoPago: estadoPago
            };

            let datosRegistroTratamientos = {
                registro: registro,
            };

            let jsonDatosRegistroTratamientos = JSON.stringify(datosRegistroTratamientos);
            console.log("Datos del registro de tratamientos:", jsonDatosRegistroTratamientos);

            // Enviar al servidor
            $("#divCuadernoOdontologia").html(loader);
            fetch("modulos/atencion_medica/registro_clinico/fn_registro_clinico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "guardarRegistroTratamientos",
                    idAtencion: idAtencion,
                    jsonDatosRegistroTratamientos: jsonDatosRegistroTratamientos
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                console.log("Datos del registro de tratamientos:", data);

                data = JSON.parse(data);

                if (data.idRecibo) {
                    $("#modal-xl").modal("show");
                    verRecibo(data.idRecibo);
                }

                if(data.estado == "OK"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Registro guardado correctamente'
                    });
                }
                else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.mensaje
                    });
                }

                listaRegistroCuaadernoOdontologia(idAtencion);
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al guardar el registro de tratamientos");
            });
        }
    });
</script>