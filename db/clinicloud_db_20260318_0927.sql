-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-03-2026 a las 14:27:31
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
  `estadoAtencion` varchar(50) NOT NULL,
  `especialidad` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `atencion_clinica`
--

INSERT INTO `atencion_clinica` (`idAtencion`, `idPaciente`, `idConsultorio`, `fechaAtencion`, `idUsuario`, `fechaRegistro`, `estadoAtencion`, `especialidad`) VALUES
(1, 1, 1, '2026-02-09 00:26:48', 5, '2026-02-09 00:26:48', 'PENDIENTE', 'ODONTOLOGIA'),
(2, 1, 1, '2026-02-10 00:03:38', 5, '2026-02-10 00:03:38', 'PENDIENTE', 'ODONTOLOGIA'),
(3, 1, 1, '2026-02-20 20:36:23', 5, '2026-02-20 20:36:23', 'PENDIENTE', 'ODONTOLOGIA'),
(4, 1, 1, '2026-03-10 21:42:50', 5, '2026-03-10 21:42:50', 'PENDIENTE', 'ODONTOLOGIA'),
(5, 1, 1, '2026-03-15 02:45:48', 5, '2026-03-15 02:45:48', 'PENDIENTE', 'ODONTOLOGIA'),
(6, 1, 1, '2026-03-17 20:00:28', 5, '2026-03-17 20:00:28', 'PENDIENTE', 'ODONTOLOGIA'),
(7, 1, 1, '2026-03-18 00:15:45', 5, '2026-03-18 00:15:45', 'PENDIENTE', 'ODONTOLOGIA');

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
  `tipoAtencion` varchar(50) NOT NULL,
  `jsonInfoCuaderno` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `fechaRegistro` datetime NOT NULL,
  `idUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `cuaderno_odontologia`
--

INSERT INTO `cuaderno_odontologia` (`idCuaOdontologia`, `idAtencion`, `tipoAtencion`, `jsonInfoCuaderno`, `fechaRegistro`, `idUsuario`) VALUES
(9, 6, 'EXAMEN GENERAL', 'intervenido_quirurgicamente=NO&intervenido_quirurgicamente_obs=&problemas_cardiacos=SI&problemas_cardiacos_obs=&diabetico_obs=&alergia_medicamento_obs=&cicatrizacion_heridas_obs=&problemas_coagulacion_obs=&tratamiento_medico_actual_obs=&toma_medicamentos_obs=&embarazada_obs=&fum=&motivo_consulta=&frecuencia_cepillo=&respirador_bucal=SI&usa_chupon=SI', '2026-03-18 04:43:10', 5),
(10, 6, 'EXAMEN GENERAL', 'Array', '2026-03-18 04:45:45', 5),
(11, 6, 'EXAMEN GENERAL', '{\"intervenido_quirurgicamente\":\"SI\",\"intervenido_quirurgicamente_obs\":\"asdf\",\"problemas_cardiacos\":\"SI\",\"problemas_cardiacos_obs\":\"\",\"diabetico\":\"NO\",\"diabetico_obs\":\"\",\"alergia_medicamento\":\"NO\",\"alergia_medicamento_obs\":\"\",\"cicatrizacion_heridas\":\"NO\",\"cicatrizacion_heridas_obs\":\"\",\"problemas_coagulacion\":\"SI\",\"problemas_coagulacion_obs\":\"123\",\"tratamiento_medico_actual_obs\":\"\",\"toma_medicamentos_obs\":\"\",\"embarazada_obs\":\"\",\"fum\":\"\",\"motivo_consulta\":\"\",\"higiene_dental\":\"MALA\",\"usa_cepillo\":\"SI\",\"frecuencia_cepillo\":\"2\",\"respirador_bucal\":\"NO\",\"usa_chupon\":\"NO\",\"toma_alcohol\":\"NO\"}', '2026-03-18 04:48:56', 5),
(12, 7, 'EXAMEN GENERAL', '{\"intervenido_quirurgicamente\":\"NO\",\"intervenido_quirurgicamente_obs\":\"\",\"problemas_cardiacos\":\"NO\",\"problemas_cardiacos_obs\":\"\",\"diabetico\":\"SI\",\"diabetico_obs\":\"\",\"alergia_medicamento\":\"NO\",\"alergia_medicamento_obs\":\"\",\"cicatrizacion_heridas_obs\":\"\",\"problemas_coagulacion_obs\":\"\",\"tratamiento_medico_actual_obs\":\"\",\"toma_medicamentos_obs\":\"\",\"embarazada_obs\":\"\",\"fum\":\"\",\"motivo_consulta\":\"\",\"frecuencia_cepillo\":\"\",\"respirador_bucal\":\"SI\"}', '2026-03-18 05:15:54', 5),
(13, 7, 'EXAMEN GENERAL', '{\"intervenido_quirurgicamente\":\"SI\",\"intervenido_quirurgicamente_obs\":\"1234\",\"problemas_cardiacos\":\"NO\",\"problemas_cardiacos_obs\":\"asdf\",\"diabetico_obs\":\"\",\"alergia_medicamento_obs\":\"\",\"cicatrizacion_heridas_obs\":\"\",\"problemas_coagulacion_obs\":\"\",\"tratamiento_medico_actual_obs\":\"\",\"toma_medicamentos_obs\":\"\",\"embarazada\":\"NO\",\"embarazada_obs\":\"\",\"fum\":\"\",\"motivo_consulta\":\"tienee dolor de muela\",\"usa_cepillo\":\"SI\",\"frecuencia_cepillo\":\"3\"}', '2026-03-18 05:16:53', 5),
(14, 7, 'EXAMEN GENERAL', '{\"intervenido_quirurgicamente\":\"NO\",\"intervenido_quirurgicamente_obs\":\"\",\"problemas_cardiacos\":\"NO\",\"problemas_cardiacos_obs\":\"\",\"diabetico_obs\":\"\",\"alergia_medicamento_obs\":\"\",\"cicatrizacion_heridas\":\"SI\",\"cicatrizacion_heridas_obs\":\"asdf\",\"problemas_coagulacion_obs\":\"\",\"tratamiento_medico_actual\":\"SI\",\"tratamiento_medico_actual_obs\":\"\",\"toma_medicamentos_obs\":\"\",\"embarazada_obs\":\"\",\"fum\":\"\",\"motivo_consulta\":\"asdf\",\"frecuencia_cepillo\":\"\"}', '2026-03-18 05:21:36', 5),
(15, 7, 'EXAMEN GENERAL', '{\"intervenido_quirurgicamente\":\"NO\",\"intervenido_quirurgicamente_obs\":\"asdf\",\"problemas_cardiacos\":\"SI\",\"problemas_cardiacos_obs\":\"asdf\",\"diabetico\":\"NO\",\"diabetico_obs\":\"asdf\",\"alergia_medicamento\":\"SI\",\"alergia_medicamento_obs\":\"asdf\",\"cicatrizacion_heridas\":\"SI\",\"cicatrizacion_heridas_obs\":\"asdf\",\"problemas_coagulacion\":\"NO\",\"problemas_coagulacion_obs\":\"asdf\",\"tratamiento_medico_actual_obs\":\"\",\"toma_medicamentos_obs\":\"\",\"embarazada_obs\":\"\",\"fum\":\"2026-03-12\",\"motivo_consulta\":\"asdfdzxcv \",\"frecuencia_cepillo\":\"\"}', '2026-03-18 05:26:54', 5),
(16, 7, 'EXAMEN GENERAL', '{\"intervenido_quirurgicamente\":\"NO\",\"intervenido_quirurgicamente_obs\":\"\",\"problemas_cardiacos\":\"SI\",\"problemas_cardiacos_obs\":\"asdf\",\"diabetico_obs\":\"\",\"alergia_medicamento_obs\":\"\",\"cicatrizacion_heridas\":\"NO\",\"cicatrizacion_heridas_obs\":\"\",\"problemas_coagulacion\":\"SI\",\"problemas_coagulacion_obs\":\"\",\"tratamiento_medico_actual\":\"NO\",\"tratamiento_medico_actual_obs\":\"\",\"toma_medicamentos_obs\":\"\",\"embarazada\":\"NO\",\"embarazada_obs\":\"\",\"fum\":\"\",\"motivo_consulta\":\"asdf\",\"frecuencia_cepillo\":\"\",\"respirador_bucal\":\"SI\",\"usa_chupon\":\"SI\",\"fuma\":\"SI\",\"toma_alcohol\":\"SI\",\"masca_coca\":\"SI\"}', '2026-03-18 05:32:35', 5),
(17, 7, 'TRATAMIENTO MÉDICO', '{\"diagnostico\":\"Caries\",\"pieza\":\"15\",\"medicion\":\"3mm\",\"tratamiento\":\"es un tratamiento enorme\"}', '2026-03-18 05:53:58', 5),
(18, 7, 'TRATAMIENTO MÉDICO', '{\"diagnostico\":\"asdf\",\"pieza\":\"123\",\"medicion\":\"3asdf\",\"tratamiento\":\"asdf asdf asdf asdf asdf\"}', '2026-03-18 05:55:58', 5),
(19, 7, 'RAYOS X', '{\"descripcion\":\"archivo de radiografia\",\"rutaImagen\":\"storage\\/rayos_x\\/rayosx_7_1773810575.jpg\"}', '2026-03-18 06:09:35', 5),
(20, 7, 'SOLICITUD PROTESICO', '{\"id_medico_protesico\":\"15\",\"nombre_medico\":\"MEDICO MEDICO MEDICO\",\"detalle_protesico\":\"PRUEBA MEDICO\"}', '2026-03-18 07:11:42', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_atencion`
--

