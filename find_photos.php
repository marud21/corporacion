<?php
// Find which user has the actual photos

require_once 'includes/database/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Búsqueda de fotos reales</h2>";

// Primero obtener lista de fotos reales en el directorio
$archivos = scandir('uploads/documentos');
$archivos_reales = [];
foreach ($archivos as $arch) {
    if (strpos($arch, 'doc_') === 0) {
        $archivos_reales[] = $arch;
        echo "Archivo real: $arch<br>";
    }
}

echo "<hr>";

// Ahora buscar qué usuario tiene esos documentos
$query = "SELECT u.id, u.nombre, u.documento FROM usuarios u WHERE u.rol = 'socio'";
$stmt = $conn->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Usuarios que tienen fotos en disco:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Archivo en disco</th><th>Documento extraído</th><th>Usuario ID</th><th>Nombre</th></tr>";

foreach ($archivos_reales as $archivo) {
    // Extraer el documento del nombre del archivo
    // Formato: doc_DOCUMENTO_TIMESTAMP_UNIQUEID.ext
    $partes = explode('_', $archivo);
    if (isset($partes[1])) {
        $documento = $partes[1];
        
        // Buscar el usuario con ese documento
        foreach ($usuarios as $user) {
            if ($user['documento'] == $documento) {
                echo "<tr>";
                echo "<td>$archivo</td>";
                echo "<td>$documento</td>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['nombre'] . "</td>";
                echo "</tr>";
                break;
            }
        }
    }
}
echo "</table>";

echo "<h3>SQL para actualizar BD:</h3>";
echo "<pre>";
foreach ($archivos_reales as $archivo) {
    $partes = explode('_', $archivo);
    if (isset($partes[1])) {
        $documento = $partes[1];
        
        foreach ($usuarios as $user) {
            if ($user['documento'] == $documento) {
                echo "UPDATE socios SET documento_pdf = 'uploads/documentos/" . $archivo . "' WHERE id = " . $user['id'] . ";\n";
                break;
            }
        }
    }
}
echo "</pre>";
?>
