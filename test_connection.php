<?php
// Incluir el archivo de conexión a la base de datos
require_once __DIR__ . '/includes/database/Database.php';

try {
    // Obtener la conexión a la base de datos
    $conn = getDBConnection();
    
    // Si llegamos aquí, la conexión fue exitosa
    echo "<h2 style='color: green;'>¡Conexión exitosa a la base de datos!</h2>";
    
    // Mostrar información del servidor de base de datos
    echo "<h3>Información del servidor:</h3>";
    echo "<ul>";
    echo "<li>Servidor: " . $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</li>";
    echo "<li>Versión del servidor: " . $conn->getAttribute(PDO::ATTR_SERVER_VERSION) . "</li>";
    echo "<li>Versión del cliente: " . $conn->getAttribute(PDO::ATTR_CLIENT_VERSION) . "</li>";
    echo "</ul>";
    
    // Consulta de prueba para verificar las tablas
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<h3>Tablas en la base de datos:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No se encontraron tablas en la base de datos.</p>";
    }
    
} catch (PDOException $e) {
    // Si hay un error en la conexión
    echo "<h2 style='color: red;'>Error de conexión:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<h3>Detalles:</h3>";
    echo "<pre>";
    var_dump($e);
    echo "</pre>";
}

// Cerrar la conexión
$conn = null;
?>
