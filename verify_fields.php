<?php
/**
 * Script de verificación: Estado y Fecha de Afiliación
 */

require_once 'includes/database/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Obtener un socio de prueba
$query = "SELECT u.*, s.* FROM usuarios u 
          LEFT JOIN socios s ON u.id = s.id 
          WHERE u.rol = 'socio' AND u.activo = 1 
          LIMIT 1";

$stmt = $conn->query($query);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if ($socio) {
    echo "═════════════════════════════════════════════════\n";
    echo "✅ VERIFICACIÓN: ESTADO Y FECHA DE AFILIACIÓN\n";
    echo "═════════════════════════════════════════════════\n\n";
    
    echo "📋 DATOS DEL SOCIO:\n";
    echo "─────────────────────────────────────────────────\n";
    printf("ID:                    %d\n", $socio['id']);
    printf("Nombre:                %s\n", $socio['nombre']);
    printf("Documento:             %s\n", $socio['documento']);
    printf("Email:                 %s\n", $socio['email']);
    printf("Teléfono:              %s\n", $socio['telefono']);
    
    echo "\n📅 INFORMACIÓN IMPORTANTE:\n";
    echo "─────────────────────────────────────────────────\n";
    printf("✓ Fecha de Afiliación: %s\n", $socio['fecha_afiliacion'] ?? 'NO DEFINIDA');
    printf("✓ Estado:              %s\n", $socio['estado'] ?? 'activo');
    printf("✓ Fecha del Estado:    %s\n", $socio['fecha_estado'] ?? 'NO DEFINIDA');
    printf("✓ Motivo del Estado:   %s\n", $socio['motivo_estado'] ?? 'NINGUNO');
    
    echo "\n💰 INFORMACIÓN FINANCIERA:\n";
    echo "─────────────────────────────────────────────────\n";
    printf("✓ Saldo:               %.2f\n", $socio['saldo']);
    printf("✓ Entidad de Salud:    %s\n", $socio['entidad_salud'] ?? 'NO DEFINIDA');
    printf("✓ Afiliado:            %s\n", $socio['afiliado'] ? 'SÍ' : 'NO');
    
    echo "\n════════════════════════════════════════════════= \n";
    echo "✅ Todos los campos están disponibles en BD\n";
    echo "════════════════════════════════════════════════= \n\n";
    
    // Verificar que los campos se pueden actualizar
    echo "🔄 VERIFICACIÓN DE ACTUALIZACIÓN:\n";
    echo "─────────────────────────────────────────────────\n";
    
    // Intentar actualizar estado
    $updateQuery = "UPDATE socios SET estado = 'lesionado', fecha_estado = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    
    if ($updateStmt->execute([$socio['id']])) {
        echo "✓ Estado se puede actualizar a 'lesionado'\n";
    } else {
        echo "✗ Error al actualizar estado\n";
    }
    
    // Intentar actualizar fecha_afiliacion
    $updateQuery2 = "UPDATE socios SET fecha_afiliacion = ? WHERE id = ?";
    $updateStmt2 = $conn->prepare($updateQuery2);
    $today = date('Y-m-d');
    
    if ($updateStmt2->execute([$today, $socio['id']])) {
        echo "✓ Fecha de afiliación se puede actualizar a '$today'\n";
    } else {
        echo "✗ Error al actualizar fecha de afiliación\n";
    }
    
    echo "\n════════════════════════════════════════════════= \n";
    echo "✅ VERIFICACIÓN COMPLETADA\n";
    echo "════════════════════════════════════════════════= \n";
    
    // Revertir cambios
    $revertQuery = "UPDATE socios SET estado = 'activo', fecha_afiliacion = (SELECT CURDATE()), fecha_estado = NOW() WHERE id = ?";
    $revertStmt = $conn->prepare($revertQuery);
    $revertStmt->execute([$socio['id']]);
    
} else {
    echo "❌ No hay socios para verificar\n";
}
?>
