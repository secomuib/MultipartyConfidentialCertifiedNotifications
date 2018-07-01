-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-03-2018 a las 19:06:37
-- Versión del servidor: 10.1.30-MariaDB
-- Versión de PHP: 7.2.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `certified_mail_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dest_variables`
--

CREATE TABLE `dest_variables` (
  `Id` int(11) NOT NULL,
  `Id_destinatario` int(11) NOT NULL,
  `hB` text COLLATE utf32_spanish_ci NOT NULL,
  `hA` text COLLATE utf32_spanish_ci NOT NULL,
  `Ka` text COLLATE utf32_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensaje`
--

CREATE TABLE `mensaje` (
  `Id` int(11) NOT NULL,
  `Id_rem_var` int(11) NOT NULL,
  `Id_dest_var` int(11) NOT NULL,
  `Id_ttp` int(11) NOT NULL,
  `c` text COLLATE utf32_spanish_ci NOT NULL,
  `Hc` text COLLATE utf32_spanish_ci NOT NULL,
  `Kt` text COLLATE utf32_spanish_ci NOT NULL,
  `contract_address` text COLLATE utf32_spanish_ci NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rem_variables`
--

CREATE TABLE `rem_variables` (
  `Id` int(11) NOT NULL,
  `Id_remitente` int(11) NOT NULL,
  `hA` text COLLATE utf32_spanish_ci NOT NULL,
  `Ka` text COLLATE utf32_spanish_ci NOT NULL,
  `hB` text COLLATE utf32_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;
-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `Id` int(11) NOT NULL,
  `email` varchar(150) COLLATE utf32_spanish_ci NOT NULL,
  `password` varchar(256) COLLATE utf32_spanish_ci NOT NULL,
  `address` varchar(42) COLLATE utf32_spanish_ci NOT NULL,
  `signKey` text COLLATE utf32_spanish_ci NOT NULL,
  `ciphKey` text COLLATE utf32_spanish_ci NOT NULL,
  `TTP` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `dest_variables`
--
ALTER TABLE `dest_variables`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Id_destinatario` (`Id_destinatario`);

--
-- Indices de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Id_param_rem` (`Id_rem_var`),
  ADD KEY `Id_param_dest` (`Id_dest_var`,`Id_ttp`),
  ADD KEY `Id_ttp` (`Id_ttp`);

--
-- Indices de la tabla `rem_variables`
--
ALTER TABLE `rem_variables`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Id_remitente` (`Id_remitente`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `dest_variables`
--
ALTER TABLE `dest_variables`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;

--
-- AUTO_INCREMENT de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;

--
-- AUTO_INCREMENT de la tabla `rem_variables`
--
ALTER TABLE `rem_variables`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `dest_variables`
--
ALTER TABLE `dest_variables`
  ADD CONSTRAINT `dest_variables_ibfk_1` FOREIGN KEY (`Id_destinatario`) REFERENCES `usuario` (`Id`);

--
-- Filtros para la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD CONSTRAINT `mensaje_ibfk_2` FOREIGN KEY (`Id_rem_var`) REFERENCES `rem_variables` (`Id`),
  ADD CONSTRAINT `mensaje_ibfk_3` FOREIGN KEY (`Id_ttp`) REFERENCES `usuario` (`Id`),
  ADD CONSTRAINT `mensaje_ibfk_4` FOREIGN KEY (`Id_dest_var`) REFERENCES `dest_variables` (`Id`);

--
-- Filtros para la tabla `rem_variables`
--
ALTER TABLE `rem_variables`
  ADD CONSTRAINT `rem_variables_ibfk_1` FOREIGN KEY (`Id_remitente`) REFERENCES `usuario` (`Id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
