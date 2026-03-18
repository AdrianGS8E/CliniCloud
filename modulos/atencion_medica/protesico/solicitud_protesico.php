<?php

    $idAtencion = $_POST['idAtencion'];
    echo "<input type='hidden' id='idAtencion' value='$idAtencion'>";
?>

<div id='divContenidoModal'></div>

<script>

    $(document).ready(function() {

        let idAtencion = $("#idAtencion").val();

        cargarFormularioSolicitudProtesico(idAtencion);

        function cargarFormularioSolicitudProtesico(idAtencion){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/protesico/fn_solicitud_protesico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "formularioSolicitudProtesico",
                    idAtencion: idAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#divContenidoModal").html(data);

                $("#btnGuardarSolicitudProtesico").click(function () {

                    let datosSolicitudProtesico = $("#frmSolicitudProtesico").serializeArray().reduce((acc, { name, value }) => {
                        if (Object.prototype.hasOwnProperty.call(acc, name)) {
                            acc[name] = Array.isArray(acc[name]) ? [...acc[name], value] : [acc[name], value];
                        } else {
                            acc[name] = value;
                        }
                        return acc;
                    }, {});

                    guardarSolicitudProtesico(idAtencion, datosSolicitudProtesico);
                });
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el formulario de solicitud protésico");
            });
        }

        function guardarSolicitudProtesico(idAtencion, datosSolicitudProtesico){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/protesico/fn_solicitud_protesico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "guardarSolicitudProtesico",
                    idAtencion: idAtencion,
                    datosSolicitudProtesico: datosSolicitudProtesico
                })
            })
            .then(function (response) { return response.json(); })
            .then(function (respuesta) {
                if (respuesta.sesion === 'cerrada') {
                    if (typeof verificarSesion === 'function') verificarSesion(JSON.stringify(respuesta));
                    return;
                }
                if (respuesta.estado == "OK") {
                    Swal.fire({
                        title: 'Solicitud protésico registrada correctamente',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    let idCuaOdontologia = respuesta.idCuaOdontologia;
                    verSolicitudProtesico(idAtencion, idCuaOdontologia);
                } else {
                    Swal.fire({
                        title: respuesta.mensaje || 'Error al guardar la solicitud',
                        icon: 'error',
                        showConfirmButton: true
                    });
                    cargarFormularioSolicitudProtesico(idAtencion);
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al guardar la solicitud protésico");
                cargarFormularioSolicitudProtesico(idAtencion);
            });
        }

        function verSolicitudProtesico(idAtencion, idCuaOdontologia){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/protesico/fn_solicitud_protesico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "verSolicitudProtesico",
                    idAtencion: idAtencion,
                    idCuaOdontologia: idCuaOdontologia
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#divContenidoModal").html(data);
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar la solicitud");
            });
        }

    });

</script>
