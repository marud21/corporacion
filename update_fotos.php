<?php
// Actualizar la foto de Ana Díaz

require_once 'includes/database/Database.php';

$db = new Database();
$conn = $db->getConnection();

$sqls = [
    "UPDATE socios SET documento_pdf = 'uploads/documentos/doc_1060606060_1765896725_6941721536eb8.png' WHERE id = 6"
];

echo "<h2>Actualización de Base de Datos</h2>";

foreach ($sqls as $sql) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        echo "<p style='color: green;'>✅ SQL ejecutado correctamente:<br><code>$sql</code></p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Verificar que se actualizó
$query = "SELECT u.id, u.nombre, s.documento_pdf FROM usuarios u LEFT JOIN socios s ON u.id = s.id WHERE u.id = 6";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Verificación post-actualización:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nombre</th><th>documento_pdf</th></tr>";
echo "<tr><td>" . $result['id'] . "</td><td>" . $result['nombre'] . "</td><td>" . $result['documento_pdf'] . "</td></tr>";
echo "</table>";
?>
