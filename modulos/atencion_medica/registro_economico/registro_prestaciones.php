<?php

    $idAtencion = $_POST['idAtencion'];
    echo "<input type='hidden' id='idAtencionRegistroPrestaciones' value='$idAtencion'>";
?>

<div id='divContenidoModalRegistroPrestaciones'></div>

<script>

    $(document).ready(function() {

        let idAtencion = $("#idAtencionRegistroPrestaciones").val();

        formularioRegistroPrestaciones(idAtencion);

        function formularioRegistroPrestaciones(idAtencion){
            $("#divContenidoModalRegistroPrestaciones").html(loader);
            fetch("modulos/atencion_medica/registro_economico/fn_registro_prestaciones.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "formularioRegistroPrestaciones",
                    idAtencion: idAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#divContenidoModalRegistroPrestaciones").html(data);

                const container = document.getElementById('hotPrestaciones');
                const totalEl = document.getElementById('totalPrestacionesValor');
                const jsonEl = document.getElementById('prestaciones_json');

                const defaultRows = Array.from({ length: 5 }, () => ['', null]);

                const hot = new Handsontable(container, {
                    data: defaultRows,
                    colHeaders: ['Prestación', 'Monto'],
                    columns: [
                        { type: 'text' },
                        { type: 'numeric', numericFormat: { pattern: '0,0.00' } }
                    ],
                    rowHeaders: true,
                    themeName: 'horizon',
                    stretchH: 'all',
                    autoColumnSize: true,
                    manualColumnResize: true,
                    height: 280,
                    width: '100%',
                    licenseKey: 'non-commercial-and-evaluation'
                });

                const calcTotal = () => {
                    const rows = hot.getData() || [];
                    let sum = 0;
                    for (const r of rows) {
                        const v = (r && r.length > 1) ? r[1] : undefined;
                        const n = typeof v === 'number' ? v : parseFloat(String(v ?? '').replace(',', '.'));
                        if (!Number.isNaN(n)) sum += n;
                    }
                    if (totalEl) totalEl.textContent = sum.toFixed(2);
                };

                const syncJson = () => {
                    if (!jsonEl) return;
                    const rows = (hot.getData() || [])
                        .map((r) => ({
                            prestacion: (((r && r.length > 0) ? r[0] : '') ?? '').toString().trim(),
                            monto: ((r && r.length > 1) ? r[1] : undefined) === '' || ((r && r.length > 1) ? r[1] : undefined) === null || typeof ((r && r.length > 1) ? r[1] : undefined) === 'undefined'
                                ? null
                                : (typeof ((r && r.length > 1) ? r[1] : undefined) === 'number'
                                    ? r[1]
                                    : parseFloat(String(r[1]).replace(',', '.')))
                        }))
                        .filter((x) => x.prestacion !== '' || (typeof x.monto === 'number' && !Number.isNaN(x.monto)));
                    jsonEl.value = JSON.stringify(rows);
                };

                hot.addHook('afterChange', (changes, source) => {
                    if (!changes || source === 'loadData') return;
                    calcTotal();
                    syncJson();
                });

                // Inicializar total y JSON con filas por defecto
                calcTotal();
                syncJson();

                // Botones: agregar / eliminar filas
                $('#btnAgregarFilaPrestacion')
                    .off('click')
                    .on('click', () => {
                        const sel = hot.getSelectedLast();
                        const lastRow = hot.countRows() - 1;
                        const baseRow = sel ? Math.max(sel[0], sel[2]) : lastRow;
                        // Handsontable CDN (sin versión fija) puede variar: preferimos insert_row_below y dejamos fallback.
                        try {
                            hot.alter('insert_row_below', baseRow, 1);
                        } catch (e) {
                            const insertAt = baseRow + 1;
                            hot.alter('insert_row', insertAt, 1);
                        }
                        const newRow = Math.min(baseRow + 1, hot.countRows() - 1);
                        hot.selectCell(newRow, 0);
                        calcTotal();
                        syncJson();
                    });

                $('#btnEliminarFilaPrestacion')
                    .off('click')
                    .on('click', () => {
                        const sel = hot.getSelectedLast();
                        const rowCount = hot.countRows();
                        if (rowCount <= 1) return;

                        if (sel) {
                            const start = Math.min(sel[0], sel[2]);
                            const end = Math.max(sel[0], sel[2]);
                            const amount = Math.min(end - start + 1, hot.countRows() - 1);
                            hot.alter('remove_row', start, amount);
                        } else {
                            hot.alter('remove_row', rowCount - 1, 1);
                        }
                        calcTotal();
                        syncJson();
                    });

                // Si se inicializa mientras el modal/tab está oculto, Handsontable puede calcular anchos mínimos.
                // Forzamos un render cuando ya está en el DOM y, si aplica, cuando el modal se muestra.
                requestAnimationFrame(() => hot.render());
                const modalEl = container.closest('.modal');
                if (modalEl) {
                    modalEl.addEventListener('shown.bs.modal', () => hot.render(), { once: true });
                }


                $("#btnRegistrarPrestaciones").click(function () { 
                    // Asegurar JSON limpio (sin filas vacías) antes de enviar
                    syncJson();
                    const jsonDetallePrestaciones = (jsonEl && jsonEl.value) ? jsonEl.value : "[]";
                    const montoTotal = totalEl ? totalEl.textContent : "0.00";
                    let estado = 'ORDEN ATENCION';
                    registrarPrestaciones(idAtencion, jsonDetallePrestaciones, montoTotal, estado);
                });
                $("#btnRegistrarCotizacion").click(function () { 
                    // Asegurar JSON limpio (sin filas vacías) antes de enviar
                    syncJson();
                    const jsonDetallePrestaciones = (jsonEl && jsonEl.value) ? jsonEl.value : "[]";
                    const montoTotal = totalEl ? totalEl.textContent : "0.00";
                    let estado = 'COTIZACION';
                    registrarPrestaciones(idAtencion, jsonDetallePrestaciones, montoTotal, estado);
                });
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al cargar el formulario de registro de prestaciones");
            });
        }


        function registrarPrestaciones(idAtencion, jsonDetallePrestaciones, montoTotal, estado){
            $("#divContenidoModalRegistroPrestaciones").html(loader);
            fetch("modulos/atencion_medica/registro_economico/fn_registro_prestaciones.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "registrarPrestaciones",
                    idAtencion: idAtencion,
                    jsonDetallePrestaciones: jsonDetallePrestaciones,
                    montoTotal: montoTotal,
                    estado: estado
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (respText) {
                if (!verificarSesion(respText)) return;

                const resp = JSON.parse(respText);
                const idOrdenAtencion = resp.idOrdenAtencion;

                if(resp.estado == 'OK'){
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Prestaciones registradas con éxito!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    verOrdenAtencion(idOrdenAtencion);
                }
                else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: resp.mensaje,
                        footer: 'Intente nuevamente'
                    })
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al registrar las prestaciones");
            });
        }

        function verOrdenAtencion(idOrdenAtencion){
            $("#divContenidoModalRegistroPrestaciones").html(loader);
            fetch("modulos/atencion_medica/registro_economico/fn_registro_prestaciones.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    funcion: "verOrdenAtencion",
                    idOrdenAtencion: idOrdenAtencion
                })
            })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (!verificarSesion(data)) return;
                $("#divContenidoModalRegistroPrestaciones").html(data);
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Error al verificar la orden de atención");
            });
        }


    });

</script>
