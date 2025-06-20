<?php
// Incluir el archivo de configuración de la base de datos
require_once 'config/database.php';

try {
    // Crear conexión
    $database = new Database();
    $conn = $database->getConnection();
    
    // Consulta para verificar la estructura de la tabla socios
    $query = "SHOW COLUMNS FROM socios";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "<h2>Estructura de la tabla 'socios':</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
