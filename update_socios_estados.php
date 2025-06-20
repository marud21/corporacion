<?php
require_once 'includes/database/Database.php';

function actualizarTablaSocios() {
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Verificar si la columna 'estado' ya existe
        $checkColumn = $conn->query("SHOW COLUMNS FROM socios LIKE 'estado'");
        
        if($checkColumn->rowCount() == 0) {
            // Agregar columna 'estado' a la tabla socios
            $sql = "ALTER TABLE socios 
                    ADD COLUMN estado ENUM('activo', 'lesionado', 'retirado') NOT NULL DEFAULT 'activo',
                    ADD COLUMN fecha_estado DATETIME DEFAULT CURRENT_TIMESTAMP,
                    ADD COLUMN motivo_estado TEXT NULL";
                    
            $conn->exec($sql);
            echo "Tabla 'socios' actualizada exitosamente.\n";
        } else {
            echo "La tabla 'socios' ya tiene las columnas necesarias.\n";
        }
        
    } catch(PDOException $e) {
        echo "Error al actualizar la tabla: " . $e->getMessage() . "\n";
    }
}

// Ejecutar la función
actualizarTablaSocios();

echo "Proceso completado. Verifica la tabla 'socios' para confirmar los cambios.";
?>
