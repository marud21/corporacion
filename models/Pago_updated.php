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

    // ... otros métodos de la clase Pago ...
}
?>
