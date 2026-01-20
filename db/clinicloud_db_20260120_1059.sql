-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-01-2026 a las 15:57:22
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `clinicloud_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aranceles`
--

CREATE TABLE `aranceles` (
  `idArancel` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `aranceles`
--

INSERT INTO `aranceles` (`idArancel`, `codigo`, `descripcion`, `precio`) VALUES
(1, 'CAR', 'LIMPIEZA DE CARIES', 150);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atencion_clinica`
--

CREATE TABLE `atencion_clinica` (
  `idAtencion` int(11) NOT NULL,
  `idPaciente` int(11) NOT NULL,
  `idConsultorio` int(11) NOT NULL,
  `fechaAtencion` datetime NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `fechaRegistro` datetime NOT NULL,
  `estadoAtencion` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultorios`
--

CREATE TABLE `consultorios` (
  `idConsultorio` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `descripcion` text NOT NULL,
  `especialidad` varchar(150) NOT NULL,
  `listaMedicos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`listaMedicos`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `consultorios`
--

INSERT INTO `consultorios` (`idConsultorio`, `codigo`, `descripcion`, `especialidad`, `listaMedicos`) VALUES
(1, 'ODON1', 'ODONTOLOGIA 1', 'ODONTOLOGIA', '{\"medicos\":[{\"idUsuario\":\"5\",\"ciUs\":\"10558875\",\"nombreUs\":\"ESTEBAN ADRIAN\",\"primerApUs\":\"GOMEZ \",\"segundoApUs\":\"SERAPIO\",\"celularUs\":\"60477779\",\"emailUs\":\"adrian.gs8e@gmail.com\",\"usuarioUs\":\"ADRIANGS\"}]}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuaderno_odontologia`
--

CREATE TABLE `cuaderno_odontologia` (
  `idCuaOdontologia` int(11) NOT NULL,
  `idAtencion` int(11) NOT NULL,
  `tipoAtencion` int(11) NOT NULL,
  `jsonExamenGenral` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`jsonExamenGenral`)),
  `jsonExamenBucoDental` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`jsonExamenBucoDental`)),
  `jsonHabitosCostumbres` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`jsonHabitosCostumbres`)),
  `motivoConsulta` text NOT NULL,
  `jsonRegistroClinico` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`jsonRegistroClinico`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuenta_paciente`
--

CREATE TABLE `cuenta_paciente` (
  `idCuentaPaciente` int(11) NOT NULL,
  `fechaHoraRegistro` datetime NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `idUsuarioRegistro` int(11) NOT NULL,
  `fechaHoraPago` datetime NOT NULL,
  `idUsuarioPago` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `idPaciente` int(11) NOT NULL,
  `ci` int(11) NOT NULL,
  `apellidoPat` varchar(100) NOT NULL,
  `apellidoMat` varchar(100) NOT NULL,
  `nombres` varchar(200) NOT NULL,
  `fechaNacimiento` date NOT NULL,
  `celular` varchar(20) NOT NULL,
  `email` text NOT NULL,
  `direccion` text NOT NULL,
  `procedencia` varchar(100) NOT NULL,
  `residencia` varchar(100) NOT NULL,
  `nombreTutor` text NOT NULL,
  `celularTutor` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`idPaciente`, `ci`, `apellidoPat`, `apellidoMat`, `nombres`, `fechaNacimiento`, `celular`, `email`, `direccion`, `procedencia`, `residencia`, `nombreTutor`, `celularTutor`) VALUES
(1, 10558875, 'GOMEZ', 'SERAPIO', 'ESTEBAN ADRIAN', '1995-05-24', '60477779', 'adrian.gs8e@gmail.com', 'AV SANTA CRUZ 324', 'TUPIZA', 'TUPIZA', '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `idUsuario` int(11) NOT NULL,
  `nombreUs` varchar(255) NOT NULL,
  `primerApUs` varchar(255) NOT NULL,
  `segundoApUs` varchar(255) NOT NULL,
  `fechaNacUs` date NOT NULL,
  `celularUs` varchar(20) NOT NULL,
  `ciUs` varchar(20) NOT NULL,
  `emailUs` varchar(255) NOT NULL,
  `usuarioUs` varchar(50) NOT NULL,
  `passwordUs` text NOT NULL,
  `perfilUs` varchar(255) DEFAULT NULL,
  `estadoUs` varchar(255) DEFAULT NULL,
  `cambioPass` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`idUsuario`, `nombreUs`, `primerApUs`, `segundoApUs`, `fechaNacUs`, `celularUs`, `ciUs`, `emailUs`, `usuarioUs`, `passwordUs`, `perfilUs`, `estadoUs`, `cambioPass`) VALUES
(5, 'ESTEBAN ADRIAN', 'GOMEZ ', 'SERAPIO', '1995-05-24', '60477779', '10558875', 'adrian.gs8e@gmail.com', 'ADRIANGS', '7773b02e5ccd7fa267f82bb801d776d94516eb85c966d1f009fda91fe18e620b', 'ADMINISTRADOR', 'ACTIVO', 'NO');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `aranceles`
--
ALTER TABLE `aranceles`
  ADD PRIMARY KEY (`idArancel`);

--
-- Indices de la tabla `atencion_clinica`
--
ALTER TABLE `atencion_clinica`
  ADD PRIMARY KEY (`idAtencion`);

--
-- Indices de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  ADD PRIMARY KEY (`idConsultorio`);

--
-- Indices de la tabla `cuaderno_odontologia`
--
ALTER TABLE `cuaderno_odontologia`
  ADD PRIMARY KEY (`idCuaOdontologia`);

--
-- Indices de la tabla `cuenta_paciente`
--
ALTER TABLE `cuenta_paciente`
  ADD PRIMARY KEY (`idCuentaPaciente`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`idPaciente`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idUsuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `aranceles`
--
ALTER TABLE `aranceles`
  MODIFY `idArancel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `atencion_clinica`
--
ALTER TABLE `atencion_clinica`
  MODIFY `idAtencion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  MODIFY `idConsultorio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cuaderno_odontologia`
--
ALTER TABLE `cuaderno_odontologia`
  MODIFY `idCuaOdontologia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cuenta_paciente`
--
ALTER TABLE `cuenta_paciente`
  MODIFY `idCuentaPaciente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `idPaciente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
