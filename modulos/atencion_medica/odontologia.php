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

        function verPacientesConsultorio(idConsultorio){
            $("#contenido").html(loader);
            fetch("modulos/atencion_medica/fn_odontologia.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "verPacientesConsultorio",
                    idConsultorio: idConsultorio
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#contenido").html(data);

                $("#btnSeleccionarPaciente").click(function () { 
                    modalSeleccionarPaciente(idConsultorio);
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


                $(".btnFormExamenGeneral").click(function () { 
                    let idPaciente = $(this).attr("id");
                    formularioExamenGeneral(idConsultorio, idPaciente);
                    $("#modal-xl").modal("hide");
                });


            });
        }


        function formularioExamenGeneral(idConsultorio, idPaciente){
            $("#contenido").html(loader);
            fetch("modulos/atencion_medica/fn_odontologia.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "formularioExamenGeneral",
                    idConsultorio: idConsultorio,
                    idPaciente: idPaciente
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#contenido").html(data);

                $("#btnGuardarExamenGeneral").click(function () { 

                    guardarExamenGeneral(idConsultorio, idPaciente);
                });
            });
        }

        function guardarExamenGeneral(idConsultorio, idPaciente){
            // Recopilar datos del examen general
            const examenGeneral = {
                intervenido_quirurgicamente: $("input[name='intervenido_quirurgicamente']:checked").val() || "",
                problemas_cardiacos: $("input[name='problemas_cardiacos']:checked").val() || "",
                diabetico: $("input[name='diabetico']:checked").val() || "",
                alergia_medicamentos: $("input[name='alergia_medicamentos']:checked").val() || "",
                cicatrizacion_normal: $("input[name='cicatrizacion_normal']:checked").val() || "",
                problemas_coagulacion: $("input[name='problemas_coagulacion']:checked").val() || "",
                tratamiento_medico: $("input[name='tratamiento_medico']:checked").val() || "",
                toma_medicamentos: $("input[name='toma_medicamentos']:checked").val() || "",
                embarazo: $("input[name='embarazo']:checked").val() || "",
                fum: $("#fum").val() || ""
            };

            // Recopilar datos del examen bucodental
            const examenBucoDental = {
                higiene_dental: $("#higiene_dental").val() || "",
                usa_cepillo_dental: $("input[name='usa_cepillo_dental']:checked").val() || "",
                frecuencia_cepillado: $("#frecuencia_cepillado").val() || "",
                usa_hilo_dental: $("input[name='usa_hilo_dental']:checked").val() || ""
            };

            // Recopilar datos de hábitos y costumbres
            const habitosCostumbres = {
                respirador_bucal: $("input[name='respirador_bucal']:checked").val() || "",
                usa_chupon: $("input[name='usa_chupon']:checked").val() || "",
                fuma: $("input[name='fuma']:checked").val() || "",
                toma_alcohol: $("input[name='toma_alcohol']:checked").val() || "",
                masca_coca: $("input[name='masca_coca']:checked").val() || ""
            };

            // Recopilar motivo de consulta
            const motivoConsulta = $("#motivo_consulta").val() || "";

            $("#contenido").html(loader);
            fetch("modulos/atencion_medica/fn_odontologia.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "guardarExamenGeneral",
                    idConsultorio: idConsultorio,
                    idPaciente: idPaciente,
                    examenGeneral: examenGeneral,
                    examenBucoDental: examenBucoDental,
                    habitosCostumbres: habitosCostumbres,
                    motivoConsulta: motivoConsulta
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                try {
                    const respuesta = JSON.parse(data);
                    if (respuesta.estado === "OK") {
                        alert("Examen general guardado correctamente");
                        listaConsultorios();
                    } else {
                        alert("Error: " + (respuesta.mensaje || "Error al guardar el examen general"));
                        $("#contenido").html(data);
                    }
                } catch (e) {
                    // Si no es JSON, mostrar el contenido directamente
                    $("#contenido").html(data);
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al guardar el examen general");
            });
        }
    });
</script>