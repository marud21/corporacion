<?php
// Debug script para verificar fotos de documentos en BD

require_once 'includes/database/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Obtener socios con documento_pdf
$query = "SELECT u.id, u.nombre, u.documento, s.documento_pdf 
          FROM usuarios u 
          LEFT JOIN socios s ON u.id = s.id 
          WHERE u.rol = 'socio' 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Verificación de Documentos en BD</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Documento</th><th>documento_pdf</th><th>Archivo Existe</th></tr>";

foreach ($result as $row) {
    $archivo_existe = false;
    $documento_pdf = $row['documento_pdf'] ?? '';
    
    if ($documento_pdf && trim($documento_pdf) !== '') {
        $ruta_completa = __DIR__ . '/' . $documento_pdf;
        $archivo_existe = file_exists($ruta_completa);
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "<td>" . $row['documento'] . "</td>";
        echo "<td>" . $documento_pdf . "</td>";
        echo "<td>" . ($archivo_existe ? "✅ SÍ" : "❌ NO") . "</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "<td>" . $row['documento'] . "</td>";
        echo "<td><em style='color:red;'>VACÍO</em></td>";
        echo "<td>-</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<h3>Contenido de /uploads/documentos/</h3>";
echo "<pre>";
if (is_dir('uploads/documentos')) {
    $files = scandir('uploads/documentos');
    print_r($files);
} else {
    echo "Directorio no existe";
}
echo "</pre>";

?>
