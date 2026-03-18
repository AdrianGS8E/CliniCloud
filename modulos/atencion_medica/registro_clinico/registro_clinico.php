<?php

    $idAtencion = $_POST['idAtencion'];
    echo "<input type='hidden' id='idAtencion' value='$idAtencion'>";
?>

<div id='divContenidoModal'></div>

<script>

    $(document).ready(function() {

        let idAtencion = $("#idAtencion").val();

        cargarFormularioRegistroClinico(idAtencion);

        function cargarFormularioRegistroClinico(idAtencion){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/registro_clinico/fn_registro_clinico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "formularioRegistroClinico",
                    idAtencion: idAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#divContenidoModal").html(data);

                $("#btnGuardarRegistroClinico").click(function () {

                    let datosRegistroClinico = $("#frmRegistroClinico").serializeArray().reduce((acc, { name, value }) => {
                        if (Object.prototype.hasOwnProperty.call(acc, name)) {
                            acc[name] = Array.isArray(acc[name]) ? [...acc[name], value] : [acc[name], value];
                        } else {
                            acc[name] = value;
                        }
                        return acc;
                    }, {});

                    guardarRegistroClinico(idAtencion, datosRegistroClinico);
                });
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el formulario de registro clínico");
            });
        }

        function guardarRegistroClinico(idAtencion, datosRegistroClinico){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/registro_clinico/fn_registro_clinico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "guardarRegistroClinico",
                    idAtencion: idAtencion,
                    datosRegistroClinico: datosRegistroClinico
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
                        title: 'Tratamiento médico registrado correctamente',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    let idCuaOdontologia = respuesta.idCuaOdontologia;
                    verRegistroClinico(idAtencion, idCuaOdontologia);
                } else {
                    Swal.fire({
                        title: respuesta.mensaje || 'Error al guardar el registro',
                        icon: 'error',
                        showConfirmButton: true
                    });
                    cargarFormularioRegistroClinico(idAtencion);
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al guardar el registro clínico");
                cargarFormularioRegistroClinico(idAtencion);
            });
        }

        function verRegistroClinico(idAtencion, idCuaOdontologia){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/registro_clinico/fn_registro_clinico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "verRegistroClinico",
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
                alert("Error al cargar el registro");
            });
        }

    });

</script>
