-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-06-2025 a las 21:33:13
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `corvepatios`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `torneo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadisticas`
--

CREATE TABLE `estadisticas` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `goles` int(11) DEFAULT 0,
  `amarillas` int(11) DEFAULT 0,
  `rojas` int(11) DEFAULT 0,
  `comentarios` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estados_socios`
--

CREATE TABLE `historial_estados_socios` (
  `id` int(11) NOT NULL,
  `socio_id` int(11) NOT NULL,
  `estado_anterior` varchar(20) NOT NULL COMMENT 'Estado anterior del socio',
  `estado_nuevo` varchar(20) NOT NULL COMMENT 'Nuevo estado del socio',
  `motivo` text DEFAULT NULL,
  `fecha_cambio` datetime NOT NULL COMMENT 'Fecha y hora en que se realizó el cambio',
  `usuario_id` int(11) DEFAULT NULL COMMENT 'ID del usuario que realizó el cambio',
  `ip` varchar(45) DEFAULT NULL,
  `agente_usuario` text DEFAULT NULL,
  `notificado` tinyint(1) DEFAULT 0 COMMENT 'Indica si se notificó al socio',
  `fecha_notificacion` datetime DEFAULT NULL,
  `metodo_notificacion` varchar(20) DEFAULT NULL COMMENT 'email, sms, etc.',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de cambios de estado de los socios (activo, lesionado, retirado)';

--
-- Volcado de datos para la tabla `historial_estados_socios`
--

INSERT INTO `historial_estados_socios` (`id`, `socio_id`, `estado_anterior`, `estado_nuevo`, `motivo`, `fecha_cambio`, `usuario_id`, `ip`, `agente_usuario`, `notificado`, `fecha_notificacion`, `metodo_notificacion`, `observaciones`) VALUES
(1, 6, 'activo', 'lesionado', '', '2025-06-14 14:29:09', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jugadores`
--