CREATE TABLE `orden_atencion` (
  `idOrdenAtencion` int(11) NOT NULL,
  `idPaciente` int(11) NOT NULL,
  `jsonDetallePrestaciones` text NOT NULL,
  `estado` varchar(50) NOT NULL,
  `montoTotal` float NOT NULL,
  `saldoPendiente` float NOT NULL,
  `fechaHoraRegistro` datetime NOT NULL,
  `idUsuarioRegistro` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `orden_atencion`
--

INSERT INTO `orden_atencion` (`idOrdenAtencion`, `idPaciente`, `jsonDetallePrestaciones`, `estado`, `montoTotal`, `saldoPendiente`, `fechaHoraRegistro`, `idUsuarioRegistro`) VALUES
(10, 1, '[{\"prestacion\":\"caries\",\"monto\":50},{\"prestacion\":\"tapadura\",\"monto\":150}]', 'ORDEN ATENCION', 200, 200, '2026-03-18 09:41:35', 5);

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
-- Estructura de tabla para la tabla `recibos`
--

CREATE TABLE `recibos` (
  `idRecibo` int(11) NOT NULL,
  `idPaciente` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `idOrdenAtencion` int(11) NOT NULL,
  `montoPagado` float NOT NULL,
  `saldoPendiente` float NOT NULL,
  `fechaRegistro` datetime NOT NULL,
  `estadoRecibo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `recibos`
--

INSERT INTO `recibos` (`idRecibo`, `idPaciente`, `idUsuario`, `idOrdenAtencion`, `montoPagado`, `saldoPendiente`, `fechaRegistro`, `estadoRecibo`) VALUES
(1, 0, 5, 0, 200, 0, '2026-02-09 00:27:16', 'PAGADO'),
(2, 1, 5, 0, 201, 0, '2026-02-09 00:29:36', 'PAGADO'),
(3, 1, 5, 0, 300, 0, '2026-02-09 00:46:11', 'PAGADO'),
(4, 1, 5, 5, 310, 0, '2026-02-09 00:47:59', 'PAGADO'),
(5, 1, 5, 6, 315, 0, '2026-02-09 01:20:53', 'PAGADO'),
(6, 1, 5, 8, 150, 0, '2026-02-09 23:37:14', 'PAGADO');

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
(5, 'ESTEBAN ADRIAN', 'GOMEZ ', 'SERAPIO', '1995-05-24', '60477779', '10558875', 'adrian.gs8e@gmail.com', 'ADRIANGS', '7773b02e5ccd7fa267f82bb801d776d94516eb85c966d1f009fda91fe18e620b', 'ADMINISTRADOR', 'ACTIVO', 'NO'),
(14, 'ADMIN', 'ADMIN', 'ADMIN', '2000-01-01', '123456', '123456', 'adrian.gs8e@gmail.com', 'ADMIN1', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'ADMINISTRADOR', 'ACTIVO', 'SI'),
(15, 'MEDICO', 'MEDICO', 'MEDICO', '2000-01-01', '123456', '123456789', 'ALGO', 'MEDICO1', '15e2b0d3c33891ebb0f1ef609ec419420c20e320ce94c65fbc8c3312448eb225', 'MEDICO', 'ACTIVO', 'SI');

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
-- Indices de la tabla `orden_atencion`
--
ALTER TABLE `orden_atencion`
  ADD PRIMARY KEY (`idOrdenAtencion`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`idPaciente`);

--
-- Indices de la tabla `recibos`
--
ALTER TABLE `recibos`
  ADD PRIMARY KEY (`idRecibo`);

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
  MODIFY `idAtencion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  MODIFY `idConsultorio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cuaderno_odontologia`
--
ALTER TABLE `cuaderno_odontologia`
  MODIFY `idCuaOdontologia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `orden_atencion`
--
ALTER TABLE `orden_atencion`
  MODIFY `idOrdenAtencion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `idPaciente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `recibos`
--
ALTER TABLE `recibos`
  MODIFY `idRecibo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
