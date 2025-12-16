<?php
/**
 * Lógica de mensualidades: NUEVO SISTEMA
 * Cada socio paga mensualidad en el aniversario de su fecha de ingreso
 * 
 * Características:
 * - Primera mensualidad se cobra al mes después del ingreso (mismo día)
 * - Se cobra solo si corresponde el aniversario mensual
 * - Se pueden revisar próximos cobros estimados
 */

include_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../includes/database/Database.php';

$db = (new Database())->getConnection();

// Obtener socios activos
$stmtSocios = $db->prepare("SELECT id FROM socios WHERE estado = 'activo'");
$stmtSocios->execute();
$socios = $stmtSocios->fetchAll(PDO::FETCH_ASSOC);

// Obtener pagos de mensualidad con nombre del socio
$stmtPagos = $db->prepare("
    SELECT p.id, p.socio_id, u.nombre, p.monto, p.fecha, p.concepto 
    FROM pagos p 
    LEFT JOIN usuarios u ON p.socio_id = u.id
    WHERE p.concepto = 'Mensualidad' 
    ORDER BY p.fecha DESC
");
$stmtPagos->execute();
$pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los socios con datos relevantes
$stmtAllSocios = $db->prepare("
    SELECT s.id, s.saldo, s.fecha_afiliacion, s.estado, u.nombre, u.documento 
    FROM socios s 
    LEFT JOIN usuarios u ON s.id = u.id
    ORDER BY u.nombre ASC
");
$stmtAllSocios->execute();
$allSocios = $stmtAllSocios->fetchAll(PDO::FETCH_ASSOC);

// Calcular información de mensualidades por socio
$sociosMensualidades = [];
$fechaHoy = new DateTime();
$mesActual = $fechaHoy->format('Y-m');
$diaHoy = (int)$fechaHoy->format('d');

foreach ($allSocios as $socio) {
    $socioId = $socio['id'];
    $sociosMensualidades[$socioId] = [
        'id' => $socioId,
        'nombre' => $socio['nombre'],
        'documento' => $socio['documento'],
        'estado' => $socio['estado'],
        'saldo' => $socio['saldo'],
        'fecha_afiliacion' => $socio['fecha_afiliacion'],
        'proximo_cobro' => null,
        'dias_para_cobro' => null,
        'historial_meses' => [],
        'total_pagado' => 0,
        'meses_cobrados' => 0
    ];
    
    // Si el socio tiene fecha de afiliación, calcular próximo cobro
    if ($socio['fecha_afiliacion']) {
        $fechaIngreso = new DateTime($socio['fecha_afiliacion']);
        $diaIngreso = (int)$fechaIngreso->format('d');
        
        // Calcular próximo cobro (aniversario mensual)
        $proximoCobro = new DateTime();
        $proximoCobro->setDate(
            (int)$fechaHoy->format('Y'),
            (int)$fechaHoy->format('m'),
            1
        );
        
        // Ajustar el día
        $ultimoDiaDelMes = (int)date('t', $proximoCobro->getTimestamp());
        $diaParaCobrar = ($diaIngreso > $ultimoDiaDelMes) ? $ultimoDiaDelMes : $diaIngreso;
        $proximoCobro->setDate(
            (int)$proximoCobro->format('Y'),
            (int)$proximoCobro->format('m'),
            $diaParaCobrar
        );
        
        // Si el próximo cobro ya pasó este mes, calcular para el próximo mes
        if ($proximoCobro < $fechaHoy) {
            $proximoCobro->modify('+1 month');
            $ultimoDiaDelMes = (int)date('t', $proximoCobro->getTimestamp());
            $diaParaCobrar = ($diaIngreso > $ultimoDiaDelMes) ? $ultimoDiaDelMes : $diaIngreso;
            $proximoCobro->setDate(
                (int)$proximoCobro->format('Y'),
                (int)$proximoCobro->format('m'),
                $diaParaCobrar
            );
        }
        
        // No mostrar próximo cobro si es el mes de ingreso
        $mesIngreso = $fechaIngreso->format('Y-m');
        if ($mesIngreso !== $mesActual && $socio['estado'] === 'activo') {
            $sociosMensualidades[$socioId]['proximo_cobro'] = $proximoCobro->format('d/m/Y');
            $intervalo = $fechaHoy->diff($proximoCobro);
            $sociosMensualidades[$socioId]['dias_para_cobro'] = $intervalo->days;
        }
    }
}

// Procesar historial de pagos
$pagosPorSocio = [];
foreach ($pagos as $pago) {
    $socioId = $pago['socio_id'];
    if (!isset($pagosPorSocio[$socioId])) {
        $pagosPorSocio[$socioId] = [];
    }
    $pagosPorSocio[$socioId][] = $pago;
}

// Actualizar información de pagos en cada socio
foreach ($pagosPorSocio as $socioId => $pagosDeSocio) {
    if (isset($sociosMensualidades[$socioId])) {
        foreach ($pagosDeSocio as $pago) {
            $fechaPago = new DateTime($pago['fecha']);
            $mesPago = $fechaPago->format('Y-m');
            $diaPago = $fechaPago->format('d');
            
            // Agregar al historial si no está duplicado
            $mesFormatted = $fechaPago->format('m/Y') . ' (' . $diaPago . ')';
            if (!in_array($mesFormatted, $sociosMensualidades[$socioId]['historial_meses'])) {
                $sociosMensualidades[$socioId]['historial_meses'][] = $mesFormatted;
            }
            
            $sociosMensualidades[$socioId]['total_pagado'] += $pago['monto'];
            $sociosMensualidades[$socioId]['meses_cobrados']++;
        }
    }
}

// Filtrar pagos para mostrar en la tabla (solo últimos 50)
$pagosFiltrados = array_slice($pagos, 0, 50);

// Calcular totales
$totalGlobalPagado = 0;
foreach ($sociosMensualidades as $socioInfo) {
    $totalGlobalPagado += $socioInfo['total_pagado'];
}

// Socios próximos a cobrar (ordenados por días restantes)
$sociosProximoCobro = array_filter($sociosMensualidades, function($s) {
    return $s['proximo_cobro'] !== null && $s['dias_para_cobro'] !== null;
});

usort($sociosProximoCobro, function($a, $b) {
    return $a['dias_para_cobro'] <=> $b['dias_para_cobro'];
});
