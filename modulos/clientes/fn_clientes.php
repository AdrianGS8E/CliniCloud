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
    case "listaClientes":
        listaClientes();
        break;
    case 'formNuevoCliente':
        formNuevoCliente();
        break;
    case 'registrarNuevoCliente':
        registrarNuevoCliente();
        break;
    case 'formEditarCliente':
        formEditarCliente();
        break;
    case 'editarCliente':
        editarCliente();
        break;
    
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function listaClientes(){
    global $link;
    global $input;

    date_default_timezone_set('America/La_Paz');

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Lista de Clientes</b>";
                echo "</div>";
                echo "<div class='card-body table-responsive'>";

                    echo "<table class='table table-bordered table-striped table-hover' id='tableClientes'>";
                        echo "<thead>";
                            echo "<tr>";
                                echo "<th>Nombre/Razón Social</th>";
                                echo "<th>Tipo Documento</th>";
                                echo "<th>Número Documento</th>";
                                echo "<th>Complemento</th>";
                                echo "<th>Código Excepción</th>";
                                echo "<th>Correo Electrónico</th>";
                                echo "<th>Celular</th>";
                                echo "<th>Acciones</th>";
                            echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                            // El tbody se llenará dinámicamente por DataTables con server-side processing
                        echo "</tbody>";
                    echo "</table>";

                    echo "<div class='row'>";
                        echo "<div class='col-md-12 text-center'>";
                            echo "<button class='btn btn-primary btn-sm m-2' id='btnFormNuevoCliente'><i class='fas fa-plus'></i> Nuevo Cliente</button>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function formNuevoCliente(){
    global $link;
    global $input;

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Nuevo Cliente</b>";
                echo "</div>";
                echo "<div class='card-body'>";
                    echo "<div class='row'>";
                        echo "<div class='col-md-12 mb-2'>";
                            echo "<label for='nombreRazonSocial' class='form-label'>Nombre/Razón Social</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                                echo "<input type='text' id='nombreRazonSocial' name='nombreRazonSocial' class='form-control' value='' required>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='codigoTipoDocumentoIdentidad' class='form-label'>Tipo de Documento</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-id-card'></i></span>";
                                echo "<select id='codigoTipoDocumentoIdentidad' name='codigoTipoDocumentoIdentidad' class='form-select' required>";
                                    echo "<option value=''>Seleccione tipo de documento</option>";
                                    //SELECT `idParametrica`, `codigoSucursal`, `codigoPuntoVenta`, `codigoClasificador`, `descripcion`, `tipoParametrica` FROM `sync_parametricas` WHERE `tipoParametrica` = 'ParametricaTipoDocumentoIdentidad' GROUP BY `codigoClasificador` ORDER BY `codigoClasificador` ASC
                                    $conTipoDocumentoIdentidad = mysqli_query($link, "SELECT `idParametrica`, `codigoSucursal`, `codigoPuntoVenta`, `codigoClasificador`, `descripcion`, `tipoParametrica` FROM `sync_parametricas` WHERE `tipoParametrica` = 'ParametricaTipoDocumentoIdentidad' GROUP BY `codigoClasificador` ORDER BY `codigoClasificador` ASC")or die(mysqli_error($link));
                                    if(mysqli_num_rows($conTipoDocumentoIdentidad) > 0){
                                        while($rowTipoDocumentoIdentidad = mysqli_fetch_array($conTipoDocumentoIdentidad)){
                                            echo "<option value='".$rowTipoDocumentoIdentidad['codigoClasificador']."'>".$rowTipoDocumentoIdentidad['descripcion']."</option>";
                                        }
                                    }
                                echo "</select>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='numeroDocumento' class='form-label'>Número de Documento</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-hashtag'></i></span>";
                                echo "<input type='text' id='numeroDocumento' name='numeroDocumento' class='form-control' value='' required>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='complemento' class='form-label'>Complemento</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-plus'></i></span>";
                                echo "<input type='text' id='complemento' name='complemento' class='form-control' value=''>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='codigoExcepcion' class='form-label'>Código de Excepción</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-exclamation-triangle'></i></span>";
                                echo "<select id='codigoExcepcion' name='codigoExcepcion' class='form-select'>";
                                    echo "<option value='0'>0 - Sin Excepción</option>";
                                    echo "<option value='1'>1 - Excepción</option>";
                                echo "</select>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='correoElectronico' class='form-label'>Correo Electrónico</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-envelope'></i></span>";
                                echo "<input type='email' id='correoElectronico' name='correoElectronico' class='form-control' value=''>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='celular' class='form-label'>Celular</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-phone'></i></span>";
                                echo "<input type='text' id='celular' name='celular' class='form-control' value=''>";
                            echo "</div>";
                        echo "</div>";

                    echo "</div>";
                    
                echo "</div>";
                echo "<div class='card-footer text-right py-2 text-center'>";
                    echo "<button class='btn btn-primary' id='btnRegistrarNuevoCliente'><i class='fas fa-plus'></i> Registrar Cliente</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function registrarNuevoCliente(){
    global $link;
    global $input;

    $datosCliente = $input['datosCliente'];

    $nombreRazonSocial = $datosCliente['nombreRazonSocial'];
    $codigoTipoDocumentoIdentidad = $datosCliente['codigoTipoDocumentoIdentidad'];
    $numeroDocumento = $datosCliente['numeroDocumento'];
    $complemento = $datosCliente['complemento'];
    $codigoExcepcion = $datosCliente['codigoExcepcion'];
    $correoElectronico = $datosCliente['correoElectronico'];
    $celular = $datosCliente['celular'];

    $conCliente = mysqli_query($link, "INSERT INTO `clientes`(`nombreRazonSocial`, `codigoTipoDocumentoIdentidad`, `numeroDocumento`, `complemento`, `codigoExcepcion`, `correoElectronico`, `celular`) VALUES ('$nombreRazonSocial', '$codigoTipoDocumentoIdentidad', '$numeroDocumento', '$complemento', '$codigoExcepcion', '$correoElectronico', '$celular')")or die(mysqli_error($link));
    if($conCliente){
        echo json_encode(["estado" => "OK", "mensaje" => "Cliente registrado correctamente"]);
    }
    else{
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al registrar el cliente"]);
    }
}

