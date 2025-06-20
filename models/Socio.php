<?php
require_once __DIR__ . '/../includes/database/Database.php';

class Socio {
    // Conexión a la base de datos
    private $conn;
    private $table_name = "socios";
    private $usuarios_table = "usuarios";

    // Propiedades del socio
    public $id;
    public $entidad_salud;
    public $documento_pdf;
    public $afiliado;
    public $saldo;
    public $estado = 'activo'; // activo, lesionado, retirado
    public $fecha_estado;
    public $motivo_estado;
    public $usuario; // Objeto con los datos del usuario
    
    // Constantes para los estados
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_LESIONADO = 'lesionado';
    const ESTADO_RETIRADO = 'retirado';
    
    // Constantes para los montos
    const MONTO_MENSUAL_NORMAL = 45000;
    const MONTO_MENSUAL_LESION = 10000;
    const MONTO_AFILIACION = 100000;
    const MONTO_INSCRIPCION = 45000;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Leer todos los socios con sus datos de usuario
    public function read($soloActivos = false) {
        $query = "SELECT u.*, s.entidad_salud, s.documento_pdf, s.afiliado, s.saldo, 
                         s.estado, s.fecha_estado, s.motivo_estado
                 FROM " . $this->usuarios_table . " u 
                 LEFT JOIN " . $this->table_name . " s ON u.id = s.id 
                 WHERE u.rol = 'socio' ";
        
        if ($soloActivos) {
            $query .= " AND s.estado = '" . self::ESTADO_ACTIVO . "'";
        }
        
        $query .= " ORDER BY u.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Crear un nuevo socio
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET id = :id, entidad_salud = :entidad_salud, 
                     documento_pdf = :documento_pdf, afiliado = :afiliado, 
                     saldo = :saldo, estado = :estado,
                     fecha_estado = :fecha_estado, motivo_estado = :motivo_estado";

        $stmt = $this->conn->prepare($query);

        // Limpiar los datos
        $this->entidad_salud = htmlspecialchars(strip_tags($this->entidad_salud));
        $this->documento_pdf = htmlspecialchars(strip_tags($this->documento_pdf));
        $this->afiliado = $this->afiliado ? 1 : 0;
        $this->saldo = (float)$this->saldo;

        // Vincular los valores
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":entidad_salud", $this->entidad_salud);
        $stmt->bindParam(":documento_pdf", $this->documento_pdf);
        $stmt->bindParam(":afiliado", $this->afiliado);
        $stmt->bindParam(":saldo", $this->saldo);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":fecha_estado", $this->fecha_estado);
        $stmt->bindParam(":motivo_estado", $this->motivo_estado);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Leer un socio específico
    public function readOne() {
        $query = "SELECT u.*, s.entidad_salud, s.documento_pdf, s.afiliado, s.saldo 
                 FROM " . $this->usuarios_table . " u 
                 LEFT JOIN " . $this->table_name . " s ON u.id = s.id 
                 WHERE u.id = ? 
                 LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->documento = $row['documento'];
            $this->email = $row['email'];
            $this->telefono = $row['telefono'];
            $this->direccion = $row['direccion'];
            $this->fecha_nacimiento = $row['fecha_nacimiento'];
            $this->entidad_salud = $row['entidad_salud'];
            $this->documento_pdf = $row['documento_pdf'];
            $this->afiliado = $row['afiliado'];
            $this->saldo = $row['saldo'];
            
            return true;
        }

        return false;
    }

    // Actualizar un socio existente
    public function update($actualizarEstado = false) {
        // Consulta base para actualizar
        $query = "UPDATE " . $this->table_name . " 
                 SET entidad_salud = :entidad_salud, 
                     documento_pdf = :documento_pdf, 
                     afiliado = :afiliado, 
                     saldo = :saldo";
        
        // Solo actualizar los campos de estado si se indica explícitamente
        if ($actualizarEstado) {
            $query .= ", estado = :estado,
                     fecha_estado = :fecha_estado,
                     motivo_estado = :motivo_estado";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Limpiar los datos
        $this->entidad_salud = htmlspecialchars(strip_tags($this->entidad_salud));
        $this->documento_pdf = htmlspecialchars(strip_tags($this->documento_pdf));
        $this->afiliado = $this->afiliado ? 1 : 0;
        $this->saldo = (float)$this->saldo;

        // Vincular los valores
        $stmt->bindParam(":entidad_salud", $this->entidad_salud);
        $stmt->bindParam(":documento_pdf", $this->documento_pdf);
        $stmt->bindParam(":afiliado", $this->afiliado);
        $stmt->bindParam(":saldo", $this->saldo);
        
        // Vincular parámetros de estado solo si se están actualizando
        if ($actualizarEstado) {
            $stmt->bindParam(":estado", $this->estado);
            $stmt->bindParam(":fecha_estado", $this->fecha_estado);
            $stmt->bindParam(":motivo_estado", $this->motivo_estado);
        }
        
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Eliminar un socio
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    /**
     * Cambia el estado de un socio
     * @param string $nuevoEstado El nuevo estado (activo, lesionado, retirado)
     * @param string $motivo El motivo del cambio de estado (opcional)
     * @return bool True si el cambio fue exitoso, false en caso contrario
     */
    public function cambiarEstado($nuevoEstado, $motivo = '') {
        if (!in_array($nuevoEstado, [self::ESTADO_ACTIVO, self::ESTADO_LESIONADO, self::ESTADO_RETIRADO])) {
            return false;
        }
        
        $this->estado = $nuevoEstado;
        $this->fecha_estado = date('Y-m-d H:i:s');
        $this->motivo_estado = $motivo;
        
        // Llamar a update con true para actualizar el estado
        return $this->update(true);
    }
    
    /**
     * Obtiene el monto de la cuota mensual según el estado del socio
     * @return float Monto de la cuota mensual
     */
    public function getMontoMensual() {
        switch ($this->estado) {
            case self::ESTADO_LESIONADO:
                return self::MONTO_MENSUAL_LESION;
            case self::ESTADO_ACTIVO:
                return self::MONTO_MENSUAL_NORMAL;
            case self::ESTADO_RETIRADO:
            default:
                return 0; // No paga cuota si está retirado
        }
    }
    
    /**
     * Verifica si el socio está activo
     * @return bool True si el socio está activo, false en caso contrario
     */
    public function estaActivo() {
        return $this->estado === self::ESTADO_ACTIVO;
    }
    
    /**
     * Verifica si el socio está lesionado
     * @return bool True si el socio está lesionado, false en caso contrario
     */
    public function estaLesionado() {
        return $this->estado === self::ESTADO_LESIONADO;
    }
    
    /**
     * Verifica si el socio está retirado
     * @return bool True si el socio está retirado, false en caso contrario
     */
    public function estaRetirado() {
        return $this->estado === self::ESTADO_RETIRADO;
    }
}
