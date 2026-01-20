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
    case "listaAranceles":
        listaAranceles();
        break;
    case 'formNuevoArancel':
        formNuevoArancel();
        break;
    case 'registrarArancel':
        registrarArancel();
        break;
    case 'formEditarArancel':
        formEditarArancel();
        break;
    case 'editarArancel':
        editarArancel();
        break;
    default:
        echo json_encode(["estado" => "ERROR", "mensaje" => "Funcion no reconocida."]);
        break;
}

function listaAranceles(){
    global $link;
    global $input;

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Lista de aranceles</b>";
                echo "</div>";
                echo "<div class='card-body'>";
                    echo "<table class='table table-bordered'>";
                        echo "<thead>";
                            echo "<tr>";
                                echo "<th>Código</th>";
                                echo "<th>Descripción</th>";
                                echo "<th>Precio</th>";
                                echo "<th>Acciones</th>";
                            echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                            $sql = "SELECT `idArancel`, `codigo`, `descripcion`, `precio` FROM `aranceles` ORDER BY `codigo` ASC";
                            $result = mysqli_query($link, $sql);
                            while ($row = mysqli_fetch_array($result)) {
                                echo "<tr>";
                                    echo "<td>" . $row['codigo'] . "</td>";
                                    echo "<td>" . $row['descripcion'] . "</td>";
                                    echo "<td>" . number_format($row['precio'], 2, '.', ',') . " Bs.</td>";
                                    echo "<td class='text-center'>";
                                        echo "<div class='btn-group'>";
                                            echo "<button class='btn btn-xs btn-primary btnFormEditarArancel' id='" . $row['idArancel'] . "' title='Editar Arancel'><i class='fas fa-edit'></i></button>";
                                        echo "</div>";
                                    echo "</td>";
                                echo "</tr>";
                            }

                        echo "</tbody>";
                    echo "</table>";


                echo "</div>";
                echo "<div class='card-footer text-right py-2 text-center'>";
                    echo "<button class='btn btn-primary' id='btnFormNuevoArancel'><i class='fas fa-plus'></i> Nuevo arancel</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function formNuevoArancel(){
    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Nuevo Arancel</b>";
                echo "</div>";
                echo "<div class='card-body row'>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='codigo' class='form-label'>Código</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-barcode'></i></span>";
                            echo "<input type='text' id='codigo' name='codigo' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='precio' class='form-label'>Precio</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-dollar-sign'></i></span>";
                            echo "<input type='number' id='precio' name='precio' class='form-control' step='0.01' min='0'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-12 mb-2'>";
                        echo "<label for='descripcion' class='form-label'>Descripción</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-align-left'></i></span>";
                            echo "<input type='text' id='descripcion' name='descripcion' class='form-control text-uppercase'>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
                echo "<div class='card-footer text-center py-2'>";
                    echo "<button class='btn btn-primary' id='btnRegistrarArancel'><i class='fas fa-save'></i> Registrar Arancel</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function registrarArancel(){
    global $link;
    global $input;

    $datosArancel = $input['datosArancel'];
    $codigo = $datosArancel['codigo'];
    $descripcion = $datosArancel['descripcion'];
    $precio = $datosArancel['precio'];

    $conRegistroArancel = mysqli_query($link, "INSERT INTO `aranceles`(`codigo`, `descripcion`, `precio`) 
                                                VALUES ('$codigo','$descripcion','$precio')") or die(mysqli_error($link));
    if ($conRegistroArancel) {
        echo "OK";
    } else {
        echo "ERROR: " . mysqli_error($link);
    }
}

function formEditarArancel(){
    global $link;
    global $input;

    $idArancel = $input['idArancel'];

    $conArancel = mysqli_query($link, "SELECT `idArancel`, `codigo`, `descripcion`, `precio` 
                                        FROM `aranceles` WHERE `idArancel` = '$idArancel'") or die(mysqli_error($link));
    if(mysqli_num_rows($conArancel) > 0){
        $rowArancel = mysqli_fetch_array($conArancel);
        $codigo = $rowArancel['codigo'];
        $descripcion = $rowArancel['descripcion'];
        $precio = $rowArancel['precio'];
    }

    echo "<div class='row'>";
        echo "<div class='col-md-12'>";
            echo "<div class='card border'>";
                echo "<div class='card-header'>";
                    echo "<b>Editar Arancel</b>";
                echo "</div>";
                echo "<div class='card-body row'>";
                    
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='codigo' class='form-label'>Código</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-barcode'></i></span>";
                            echo "<input type='text' id='codigo' name='codigo' class='form-control text-uppercase' value='$codigo'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-6 mb-2'>";
                        echo "<label for='precio' class='form-label'>Precio</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-dollar-sign'></i></span>";
                            echo "<input type='number' id='precio' name='precio' class='form-control' step='0.01' min='0' value='$precio'>";
                        echo "</div>";
                    echo "</div>";
                    echo "<div class='col-md-12 mb-2'>";
                        echo "<label for='descripcion' class='form-label'>Descripción</label>";
                        echo "<div class='input-group'>";
                            echo "<span class='input-group-text'><i class='fa fa-align-left'></i></span>";
                            echo "<input type='text' id='descripcion' name='descripcion' class='form-control text-uppercase' value='$descripcion'>";
                        echo "</div>";
                    echo "</div>";

                echo "</div>";
                echo "<div class='card-footer text-center py-2'>";
                    echo "<button class='btn btn-primary' id='btnEditarArancel'><i class='fas fa-save'></i> Guardar Cambios</button>";
                echo "</div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
}

function editarArancel(){
    global $link;
    global $input;

    $datosArancel = $input['datosArancel'];
    $idArancel = $datosArancel['idArancel'];
    $codigo = $datosArancel['codigo'];
    $descripcion = $datosArancel['descripcion'];
    $precio = $datosArancel['precio'];

    $conUpdate = mysqli_query($link, "UPDATE `aranceles` SET `codigo`='$codigo',`descripcion`='$descripcion',`precio`='$precio' WHERE `idArancel` = '$idArancel'") or die(mysqli_error($link));
    if ($conUpdate) {
        echo "OK";
    } else {
        echo "ERROR: " . mysqli_error($link);
    }
}

?>