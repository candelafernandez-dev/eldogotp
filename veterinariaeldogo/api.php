<?php
// api.php
// Che, este es el backend puro, la API RESTful que maneja todo el laburo con la base de datos.
// URL de uso: /api.php?entidad=clientes o /api.php?entidad=mascotas

require_once 'config.php';

// ======================= SESIÓN Y AUTENTICACIÓN (PARA LA API) =======================
session_start();

// Protección de la API: solo si hay sesión iniciada
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Acceso a la API no autorizado"]);
    exit();
}

// ======================= DB FUNCTIONS =======================
function conectarDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(["error" => "Falló la conexión a MySQL, revise el 'config.php': " . $conn->connect_error]));
    }
    $conn->set_charset(DB_CHARSET);
    return $conn;
}

function query($db, $sql) {
    $result = $db->query($sql);
    if ($result === FALSE) {
        http_response_code(500);
        echo json_encode(["error" => "Error de SQL, revise la consulta: " . $db->error . " | Query: " . $sql]);
        $db->close();
        exit();
    }
    return $result;
}

// ======================= API ROUTING/CRUD =======================

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$entity = isset($_GET['entidad']) ? strtolower($_GET['entidad']) : '';
$db = conectarDB();

$input = file_get_contents('php://input');
$data = json_decode($input, true);

switch ($entity) {
    case 'clientes':
        // Lógica CRUD de CLIENTES
        switch ($method) {
            case 'GET':
                $sql = "SELECT id, nombre, direccion, telefono, activo FROM clientes WHERE activo = 1";
                $result = query($db, $sql);
                $clientes = [];
                while($row = $result->fetch_assoc()) { $clientes[] = $row; }
                echo json_encode($clientes);
                break;
            case 'POST':
                $nombre = $db->real_escape_string($data['nombre']);
                $direccion = $db->real_escape_string($data['direccion']);
                $telefono = $db->real_escape_string($data['telefono']);
                $sql = "INSERT INTO clientes (nombre, direccion, telefono) VALUES ('$nombre', '$direccion', '$telefono')";
                query($db, $sql);
                http_response_code(201);
                echo json_encode(["mensaje" => "Cliente creado con éxito. ID: " . $db->insert_id]);
                break;
            case 'PUT':
                $id = (int)$data['id'];
                if (isset($data['baja']) && $data['baja'] === true) {
                    $sql = "UPDATE clientes SET activo = 0 WHERE id = $id";
                    query($db, $sql);
                    $sql_mascotas = "UPDATE mascotas SET activo = 0, clienteId = NULL WHERE clienteId = $id";
                    query($db, $sql_mascotas);
                    echo json_encode(["mensaje" => "Cliente y sus mascotas dados de baja con éxito"]);
                } else {
                    $nombre = $db->real_escape_string($data['nombre']);
                    $direccion = $db->real_escape_string($data['direccion']);
                    $telefono = $db->real_escape_string($data['telefono']);
                    $sql = "UPDATE clientes SET nombre='$nombre', direccion='$direccion', telefono='$telefono' WHERE id = $id";
                    query($db, $sql);
                    echo json_encode(["mensaje" => "Cliente actualizado con éxito"]);
                }
                break;
            default:
                http_response_code(405); // Method Not Allowed
                echo json_encode(["error" => "Método no soportado para clientes"]);
                break;
        }
        break;

    case 'mascotas':
        // Lógica CRUD de MASCOTAS (similar a la anterior...)
        // [El resto de la lógica de 'mascotas' se mantiene igual que en tu index.php original]
        // ... (Continuación de la lógica de Mascostas)
        switch ($method) {
            case 'GET':
                $sql = "SELECT id, nombre, especie, raza, edad, problemas, salud, clienteId, activo FROM mascotas WHERE activo = 1";
                $result = query($db, $sql);
                $mascotas = [];
                while($row = $result->fetch_assoc()) { $mascotas[] = $row; }
                echo json_encode($mascotas);
                break;
            case 'POST':
                $nombre = $db->real_escape_string($data['nombre']);
                $especie = $db->real_escape_string($data['especie']);
                $raza = $db->real_escape_string($data['raza']);
                $edad = (int)$data['edad'];
                $problemas = $db->real_escape_string($data['problemas']);
                $salud = $db->real_escape_string($data['salud']);
                $clienteId = (int)$data['clienteId'];
                $sql = "INSERT INTO mascotas (nombre, especie, raza, edad, problemas, salud, clienteId) 
                        VALUES ('$nombre', '$especie', '$raza', $edad, '$problemas', '$salud', $clienteId)";
                query($db, $sql);
                http_response_code(201);
                echo json_encode(["mensaje" => "Mascota creada. ID: " . $db->insert_id]);
                break;
            case 'PUT':
                $id = (int)$data['id'];
                if (isset($data['baja']) && $data['baja'] === true) {
                    $sql = "UPDATE mascotas SET activo = 0 WHERE id = $id";
                    query($db, $sql);
                    echo json_encode(["mensaje" => "Mascota dada de baja con éxito"]);
                } elseif (isset($data['cambio_dueno']) && $data['cambio_dueno'] === true) {
                    $nuevo_cliente_id = (int)$data['nuevo_cliente_id'];
                    $sql = "UPDATE mascotas SET clienteId = $nuevo_cliente_id WHERE id = $id";
                    query($db, $sql);
                    echo json_encode(["mensaje" => "Dueño de mascota cambiado con éxito"]);
                } else {
                    $nombre = $db->real_escape_string($data['nombre']);
                    $especie = $db->real_escape_string($data['especie']);
                    $raza = $db->real_escape_string($data['raza']);
                    $edad = (int)$data['edad'];
                    $problemas = $db->real_escape_string($data['problemas']);
                    $salud = $db->real_escape_string($data['salud']);
                    $clienteId = (int)$data['clienteId'];

                    $sql = "UPDATE mascotas SET 
                                nombre='$nombre', especie='$especie', raza='$raza', 
                                edad=$edad, problemas='$problemas', salud='$salud', 
                                clienteId=$clienteId 
                            WHERE id = $id";
                    query($db, $sql);
                    echo json_encode(["mensaje" => "Mascota actualizada con éxito"]);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(["error" => "Método no soportado para mascotas"]);
                break;
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["mensaje" => "Entidad no encontrada, revise la URL"]);
        break;
}

$db->close();
exit();