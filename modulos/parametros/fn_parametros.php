<?php
session_start();

// SI NO HAY SESIÓN, DETENER TODO ANTES DE INCLUIR O PROCESAR NADA
if (!isset($_SESSION['idUsuario_clinicloud'])) {
    // Forzar JSON limpio si se trata de una petición AJAX (fetch)
    header('Content-Type: application/json');
    echo json_encode(['sesion' => 'cerrada']);
    exit;
}

require_once "../../config_db_mysql.php";
header("Content-Type: application/json");

// Leer datos enviados desde fetch
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

if (!isset($input['funcion'])) {
    echo json_encode(["estado" => "ERROR", "mensaje" => "No se especificó la función a ejecutar."]);
    exit;
}

switch ($input['funcion']) {
    case "listaParamtros":
        listaParamtros();
        break;
    case 'formEditarParametro':
        formEditarParametro();
        break;
    case 'editarParametro':
        editarParametro();
        break;
    case 'formFirmaDigital':
        formFirmaDigital();
        break;
    case 'cargarFirmaDigital':
        cargarFirmaDigital();
        break;

    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function listaParamtros(){
    global $link;
    global $input;


    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Lista de Parametros</b>";
                echo "</div>";
                echo "<div class='card-body table-responsive'>";
                    echo "<table class='table table-bordered table-sm table-hover'>";
                        echo "<thead>";
                            echo "<tr>";
                                echo "<th>ID</th>";
                                echo "<th>Clave</th>";
                                echo "<th>Valor</th>";
                                echo "<th>Descripcion</th>";
                                echo "<th>Acciones</th>";
                            echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";

                        $modalidad = 0;

                        $sql = "SELECT `idParametro`, `clave`, `valor`, `descripcion` FROM `parametros` ORDER BY `idParametro` ASC";
                        $result = mysqli_query($link, $sql);
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                                echo "<td>" . $row['idParametro'] . "</td>";
                                echo "<td>" . $row['clave'] . "</td>";

                                if($row['clave'] == "CODIGO_MODALIDAD"){
                                    $modalidad = $row['valor'];
                                }

                                // Limitar el largo de 'valor'
                                $valor = $row['valor'];
                                $valorCompleto = $row['valor'];
                                if (strlen($valor) > 40) {
                                    $valor = substr($valor, 0, 40) . '...';
                                }

                                if($row['clave'] == "MAIL_PASSWORD"){
                                    $valor = "********";
                                    $valorCompleto = "";
                                }
                                echo "<td title='" . $valorCompleto . "'>" . htmlspecialchars($valor) . "</td>";

                                echo "<td>" . $row['descripcion'] . "</td>";
                                echo "<td class='text-center'>";
                                    echo "<button class='btn btn-sm btn-primary btnFormEditarParametro' id='" . $row['idParametro'] . "'><i class='fas fa-edit'></i></button>";
                                echo "</td>";
                            echo "</tr>";
                        }

                        echo "</tbody>";
                    echo "</table>";


                echo "</div>";
                if($modalidad == 1){
                    echo "<div class='card-footer text-right py-2 text-center'>";
                        echo "<button class='btn btn-primary' id='btnFormFirmaDigital'><i class='fas fa-plus'></i> Firma Digital</button>";
                    echo "</div>";
                }
            echo "</div>";
        echo "</div>";
    echo "</div>";


}