CREATE TABLE `jugadores` (
  `id` int(11) NOT NULL,
  `equipo_id` int(11) DEFAULT NULL,
  `rol` enum('jugador','arquero') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `socio_id` int(11) NOT NULL,
  `monto` float NOT NULL,
  `concepto` enum('afiliación','mensualidad','inscripción','sanción') NOT NULL,
  `fecha` datetime NOT NULL,
  `pagado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidos`
--

CREATE TABLE `partidos` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `equipo_local` int(11) NOT NULL,
  `equipo_visitante` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `cancha` varchar(100) NOT NULL,
  `resultado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sanciones`
--

CREATE TABLE `sanciones` (
  `id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `tipo` enum('amarilla','roja') NOT NULL,
  `costo` float NOT NULL,
  `suspension` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `socios`
--

CREATE TABLE `socios` (
  `id` int(11) NOT NULL,
  `entidad_salud` varchar(100) DEFAULT NULL,
  `documento_pdf` varchar(200) DEFAULT NULL,
  `afiliado` tinyint(1) DEFAULT 0,
  `saldo` float DEFAULT 0,
  `estado` enum('activo','lesionado','retirado') DEFAULT 'activo',
  `fecha_estado` datetime DEFAULT current_timestamp(),
  `motivo_estado` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `socios`
--

INSERT INTO `socios` (`id`, `entidad_salud`, `documento_pdf`, `afiliado`, `saldo`, `estado`, `fecha_estado`, `motivo_estado`) VALUES
(1, 'Nueva EPS', 'documento_juan_perez.pdf', 1, 150.75, 'activo', '2025-06-14 14:27:02', NULL),
(2, 'SURA EPS', 'documento_maria_gomez.pdf', 0, 0, 'activo', '2025-06-14 14:27:02', NULL),
(3, 'Coomeva EPS', 'documento_carlos_ruiz.pdf', 1, 300.5, 'activo', '2025-06-14 14:27:02', NULL),
(4, 'Sanitas EPS', 'documento_laura_lopez.pdf', 1, 75.2, 'activo', '2025-06-14 14:27:02', NULL),
(5, 'Salud Total EPS', 'documento_pedro_ramirez.pdf', 0, 0, 'activo', '2025-06-14 14:27:02', NULL),
(6, 'Compensar EPS', 'documento_ana_diaz.pdf', 1, 220, 'lesionado', '2025-06-14 14:29:09', ''),
(7, 'Medimas EPS', 'documento_luis_fernandez.pdf', 0, 0, 'activo', '2025-06-14 14:27:02', NULL),
(8, 'Famisanar EPS', 'documento_sofia_garcia.pdf', 1, 120.9, 'activo', '2025-06-14 14:27:02', NULL),
(9, 'Alianza Salud', 'documento_diego_martinez.pdf', 1, 50, 'activo', '2025-06-14 14:27:02', NULL),
(10, 'Sura EPS', 'documento_valeria_hernandez.pdf', 0, 0, 'activo', '2025-06-14 14:27:02', NULL),
(11, 'Nueva EPS', 'documento_andres_torres.pdf', 1, 400, 'activo', '2025-06-14 14:27:02', NULL),
(12, 'Coomeva EPS', 'documento_isabella_castro.pdf', 0, 0, 'activo', '2025-06-14 14:27:02', NULL),
(13, 'Sanitas EPS', 'documento_sebastian_jimenez.pdf', 1, 90.1, 'activo', '2025-06-14 14:27:02', NULL),
(14, 'Salud Total EPS', 'documento_camila_morales.pdf', 1, 180.3, 'activo', '2025-06-14 14:27:02', NULL),
(15, 'Compensar EPS', 'documento_daniel_ortiz.pdf', 0, 0, 'activo', '2025-06-14 14:27:02', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torneos`
--

CREATE TABLE `torneos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `categoria` enum('40','50') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `rol` enum('socio','admin_admin','admin_deportivo','admin_principal') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `password_hash` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `documento`, `email`, `telefono`, `direccion`, `fecha_nacimiento`, `rol`, `activo`, `password_hash`) VALUES
(1, 'Juan Pérez', '1010101010', 'juan.perez@example.com', '3001112233', 'Calle 10 # 5-15', '1985-03-10', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(2, 'María Gómez', '1020202020', 'maria.gomez@example.com', '3004445566', 'Carrera 20 # 12-30', '1990-07-22', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(3, 'Carlos Ruiz', '1030303030', 'carlos.ruiz@example.com', '3007778899', 'Avenida 3 # 8-50', '1978-01-05', 'admin_deportivo', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(4, 'Laura López', '1040404040', 'laura.lopez@example.com', '3001234567', 'Diagonal 45 # 2-10', '1995-11-18', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(5, 'Pedro Ramírez', '1050505050', 'pedro.ramirez@example.com', '3009876543', 'Transversal 60 # 25-5', '1982-04-25', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(6, 'Ana Díaz', '1060606060', 'ana.diaz@example.com', '3002345678', 'Calle 7 # 1-90', '1993-09-01', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(7, 'Luis Fernández', '1070707070', 'luis.fernandez@example.com', '3008765432', 'Carrera 80 # 30-100', '1970-12-12', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(8, 'Sofía García', '1080808080', 'sofia.garcia@example.com', '3003456789', 'Avenida 5 # 15-40', '1998-06-03', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(9, 'Diego Martínez', '1090909090', 'diego.martinez@example.com', '3006789012', 'Diagonal 2 # 7-20', '1987-02-14', 'admin_principal', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(10, 'Valeria Hernández', '1101010101', 'valeria.hernandez@example.com', '3005432109', 'Transversal 1 # 10-10', '1991-08-08', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(11, 'Andrés Torres', '1111111111', 'andres.torres@example.com', '3002109876', 'Calle 100 # 3-5', '1980-05-19', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(12, 'Isabella Castro', '1121212121', 'isabella.castro@example.com', '3009871234', 'Carrera 50 # 20-20', '1996-04-01', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(13, 'Sebastián Jiménez', '1131313131', 'sebastian.jimenez@example.com', '3006547890', 'Avenida 7 # 4-80', '1983-10-26', 'admin_admin', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(14, 'Camila Morales', '1141414141', 'camila.morales@example.com', '3003210987', 'Diagonal 9 # 18-30', '1994-03-17', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(15, 'Daniel Ortiz', '1151515151', 'daniel.ortiz@example.com', '3007654321', 'Transversal 11 # 6-60', '1989-07-09', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(16, 'Gabriela Guerrero', '1161616161', 'gabriela.guerrero@example.com', '3004321098', 'Calle 2 # 9-70', '1997-01-20', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(17, 'Javier Rojas', '1171717171', 'javier.rojas@example.com', '3001098765', 'Carrera 13 # 22-10', '1981-11-29', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(18, 'Mariana Vega', '1181818181', 'mariana.vega@example.com', '3008761234', 'Avenida 6 # 14-55', '1992-05-06', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(19, 'Felipe Serrano', '1191919191', 'felipe.serrano@example.com', '3005438765', 'Diagonal 88 # 1-1', '1975-02-02', 'admin_deportivo', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn'),
(20, 'Paula Navarro', '1202020202', 'paula.navarro@example.com', '3002105432', 'Transversal 5 # 3-45', '1999-10-15', 'socio', 1, '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghijklmn');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `torneo_id` (`torneo_id`);

--
-- Indices de la tabla `estadisticas`
--
ALTER TABLE `estadisticas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partido_id` (`partido_id`),
  ADD KEY `jugador_id` (`jugador_id`);

--
-- Indices de la tabla `historial_estados_socios`
--
ALTER TABLE `historial_estados_socios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_socio_id` (`socio_id`),
  ADD KEY `idx_fecha_cambio` (`fecha_cambio`),
  ADD KEY `idx_estado_nuevo` (`estado_nuevo`),
  ADD KEY `idx_usuario_id` (`usuario_id`);

--
-- Indices de la tabla `jugadores`
--
ALTER TABLE `jugadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipo_id` (`equipo_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `socio_id` (`socio_id`);

--
-- Indices de la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `torneo_id` (`torneo_id`),
  ADD KEY `equipo_local` (`equipo_local`),
  ADD KEY `equipo_visitante` (`equipo_visitante`);

--
-- Indices de la tabla `sanciones`
--
ALTER TABLE `sanciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jugador_id` (`jugador_id`),
  ADD KEY `partido_id` (`partido_id`);

--
-- Indices de la tabla `socios`
--
ALTER TABLE `socios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `torneos`
--
ALTER TABLE `torneos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documento` (`documento`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estadisticas`
--
ALTER TABLE `estadisticas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_estados_socios`
--
ALTER TABLE `historial_estados_socios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sanciones`
--
ALTER TABLE `sanciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD CONSTRAINT `equipos_ibfk_1` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`);

--
-- Filtros para la tabla `estadisticas`
--
ALTER TABLE `estadisticas`
  ADD CONSTRAINT `estadisticas_ibfk_1` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`),
  ADD CONSTRAINT `estadisticas_ibfk_2` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`);

--
-- Filtros para la tabla `historial_estados_socios`
--
ALTER TABLE `historial_estados_socios`
  ADD CONSTRAINT `fk_historial_socio` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `jugadores`
--
ALTER TABLE `jugadores`
  ADD CONSTRAINT `jugadores_ibfk_1` FOREIGN KEY (`id`) REFERENCES `socios` (`id`),
  ADD CONSTRAINT `jugadores_ibfk_2` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`);

--
-- Filtros para la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD CONSTRAINT `partidos_ibfk_1` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`),
  ADD CONSTRAINT `partidos_ibfk_2` FOREIGN KEY (`equipo_local`) REFERENCES `equipos` (`id`),
  ADD CONSTRAINT `partidos_ibfk_3` FOREIGN KEY (`equipo_visitante`) REFERENCES `equipos` (`id`);

--
-- Filtros para la tabla `sanciones`
--
ALTER TABLE `sanciones`
  ADD CONSTRAINT `sanciones_ibfk_1` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`),
  ADD CONSTRAINT `sanciones_ibfk_2` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`);

--
-- Filtros para la tabla `socios`
--
ALTER TABLE `socios`
  ADD CONSTRAINT `socios_ibfk_1` FOREIGN KEY (`id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
