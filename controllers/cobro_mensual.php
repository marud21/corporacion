<?php
/**
 * Script para cobrar la mensualidad a todos los socios activos
 * NUEVO SISTEMA: Cobra aniversarios mensuales desde la fecha de ingreso de cada socio
 * 
 * Lógica:
 * - Cada socio se le cobra en el mismo día del mes en que ingresó
 * - No se cobra en el mes de ingreso (primera mensualidad es gratis)
 * - Si el socio ingresó el día 31, se cobra el último día del mes
 */
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Tarifa.php';
require_once __DIR__ . '/../includes/database/Database.php';

// Ruta del log
$logFile = __DIR__ . '/../logs/php_error.log';
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
    
    // Obtener tarifas de la BD
    $tarifaModel = new Tarifa($db);
    $mensualidad = $tarifaModel->getMonto('Mensualidad Activo');
    $mensualidad_lesionado = $tarifaModel->getMonto('Mensualidad Lesionado');
    
    log_debug('Tarifas obtenidas - Activo: $' . number_format($mensualidad, 0, ',', '.') . ', Lesionado: $' . number_format($mensualidad_lesionado, 0, ',', '.'));
    
    $fechaHoy = new DateTime();
    $diaHoy = (int)$fechaHoy->format('d');
    $mesActual = $fechaHoy->format('Y-m');
    
    log_debug('Día de hoy: ' . $diaHoy . ', Mes actual: ' . $mesActual);
    
    // Seleccionar socios ACTIVOS o LESIONADOS (no RETIRADOS)
    $stmt = $db->prepare("
        SELECT s.id, s.saldo, s.estado, s.fecha_afiliacion, u.nombre 
        FROM socios s 
        LEFT JOIN usuarios u ON s.id = u.id 
        WHERE s.estado IN ('activo', 'lesionado')
    ");
    $stmt->execute();
    $socios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    log_debug('Socios activos/lesionados encontrados: ' . count($socios));
    
    if (!$socios) {
        log_debug('No hay socios activos para cobrar.');
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'No hay socios activos para cobrar.'
        ]);
        exit;
    }

    // Preparar consultas
    $insertPago = $db->prepare("
        INSERT INTO pagos (socio_id, monto, concepto, fecha, pagado) 
        VALUES (:socio_id, :monto, 'Mensualidad', :fecha, 0)
    ");
    
    $updateSaldo = $db->prepare("
        UPDATE socios SET saldo = saldo + :monto WHERE id = :id
    ");
    
    $checkPago = $db->prepare("
        SELECT COUNT(*) FROM pagos 
        WHERE socio_id = :id 
        AND concepto = 'Mensualidad' 
        AND DATE_FORMAT(fecha, '%Y-%m') = :mes
    ");

    $cobrados = 0;
    $yaCobrados = 0;
    $sinFechaIngreso = 0;
    $noCorresponde = 0;
    $primeraAfiliacion = 0;
    
    foreach ($socios as $socio) {
        $socioId = $socio['id'];
        $nombreSocio = $socio['nombre'] ?? "ID: $socioId";
        $estadoSocio = $socio['estado'];
        
        // Determinar monto según estado del socio
        $monto = ($estadoSocio === 'lesionado') ? $mensualidad_lesionado : $mensualidad;
        
        log_debug('Procesando socio ID: ' . $socioId . ' (' . $nombreSocio . ', Estado: ' . $estadoSocio . ', Monto: $' . number_format($monto, 0, ',', '.') . ')');
        
        // Verificar que el socio tenga fecha de ingreso
        if (!$socio['fecha_afiliacion']) {
            log_debug('Socio ' . $socioId . ' sin fecha de ingreso, omitido.');
            $sinFechaIngreso++;
            continue;
        }
        
        // Obtener día de ingreso del socio
        $fechaIngreso = new DateTime($socio['fecha_afiliacion']);
        $diaIngreso = (int)$fechaIngreso->format('d');
        $mesIngreso = $fechaIngreso->format('Y-m');
        
        log_debug('Socio ' . $socioId . ': Ingresó el día ' . $diaIngreso . ' del mes ' . $mesIngreso);
        
        // Verificar si el socio ingresó este mes (primer mes no se cobra)
        if ($mesIngreso === $mesActual) {
            log_debug('Socio ' . $socioId . ' ingresó este mes, no se cobra en el mes de ingreso.');
            $primeraAfiliacion++;
            continue;
        }
        
        // Verificar si hoy es aniversario mensual (mismo día de ingreso)
        // Considerar caso especial: si ingresó día 31 pero el mes actual tiene menos días
        $ultimoDiaDelMes = (int)date('t', strtotime($mesActual . '-01'));
        $diaACobruDiaActual = ($diaIngreso > $ultimoDiaDelMes) ? $ultimoDiaDelMes : $diaIngreso;
        
        if ($diaHoy !== $diaACobruDiaActual) {
            log_debug('Socio ' . $socioId . ' - Hoy es día ' . $diaHoy . ', debe cobrarse día ' . $diaACobruDiaActual . '. No corresponde cobro.');
            $noCorresponde++;
            continue;
        }
        
        // Verificar si ya se cobró este mes
        $checkPago->execute([
            ':id' => $socioId,
            ':mes' => $mesActual
        ]);
        $cobrado = $checkPago->fetchColumn();
        
        if ($cobrado) {
            log_debug('Socio ' . $socioId . ' ya cobrado este mes.');
            $yaCobrados++;
            continue;
        }
        
        // Insertar registro de pago
        try {
            $insertPago->execute([
                ':socio_id' => $socioId,
                ':monto' => $monto,
                ':fecha' => $fechaHoy->format('Y-m-d H:i:s')
            ]);
            
            // Actualizar saldo del socio (suma a la deuda)
            $updateSaldo->execute([
                ':monto' => $monto,
                ':id' => $socioId
            ]);
            
            $estadoTexto = ($estadoSocio === 'lesionado') ? '[LESIONADO]' : '[ACTIVO]';
            log_debug('✓ Cobro aplicado a socio ' . $socioId . ' (' . $nombreSocio . ') ' . $estadoTexto . ' - Monto: $' . number_format($monto, 0, ',', '.'));
            $cobrados++;
            
        } catch (Exception $e) {
            log_debug('✗ ERROR al cobrar a socio ' . $socioId . ': ' . $e->getMessage());
            throw $e;
        }
    }
    
    log_debug("═══════════════════════════════════════════════════════════════");
    log_debug("Cobro mensual finalizado");
    log_debug("  ✓ Cobrados: $cobrados");
    log_debug("  ⚠ Ya cobrados este mes: $yaCobrados");
    log_debug("  ⚠ Primer mes (no cobran): $primeraAfiliacion");
    log_debug("  ⚠ No corresponde (no es aniversario): $noCorresponde");
    log_debug("  ⚠ Sin fecha de ingreso: $sinFechaIngreso");
    log_debug("  💰 Monto por socio (ACTIVO): \$$mensualidad");
    log_debug("  💰 Monto por socio (LESIONADO): \$$mensualidad_lesionado");
    log_debug("  📝 Nota: Los socios RETIRADOS no reciben cobro hasta cambiar de estado");
    log_debug("═══════════════════════════════════════════════════════════════");

    if (php_sapi_name() === 'cli') {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "COBRO MENSUAL REALIZADO EXITOSAMENTE\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "✓ Socios cobrados:                    $cobrados\n";
        echo "⚠ Socios ya cobrados este mes:        $yaCobrados\n";
        echo "⚠ Primer mes (no cobran):             $primeraAfiliacion\n";
        echo "⚠ No corresponde (no es aniversario): $noCorresponde\n";
        echo "⚠ Sin fecha de ingreso:               $sinFechaIngreso\n";
        echo "💰 Monto por socio (ACTIVO):          \$$mensualidad\n";
        echo "💰 Monto por socio (LESIONADO):       \$$mensualidad_lesionado\n";
        echo "📝 Nota: Los socios RETIRADOS no reciben cobro hasta cambiar de estado\n";
        echo "Fecha de ejecución:                   " . $fechaHoy->format('Y-m-d H:i:s') . "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
    } else {
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'cobrados' => $cobrados,
            'ya_cobrados' => $yaCobrados,
            'primer_mes' => $primeraAfiliacion,
            'no_corresponde' => $noCorresponde,
            'sin_fecha_ingreso' => $sinFechaIngreso,
            'monto_activo' => $mensualidad,
            'monto_lesionado' => $mensualidad_lesionado,
            'fecha' => $fechaHoy->format('Y-m-d H:i:s'),
            'message' => "Cobro mensual completado: $cobrados socios cobrados (Activos: \$$mensualidad, Lesionados: \$$mensualidad_lesionado)"
        ]);
    }
    
} catch (Exception $e) {
    log_debug('✗ ERROR GENERAL: ' . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "✗ Error al realizar el cobro mensual: " . $e->getMessage() . "\n";
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
