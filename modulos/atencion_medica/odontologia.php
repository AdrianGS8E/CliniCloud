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
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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
                    formularioExamenGeneral(idAtencion);
                });

                $("#btnListaRegistroCuaadernoOdontologia").click(function () { 
                    listaRegistroCuaadernoOdontologia(idAtencion);
                });

                $("#btnFormRegistroTratamientos").click(function () { 
                    formularioRegistroTratamientos(idAtencion);
                });


            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar la atención clínica");
            });
        }

        function listaRegistroCuaadernoOdontologia(idAtencion){
            $("#divCuadernoOdontologia").html(loader);
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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


        function imprimirRegistroCuaderno(idAtencion, idCuaOdontologia){
            $("#modal-xl-content").html(loader);
            $("#modal-xl").modal("show");
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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

                // Agregar el primer registro
                agregarRegistroTratamiento();

                // Evento para agregar nuevo registro
                $("#btnAgregarRegistro").click(function () { 
                    agregarRegistroTratamiento();
                });

                // Evento delegado para eliminar registros
                $(document).on("click", ".btnEliminarRegistro", function () {
                    $(this).closest(".registro-tratamiento").remove();
                    actualizarNumerosRegistros();
                    calcularTotalGeneral();
                });

                // Evento delegado para cuando cambia el tratamiento (calcular total)
                $(document).on("change", ".campo-tratamiento", function () {
                    calcularTotalRegistro($(this).closest(".registro-tratamiento"));
                    calcularTotalGeneral();
                });

                // Evento para guardar
                $("#btnGuardarRegistroTratamiento").click(function () { 
                    guardarRegistroTratamientos(idAtencion);
                });
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el formulario de registro de tratamientos");
            });
        }

        function agregarRegistroTratamiento(){
            let template = $("#templateRegistro").html();
            let indice = Date.now() + Math.random();
            let numero = $(".registro-tratamiento").length + 1;
            
            template = template.replace(/\{\{INDICE\}\}/g, indice);
            template = template.replace(/\{\{NUMERO\}\}/g, numero);
            
            $("#contenedorRegistrosTratamientos").append(template);
            actualizarNumerosRegistros();
        }

        function actualizarNumerosRegistros(){
            $(".registro-tratamiento").each(function(index) {
                $(this).find(".numero-registro").text(index + 1);
                $(this).attr("data-indice", index);
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

        function calcularTotalGeneral(){
            let totalGeneral = 0;
            
            $(".campo-total").each(function() {
                let valor = parseFloat($(this).val()) || 0;
                totalGeneral += valor;
            });
            
            $("#totalGeneral").text(totalGeneral.toFixed(2));
        }

        function guardarRegistroTratamientos(idAtencion){
            // Validar que haya al menos un registro
            if($(".registro-tratamiento").length == 0){
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: 'Debe agregar al menos un registro de tratamiento'
                });
                return;
            }

            // Validar campos requeridos
            let hayErrores = false;
            let mensajeError = "";

            $(".registro-tratamiento").each(function(index) {
                let fecha = $(this).find(".campo-fecha").val();
                let diagnostico = $(this).find(".campo-diagnostico").val();
                let pieza = $(this).find(".campo-pieza").val();
                let tratamiento = $(this).find(".campo-tratamiento").val();

                if(!fecha || fecha.trim() == ''){
                    hayErrores = true;
                    mensajeError = "El campo Fecha es requerido en el registro #" + (index + 1);
                    return false;
                }
                if(!diagnostico || diagnostico.trim() == ''){
                    hayErrores = true;
                    mensajeError = "El campo Diagnóstico es requerido en el registro #" + (index + 1);
                    return false;
                }
                if(!pieza || pieza.trim() == ''){
                    hayErrores = true;
                    mensajeError = "El campo Pieza N° es requerido en el registro #" + (index + 1);
                    return false;
                }
                if(!tratamiento || tratamiento == ''){
                    hayErrores = true;
                    mensajeError = "El campo Tratamiento es requerido en el registro #" + (index + 1);
                    return false;
                }
            });

            if(hayErrores){
                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: mensajeError
                });
                return;
            }

            // Recopilar todos los registros en un array
            let registros = [];
            
            $(".registro-tratamiento").each(function() {
                let $registro = $(this);
                let $selectTratamiento = $registro.find(".campo-tratamiento");
                let $optionSeleccionada = $selectTratamiento.find("option:selected");
                
                let registro = {
                    fecha: $registro.find(".campo-fecha").val(),
                    diagnostico: $registro.find(".campo-diagnostico").val(),
                    pieza: $registro.find(".campo-pieza").val(),
                    idArancel: $selectTratamiento.val(),
                    codigoArancel: $optionSeleccionada.data("codigo") || "",
                    descripcionArancel: $optionSeleccionada.text().split(" - ")[1]?.split(" (Bs.")[0] || "",
                    precio: parseFloat($optionSeleccionada.data("precio")) || 0,
                    medicion: $registro.find(".campo-medicion").val() || "",
                    total: parseFloat($registro.find(".campo-total").val()) || 0
                };
                
                registros.push(registro);
            });

            // Crear objeto JSON con todos los registros
            let datosRegistroTratamientos = {
                registros: registros,
                totalGeneral: parseFloat($("#totalGeneral").text()) || 0
            };

            let jsonDatosRegistroTratamientos = JSON.stringify(datosRegistroTratamientos);
            console.log("Datos del registro de tratamientos:", jsonDatosRegistroTratamientos);

            // Enviar al servidor
            $("#divCuadernoOdontologia").html(loader);
            fetch("modulos/atencion_medica/fn_odontologia.php", {
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
                data = JSON.parse(data);

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