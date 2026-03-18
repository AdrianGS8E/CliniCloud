<?php

    $idAtencion = $_POST['idAtencion'];
    echo "<input type='hidden' id='idAtencion' value='$idAtencion'>";
?>

<div id='divContenidoModal'></div>

<script>

    $(document).ready(function() {

        let idAtencion = $("#idAtencion").val();

        listaOrdenesAtencion(idAtencion);
        function listaOrdenesAtencion(idAtencion){
            $("#divContenidoModal").html(loader);
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


                $("#divContenidoModal").html(data);

                
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al listar las ordenes de atención");
            });
        }


    });

</script>