function formEditarParametro(){
    global $link;
    global $input;

    $idParametro = $input['idParametro'];

    $conParametro = mysqli_query($link, "SELECT `idParametro`, `clave`, `valor`, `descripcion` FROM `parametros` WHERE `idParametro` = '$idParametro'")or die(mysqli_error($link));
    if(mysqli_num_rows($conParametro) > 0){
        $rowParametro = mysqli_fetch_array($conParametro);
        $clave = $rowParametro['clave'];
        $valor = $rowParametro['valor'];
        $descripcion = $rowParametro['descripcion'];
    }

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0' id=''>Modificar Parametro</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
    
        echo "<div class='row'>";

            echo "<div class='col-md-12'>";
                echo "<div class='mb-3'>";
                    echo "<label class='form-label' for='clave'>Clave</label>";
                    echo "<div class='input-group'>";
                        echo "<span class='input-group-text'><i class='fas fa-key'></i></span>";
                        echo "<input type='text' id='clave' name='clave' class='form-control' placeholder='Clave' value='$clave' disabled>";
                    echo "</div>";
                    echo "<div class='form-text'>Clave identificador del parametro</div>";
                echo "</div>";
            echo "</div>";

            echo "<div class='col-md-12'>";
                echo "<div class='mb-3'>";
                    echo "<label class='form-label' for='valor'>Valor</label>";
                    echo "<div class='input-group'>";
                        echo "<span class='input-group-text'><i class='fas fa-code'></i></span>";
                        if($clave == "MAIL_PASSWORD"){
                            $valor = "";
                        }
                        echo "<textarea id='valor' name='valor' class='form-control' placeholder='Valor' rows='4'>$valor</textarea>";
                    echo "</div>";
                    echo "<div class='form-text'>Valor del parametro</div>";
                echo "</div>";
            echo "</div>";

            echo "<div class='col-md-12'>";
                echo "<div class='mb-3'>";
                    echo "<label class='form-label' for='descripcion'>Descripcion</label>";
                    echo "<div class='input-group'>";
                        echo "<span class='input-group-text'><i class='fas fa-align-left'></i></span>";
                        echo "<textarea id='descripcion' name='descripcion' class='form-control' placeholder='Descripcion' rows='4'>$descripcion</textarea>";
                    echo "</div>";
                    echo "<div class='form-text'>Descripcion del parametro</div>";
                echo "</div>";
            echo "</div>";

        echo "</div>";


    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnEditarParametro'>Modificar Parametro</button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";

    
}

function editarParametro(){
    global $link;
    global $input;

    $datosEditarParametro = $input['datosEditarParametro'];

    $idParametro = $datosEditarParametro['idParametro'];
    $clave = $datosEditarParametro['clave'];
    $valor = $datosEditarParametro['valor'];
    $descripcion = $datosEditarParametro['descripcion'];

    //UPDATE `parametros` SET `clave`='[value-2]',`valor`='[value-3]',`descripcion`='[value-4]' WHERE `idParametro` = ''
    $sql = "UPDATE `parametros` SET `clave`='$clave',`valor`='$valor',`descripcion`='$descripcion' WHERE `idParametro` = '$idParametro'";
    $result = mysqli_query($link, $sql);
    if($result){
        echo "OK";
    }
    else{
        echo "Error al modificar el parametro";
    }
}

function formFirmaDigital(){
    global $link;
    global $input;

    echo "<div class='modal-header'>";
        echo "<h4 class='modal-title mt-0' id=''>Firma Digital</h4>";
        echo "<button type='button' class='btn btn-default btn-icon rounded-circle ms-auto' data-bs-dismiss='modal' aria-label='Close'>";
            echo "<i class='fas fa-times'></i>";
        echo "</button>";
    echo "</div>";
    echo "<div class='modal-body'>";
        echo "<div class='row'>";
            echo "<div class='col-md-12'>";
                echo "<div class='mb-3'>";
                    echo "<label class='form-label' for='clave'>Clave</label>";
                    echo "<div class='input-group'>";
                        echo "<span class='input-group-text'><i class='fas fa-key'></i></span>";
                        echo "<input type='text' id='clave' name='clave' class='form-control' placeholder='Clave' value=''>";
                    echo "</div>";
                    echo "<div class='form-text'>Clave identificador del parametro</div>";
                echo "</div>";
            echo "</div>";

            echo "<div class='col-md-12'>";
                echo "<div class='mb-3'>";
                    echo "<label class='form-label' for='archivoFirmaDigital'>Archivo</label>";
                    echo "<div class='input-group'>";
                        echo "<span class='input-group-text'><i class='fas fa-key'></i></span>";
                        echo "<input type='file' id='archivoFirmaDigital' name='archivoFirmaDigital' class='form-control' placeholder='Archivo'>";
                    echo "</div>";
                    echo "<div class='form-text'>Archivo identificador del parametro</div>";
                echo "</div>";
            echo "</div>";
        echo "</div>";

    echo "</div>";
    echo "<div class='modal-footer'>";
        echo "<button type='button' class='btn btn-primary waves-effect waves-light' id='btnCargarFirmaDigital'>Cargar Firma Digital</button>";
        echo "<button type='button' class='btn btn-secondary waves-effect' data-bs-dismiss='modal'>Cerrar</button>";
    echo "</div>";

    
}

