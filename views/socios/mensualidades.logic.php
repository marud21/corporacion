<?php
// Lógica de mensualidades: consultas, cálculos y variables PHP
// Este archivo debe ser incluido al inicio de mensualidades.php

include_once '../../includes/header.php';
require_once '../../models/Socio.php';
require_once '../../includes/database/Database.php';

$db = (new Database())->getConnection();

// Obtener socios activos (solo columnas existentes)
$stmtSocios = $db->prepare("SELECT id FROM socios WHERE estado = 'activo'");
$stmtSocios->execute();
$socios = $stmtSocios->fetchAll(PDO::FETCH_ASSOC);

// Obtener pagos de mensualidad (concepto = 'Mensualidad')
$stmtPagos = $db->prepare("SELECT p.id, p.socio_id, p.monto, p.fecha, p.concepto FROM pagos p WHERE p.concepto = 'Mensualidad' ORDER BY p.fecha DESC");
$stmtPagos->execute();
$pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los socios y sus saldos y nombre (desde usuarios)
$stmtAllSocios = $db->prepare("SELECT s.id, s.saldo, u.nombre FROM socios s LEFT JOIN usuarios u ON s.id = u.id");
$stmtAllSocios->execute();
$allSocios = $stmtAllSocios->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de mensualidades agrupado por socio y mes
$stmtHistorial = $db->prepare("SELECT socio_id, DATE_FORMAT(fecha, '%Y-%m') as mes_cobro FROM pagos WHERE concepto = 'Mensualidad' GROUP BY socio_id, mes_cobro ORDER BY socio_id, mes_cobro DESC");
$stmtHistorial->execute();
$historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

// Obtener fechas de afiliación de los socios
$sociosAfiliacion = [];
$stmtFechasAfiliacion = $db->prepare("SELECT id, fecha_afiliacion FROM socios");
$stmtFechasAfiliacion->execute();
while ($row = $stmtFechasAfiliacion->fetch(PDO::FETCH_ASSOC)) {
    $sociosAfiliacion[$row['id']] = $row['fecha_afiliacion'];
}

// Filtrar pagos para NO mostrar el pago inicial de mensualidad (el que coincide con la fecha de afiliación)
$pagosFiltrados = array_filter($pagos, function($pago) use ($sociosAfiliacion) {
    $fechaAfiliacion = $sociosAfiliacion[$pago['socio_id']] ?? null;
    if ($fechaAfiliacion && strpos($pago['fecha'], $fechaAfiliacion) === 0) {
        return false;
    }
    return true;
});

// Calcular el total pagado por cada socio (excluyendo el pago inicial de mensualidad)
$stmtTotalPagado = $db->prepare("SELECT socio_id, monto, fecha, concepto FROM pagos WHERE monto > 0");
$stmtTotalPagado->execute();
$totalesPagados = $stmtTotalPagado->fetchAll(PDO::FETCH_ASSOC);
$totalPagadoPorSocio = [];
foreach ($totalesPagados as $row) {
    $fechaAfiliacion = $sociosAfiliacion[$row['socio_id']] ?? null;
    if ($row['concepto'] === 'Mensualidad' && $fechaAfiliacion && strpos($row['fecha'], $fechaAfiliacion) === 0) {
        continue;
    }
    if (!isset($totalPagadoPorSocio[$row['socio_id']])) {
        $totalPagadoPorSocio[$row['socio_id']] = 0;
    }
    $totalPagadoPorSocio[$row['socio_id']] += $row['monto'];
}

// Calcular el total global pagado por todos los socios
$totalGlobalPagado = array_sum($totalPagadoPorSocio);

// Organizar historial por socio y guardar la fecha exacta de cobro
$historialPorSocio = [];
foreach ($historial as $row) {
    $stmtFecha = $db->prepare("SELECT fecha FROM pagos WHERE socio_id = ? AND concepto = 'Mensualidad' AND DATE_FORMAT(fecha, '%Y-%m') = ? ORDER BY fecha DESC");
    $stmtFecha->execute([$row['socio_id'], $row['mes_cobro']]);
    $fechas = $stmtFecha->fetchAll(PDO::FETCH_COLUMN);
    foreach ($fechas as $fecha) {
        $historialPorSocio[$row['socio_id']][] = $row['mes_cobro'] . ' (' . date('d/m/Y', strtotime($fecha)) . ')';
    }
}
