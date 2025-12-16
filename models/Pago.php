<?php
/**
 * Modelo para gestionar las operaciones de pagos de los socios
 */
class Pago {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Obtiene el historial de pagos con filtros
     */
    public function getHistorial($filtros = []) {
        $query = "SELECT p.*, u.nombre as socio_nombre, u.documento as socio_documento, 
                        s.estado as socio_estado, s.saldo as socio_saldo
                 FROM pagos p 
                 JOIN socios s ON p.socio_id = s.id 
                 JOIN usuarios u ON s.id = u.id 
                 WHERE 1=1";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filtros['socio_id'])) {
            $query .= " AND p.socio_id = :socio_id";
            $params[':socio_id'] = $filtros['socio_id'];
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND p.fecha >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND p.fecha <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
        }
        
        if (!empty($filtros['concepto'])) {
            $query .= " AND p.concepto LIKE :concepto";
            $params[':concepto'] = '%' . $filtros['concepto'] . '%';
        }
        
        // Filtrar por nombre o documento de socio
        if (!empty($filtros['buscar_nombre'])) {
            $query .= " AND (u.nombre LIKE :buscar_nombre OR u.documento LIKE :buscar_documento)";
            $params[':buscar_nombre'] = '%' . $filtros['buscar_nombre'] . '%';
            $params[':buscar_documento'] = '%' . $filtros['buscar_nombre'] . '%';
        }
        
        $query .= " ORDER BY p.fecha DESC";
        
        // Aplicar límite para paginación
        if (!empty($filtros['limit'])) {
            $query .= " LIMIT " . (int)$filtros['limit'];
            
            if (!empty($filtros['offset'])) {
                $query .= " OFFSET " . (int)$filtros['offset'];
            }
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el total de registros para la paginación
     */
    public function getTotalRegistros($filtros = []) {
        $query = "SELECT COUNT(p.id) as total
                 FROM pagos p 
                 JOIN socios s ON p.socio_id = s.id 
                 JOIN usuarios u ON s.id = u.id 
                 WHERE 1=1";
        
        $params = [];
        
        // Aplicar los mismos filtros que en getHistorial
        if (!empty($filtros['socio_id'])) {
            $query .= " AND p.socio_id = :socio_id";
            $params[':socio_id'] = $filtros['socio_id'];
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND p.fecha >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND p.fecha <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
        }
        
        if (!empty($filtros['concepto'])) {
            $query .= " AND p.concepto LIKE :concepto";
            $params[':concepto'] = '%' . $filtros['concepto'] . '%';
        }
        
        // Filtrar por nombre o documento de socio
        if (!empty($filtros['buscar_nombre'])) {
            $query .= " AND (u.nombre LIKE :buscar_nombre OR u.documento LIKE :buscar_documento)";
            $params[':buscar_nombre'] = '%' . $filtros['buscar_nombre'] . '%';
            $params[':buscar_documento'] = '%' . $filtros['buscar_nombre'] . '%';
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado ? (int)$resultado['total'] : 0;
    }
    
    /**
     * Obtiene la deuda actual de un socio (saldo negativo)
     * @param int $socio_id
     * @return float Deuda (valor absoluto del saldo si es negativo, 0 si no hay deuda)
     */
    public function getDeudaSocio($socio_id) {
        $query = "SELECT saldo FROM socios WHERE id = :socio_id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':socio_id', $socio_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['saldo']) && $row['saldo'] < 0) {
            return abs($row['saldo']);
        }
        return 0;
    }
    
    /**
     * Inserta un nuevo pago en la base de datos y descuenta el monto del saldo del socio
     * @param array $data
     * @return int ID del pago insertado
     */
    public function create($data) {
        $this->db->beginTransaction();
        try {
            $query = "INSERT INTO pagos (socio_id, monto, concepto, fecha, metodo_pago, referencia, observaciones) VALUES (:socio_id, :monto, :concepto, :fecha, :metodo_pago, :referencia, :observaciones)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':socio_id', $data['socio_id'], PDO::PARAM_INT);
            $stmt->bindParam(':monto', $data['monto']);
            $stmt->bindParam(':concepto', $data['concepto']);
            $fecha = isset($data['fecha']) ? $data['fecha'] : date('Y-m-d H:i:s');
            $stmt->bindParam(':fecha', $fecha);
            $metodo_pago = isset($data['metodo_pago']) ? $data['metodo_pago'] : 'efectivo';
            $stmt->bindParam(':metodo_pago', $metodo_pago);
            $referencia = isset($data['referencia']) ? $data['referencia'] : null;
            $stmt->bindParam(':referencia', $referencia);
            $observaciones = isset($data['observaciones']) ? $data['observaciones'] : null;
            $stmt->bindParam(':observaciones', $observaciones);
            $stmt->execute();
            $pago_id = $this->db->lastInsertId();

            // Restar el monto del saldo del socio
            $update = $this->db->prepare("UPDATE socios SET saldo = saldo - :monto WHERE id = :socio_id");
            $update->bindParam(':monto', $data['monto']);
            $update->bindParam(':socio_id', $data['socio_id'], PDO::PARAM_INT);
            $update->execute();

            $this->db->commit();
            return $pago_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
?>
