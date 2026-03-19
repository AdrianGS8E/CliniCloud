<?php

    $idAtencion = $_POST['idAtencion'];
    echo "<input type='hidden' id='idAtencionRegistroPago' value='$idAtencion'>";
?>

<div id='divContenidoModalRegistroPago'></div>

<script>

    $(document).ready(function() {

        let idAtencion = $("#idAtencionRegistroPago").val();

        function calcularSaldoPago(){
            var hdnSaldoPendiente = document.getElementById('hdnSaldoPendiente');
            var inputMontoPago = document.getElementById('txtMontoPago');
            var inputSaldoRestante = document.getElementById('txtSaldoRestante');
            var badgeSaldoPendiente = document.getElementById('badgeSaldoPendiente');
            var hdnTieneSaldoPendiente = document.getElementById('hdnTieneSaldoPendiente');

            // Si aún no existe el modal (o no se insertó el contenido), no hacemos nada.
            if(!hdnSaldoPendiente || !inputMontoPago || !inputSaldoRestante) return;

            var saldoPendiente = parseFloat(String(hdnSaldoPendiente.value || '0').replace(',', '.')) || 0;
            var monto = parseFloat(String(inputMontoPago.value || '0').replace(',', '.')) || 0;

            if(monto < 0) monto = 0;

            var saldoRestante = saldoPendiente - monto;
            var saldoRestanteClamped = saldoRestante < 0 ? 0 : saldoRestante;

            inputSaldoRestante.value = (Math.round(saldoRestanteClamped * 100) / 100).toFixed(2);

            if(saldoRestante <= 0.000001){
                if(badgeSaldoPendiente){
                    badgeSaldoPendiente.className = 'badge bg-success';
                    badgeSaldoPendiente.textContent = 'Pago completo (sin saldo pendiente)';
                }
                if(hdnTieneSaldoPendiente) hdnTieneSaldoPendiente.value = '0';
            }
            else{
                if(badgeSaldoPendiente){
                    badgeSaldoPendiente.className = 'badge bg-warning text-dark';
                    badgeSaldoPendiente.textContent = 'Aún hay saldo pendiente';
                }
                if(hdnTieneSaldoPendiente) hdnTieneSaldoPendiente.value = '1';
            }
        }

        // Delegación para cuando el modal se inserte dinámicamente.
        $(document).on('input', '#txtMontoPago', function(){
            calcularSaldoPago();
        });

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

                $(".btnFormularioRegistrarPago").click(function () { 
                    let idOrdenAtencion = $(this).attr("id");

                    formularioRegistrarPago(idOrdenAtencion, idAtencion);
                });

                $(".btnProcesarCotizacion").click(function () { 
                    let idOrdenAtencion = $(this).attr("id");
                    procesarCotizacion(idOrdenAtencion, idAtencion);
                });

                
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al listar las ordenes de atención");
            });
        }

        function formularioRegistrarPago(idOrdenAtencion, idAtencion){
            $("#divContenidoModalRegistroPago").html(loader);
            fetch("modulos/atencion_medica/registro_economico/fn_registro_pago.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "formularioRegistrarPago",
                    idOrdenAtencion: idOrdenAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#divContenidoModalRegistroPago").html(data);
                calcularSaldoPago();

                $("#btnRegistrarPago").click(function () { 
                    let idOrdenAtencion = $("#idOrdenAtencionRegistroPago").val();
                    let montoPago = $("#txtMontoPago").val();

                    registrarPago(idOrdenAtencion, montoPago, idAtencion);
                });
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al mostrar el formulario de registrar pago");
            });
        }

        function registrarPago(idOrdenAtencion, montoPago, idAtencion){
            $("#divContenidoModalRegistroPago").html(loader);
            fetch("modulos/atencion_medica/registro_economico/fn_registro_pago.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "registrarPago",
                    idOrdenAtencion: idOrdenAtencion,
                    montoPago: montoPago
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                let jsonData = JSON.parse(data);
                let idRecibo = jsonData.idRecibo;

                if(idRecibo){
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Pago registrado con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    verReciboPago(idRecibo, idAtencion);
                }
                else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Hubo un error al registrar el pago!',
                        footer: 'Intente nuevamente'
                    })
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al registrar el pago");
            });
        }

        function verReciboPago(idRecibo, idAtencion){
            $("#divContenidoModalRegistroPago").html(loader);
            fetch("modulos/atencion_medica/registro_economico/fn_registro_pago.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "verReciboPago",
                    idRecibo: idRecibo
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                $("#divContenidoModalRegistroPago").html(data);
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al ver el recibo de pago");
            });
        }

        function procesarCotizacion(idOrdenAtencion, idAtencion){
            $("#divContenidoModalRegistroPago").html(loader);
            fetch("modulos/atencion_medica/registro_economico/fn_registro_pago.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "procesarCotizacion",
                    idOrdenAtencion: idOrdenAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;

                let jsonData = JSON.parse(data);
                let estado = jsonData.estado;
                if(estado == 'OK'){
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Cotización procesada con éxito!',
                    })
                }

                listaOrdenesAtencion(idAtencion);
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al procesar la cotización");
            });
        }
    });

</script>
