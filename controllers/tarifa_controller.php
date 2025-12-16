<?php
/**
 * Controlador para gestionar tarifas
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/Tarifa.php';
require_once __DIR__ . '/../includes/database/Database.php';

$database = new Database();
$db = $database->getConnection();
$tarifaModel = new Tarifa($db);

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => ''];

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? null;
            
            if ($action === 'resumen') {
                // Obtener resumen de tarifas
                $resumen = $tarifaModel->getResumen();
                $response = [
                    'success' => true,
                    'data' => $resumen
                ];
            } else {
                // Obtener todas las tarifas
                $tarifas = $tarifaModel->getAll();
                $response = [
                    'success' => true,
                    'data' => $tarifas
                ];
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || !isset($data['monto'])) {
                throw new Exception('Faltan datos requeridos: id y monto');
            }
            
            $tarifaModel->update($data['id'], [
                'monto' => (float)$data['monto'],
                'descripcion' => $data['descripcion'] ?? '',
                'activa' => $data['activa'] ?? 1
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Tarifa actualizada correctamente'
            ];
            break;
            
        default:
            http_response_code(405);
            $response['message'] = 'Método no permitido';
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Error en tarifa_controller: ' . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
