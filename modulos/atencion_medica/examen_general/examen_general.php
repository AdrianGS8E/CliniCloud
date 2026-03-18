<?php

    $idAtencion = $_POST['idAtencion'];
    echo "<input type='hidden' id='idAtencion' value='$idAtencion'>";
?>

<!-- <div class='modal-header'>
    <h4 class='modal-title mt-0' id=''>Titulo</h4>
    <button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>
        <i class='fas fa-times'></i>
    </button>
</div>
<div class='modal-body divContenidoModal'>
    
</div>
<div class='modal-footer'>
    <button type='button' class='btn btn-primary waves-effect waves-light'></button>
    <button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>
</div> -->

<div id='divContenidoModal'></div>


<script>

    $(document).ready(function() {

        let idAtencion = $("#idAtencion").val();
        
        formularioExamenGeneral(idAtencion);

        function formularioExamenGeneral(idAtencion){
            $("#divContenidoModal").html(loader);
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
                $("#divContenidoModal").html(data);

                $("#btnGuardarExamenGeneral").click(function () { 

                    let datosExamenGeneral = $("#frmExamenGeneral").serializeArray().reduce((acc, { name, value }) => {
                        if (Object.prototype.hasOwnProperty.call(acc, name)) {
                            acc[name] = Array.isArray(acc[name]) ? [...acc[name], value] : [acc[name], value];
                        } else {
                            acc[name] = value;
                        }
                        return acc;
                    }, {});
                    console.log(datosExamenGeneral);

                    guardarExamenGeneral(idAtencion, datosExamenGeneral);
                });

            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el formulario de examen general");
            });
        }

        function guardarExamenGeneral(idAtencion, datosExamenGeneral){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/examen_general/fn_examen_general.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "guardarExamenGeneral", 
                    idAtencion: idAtencion,
                    datosExamenGeneral: datosExamenGeneral
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                
                let respuesta = JSON.parse(data);


                if (respuesta.estado == "OK") {
                    Swal.fire({
                        title: 'Examen general guardado correctamente',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    let idCuaOdontologia = respuesta.idCuaOdontologia;
                    verFormularioExamenGeneral(idAtencion, idCuaOdontologia);
                } else {
                    Swal.fire({
                        title: 'Error al guardar el examen general',
                        icon: 'error',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al guardar el examen general");
            });
        }

        function verFormularioExamenGeneral(idAtencion, idCuaOdontologia){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/examen_general/fn_examen_general.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ 
                    funcion: "verFormularioExamenGeneral", 
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
                alert("Error al verificar el formulario de examen general");
            });
        }














    });

</script>