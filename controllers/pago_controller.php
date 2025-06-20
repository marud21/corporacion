<?php
// Configuración de encabezados para JSON
header('Content-Type: application/json');

// Incluir el modelo de Pago
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/Database.php';

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Crear instancia del modelo Pago
$pagoModel = new Pago($db);

// Obtener el método de la petición
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'GET':
            // Obtener el ID del socio si se proporciona
            $socio_id = isset($_GET['socio_id']) ? (int)$_GET['socio_id'] : null;
            $filtros = [
                'socio_id' => $socio_id,
                'fecha_desde' => $_GET['fecha_desde'] ?? null,
                'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
                'concepto' => $_GET['concepto'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];
            
            // Obtener el historial de pagos
            $pagos = $pagoModel->getHistorial($filtros);
            $total = $pagoModel->getTotalRegistros($filtros);
            
            $response = [
                'success' => true,
                'data' => $pagos,
                'total' => $total,
                'filtros' => $filtros
            ];
            break;
            
        case 'POST':
            // Obtener los datos del pago del cuerpo de la petición
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['socio_id']) || empty($data['monto']) || empty($data['concepto'])) {
                throw new Exception('Faltan campos obligatorios');
            }
            
            // Validar monto
            $monto = (float)$data['monto'];
            if ($monto <= 0) {
                throw new Exception('El monto debe ser mayor a cero');
            }
            
            // Crear el pago
            $pago_id = $pagoModel->create([
                'socio_id' => (int)$data['socio_id'],
                'monto' => $monto,
                'concepto' => $data['concepto'],
                'fecha' => $data['fecha'] ?? date('Y-m-d H:i:s'),
                'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
                'referencia' => $data['referencia'] ?? null,
                'observaciones' => $data['observaciones'] ?? null
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Pago registrado correctamente',
                'pago_id' => $pago_id
            ];
            break;
            
        default:
            http_response_code(405); // Método no permitido
            $response['message'] = 'Método no permitido';
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Error en pago_controller: ' . $e->getMessage());
}

// Enviar la respuesta como JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
