<?php

    $idAtencion = $_POST['idAtencion'];
    echo "<input type='hidden' id='idAtencion' value='$idAtencion'>";
?>

<div id='divContenidoModal'></div>

<script>

    $(document).ready(function() {

        let idAtencion = $("#idAtencion").val();

        cargarFormularioRayosX(idAtencion);

        function cargarFormularioRayosX(idAtencion){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/rayos_x/fn_rayox_x.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "formularioRayosX",
                    idAtencion: idAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#divContenidoModal").html(data);

                $("#btnGuardarRayosX").off("click").on("click", function () {
                    const form = document.getElementById("frmRayosX");
                    if (!form) {
                        alert("No se encontró el formulario de Rayos X.");
                        return;
                    }

                    const formData = new FormData(form);
                    formData.append("funcion", "guardarRayosX");
                    formData.append("idAtencion", idAtencion);

                    guardarRayosX(formData, idAtencion);
                });
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el formulario de rayos X");
            });
        }

        function guardarRayosX(formData, idAtencion){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/rayos_x/fn_rayox_x.php", {
                method: "POST",
                body: formData
            })
            .then(function (response) { return response.json(); })
            .then(function (respuesta) {
                if (respuesta.sesion === 'cerrada') {
                    if (typeof verificarSesion === 'function') verificarSesion(JSON.stringify(respuesta));
                    return;
                }
                if (respuesta.estado === "OK") {
                    Swal.fire({
                        title: 'Radiografía registrada correctamente',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    let idCuaOdontologia = respuesta.idCuaOdontologia;
                    verRayosX(idAtencion, idCuaOdontologia);
                } else {
                    Swal.fire({
                        title: respuesta.mensaje || 'Error al guardar la radiografía',
                        icon: 'error',
                        showConfirmButton: true
                    });
                    cargarFormularioRayosX(idAtencion);
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al guardar la radiografía");
                cargarFormularioRayosX(idAtencion);
            });
        }

        function verRayosX(idAtencion, idCuaOdontologia){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/rayos_x/fn_rayox_x.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "verRayosX",
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
                alert("Error al cargar la radiografía");
            });
        }

    });

</script>