function formEditarCliente(){
    global $link;
    global $input;

    $idCliente = $input['idCliente'];

    $conCliente = mysqli_query($link, "SELECT `idCliente`, `nombreRazonSocial`, `codigoTipoDocumentoIdentidad`, `numeroDocumento`, `complemento`, `codigoExcepcion`, `correoElectronico`, `celular` FROM `clientes` WHERE `idCliente` = '$idCliente'")or die(mysqli_error($link));
    if(mysqli_num_rows($conCliente) > 0){
        $rowCliente = mysqli_fetch_array($conCliente);
        $nombreRazonSocial = $rowCliente['nombreRazonSocial'];
        $codigoTipoDocumentoIdentidad = $rowCliente['codigoTipoDocumentoIdentidad'];
        $numeroDocumento = $rowCliente['numeroDocumento'];
        $complemento = $rowCliente['complemento'];
        $codigoExcepcion = $rowCliente['codigoExcepcion'];
        $correoElectronico = $rowCliente['correoElectronico'];
        $celular = $rowCliente['celular'];
    }

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Nuevo Cliente</b>";
                echo "</div>";
                echo "<div class='card-body'>";
                    echo "<div class='row'>";
                        echo "<div class='col-md-12 mb-2'>";
                            echo "<label for='nombreRazonSocial' class='form-label'>Nombre/Razón Social</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-user'></i></span>";
                                echo "<input type='text' id='nombreRazonSocial' name='nombreRazonSocial' class='form-control' value='".$nombreRazonSocial."'>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='codigoTipoDocumentoIdentidad' class='form-label'>Tipo de Documento</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-id-card'></i></span>";
                                echo "<select id='codigoTipoDocumentoIdentidad' name='codigoTipoDocumentoIdentidad' class='form-select' required>";
                                    echo "<option value=''>Seleccione tipo de documento</option>";
                                    //SELECT `idParametrica`, `codigoSucursal`, `codigoPuntoVenta`, `codigoClasificador`, `descripcion`, `tipoParametrica` FROM `sync_parametricas` WHERE `tipoParametrica` = 'ParametricaTipoDocumentoIdentidad' GROUP BY `codigoClasificador` ORDER BY `codigoClasificador` ASC
                                    $conTipoDocumentoIdentidad = mysqli_query($link, "SELECT `idParametrica`, `codigoSucursal`, `codigoPuntoVenta`, `codigoClasificador`, `descripcion`, `tipoParametrica` FROM `sync_parametricas` WHERE `tipoParametrica` = 'ParametricaTipoDocumentoIdentidad' GROUP BY `codigoClasificador` ORDER BY `codigoClasificador` ASC")or die(mysqli_error($link));
                                    if(mysqli_num_rows($conTipoDocumentoIdentidad) > 0){
                                        while($rowTipoDocumentoIdentidad = mysqli_fetch_array($conTipoDocumentoIdentidad)){
                                            echo "<option value='".$rowTipoDocumentoIdentidad['codigoClasificador']."' ".($codigoTipoDocumentoIdentidad == $rowTipoDocumentoIdentidad['codigoClasificador'] ? "selected" : "").">".$rowTipoDocumentoIdentidad['descripcion']."</option>";
                                        }
                                    }
                                echo "</select>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='numeroDocumento' class='form-label'>Número de Documento</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-hashtag'></i></span>";
                                echo "<input type='text' id='numeroDocumento' name='numeroDocumento' class='form-control' value='".$numeroDocumento."'>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='complemento' class='form-label'>Complemento</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-plus'></i></span>";
                                echo "<input type='text' id='complemento' name='complemento' class='form-control' value='".$complemento."'>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='codigoExcepcion' class='form-label'>Código de Excepción</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-exclamation-triangle'></i></span>";
                                echo "<select id='codigoExcepcion' name='codigoExcepcion' class='form-select'>";
                                    echo "<option value='0'>0 - Sin Excepción</option>";
                                    echo "<option value='1'>1 - Excepción</option>";
                                echo "</select>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='correoElectronico' class='form-label'>Correo Electrónico</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-envelope'></i></span>";
                                echo "<input type='email' id='correoElectronico' name='correoElectronico' class='form-control' value='".$correoElectronico."'>";
                            echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-4 mb-2'>";
                            echo "<label for='celular' class='form-label'>Celular</label>";
                            echo "<div class='input-group'>";
                                echo "<span class='input-group-text'><i class='fa fa-phone'></i></span>";
                                echo "<input type='text' id='celular' name='celular' class='form-control' value='".$celular."'>";
                            echo "</div>";
                        echo "</div>";

                    echo "</div>";
                    
                echo "</div>";
                echo "<div class='card-footer text-right py-2 text-center'>";
                    echo "<button class='btn btn-primary' id='btnActualizarCliente'><i class='fas fa-save'></i> Actualizar Cliente</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function editarCliente(){
    global $link;
    global $input;

    $datosCliente = $input['datosCliente'];

    $idCliente = $datosCliente['idCliente'];
    $nombreRazonSocial = $datosCliente['nombreRazonSocial'];
    $codigoTipoDocumentoIdentidad = $datosCliente['codigoTipoDocumentoIdentidad'];
    $numeroDocumento = $datosCliente['numeroDocumento'];
    $complemento = $datosCliente['complemento'];
    $codigoExcepcion = $datosCliente['codigoExcepcion'];
    $correoElectronico = $datosCliente['correoElectronico'];
    $celular = $datosCliente['celular'];

    $conCliente = mysqli_query($link, "UPDATE `clientes` SET `nombreRazonSocial` = '$nombreRazonSocial', `codigoTipoDocumentoIdentidad` = '$codigoTipoDocumentoIdentidad', `numeroDocumento` = '$numeroDocumento', `complemento` = '$complemento', `codigoExcepcion` = '$codigoExcepcion', `correoElectronico` = '$correoElectronico', `celular` = '$celular' WHERE `idCliente` = '$idCliente'")or die(mysqli_error($link));
    if($conCliente){
        echo json_encode(["estado" => "OK", "mensaje" => "Cliente actualizado correctamente"]);
    }
    else{
        echo json_encode(["estado" => "ERROR", "mensaje" => "Error al actualizar el cliente"]);
    }
}

?>