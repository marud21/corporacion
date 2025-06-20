<?php
/**
 * Clase para manejar la conexión a la base de datos
 */
class Database {
    // Configuración de la base de datos
    private $host = 'localhost';
    private $db_name = 'corporacion';
    private $username = 'root';
    private $password = '';
    private $conn;

    // Obtener la conexión a la base de datos
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            
            // Configurar el modo de error de PDO a excepción
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Configurar el juego de caracteres a UTF-8
            $this->conn->exec("set names utf8");
            
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
            die();
        }

        return $this->conn;
    }

    // Obtener la instancia única (Singleton)
    public static function getInstance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new static();
        }
        return $instance;
    }

    // Función estática de ayuda para obtener la conexión
    public static function getDBConnection() {
        $database = self::getInstance();
        return $database->getConnection();
    }
}

// Función global de ayuda para obtener la conexión
function getDBConnection() {
    return Database::getDBConnection();
}
?>
