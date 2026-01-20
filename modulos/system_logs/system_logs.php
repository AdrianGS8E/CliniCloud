<?php
session_start();

require_once "../../config_db_mysql.php";

?>

<!-- <nav class="app-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb ms-0">
        <li class="breadcrumb-item">Design</li>
        <li class="breadcrumb-item">Documentation</li>
        <li class="breadcrumb-item active" aria-current="page">Core Plugins</li>
    </ol>
</nav> -->
<h1 class="subheader-title"> Logs de Sistema
    <small> Modulo de logs de sistema del sistema </small>
</h1>



<div class="row">
    <div class="col-md-6 mx-auto">
        <div class='card border'>
            <div class='card-header'>
                <b>Parametros SystemLogs</b>
            </div>
            <div class='card-body row'>
                <div class='col-md-12 mb-2'>
                    <label for='fecha' class='form-label'>Fecha</label>
                    <div class='input-group'>
                        <span class='input-group-text'><i class='fa fa-calendar'></i></span>
                        <?php
                        date_default_timezone_set('America/La_Paz');
                        $fecha = date('Y-m-d');
                        ?>
                        <input type='date' id='fecha' name='fecha' class='form-control' value='<?php echo $fecha; ?>'>
                        <button class='btn btn-primary' id='btnBuscarLogs'><i class='fas fa-search'></i> Buscar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id='contenido'></div>

<script>
    $(document).ready(function(){

        $("#btnBuscarLogs").click(function () { 
            $("#contenido").html(loader);

            let fecha = $("#fecha").val();
            muestraLogs(fecha);
        });



        ace.define('ace/mode/log_highlight_rules', function(require, exports, module){
            const oop = require('ace/lib/oop');
            const TextHighlightRules = require('ace/mode/text_highlight_rules').TextHighlightRules;
            const LogHighlightRules = function() {
            this.$rules = {
                start: [
                { token: 'log.error',   regex: '\\bERROR\\b' },
                { token: 'log.success', regex: '\\b(?:EXITO|SUCCESS|CORRECTO)\\b' },
                { token: 'log.info',    regex: '\\bINFO\\b' },
                { defaultToken: 'text' }
                ]
            };
            };
            oop.inherits(LogHighlightRules, TextHighlightRules);
            exports.LogHighlightRules = LogHighlightRules;
        });

        // 2) Define el modo que usa esas reglas
        ace.define('ace/mode/log_mode', function(require, exports, module){
            const oop = require('ace/lib/oop');
            const TextMode = require('ace/mode/text').Mode;
            const LogHighlightRules = require('ace/mode/log_highlight_rules').LogHighlightRules;
            const Mode = function() {
            this.HighlightRules = LogHighlightRules;
            };
            oop.inherits(Mode, TextMode);
            Mode.prototype.$id = 'ace/mode/log_mode';
            exports.Mode = Mode;
        });

        function muestraLogs(fecha) {
            console.log(fecha);
            $("#contenido").html(loader);

            fetch("modulos/system_logs/fn_system_logs.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ funcion: "muestraLogs", fecha })
            })
            .then(r => r.text())
            .then(html => {
                if (!verificarSesion(html)) return;
                $("#contenido").html(html);
                const rutaLog = $("#rutaLog").val();
                if (!rutaLog) return; // Si está vacío, no hacer nada
                console.log(rutaLog);
                return fetch(rutaLog);
            })
            .then(r => r ? r.text() : null)
            .then(texto => {
                if (!texto) return; // Si no hay texto, no hacer nada
                // Inicializa Ace en el div
                const editor = ace.edit("editor");

                // Usa el modo personalizado para logs
                editor.session.setMode("ace/mode/log_mode");

                // Tema oscuro (puedes cambiarlo)
                editor.setTheme("ace/theme/monokai");

                // Opciones válidas
                editor.setOptions({
                    readOnly: true,
                    highlightActiveLine: false,
                    showPrintMargin: false,
                    wrap: true,
                    fontSize: "10pt"
                });

                // Asegura numeración de líneas
                editor.renderer.setShowGutter(true);

                // Vuelca el contenido
                editor.session.setValue(texto);
            })
            .catch(err => console.error("No se pudo cargar el log:", err));
        }


    });
</script>