<?php

    $idAtencion = $_POST['idAtencion'];
    echo "<input type='hidden' id='idAtencionRegistroPago' value='$idAtencion'>";
?>

<div id='divContenidoModalRegistroPago'></div>

<script>

    $(document).ready(function() {

        let idAtencion = $("#idAtencionRegistroPago").val();

        listaOrdenesAtencion(idAtencion);
        function listaOrdenesAtencion(idAtencion){
            $("#divContenidoModalRegistroPago").html(loader);
            fetch("modulos/atencion_medica/registro_economico/fn_registro_pago.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "listaOrdenesAtencion",
                    idAtencion: idAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;


                $("#divContenidoModalRegistroPago").html(data);

                
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al listar las ordenes de atención");
            });
        }


    });

</script>
