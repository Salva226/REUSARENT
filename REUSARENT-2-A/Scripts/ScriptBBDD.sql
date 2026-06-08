--Codigo para insertar todos las tablas y campos pero sin datos
-- TABLA USUARIO (Entidad padre)
CREATE TABLE IF NOT EXISTS usuario (
    DNI VARCHAR(9) PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol VARCHAR(20),
    telefono VARCHAR(15),
    direccion_facturacion VARCHAR(100),
    email VARCHAR(80) UNIQUE
) ENGINE=InnoDB;

-- TABLA ARRENDADOR
CREATE TABLE IF NOT EXISTS arrendador (
    id_arrendador INT AUTO_INCREMENT PRIMARY KEY,
    DNI VARCHAR(9),
    FOREIGN KEY (DNI) 
        REFERENCES usuario(DNI)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- TABLA ARRENDATARIO
CREATE TABLE IF NOT EXISTS arrendatario (
    id_arrendatario INT AUTO_INCREMENT PRIMARY KEY,
    DNI VARCHAR(9),
    FOREIGN KEY (DNI)
        REFERENCES usuario(DNI)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- TABLA CATEGORIA
CREATE TABLE IF NOT EXISTS categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(200)
) ENGINE=InnoDB;

-- TABLA ARTICULO
CREATE TABLE IF NOT EXISTS articulo (
    id_articulo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    precio DECIMAL(8,2) NOT NULL,
    foto LONGBLOB,
    id_categoria INT,
    id_arrendador INT,
    FOREIGN KEY (id_categoria)
        REFERENCES categoria(id_categoria),
    FOREIGN KEY (id_arrendador)
        REFERENCES arrendador(id_arrendador)
) ENGINE=InnoDB;

-- TABLA ALQUILER
CREATE TABLE IF NOT EXISTS alquiler (
    id_alquiler INT AUTO_INCREMENT PRIMARY KEY,
    id_articulo INT,
    id_arrendatario INT,
    fecha_alquiler DATE,
    vencimiento_alquiler DATE,
    FOREIGN KEY (id_articulo)
        REFERENCES articulo(id_articulo),
    FOREIGN KEY (id_arrendatario)
        REFERENCES arrendatario(id_arrendatario)
) ENGINE=InnoDB;

-- Tabla para insertar datos tambien

SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tablas si existen (en orden inverso por las claves foráneas)
DROP TABLE IF EXISTS `alquiler`;
DROP TABLE IF EXISTS `articulo`;
DROP TABLE IF EXISTS `categoria`;
DROP TABLE IF EXISTS `arrendatario`;
DROP TABLE IF EXISTS `arrendador`;
DROP TABLE IF EXISTS `usuario`;

SET FOREIGN_KEY_CHECKS = 1;

-- Estructura de tabla para la tabla `usuario`
CREATE TABLE `usuario` (
  `DNI` varchar(9) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` varchar(20) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion_facturacion` varchar(100) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `foto_perfil` longblob DEFAULT NULL,
  PRIMARY KEY (`DNI`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de tabla para la tabla `arrendador`
CREATE TABLE `arrendador` (
  `id_arrendador` int(11) NOT NULL AUTO_INCREMENT,
  `DNI` varchar(9) DEFAULT NULL,
  PRIMARY KEY (`id_arrendador`),
  KEY `DNI` (`DNI`),
  CONSTRAINT `arrendador_ibfk_1` FOREIGN KEY (`DNI`) REFERENCES `usuario` (`DNI`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de tabla para la tabla `arrendatario`
CREATE TABLE `arrendatario` (
  `id_arrendatario` int(11) NOT NULL AUTO_INCREMENT,
  `DNI` varchar(9) DEFAULT NULL,
  PRIMARY KEY (`id_arrendatario`),
  KEY `DNI` (`DNI`),
  CONSTRAINT `arrendatario_ibfk_1` FOREIGN KEY (`DNI`) REFERENCES `usuario` (`DNI`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de tabla para la tabla `categoria`
CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de tabla para la tabla `articulo`
CREATE TABLE `articulo` (
  `id_articulo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `precio` decimal(8,2) NOT NULL,
  `foto` longblob DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_arrendador` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_articulo`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_arrendador` (`id_arrendador`),
  CONSTRAINT `articulo_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  CONSTRAINT `articulo_ibfk_2` FOREIGN KEY (`id_arrendador`) REFERENCES `arrendador` (`id_arrendador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de tabla para la tabla `alquiler`
CREATE TABLE `alquiler` (
  `id_alquiler` int(11) NOT NULL AUTO_INCREMENT,
  `id_articulo` int(11) DEFAULT NULL,
  `id_arrendatario` int(11) DEFAULT NULL,
  `fecha_alquiler` date DEFAULT NULL,
  `vencimiento_alquiler` date DEFAULT NULL,
  PRIMARY KEY (`id_alquiler`),
  KEY `id_articulo` (`id_articulo`),
  KEY `id_arrendatario` (`id_arrendatario`),
  CONSTRAINT `alquiler_ibfk_1` FOREIGN KEY (`id_articulo`) REFERENCES `articulo` (`id_articulo`),
  CONSTRAINT `alquiler_ibfk_2` FOREIGN KEY (`id_arrendatario`) REFERENCES `arrendatario` (`id_arrendatario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Volcado de datos
INSERT INTO `usuario` (`DNI`, `usuario`, `contrasena`, `rol`, `telefono`, `direccion_facturacion`, `email`) VALUES
('12345678P', 'salvador09', '$2y$10$nihuLWZCVFHHjgDnHi0f4OLqPu1bDV3E4Y.CiMjnqRB6pmwcAlRNi', 'no-premium', 'dfdfdfdfd', NULL, 'salva@gmail.com'),
('87654321H', 'eduardo.garcia', '$2y$10$mt5anXipIUTBwnMfepfvkuMgfC17WLfIMr5tbgtTgHEoswcKzdLk.', 'no-premium', 'fdfdffdf', NULL, 'eddd@gmail.com');

INSERT INTO `arrendador` (`id_arrendador`, `DNI`) VALUES (7, '87654321H');
INSERT INTO `arrendatario` (`id_arrendatario`, `DNI`) VALUES (6, '12345678P');
INSERT INTO `categoria` (`id_categoria`, `nombre`, `descripcion`) VALUES (1, 'Herramientas', 'Equipos de construcción');

INSERT INTO `articulo` (`id_articulo`, `nombre`, `precio`, `foto`, `id_categoria`, `id_arrendador`) VALUES
(20, 'Andamio Reforzado', 35.50, NULL, 1, 7),
(21, 'Taladro Profesional X2', 18.00, NULL, 1, 7),
(22, 'Hormigonera Eléctrica 125L', 45.00, NULL, 1, 7),
(23, 'Motosierra Stihl MS 170', 25.50, NULL, 1, 7),
(24, 'Lijadora de Banda Makita', 12.00, NULL, 1, 7);

INSERT INTO `alquiler` (`id_alquiler`, `id_articulo`, `id_arrendatario`, `fecha_alquiler`, `vencimiento_alquiler`) VALUES
(7, 20, 6, '2024-03-01', '2024-03-15'),
(8, 21, 6, '2024-03-20', '2024-03-30'),
(9, 22, 6, '2024-03-22', '2024-04-05'),
(10, 23, 6, '2024-03-25', '2024-04-01'),
(11, 24, 6, '2024-03-26', '2024-04-10');