/*
@fn_parametros.php#L249-257 verifica que la firma digital sea un archivo .p12 
guarda el archivo p12 en la siguiente ruta 
\storage\firma_digital
utilizando la logica del archivo @extrae_pem.php extrae los archivos necesarios de llave privada y certificado, y guardalos en el mismo lugar, 
si el procedimiento ha sido correcto, imprime en html con echo la informacion del certificado tal como lo hace el archivo @ver_firma.php 
*/

function cargarFirmaDigital(){
    global $link;
    global $input;

    $clave = $input['clave'];
    $archivoFirmaDigital = $input['archivoFirmaDigital'];

    // Verificar que se recibió el archivo
    if (empty($archivoFirmaDigital)) {
        echo json_encode(['error' => 'No se recibió el archivo de firma digital']);
        return;
    }

    // Verificar que la clave fue proporcionada
    if (empty($clave)) {
        echo json_encode(['error' => 'No se proporcionó la contraseña del certificado']);
        return;
    }

    // Decodificar el archivo (asumiendo que viene en base64)
    $fileContent = base64_decode($archivoFirmaDigital);
    
    if ($fileContent === false) {
        echo json_encode(['error' => 'Error al decodificar el archivo']);
        return;
    }

    // Verificar que sea un archivo .p12 válido intentando leerlo
    $certificates = [];
    $pkcs12 = openssl_pkcs12_read($fileContent, $certificates, $clave);

    if (!$pkcs12) {
        echo "❌ No se pudo abrir el archivo P12 o la contraseña es incorrecta.<br>";
        return;
    }
    else{
        //UPDATE `parametros` SET `valor`='[value-3]' WHERE `clave` = 'PASSWORD_FIRMA_DIGITAL'
        $sql = "UPDATE `parametros` SET `valor`='$clave' WHERE `clave` = 'PASSWORD_FIRMA_DIGITAL'";
        $result = mysqli_query($link, $sql);
    }

    // Definir la ruta donde se guardará
    $savePath = __DIR__ . '/../../storage/firma_digital/';
    
    // Crear el directorio si no existe
    if (!file_exists($savePath)) {
        mkdir($savePath, 0755, true);
    }

    // Guardar el archivo .p12
    $p12FileName = 'certificado.p12';
    $p12FilePath = $savePath . $p12FileName;
    file_put_contents($p12FilePath, $fileContent);

    // Extraer la clave privada y el certificado
    $privateKey = $certificates['pkey'];
    $certificate = $certificates['cert'];
    $caCert = isset($certificates['extracerts']) ? implode("\n", $certificates['extracerts']) : null;

    // Guardar los archivos .pem
    file_put_contents($savePath . 'private_key.pem', $privateKey);
    file_put_contents($savePath . 'certificate.pem', $certificate);

    if ($caCert) {
        file_put_contents($savePath . 'ca_chain.pem', $caCert);
    }

    // Obtener información del certificado
    $certinfo = openssl_x509_parse($certificate);

    // Mostrar la información del certificado en HTML
    echo "=========================<br>";
    echo "   INFORMACIÓN DEL CERTIFICADO<br>";
    echo "=========================<br>";
    echo "Titular (CN):       " . ($certinfo['subject']['CN'] ?? '-') . "<br>";
    echo "NIT (serialNumber): " . ($certinfo['subject']['serialNumber'] ?? '-') . "<br>";
    echo "Entidad:            " . ($certinfo['subject']['O'] ?? '-') . "<br>";
    echo "País:               " . ($certinfo['subject']['C'] ?? '-') . "<br>";
    echo "Correo:             " . ($certinfo['extensions']['subjectAltName'] ?? '-') . "<br>";
    echo "Válido desde:       " . date('Y-m-d H:i:s', $certinfo['validFrom_time_t']) . "<br>";
    echo "Válido hasta:       " . date('Y-m-d H:i:s', $certinfo['validTo_time_t']) . "<br>";
    echo "Emisor:             " . ($certinfo['issuer']['CN'] ?? '-') . "<br>";
    echo "-------------------------<br>";
    echo "Algoritmo Firma:    " . ($certinfo['signatureTypeLN'] ?? '-') . "<br>";
    echo "Uso de clave:       " . ($certinfo['extensions']['keyUsage'] ?? '-') . "<br>";
    echo "-------------------------<br>";
    echo "Número de serie:    " . ($certinfo['serialNumber'] ?? '-') . "<br>";
    echo "Serial Hex:         " . ($certinfo['serialNumberHex'] ?? '-') . "<br>";
    echo "=========================<br>";
    echo "✅ Archivos generados correctamente en: $savePath<br>";
}

?>