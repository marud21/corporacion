-- Crear la tabla de historial de estados de socios
CREATE TABLE IF NOT EXISTS `historial_estados_socios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `socio_id` int(11) NOT NULL,
  `estado_anterior` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_nuevo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci,
  `fecha_cambio` datetime NOT NULL,
  `usuario_id` int(11) DEFAULT NULL COMMENT 'ID del usuario que realizó el cambio',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agente_usuario` text COLLATE utf8mb4_unicode_ci,
  `notificado` tinyint(1) DEFAULT '0' COMMENT 'Indica si se notificó al socio',
  `fecha_notificacion` datetime DEFAULT NULL,
  `metodo_notificacion` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'email, sms, etc.',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_socio_id` (`socio_id`),
  KEY `idx_fecha_cambio` (`fecha_cambio`),
  KEY `idx_estado_nuevo` (`estado_nuevo`),
  KEY `idx_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_historial_socio` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de cambios de estado de los socios (activo, lesionado, retirado)';

-- Agregar comentarios a las columnas
ALTER TABLE `historial_estados_socios`
  MODIFY `estado_anterior` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Estado anterior del socio',
  MODIFY `estado_nuevo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nuevo estado del socio',
  MODIFY `fecha_cambio` datetime NOT NULL COMMENT 'Fecha y hora en que se realizó el cambio';

-- Crear un trigger para registrar automáticamente los cambios de estado
DELIMITER //
CREATE TRIGGER after_socio_estado_update
AFTER UPDATE ON socios
FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO historial_estados_socios 
        (socio_id, estado_anterior, estado_nuevo, fecha_cambio, usuario_id, ip, agente_usuario)
        VALUES 
        (NEW.id, OLD.estado, NEW.estado, NOW(), 
         @usuario_id, @ip_usuario, @agente_usuario);
    END IF;
END //
DELIMITER ;

-- Crear una vista para facilitar la consulta del historial
CREATE OR REPLACE VIEW vw_historial_estados_socios AS
SELECT 
    h.id,
    h.socio_id,
    CONCAT(u.nombre, ' ', u.apellido) AS nombre_socio,
    h.estado_anterior,
    h.estado_nuevo,
    h.motivo,
    h.fecha_cambio,
    CONCAT(usu.nombre, ' ', usu.apellido) AS usuario_cambio,
    h.ip,
    h.notificado,
    h.fecha_notificacion,
    h.metodo_notificacion,
    h.observaciones
FROM 
    historial_estados_socios h
    LEFT JOIN usuarios u ON h.socio_id = u.id
    LEFT JOIN usuarios usu ON h.usuario_id = usu.id
ORDER BY 
    h.fecha_cambio DESC;
