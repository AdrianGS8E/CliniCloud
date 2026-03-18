<?php
    $idAtencion = isset($_POST['idAtencion']) ? (int)$_POST['idAtencion'] : 0;
    echo "<input type='hidden' id='idAtencion' value='" . $idAtencion . "'>";
?>

<div id="divContenidoModal"></div>

<script>
    $(document).ready(function() {
        const idAtencion = parseInt($("#idAtencion").val() || "0", 10);
        cargarHistorial(idAtencion);

        function cargarHistorial(idAtencion){
            $("#divContenidoModal").html(loader);
            fetch("modulos/atencion_medica/historial/fn_historial..php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    funcion: "listarHistorial",
                    idAtencion: idAtencion
                })
            })
            .then(r => r.text())
            .then(html => {
                if (!verificarSesion(html)) return;
                $("#divContenidoModal").html(html);
            })
            .catch(err => {
                console.error(err);
                $("#divContenidoModal").html("<div class='alert alert-danger mb-0'>No se pudo cargar el historial.</div>");
            });
        }

        $(document).off("click", ".btnVerHistorial").on("click", ".btnVerHistorial", function(){
            const idCuaOdontologia = parseInt($(this).data("idcua") || "0", 10);
            const tipoAtencion = String($(this).data("tipo") || "");
            if (!idCuaOdontologia || !tipoAtencion) return;
            verRegistroPorTipo(idAtencion, idCuaOdontologia, tipoAtencion);
        });

        function verRegistroPorTipo(idAtencion, idCuaOdontologia, tipoAtencion){
            let endpoint = "";
            let funcion = "";

            switch (tipoAtencion) {
                case "EXAMEN GENERAL":
                    endpoint = "modulos/atencion_medica/examen_general/fn_examen_general.php";
                    funcion = "verFormularioExamenGeneral";
                    break;
                case "SOLICITUD PROTESICO":
                    endpoint = "modulos/atencion_medica/protesico/fn_solicitud_protesico.php";
                    funcion = "verSolicitudProtesico";
                    break;
                case "RAYOS X":
                    endpoint = "modulos/atencion_medica/rayos_x/fn_rayox_x.php";
                    funcion = "verRayosX";
                    break;
                case "TRATAMIENTO MEDICO":
                    endpoint = "modulos/atencion_medica/registro_clinico/fn_registro_clinico.php";
                    funcion = "verRegistroClinico";
                    break;
                default:
                    $("#divContenidoModal").html("<div class='alert alert-warning mb-0'>Tipo de atención no soportado: <b>" + tipoAtencion + "</b></div>");
                    return;
            }

            $("#divContenidoModal").html(loader);
            fetch(endpoint, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    funcion: funcion,
                    idAtencion: idAtencion,
                    idCuaOdontologia: idCuaOdontologia
                })
            })
            .then(r => r.text())
            .then(html => {
                if (!verificarSesion(html)) return;
                $("#divContenidoModal").html(html);

                
            })
            .catch(err => {
                console.error(err);
                $("#divContenidoModal").html("<div class='alert alert-danger mb-0'>No se pudo mostrar el registro.</div>");
            });
        }
    });
</script>
