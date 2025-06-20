<?php
// Incluir el archivo de configuración de la base de datos
require_once 'config/database.php';

try {
    // Crear conexión
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verificar si la columna ya existe
    $check_column = "SHOW COLUMNS FROM socios LIKE 'fecha_afiliacion'";
    $stmt = $conn->query($check_column);
    
    if($stmt->rowCount() == 0) {
        // La columna no existe, vamos a crearla
        $query = "ALTER TABLE socios ADD COLUMN fecha_afiliacion DATE NULL AFTER saldo";
        $conn->exec($query);
        echo "<p style='color: green;'>Se ha agregado la columna 'fecha_afiliacion' a la tabla 'socios'</p>";
    } else {
        echo "<p>La columna 'fecha_afiliacion' ya existe en la tabla 'socios'</p>";
    }
    
    // Mostrar la estructura actualizada
    echo "<h2>Estructura actual de la tabla 'socios':</h2>";
    $query = "SHOW COLUMNS FROM socios";
    $stmt = $conn->query($query);
    
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
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
