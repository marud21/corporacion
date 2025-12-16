<?php
// Script para cobrar la mensualidad a todos los socios activos
require_once __DIR__ . '/models/Socio.php';
require_once __DIR__ . '/includes/database/Database.php';

// Ruta del log
$logFile = __DIR__ . '/logs/php_error.log';
function log_debug($msg) {
    global $logFile;
    error_log(date('[Y-m-d H:i:s] ') . $msg . "\n", 3, $logFile);
}

// Confirmación doble solo si se ejecuta en CLI
if (php_sapi_name() === 'cli') {
    function confirmar($mensaje) {
        $respuesta = readline($mensaje . " (escriba SI para continuar): ");
        return strtoupper(trim($respuesta)) === 'SI';
    }
    if (!confirmar('¿Está seguro de que desea ejecutar el cobro mensual a todos los socios activos?')) {
        echo "Operación cancelada.\n";
        exit;
    }
    if (!confirmar('CONFIRME nuevamente: ¿Está completamente seguro de ejecutar el cobro mensual?')) {
        echo "Operación cancelada.\n";
        exit;
    }
}

// Solo permitir POST por HTTP
if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Solo se permite POST.'
    ]);
    exit;
}

log_debug('Inicio de cobro mensual. Método: ' . $_SERVER['REQUEST_METHOD']);

try {
    $db = (new Database())->getConnection();
    log_debug('Conexión a BD exitosa');
    $mensualidad = 45000;
    $fechaHoy = date('Y-m-d');
    $mesActual = date('Y-m');
    
    // Seleccionar socios activos (id, saldo, fecha_afiliacion)
    $stmt = $db->prepare("SELECT id, saldo, fecha_afiliacion FROM socios WHERE estado = 'activo'");
    $stmt->execute();
    $socios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    log_debug('Socios activos encontrados: ' . count($socios));
    if (!$socios) {
        log_debug('No hay socios activos para cobrar.');
        echo json_encode(['success' => false, 'message' => 'No hay socios activos para cobrar.']);
        exit;
    }

    // Preparar consulta de actualización de saldo
    $update = $db->prepare("UPDATE socios SET saldo = saldo + :mensualidad WHERE id = :id");
    $checkPago = $db->prepare("SELECT COUNT(*) FROM pagos WHERE socio_id = :id AND concepto = 'Mensualidad' AND DATE_FORMAT(fecha, '%Y-%m') = :mes");

    $cobrados = 0;
    $omitidos = 0;
    $omitidosAfiliacion = 0;
    foreach ($socios as $socio) {
        log_debug('Procesando socio ID: ' . $socio['id']);
        // Verificar si ya se cobró este mes
        $checkPago->execute([
            ':id' => $socio['id'],
            ':mes' => $mesActual
        ]);
        $yaCobrado = $checkPago->fetchColumn();
        if ($yaCobrado) {
            log_debug('Socio ' . $socio['id'] . ' ya cobrado este mes.');
            $omitidos++;
            continue;
        }
        // Verificar si el socio se afilió este mes
        $fechaAfiliacion = $socio['fecha_afiliacion'];
        if ($fechaAfiliacion && date('Y-m', strtotime($fechaAfiliacion)) === $mesActual) {
            log_debug('Socio ' . $socio['id'] . ' afiliado este mes, omitido.');
            $omitidosAfiliacion++;
            continue;
        }
        // Cobrar mensualidad (solo suma al saldo, NO inserta en pagos)
        $update->execute([
            ':mensualidad' => $mensualidad,
            ':id' => $socio['id']
        ]);
        log_debug('Cobro aplicado a socio ' . $socio['id']);
        $cobrados++;
    }
    log_debug("Cobro mensual finalizado. Cobrados: $cobrados, Omitidos: $omitidos, OmitidosAfiliacion: $omitidosAfiliacion");

    if (php_sapi_name() === 'cli') {
        echo "Cobro mensual realizado exitosamente.\n";
        echo "Socios activos cobrados: $cobrados\n";
        echo "Socios omitidos (ya cobrados este mes): $omitidos\n";
        echo "Socios omitidos (afiliados este mes): $omitidosAfiliacion\n";
        echo "Monto por socio: $mensualidad\n";
        echo "Fecha: $fechaHoy\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cobrados' => $cobrados,
            'omitidos' => $omitidos,
            'omitidosAfiliacion' => $omitidosAfiliacion,
            'monto' => $mensualidad,
            'fecha' => $fechaHoy
        ]);
    }
} catch (Exception $e) {
    log_debug('ERROR: ' . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Error al realizar el cobro mensual: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al realizar el cobro mensual: ' . $e->getMessage()
        ]);
        exit;
    }
}
