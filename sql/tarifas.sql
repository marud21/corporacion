-- Tabla de tarifas del sistema
CREATE TABLE IF NOT EXISTS tarifas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    concepto VARCHAR(100) NOT NULL UNIQUE,
    monto DECIMAL(10, 2) NOT NULL DEFAULT 0,
    descripcion TEXT,
    activa BOOLEAN DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_concepto (concepto),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar tarifas por defecto
INSERT INTO tarifas (concepto, monto, descripcion, activa) VALUES
    ('Mensualidad Activo', 45000, 'Cuota mensual para socios activos', 1),
    ('Mensualidad Lesionado', 10000, 'Cuota mensual reducida para socios lesionados', 1),
    ('Inscripción', 0, 'Tarifa de inscripción de nuevos socios', 1),
    ('Afiliación', 100000, 'Cuota de afiliación para nuevos socios', 1)
ON DUPLICATE KEY UPDATE monto = VALUES(monto);
