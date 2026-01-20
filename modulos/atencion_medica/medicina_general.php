<?php
session_start();

require_once "../../config_db_mysql.php";

?>

<h1 class="subheader-title"> Atencion Medica
    <small> Gestion de la atencion medica </small>
</h1>

<div id='contenido'></div>

<script>
    $(document).ready(function(){

        listaConsultorios();
        function listaConsultorios() {
            console.log("listando consultorios");
            $("#contenido").html(loader);
            fetch("modulos/atencion_medica/fn_atencion_medica.php", {
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

                $("#btnFormNuevoConsultorio").click(function () { 
                    formNuevoConsultorio();
                });

                $(".btnFormEditarConsultorio").click(function () { 
                    let idConsultorio = $(this).attr("id");
                    formEditarConsultorio(idConsultorio);
                });

            });
        }

    });
</script>