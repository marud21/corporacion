<?php
/**
 * Modelo para gestionar las tarifas del sistema
 */
class Tarifa {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Obtiene todas las tarifas
     */
    public function getAll() {
        $query = "SELECT * FROM tarifas ORDER BY concepto ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene solo las tarifas activas
     */
    public function getActivas() {
        $query = "SELECT * FROM tarifas WHERE activa = 1 ORDER BY concepto ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una tarifa por ID
     */
    public function getById($id) {
        $query = "SELECT * FROM tarifas WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una tarifa por concepto
     */
    public function getByConcepto($concepto) {
        $query = "SELECT * FROM tarifas WHERE concepto = :concepto";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':concepto', $concepto);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el monto de una tarifa por concepto
     */
    public function getMonto($concepto) {
        $tarifa = $this->getByConcepto($concepto);
        return $tarifa ? (float)$tarifa['monto'] : 0;
    }

    /**
     * Actualiza una tarifa
     */
    public function update($id, $data) {
        $this->db->beginTransaction();
        try {
            $query = "UPDATE tarifas SET monto = :monto, descripcion = :descripcion, activa = :activa WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            // Usar bindValue() en lugar de bindParam() para valores directos
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->bindValue(':monto', (float)$data['monto']);
            $stmt->bindValue(':descripcion', isset($data['descripcion']) ? $data['descripcion'] : '');
            $stmt->bindValue(':activa', isset($data['activa']) ? (int)$data['activa'] : 1, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene resumen de tarifas activas
     */
    public function getResumen() {
        $tarifas = $this->getActivas();
        $resumen = [];
        
        foreach ($tarifas as $tarifa) {
            $resumen[$tarifa['concepto']] = (float)$tarifa['monto'];
        }
        
        return $resumen;
    }
}
?>
