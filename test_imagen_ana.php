<?php
// Test para verificar si la imagen se sirve correctamente

require_once 'includes/database/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Obtener Ana Díaz (ID 6)
$query = "SELECT u.id, u.nombre, s.documento_pdf FROM usuarios u LEFT JOIN socios s ON u.id = s.id WHERE u.id = 6";
$stmt = $conn->prepare($query);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Test de Imagen - Ana Díaz</h2>";

echo "<h3>Datos en BD:</h3>";
echo "<p>ID: " . $usuario['id'] . "</p>";
echo "<p>Nombre: " . $usuario['nombre'] . "</p>";
echo "<p>Ruta en BD: " . $usuario['documento_pdf'] . "</p>";

echo "<h3>Rutas Construidas:</h3>";
$ruta_relativa = '/corve/' . $usuario['documento_pdf'];
$ruta_absoluta = __DIR__ . '/' . $usuario['documento_pdf'];

echo "<p>Ruta relativa (para HTML): <code>$ruta_relativa</code></p>";
echo "<p>Ruta absoluta (en servidor): <code>$ruta_absoluta</code></p>";

echo "<h3>Verificaciones:</h3>";
echo "<p>Archivo existe en servidor: " . (file_exists($ruta_absoluta) ? "✅ SÍ" : "❌ NO") . "</p>";
echo "<p>Tamaño del archivo: " . (file_exists($ruta_absoluta) ? filesize($ruta_absoluta) . " bytes" : "N/A") . "</p>";

echo "<h3>Visualización:</h3>";
echo "<img src='$ruta_relativa' alt='Documento' style='max-height: 300px; border: 1px solid #ccc;'>";

echo "<h3>HTML que genera JavaScript:</h3>";
echo "<pre>";
echo "document.getElementById('fotoDocumento').src = '" . $ruta_relativa . "';\n";
echo "document.getElementById('fotoDocumento').style.display = 'block';\n";
echo "document.getElementById('sinFoto').style.display = 'none';\n";
echo "</pre>";

echo "<h3>Response JSON que retorna API:</h3>";
echo "<pre>";
echo json_encode([
    'success' => true,
    'data' => $usuario
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "</pre>";
?>
