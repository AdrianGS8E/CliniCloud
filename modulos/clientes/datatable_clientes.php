<?php
session_start();

// SI NO HAY SESIÓN, DETENER TODO ANTES DE INCLUIR O PROCESAR NADA
if (!isset($_SESSION['idUsuario_clinicloud'])) {
    header('Content-Type: application/json');
    echo json_encode(['sesion' => 'cerrada']);
    exit;
}

require_once "../../config_db_mysql.php";

// Configurar zona horaria
date_default_timezone_set('America/La_Paz');

// Parámetros de DataTables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'ASC';

// Columnas de la tabla
$columns = array(
    'nombreRazonSocial',
    'codigoTipoDocumentoIdentidad',
    'numeroDocumento',
    'complemento',
    'codigoExcepcion',
    'correoElectronico',
    'celular'
);

// Nombre de la columna para ordenar
$orderBy = $columns[$orderColumn];

// Construir la consulta base
$query = "SELECT `idCliente`, `nombreRazonSocial`, `codigoTipoDocumentoIdentidad`, `numeroDocumento`, `complemento`, `codigoExcepcion`, `correoElectronico`, `celular` FROM `clientes`";

// Agregar condiciones de búsqueda
$where = "";
if (!empty($search)) {
    $search = mysqli_real_escape_string($link, $search);
    $where = " WHERE (
        `nombreRazonSocial` LIKE '%$search%' OR
        `codigoTipoDocumentoIdentidad` LIKE '%$search%' OR
        `numeroDocumento` LIKE '%$search%' OR
        `complemento` LIKE '%$search%' OR
        `codigoExcepcion` LIKE '%$search%' OR
        `correoElectronico` LIKE '%$search%' OR
        `celular` LIKE '%$search%'
    )";
}

// Contar total de registros sin filtros
$countQuery = "SELECT COUNT(*) as total FROM `clientes`";
$countResult = mysqli_query($link, $countQuery);
$countRow = mysqli_fetch_assoc($countResult);
$recordsTotal = $countRow['total'];

// Contar registros con filtros de búsqueda
$countFilteredQuery = "SELECT COUNT(*) as total FROM `clientes`" . $where;
$countFilteredResult = mysqli_query($link, $countFilteredQuery);
$countFilteredRow = mysqli_fetch_assoc($countFilteredResult);
$recordsFiltered = $countFilteredRow['total'];

// Consulta principal con paginación y ordenamiento
$query .= $where . " ORDER BY `$orderBy` $orderDir LIMIT $start, $length";

$result = mysqli_query($link, $query);

// Preparar los datos
$data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = array(
        $row['nombreRazonSocial'],
        $row['codigoTipoDocumentoIdentidad'],
        $row['numeroDocumento'],
        $row['complemento'],
        $row['codigoExcepcion'],
        $row['correoElectronico'],
        $row['celular'],
        '<button class="btn btn-primary btn-sm btnEditarCliente" id="' . $row['idCliente'] . '"><i class="fas fa-edit"></i></button>'
    );
}

// Respuesta en formato JSON para DataTables
$response = array(
    "draw" => $draw,
    "recordsTotal" => $recordsTotal,
    "recordsFiltered" => $recordsFiltered,
    "data" => $data
);

header('Content-Type: application/json');
echo json_encode($response);
?>

