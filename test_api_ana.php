<?php
// Test completo del API para ver si retorna foto de Ana Díaz

require_once 'controllers/socio_controller.php';

// Simular una solicitud GET a /controllers/socio_controller.php?id=6
$_GET['id'] = '6';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capturar la salida
ob_start();

// Crear instancia del controlador
$controller = new SocioController();

// Ver si tiene el método correcto
if (method_exists($controller, 'index')) {
    // Obtener el JSON que se retorna
    try {
        // Parsear manualmente
        $socio = $controller->show(6);
        echo json_encode([
            'success' => true,
            'data' => $socio
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

$output = ob_get_clean();
echo $output;

echo "<h2>Response JSON (parseable)</h2>";
$json = json_decode($output, true);
echo "<table border='1' cellpadding='10'>";
foreach ($json['data'] as $key => $value) {
    if (is_array($value)) {
        $value = json_encode($value);
    }
    echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
}
echo "</table>";
?>
