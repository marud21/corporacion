<?php
// Limpiar documentos_pdf inválidos

require_once 'includes/database/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Limpieza de referencias de documentos inválidos</h2>";

// Obtener todos los socios con documento_pdf
$query = "SELECT u.id, u.nombre, s.documento_pdf FROM usuarios u LEFT JOIN socios s ON u.id = s.id WHERE u.rol = 'socio' AND s.documento_pdf IS NOT NULL";
$stmt = $conn->prepare($query);
$stmt->execute();
$socios = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Ruta en BD</th><th>Existe</th><th>Acción</th></tr>";

$count_limpiados = 0;

foreach ($socios as $socio) {
    $ruta_completa = __DIR__ . '/' . $socio['documento_pdf'];
    $existe = file_exists($ruta_completa);
    
    echo "<tr>";
    echo "<td>" . $socio['id'] . "</td>";
    echo "<td>" . $socio['nombre'] . "</td>";
    echo "<td>" . $socio['documento_pdf'] . "</td>";
    echo "<td>" . ($existe ? "✅" : "❌") . "</td>";
    
    if (!$existe && $socio['documento_pdf'] !== 'uploads/documentos/doc_1060606060_1765896725_6941721536eb8.png') {
        // Limpiar esta referencia
        $delete_query = "UPDATE socios SET documento_pdf = NULL WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->execute([$socio['id']]);
        echo "<td style='color: orange;'>🧹 LIMPIADO</td>";
        $count_limpiados++;
    } else {
        echo "<td>-</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

echo "<p><strong>Total limpiados: $count_limpiados</strong></p>";
?>
