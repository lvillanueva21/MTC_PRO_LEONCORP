-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 17-03-2026 a las 19:36:43
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u517204426_pruebabrevete`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `al_alertas`
--

CREATE TABLE `al_alertas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(160) NOT NULL,
  `categoria` varchar(80) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo` enum('ONCE','MONTHLY','YEARLY','INTERVAL') NOT NULL DEFAULT 'ONCE',
  `intervalo_dias` int(10) UNSIGNED DEFAULT NULL,
  `fecha_base` datetime NOT NULL,
  `anticipacion_dias` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `al_alertas`
--

INSERT INTO `al_alertas` (`id`, `id_empresa`, `titulo`, `categoria`, `descripcion`, `tipo`, `intervalo_dias`, `fecha_base`, `anticipacion_dias`, `activo`, `creado`, `actualizado`) VALUES
(1, 19, 'Pagar la luz', 'pagos', 'Debo pagar la luz el dia 05 a las 17:00 hrs', 'INTERVAL', 20, '2026-03-05 16:27:00', 0, 1, '2026-03-05 16:32:33', '2026-03-05 22:25:49'),
(2, 19, 'Pago alquiler local', 'Pagos', 'Alquiler oficina principal', 'MONTHLY', 0, '2026-03-12 10:00:00', 3, 1, '2026-03-05 16:39:07', '2026-03-05 22:25:40'),
(3, 19, 'Renovación licencia municipal', 'Documentos', 'Renovar antes del vencimiento', 'YEARLY', 0, '2026-04-20 09:00:00', 15, 1, '2026-03-05 16:40:24', '2026-03-05 16:40:24'),
(4, 19, 'f', 'f', 'asdas', 'ONCE', 0, '2026-03-05 18:53:00', 0, 1, '2026-03-05 18:53:22', '2026-03-05 18:53:22'),
(5, 19, 'Pagar el disco HDD 4TB EN DELTRON', 'hdd', 'Se debe pagar en deltron hdd 4tb de deltron Se debe pagar en deltron hdd 4tb de deltron Se debe pagar en deltron hdd 4tb de deltron Se debe pagar en deltron hdd 4tb de deltron', 'ONCE', 0, '2026-03-05 20:37:00', 0, 1, '2026-03-05 20:31:51', '2026-03-05 20:35:01'),
(6, 19, 'adsadasdasdasdasdasdasdsd', 'Documentos', 'asdasdasdasd', 'MONTHLY', 0, '2026-03-05 20:36:00', 0, 1, '2026-03-05 20:34:43', '2026-03-05 20:34:43'),
(7, 19, 'PAGAR LUZ', '', 'Tengo que pagar la luz con la empresa enosa', 'MONTHLY', 0, '2026-03-30 11:43:00', 2, 1, '2026-03-10 11:44:37', '2026-03-10 11:44:37'),
(8, 19, 'PAGAR ALQUILER', 'pagos', 'Tengo que pagar a la señora del alquiler.', 'MONTHLY', 0, '2026-03-14 18:00:00', 1, 1, '2026-03-13 10:40:29', '2026-03-13 10:40:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `al_alertas_log`
--

CREATE TABLE `al_alertas_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_alerta` int(10) UNSIGNED NOT NULL,
  `evento` enum('CREATED','UPDATED','FIRED','DISMISSED','TOGGLED') NOT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `al_alertas_log`
--

INSERT INTO `al_alertas_log` (`id`, `id_alerta`, `evento`, `detalle`, `creado`) VALUES
(1, 1, 'CREATED', NULL, '2026-03-05 16:32:33'),
(2, 1, 'TOGGLED', NULL, '2026-03-05 16:37:10'),
(3, 2, 'CREATED', NULL, '2026-03-05 16:39:07'),
(4, 2, 'UPDATED', NULL, '2026-03-05 16:39:21'),
(5, 3, 'CREATED', NULL, '2026-03-05 16:40:24'),
(6, 1, 'TOGGLED', NULL, '2026-03-05 16:40:45'),
(7, 4, 'CREATED', NULL, '2026-03-05 18:53:22'),
(8, 2, 'TOGGLED', NULL, '2026-03-05 20:17:23'),
(9, 2, 'TOGGLED', NULL, '2026-03-05 20:17:25'),
(10, 5, 'CREATED', NULL, '2026-03-05 20:31:51'),
(11, 5, 'UPDATED', NULL, '2026-03-05 20:33:04'),
(12, 6, 'CREATED', NULL, '2026-03-05 20:34:43'),
(13, 5, 'UPDATED', NULL, '2026-03-05 20:35:01'),
(14, 2, 'UPDATED', NULL, '2026-03-05 22:25:40'),
(15, 1, 'UPDATED', NULL, '2026-03-05 22:25:49'),
(16, 7, 'CREATED', NULL, '2026-03-10 11:44:37'),
(17, 8, 'CREATED', NULL, '2026-03-13 10:40:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cam_camaras`
--

CREATE TABLE `cam_camaras` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `link_externo` varchar(255) DEFAULT NULL,
  `link_local` varchar(255) DEFAULT NULL,
  `color_bg` varchar(7) NOT NULL DEFAULT '#000000',
  `color_text` varchar(7) NOT NULL DEFAULT '#ffffff',
  `creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cam_camaras`
--

INSERT INTO `cam_camaras` (`id`, `id_empresa`, `nombre`, `link_externo`, `link_local`, `color_bg`, `color_text`, `creacion`, `actualizacion`) VALUES
(16, 17, 'Guía mis rutas', 'http://guiasmisrutas.dvrdns.org:2010', 'http://192.168.18.101:2010', '#171616', '#ffffff', '2026-01-22 23:25:45', '2026-01-23 15:53:33'),
(18, 25, 'Allain Prost La Merced', 'http://181.64.27.190:700', 'http://192.168.0.50:80', '#ff060e', '#ffffff', '2026-03-09 19:18:51', '2026-03-09 19:19:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cam_camaras_usuarios`
--

CREATE TABLE `cam_camaras_usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_camara` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cam_hdd`
--

CREATE TABLE `cam_hdd` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_camara` int(10) UNSIGNED NOT NULL,
  `marca` varchar(100) NOT NULL,
  `nro_serie` varchar(100) NOT NULL,
  `capacidad_gb` int(10) UNSIGNED NOT NULL,
  `estado` enum('INSTALADO','RETIRADO') NOT NULL DEFAULT 'INSTALADO',
  `fecha_instalacion` datetime NOT NULL,
  `nota_instalacion` varchar(500) DEFAULT NULL,
  `fecha_retiro` datetime DEFAULT NULL,
  `responsable_retiro` varchar(150) DEFAULT NULL,
  `fecha_inicio_grab` datetime DEFAULT NULL,
  `fecha_fin_grab` datetime DEFAULT NULL,
  `nota_retiro` varchar(500) DEFAULT NULL,
  `creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cam_hdd`
--

INSERT INTO `cam_hdd` (`id`, `id_camara`, `marca`, `nro_serie`, `capacidad_gb`, `estado`, `fecha_instalacion`, `nota_instalacion`, `fecha_retiro`, `responsable_retiro`, `fecha_inicio_grab`, `fecha_fin_grab`, `nota_retiro`, `creacion`, `actualizacion`) VALUES
(14, 16, 'WD PURPLE', '00200148', 4096, 'RETIRADO', '2025-03-17 18:40:00', '', '2026-02-11 09:08:00', 'MIGUEL GUTIERREZ', '2025-03-17 18:40:00', '2026-02-06 17:56:00', '', '2026-01-26 10:00:03', '2026-02-11 09:08:35'),
(19, 16, 'WD PURPLE', '00200148', 6144, 'INSTALADO', '2026-02-06 17:57:00', '', NULL, NULL, NULL, NULL, NULL, '2026-02-11 09:09:19', '2026-02-11 09:09:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cam_hdd_consumo`
--

CREATE TABLE `cam_hdd_consumo` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_hdd` int(10) UNSIGNED NOT NULL,
  `fecha_dia` date NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `tipo` enum('LIBRE','USADO') NOT NULL DEFAULT 'LIBRE',
  `valor_gb` int(10) UNSIGNED NOT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cam_hdd_consumo`
--

INSERT INTO `cam_hdd_consumo` (`id`, `id_hdd`, `fecha_dia`, `fecha_registro`, `tipo`, `valor_gb`, `nota`, `creacion`, `actualizacion`) VALUES
(19, 14, '2026-01-26', '2026-01-26 10:00:00', 'LIBRE', 153, '', '2026-01-26 10:00:06', '2026-01-26 10:00:06'),
(35, 14, '2026-01-27', '2026-01-27 09:47:00', 'LIBRE', 142, '', '2026-01-27 09:47:54', '2026-01-27 09:47:54'),
(52, 14, '2026-01-28', '2026-01-28 08:46:00', 'LIBRE', 129, '', '2026-01-28 08:46:29', '2026-01-28 08:46:29'),
(70, 14, '2026-01-29', '2026-01-29 16:58:00', 'LIBRE', 112, '', '2026-01-29 16:58:27', '2026-01-29 16:58:27'),
(113, 14, '2026-02-02', '2026-02-02 09:07:00', 'LIBRE', 76, '', '2026-02-04 09:07:43', '2026-02-04 09:07:43'),
(114, 14, '2026-02-03', '2026-02-03 09:07:00', 'LIBRE', 63, '', '2026-02-04 09:07:48', '2026-02-04 09:07:48'),
(115, 14, '2026-02-04', '2026-02-04 09:07:00', 'LIBRE', 54, '', '2026-02-04 09:08:05', '2026-02-04 09:08:05'),
(141, 14, '2026-02-05', '2026-02-05 09:06:00', 'LIBRE', 42, '', '2026-02-11 09:06:12', '2026-02-11 09:06:30'),
(143, 14, '2026-02-06', '2026-02-06 09:06:00', 'LIBRE', 32, '', '2026-02-11 09:06:39', '2026-02-11 09:06:39'),
(144, 19, '2026-02-09', '2026-02-09 09:09:00', 'LIBRE', 5532, '', '2026-02-11 09:10:27', '2026-02-11 09:10:27'),
(145, 19, '2026-02-10', '2026-02-10 09:10:00', 'LIBRE', 5520, '', '2026-02-11 09:10:37', '2026-02-11 09:10:37'),
(146, 19, '2026-02-11', '2026-02-11 09:10:00', 'LIBRE', 5509, '', '2026-02-11 09:10:53', '2026-02-11 09:10:53'),
(214, 19, '2026-02-12', '2026-02-12 12:58:00', 'LIBRE', 5494, '', '2026-02-12 12:58:18', '2026-02-12 12:58:18'),
(278, 19, '2026-02-13', '2026-02-13 10:06:00', 'LIBRE', 5485, '', '2026-02-18 10:07:06', '2026-02-18 10:07:06'),
(279, 19, '2026-02-16', '2026-02-16 10:07:00', 'LIBRE', 5453, '', '2026-02-18 10:07:11', '2026-02-18 10:07:11'),
(280, 19, '2026-02-17', '2026-02-17 10:07:00', 'LIBRE', 5441, '', '2026-02-18 10:07:16', '2026-02-18 10:07:16'),
(281, 19, '2026-02-18', '2026-02-18 10:07:00', 'LIBRE', 5431, '', '2026-02-18 10:07:21', '2026-02-18 10:07:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ce_componentes`
--

CREATE TABLE `ce_componentes` (
  `id` int(10) UNSIGNED NOT NULL,
  `plantilla_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('TEXT','TEXTAREA','NUMBER','DATE','BOOL','SELECT','STATIC_TEXT','STATIC_IMAGE','NUMERACION') NOT NULL,
  `nombre` varchar(60) DEFAULT NULL,
  `etiqueta` varchar(120) DEFAULT NULL,
  `requerido` tinyint(1) NOT NULL DEFAULT 0,
  `orden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `texto_fijo` text DEFAULT NULL,
  `imagen_path` varchar(255) DEFAULT NULL,
  `num_prefijo` varchar(20) DEFAULT NULL,
  `num_longitud` smallint(5) UNSIGNED DEFAULT NULL,
  `num_siguiente` int(10) UNSIGNED DEFAULT NULL,
  `min_len` smallint(5) UNSIGNED DEFAULT NULL,
  `max_len` smallint(5) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ce_componente_opciones`
--

CREATE TABLE `ce_componente_opciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `componente_id` int(10) UNSIGNED NOT NULL,
  `valor` varchar(120) NOT NULL,
  `etiqueta` varchar(120) NOT NULL,
  `orden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ce_plantillas`
--

CREATE TABLE `ce_plantillas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `orientacion` enum('H','V') NOT NULL DEFAULT 'H',
  `creado_por` int(10) UNSIGNED NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `com_comunicados`
--

CREATE TABLE `com_comunicados` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(300) NOT NULL,
  `cuerpo` longtext DEFAULT NULL,
  `imagen_path` varchar(255) DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `fecha_limite` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `com_comunicado_target`
--

CREATE TABLE `com_comunicado_target` (
  `id` int(10) UNSIGNED NOT NULL,
  `comunicado_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('TODOS','USUARIO','ROL','EMPRESA','EMPRESA_ROL') NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `rol_id` int(10) UNSIGNED DEFAULT NULL,
  `empresa_id` int(10) UNSIGNED DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `com_comunicado_vista`
--

CREATE TABLE `com_comunicado_vista` (
  `id` int(10) UNSIGNED NOT NULL,
  `comunicado_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `visto` tinyint(1) NOT NULL DEFAULT 0,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `visto_en` datetime DEFAULT NULL,
  `leido_en` datetime DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cq_categorias_licencia`
--

CREATE TABLE `cq_categorias_licencia` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `tipo_categoria` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cq_categorias_licencia`
--

INSERT INTO `cq_categorias_licencia` (`id`, `codigo`, `tipo_categoria`) VALUES
(1, 'A-I', 'A'),
(2, 'A-IIa', 'A'),
(3, 'A-IIb', 'A'),
(4, 'A-IIIa', 'A'),
(5, 'A-IIIb', 'A'),
(6, 'A-IIIc', 'A'),
(7, 'B-I', 'B'),
(8, 'B-IIa', 'B'),
(9, 'B-IIb', 'B'),
(10, 'B-IIc', 'B');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cq_certificados`
--

CREATE TABLE `cq_certificados` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_usuario_emisor` int(10) UNSIGNED NOT NULL,
  `id_curso` int(10) UNSIGNED NOT NULL,
  `id_plantilla_certificado` int(10) UNSIGNED NOT NULL,
  `id_tipo_doc` tinyint(3) UNSIGNED NOT NULL,
  `id_categoria_licencia` smallint(5) UNSIGNED DEFAULT NULL,
  `correlativo_empresa` int(10) UNSIGNED NOT NULL,
  `codigo_certificado` varchar(16) NOT NULL,
  `nombres_cliente` varchar(100) NOT NULL,
  `apellidos_cliente` varchar(100) NOT NULL,
  `documento_cliente` varchar(20) NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `horas_teoricas` smallint(5) UNSIGNED DEFAULT NULL,
  `horas_practicas` smallint(5) UNSIGNED DEFAULT NULL,
  `estado` enum('Activo','Inactivo','Vencido') NOT NULL DEFAULT 'Activo',
  `codigo_qr` char(32) NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cq_certificados`
--

INSERT INTO `cq_certificados` (`id`, `id_empresa`, `id_usuario_emisor`, `id_curso`, `id_plantilla_certificado`, `id_tipo_doc`, `id_categoria_licencia`, `correlativo_empresa`, `codigo_certificado`, `nombres_cliente`, `apellidos_cliente`, `documento_cliente`, `fecha_emision`, `fecha_inicio`, `fecha_fin`, `horas_teoricas`, `horas_practicas`, `estado`, `codigo_qr`, `creado`, `actualizado`) VALUES
(1, 2, 2, 1, 1, 1, 3, 1, '000001', 'ALMA DE JESUS', 'SANTOS ALVAREZ', '48067143', '2026-01-22', '2026-01-22', '2026-01-22', 4, 4, 'Inactivo', 'ec3fb180616bc7f086fe3bab40d00765', '2026-01-22 16:19:35', '2026-01-22 16:32:45'),
(2, 3, 3, 14, 3, 1, 4, 1, '000001', 'WILMER HERNAN', 'YOVERA SILVA', '03698061', '2026-01-23', '2026-01-21', '2026-01-23', 6, 6, 'Activo', 'f370c8ba169fa484d8cc234695f3d744', '2026-01-23 09:08:05', '2026-01-23 09:08:05'),
(3, 3, 3, 1, 3, 1, 6, 2, '000002', 'JUAN FRANCISCO', 'ALBURQUEQUE LOPEZ', '03854874', '2026-01-26', '2026-01-23', '2026-01-24', 6, 6, 'Activo', '45f651e28f38ba40d9cbed30bb3bd7e1', '2026-01-26 11:56:19', '2026-01-26 11:56:19'),
(4, 3, 3, 1, 3, 1, 6, 3, '000003', 'MARCO ARNALDO', 'BRICEÑO REYES', '05643219', '2026-01-26', '2026-01-23', '2026-01-24', 6, 6, 'Activo', 'a52afa8785fc538f54f4427f69200e76', '2026-01-26 11:58:25', '2026-01-26 11:58:25'),
(5, 3, 3, 1, 3, 1, 6, 4, '000004', 'PALOMINO RUIZ', 'JORGE LUIS', '02671304', '2026-01-26', '2026-01-23', '2026-01-24', 6, 6, 'Activo', 'f0af93f8fdb9bf517b68217eed7f236f', '2026-01-26 11:59:55', '2026-01-26 11:59:55'),
(6, 3, 3, 1, 3, 1, 6, 5, '000005', 'DARWIN IVAN', 'ASANZA MOGOLLON', '40599123', '2026-01-24', '2026-01-23', '2026-01-24', 6, 6, 'Activo', '1ba77549fe6395383117281e0b8a7fcd', '2026-01-26 12:24:28', '2026-01-26 12:24:28'),
(7, 3, 3, 27, 3, 1, 6, 6, '000006', 'DARWIN IVAN', 'ASANZA MOGOLLON', '40599123', '2026-01-10', '2026-01-09', '2026-01-10', 6, 6, 'Activo', 'd4822ed0d68c18e0da5e8172657b85ee', '2026-01-26 12:31:10', '2026-01-26 12:44:49'),
(8, 3, 3, 28, 3, 1, 6, 7, '000007', 'DARWIN IVAN', 'ASANZA MOGOLLON', '40599123', '2026-01-17', '2026-01-16', '2026-01-17', 6, 6, 'Activo', 'ffb5a0021e3f4d7d13ad29ff3c6da367', '2026-01-26 12:32:08', '2026-01-26 12:32:08'),
(9, 3, 3, 1, 3, 1, 6, 8, '000008', 'JOSE GUALBERTO', 'AYALA MARCELO', '02897891', '2026-01-24', '2026-01-23', '2026-01-24', 6, 6, 'Activo', 'b6607da40a27da9c56baf75244b64740', '2026-01-26 12:36:28', '2026-01-26 12:36:28'),
(10, 3, 3, 27, 3, 1, 6, 9, '000009', 'JOSE GUALBERTO', 'AYALA MARCELO', '02897891', '2026-01-10', '2026-01-09', '2026-01-10', 6, 6, 'Activo', 'cc45ef7012896c833e28500e69b8bbd6', '2026-01-26 12:37:16', '2026-01-26 12:49:18'),
(11, 3, 3, 28, 3, 1, 6, 10, '000010', 'JOSE GUALBERTO', 'AYALA MARCELO', '02897891', '2026-01-17', '2026-01-16', '2026-01-17', 6, 6, 'Activo', '7aa4176b7cf9ab5b27d821dba298594c', '2026-01-26 12:39:29', '2026-01-26 12:49:22'),
(12, 3, 3, 1, 3, 1, 6, 11, '000011', 'JAIME ALBERTO', 'VALLADARES VEGA', '03890902', '2026-01-24', '2026-01-23', '2026-01-24', 6, 6, 'Activo', 'd6fb6a63169f269f330fc2e3fb0c8677', '2026-01-26 12:41:31', '2026-01-26 12:41:31'),
(13, 3, 3, 28, 3, 1, 6, 12, '000012', 'JAIME ALBERTO', 'VALLADARES VEGA', '03890902', '2026-01-17', '2026-01-16', '2026-01-17', 6, 6, 'Activo', '4be9e74a8d760ac95958bb5cbba2021b', '2026-01-26 12:42:57', '2026-01-26 12:45:12'),
(14, 3, 3, 27, 3, 1, 6, 13, '000013', 'JAIME ALBERTO', 'VALLADARES VEGA', '03890902', '2026-01-10', '2026-01-09', '2026-01-10', 6, 6, 'Activo', '96311edbb3287bd95a443186e28960aa', '2026-01-26 12:43:34', '2026-01-26 12:45:06'),
(15, 2, 2, 3, 1, 1, 3, 2, '000002', 'FRANS SEGUNDO', 'LABRIN CESPEDES', '44710169', '2026-01-27', '2026-01-26', '2026-01-27', 6, 6, 'Activo', '4b7b0c94cd56fc5e09ddb2f8ae646534', '2026-01-27 09:41:02', '2026-01-27 09:41:02'),
(16, 3, 3, 1, 3, 1, 6, 14, '000014', 'LUIS ALBERTO', 'ZURITA QUINDE', '47754242', '2026-01-29', '2026-01-28', '2026-01-29', 6, 6, 'Activo', 'aba3aa92bf003b39340fc8f0751e7040', '2026-01-29 11:18:14', '2026-01-29 11:18:14'),
(17, 3, 3, 1, 3, 1, 1, 15, '000015', 'OSCAR DANY', 'VALDERRAMA ROMERO', '43734255', '2026-01-29', '2026-01-28', '2026-01-29', 6, 6, 'Activo', 'a71bdfcfc011576f51b34030a4586015', '2026-01-29 17:41:34', '2026-01-29 17:41:34'),
(18, 2, 2, 1, 1, 1, 5, 3, '000003', 'CARLOS ROBERTSON', 'DAMIAN SANTISTEBAN', '74658432', '2026-01-30', '2026-01-26', '2026-01-28', 12, 12, 'Activo', 'c57aec144fa081c6bd160042bea37ab0', '2026-01-30 07:45:01', '2026-01-30 07:45:01'),
(19, 3, 3, 7, 3, 1, 1, 16, '000016', 'LUIS GERARDO', 'OLIVOS ECHE', '71920127', '2026-01-30', '2026-01-29', '2026-01-30', 6, 6, 'Activo', '3a6e9b809eae083fa025145026555afa', '2026-01-30 09:37:41', '2026-01-30 09:37:41'),
(20, 3, 3, 27, 3, 1, 3, 17, '000017', 'EDY ALBERTO', 'PEÑA CAMPOS', '45055169', '2026-02-02', '2026-01-26', '2026-01-30', 15, 15, 'Activo', '5673c04953fbb2e2f714a2ab0d5bfbaa', '2026-02-02 12:44:39', '2026-02-02 12:47:57'),
(21, 3, 3, 1, 3, 1, 6, 18, '000018', 'LEONCIO', 'ANTON ZETA', '03697087', '2026-02-03', '2026-02-02', '2026-02-03', 6, 6, 'Activo', 'f1be5604cac3c9c47916ce072516154a', '2026-02-03 15:49:15', '2026-02-03 15:49:15'),
(22, 3, 3, 27, 3, 3, 1, 19, '000019', 'DARWIN MANUEL', 'REYES MARTINEZ', '006574673', '2026-02-02', '2026-01-26', '2026-01-30', 15, 15, 'Activo', '8c2d6a64af6e109b7f4a72d01e4761e0', '2026-02-03 17:02:03', '2026-02-03 17:02:03'),
(23, 3, 3, 1, 3, 1, 6, 20, '000020', 'ROBERTO ADRIANO', 'CASTILLO CARRASCO', '41455998', '2026-02-06', '2026-02-04', '2026-02-05', 6, 6, 'Activo', '79fb3ae58985fa44651d3ddd7e230709', '2026-02-06 18:49:10', '2026-02-06 18:49:10'),
(24, 2, 2, 1, 1, 1, 5, 4, '000004', 'REYNER', 'SANCHEZ DELGADO', '42555231', '2026-02-06', '2026-02-05', '2026-02-06', 6, 6, 'Activo', 'b18f69b4153729dcbfe64a4b65b9dc12', '2026-02-09 14:00:10', '2026-02-09 14:00:10'),
(25, 3, 3, 5, 3, 1, 6, 21, '000021', 'JUAN MANUEL', 'BOLIVAR GARCIA', '49085411', '2026-02-09', '2026-02-06', '2026-02-07', 6, 6, 'Activo', 'e8ef6aba0305abf2b73b3b6858a4c324', '2026-02-09 17:22:28', '2026-02-09 17:22:28'),
(26, 3, 3, 1, 3, 1, 1, 22, '000022', 'ALEX DANNY', 'DE LA CRUZ MACHARE', '45517922', '2026-02-10', '2026-02-09', '2026-02-10', 6, 6, 'Activo', 'f9d3663daa4bcc6f7c56fed5097a8d43', '2026-02-10 10:43:19', '2026-02-10 10:43:19'),
(27, 2, 2, 1, 1, 1, 3, 5, '000005', 'WILDER HERNAN', 'BURGOS PALACIOS', '42293768', '2026-01-21', '2026-01-19', '2026-01-21', 16, 8, 'Activo', '6e70bb7d6ad59a07a9ad56180f400d93', '2026-02-11 15:13:59', '2026-02-11 15:13:59'),
(28, 3, 3, 1, 3, 1, 6, 23, '000023', 'JORGE MARTIN', 'LALUPU RAMOS', '03853924', '2026-02-12', '2026-02-11', '2026-02-12', 6, 6, 'Activo', 'ece9828b0614b320d39566bc32e66c8c', '2026-02-12 16:27:47', '2026-02-12 16:27:47'),
(29, 3, 3, 1, 3, 1, 3, 24, '000024', 'CARLOS CESAR', 'CHUNA SARANGO', '41666917', '2026-02-12', '2026-02-11', '2026-02-12', 6, 6, 'Activo', 'db91ab994f49264795d4a802dcc9cf76', '2026-02-12 16:29:49', '2026-02-12 16:29:49'),
(30, 3, 3, 1, 3, 1, 1, 25, '000025', 'CESAR JORDY', 'RAMIREZ VARGAS MACHUCA', '72711138', '2026-02-12', '2026-02-11', '2026-02-12', 6, 6, 'Activo', 'd71820959b6f0400e853ecc7b3fd83f6', '2026-02-13 10:02:21', '2026-02-13 10:02:21'),
(31, 2, 2, 12, 1, 1, 3, 6, '000006', 'DAMIAN ALFREDO', 'MANAYAY CAJO', '47192426', '2025-12-18', '2025-12-16', '2025-12-18', 12, 12, 'Activo', '6f231c0bf8e753e1515dd2ae37af0c2c', '2026-02-16 09:52:14', '2026-02-16 09:52:14'),
(32, 2, 2, 1, 1, 1, 3, 7, '000007', 'DAMIAN ALFREDO', 'MANAYAY CAJO', '47192426', '2026-01-16', '2026-01-14', '2026-01-16', 16, 8, 'Activo', '92d7a44cab4dc87a297b692d6b42aa15', '2026-02-16 09:56:40', '2026-02-16 09:56:40'),
(33, 3, 3, 3, 3, 1, 5, 26, '000026', 'EGAR', 'ERAS SALDARRIAGA', '00328308', '2026-02-16', '2026-02-16', '2026-02-16', 8, 0, 'Activo', '7d627c21391c2b8f2486824b927a95ca', '2026-02-16 16:05:04', '2026-02-16 16:05:04'),
(34, 2, 2, 3, 1, 1, 3, 8, '000008', 'JOSE ANTONIO', 'BANCES RIOJAS', '76077953', '2026-02-17', '2026-02-17', '2026-02-17', 6, 2, 'Activo', '8e302ef5367fcab5ee3b37ede75e7e9a', '2026-02-17 17:34:27', '2026-02-17 17:34:27'),
(35, 2, 2, 3, 1, 1, 3, 9, '000009', 'JUAN DEMETRIO', 'DAMIAN BALDERA', '42372761', '2026-02-17', '2026-02-17', '2026-02-17', 6, 2, 'Activo', 'c17fee4eb463d09d9195a4ddadb93915', '2026-02-17 17:35:32', '2026-02-17 17:35:32'),
(36, 3, 3, 1, 3, 1, 6, 27, '000027', 'JESUS OMAR', 'CALDERON QUIROGA', '45926540', '2026-02-20', '2026-02-18', '2026-02-20', 6, 6, 'Activo', '6caca709711941dd9ac57569eedc4764', '2026-02-20 16:09:39', '2026-02-20 16:09:39'),
(37, 2, 2, 2, 4, 1, NULL, 10, '000010', 'JOSE PERCY', 'DIAZ CHAVARRY', '43343972', '2026-02-20', '2026-02-19', '2026-02-20', 8, 8, 'Activo', '4d591e0a4079e544ef6d8d826b4b364f', '2026-02-20 16:51:10', '2026-02-20 16:51:10'),
(38, 3, 3, 1, 3, 1, 6, 28, '000028', 'IRWING JOEL', 'PINGO CHERRE', '46222845', '2026-02-23', '2026-02-19', '2026-02-21', 6, 6, 'Activo', 'b7e06458ad2278bc0c74fc889dd6a49f', '2026-02-23 11:08:28', '2026-02-23 11:08:28'),
(39, 3, 3, 1, 3, 1, 5, 29, '000029', 'CARLOS RUBEN', 'FERIA TANDAZO', '42073094', '2026-02-23', '2026-02-20', '2026-02-21', 6, 6, 'Activo', 'e2b13b8a1188d57ff09a68a3a27a58ef', '2026-02-23 12:55:00', '2026-02-23 12:55:00'),
(40, 3, 3, 1, 3, 1, 6, 30, '000030', 'RAFAEL OMAR', 'CABRERA BURGOS', '41267088', '2026-02-02', '2026-01-30', '2026-01-31', 6, 6, 'Activo', '668bad35786f1062e899282cb03e26df', '2026-02-23 12:59:28', '2026-02-23 12:59:28'),
(41, 3, 3, 1, 3, 1, 3, 31, '000031', 'ESLEYTER MARINO', 'RIVEROS RAMIREZ', '72210715', '2026-02-23', '2026-02-20', '2026-02-21', 6, 6, 'Activo', '2b6048073fac529ea7c4b983741c4fa4', '2026-02-23 13:05:17', '2026-02-23 13:05:17'),
(42, 2, 2, 2, 4, 1, NULL, 11, '000011', 'HENRY OSWALDO', 'RAMIREZ LANDEO', '44283429', '2026-02-24', '2026-02-23', '2026-02-24', 8, 8, 'Activo', '54fae2e20426d70542f79735eb4dca68', '2026-02-25 10:47:23', '2026-02-25 10:48:38'),
(43, 2, 2, 1, 4, 1, 6, 12, '000012', 'CARLOS FRANKLIN', 'BECERRA HERNANDEZ', '43401462', '2026-02-26', '2026-02-25', '2026-02-26', 8, 4, 'Activo', '4837884c102c0a194cdfdd12472a1244', '2026-02-27 11:12:08', '2026-02-27 11:12:08'),
(44, 2, 2, 3, 4, 1, 3, 13, '000013', 'FRANKLIN', 'TORRES SUYON', '16750250', '2026-02-28', '2026-02-26', '2026-02-28', 16, 8, 'Activo', '91b9f345d6a45ccdfe4bafcc1af3206e', '2026-02-27 12:08:24', '2026-02-27 12:14:01'),
(45, 3, 3, 1, 3, 1, 6, 32, '000032', 'ALDO FRANCHESCO', 'SAAVEDRA MORENO', '03508240', '2026-02-27', '2026-02-24', '2026-02-26', 6, 6, 'Activo', 'ee30a1ed1cf29a77f1aa152c80b43f61', '2026-02-27 17:50:34', '2026-02-27 17:50:34'),
(46, 3, 3, 1, 3, 1, 1, 33, '000033', 'ANGEL FERNANDO', 'GANOZA SAAVEDRA', '40936919', '2026-03-02', '2026-02-27', '2026-02-28', 6, 6, 'Activo', '1fe185af4af7608df7c85a61403e2667', '2026-03-02 10:00:26', '2026-03-02 10:00:26'),
(47, 3, 3, 1, 3, 1, 1, 34, '000034', 'JESUS JOEL', 'REQUENA DIOSES', '42391272', '2026-03-02', '2026-02-27', '2026-02-28', 6, 6, 'Activo', '6c563bb40ffc5c5b9d12a03e649e914d', '2026-03-02 10:04:05', '2026-03-02 10:04:05'),
(48, 3, 3, 1, 3, 1, 3, 35, '000035', 'BELUPU FLORES', 'EDWIN JHOEL', '46964030', '2026-03-02', '2026-02-27', '2026-02-28', 6, 6, 'Activo', '1231f582b21c734800bee88cf5818294', '2026-03-02 12:23:24', '2026-03-02 12:23:24'),
(49, 3, 3, 1, 3, 1, 6, 36, '000036', 'JOSE DE LOS SANTOS', 'PEÑA SANTA CRUZ', '03239363', '2026-03-03', '2026-03-02', '2026-03-03', 6, 6, 'Activo', 'e0443a0c8eeabe11ace23048050f1b1d', '2026-03-03 16:58:35', '2026-03-03 16:58:35'),
(50, 3, 3, 1, 3, 1, 1, 37, '000037', 'ROGER RICHARD', 'CHIROQUE SERNAQUE', '40513865', '2026-03-05', '2026-03-03', '2026-03-04', 6, 6, 'Activo', 'b735ffb95de562e0bf7a5f475bb23741', '2026-03-05 13:30:53', '2026-03-05 13:30:53'),
(51, 3, 3, 1, 3, 1, 5, 38, '000038', 'RENZO MARTIN', 'VELASQUEZ SANDOVAL', '80490294', '2026-03-06', '2026-03-04', '2026-03-05', 6, 6, 'Activo', '9bb2cf28ffd8001c21af1797d3e51c1e', '2026-03-05 15:44:10', '2026-03-05 15:44:10'),
(52, 3, 3, 3, 3, 1, 6, 39, '000039', 'JULIO CESAR', 'CRUZ AGURTO', '03877504', '2026-03-06', '2026-03-05', '2026-03-05', 4, 4, 'Activo', '76004ea0f228e680625e3dd547776605', '2026-03-06 18:11:50', '2026-03-06 18:11:50'),
(53, 3, 3, 3, 3, 1, 1, 40, '000040', 'IRVIN WILFREDO', 'PAJUELO LUCIANO', '46474698', '2026-03-06', '2026-03-05', '2026-03-06', 4, 4, 'Activo', 'b51145d54dcdd0c6b8adc48c031ae8a8', '2026-03-06 18:19:34', '2026-03-06 18:19:34'),
(54, 19, 10, 1, 6, 1, 3, 1, '000001', 'DELIA', 'VEGA BAZAN', '70009876', '2026-03-10', '2026-03-02', '2026-03-06', 4, 8, 'Activo', 'e03e812df6dd59177d9bca360317b637', '2026-03-10 12:19:33', '2026-03-10 12:19:33'),
(55, 19, 10, 1, 6, 1, 2, 2, '000002', 'LUIGI', 'VILLANUEVA PEREZ', '70379885', '2026-03-13', '2026-03-02', '2026-03-06', 6, 6, 'Activo', 'e7ccb83d1acf0335718ab5338fb21717', '2026-03-13 10:35:25', '2026-03-13 10:35:25'),
(56, 19, 10, 1, 6, 1, 6, 3, '000003', 'VICTOR', 'AGUILAR', '70332326', '2026-03-13', '2026-03-13', '2026-03-13', 12, 8, 'Activo', '154fdb90ff9c0d6b43ce305ba9d87111', '2026-03-13 21:36:23', '2026-03-13 21:36:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cq_plantillas_certificados`
--

CREATE TABLE `cq_plantillas_certificados` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `paginas` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `representante` varchar(200) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `resolucion` varchar(200) DEFAULT NULL,
  `fondo_path` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `firma_path` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cq_plantillas_certificados`
--

INSERT INTO `cq_plantillas_certificados` (`id`, `nombre`, `paginas`, `id_empresa`, `representante`, `ciudad`, `resolucion`, `fondo_path`, `logo_path`, `firma_path`, `activo`, `creado`, `actualizado`) VALUES
(1, 'GC CHICLAYO - FIRMA VICENTE', 1, 2, 'ALMA', 'Chiclayo', 'Resolución Directoral N°412-2021-MTC/17.03', 'almacen/2026/01/22/fondo_certificado/fondo-certificado-20260122T102108-a09471.png', 'almacen/2026/01/22/logo_certificado/logo-certificado-20260122T102108-682170.png', 'almacen/2026/02/17/firma_representante/firma-representante-plantilla-1-20260217T174227-6e01ef.png', 1, '2026-01-22 10:21:08', '2026-02-17 17:53:08'),
(3, 'GC PIURA - PRINCIPAL', 1, 3, 'MILCA SARAI CASTILLO SUXCE', 'Piura', 'Resolución Directoral N°0790-2023-MTC/17.03', 'almacen/2026/01/22/fondo_certificado/fondo-certificado-plantilla-3-20260122T182148-aec145.png', 'almacen/2026/01/22/logo_certificado/logo_certificado-20260122_181621-6657.png', 'almacen/2026/01/22/firma_representante/firma-representante-plantilla-3-20260122T182148-e77cf5.png', 1, '2026-01-22 18:16:21', '2026-01-22 18:21:48'),
(4, 'GC CHICLAYO - FIRMA ALMA', 1, 2, 'ALMA DE JESUS SANTOS ALVAREZ', 'Chiclayo', 'Resolución Directoral N°412-2021-MTC/17.03', 'almacen/2026/02/17/fondo_certificado/fondo_certificado-20260217_174813-3901.png', 'almacen/2026/02/17/logo_certificado/logo_certificado-20260217_174813-6372.png', 'almacen/2026/02/17/firma_representante/firma-representante-plantilla-4-20260217T175222-257068.png', 1, '2026-02-17 17:48:13', '2026-02-17 17:52:51'),
(5, 'GLobal Car La Libertad', 1, 12, 'SEGUNDO MANUEL ALVAREZ ACOSTA', 'Tujillo', '-', 'almacen/2026/02/27/fondo_certificado/fondo_certificado-20260227_091704-1517.png', 'almacen/2026/02/27/logo_certificado/logo-certificado-plantilla-5-20260227T104310-9ac317.png', 'almacen/2026/02/27/firma_representante/firma-representante-plantilla-5-20260227T103915-f355dc.png', 1, '2026-02-27 09:17:04', '2026-02-27 10:43:10'),
(6, 'GC CHICLAYO - FIRMA ALMA (Copia)', 1, 19, 'ALMA DE JESUS SANTOS ALVAREZ', 'Chiclayo', 'Resolución Directoral N°412-2021-MTC/17.03', 'almacen/2026/03/10/fondo_certificado/fondo_certificado-20260310_121742-4625.png', 'almacen/2026/03/10/logo_certificado/logo_certificado-20260310_121742-6128.png', 'almacen/2026/03/10/firma_representante/firma_representante-20260310_121742-8091.png', 1, '2026-03-10 12:17:42', '2026-03-10 12:17:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cq_plantillas_elementos`
--

CREATE TABLE `cq_plantillas_elementos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_plantilla_certificado` int(10) UNSIGNED NOT NULL,
  `codigo_elemento` varchar(50) NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cq_plantillas_elementos`
--

INSERT INTO `cq_plantillas_elementos` (`id`, `id_plantilla_certificado`, `codigo_elemento`, `creado`) VALUES
(116, 1, 'plantilla_resolucion', '2026-01-22 15:39:40'),
(117, 1, 'nombre_completo', '2026-01-22 15:39:40'),
(118, 1, 'curso', '2026-01-22 15:39:40'),
(119, 1, 'detalle_curso', '2026-01-22 15:39:40'),
(120, 1, 'codigo_certificado', '2026-01-22 15:39:40'),
(121, 1, 'representante', '2026-01-22 15:39:40'),
(122, 1, 'fecha_caducidad', '2026-01-22 15:39:40'),
(123, 1, 'ciudad_fecha_emision', '2026-01-22 15:39:40'),
(124, 1, 'qr', '2026-01-22 15:39:40'),
(140, 3, 'ciudad_fecha_emision', '2026-01-22 18:16:21'),
(141, 3, 'codigo_certificado', '2026-01-22 18:16:21'),
(142, 3, 'curso', '2026-01-22 18:16:21'),
(143, 3, 'detalle_curso', '2026-01-22 18:16:21'),
(144, 3, 'fecha_caducidad', '2026-01-22 18:16:21'),
(145, 3, 'nombre_completo', '2026-01-22 18:16:21'),
(146, 3, 'plantilla_resolucion', '2026-01-22 18:16:21'),
(147, 3, 'qr', '2026-01-22 18:16:21'),
(148, 3, 'representante', '2026-01-22 18:16:21'),
(149, 4, 'ciudad_fecha_emision', '2026-02-17 17:48:13'),
(150, 4, 'codigo_certificado', '2026-02-17 17:48:13'),
(151, 4, 'curso', '2026-02-17 17:48:13'),
(152, 4, 'detalle_curso', '2026-02-17 17:48:13'),
(153, 4, 'fecha_caducidad', '2026-02-17 17:48:13'),
(154, 4, 'nombre_completo', '2026-02-17 17:48:13'),
(155, 4, 'plantilla_resolucion', '2026-02-17 17:48:13'),
(156, 4, 'qr', '2026-02-17 17:48:13'),
(157, 4, 'representante', '2026-02-17 17:48:13'),
(158, 5, 'ciudad_fecha_emision', '2026-02-27 09:17:04'),
(159, 5, 'codigo_certificado', '2026-02-27 09:17:04'),
(160, 5, 'curso', '2026-02-27 09:17:04'),
(161, 5, 'detalle_curso', '2026-02-27 09:17:04'),
(162, 5, 'fecha_caducidad', '2026-02-27 09:17:04'),
(163, 5, 'nombre_completo', '2026-02-27 09:17:04'),
(164, 5, 'plantilla_resolucion', '2026-02-27 09:17:04'),
(165, 5, 'qr', '2026-02-27 09:17:04'),
(166, 5, 'representante', '2026-02-27 09:17:04'),
(173, 6, 'ciudad_fecha_emision', '2026-03-10 12:17:42'),
(174, 6, 'codigo_certificado', '2026-03-10 12:17:42'),
(175, 6, 'curso', '2026-03-10 12:17:42'),
(176, 6, 'detalle_curso', '2026-03-10 12:17:42'),
(177, 6, 'fecha_caducidad', '2026-03-10 12:17:42'),
(178, 6, 'nombre_completo', '2026-03-10 12:17:42'),
(179, 6, 'plantilla_resolucion', '2026-03-10 12:17:42'),
(180, 6, 'qr', '2026-03-10 12:17:42'),
(181, 6, 'representante', '2026-03-10 12:17:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cq_plantillas_posiciones`
--

CREATE TABLE `cq_plantillas_posiciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_plantilla_certificado` int(10) UNSIGNED NOT NULL,
  `codigo_elemento` varchar(50) NOT NULL,
  `pagina` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `pos_x` decimal(5,2) NOT NULL,
  `pos_y` decimal(5,2) NOT NULL,
  `ancho` decimal(5,2) NOT NULL DEFAULT 0.00,
  `ejemplo_texto` varchar(255) DEFAULT NULL,
  `font_size` smallint(5) UNSIGNED DEFAULT NULL,
  `font_bold` tinyint(1) NOT NULL DEFAULT 0,
  `font_align` char(1) DEFAULT NULL,
  `font_family` varchar(50) DEFAULT NULL,
  `font_color` varchar(7) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cq_plantillas_posiciones`
--

INSERT INTO `cq_plantillas_posiciones` (`id`, `id_plantilla_certificado`, `codigo_elemento`, `pagina`, `pos_x`, `pos_y`, `ancho`, `ejemplo_texto`, `font_size`, `font_bold`, `font_align`, `font_family`, `font_color`, `creado`, `actualizado`) VALUES
(574, 3, 'logo', 1, 12.00, 16.00, 16.00, NULL, 0, 0, NULL, NULL, NULL, '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(575, 3, 'firma', 1, 75.00, 73.00, 21.00, NULL, 0, 0, NULL, NULL, NULL, '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(576, 3, 'qr', 1, 11.00, 78.00, 12.00, NULL, 0, 0, NULL, NULL, NULL, '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(577, 3, 'plantilla_nombre', 1, 50.00, 50.00, 40.00, 'CERTIFICADO DE CAPACITACIÓN', 100, 0, 'C', NULL, '#000000', '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(578, 3, 'plantilla_resolucion', 1, 89.00, 9.00, 40.00, 'Res. Directoral N.° 123-2025-MTC/15', 80, 0, 'C', NULL, '#FFFFFF', '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(579, 3, 'nombre_completo', 1, 54.00, 34.00, 40.00, 'VILLANUEVA PEREZ LUIGI ISRAEL', 180, 1, 'C', NULL, '#B88923', '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(580, 3, 'curso', 1, 54.00, 47.00, 40.00, 'MANEJO DEFENSIVO', 150, 1, 'C', NULL, '#000000', '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(581, 3, 'detalle_curso', 1, 54.00, 54.00, 40.00, 'Identificado(a) con DNI N.º 41893357 y categoría AIIIC, ha participado en el curso realizado el 05 de noviembre de 2025, cumpliendo satisfactoriamente con una duración total de 08 horas teóricas y 08 horas prácticas.', 130, 0, 'J', NULL, '#000000', '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(582, 3, 'codigo_certificado', 1, 37.00, 79.00, 40.00, 'CERT-2025-000123', 110, 1, 'C', NULL, '#AD0000', '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(583, 3, 'representante', 1, 75.00, 85.00, 40.00, 'JUAN PEREZ GARCÍA', 110, 1, 'C', NULL, '#000000', '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(584, 3, 'fecha_caducidad', 1, 37.00, 93.00, 40.00, 'Válido hasta: 15/11/2026', 140, 0, 'C', NULL, '#000000', '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(585, 3, 'ciudad_fecha_emision', 1, 75.00, 93.00, 40.00, 'Lima, 15 de noviembre de 2025', 140, 0, 'C', NULL, '#000000', '2026-01-22 18:23:03', '2026-01-22 18:23:03'),
(586, 1, 'logo', 1, 12.00, 16.00, 16.00, NULL, 0, 0, NULL, NULL, NULL, '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(587, 1, 'firma', 1, 75.00, 76.00, 21.00, NULL, 0, 0, NULL, NULL, NULL, '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(588, 1, 'qr', 1, 11.00, 78.00, 12.00, NULL, 0, 0, NULL, NULL, NULL, '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(589, 1, 'plantilla_nombre', 1, 50.00, 50.00, 40.00, 'CERTIFICADO DE CAPACITACIÓN', 100, 0, 'C', NULL, '#000000', '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(590, 1, 'plantilla_resolucion', 1, 89.00, 9.00, 40.00, 'Res. Directoral N.° 123-2025-MTC/15', 80, 0, 'C', NULL, '#FFFFFF', '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(591, 1, 'nombre_completo', 1, 54.00, 34.00, 40.00, 'VILLANUEVA PEREZ LUIGI ISRAEL', 170, 1, 'C', NULL, '#B88923', '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(592, 1, 'curso', 1, 54.00, 47.00, 40.00, 'MANEJO DEFENSIVO', 150, 1, 'C', NULL, '#000000', '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(593, 1, 'detalle_curso', 1, 54.00, 54.00, 40.00, 'Identificado(a) con DNI N.º 41893357 y categoría AIIIC, ha participado en el curso realizado el 05 de noviembre de 2025, cumpliendo satisfactoriamente con una duración total de 08 horas teóricas y 08 horas prácticas.', 130, 0, 'J', NULL, '#000000', '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(594, 1, 'codigo_certificado', 1, 37.00, 79.00, 40.00, 'CERT-2025-000123', 110, 1, 'C', NULL, '#AD0000', '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(595, 1, 'representante', 1, 75.00, 85.00, 40.00, 'JUAN PEREZ GARCÍA', 110, 1, 'C', NULL, '#000000', '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(596, 1, 'fecha_caducidad', 1, 37.00, 93.00, 40.00, 'Válido hasta: 15/11/2026', 140, 0, 'C', NULL, '#000000', '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(597, 1, 'ciudad_fecha_emision', 1, 75.00, 93.00, 40.00, 'Lima, 15 de noviembre de 2025', 140, 0, 'C', NULL, '#000000', '2026-01-27 09:53:46', '2026-01-27 09:53:46'),
(598, 4, 'ciudad_fecha_emision', 1, 75.00, 93.00, 40.00, 'Lima, 15 de noviembre de 2025', 140, 0, 'C', NULL, '#000000', '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(599, 4, 'codigo_certificado', 1, 37.00, 79.00, 40.00, 'CERT-2025-000123', 110, 1, 'C', NULL, '#AD0000', '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(600, 4, 'curso', 1, 54.00, 47.00, 40.00, 'MANEJO DEFENSIVO', 150, 1, 'C', NULL, '#000000', '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(601, 4, 'detalle_curso', 1, 54.00, 54.00, 40.00, 'Identificado(a) con DNI N.º 41893357 y categoría AIIIC, ha participado en el curso realizado el 05 de noviembre de 2025, cumpliendo satisfactoriamente con una duración total de 08 horas teóricas y 08 horas prácticas.', 130, 0, 'J', NULL, '#000000', '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(602, 4, 'fecha_caducidad', 1, 37.00, 93.00, 40.00, 'Válido hasta: 15/11/2026', 140, 0, 'C', NULL, '#000000', '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(603, 4, 'firma', 1, 75.00, 76.00, 21.00, NULL, 0, 0, NULL, NULL, NULL, '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(604, 4, 'logo', 1, 12.00, 16.00, 16.00, NULL, 0, 0, NULL, NULL, NULL, '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(605, 4, 'nombre_completo', 1, 54.00, 34.00, 40.00, 'VILLANUEVA PEREZ LUIGI ISRAEL', 170, 1, 'C', NULL, '#B88923', '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(606, 4, 'plantilla_nombre', 1, 50.00, 50.00, 40.00, 'CERTIFICADO DE CAPACITACIÓN', 100, 0, 'C', NULL, '#000000', '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(607, 4, 'plantilla_resolucion', 1, 89.00, 9.00, 40.00, 'Res. Directoral N.° 123-2025-MTC/15', 80, 0, 'C', NULL, '#FFFFFF', '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(608, 4, 'qr', 1, 11.00, 78.00, 12.00, NULL, 0, 0, NULL, NULL, NULL, '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(609, 4, 'representante', 1, 75.00, 85.00, 40.00, 'JUAN PEREZ GARCÍA', 110, 1, 'C', NULL, '#000000', '2026-02-17 17:48:13', '2026-02-17 17:48:13'),
(610, 5, 'ciudad_fecha_emision', 1, 75.00, 93.00, 40.00, 'Lima, 15 de noviembre de 2025', 140, 0, 'C', NULL, '#000000', '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(611, 5, 'codigo_certificado', 1, 37.00, 79.00, 40.00, 'CERT-2025-000123', 110, 1, 'C', NULL, '#AD0000', '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(612, 5, 'curso', 1, 54.00, 47.00, 40.00, 'MANEJO DEFENSIVO', 150, 1, 'C', NULL, '#000000', '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(613, 5, 'detalle_curso', 1, 54.00, 54.00, 40.00, 'Identificado(a) con DNI N.º 41893357 y categoría AIIIC, ha participado en el curso realizado el 05 de noviembre de 2025, cumpliendo satisfactoriamente con una duración total de 08 horas teóricas y 08 horas prácticas.', 130, 0, 'J', NULL, '#000000', '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(614, 5, 'fecha_caducidad', 1, 37.00, 93.00, 40.00, 'Válido hasta: 15/11/2026', 140, 0, 'C', NULL, '#000000', '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(615, 5, 'firma', 1, 75.00, 73.00, 21.00, NULL, 0, 0, NULL, NULL, NULL, '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(616, 5, 'logo', 1, 12.00, 16.00, 16.00, NULL, 0, 0, NULL, NULL, NULL, '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(617, 5, 'nombre_completo', 1, 54.00, 34.00, 40.00, 'VILLANUEVA PEREZ LUIGI ISRAEL', 180, 1, 'C', NULL, '#B88923', '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(618, 5, 'plantilla_nombre', 1, 50.00, 50.00, 40.00, 'CERTIFICADO DE CAPACITACIÓN', 100, 0, 'C', NULL, '#000000', '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(619, 5, 'plantilla_resolucion', 1, 89.00, 9.00, 40.00, 'Res. Directoral N.° 123-2025-MTC/15', 80, 0, 'C', NULL, '#FFFFFF', '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(620, 5, 'qr', 1, 11.00, 78.00, 12.00, NULL, 0, 0, NULL, NULL, NULL, '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(621, 5, 'representante', 1, 75.00, 85.00, 40.00, 'JUAN PEREZ GARCÍA', 110, 1, 'C', NULL, '#000000', '2026-02-27 09:17:04', '2026-02-27 09:17:04'),
(625, 6, 'ciudad_fecha_emision', 1, 75.00, 93.00, 40.00, 'Lima, 15 de noviembre de 2025', 140, 0, 'C', NULL, '#000000', '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(626, 6, 'codigo_certificado', 1, 37.00, 79.00, 40.00, 'CERT-2025-000123', 110, 1, 'C', NULL, '#AD0000', '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(627, 6, 'curso', 1, 54.00, 47.00, 40.00, 'MANEJO DEFENSIVO', 150, 1, 'C', NULL, '#000000', '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(628, 6, 'detalle_curso', 1, 54.00, 54.00, 40.00, 'Identificado(a) con DNI N.º 41893357 y categoría AIIIC, ha participado en el curso realizado el 05 de noviembre de 2025, cumpliendo satisfactoriamente con una duración total de 08 horas teóricas y 08 horas prácticas.', 130, 0, 'J', NULL, '#000000', '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(629, 6, 'fecha_caducidad', 1, 37.00, 93.00, 40.00, 'Válido hasta: 15/11/2026', 140, 0, 'C', NULL, '#000000', '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(630, 6, 'firma', 1, 75.00, 76.00, 21.00, NULL, 0, 0, NULL, NULL, NULL, '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(631, 6, 'logo', 1, 12.00, 16.00, 16.00, NULL, 0, 0, NULL, NULL, NULL, '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(632, 6, 'nombre_completo', 1, 54.00, 34.00, 40.00, 'VILLANUEVA PEREZ LUIGI ISRAEL', 170, 1, 'C', NULL, '#B88923', '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(633, 6, 'plantilla_nombre', 1, 50.00, 50.00, 40.00, 'CERTIFICADO DE CAPACITACIÓN', 100, 0, 'C', NULL, '#000000', '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(634, 6, 'plantilla_resolucion', 1, 89.00, 9.00, 40.00, 'Res. Directoral N.° 123-2025-MTC/15', 80, 0, 'C', NULL, '#FFFFFF', '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(635, 6, 'qr', 1, 11.00, 78.00, 12.00, NULL, 0, 0, NULL, NULL, NULL, '2026-03-10 12:17:42', '2026-03-10 12:17:42'),
(636, 6, 'representante', 1, 75.00, 85.00, 40.00, 'JUAN PEREZ GARCÍA', 110, 1, 'C', NULL, '#000000', '2026-03-10 12:17:42', '2026-03-10 12:17:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cq_tipos_documento`
--

CREATE TABLE `cq_tipos_documento` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `codigo` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cq_tipos_documento`
--

INSERT INTO `cq_tipos_documento` (`id`, `codigo`) VALUES
(2, 'BREVETE'),
(3, 'CE'),
(1, 'DNI');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_cursos`
--

CREATE TABLE `cr_cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `imagen_path` varchar(255) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_cursos`
--

INSERT INTO `cr_cursos` (`id`, `nombre`, `descripcion`, `activo`, `imagen_path`, `creado`, `actualizado`) VALUES
(1, 'MANEJO DEFENSIVO', '', 1, NULL, '2026-01-22 01:51:50', '2026-01-22 01:51:50'),
(2, 'MANEJO A LA DEFENSIVA', '', 1, NULL, '2026-01-22 01:51:55', '2026-01-22 01:51:55'),
(3, 'MANEJO DEFENSIVO 4x4', '', 1, NULL, '2026-01-22 01:52:01', '2026-01-22 01:52:01'),
(4, 'MANEJO DEFENSIVO EN 4x4', '', 1, NULL, '2026-01-22 01:52:05', '2026-01-22 01:52:05'),
(5, 'MANEJO DEFENSIVO EN CAMIONETA 4x4', '', 1, NULL, '2026-01-22 01:52:09', '2026-01-22 01:52:09'),
(6, 'MANEJO DEFENSIVO 4x4 PRÁCTICO Y TEÓRICO', '', 1, NULL, '2026-01-22 01:52:13', '2026-01-22 01:52:13'),
(7, 'MANEJO DEFENSIVO Y SEGURIDAD VIAL', '', 1, NULL, '2026-01-22 01:52:17', '2026-01-22 01:52:17'),
(8, 'MANEJO DEFENSIVO Y SEGURIDAD VIAL (CAMIONETA 4x4)', '', 1, NULL, '2026-01-22 01:52:22', '2026-01-22 01:52:22'),
(9, 'MANEJO DEFENSIVO Y CONDUCCIÓN SEGURA EN CAMIONETA 4x4', '', 1, NULL, '2026-01-22 01:52:26', '2026-01-22 01:52:26'),
(10, 'MANEJO 4x4, CAMIONETAS 4x4, PARA RUTAS DE VÍAS INEXISTENTES, AGRESTES Y/O PROYECTOS NUEVOS', '', 1, NULL, '2026-01-22 01:52:31', '2026-01-22 01:52:31'),
(11, 'TRANSPORTE EN CISTERNA', '', 1, NULL, '2026-01-22 01:52:35', '2026-01-22 01:52:35'),
(12, 'MECÁNICA BÁSICA', 'Aprende los fundamentos de la mecánica automotriz desde cero. En este curso conocerás las partes principales de un vehículo, el funcionamiento del motor, sistemas de frenos, suspensión, transmisión y mantenimiento preventivo. Ideal para principiantes que desean comprender cómo funciona un automóvil y realizar revisiones básicas.', 1, 'almacen/img_cursos/20260306-mecanica-basica-cur000012.png', '2026-01-22 01:52:39', '2026-03-06 09:20:31'),
(13, 'MECÁNICA BÁSICA AUTOMOTRIZ', '', 1, NULL, '2026-01-22 01:52:44', '2026-01-22 01:52:44'),
(14, 'CURSO BÁSICO DE MECÁNICA GENERAL Y AUTOMOTRIZ', '', 1, NULL, '2026-01-22 01:52:47', '2026-01-22 01:52:47'),
(15, 'MECÁNICA AUTOMOTRIZ Y REGLAS DE TRÁNSITO', '', 1, NULL, '2026-01-22 01:52:50', '2026-01-22 01:52:50'),
(16, 'CAPACITACIÓN EN NORMAS DE TRÁNSITO Y MECÁNICA BÁSICA', '', 1, NULL, '2026-01-22 01:52:54', '2026-01-22 01:52:54'),
(17, 'CAPACITACIÓN EN REGLAS DE TRÁNSITO', '', 1, NULL, '2026-01-22 01:52:57', '2026-01-22 01:52:57'),
(18, 'REGLAS Y RUTAS DE TRÁNSITO', '', 1, NULL, '2026-01-22 01:53:00', '2026-01-22 01:53:00'),
(19, 'REGLAMENTO NACIONAL DE TRÁNSITO', '', 1, NULL, '2026-01-22 01:53:06', '2026-01-22 01:53:06'),
(20, 'NORMAS DE TRÁNSITO', '', 1, NULL, '2026-01-22 01:53:11', '2026-01-22 01:53:11'),
(21, 'SEGURIDAD VIAL', '', 1, NULL, '2026-01-22 01:53:15', '2026-01-22 01:53:15'),
(22, 'SEGURIDAD VIAL Y SENSIBILIZACIÓN DEL INFRACTOR', '', 1, NULL, '2026-01-22 01:53:20', '2026-01-22 01:53:20'),
(23, 'SEGURIDAD VIAL Y NORMAS DE TRÁNSITO', '', 1, NULL, '2026-01-22 01:53:24', '2026-01-22 01:53:24'),
(24, 'PRIMEROS AUXILIOS', '', 1, NULL, '2026-01-22 01:53:27', '2026-01-22 01:53:27'),
(25, 'IPERC', '', 1, NULL, '2026-01-22 01:53:31', '2026-01-22 01:53:31'),
(26, 'GESTIÓN DE SEGURIDAD Y TRANSPORTE EN MATERIALES PELIGROSOS MATPEL NIVEL I-II', '', 1, NULL, '2026-01-22 01:53:36', '2026-01-22 01:53:36'),
(27, 'MATPEL I', '', 1, NULL, '2026-01-22 01:53:40', '2026-01-22 01:53:40'),
(28, 'MATPEL II', '', 1, NULL, '2026-01-22 01:53:44', '2026-01-22 01:53:44'),
(29, 'MATPEL III', '', 1, NULL, '2026-01-22 01:53:50', '2026-01-22 01:53:50'),
(30, 'MATPEL I-II-III', '', 1, NULL, '2026-01-22 01:53:54', '2026-01-22 01:53:54'),
(31, 'MANEJO EN UNIDAD MECÁNICA', '', 1, NULL, '2026-02-27 10:56:16', '2026-02-27 10:56:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_curso_etiqueta`
--

CREATE TABLE `cr_curso_etiqueta` (
  `curso_id` int(10) UNSIGNED NOT NULL,
  `etiqueta_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_curso_etiqueta`
--

INSERT INTO `cr_curso_etiqueta` (`curso_id`, `etiqueta_id`) VALUES
(12, 1),
(12, 2),
(12, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_etiquetas`
--

CREATE TABLE `cr_etiquetas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_etiquetas`
--

INSERT INTO `cr_etiquetas` (`id`, `nombre`) VALUES
(2, 'automotriz'),
(1, 'mecanica'),
(3, 'motor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_formularios`
--

CREATE TABLE `cr_formularios` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `modo` enum('FAST','AULA') NOT NULL,
  `tipo` enum('EXAMEN','TEST','ENCUESTA') NOT NULL DEFAULT 'EXAMEN',
  `grupo_id` int(10) UNSIGNED DEFAULT NULL,
  `curso_id` int(10) UNSIGNED DEFAULT NULL,
  `tema_id` int(10) UNSIGNED DEFAULT NULL,
  `titulo` varchar(180) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('BORRADOR','PUBLICADO','CERRADO') NOT NULL DEFAULT 'BORRADOR',
  `intentos_max` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `tiempo_activo` tinyint(1) NOT NULL DEFAULT 0,
  `duracion_min` int(10) UNSIGNED DEFAULT NULL,
  `nota_min` decimal(5,2) NOT NULL DEFAULT 11.00,
  `mostrar_resultado` tinyint(1) NOT NULL DEFAULT 1,
  `requisito_cumplimiento` enum('ENVIAR','APROBAR') NOT NULL DEFAULT 'ENVIAR',
  `campos_fast` longtext DEFAULT NULL,
  `public_code` varchar(32) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_formularios`
--

INSERT INTO `cr_formularios` (`id`, `empresa_id`, `modo`, `tipo`, `grupo_id`, `curso_id`, `tema_id`, `titulo`, `descripcion`, `estado`, `intentos_max`, `tiempo_activo`, `duracion_min`, `nota_min`, `mostrar_resultado`, `requisito_cumplimiento`, `campos_fast`, `public_code`, `created_at`, `updated_at`) VALUES
(1, 19, 'AULA', 'EXAMEN', 2, 12, NULL, 'EXAMEN DE CONOCIMIENTOS SOBRE EL SEMAFORO', 'Este es un examen sobre tus conocimientos sobre el semaforo en la conducción de vehiculos.', 'PUBLICADO', 1, 1, 5, 11.00, 1, 'ENVIAR', NULL, NULL, '2026-03-08 00:11:27', '2026-03-08 00:25:02'),
(2, 19, 'FAST', 'EXAMEN', NULL, NULL, NULL, 'EXAMEN DE MANEJO DEFENSIVO', 'Este es un examen donde te preguntaremos cosas de manejo defensivo.', 'PUBLICADO', 1, 1, 5, 11.00, 1, 'ENVIAR', '{\"pedir_nombres\":1,\"pedir_apellidos\":1,\"pedir_celular\":1,\"pedir_categorias\":0,\"tipos_doc_permitidos\":[1,2,3]}', 'FAST1BE6385042', '2026-03-08 00:32:33', '2026-03-08 00:34:47'),
(3, 19, 'AULA', 'EXAMEN', 2, 12, NULL, 'Examen mecanica Basica', 'En esta examen se va a comprobaar los conocimientos de tu aula virtual.', 'BORRADOR', 2, 1, 5, 11.00, 1, 'APROBAR', NULL, NULL, '2026-03-10 12:10:36', '2026-03-10 12:10:36'),
(4, 19, 'FAST', 'EXAMEN', NULL, NULL, NULL, 'PREGUNTAS DE MANEJO', 'Estas son preguntas rapidas', 'BORRADOR', 2, 1, 10, 11.00, 1, 'ENVIAR', '{\"pedir_nombres\":1,\"pedir_apellidos\":1,\"pedir_celular\":1,\"pedir_categorias\":0,\"tipos_doc_permitidos\":[1,2,3]}', 'FAST10A446AAE3', '2026-03-13 11:36:50', '2026-03-13 11:36:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_formulario_intentos`
--

CREATE TABLE `cr_formulario_intentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `formulario_id` int(10) UNSIGNED NOT NULL,
  `modo` enum('FAST','AULA') NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `tipo_doc_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `nro_doc` varchar(20) DEFAULT NULL,
  `nombres` varchar(120) DEFAULT NULL,
  `apellidos` varchar(120) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `categorias_json` longtext DEFAULT NULL,
  `intento_nro` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `status` enum('EN_PROGRESO','ENVIADO','EXPIRADO') NOT NULL DEFAULT 'EN_PROGRESO',
  `start_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `last_saved_at` datetime DEFAULT NULL,
  `puntaje_obtenido` decimal(6,2) DEFAULT NULL,
  `nota_final` decimal(6,2) DEFAULT NULL,
  `aprobado` tinyint(1) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_formulario_intentos`
--

INSERT INTO `cr_formulario_intentos` (`id`, `formulario_id`, `modo`, `usuario_id`, `tipo_doc_id`, `nro_doc`, `nombres`, `apellidos`, `celular`, `categorias_json`, `intento_nro`, `token`, `status`, `start_at`, `expires_at`, `submitted_at`, `last_saved_at`, `puntaje_obtenido`, `nota_final`, `aprobado`, `created_at`, `updated_at`) VALUES
(2, 1, 'AULA', 15, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'afa4b64a3074bae74214a1469be818af455ca41e', 'ENVIADO', '2026-03-08 00:30:35', '2026-03-08 00:35:35', '2026-03-08 00:30:43', '2026-03-08 00:30:43', 0.00, 0.00, 0, '2026-03-08 00:30:35', '2026-03-08 00:30:43'),
(3, 2, 'FAST', NULL, 1, '70379752', 'LUIGI ISRAEL', 'VILLANUEVA PEREZ', '964881841', NULL, 1, '48640794976830a05f090a2f4077bd1fb81ec7aa', 'ENVIADO', '2026-03-08 00:36:01', '2026-03-08 00:41:01', '2026-03-08 00:36:16', '2026-03-08 00:36:16', 8.00, 8.00, 0, '2026-03-08 00:36:01', '2026-03-08 00:36:16'),
(4, 2, 'FAST', NULL, 1, '70989876', 'Julia', 'Villanueva Castro', NULL, NULL, 1, 'daebc582bcb9ef872c080edbe93ae560c8284cdc', 'ENVIADO', '2026-03-08 00:37:22', '2026-03-08 00:42:22', '2026-03-08 00:38:02', '2026-03-08 00:38:02', 20.00, 20.00, 1, '2026-03-08 00:37:22', '2026-03-08 00:38:02'),
(5, 2, 'FAST', NULL, 2, 'B98787654', 'Marlon', 'Juarez', '964881834', NULL, 1, 'a54ddd415538c7e03a758111123b3c569f30a99e', 'ENVIADO', '2026-03-08 00:39:34', '2026-03-08 00:44:34', '2026-03-08 00:39:41', '2026-03-08 00:39:41', 8.00, 8.00, 0, '2026-03-08 00:39:34', '2026-03-08 00:39:41'),
(6, 2, 'FAST', NULL, 3, '8767655456', 'ANGEL', 'Perez perez', '987776543', NULL, 1, '467f8b3ac80c6099ed5bc04f43a424569f2de967', 'ENVIADO', '2026-03-10 12:04:56', '2026-03-10 12:09:56', '2026-03-10 12:05:25', '2026-03-10 12:05:25', 20.00, 20.00, 1, '2026-03-10 12:04:56', '2026-03-10 12:05:25'),
(7, 2, 'FAST', NULL, 1, '87676544', 'ANGEL', 'Perez perez', '987776543', NULL, 1, 'f46261af124d1f5588701ebdb41d178b1c8993c4', 'ENVIADO', '2026-03-10 12:06:28', '2026-03-10 12:11:28', '2026-03-10 12:06:53', '2026-03-10 12:06:53', 0.00, 0.00, 0, '2026-03-10 12:06:28', '2026-03-10 12:06:53'),
(8, 1, 'AULA', 14, NULL, NULL, NULL, NULL, NULL, NULL, 1, '7ac82b8db7886188ce4dcd703d1213d8ea292592', 'ENVIADO', '2026-03-10 12:15:47', '2026-03-10 12:20:47', '2026-03-10 12:16:10', '2026-03-10 12:16:10', 8.00, 8.00, 0, '2026-03-10 12:15:47', '2026-03-10 12:16:10'),
(9, 2, 'FAST', NULL, 1, '10299876', 'ANGEL', 'Perez perez', '987776543', NULL, 1, 'bc571b82b7e274556ed8646a2c927d3faa656048', 'ENVIADO', '2026-03-13 11:39:26', '2026-03-13 11:44:26', '2026-03-13 11:39:47', '2026-03-13 11:39:47', 20.00, 20.00, 1, '2026-03-13 11:39:26', '2026-03-13 11:39:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_formulario_intento_categoria`
--

CREATE TABLE `cr_formulario_intento_categoria` (
  `intento_id` bigint(20) UNSIGNED NOT NULL,
  `categoria_id` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_formulario_opciones`
--

CREATE TABLE `cr_formulario_opciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `pregunta_id` int(10) UNSIGNED NOT NULL,
  `texto` varchar(255) NOT NULL,
  `es_correcta` tinyint(1) NOT NULL DEFAULT 0,
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_formulario_opciones`
--

INSERT INTO `cr_formulario_opciones` (`id`, `pregunta_id`, `texto`, `es_correcta`, `orden`, `created_at`, `updated_at`) VALUES
(4, 1, 'CRUZAR', 0, 1, '2026-03-08 00:24:02', '2026-03-08 00:24:02'),
(5, 1, 'IGNORAR', 0, 2, '2026-03-08 00:24:02', '2026-03-08 00:24:02'),
(6, 1, 'DETENER', 1, 3, '2026-03-08 00:24:02', '2026-03-08 00:24:02'),
(7, 2, '80', 1, 1, '2026-03-08 00:24:48', '2026-03-08 00:24:48'),
(8, 2, '60', 1, 2, '2026-03-08 00:24:48', '2026-03-08 00:24:48'),
(9, 2, '50', 0, 3, '2026-03-08 00:24:48', '2026-03-08 00:24:48'),
(10, 2, '120', 0, 4, '2026-03-08 00:24:48', '2026-03-08 00:24:48'),
(11, 3, 'Esta es la respuesta 01 de la pregunta', 1, 1, '2026-03-08 00:33:36', '2026-03-08 00:33:36'),
(12, 3, 'Esta es la respuesta 02 de la pregunta', 0, 2, '2026-03-08 00:33:36', '2026-03-08 00:33:36'),
(13, 3, 'esta es la respuesta 03 de la pregunta', 0, 3, '2026-03-08 00:33:36', '2026-03-08 00:33:36'),
(14, 3, 'Esta es la respuesta 04 de la pregunta', 0, 4, '2026-03-08 00:33:36', '2026-03-08 00:33:36'),
(15, 4, 'Debemos evitar esto 01', 1, 1, '2026-03-08 00:34:29', '2026-03-08 00:34:29'),
(16, 4, 'Debemos evitar hacer la cosa nro 02', 1, 2, '2026-03-08 00:34:29', '2026-03-08 00:34:29'),
(17, 4, 'Debemos evitar hacer la cosa numero 03', 0, 3, '2026-03-08 00:34:29', '2026-03-08 00:34:29'),
(18, 4, 'Debemos evitar hacer la cosa numero 04', 0, 4, '2026-03-08 00:34:29', '2026-03-08 00:34:29'),
(19, 4, 'Debemos evitar hacer la cosa numero 05', 0, 5, '2026-03-08 00:34:29', '2026-03-08 00:34:29'),
(20, 5, 'ME DETENGO', 1, 1, '2026-03-10 12:11:30', '2026-03-10 12:11:30'),
(21, 5, 'CONTINUO', 0, 2, '2026-03-10 12:11:30', '2026-03-10 12:11:30'),
(22, 5, 'NINGUNA DE LAS ANTERIORES', 0, 3, '2026-03-10 12:11:30', '2026-03-10 12:11:30'),
(23, 6, '1) Detenerse', 1, 1, '2026-03-13 11:37:33', '2026-03-13 11:37:33'),
(24, 6, '2) Acelerar', 0, 2, '2026-03-13 11:37:33', '2026-03-13 11:37:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_formulario_preguntas`
--

CREATE TABLE `cr_formulario_preguntas` (
  `id` int(10) UNSIGNED NOT NULL,
  `formulario_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('OM_UNICA','OM_MULTIPLE') NOT NULL,
  `enunciado` text NOT NULL,
  `puntos` decimal(5,2) NOT NULL DEFAULT 0.00,
  `orden` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_formulario_preguntas`
--

INSERT INTO `cr_formulario_preguntas` (`id`, `formulario_id`, `tipo`, `enunciado`, `puntos`, `orden`, `created_at`, `updated_at`) VALUES
(1, 1, 'OM_UNICA', 'Que debes hacer cuando el semaforo está en rojo?', 12.00, 1, '2026-03-08 00:23:37', '2026-03-08 00:24:02'),
(2, 1, 'OM_MULTIPLE', 'A cuanta velocidad debes ir en carretera?', 8.00, 2, '2026-03-08 00:24:48', '2026-03-08 00:24:48'),
(3, 2, 'OM_UNICA', 'Que es el manejo defensivo.', 8.00, 1, '2026-03-08 00:33:36', '2026-03-08 00:33:36'),
(4, 2, 'OM_MULTIPLE', 'Que debemos evitar en el manejo defensivo?', 12.00, 2, '2026-03-08 00:34:29', '2026-03-08 00:34:29'),
(5, 3, 'OM_UNICA', 'Que se debe hacer si estoy conduciendo y aparce el semaforo en rojo.', 20.00, 1, '2026-03-10 12:11:30', '2026-03-10 12:11:30'),
(6, 4, 'OM_UNICA', '¿Qué debes hacer si estas conduciendo y aparece un semaforo en luz roja?', 20.00, 1, '2026-03-13 11:37:33', '2026-03-13 11:37:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_formulario_respuestas`
--

CREATE TABLE `cr_formulario_respuestas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `intento_id` bigint(20) UNSIGNED NOT NULL,
  `pregunta_id` int(10) UNSIGNED NOT NULL,
  `respuesta_json` longtext DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `puntos_obtenidos` decimal(6,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_formulario_respuestas`
--

INSERT INTO `cr_formulario_respuestas` (`id`, `intento_id`, `pregunta_id`, `respuesta_json`, `is_correct`, `puntos_obtenidos`, `created_at`, `updated_at`) VALUES
(12, 2, 1, '[5]', 0, 0.00, '2026-03-08 00:30:38', '2026-03-08 00:30:43'),
(14, 2, 2, '[9]', 0, 0.00, '2026-03-08 00:30:39', '2026-03-08 00:30:43'),
(20, 3, 3, '[11]', 1, 8.00, '2026-03-08 00:36:09', '2026-03-08 00:36:16'),
(22, 3, 4, '[16]', 0, 0.00, '2026-03-08 00:36:13', '2026-03-08 00:36:16'),
(27, 4, 3, '[11]', 1, 8.00, '2026-03-08 00:37:36', '2026-03-08 00:38:02'),
(29, 4, 4, '[15,16]', 1, 12.00, '2026-03-08 00:37:55', '2026-03-08 00:38:02'),
(36, 5, 3, '[11]', 1, 8.00, '2026-03-08 00:39:37', '2026-03-08 00:39:41'),
(38, 5, 4, '[15]', 0, 0.00, '2026-03-08 00:39:38', '2026-03-08 00:39:41'),
(43, 6, 3, '[11]', 1, 8.00, '2026-03-10 12:05:15', '2026-03-10 12:05:25'),
(45, 6, 4, '[15,16]', 1, 12.00, '2026-03-10 12:05:18', '2026-03-10 12:05:25'),
(50, 7, 4, '[15]', 0, 0.00, '2026-03-10 12:06:42', '2026-03-10 12:06:53'),
(51, 7, 3, '[14]', 0, 0.00, '2026-03-10 12:06:44', '2026-03-10 12:06:53'),
(61, 8, 1, '[4]', 0, 0.00, '2026-03-10 12:15:55', '2026-03-10 12:16:10'),
(63, 8, 2, '[7,8]', 1, 8.00, '2026-03-10 12:15:58', '2026-03-10 12:16:10'),
(70, 9, 3, '[11]', 1, 8.00, '2026-03-13 11:39:30', '2026-03-13 11:39:47'),
(72, 9, 4, '[15,16]', 1, 12.00, '2026-03-13 11:39:32', '2026-03-13 11:39:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_grupos`
--

CREATE TABLE `cr_grupos` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `inicio_at` datetime DEFAULT NULL,
  `fin_at` datetime DEFAULT NULL,
  `codigo` varchar(32) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_grupos`
--

INSERT INTO `cr_grupos` (`id`, `curso_id`, `empresa_id`, `nombre`, `descripcion`, `inicio_at`, `fin_at`, `codigo`, `activo`, `created_at`, `updated_at`) VALUES
(1, 12, 19, 'Empresa CEMENTOS PERUANOSs SAC', 'Dirigido por asesor AA MM', NULL, NULL, 'CR_10326', 1, '2026-03-07 20:07:15', '2026-03-07 23:47:43'),
(2, 12, 19, 'Empresa CARTAVIO SAC', 'Con profesor David', '2026-03-07 23:52:00', '2026-03-15 23:59:00', 'CR_20326', 1, '2026-03-07 23:48:37', '2026-03-10 12:00:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_matriculas_grupo`
--

CREATE TABLE `cr_matriculas_grupo` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `matriculado_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expulsado_at` datetime DEFAULT NULL,
  `expulsado_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_matriculas_grupo`
--

INSERT INTO `cr_matriculas_grupo` (`id`, `curso_id`, `grupo_id`, `usuario_id`, `estado`, `matriculado_at`, `expulsado_at`, `expulsado_by`, `created_at`, `updated_at`) VALUES
(1, 12, 2, 15, 1, '2026-03-10 11:59:10', NULL, NULL, '2026-03-07 23:49:13', '2026-03-10 11:59:10'),
(2, 12, 2, 14, 1, '2026-03-10 12:14:05', NULL, NULL, '2026-03-10 12:14:05', NULL),
(3, 12, 1, 13, 1, '2026-03-13 11:33:49', NULL, NULL, '2026-03-13 11:33:49', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_temas`
--

CREATE TABLE `cr_temas` (
  `id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `clase` longtext DEFAULT NULL,
  `video_url` varchar(300) DEFAULT NULL,
  `miniatura_path` varchar(255) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_temas`
--

INSERT INTO `cr_temas` (`id`, `curso_id`, `titulo`, `clase`, `video_url`, `miniatura_path`, `creado`, `actualizado`) VALUES
(1, 12, 'Introducción a la mecánica automotriz', 'En esta clase conocerás los conceptos básicos de la mecánica automotriz y la importancia del mantenimiento preventivo en los vehículos.\r\n\r\nSe explicará qué es la mecánica automotriz, los tipos de mantenimiento que existen y las herramientas básicas utilizadas en un taller.\r\n\r\nTambién aprenderás las normas básicas de seguridad que se deben seguir al trabajar con vehículos para evitar accidentes.', 'https://www.youtube.com/embed/-DE3_4M3Goo?si=_rzKQzSnCVz1ytTN', 'almacen/img_temas/20260306-tema-tem000001.png', '2026-03-06 09:21:13', '2026-03-06 09:23:47'),
(2, 12, 'Partes principales de un automóvil  Clase', 'En esta lección aprenderás a identificar las principales partes de un automóvil.\r\n\r\nSe estudiarán componentes como:\r\n\r\nMotor\r\n\r\nTransmisión\r\n\r\nSistema de frenos\r\n\r\nSistema eléctrico\r\n\r\nSuspensión\r\n\r\nDirección\r\n\r\nConocer cada uno de estos sistemas es fundamental para comprender cómo funciona un vehículo.', 'https://www.youtube.com/embed/-DE3_4M3Goo?si=_rzKQzSnCVz1ytTN', 'almacen/img_temas/20260306-tema-tem000002.png', '2026-03-06 09:21:23', '2026-03-06 09:24:47'),
(3, 12, 'Funcionamiento del motor', 'El motor es el corazón del automóvil. En esta clase aprenderás cómo funciona un motor de combustión interna.\r\n\r\nSe explicará el proceso de los 4 tiempos del motor:\r\n\r\nAdmisión\r\n\r\nCompresión\r\n\r\nCombustión\r\n\r\nEscape\r\n\r\nTambién conocerás componentes importantes como pistones, cilindros, válvulas, árbol de levas y cigüeñal.', 'https://www.youtube.com/embed/-DE3_4M3Goo?si=_rzKQzSnCVz1ytTN', 'almacen/img_temas/20260306-tema-tem000003.png', '2026-03-06 09:21:44', '2026-03-06 09:24:54'),
(4, 12, 'Sistema de frenos', 'El sistema de frenos es uno de los sistemas más importantes para la seguridad del vehículo.\r\n\r\nEn esta clase aprenderás:\r\n\r\nTipos de frenos (disco y tambor)\r\n\r\nCómo funciona el sistema hidráulico\r\n\r\nComponentes principales del sistema de frenos\r\n\r\nCómo identificar desgaste en las pastillas de freno\r\n\r\nTambién veremos recomendaciones para el mantenimiento del sistema de frenado.', 'https://www.youtube.com/embed/-DE3_4M3Goo?si=_rzKQzSnCVz1ytTN', 'almacen/img_temas/20260306-tema-tem000004.jpg', '2026-03-06 09:21:53', '2026-03-06 09:25:56'),
(5, 12, 'Sistema de suspensión', 'La suspensión permite que el vehículo tenga estabilidad y comodidad al circular.\r\n\r\nEn esta lección se estudiarán componentes como:\r\n\r\nAmortiguadores\r\n\r\nResortes\r\n\r\nBrazos de suspensión\r\n\r\nBarra estabilizadora\r\n\r\nTambién aprenderás cómo detectar problemas comunes en la suspensión del vehículo.', 'https://www.youtube.com/embed/-DE3_4M3Goo?si=_rzKQzSnCVz1ytTN', 'almacen/img_temas/20260306-tema-tem000005.png', '2026-03-06 09:22:01', '2026-03-06 09:25:45'),
(6, 12, 'Mantenimiento básico del vehículo', 'En esta clase aprenderás a realizar revisiones básicas para mantener tu vehículo en buen estado.\r\n\r\nSe enseñará cómo revisar:\r\n\r\nNivel de aceite del motor\r\n\r\nNivel de refrigerante\r\n\r\nPresión de neumáticos\r\n\r\nEstado de la batería\r\n\r\nEstado de los frenos\r\n\r\nEl mantenimiento preventivo ayuda a evitar averías y prolonga la vida útil del automóvil.', 'https://www.youtube.com/embed/-DE3_4M3Goo?si=_rzKQzSnCVz1ytTN', 'almacen/img_temas/20260306-tema-tem000006.png', '2026-03-06 09:22:10', '2026-03-06 09:25:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cr_usuario_curso`
--

CREATE TABLE `cr_usuario_curso` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `asignado_por` int(10) UNSIGNED DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cr_usuario_curso`
--

INSERT INTO `cr_usuario_curso` (`id`, `usuario_id`, `curso_id`, `activo`, `asignado_por`, `creado`, `actualizado`) VALUES
(1, 11, 12, 0, 10, '2026-03-06 09:12:48', '2026-03-07 18:50:04'),
(2, 11, 1, 0, NULL, '2026-03-06 15:22:03', '2026-03-07 12:33:26'),
(3, 11, 20, 0, NULL, '2026-03-06 18:24:26', '2026-03-06 18:24:28'),
(4, 11, 17, 0, NULL, '2026-03-06 19:04:54', '2026-03-06 19:04:58'),
(5, 11, 26, 0, NULL, '2026-03-06 19:04:54', '2026-03-06 19:04:57'),
(6, 11, 10, 0, NULL, '2026-03-06 19:04:55', '2026-03-06 19:04:56'),
(7, 14, 17, 0, NULL, '2026-03-06 19:09:07', '2026-03-07 12:33:13'),
(8, 13, 4, 0, NULL, '2026-03-06 19:09:14', '2026-03-07 12:33:19'),
(9, 12, 23, 0, NULL, '2026-03-06 19:09:19', '2026-03-07 12:33:24'),
(10, 12, 24, 0, NULL, '2026-03-06 19:09:21', '2026-03-07 12:33:22'),
(11, 12, 12, 0, NULL, '2026-03-06 19:09:23', '2026-03-07 12:33:23'),
(12, 12, 30, 0, NULL, '2026-03-06 19:09:26', '2026-03-07 12:33:21'),
(13, 12, 31, 0, NULL, '2026-03-06 19:09:28', '2026-03-07 12:33:20'),
(14, 14, 14, 0, NULL, '2026-03-07 12:33:10', '2026-03-07 12:33:12'),
(15, 15, 16, 1, NULL, '2026-03-07 16:35:14', '2026-03-07 16:35:14'),
(16, 15, 14, 1, NULL, '2026-03-07 16:46:51', '2026-03-07 16:46:51'),
(17, 11, 2, 0, 10, '2026-03-07 18:15:27', '2026-03-07 18:50:02'),
(23, 15, 12, 1, 10, '2026-03-07 20:08:05', '2026-03-10 11:59:10'),
(29, 14, 12, 1, 10, '2026-03-10 12:14:05', '2026-03-10 12:14:05'),
(30, 13, 12, 1, 10, '2026-03-13 11:33:49', '2026-03-13 11:33:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egr_correlativos`
--

CREATE TABLE `egr_correlativos` (
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `ultimo_numero` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `egr_correlativos`
--

INSERT INTO `egr_correlativos` (`id_empresa`, `ultimo_numero`, `actualizado`) VALUES
(19, 19, '2026-03-17 14:35:23'),
(20, 3, '2026-03-16 15:35:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egr_egresos`
--

CREATE TABLE `egr_egresos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_caja_mensual` int(10) UNSIGNED NOT NULL,
  `id_caja_diaria` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(16) NOT NULL,
  `correlativo` int(10) UNSIGNED NOT NULL,
  `tipo_comprobante` enum('RECIBO','BOLETA','FACTURA') NOT NULL DEFAULT 'RECIBO',
  `serie` varchar(10) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `referencia` varchar(120) DEFAULT NULL,
  `fecha_emision` datetime NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `beneficiario` varchar(160) DEFAULT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `concepto` varchar(1000) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVO','ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `tipo_egreso` enum('NORMAL','MULTICAJA') NOT NULL DEFAULT 'NORMAL',
  `anulado_por` int(10) UNSIGNED DEFAULT NULL,
  `anulado_en` datetime DEFAULT NULL,
  `anulado_motivo` varchar(255) DEFAULT NULL,
  `creado_por` int(10) UNSIGNED NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `egr_egresos`
--

INSERT INTO `egr_egresos` (`id`, `id_empresa`, `id_caja_mensual`, `id_caja_diaria`, `codigo`, `correlativo`, `tipo_comprobante`, `serie`, `numero`, `referencia`, `fecha_emision`, `monto`, `beneficiario`, `documento`, `concepto`, `observaciones`, `estado`, `tipo_egreso`, `anulado_por`, `anulado_en`, `anulado_motivo`, `creado_por`, `creado`, `actualizado`) VALUES
(1, 19, 1, 3, 'E019-000001', 1, 'RECIBO', NULL, NULL, 'Recibo interno 001-Útiles', '2026-03-05 09:10:00', 28.50, 'Librería Milenio', '20604567891', 'Compra de lapiceros, resaltadores y folder para atención en caja.', 'Caja chica.', 'ANULADO', 'NORMAL', 10, '2026-03-05 13:14:09', 'Me equivoqué', 10, '2026-03-05 13:12:22', '2026-03-05 13:14:09'),
(2, 19, 1, 3, 'E019-000002', 2, 'FACTURA', 'F-632', '0005241', NULL, '2026-03-05 10:00:00', 2000.00, 'TIENDAS F SAC', '20602546954', 'Compra de generador electrico para caidas de luz en la central de redes de La Alameda.', 'Coordinado con Luigi Villanueva  - Soporte TI', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-05 13:46:13', '2026-03-05 13:46:13'),
(3, 19, 1, 3, 'E019-000003', 3, 'BOLETA', 'B-002', '34343', NULL, '2026-03-05 14:01:00', 10.00, 'BODEGA LA ECONOMIA', '71525545', 'Bolsa de pan y 2 gaseosas.', 'Viaje a chimbote', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-05 14:03:18', '2026-03-05 14:03:18'),
(4, 19, 1, 3, 'E019-000004', 4, 'BOLETA', 'B-1', '47484', NULL, '2026-03-05 15:29:00', 50.00, 'PETROPERU SAC', '20605874585', 'Gasolina para auto N03', NULL, 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-05 15:30:15', '2026-03-05 15:30:15'),
(5, 19, 1, 3, 'E019-000005', 5, 'RECIBO', NULL, NULL, '56565', '2026-03-05 15:46:00', 4.00, 'LUIGI', '70379752', 'PASAJE A LA ALAMEDA', 'COORD', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-05 15:46:53', '2026-03-05 15:46:53'),
(6, 19, 1, 5, 'E019-000006', 6, 'BOLETA', 'B-01', '5263', NULL, '2026-03-10 09:27:00', 20.00, 'BODEGA LA ECONOMIA', '-', 'Gaseosas para alumnos', 'Coordinado con el administrador', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-10 09:29:40', '2026-03-10 09:29:40'),
(7, 19, 1, 5, 'E019-000007', 7, 'BOLETA', 'B-002', '3434', NULL, '2026-03-10 09:44:00', 50.00, 'GLADYS CHAVEZ', '70332321', 'Limpieza del local día 10-03-26', 'Coordinado con gerente', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-10 09:44:51', '2026-03-10 09:44:51'),
(8, 19, 1, 5, 'E019-000008', 8, 'RECIBO', NULL, NULL, NULL, '2026-03-09 09:44:00', 50.00, 'GLADYS CHAVEZ', '70001012', 'Limpieza del local día 10-03-26', 'Cooridnado con gerente', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-10 10:02:07', '2026-03-10 10:02:07'),
(9, 19, 1, 5, 'E019-000009', 9, 'RECIBO', NULL, NULL, NULL, '2026-03-10 11:28:00', 20.00, 'BODEGA ECONOMIA', '-', 'Gaseosas para los alumnos', 'Coordinado con sra Roxana', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-10 11:30:05', '2026-03-10 11:30:05'),
(10, 19, 1, 7, 'E019-000010', 10, 'RECIBO', NULL, NULL, NULL, '2026-03-13 08:18:00', 10.00, 'BODEGA ECONOMIA', '-', 'Gaseosas para alumnos', 'Coordinado con gerente', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-13 11:19:35', '2026-03-13 11:19:35'),
(11, 19, 1, 7, 'E019-000011', 11, 'FACTURA', 'F01', '0002541', NULL, '2026-03-13 11:20:00', 50.00, 'TAYLOY SAC', '20100049181', 'Compra de lapiceros, cuadernos, fotocheck etc.\nCompra de lapiceros, cuadernos, fotocheck etc.\nCompra de lapiceros, cuadernos, fotocheck etc.', 'Coordinador con instructor', 'ANULADO', 'NORMAL', 10, '2026-03-13 11:23:39', 'Se devolvieron los lapiceros', 10, '2026-03-13 11:22:58', '2026-03-13 11:23:39'),
(12, 19, 1, 7, 'E019-000012', 12, 'FACTURA', 'F-01', '000256325', NULL, '2026-03-13 15:31:00', 50.00, 'FABER CASTELL SAC', '20601542362', 'Compra de folder para archivar.', 'Observación interna.', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-13 15:39:05', '2026-03-13 15:39:05'),
(13, 19, 1, 7, 'E019-000013', 13, 'RECIBO', NULL, NULL, NULL, '2026-03-13 18:21:00', 50.00, 'LUCINDA VASQUEZ', '70366365', 'Pago por concepto de limpieza del día 13 de marzo 2026. Todo conforme.', 'Dinero yapeado a celular de la señora.', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-13 18:22:43', '2026-03-13 18:22:43'),
(14, 19, 1, 9, 'E019-000014', 14, 'FACTURA', 'F332', '20005', NULL, '2026-03-15 19:09:00', 20.00, 'BODEGA LA ECONOMIA', '2060365236', 'Compra de gaseosas y galletas para los alumnos que llevaron el curso en la escuela de Chocope - Grupo Cartavio.', 'Coordinado con administradora.', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-15 19:11:35', '2026-03-15 19:11:35'),
(15, 19, 1, 9, 'E019-000015', 15, 'RECIBO', NULL, NULL, NULL, '2026-03-15 21:26:00', 10.00, 'JULIA FLORES FLORES', '70332321', 'Concepto de limpieza de las aulas de la escuela.', 'aa', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-15 21:28:38', '2026-03-15 21:28:38'),
(16, 19, 1, 9, 'E019-000016', 16, 'BOLETA', 'B001', '00135', NULL, '2026-03-15 22:26:00', 15.00, 'LIBRERIA JULIAN', NULL, 'Cuadernos para los alumnos.', NULL, 'ANULADO', 'NORMAL', 10, '2026-03-15 23:08:29', 'hhhhhhhhhhh', 10, '2026-03-15 23:07:45', '2026-03-15 23:08:29'),
(17, 19, 1, 9, 'E019-000017', 17, 'BOLETA', 'B001', '00136', NULL, '2026-03-15 23:09:00', 15.00, 'LIBRERIA JULIAN', NULL, 'Cuadernos para los alumnos.', NULL, 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-15 23:09:50', '2026-03-15 23:09:50'),
(18, 20, 2, 11, 'E020-000001', 1, 'RECIBO', NULL, NULL, NULL, '2026-03-16 12:46:00', 20.00, 'LUIGI VILLANUEVA', '70379752', 'Gaseosas y galletas', 'Coordinado con gerencia.', 'ANULADO', 'NORMAL', 18, '2026-03-16 12:49:00', 'Se devolvieron las galletas', 18, '2026-03-16 12:47:27', '2026-03-16 12:49:00'),
(19, 19, 1, 10, 'E019-000018', 18, 'RECIBO', NULL, NULL, NULL, '2026-03-16 13:27:00', 5.00, 'LUIGI ISRAEL VILLANUEVA PEREZ', '70379752', 'Pago por concepto de desarrollo de software', 'Coordinado con gerencia.', 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-16 13:28:14', '2026-03-16 13:28:14'),
(20, 20, 2, 11, 'E020-000002', 2, 'RECIBO', NULL, NULL, NULL, '2026-03-16 15:29:00', 7.50, 'INKAFARMA', '20608430301', 'COMPRA 1 CAJA DE MASCARILLAS', NULL, 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 18, '2026-03-16 15:32:01', '2026-03-16 15:32:01'),
(21, 20, 2, 11, 'E020-000003', 3, 'RECIBO', NULL, NULL, NULL, '2026-03-16 15:32:00', 195.00, 'HIJO SR. ITALO', '11111111', 'PAGO SUNAT ALLAIN Y ARGOS', NULL, 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 18, '2026-03-16 15:35:16', '2026-03-16 15:35:16'),
(22, 19, 1, 13, 'E019-000019', 19, 'FACTURA', 'F01', '00525', NULL, '2026-03-17 14:34:00', 10.00, 'JUANA ALVAREZ', '70333232', 'COMPRA DE GREOSAS', NULL, 'ACTIVO', 'NORMAL', NULL, NULL, NULL, 10, '2026-03-17 14:35:23', '2026-03-17 14:35:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `egr_egreso_fuentes`
--

CREATE TABLE `egr_egreso_fuentes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_egreso` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_caja_diaria` int(10) UNSIGNED NOT NULL,
  `fuente_key` enum('EFECTIVO','YAPE','PLIN','TRANSFERENCIA') NOT NULL,
  `medio_id` int(10) UNSIGNED NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `egr_egreso_fuentes`
--

INSERT INTO `egr_egreso_fuentes` (`id`, `id_egreso`, `id_empresa`, `id_caja_diaria`, `fuente_key`, `medio_id`, `monto`, `creado`) VALUES
(1, 12, 19, 7, 'EFECTIVO', 1, 10.00, '2026-03-13 15:39:05'),
(2, 12, 19, 7, 'YAPE', 2, 10.00, '2026-03-13 15:39:05'),
(3, 12, 19, 7, 'PLIN', 3, 10.00, '2026-03-13 15:39:05'),
(4, 12, 19, 7, 'TRANSFERENCIA', 4, 20.00, '2026-03-13 15:39:05'),
(5, 13, 19, 7, 'YAPE', 2, 50.00, '2026-03-13 18:22:43'),
(6, 14, 19, 9, 'EFECTIVO', 1, 5.00, '2026-03-15 19:11:35'),
(7, 14, 19, 9, 'YAPE', 2, 5.00, '2026-03-15 19:11:35'),
(8, 14, 19, 9, 'PLIN', 3, 5.00, '2026-03-15 19:11:35'),
(9, 14, 19, 9, 'TRANSFERENCIA', 4, 5.00, '2026-03-15 19:11:35'),
(10, 15, 19, 9, 'EFECTIVO', 1, 10.00, '2026-03-15 21:28:38'),
(11, 16, 19, 9, 'EFECTIVO', 1, 15.00, '2026-03-15 23:07:45'),
(12, 17, 19, 9, 'EFECTIVO', 1, 15.00, '2026-03-15 23:09:50'),
(13, 18, 20, 11, 'EFECTIVO', 1, 20.00, '2026-03-16 12:47:27'),
(14, 19, 19, 10, 'EFECTIVO', 1, 5.00, '2026-03-16 13:28:14'),
(15, 20, 20, 11, 'EFECTIVO', 1, 7.50, '2026-03-16 15:32:01'),
(16, 21, 20, 11, 'EFECTIVO', 1, 195.00, '2026-03-16 15:35:16'),
(17, 22, 19, 13, 'EFECTIVO', 1, 10.00, '2026-03-17 14:35:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ferreteria_productos`
--

CREATE TABLE `ferreteria_productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `image_key` varchar(1024) DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ferreteria_productos`
--

INSERT INTO `ferreteria_productos` (`id`, `nombre`, `cantidad`, `image_key`, `creado_en`) VALUES
(1, 'MARTILLO CANTOL', 10, 'ferreteria/2026/01/p1_MARTILLO_CANTOL_b9bec1ed4a3e.jpg', '2026-01-23 06:17:09'),
(2, 'TORNILLOS CEC', 33, 'ferreteria/2026/01/p2_TORNILLOS_CEC_20af63b6b9ec.webp', '2026-01-23 06:17:55'),
(3, 'CLAVOS DE OLOR', 3, 'ferreteria/2026/01/p3_CLAVOS_DE_OLOR_e18d6629b754.png', '2026-01-23 06:18:09'),
(4, 'PINTURA CPP', 33, 'ferreteria/2026/01/p4_PINTURA_CPP_86c38139a835.webp', '2026-01-23 06:18:18'),
(5, 'CODITO PVC', 3, 'ferreteria/2026/01/p5_CODITO_PVC_50535f36409a.webp', '2026-01-23 06:18:41'),
(6, 'alicate', 33, 'ferreteria/2026/01/p6_alicate_b4edcb9c1e80.webp', '2026-01-23 15:25:33'),
(7, 'COMPUTADORA HP', 2, 'ferreteria/2026/01/COMPUTADORA_HP_1769493273_52a7a0e1b029.webp', '2026-01-27 05:54:34'),
(8, 'video ejemplo', 2, 'ferreteria/2026/01/video_ejemplo_1769493323_aa95ac26bef7.jpg', '2026-01-27 05:55:25'),
(9, 'video de prueba', 1, 'ferreteria/videos/2026/01/video_de_prueba_1769493643_e3774c723425.mp4', '2026-01-27 06:01:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inv_bienes`
--

CREATE TABLE `inv_bienes` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `tipo` enum('EQUIPO','HERRAMIENTA','CONSUMIBLE','MUEBLE','OTRO') NOT NULL DEFAULT 'EQUIPO',
  `nombre` varchar(160) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `serie` varchar(120) DEFAULT NULL,
  `cantidad` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unidad` varchar(20) NOT NULL DEFAULT 'UND',
  `estado` enum('BUENO','REGULAR','AVERIADO') NOT NULL DEFAULT 'BUENO',
  `id_ubicacion` int(10) UNSIGNED DEFAULT NULL,
  `id_responsable` int(10) UNSIGNED DEFAULT NULL,
  `responsable_nombres` varchar(100) DEFAULT NULL,
  `responsable_apellidos` varchar(100) DEFAULT NULL,
  `responsable_dni` varchar(15) DEFAULT NULL,
  `notas` varchar(400) DEFAULT NULL,
  `img_key` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `inv_bienes`
--

INSERT INTO `inv_bienes` (`id`, `id_empresa`, `tipo`, `nombre`, `descripcion`, `marca`, `modelo`, `serie`, `cantidad`, `unidad`, `estado`, `id_ubicacion`, `id_responsable`, `responsable_nombres`, `responsable_apellidos`, `responsable_dni`, `notas`, `img_key`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, 'EQUIPO', 'Monitor', 'Monito 19\" cuenta solo con entrada VGA', 'LG', '19M38A', '007NTQD0J316', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000192664_jpg_1770054432_24ac59b294b1.jpg', 1, '2026-02-02 12:48:53', '2026-02-02 19:04:23'),
(2, 1, 'EQUIPO', 'Monitor', 'Monitor LG 19\". Solo entrada VGA', 'Lg', '19M38A', '007NTWG0J209', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000192673_jpg_1770054713_6f76a51d2b57.jpg', 1, '2026-02-02 12:53:11', '2026-02-02 19:04:17'),
(3, 1, 'EQUIPO', 'Monitor', 'Monitor LG 22\". Inputs: VGA, HDMI y AUX', 'LG', '22MK400H', '809NTEP40988', 1.00, 'UND', 'AVERIADO', 1, 4, NULL, NULL, NULL, 'Pantalla Rota', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000192677_jpg_1770055081_27a30647dc80.jpg', 0, '2026-02-02 12:58:08', '2026-02-02 19:04:08'),
(5, 1, 'EQUIPO', 'Monitor', 'Monitor  ViewSonic 19\", input VGA', 'ViewSonic', 'VA1901', 'UNN182431834', 1.00, 'UND', 'AVERIADO', 1, 4, NULL, NULL, NULL, 'Pantalla blanca', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000192679_jpg_1770055418_183697eede2a.jpg', 0, '2026-02-02 13:03:45', '2026-02-02 19:04:01'),
(6, 1, 'EQUIPO', 'Monitor', 'Monitor con luz blanca en la parte inferior.', 'SAMSUNG', 'S20D300NH', '0L9RHTKG303396H', 1.00, 'UND', 'REGULAR', 1, 1, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000571128_jpg_1770076308_b330d1ac798b.jpg', 1, '2026-02-02 18:53:42', '2026-02-02 18:53:42'),
(7, 1, 'EQUIPO', 'Monitor', 'Solo VGA', 'ViewSonic', 'VA1901-A', 'UNN182432119', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17700787702868817848611631780668_jpg_1770078786_4b68012d39da.jpg', 1, '2026-02-02 19:35:03', '2026-02-02 19:35:03'),
(8, 1, 'EQUIPO', 'Monitor', 'Solo VGA', 'LG', '19M38A-B', '011NTMXAH643', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17700791384952887722170691012609_jpg_1770079144_9a13d6b7dd47.jpg', 1, '2026-02-02 19:40:37', '2026-02-02 19:40:37'),
(9, 1, 'EQUIPO', 'Monitor', 'VGA, HDMI y aux', 'LG', '19M38H-B', '209NTGY6B991', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17700794384367288858803781444883_jpg_1770079468_ed16d579969e.jpg', 1, '2026-02-02 19:46:40', '2026-02-02 19:46:40'),
(10, 1, 'EQUIPO', 'Monitor', 'Solo VGA', 'LG', '19M38A-B', '011NTEPAG092', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17700797667477312734558360844394_jpg_1770079774_31cafee2e592.jpg', 1, '2026-02-02 19:51:19', '2026-02-02 19:51:19'),
(11, 1, 'EQUIPO', 'Monitor', 'Solo VGA', 'HP', 'V194', '3CQ0200FCX', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17700800868936342338702043137451_jpg_1770080094_3446c23203cf.jpg', 1, '2026-02-02 19:56:38', '2026-02-02 19:56:38'),
(12, 1, 'EQUIPO', 'Monitor', 'Solo VGA', 'SAMSUNG', 'S20D300NH', 'ZZCWH4LG602370D', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1770080394579459972364408949823_jpg_1770080401_aed9e3f53e31.jpg', 1, '2026-02-02 20:01:39', '2026-02-02 20:01:39'),
(13, 1, 'EQUIPO', 'NVR', 'NVR Hikvision, 8 channels', 'HIKVISION', 'DS-7608NI-Q1', 'E00810621', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'Elimina grabaciones. La clave es un check', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000193157_jpg_1770135047_b1d2f361f1c7.jpg', 1, '2026-02-03 11:13:19', '2026-02-03 11:14:28'),
(14, 1, 'EQUIPO', 'NVR', 'NVR Hikvision. 8 channels', 'HIKVISION', 'Ds-7608NI-Q1', 'D92777219', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'Al conectar Ethernet se apaga. La clave es un check', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000193164_jpg_1770135458_e428a1690335.jpg', 1, '2026-02-03 11:19:21', '2026-02-03 11:19:21'),
(15, 1, 'EQUIPO', 'NVR', 'NVR Hikvision. 8 channels', 'HIKVISION', 'DS-7608NI-Q1', 'E00810618', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativo pero sin acceso, se olvido clave y patron', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000193165_jpg_1770136091_844052d9e069.jpg', 1, '2026-02-03 11:28:19', '2026-02-03 11:28:19'),
(16, 1, 'EQUIPO', 'DVR', 'DVR Hikvision. 8 canales', 'HIKVISION', 'DS-7208HGHI-F1/N', 'E22851004', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'No tiene configurado clave ni patron', 'inventario/leoncorp_bucket/bienes/1/2026/02/17701365159678124800910096871489_jpg_1770136556_5bc594186575.jpg', 1, '2026-02-03 11:38:20', '2026-02-03 11:38:20'),
(17, 1, 'EQUIPO', 'DVR', 'DVR Hikvision. 4 canales', 'HIKVISION', 'DS-7204HGHI-K1', 'K32616482', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Patron check o aspa. Clave: gc7777777gc', 'inventario/leoncorp_bucket/bienes/1/2026/02/17701370767664167862395723288338_jpg_1770137102_2c77bdfeeae2.jpg', 1, '2026-02-03 11:45:10', '2026-02-03 11:45:10'),
(18, 1, 'EQUIPO', 'NVR', 'NVR Hikvision. 4 canales', 'HIKVISION', 'DS-7104NI-SN', '623426480', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Bloqueado(no se tiene clave)', 'inventario/leoncorp_bucket/bienes/1/2026/02/17701377860382575460618429064317_jpg_1770137797_b3d2f0f25a57.jpg', 1, '2026-02-03 11:56:46', '2026-02-03 11:56:46'),
(19, 1, 'EQUIPO', 'NVR', 'NVR Hikvision. 4 canales', 'HIKVISION', 'DS-7104NI-SN', '636490764', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Sin clave ni patron. Acceso libre a configuración', 'inventario/leoncorp_bucket/bienes/1/2026/02/17701382377825434838686426968721_jpg_1770138249_39b930f56bc9.jpg', 1, '2026-02-03 12:04:18', '2026-02-03 12:04:18'),
(20, 1, 'EQUIPO', 'NVR', 'NVR Hivision. 4 canales', 'HIKVISION', 'DS-7104NI-SN', '636490755', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Sin clave ni patrón. Acceso libre a configurar', 'inventario/leoncorp_bucket/bienes/1/2026/02/1770138346681107216643730567203_jpg_1770138360_6a07abd78090.jpg', 1, '2026-02-03 12:13:53', '2026-02-03 12:13:53'),
(21, 1, 'EQUIPO', 'NVR', 'NVR Hikvision. 4 canales', 'HIKVISION', 'DS-7104NI-SN', '636490731', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Clave: 211***ju***', 'inventario/leoncorp_bucket/bienes/1/2026/02/17701394620324912698912718042395_jpg_1770139471_fbb039cfc792.jpg', 1, '2026-02-03 12:24:14', '2026-02-03 12:24:40'),
(22, 1, 'EQUIPO', 'DVR', 'DVR Hikvision. 8 canales', 'HIKVISION', 'DS-7208HGHI-F1/N', 'D83466812', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Tiene etiquera que se olvido clave. Se probó y tiene acceso libre a configuración', 'inventario/leoncorp_bucket/bienes/1/2026/02/1770139630740350238115507183867_jpg_1770139642_638bdad3cc74.jpg', 1, '2026-02-03 12:27:06', '2026-02-03 12:27:28'),
(23, 1, 'EQUIPO', 'SWITCH', '16 puertos, 10/100/1000 mbps', 'TPLINK', 'TL-SG1016D', '219C073000002', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, 'Se enviará a Open Medic Tumbes para inspección Febrero 2026 - Open Car', 'inventario/leoncorp_bucket/bienes/1/2026/02/17702372321731018773025642752703_jpg_1770237290_725b5daca969.jpg', 1, '2026-02-04 15:34:57', '2026-02-04 15:34:57'),
(24, 1, 'EQUIPO', 'DVR', '4 canales analógicos y 4 canales IP', 'HIKVISION', 'iDS-7204HQHI-M1/S', 'K11962636', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17702416208483598656038359749597_jpg_1770241649_8e689e1d74cc.jpg', 1, '2026-02-04 16:51:44', '2026-02-04 16:51:44'),
(25, 1, 'EQUIPO', 'CAMARA', 'Ip', 'HIKVISION', 'DS-2CD2110F-I', '508027635', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, 'gc7777777gc - software antiguo - 192.168.0.166', 'inventario/leoncorp_bucket/bienes/1/2026/02/17702427325684675630128819172753_jpg_1770242750_605b42f09c77.jpg', 1, '2026-02-04 17:05:26', '2026-02-04 17:05:59'),
(26, 1, 'EQUIPO', 'CAMARA', 'Ip', 'HIKVISION', 'DS-2CD2110F-I', '531998053', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, 'gc7777777gc \r\n192.168.0.188 \r\nantiguo', 'inventario/leoncorp_bucket/bienes/1/2026/02/17702429157924539232127295441148_jpg_1770242931_c35f7da76a16.jpg', 1, '2026-02-04 17:09:04', '2026-02-04 17:09:04'),
(27, 1, 'EQUIPO', 'Monitor', 'Monitor LG 19\". Entrada vga', 'LG', 'E1941S-BN', '102QCSF04366', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17703044317461186531869947048699_jpg_1770304443_2c2f60f9e61d.jpg', 1, '2026-02-05 10:16:00', '2026-02-05 10:16:00'),
(28, 1, 'EQUIPO', 'Monitor', 'Monitor HP 19\". Entrada VGA', 'Hp', 'V190 Monitor', '1CR8390RRN', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1770304625475379890534607506745_jpg_1770304638_d35217346638.jpg', 1, '2026-02-05 10:18:36', '2026-02-05 10:18:36'),
(29, 1, 'EQUIPO', 'Monitor', 'Monitor LG 20\". Entrada VGA', 'LG', '20MP48A', '801NTFABK459', 1.00, 'UND', 'AVERIADO', 1, 4, NULL, NULL, NULL, 'Pantalla rota', 'inventario/leoncorp_bucket/bienes/1/2026/02/17703048508846307470890517600299_jpg_1770304867_23efe8142a30.jpg', 1, '2026-02-05 10:22:54', '2026-02-05 10:23:41'),
(30, 1, 'EQUIPO', 'Monitor', 'Monitor LG 20\". Entrada HDMI, VGA y aux', 'LG', '20MK400H', '908NTQD42684', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'No se sabe de donde vino', 'inventario/leoncorp_bucket/bienes/1/2026/02/17703052461588523949433646072583_jpg_1770305259_1542955743d0.jpg', 1, '2026-02-05 10:28:44', '2026-02-05 10:28:44'),
(31, 1, 'EQUIPO', 'Huellero', 'Huellero IB Columbo. Presenta manchas', 'Integrated Biometric', 'Columbo DT', 'CD110CA- 22600326-000C', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'Presenta manchas', 'inventario/leoncorp_bucket/bienes/1/2026/02/17703074740919019079608528208226_jpg_1770307482_964f556a14e1.jpg', 1, '2026-02-05 11:06:34', '2026-02-05 11:50:43'),
(32, 1, 'EQUIPO', 'Huellero', 'Huller IB Columbo. No es reconocido', 'Integrated Biometric', 'Columbo DT', 'CD110CA-22302364-000C', 1.00, 'UND', 'AVERIADO', 1, 4, NULL, NULL, NULL, 'No es reconocido', 'inventario/leoncorp_bucket/bienes/1/2026/02/17703078052243638495905620720843_jpg_1770307813_d8c5419f9469.jpg', 1, '2026-02-05 11:11:56', '2026-02-05 11:25:33'),
(33, 1, 'EQUIPO', 'Huellero', 'Huellero IB Columbo DT. No es reconocido', 'Integrated Biometric', 'Columbo DT', 'CD110CA-11100727-000C', 1.00, 'UND', 'AVERIADO', 1, 4, NULL, NULL, NULL, 'No es reconocido', 'inventario/leoncorp_bucket/bienes/1/2026/02/17703085986412060544055644713923_jpg_1770308613_a54529548a44.jpg', 1, '2026-02-05 11:25:07', '2026-02-05 11:25:07'),
(34, 1, 'EQUIPO', 'Huellero - intacto', 'Huellero IB Columbo. Cuenta con etiqueta intacta', 'Integrated Biometric', 'Columbo DT', 'CD110CA-00200153-000C', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17703098173194377067307612494235_jpg_1770309825_ce9bdd7b757f.jpg', 1, '2026-02-05 11:44:57', '2026-02-05 11:44:57'),
(35, 1, 'EQUIPO', 'Huellero', 'Huellero IB Columbo', 'Integrated Biometric', 'Columbo DT', 'CD110CA-00200117-000C', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Etiqueta en blanco', 'inventario/leoncorp_bucket/bienes/1/2026/02/17703102968336730989813514513216_jpg_1770310304_d756966bd43c.jpg', 1, '2026-02-05 11:53:28', '2026-02-05 11:53:28'),
(36, 1, 'EQUIPO', 'Huellero', 'Huellero IB Columbo. Usb levemente flojo y dificultad en leer huella', 'Integrated Biometric', 'Columbo DT', 'CD110DC-13900260-000C', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1770312023545464638409781802907_jpg_1770312030_7cc2800a798b.jpg', 1, '2026-02-05 12:22:19', '2026-02-05 12:22:19'),
(37, 1, 'EQUIPO', 'Huellero', 'Huellero IB Columbo. No es detectado', 'Integrated Biometric', 'Columbo DT', 'No visible', 1.00, 'UND', 'AVERIADO', 1, 4, NULL, NULL, NULL, 'Fallado no es reconocido', 'inventario/leoncorp_bucket/bienes/1/2026/02/17706551392145428512109259246074_jpg_1770655148_61ea55ac964c.jpg', 0, '2026-02-09 11:39:26', '2026-02-09 11:39:26'),
(41, 1, 'EQUIPO', 'Huellero', 'Huelleo IB Columbo. Usb holgado', 'Integrated Biometric', 'Columbo DT', '00200166', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'Usb holgado', 'inventario/leoncorp_bucket/bienes/1/2026/02/17706580873349210315560394046515_jpg_1770658095_1c586c14e221.jpg', 1, '2026-02-09 12:28:55', '2026-02-09 12:28:55'),
(42, 1, 'EQUIPO', 'Huellero', 'Huellero IB Columbo', 'Integrated Biometric', 'Columbo DT', '00200181', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Detecta bien la huella', 'inventario/leoncorp_bucket/bienes/1/2026/02/17706584902486413518983469816885_jpg_1770658499_1f1368b3f2c3.jpg', 1, '2026-02-09 12:35:39', '2026-02-09 12:35:39'),
(43, 1, 'EQUIPO', 'Ticktera', 'Ticketera Epson TM-20III', 'EPSON', 'M267D', 'X7AT179394', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativa y con papel', 'inventario/leoncorp_bucket/bienes/1/2026/02/17708289205153268316065448159658_jpg_1770828943_12a7c63dc418.jpg', 1, '2026-02-11 11:58:39', '2026-02-11 11:58:39'),
(44, 1, 'EQUIPO', 'Ticketera', 'Ticketera Epson TM-20III', 'EPSON', 'M267D', 'X7AT164172', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativa con papel. Detalle golpe en la tapa delantera', 'inventario/leoncorp_bucket/bienes/1/2026/02/17708292043144272724172674052736_jpg_1770829221_a366fc48028c.jpg', 1, '2026-02-11 12:01:57', '2026-02-11 12:01:57'),
(45, 1, 'EQUIPO', 'Ticketera', 'Ticketera Epson TM-20III', 'EPSON', 'M267D', 'X7AT164176', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativa con papel', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000197298_jpg_1770829473_100b5e5ea454.jpg', 1, '2026-02-11 12:05:20', '2026-02-11 12:05:20'),
(46, 1, 'EQUIPO', 'Ticketera', 'Ticketera Epson TM-20III', 'EPSON', 'M267D', 'X7AT179393', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativo con papel', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000197298_jpg_1770829577_038b8a33c13d.jpg', 1, '2026-02-11 12:07:05', '2026-02-11 12:07:05'),
(47, 1, 'EQUIPO', 'Ticketera', 'Ticketera Epson TM-20III', 'EPSON', 'M267D', 'X7AT138359', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativa con papel', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000197298_jpg_1770829892_f836ead39b33.jpg', 1, '2026-02-11 12:12:28', '2026-02-11 12:12:28'),
(48, 1, 'EQUIPO', 'Ticketera', 'Ticketera Epson TM-20III', 'EPSON', 'M267D', 'X7AT171374', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativo con papel', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000197298_jpg_1770829967_d93fe2962463.jpg', 1, '2026-02-11 12:13:39', '2026-02-11 12:13:39'),
(49, 1, 'EQUIPO', 'Tickectera', 'Ticketera Epson TM-20III', 'EPSON', 'M267D', 'X7AT164168', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativo con papel', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000197298_jpg_1770830038_d9c86eee122a.jpg', 1, '2026-02-11 12:14:39', '2026-02-11 12:14:39'),
(50, 1, 'EQUIPO', 'Ticketera', 'Ticketera Epson TM-20III', 'EPSON', 'M267D', 'X7AT171303', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativo sin papel', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000197298_jpg_1770830114_2fc8c4a59b31.jpg', 1, '2026-02-11 12:15:46', '2026-02-11 12:19:26'),
(51, 1, 'EQUIPO', 'Ticktera', 'Ticketera Epson TM-T88V', 'EPSON', 'M244A', 'X6NM122807', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Operativo con papel', 'inventario/leoncorp_bucket/bienes/1/2026/02/17708302734325419496550861475697_jpg_1770830299_63b47834a310.jpg', 1, '2026-02-11 12:19:09', '2026-02-11 12:19:09'),
(52, 1, 'EQUIPO', 'Switch', '8 puertos, 9v', 'Tp-link', 'TL-SG1008D', '2175681001257', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17709975626202849672417431610309_jpg_1770997599_a537290ee3c2.jpg', 1, '2026-02-13 10:48:24', '2026-02-13 10:48:24'),
(53, 1, 'EQUIPO', 'Switch', '8 puerto, 5v', 'TP-LINK', 'TL-SF1008D', '22262D3008502', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17709980806777781448719252622381_jpg_1770998104_a51841b4c098.jpg', 1, '2026-02-13 10:56:18', '2026-02-13 10:56:18'),
(59, 1, 'EQUIPO', 'Switch', '8 puertos, 5v', 'TP-LIN', 'TL-SF1008D', '2157732001329', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17709982108332268261557491865758_jpg_1770998223_268e8558eeb0.jpg', 1, '2026-02-13 10:58:05', '2026-02-13 11:02:04'),
(60, 1, 'EQUIPO', 'Switch', '8 puertos, 5v', 'TP-LINK', 'TL-SF1008D', '2156243018463', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17709985526338573634821312740316_jpg_1770998562_c40baa3e4927.jpg', 1, '2026-02-13 11:03:42', '2026-02-13 11:03:42'),
(61, 1, 'EQUIPO', 'Access Point WIFI', 'Hasta 450 Mbps', 'TP-LINK', 'TL-WA901ND', '215B603000414', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17709987342423665289907182806204_jpg_1770998743_c85939280151.jpg', 1, '2026-02-13 11:06:49', '2026-02-13 11:06:49'),
(62, 1, 'EQUIPO', 'Router', '3 puertos, 12v, 300Mbps', 'HUAWEI', 'WS318n', '34E7S20716002087', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17709988721397741085714386225439_jpg_1770998894_8d194a784a94.jpg', 1, '2026-02-13 11:09:52', '2026-02-13 11:09:52'),
(64, 1, 'EQUIPO', 'Router', '4 puertos, 12v. Cargador extraviado', 'ADTRAN', 'NetVanta 832T', '117283G1', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17709991166632113943471903368018_jpg_1770999133_4f7004589bdc.jpg', 1, '2026-02-13 11:12:53', '2026-02-13 11:12:53'),
(66, 1, 'EQUIPO', 'Router LTE', '4 puertos, 12v. Se extravió 1 antena', 'HUAWEI', 'B612-533', 'CSD7S2062900262621', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17709997362746468007160390629746_jpg_1770999776_3407576b936e.jpg', 1, '2026-02-13 11:24:02', '2026-02-13 11:27:26'),
(67, 1, 'EQUIPO', 'Router LTE', '4 puertos, 12v. Antenas extraviadas.', 'HUAWEI', 'B612-533', 'CSD7S20624009953', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Solo cuenta con cargador, sin antenas', 'inventario/leoncorp_bucket/bienes/1/2026/02/17709999201106906131153446870741_jpg_1770999928_809e50613a04.jpg', 1, '2026-02-13 11:27:10', '2026-02-13 11:27:10'),
(68, 1, 'EQUIPO', 'Switch', '16 puertos', 'PLANET', 'GSW-1601', 'A5001421800760', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17710015230551141935594983861431_jpg_1771001537_8b376e56b071.jpg', 1, '2026-02-13 11:53:33', '2026-02-13 11:53:33'),
(70, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818252', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1771257938027949096380085342236_jpg_1771257975_a48aa7b077ae.jpg', 1, '2026-02-16 11:07:10', '2026-02-16 11:07:10'),
(72, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818247', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712581107366204622298674224286_jpg_1771258118_d704f236ac5d.jpg', 1, '2026-02-16 11:09:48', '2026-02-16 11:09:48'),
(73, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818244', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712582874241992979056605846506_jpg_1771258309_72b373f92e5a.jpg', 1, '2026-02-16 11:12:21', '2026-02-16 11:12:21'),
(74, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818256', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712583968234125173234377794212_jpg_1771258412_906057002064.jpg', 1, '2026-02-16 11:14:36', '2026-02-16 11:14:36'),
(75, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818257', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712587348272910167297144458415_jpg_1771258746_73fb0638a24b.jpg', 1, '2026-02-16 11:19:44', '2026-02-16 11:19:44'),
(76, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818246', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712588303947602566608602791215_jpg_1771258843_925f301a7e89.jpg', 1, '2026-02-16 11:21:27', '2026-02-16 11:21:27'),
(77, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818262', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712589501434079646220749393512_jpg_1771258962_2d62067eb741.jpg', 1, '2026-02-16 11:23:16', '2026-02-16 11:23:16'),
(78, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818235', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712590891297595882598722494371_jpg_1771259098_3d14a58e06ae.jpg', 1, '2026-02-16 11:25:30', '2026-02-16 11:25:30'),
(79, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818259', 1.00, 'UND', 'BUENO', 1, NULL, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712591693434956215273804864122_jpg_1771259176_00ff4df5a2c1.jpg', 1, '2026-02-16 11:26:44', '2026-02-16 11:26:44'),
(80, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818253', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712592641441710438054399446415_jpg_1771259282_b208ccd094b0.jpg', 1, '2026-02-16 11:28:31', '2026-02-16 11:28:31'),
(81, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818240', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712593983732452106789582595969_jpg_1771259414_4cd5c519b9ab.jpg', 1, '2026-02-16 11:30:34', '2026-02-16 11:30:34'),
(82, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818258', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712595788776407594669803415177_jpg_1771259585_f1b693647e82.jpg', 1, '2026-02-16 11:33:40', '2026-02-16 11:33:40'),
(83, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic - Nuevo', 'Futronic', 'F588HS', 'FS8818236', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712596489863229741463433679000_jpg_1771259665_4d73f6e8155b.jpg', 1, '2026-02-16 11:35:01', '2026-02-16 11:37:22'),
(91, 1, 'EQUIPO', 'Huellero', 'Huellero Futronic', 'Futronic', 'F588HS', 'FS8818234', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712598875632753750393771752964_jpg_1771259899_e73d264e7dda.jpg', 1, '2026-02-16 11:38:58', '2026-02-16 11:38:58'),
(92, 1, 'EQUIPO', 'Hullero', 'Huellero Futronic', 'Futronic', 'F588H', 'FP511205', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1771259957939607257574843547366_jpg_1771259968_0f05039c0c6b.jpg', 1, '2026-02-16 11:40:06', '2026-02-16 11:40:06'),
(93, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52160308073', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1771260242322142486703453251326_jpg_1771260259_62087ab7c89f.jpg', 1, '2026-02-16 11:45:30', '2026-02-16 11:45:30'),
(94, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52160308082', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712603509265093759987950984461_jpg_1771260360_a05b71b464a5.jpg', 1, '2026-02-16 11:47:07', '2026-02-16 11:47:07'),
(96, 1, 'EQUIPO', 'Huellero', 'SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52150400778', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712609878318244571010385760986_jpg_1771261010_f1a35729f0a1.jpg', 1, '2026-02-16 11:57:37', '2026-02-16 11:57:37'),
(97, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52150400730', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712610928526747037276848519618_jpg_1771261100_b6c1b37018f4.jpg', 1, '2026-02-16 11:59:55', '2026-02-16 11:59:55'),
(98, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52150400655', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712612591423167512972113400913_jpg_1771261266_edf3669582fd.jpg', 1, '2026-02-16 12:01:39', '2026-02-16 12:01:39'),
(99, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52150400713', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712613697092634243580397514820_jpg_1771261378_b427adc21602.jpg', 1, '2026-02-16 12:03:39', '2026-02-16 12:03:39'),
(100, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen - nro serie no visible', 'SecuGen', 'HAMSTER PRO 20', 'H5215...', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712620049558831751139535849082_jpg_1771262080_d885db6a8da4.jpg', 1, '2026-02-16 12:15:37', '2026-02-16 12:15:37'),
(101, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER  PRO 20', 'H52160308054', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712621739341532930729703111457_jpg_1771262182_a149b7b53ea7.jpg', 1, '2026-02-16 12:17:13', '2026-02-16 12:17:13'),
(102, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52160308038', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712622629086797214289919705176_jpg_1771262275_6251508c2d2f.jpg', 1, '2026-02-16 12:18:31', '2026-02-16 12:18:31'),
(103, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52150400720', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712626059457836305861215847934_jpg_1771262614_7a5164c00d71.jpg', 1, '2026-02-16 12:24:19', '2026-02-16 12:24:19'),
(104, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52150400761', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712626725434469504156464764223_jpg_1771262681_18fb37a2e3e9.jpg', 1, '2026-02-16 12:25:17', '2026-02-16 12:25:17'),
(105, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52160308034', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712628008277619972506149696320_jpg_1771262807_1264289a364f.jpg', 1, '2026-02-16 12:27:19', '2026-02-16 12:27:19'),
(106, 1, 'EQUIPO', 'Huellero', 'Huellero SecuGen', 'SecuGen', 'HAMSTER PRO 20', 'H52150400790', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17712632008794832831441303850370_jpg_1771263210_b8ca0793527e.jpg', 1, '2026-02-16 12:34:20', '2026-02-16 12:34:20'),
(107, 1, 'EQUIPO', 'Monitor', 'Monitor LG 19\". Entrada VGA', 'LG', '19M38', '108NTXR1J042', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17714284777754853625377170107573_jpg_1771428491_8aecc240d3ab.jpg', 1, '2026-02-18 10:29:08', '2026-02-18 10:29:08'),
(108, 1, 'EQUIPO', 'Monitor', 'Monitor LG 19\". Entrada VGA, pantalla parpadea color blanco', 'LG', '19M38A', '911NTXR3A250', 1.00, 'UND', 'AVERIADO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17714295690376007892358724079300_jpg_1771429607_e0cc29065850.jpg', 1, '2026-02-18 10:48:11', '2026-02-18 10:48:11'),
(109, 1, 'EQUIPO', 'Monitor', 'Monitor LG 20\". Entrada hdmi, vga. Salida aux.', 'LG', '20MK400H', '106NTNHDG591', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Sin tonillos para amar la base', 'inventario/leoncorp_bucket/bienes/1/2026/02/17714308676598832913135805952533_jpg_1771430903_e95d5a9d9080.jpg', 1, '2026-02-18 11:09:14', '2026-02-18 11:23:43'),
(110, 1, 'EQUIPO', 'Monitor', 'Monitor LG 19\". Entrada VGA. Roto en la esquina inferior izquierda', 'LG', '19M38', '010NTPC4R784', 1.00, 'UND', 'AVERIADO', 1, 4, NULL, NULL, NULL, 'Base incompleta', 'inventario/leoncorp_bucket/bienes/1/2026/02/17714316514055412451964291947496_jpg_1771431676_2a0c26c33b9a.jpg', 1, '2026-02-18 11:23:06', '2026-02-18 11:23:55'),
(111, 1, 'EQUIPO', 'Switch', 'Switch TP-LINK 5 entradas', 'TP-LINK', 'TL-SF1005D', '220A3K9004654', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17714319979994360529043393509743_jpg_1771432009_18e5297045e0.jpg', 1, '2026-02-18 11:28:06', '2026-02-18 11:28:06'),
(112, 1, 'EQUIPO', 'Cámara Network', 'Cámara Network Hikvision 2.8mm', 'Hikvision', 'DS-2CD2112-I', '466339189', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715175827252061016527263645452_jpg_1771517603_19ff3ebbbc24.jpg', 1, '2026-02-19 11:06:25', '2026-02-19 11:13:29'),
(113, 1, 'EQUIPO', 'Cámara Network', 'Cámara Network Hikvision 2.8mm', 'Hikvision', 'DS-2CD2110F-I', '531997672', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715172644197712800890968250518_jpg_1771517274_776f0b37aa26.jpg', 1, '2026-02-19 11:09:06', '2026-02-19 11:09:06'),
(114, 1, 'EQUIPO', 'Cámara Network', 'Cámara Network Hikvision 2.8mm', 'Hikvision', 'DS-2CD2120F-I', '563217783', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715176364995133287284001170572_jpg_1771517654_8fd651048b9b.jpg', 1, '2026-02-19 11:15:39', '2026-02-19 11:15:39'),
(115, 1, 'EQUIPO', 'Cámara Network', 'Cámara Network Hikvision 2.8mm', 'Hikvision', 'DS-2CD2120F-I', '558346420', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715177569531727478759111528365_jpg_1771517767_8aa508d54453.jpg', 1, '2026-02-19 11:18:07', '2026-02-19 11:18:07'),
(116, 1, 'EQUIPO', 'Camara Network', 'Cámara Network Hikvision 2.8mm', 'Hikvision', 'DS-2CD2120F-I', '0000', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'Sín etiqueta de datos', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715179267893413327689470465366_jpg_1771517945_386b4fa8d20b.jpg', 1, '2026-02-19 11:19:36', '2026-02-19 11:19:36'),
(117, 1, 'EQUIPO', 'Cámara Network', 'Cámara Network Hikvision 2.8mm - 12mm', 'Hikvision', 'DS-2CD1723G0-I', 'F91047530', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1771518400187889161877393250370_jpg_1771518432_e4c854190bbf.jpg', 1, '2026-02-19 11:28:56', '2026-02-19 11:28:56'),
(118, 1, 'EQUIPO', 'Cámara Network', 'Cámara Network Hikvision 2.8mm - 12mm', 'Hikvision', 'DS-2CD1723G0-I', 'C97723309', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715189410973631775878991813401_jpg_1771518955_3ca022df827d.jpg', 1, '2026-02-19 11:36:48', '2026-02-19 11:36:48'),
(119, 1, 'EQUIPO', 'Cámara Color', 'Cámara Color Hikvision 3.6mm', 'Hikvision', 'DS-2CE15A2N-IR', '450184664', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1771519074190582865508841621155_jpg_1771519088_7e8a746e0315.jpg', 1, '2026-02-19 11:40:21', '2026-02-19 11:40:21'),
(120, 1, 'EQUIPO', 'Cámara', 'Cámara Hikvision 3.6mm', 'Hikvision', 'DS-2CE55A2N-IRM', '450856984', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715197980712109719230870314783_jpg_1771519825_c50693954e2d.jpg', 1, '2026-02-19 11:51:51', '2026-02-19 11:51:51'),
(121, 1, 'EQUIPO', 'Cámara', 'Cámara Hikvision 2.8mm', 'Hikvision', 'DS-2CE56D0T-IRPF', 'K23594094', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715200472611847716780002978712_jpg_1771520057_88de2bcebf2b.jpg', 1, '2026-02-19 11:55:36', '2026-02-19 11:55:36'),
(122, 1, 'EQUIPO', 'Cámara', 'Camara Hikvision 3.5mm', 'Hikvision', 'DS-2CE56COT-IT3F', 'C5851938', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1771520262723691922491331458429_jpg_1771520269_d7c4658d8654.jpg', 1, '2026-02-19 11:59:38', '2026-02-19 11:59:38'),
(123, 1, 'EQUIPO', 'Cámara', 'Cámara Hikvision 3.6mm', 'Hikvision', 'DS-2CE56COT-IT3F', 'C58519231', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715204842151252595779040184202_jpg_1771520497_8ceea2e36296.jpg', 1, '2026-02-19 12:02:31', '2026-02-19 12:02:31'),
(124, 1, 'EQUIPO', 'Cámara', 'Cámara Hikvision 3.6mm protector de lente roto', 'Hikvision', 'DS-2CE562T-IT3', '559596420', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1771521401097622462910826182771_jpg_1771521424_f9a010d63b07.jpg', 1, '2026-02-19 12:18:25', '2026-02-19 12:18:25'),
(125, 1, 'EQUIPO', 'Cámara', 'Cámara Hikvision 3.6mm', 'Hikvision', 'DS-2CD2110F-I', '587592140', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715221568064017875113967468791_jpg_1771522168_a5058764882d.jpg', 1, '2026-02-19 12:30:22', '2026-02-19 12:30:22'),
(130, 1, 'EQUIPO', 'Cámara', 'Cámara Hikvision 3.6mm', 'Hikvision', 'DS-2CD2110F-I', '524545612', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17715223979791220470667326405745_jpg_1771522404_0cada1bc9db1.jpg', 1, '2026-02-19 12:34:14', '2026-02-19 12:34:14'),
(145, 1, 'EQUIPO', 'Cámara', 'Cámara Hikvision 3.6mm', 'Hikvision', 'DS-2CE56C2T-IT1', '558172820', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17716086476664247896393998971063_jpg_1771608656_8c04c834eec9.jpg', 1, '2026-02-20 12:27:36', '2026-02-20 12:35:07'),
(147, 1, 'EQUIPO', 'Teclado', 'Teclado Enkore', 'Enkore', 'ENT 502', NULL, 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720316136642978969338219175875_jpg_1772031699_9d1ceeb1e386.jpg', 1, '2026-02-25 10:02:05', '2026-02-25 10:02:05'),
(148, 1, 'EQUIPO', 'Teclado', 'Teclado Enkore, letras borradas', 'Enkore', 'ENT 502', '8992ENT5021186', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720318220597222853760656847660_jpg_1772031839_afd4c8033e5a.jpg', 1, '2026-02-25 10:04:43', '2026-02-25 10:10:23'),
(149, 1, 'EQUIPO', 'Teclado', 'Teclado Logitech, letras borradas', 'Logitech', 'K120', '1824SC50TKR8', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720319819961040643343925333823_jpg_1772032003_fa56fe68bbfc.jpg', 1, '2026-02-25 10:07:23', '2026-02-25 10:09:29'),
(150, 1, 'EQUIPO', 'Teclado', 'Teclado Logitech, letras borradas', 'Logitech', 'K120', '2037SC33LWUS', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720320946265003976865097193580_jpg_1772032108_05c3e972669b.jpg', 1, '2026-02-25 10:09:03', '2026-02-25 10:09:03'),
(151, 1, 'EQUIPO', 'Teclado', 'Teclado Halion', 'Halion', 'HA-K233C', 'CO-2379-2380', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720324375233835677504524654159_jpg_1772032452_b173b06cd46e.jpg', 1, '2026-02-25 10:15:02', '2026-02-25 10:15:02'),
(153, 1, 'EQUIPO', 'Teclado', 'Teclado Halion', 'Halion', 'HA-K233C', '2379-2380', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720326540736387504617600507027_jpg_1772032672_dc2ba6360b30.jpg', 1, '2026-02-25 10:18:34', '2026-02-25 10:18:34'),
(154, 1, 'EQUIPO', 'Teclado', 'Teclado Halion', 'Halion', 'HA-KIT 45', 'CO-2419', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720329886751999877688471133525_jpg_1772033003_2be6b557c11c.jpg', 1, '2026-02-25 10:24:04', '2026-02-25 10:24:04'),
(155, 1, 'EQUIPO', 'Teclado', 'Teclado Enkore, letras borradas', 'Enkore', 'ENT 508', '3835ENT5085000', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720330797047980113090868016712_jpg_1772033093_5ac83fe325f6.jpg', 1, '2026-02-25 10:25:41', '2026-02-25 10:25:41'),
(156, 1, 'EQUIPO', 'Teclado', 'Teclado Cybertel', 'Cybertel', 'CYB K107', '4024k1074868', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720332642553600900782265354299_jpg_1772033277_f2658109cae0.jpg', 1, '2026-02-25 10:28:45', '2026-02-25 10:28:45'),
(157, 1, 'EQUIPO', 'Teclado', 'Teclado Genius', 'Genius', 'GK-070008/U', 'ZCE1C0200314', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720362575387626131380314235146_jpg_1772036271_da25d0a9507a.jpg', 1, '2026-02-25 11:18:37', '2026-02-25 11:18:37'),
(158, 1, 'EQUIPO', 'Teclado', 'Teclado Teros', 'Teros', 'TE-D8700', NULL, 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720365559336397464252357083317_jpg_1772036568_9c0a355905b1.jpg', 1, '2026-02-25 11:23:16', '2026-02-25 11:23:16'),
(159, 1, 'EQUIPO', 'Teclado', 'Teclado Genius, letras borradas', 'Genius', 'GK-150001', 'XE1508A15103', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720366480563787154995066605365_jpg_1772036663_649c36b1cee6.jpg', 1, '2026-02-25 11:25:18', '2026-02-25 11:25:18'),
(160, 1, 'EQUIPO', 'Mouse', 'Mouse DVR', '', '', 'Dvr1', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720368801287738172876927447603_jpg_1772036894_7af6136637d9.jpg', 1, '2026-02-25 11:28:29', '2026-02-25 11:31:23'),
(161, 1, 'EQUIPO', 'Mouse', 'Mouse DVR', '', '', 'Dvr2', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000204129_jpg_1772037023_9cf8d125ce25.jpg', 1, '2026-02-25 11:30:29', '2026-02-25 11:30:29'),
(162, 1, 'EQUIPO', 'Mouse', 'Mouse DVR', '', '', 'Dvr3', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000204129_jpg_1772037050_0fe5d81ba235.jpg', 1, '2026-02-25 11:31:10', '2026-02-25 11:31:30'),
(163, 1, 'EQUIPO', 'Mouse', 'Mous DVR', '', '', 'Dvr4', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000204129_jpg_1772037109_ec520839e018.jpg', 1, '2026-02-25 11:32:13', '2026-02-25 11:32:13'),
(164, 1, 'EQUIPO', 'Mouse', 'Mouse DVR', '', '', 'Dvr5', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/1000204129_jpg_1772037168_104d144385d4.jpg', 1, '2026-02-25 11:33:00', '2026-02-25 11:33:00'),
(165, 1, 'EQUIPO', 'Mouse', 'Mouse DVR', '', '', 'Dvr6', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720371911724470075860245697426_jpg_1772037196_f5803775d008.jpg', 1, '2026-02-25 11:33:27', '2026-02-25 11:33:27'),
(166, 1, 'EQUIPO', 'Mouse', 'Mouse Logitech', 'Logitech', 'MU26', '810-002182', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720372291933260518454757151687_jpg_1772037234_6434b399c3ac.jpg', 1, '2026-02-25 11:34:57', '2026-02-25 11:34:57'),
(167, 1, 'EQUIPO', 'Mouse', 'Mouse Logitech', 'Logitech', 'MU26', '810002182', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720373274303075901309298083072_jpg_1772037334_5feedff2b64b.jpg', 1, '2026-02-25 11:36:37', '2026-02-25 11:36:37'),
(168, 1, 'EQUIPO', 'Mouse', 'Mouse Genius', 'Genius', 'DX110', 'X9H95301215251', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720375744241084431999437201771_jpg_1772037581_94fa64865ad5.jpg', 1, '2026-02-25 11:40:31', '2026-02-25 11:40:31'),
(169, 1, 'EQUIPO', 'Mouse', 'Mouse Genius', 'Genius', 'DX120', 'XE1508A1510E', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720376486954610274045562323322_jpg_1772037651_5b842d1369ba.jpg', 1, '2026-02-25 11:41:45', '2026-02-25 11:41:45'),
(171, 1, 'EQUIPO', 'Mouse', 'Mouse Genius', 'Genius', 'DX160', 'Nn', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720377193412108247452657089546_jpg_1772037722_18a290f30a19.jpg', 1, '2026-02-25 11:42:34', '2026-02-25 11:42:48'),
(172, 1, 'EQUIPO', 'Mouse', 'Mouse Microsoft', 'Microsoft', 'MSK1113', 'X821908-001', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720377833245151204228255696382_jpg_1772037786_f394665e2471.jpg', 1, '2026-02-25 11:44:15', '2026-02-25 11:44:15'),
(174, 1, 'EQUIPO', 'Mouse', 'Mouse Halion', 'Haliob', 'HA-K233C', '23792380', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/02/17720378719226847949997256941477_jpg_1772037880_7ec186d3933c.jpg', 1, '2026-02-25 11:45:22', '2026-02-25 11:45:22'),
(175, 1, 'EQUIPO', 'Teclado', 'Teclado Halion', 'Halion', 'HA-K233C', '2380', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725470600867077163155402239917_jpg_1772547088_69615b5f39b2.jpg', 1, '2026-03-03 09:12:12', '2026-03-03 09:12:12'),
(176, 1, 'EQUIPO', 'Teclado Inalámbrico', 'Teclado inalámbrico Teros, con usb, sin pilas', 'Teros', 'TE-4031', 'No hay', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725472356979204509178269772300_jpg_1772547253_ffbb6934e181.jpg', 1, '2026-03-03 09:15:03', '2026-03-03 09:15:03'),
(177, 1, 'EQUIPO', 'Teclado inalambrico', 'Teclado inalámbrico Logitech, sin usb y sin pilas', 'Logitech', 'K235', 'Nohay', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725476668272138184119929322334_jpg_1772547682_1ac9408e3de3.jpg', 1, '2026-03-03 09:22:00', '2026-03-03 09:22:00'),
(178, 1, 'EQUIPO', 'Teclado Inalámbrico', 'Teclado inalámbrico', 'Microsoft', 'Nd', 'Nd', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725477437438090862117870272572_jpg_1772547755_b6c6486113c0.jpg', 1, '2026-03-03 09:24:06', '2026-03-03 09:24:06'),
(179, 1, 'EQUIPO', 'Switch', 'Switch 8puertos', 'Tp-Link', 'TL-SF1008D', '22292T8002863', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725479193545942397331406467730_jpg_1772547933_b4d4a2fe6d4a.jpg', 1, '2026-03-03 09:26:27', '2026-03-03 09:26:40'),
(180, 1, 'EQUIPO', 'Switch', 'Switch 5 puertos', 'Tp-link', 'TL-SF1005D', '2165734011481', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725480424596480505617764337228_jpg_1772548053_2223b066ff8e.jpg', 1, '2026-03-03 09:28:50', '2026-03-03 09:28:50'),
(181, 1, 'EQUIPO', 'WebCam', 'WebCam usb', 'Microsoft', 'LifeCam-HD-3000', 'X822025-002', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725482945194004940044964045574_jpg_1772548303_d24284ae9bbc.jpg', 1, '2026-03-03 09:32:44', '2026-03-03 09:32:44'),
(184, 1, 'EQUIPO', 'WebCam', 'WebCam usb', 'Microsoft', 'LifeCam-HD-3000', 'X822025-0022', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/1772548716153388165054362257942_jpg_1772548723_3204d9e4d141.jpg', 1, '2026-03-03 09:40:14', '2026-03-03 09:40:14'),
(185, 1, 'EQUIPO', 'WebCam', 'WebCam usb', 'Microsft', 'LifeCam-HD-3000', 'X822025-004', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/1772548830895579697660788224310_jpg_1772548838_6b7138499301.jpg', 1, '2026-03-03 09:41:34', '2026-03-03 09:41:34'),
(186, 1, 'EQUIPO', 'Headset', 'USB headset', 'Genius', 'HS-230U', 'VW2423600992', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725489424257323321773904701603_jpg_1772548952_e37946a66261.jpg', 1, '2026-03-03 09:44:15', '2026-03-03 09:44:15'),
(187, 1, 'EQUIPO', 'Dvr', 'Dvr Hikvision 4in port y 1out port', 'Hikvision', '¡DS-7204HQHI', 'K11962642', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725491264594932611738842083370_jpg_1772549146_8af55678df1b.jpg', 1, '2026-03-03 09:47:06', '2026-03-03 09:47:06'),
(188, 1, 'EQUIPO', 'Modem', 'Modem 4 puertos', 'Sagemcom', 'CS 50001', 'NQ1900448003882', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17725492592891230728954370370451_jpg_1772549271_944e462e6d05.jpg', 1, '2026-03-03 09:49:45', '2026-03-03 09:49:45'),
(189, 1, 'EQUIPO', 'HDD 4TB OPEN MEDIC TRUJILLO', '19 dic 2023 - 25 feb 2025', 'Wester Digital', 'WD43PURZ', 'WX32D63RJFXF', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726411581203364795004110526538_jpg_1772641176_37b3bbbe55af.jpg', 1, '2026-03-04 11:20:31', '2026-03-04 11:20:31'),
(190, 1, 'EQUIPO', 'HDD 4TB OPEN MEDIC TRUJILLO', '25 jul 2023 - 19 dic 2023', 'Western Digital', 'WD40PURZ', 'WX12DC13FFS4', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726419673346297027535312564978_jpg_1772641977_ba735829bf15.jpg', 1, '2026-03-04 11:33:48', '2026-03-04 11:33:48'),
(191, 1, 'EQUIPO', 'HDD 4TB GLOBAL CAR CHIMBOTE', '22 oct 2024 - 07 jul 2025', 'Toshiba', 'Surveillance S300', '83QOK21UFB3G', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726432221797927291722649013659_jpg_1772643229_0656d3d73c97.jpg', 1, '2026-03-04 11:57:06', '2026-03-04 11:57:06'),
(192, 1, 'EQUIPO', 'HDD 3TB GLOBAL CAR CHOTA', '06 sept 2024 - 02 sept 2025', 'Western Digital', 'WD30PURZ', 'WX42D60HR3PA', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726437197176887704549273594786_jpg_1772643726_8a19fc61822c.jpg', 1, '2026-03-04 12:02:54', '2026-03-04 12:02:54'),
(193, 1, 'EQUIPO', 'HDD 4TB GUIAS MIS RUTAS HUANUCO', '12 ago 2023 - 13 nov 2023', 'Western Digital', 'WD40PURZ', 'WX22DC1DLTH2', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726439850126639175179615981630_jpg_1772643993_15a125295821.jpg', 1, '2026-03-04 12:07:33', '2026-03-04 12:07:33'),
(194, 1, 'EQUIPO', 'HDD 3TB GUIA MIS RUTAS HUANUCO', '14 mar 2023 - 11 ago 2023', 'Western Digital', 'WD30PURZ', 'WX62D313TA9D', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726445132101146249527447643947_jpg_1772644627_d78f16d0e4f8.jpg', 1, '2026-03-04 12:17:55', '2026-03-04 12:17:55'),
(195, 1, 'EQUIPO', 'HDD 3TB GLOBAL CAR CORP TUMBES', '17 jun 2022 - 06 jul 2023', 'Western Digital', 'WD30PURZ', 'WX22D518YZVO', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'Videos solo abren con Hetman', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726452780934906680808617416361_jpg_1772645286_172585dc485f.jpg', 1, '2026-03-04 12:31:42', '2026-03-05 10:24:59'),
(196, 1, 'EQUIPO', 'HDD 3TB GLOBAL CAR CHICLAYO', '14 abr 2025 - 13 oct 2025', 'Western Digital', 'WD30PURZ', 'WX22D617CZPV', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726457932086677283037237957270_jpg_1772645804_f152bfa26480.jpg', 1, '2026-03-04 12:40:20', '2026-03-04 12:40:20'),
(197, 1, 'EQUIPO', 'HDD 4TB GLOBAL CAR CHIMBOTE', '14 sep 2023 - 22 oct 2024', 'Western Digital', 'WD43PURZ', 'WXF2D53DSRA2', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726461896442106485995640090144_jpg_1772646199_e66556c9ec0a.jpg', 1, '2026-03-04 12:44:19', '2026-03-04 12:44:19'),
(198, 1, 'EQUIPO', 'HDD 4TB ESCUELA GLOBAL CAR TRUJILLO', '16 may 2024 - 28 ene 2026', 'Western Digital', 'WD42PURZ', 'WX72D123KKAX', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726464897338910755066549979909_jpg_1772646507_3106040bca07.jpg', 1, '2026-03-04 12:49:20', '2026-03-04 12:49:20'),
(199, 1, 'EQUIPO', 'HDD 4TB GLOBAL CAR PERU CAJAMARCA', '18 jul 2019 - 07 sep 2023', 'Western Digital', 'WD40PURZ', 'WCC7KOZFKEE8', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'Videos abre con Hetman', 'inventario/leoncorp_bucket/bienes/1/2026/03/1772646699993427855027770594380_jpg_1772646706_849b0afa804f.jpg', 1, '2026-03-04 12:52:49', '2026-03-05 10:23:54'),
(200, 1, 'EQUIPO', 'HDD 4TB OPEN MEDIC TRUJILLO', '07 feb 2023 - 25 jul 2023', 'Western Digital', 'WD40PURZ', 'WX32DB1P5A8X', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726468903313208576593814358213_jpg_1772646896_23d7c445d798.jpg', 1, '2026-03-04 12:55:44', '2026-03-04 12:55:44'),
(201, 1, 'EQUIPO', 'HDD 3TB GLOBAL CAR PIURA', '11 dic 2024 - 23 abr 2025', 'Western Digital', 'WD30PURZ', 'WCC4N6YVRZE1', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726471176537427544847706829489_jpg_1772647126_069cb2535b24.jpg', 1, '2026-03-04 12:59:28', '2026-03-04 12:59:28'),
(202, 1, 'EQUIPO', 'HDD 4TB GUIA MIS RUTAS HUARAZ', '25 mar 2024 - 17 mar 2025', 'Western Digital', 'WD43PURZ', 'WX32D83A1TVV', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Gab 11/09', 'inventario/leoncorp_bucket/bienes/1/2026/03/17726473761144231987432812065390_jpg_1772647385_7ac9aa6f4ff2.jpg', 1, '2026-03-04 13:04:01', '2026-03-04 13:05:19'),
(203, 1, 'EQUIPO', 'HDD 4TB SELVA CAR LORETO', '25 abr 2024 - 19 sep 2025', 'Western Digital', 'WD43PURZ', 'WX72D63P3ZVH', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727226741253581110309659899676_jpg_1772722719_8feeb9964172.jpg', 1, '2026-03-05 09:59:33', '2026-03-05 09:59:33');
INSERT INTO `inv_bienes` (`id`, `id_empresa`, `tipo`, `nombre`, `descripcion`, `marca`, `modelo`, `serie`, `cantidad`, `unidad`, `estado`, `id_ubicacion`, `id_responsable`, `responsable_nombres`, `responsable_apellidos`, `responsable_dni`, `notas`, `img_key`, `activo`, `creado`, `actualizado`) VALUES
(204, 1, 'EQUIPO', 'HDD 4TB GLOBAL CAR TUMBES', '08 dic 2024 - 08 may 2025', 'Western Digital', 'WD43PURZ', 'WX12D83JY5JA', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'Videos solo abren con Hetman', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727241180514528003298699074701_jpg_1772724127_50bc3499c802.jpg', 1, '2026-03-05 10:22:47', '2026-03-05 10:23:27'),
(205, 1, 'EQUIPO', 'HDD 3TB SELVA CAR IQUITOS', '27 sep 2023 - 25 abr 2024', 'Western Digital', 'WD30PURZ', 'WX22D610FS7J', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727245032774141019458578507210_jpg_1772724511_df3aa02d3c42.jpg', 1, '2026-03-05 10:29:10', '2026-03-05 10:29:10'),
(206, 1, 'EQUIPO', 'HDD 3TB GUIA MIS RUTAS HUARAZ', '28 ago 2023 - 22 may 2024', 'Western Digital', 'WD30PURZ', 'WCC4N5RJS704', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, 'Intalado por Luigi despues de problema con grabaciones de alumnos', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727247344606205180339832384541_jpg_1772724741_961b5d2f74bf.jpg', 1, '2026-03-05 10:33:07', '2026-03-05 10:33:42'),
(207, 1, 'EQUIPO', 'HDD 3TB GLOBAL MEDIC CORPORATION', '23 dic 2019 - 09 dic 2020', 'Western Digital', 'WD30PURZ', 'WCC4N0YZU1P0', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727256641077358086791329046533_jpg_1772725678_0ae781d4104c.jpg', 1, '2026-03-05 10:48:37', '2026-03-05 10:48:37'),
(208, 1, 'EQUIPO', 'HDD 3TB SELVA CAR PUCALLPA', '19 ago 2020 - 21 dic 2020', 'Western Digital', 'WD30PURZ', 'WCC4N0JZN01P', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727259971272791203351461756185_jpg_1772726015_08f10b3cb4c2.jpg', 1, '2026-03-05 10:54:24', '2026-03-05 10:55:02'),
(209, 1, 'EQUIPO', 'HDD 3TB GLOBAL MEDIC PUCALLPA', '28 jun 2022 - 27 sep 2022', 'Western Digital', 'WD30PURZ', 'WX12D81HH5YC', 1.00, 'UND', 'BUENO', 1, NULL, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727262016102861251415995694509_jpg_1772726210_4192f8880a89.jpg', 1, '2026-03-05 10:57:28', '2026-03-05 10:57:28'),
(210, 1, 'EQUIPO', 'HDD 3TB GLOBAL MEDIC PUCALLPA', '12 abr 2021 - 07 jul 2021', 'Western Digital', 'WD30PURZ', 'WX42D60HRVZT', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727269950236136556666625118396_jpg_1772727011_42095de0a18d.jpg', 1, '2026-03-05 11:10:56', '2026-03-05 11:10:56'),
(211, 1, 'EQUIPO', 'HDD 3TB SELVA CAR PUCALLPA', '17 ene 2022 - 01 jul 2022', 'Western Digital', 'WD30PURZ', 'WX42D51N5T7P', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727271959054615113069262456629_jpg_1772727210_176c5003a4df.jpg', 1, '2026-03-05 11:18:33', '2026-03-05 11:18:33'),
(212, 1, 'EQUIPO', 'HDD 3TB GLOBAL MEDIC PUCALLPA', '31 dic 2021 - 29 mar 2022', 'Western Digital', 'WD30PURZ', 'WX52D60DSPC9', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727276993293876480675148815863_jpg_1772727710_b48e923d96a8.jpg', 1, '2026-03-05 11:22:54', '2026-03-05 11:22:54'),
(213, 1, 'EQUIPO', 'HDD 1TB SELVA CAR PUCALLPA', '22 nov 2021 - 16 ene 2022', 'Seagate', 'BarraCuda', 'ZN1NHML7', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727278744916589023206868782768_jpg_1772727883_909533868e0f.jpg', 1, '2026-03-05 11:27:22', '2026-03-05 11:27:22'),
(214, 1, 'EQUIPO', 'HDD 3TB SELVA CAR PUCALLPA', '04 jul 2022 - 12 dic 2022', 'Western Digital', 'WD30PURZ', 'WX12D81FZC76', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727280931726111530785351893735_jpg_1772728101_6f09b6fe5c35.jpg', 1, '2026-03-05 11:29:40', '2026-03-05 11:29:40'),
(215, 1, 'EQUIPO', 'HDD 4TB GLOBAL MEDIC PUCALLPA', '30 dic 2022 - 04 may 2023', 'Western Digital', 'WD42PURZ', 'WX62D12JZ6ZD', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727282537397304780289256706076_jpg_1772728268_cb9ffee91e83.jpg', 1, '2026-03-05 11:32:03', '2026-03-05 11:32:03'),
(216, 1, 'EQUIPO', 'HDD 3TB GLOBAL MEDIC PUCALLPA', '20 oct 2020 - 13 ene 2021', 'Western Digital', 'WD30PURZ', 'WCC4N4UDU649', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/1772728467896223520861206042659_jpg_1772728482_98d86cd5b912.jpg', 1, '2026-03-05 11:35:21', '2026-03-05 11:35:21'),
(217, 1, 'EQUIPO', 'HDD 4TB SELVA CAR PUCALLPA', '31 dic 2023 - 03 oct 2024', 'Western Digital', 'WD43PURZ', 'WX22D639J9R5', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'No reconoce el DVR', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727289235092358505010245004871_jpg_1772728933_46dea1eb601c.jpg', 1, '2026-03-05 11:42:58', '2026-03-05 11:43:51'),
(218, 1, 'EQUIPO', 'HDD 4TB SELVA CAR PUCALLPA', '16 jun 2023 - 29 dic 2023', 'Western Digital', 'WD40PURZ', 'WXB2DA1PYP18', 1.00, 'UND', 'REGULAR', 1, 4, NULL, NULL, NULL, 'No reconoce DVR', 'inventario/leoncorp_bucket/bienes/1/2026/03/1772729216318905868114569517760_jpg_1772729225_a2aa08a8093e.jpg', 1, '2026-03-05 11:47:46', '2026-03-05 11:49:34'),
(219, 1, 'EQUIPO', 'HDD 3TB GLOBAL MEDIC PUCALLPA', '30 mar 2022 - 27 jun 2022', 'Western Digital', 'WD30PURZ', 'WX22D610F4KC', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727294107105264371908992956088_jpg_1772729428_7476920321ac.jpg', 1, '2026-03-05 11:51:17', '2026-03-05 11:51:17'),
(220, 1, 'EQUIPO', 'HDD 3TB GLOBAL MEDIC PUCALLPA', '08 jul 2021 - 04 oct 2021', 'Western Digital', 'WD30PURZ', 'WX32D60FXK4D', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727295452125191231596437659059_jpg_1772729554_926c8f7281f2.jpg', 1, '2026-03-05 11:53:39', '2026-03-05 11:53:39'),
(221, 1, 'EQUIPO', 'HDD 3TB GLOBAL MEDIC PUCALLPA', '14 ene 2021 - 09 abri 2021', 'Western Digital', 'WD30PURZ', 'WX92D5087FL1', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727303360264368117015027950673_jpg_1772730344_35b31b00ef5c.jpg', 1, '2026-03-05 12:06:38', '2026-03-05 12:06:38'),
(222, 1, 'EQUIPO', 'HDD 3TB SELVA CAR PUCALLPA', '26 abr 2021 - 03 sep 2021', 'Western Digital', 'WD30PURZ', 'WX42D60HRDLL', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17727305236621863576444121872943_jpg_1772730531_ded01eee6ef3.jpg', 1, '2026-03-05 12:09:29', '2026-03-05 12:09:53'),
(223, 1, 'EQUIPO', 'HDD 3TB GLOBAL CAR TRUJILLO', '29 may 2025 - 30 jul 2025', 'Western Dígital', 'WD30PURZ', 'WX42D6038K61', 1.00, 'UND', 'BUENO', 1, 4, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17728108379834569337098990375854_jpg_1772810852_6c7324a94da1.jpg', 1, '2026-03-06 10:28:11', '2026-03-06 10:28:11'),
(224, 1, 'EQUIPO', 'USB', 'Usb de 64 gb', 'Kingston', 'AbC23', '37373839', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, '3 años de uso.', 'inventario/leoncorp_bucket/bienes/1/2026/03/circular_genesis_png_1773147990_c6327eb5a5ce.png', 1, '2026-03-10 08:00:39', '2026-03-10 08:06:35'),
(225, 1, 'EQUIPO', 'Laptop Dell', 'Laptop gris en buen estado para administracion', 'Dell', 'Kjh-2939', '847474849', 1.00, 'UND', 'BUENO', 1, 1, NULL, NULL, NULL, '', 'inventario/leoncorp_bucket/bienes/1/2026/03/17731615241338974802133825073556_jpg_1773161535_302db4310482.jpg', 1, '2026-03-10 11:52:30', '2026-03-10 11:52:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inv_bien_categoria`
--

CREATE TABLE `inv_bien_categoria` (
  `id_bien` int(10) UNSIGNED NOT NULL,
  `id_categoria` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `inv_bien_categoria`
--

INSERT INTO `inv_bien_categoria` (`id_bien`, `id_categoria`) VALUES
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(24, 1),
(25, 1),
(112, 1),
(113, 1),
(114, 1),
(115, 1),
(116, 1),
(117, 1),
(118, 1),
(119, 1),
(120, 1),
(121, 1),
(122, 1),
(123, 1),
(124, 1),
(125, 1),
(130, 1),
(145, 1),
(187, 1),
(189, 1),
(190, 1),
(191, 1),
(192, 1),
(193, 1),
(194, 1),
(195, 1),
(196, 1),
(197, 1),
(198, 1),
(199, 1),
(200, 1),
(201, 1),
(202, 1),
(203, 1),
(204, 1),
(205, 1),
(206, 1),
(207, 1),
(208, 1),
(209, 1),
(210, 1),
(211, 1),
(212, 1),
(213, 1),
(214, 1),
(215, 1),
(216, 1),
(217, 1),
(218, 1),
(219, 1),
(220, 1),
(221, 1),
(222, 1),
(223, 1),
(225, 2),
(31, 4),
(32, 4),
(33, 4),
(34, 4),
(35, 4),
(36, 4),
(37, 4),
(41, 4),
(42, 4),
(70, 4),
(72, 4),
(73, 4),
(74, 4),
(75, 4),
(76, 4),
(77, 4),
(78, 4),
(79, 4),
(80, 4),
(81, 4),
(82, 4),
(83, 4),
(91, 4),
(92, 4),
(93, 4),
(94, 4),
(96, 4),
(97, 4),
(98, 4),
(99, 4),
(100, 4),
(101, 4),
(102, 4),
(103, 4),
(104, 4),
(105, 4),
(106, 4),
(23, 5),
(52, 5),
(53, 5),
(59, 5),
(60, 5),
(61, 5),
(62, 5),
(64, 5),
(66, 5),
(67, 5),
(68, 5),
(111, 5),
(179, 5),
(180, 5),
(188, 5),
(1, 6),
(2, 6),
(3, 6),
(5, 6),
(6, 6),
(7, 6),
(8, 6),
(9, 6),
(10, 6),
(11, 6),
(12, 6),
(27, 6),
(28, 6),
(29, 6),
(30, 6),
(107, 6),
(108, 6),
(109, 6),
(110, 6),
(147, 6),
(148, 6),
(149, 6),
(150, 6),
(151, 6),
(153, 6),
(154, 6),
(155, 6),
(156, 6),
(157, 6),
(158, 6),
(159, 6),
(160, 6),
(161, 6),
(162, 6),
(163, 6),
(164, 6),
(165, 6),
(166, 6),
(167, 6),
(168, 6),
(169, 6),
(171, 6),
(172, 6),
(174, 6),
(175, 6),
(176, 6),
(177, 6),
(178, 6),
(181, 6),
(184, 6),
(185, 6),
(186, 6),
(224, 6),
(225, 6),
(23, 7),
(24, 7),
(25, 7),
(26, 7),
(43, 8),
(44, 8),
(45, 8),
(46, 8),
(47, 8),
(48, 8),
(49, 8),
(50, 8),
(51, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inv_categorias`
--

CREATE TABLE `inv_categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `inv_categorias`
--

INSERT INTO `inv_categorias` (`id`, `id_empresa`, `nombre`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, 'camaras', 1, '2026-01-30 15:51:04', '2026-01-30 15:51:04'),
(2, 1, 'inspección', 1, '2026-01-30 15:51:23', '2026-01-30 15:51:23'),
(3, 1, 'tumbes', 1, '2026-01-30 15:51:34', '2026-01-30 15:51:34'),
(4, 1, 'huelleros', 1, '2026-01-30 15:53:49', '2026-01-30 15:53:49'),
(5, 1, 'redes', 1, '2026-01-30 15:55:34', '2026-01-30 15:55:34'),
(6, 1, 'Computo', 1, '2026-02-02 12:48:50', '2026-02-02 12:48:50'),
(7, 1, 'OpenCarTumbes', 1, '2026-02-04 16:51:42', '2026-02-04 16:51:42'),
(8, 1, 'Facturación', 1, '2026-02-11 11:58:19', '2026-02-11 11:58:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inv_movimientos`
--

CREATE TABLE `inv_movimientos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_bien` int(10) UNSIGNED NOT NULL,
  `tipo` enum('INGRESO','ASIGNACION','TRASLADO','BAJA','AJUSTE') NOT NULL DEFAULT 'TRASLADO',
  `desde_ubicacion` int(10) UNSIGNED DEFAULT NULL,
  `hacia_ubicacion` int(10) UNSIGNED DEFAULT NULL,
  `desde_responsable` int(10) UNSIGNED DEFAULT NULL,
  `desde_resp_nombres` varchar(100) DEFAULT NULL,
  `desde_resp_apellidos` varchar(100) DEFAULT NULL,
  `desde_resp_dni` varchar(15) DEFAULT NULL,
  `hacia_responsable` int(10) UNSIGNED DEFAULT NULL,
  `hacia_resp_nombres` varchar(100) DEFAULT NULL,
  `hacia_resp_apellidos` varchar(100) DEFAULT NULL,
  `hacia_resp_dni` varchar(15) DEFAULT NULL,
  `cantidad` decimal(12,2) DEFAULT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `inv_movimientos`
--

INSERT INTO `inv_movimientos` (`id`, `id_empresa`, `id_bien`, `tipo`, `desde_ubicacion`, `hacia_ubicacion`, `desde_responsable`, `desde_resp_nombres`, `desde_resp_apellidos`, `desde_resp_dni`, `hacia_responsable`, `hacia_resp_nombres`, `hacia_resp_apellidos`, `hacia_resp_dni`, `cantidad`, `nota`, `id_usuario`, `creado`) VALUES
(1, 1, 1, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-02 12:48:53'),
(2, 1, 2, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-02 12:53:11'),
(3, 1, 3, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-02 12:58:08'),
(4, 1, 5, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-02 13:03:45'),
(5, 1, 6, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-02 18:53:42'),
(6, 1, 7, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-02 19:35:03'),
(7, 1, 8, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-02 19:40:37'),
(8, 1, 9, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-02 19:46:40'),
(9, 1, 10, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-02 19:51:19'),
(10, 1, 11, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-02 19:56:38'),
(11, 1, 12, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-02 20:01:39'),
(12, 1, 13, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 11:13:19'),
(13, 1, 14, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 11:19:21'),
(14, 1, 15, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 11:28:19'),
(15, 1, 16, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 11:38:20'),
(16, 1, 17, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 11:45:10'),
(17, 1, 18, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 11:56:46'),
(18, 1, 19, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 12:04:18'),
(19, 1, 20, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 12:13:53'),
(20, 1, 21, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 12:24:14'),
(21, 1, 22, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-03 12:27:06'),
(22, 1, 23, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-04 15:34:57'),
(23, 1, 24, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-04 16:51:44'),
(24, 1, 25, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-04 17:05:26'),
(25, 1, 26, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-04 17:09:04'),
(26, 1, 27, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 10:16:00'),
(27, 1, 28, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 10:18:36'),
(28, 1, 29, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 10:22:54'),
(29, 1, 30, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 10:28:44'),
(30, 1, 31, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 11:06:34'),
(31, 1, 32, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 11:11:56'),
(32, 1, 33, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 11:25:07'),
(33, 1, 34, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 11:44:57'),
(34, 1, 35, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 11:53:28'),
(35, 1, 36, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-05 12:22:19'),
(36, 1, 37, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-09 11:39:26'),
(37, 1, 41, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-09 12:28:55'),
(38, 1, 42, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-09 12:35:40'),
(39, 1, 43, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-11 11:58:39'),
(40, 1, 44, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-11 12:01:57'),
(41, 1, 45, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-11 12:05:20'),
(42, 1, 46, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-11 12:07:05'),
(43, 1, 47, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-11 12:12:28'),
(44, 1, 48, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-11 12:13:39'),
(45, 1, 49, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-11 12:14:39'),
(46, 1, 50, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-11 12:15:46'),
(47, 1, 51, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-11 12:19:10'),
(48, 1, 52, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 10:48:24'),
(49, 1, 53, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 10:56:18'),
(50, 1, 59, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 10:58:05'),
(51, 1, 60, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 11:03:42'),
(52, 1, 61, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 11:06:49'),
(53, 1, 62, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 11:09:52'),
(54, 1, 64, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 11:12:53'),
(55, 1, 66, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 11:24:02'),
(56, 1, 67, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 11:27:10'),
(57, 1, 68, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-13 11:53:33'),
(58, 1, 70, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:07:10'),
(59, 1, 72, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:09:48'),
(60, 1, 73, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:12:21'),
(61, 1, 74, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:14:36'),
(62, 1, 75, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:19:44'),
(63, 1, 76, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:21:27'),
(64, 1, 77, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:23:16'),
(65, 1, 78, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:25:30'),
(66, 1, 79, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:26:44'),
(67, 1, 80, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:28:31'),
(68, 1, 81, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:30:34'),
(69, 1, 82, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:33:41'),
(70, 1, 83, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:35:01'),
(71, 1, 91, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:38:58'),
(72, 1, 92, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:40:06'),
(73, 1, 93, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:45:30'),
(74, 1, 94, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:47:07'),
(75, 1, 96, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:57:37'),
(76, 1, 97, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 11:59:55'),
(77, 1, 98, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 12:01:39'),
(78, 1, 99, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 12:03:39'),
(79, 1, 100, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 12:15:37'),
(80, 1, 101, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 12:17:13'),
(81, 1, 102, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 12:18:31'),
(82, 1, 103, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 12:24:19'),
(83, 1, 104, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 12:25:17'),
(84, 1, 105, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 12:27:19'),
(85, 1, 106, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-16 12:34:20'),
(86, 1, 107, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-18 10:29:08'),
(87, 1, 108, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-18 10:48:11'),
(88, 1, 109, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-18 11:09:14'),
(89, 1, 110, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-18 11:23:06'),
(90, 1, 111, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-18 11:28:06'),
(91, 1, 112, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:06:25'),
(92, 1, 113, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:09:06'),
(93, 1, 114, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:15:39'),
(94, 1, 115, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:18:07'),
(95, 1, 116, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:19:36'),
(96, 1, 117, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:28:56'),
(97, 1, 118, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:36:48'),
(98, 1, 119, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:40:21'),
(99, 1, 120, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:51:51'),
(100, 1, 121, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:55:36'),
(101, 1, 122, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 11:59:38'),
(102, 1, 123, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 12:02:31'),
(103, 1, 124, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 12:18:25'),
(104, 1, 125, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 12:30:22'),
(105, 1, 130, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-19 12:34:14'),
(106, 1, 145, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-02-20 12:27:36'),
(108, 1, 147, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 10:02:05'),
(109, 1, 148, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 10:04:43'),
(110, 1, 149, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 10:07:23'),
(111, 1, 150, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 10:09:03'),
(112, 1, 151, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 10:15:02'),
(113, 1, 153, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 10:18:34'),
(114, 1, 154, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 10:24:04'),
(115, 1, 155, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 10:25:41'),
(116, 1, 156, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 10:28:45'),
(117, 1, 157, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:18:37'),
(118, 1, 158, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:23:16'),
(119, 1, 159, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:25:18'),
(120, 1, 160, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:28:29'),
(121, 1, 161, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:30:29'),
(122, 1, 162, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:31:10'),
(123, 1, 163, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:32:13'),
(124, 1, 164, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:33:00'),
(125, 1, 165, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:33:27'),
(126, 1, 166, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:34:57'),
(127, 1, 167, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:36:37'),
(128, 1, 168, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:40:31'),
(129, 1, 169, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:41:45'),
(130, 1, 171, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:42:34'),
(131, 1, 172, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:44:15'),
(132, 1, 174, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-02-25 11:45:22'),
(133, 1, 175, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:12:13'),
(134, 1, 176, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:15:03'),
(135, 1, 177, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:22:00'),
(136, 1, 178, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:24:06'),
(137, 1, 179, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:26:27'),
(138, 1, 180, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:28:50'),
(139, 1, 181, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:32:44'),
(140, 1, 184, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:40:14'),
(141, 1, 185, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:41:35'),
(142, 1, 186, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:44:15'),
(143, 1, 187, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:47:06'),
(144, 1, 188, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-03 09:49:45'),
(145, 1, 189, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 11:20:31'),
(146, 1, 190, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 11:33:48'),
(147, 1, 191, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 11:57:06'),
(148, 1, 192, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:02:54'),
(149, 1, 193, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:07:33'),
(150, 1, 194, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:17:55'),
(151, 1, 195, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:31:42'),
(152, 1, 196, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:40:20'),
(153, 1, 197, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:44:20'),
(154, 1, 198, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:49:20'),
(155, 1, 199, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:52:49'),
(156, 1, 200, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:55:44'),
(157, 1, 201, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 12:59:28'),
(158, 1, 202, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-04 13:04:01'),
(159, 1, 203, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 09:59:33'),
(160, 1, 204, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 10:22:47'),
(161, 1, 205, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 10:29:10'),
(162, 1, 206, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 10:33:07'),
(163, 1, 207, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 10:48:37'),
(164, 1, 208, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 10:54:24'),
(165, 1, 209, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 10:57:28'),
(166, 1, 210, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:10:56'),
(167, 1, 211, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:18:33'),
(168, 1, 212, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:22:54'),
(169, 1, 213, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:27:22'),
(170, 1, 214, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:29:40'),
(171, 1, 215, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:32:03'),
(172, 1, 216, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:35:21'),
(173, 1, 217, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:42:58'),
(174, 1, 218, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:47:46'),
(175, 1, 219, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:51:17'),
(176, 1, 220, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 11:53:39'),
(177, 1, 221, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 12:06:38'),
(178, 1, 222, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-05 12:09:29'),
(179, 1, 223, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 4, '2026-03-06 10:28:11'),
(180, 1, 224, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-03-10 08:00:39'),
(181, 1, 225, 'INGRESO', NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 1.00, 'Ingreso inicial', 1, '2026-03-10 11:52:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inv_ubicaciones`
--

CREATE TABLE `inv_ubicaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `inv_ubicaciones`
--

INSERT INTO `inv_ubicaciones` (`id`, `id_empresa`, `nombre`, `descripcion`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, 'ALMACEN TI CENTRAL (5TO PISO)', NULL, 1, '2026-01-30 15:50:51', '2026-01-30 15:50:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iv_camaras`
--

CREATE TABLE `iv_camaras` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `etiqueta` varchar(100) DEFAULT NULL,
  `ambiente` varchar(120) DEFAULT NULL,
  `marca` varchar(120) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `serie` varchar(120) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `iv_camaras`
--

INSERT INTO `iv_camaras` (`id`, `id_empresa`, `etiqueta`, `ambiente`, `marca`, `modelo`, `serie`, `notas`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, 'CAM-01', 'Entrada', 'Hikvision', 'DS-2CD2143G2-I', 'HK-CAM-PE-00001', 'Ángulo a puerta principal.', 0, '2026-02-03 11:31:52', '2026-03-05 17:56:51'),
(2, 1, 'CAM-02', 'Recepción', 'Dahua', 'IPC-HDW2431T-AS', 'DH-CAM-PE-00018', 'Vista mostrador.', 0, '2026-02-03 11:32:09', '2026-03-05 17:56:49'),
(3, 1, 'CAM-03', 'Sala CCTV', 'Uniview', 'IPC3614SR3-DPF28', 'UV-CAM-PE-00005', 'Foco fijo 2.8mm.', 0, '2026-02-03 11:32:26', '2026-03-05 17:56:48'),
(4, 8, 'CÁMARA 01', 'Recepción', 'HIKVISION', 'DS-2CE56D0T-IRPF', 'K23594167', '', 1, '2026-02-03 17:46:49', '2026-02-03 17:48:10'),
(5, 8, 'CÁMARA 02', 'Aula teórica 1', 'HIKVISION', 'DS-2CE56C2T-IT3', '559596420', '', 1, '2026-02-03 17:47:25', '2026-02-03 17:47:25'),
(6, 7, 'CÁMARA 03', 'AULA 2', 'HIKVISION', 'DS-2CE56D0T-IRPF', 'FY7383213', '', 1, '2026-02-05 18:21:25', '2026-02-06 10:22:08'),
(7, 7, 'CÁMARA 02', 'AULA 1', 'HIKVISION', 'DS-2CE56C2T-IT3', '559596410', '', 1, '2026-02-05 18:25:25', '2026-02-06 10:21:39'),
(8, 7, 'CÁMARA 04', 'PASILLO', 'HIKVISION', 'DS-2CE56C0T-IRPF', 'C84726011', '', 0, '2026-02-05 18:31:37', '2026-02-09 14:28:05'),
(9, 7, 'CÁMARA 01', 'RECEPCION', 'HIKVISION', 'DS-2CE56C2T-IT3', '558172837', '', 1, '2026-02-05 18:39:23', '2026-02-06 10:21:01'),
(10, 7, 'CÁMARA 05', 'EXTERIOR', 'HIKVISION', 'DS-2CD2022F-I', '135746720', '', 0, '2026-02-05 19:16:17', '2026-02-09 14:27:30'),
(11, 18, '', 'RECEPCION', 'HIKVISION', 'DS-2CD1023G0E-I', 'K10974313', '', 1, '2026-03-03 17:47:38', '2026-03-03 17:47:38'),
(12, 18, '', 'AULA-TEORICA', 'HIKVISION', 'DS-2CD2110F-I', '505027635', '', 1, '2026-03-03 18:01:18', '2026-03-03 18:01:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iv_computadoras`
--

CREATE TABLE `iv_computadoras` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `ambiente` varchar(120) DEFAULT NULL,
  `nombre_equipo` varchar(120) DEFAULT NULL,
  `marca` varchar(120) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `serie` varchar(120) DEFAULT NULL,
  `procesador` varchar(160) DEFAULT NULL,
  `disco_gb` varchar(60) DEFAULT NULL,
  `ram_gb` varchar(60) DEFAULT NULL,
  `sistema_operativo` varchar(160) DEFAULT NULL,
  `mac` varchar(60) DEFAULT NULL,
  `ip` varchar(60) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `iv_computadoras`
--

INSERT INTO `iv_computadoras` (`id`, `id_empresa`, `ambiente`, `nombre_equipo`, `marca`, `modelo`, `serie`, `procesador`, `disco_gb`, `ram_gb`, `sistema_operativo`, `mac`, `ip`, `notas`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, 'Recepción', 'PC-RECEP-01', 'Dell', 'OptiPlex 7090', 'DL7090-PE-00121', 'i5-11500', '512', '16', 'Windows 10 Pro', 'A4:BB:6D:10:22:33', '192.168.10.21', 'Equipo atención público.', 0, '2026-02-03 11:28:02', '2026-03-05 17:51:18'),
(2, 1, 'Administración', 'PC-ADM-01', 'HP', 'ProDesk 400 G6', 'HP400-PE-00077', 'i7-10700', '1024', '32', 'Windows 11 Pro', '10:7B:44:AA:BB:CC', '192.168.10.31', 'Caja fuerte digital instalada.', 0, '2026-02-03 11:29:04', '2026-03-05 17:51:20'),
(3, 1, 'Sala CCTV', 'PC-CCTV-01', 'Lenovo', 'ThinkCentre M70s', 'LN70S-PE-00012', 'i5-10400', '256', '8', 'Windows 10 Pro', '58:11:22:33:44:55', '192.168.10.41', 'Solo monitoreo.', 0, '2026-02-03 11:29:51', '2026-03-05 17:51:22'),
(4, 8, 'RECEPCION', 'RECEP', 'ENSAMBLADA', 'ENSAMBLADA', 'ENSAMBLADA', '12th Gen Intel(R) Core(TM) i5-12400', '466 GB', '8 GB', 'Windows 11 Pro', '9C-6B-00-69-08-9A', '192.168.143.100', '', 1, '2026-02-03 16:31:15', '2026-02-03 17:49:55'),
(5, 8, 'AULA', 'AULA', 'HP', '240 G7', 'SCG0302NT1', 'Intel(R) Core(TM) i3-1005G1', '224 GB', '4 GB', 'Windows 10 Pro', 'D3-6B-22-7A-08-B6', '192.168.143.130', '', 1, '2026-02-03 17:44:58', '2026-02-03 17:49:46'),
(6, 7, 'AULA 2', 'AULA02', 'Hewlett-Packard', 'HP ProDesk 600 G1 SFF', 'MXL4221T0J', 'INTEL CORE I3-4130', '240GB', '4GB', 'Windows 10 Pro', 'A0-D3-C1-1A-1D-43', '190.117.1.176', '', 1, '2026-02-05 18:51:20', '2026-02-06 11:16:36'),
(7, 7, 'AULA 1', 'AULA01', 'EMSAMBLADO', 'EMSAMBLADO', 'EMSAMBLADO', 'INTEL CORE I3-10100F', '932GB', '8GB', 'Windows 10 Pro', '18-C0-4D-E8-6B-22', '192.168.1.104', '', 1, '2026-02-05 18:58:41', '2026-02-06 11:07:10'),
(8, 7, 'RECEPCION', 'RECEPCION', 'ENSAMBLADO', 'ENSAMBLADO', 'ENSAMBLADO', 'INTEL CORE I3-3220', '224GB', '8GB', 'Windows 10 Home', 'EC-A8-6B-72-AD-F6', '192.168.1.190', '', 1, '2026-02-05 19:04:00', '2026-02-06 10:58:42'),
(9, 18, 'RECEPCION', 'RECEPCION', 'DATAONE', 'ENSAMBLADA', 'ENSAMBLADA', 'Intel (R) Core (TM) i3-4130', '465 GB', '4.00 GB', 'Windows 10 Pro', '2A-10-E1-08-00-29', '192.168.131.250', '', 1, '2026-03-03 17:41:03', '2026-03-04 08:25:58'),
(10, 18, 'AULA TEORICA', 'AULA-01', 'AVATEC', 'ENSAMBLADA', 'ENSAMBLADA', 'Intel (R) Core (TM) i3-4170', '224 GB', '2.00 GB', 'Windows 10 Pro', '0A-E0-AF-C1-16-96', '192.168.131.252', '', 1, '2026-03-03 17:44:19', '2026-03-03 17:44:19'),
(11, 18, 'RECEPCION', 'RECEP-02', 'AVATEC', 'ENSAMBLADA', 'ENSAMBLADA', 'Intel (R) Core (TM) i3-4170', '224 GB', '4.00 GB', 'Windows 10 Pro', 'FC-AA-14-59-D2-9A', '192.168.131.249', '', 1, '2026-03-03 18:32:08', '2026-03-03 18:32:08'),
(12, 1, 'LABORATORIO', 'LAB', 'ENSAMBLADO', 'ENSAMBLADO', 'ENSAMBLADO', '13th Gen Intel(R) Core(TM) i3-13100', '466 GB', '8 GB', 'Windows 11 Pro', '30-56-0F-56-F2-9C', '192.168.191.65', 'De GM Chota, falta huellero - pc nueva que se compró.', 1, '2026-03-05 17:53:33', '2026-03-05 17:53:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iv_dvrs`
--

CREATE TABLE `iv_dvrs` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `marca` varchar(120) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `serie` varchar(120) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `iv_dvrs`
--

INSERT INTO `iv_dvrs` (`id`, `id_empresa`, `marca`, `modelo`, `serie`, `notas`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, 'Hikvision', 'DS-7608NI-K2', 'HVK2-8CH-PE-000341', 'Rack principal, 8 canales.', 0, '2026-02-03 11:26:51', '2026-03-05 17:56:53'),
(2, 7, 'HIKVISION', 'iDS-7204HQHI-M1 / S', 'L07051972', '', 1, '2026-02-03 17:25:40', '2026-02-05 18:41:17'),
(3, 8, 'HIKVISION', 'DS-7204HGHI-K1', 'K13896640', '', 1, '2026-02-03 17:32:36', '2026-02-03 17:48:39'),
(4, 18, 'HIKVISION', 'iDS-7204HQHI-M1/S', 'K11962636', '', 1, '2026-03-03 18:02:45', '2026-03-03 18:02:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iv_huelleros`
--

CREATE TABLE `iv_huelleros` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `etiqueta` varchar(100) DEFAULT NULL,
  `marca` varchar(120) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `serie` varchar(120) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `iv_huelleros`
--

INSERT INTO `iv_huelleros` (`id`, `id_empresa`, `etiqueta`, `marca`, `modelo`, `serie`, `notas`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, 'BIO-01', 'ZKTeco', 'K40', 'ZK-K40-PE-00009', 'Ingreso personal.', 0, '2026-02-03 11:31:11', '2026-03-05 17:57:06'),
(2, 1, 'BIO-02', 'ZKTeco', 'iFace 402', 'ZK-IF402-PE-00003', 'Ingreso supervisores.', 0, '2026-02-03 11:31:28', '2026-03-05 17:57:04'),
(3, 8, 'RECEPCIÓN', 'INTEGRATED BIOMETRICS', 'COLUMBO', '34400126-000C', '', 1, '2026-02-03 16:32:56', '2026-02-03 16:32:56'),
(4, 7, 'RECEPCION', 'Integrated Biometrics', 'Columbo', '22302269-000c', '', 1, '2026-02-05 19:08:15', '2026-02-06 11:00:18'),
(5, 18, '', 'INTEGRATED BIOMETRICS', 'COLUMBO', 'CD110CA-02900317-000C', '', 1, '2026-03-03 18:05:59', '2026-03-03 19:00:54'),
(6, 18, '', 'INTEGRATED BIOMETRICS', 'COLUMBO', 'CD110CA-22600177-000K', '', 1, '2026-03-03 18:08:31', '2026-03-03 18:08:31'),
(7, 1, 'LABORATORIO', 'INTEGRATED BIOMETRICS', 'COLUMBO', 'CD110CA-52600296-000C', 'Del dr Jimmy GM Chota, se inventó la primera parte del nro de serie.', 1, '2026-03-05 17:56:31', '2026-03-05 17:57:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iv_red`
--

CREATE TABLE `iv_red` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `ip_publica` varchar(120) DEFAULT NULL,
  `transmision_online` varchar(255) DEFAULT NULL,
  `bajada_txt` varchar(60) DEFAULT NULL,
  `subida_txt` varchar(60) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `iv_red`
--

INSERT INTO `iv_red` (`id`, `id_empresa`, `ip_publica`, `transmision_online`, `bajada_txt`, `subida_txt`, `notas`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, '190.12.34.56', 'https://stream.empresa-pe.com/live/admin', '200 Mbps', '50 Mbps', 'Internet fibra. Router principal en rack.', 0, '2026-02-03 11:25:48', '2026-03-05 17:57:18'),
(2, 7, '190.117.61.84', 'http://190.117.61.84:2004', '10 Mbps', '10 Mbps', '', 1, '2026-02-03 16:57:40', '2026-02-03 17:11:33'),
(3, 8, '187.102.210.150', 'http://187.102.210.150:2002', '10Mbps', '10Mbps', '', 1, '2026-02-03 17:31:17', '2026-02-03 17:31:17'),
(4, 18, '187.102.210.147', '187.102.210.147:2003', '4 mbps', '4 mbps', '', 1, '2026-03-03 18:11:15', '2026-03-04 08:28:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iv_switches`
--

CREATE TABLE `iv_switches` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `marca` varchar(120) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `serie` varchar(120) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `iv_switches`
--

INSERT INTO `iv_switches` (`id`, `id_empresa`, `marca`, `modelo`, `serie`, `notas`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, 'TP-Link', 'TL-SG1024DE', 'TPL-24P-2026-00991', '24 puertos, VLAN para CCTV.', 0, '2026-02-03 11:27:07', '2026-03-05 17:57:20'),
(2, 8, 'TP-LINK', 'TL-SG1008D', '2178056008355', '', 1, '2026-02-03 17:50:29', '2026-02-03 17:50:29'),
(3, 7, 'TP-LINK', 'TL-WR850N', '2239664000627', '', 1, '2026-02-05 18:45:12', '2026-02-05 18:45:12'),
(4, 18, 'Tp-Link', 'TL-SG1016D', '219C073000002', '', 1, '2026-03-03 18:03:55', '2026-03-03 18:03:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `iv_transmision`
--

CREATE TABLE `iv_transmision` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `acceso_url` varchar(255) DEFAULT NULL,
  `usuario` varchar(120) DEFAULT NULL,
  `clave` varchar(160) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `iv_transmision`
--

INSERT INTO `iv_transmision` (`id`, `id_empresa`, `acceso_url`, `usuario`, `clave`, `notas`, `activo`, `creado`, `actualizado`) VALUES
(1, 1, 'https://panel.streaming.com/login', 'admin_stream', 'Mtc#2026!', 'Cuenta principal para emisión.', 0, '2026-02-03 11:26:22', '2026-03-05 17:57:16'),
(2, 7, 'http://scpucallpa.dvrdns.org:2004', 'sutran', 'sutr@n@LC2023', 'Clave aun no configurada', 1, '2026-02-03 16:55:34', '2026-02-06 10:26:33'),
(3, 8, 'http://scaguaytia.dvrdns.org:2002', 'sutran', 'sutr@n@LC2024', '', 1, '2026-02-03 17:30:11', '2026-02-03 17:51:51'),
(4, 18, 'http://opencartumbes.dvrdns.org:2003', 'sutran', 'openSUT@26', 'por crear', 1, '2026-03-03 18:12:11', '2026-03-04 08:36:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mod_api_hub_uso_mensual`
--

CREATE TABLE `mod_api_hub_uso_mensual` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `periodo_mes` date NOT NULL COMMENT 'Primer día del mes, p.ej. 2026-03-01',
  `dni_ok` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `dni_fail` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ruc_ok` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ruc_fail` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ultima_consulta_at` datetime DEFAULT NULL,
  `ultima_tipo` enum('DNI','RUC') DEFAULT NULL,
  `ultima_estado` enum('OK','FAIL') DEFAULT NULL,
  `ultima_mensaje` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mod_api_hub_uso_mensual`
--

INSERT INTO `mod_api_hub_uso_mensual` (`id`, `empresa_id`, `periodo_mes`, `dni_ok`, `dni_fail`, `ruc_ok`, `ruc_fail`, `ultima_consulta_at`, `ultima_tipo`, `ultima_estado`, `ultima_mensaje`, `created_at`, `updated_at`) VALUES
(1, 19, '2026-03-01', 26, 9, 4, 2, '2026-03-17 14:34:10', 'DNI', 'OK', '', '2026-03-13 01:36:08', '2026-03-17 14:34:10'),
(40, 20, '2026-03-01', 10, 14, 0, 0, '2026-03-17 12:07:12', 'DNI', 'FAIL', 'No se encontró información para ese DNI.', '2026-03-16 12:39:19', '2026-03-17 12:07:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mod_caja_auditoria`
--

CREATE TABLE `mod_caja_auditoria` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_caja_mensual` int(10) UNSIGNED DEFAULT NULL,
  `id_caja_diaria` int(10) UNSIGNED DEFAULT NULL,
  `evento` enum('abrir_mensual','cerrar_mensual','abrir_diaria','cerrar_diaria','eliminar_mensual','eliminar_diaria') NOT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `actor_id` int(10) UNSIGNED NOT NULL,
  `actor_usuario` varchar(64) NOT NULL,
  `actor_nombre` varchar(150) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mod_caja_auditoria`
--

INSERT INTO `mod_caja_auditoria` (`id`, `id_empresa`, `id_caja_mensual`, `id_caja_diaria`, `evento`, `detalle`, `actor_id`, `actor_usuario`, `actor_nombre`, `ip`, `creado_en`) VALUES
(1, 19, 1, NULL, 'abrir_mensual', 'Apertura CM CM-19-202603', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-03 23:14:39'),
(2, 19, 1, 1, 'abrir_diaria', 'Apertura CD CD-19-20260303', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-03 23:14:49'),
(3, 19, 1, 1, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260303 (fecha 2026-03-03). Motivo: Ocupado con clientes.', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 09:58:49'),
(4, 19, 1, 2, 'abrir_diaria', 'Apertura CD CD-19-20260304', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 09:58:56'),
(5, 19, 1, 2, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260304 (fecha 2026-03-04). Motivo: me olvidé', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-05 09:33:10'),
(6, 19, 1, 3, 'abrir_diaria', 'Apertura CD CD-19-20260305', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-05 09:35:54'),
(7, 19, 1, 3, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260305 (fecha 2026-03-05). Motivo: hhhhhhhhhhhh', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-06 10:33:08'),
(8, 19, 1, 4, 'abrir_diaria', 'Apertura CD CD-19-20260306', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '38.253.190.90', '2026-03-06 17:45:20'),
(9, 19, 1, 4, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260306 (fecha 2026-03-06). Motivo: me equivoqué', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-10 08:57:24'),
(10, 19, 1, 5, 'abrir_diaria', 'Apertura CD CD-19-20260310', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-10 08:57:38'),
(11, 19, 1, 5, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260310 (fecha 2026-03-10). Motivo: faltó', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-12 09:56:48'),
(12, 19, 1, 6, 'abrir_diaria', 'Apertura CD CD-19-20260312', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-12 09:56:57'),
(13, 19, 1, 6, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260312 (fecha 2026-03-12). Motivo: FGFFGGF', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-13 00:01:32'),
(14, 19, 1, 7, 'abrir_diaria', 'Apertura CD CD-19-20260313', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-13 00:05:46'),
(15, 19, 1, 7, 'cerrar_diaria', 'Cierre CD CD-19-20260313', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-13 00:05:51'),
(16, 19, 1, 7, 'abrir_diaria', 'Apertura # 08 | 2026-03-13 | Reapertura', 1, '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', '179.6.167.180', '2026-03-13 00:06:37'),
(17, 19, 1, 7, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260313 (fecha 2026-03-13). Motivo: jjjjjjjjjjjjj', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 11:50:59'),
(18, 19, 1, 8, 'abrir_diaria', 'Apertura CD CD-19-20260314', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 11:52:14'),
(19, 19, 1, 8, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260314 (fecha 2026-03-14). Motivo: aaaaaaaaaaaaa', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 10:01:41'),
(20, 19, 1, 9, 'abrir_diaria', 'Apertura CD CD-19-20260315', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 10:02:18'),
(21, 20, 2, NULL, 'abrir_mensual', 'Apertura CM CM-20-202603', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '179.6.167.180', '2026-03-16 01:09:36'),
(22, 19, 1, 9, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260315 (fecha 2026-03-15). Motivo: aaaaaa', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-16 09:43:19'),
(23, 19, 1, 10, 'abrir_diaria', 'Apertura CD CD-19-20260316', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-16 09:43:25'),
(24, 20, 2, 11, 'abrir_diaria', 'Apertura CD CD-20-20260316', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-16 12:36:38'),
(25, 19, 1, 10, 'cerrar_diaria', 'Cierre extemporáneo CD CD-19-20260316 (fecha 2026-03-16). Motivo: lllllllll', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-17 11:15:21'),
(26, 20, 2, 11, 'cerrar_diaria', 'Cierre extemporáneo CD CD-20-20260316 (fecha 2026-03-16). Motivo: Periodo de pruebas', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-17 11:52:43'),
(27, 20, 2, 12, 'abrir_diaria', 'Apertura CD CD-20-20260317', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-17 11:53:25'),
(28, 19, 1, 13, 'abrir_diaria', 'Apertura CD CD-19-20260317', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-17 13:54:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mod_caja_diaria`
--

CREATE TABLE `mod_caja_diaria` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_caja_mensual` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  `abierto_por` int(10) UNSIGNED NOT NULL,
  `abierto_en` datetime NOT NULL DEFAULT current_timestamp(),
  `cerrado_por` int(10) UNSIGNED DEFAULT NULL,
  `cerrado_en` datetime DEFAULT NULL,
  `abierta_key` tinyint(4) GENERATED ALWAYS AS (case when `estado` = 'abierta' then 1 else NULL end) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mod_caja_diaria`
--

INSERT INTO `mod_caja_diaria` (`id`, `id_empresa`, `id_caja_mensual`, `fecha`, `codigo`, `estado`, `abierto_por`, `abierto_en`, `cerrado_por`, `cerrado_en`) VALUES
(1, 19, 1, '2026-03-03', 'CD-19-20260303', 'cerrada', 10, '2026-03-03 23:14:49', 10, '2026-03-04 09:58:49'),
(2, 19, 1, '2026-03-04', 'CD-19-20260304', 'cerrada', 10, '2026-03-04 09:58:56', 10, '2026-03-05 09:33:10'),
(3, 19, 1, '2026-03-05', 'CD-19-20260305', 'cerrada', 10, '2026-03-05 09:35:54', 10, '2026-03-06 10:33:08'),
(4, 19, 1, '2026-03-06', 'CD-19-20260306', 'cerrada', 10, '2026-03-06 17:45:20', 10, '2026-03-10 08:57:24'),
(5, 19, 1, '2026-03-10', 'CD-19-20260310', 'cerrada', 10, '2026-03-10 08:57:38', 10, '2026-03-12 09:56:48'),
(6, 19, 1, '2026-03-12', 'CD-19-20260312', 'cerrada', 10, '2026-03-12 09:56:57', 10, '2026-03-13 00:01:32'),
(7, 19, 1, '2026-03-13', 'CD-19-20260313', 'cerrada', 1, '2026-03-13 00:06:37', 10, '2026-03-14 11:50:59'),
(8, 19, 1, '2026-03-14', 'CD-19-20260314', 'cerrada', 10, '2026-03-14 11:52:14', 10, '2026-03-15 10:01:41'),
(9, 19, 1, '2026-03-15', 'CD-19-20260315', 'cerrada', 10, '2026-03-15 10:02:18', 10, '2026-03-16 09:43:19'),
(10, 19, 1, '2026-03-16', 'CD-19-20260316', 'cerrada', 10, '2026-03-16 09:43:25', 10, '2026-03-17 11:15:21'),
(11, 20, 2, '2026-03-16', 'CD-20-20260316', 'cerrada', 18, '2026-03-16 12:36:38', 18, '2026-03-17 11:52:43'),
(12, 20, 2, '2026-03-17', 'CD-20-20260317', 'abierta', 18, '2026-03-17 11:53:25', NULL, NULL),
(13, 19, 1, '2026-03-17', 'CD-19-20260317', 'abierta', 10, '2026-03-17 13:54:12', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mod_caja_mensual`
--

CREATE TABLE `mod_caja_mensual` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `anio` smallint(5) UNSIGNED NOT NULL,
  `mes` tinyint(3) UNSIGNED NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  `abierto_por` int(10) UNSIGNED NOT NULL,
  `abierto_en` datetime NOT NULL DEFAULT current_timestamp(),
  `cerrado_por` int(10) UNSIGNED DEFAULT NULL,
  `cerrado_en` datetime DEFAULT NULL,
  `abierta_key` tinyint(4) GENERATED ALWAYS AS (case when `estado` = 'abierta' then 1 else NULL end) STORED,
  `periodo` int(11) GENERATED ALWAYS AS (`anio` * 100 + `mes`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mod_caja_mensual`
--

INSERT INTO `mod_caja_mensual` (`id`, `id_empresa`, `anio`, `mes`, `codigo`, `estado`, `abierto_por`, `abierto_en`, `cerrado_por`, `cerrado_en`) VALUES
(1, 19, 2026, 3, 'CM-19-202603', 'abierta', 10, '2026-03-03 23:14:39', NULL, NULL),
(2, 20, 2026, 3, 'CM-20-202603', 'abierta', 18, '2026-03-16 01:09:36', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mod_empresa_servicio`
--

CREATE TABLE `mod_empresa_servicio` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `servicio_id` int(10) UNSIGNED NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mod_empresa_servicio`
--

INSERT INTO `mod_empresa_servicio` (`id`, `empresa_id`, `servicio_id`, `creado`, `actualizado`) VALUES
(1, 19, 1, '2026-03-03 23:09:52', '2026-03-03 23:09:52'),
(2, 19, 2, '2026-03-03 23:14:06', '2026-03-03 23:14:06'),
(3, 19, 3, '2026-03-12 17:04:45', '2026-03-12 17:04:45'),
(4, 19, 4, '2026-03-12 23:08:35', '2026-03-12 23:08:35'),
(5, 19, 6, '2026-03-12 23:26:41', '2026-03-12 23:26:41'),
(6, 19, 7, '2026-03-12 23:30:56', '2026-03-12 23:30:56'),
(7, 19, 8, '2026-03-12 23:35:13', '2026-03-12 23:35:13'),
(8, 19, 9, '2026-03-12 23:39:13', '2026-03-12 23:39:13'),
(9, 19, 11, '2026-03-12 23:54:23', '2026-03-12 23:54:23'),
(10, 19, 10, '2026-03-13 00:01:49', '2026-03-13 00:01:49'),
(11, 19, 12, '2026-03-13 00:07:36', '2026-03-13 00:07:36'),
(12, 19, 13, '2026-03-13 10:49:13', '2026-03-13 10:49:13'),
(13, 20, 1, '2026-03-16 01:07:12', '2026-03-16 01:07:12'),
(14, 20, 2, '2026-03-16 01:07:18', '2026-03-16 01:07:18'),
(15, 20, 3, '2026-03-16 01:07:24', '2026-03-16 01:07:24'),
(16, 20, 4, '2026-03-16 01:07:30', '2026-03-16 01:07:30'),
(17, 20, 5, '2026-03-16 01:07:35', '2026-03-16 01:07:35'),
(18, 20, 6, '2026-03-16 01:08:19', '2026-03-16 01:08:19'),
(19, 20, 7, '2026-03-16 01:08:23', '2026-03-16 01:08:23'),
(20, 20, 8, '2026-03-16 01:08:27', '2026-03-16 01:08:27'),
(21, 20, 9, '2026-03-16 01:08:35', '2026-03-16 01:08:35'),
(22, 20, 10, '2026-03-16 01:08:43', '2026-03-16 01:08:43'),
(23, 20, 11, '2026-03-16 01:08:53', '2026-03-16 01:08:53'),
(24, 20, 12, '2026-03-16 01:08:58', '2026-03-16 01:08:58'),
(25, 20, 13, '2026-03-16 01:09:01', '2026-03-16 01:09:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mod_etiquetas`
--

CREATE TABLE `mod_etiquetas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mod_etiquetas`
--

INSERT INTO `mod_etiquetas` (`id`, `nombre`, `creado`, `actualizado`) VALUES
(1, 'moto', '2026-03-03 23:09:40', '2026-03-03 23:09:40'),
(2, 'biic', '2026-03-03 23:09:40', '2026-03-03 23:09:40'),
(3, 'obtencion', '2026-03-03 23:09:40', '2026-03-03 23:09:40'),
(4, 'reca', '2026-03-03 23:11:02', '2026-03-03 23:11:02'),
(5, 'aii', '2026-03-03 23:11:02', '2026-03-03 23:11:02'),
(6, 'recategorizacion', '2026-03-03 23:11:02', '2026-03-03 23:11:02'),
(7, 'AIIB', '2026-03-12 16:29:36', '2026-03-12 16:29:36'),
(8, 'A1', '2026-03-12 23:08:17', '2026-03-12 23:08:17'),
(9, 'revalidacion', '2026-03-12 23:15:34', '2026-03-12 23:15:34'),
(10, 'AIIIA', '2026-03-12 23:26:08', '2026-03-12 23:26:08'),
(11, 'AIIIB', '2026-03-12 23:28:58', '2026-03-12 23:28:58'),
(12, 'AIIIC', '2026-03-12 23:35:03', '2026-03-12 23:35:03'),
(13, 'AIIA', '2026-03-12 23:36:56', '2026-03-12 23:36:56'),
(14, 'actitud', '2026-03-12 23:39:01', '2026-03-12 23:39:01'),
(15, 'taller', '2026-03-12 23:39:01', '2026-03-12 23:39:01'),
(16, 'especial', '2026-03-12 23:49:45', '2026-03-12 23:49:45'),
(17, 'AIV', '2026-03-12 23:49:45', '2026-03-12 23:49:45'),
(18, 'PELIGROSOS', '2026-03-12 23:49:45', '2026-03-12 23:49:45'),
(19, 'carga', '2026-03-12 23:53:33', '2026-03-12 23:53:33'),
(20, 'mercancias', '2026-03-12 23:53:33', '2026-03-12 23:53:33'),
(21, 'actualizacion', '2026-03-12 23:53:33', '2026-03-12 23:53:33'),
(22, 'pasajeros', '2026-03-13 00:03:52', '2026-03-13 00:03:52'),
(23, 'personas', '2026-03-13 00:03:52', '2026-03-13 00:03:52'),
(24, 'producto', '2026-03-13 10:48:36', '2026-03-13 10:48:36'),
(25, 'informacion', '2026-03-13 10:48:36', '2026-03-13 10:48:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mod_precios`
--

CREATE TABLE `mod_precios` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `servicio_id` int(10) UNSIGNED NOT NULL,
  `rol` enum('A','B','C','D','E') NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 0,
  `nota` varchar(200) DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mod_precios`
--

INSERT INTO `mod_precios` (`id`, `empresa_id`, `servicio_id`, `rol`, `precio`, `activo`, `nota`, `es_principal`, `creado`, `actualizado`) VALUES
(1, 19, 1, 'A', 500.00, 1, 'Precio base', 1, '2026-03-03 23:11:31', '2026-03-12 19:00:44'),
(2, 19, 1, 'B', 400.00, 1, 'Descuento Movil Bus', 0, '2026-03-03 23:11:31', '2026-03-12 19:00:40'),
(3, 19, 1, 'C', 600.00, 1, 'Precio junio', 0, '2026-03-03 23:11:31', '2026-03-12 19:00:44'),
(4, 19, 1, 'D', 0.00, 0, NULL, 0, '2026-03-03 23:11:31', '2026-03-03 23:11:31'),
(5, 19, 1, 'E', 0.00, 0, NULL, 0, '2026-03-03 23:11:31', '2026-03-03 23:11:31'),
(31, 19, 2, 'A', 1200.00, 1, 'Precio base', 1, '2026-03-03 23:12:35', '2026-03-03 23:12:52'),
(32, 19, 2, 'B', 1000.00, 1, 'Descuento Emtrafesa', 0, '2026-03-03 23:12:35', '2026-03-03 23:13:06'),
(33, 19, 2, 'C', 0.00, 0, NULL, 0, '2026-03-03 23:12:35', '2026-03-03 23:12:35'),
(34, 19, 2, 'D', 0.00, 0, NULL, 0, '2026-03-03 23:12:35', '2026-03-03 23:12:35'),
(35, 19, 2, 'E', 0.00, 0, NULL, 0, '2026-03-03 23:12:35', '2026-03-03 23:12:35'),
(86, 27, 1, 'A', 1.00, 1, NULL, 1, '2026-03-12 18:14:59', '2026-03-12 18:14:59'),
(87, 27, 1, 'B', 0.00, 0, NULL, 0, '2026-03-12 18:14:59', '2026-03-12 18:14:59'),
(88, 27, 1, 'C', 0.00, 0, NULL, 0, '2026-03-12 18:14:59', '2026-03-12 18:14:59'),
(89, 27, 1, 'D', 0.00, 0, NULL, 0, '2026-03-12 18:14:59', '2026-03-12 18:14:59'),
(90, 27, 1, 'E', 0.00, 0, NULL, 0, '2026-03-12 18:14:59', '2026-03-12 18:14:59'),
(91, 27, 2, 'A', 1.00, 1, NULL, 1, '2026-03-12 18:15:02', '2026-03-12 18:15:02'),
(92, 27, 2, 'B', 0.00, 0, NULL, 0, '2026-03-12 18:15:02', '2026-03-12 18:15:02'),
(93, 27, 2, 'C', 0.00, 0, NULL, 0, '2026-03-12 18:15:02', '2026-03-12 18:15:02'),
(94, 27, 2, 'D', 0.00, 0, NULL, 0, '2026-03-12 18:15:02', '2026-03-12 18:15:02'),
(95, 27, 2, 'E', 0.00, 0, NULL, 0, '2026-03-12 18:15:02', '2026-03-12 18:15:02'),
(101, 27, 3, 'A', 1.00, 1, NULL, 1, '2026-03-12 18:15:07', '2026-03-12 18:15:07'),
(102, 27, 3, 'B', 0.00, 0, NULL, 0, '2026-03-12 18:15:07', '2026-03-12 18:15:07'),
(103, 27, 3, 'C', 0.00, 0, NULL, 0, '2026-03-12 18:15:07', '2026-03-12 18:15:07'),
(104, 27, 3, 'D', 0.00, 0, NULL, 0, '2026-03-12 18:15:07', '2026-03-12 18:15:07'),
(105, 27, 3, 'E', 0.00, 0, NULL, 0, '2026-03-12 18:15:07', '2026-03-12 18:15:07'),
(131, 19, 3, 'A', 1100.00, 1, NULL, 1, '2026-03-12 18:19:51', '2026-03-12 18:23:35'),
(132, 19, 3, 'B', 1000.00, 1, NULL, 0, '2026-03-12 18:19:51', '2026-03-12 18:23:43'),
(133, 19, 3, 'C', 0.00, 0, NULL, 0, '2026-03-12 18:19:51', '2026-03-12 18:19:51'),
(134, 19, 3, 'D', 0.00, 0, NULL, 0, '2026-03-12 18:19:51', '2026-03-12 18:19:51'),
(135, 19, 3, 'E', 0.00, 0, NULL, 0, '2026-03-12 18:19:51', '2026-03-12 18:19:51'),
(616, 26, 11, 'A', 1.00, 1, NULL, 1, '2026-03-13 00:05:22', '2026-03-13 00:05:22'),
(617, 26, 11, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:05:22', '2026-03-13 00:05:22'),
(618, 26, 11, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:05:22', '2026-03-13 00:05:22'),
(619, 26, 11, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:05:22', '2026-03-13 00:05:22'),
(620, 26, 11, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:05:22', '2026-03-13 00:05:22'),
(621, 26, 1, 'A', 1.00, 1, NULL, 1, '2026-03-13 00:05:23', '2026-03-13 00:05:23'),
(622, 26, 1, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:05:23', '2026-03-13 00:05:23'),
(623, 26, 1, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:05:23', '2026-03-13 00:05:23'),
(624, 26, 1, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:05:23', '2026-03-13 00:05:23'),
(625, 26, 1, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:05:23', '2026-03-13 00:05:23'),
(626, 26, 4, 'A', 1.00, 1, NULL, 1, '2026-03-13 00:05:25', '2026-03-13 00:05:25'),
(627, 26, 4, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:05:25', '2026-03-13 00:05:25'),
(628, 26, 4, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:05:25', '2026-03-13 00:05:25'),
(629, 26, 4, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:05:25', '2026-03-13 00:05:25'),
(630, 26, 4, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:05:25', '2026-03-13 00:05:25'),
(636, 26, 10, 'A', 1.00, 1, NULL, 1, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(637, 26, 10, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(638, 26, 10, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(639, 26, 10, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(640, 26, 10, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(641, 26, 12, 'A', 1.00, 1, NULL, 1, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(642, 26, 12, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(643, 26, 12, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(644, 26, 12, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(645, 26, 12, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:05:26', '2026-03-13 00:05:26'),
(651, 19, 11, 'A', 150.00, 1, NULL, 1, '2026-03-13 00:07:46', '2026-03-13 00:10:57'),
(652, 19, 11, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:07:46', '2026-03-13 00:07:46'),
(653, 19, 11, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:07:46', '2026-03-13 00:07:46'),
(654, 19, 11, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:07:46', '2026-03-13 00:07:46'),
(655, 19, 11, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:07:46', '2026-03-13 00:07:46'),
(656, 19, 12, 'A', 150.00, 1, NULL, 1, '2026-03-13 00:07:47', '2026-03-13 00:11:03'),
(657, 19, 12, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:07:47', '2026-03-13 00:07:47'),
(658, 19, 12, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:07:47', '2026-03-13 00:07:47'),
(659, 19, 12, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:07:47', '2026-03-13 00:07:47'),
(660, 19, 12, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:07:47', '2026-03-13 00:07:47'),
(661, 19, 10, 'A', 120.00, 1, NULL, 1, '2026-03-13 00:07:48', '2026-03-13 00:11:10'),
(662, 19, 10, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:07:48', '2026-03-13 00:07:48'),
(663, 19, 10, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:07:48', '2026-03-13 00:07:48'),
(664, 19, 10, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:07:48', '2026-03-13 00:07:48'),
(665, 19, 10, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:07:48', '2026-03-13 00:07:48'),
(671, 19, 4, 'A', 600.00, 1, NULL, 1, '2026-03-13 00:07:49', '2026-03-13 00:11:19'),
(672, 19, 4, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:07:49', '2026-03-13 00:07:49'),
(673, 19, 4, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:07:49', '2026-03-13 00:07:49'),
(674, 19, 4, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:07:49', '2026-03-13 00:07:49'),
(675, 19, 4, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:07:49', '2026-03-13 00:07:49'),
(756, 19, 6, 'A', 1000.00, 1, NULL, 1, '2026-03-13 00:11:23', '2026-03-13 00:11:34'),
(757, 19, 6, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:11:23', '2026-03-13 00:11:23'),
(758, 19, 6, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:11:23', '2026-03-13 00:11:23'),
(759, 19, 6, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:11:23', '2026-03-13 00:11:23'),
(760, 19, 6, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:11:23', '2026-03-13 00:11:23'),
(766, 19, 7, 'A', 1500.00, 1, NULL, 1, '2026-03-13 00:11:26', '2026-03-13 00:12:28'),
(767, 19, 7, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:11:26', '2026-03-13 00:11:26'),
(768, 19, 7, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:11:26', '2026-03-13 00:11:26'),
(769, 19, 7, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:11:26', '2026-03-13 00:11:26'),
(770, 19, 7, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:11:26', '2026-03-13 00:11:26'),
(791, 19, 8, 'A', 1000.00, 1, NULL, 1, '2026-03-13 00:11:36', '2026-03-13 00:11:42'),
(792, 19, 8, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:11:36', '2026-03-13 00:11:36'),
(793, 19, 8, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:11:36', '2026-03-13 00:11:36'),
(794, 19, 8, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:11:36', '2026-03-13 00:11:36'),
(795, 19, 8, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:11:36', '2026-03-13 00:11:36'),
(801, 19, 5, 'A', 300.00, 1, NULL, 1, '2026-03-13 00:11:45', '2026-03-13 00:11:54'),
(802, 19, 5, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:11:45', '2026-03-13 00:11:45'),
(803, 19, 5, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:11:45', '2026-03-13 00:11:45'),
(804, 19, 5, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:11:45', '2026-03-13 00:11:45'),
(805, 19, 5, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:11:45', '2026-03-13 00:11:45'),
(811, 19, 9, 'A', 350.00, 1, NULL, 1, '2026-03-13 00:11:55', '2026-03-13 00:12:00'),
(812, 19, 9, 'B', 0.00, 0, NULL, 0, '2026-03-13 00:11:55', '2026-03-13 00:11:55'),
(813, 19, 9, 'C', 0.00, 0, NULL, 0, '2026-03-13 00:11:55', '2026-03-13 00:11:55'),
(814, 19, 9, 'D', 0.00, 0, NULL, 0, '2026-03-13 00:11:55', '2026-03-13 00:11:55'),
(815, 19, 9, 'E', 0.00, 0, NULL, 0, '2026-03-13 00:11:55', '2026-03-13 00:11:55'),
(831, 19, 13, 'A', 10.00, 1, 'precio standar', 1, '2026-03-13 10:49:59', '2026-03-13 10:50:20'),
(832, 19, 13, 'B', 0.00, 0, NULL, 0, '2026-03-13 10:49:59', '2026-03-13 10:49:59'),
(833, 19, 13, 'C', 0.00, 0, NULL, 0, '2026-03-13 10:49:59', '2026-03-13 10:49:59'),
(834, 19, 13, 'D', 0.00, 0, NULL, 0, '2026-03-13 10:49:59', '2026-03-13 10:49:59'),
(835, 19, 13, 'E', 0.00, 0, NULL, 0, '2026-03-13 10:49:59', '2026-03-13 10:49:59'),
(841, 20, 13, 'A', 10.00, 1, NULL, 1, '2026-03-16 01:06:53', '2026-03-16 09:14:41'),
(842, 20, 13, 'B', 0.00, 0, NULL, 0, '2026-03-16 01:06:53', '2026-03-16 01:06:53'),
(843, 20, 13, 'C', 0.00, 0, NULL, 0, '2026-03-16 01:06:53', '2026-03-16 01:06:53'),
(844, 20, 13, 'D', 0.00, 0, NULL, 0, '2026-03-16 01:06:53', '2026-03-16 01:06:53'),
(845, 20, 13, 'E', 0.00, 0, NULL, 0, '2026-03-16 01:06:53', '2026-03-16 01:06:53'),
(846, 20, 11, 'A', 80.00, 1, 'precio principal', 1, '2026-03-16 01:06:54', '2026-03-16 12:04:36'),
(847, 20, 11, 'B', 70.00, 1, 'con descuento', 0, '2026-03-16 01:06:54', '2026-03-16 12:05:00'),
(848, 20, 11, 'C', 60.00, 1, 'con descuento', 0, '2026-03-16 01:06:54', '2026-03-16 12:05:27'),
(849, 20, 11, 'D', 50.00, 1, 'precio para promotores', 0, '2026-03-16 01:06:54', '2026-03-16 12:06:07'),
(850, 20, 11, 'E', 40.00, 1, 'precio para promotores con autorización de Gerencia', 0, '2026-03-16 01:06:54', '2026-03-16 12:06:38'),
(851, 20, 12, 'A', 80.00, 1, 'precio principal', 1, '2026-03-16 01:06:56', '2026-03-16 12:07:07'),
(852, 20, 12, 'B', 70.00, 1, 'con descuento', 0, '2026-03-16 01:06:56', '2026-03-16 12:08:14'),
(853, 20, 12, 'C', 60.00, 1, 'con descuento', 0, '2026-03-16 01:06:56', '2026-03-16 12:08:15'),
(854, 20, 12, 'D', 50.00, 1, 'precio para promotores', 0, '2026-03-16 01:06:56', '2026-03-16 12:08:31'),
(855, 20, 12, 'E', 40.00, 1, 'precio para promotores con autorización de Gerencia', 0, '2026-03-16 01:06:56', '2026-03-16 12:08:56'),
(856, 20, 10, 'A', 200.00, 1, 'precio principal', 1, '2026-03-16 01:06:57', '2026-03-16 12:09:19'),
(857, 20, 10, 'B', 150.00, 1, 'con descuento', 0, '2026-03-16 01:06:57', '2026-03-16 12:09:31'),
(858, 20, 10, 'C', 100.00, 1, 'precio para promotores', 0, '2026-03-16 01:06:57', '2026-03-16 12:09:50'),
(859, 20, 10, 'D', 0.00, 0, NULL, 0, '2026-03-16 01:06:57', '2026-03-16 01:06:57'),
(860, 20, 10, 'E', 0.00, 0, NULL, 0, '2026-03-16 01:06:57', '2026-03-16 01:06:57'),
(861, 20, 1, 'A', 200.00, 1, 'precio principal', 1, '2026-03-16 01:06:58', '2026-03-16 12:10:12'),
(862, 20, 1, 'B', 150.00, 1, 'con descuento y precio para promotores', 0, '2026-03-16 01:06:58', '2026-03-16 12:10:30'),
(863, 20, 1, 'C', 0.00, 0, NULL, 0, '2026-03-16 01:06:58', '2026-03-16 01:06:58'),
(864, 20, 1, 'D', 0.00, 0, NULL, 0, '2026-03-16 01:06:58', '2026-03-16 01:06:58'),
(865, 20, 1, 'E', 0.00, 0, NULL, 0, '2026-03-16 01:06:58', '2026-03-16 01:06:58'),
(1056, 20, 4, 'A', 850.00, 1, 'paquete completo', 1, '2026-03-16 12:10:36', '2026-03-16 12:10:46'),
(1057, 20, 4, 'B', 800.00, 1, 'con descuento, autorización Gerencia', 0, '2026-03-16 12:10:36', '2026-03-16 12:11:09'),
(1058, 20, 4, 'C', 0.00, 0, NULL, 0, '2026-03-16 12:10:36', '2026-03-16 12:10:36'),
(1059, 20, 4, 'D', 0.00, 0, NULL, 0, '2026-03-16 12:10:36', '2026-03-16 12:10:36'),
(1060, 20, 4, 'E', 0.00, 0, NULL, 0, '2026-03-16 12:10:36', '2026-03-16 12:10:36'),
(1076, 20, 2, 'A', 600.00, 1, 'precio principal', 1, '2026-03-16 12:12:18', '2026-03-16 12:12:42'),
(1077, 20, 2, 'B', 500.00, 1, 'con descuento y para promotores', 0, '2026-03-16 12:12:18', '2026-03-16 12:13:03'),
(1078, 20, 2, 'C', 450.00, 1, 'promotores con autorización de Gerencia', 0, '2026-03-16 12:12:18', '2026-03-16 12:13:36'),
(1079, 20, 2, 'D', 0.00, 0, NULL, 0, '2026-03-16 12:12:18', '2026-03-16 12:12:18'),
(1080, 20, 2, 'E', 0.00, 0, NULL, 0, '2026-03-16 12:12:18', '2026-03-16 12:12:18'),
(1106, 20, 3, 'A', 600.00, 1, 'precio principal', 1, '2026-03-16 12:17:30', '2026-03-16 12:17:41'),
(1107, 20, 3, 'B', 500.00, 1, 'con descuento y precio promotores', 0, '2026-03-16 12:17:30', '2026-03-16 12:17:57'),
(1108, 20, 3, 'C', 450.00, 1, 'para promotores con autorización de Gerencia', 0, '2026-03-16 12:17:30', '2026-03-16 12:18:21'),
(1109, 20, 3, 'D', 0.00, 0, NULL, 0, '2026-03-16 12:17:30', '2026-03-16 12:17:30'),
(1110, 20, 3, 'E', 0.00, 0, NULL, 0, '2026-03-16 12:17:30', '2026-03-16 12:17:30'),
(1136, 20, 6, 'A', 600.00, 1, 'precio principal', 1, '2026-03-16 12:18:27', '2026-03-16 12:18:42'),
(1137, 20, 6, 'B', 500.00, 1, 'con descuento y para promotores', 0, '2026-03-16 12:18:27', '2026-03-16 12:19:03'),
(1138, 20, 6, 'C', 450.00, 1, 'para promotores con autorización de Gerencia', 0, '2026-03-16 12:18:27', '2026-03-16 12:19:47'),
(1139, 20, 6, 'D', 0.00, 0, NULL, 0, '2026-03-16 12:18:27', '2026-03-16 12:18:27'),
(1140, 20, 6, 'E', 0.00, 0, NULL, 0, '2026-03-16 12:18:27', '2026-03-16 12:18:27'),
(1166, 20, 7, 'A', 600.00, 1, 'precio principal', 1, '2026-03-16 12:19:51', '2026-03-16 12:20:07'),
(1167, 20, 7, 'B', 500.00, 1, 'con descuento y precio para promotores', 0, '2026-03-16 12:19:51', '2026-03-16 12:20:50'),
(1168, 20, 7, 'C', 450.00, 1, 'para promotores con autorización de Gerencia', 0, '2026-03-16 12:19:51', '2026-03-16 12:21:13'),
(1169, 20, 7, 'D', 0.00, 0, NULL, 0, '2026-03-16 12:19:51', '2026-03-16 12:19:51'),
(1170, 20, 7, 'E', 0.00, 0, NULL, 0, '2026-03-16 12:19:51', '2026-03-16 12:19:51'),
(1201, 20, 8, 'A', 600.00, 1, 'precio principal, 55 horas', 1, '2026-03-16 12:21:25', '2026-03-16 12:21:52'),
(1202, 20, 8, 'B', 500.00, 1, 'para promotores, 55 horas', 0, '2026-03-16 12:21:25', '2026-03-16 12:23:31'),
(1203, 20, 8, 'C', 1100.00, 1, 'precio principal, 100 horas', 0, '2026-03-16 12:21:25', '2026-03-16 12:23:30'),
(1204, 20, 8, 'D', 1000.00, 1, 'con descuento y para promotores, 100 horas', 0, '2026-03-16 12:21:25', '2026-03-16 12:23:54'),
(1205, 20, 8, 'E', 800.00, 1, 'para promotores, autorización Gerencia (100 horas)', 0, '2026-03-16 12:21:25', '2026-03-16 12:24:24'),
(1251, 20, 9, 'A', 300.00, 1, 'precio principal', 1, '2026-03-16 12:24:29', '2026-03-16 12:24:49'),
(1252, 20, 9, 'B', 250.00, 1, 'con descuento', 0, '2026-03-16 12:24:29', '2026-03-16 12:25:07'),
(1253, 20, 9, 'C', 200.00, 1, 'precio para promotores', 0, '2026-03-16 12:24:29', '2026-03-16 12:28:38'),
(1254, 20, 9, 'D', 150.00, 1, 'precio promotores con autorización gerencia', 0, '2026-03-16 12:24:29', '2026-03-16 12:29:21'),
(1255, 20, 9, 'E', 0.00, 0, NULL, 0, '2026-03-16 12:24:29', '2026-03-16 12:24:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mod_servicios`
--

CREATE TABLE `mod_servicios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `imagen_path` varchar(255) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mod_servicios`
--

INSERT INTO `mod_servicios` (`id`, `nombre`, `descripcion`, `activo`, `imagen_path`, `creado`, `actualizado`) VALUES
(1, 'MOTO BIIC', 'Este es el curso de MOTO BIIC para conductores nuevos que buscan obtener su brevete.', 1, 'almacen/img_servicios/20260313075552-moto-biic-srv000001.jpg', '2026-03-03 23:09:40', '2026-03-13 07:55:52'),
(2, 'RECA AIIA', 'Permite transporte de pasajeros en automóvil', 1, 'almacen/img_servicios/20260312233216-reca-aiia-srv000002.jpg', '2026-03-03 23:11:02', '2026-03-12 23:32:16'),
(3, 'RECA AIIB', 'licencia AIIB peru microbus minibus transporte de pasajeros carga N2', 1, 'almacen/img_servicios/20260312-reca-aiib-srv000003.jpg', '2026-03-12 16:29:36', '2026-03-12 16:29:36'),
(4, 'OBTENCIÓN A1', 'Trámite para sacar tu primera licencia de conducir para auto particular.', 1, 'almacen/img_servicios/20260312230817-obtencion-a1-srv000004.jpg', '2026-03-12 23:08:17', '2026-03-12 23:33:29'),
(5, 'REVALIDACIÓN', 'Renovación de licencia vencida o por vencer para seguir conduciendo legalmente.', 1, 'almacen/img_servicios/20260312231534-revalidacion-srv000005.jpg', '2026-03-12 23:15:34', '2026-03-12 23:33:15'),
(6, 'RECA AIIIA', 'Cambio de categoría para conducir buses y ómnibus pesados de pasajeros.', 1, 'almacen/img_servicios/20260312232608-reca-aiiia-srv000006.webp', '2026-03-12 23:26:08', '2026-03-12 23:26:08'),
(7, 'RECA AIIIB', 'Cambio de categoría para conducir camiones y unidades pesadas de carga.', 1, 'almacen/img_servicios/20260312232858-reca-aiiib-srv000007.png', '2026-03-12 23:28:58', '2026-03-12 23:28:58'),
(8, 'RECA AIIIC', 'Cambio de categoría para conducir tanto transporte pesado de pasajeros como de carga.', 1, 'almacen/img_servicios/20260312233503-reca-aiiic-srv000008.jpg', '2026-03-12 23:35:03', '2026-03-12 23:35:03'),
(9, 'Taller Cambiemos de Actitud', 'Taller obligatorio para regularizar la situación de conductores sancionados.', 1, 'almacen/img_servicios/20260312233901-taller-cambiemos-de-actitud-srv000009.jpg', '2026-03-12 23:39:01', '2026-03-12 23:39:01'),
(10, 'Licencia especial AIV', 'Autorización complementaria para conducir vehículos que transportan materiales o residuos peligrosos.', 1, 'almacen/img_servicios/20260312234945-licencia-especial-aiv-srv000010.webp', '2026-03-12 23:49:45', '2026-03-12 23:49:45'),
(11, 'Curso de actualización - Carga', 'Curso obligatorio para actualizar conocimientos de normativa de transporte y tránsito.', 1, 'almacen/img_servicios/20260312235333-curso-de-actualizacion-carga-srv000011.webp', '2026-03-12 23:53:33', '2026-03-12 23:53:33'),
(12, 'Curso de actualización - Pasajeros', 'Curso obligatorio para actualizar conocimientos de normativa de transporte y tránsito.', 1, 'almacen/img_servicios/20260313000352-curso-de-actualizacion-pasajeros-srv000012.jpg', '2026-03-13 00:03:52', '2026-03-13 00:03:52'),
(13, 'BALOTARIO', 'Este es un balotario', 1, 'almacen/img_servicios/20260313104836-balotario-srv000013.jpg', '2026-03-13 10:48:36', '2026-03-13 10:48:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mod_servicio_etiqueta`
--

CREATE TABLE `mod_servicio_etiqueta` (
  `servicio_id` int(10) UNSIGNED NOT NULL,
  `etiqueta_id` int(10) UNSIGNED NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mod_servicio_etiqueta`
--

INSERT INTO `mod_servicio_etiqueta` (`servicio_id`, `etiqueta_id`, `creado`, `actualizado`) VALUES
(1, 1, '2026-03-13 07:55:52', '2026-03-13 07:55:52'),
(1, 2, '2026-03-13 07:55:52', '2026-03-13 07:55:52'),
(1, 3, '2026-03-13 07:55:52', '2026-03-13 07:55:52'),
(2, 4, '2026-03-12 23:36:56', '2026-03-12 23:36:56'),
(2, 13, '2026-03-12 23:36:56', '2026-03-12 23:36:56'),
(3, 4, '2026-03-12 16:29:36', '2026-03-12 16:29:36'),
(3, 7, '2026-03-12 16:29:36', '2026-03-12 16:29:36'),
(4, 3, '2026-03-12 23:33:29', '2026-03-12 23:33:29'),
(4, 8, '2026-03-12 23:33:29', '2026-03-12 23:33:29'),
(5, 9, '2026-03-12 23:33:15', '2026-03-12 23:33:15'),
(6, 4, '2026-03-12 23:26:08', '2026-03-12 23:26:08'),
(6, 10, '2026-03-12 23:26:08', '2026-03-12 23:26:08'),
(7, 4, '2026-03-12 23:28:58', '2026-03-12 23:28:58'),
(7, 11, '2026-03-12 23:28:58', '2026-03-12 23:28:58'),
(8, 4, '2026-03-12 23:35:03', '2026-03-12 23:35:03'),
(8, 12, '2026-03-12 23:35:03', '2026-03-12 23:35:03'),
(9, 14, '2026-03-12 23:39:01', '2026-03-12 23:39:01'),
(9, 15, '2026-03-12 23:39:01', '2026-03-12 23:39:01'),
(10, 16, '2026-03-12 23:49:45', '2026-03-12 23:49:45'),
(10, 17, '2026-03-12 23:49:45', '2026-03-12 23:49:45'),
(10, 18, '2026-03-12 23:49:45', '2026-03-12 23:49:45'),
(11, 19, '2026-03-12 23:53:33', '2026-03-12 23:53:33'),
(11, 20, '2026-03-12 23:53:33', '2026-03-12 23:53:33'),
(11, 21, '2026-03-12 23:53:33', '2026-03-12 23:53:33'),
(12, 21, '2026-03-13 00:03:52', '2026-03-13 00:03:52'),
(12, 22, '2026-03-13 00:03:52', '2026-03-13 00:03:52'),
(12, 23, '2026-03-13 00:03:52', '2026-03-13 00:03:52'),
(13, 24, '2026-03-13 10:48:36', '2026-03-13 10:48:36'),
(13, 25, '2026-03-13 10:48:36', '2026-03-13 10:48:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_alumnos`
--

CREATE TABLE `mtp_alumnos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `documento` varchar(15) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_archivos`
--

CREATE TABLE `mtp_archivos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `triada` varchar(50) NOT NULL,
  `entidad` varchar(50) DEFAULT NULL,
  `entidad_id` bigint(20) UNSIGNED DEFAULT NULL,
  `categoria` varchar(50) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `nombre_final` varchar(255) NOT NULL,
  `ext` varchar(10) NOT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `tamano_bytes` bigint(20) UNSIGNED NOT NULL,
  `ruta_relativa` varchar(300) NOT NULL,
  `checksum_sha256` char(64) DEFAULT NULL,
  `estado` enum('local','exportado','borrado','reemplazado') NOT NULL DEFAULT 'local',
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_archivos`
--

INSERT INTO `mtp_archivos` (`id`, `triada`, `entidad`, `entidad_id`, `categoria`, `nombre_original`, `nombre_final`, `ext`, `mime`, `tamano_bytes`, `ruta_relativa`, `checksum_sha256`, `estado`, `creado`, `actualizado`) VALUES
(1, 'empresas', 'empresa', 1, 'img_logos_empresas', 'logo_leoncorp_transparente_circular_notxt.png', 'logo-empresa-empresa-1-20260121T235150-57a9fe.png', 'png', 'image/png', 26403, 'almacen/2026/01/21/img_logos_empresas/logo-empresa-empresa-1-20260121T235150-57a9fe.png', 'c03ec86a84825f11f34f13d4b4168ad7f27b569006c3255ddb287124ac0b2fb1', 'local', '2026-01-21 23:51:50', '2026-01-21 23:51:50'),
(2, 'usuarios', 'usuario', 1, 'img_perfil', '1-5183d21a8552.jpg', 'perfil-usuario-usuario-1-20260121T235351-0a0502.jpg', 'jpg', 'image/jpeg', 137105, 'almacen/2026/01/21/img_perfil/perfil-usuario-usuario-1-20260121T235351-0a0502.jpg', '2f86463a29d680885b31ef2b61c452709cf357c3f2fa1780e34478cdf1a4400a', 'reemplazado', '2026-01-21 23:53:51', '2026-01-21 23:54:54'),
(3, 'usuarios', 'usuario', 1, 'img_perfil', '1-5183d21a8552 (1).jpg', 'perfil-usuario-usuario-1-20260121T235454-c7e25f.jpg', 'jpg', 'image/jpeg', 37532, 'almacen/2026/01/21/img_perfil/perfil-usuario-usuario-1-20260121T235454-c7e25f.jpg', 'd514d5bcac3e9de4a166e215308ff1ecd40f5120ec9f42ffe50bf78b3a144725', 'reemplazado', '2026-01-21 23:54:54', '2026-01-21 23:56:08'),
(4, 'usuarios', 'usuario', 1, 'img_perfil', 'Captura de pantalla 2026-01-21 235540.png', 'perfil-usuario-usuario-1-20260121T235608-b9f756.png', 'png', 'image/png', 358452, 'almacen/2026/01/21/img_perfil/perfil-usuario-usuario-1-20260121T235608-b9f756.png', '50b8fda757c58330731731b705e298c6a3cc4eb1588e3bf4fed99eb21b282b96', 'local', '2026-01-21 23:56:08', '2026-01-21 23:56:08'),
(5, 'empresas', 'empresa', 2, 'img_logos_empresas', 'LOGO GLOBAL CAR.png', 'logo-empresa-empresa-2-20260122T010707-d5d470.png', 'png', 'image/png', 43100, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-2-20260122T010707-d5d470.png', '0b5ede69dbfc6d96a13174e6f526fe87f2cd72a57762e58ce92e4f5c6cac7dec', 'borrado', '2026-01-22 01:07:07', '2026-03-16 00:51:37'),
(6, 'empresas', 'empresa', 3, 'img_logos_empresas', 'LOGO GLOBAL CAR.png', 'logo-empresa-empresa-3-20260122T010805-81a59c.png', 'png', 'image/png', 43100, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-3-20260122T010805-81a59c.png', '0b5ede69dbfc6d96a13174e6f526fe87f2cd72a57762e58ce92e4f5c6cac7dec', 'borrado', '2026-01-22 01:08:05', '2026-03-16 00:51:35'),
(7, 'usuarios', 'usuario', 2, 'img_perfil', 'alma foto chiclayo.png', 'perfil-usuario-usuario-2-20260122T011610-1fbc5d.png', 'png', 'image/png', 24265, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-2-20260122T011610-1fbc5d.png', '61ee1b859ef70378e4ae6e862c7845c6a5ace507700a052e2f2de25844f37aba', 'reemplazado', '2026-01-22 01:16:10', '2026-01-22 01:22:46'),
(8, 'usuarios', 'usuario', 3, 'img_perfil', 'milca foto piura.png', 'perfil-usuario-usuario-3-20260122T011656-941c57.png', 'png', 'image/png', 28644, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-3-20260122T011656-941c57.png', '5ad94a87e1a9e88f833ce1299f830a3108774c8177ed7ee82fddd98dafa73bcb', 'reemplazado', '2026-01-22 01:16:56', '2026-01-22 01:19:04'),
(9, 'usuarios', 'usuario', 3, 'img_perfil', 'Captura de pantalla 2026-01-22 011842.png', 'perfil-usuario-usuario-3-20260122T011904-364f10.png', 'png', 'image/png', 64565, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-3-20260122T011904-364f10.png', '208082ff302f69fc9d3c7c8eb028fdc837ab77bb5c6ca9bb7fcc23f2b7bfe054', 'reemplazado', '2026-01-22 01:19:04', '2026-01-22 01:19:54'),
(10, 'usuarios', 'usuario', 3, 'img_perfil', 'Captura de pantalla 2026-01-22 011929.png', 'perfil-usuario-usuario-3-20260122T011954-6d77bb.png', 'png', 'image/png', 72559, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-3-20260122T011954-6d77bb.png', '4ddeddda16b466fce30d37e292dfbba9ab28482051eb0a201bc6db04dc4a4c72', 'reemplazado', '2026-01-22 01:19:54', '2026-01-22 01:21:03'),
(11, 'usuarios', 'usuario', 3, 'img_perfil', 'Captura de pantalla 2026-01-22 012042.png', 'perfil-usuario-usuario-3-20260122T012103-5e08ef.png', 'png', 'image/png', 121855, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-3-20260122T012103-5e08ef.png', 'c18e2ba5f9c8e0125467611b26fff0c104573dbc64f9e35c859c9ae22ba4a6c5', 'reemplazado', '2026-01-22 01:21:03', '2026-01-22 01:26:53'),
(12, 'usuarios', 'usuario', 2, 'img_perfil', 'Captura de pantalla 2026-01-22 012210.png', 'perfil-usuario-usuario-2-20260122T012245-e04dfe.png', 'png', 'image/png', 186254, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-2-20260122T012245-e04dfe.png', '7f6e53da32bdf14444fadad94a4eb5faa2414843638b92c25feebb933c8283f5', 'local', '2026-01-22 01:22:45', '2026-01-22 01:22:45'),
(13, 'usuarios', 'usuario', 3, 'img_perfil', 'Captura de pantalla 2026-01-22 012631.png', 'perfil-usuario-usuario-3-20260122T012653-9570f7.png', 'png', 'image/png', 266516, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-3-20260122T012653-9570f7.png', 'e30ecf8a72fdf8e3e34ddf64767804e744648f75f37f67e278f9a9e6f18b8e91', 'reemplazado', '2026-01-22 01:26:53', '2026-01-22 01:27:55'),
(14, 'usuarios', 'usuario', 3, 'img_perfil', 'Captura de pantalla 2026-01-22 012739.png', 'perfil-usuario-usuario-3-20260122T012755-2b0900.png', 'png', 'image/png', 203253, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-3-20260122T012755-2b0900.png', 'f43b7133804d94d2b837a306fd41bab83b8bf72343d406d5110b5766a7617a99', 'local', '2026-01-22 01:27:55', '2026-01-22 01:27:55'),
(15, 'certificados', 'plantilla', NULL, 'fondo_certificado', 'fondo-certificado-20251110T194228-68cdb0_mejorado2.png', 'fondo-certificado-20260122T102108-a09471.png', 'png', 'image/png', 123833, 'almacen/2026/01/22/fondo_certificado/fondo-certificado-20260122T102108-a09471.png', 'd57c6a09ddad83ded6b92b90d5fa28229a2c36baefd69f3e08a45178fed8f5f5', 'local', '2026-01-22 10:21:08', '2026-01-22 10:21:08'),
(16, 'certificados', 'plantilla', NULL, 'logo_certificado', 'LOGO GLOBAL CAR.png', 'logo-certificado-20260122T102108-682170.png', 'png', 'image/png', 43100, 'almacen/2026/01/22/logo_certificado/logo-certificado-20260122T102108-682170.png', '0b5ede69dbfc6d96a13174e6f526fe87f2cd72a57762e58ce92e4f5c6cac7dec', 'local', '2026-01-22 10:21:08', '2026-01-22 10:21:08'),
(17, 'certificados', 'plantilla', NULL, 'firma_representante', 'firma y sello alma chiclayo.png', 'firma-representante-20260122T102108-424e54.png', 'png', 'image/png', 107325, 'almacen/2026/01/22/firma_representante/firma-representante-20260122T102108-424e54.png', 'f54b3cce722993f71a402cd86a05a819767d87794eedc4861539a3dbd800ae19', 'reemplazado', '2026-01-22 10:21:08', '2026-01-27 09:48:29'),
(18, 'certificados', 'plantilla', 2, 'fondo_certificado', 'plantilla para certificados piura.png', 'fondo-certificado-plantilla-2-20260122T180627-e787b4.png', 'png', 'image/png', 115765, 'almacen/2026/01/22/fondo_certificado/fondo-certificado-plantilla-2-20260122T180627-e787b4.png', '853abd800f8ecf653fa524115468b79b416dc54fa1927be0783ef7a651b314a7', 'reemplazado', '2026-01-22 18:06:27', '2026-01-22 18:14:06'),
(19, 'certificados', 'plantilla', 2, 'firma_representante', 'fima y sello milca.png', 'firma-representante-plantilla-2-20260122T180627-584393.png', 'png', 'image/png', 258450, 'almacen/2026/01/22/firma_representante/firma-representante-plantilla-2-20260122T180627-584393.png', '7e7839cd8c9a2f560168ce36b0f99a3ace92e69ada39eac5566838b16dcb3475', 'local', '2026-01-22 18:06:27', '2026-01-22 18:06:27'),
(20, 'certificados', 'plantilla', 2, 'fondo_certificado', 'plantilla para certificados piura.png', 'fondo-certificado-plantilla-2-20260122T181406-933397.png', 'png', 'image/png', 115765, 'almacen/2026/01/22/fondo_certificado/fondo-certificado-plantilla-2-20260122T181406-933397.png', '853abd800f8ecf653fa524115468b79b416dc54fa1927be0783ef7a651b314a7', 'local', '2026-01-22 18:14:06', '2026-01-22 18:14:06'),
(21, 'certificados', 'plantilla', 3, 'fondo_certificado', 'plantilla para certificados.png', 'fondo-certificado-plantilla-3-20260122T182148-aec145.png', 'png', 'image/png', 117314, 'almacen/2026/01/22/fondo_certificado/fondo-certificado-plantilla-3-20260122T182148-aec145.png', 'b0c5578e3eebd3e45d4413c79a7c6355735534b238b5f9b61b7bb5fd36deb34c', 'local', '2026-01-22 18:21:48', '2026-01-22 18:21:48'),
(22, 'certificados', 'plantilla', 3, 'firma_representante', 'fima y sello milca.png', 'firma-representante-plantilla-3-20260122T182148-e77cf5.png', 'png', 'image/png', 258450, 'almacen/2026/01/22/firma_representante/firma-representante-plantilla-3-20260122T182148-e77cf5.png', '7e7839cd8c9a2f560168ce36b0f99a3ace92e69ada39eac5566838b16dcb3475', 'local', '2026-01-22 18:21:48', '2026-01-22 18:21:48'),
(23, 'empresas', 'empresa', 4, 'img_logos_empresas', 'LOGO GLOBAL CAR.png', 'logo-empresa-empresa-4-20260122T184028-e1afb6.png', 'png', 'image/png', 43100, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-4-20260122T184028-e1afb6.png', '0b5ede69dbfc6d96a13174e6f526fe87f2cd72a57762e58ce92e4f5c6cac7dec', 'borrado', '2026-01-22 18:40:28', '2026-03-16 00:51:32'),
(24, 'empresas', 'empresa', 5, 'img_logos_empresas', 'LOGO GLOBAL CAR.png', 'logo-empresa-empresa-5-20260122T184202-3d82ff.png', 'png', 'image/png', 43100, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-5-20260122T184202-3d82ff.png', '0b5ede69dbfc6d96a13174e6f526fe87f2cd72a57762e58ce92e4f5c6cac7dec', 'borrado', '2026-01-22 18:42:02', '2026-03-16 00:51:30'),
(25, 'empresas', 'empresa', 6, 'img_logos_empresas', 'LOGO GLOBAL CAR.png', 'logo-empresa-empresa-6-20260122T184329-0c3903.png', 'png', 'image/png', 43100, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-6-20260122T184329-0c3903.png', '0b5ede69dbfc6d96a13174e6f526fe87f2cd72a57762e58ce92e4f5c6cac7dec', 'borrado', '2026-01-22 18:43:29', '2026-03-16 00:51:27'),
(26, 'empresas', 'empresa', 7, 'img_logos_empresas', 'LOGO SELVA CAR.png', 'logo-empresa-empresa-7-20260122T184509-53ee42.png', 'png', 'image/png', 42616, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-7-20260122T184509-53ee42.png', 'bb73826616eaafdbb40594cd4639c0a28736c0606e9121981b99001794bb17f5', 'borrado', '2026-01-22 18:45:10', '2026-03-16 00:50:57'),
(27, 'empresas', 'empresa', 8, 'img_logos_empresas', 'LOGO SELVA CAR.png', 'logo-empresa-empresa-8-20260122T184610-e75c5e.png', 'png', 'image/png', 42616, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-8-20260122T184610-e75c5e.png', 'bb73826616eaafdbb40594cd4639c0a28736c0606e9121981b99001794bb17f5', 'borrado', '2026-01-22 18:46:10', '2026-03-16 00:50:52'),
(28, 'empresas', 'empresa', 9, 'img_logos_empresas', 'LOGO SELVA CAR.png', 'logo-empresa-empresa-9-20260122T184737-97c298.png', 'png', 'image/png', 42616, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-9-20260122T184737-97c298.png', 'bb73826616eaafdbb40594cd4639c0a28736c0606e9121981b99001794bb17f5', 'borrado', '2026-01-22 18:47:37', '2026-03-16 00:50:50'),
(29, 'empresas', 'empresa', 11, 'img_logos_empresas', 'LOGO SELVA CAR.png', 'logo-empresa-empresa-11-20260122T185055-8e0e8f.png', 'png', 'image/png', 42616, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-11-20260122T185055-8e0e8f.png', 'bb73826616eaafdbb40594cd4639c0a28736c0606e9121981b99001794bb17f5', 'borrado', '2026-01-22 18:50:55', '2026-03-16 00:50:46'),
(30, 'empresas', 'empresa', 12, 'img_logos_empresas', 'LOGO GLOBAL CAR.png', 'logo-empresa-empresa-12-20260122T185333-132e60.png', 'png', 'image/png', 43100, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-12-20260122T185333-132e60.png', '0b5ede69dbfc6d96a13174e6f526fe87f2cd72a57762e58ce92e4f5c6cac7dec', 'borrado', '2026-01-22 18:53:33', '2026-03-16 00:51:23'),
(31, 'empresas', 'empresa', 13, 'img_logos_empresas', 'logo open medic_Mesa de trabajo 1.png', 'logo-empresa-empresa-13-20260122T185748-9bde0a.png', 'png', 'image/png', 34748, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-13-20260122T185748-9bde0a.png', '674a9e9a2de3b2be270157381b94792d762245cc68bac14cdd06d5c7628e1eff', 'borrado', '2026-01-22 18:57:48', '2026-03-16 00:52:03'),
(32, 'empresas', 'empresa', 14, 'img_logos_empresas', 'logo open medic_Mesa de trabajo 1.png', 'logo-empresa-empresa-14-20260122T185917-5cf6b1.png', 'png', 'image/png', 34748, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-14-20260122T185917-5cf6b1.png', '674a9e9a2de3b2be270157381b94792d762245cc68bac14cdd06d5c7628e1eff', 'borrado', '2026-01-22 18:59:17', '2026-03-16 00:52:01'),
(33, 'empresas', 'empresa', 15, 'img_logos_empresas', 'logo open medic_Mesa de trabajo 1.png', 'logo-empresa-empresa-15-20260122T190956-3a462f.png', 'png', 'image/png', 34748, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-15-20260122T190956-3a462f.png', '674a9e9a2de3b2be270157381b94792d762245cc68bac14cdd06d5c7628e1eff', 'borrado', '2026-01-22 19:09:56', '2026-03-16 00:51:59'),
(34, 'empresas', 'empresa', 16, 'img_logos_empresas', 'LOGO GLOBAL MEDIC.png', 'logo-empresa-empresa-16-20260122T191129-9cfda6.png', 'png', 'image/png', 22436, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-16-20260122T191129-9cfda6.png', 'f41fc94b673e490c1570a7c9bc19444aa00d9f0da876bffc1aa148b6fdba1ca5', 'borrado', '2026-01-22 19:11:29', '2026-03-16 00:51:20'),
(35, 'empresas', 'empresa', 17, 'img_logos_empresas', 'circular guia mis rutas.png', 'logo-empresa-empresa-17-20260122T232400-e07c18.png', 'png', 'image/png', 245413, 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-17-20260122T232400-e07c18.png', 'd457fed1d4d12103e220bd56eb5b1e359936eb1b460e7ec56c026bd1a23dfa10', 'local', '2026-01-22 23:24:00', '2026-01-22 23:24:00'),
(36, 'usuarios', 'usuario', 4, 'img_perfil', '4139981.png', 'perfil-usuario-usuario-4-20260123T121428-1bb5e3.png', 'png', 'image/png', 33704, 'almacen/2026/01/23/img_perfil/perfil-usuario-usuario-4-20260123T121428-1bb5e3.png', '4ef7a12f9a482a466c1d461678a1eb1cc4c03181c32eb4817221bafd75403fd3', 'reemplazado', '2026-01-23 12:14:28', '2026-01-23 12:29:37'),
(37, 'usuarios', 'usuario', 4, 'img_perfil', 'FotoPerfil.jpg', 'perfil-usuario-usuario-4-20260123T122937-6a97e5.jpg', 'jpg', 'image/jpeg', 594872, 'almacen/2026/01/23/img_perfil/perfil-usuario-usuario-4-20260123T122937-6a97e5.jpg', '466a94bd3659698826024f4eb61ada9e573afb7c341b704fa98ff13e89a044e4', 'local', '2026-01-23 12:29:37', '2026-01-23 12:29:37'),
(38, 'certificados', 'plantilla', 1, 'firma_representante', 'firma alma final.png', 'firma-representante-plantilla-1-20260127T094829-f370a4.png', 'png', 'image/png', 214848, 'almacen/2026/01/27/firma_representante/firma-representante-plantilla-1-20260127T094829-f370a4.png', '1eab19462a4ee56b78bf3f055419c48c6954d894e7a96109c1030e5204b396b7', 'reemplazado', '2026-01-27 09:48:29', '2026-02-17 17:42:27'),
(39, 'usuarios', 'usuario', 5, 'img_perfil', 'LOGO LEONCORP.png', 'perfil-usuario-usuario-5-20260203T155038-6e4fec.png', 'png', 'image/png', 28969, 'almacen/2026/02/03/img_perfil/perfil-usuario-usuario-5-20260203T155038-6e4fec.png', 'a1c008404880632a53167329813f553563cea5681fbebf1e917b5d6355272d60', 'local', '2026-02-03 15:50:38', '2026-02-03 15:50:38'),
(40, 'usuarios', 'usuario', 6, 'img_perfil', 'milagros.png', 'perfil-usuario-usuario-6-20260203T161847-bf58b4.png', 'png', 'image/png', 229268, 'almacen/2026/02/03/img_perfil/perfil-usuario-usuario-6-20260203T161847-bf58b4.png', '13c6e7d990c8e75aabf3d5a16dd4bee63d779c5a86421b8b62a30ff763b0a800', 'local', '2026-02-03 16:18:47', '2026-02-03 16:18:47'),
(41, 'usuarios', 'usuario', 7, 'img_perfil', 'Captura de pantalla 2026-02-03 165031.png', 'perfil-usuario-usuario-7-20260203T165113-89e787.png', 'png', 'image/png', 225475, 'almacen/2026/02/03/img_perfil/perfil-usuario-usuario-7-20260203T165113-89e787.png', '6de4dcbdb9142264ac1c7ace3a9ac37e36758969ef738c7764ecb7ca196bf51c', 'local', '2026-02-03 16:51:13', '2026-02-03 16:51:13'),
(42, 'certificados', 'plantilla', 1, 'firma_representante', 'firma vicente transparente con sello.png', 'firma-representante-plantilla-1-20260217T174227-6e01ef.png', 'png', 'image/png', 137026, 'almacen/2026/02/17/firma_representante/firma-representante-plantilla-1-20260217T174227-6e01ef.png', '28aff196f50c235c2caafa57dcf98935ad6482b3b62fe1aa758adc7d2cd4e4f7', 'local', '2026-02-17 17:42:27', '2026-02-17 17:42:27'),
(43, 'certificados', 'plantilla', 4, 'firma_representante', 'FIRMA ALMA GC CHICLAYO TRANSPARENTE 2026.png', 'firma-representante-plantilla-4-20260217T175222-257068.png', 'png', 'image/png', 134507, 'almacen/2026/02/17/firma_representante/firma-representante-plantilla-4-20260217T175222-257068.png', '45317de49103c293aad7f08be01ef1cbfcc1cee6c4ae35c176d6bb8da4dcaa6e', 'local', '2026-02-17 17:52:22', '2026-02-17 17:52:22'),
(44, 'certificados', 'plantilla', 5, 'firma_representante', 'firma_transparente_manuel alvarez gerente.png', 'firma-representante-plantilla-5-20260227T103915-f355dc.png', 'png', 'image/png', 28219, 'almacen/2026/02/27/firma_representante/firma-representante-plantilla-5-20260227T103915-f355dc.png', '8f317ef39d86f0651be907ca873720430637e9fd7e97560fd0b2d9578646db81', 'local', '2026-02-27 10:39:15', '2026-02-27 10:39:15'),
(45, 'certificados', 'plantilla', 5, 'logo_certificado', 'LOGO GLOBAL CAR.png', 'logo-certificado-plantilla-5-20260227T104310-9ac317.png', 'png', 'image/png', 43100, 'almacen/2026/02/27/logo_certificado/logo-certificado-plantilla-5-20260227T104310-9ac317.png', '0b5ede69dbfc6d96a13174e6f526fe87f2cd72a57762e58ce92e4f5c6cac7dec', 'local', '2026-02-27 10:43:10', '2026-02-27 10:43:10'),
(46, 'empresas', 'empresa', 18, 'img_logos_empresas', 'LOGO GLOBAL CAR.png', 'logo-empresa-empresa-18-20260303T084638-e1eaa9.png', 'png', 'image/png', 43100, 'almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-18-20260303T084638-e1eaa9.png', '0b5ede69dbfc6d96a13174e6f526fe87f2cd72a57762e58ce92e4f5c6cac7dec', 'borrado', '2026-03-03 08:46:38', '2026-03-16 00:52:17'),
(47, 'empresas', 'empresa', 19, 'img_logos_empresas', 'logo2_01-01-26_agrandado.png', 'logo-empresa-empresa-19-20260303T225708-f14484.png', 'png', 'image/png', 197183, 'almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png', 'cab290a5682a4e812e56f1b78b73095b84620d2352deadf4028f83260973c7b3', 'local', '2026-03-03 22:57:08', '2026-03-03 22:57:08'),
(48, 'usuarios', 'usuario', 10, 'img_perfil', '20251109_2326_Logo sobre fondo blanco_remix_01k9p06s6xf3fvv49cy16h7vfp.png', 'perfil-usuario-usuario-10-20260303T230114-423cf9.png', 'png', 'image/png', 837253, 'almacen/2026/03/03/img_perfil/perfil-usuario-usuario-10-20260303T230114-423cf9.png', '661a033ff4313563e71024a90a2d78ac49b5e779dbe73f354d4df809dd746385', 'local', '2026-03-03 23:01:14', '2026-03-03 23:01:14'),
(49, 'usuarios', 'usuario', 11, 'img_perfil', 'driver.png', 'perfil-usuario-usuario-11-20260306T085348-f6d491.png', 'png', 'image/png', 23666, 'almacen/2026/03/06/img_perfil/perfil-usuario-usuario-11-20260306T085348-f6d491.png', '70650377b6acc71b06f6b5df48e06aac85e062b8f635d739143bb8566f4f0dc4', 'local', '2026-03-06 08:53:48', '2026-03-06 08:53:48'),
(50, 'usuarios', 'usuario', 12, 'img_perfil', '33333.png', 'perfil-usuario-usuario-12-20260306T190114-3206b4.png', 'png', 'image/png', 24565, 'almacen/2026/03/06/img_perfil/perfil-usuario-usuario-12-20260306T190114-3206b4.png', '6768805257cdf920ee4c3cc9aaceb0a0853b8a75a8a28db718748a9d9b6de9c3', 'reemplazado', '2026-03-06 19:01:14', '2026-03-06 19:03:02'),
(51, 'usuarios', 'usuario', 12, 'img_perfil', '44444.png', 'perfil-usuario-usuario-12-20260306T190302-478825.png', 'png', 'image/png', 29631, 'almacen/2026/03/06/img_perfil/perfil-usuario-usuario-12-20260306T190302-478825.png', '0dccada84dc4fbf2b297a37cfd94bb482cdc37828b4a09fdc1b63ca6be1a930c', 'local', '2026-03-06 19:03:02', '2026-03-06 19:03:02'),
(52, 'usuarios', 'usuario', 13, 'img_perfil', '22222.png', 'perfil-usuario-usuario-13-20260306T190728-338ecc.png', 'png', 'image/png', 27022, 'almacen/2026/03/06/img_perfil/perfil-usuario-usuario-13-20260306T190728-338ecc.png', '9c191e7137a3e9ca05106dbc32927a24d4d4257659fd13258709dcd9d4a99fed', 'local', '2026-03-06 19:07:28', '2026-03-06 19:07:28'),
(53, 'usuarios', 'usuario', 14, 'img_perfil', '0bec91b2-f721-4b56-97c3-3e8c4e0fafc7.jpg', 'perfil-usuario-usuario-14-20260306T190811-cbd1a6.jpg', 'jpg', 'image/jpeg', 213291, 'almacen/2026/03/06/img_perfil/perfil-usuario-usuario-14-20260306T190811-cbd1a6.jpg', '645cc08ce702a21919494dd71ed1cda8b09fa613966ba76047c92b7ea80492c0', 'local', '2026-03-06 19:08:11', '2026-03-06 19:08:11'),
(54, 'usuarios', 'usuario', 15, 'img_perfil', 'DELIA.png', 'perfil-usuario-usuario-15-20260307T163507-5a0549.png', 'png', 'image/png', 316630, 'almacen/2026/03/07/img_perfil/perfil-usuario-usuario-15-20260307T163507-5a0549.png', '3a7e66819f05280822fe8b61a8b2e16ec88d74cc9351f1f7bb29e12bcf655435', 'local', '2026-03-07 16:35:07', '2026-03-07 16:35:07'),
(55, 'web', 'menu', 1, 'logo_web', 'logo2_01-01-26_agrandado.png', 'logo-web-menu-1-20260308T141109-52cf83.png', 'png', 'image/png', 197183, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T141109-52cf83.png', 'cab290a5682a4e812e56f1b78b73095b84620d2352deadf4028f83260973c7b3', 'reemplazado', '2026-03-08 14:11:09', '2026-03-08 14:12:55'),
(56, 'web', 'menu', 1, 'logo_web', 'logo2_01-01-26_agrandado.png', 'logo-web-menu-1-20260308T141255-e04d20.png', 'png', 'image/png', 197183, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T141255-e04d20.png', 'cab290a5682a4e812e56f1b78b73095b84620d2352deadf4028f83260973c7b3', 'reemplazado', '2026-03-08 14:12:55', '2026-03-08 14:14:09'),
(57, 'web', 'menu', 1, 'logo_web', 'logo2_01-01-26_agrandado.png', 'logo-web-menu-1-20260308T141409-f6dd1d.png', 'png', 'image/png', 197183, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T141409-f6dd1d.png', 'cab290a5682a4e812e56f1b78b73095b84620d2352deadf4028f83260973c7b3', 'reemplazado', '2026-03-08 14:14:09', '2026-03-08 14:14:15'),
(58, 'web', 'menu', 1, 'logo_web', 'logo2_01-01-26_agrandado.png', 'logo-web-menu-1-20260308T141415-f3f338.png', 'png', 'image/png', 197183, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T141415-f3f338.png', 'cab290a5682a4e812e56f1b78b73095b84620d2352deadf4028f83260973c7b3', 'reemplazado', '2026-03-08 14:14:15', '2026-03-08 14:14:50'),
(59, 'web', 'menu', 1, 'logo_web', 'logo2_01-01-26_agrandado.png', 'logo-web-menu-1-20260308T141450-9a9a16.png', 'png', 'image/png', 197183, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T141450-9a9a16.png', 'cab290a5682a4e812e56f1b78b73095b84620d2352deadf4028f83260973c7b3', 'reemplazado', '2026-03-08 14:14:50', '2026-03-08 14:21:29'),
(60, 'web', 'menu', 1, 'logo_web', '20251109_2130_Luigi Sistemas Logo Concepts_simple_compose.png', 'logo-web-menu-1-20260308T142129-19fa3f.png', 'png', 'image/png', 382920, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T142129-19fa3f.png', '7626a8d5a8fbf508305d13728ea387fea57ac9fc8486e5b8bca8145edd816a6f', 'reemplazado', '2026-03-08 14:21:29', '2026-03-08 14:21:46'),
(61, 'web', 'menu', 1, 'logo_web', '20251109_2326_Logo sobre fondo blanco_remix_01k9p06s6xf3fvv49cy16h7vfp.png', 'logo-web-menu-1-20260308T142146-2316a7.png', 'png', 'image/png', 837253, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T142146-2316a7.png', '661a033ff4313563e71024a90a2d78ac49b5e779dbe73f354d4df809dd746385', 'reemplazado', '2026-03-08 14:21:46', '2026-03-08 14:37:53'),
(62, 'web', 'menu', 1, 'logo_web', '20251110_0013_Logo sin Fondo_remix_01k9p2wq0qeakb6bfzqeexz8n3.png', 'logo-web-menu-1-20260308T143753-a8a672.png', 'png', 'image/png', 1519980, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T143753-a8a672.png', 'b9139dd1de35ec1ed5a520bfebefa96f3aeebf845029f6ee8553587c2a8dda3a', 'reemplazado', '2026-03-08 14:37:53', '2026-03-08 14:38:08'),
(63, 'web', 'menu', 1, 'logo_web', 'luigi_sistemas_logo03.png', 'logo-web-menu-1-20260308T143808-df3009.png', 'png', 'image/png', 396572, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T143808-df3009.png', '93bbe18f9884a40c2d67e8f1bee8adc816e6851ed548c6dd656d35f9f49705eb', 'reemplazado', '2026-03-08 14:38:08', '2026-03-08 14:38:25'),
(64, 'web', 'menu', 1, 'logo_web', '20251109_2320_Logo Animado Retro_remix_01k9nztywyem0v5qh9srfx3zm6.png', 'logo-web-menu-1-20260308T143825-703621.png', 'png', 'image/png', 1597651, 'almacen/2026/03/08/logo_web/logo-web-menu-1-20260308T143825-703621.png', '2a834c7d7b982a3e51e1b336488f0c3fb6fcec6525c1c066455a49a12b4907be', 'reemplazado', '2026-03-08 14:38:25', '2026-03-09 12:27:00'),
(65, 'web', 'caracteristicas', 1, 'img_caracteristica', '20251109_2320_Logo Animado Retro_remix_01k9nztywyem0v5qh9srfx3zm6.png', 'caracteristica-web-caracteristicas-1-20260308T172012-7ae2a0.png', 'png', 'image/png', 1597651, 'almacen/2026/03/08/img_caracteristica/caracteristica-web-caracteristicas-1-20260308T172012-7ae2a0.png', '2a834c7d7b982a3e51e1b336488f0c3fb6fcec6525c1c066455a49a12b4907be', 'borrado', '2026-03-08 17:20:12', '2026-03-08 17:21:47'),
(66, 'web', 'nosotros', 1, 'img_nosotros', '20251109_2320_Logo Animado Retro_remix_01k9nztywyem0v5qh9srfx3zm6.png', 'nosotros-icono-1-nosotros-1-20260308T184734-aa9fa9.png', 'png', 'image/png', 1597651, 'almacen/2026/03/08/img_nosotros/nosotros-icono-1-nosotros-1-20260308T184734-aa9fa9.png', '2a834c7d7b982a3e51e1b336488f0c3fb6fcec6525c1c066455a49a12b4907be', 'borrado', '2026-03-08 18:47:34', '2026-03-08 18:49:08'),
(67, 'web', 'nosotros', 1, 'img_nosotros', 'ai-generated-bright-and-clean-office-environment-abstract-background-bright-office-with-plants-and-large-windows-by-generated-ai-photo.jpg', 'nosotros-principal-nosotros-1-20260308T185540-159ed4.jpg', 'jpg', 'image/jpeg', 21127, 'almacen/2026/03/08/img_nosotros/nosotros-principal-nosotros-1-20260308T185540-159ed4.jpg', '0efb77ae022aacff62802306659bfcf91d6f48a3f958d273972b447f9934dc81', 'borrado', '2026-03-08 18:55:40', '2026-03-08 18:56:19'),
(68, 'web', 'nosotros', 1, 'img_nosotros', 'Contar-con-una-ferreteria-a-pocos-metros-de-casa-un-lujo-hoy-en-dia.jpg', 'nosotros-secundaria-nosotros-1-20260308T185540-559a9e.jpg', 'jpg', 'image/jpeg', 497291, 'almacen/2026/03/08/img_nosotros/nosotros-secundaria-nosotros-1-20260308T185540-559a9e.jpg', 'de34531af163b9f0e28ed094f51cede1e81d46f83974f3703e2a0369a0b81b2c', 'borrado', '2026-03-08 18:55:40', '2026-03-08 18:56:19'),
(69, 'web', 'banner', 1, 'img_banner', 'bg1.webp', 'banner-web-banner-1-20260308T204655-ee27ed.webp', 'webp', 'image/webp', 17830, 'almacen/2026/03/08/img_banner/banner-web-banner-1-20260308T204655-ee27ed.webp', 'c1540ab7e94685fbf16639f5b87eaa82f9aa828f8e06789cbe52a7bc837d6521', 'local', '2026-03-08 20:46:55', '2026-03-08 20:46:55'),
(70, 'web', 'formulario_carrusel', 1, 'img_formulario_carrusel', 'istockphoto-1137578281-612x612.jpg', 'slide-formulario-carrusel-formulario_carrusel-1-20260308T224748-0dcf70.jpg', 'jpg', 'image/jpeg', 64491, 'almacen/2026/03/08/img_formulario_carrusel/slide-formulario-carrusel-formulario_carrusel-1-20260308T224748-0dcf70.jpg', '6cdbecb3bd36f860a93416e1fcc87964cbbf2f556b5bd970a87dfc19b0ddaba1', 'reemplazado', '2026-03-08 22:47:48', '2026-03-09 12:27:47'),
(71, 'web', 'carrusel_servicios', 1, 'img_carrusel_servicios', 'reca_aiii_512.png', 'carrusel-servicio-carrusel_servicios-1-20260309T003502-8d8532.png', 'png', 'image/png', 65735, 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-8d8532.png', '17026d30133eb8e9f250bfc2aec10681f5cbfe1b16ae41c0cc83712b7d0a105f', 'local', '2026-03-09 00:35:02', '2026-03-09 00:35:02'),
(72, 'web', 'carrusel_servicios', 1, 'img_carrusel_servicios', 'reca_aii_512.png', 'carrusel-servicio-carrusel_servicios-1-20260309T003502-d942ee.png', 'png', 'image/png', 95159, 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-d942ee.png', '03abf580ff2078708e683495784dc95512f598392c36a7e6bd66ea3c595917f3', 'local', '2026-03-09 00:35:02', '2026-03-09 00:35:02'),
(73, 'web', 'carrusel_servicios', 1, 'img_carrusel_servicios', 'moto_biic_512.png', 'carrusel-servicio-carrusel_servicios-1-20260309T003502-1c6764.png', 'png', 'image/png', 104278, 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-1c6764.png', 'bae735582838a37189b0781f6fed145fd81c35a6379e18b02d59bcce2114f0d0', 'local', '2026-03-09 00:35:02', '2026-03-09 00:35:02'),
(74, 'web', 'carrusel_servicios', 1, 'img_carrusel_servicios', 'cambiemos_actitud_512.png', 'carrusel-servicio-carrusel_servicios-1-20260309T003502-77ae58.png', 'png', 'image/png', 117779, 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-77ae58.png', 'a9ca49bd36e113be6da6794b2805bb0beb361af2b93e1bc908d8faff16147135', 'local', '2026-03-09 00:35:02', '2026-03-09 00:35:02'),
(75, 'web', 'carrusel_servicios', 1, 'img_carrusel_servicios', 'matpel_512.png', 'carrusel-servicio-carrusel_servicios-1-20260309T003502-665200.png', 'png', 'image/png', 61656, 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-665200.png', 'b2c951872d507d081aa48995c59f0a07eb11b27484c60b2053a9657556fda42f', 'local', '2026-03-09 00:35:02', '2026-03-09 00:35:02'),
(76, 'web', 'carrusel_empresas', 1, 'img_carrusel_empresas', 'circular guia mis rutas.png', 'carrusel-empresa-carrusel_empresas-1-20260309T092914-cd85c5.png', 'png', 'image/png', 245413, 'almacen/2026/03/09/img_carrusel_empresas/carrusel-empresa-carrusel_empresas-1-20260309T092914-cd85c5.png', 'd457fed1d4d12103e220bd56eb5b1e359936eb1b460e7ec56c026bd1a23dfa10', 'local', '2026-03-09 09:29:14', '2026-03-09 09:29:14'),
(77, 'web', 'carrusel_empresas', 1, 'img_carrusel_empresas', 'circular allain prost.png', 'carrusel-empresa-carrusel_empresas-1-20260309T092914-5dd2a9.png', 'png', 'image/png', 793204, 'almacen/2026/03/09/img_carrusel_empresas/carrusel-empresa-carrusel_empresas-1-20260309T092914-5dd2a9.png', '87abbbe6d23b34241890865f45a9cc1237e2cd3619d62aa95c9362be697ea1fe', 'local', '2026-03-09 09:29:14', '2026-03-09 09:29:14'),
(78, 'web', 'carrusel_empresas', 1, 'img_carrusel_empresas', 'circular vias seguras.png', 'carrusel-empresa-carrusel_empresas-1-20260309T092914-37ba05.png', 'png', 'image/png', 1861531, 'almacen/2026/03/09/img_carrusel_empresas/carrusel-empresa-carrusel_empresas-1-20260309T092914-37ba05.png', 'be119d25c73a12db7ed5be326a9faeba043f5b3e84972944328fa054f09ac0c1', 'local', '2026-03-09 09:29:14', '2026-03-09 09:29:14'),
(79, 'web', 'carrusel_empresas', 1, 'img_carrusel_empresas', 'circular genesis.png', 'carrusel-empresa-carrusel_empresas-1-20260309T092914-ca19fe.png', 'png', 'image/png', 1514254, 'almacen/2026/03/09/img_carrusel_empresas/carrusel-empresa-carrusel_empresas-1-20260309T092914-ca19fe.png', 'a736f6182cb3e49a0c9783a4fcd3557b958b1331c67d798d67c66f35866e3970', 'local', '2026-03-09 09:29:14', '2026-03-09 09:29:14'),
(80, 'web', 'testimonios', 1, 'img_testimonios', '4e7fbf06-5c6d-4763-a282-e56281ea5eb3.jpg', 'testimonio-cliente-testimonios-1-20260309T100405-67da69.jpg', 'jpg', 'image/jpeg', 320241, 'almacen/2026/03/09/img_testimonios/testimonio-cliente-testimonios-1-20260309T100405-67da69.jpg', '81c03d12db449aca3d62e0cb48b73034a25fd4244b7728e331af7365c3b28030', 'local', '2026-03-09 10:04:05', '2026-03-09 10:04:05'),
(81, 'web', 'testimonios', 1, 'img_testimonios', 'CATHERINE SOCORRO CANALES HERRADA.jpg', 'testimonio-cliente-testimonios-1-20260309T100405-760983.jpg', 'jpg', 'image/jpeg', 197632, 'almacen/2026/03/09/img_testimonios/testimonio-cliente-testimonios-1-20260309T100405-760983.jpg', 'f8e17b778b090f265b7a6c5cfa6b0e76234d1d7912a6ed4409ca5f54b52e8b1d', 'local', '2026-03-09 10:04:05', '2026-03-09 10:04:05'),
(82, 'web', 'menu', 1, 'logo_web', 'circular genesis.png', 'logo-web-menu-1-20260309T122700-9ee68a.png', 'png', 'image/png', 1514254, 'almacen/2026/03/09/logo_web/logo-web-menu-1-20260309T122700-9ee68a.png', 'a736f6182cb3e49a0c9783a4fcd3557b958b1331c67d798d67c66f35866e3970', 'local', '2026-03-09 12:27:00', '2026-03-09 12:27:00'),
(83, 'web', 'formulario_carrusel', 1, 'img_formulario_carrusel', '637645387_122123758989118052_4091517104382176970_n.png', 'slide-formulario-carrusel-formulario_carrusel-1-20260309T122747-ccf7b4.png', 'png', 'image/png', 1394986, 'almacen/2026/03/09/img_formulario_carrusel/slide-formulario-carrusel-formulario_carrusel-1-20260309T122747-ccf7b4.png', '09bf851d52616af80f4d75fd1a2bf521025655014a6c0bf427fd40724301764f', 'reemplazado', '2026-03-09 12:27:47', '2026-03-09 13:35:58'),
(84, 'web', 'formulario_carrusel', 1, 'img_formulario_carrusel', '637645387_122123758989118052_4091517104382176970_n.png', 'slide-formulario-carrusel-formulario_carrusel-1-20260309T133558-a65eac.png', 'png', 'image/png', 1394986, 'almacen/2026/03/09/img_formulario_carrusel/slide-formulario-carrusel-formulario_carrusel-1-20260309T133558-a65eac.png', '09bf851d52616af80f4d75fd1a2bf521025655014a6c0bf427fd40724301764f', 'local', '2026-03-09 13:35:58', '2026-03-09 13:35:58'),
(85, 'web', 'formulario_carrusel', 1, 'img_formulario_carrusel', '634480748_122117897613139222_4798721979520315320_n.png', 'slide-formulario-carrusel-formulario_carrusel-1-20260309T133558-50f51e.png', 'png', 'image/png', 1270087, 'almacen/2026/03/09/img_formulario_carrusel/slide-formulario-carrusel-formulario_carrusel-1-20260309T133558-50f51e.png', '17da83afe7dde5e248ec851ce95031e4cd51d2317fb532a1e64d140a6d3b9343', 'local', '2026-03-09 13:35:58', '2026-03-09 13:35:58'),
(86, 'web', 'formulario_carrusel', 1, 'img_formulario_carrusel', '646377120_122127347037127007_8639269957831514301_n.png', 'slide-formulario-carrusel-formulario_carrusel-1-20260309T133558-c227ee.png', 'png', 'image/png', 1334417, 'almacen/2026/03/09/img_formulario_carrusel/slide-formulario-carrusel-formulario_carrusel-1-20260309T133558-c227ee.png', '0134608b8a57d1ae49deb96d3ce37c9008366022df5072562ade9ec091e22b38', 'local', '2026-03-09 13:35:58', '2026-03-09 13:35:58'),
(87, 'web', 'nosotros', 1, 'img_nosotros', 'sacar-brevete-clases-manejo-instructores-conduccion-peru-academia.png', 'nosotros-secundaria-nosotros-1-20260309T135348-7813f7.png', 'png', 'image/png', 1827520, 'almacen/2026/03/09/img_nosotros/nosotros-secundaria-nosotros-1-20260309T135348-7813f7.png', '0932ec3b0f16eb3ce77740af7c5b99630da7fe61fa8d12a86a889a16d9d73630', 'local', '2026-03-09 13:53:48', '2026-03-09 13:53:48'),
(88, 'web', 'novedades', 1, 'img_novedades', '633f4eb2b5f3f245363d0666.png', 'novedad-blog-novedades-1-20260309T175647-683df9.png', 'png', 'image/png', 1646677, 'almacen/2026/03/09/img_novedades/novedad-blog-novedades-1-20260309T175647-683df9.png', '3dee53c9505a71c6d293f5ff8b770e0f43a7e8ffde8226c6c2cf3dc17501e914', 'local', '2026-03-09 17:56:47', '2026-03-09 17:56:47'),
(89, 'web', 'novedades', 1, 'img_novedades', 'transporte-casa-lima-portada.jpg', 'novedad-blog-novedades-1-20260309T175647-129ae7.jpg', 'jpg', 'image/jpeg', 100091, 'almacen/2026/03/09/img_novedades/novedad-blog-novedades-1-20260309T175647-129ae7.jpg', '5077b3391e2bbf4fb8ae239d1dcbfc2498a6442b41b716460990c7111b40d820', 'local', '2026-03-09 17:56:47', '2026-03-09 17:56:47'),
(90, 'web', 'novedades', 1, 'img_novedades', 'Cual-es-el-sueldo-de-un-conductor-de-transporte-pesado.png', 'novedad-blog-novedades-1-20260309T175818-8ef62f.png', 'png', 'image/png', 261422, 'almacen/2026/03/09/img_novedades/novedad-blog-novedades-1-20260309T175818-8ef62f.png', '829b9c1a07fc88d5e6058239654224fd67b0080c6a6f1d164ea24576372eb30e', 'local', '2026-03-09 17:58:18', '2026-03-09 17:58:18'),
(91, 'web', 'novedades', 1, 'img_novedades', 'aplicaciones-de-la-Cisterna-de-Combustible-rojo-1.png', 'novedad-blog-novedades-1-20260309T175818-702759.png', 'png', 'image/png', 506955, 'almacen/2026/03/09/img_novedades/novedad-blog-novedades-1-20260309T175818-702759.png', 'efec504bc920e40454b484ad849583e165fa15a90cc3f62cec916c071cadfaac', 'local', '2026-03-09 17:58:18', '2026-03-09 17:58:18'),
(92, 'empresas', 'empresa', 20, 'img_logos_empresas', 'circular guia mis rutas.png', 'logo-empresa-empresa-20-20260309T190710-a8c7d0.png', 'png', 'image/png', 245413, 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png', 'd457fed1d4d12103e220bd56eb5b1e359936eb1b460e7ec56c026bd1a23dfa10', 'local', '2026-03-09 19:07:10', '2026-03-09 19:07:10'),
(93, 'empresas', 'empresa', 21, 'img_logos_empresas', 'circular guia mis rutas.png', 'logo-empresa-empresa-21-20260309T190757-8eff56.png', 'png', 'image/png', 245413, 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-21-20260309T190757-8eff56.png', 'd457fed1d4d12103e220bd56eb5b1e359936eb1b460e7ec56c026bd1a23dfa10', 'local', '2026-03-09 19:07:57', '2026-03-09 19:07:57'),
(94, 'empresas', 'empresa', 22, 'img_logos_empresas', 'circular guia mis rutas.png', 'logo-empresa-empresa-22-20260309T190849-64ca30.png', 'png', 'image/png', 245413, 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-22-20260309T190849-64ca30.png', 'd457fed1d4d12103e220bd56eb5b1e359936eb1b460e7ec56c026bd1a23dfa10', 'local', '2026-03-09 19:08:49', '2026-03-09 19:08:49'),
(95, 'empresas', 'empresa', 23, 'img_logos_empresas', 'circular vias seguras.png', 'logo-empresa-empresa-23-20260309T191127-076eb8.png', 'png', 'image/png', 1861531, 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-23-20260309T191127-076eb8.png', 'be119d25c73a12db7ed5be326a9faeba043f5b3e84972944328fa054f09ac0c1', 'local', '2026-03-09 19:11:27', '2026-03-09 19:11:27'),
(96, 'empresas', 'empresa', 24, 'img_logos_empresas', 'circular vias seguras.png', 'logo-empresa-empresa-24-20260309T191231-901a23.png', 'png', 'image/png', 1861531, 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-24-20260309T191231-901a23.png', 'be119d25c73a12db7ed5be326a9faeba043f5b3e84972944328fa054f09ac0c1', 'local', '2026-03-09 19:12:31', '2026-03-09 19:12:31'),
(97, 'empresas', 'empresa', 25, 'img_logos_empresas', 'circular allain prost.png', 'logo-empresa-empresa-25-20260309T191348-68c56e.png', 'png', 'image/png', 793204, 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-25-20260309T191348-68c56e.png', '87abbbe6d23b34241890865f45a9cc1237e2cd3619d62aa95c9362be697ea1fe', 'local', '2026-03-09 19:13:48', '2026-03-09 19:13:48'),
(98, 'empresas', 'empresa', 26, 'img_logos_empresas', 'circular allain prost.png', 'logo-empresa-empresa-26-20260309T191437-0e4be1.png', 'png', 'image/png', 793204, 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-26-20260309T191437-0e4be1.png', '87abbbe6d23b34241890865f45a9cc1237e2cd3619d62aa95c9362be697ea1fe', 'local', '2026-03-09 19:14:37', '2026-03-09 19:14:37'),
(99, 'empresas', 'empresa', 27, 'img_logos_empresas', 'circular allain prost.png', 'logo-empresa-empresa-27-20260309T191605-e34a33.png', 'png', 'image/png', 793204, 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-27-20260309T191605-e34a33.png', '87abbbe6d23b34241890865f45a9cc1237e2cd3619d62aa95c9362be697ea1fe', 'local', '2026-03-09 19:16:05', '2026-03-09 19:16:05'),
(100, 'empresas', 'empresa', 28, 'img_logos_empresas', 'circular allain prost.png', 'logo-empresa-empresa-28-20260309T191722-eac940.png', 'png', 'image/png', 793204, 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-28-20260309T191722-eac940.png', '87abbbe6d23b34241890865f45a9cc1237e2cd3619d62aa95c9362be697ea1fe', 'local', '2026-03-09 19:17:22', '2026-03-09 19:17:22'),
(101, 'usuarios', 'usuario', 16, 'img_perfil', 'carnet 4.png', 'perfil-usuario-usuario-16-20260312T101319-46b13e.png', 'png', 'image/png', 88633, 'almacen/2026/03/12/img_perfil/perfil-usuario-usuario-16-20260312T101319-46b13e.png', 'be018b8a157d9d3d1a1b6a8c95a2041db4d3e1d10012b8703e7bec875ed66554', 'local', '2026-03-12 10:13:19', '2026-03-12 10:13:19'),
(102, 'usuarios', 'usuario', 17, 'img_perfil', 'helen.png', 'perfil-usuario-usuario-17-20260316T005659-71320d.png', 'png', 'image/png', 25996, 'almacen/2026/03/16/img_perfil/perfil-usuario-usuario-17-20260316T005659-71320d.png', '996780da1af186028517cce09b4ace62d3365b9866886785e0ce79ed19bfaf4f', 'local', '2026-03-16 00:56:59', '2026-03-16 00:56:59'),
(103, 'usuarios', 'usuario', 18, 'img_perfil', 'andy.png', 'perfil-usuario-usuario-18-20260316T005730-6f6533.png', 'png', 'image/png', 26958, 'almacen/2026/03/16/img_perfil/perfil-usuario-usuario-18-20260316T005730-6f6533.png', '2bf70c74ac56ca1448689753bb86df291bedcbf2d3513384dba88032a424ddea', 'local', '2026-03-16 00:57:30', '2026-03-16 00:57:30'),
(104, 'usuarios', 'usuario', 19, 'img_perfil', 'jeferson.png', 'perfil-usuario-usuario-19-20260316T005755-384dee.png', 'png', 'image/png', 25015, 'almacen/2026/03/16/img_perfil/perfil-usuario-usuario-19-20260316T005755-384dee.png', 'de385a3533d61852b0864dde2bebc38c18a0481b52e392338a1a6124ad7ef34d', 'local', '2026-03-16 00:57:55', '2026-03-16 00:57:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_control_interfaces_usuario`
--

CREATE TABLE `mtp_control_interfaces_usuario` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `interface_slug` varchar(120) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actualizado_por` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_control_interfaces_usuario`
--

INSERT INTO `mtp_control_interfaces_usuario` (`id`, `id_usuario`, `interface_slug`, `estado`, `creado_en`, `actualizado_en`, `actualizado_por`) VALUES
(1, 16, 'control_servicios', 1, '2026-03-12 17:02:37', '2026-03-12 23:14:06', 1),
(3, 16, 'control_precios', 1, '2026-03-12 23:14:06', '2026-03-12 23:14:06', 1),
(4, 17, 'control_servicios', 1, '2026-03-16 06:06:25', '2026-03-16 06:06:25', 1),
(5, 17, 'control_precios', 1, '2026-03-16 06:06:25', '2026-03-16 06:06:25', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_departamentos`
--

CREATE TABLE `mtp_departamentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_departamentos`
--

INSERT INTO `mtp_departamentos` (`id`, `nombre`) VALUES
(6, 'Ancash'),
(5, 'Cajamarca'),
(11, 'Junín'),
(4, 'La Libertad'),
(3, 'Lambayeque'),
(13, 'Lima'),
(9, 'Loreto'),
(12, 'Pasco'),
(2, 'Piura'),
(8, 'San Martín'),
(1, 'Tumbes'),
(7, 'Ucayali');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_detalle_usuario`
--

CREATE TABLE `mtp_detalle_usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `ruta_foto` varchar(255) DEFAULT NULL,
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_detalle_usuario`
--

INSERT INTO `mtp_detalle_usuario` (`id_usuario`, `ruta_foto`, `actualizado`) VALUES
(1, 'almacen/2026/01/21/img_perfil/perfil-usuario-usuario-1-20260121T235608-b9f756.png', '2026-01-21 23:56:08'),
(2, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-2-20260122T012245-e04dfe.png', '2026-01-22 01:22:46'),
(3, 'almacen/2026/01/22/img_perfil/perfil-usuario-usuario-3-20260122T012755-2b0900.png', '2026-01-22 01:27:55'),
(4, 'almacen/2026/01/23/img_perfil/perfil-usuario-usuario-4-20260123T122937-6a97e5.jpg', '2026-01-23 12:29:37'),
(5, 'almacen/2026/02/03/img_perfil/perfil-usuario-usuario-5-20260203T155038-6e4fec.png', '2026-02-03 15:50:38'),
(6, 'almacen/2026/02/03/img_perfil/perfil-usuario-usuario-6-20260203T161847-bf58b4.png', '2026-02-03 16:18:47'),
(7, 'almacen/2026/02/03/img_perfil/perfil-usuario-usuario-7-20260203T165113-89e787.png', '2026-02-03 16:51:13'),
(10, 'almacen/2026/03/03/img_perfil/perfil-usuario-usuario-10-20260303T230114-423cf9.png', '2026-03-03 23:01:14'),
(11, 'almacen/2026/03/06/img_perfil/perfil-usuario-usuario-11-20260306T085348-f6d491.png', '2026-03-06 08:53:48'),
(12, 'almacen/2026/03/06/img_perfil/perfil-usuario-usuario-12-20260306T190302-478825.png', '2026-03-06 19:03:02'),
(13, 'almacen/2026/03/06/img_perfil/perfil-usuario-usuario-13-20260306T190728-338ecc.png', '2026-03-06 19:07:28'),
(14, 'almacen/2026/03/06/img_perfil/perfil-usuario-usuario-14-20260306T190811-cbd1a6.jpg', '2026-03-06 19:08:11'),
(15, 'almacen/2026/03/07/img_perfil/perfil-usuario-usuario-15-20260307T163507-5a0549.png', '2026-03-07 16:35:07'),
(16, 'almacen/2026/03/12/img_perfil/perfil-usuario-usuario-16-20260312T101319-46b13e.png', '2026-03-12 10:13:19'),
(17, 'almacen/2026/03/16/img_perfil/perfil-usuario-usuario-17-20260316T005659-71320d.png', '2026-03-16 00:56:59'),
(18, 'almacen/2026/03/16/img_perfil/perfil-usuario-usuario-18-20260316T005730-6f6533.png', '2026-03-16 00:57:30'),
(19, 'almacen/2026/03/16/img_perfil/perfil-usuario-usuario-19-20260316T005755-384dee.png', '2026-03-16 00:57:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_empresas`
--

CREATE TABLE `mtp_empresas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `razon_social` varchar(300) NOT NULL,
  `ruc` varchar(11) NOT NULL,
  `direccion` varchar(300) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `id_tipo` int(10) UNSIGNED NOT NULL,
  `id_depa` int(10) UNSIGNED NOT NULL,
  `id_repleg` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_empresas`
--

INSERT INTO `mtp_empresas` (`id`, `nombre`, `razon_social`, `ruc`, `direccion`, `logo_path`, `id_tipo`, `id_depa`, `id_repleg`) VALUES
(1, 'LEONCORP', 'Corporación León & Asociados S.A.C.', '20608302531', 'Av. America Oeste 580', 'almacen/2026/01/21/img_logos_empresas/logo-empresa-empresa-1-20260121T235150-57a9fe.png', 6, 4, 1),
(2, 'GLOBAL CAR CHICLAYO', 'GLOBAL CAR PERU S.A.C.', '20539808487', 'Carr. Chiclayo - Pimentel Km. 2-3 Ov. El Trébol (Frente a la Zona Industrial)', 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-2-20260122T010707-d5d470.png', 1, 3, 10),
(3, 'GLOBAL CAR PIURA', 'GLOBAL CAR PERU S.A.C.', '20539808487', 'Calle Los Ceibos N°320 (A espaldas de la posta de Pachitea)', 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-3-20260122T010805-81a59c.png', 1, 2, 10),
(7, 'SELVA CAR PUCALLPA', 'SELVA CAR S.A.C.', '20477683909', 'Jr. Leoncio Prado 125 - Ref. Cuadra 4 de la Av. Centenario al lado de mayólicas SaniCenter a media cuadra del parque MARGARITA', 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-7-20260122T184509-53ee42.png', 1, 7, 6),
(8, 'SELVA CAR AGUAYTIA', 'SELVA CAR S.A.C.', '20477683909', 'FRENTE AL SEMAFORO DEL TERMINAL TERRESTRE - BARRIO UNIDO Calle 4. Mz.211 Lt.9', 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-8-20260122T184610-e75c5e.png', 1, 7, 6),
(12, 'GLOBAL CAR S.A.C', 'ESCUELA GLOBAL CAR S.A.C.', '20600213769', 'Jr. México N° 500 Urb. Torres Araujo (Frente a la Gerencia Regional de Transportes de La Libertad)', 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-12-20260122T185333-132e60.png', 1, 4, 10),
(17, 'GUÍA MIS RUTAS', 'ASOCIACION GUIA MIS RUTAS', '20477238336', 'Jr. Gabino Uribe S/N. Mz. 8 Lt. 13, Barrido Pedregal Medio', 'almacen/2026/01/22/img_logos_empresas/logo-empresa-empresa-17-20260122T232400-e07c18.png', 1, 6, 10),
(18, 'OPEN CAR TUMBES', 'OPEN CAR TUMBES S.A.C.', '20609707594', 'AV. TUMBES NRO. 548 URB. CERCADO (TERCER PISO) TUMBES - TUMBES - TUMBES', 'almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-18-20260303T084638-e1eaa9.png', 1, 1, 16),
(19, 'LSISTEMAS', 'LUIGI SISTEMAS', '20601111111', 'Calle 8 de septiembre #1345', 'almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png', 1, 4, 17),
(20, 'GUIA MIS RUTAS TRUJILLO', 'ASOCIACION GUIA MIS RUTAS', '20477238336', '-', 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png', 1, 4, 20),
(21, 'GUIA MIS RUTAS LIMA', 'ASOCIACION GUIA MIS RUTAS', '20477238336', '-', 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-21-20260309T190757-8eff56.png', 1, 13, 20),
(22, 'GUIA MIS RUTAS HUANCAYO', 'ASOCIACION GUIA MIS RUTAS', '20477238336', '-', 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-22-20260309T190849-64ca30.png', 1, 11, 20),
(23, 'VIAS SEGURAS CHICLAYO', 'VIAS SEGURAS S.A.C.', '20482764151', '-', 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-23-20260309T191127-076eb8.png', 1, 3, 19),
(24, 'VIAS SEGURAS PASCO', 'VIAS SEGURAS S.A.C.', '20482764151', '-', 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-24-20260309T191231-901a23.png', 1, 12, 19),
(25, 'ALLAIN PROST LA MERCED', 'ESCUELA DE CONDUCTORES INTEGRALES ALLAIN PROST E.I.R.L.', '20482833811', '-', 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-25-20260309T191348-68c56e.png', 1, 11, 18),
(26, 'ALLAIN PROST PIURA', 'ESCUELA DE CONDUCTORES INTEGRALES ALLAIN PROST E.I.R.L.', '20482833811', '-', 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-26-20260309T191437-0e4be1.png', 1, 2, 18),
(27, 'ALLAIN PROST CHOCOPE', 'ESCUELA DE CONDUCTORES INTEGRALES ALLAIN PROST E.I.R.L.', '20482833811', '-', 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-27-20260309T191605-e34a33.png', 1, 4, 18),
(28, 'ALLAIN PROST HUANCAYO', 'ESCUELA DE CONDUCTORES INTEGRALES ALLAIN PROST E.I.R.L.', '20482833811', '-', 'almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-28-20260309T191722-eac940.png', 1, 11, 18);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_representante_legal`
--

CREATE TABLE `mtp_representante_legal` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `documento` varchar(8) NOT NULL,
  `clave_mana` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_representante_legal`
--

INSERT INTO `mtp_representante_legal` (`id`, `nombres`, `apellidos`, `documento`, `clave_mana`) VALUES
(1, 'AMINA CATALINA', 'LEON APONTE', '17867379', '17867379'),
(2, 'HORMECINDA ELISA', 'LEON APONTE', '17867380', '17867380'),
(3, 'CARLOS ANTONIO', 'OLIVERA ACOSTA', '19208698', '#Leon08Corp@77777'),
(4, 'MARTIN', 'NECIOSUP LEON', '71997424', 'LeonCorp777*'),
(5, 'MORTIMER ONECIMO', 'LEÓN APONTE', '17810377', 'LeonCorp@777766'),
(6, 'SEGUNDO GILBERTO', 'NEYRA ALVAREZ', '27151301', 'SelvaCar@77777'),
(7, 'NICOLAS RODOLFO', 'ACOSTA ARGUEDAS', '21409816', 'SC_shadai257777'),
(8, 'VICENTE', 'CARDENAS CARDENAS', '41410621', 'total@MEDIC009'),
(9, 'JORGE DAVID', 'JORGE DAVID', '74749290', 'omT@77766'),
(10, 'SEGUNDO MANUEL', 'ALVAREZ COSTA', '41498367', 'SCLoreto@77777'),
(11, 'VICTORIA ESTELITA', 'GARCIA YPARRAGUIRRE', '42539405', 'GCtum@777777777'),
(12, 'SEGUNDO FRANCISCO', 'PERALTA VASQUEZ', '27360051', '27360051@Gm776'),
(13, 'MAGLEM ALMA', 'CARRANZA RODRIGUEZ', '77083494', 'GCTsac@77777'),
(14, 'HENRRY', 'FERNANDEZ DOMINGUEZ', '41040656', 'Shad@i777777'),
(15, 'JUAN CARLOS', 'LOPEZ HERNANDEZ', '43258330', '43258330@Sa01'),
(16, 'NATALY ROSMERY', 'REYES SARE', '70613193', '70613193'),
(17, 'LUIGI ISRAEL', 'VILLANUEVA PEREZ', '70379752', '70379752'),
(18, 'NADEZHDA', 'CIENFUEGOS RAMIREZ', '45688335', 'ECAPa74*'),
(19, 'ROXANA MARILU', 'TRELLES URQUIZA', '18198265', 'RMTUd*15'),
(20, 'EDWARD GIANCARLO', 'SUAREZ MENDOZA', '06671460', '28The_Virus*');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_roles`
--

CREATE TABLE `mtp_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_roles`
--

INSERT INTO `mtp_roles` (`id`, `nombre`) VALUES
(4, 'Administración'),
(7, 'Cliente'),
(2, 'Control'),
(5, 'COTI'),
(1, 'Desarrollo'),
(6, 'Gerente'),
(3, 'Recepción');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_tipos_empresas`
--

CREATE TABLE `mtp_tipos_empresas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_tipos_empresas`
--

INSERT INTO `mtp_tipos_empresas` (`id`, `nombre`) VALUES
(6, 'Central'),
(2, 'ECSAL'),
(1, 'ESCON'),
(4, 'Escuela de Manejo'),
(3, 'MATPEL'),
(5, 'Proyecto');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_usuarios`
--

CREATE TABLE `mtp_usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(11) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_usuarios`
--

INSERT INTO `mtp_usuarios` (`id`, `usuario`, `clave`, `nombres`, `apellidos`, `id_empresa`, `creacion`, `actualizacion`) VALUES
(1, '70379752', '$2y$10$6/jVdqi8gqZrvXZZNdsN.ugPOJBpnvJju87aBDF3brlBbTPE3QcdO', 'LUIGI ISRAEL', 'VILLANUEVA PEREZ', 1, '2026-01-21 23:47:48', '2026-01-21 23:47:48'),
(2, '48067143', '$2y$10$FKv6kU3a9/22LQacbESwjO13BESb2lLPzskrANSv6jmDscfF9EbBe', 'ALMA DE JESUS', 'SANTOS ALVAREZ', 2, '2026-01-22 01:16:10', '2026-01-22 01:16:10'),
(3, '75133929', '$2y$10$NzXmSf/Iign5W5V5kvv8vuMy0QPir/kx4fBL6Z56T6sM7czLrcaeK', 'MILCA SARAI', 'CASTILLO SUXCE', 3, '2026-01-22 01:16:56', '2026-01-22 01:16:56'),
(4, '76007644', '$2y$10$sSbMyIOkV9nuA7Zun.bCIeDorHUrlPVTHrE9Sr5x3RsSFtm3OJLXu', 'MIGUEL BENJAMIN', 'GUTIERREZ GOMEZ', 1, '2026-01-23 10:23:11', '2026-01-23 10:23:11'),
(5, '77083494', '$2y$10$JAcC6cuZE6dlGTw4JuzTY.q/t4bkjZk7aBNkghpEA1LKxSBF1wxBa', 'MAGLEM ALMA', 'CARRANZA RODRIGUEZ', 1, '2026-02-03 15:50:38', '2026-02-03 15:50:38'),
(6, '63245466', '$2y$10$E/3Vd62rJgi/9.MeUtF9ye5xAZC8a8zR3xBzEy95/Q7/hpEBhuNCm', 'MILAGROS', 'SOLORZANO TARAZONA', 8, '2026-02-03 16:18:47', '2026-02-03 16:18:47'),
(7, '44036670', '$2y$10$8tXcXYI.Lvj1zW9XT5QA.Ov/gZ2wrvhbthAKsMu5QqUumkC/d6SS6', 'JUAN CARLOS', 'DEL CASTILLO PINON', 7, '2026-02-03 16:51:13', '2026-02-03 16:51:13'),
(8, '49494949', '$2y$10$YmvJ9B8U8TAU8fau8r9Wje97M5Fs6/trOEb2YnXqeRkIMtNPIWsvG', 'Francis', 'Paredes', 12, '2026-02-27 09:11:09', '2026-02-27 09:11:09'),
(9, '71883368', '$2y$10$6C0MQwKvQbOKeWPVROZEUOMmmc53OHBdLgTFrmzL.xp6YAr2zNRGm', 'ANGEL ORLANDO', 'DEL ROSARIO CARRILLO', 18, '2026-03-03 08:47:50', '2026-03-03 08:47:50'),
(10, '12121212', '$2y$10$sD2DFcl.PeC7ztqlc3oHauxnxi1CZrO.inJtWcvvuR3eipQVbAgCe', 'JOSE ALBERTO', 'VARGAS CHAVEZ', 19, '2026-03-03 23:01:14', '2026-03-03 23:01:14'),
(11, '13131313', '$2y$10$HKlF.yIRBq6cj23KRu1sBuny6fKRB2UXKIgemPg6lZN9X7oNcEzSe', 'Carlos', 'Castillo Cárdenas', 19, '2026-03-06 08:53:48', '2026-03-06 08:53:48'),
(12, '14141414', '$2y$10$veoYQHTMwzkDtCByliTCRObCNpR95XjeujKkX9g2sAuFUnsm0FnCi', 'MARTIN', 'MARCIAL MARTES', 19, '2026-03-06 18:25:14', '2026-03-06 18:25:14'),
(13, '15151515', '$2y$10$p0RgwV/bE2tAdyAv5L65tejaRfG16rL6./7GFm4fKcE753/in8HJC', 'SEGUNDO', 'SERENO SILVA', 19, '2026-03-06 19:07:28', '2026-03-06 19:07:28'),
(14, '16161616', '$2y$10$Qr9i3TrZLjsz.NLdLMKsieGa.piJQ/KHCRKzr7HT0VWtrvjNYN5Pa', 'CARLA', 'SEIJAS ZARATE', 19, '2026-03-06 19:08:11', '2026-03-06 19:08:11'),
(15, '17171717', '$2y$10$k00/X42F727.cXMa7j4nw.8MehuK16Xfr6KRpcz.ycXPIM9rxc.AC', 'ANGELA', 'VIDAURRE VASQUEZ', 19, '2026-03-07 16:35:07', '2026-03-07 16:35:07'),
(16, '18181818', '$2y$10$cfsRAXaihWi/3Myz7YU90eehYIpLGy.keu0DcjDFejY7YWd630yK2', 'CESAR', 'MARQUEZ PAREDES', 19, '2026-03-12 10:13:19', '2026-03-12 10:13:19'),
(17, '47305338', '$2y$10$/KrcFAFoOm0a0lvLUNZWse36k9yEZXqZgyGnHibqnc.26Mdf6Th2K', 'KARLA HELEN', 'BELTRÁN ARANDA', 20, '2026-03-16 00:56:59', '2026-03-16 00:56:59'),
(18, '75806539', '$2y$10$I8WnZbbySbz4LoeVj.3GZOA5by/uQPaM5vFu/ZJs/NZAWGqXgyiZy', 'ANDY JAVIER', 'ROJAS CUBAS', 20, '2026-03-16 00:57:30', '2026-03-16 00:57:30'),
(19, '71252952', '$2y$10$hrRJ47PkHCzlJTZayu0e8.0Eo2BdcLHmcuubj/qO8Ymsjvvnu/Y6.', 'JHEFERSON ALESSANDRO', 'RODRIGUEZ PAREDES', 20, '2026-03-16 00:57:55', '2026-03-16 00:57:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mtp_usuario_roles`
--

CREATE TABLE `mtp_usuario_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_rol` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `mtp_usuario_roles`
--

INSERT INTO `mtp_usuario_roles` (`id`, `id_usuario`, `id_rol`) VALUES
(4, 1, 1),
(10, 2, 4),
(12, 3, 4),
(15, 4, 1),
(20, 5, 2),
(17, 6, 4),
(19, 7, 4),
(21, 8, 4),
(22, 9, 4),
(23, 10, 4),
(24, 11, 7),
(27, 12, 7),
(28, 13, 7),
(29, 14, 7),
(30, 15, 7),
(31, 16, 2),
(32, 17, 2),
(35, 18, 4),
(36, 19, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pb_etiquetas`
--

CREATE TABLE `pb_etiquetas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pb_etiquetas`
--

INSERT INTO `pb_etiquetas` (`id`, `nombre`) VALUES
(1, 'hola');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pb_grupos`
--

CREATE TABLE `pb_grupos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `layout_slots` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pb_grupos`
--

INSERT INTO `pb_grupos` (`id`, `nombre`, `layout_slots`, `activo`, `creado`, `actualizado`) VALUES
(1, 'aGMR', 2, 1, '2026-03-10 12:26:50', '2026-03-10 12:28:45'),
(2, 'HHH', 2, 1, '2026-03-10 12:31:10', '2026-03-10 12:31:10'),
(3, 'GRUPO01', 2, 1, '2026-03-10 12:33:46', '2026-03-10 12:33:46'),
(4, 'GRUPO TEST 01', 1, 1, '2026-03-11 12:28:47', '2026-03-11 12:28:47'),
(5, 'Clientes Cartavio', 2, 1, '2026-03-11 12:43:59', '2026-03-11 12:44:53'),
(6, 'Clientes Cartavio', 4, 1, '2026-03-11 12:45:58', '2026-03-11 12:45:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pb_grupo_item`
--

CREATE TABLE `pb_grupo_item` (
  `id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `publicidad_id` int(10) UNSIGNED NOT NULL,
  `orden` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pb_grupo_item`
--

INSERT INTO `pb_grupo_item` (`id`, `grupo_id`, `publicidad_id`, `orden`) VALUES
(1, 4, 4, 1),
(2, 5, 5, 1),
(3, 5, 4, 2),
(4, 5, 6, 3),
(5, 5, 2, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pb_grupo_target`
--

CREATE TABLE `pb_grupo_target` (
  `id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('TODOS','USUARIO','ROL','EMPRESA','EMPRESA_ROL') NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `rol_id` int(10) UNSIGNED DEFAULT NULL,
  `empresa_id` int(10) UNSIGNED DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pb_grupo_target`
--

INSERT INTO `pb_grupo_target` (`id`, `grupo_id`, `tipo`, `usuario_id`, `rol_id`, `empresa_id`, `creado`, `actualizado`) VALUES
(2, 2, 'TODOS', NULL, NULL, NULL, '2026-03-10 12:31:32', '2026-03-10 12:31:32'),
(3, 3, 'TODOS', NULL, NULL, NULL, '2026-03-10 12:36:09', '2026-03-10 12:36:09'),
(5, 4, 'EMPRESA_ROL', NULL, 7, 19, '2026-03-11 12:29:46', '2026-03-11 12:29:46'),
(6, 5, 'EMPRESA_ROL', NULL, 7, 19, '2026-03-11 12:44:20', '2026-03-11 12:44:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pb_publicidades`
--

CREATE TABLE `pb_publicidades` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(300) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen_path` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pb_publicidades`
--

INSERT INTO `pb_publicidades` (`id`, `titulo`, `descripcion`, `imagen_path`, `activo`, `creado`, `actualizado`) VALUES
(1, 'LSISTEMAS Publi Fb', 'Publicidad definida por el area de marketing de guia mis rutas', 'almacen/img_publicidades/20260310-lsistemas-publi-fb-pub000001.jpg', 1, '2026-03-10 12:26:17', '2026-03-10 12:26:17'),
(2, 'SSSS', 'SSSS', 'almacen/img_publicidades/20260310-ssss-pub000002.jpg', 1, '2026-03-10 12:30:55', '2026-03-10 12:30:55'),
(3, 'FFF', 'PUBLICIDAD', 'almacen/img_publicidades/20260310-fff-pub000003.jpg', 1, '2026-03-10 12:33:09', '2026-03-10 12:33:09'),
(4, 'TEST PUB 01', 'TEST PUB 01TEST PUB 01TEST PUB 01', 'almacen/img_publicidades/20260311-test-pub-01-pub000004.jpg', 1, '2026-03-11 12:28:20', '2026-03-11 12:40:17'),
(5, 'GCPiura_11-03-26', '', 'almacen/img_publicidades/20260311-gcpiura-11-03-26-pub000005.jpg', 1, '2026-03-11 12:43:40', '2026-03-11 12:43:40'),
(6, 'aaaaaa', '', 'almacen/img_publicidades/20260311-aaaaaa-pub000006.jpg', 1, '2026-03-11 12:45:52', '2026-03-11 12:45:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pb_publicidad_etiqueta`
--

CREATE TABLE `pb_publicidad_etiqueta` (
  `publicidad_id` int(10) UNSIGNED NOT NULL,
  `etiqueta_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pb_publicidad_etiqueta`
--

INSERT INTO `pb_publicidad_etiqueta` (`publicidad_id`, `etiqueta_id`) VALUES
(4, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pb_publicidad_target`
--

CREATE TABLE `pb_publicidad_target` (
  `id` int(10) UNSIGNED NOT NULL,
  `publicidad_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('TODOS','USUARIO','ROL','EMPRESA','EMPRESA_ROL') NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `rol_id` int(10) UNSIGNED DEFAULT NULL,
  `empresa_id` int(10) UNSIGNED DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pb_publicidad_target`
--

INSERT INTO `pb_publicidad_target` (`id`, `publicidad_id`, `tipo`, `usuario_id`, `rol_id`, `empresa_id`, `creado`, `actualizado`) VALUES
(1, 1, 'EMPRESA', NULL, NULL, 19, '2026-03-10 12:26:37', '2026-03-10 12:26:37'),
(2, 1, 'TODOS', NULL, NULL, NULL, '2026-03-10 12:29:48', '2026-03-10 12:29:48'),
(3, 2, 'EMPRESA_ROL', NULL, 7, 19, '2026-03-10 12:32:08', '2026-03-10 12:32:08'),
(4, 3, 'EMPRESA_ROL', NULL, 7, 19, '2026-03-10 12:33:26', '2026-03-10 12:33:26'),
(5, 3, 'EMPRESA_ROL', NULL, 7, 1, '2026-03-10 12:34:02', '2026-03-10 12:34:02'),
(6, 3, 'TODOS', NULL, NULL, NULL, '2026-03-10 12:34:30', '2026-03-10 12:34:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_abonos`
--

CREATE TABLE `pos_abonos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `caja_diaria_id` int(10) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `medio_id` int(10) UNSIGNED NOT NULL,
  `fecha` datetime NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `referencia` varchar(80) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_abonos`
--

INSERT INTO `pos_abonos` (`id`, `id_empresa`, `caja_diaria_id`, `cliente_id`, `medio_id`, `fecha`, `monto`, `referencia`, `observacion`, `creado_por`, `creado`, `actualizado`) VALUES
(1, 19, 1, 3, 1, '2026-03-04 00:34:34', 500.00, NULL, 'Saldado completo', 10, '2026-03-04 00:34:34', '2026-03-04 00:34:34'),
(2, 19, 2, 4, 1, '2026-03-04 10:05:28', 200.00, NULL, 'Mañana paga el resto.', 10, '2026-03-04 10:05:28', '2026-03-04 10:05:28'),
(3, 19, 2, 5, 1, '2026-03-04 11:25:54', 1200.00, NULL, NULL, 10, '2026-03-04 11:25:54', '2026-03-04 11:25:54'),
(4, 19, 2, 6, 1, '2026-03-04 13:29:25', 500.00, NULL, NULL, 10, '2026-03-04 13:29:25', '2026-03-04 13:29:25'),
(5, 19, 2, 3, 2, '2026-03-04 13:32:25', 500.00, '1234', NULL, 10, '2026-03-04 13:32:25', '2026-03-04 13:32:25'),
(6, 19, 2, 7, 3, '2026-03-04 13:35:38', 1200.00, 'BIICAA', NULL, 10, '2026-03-04 13:35:38', '2026-03-04 13:35:38'),
(7, 19, 2, 8, 1, '2026-03-04 13:38:28', 1200.00, NULL, NULL, 10, '2026-03-04 13:38:28', '2026-03-04 13:38:28'),
(8, 19, 2, 9, 2, '2026-03-04 14:16:27', 1200.00, 'A22 A33', 'PAGO CON 2 COMPROBANTES', 10, '2026-03-04 14:16:27', '2026-03-04 14:16:27'),
(9, 19, 2, 10, 4, '2026-03-04 15:15:37', 1200.00, 'aaaaaaaaaaaaa3233', NULL, 10, '2026-03-04 15:15:37', '2026-03-04 15:15:37'),
(10, 19, 2, 11, 1, '2026-03-04 15:23:39', 1200.00, NULL, NULL, 10, '2026-03-04 15:23:39', '2026-03-04 15:23:39'),
(11, 19, 2, 12, 1, '2026-03-04 15:53:20', 1200.00, NULL, NULL, 10, '2026-03-04 15:53:20', '2026-03-04 15:53:20'),
(12, 19, 2, 13, 1, '2026-03-04 16:03:27', 500.00, NULL, NULL, 10, '2026-03-04 16:03:27', '2026-03-04 16:03:27'),
(13, 19, 2, 14, 1, '2026-03-04 16:04:52', 500.00, NULL, NULL, 10, '2026-03-04 16:04:52', '2026-03-04 16:04:52'),
(14, 19, 2, 15, 1, '2026-03-04 16:08:54', 500.00, NULL, NULL, 10, '2026-03-04 16:08:54', '2026-03-04 16:08:54'),
(15, 19, 2, 16, 1, '2026-03-04 16:16:37', 1200.00, NULL, NULL, 10, '2026-03-04 16:16:37', '2026-03-04 16:16:37'),
(16, 19, 2, 17, 1, '2026-03-04 16:18:00', 500.00, NULL, NULL, 10, '2026-03-04 16:18:00', '2026-03-04 16:18:00'),
(17, 19, 2, 18, 3, '2026-03-04 21:29:45', 1000.00, 'ABB3', 'DEBE 200', 10, '2026-03-04 21:29:45', '2026-03-04 21:29:45'),
(18, 19, 3, 19, 1, '2026-03-05 09:42:20', 500.00, 'aaaaa', NULL, 10, '2026-03-05 09:42:20', '2026-03-05 09:42:20'),
(19, 19, 3, 20, 1, '2026-03-05 09:56:05', 500.00, NULL, NULL, 10, '2026-03-05 09:56:05', '2026-03-05 09:56:05'),
(20, 19, 3, 21, 1, '2026-03-05 13:40:04', 1200.00, NULL, NULL, 10, '2026-03-05 13:40:04', '2026-03-05 13:40:04'),
(21, 19, 3, 22, 2, '2026-03-05 13:42:27', 1200.00, 'aa3f-3', NULL, 10, '2026-03-05 13:42:27', '2026-03-05 13:42:27'),
(22, 19, 3, 23, 3, '2026-03-05 15:23:03', 500.00, 'ABD-456', NULL, 10, '2026-03-05 15:23:03', '2026-03-05 15:23:03'),
(23, 19, 3, 23, 2, '2026-03-05 15:24:46', 100.00, 'aaa-3', NULL, 10, '2026-03-05 15:24:46', '2026-03-05 15:24:46'),
(24, 19, 3, 23, 4, '2026-03-05 15:25:52', 100.00, '5445565665', NULL, 10, '2026-03-05 15:25:52', '2026-03-05 15:25:52'),
(25, 19, 3, 24, 2, '2026-03-05 16:09:22', 1200.00, 'ABV-33', NULL, 10, '2026-03-05 16:09:22', '2026-03-05 16:09:22'),
(26, 19, 3, 23, 1, '2026-03-05 16:10:18', 12.00, NULL, NULL, 10, '2026-03-05 16:10:18', '2026-03-05 16:10:18'),
(27, 19, 3, 25, 3, '2026-03-05 16:19:50', 1000.00, 'ave-33', NULL, 10, '2026-03-05 16:19:50', '2026-03-05 16:19:50'),
(28, 19, 4, 26, 4, '2026-03-06 17:46:04', 4800.00, 'ASDA-65653', NULL, 10, '2026-03-06 17:46:04', '2026-03-06 17:46:04'),
(29, 19, 5, 27, 1, '2026-03-10 09:16:36', 500.00, NULL, NULL, 10, '2026-03-10 09:16:36', '2026-03-10 09:16:36'),
(30, 19, 5, 28, 2, '2026-03-10 09:27:00', 500.00, 'FAV-333', NULL, 10, '2026-03-10 09:27:00', '2026-03-10 09:27:00'),
(31, 19, 5, 29, 2, '2026-03-10 11:34:08', 700.00, '123', NULL, 10, '2026-03-10 11:34:08', '2026-03-10 11:34:08'),
(32, 19, 5, 29, 3, '2026-03-10 11:34:08', 500.00, '1452', NULL, 10, '2026-03-10 11:34:08', '2026-03-10 11:34:08'),
(33, 19, 5, 30, 1, '2026-03-10 11:35:57', 300.00, NULL, NULL, 10, '2026-03-10 11:35:57', '2026-03-10 11:35:57'),
(34, 19, 5, 30, 4, '2026-03-10 11:37:26', 200.00, '3332HH2J', NULL, 10, '2026-03-10 11:37:26', '2026-03-10 11:37:26'),
(35, 19, 5, 31, 1, '2026-03-10 11:43:19', 1200.00, NULL, NULL, 10, '2026-03-10 11:43:19', '2026-03-10 11:43:19'),
(36, 19, 6, 32, 1, '2026-03-12 22:58:24', 1200.00, NULL, NULL, 10, '2026-03-12 22:58:24', '2026-03-12 22:58:24'),
(37, 19, 6, 34, 1, '2026-03-12 23:01:16', 500.00, NULL, NULL, 10, '2026-03-12 23:01:16', '2026-03-12 23:01:16'),
(38, 19, 6, 35, 1, '2026-03-12 23:59:00', 1200.00, NULL, NULL, 10, '2026-03-12 23:59:00', '2026-03-12 23:59:00'),
(39, 19, 7, 38, 1, '2026-03-13 01:41:01', 300.00, NULL, NULL, 10, '2026-03-13 01:41:01', '2026-03-13 01:41:01'),
(40, 19, 7, 38, 2, '2026-03-13 01:41:01', 100.00, 'ac-3', NULL, 10, '2026-03-13 01:41:01', '2026-03-13 01:41:01'),
(41, 19, 7, 39, 4, '2026-03-13 01:43:02', 150.00, 'dasdas-sdsd', NULL, 10, '2026-03-13 01:43:02', '2026-03-13 01:43:02'),
(42, 19, 7, 38, 4, '2026-03-13 02:56:04', 50.00, 'ffffff44', NULL, 10, '2026-03-13 02:56:04', '2026-03-13 02:56:04'),
(43, 19, 7, 38, 3, '2026-03-13 07:56:54', 50.00, 'asds-343', NULL, 10, '2026-03-13 07:56:54', '2026-03-13 07:56:54'),
(44, 19, 7, 40, 1, '2026-03-13 08:03:04', 150.00, NULL, NULL, 10, '2026-03-13 08:03:04', '2026-03-13 08:03:04'),
(45, 19, 7, 41, 1, '2026-03-13 10:52:41', 10.00, NULL, NULL, 10, '2026-03-13 10:52:41', '2026-03-13 10:52:41'),
(46, 19, 7, 42, 4, '2026-03-13 10:56:42', 500.00, 'Nro: 3383993-eer', 'els eñorpago con interbank.', 10, '2026-03-13 10:56:42', '2026-03-13 10:56:42'),
(47, 19, 7, 42, 3, '2026-03-13 10:58:14', 400.00, '33-ff', NULL, 10, '2026-03-13 10:58:14', '2026-03-13 10:58:14'),
(48, 19, 7, 43, 2, '2026-03-13 11:04:41', 100.00, 'COD:234', NULL, 10, '2026-03-13 11:04:41', '2026-03-13 11:04:41'),
(49, 19, 7, 43, 3, '2026-03-13 11:04:41', 50.00, '1233-DS', NULL, 10, '2026-03-13 11:04:41', '2026-03-13 11:04:41'),
(50, 19, 7, 44, 1, '2026-03-13 11:10:18', 200.00, NULL, NULL, 10, '2026-03-13 11:10:18', '2026-03-13 11:10:18'),
(51, 19, 7, 44, 2, '2026-03-13 11:10:18', 200.00, 'ASD3', NULL, 10, '2026-03-13 11:10:18', '2026-03-13 11:10:18'),
(52, 19, 7, 44, 3, '2026-03-13 11:10:18', 500.00, 'ASDAS', NULL, 10, '2026-03-13 11:10:18', '2026-03-13 11:10:18'),
(53, 19, 7, 44, 4, '2026-03-13 11:10:18', 200.00, 'BCP', NULL, 10, '2026-03-13 11:10:18', '2026-03-13 11:10:18'),
(54, 19, 7, 37, 1, '2026-03-13 11:13:06', 150.00, NULL, NULL, 10, '2026-03-13 11:13:06', '2026-03-13 11:13:06'),
(55, 19, 7, 45, 4, '2026-03-13 15:27:42', 150.00, 'jhg-76', NULL, 10, '2026-03-13 15:27:42', '2026-03-13 15:27:42'),
(56, 19, 7, 40, 1, '2026-03-13 21:31:29', 50.00, NULL, NULL, 10, '2026-03-13 21:31:29', '2026-03-13 21:31:29'),
(57, 19, 7, 40, 2, '2026-03-13 21:31:29', 50.00, 'ffafa', NULL, 10, '2026-03-13 21:31:29', '2026-03-13 21:31:29'),
(58, 19, 7, 40, 3, '2026-03-13 21:31:29', 50.00, 'ffff', NULL, 10, '2026-03-13 21:31:29', '2026-03-13 21:31:29'),
(59, 19, 8, 46, 1, '2026-03-14 11:53:21', 500.00, 'ABC-HGF', NULL, 10, '2026-03-14 11:53:21', '2026-03-14 11:53:21'),
(60, 19, 8, 47, 1, '2026-03-14 11:54:45', 10.00, NULL, NULL, 10, '2026-03-14 11:54:45', '2026-03-14 11:54:45'),
(61, 19, 8, 48, 1, '2026-03-14 11:57:54', 1100.00, NULL, NULL, 10, '2026-03-14 11:57:54', '2026-03-14 11:57:54'),
(62, 19, 8, 49, 2, '2026-03-14 12:14:13', 500.00, 'af3-3f', NULL, 10, '2026-03-14 12:14:13', '2026-03-14 12:14:13'),
(63, 19, 8, 50, 4, '2026-03-14 12:34:19', 1200.00, 'aa-hyg', NULL, 10, '2026-03-14 12:34:19', '2026-03-14 12:34:19'),
(64, 19, 8, 51, 1, '2026-03-14 13:23:27', 120.00, NULL, NULL, 10, '2026-03-14 13:23:27', '2026-03-14 13:23:27'),
(65, 19, 8, 52, 3, '2026-03-14 13:25:06', 600.00, 'ac-7', NULL, 10, '2026-03-14 13:25:06', '2026-03-14 13:25:06'),
(66, 19, 8, 53, 2, '2026-03-14 13:28:30', 1000.00, 'gggg-gfgg', NULL, 10, '2026-03-14 13:28:30', '2026-03-14 13:28:30'),
(67, 19, 8, 54, 2, '2026-03-14 13:31:28', 500.00, 'asdsd', NULL, 10, '2026-03-14 13:31:28', '2026-03-14 13:31:28'),
(68, 19, 8, 54, 3, '2026-03-14 13:31:28', 500.00, '3f3', NULL, 10, '2026-03-14 13:31:28', '2026-03-14 13:31:28'),
(69, 19, 8, 54, 1, '2026-03-14 13:31:28', 500.00, NULL, NULL, 10, '2026-03-14 13:31:28', '2026-03-14 13:31:28'),
(70, 19, 8, 55, 1, '2026-03-14 19:34:48', 1100.00, NULL, NULL, 10, '2026-03-14 19:34:48', '2026-03-14 19:34:48'),
(71, 19, 8, 56, 1, '2026-03-14 19:40:16', 500.00, NULL, NULL, 10, '2026-03-14 19:40:16', '2026-03-14 19:40:16'),
(72, 19, 8, 57, 1, '2026-03-14 19:45:37', 1000.00, NULL, NULL, 10, '2026-03-14 19:45:37', '2026-03-14 19:45:37'),
(73, 19, 8, 58, 4, '2026-03-14 19:52:18', 600.00, 'ad2d3', NULL, 10, '2026-03-14 19:52:18', '2026-03-14 19:52:18'),
(74, 19, 8, 58, 1, '2026-03-14 19:56:31', 150.00, NULL, NULL, 10, '2026-03-14 19:56:31', '2026-03-14 19:56:31'),
(75, 19, 8, 40, 1, '2026-03-14 20:00:17', 5.00, NULL, NULL, 10, '2026-03-14 20:00:17', '2026-03-14 20:00:17'),
(76, 19, 8, 40, 1, '2026-03-14 20:00:51', 5.00, NULL, NULL, 10, '2026-03-14 20:00:51', '2026-03-14 20:00:51'),
(77, 19, 8, 59, 1, '2026-03-14 22:48:09', 1200.00, NULL, NULL, 10, '2026-03-14 22:48:09', '2026-03-14 22:48:09'),
(78, 19, 8, 60, 1, '2026-03-14 22:54:53', 100.00, NULL, NULL, 10, '2026-03-14 22:54:53', '2026-03-14 22:54:53'),
(79, 19, 8, 60, 2, '2026-03-14 22:55:12', 100.00, 'DD-DD', NULL, 10, '2026-03-14 22:55:12', '2026-03-14 22:55:12'),
(80, 19, 8, 60, 1, '2026-03-14 23:01:01', 20.00, NULL, NULL, 10, '2026-03-14 23:01:01', '2026-03-14 23:01:01'),
(81, 19, 8, 40, 1, '2026-03-15 01:24:18', 100.00, NULL, NULL, 10, '2026-03-15 01:24:18', '2026-03-15 01:24:18'),
(82, 19, 9, 40, 1, '2026-03-15 10:02:46', 150.00, NULL, NULL, 10, '2026-03-15 10:02:46', '2026-03-15 10:02:46'),
(83, 19, 9, 40, 1, '2026-03-15 10:06:22', 100.00, NULL, NULL, 10, '2026-03-15 10:06:22', '2026-03-15 10:06:22'),
(84, 19, 9, 40, 1, '2026-03-15 10:48:41', 1100.00, NULL, NULL, 10, '2026-03-15 10:48:41', '2026-03-15 10:48:41'),
(85, 19, 9, 61, 1, '2026-03-15 18:38:59', 1000.00, NULL, NULL, 10, '2026-03-15 18:38:59', '2026-03-15 18:38:59'),
(86, 19, 9, 62, 2, '2026-03-15 18:40:18', 500.00, 'addas', NULL, 10, '2026-03-15 18:40:18', '2026-03-15 18:40:18'),
(87, 19, 9, 62, 1, '2026-03-15 18:40:18', 500.00, NULL, NULL, 10, '2026-03-15 18:40:18', '2026-03-15 18:40:18'),
(88, 19, 9, 63, 4, '2026-03-15 18:42:13', 1000.00, 's22', NULL, 10, '2026-03-15 18:42:13', '2026-03-15 18:42:13'),
(89, 19, 9, 40, 1, '2026-03-15 18:42:38', 500.00, NULL, NULL, 10, '2026-03-15 18:42:38', '2026-03-15 18:42:38'),
(90, 19, 9, 20, 3, '2026-03-15 19:08:33', 500.00, 'VVV', NULL, 10, '2026-03-15 19:08:33', '2026-03-15 19:08:33'),
(91, 19, 9, 63, 1, '2026-03-15 22:17:28', 1200.00, NULL, NULL, 10, '2026-03-15 22:17:28', '2026-03-15 22:17:28'),
(92, 19, 10, 64, 1, '2026-03-16 09:43:57', 1200.00, NULL, NULL, 10, '2026-03-16 09:43:57', '2026-03-16 09:43:57'),
(93, 19, 10, 63, 1, '2026-03-16 10:13:58', 10.00, NULL, NULL, 10, '2026-03-16 10:13:58', '2026-03-16 10:13:58'),
(94, 20, 11, 65, 1, '2026-03-16 12:44:10', 40.00, NULL, 'Autorización Gerencia', 18, '2026-03-16 12:44:10', '2026-03-16 12:44:10'),
(95, 20, 11, 66, 1, '2026-03-16 14:52:01', 150.00, NULL, NULL, 19, '2026-03-16 14:52:01', '2026-03-16 14:52:01'),
(96, 20, 11, 65, 1, '2026-03-16 15:23:27', 50.00, NULL, NULL, 18, '2026-03-16 15:23:27', '2026-03-16 15:23:27'),
(97, 20, 11, 65, 1, '2026-03-16 15:24:55', 50.00, NULL, NULL, 18, '2026-03-16 15:24:55', '2026-03-16 15:24:55'),
(98, 20, 11, 65, 1, '2026-03-16 15:26:19', 40.00, NULL, NULL, 18, '2026-03-16 15:26:19', '2026-03-16 15:26:19'),
(99, 20, 11, 67, 1, '2026-03-16 15:29:14', 250.00, NULL, NULL, 18, '2026-03-16 15:29:14', '2026-03-16 15:29:14'),
(100, 20, 11, 68, 1, '2026-03-16 21:23:42', 50.00, NULL, NULL, 19, '2026-03-16 21:23:42', '2026-03-16 21:23:42'),
(101, 20, 11, 69, 2, '2026-03-16 21:27:01', 70.00, '58607569', NULL, 19, '2026-03-16 21:27:01', '2026-03-16 21:27:01'),
(102, 20, 11, 70, 2, '2026-03-16 21:28:50', 50.00, '0805905', NULL, 19, '2026-03-16 21:28:50', '2026-03-16 21:28:50'),
(103, 20, 11, 71, 1, '2026-03-16 21:30:25', 50.00, NULL, 'HONORES CHAVARRY ALFREDO', 19, '2026-03-16 21:30:25', '2026-03-16 21:30:25'),
(104, 20, 11, 71, 1, '2026-03-16 21:30:25', 50.00, NULL, 'HONORES CHAVARRY LUIS', 19, '2026-03-16 21:30:25', '2026-03-16 21:30:25'),
(105, 20, 11, 72, 1, '2026-03-16 21:31:18', 50.00, NULL, NULL, 19, '2026-03-16 21:31:18', '2026-03-16 21:31:18'),
(106, 20, 11, 73, 1, '2026-03-16 21:31:52', 50.00, NULL, NULL, 19, '2026-03-16 21:31:52', '2026-03-16 21:31:52'),
(107, 20, 11, 74, 1, '2026-03-16 21:33:54', 100.00, NULL, NULL, 19, '2026-03-16 21:33:54', '2026-03-16 21:33:54'),
(108, 20, 11, 74, 2, '2026-03-16 21:33:54', 30.00, '997', NULL, 19, '2026-03-16 21:33:54', '2026-03-16 21:33:54'),
(109, 20, 11, 75, 2, '2026-03-16 21:34:59', 130.00, '357', NULL, 19, '2026-03-16 21:34:59', '2026-03-16 21:34:59'),
(110, 20, 11, 76, 2, '2026-03-16 21:35:53', 130.00, '893', NULL, 19, '2026-03-16 21:35:53', '2026-03-16 21:35:53'),
(111, 20, 11, 77, 1, '2026-03-16 21:37:07', 120.00, NULL, NULL, 19, '2026-03-16 21:37:07', '2026-03-16 21:37:07'),
(112, 20, 11, 77, 2, '2026-03-16 21:37:07', 10.00, '423', NULL, 19, '2026-03-16 21:37:07', '2026-03-16 21:37:07'),
(113, 20, 11, 78, 1, '2026-03-16 21:37:39', 130.00, NULL, NULL, 19, '2026-03-16 21:37:39', '2026-03-16 21:37:39'),
(114, 20, 12, 79, 2, '2026-03-17 11:56:54', 50.00, '7:41', NULL, 18, '2026-03-17 11:56:54', '2026-03-17 11:56:54'),
(115, 20, 12, 80, 2, '2026-03-17 11:59:46', 70.00, '738', NULL, 18, '2026-03-17 11:59:46', '2026-03-17 11:59:46'),
(116, 20, 12, 81, 1, '2026-03-17 12:01:18', 50.00, NULL, NULL, 18, '2026-03-17 12:01:18', '2026-03-17 12:01:18'),
(117, 20, 12, 82, 1, '2026-03-17 12:03:58', 60.00, NULL, NULL, 18, '2026-03-17 12:03:58', '2026-03-17 12:03:58'),
(118, 20, 12, 83, 2, '2026-03-17 12:04:45', 60.00, '735', NULL, 18, '2026-03-17 12:04:45', '2026-03-17 12:04:45'),
(119, 20, 12, 84, 2, '2026-03-17 12:06:34', 50.00, '745', NULL, 18, '2026-03-17 12:06:34', '2026-03-17 12:06:34'),
(120, 20, 12, 85, 2, '2026-03-17 12:08:01', 60.00, '726', NULL, 18, '2026-03-17 12:08:01', '2026-03-17 12:08:01'),
(121, 19, 13, 63, 1, '2026-03-17 14:10:10', 500.00, NULL, NULL, 10, '2026-03-17 14:10:10', '2026-03-17 14:10:10'),
(122, 19, 13, 63, 1, '2026-03-17 14:34:16', 150.00, NULL, NULL, 10, '2026-03-17 14:34:16', '2026-03-17 14:34:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_abono_aplicaciones`
--

CREATE TABLE `pos_abono_aplicaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `abono_id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `monto_aplicado` decimal(14,2) NOT NULL,
  `aplicado_en` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_abono_aplicaciones`
--

INSERT INTO `pos_abono_aplicaciones` (`id`, `abono_id`, `venta_id`, `monto_aplicado`, `aplicado_en`) VALUES
(1, 1, 1, 500.00, '2026-03-04 00:34:34'),
(2, 2, 2, 200.00, '2026-03-04 10:05:28'),
(3, 3, 3, 1200.00, '2026-03-04 11:25:54'),
(4, 4, 4, 500.00, '2026-03-04 13:29:25'),
(5, 5, 5, 500.00, '2026-03-04 13:32:25'),
(6, 6, 6, 1200.00, '2026-03-04 13:35:38'),
(7, 7, 7, 1200.00, '2026-03-04 13:38:28'),
(8, 8, 8, 1200.00, '2026-03-04 14:16:27'),
(9, 9, 9, 1200.00, '2026-03-04 15:15:37'),
(10, 10, 10, 1200.00, '2026-03-04 15:23:39'),
(11, 11, 11, 1200.00, '2026-03-04 15:53:20'),
(12, 12, 12, 500.00, '2026-03-04 16:03:27'),
(13, 13, 13, 500.00, '2026-03-04 16:04:52'),
(14, 14, 14, 500.00, '2026-03-04 16:08:54'),
(15, 15, 15, 1200.00, '2026-03-04 16:16:37'),
(16, 16, 16, 500.00, '2026-03-04 16:18:00'),
(17, 17, 17, 1000.00, '2026-03-04 21:29:45'),
(18, 18, 18, 500.00, '2026-03-05 09:42:20'),
(19, 19, 19, 500.00, '2026-03-05 09:56:05'),
(20, 20, 20, 1200.00, '2026-03-05 13:40:04'),
(21, 21, 21, 1200.00, '2026-03-05 13:42:27'),
(22, 22, 22, 500.00, '2026-03-05 15:23:03'),
(23, 23, 22, 100.00, '2026-03-05 15:24:46'),
(24, 24, 22, 100.00, '2026-03-05 15:25:52'),
(25, 25, 23, 1200.00, '2026-03-05 16:09:22'),
(26, 26, 22, 12.00, '2026-03-05 16:10:18'),
(27, 27, 24, 1000.00, '2026-03-05 16:19:50'),
(28, 28, 25, 4800.00, '2026-03-06 17:46:04'),
(29, 29, 26, 500.00, '2026-03-10 09:16:36'),
(30, 30, 27, 500.00, '2026-03-10 09:27:00'),
(31, 31, 28, 700.00, '2026-03-10 11:34:08'),
(32, 32, 28, 500.00, '2026-03-10 11:34:08'),
(33, 33, 29, 300.00, '2026-03-10 11:35:57'),
(34, 34, 29, 200.00, '2026-03-10 11:37:26'),
(35, 35, 30, 1200.00, '2026-03-10 11:43:19'),
(36, 36, 36, 1200.00, '2026-03-12 22:58:24'),
(37, 37, 37, 500.00, '2026-03-12 23:01:16'),
(38, 38, 38, 1200.00, '2026-03-12 23:59:00'),
(39, 39, 41, 300.00, '2026-03-13 01:41:01'),
(40, 40, 41, 100.00, '2026-03-13 01:41:01'),
(41, 41, 42, 150.00, '2026-03-13 01:43:02'),
(42, 42, 41, 50.00, '2026-03-13 02:56:04'),
(43, 43, 41, 50.00, '2026-03-13 07:56:54'),
(44, 44, 43, 150.00, '2026-03-13 08:03:04'),
(45, 45, 44, 10.00, '2026-03-13 10:52:41'),
(46, 46, 45, 500.00, '2026-03-13 10:56:42'),
(47, 47, 45, 400.00, '2026-03-13 10:58:14'),
(48, 48, 46, 100.00, '2026-03-13 11:04:41'),
(49, 49, 46, 50.00, '2026-03-13 11:04:41'),
(50, 50, 47, 200.00, '2026-03-13 11:10:18'),
(51, 51, 47, 200.00, '2026-03-13 11:10:18'),
(52, 52, 47, 500.00, '2026-03-13 11:10:18'),
(53, 53, 47, 200.00, '2026-03-13 11:10:18'),
(54, 54, 40, 150.00, '2026-03-13 11:13:06'),
(55, 55, 48, 150.00, '2026-03-13 15:27:42'),
(56, 56, 49, 50.00, '2026-03-13 21:31:29'),
(57, 57, 49, 50.00, '2026-03-13 21:31:29'),
(58, 58, 49, 50.00, '2026-03-13 21:31:29'),
(59, 59, 50, 500.00, '2026-03-14 11:53:21'),
(60, 60, 51, 10.00, '2026-03-14 11:54:45'),
(61, 61, 52, 1100.00, '2026-03-14 11:57:54'),
(62, 62, 53, 500.00, '2026-03-14 12:14:13'),
(63, 63, 54, 1200.00, '2026-03-14 12:34:19'),
(64, 64, 55, 120.00, '2026-03-14 13:23:27'),
(65, 65, 56, 600.00, '2026-03-14 13:25:06'),
(66, 66, 57, 1000.00, '2026-03-14 13:28:30'),
(67, 67, 58, 500.00, '2026-03-14 13:31:28'),
(68, 68, 58, 500.00, '2026-03-14 13:31:28'),
(69, 69, 58, 500.00, '2026-03-14 13:31:28'),
(70, 70, 59, 1100.00, '2026-03-14 19:34:48'),
(71, 71, 60, 500.00, '2026-03-14 19:40:16'),
(72, 72, 61, 1000.00, '2026-03-14 19:45:37'),
(73, 73, 62, 600.00, '2026-03-14 19:52:18'),
(74, 74, 63, 150.00, '2026-03-14 19:56:31'),
(75, 75, 64, 5.00, '2026-03-14 20:00:17'),
(76, 76, 64, 5.00, '2026-03-14 20:00:51'),
(77, 77, 65, 1200.00, '2026-03-14 22:48:09'),
(78, 78, 66, 100.00, '2026-03-14 22:54:53'),
(79, 79, 66, 100.00, '2026-03-14 22:55:12'),
(80, 80, 66, 20.00, '2026-03-14 23:01:01'),
(81, 81, 67, 100.00, '2026-03-15 01:24:18'),
(82, 82, 68, 150.00, '2026-03-15 10:02:46'),
(83, 83, 68, 100.00, '2026-03-15 10:06:22'),
(84, 84, 69, 1100.00, '2026-03-15 10:48:41'),
(85, 85, 70, 1000.00, '2026-03-15 18:38:59'),
(86, 86, 71, 500.00, '2026-03-15 18:40:18'),
(87, 87, 71, 500.00, '2026-03-15 18:40:18'),
(88, 88, 72, 1000.00, '2026-03-15 18:42:13'),
(89, 89, 69, 500.00, '2026-03-15 18:42:38'),
(90, 90, 73, 500.00, '2026-03-15 19:08:33'),
(91, 91, 74, 1200.00, '2026-03-15 22:17:28'),
(92, 92, 75, 1200.00, '2026-03-16 09:43:57'),
(93, 93, 76, 10.00, '2026-03-16 10:13:58'),
(94, 94, 77, 40.00, '2026-03-16 12:44:10'),
(95, 95, 78, 150.00, '2026-03-16 14:52:01'),
(96, 96, 79, 50.00, '2026-03-16 15:23:27'),
(97, 97, 80, 50.00, '2026-03-16 15:24:55'),
(98, 98, 81, 40.00, '2026-03-16 15:26:19'),
(99, 99, 82, 250.00, '2026-03-16 15:29:14'),
(100, 100, 83, 50.00, '2026-03-16 21:23:42'),
(101, 101, 84, 70.00, '2026-03-16 21:27:01'),
(102, 102, 85, 50.00, '2026-03-16 21:28:50'),
(103, 103, 86, 50.00, '2026-03-16 21:30:25'),
(104, 104, 86, 50.00, '2026-03-16 21:30:25'),
(105, 105, 87, 50.00, '2026-03-16 21:31:18'),
(106, 106, 88, 50.00, '2026-03-16 21:31:52'),
(107, 107, 89, 100.00, '2026-03-16 21:33:54'),
(108, 108, 89, 30.00, '2026-03-16 21:33:54'),
(109, 109, 90, 130.00, '2026-03-16 21:34:59'),
(110, 110, 91, 130.00, '2026-03-16 21:35:53'),
(111, 111, 92, 120.00, '2026-03-16 21:37:07'),
(112, 112, 92, 10.00, '2026-03-16 21:37:07'),
(113, 113, 93, 130.00, '2026-03-16 21:37:39'),
(114, 114, 94, 50.00, '2026-03-17 11:56:54'),
(115, 115, 95, 70.00, '2026-03-17 11:59:46'),
(116, 116, 96, 50.00, '2026-03-17 12:01:18'),
(117, 117, 97, 60.00, '2026-03-17 12:03:58'),
(118, 118, 98, 60.00, '2026-03-17 12:04:45'),
(119, 119, 99, 50.00, '2026-03-17 12:06:34'),
(120, 120, 100, 60.00, '2026-03-17 12:08:01'),
(121, 121, 101, 500.00, '2026-03-17 14:10:10'),
(122, 122, 102, 150.00, '2026-03-17 14:34:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_auditoria`
--

CREATE TABLE `pos_auditoria` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `tabla` varchar(40) NOT NULL,
  `registro_id` bigint(20) UNSIGNED NOT NULL,
  `evento` varchar(40) NOT NULL,
  `datos` text DEFAULT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_usuario` varchar(64) DEFAULT NULL,
  `actor_nombre` varchar(150) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `creado_en` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_auditoria`
--

INSERT INTO `pos_auditoria` (`id`, `id_empresa`, `tabla`, `registro_id`, `evento`, `datos`, `actor_id`, `actor_usuario`, `actor_nombre`, `ip`, `creado_en`) VALUES
(1, 19, 'pos_ventas', 1, 'VENTA_CREADA', NULL, 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 00:34:34'),
(2, 19, 'pos_ventas', 2, 'VENTA_CREADA', NULL, 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 10:05:28'),
(3, 19, 'pos_ventas', 3, 'VENTA_CREADA', NULL, 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 11:25:54'),
(4, 19, 'pos_ventas', 4, 'VENTA_CREADA', '{\"cliente\":{\"id\":6,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"52412514\",\"nombre\":\"KIMBERLY FLORES LOPEZ\",\"telefono\":\"965252145\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"52412514\",\"nombres\":\"KIMBERLY\",\"apellidos\":\"FLORES LOPEZ\",\"telefono\":\"965252145\"},\"venta\":{\"caja_diaria_id\":2,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 13:29:25'),
(5, 19, 'pos_ventas', 5, 'VENTA_CREADA', '{\"cliente\":{\"id\":3,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70366365\",\"nombre\":\"Maria Lopez\",\"telefono\":\"966362532\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":6,\"doc_tipo\":\"DNI\",\"doc_numero\":\"44556677\",\"nombres\":\"Juan\",\"apellidos\":\"Perez\",\"telefono\":\"966363623\"},\"venta\":{\"caja_diaria_id\":2,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 13:32:25'),
(6, 19, 'pos_ventas', 6, 'VENTA_CREADA', '{\"cliente\":{\"id\":7,\"tipo_persona\":\"JURIDICA\",\"doc_tipo\":\"RUC\",\"doc_numero\":\"20603562514\",\"nombre\":\"EMPRESA CONSTRUCTORA SAC\",\"telefono\":\"9\"},\"contratante\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"58585474\",\"nombres\":\"Luis\",\"apellidos\":\"Aguilar\",\"telefono\":\"9\"},\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":7,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70363636\",\"nombres\":\"Americo\",\"apellidos\":\"Barrios Canto\",\"telefono\":\"966366632\"},\"venta\":{\"caja_diaria_id\":2,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 13:35:38'),
(7, 19, 'pos_ventas', 7, 'VENTA_CREADA', '{\"cliente\":{\"id\":8,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"CE\",\"doc_numero\":\"48392716\",\"nombre\":\"Diego Ramírez Soto\",\"telefono\":\"912345678\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"CE\",\"doc_numero\":\"48392716\",\"nombres\":\"Diego\",\"apellidos\":\"Ramírez Soto\",\"telefono\":\"912345678\"},\"venta\":{\"caja_diaria_id\":2,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 13:38:28'),
(8, 19, 'pos_ventas', 8, 'VENTA_CREADA', '{\"cliente\":{\"id\":9,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"41414251\",\"nombre\":\"JULIO VELASQUEZ QUESQUEN\",\"telefono\":\"966565214\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":8,\"doc_tipo\":\"DNI\",\"doc_numero\":\"41414525\",\"nombres\":\"ANA LUCIA\",\"apellidos\":\"JARA PEREZ\",\"telefono\":\"966363636\"},\"venta\":{\"caja_diaria_id\":2,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 14:16:27'),
(9, 19, 'pos_ventas', 9, 'VENTA_CREADA', '{\"cliente\":{\"id\":10,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70111141\",\"nombre\":\"Maricarmen Villalobos Alfaro\",\"telefono\":\"9633632541\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70111141\",\"nombres\":\"Maricarmen\",\"apellidos\":\"Villalobos Alfaro\",\"telefono\":\"9633632541\"},\"venta\":{\"caja_diaria_id\":2,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '38.253.190.90', '2026-03-04 15:15:37'),
(10, 19, 'pos_ventas', 10, 'VENTA_CREADA', '{\"cliente\":{\"id\":11,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"B63635478\",\"nombre\":\"MARIA ELENA COBARRUBIAS ALVA\",\"telefono\":\"966663574\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"B63635478\",\"nombres\":\"MARIA ELENA\",\"apellidos\":\"COBARRUBIAS ALVA\",\"telefono\":\"966663574\"},\"venta\":{\"caja_diaria_id\":2,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 15:23:39'),
(11, 19, 'pos_ventas', 11, 'VENTA_CREADA', '{\"cliente\":{\"id\":12,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70414215\",\"nombre\":\"CAMILA PAREDES GONZALES\",\"telefono\":\"966565415\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70414215\",\"nombres\":\"CAMILA\",\"apellidos\":\"PAREDES GONZALES\",\"telefono\":\"966565415\"},\"venta\":{\"caja_diaria_id\":2,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 15:53:20'),
(12, 19, 'pos_ventas', 12, 'VENTA_CREADA', '{\"cliente\":{\"id\":13,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"41141251\",\"nombre\":\"CRISTIANM CASTRO CARRILLO\",\"telefono\":\"965211412\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"41141251\",\"nombres\":\"CRISTIANM\",\"apellidos\":\"CASTRO CARRILLO\",\"telefono\":\"965211412\"},\"venta\":{\"caja_diaria_id\":2,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 16:03:27'),
(13, 19, 'pos_ventas', 13, 'VENTA_CREADA', '{\"cliente\":{\"id\":14,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"71114121\",\"nombre\":\"julian juarez juvenal\",\"telefono\":\"963323214\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"71114121\",\"nombres\":\"julian\",\"apellidos\":\"juarez juvenal\",\"telefono\":\"963323214\"},\"venta\":{\"caja_diaria_id\":2,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 16:04:52'),
(14, 19, 'pos_ventas', 14, 'VENTA_CREADA', '{\"cliente\":{\"id\":15,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70414125\",\"nombre\":\"ALBERTO BARROS BAILON\",\"telefono\":\"966363251\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70414125\",\"nombres\":\"ALBERTO\",\"apellidos\":\"BARROS BAILON\",\"telefono\":\"966363251\"},\"venta\":{\"caja_diaria_id\":2,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 16:08:54'),
(15, 19, 'pos_ventas', 15, 'VENTA_CREADA', '{\"cliente\":{\"id\":16,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70252541\",\"nombre\":\"melisa perez juarez\",\"telefono\":\"963323214\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70252541\",\"nombres\":\"melisa\",\"apellidos\":\"perez juarez\",\"telefono\":\"963323214\"},\"venta\":{\"caja_diaria_id\":2,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 16:16:37'),
(16, 19, 'pos_ventas', 16, 'VENTA_CREADA', '{\"cliente\":{\"id\":17,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70333236\",\"nombre\":\"ROBERTO BLADES JUAREZ\",\"telefono\":\"965554474\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70333236\",\"nombres\":\"ROBERTO\",\"apellidos\":\"BLADES JUAREZ\",\"telefono\":\"965554474\"},\"venta\":{\"caja_diaria_id\":2,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 16:18:00'),
(17, 19, 'pos_ventas', 17, 'VENTA_CREADA', '{\"cliente\":{\"id\":18,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"A63635412\",\"nombre\":\"CAMILA AMERICA\",\"telefono\":\"96478547\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":9,\"doc_tipo\":\"DNI\",\"doc_numero\":\"50504012\",\"nombres\":\"ALEXANDRA\",\"apellidos\":\"ALAMA VASQUEZ\",\"telefono\":\"966363254\"},\"venta\":{\"caja_diaria_id\":2,\"total\":1200,\"pagado\":1000,\"saldo\":200}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-04 21:29:45'),
(18, 19, 'pos_ventas', 18, 'VENTA_CREADA', '{\"cliente\":{\"id\":19,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"Q34567891\",\"nombre\":\"Luis Vargas Soto\",\"telefono\":\"965874123\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"Q34567891\",\"nombres\":\"Luis\",\"apellidos\":\"Vargas Soto\",\"telefono\":\"965874123\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":3,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-05 09:42:20'),
(19, 19, 'pos_ventas', 19, 'VENTA_CREADA', '{\"cliente\":{\"id\":20,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"59201734\",\"nombre\":\"Andrea Torres Vega\",\"telefono\":\"944785236\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"59201734\",\"nombres\":\"Andrea\",\"apellidos\":\"Torres Vega\",\"telefono\":\"944785236\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"59201734\",\"canal\":\"WHATSAPP\",\"email\":\"juan.ramirez@mail.com\",\"nacimiento\":\"1995-05-12\",\"categoria_auto_id\":1,\"categoria_moto_id\":null,\"nota\":\"Interesado en curso básico\"},\"venta\":{\"caja_diaria_id\":3,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-05 09:56:05'),
(20, 19, 'pos_ventas', 20, 'VENTA_CREADA', '{\"cliente\":{\"id\":21,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"52625214\",\"nombre\":\"VICENTE CARDENAS CARDENAS\",\"telefono\":\"963332145\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"52625214\",\"nombres\":\"VICENTE\",\"apellidos\":\"CARDENAS CARDENAS\",\"telefono\":\"963332145\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"52625214\",\"canal\":\"LLAMADA\",\"email\":\"a@gmail.com\",\"nacimiento\":\"1999-09-06\",\"categoria_auto_id\":5,\"categoria_moto_id\":9,\"nota\":\"El señor viajará pronto.\"},\"venta\":{\"caja_diaria_id\":3,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-05 13:40:04'),
(21, 19, 'pos_ventas', 21, 'VENTA_CREADA', '{\"cliente\":{\"id\":22,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"50201214\",\"nombre\":\"Miguel Mariños Marcial\",\"telefono\":\"963323214\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"50201214\",\"nombres\":\"Miguel\",\"apellidos\":\"Mariños Marcial\",\"telefono\":\"963323214\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"50201214\",\"canal\":\"LLAMADA\",\"email\":\"qqq@gmail.com\",\"nacimiento\":\"1980-09-09\",\"categoria_auto_id\":3,\"categoria_moto_id\":9,\"nota\":\"Señor interesado en revalidar.\"},\"venta\":{\"caja_diaria_id\":3,\"total\":1700,\"pagado\":1200,\"saldo\":500}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-05 13:42:27'),
(22, 19, 'pos_ventas', 22, 'VENTA_CREADA', '{\"cliente\":{\"id\":23,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70555562\",\"nombre\":\"JUAN LUIS GUERRA PAZ\",\"telefono\":\"963333632\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70555562\",\"nombres\":\"JUAN LUIS\",\"apellidos\":\"GUERRA PAZ\",\"telefono\":\"963333632\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":3,\"total\":1200,\"pagado\":500,\"saldo\":700}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '38.253.190.90', '2026-03-05 15:23:03'),
(23, 19, 'pos_ventas', 23, 'VENTA_CREADA', '{\"cliente\":{\"id\":24,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70333321\",\"nombre\":\"George Washington\",\"telefono\":\"963323214\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":10,\"doc_tipo\":\"CE\",\"doc_numero\":\"90303060233\",\"nombres\":\"JULIAN\",\"apellidos\":\"PEREZ PEREZ\",\"telefono\":\"966636254\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"CE\",\"doc_numero\":\"90303060233\",\"canal\":\"TIKTOK\",\"email\":\"aaa@gmail.com\",\"nacimiento\":\"1975-08-08\",\"categoria_auto_id\":4,\"categoria_moto_id\":9,\"nota\":\"Vio un tiktok y le dio risa, entonces vino al local.\"},\"venta\":{\"caja_diaria_id\":3,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '38.253.190.90', '2026-03-05 16:09:22'),
(24, 19, 'pos_ventas', 24, 'VENTA_CREADA', '{\"cliente\":{\"id\":25,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"703333215\",\"nombre\":\"JUAN JUAREZ JORA\",\"telefono\":\"969696854\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":11,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70000000\",\"nombres\":\"PEPITO\",\"apellidos\":\"RUIZ JUAREZ\",\"telefono\":\"965555555\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70000000\",\"canal\":\"TIKTOK\",\"email\":\"a@gmail.com\",\"nacimiento\":\"1998-05-05\",\"categoria_auto_id\":4,\"categoria_moto_id\":8,\"nota\":\"VIO UN TIKTOK Y LE GUSTÓ\"},\"venta\":{\"caja_diaria_id\":3,\"total\":1200,\"pagado\":1000,\"saldo\":200}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '38.253.190.90', '2026-03-05 16:19:50'),
(25, 19, 'pos_ventas', 25, 'VENTA_CREADA', '{\"cliente\":{\"id\":26,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70554411\",\"nombre\":\"ELOISA MARTINEZ FERNANDEZ\",\"telefono\":\"963323214\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70554411\",\"nombres\":\"ELOISA\",\"apellidos\":\"MARTINEZ FERNANDEZ\",\"telefono\":\"963323214\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":4,\"total\":4800,\"pagado\":4800,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '38.253.190.90', '2026-03-06 17:46:04'),
(26, 19, 'pos_ventas', 26, 'VENTA_CREADA', '{\"cliente\":{\"id\":27,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70333362\",\"nombre\":\"CRISTIAN SOTO SOL\",\"telefono\":\"963332321\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70333362\",\"nombres\":\"CRISTIAN\",\"apellidos\":\"SOTO SOL\",\"telefono\":\"963332321\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":5,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-10 09:16:36'),
(27, 19, 'pos_ventas', 27, 'VENTA_CREADA', '{\"cliente\":{\"id\":28,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70555542\",\"nombre\":\"JUAN LUIS VARGAS VARGAS\",\"telefono\":\"963332142\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70555542\",\"nombres\":\"JUAN LUIS\",\"apellidos\":\"VARGAS VARGAS\",\"telefono\":\"963332142\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":5,\"total\":500,\"pagado\":500,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-10 09:27:00'),
(28, 19, 'pos_ventas', 28, 'VENTA_CREADA', '{\"cliente\":{\"id\":29,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"78888765\",\"nombre\":\"JUAN VARGAS VARGAS\",\"telefono\":\"988887654\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"78888765\",\"nombres\":\"JUAN\",\"apellidos\":\"VARGAS VARGAS\",\"telefono\":\"988887654\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":5,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '2800:200:fdc8:39:567:c636:d676:452', '2026-03-10 11:34:08'),
(29, 19, 'pos_ventas', 29, 'VENTA_CREADA', '{\"cliente\":{\"id\":30,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"B76654543\",\"nombre\":\"JUANA GONZALES PEREZ\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"B76654543\",\"nombres\":\"JUANA\",\"apellidos\":\"GONZALES PEREZ\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":5,\"total\":500,\"pagado\":300,\"saldo\":200}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '2800:200:fdc8:39:567:c636:d676:452', '2026-03-10 11:35:57'),
(30, 19, 'pos_ventas', 30, 'VENTA_CREADA', '{\"cliente\":{\"id\":31,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"78888876\",\"nombre\":\"LUIS VILLANUEVA\",\"telefono\":\"964555532\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"78888876\",\"nombres\":\"LUIS\",\"apellidos\":\"VILLANUEVA\",\"telefono\":\"964555532\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"78888876\",\"canal\":\"FACEBOOK\",\"email\":null,\"nacimiento\":\"1999-03-04\",\"categoria_auto_id\":3,\"categoria_moto_id\":10,\"nota\":\"El señor viene de una municipalidad posible convenio\"},\"venta\":{\"caja_diaria_id\":5,\"total\":1200,\"pagado\":1200,\"saldo\":0}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '2800:200:fdc8:39:567:c636:d676:452', '2026-03-10 11:43:19'),
(31, 19, 'pos_ventas', 36, 'VENTA_CREADA', '{\"cliente\":{\"id\":32,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"77889966\",\"nombre\":\"Luigi Villanueva\",\"telefono\":\"964881842\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"77889966\",\"nombres\":\"Luigi\",\"apellidos\":\"Villanueva\",\"telefono\":\"964881842\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":6,\"total\":1200,\"pagado\":1200,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-12 22:58:24'),
(32, 19, 'pos_ventas', 37, 'VENTA_CREADA', '{\"cliente\":{\"id\":34,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441336\",\"nombre\":\"Anastacia León León\",\"telefono\":\"963332321\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441336\",\"nombres\":\"Anastacia\",\"apellidos\":\"León León\",\"telefono\":\"963332321\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":6,\"total\":1050,\"pagado\":500,\"saldo\":550},\"precio_temporal\":{\"aplica\":true,\"actor\":{\"id\":10,\"usuario\":\"12121212\",\"nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"items\":[{\"servicio_id\":3,\"servicio_nombre\":\"RECA AIIB\",\"cantidad\":1,\"precio_unitario\":1050,\"motivo\":\"Coordinado con ventas\",\"fecha\":\"2026-03-12 23:01:16\"}]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-12 23:01:16'),
(33, 19, 'pos_ventas', 38, 'VENTA_CREADA', '{\"cliente\":{\"id\":35,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441212\",\"nombre\":\"LUIS GUERRA GUERRA\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441212\",\"nombres\":\"LUIS\",\"apellidos\":\"GUERRA GUERRA\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":6,\"total\":1200,\"pagado\":1200,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-12 23:59:00'),
(34, 19, 'pos_ventas', 39, 'VENTA_CREADA', '{\"cliente\":{\"id\":36,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70555523\",\"nombre\":\"LUIS GUERRA PAZ\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70555523\",\"nombres\":\"LUIS\",\"apellidos\":\"GUERRA PAZ\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":6,\"total\":1200,\"pagado\":0,\"saldo\":1200},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-13 00:00:06'),
(35, 19, 'pos_ventas', 40, 'VENTA_CREADA', '{\"cliente\":{\"id\":37,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70525142\",\"nombre\":\"DIANA PAZ\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70525142\",\"nombres\":\"DIANA\",\"apellidos\":\"PAZ\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":150,\"pagado\":0,\"saldo\":150},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-13 00:18:03'),
(36, 19, 'pos_ventas', 41, 'VENTA_CREADA', '{\"cliente\":{\"id\":38,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70123625\",\"nombre\":\"SANDRA ERIKA MONTOYA CAMARGO\",\"telefono\":\"966635263\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70123625\",\"nombres\":\"SANDRA ERIKA\",\"apellidos\":\"MONTOYA CAMARGO\",\"telefono\":\"966635263\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":600,\"pagado\":400,\"saldo\":200},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-13 01:41:01'),
(37, 19, 'pos_ventas', 42, 'VENTA_CREADA', '{\"cliente\":{\"id\":39,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70455253\",\"nombre\":\"CYNTHIA MARIA CARMEN ROUILLON\",\"telefono\":\"963323214\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70455253\",\"nombres\":\"CYNTHIA MARIA\",\"apellidos\":\"CARMEN ROUILLON\",\"telefono\":\"963323214\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":150,\"pagado\":150,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-13 01:43:02'),
(38, 19, 'pos_ventas', 43, 'VENTA_CREADA', '{\"cliente\":{\"id\":40,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"964881841\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombres\":\"LUIGI ISRAEL\",\"apellidos\":\"VILLANUEVA PEREZ\",\"telefono\":\"964881841\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":150,\"pagado\":150,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-13 08:03:04'),
(39, 19, 'pos_ventas', 44, 'VENTA_CREADA', '{\"cliente\":{\"id\":41,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"18198265\",\"nombre\":\"ROXANA MARILU TRELLES URQUIZA\",\"telefono\":\"963632145\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"18198265\",\"nombres\":\"ROXANA MARILU\",\"apellidos\":\"TRELLES URQUIZA\",\"telefono\":\"963632145\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":10,\"pagado\":10,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '2800:200:fdd8:58:b423:e506:88d7:abac', '2026-03-13 10:52:41'),
(40, 19, 'pos_ventas', 45, 'VENTA_CREADA', '{\"cliente\":{\"id\":42,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"71252952\",\"nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"telefono\":\"963232142\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"71252952\",\"nombres\":\"JHEFERSON ALESSANDRO\",\"apellidos\":\"RODRIGUEZ PAREDES\",\"telefono\":\"963232142\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":900,\"pagado\":500,\"saldo\":400},\"precio_temporal\":{\"aplica\":true,\"actor\":{\"id\":10,\"usuario\":\"12121212\",\"nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"items\":[{\"servicio_id\":7,\"servicio_nombre\":\"RECA AIIIB\",\"cantidad\":1,\"precio_unitario\":900,\"motivo\":\"coordinado con gerencia\",\"fecha\":\"2026-03-13 10:56:42\"}]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '2800:200:fdd8:58:b423:e506:88d7:abac', '2026-03-13 10:56:42'),
(41, 19, 'pos_ventas', 46, 'VENTA_CREADA', '{\"cliente\":{\"id\":43,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"47305338\",\"nombre\":\"KARLA HELEN BELTRAN ARANDA\",\"telefono\":\"964885412\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"47305338\",\"nombres\":\"KARLA HELEN\",\"apellidos\":\"BELTRAN ARANDA\",\"telefono\":\"964885412\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":150,\"pagado\":150,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '2800:200:fdd8:58:b423:e506:88d7:abac', '2026-03-13 11:04:41'),
(42, 19, 'pos_ventas', 47, 'VENTA_CREADA', '{\"cliente\":{\"id\":44,\"tipo_persona\":\"JURIDICA\",\"doc_tipo\":\"RUC\",\"doc_numero\":\"20482833811\",\"nombre\":\"ESCUELA DE CONDUCTORES INTEGRALES ALLAIN PROST E.I.R.L.\",\"telefono\":\"965332321\"},\"contratante\":{\"doc_tipo\":\"CE\",\"doc_numero\":\"7036365214\",\"nombres\":\"LUIS\",\"apellidos\":\"PAREDES PAREDES\",\"telefono\":\"965332321\"},\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"contratante_juridica\",\"conductor_id\":null,\"doc_tipo\":\"CE\",\"doc_numero\":\"7036365214\",\"nombres\":\"LUIS\",\"apellidos\":\"PAREDES PAREDES\",\"telefono\":\"965332321\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":1100,\"pagado\":1100,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '2800:200:fdd8:58:b423:e506:88d7:abac', '2026-03-13 11:10:18'),
(43, 19, 'pos_ventas', 48, 'VENTA_CREADA', '{\"cliente\":{\"id\":45,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70352321\",\"nombre\":\"TATHIANA ALEXE MARIA CAMA CAMASCA\",\"telefono\":\"963323214\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70352321\",\"nombres\":\"TATHIANA ALEXE MARIA\",\"apellidos\":\"CAMA CAMASCA\",\"telefono\":\"963323214\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":150,\"pagado\":150,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '38.253.190.90', '2026-03-13 15:27:42'),
(44, 19, 'pos_ventas', 49, 'VENTA_CREADA', '{\"cliente\":{\"id\":40,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"964881841\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombres\":\"LUIGI ISRAEL\",\"apellidos\":\"VILLANUEVA PEREZ\",\"telefono\":\"964881841\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":7,\"total\":150,\"pagado\":150,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-13 21:31:29'),
(45, 19, 'pos_ventas', 50, 'VENTA_CREADA', '{\"cliente\":{\"id\":46,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70010001\",\"nombre\":\"Juan Perez\",\"telefono\":\"900111111\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70010001\",\"nombres\":\"Juan\",\"apellidos\":\"Perez\",\"telefono\":\"900111111\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":500,\"pagado\":500,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 11:53:21'),
(46, 19, 'pos_ventas', 51, 'VENTA_CREADA', '{\"cliente\":{\"id\":47,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"CE\",\"doc_numero\":\"70010002\",\"nombre\":\"Maria Loayza\",\"telefono\":\"900111112\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"CE\",\"doc_numero\":\"70010002\",\"nombres\":\"Maria\",\"apellidos\":\"Loayza\",\"telefono\":\"900111112\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":10,\"pagado\":10,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 11:54:45'),
(47, 19, 'pos_ventas', 52, 'VENTA_CREADA', '{\"cliente\":{\"id\":48,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70010003\",\"nombre\":\"Luis Vargas\",\"telefono\":\"900111113\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70010003\",\"nombres\":\"Luis\",\"apellidos\":\"Vargas\",\"telefono\":\"900111113\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70010003\",\"canal\":\"WHATSAPP\",\"email\":\"luis.test@demo.com\",\"nacimiento\":\"1993-04-10\",\"categoria_auto_id\":null,\"categoria_moto_id\":null,\"nota\":\"cliente satisfecho. Volverá.\"},\"venta\":{\"caja_diaria_id\":8,\"total\":1100,\"pagado\":1100,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 11:57:54'),
(48, 19, 'pos_ventas', 53, 'VENTA_CREADA', '{\"cliente\":{\"id\":49,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70010005\",\"nombre\":\"Ana Ruiz\",\"telefono\":\"900111115\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":12,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70020005\",\"nombres\":\"Pedro\",\"apellidos\":\"Soto\",\"telefono\":\"911222331\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":500,\"pagado\":500,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 12:14:13'),
(49, 19, 'pos_ventas', 54, 'VENTA_CREADA', '{\"cliente\":{\"id\":50,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"CE\",\"doc_numero\":\"70010006\",\"nombre\":\"Elena Paz\",\"telefono\":\"900111116\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"CE\",\"doc_numero\":\"70010006\",\"nombres\":\"Elena\",\"apellidos\":\"Paz\",\"telefono\":\"900111116\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":1200,\"pagado\":1200,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 12:34:19'),
(50, 19, 'pos_ventas', 55, 'VENTA_CREADA', '{\"cliente\":{\"id\":51,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"52719384\",\"nombre\":\"Rocío Castro Luna Arce\",\"telefono\":\"912345675\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":13,\"doc_tipo\":\"DNI\",\"doc_numero\":\"46820517\",\"nombres\":\"Bruno\",\"apellidos\":\"Soto Aguilar\",\"telefono\":\"923456781\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":120,\"pagado\":120,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 13:23:27'),
(51, 19, 'pos_ventas', 56, 'VENTA_CREADA', '{\"cliente\":{\"id\":52,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"784512309\",\"nombre\":\"Matías Herrera Campos\",\"telefono\":\"912345676\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":14,\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"B90817263\",\"nombres\":\"Andrea\",\"apellidos\":\"Peña Cárdenas\",\"telefono\":\"923456782\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":600,\"pagado\":600,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 13:25:06'),
(52, 19, 'pos_ventas', 57, 'VENTA_CREADA', '{\"cliente\":{\"id\":53,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"63917420\",\"nombre\":\"Fernanda Núñez Ramos\",\"telefono\":\"912345677\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":15,\"doc_tipo\":\"DNI\",\"doc_numero\":\"57284019\",\"nombres\":\"Santiago\",\"apellidos\":\"Vega Lozano\",\"telefono\":\"923456783\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"57284019\",\"canal\":\"SMS\",\"email\":\"santiago.vega+uatf401@example.com\",\"nacimiento\":\"1993-11-27\",\"categoria_auto_id\":null,\"categoria_moto_id\":null,\"nota\":\"Enviar aviso por SMS\"},\"venta\":{\"caja_diaria_id\":8,\"total\":1000,\"pagado\":1000,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 13:28:30'),
(53, 19, 'pos_ventas', 58, 'VENTA_CREADA', '{\"cliente\":{\"id\":54,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"47820563\",\"nombre\":\"Kevin Torres Mejía\",\"telefono\":\"912345678\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":16,\"doc_tipo\":\"CE\",\"doc_numero\":\"659301842\",\"nombres\":\"Lucía\",\"apellidos\":\"Suárez Pinto\",\"telefono\":\"923456784\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"CE\",\"doc_numero\":\"659301842\",\"canal\":\"SMS\",\"email\":\"lucia.suarez+uatf402@example.com\",\"nacimiento\":\"2001-05-09\",\"categoria_auto_id\":null,\"categoria_moto_id\":null,\"nota\":\"Correo de confirmación requerido\"},\"venta\":{\"caja_diaria_id\":8,\"total\":1500,\"pagado\":1500,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 13:31:28'),
(54, 19, 'pos_ventas', 59, 'VENTA_CREADA', '{\"cliente\":{\"id\":55,\"tipo_persona\":\"JURIDICA\",\"doc_tipo\":\"RUC\",\"doc_numero\":\"20574839216\",\"nombre\":\"Servicios Andinos UAT S.A.C.\",\"telefono\":\"912345679\"},\"contratante\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"71640258\",\"nombres\":\"Marcos\",\"apellidos\":\"Cruz Valdivia\",\"telefono\":\"912345679\"},\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"contratante_juridica\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"71640258\",\"nombres\":\"Marcos\",\"apellidos\":\"Cruz Valdivia\",\"telefono\":\"912345679\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":1100,\"pagado\":1100,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 19:34:48'),
(55, 19, 'pos_ventas', 60, 'VENTA_CREADA', '{\"cliente\":{\"id\":56,\"tipo_persona\":\"JURIDICA\",\"doc_tipo\":\"RUC\",\"doc_numero\":\"20650173928\",\"nombre\":\"Constructora Nuevo Horizonte UAT S.R.L.\",\"telefono\":\"912345681\"},\"contratante\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"68492017\",\"nombres\":\"Renzo\",\"apellidos\":\"Flores Castañeda\",\"telefono\":\"912345681\"},\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"contratante_juridica\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"68492017\",\"nombres\":\"Renzo\",\"apellidos\":\"Flores Castañeda\",\"telefono\":\"912345681\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"68492017\",\"canal\":\"WHATSAPP\",\"email\":\"renzo.flores+uatf601@example.com\",\"nacimiento\":\"1985-01-22\",\"categoria_auto_id\":2,\"categoria_moto_id\":null,\"nota\":\"Enviar constancia firmada\"},\"venta\":{\"caja_diaria_id\":8,\"total\":500,\"pagado\":500,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 19:40:16'),
(56, 19, 'pos_ventas', 61, 'VENTA_CREADA', '{\"cliente\":{\"id\":57,\"tipo_persona\":\"JURIDICA\",\"doc_tipo\":\"RUC\",\"doc_numero\":\"20948573612\",\"nombre\":\"Grupo Ferretero Tambo UAT S.R.L.\",\"telefono\":\"739201458\"},\"contratante\":{\"doc_tipo\":\"CE\",\"doc_numero\":\"912345684\",\"nombres\":\"Elena\",\"apellidos\":\"Navarro Cordero\",\"telefono\":\"739201458\"},\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":17,\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"B61529407\",\"nombres\":\"Iván\",\"apellidos\":\"Quinteros Ledesma\",\"telefono\":\"923456786\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":1000,\"pagado\":1000,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 19:45:37'),
(57, 19, 'pos_ventas', 62, 'VENTA_CREADA', '{\"cliente\":{\"id\":58,\"tipo_persona\":\"JURIDICA\",\"doc_tipo\":\"RUC\",\"doc_numero\":\"20173948526\",\"nombre\":\"Tecnored UAT Solutions S.A.C.\",\"telefono\":\"912345686\"},\"contratante\":{\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"A90371642\",\"nombres\":\"Nicolás\",\"apellidos\":\"Ávila Sarmiento\",\"telefono\":\"912345686\"},\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":18,\"doc_tipo\":\"DNI\",\"doc_numero\":\"54839271\",\"nombres\":\"Carla\",\"apellidos\":\"Bautista Romero\",\"telefono\":\"923456788\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":600,\"pagado\":600,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 19:52:18'),
(58, 19, 'pos_ventas', 63, 'VENTA_CREADA', '{\"cliente\":{\"id\":58,\"tipo_persona\":\"JURIDICA\",\"doc_tipo\":\"RUC\",\"doc_numero\":\"20173948526\",\"nombre\":\"Tecnored UAT Solutions S.A.C.\",\"telefono\":\"912345686\"},\"contratante\":{\"doc_tipo\":\"BREVETE\",\"doc_numero\":\"A90371642\",\"nombres\":\"Nicolás\",\"apellidos\":\"Ávila Sarmiento\",\"telefono\":\"912345686\"},\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":18,\"doc_tipo\":\"DNI\",\"doc_numero\":\"54839271\",\"nombres\":\"Carla\",\"apellidos\":\"Bautista Romero\",\"telefono\":\"923456788\"},\"conductor_perfil_extra\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"54839271\",\"canal\":\"SMS\",\"email\":\"carla.bautista+uatf802@example.com\",\"nacimiento\":\"1998-05-12\",\"categoria_auto_id\":4,\"categoria_moto_id\":9,\"nota\":\"Notificar por SMS al conductor\"},\"venta\":{\"caja_diaria_id\":8,\"total\":150,\"pagado\":150,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 19:56:31'),
(59, 19, 'pos_ventas', 64, 'VENTA_CREADA', '{\"cliente\":{\"id\":40,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"964881842\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombres\":\"LUIGI ISRAEL\",\"apellidos\":\"VILLANUEVA PEREZ\",\"telefono\":\"964881842\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":10,\"pagado\":5,\"saldo\":5},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 20:00:17'),
(60, 19, 'pos_ventas', 65, 'VENTA_CREADA', '{\"cliente\":{\"id\":59,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70000001\",\"nombre\":\"JUAN PEREZ\",\"telefono\":\"900111222\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70000001\",\"nombres\":\"JUAN\",\"apellidos\":\"PEREZ\",\"telefono\":\"900111222\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":1200,\"pagado\":1200,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 22:48:09'),
(61, 19, 'pos_ventas', 66, 'VENTA_CREADA', '{\"cliente\":{\"id\":60,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70212121\",\"nombre\":\"WILIE MARQUEZ\",\"telefono\":\"963232142\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70212121\",\"nombres\":\"WILIE\",\"apellidos\":\"MARQUEZ\",\"telefono\":\"963232142\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":350,\"pagado\":100,\"saldo\":250},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-14 22:54:53'),
(62, 19, 'pos_ventas', 67, 'VENTA_CREADA', '{\"cliente\":{\"id\":40,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"964881854\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombres\":\"LUIGI ISRAEL\",\"apellidos\":\"VILLANUEVA PEREZ\",\"telefono\":\"964881854\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":8,\"total\":150,\"pagado\":100,\"saldo\":50},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 01:24:18'),
(63, 19, 'pos_ventas', 68, 'VENTA_CREADA', '{\"cliente\":{\"id\":40,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombres\":\"LUIGI ISRAEL\",\"apellidos\":\"VILLANUEVA PEREZ\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":9,\"total\":150,\"pagado\":150,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 10:02:46'),
(64, 19, 'pos_ventas', 69, 'VENTA_CREADA', '{\"cliente\":{\"id\":40,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"nombres\":\"LUIGI ISRAEL\",\"apellidos\":\"VILLANUEVA PEREZ\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":9,\"total\":1100,\"pagado\":1100,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 10:48:41'),
(65, 19, 'pos_ventas', 70, 'VENTA_CREADA', '{\"cliente\":{\"id\":61,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70363241\",\"nombre\":\"LUIS FERNANDO LOPEZ VARGAS\",\"telefono\":\"964121214\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70363241\",\"nombres\":\"LUIS FERNANDO\",\"apellidos\":\"LOPEZ VARGAS\",\"telefono\":\"964121214\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":9,\"total\":1000,\"pagado\":1000,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 18:38:59'),
(66, 19, 'pos_ventas', 71, 'VENTA_CREADA', '{\"cliente\":{\"id\":62,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70362121\",\"nombre\":\"JUNIOR TEODORO RODRIGUEZ GUEVARA\",\"telefono\":\"964881523\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70362121\",\"nombres\":\"JUNIOR TEODORO\",\"apellidos\":\"RODRIGUEZ GUEVARA\",\"telefono\":\"964881523\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":9,\"total\":1000,\"pagado\":1000,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 18:40:18'),
(67, 19, 'pos_ventas', 72, 'VENTA_CREADA', '{\"cliente\":{\"id\":63,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964112123\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombres\":\"ISABEL ROSALI\",\"apellidos\":\"TORREJON GONZALES\",\"telefono\":\"964112123\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":9,\"total\":1000,\"pagado\":1000,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 18:42:13'),
(68, 19, 'pos_ventas', 73, 'VENTA_CREADA', '{\"cliente\":{\"id\":20,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"59201734\",\"nombre\":\"MILAGROS VARGAS VARGAS\",\"telefono\":\"964881852\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"59201734\",\"nombres\":\"MILAGROS\",\"apellidos\":\"VARGAS VARGAS\",\"telefono\":\"964881852\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":9,\"total\":500,\"pagado\":500,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 19:08:33');
INSERT INTO `pos_auditoria` (`id`, `id_empresa`, `tabla`, `registro_id`, `evento`, `datos`, `actor_id`, `actor_usuario`, `actor_nombre`, `ip`, `creado_en`) VALUES
(69, 19, 'pos_ventas', 74, 'VENTA_CREADA', '{\"cliente\":{\"id\":63,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombres\":\"ISABEL ROSALI\",\"apellidos\":\"TORREJON GONZALES\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":9,\"total\":1200,\"pagado\":1200,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-15 22:17:28'),
(70, 19, 'pos_ventas', 75, 'VENTA_CREADA', '{\"cliente\":{\"id\":64,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441214\",\"nombre\":\"LIZBETH ROXANA CAMPOS RAMOS\",\"telefono\":\"964441452\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441214\",\"nombres\":\"LIZBETH ROXANA\",\"apellidos\":\"CAMPOS RAMOS\",\"telefono\":\"964441452\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":10,\"total\":1200,\"pagado\":1200,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-16 09:43:57'),
(71, 19, 'pos_ventas', 76, 'VENTA_CREADA', '{\"cliente\":{\"id\":63,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964881842\"},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombres\":\"ISABEL ROSALI\",\"apellidos\":\"TORREJON GONZALES\",\"telefono\":\"964881842\"},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":10,\"total\":10,\"pagado\":10,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-16 10:13:58'),
(72, 20, 'pos_ventas', 77, 'VENTA_CREADA', '{\"cliente\":{\"id\":65,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"nombre\":\"PROMOTOR JOEL\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":19,\"doc_tipo\":\"DNI\",\"doc_numero\":\"42240258\",\"nombres\":\"FRANKLIN TARDELLI\",\"apellidos\":\"MARTINEZ SOLANO\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":40,\"pagado\":40,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-16 12:44:10'),
(73, 20, 'pos_ventas', 78, 'VENTA_CREADA', '{\"cliente\":{\"id\":66,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"42309191\",\"nombre\":\"HEHIVER ANTENOR REYNA SILVESTRE\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"42309191\",\"nombres\":\"HEHIVER ANTENOR\",\"apellidos\":\"REYNA SILVESTRE\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":150,\"pagado\":150,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 14:52:01'),
(74, 20, 'pos_ventas', 79, 'VENTA_CREADA', '{\"cliente\":{\"id\":65,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"nombre\":\"PROMOTOR MAGUIN\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":20,\"doc_tipo\":\"DNI\",\"doc_numero\":\"41906002\",\"nombres\":\"JOSE LUIS\",\"apellidos\":\"MANTILLA QUILICHE\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":50,\"pagado\":50,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-16 15:23:27'),
(75, 20, 'pos_ventas', 80, 'VENTA_CREADA', '{\"cliente\":{\"id\":65,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"nombre\":\"PROMOTOR TRUJILLO\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":21,\"doc_tipo\":\"DNI\",\"doc_numero\":\"47481147\",\"nombres\":\"FRANCISCO\",\"apellidos\":\"GARCIA BRICEÑO\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":50,\"pagado\":50,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-16 15:24:55'),
(76, 20, 'pos_ventas', 81, 'VENTA_CREADA', '{\"cliente\":{\"id\":65,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"nombre\":\"PROMOTOR SUSY\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"REGISTRADO\",\"origen\":\"conductor_otra_persona\",\"conductor_id\":22,\"doc_tipo\":\"DNI\",\"doc_numero\":\"46782087\",\"nombres\":\"JILDER\",\"apellidos\":\"CORONEL DELGADO\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":40,\"pagado\":40,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-16 15:26:19'),
(77, 20, 'pos_ventas', 82, 'VENTA_CREADA', '{\"cliente\":{\"id\":67,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"18196115\",\"nombre\":\"TEOFILO VICTOR FLORES FLORES\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"18196115\",\"nombres\":\"TEOFILO VICTOR\",\"apellidos\":\"FLORES FLORES\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":250,\"pagado\":250,\"saldo\":0},\"precio_temporal\":{\"aplica\":true,\"actor\":{\"id\":18,\"usuario\":\"75806539\",\"nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"items\":[{\"servicio_id\":3,\"servicio_nombre\":\"RECA AIIB\",\"cantidad\":1,\"precio_unitario\":250,\"motivo\":\"CANC. SALDO RECA MATRICULADA VIERNES 13/03\",\"fecha\":\"2026-03-16 15:29:14\"}]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-16 15:29:14'),
(78, 20, 'pos_ventas', 83, 'VENTA_CREADA', '{\"cliente\":{\"id\":68,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"40498468\",\"nombre\":\"LUIS YHONY SERRANO DIAZ\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"40498468\",\"nombres\":\"LUIS YHONY\",\"apellidos\":\"SERRANO DIAZ\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":50,\"pagado\":50,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:23:42'),
(79, 20, 'pos_ventas', 84, 'VENTA_CREADA', '{\"cliente\":{\"id\":69,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"45400085\",\"nombre\":\"JHONNATTAN VENTURA CERNA\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"45400085\",\"nombres\":\"JHONNATTAN\",\"apellidos\":\"VENTURA CERNA\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":70,\"pagado\":70,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:27:01'),
(80, 20, 'pos_ventas', 85, 'VENTA_CREADA', '{\"cliente\":{\"id\":70,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"4854581\",\"nombre\":\"PROMOTOR ZAVALETA\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"4854581\",\"nombres\":\"PROMOTOR\",\"apellidos\":\"ZAVALETA\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":50,\"pagado\":50,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:28:50'),
(81, 20, 'pos_ventas', 86, 'VENTA_CREADA', '{\"cliente\":{\"id\":71,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"1515318\",\"nombre\":\"PROMOTOR TRUJILLO\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"1515318\",\"nombres\":\"PROMOTOR\",\"apellidos\":\"TRUJILLO\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":100,\"pagado\":100,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:30:25'),
(82, 20, 'pos_ventas', 87, 'VENTA_CREADA', '{\"cliente\":{\"id\":72,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"5198145\",\"nombre\":\"PROMOTORA NECKY\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"5198145\",\"nombres\":\"PROMOTORA\",\"apellidos\":\"NECKY\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":50,\"pagado\":50,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:31:18'),
(83, 20, 'pos_ventas', 88, 'VENTA_CREADA', '{\"cliente\":{\"id\":73,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"581684\",\"nombre\":\"PROMOTORA KELLY\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"581684\",\"nombres\":\"PROMOTORA\",\"apellidos\":\"KELLY\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":50,\"pagado\":50,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:31:52'),
(84, 20, 'pos_ventas', 89, 'VENTA_CREADA', '{\"cliente\":{\"id\":74,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"78104830\",\"nombre\":\"APONTE LEIVA\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"78104830\",\"nombres\":\"APONTE\",\"apellidos\":\"LEIVA\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":130,\"pagado\":130,\"saldo\":0},\"precio_temporal\":{\"aplica\":true,\"actor\":{\"id\":19,\"usuario\":\"71252952\",\"nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"items\":[{\"servicio_id\":1,\"servicio_nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio_unitario\":130,\"motivo\":\"Coordinado con Gerencia\",\"fecha\":\"2026-03-16 21:33:54\"}]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:33:54'),
(85, 20, 'pos_ventas', 90, 'VENTA_CREADA', '{\"cliente\":{\"id\":75,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"17988823\",\"nombre\":\"AZAÑERO LLAROS\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"17988823\",\"nombres\":\"AZAÑERO\",\"apellidos\":\"LLAROS\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":130,\"pagado\":130,\"saldo\":0},\"precio_temporal\":{\"aplica\":true,\"actor\":{\"id\":19,\"usuario\":\"71252952\",\"nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"items\":[{\"servicio_id\":1,\"servicio_nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio_unitario\":130,\"motivo\":\"Coordinado con Gerencia\",\"fecha\":\"2026-03-16 21:34:59\"}]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:34:59'),
(86, 20, 'pos_ventas', 91, 'VENTA_CREADA', '{\"cliente\":{\"id\":76,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"27161104\",\"nombre\":\"CIRO GAMANIEL APONTE CASTILLO\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"27161104\",\"nombres\":\"CIRO GAMANIEL\",\"apellidos\":\"APONTE CASTILLO\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":130,\"pagado\":130,\"saldo\":0},\"precio_temporal\":{\"aplica\":true,\"actor\":{\"id\":19,\"usuario\":\"71252952\",\"nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"items\":[{\"servicio_id\":1,\"servicio_nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio_unitario\":130,\"motivo\":\"Coordinado con Gerencia\",\"fecha\":\"2026-03-16 21:35:53\"}]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:35:53'),
(87, 20, 'pos_ventas', 92, 'VENTA_CREADA', '{\"cliente\":{\"id\":77,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"76686301\",\"nombre\":\"CAMPOS ZAVALA\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"76686301\",\"nombres\":\"CAMPOS\",\"apellidos\":\"ZAVALA\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":130,\"pagado\":130,\"saldo\":0},\"precio_temporal\":{\"aplica\":true,\"actor\":{\"id\":19,\"usuario\":\"71252952\",\"nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"items\":[{\"servicio_id\":1,\"servicio_nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio_unitario\":130,\"motivo\":\"Coordinado con Gerencia\",\"fecha\":\"2026-03-16 21:37:07\"}]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:37:07'),
(88, 20, 'pos_ventas', 93, 'VENTA_CREADA', '{\"cliente\":{\"id\":78,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"18174136\",\"nombre\":\"DORIS RICARDINA ZAVALA ESPEJO\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"18174136\",\"nombres\":\"DORIS RICARDINA\",\"apellidos\":\"ZAVALA ESPEJO\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":11,\"total\":130,\"pagado\":130,\"saldo\":0},\"precio_temporal\":{\"aplica\":true,\"actor\":{\"id\":19,\"usuario\":\"71252952\",\"nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"items\":[{\"servicio_id\":1,\"servicio_nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio_unitario\":130,\"motivo\":\"Coordinado con Gerencia\",\"fecha\":\"2026-03-16 21:37:39\"}]}}', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '190.117.239.144', '2026-03-16 21:37:39'),
(89, 20, 'pos_ventas', 94, 'VENTA_CREADA', '{\"cliente\":{\"id\":79,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"41905307\",\"nombre\":\"PORFIRIO LUCANO ACUÑA\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"41905307\",\"nombres\":\"PORFIRIO\",\"apellidos\":\"LUCANO ACUÑA\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":12,\"total\":50,\"pagado\":50,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-17 11:56:54'),
(90, 20, 'pos_ventas', 95, 'VENTA_CREADA', '{\"cliente\":{\"id\":80,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"19536728\",\"nombre\":\"JOSE LUIS ROMAN CRUZ\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"19536728\",\"nombres\":\"JOSE LUIS\",\"apellidos\":\"ROMAN CRUZ\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":12,\"total\":70,\"pagado\":70,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-17 11:59:46'),
(91, 20, 'pos_ventas', 96, 'VENTA_CREADA', '{\"cliente\":{\"id\":81,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"31652296\",\"nombre\":\"VALENTIN MORALES DEXTRE\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"31652296\",\"nombres\":\"VALENTIN\",\"apellidos\":\"MORALES DEXTRE\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":12,\"total\":50,\"pagado\":50,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-17 12:01:18'),
(92, 20, 'pos_ventas', 97, 'VENTA_CREADA', '{\"cliente\":{\"id\":82,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"43751779\",\"nombre\":\"EDWIN JAKHON CASTILLO JICARO\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"43751779\",\"nombres\":\"EDWIN JAKHON\",\"apellidos\":\"CASTILLO JICARO\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":12,\"total\":60,\"pagado\":60,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-17 12:03:58'),
(93, 20, 'pos_ventas', 98, 'VENTA_CREADA', '{\"cliente\":{\"id\":83,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"00103906\",\"nombre\":\"CARLOS MORI SALDAÑA\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"00103906\",\"nombres\":\"CARLOS\",\"apellidos\":\"MORI SALDAÑA\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":12,\"total\":60,\"pagado\":60,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-17 12:04:45'),
(94, 20, 'pos_ventas', 99, 'VENTA_CREADA', '{\"cliente\":{\"id\":84,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"40761046\",\"nombre\":\"PERSIL VIDAL PEREZ BALTODANO\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"40761046\",\"nombres\":\"PERSIL VIDAL\",\"apellidos\":\"PEREZ BALTODANO\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":12,\"total\":50,\"pagado\":50,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-17 12:06:34'),
(95, 20, 'pos_ventas', 100, 'VENTA_CREADA', '{\"cliente\":{\"id\":85,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"45243491\",\"nombre\":\"JOSE FREDDY DE LA CRUZ AZABACHE\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"45243491\",\"nombres\":\"JOSE FREDDY\",\"apellidos\":\"DE LA CRUZ AZABACHE\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":12,\"total\":60,\"pagado\":60,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', '190.117.239.144', '2026-03-17 12:08:01'),
(96, 19, 'pos_ventas', 101, 'VENTA_CREADA', '{\"cliente\":{\"id\":63,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombres\":\"ISABEL ROSALI\",\"apellidos\":\"TORREJON GONZALES\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":13,\"total\":500,\"pagado\":500,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-17 14:10:10'),
(97, 19, 'pos_ventas', 102, 'VENTA_CREADA', '{\"cliente\":{\"id\":63,\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":null},\"contratante\":null,\"conductor\":{\"tipo_relacion\":\"CLIENTE\",\"origen\":\"cliente_natural\",\"conductor_id\":null,\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"nombres\":\"ISABEL ROSALI\",\"apellidos\":\"TORREJON GONZALES\",\"telefono\":null},\"conductor_perfil_extra\":null,\"venta\":{\"caja_diaria_id\":13,\"total\":150,\"pagado\":150,\"saldo\":0},\"precio_temporal\":{\"aplica\":false,\"actor\":null,\"items\":[]}}', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', '179.6.167.180', '2026-03-17 14:34:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_clientes`
--

CREATE TABLE `pos_clientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `tipo_persona` enum('NATURAL','JURIDICA') NOT NULL DEFAULT 'NATURAL',
  `doc_tipo` enum('DNI','RUC','CE','PAS','BREVETE') NOT NULL,
  `doc_numero` varchar(20) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_clientes`
--

INSERT INTO `pos_clientes` (`id`, `id_empresa`, `tipo_persona`, `doc_tipo`, `doc_numero`, `nombre`, `telefono`, `activo`, `creado`, `actualizado`) VALUES
(1, 19, 'NATURAL', 'DNI', '70379796', 'JULIAN ALVAREZ MORALES', '965652321', 1, '2026-03-03 23:29:46', '2026-03-03 23:29:46'),
(2, 19, 'NATURAL', 'DNI', '72525212', 'ASDASDAS ASDASD', '965652541', 1, '2026-03-03 23:31:54', '2026-03-03 23:31:54'),
(3, 19, 'NATURAL', 'DNI', '70366365', 'Maria Lopez', '966362532', 1, '2026-03-04 00:34:34', '2026-03-04 13:32:25'),
(4, 19, 'NATURAL', 'DNI', '72554145', 'MELISA RODRIGUEZ', '966653254', 1, '2026-03-04 10:05:28', '2026-03-04 10:05:28'),
(5, 19, 'NATURAL', 'DNI', '48392015', 'Carlos Mendoza Ríos', '987654321', 1, '2026-03-04 11:25:54', '2026-03-04 11:25:54'),
(6, 19, 'NATURAL', 'DNI', '52412514', 'KIMBERLY FLORES LOPEZ', '965252145', 1, '2026-03-04 13:29:25', '2026-03-04 13:29:25'),
(7, 19, 'JURIDICA', 'RUC', '20603562514', 'EMPRESA CONSTRUCTORA SAC', '9', 1, '2026-03-04 13:35:38', '2026-03-04 13:35:38'),
(8, 19, 'NATURAL', 'CE', '48392716', 'Diego Ramírez Soto', '912345678', 1, '2026-03-04 13:38:28', '2026-03-04 13:38:28'),
(9, 19, 'NATURAL', 'DNI', '41414251', 'JULIO VELASQUEZ QUESQUEN', '966565214', 1, '2026-03-04 14:16:27', '2026-03-04 14:16:27'),
(10, 19, 'NATURAL', 'DNI', '70111141', 'Maricarmen Villalobos Alfaro', '9633632541', 1, '2026-03-04 15:15:37', '2026-03-04 15:15:37'),
(11, 19, 'NATURAL', 'BREVETE', 'B63635478', 'MARIA ELENA COBARRUBIAS ALVA', '966663574', 1, '2026-03-04 15:23:39', '2026-03-04 15:23:39'),
(12, 19, 'NATURAL', 'DNI', '70414215', 'CAMILA PAREDES GONZALES', '966565415', 1, '2026-03-04 15:53:20', '2026-03-04 15:53:20'),
(13, 19, 'NATURAL', 'DNI', '41141251', 'CRISTIANM CASTRO CARRILLO', '965211412', 1, '2026-03-04 16:03:27', '2026-03-04 16:03:27'),
(14, 19, 'NATURAL', 'DNI', '71114121', 'julian juarez juvenal', '963323214', 1, '2026-03-04 16:04:52', '2026-03-04 16:04:52'),
(15, 19, 'NATURAL', 'DNI', '70414125', 'ALBERTO BARROS BAILON', '966363251', 1, '2026-03-04 16:08:54', '2026-03-04 16:08:54'),
(16, 19, 'NATURAL', 'DNI', '70252541', 'melisa perez juarez', '963323214', 1, '2026-03-04 16:16:37', '2026-03-04 16:16:37'),
(17, 19, 'NATURAL', 'DNI', '70333236', 'ROBERTO BLADES JUAREZ', '965554474', 1, '2026-03-04 16:18:00', '2026-03-04 16:18:00'),
(18, 19, 'NATURAL', 'BREVETE', 'A63635412', 'CAMILA AMERICA', '96478547', 1, '2026-03-04 21:29:45', '2026-03-04 21:29:45'),
(19, 19, 'NATURAL', 'BREVETE', 'Q34567891', 'Luis Vargas Soto', '965874123', 1, '2026-03-05 09:42:20', '2026-03-05 09:42:20'),
(20, 19, 'NATURAL', 'DNI', '59201734', 'MILAGROS VARGAS VARGAS', '964881852', 1, '2026-03-05 09:56:05', '2026-03-15 19:08:33'),
(21, 19, 'NATURAL', 'DNI', '52625214', 'VICENTE CARDENAS CARDENAS', '963332145', 1, '2026-03-05 13:40:04', '2026-03-05 13:40:04'),
(22, 19, 'NATURAL', 'DNI', '50201214', 'Miguel Mariños Marcial', '963323214', 1, '2026-03-05 13:42:27', '2026-03-05 13:42:27'),
(23, 19, 'NATURAL', 'DNI', '70555562', 'JUAN LUIS GUERRA PAZ', '963333632', 1, '2026-03-05 15:23:03', '2026-03-05 15:23:03'),
(24, 19, 'NATURAL', 'DNI', '70333321', 'George Washington', '963323214', 1, '2026-03-05 16:09:22', '2026-03-05 16:09:22'),
(25, 19, 'NATURAL', 'DNI', '703333215', 'JUAN JUAREZ JORA', '969696854', 1, '2026-03-05 16:19:50', '2026-03-05 16:19:50'),
(26, 19, 'NATURAL', 'DNI', '70554411', 'ELOISA MARTINEZ FERNANDEZ', '963323214', 1, '2026-03-06 17:46:04', '2026-03-06 17:46:04'),
(27, 19, 'NATURAL', 'DNI', '70333362', 'CRISTIAN SOTO SOL', '963332321', 1, '2026-03-10 09:16:36', '2026-03-10 09:16:36'),
(28, 19, 'NATURAL', 'DNI', '70555542', 'JUAN LUIS VARGAS VARGAS', '963332142', 1, '2026-03-10 09:27:00', '2026-03-10 09:27:00'),
(29, 19, 'NATURAL', 'DNI', '78888765', 'JUAN VARGAS VARGAS', '988887654', 1, '2026-03-10 11:34:08', '2026-03-10 11:34:08'),
(30, 19, 'NATURAL', 'BREVETE', 'B76654543', 'JUANA GONZALES PEREZ', NULL, 1, '2026-03-10 11:35:57', '2026-03-10 11:35:57'),
(31, 19, 'NATURAL', 'DNI', '78888876', 'LUIS VILLANUEVA', '964555532', 1, '2026-03-10 11:43:19', '2026-03-10 11:43:19'),
(32, 19, 'NATURAL', 'DNI', '77889966', 'Luigi Villanueva', '964881842', 1, '2026-03-12 22:37:03', '2026-03-12 22:51:20'),
(33, 19, 'NATURAL', 'DNI', '70444444', 'JUANA FLORES MARQUEZ', '964445124', 1, '2026-03-12 22:39:15', '2026-03-12 22:39:15'),
(34, 19, 'NATURAL', 'DNI', '70441336', 'Anastacia León León', '963332321', 1, '2026-03-12 23:01:16', '2026-03-12 23:01:16'),
(35, 19, 'NATURAL', 'DNI', '70441212', 'LUIS GUERRA GUERRA', NULL, 1, '2026-03-12 23:59:00', '2026-03-12 23:59:00'),
(36, 19, 'NATURAL', 'DNI', '70555523', 'LUIS GUERRA PAZ', NULL, 1, '2026-03-13 00:00:06', '2026-03-13 00:00:06'),
(37, 19, 'NATURAL', 'DNI', '70525142', 'DIANA PAZ', NULL, 1, '2026-03-13 00:18:03', '2026-03-13 00:18:03'),
(38, 19, 'NATURAL', 'DNI', '70123625', 'SANDRA ERIKA MONTOYA CAMARGO', '966635263', 1, '2026-03-13 01:41:01', '2026-03-13 01:41:01'),
(39, 19, 'NATURAL', 'DNI', '70455253', 'CYNTHIA MARIA CARMEN ROUILLON', '963323214', 1, '2026-03-13 01:43:02', '2026-03-13 01:43:02'),
(40, 19, 'NATURAL', 'DNI', '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', NULL, 1, '2026-03-13 08:03:04', '2026-03-15 10:02:46'),
(41, 19, 'NATURAL', 'DNI', '18198265', 'ROXANA MARILU TRELLES URQUIZA', '963632145', 1, '2026-03-13 10:52:41', '2026-03-13 10:52:41'),
(42, 19, 'NATURAL', 'DNI', '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '963232142', 1, '2026-03-13 10:56:42', '2026-03-13 10:56:42'),
(43, 19, 'NATURAL', 'DNI', '47305338', 'KARLA HELEN BELTRAN ARANDA', '964885412', 1, '2026-03-13 11:04:41', '2026-03-13 11:04:41'),
(44, 19, 'JURIDICA', 'RUC', '20482833811', 'ESCUELA DE CONDUCTORES INTEGRALES ALLAIN PROST E.I.R.L.', '965332321', 1, '2026-03-13 11:10:18', '2026-03-13 11:10:18'),
(45, 19, 'NATURAL', 'DNI', '70352321', 'TATHIANA ALEXE MARIA CAMA CAMASCA', '963323214', 1, '2026-03-13 15:27:42', '2026-03-13 15:27:42'),
(46, 19, 'NATURAL', 'DNI', '70010001', 'Juan Perez', '900111111', 1, '2026-03-14 11:53:21', '2026-03-14 11:53:21'),
(47, 19, 'NATURAL', 'CE', '70010002', 'Maria Loayza', '900111112', 1, '2026-03-14 11:54:45', '2026-03-14 11:54:45'),
(48, 19, 'NATURAL', 'DNI', '70010003', 'Luis Vargas', '900111113', 1, '2026-03-14 11:57:54', '2026-03-14 11:57:54'),
(49, 19, 'NATURAL', 'DNI', '70010005', 'Ana Ruiz', '900111115', 1, '2026-03-14 12:14:13', '2026-03-14 12:14:13'),
(50, 19, 'NATURAL', 'CE', '70010006', 'Elena Paz', '900111116', 1, '2026-03-14 12:34:19', '2026-03-14 12:34:19'),
(51, 19, 'NATURAL', 'DNI', '52719384', 'Rocío Castro Luna Arce', '912345675', 1, '2026-03-14 13:23:27', '2026-03-14 13:23:27'),
(52, 19, 'NATURAL', 'DNI', '784512309', 'Matías Herrera Campos', '912345676', 1, '2026-03-14 13:25:06', '2026-03-14 13:25:06'),
(53, 19, 'NATURAL', 'DNI', '63917420', 'Fernanda Núñez Ramos', '912345677', 1, '2026-03-14 13:28:30', '2026-03-14 13:28:30'),
(54, 19, 'NATURAL', 'DNI', '47820563', 'Kevin Torres Mejía', '912345678', 1, '2026-03-14 13:31:28', '2026-03-14 13:31:28'),
(55, 19, 'JURIDICA', 'RUC', '20574839216', 'Servicios Andinos UAT S.A.C.', '912345679', 1, '2026-03-14 19:34:48', '2026-03-14 19:34:48'),
(56, 19, 'JURIDICA', 'RUC', '20650173928', 'Constructora Nuevo Horizonte UAT S.R.L.', '912345681', 1, '2026-03-14 19:40:16', '2026-03-14 19:40:16'),
(57, 19, 'JURIDICA', 'RUC', '20948573612', 'Grupo Ferretero Tambo UAT S.R.L.', '739201458', 1, '2026-03-14 19:45:37', '2026-03-14 19:45:37'),
(58, 19, 'JURIDICA', 'RUC', '20173948526', 'Tecnored UAT Solutions S.A.C.', '912345686', 1, '2026-03-14 19:52:18', '2026-03-14 19:52:18'),
(59, 19, 'NATURAL', 'DNI', '70000001', 'JUAN PEREZ', '900111222', 1, '2026-03-14 22:48:09', '2026-03-14 22:48:09'),
(60, 19, 'NATURAL', 'DNI', '70212121', 'WILIE MARQUEZ', '963232142', 1, '2026-03-14 22:54:53', '2026-03-14 22:54:53'),
(61, 19, 'NATURAL', 'DNI', '70363241', 'LUIS FERNANDO LOPEZ VARGAS', '964121214', 1, '2026-03-15 18:38:59', '2026-03-15 18:38:59'),
(62, 19, 'NATURAL', 'DNI', '70362121', 'JUNIOR TEODORO RODRIGUEZ GUEVARA', '964881523', 1, '2026-03-15 18:40:18', '2026-03-15 18:40:18'),
(63, 19, 'NATURAL', 'DNI', '70121232', 'ISABEL ROSALI TORREJON GONZALES', NULL, 1, '2026-03-15 18:42:13', '2026-03-17 14:10:10'),
(64, 19, 'NATURAL', 'DNI', '70441214', 'LIZBETH ROXANA CAMPOS RAMOS', '964441452', 1, '2026-03-16 09:43:57', '2026-03-16 09:43:57'),
(65, 20, 'NATURAL', 'DNI', '12121212', 'PROMOTOR SUSY', NULL, 1, '2026-03-16 12:44:10', '2026-03-16 15:26:19'),
(66, 20, 'NATURAL', 'DNI', '42309191', 'HEHIVER ANTENOR REYNA SILVESTRE', NULL, 1, '2026-03-16 14:52:01', '2026-03-16 14:52:01'),
(67, 20, 'NATURAL', 'DNI', '18196115', 'TEOFILO VICTOR FLORES FLORES', NULL, 1, '2026-03-16 15:29:14', '2026-03-16 15:29:14'),
(68, 20, 'NATURAL', 'DNI', '40498468', 'LUIS YHONY SERRANO DIAZ', NULL, 1, '2026-03-16 21:23:42', '2026-03-16 21:23:42'),
(69, 20, 'NATURAL', 'DNI', '45400085', 'JHONNATTAN VENTURA CERNA', NULL, 1, '2026-03-16 21:27:01', '2026-03-16 21:27:01'),
(70, 20, 'NATURAL', 'DNI', '4854581', 'PROMOTOR ZAVALETA', NULL, 1, '2026-03-16 21:28:50', '2026-03-16 21:28:50'),
(71, 20, 'NATURAL', 'DNI', '1515318', 'PROMOTOR TRUJILLO', NULL, 1, '2026-03-16 21:30:25', '2026-03-16 21:30:25'),
(72, 20, 'NATURAL', 'DNI', '5198145', 'PROMOTORA NECKY', NULL, 1, '2026-03-16 21:31:18', '2026-03-16 21:31:18'),
(73, 20, 'NATURAL', 'DNI', '581684', 'PROMOTORA KELLY', NULL, 1, '2026-03-16 21:31:52', '2026-03-16 21:31:52'),
(74, 20, 'NATURAL', 'DNI', '78104830', 'APONTE LEIVA', NULL, 1, '2026-03-16 21:33:54', '2026-03-16 21:33:54'),
(75, 20, 'NATURAL', 'DNI', '17988823', 'AZAÑERO LLAROS', NULL, 1, '2026-03-16 21:34:59', '2026-03-16 21:34:59'),
(76, 20, 'NATURAL', 'DNI', '27161104', 'CIRO GAMANIEL APONTE CASTILLO', NULL, 1, '2026-03-16 21:35:53', '2026-03-16 21:35:53'),
(77, 20, 'NATURAL', 'DNI', '76686301', 'CAMPOS ZAVALA', NULL, 1, '2026-03-16 21:37:07', '2026-03-16 21:37:07'),
(78, 20, 'NATURAL', 'DNI', '18174136', 'DORIS RICARDINA ZAVALA ESPEJO', NULL, 1, '2026-03-16 21:37:39', '2026-03-16 21:37:39'),
(79, 20, 'NATURAL', 'DNI', '41905307', 'PORFIRIO LUCANO ACUÑA', NULL, 1, '2026-03-17 11:56:54', '2026-03-17 11:56:54'),
(80, 20, 'NATURAL', 'DNI', '19536728', 'JOSE LUIS ROMAN CRUZ', NULL, 1, '2026-03-17 11:59:46', '2026-03-17 11:59:46'),
(81, 20, 'NATURAL', 'DNI', '31652296', 'VALENTIN MORALES DEXTRE', NULL, 1, '2026-03-17 12:01:18', '2026-03-17 12:01:18'),
(82, 20, 'NATURAL', 'DNI', '43751779', 'EDWIN JAKHON CASTILLO JICARO', NULL, 1, '2026-03-17 12:03:58', '2026-03-17 12:03:58'),
(83, 20, 'NATURAL', 'DNI', '00103906', 'CARLOS MORI SALDAÑA', NULL, 1, '2026-03-17 12:04:45', '2026-03-17 12:04:45'),
(84, 20, 'NATURAL', 'DNI', '40761046', 'PERSIL VIDAL PEREZ BALTODANO', NULL, 1, '2026-03-17 12:06:34', '2026-03-17 12:06:34'),
(85, 20, 'NATURAL', 'DNI', '45243491', 'JOSE FREDDY DE LA CRUZ AZABACHE', NULL, 1, '2026-03-17 12:08:01', '2026-03-17 12:08:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_comprobantes`
--

CREATE TABLE `pos_comprobantes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `tipo` enum('VENTA','ABONO') NOT NULL,
  `modo` enum('ORIGINAL') NOT NULL DEFAULT 'ORIGINAL',
  `venta_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ticket_serie` varchar(10) NOT NULL,
  `ticket_numero` int(10) UNSIGNED NOT NULL,
  `ticket_codigo` varchar(20) NOT NULL,
  `emitido_en` datetime NOT NULL,
  `emitido_por` int(10) UNSIGNED DEFAULT NULL,
  `emitido_por_usuario` varchar(64) DEFAULT NULL,
  `emitido_por_nombre` varchar(150) DEFAULT NULL,
  `formato_default` enum('ticket80','ticket58','a4') NOT NULL DEFAULT 'ticket80',
  `snapshot_json` longtext NOT NULL,
  `exactitud` enum('EXACTO','APROXIMADO') NOT NULL DEFAULT 'EXACTO',
  `observacion` varchar(255) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_comprobantes`
--

INSERT INTO `pos_comprobantes` (`id`, `id_empresa`, `tipo`, `modo`, `venta_id`, `ticket_serie`, `ticket_numero`, `ticket_codigo`, `emitido_en`, `emitido_por`, `emitido_por_usuario`, `emitido_por_nombre`, `formato_default`, `snapshot_json`, `exactitud`, `observacion`, `creado`) VALUES
(1, 19, 'VENTA', 'ORIGINAL', 65, 'T018', 65, 'T018-0065', '2026-03-14 22:48:09', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0065\",\"serie\":\"T018\",\"numero\":65,\"fecha_raw\":\"2026-03-14 22:48:09\",\"fecha_venta_raw\":\"2026-03-14 22:48:09\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70000001\",\"doc\":\"DNI 70000001\",\"nombre\":\"JUAN PEREZ\",\"telefono\":\"900111222\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70000001\",\"doc\":\"DNI 70000001\",\"nombre\":\"JUAN PEREZ\",\"telefono\":\"900111222\"},\"items\":[{\"nombre\":\"RECA AIIA\",\"cantidad\":1,\"precio\":1200,\"total\":1200}],\"abonos\":[{\"abono_id\":77,\"aplicacion_id\":77,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":1200,\"monto_aplicado\":1200,\"monto_devuelto\":0,\"monto_neto\":1200,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-14 22:48:09\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1200,\"pagado\":1200,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":65,\"abono_ids\":[77]}}', 'EXACTO', NULL, '2026-03-14 22:48:09'),
(2, 19, 'VENTA', 'ORIGINAL', 66, 'T018', 66, 'T018-0066', '2026-03-14 22:54:53', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0066\",\"serie\":\"T018\",\"numero\":66,\"fecha_raw\":\"2026-03-14 22:54:53\",\"fecha_venta_raw\":\"2026-03-14 22:54:53\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70212121\",\"doc\":\"DNI 70212121\",\"nombre\":\"WILIE MARQUEZ\",\"telefono\":\"963232142\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70212121\",\"doc\":\"DNI 70212121\",\"nombre\":\"WILIE MARQUEZ\",\"telefono\":\"963232142\"},\"items\":[{\"nombre\":\"Taller Cambiemos de Actitud\",\"cantidad\":1,\"precio\":350,\"total\":350}],\"abonos\":[{\"abono_id\":78,\"aplicacion_id\":78,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":100,\"monto_aplicado\":100,\"monto_devuelto\":0,\"monto_neto\":100,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-14 22:54:53\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":350,\"pagado\":100,\"saldo\":250,\"devuelto\":0},\"refs\":{\"venta_id\":66,\"abono_ids\":[78]}}', 'EXACTO', NULL, '2026-03-14 22:54:53'),
(3, 19, 'ABONO', 'ORIGINAL', 66, 'T018', 66, 'T018-0066', '2026-03-14 22:55:12', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0066\",\"serie\":\"T018\",\"numero\":66,\"fecha_raw\":\"2026-03-14 22:55:12\",\"fecha_venta_raw\":\"2026-03-14 22:54:53\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70212121\",\"doc\":\"DNI 70212121\",\"nombre\":\"WILIE MARQUEZ\",\"telefono\":\"963232142\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70212121\",\"doc\":\"DNI 70212121\",\"nombre\":\"WILIE MARQUEZ\",\"telefono\":\"963232142\"},\"items\":[{\"nombre\":\"Taller Cambiemos de Actitud\",\"cantidad\":1,\"precio\":350,\"total\":350}],\"abonos\":[{\"abono_id\":79,\"aplicacion_id\":79,\"medio\":\"YAPE\",\"referencia\":\"DD-DD\",\"monto\":100,\"monto_aplicado\":100,\"monto_devuelto\":0,\"monto_neto\":100,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-14 22:55:12\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":350,\"pagado\":200,\"saldo\":150,\"devuelto\":0},\"refs\":{\"venta_id\":66,\"abono_ids\":[79]}}', 'EXACTO', NULL, '2026-03-14 22:55:12'),
(4, 19, 'ABONO', 'ORIGINAL', 66, 'T018', 66, 'T018-0066', '2026-03-14 23:01:01', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0066\",\"serie\":\"T018\",\"numero\":66,\"fecha_raw\":\"2026-03-14 23:01:01\",\"fecha_venta_raw\":\"2026-03-14 22:54:53\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70212121\",\"doc\":\"DNI 70212121\",\"nombre\":\"WILIE MARQUEZ\",\"telefono\":\"963232142\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70212121\",\"doc\":\"DNI 70212121\",\"nombre\":\"WILIE MARQUEZ\",\"telefono\":\"963232142\"},\"items\":[{\"nombre\":\"Taller Cambiemos de Actitud\",\"cantidad\":1,\"precio\":350,\"total\":350}],\"abonos\":[{\"abono_id\":80,\"aplicacion_id\":80,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":20,\"monto_aplicado\":20,\"monto_devuelto\":0,\"monto_neto\":20,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-14 23:01:01\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":350,\"pagado\":220,\"saldo\":130,\"devuelto\":0},\"refs\":{\"venta_id\":66,\"abono_ids\":[80]}}', 'EXACTO', NULL, '2026-03-14 23:01:01'),
(5, 19, 'VENTA', 'ORIGINAL', 67, 'T018', 67, 'T018-0067', '2026-03-15 01:24:18', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0067\",\"serie\":\"T018\",\"numero\":67,\"fecha_raw\":\"2026-03-15 01:24:18\",\"fecha_venta_raw\":\"2026-03-15 01:24:18\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"964881854\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"964881854\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":150,\"total\":150}],\"abonos\":[{\"abono_id\":81,\"aplicacion_id\":81,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":100,\"monto_aplicado\":100,\"monto_devuelto\":0,\"monto_neto\":100,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 01:24:18\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":150,\"pagado\":100,\"saldo\":50,\"devuelto\":0},\"refs\":{\"venta_id\":67,\"abono_ids\":[81]}}', 'EXACTO', NULL, '2026-03-15 01:24:18'),
(6, 19, 'ABONO', 'ORIGINAL', 67, 'T018', 67, 'T018-0067', '2026-03-15 01:24:18', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0067\",\"serie\":\"T018\",\"numero\":67,\"fecha_raw\":\"2026-03-15 01:24:18\",\"fecha_venta_raw\":\"2026-03-15 01:24:18\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"964881854\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"964881854\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":150,\"total\":150}],\"abonos\":[{\"abono_id\":81,\"aplicacion_id\":81,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":100,\"monto_aplicado\":100,\"monto_devuelto\":0,\"monto_neto\":100,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 01:24:18\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":150,\"pagado\":100,\"saldo\":50,\"devuelto\":0},\"refs\":{\"venta_id\":67,\"abono_ids\":[81]}}', 'EXACTO', NULL, '2026-03-15 01:24:18'),
(7, 19, 'VENTA', 'ORIGINAL', 68, 'T018', 68, 'T018-0068', '2026-03-15 10:02:46', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0068\",\"serie\":\"T018\",\"numero\":68,\"fecha_raw\":\"2026-03-15 10:02:46\",\"fecha_venta_raw\":\"2026-03-15 10:02:46\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Pasajeros\",\"cantidad\":1,\"precio\":150,\"total\":150}],\"abonos\":[{\"abono_id\":82,\"aplicacion_id\":82,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":150,\"monto_aplicado\":150,\"monto_devuelto\":0,\"monto_neto\":150,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 10:02:46\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":150,\"pagado\":150,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":68,\"abono_ids\":[82]}}', 'EXACTO', NULL, '2026-03-15 10:02:46'),
(8, 19, 'ABONO', 'ORIGINAL', 68, 'T018', 68, 'T018-0068', '2026-03-15 10:02:46', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0068\",\"serie\":\"T018\",\"numero\":68,\"fecha_raw\":\"2026-03-15 10:02:46\",\"fecha_venta_raw\":\"2026-03-15 10:02:46\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Pasajeros\",\"cantidad\":1,\"precio\":150,\"total\":150}],\"abonos\":[{\"abono_id\":82,\"aplicacion_id\":82,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":150,\"monto_aplicado\":150,\"monto_devuelto\":0,\"monto_neto\":150,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 10:02:46\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":150,\"pagado\":150,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":68,\"abono_ids\":[82]}}', 'EXACTO', NULL, '2026-03-15 10:02:46'),
(9, 19, 'ABONO', 'ORIGINAL', 68, 'T018', 68, 'T018-0068', '2026-03-15 10:06:22', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0068\",\"serie\":\"T018\",\"numero\":68,\"fecha_raw\":\"2026-03-15 10:06:22\",\"fecha_venta_raw\":\"2026-03-15 10:02:46\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Pasajeros\",\"cantidad\":1,\"precio\":150,\"total\":150}],\"abonos\":[{\"abono_id\":83,\"aplicacion_id\":83,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":100,\"monto_aplicado\":100,\"monto_devuelto\":0,\"monto_neto\":100,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 10:06:22\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":150,\"pagado\":100,\"saldo\":50,\"devuelto\":150},\"refs\":{\"venta_id\":68,\"abono_ids\":[83]}}', 'EXACTO', NULL, '2026-03-15 10:06:22'),
(10, 19, 'VENTA', 'ORIGINAL', 69, 'T018', 69, 'T018-0069', '2026-03-15 10:48:41', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0069\",\"serie\":\"T018\",\"numero\":69,\"fecha_raw\":\"2026-03-15 10:48:41\",\"fecha_venta_raw\":\"2026-03-15 10:48:41\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"RECA AIIB\",\"cantidad\":1,\"precio\":1100,\"total\":1100}],\"abonos\":[{\"abono_id\":84,\"aplicacion_id\":84,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":1100,\"monto_aplicado\":1100,\"monto_devuelto\":0,\"monto_neto\":1100,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 10:48:41\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1100,\"pagado\":1100,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":69,\"abono_ids\":[84]}}', 'EXACTO', NULL, '2026-03-15 10:48:41'),
(11, 19, 'ABONO', 'ORIGINAL', 69, 'T018', 69, 'T018-0069', '2026-03-15 10:48:41', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0069\",\"serie\":\"T018\",\"numero\":69,\"fecha_raw\":\"2026-03-15 10:48:41\",\"fecha_venta_raw\":\"2026-03-15 10:48:41\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"RECA AIIB\",\"cantidad\":1,\"precio\":1100,\"total\":1100}],\"abonos\":[{\"abono_id\":84,\"aplicacion_id\":84,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":1100,\"monto_aplicado\":1100,\"monto_devuelto\":0,\"monto_neto\":1100,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 10:48:41\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1100,\"pagado\":1100,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":69,\"abono_ids\":[84]}}', 'EXACTO', NULL, '2026-03-15 10:48:41'),
(12, 19, 'VENTA', 'ORIGINAL', 70, 'T018', 70, 'T018-0070', '2026-03-15 18:38:59', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0070\",\"serie\":\"T018\",\"numero\":70,\"fecha_raw\":\"2026-03-15 18:38:59\",\"fecha_venta_raw\":\"2026-03-15 18:38:59\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70363241\",\"doc\":\"DNI 70363241\",\"nombre\":\"LUIS FERNANDO LOPEZ VARGAS\",\"telefono\":\"964121214\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70363241\",\"doc\":\"DNI 70363241\",\"nombre\":\"LUIS FERNANDO LOPEZ VARGAS\",\"telefono\":\"964121214\"},\"items\":[{\"nombre\":\"RECA AIIIA\",\"cantidad\":1,\"precio\":1000,\"total\":1000}],\"abonos\":[{\"abono_id\":85,\"aplicacion_id\":85,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":1000,\"monto_aplicado\":1000,\"monto_devuelto\":0,\"monto_neto\":1000,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 18:38:59\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1000,\"pagado\":1000,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":70,\"abono_ids\":[85]}}', 'EXACTO', NULL, '2026-03-15 18:38:59'),
(13, 19, 'ABONO', 'ORIGINAL', 70, 'T018', 70, 'T018-0070', '2026-03-15 18:38:59', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0070\",\"serie\":\"T018\",\"numero\":70,\"fecha_raw\":\"2026-03-15 18:38:59\",\"fecha_venta_raw\":\"2026-03-15 18:38:59\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70363241\",\"doc\":\"DNI 70363241\",\"nombre\":\"LUIS FERNANDO LOPEZ VARGAS\",\"telefono\":\"964121214\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70363241\",\"doc\":\"DNI 70363241\",\"nombre\":\"LUIS FERNANDO LOPEZ VARGAS\",\"telefono\":\"964121214\"},\"items\":[{\"nombre\":\"RECA AIIIA\",\"cantidad\":1,\"precio\":1000,\"total\":1000}],\"abonos\":[{\"abono_id\":85,\"aplicacion_id\":85,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":1000,\"monto_aplicado\":1000,\"monto_devuelto\":0,\"monto_neto\":1000,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 18:38:59\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1000,\"pagado\":1000,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":70,\"abono_ids\":[85]}}', 'EXACTO', NULL, '2026-03-15 18:38:59'),
(14, 19, 'VENTA', 'ORIGINAL', 71, 'T018', 71, 'T018-0071', '2026-03-15 18:40:18', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0071\",\"serie\":\"T018\",\"numero\":71,\"fecha_raw\":\"2026-03-15 18:40:18\",\"fecha_venta_raw\":\"2026-03-15 18:40:18\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70362121\",\"doc\":\"DNI 70362121\",\"nombre\":\"JUNIOR TEODORO RODRIGUEZ GUEVARA\",\"telefono\":\"964881523\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70362121\",\"doc\":\"DNI 70362121\",\"nombre\":\"JUNIOR TEODORO RODRIGUEZ GUEVARA\",\"telefono\":\"964881523\"},\"items\":[{\"nombre\":\"RECA AIIIA\",\"cantidad\":1,\"precio\":1000,\"total\":1000}],\"abonos\":[{\"abono_id\":86,\"aplicacion_id\":86,\"medio\":\"YAPE\",\"referencia\":\"addas\",\"monto\":500,\"monto_aplicado\":500,\"monto_devuelto\":0,\"monto_neto\":500,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 18:40:18\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},{\"abono_id\":87,\"aplicacion_id\":87,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":500,\"monto_aplicado\":500,\"monto_devuelto\":0,\"monto_neto\":500,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 18:40:18\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1000,\"pagado\":1000,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":71,\"abono_ids\":[86,87]}}', 'EXACTO', NULL, '2026-03-15 18:40:18'),
(15, 19, 'ABONO', 'ORIGINAL', 71, 'T018', 71, 'T018-0071', '2026-03-15 18:40:18', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0071\",\"serie\":\"T018\",\"numero\":71,\"fecha_raw\":\"2026-03-15 18:40:18\",\"fecha_venta_raw\":\"2026-03-15 18:40:18\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70362121\",\"doc\":\"DNI 70362121\",\"nombre\":\"JUNIOR TEODORO RODRIGUEZ GUEVARA\",\"telefono\":\"964881523\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70362121\",\"doc\":\"DNI 70362121\",\"nombre\":\"JUNIOR TEODORO RODRIGUEZ GUEVARA\",\"telefono\":\"964881523\"},\"items\":[{\"nombre\":\"RECA AIIIA\",\"cantidad\":1,\"precio\":1000,\"total\":1000}],\"abonos\":[{\"abono_id\":86,\"aplicacion_id\":86,\"medio\":\"YAPE\",\"referencia\":\"addas\",\"monto\":500,\"monto_aplicado\":500,\"monto_devuelto\":0,\"monto_neto\":500,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 18:40:18\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},{\"abono_id\":87,\"aplicacion_id\":87,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":500,\"monto_aplicado\":500,\"monto_devuelto\":0,\"monto_neto\":500,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 18:40:18\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1000,\"pagado\":1000,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":71,\"abono_ids\":[86,87]}}', 'EXACTO', NULL, '2026-03-15 18:40:18'),
(16, 19, 'VENTA', 'ORIGINAL', 72, 'T018', 72, 'T018-0072', '2026-03-15 18:42:13', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0072\",\"serie\":\"T018\",\"numero\":72,\"fecha_raw\":\"2026-03-15 18:42:13\",\"fecha_venta_raw\":\"2026-03-15 18:42:13\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964112123\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964112123\"},\"items\":[{\"nombre\":\"RECA AIIIC\",\"cantidad\":1,\"precio\":1000,\"total\":1000}],\"abonos\":[{\"abono_id\":88,\"aplicacion_id\":88,\"medio\":\"TRANSFERENCIA\",\"referencia\":\"s22\",\"monto\":1000,\"monto_aplicado\":1000,\"monto_devuelto\":0,\"monto_neto\":1000,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 18:42:13\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1000,\"pagado\":1000,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":72,\"abono_ids\":[88]}}', 'EXACTO', NULL, '2026-03-15 18:42:13'),
(17, 19, 'ABONO', 'ORIGINAL', 72, 'T018', 72, 'T018-0072', '2026-03-15 18:42:13', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0072\",\"serie\":\"T018\",\"numero\":72,\"fecha_raw\":\"2026-03-15 18:42:13\",\"fecha_venta_raw\":\"2026-03-15 18:42:13\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964112123\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964112123\"},\"items\":[{\"nombre\":\"RECA AIIIC\",\"cantidad\":1,\"precio\":1000,\"total\":1000}],\"abonos\":[{\"abono_id\":88,\"aplicacion_id\":88,\"medio\":\"TRANSFERENCIA\",\"referencia\":\"s22\",\"monto\":1000,\"monto_aplicado\":1000,\"monto_devuelto\":0,\"monto_neto\":1000,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 18:42:13\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1000,\"pagado\":1000,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":72,\"abono_ids\":[88]}}', 'EXACTO', NULL, '2026-03-15 18:42:13'),
(18, 19, 'ABONO', 'ORIGINAL', 69, 'T018', 69, 'T018-0069', '2026-03-15 18:42:38', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0069\",\"serie\":\"T018\",\"numero\":69,\"fecha_raw\":\"2026-03-15 18:42:38\",\"fecha_venta_raw\":\"2026-03-15 10:48:41\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70379752\",\"doc\":\"DNI 70379752\",\"nombre\":\"LUIGI ISRAEL VILLANUEVA PEREZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"RECA AIIB\",\"cantidad\":1,\"precio\":1100,\"total\":1100}],\"abonos\":[{\"abono_id\":89,\"aplicacion_id\":89,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":500,\"monto_aplicado\":500,\"monto_devuelto\":0,\"monto_neto\":500,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 18:42:38\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1100,\"pagado\":500,\"saldo\":600,\"devuelto\":1100},\"refs\":{\"venta_id\":69,\"abono_ids\":[89]}}', 'EXACTO', NULL, '2026-03-15 18:42:38'),
(19, 19, 'VENTA', 'ORIGINAL', 73, 'T018', 73, 'T018-0073', '2026-03-15 19:08:33', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0073\",\"serie\":\"T018\",\"numero\":73,\"fecha_raw\":\"2026-03-15 19:08:33\",\"fecha_venta_raw\":\"2026-03-15 19:08:33\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"59201734\",\"doc\":\"DNI 59201734\",\"nombre\":\"MILAGROS VARGAS VARGAS\",\"telefono\":\"964881852\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"59201734\",\"doc\":\"DNI 59201734\",\"nombre\":\"MILAGROS VARGAS VARGAS\",\"telefono\":\"964881852\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":500,\"total\":500}],\"abonos\":[{\"abono_id\":90,\"aplicacion_id\":90,\"medio\":\"PLIN\",\"referencia\":\"VVV\",\"monto\":500,\"monto_aplicado\":500,\"monto_devuelto\":0,\"monto_neto\":500,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 19:08:33\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":500,\"pagado\":500,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":73,\"abono_ids\":[90]}}', 'EXACTO', NULL, '2026-03-15 19:08:33'),
(20, 19, 'ABONO', 'ORIGINAL', 73, 'T018', 73, 'T018-0073', '2026-03-15 19:08:33', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0073\",\"serie\":\"T018\",\"numero\":73,\"fecha_raw\":\"2026-03-15 19:08:33\",\"fecha_venta_raw\":\"2026-03-15 19:08:33\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"59201734\",\"doc\":\"DNI 59201734\",\"nombre\":\"MILAGROS VARGAS VARGAS\",\"telefono\":\"964881852\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"59201734\",\"doc\":\"DNI 59201734\",\"nombre\":\"MILAGROS VARGAS VARGAS\",\"telefono\":\"964881852\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":500,\"total\":500}],\"abonos\":[{\"abono_id\":90,\"aplicacion_id\":90,\"medio\":\"PLIN\",\"referencia\":\"VVV\",\"monto\":500,\"monto_aplicado\":500,\"monto_devuelto\":0,\"monto_neto\":500,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 19:08:33\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":500,\"pagado\":500,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":73,\"abono_ids\":[90]}}', 'EXACTO', NULL, '2026-03-15 19:08:33'),
(21, 19, 'VENTA', 'ORIGINAL', 74, 'T018', 74, 'T018-0074', '2026-03-15 22:17:28', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0074\",\"serie\":\"T018\",\"numero\":74,\"fecha_raw\":\"2026-03-15 22:17:28\",\"fecha_venta_raw\":\"2026-03-15 22:17:28\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"RECA AIIA\",\"cantidad\":1,\"precio\":1200,\"total\":1200}],\"abonos\":[{\"abono_id\":91,\"aplicacion_id\":91,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":1200,\"monto_aplicado\":1200,\"monto_devuelto\":0,\"monto_neto\":1200,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 22:17:28\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1200,\"pagado\":1200,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":74,\"abono_ids\":[91]}}', 'EXACTO', NULL, '2026-03-15 22:17:28'),
(22, 19, 'ABONO', 'ORIGINAL', 74, 'T018', 74, 'T018-0074', '2026-03-15 22:17:28', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0074\",\"serie\":\"T018\",\"numero\":74,\"fecha_raw\":\"2026-03-15 22:17:28\",\"fecha_venta_raw\":\"2026-03-15 22:17:28\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"RECA AIIA\",\"cantidad\":1,\"precio\":1200,\"total\":1200}],\"abonos\":[{\"abono_id\":91,\"aplicacion_id\":91,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":1200,\"monto_aplicado\":1200,\"monto_devuelto\":0,\"monto_neto\":1200,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-15 22:17:28\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1200,\"pagado\":1200,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":74,\"abono_ids\":[91]}}', 'EXACTO', NULL, '2026-03-15 22:17:28'),
(23, 19, 'VENTA', 'ORIGINAL', 75, 'T018', 75, 'T018-0075', '2026-03-16 09:43:57', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0075\",\"serie\":\"T018\",\"numero\":75,\"fecha_raw\":\"2026-03-16 09:43:57\",\"fecha_venta_raw\":\"2026-03-16 09:43:57\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441214\",\"doc\":\"DNI 70441214\",\"nombre\":\"LIZBETH ROXANA CAMPOS RAMOS\",\"telefono\":\"964441452\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441214\",\"doc\":\"DNI 70441214\",\"nombre\":\"LIZBETH ROXANA CAMPOS RAMOS\",\"telefono\":\"964441452\"},\"items\":[{\"nombre\":\"RECA AIIA\",\"cantidad\":1,\"precio\":1200,\"total\":1200}],\"abonos\":[{\"abono_id\":92,\"aplicacion_id\":92,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":1200,\"monto_aplicado\":1200,\"monto_devuelto\":0,\"monto_neto\":1200,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 09:43:57\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1200,\"pagado\":1200,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":75,\"abono_ids\":[92]}}', 'EXACTO', NULL, '2026-03-16 09:43:57'),
(24, 19, 'ABONO', 'ORIGINAL', 75, 'T018', 75, 'T018-0075', '2026-03-16 09:43:57', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0075\",\"serie\":\"T018\",\"numero\":75,\"fecha_raw\":\"2026-03-16 09:43:57\",\"fecha_venta_raw\":\"2026-03-16 09:43:57\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441214\",\"doc\":\"DNI 70441214\",\"nombre\":\"LIZBETH ROXANA CAMPOS RAMOS\",\"telefono\":\"964441452\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70441214\",\"doc\":\"DNI 70441214\",\"nombre\":\"LIZBETH ROXANA CAMPOS RAMOS\",\"telefono\":\"964441452\"},\"items\":[{\"nombre\":\"RECA AIIA\",\"cantidad\":1,\"precio\":1200,\"total\":1200}],\"abonos\":[{\"abono_id\":92,\"aplicacion_id\":92,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":1200,\"monto_aplicado\":1200,\"monto_devuelto\":0,\"monto_neto\":1200,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 09:43:57\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":1200,\"pagado\":1200,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":75,\"abono_ids\":[92]}}', 'EXACTO', NULL, '2026-03-16 09:43:57');
INSERT INTO `pos_comprobantes` (`id`, `id_empresa`, `tipo`, `modo`, `venta_id`, `ticket_serie`, `ticket_numero`, `ticket_codigo`, `emitido_en`, `emitido_por`, `emitido_por_usuario`, `emitido_por_nombre`, `formato_default`, `snapshot_json`, `exactitud`, `observacion`, `creado`) VALUES
(25, 19, 'VENTA', 'ORIGINAL', 76, 'T018', 76, 'T018-0076', '2026-03-16 10:13:58', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0076\",\"serie\":\"T018\",\"numero\":76,\"fecha_raw\":\"2026-03-16 10:13:58\",\"fecha_venta_raw\":\"2026-03-16 10:13:58\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964881842\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964881842\"},\"items\":[{\"nombre\":\"BALOTARIO\",\"cantidad\":1,\"precio\":10,\"total\":10}],\"abonos\":[{\"abono_id\":93,\"aplicacion_id\":93,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":10,\"monto_aplicado\":10,\"monto_devuelto\":0,\"monto_neto\":10,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 10:13:58\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":10,\"pagado\":10,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":76,\"abono_ids\":[93]}}', 'EXACTO', NULL, '2026-03-16 10:13:58'),
(26, 19, 'ABONO', 'ORIGINAL', 76, 'T018', 76, 'T018-0076', '2026-03-16 10:13:58', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0076\",\"serie\":\"T018\",\"numero\":76,\"fecha_raw\":\"2026-03-16 10:13:58\",\"fecha_venta_raw\":\"2026-03-16 10:13:58\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964881842\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"964881842\"},\"items\":[{\"nombre\":\"BALOTARIO\",\"cantidad\":1,\"precio\":10,\"total\":10}],\"abonos\":[{\"abono_id\":93,\"aplicacion_id\":93,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":10,\"monto_aplicado\":10,\"monto_devuelto\":0,\"monto_neto\":10,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 10:13:58\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":10,\"pagado\":10,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":76,\"abono_ids\":[93]}}', 'EXACTO', NULL, '2026-03-16 10:13:58'),
(27, 20, 'VENTA', 'ORIGINAL', 77, 'T001', 1, 'T001-0001', '2026-03-16 12:44:10', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0001\",\"serie\":\"T001\",\"numero\":1,\"fecha_raw\":\"2026-03-16 12:44:10\",\"fecha_venta_raw\":\"2026-03-16 12:44:10\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"doc\":\"DNI 12121212\",\"nombre\":\"PROMOTOR JOEL\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"42240258\",\"doc\":\"DNI 42240258\",\"nombre\":\"FRANKLIN TARDELLI MARTINEZ SOLANO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":40,\"total\":40}],\"abonos\":[{\"abono_id\":94,\"aplicacion_id\":94,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":40,\"monto_aplicado\":40,\"monto_devuelto\":0,\"monto_neto\":40,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 12:44:10\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":40,\"pagado\":40,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":77,\"abono_ids\":[94]}}', 'EXACTO', NULL, '2026-03-16 12:44:10'),
(28, 20, 'ABONO', 'ORIGINAL', 77, 'T001', 1, 'T001-0001', '2026-03-16 12:44:10', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0001\",\"serie\":\"T001\",\"numero\":1,\"fecha_raw\":\"2026-03-16 12:44:10\",\"fecha_venta_raw\":\"2026-03-16 12:44:10\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"doc\":\"DNI 12121212\",\"nombre\":\"PROMOTOR JOEL\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"42240258\",\"doc\":\"DNI 42240258\",\"nombre\":\"FRANKLIN TARDELLI MARTINEZ SOLANO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":40,\"total\":40}],\"abonos\":[{\"abono_id\":94,\"aplicacion_id\":94,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":40,\"monto_aplicado\":40,\"monto_devuelto\":0,\"monto_neto\":40,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 12:44:10\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":40,\"pagado\":40,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":77,\"abono_ids\":[94]}}', 'EXACTO', NULL, '2026-03-16 12:44:10'),
(29, 20, 'VENTA', 'ORIGINAL', 78, 'T001', 2, 'T001-0002', '2026-03-16 14:52:01', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0002\",\"serie\":\"T001\",\"numero\":2,\"fecha_raw\":\"2026-03-16 14:52:01\",\"fecha_venta_raw\":\"2026-03-16 14:52:01\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"42309191\",\"doc\":\"DNI 42309191\",\"nombre\":\"HEHIVER ANTENOR REYNA SILVESTRE\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"42309191\",\"doc\":\"DNI 42309191\",\"nombre\":\"HEHIVER ANTENOR REYNA SILVESTRE\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":150,\"total\":150}],\"abonos\":[{\"abono_id\":95,\"aplicacion_id\":95,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":150,\"monto_aplicado\":150,\"monto_devuelto\":0,\"monto_neto\":150,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 14:52:01\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":150,\"pagado\":150,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":78,\"abono_ids\":[95]}}', 'EXACTO', NULL, '2026-03-16 14:52:01'),
(30, 20, 'ABONO', 'ORIGINAL', 78, 'T001', 2, 'T001-0002', '2026-03-16 14:52:01', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0002\",\"serie\":\"T001\",\"numero\":2,\"fecha_raw\":\"2026-03-16 14:52:01\",\"fecha_venta_raw\":\"2026-03-16 14:52:01\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"42309191\",\"doc\":\"DNI 42309191\",\"nombre\":\"HEHIVER ANTENOR REYNA SILVESTRE\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"42309191\",\"doc\":\"DNI 42309191\",\"nombre\":\"HEHIVER ANTENOR REYNA SILVESTRE\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":150,\"total\":150}],\"abonos\":[{\"abono_id\":95,\"aplicacion_id\":95,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":150,\"monto_aplicado\":150,\"monto_devuelto\":0,\"monto_neto\":150,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 14:52:01\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":150,\"pagado\":150,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":78,\"abono_ids\":[95]}}', 'EXACTO', NULL, '2026-03-16 14:52:01'),
(31, 20, 'VENTA', 'ORIGINAL', 79, 'T001', 3, 'T001-0003', '2026-03-16 15:23:27', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0003\",\"serie\":\"T001\",\"numero\":3,\"fecha_raw\":\"2026-03-16 15:23:27\",\"fecha_venta_raw\":\"2026-03-16 15:23:27\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"doc\":\"DNI 12121212\",\"nombre\":\"PROMOTOR MAGUIN\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"41906002\",\"doc\":\"DNI 41906002\",\"nombre\":\"JOSE LUIS MANTILLA QUILICHE\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":96,\"aplicacion_id\":96,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 15:23:27\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":79,\"abono_ids\":[96]}}', 'EXACTO', NULL, '2026-03-16 15:23:27'),
(32, 20, 'ABONO', 'ORIGINAL', 79, 'T001', 3, 'T001-0003', '2026-03-16 15:23:27', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0003\",\"serie\":\"T001\",\"numero\":3,\"fecha_raw\":\"2026-03-16 15:23:27\",\"fecha_venta_raw\":\"2026-03-16 15:23:27\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"doc\":\"DNI 12121212\",\"nombre\":\"PROMOTOR MAGUIN\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"41906002\",\"doc\":\"DNI 41906002\",\"nombre\":\"JOSE LUIS MANTILLA QUILICHE\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":96,\"aplicacion_id\":96,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 15:23:27\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":79,\"abono_ids\":[96]}}', 'EXACTO', NULL, '2026-03-16 15:23:27'),
(33, 20, 'VENTA', 'ORIGINAL', 80, 'T001', 4, 'T001-0004', '2026-03-16 15:24:55', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0004\",\"serie\":\"T001\",\"numero\":4,\"fecha_raw\":\"2026-03-16 15:24:55\",\"fecha_venta_raw\":\"2026-03-16 15:24:55\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"doc\":\"DNI 12121212\",\"nombre\":\"PROMOTOR TRUJILLO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"47481147\",\"doc\":\"DNI 47481147\",\"nombre\":\"FRANCISCO GARCIA BRICEÑO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":97,\"aplicacion_id\":97,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 15:24:55\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":80,\"abono_ids\":[97]}}', 'EXACTO', NULL, '2026-03-16 15:24:55'),
(34, 20, 'ABONO', 'ORIGINAL', 80, 'T001', 4, 'T001-0004', '2026-03-16 15:24:55', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0004\",\"serie\":\"T001\",\"numero\":4,\"fecha_raw\":\"2026-03-16 15:24:55\",\"fecha_venta_raw\":\"2026-03-16 15:24:55\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"doc\":\"DNI 12121212\",\"nombre\":\"PROMOTOR TRUJILLO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"47481147\",\"doc\":\"DNI 47481147\",\"nombre\":\"FRANCISCO GARCIA BRICEÑO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":97,\"aplicacion_id\":97,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 15:24:55\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":80,\"abono_ids\":[97]}}', 'EXACTO', NULL, '2026-03-16 15:24:55'),
(35, 20, 'VENTA', 'ORIGINAL', 81, 'T001', 5, 'T001-0005', '2026-03-16 15:26:19', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0005\",\"serie\":\"T001\",\"numero\":5,\"fecha_raw\":\"2026-03-16 15:26:19\",\"fecha_venta_raw\":\"2026-03-16 15:26:19\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"doc\":\"DNI 12121212\",\"nombre\":\"PROMOTOR SUSY\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"46782087\",\"doc\":\"DNI 46782087\",\"nombre\":\"JILDER CORONEL DELGADO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":40,\"total\":40}],\"abonos\":[{\"abono_id\":98,\"aplicacion_id\":98,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":40,\"monto_aplicado\":40,\"monto_devuelto\":0,\"monto_neto\":40,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 15:26:19\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":40,\"pagado\":40,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":81,\"abono_ids\":[98]}}', 'EXACTO', NULL, '2026-03-16 15:26:19'),
(36, 20, 'ABONO', 'ORIGINAL', 81, 'T001', 5, 'T001-0005', '2026-03-16 15:26:19', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0005\",\"serie\":\"T001\",\"numero\":5,\"fecha_raw\":\"2026-03-16 15:26:19\",\"fecha_venta_raw\":\"2026-03-16 15:26:19\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"12121212\",\"doc\":\"DNI 12121212\",\"nombre\":\"PROMOTOR SUSY\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"46782087\",\"doc\":\"DNI 46782087\",\"nombre\":\"JILDER CORONEL DELGADO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":40,\"total\":40}],\"abonos\":[{\"abono_id\":98,\"aplicacion_id\":98,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":40,\"monto_aplicado\":40,\"monto_devuelto\":0,\"monto_neto\":40,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 15:26:19\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":40,\"pagado\":40,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":81,\"abono_ids\":[98]}}', 'EXACTO', NULL, '2026-03-16 15:26:19'),
(37, 20, 'VENTA', 'ORIGINAL', 82, 'T001', 6, 'T001-0006', '2026-03-16 15:29:14', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0006\",\"serie\":\"T001\",\"numero\":6,\"fecha_raw\":\"2026-03-16 15:29:14\",\"fecha_venta_raw\":\"2026-03-16 15:29:14\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"18196115\",\"doc\":\"DNI 18196115\",\"nombre\":\"TEOFILO VICTOR FLORES FLORES\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"18196115\",\"doc\":\"DNI 18196115\",\"nombre\":\"TEOFILO VICTOR FLORES FLORES\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"RECA AIIB\",\"cantidad\":1,\"precio\":250,\"total\":250}],\"abonos\":[{\"abono_id\":99,\"aplicacion_id\":99,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":250,\"monto_aplicado\":250,\"monto_devuelto\":0,\"monto_neto\":250,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 15:29:14\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":250,\"pagado\":250,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":82,\"abono_ids\":[99]}}', 'EXACTO', NULL, '2026-03-16 15:29:14'),
(38, 20, 'ABONO', 'ORIGINAL', 82, 'T001', 6, 'T001-0006', '2026-03-16 15:29:14', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0006\",\"serie\":\"T001\",\"numero\":6,\"fecha_raw\":\"2026-03-16 15:29:14\",\"fecha_venta_raw\":\"2026-03-16 15:29:14\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"18196115\",\"doc\":\"DNI 18196115\",\"nombre\":\"TEOFILO VICTOR FLORES FLORES\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"18196115\",\"doc\":\"DNI 18196115\",\"nombre\":\"TEOFILO VICTOR FLORES FLORES\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"RECA AIIB\",\"cantidad\":1,\"precio\":250,\"total\":250}],\"abonos\":[{\"abono_id\":99,\"aplicacion_id\":99,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":250,\"monto_aplicado\":250,\"monto_devuelto\":0,\"monto_neto\":250,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 15:29:14\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":250,\"pagado\":250,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":82,\"abono_ids\":[99]}}', 'EXACTO', NULL, '2026-03-16 15:29:14'),
(39, 20, 'VENTA', 'ORIGINAL', 83, 'T001', 7, 'T001-0007', '2026-03-16 21:23:42', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0007\",\"serie\":\"T001\",\"numero\":7,\"fecha_raw\":\"2026-03-16 21:23:42\",\"fecha_venta_raw\":\"2026-03-16 21:23:42\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"40498468\",\"doc\":\"DNI 40498468\",\"nombre\":\"LUIS YHONY SERRANO DIAZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"40498468\",\"doc\":\"DNI 40498468\",\"nombre\":\"LUIS YHONY SERRANO DIAZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":100,\"aplicacion_id\":100,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:23:42\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":83,\"abono_ids\":[100]}}', 'EXACTO', NULL, '2026-03-16 21:23:42'),
(40, 20, 'ABONO', 'ORIGINAL', 83, 'T001', 7, 'T001-0007', '2026-03-16 21:23:42', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0007\",\"serie\":\"T001\",\"numero\":7,\"fecha_raw\":\"2026-03-16 21:23:42\",\"fecha_venta_raw\":\"2026-03-16 21:23:42\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"40498468\",\"doc\":\"DNI 40498468\",\"nombre\":\"LUIS YHONY SERRANO DIAZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"40498468\",\"doc\":\"DNI 40498468\",\"nombre\":\"LUIS YHONY SERRANO DIAZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":100,\"aplicacion_id\":100,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:23:42\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":83,\"abono_ids\":[100]}}', 'EXACTO', NULL, '2026-03-16 21:23:42'),
(41, 20, 'VENTA', 'ORIGINAL', 84, 'T001', 8, 'T001-0008', '2026-03-16 21:27:01', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0008\",\"serie\":\"T001\",\"numero\":8,\"fecha_raw\":\"2026-03-16 21:27:01\",\"fecha_venta_raw\":\"2026-03-16 21:27:01\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"45400085\",\"doc\":\"DNI 45400085\",\"nombre\":\"JHONNATTAN VENTURA CERNA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"45400085\",\"doc\":\"DNI 45400085\",\"nombre\":\"JHONNATTAN VENTURA CERNA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":70,\"total\":70}],\"abonos\":[{\"abono_id\":101,\"aplicacion_id\":101,\"medio\":\"YAPE\",\"referencia\":\"58607569\",\"monto\":70,\"monto_aplicado\":70,\"monto_devuelto\":0,\"monto_neto\":70,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:27:01\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":70,\"pagado\":70,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":84,\"abono_ids\":[101]}}', 'EXACTO', NULL, '2026-03-16 21:27:01'),
(42, 20, 'ABONO', 'ORIGINAL', 84, 'T001', 8, 'T001-0008', '2026-03-16 21:27:01', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0008\",\"serie\":\"T001\",\"numero\":8,\"fecha_raw\":\"2026-03-16 21:27:01\",\"fecha_venta_raw\":\"2026-03-16 21:27:01\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"45400085\",\"doc\":\"DNI 45400085\",\"nombre\":\"JHONNATTAN VENTURA CERNA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"45400085\",\"doc\":\"DNI 45400085\",\"nombre\":\"JHONNATTAN VENTURA CERNA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":70,\"total\":70}],\"abonos\":[{\"abono_id\":101,\"aplicacion_id\":101,\"medio\":\"YAPE\",\"referencia\":\"58607569\",\"monto\":70,\"monto_aplicado\":70,\"monto_devuelto\":0,\"monto_neto\":70,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:27:01\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":70,\"pagado\":70,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":84,\"abono_ids\":[101]}}', 'EXACTO', NULL, '2026-03-16 21:27:01'),
(43, 20, 'VENTA', 'ORIGINAL', 85, 'T001', 9, 'T001-0009', '2026-03-16 21:28:50', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0009\",\"serie\":\"T001\",\"numero\":9,\"fecha_raw\":\"2026-03-16 21:28:50\",\"fecha_venta_raw\":\"2026-03-16 21:28:50\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"4854581\",\"doc\":\"DNI 4854581\",\"nombre\":\"PROMOTOR ZAVALETA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"4854581\",\"doc\":\"DNI 4854581\",\"nombre\":\"PROMOTOR ZAVALETA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":102,\"aplicacion_id\":102,\"medio\":\"YAPE\",\"referencia\":\"0805905\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:28:50\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":85,\"abono_ids\":[102]}}', 'EXACTO', NULL, '2026-03-16 21:28:50'),
(44, 20, 'ABONO', 'ORIGINAL', 85, 'T001', 9, 'T001-0009', '2026-03-16 21:28:50', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0009\",\"serie\":\"T001\",\"numero\":9,\"fecha_raw\":\"2026-03-16 21:28:50\",\"fecha_venta_raw\":\"2026-03-16 21:28:50\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"4854581\",\"doc\":\"DNI 4854581\",\"nombre\":\"PROMOTOR ZAVALETA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"4854581\",\"doc\":\"DNI 4854581\",\"nombre\":\"PROMOTOR ZAVALETA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":102,\"aplicacion_id\":102,\"medio\":\"YAPE\",\"referencia\":\"0805905\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:28:50\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":85,\"abono_ids\":[102]}}', 'EXACTO', NULL, '2026-03-16 21:28:50'),
(45, 20, 'VENTA', 'ORIGINAL', 86, 'T001', 10, 'T001-0010', '2026-03-16 21:30:25', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0010\",\"serie\":\"T001\",\"numero\":10,\"fecha_raw\":\"2026-03-16 21:30:25\",\"fecha_venta_raw\":\"2026-03-16 21:30:25\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"1515318\",\"doc\":\"DNI 1515318\",\"nombre\":\"PROMOTOR TRUJILLO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"1515318\",\"doc\":\"DNI 1515318\",\"nombre\":\"PROMOTOR TRUJILLO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":2,\"precio\":50,\"total\":100}],\"abonos\":[{\"abono_id\":103,\"aplicacion_id\":103,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:30:25\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},{\"abono_id\":104,\"aplicacion_id\":104,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:30:25\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":100,\"pagado\":100,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":86,\"abono_ids\":[103,104]}}', 'EXACTO', NULL, '2026-03-16 21:30:25'),
(46, 20, 'ABONO', 'ORIGINAL', 86, 'T001', 10, 'T001-0010', '2026-03-16 21:30:25', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0010\",\"serie\":\"T001\",\"numero\":10,\"fecha_raw\":\"2026-03-16 21:30:25\",\"fecha_venta_raw\":\"2026-03-16 21:30:25\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"1515318\",\"doc\":\"DNI 1515318\",\"nombre\":\"PROMOTOR TRUJILLO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"1515318\",\"doc\":\"DNI 1515318\",\"nombre\":\"PROMOTOR TRUJILLO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":2,\"precio\":50,\"total\":100}],\"abonos\":[{\"abono_id\":103,\"aplicacion_id\":103,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:30:25\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},{\"abono_id\":104,\"aplicacion_id\":104,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:30:25\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":100,\"pagado\":100,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":86,\"abono_ids\":[103,104]}}', 'EXACTO', NULL, '2026-03-16 21:30:25'),
(47, 20, 'VENTA', 'ORIGINAL', 87, 'T001', 11, 'T001-0011', '2026-03-16 21:31:18', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0011\",\"serie\":\"T001\",\"numero\":11,\"fecha_raw\":\"2026-03-16 21:31:18\",\"fecha_venta_raw\":\"2026-03-16 21:31:18\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"5198145\",\"doc\":\"DNI 5198145\",\"nombre\":\"PROMOTORA NECKY\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"5198145\",\"doc\":\"DNI 5198145\",\"nombre\":\"PROMOTORA NECKY\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":105,\"aplicacion_id\":105,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:31:18\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":87,\"abono_ids\":[105]}}', 'EXACTO', NULL, '2026-03-16 21:31:18'),
(48, 20, 'ABONO', 'ORIGINAL', 87, 'T001', 11, 'T001-0011', '2026-03-16 21:31:18', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0011\",\"serie\":\"T001\",\"numero\":11,\"fecha_raw\":\"2026-03-16 21:31:18\",\"fecha_venta_raw\":\"2026-03-16 21:31:18\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"5198145\",\"doc\":\"DNI 5198145\",\"nombre\":\"PROMOTORA NECKY\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"5198145\",\"doc\":\"DNI 5198145\",\"nombre\":\"PROMOTORA NECKY\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":105,\"aplicacion_id\":105,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:31:18\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":87,\"abono_ids\":[105]}}', 'EXACTO', NULL, '2026-03-16 21:31:18');
INSERT INTO `pos_comprobantes` (`id`, `id_empresa`, `tipo`, `modo`, `venta_id`, `ticket_serie`, `ticket_numero`, `ticket_codigo`, `emitido_en`, `emitido_por`, `emitido_por_usuario`, `emitido_por_nombre`, `formato_default`, `snapshot_json`, `exactitud`, `observacion`, `creado`) VALUES
(49, 20, 'VENTA', 'ORIGINAL', 88, 'T001', 12, 'T001-0012', '2026-03-16 21:31:52', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0012\",\"serie\":\"T001\",\"numero\":12,\"fecha_raw\":\"2026-03-16 21:31:52\",\"fecha_venta_raw\":\"2026-03-16 21:31:52\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"581684\",\"doc\":\"DNI 581684\",\"nombre\":\"PROMOTORA KELLY\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"581684\",\"doc\":\"DNI 581684\",\"nombre\":\"PROMOTORA KELLY\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":106,\"aplicacion_id\":106,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:31:52\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":88,\"abono_ids\":[106]}}', 'EXACTO', NULL, '2026-03-16 21:31:52'),
(50, 20, 'ABONO', 'ORIGINAL', 88, 'T001', 12, 'T001-0012', '2026-03-16 21:31:52', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0012\",\"serie\":\"T001\",\"numero\":12,\"fecha_raw\":\"2026-03-16 21:31:52\",\"fecha_venta_raw\":\"2026-03-16 21:31:52\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"581684\",\"doc\":\"DNI 581684\",\"nombre\":\"PROMOTORA KELLY\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"581684\",\"doc\":\"DNI 581684\",\"nombre\":\"PROMOTORA KELLY\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":106,\"aplicacion_id\":106,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:31:52\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":88,\"abono_ids\":[106]}}', 'EXACTO', NULL, '2026-03-16 21:31:52'),
(51, 20, 'VENTA', 'ORIGINAL', 89, 'T001', 13, 'T001-0013', '2026-03-16 21:33:54', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0013\",\"serie\":\"T001\",\"numero\":13,\"fecha_raw\":\"2026-03-16 21:33:54\",\"fecha_venta_raw\":\"2026-03-16 21:33:54\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"78104830\",\"doc\":\"DNI 78104830\",\"nombre\":\"APONTE LEIVA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"78104830\",\"doc\":\"DNI 78104830\",\"nombre\":\"APONTE LEIVA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":107,\"aplicacion_id\":107,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":100,\"monto_aplicado\":100,\"monto_devuelto\":0,\"monto_neto\":100,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:33:54\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},{\"abono_id\":108,\"aplicacion_id\":108,\"medio\":\"YAPE\",\"referencia\":\"997\",\"monto\":30,\"monto_aplicado\":30,\"monto_devuelto\":0,\"monto_neto\":30,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:33:54\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":89,\"abono_ids\":[107,108]}}', 'EXACTO', NULL, '2026-03-16 21:33:54'),
(52, 20, 'ABONO', 'ORIGINAL', 89, 'T001', 13, 'T001-0013', '2026-03-16 21:33:54', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0013\",\"serie\":\"T001\",\"numero\":13,\"fecha_raw\":\"2026-03-16 21:33:54\",\"fecha_venta_raw\":\"2026-03-16 21:33:54\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"78104830\",\"doc\":\"DNI 78104830\",\"nombre\":\"APONTE LEIVA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"78104830\",\"doc\":\"DNI 78104830\",\"nombre\":\"APONTE LEIVA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":107,\"aplicacion_id\":107,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":100,\"monto_aplicado\":100,\"monto_devuelto\":0,\"monto_neto\":100,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:33:54\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},{\"abono_id\":108,\"aplicacion_id\":108,\"medio\":\"YAPE\",\"referencia\":\"997\",\"monto\":30,\"monto_aplicado\":30,\"monto_devuelto\":0,\"monto_neto\":30,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:33:54\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":89,\"abono_ids\":[107,108]}}', 'EXACTO', NULL, '2026-03-16 21:33:54'),
(53, 20, 'VENTA', 'ORIGINAL', 90, 'T001', 14, 'T001-0014', '2026-03-16 21:34:59', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0014\",\"serie\":\"T001\",\"numero\":14,\"fecha_raw\":\"2026-03-16 21:34:59\",\"fecha_venta_raw\":\"2026-03-16 21:34:59\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"17988823\",\"doc\":\"DNI 17988823\",\"nombre\":\"AZAÑERO LLAROS\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"17988823\",\"doc\":\"DNI 17988823\",\"nombre\":\"AZAÑERO LLAROS\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":109,\"aplicacion_id\":109,\"medio\":\"YAPE\",\"referencia\":\"357\",\"monto\":130,\"monto_aplicado\":130,\"monto_devuelto\":0,\"monto_neto\":130,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:34:59\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":90,\"abono_ids\":[109]}}', 'EXACTO', NULL, '2026-03-16 21:34:59'),
(54, 20, 'ABONO', 'ORIGINAL', 90, 'T001', 14, 'T001-0014', '2026-03-16 21:34:59', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0014\",\"serie\":\"T001\",\"numero\":14,\"fecha_raw\":\"2026-03-16 21:34:59\",\"fecha_venta_raw\":\"2026-03-16 21:34:59\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"17988823\",\"doc\":\"DNI 17988823\",\"nombre\":\"AZAÑERO LLAROS\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"17988823\",\"doc\":\"DNI 17988823\",\"nombre\":\"AZAÑERO LLAROS\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":109,\"aplicacion_id\":109,\"medio\":\"YAPE\",\"referencia\":\"357\",\"monto\":130,\"monto_aplicado\":130,\"monto_devuelto\":0,\"monto_neto\":130,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:34:59\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":90,\"abono_ids\":[109]}}', 'EXACTO', NULL, '2026-03-16 21:34:59'),
(55, 20, 'VENTA', 'ORIGINAL', 91, 'T001', 15, 'T001-0015', '2026-03-16 21:35:53', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0015\",\"serie\":\"T001\",\"numero\":15,\"fecha_raw\":\"2026-03-16 21:35:53\",\"fecha_venta_raw\":\"2026-03-16 21:35:53\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"27161104\",\"doc\":\"DNI 27161104\",\"nombre\":\"CIRO GAMANIEL APONTE CASTILLO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"27161104\",\"doc\":\"DNI 27161104\",\"nombre\":\"CIRO GAMANIEL APONTE CASTILLO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":110,\"aplicacion_id\":110,\"medio\":\"YAPE\",\"referencia\":\"893\",\"monto\":130,\"monto_aplicado\":130,\"monto_devuelto\":0,\"monto_neto\":130,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:35:53\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":91,\"abono_ids\":[110]}}', 'EXACTO', NULL, '2026-03-16 21:35:53'),
(56, 20, 'ABONO', 'ORIGINAL', 91, 'T001', 15, 'T001-0015', '2026-03-16 21:35:53', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0015\",\"serie\":\"T001\",\"numero\":15,\"fecha_raw\":\"2026-03-16 21:35:53\",\"fecha_venta_raw\":\"2026-03-16 21:35:53\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"27161104\",\"doc\":\"DNI 27161104\",\"nombre\":\"CIRO GAMANIEL APONTE CASTILLO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"27161104\",\"doc\":\"DNI 27161104\",\"nombre\":\"CIRO GAMANIEL APONTE CASTILLO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":110,\"aplicacion_id\":110,\"medio\":\"YAPE\",\"referencia\":\"893\",\"monto\":130,\"monto_aplicado\":130,\"monto_devuelto\":0,\"monto_neto\":130,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:35:53\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":91,\"abono_ids\":[110]}}', 'EXACTO', NULL, '2026-03-16 21:35:53'),
(57, 20, 'VENTA', 'ORIGINAL', 92, 'T001', 16, 'T001-0016', '2026-03-16 21:37:07', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0016\",\"serie\":\"T001\",\"numero\":16,\"fecha_raw\":\"2026-03-16 21:37:07\",\"fecha_venta_raw\":\"2026-03-16 21:37:07\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"76686301\",\"doc\":\"DNI 76686301\",\"nombre\":\"CAMPOS ZAVALA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"76686301\",\"doc\":\"DNI 76686301\",\"nombre\":\"CAMPOS ZAVALA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":111,\"aplicacion_id\":111,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":120,\"monto_aplicado\":120,\"monto_devuelto\":0,\"monto_neto\":120,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:37:07\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},{\"abono_id\":112,\"aplicacion_id\":112,\"medio\":\"YAPE\",\"referencia\":\"423\",\"monto\":10,\"monto_aplicado\":10,\"monto_devuelto\":0,\"monto_neto\":10,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:37:07\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":92,\"abono_ids\":[111,112]}}', 'EXACTO', NULL, '2026-03-16 21:37:07'),
(58, 20, 'ABONO', 'ORIGINAL', 92, 'T001', 16, 'T001-0016', '2026-03-16 21:37:07', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0016\",\"serie\":\"T001\",\"numero\":16,\"fecha_raw\":\"2026-03-16 21:37:07\",\"fecha_venta_raw\":\"2026-03-16 21:37:07\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"76686301\",\"doc\":\"DNI 76686301\",\"nombre\":\"CAMPOS ZAVALA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"76686301\",\"doc\":\"DNI 76686301\",\"nombre\":\"CAMPOS ZAVALA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":111,\"aplicacion_id\":111,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":120,\"monto_aplicado\":120,\"monto_devuelto\":0,\"monto_neto\":120,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:37:07\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},{\"abono_id\":112,\"aplicacion_id\":112,\"medio\":\"YAPE\",\"referencia\":\"423\",\"monto\":10,\"monto_aplicado\":10,\"monto_devuelto\":0,\"monto_neto\":10,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:37:07\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":92,\"abono_ids\":[111,112]}}', 'EXACTO', NULL, '2026-03-16 21:37:07'),
(59, 20, 'VENTA', 'ORIGINAL', 93, 'T001', 17, 'T001-0017', '2026-03-16 21:37:39', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0017\",\"serie\":\"T001\",\"numero\":17,\"fecha_raw\":\"2026-03-16 21:37:39\",\"fecha_venta_raw\":\"2026-03-16 21:37:39\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"18174136\",\"doc\":\"DNI 18174136\",\"nombre\":\"DORIS RICARDINA ZAVALA ESPEJO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"18174136\",\"doc\":\"DNI 18174136\",\"nombre\":\"DORIS RICARDINA ZAVALA ESPEJO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":113,\"aplicacion_id\":113,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":130,\"monto_aplicado\":130,\"monto_devuelto\":0,\"monto_neto\":130,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:37:39\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":93,\"abono_ids\":[113]}}', 'EXACTO', NULL, '2026-03-16 21:37:39'),
(60, 20, 'ABONO', 'ORIGINAL', 93, 'T001', 17, 'T001-0017', '2026-03-16 21:37:39', 19, '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0017\",\"serie\":\"T001\",\"numero\":17,\"fecha_raw\":\"2026-03-16 21:37:39\",\"fecha_venta_raw\":\"2026-03-16 21:37:39\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":19,\"cajero_operacion_usuario\":\"71252952\",\"cajero_operacion_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\",\"reimpreso_por_id\":19,\"reimpreso_por_usuario\":\"71252952\",\"reimpreso_por_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"18174136\",\"doc\":\"DNI 18174136\",\"nombre\":\"DORIS RICARDINA ZAVALA ESPEJO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"18174136\",\"doc\":\"DNI 18174136\",\"nombre\":\"DORIS RICARDINA ZAVALA ESPEJO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":130,\"total\":130}],\"abonos\":[{\"abono_id\":113,\"aplicacion_id\":113,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":130,\"monto_aplicado\":130,\"monto_devuelto\":0,\"monto_neto\":130,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-16 21:37:39\",\"creado_por\":19,\"creado_usuario\":\"71252952\",\"creado_nombre\":\"JHEFERSON ALESSANDRO RODRIGUEZ PAREDES\"}],\"totales\":{\"total\":130,\"pagado\":130,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":93,\"abono_ids\":[113]}}', 'EXACTO', NULL, '2026-03-16 21:37:39'),
(61, 20, 'VENTA', 'ORIGINAL', 94, 'T001', 18, 'T001-0018', '2026-03-17 11:56:54', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0018\",\"serie\":\"T001\",\"numero\":18,\"fecha_raw\":\"2026-03-17 11:56:54\",\"fecha_venta_raw\":\"2026-03-17 11:56:54\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"41905307\",\"doc\":\"DNI 41905307\",\"nombre\":\"PORFIRIO LUCANO ACUÑA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"41905307\",\"doc\":\"DNI 41905307\",\"nombre\":\"PORFIRIO LUCANO ACUÑA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":114,\"aplicacion_id\":114,\"medio\":\"YAPE\",\"referencia\":\"7:41\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 11:56:54\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":94,\"abono_ids\":[114]}}', 'EXACTO', NULL, '2026-03-17 11:56:54'),
(62, 20, 'ABONO', 'ORIGINAL', 94, 'T001', 18, 'T001-0018', '2026-03-17 11:56:54', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0018\",\"serie\":\"T001\",\"numero\":18,\"fecha_raw\":\"2026-03-17 11:56:54\",\"fecha_venta_raw\":\"2026-03-17 11:56:54\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"41905307\",\"doc\":\"DNI 41905307\",\"nombre\":\"PORFIRIO LUCANO ACUÑA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"41905307\",\"doc\":\"DNI 41905307\",\"nombre\":\"PORFIRIO LUCANO ACUÑA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":114,\"aplicacion_id\":114,\"medio\":\"YAPE\",\"referencia\":\"7:41\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 11:56:54\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":94,\"abono_ids\":[114]}}', 'EXACTO', NULL, '2026-03-17 11:56:54'),
(63, 20, 'VENTA', 'ORIGINAL', 95, 'T001', 19, 'T001-0019', '2026-03-17 11:59:46', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0019\",\"serie\":\"T001\",\"numero\":19,\"fecha_raw\":\"2026-03-17 11:59:46\",\"fecha_venta_raw\":\"2026-03-17 11:59:46\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"19536728\",\"doc\":\"DNI 19536728\",\"nombre\":\"JOSE LUIS ROMAN CRUZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"19536728\",\"doc\":\"DNI 19536728\",\"nombre\":\"JOSE LUIS ROMAN CRUZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":70,\"total\":70}],\"abonos\":[{\"abono_id\":115,\"aplicacion_id\":115,\"medio\":\"YAPE\",\"referencia\":\"738\",\"monto\":70,\"monto_aplicado\":70,\"monto_devuelto\":0,\"monto_neto\":70,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 11:59:46\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":70,\"pagado\":70,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":95,\"abono_ids\":[115]}}', 'EXACTO', NULL, '2026-03-17 11:59:46'),
(64, 20, 'ABONO', 'ORIGINAL', 95, 'T001', 19, 'T001-0019', '2026-03-17 11:59:46', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0019\",\"serie\":\"T001\",\"numero\":19,\"fecha_raw\":\"2026-03-17 11:59:46\",\"fecha_venta_raw\":\"2026-03-17 11:59:46\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"19536728\",\"doc\":\"DNI 19536728\",\"nombre\":\"JOSE LUIS ROMAN CRUZ\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"19536728\",\"doc\":\"DNI 19536728\",\"nombre\":\"JOSE LUIS ROMAN CRUZ\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":70,\"total\":70}],\"abonos\":[{\"abono_id\":115,\"aplicacion_id\":115,\"medio\":\"YAPE\",\"referencia\":\"738\",\"monto\":70,\"monto_aplicado\":70,\"monto_devuelto\":0,\"monto_neto\":70,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 11:59:46\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":70,\"pagado\":70,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":95,\"abono_ids\":[115]}}', 'EXACTO', NULL, '2026-03-17 11:59:46'),
(65, 20, 'VENTA', 'ORIGINAL', 96, 'T001', 20, 'T001-0020', '2026-03-17 12:01:18', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0020\",\"serie\":\"T001\",\"numero\":20,\"fecha_raw\":\"2026-03-17 12:01:18\",\"fecha_venta_raw\":\"2026-03-17 12:01:18\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"31652296\",\"doc\":\"DNI 31652296\",\"nombre\":\"VALENTIN MORALES DEXTRE\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"31652296\",\"doc\":\"DNI 31652296\",\"nombre\":\"VALENTIN MORALES DEXTRE\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":116,\"aplicacion_id\":116,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:01:18\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":96,\"abono_ids\":[116]}}', 'EXACTO', NULL, '2026-03-17 12:01:18'),
(66, 20, 'ABONO', 'ORIGINAL', 96, 'T001', 20, 'T001-0020', '2026-03-17 12:01:18', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0020\",\"serie\":\"T001\",\"numero\":20,\"fecha_raw\":\"2026-03-17 12:01:18\",\"fecha_venta_raw\":\"2026-03-17 12:01:18\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"31652296\",\"doc\":\"DNI 31652296\",\"nombre\":\"VALENTIN MORALES DEXTRE\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"31652296\",\"doc\":\"DNI 31652296\",\"nombre\":\"VALENTIN MORALES DEXTRE\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":116,\"aplicacion_id\":116,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:01:18\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":96,\"abono_ids\":[116]}}', 'EXACTO', NULL, '2026-03-17 12:01:18'),
(67, 20, 'VENTA', 'ORIGINAL', 97, 'T001', 21, 'T001-0021', '2026-03-17 12:03:58', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0021\",\"serie\":\"T001\",\"numero\":21,\"fecha_raw\":\"2026-03-17 12:03:58\",\"fecha_venta_raw\":\"2026-03-17 12:03:58\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"43751779\",\"doc\":\"DNI 43751779\",\"nombre\":\"EDWIN JAKHON CASTILLO JICARO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"43751779\",\"doc\":\"DNI 43751779\",\"nombre\":\"EDWIN JAKHON CASTILLO JICARO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":60,\"total\":60}],\"abonos\":[{\"abono_id\":117,\"aplicacion_id\":117,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":60,\"monto_aplicado\":60,\"monto_devuelto\":0,\"monto_neto\":60,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:03:58\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":60,\"pagado\":60,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":97,\"abono_ids\":[117]}}', 'EXACTO', NULL, '2026-03-17 12:03:58'),
(68, 20, 'ABONO', 'ORIGINAL', 97, 'T001', 21, 'T001-0021', '2026-03-17 12:03:58', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0021\",\"serie\":\"T001\",\"numero\":21,\"fecha_raw\":\"2026-03-17 12:03:58\",\"fecha_venta_raw\":\"2026-03-17 12:03:58\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"43751779\",\"doc\":\"DNI 43751779\",\"nombre\":\"EDWIN JAKHON CASTILLO JICARO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"43751779\",\"doc\":\"DNI 43751779\",\"nombre\":\"EDWIN JAKHON CASTILLO JICARO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":60,\"total\":60}],\"abonos\":[{\"abono_id\":117,\"aplicacion_id\":117,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":60,\"monto_aplicado\":60,\"monto_devuelto\":0,\"monto_neto\":60,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:03:58\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":60,\"pagado\":60,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":97,\"abono_ids\":[117]}}', 'EXACTO', NULL, '2026-03-17 12:03:58'),
(69, 20, 'VENTA', 'ORIGINAL', 98, 'T001', 22, 'T001-0022', '2026-03-17 12:04:45', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0022\",\"serie\":\"T001\",\"numero\":22,\"fecha_raw\":\"2026-03-17 12:04:45\",\"fecha_venta_raw\":\"2026-03-17 12:04:45\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"00103906\",\"doc\":\"DNI 00103906\",\"nombre\":\"CARLOS MORI SALDAÑA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"00103906\",\"doc\":\"DNI 00103906\",\"nombre\":\"CARLOS MORI SALDAÑA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":60,\"total\":60}],\"abonos\":[{\"abono_id\":118,\"aplicacion_id\":118,\"medio\":\"YAPE\",\"referencia\":\"735\",\"monto\":60,\"monto_aplicado\":60,\"monto_devuelto\":0,\"monto_neto\":60,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:04:45\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":60,\"pagado\":60,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":98,\"abono_ids\":[118]}}', 'EXACTO', NULL, '2026-03-17 12:04:45'),
(70, 20, 'ABONO', 'ORIGINAL', 98, 'T001', 22, 'T001-0022', '2026-03-17 12:04:45', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0022\",\"serie\":\"T001\",\"numero\":22,\"fecha_raw\":\"2026-03-17 12:04:45\",\"fecha_venta_raw\":\"2026-03-17 12:04:45\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"00103906\",\"doc\":\"DNI 00103906\",\"nombre\":\"CARLOS MORI SALDAÑA\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"00103906\",\"doc\":\"DNI 00103906\",\"nombre\":\"CARLOS MORI SALDAÑA\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":60,\"total\":60}],\"abonos\":[{\"abono_id\":118,\"aplicacion_id\":118,\"medio\":\"YAPE\",\"referencia\":\"735\",\"monto\":60,\"monto_aplicado\":60,\"monto_devuelto\":0,\"monto_neto\":60,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:04:45\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":60,\"pagado\":60,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":98,\"abono_ids\":[118]}}', 'EXACTO', NULL, '2026-03-17 12:04:45'),
(71, 20, 'VENTA', 'ORIGINAL', 99, 'T001', 23, 'T001-0023', '2026-03-17 12:06:34', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0023\",\"serie\":\"T001\",\"numero\":23,\"fecha_raw\":\"2026-03-17 12:06:34\",\"fecha_venta_raw\":\"2026-03-17 12:06:34\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"40761046\",\"doc\":\"DNI 40761046\",\"nombre\":\"PERSIL VIDAL PEREZ BALTODANO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"40761046\",\"doc\":\"DNI 40761046\",\"nombre\":\"PERSIL VIDAL PEREZ BALTODANO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":119,\"aplicacion_id\":119,\"medio\":\"YAPE\",\"referencia\":\"745\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:06:34\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":99,\"abono_ids\":[119]}}', 'EXACTO', NULL, '2026-03-17 12:06:34');
INSERT INTO `pos_comprobantes` (`id`, `id_empresa`, `tipo`, `modo`, `venta_id`, `ticket_serie`, `ticket_numero`, `ticket_codigo`, `emitido_en`, `emitido_por`, `emitido_por_usuario`, `emitido_por_nombre`, `formato_default`, `snapshot_json`, `exactitud`, `observacion`, `creado`) VALUES
(72, 20, 'ABONO', 'ORIGINAL', 99, 'T001', 23, 'T001-0023', '2026-03-17 12:06:34', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0023\",\"serie\":\"T001\",\"numero\":23,\"fecha_raw\":\"2026-03-17 12:06:34\",\"fecha_venta_raw\":\"2026-03-17 12:06:34\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"40761046\",\"doc\":\"DNI 40761046\",\"nombre\":\"PERSIL VIDAL PEREZ BALTODANO\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"40761046\",\"doc\":\"DNI 40761046\",\"nombre\":\"PERSIL VIDAL PEREZ BALTODANO\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":50,\"total\":50}],\"abonos\":[{\"abono_id\":119,\"aplicacion_id\":119,\"medio\":\"YAPE\",\"referencia\":\"745\",\"monto\":50,\"monto_aplicado\":50,\"monto_devuelto\":0,\"monto_neto\":50,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:06:34\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":50,\"pagado\":50,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":99,\"abono_ids\":[119]}}', 'EXACTO', NULL, '2026-03-17 12:06:34'),
(73, 20, 'VENTA', 'ORIGINAL', 100, 'T001', 24, 'T001-0024', '2026-03-17 12:08:01', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0024\",\"serie\":\"T001\",\"numero\":24,\"fecha_raw\":\"2026-03-17 12:08:01\",\"fecha_venta_raw\":\"2026-03-17 12:08:01\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"45243491\",\"doc\":\"DNI 45243491\",\"nombre\":\"JOSE FREDDY DE LA CRUZ AZABACHE\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"45243491\",\"doc\":\"DNI 45243491\",\"nombre\":\"JOSE FREDDY DE LA CRUZ AZABACHE\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":60,\"total\":60}],\"abonos\":[{\"abono_id\":120,\"aplicacion_id\":120,\"medio\":\"YAPE\",\"referencia\":\"726\",\"monto\":60,\"monto_aplicado\":60,\"monto_devuelto\":0,\"monto_neto\":60,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:08:01\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":60,\"pagado\":60,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":100,\"abono_ids\":[120]}}', 'EXACTO', NULL, '2026-03-17 12:08:01'),
(74, 20, 'ABONO', 'ORIGINAL', 100, 'T001', 24, 'T001-0024', '2026-03-17 12:08:01', 18, '75806539', 'ANDY JAVIER ROJAS CUBAS', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"GUIA MIS RUTAS TRUJILLO\",\"razon_social\":\"ASOCIACION GUIA MIS RUTAS\",\"ruc\":\"20477238336\",\"direccion\":\"-\",\"logo_path\":\"almacen/2026/03/09/img_logos_empresas/logo-empresa-empresa-20-20260309T190710-a8c7d0.png\"},\"meta\":{\"ticket\":\"T001-0024\",\"serie\":\"T001\",\"numero\":24,\"fecha_raw\":\"2026-03-17 12:08:01\",\"fecha_venta_raw\":\"2026-03-17 12:08:01\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":18,\"cajero_operacion_usuario\":\"75806539\",\"cajero_operacion_nombre\":\"ANDY JAVIER ROJAS CUBAS\",\"reimpreso_por_id\":18,\"reimpreso_por_usuario\":\"75806539\",\"reimpreso_por_nombre\":\"ANDY JAVIER ROJAS CUBAS\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"45243491\",\"doc\":\"DNI 45243491\",\"nombre\":\"JOSE FREDDY DE LA CRUZ AZABACHE\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"45243491\",\"doc\":\"DNI 45243491\",\"nombre\":\"JOSE FREDDY DE LA CRUZ AZABACHE\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Carga\",\"cantidad\":1,\"precio\":60,\"total\":60}],\"abonos\":[{\"abono_id\":120,\"aplicacion_id\":120,\"medio\":\"YAPE\",\"referencia\":\"726\",\"monto\":60,\"monto_aplicado\":60,\"monto_devuelto\":0,\"monto_neto\":60,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 12:08:01\",\"creado_por\":18,\"creado_usuario\":\"75806539\",\"creado_nombre\":\"ANDY JAVIER ROJAS CUBAS\"}],\"totales\":{\"total\":60,\"pagado\":60,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":100,\"abono_ids\":[120]}}', 'EXACTO', NULL, '2026-03-17 12:08:01'),
(75, 19, 'VENTA', 'ORIGINAL', 101, 'T018', 77, 'T018-0077', '2026-03-17 14:10:10', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0077\",\"serie\":\"T018\",\"numero\":77,\"fecha_raw\":\"2026-03-17 14:10:10\",\"fecha_venta_raw\":\"2026-03-17 14:10:10\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":500,\"total\":500}],\"abonos\":[{\"abono_id\":121,\"aplicacion_id\":121,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":500,\"monto_aplicado\":500,\"monto_devuelto\":0,\"monto_neto\":500,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 14:10:10\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":500,\"pagado\":500,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":101,\"abono_ids\":[121]}}', 'EXACTO', NULL, '2026-03-17 14:10:10'),
(76, 19, 'ABONO', 'ORIGINAL', 101, 'T018', 77, 'T018-0077', '2026-03-17 14:10:10', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0077\",\"serie\":\"T018\",\"numero\":77,\"fecha_raw\":\"2026-03-17 14:10:10\",\"fecha_venta_raw\":\"2026-03-17 14:10:10\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"MOTO BIIC\",\"cantidad\":1,\"precio\":500,\"total\":500}],\"abonos\":[{\"abono_id\":121,\"aplicacion_id\":121,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":500,\"monto_aplicado\":500,\"monto_devuelto\":0,\"monto_neto\":500,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 14:10:10\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":500,\"pagado\":500,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":101,\"abono_ids\":[121]}}', 'EXACTO', NULL, '2026-03-17 14:10:10'),
(77, 19, 'VENTA', 'ORIGINAL', 102, 'T018', 78, 'T018-0078', '2026-03-17 14:34:16', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"venta\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0078\",\"serie\":\"T018\",\"numero\":78,\"fecha_raw\":\"2026-03-17 14:34:16\",\"fecha_venta_raw\":\"2026-03-17 14:34:16\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Pasajeros\",\"cantidad\":1,\"precio\":150,\"total\":150}],\"abonos\":[{\"abono_id\":122,\"aplicacion_id\":122,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":150,\"monto_aplicado\":150,\"monto_devuelto\":0,\"monto_neto\":150,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 14:34:16\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":150,\"pagado\":150,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":102,\"abono_ids\":[122]}}', 'EXACTO', NULL, '2026-03-17 14:34:16'),
(78, 19, 'ABONO', 'ORIGINAL', 102, 'T018', 78, 'T018-0078', '2026-03-17 14:34:16', 10, '12121212', 'JOSE ALBERTO VARGAS CHAVEZ', 'ticket80', '{\"version\":1,\"kind\":\"abono\",\"scope\":\"original\",\"exactitud\":\"EXACTO\",\"empresa\":{\"nombre\":\"LSISTEMAS\",\"razon_social\":\"LUIGI SISTEMAS\",\"ruc\":\"20601111111\",\"direccion\":\"Calle 8 de septiembre #1345\",\"logo_path\":\"almacen/2026/03/03/img_logos_empresas/logo-empresa-empresa-19-20260303T225708-f14484.png\"},\"meta\":{\"ticket\":\"T018-0078\",\"serie\":\"T018\",\"numero\":78,\"fecha_raw\":\"2026-03-17 14:34:16\",\"fecha_venta_raw\":\"2026-03-17 14:34:16\",\"alcance\":\"original\",\"alcance_label\":\"ORIGINAL\",\"estado_venta\":\"EMITIDA\",\"exactitud\":\"EXACTO\",\"cajero_operacion_id\":10,\"cajero_operacion_usuario\":\"12121212\",\"cajero_operacion_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\",\"reimpreso_por_id\":10,\"reimpreso_por_usuario\":\"12121212\",\"reimpreso_por_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"},\"cliente\":{\"tipo_persona\":\"NATURAL\",\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"contratante\":{\"doc_tipo\":\"\",\"doc_numero\":\"\",\"doc\":\"\",\"nombre\":\"\",\"telefono\":\"\"},\"conductor\":{\"doc_tipo\":\"DNI\",\"doc_numero\":\"70121232\",\"doc\":\"DNI 70121232\",\"nombre\":\"ISABEL ROSALI TORREJON GONZALES\",\"telefono\":\"\"},\"items\":[{\"nombre\":\"Curso de actualización - Pasajeros\",\"cantidad\":1,\"precio\":150,\"total\":150}],\"abonos\":[{\"abono_id\":122,\"aplicacion_id\":122,\"medio\":\"EFECTIVO\",\"referencia\":\"\",\"monto\":150,\"monto_aplicado\":150,\"monto_devuelto\":0,\"monto_neto\":150,\"estado_code\":\"APLICADO\",\"estado_text\":\"Aplicado\",\"fecha\":\"2026-03-17 14:34:16\",\"creado_por\":10,\"creado_usuario\":\"12121212\",\"creado_nombre\":\"JOSE ALBERTO VARGAS CHAVEZ\"}],\"totales\":{\"total\":150,\"pagado\":150,\"saldo\":0,\"devuelto\":0},\"refs\":{\"venta_id\":102,\"abono_ids\":[122]}}', 'EXACTO', NULL, '2026-03-17 14:34:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_comprobante_abonos`
--

CREATE TABLE `pos_comprobante_abonos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `comprobante_id` bigint(20) UNSIGNED NOT NULL,
  `abono_id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `monto_aplicado_snapshot` decimal(14,2) NOT NULL DEFAULT 0.00,
  `creado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_comprobante_abonos`
--

INSERT INTO `pos_comprobante_abonos` (`id`, `comprobante_id`, `abono_id`, `venta_id`, `monto_aplicado_snapshot`, `creado`) VALUES
(1, 3, 79, 66, 100.00, '2026-03-14 22:55:12'),
(2, 4, 80, 66, 20.00, '2026-03-14 23:01:01'),
(3, 6, 81, 67, 100.00, '2026-03-15 01:24:18'),
(4, 8, 82, 68, 150.00, '2026-03-15 10:02:46'),
(5, 9, 83, 68, 100.00, '2026-03-15 10:06:22'),
(6, 11, 84, 69, 1100.00, '2026-03-15 10:48:41'),
(7, 13, 85, 70, 1000.00, '2026-03-15 18:38:59'),
(8, 15, 86, 71, 500.00, '2026-03-15 18:40:18'),
(9, 15, 87, 71, 500.00, '2026-03-15 18:40:18'),
(10, 17, 88, 72, 1000.00, '2026-03-15 18:42:13'),
(11, 18, 89, 69, 500.00, '2026-03-15 18:42:38'),
(12, 20, 90, 73, 500.00, '2026-03-15 19:08:33'),
(13, 22, 91, 74, 1200.00, '2026-03-15 22:17:28'),
(14, 24, 92, 75, 1200.00, '2026-03-16 09:43:57'),
(15, 26, 93, 76, 10.00, '2026-03-16 10:13:58'),
(16, 28, 94, 77, 40.00, '2026-03-16 12:44:10'),
(17, 30, 95, 78, 150.00, '2026-03-16 14:52:01'),
(18, 32, 96, 79, 50.00, '2026-03-16 15:23:27'),
(19, 34, 97, 80, 50.00, '2026-03-16 15:24:55'),
(20, 36, 98, 81, 40.00, '2026-03-16 15:26:19'),
(21, 38, 99, 82, 250.00, '2026-03-16 15:29:14'),
(22, 40, 100, 83, 50.00, '2026-03-16 21:23:42'),
(23, 42, 101, 84, 70.00, '2026-03-16 21:27:01'),
(24, 44, 102, 85, 50.00, '2026-03-16 21:28:50'),
(25, 46, 103, 86, 50.00, '2026-03-16 21:30:25'),
(26, 46, 104, 86, 50.00, '2026-03-16 21:30:25'),
(27, 48, 105, 87, 50.00, '2026-03-16 21:31:18'),
(28, 50, 106, 88, 50.00, '2026-03-16 21:31:52'),
(29, 52, 107, 89, 100.00, '2026-03-16 21:33:54'),
(30, 52, 108, 89, 30.00, '2026-03-16 21:33:54'),
(31, 54, 109, 90, 130.00, '2026-03-16 21:34:59'),
(32, 56, 110, 91, 130.00, '2026-03-16 21:35:53'),
(33, 58, 111, 92, 120.00, '2026-03-16 21:37:07'),
(34, 58, 112, 92, 10.00, '2026-03-16 21:37:07'),
(35, 60, 113, 93, 130.00, '2026-03-16 21:37:39'),
(36, 62, 114, 94, 50.00, '2026-03-17 11:56:54'),
(37, 64, 115, 95, 70.00, '2026-03-17 11:59:46'),
(38, 66, 116, 96, 50.00, '2026-03-17 12:01:18'),
(39, 68, 117, 97, 60.00, '2026-03-17 12:03:58'),
(40, 70, 118, 98, 60.00, '2026-03-17 12:04:45'),
(41, 72, 119, 99, 50.00, '2026-03-17 12:06:34'),
(42, 74, 120, 100, 60.00, '2026-03-17 12:08:01'),
(43, 76, 121, 101, 500.00, '2026-03-17 14:10:10'),
(44, 78, 122, 102, 150.00, '2026-03-17 14:34:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_conductores`
--

CREATE TABLE `pos_conductores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `doc_tipo` enum('DNI','CE','PAS','BREVETE') NOT NULL,
  `doc_numero` varchar(20) NOT NULL,
  `nombres` varchar(120) NOT NULL,
  `apellidos` varchar(120) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_conductores`
--

INSERT INTO `pos_conductores` (`id`, `id_empresa`, `doc_tipo`, `doc_numero`, `nombres`, `apellidos`, `telefono`, `email`, `activo`, `creado`, `actualizado`) VALUES
(1, 19, 'DNI', '70379796', 'JULIAN', 'ALVAREZ MORALES', '965652321', NULL, 1, '2026-03-03 23:29:46', '2026-03-03 23:29:46'),
(2, 19, 'DNI', '72525212', 'ASDASDAS', 'ASDASD', '965652541', NULL, 1, '2026-03-03 23:31:54', '2026-03-03 23:31:54'),
(3, 19, 'DNI', '70366365', 'JULIAN', 'ALVAREZ MANA', '965541421', NULL, 1, '2026-03-04 00:34:34', '2026-03-04 00:34:34'),
(4, 19, 'DNI', '72554145', 'MELISA', 'RODRIGUEZ', '966653254', NULL, 1, '2026-03-04 10:05:28', '2026-03-04 10:05:28'),
(5, 19, 'DNI', '48392015', 'Carlos', 'Mendoza Ríos', '987654321', NULL, 1, '2026-03-04 11:25:54', '2026-03-04 11:25:54'),
(6, 19, 'DNI', '44556677', 'Juan', 'Perez', '966363623', NULL, 1, '2026-03-04 13:32:25', '2026-03-04 13:32:25'),
(7, 19, 'DNI', '70363636', 'Americo', 'Barrios Canto', '966366632', NULL, 1, '2026-03-04 13:35:38', '2026-03-04 13:35:38'),
(8, 19, 'DNI', '41414525', 'ANA LUCIA', 'JARA PEREZ', '966363636', NULL, 1, '2026-03-04 14:16:27', '2026-03-04 14:16:27'),
(9, 19, 'DNI', '50504012', 'ALEXANDRA', 'ALAMA VASQUEZ', '966363254', NULL, 1, '2026-03-04 21:29:45', '2026-03-04 21:29:45'),
(10, 19, 'CE', '90303060233', 'JULIAN', 'PEREZ PEREZ', '966636254', NULL, 1, '2026-03-05 16:09:22', '2026-03-05 16:09:22'),
(11, 19, 'DNI', '70000000', 'PEPITO', 'RUIZ JUAREZ', '965555555', NULL, 1, '2026-03-05 16:19:50', '2026-03-05 16:19:50'),
(12, 19, 'DNI', '70020005', 'Pedro', 'Soto', '911222331', NULL, 1, '2026-03-14 12:14:13', '2026-03-14 12:14:13'),
(13, 19, 'DNI', '46820517', 'Bruno', 'Soto Aguilar', '923456781', NULL, 1, '2026-03-14 13:23:27', '2026-03-14 13:23:27'),
(14, 19, 'BREVETE', 'B90817263', 'Andrea', 'Peña Cárdenas', '923456782', NULL, 1, '2026-03-14 13:25:06', '2026-03-14 13:25:06'),
(15, 19, 'DNI', '57284019', 'Santiago', 'Vega Lozano', '923456783', NULL, 1, '2026-03-14 13:28:30', '2026-03-14 13:28:30'),
(16, 19, 'CE', '659301842', 'Lucía', 'Suárez Pinto', '923456784', NULL, 1, '2026-03-14 13:31:28', '2026-03-14 13:31:28'),
(17, 19, 'BREVETE', 'B61529407', 'Iván', 'Quinteros Ledesma', '923456786', NULL, 1, '2026-03-14 19:45:37', '2026-03-14 19:45:37'),
(18, 19, 'DNI', '54839271', 'Carla', 'Bautista Romero', '923456788', NULL, 1, '2026-03-14 19:52:18', '2026-03-14 19:52:18'),
(19, 20, 'DNI', '42240258', 'FRANKLIN TARDELLI', 'MARTINEZ SOLANO', NULL, NULL, 1, '2026-03-16 12:44:10', '2026-03-16 12:44:10'),
(20, 20, 'DNI', '41906002', 'JOSE LUIS', 'MANTILLA QUILICHE', NULL, NULL, 1, '2026-03-16 15:23:27', '2026-03-16 15:23:27'),
(21, 20, 'DNI', '47481147', 'FRANCISCO', 'GARCIA BRICEÑO', NULL, NULL, 1, '2026-03-16 15:24:55', '2026-03-16 15:24:55'),
(22, 20, 'DNI', '46782087', 'JILDER', 'CORONEL DELGADO', NULL, NULL, 1, '2026-03-16 15:26:19', '2026-03-16 15:26:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_devoluciones`
--

CREATE TABLE `pos_devoluciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `caja_diaria_id` int(10) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `abono_aplicacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `medio_id` int(10) UNSIGNED NOT NULL,
  `monto_devuelto` decimal(14,2) NOT NULL,
  `referencia` varchar(80) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `devuelto_por` int(10) UNSIGNED DEFAULT NULL,
  `devuelto_en` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_devoluciones`
--

INSERT INTO `pos_devoluciones` (`id`, `id_empresa`, `caja_diaria_id`, `venta_id`, `abono_aplicacion_id`, `medio_id`, `monto_devuelto`, `referencia`, `motivo`, `devuelto_por`, `devuelto_en`) VALUES
(1, 19, 7, 43, 44, 1, 150.00, NULL, 'Su huella no pasa.', 10, '2026-03-13 08:03:27'),
(2, 19, 7, 45, 46, 4, 500.00, NULL, 'La huella del cliente no pasaba', 10, '2026-03-13 10:59:26'),
(3, 19, 7, 45, 47, 3, 400.00, NULL, 'La huella del cliente no pasaba', 10, '2026-03-13 10:59:26'),
(4, 19, 8, 65, 77, 1, 1200.00, NULL, 'tyutyuytu', 10, '2026-03-14 22:50:06'),
(5, 19, 8, 67, 81, 1, 100.00, NULL, 'ikk', 10, '2026-03-15 01:42:36'),
(6, 19, 8, 66, 78, 1, 100.00, NULL, 'asdasd', 10, '2026-03-15 02:41:45'),
(7, 19, 8, 66, 79, 2, 100.00, NULL, 'asdasd', 10, '2026-03-15 02:41:45'),
(8, 19, 8, 66, 80, 1, 20.00, NULL, 'asdasd', 10, '2026-03-15 02:41:45'),
(9, 19, 9, 68, 82, 1, 150.00, NULL, 'lllllllllllllllll', 10, '2026-03-15 10:04:51'),
(10, 19, 9, 68, 83, 1, 100.00, NULL, 'l{ñ{', 10, '2026-03-15 10:08:13'),
(11, 19, 9, 69, 84, 1, 1100.00, NULL, 'ddddddddddddd', 10, '2026-03-15 10:49:07'),
(12, 19, 10, 76, 93, 1, 10.00, NULL, 'ssssssssss', 10, '2026-03-16 10:14:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_medios_pago`
--

CREATE TABLE `pos_medios_pago` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `requiere_ref` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_medios_pago`
--

INSERT INTO `pos_medios_pago` (`id`, `nombre`, `requiere_ref`, `activo`, `creado`, `actualizado`) VALUES
(1, 'EFECTIVO', 0, 1, '2025-10-05 20:20:08', '2025-10-05 20:20:08'),
(2, 'YAPE', 1, 1, '2025-10-05 20:20:08', '2025-10-05 20:20:08'),
(3, 'PLIN', 1, 1, '2025-10-05 20:20:08', '2025-10-05 20:20:08'),
(4, 'TRANSFERENCIA', 1, 1, '2025-10-05 20:20:08', '2025-10-05 20:20:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_perfil_conductor`
--

CREATE TABLE `pos_perfil_conductor` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `doc_tipo` enum('DNI','CE','BREVETE') NOT NULL,
  `doc_numero` varchar(20) NOT NULL,
  `canal` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `nacimiento` date DEFAULT NULL,
  `categoria_auto_id` smallint(5) UNSIGNED DEFAULT NULL,
  `categoria_moto_id` smallint(5) UNSIGNED DEFAULT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_perfil_conductor`
--

INSERT INTO `pos_perfil_conductor` (`id`, `id_empresa`, `doc_tipo`, `doc_numero`, `canal`, `email`, `nacimiento`, `categoria_auto_id`, `categoria_moto_id`, `nota`, `creado`, `actualizado`) VALUES
(1, 19, 'DNI', '59201734', 'WHATSAPP', 'juan.ramirez@mail.com', '1995-05-12', 1, NULL, 'Interesado en curso básico', '2026-03-05 09:56:05', '2026-03-05 09:56:05'),
(2, 19, 'DNI', '52625214', 'LLAMADA', 'a@gmail.com', '1999-09-06', 5, 9, 'El señor viajará pronto.', '2026-03-05 13:40:04', '2026-03-05 13:40:04'),
(3, 19, 'DNI', '50201214', 'LLAMADA', 'qqq@gmail.com', '1980-09-09', 3, 9, 'Señor interesado en revalidar.', '2026-03-05 13:42:27', '2026-03-05 13:42:27'),
(4, 19, 'CE', '90303060233', 'TIKTOK', 'aaa@gmail.com', '1975-08-08', 4, 9, 'Vio un tiktok y le dio risa, entonces vino al local.', '2026-03-05 16:09:22', '2026-03-05 16:09:22'),
(5, 19, 'DNI', '70000000', 'TIKTOK', 'a@gmail.com', '1998-05-05', 4, 8, 'VIO UN TIKTOK Y LE GUSTÓ', '2026-03-05 16:19:50', '2026-03-05 16:19:50'),
(6, 19, 'DNI', '78888876', 'FACEBOOK', NULL, '1999-03-04', 3, 10, 'El señor viene de una municipalidad posible convenio', '2026-03-10 11:43:19', '2026-03-10 11:43:19'),
(7, 19, 'DNI', '70010003', 'WHATSAPP', 'luis.test@demo.com', '1993-04-10', NULL, NULL, 'cliente satisfecho. Volverá.', '2026-03-14 11:57:54', '2026-03-14 11:57:54'),
(8, 19, 'DNI', '57284019', 'SMS', 'santiago.vega+uatf401@example.com', '1993-11-27', NULL, NULL, 'Enviar aviso por SMS', '2026-03-14 13:28:30', '2026-03-14 13:28:30'),
(9, 19, 'CE', '659301842', 'SMS', 'lucia.suarez+uatf402@example.com', '2001-05-09', NULL, NULL, 'Correo de confirmación requerido', '2026-03-14 13:31:28', '2026-03-14 13:31:28'),
(10, 19, 'DNI', '68492017', 'WHATSAPP', 'renzo.flores+uatf601@example.com', '1985-01-22', 2, NULL, 'Enviar constancia firmada', '2026-03-14 19:40:16', '2026-03-14 19:40:16'),
(11, 19, 'DNI', '54839271', 'SMS', 'carla.bautista+uatf802@example.com', '1998-05-12', 4, 9, 'Notificar por SMS al conductor', '2026-03-14 19:56:31', '2026-03-14 19:56:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_series`
--

CREATE TABLE `pos_series` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `tipo_comprobante` enum('TICKET') NOT NULL DEFAULT 'TICKET',
  `serie` varchar(10) NOT NULL,
  `siguiente_numero` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_series`
--

INSERT INTO `pos_series` (`id`, `id_empresa`, `tipo_comprobante`, `serie`, `siguiente_numero`, `activo`, `creado`, `actualizado`) VALUES
(1, 19, 'TICKET', 'T018', 79, 1, '2026-03-04 00:31:54', '2026-03-17 14:34:16'),
(2, 20, 'TICKET', 'T001', 25, 1, '2026-03-16 01:05:48', '2026-03-17 12:08:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_ventas`
--

CREATE TABLE `pos_ventas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `caja_diaria_id` int(10) UNSIGNED NOT NULL,
  `serie_id` int(10) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_snapshot_tipo_persona` enum('NATURAL','JURIDICA') DEFAULT NULL,
  `cliente_snapshot_doc_tipo` enum('DNI','RUC','CE','PAS','BREVETE') DEFAULT NULL,
  `cliente_snapshot_doc_numero` varchar(20) DEFAULT NULL,
  `cliente_snapshot_nombre` varchar(200) DEFAULT NULL,
  `cliente_snapshot_telefono` varchar(30) DEFAULT NULL,
  `contratante_doc_tipo` enum('DNI','CE','BREVETE') DEFAULT NULL,
  `contratante_doc_numero` varchar(32) DEFAULT NULL,
  `contratante_nombres` varchar(120) DEFAULT NULL,
  `contratante_apellidos` varchar(120) DEFAULT NULL,
  `contratante_telefono` varchar(30) DEFAULT NULL,
  `tipo_comprobante` enum('TICKET') NOT NULL DEFAULT 'TICKET',
  `serie` varchar(10) NOT NULL,
  `numero` int(10) UNSIGNED NOT NULL,
  `fecha_emision` datetime NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'PEN',
  `estado` enum('EMITIDA','ANULADA') NOT NULL DEFAULT 'EMITIDA',
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_pagado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_devuelto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `saldo` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tiene_precio_temporal` tinyint(1) NOT NULL DEFAULT 0,
  `observacion` varchar(255) DEFAULT NULL,
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_ventas`
--

INSERT INTO `pos_ventas` (`id`, `id_empresa`, `caja_diaria_id`, `serie_id`, `cliente_id`, `cliente_snapshot_tipo_persona`, `cliente_snapshot_doc_tipo`, `cliente_snapshot_doc_numero`, `cliente_snapshot_nombre`, `cliente_snapshot_telefono`, `contratante_doc_tipo`, `contratante_doc_numero`, `contratante_nombres`, `contratante_apellidos`, `contratante_telefono`, `tipo_comprobante`, `serie`, `numero`, `fecha_emision`, `moneda`, `estado`, `total`, `total_pagado`, `total_devuelto`, `saldo`, `tiene_precio_temporal`, `observacion`, `creado_por`, `creado`, `actualizado`) VALUES
(1, 19, 1, 1, 3, 'NATURAL', 'DNI', '70366365', 'Maria Lopez', '966362532', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 1, '2026-03-04 00:34:34', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 00:34:34', '2026-03-14 16:41:30'),
(2, 19, 2, 1, 4, 'NATURAL', 'DNI', '72554145', 'MELISA RODRIGUEZ', '966653254', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 2, '2026-03-04 10:05:28', 'PEN', 'EMITIDA', 500.00, 200.00, 0.00, 300.00, 0, NULL, 10, '2026-03-04 10:05:28', '2026-03-14 16:41:30'),
(3, 19, 2, 1, 5, 'NATURAL', 'DNI', '48392015', 'Carlos Mendoza Ríos', '987654321', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 3, '2026-03-04 11:25:54', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 11:25:54', '2026-03-14 16:41:30'),
(4, 19, 2, 1, 6, 'NATURAL', 'DNI', '52412514', 'KIMBERLY FLORES LOPEZ', '965252145', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 4, '2026-03-04 13:29:25', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 13:29:25', '2026-03-14 16:41:30'),
(5, 19, 2, 1, 3, 'NATURAL', 'DNI', '70366365', 'Maria Lopez', '966362532', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 5, '2026-03-04 13:32:25', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 13:32:25', '2026-03-14 16:41:30'),
(6, 19, 2, 1, 7, 'JURIDICA', 'RUC', '20603562514', 'EMPRESA CONSTRUCTORA SAC', '9', 'DNI', '58585474', 'Luis', 'Aguilar', '9', 'TICKET', 'T018', 6, '2026-03-04 13:35:38', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 13:35:38', '2026-03-14 16:41:30'),
(7, 19, 2, 1, 8, 'NATURAL', 'CE', '48392716', 'Diego Ramírez Soto', '912345678', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 7, '2026-03-04 13:38:28', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 13:38:28', '2026-03-14 16:41:30'),
(8, 19, 2, 1, 9, 'NATURAL', 'DNI', '41414251', 'JULIO VELASQUEZ QUESQUEN', '966565214', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 8, '2026-03-04 14:16:27', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 14:16:27', '2026-03-14 16:41:30'),
(9, 19, 2, 1, 10, 'NATURAL', 'DNI', '70111141', 'Maricarmen Villalobos Alfaro', '9633632541', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 9, '2026-03-04 15:15:37', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 15:15:37', '2026-03-14 16:41:30'),
(10, 19, 2, 1, 11, 'NATURAL', 'BREVETE', 'B63635478', 'MARIA ELENA COBARRUBIAS ALVA', '966663574', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 10, '2026-03-04 15:23:39', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 15:23:39', '2026-03-14 16:41:30'),
(11, 19, 2, 1, 12, 'NATURAL', 'DNI', '70414215', 'CAMILA PAREDES GONZALES', '966565415', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 11, '2026-03-04 15:53:20', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 15:53:20', '2026-03-14 16:41:30'),
(12, 19, 2, 1, 13, 'NATURAL', 'DNI', '41141251', 'CRISTIANM CASTRO CARRILLO', '965211412', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 12, '2026-03-04 16:03:27', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 16:03:27', '2026-03-14 16:41:30'),
(13, 19, 2, 1, 14, 'NATURAL', 'DNI', '71114121', 'julian juarez juvenal', '963323214', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 13, '2026-03-04 16:04:52', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 16:04:52', '2026-03-14 16:41:30'),
(14, 19, 2, 1, 15, 'NATURAL', 'DNI', '70414125', 'ALBERTO BARROS BAILON', '966363251', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 14, '2026-03-04 16:08:54', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 16:08:54', '2026-03-14 16:41:30'),
(15, 19, 2, 1, 16, 'NATURAL', 'DNI', '70252541', 'melisa perez juarez', '963323214', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 15, '2026-03-04 16:16:37', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 16:16:37', '2026-03-14 16:41:30'),
(16, 19, 2, 1, 17, 'NATURAL', 'DNI', '70333236', 'ROBERTO BLADES JUAREZ', '965554474', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 16, '2026-03-04 16:18:00', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-04 16:18:00', '2026-03-14 16:41:30'),
(17, 19, 2, 1, 18, 'NATURAL', 'BREVETE', 'A63635412', 'CAMILA AMERICA', '96478547', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 17, '2026-03-04 21:29:45', 'PEN', 'EMITIDA', 1200.00, 1000.00, 0.00, 200.00, 0, NULL, 10, '2026-03-04 21:29:45', '2026-03-14 16:41:30'),
(18, 19, 3, 1, 19, 'NATURAL', 'BREVETE', 'Q34567891', 'Luis Vargas Soto', '965874123', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 18, '2026-03-05 09:42:20', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-05 09:42:20', '2026-03-14 16:41:30'),
(19, 19, 3, 1, 20, 'NATURAL', 'DNI', '59201734', 'Andrea Torres Vega', '944785236', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 19, '2026-03-05 09:56:05', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-05 09:56:05', '2026-03-14 16:41:30'),
(20, 19, 3, 1, 21, 'NATURAL', 'DNI', '52625214', 'VICENTE CARDENAS CARDENAS', '963332145', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 20, '2026-03-05 13:40:04', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-05 13:40:04', '2026-03-14 16:41:30'),
(21, 19, 3, 1, 22, 'NATURAL', 'DNI', '50201214', 'Miguel Mariños Marcial', '963323214', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 21, '2026-03-05 13:42:27', 'PEN', 'EMITIDA', 1700.00, 1200.00, 0.00, 500.00, 0, NULL, 10, '2026-03-05 13:42:27', '2026-03-14 16:41:30'),
(22, 19, 3, 1, 23, 'NATURAL', 'DNI', '70555562', 'JUAN LUIS GUERRA PAZ', '963333632', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 22, '2026-03-05 15:23:03', 'PEN', 'EMITIDA', 1200.00, 712.00, 0.00, 488.00, 0, NULL, 10, '2026-03-05 15:23:03', '2026-03-14 16:41:30'),
(23, 19, 3, 1, 24, 'NATURAL', 'DNI', '70333321', 'George Washington', '963323214', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 23, '2026-03-05 16:09:22', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-05 16:09:22', '2026-03-14 16:41:30'),
(24, 19, 3, 1, 25, 'NATURAL', 'DNI', '703333215', 'JUAN JUAREZ JORA', '969696854', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 24, '2026-03-05 16:19:50', 'PEN', 'EMITIDA', 1200.00, 1000.00, 0.00, 200.00, 0, NULL, 10, '2026-03-05 16:19:50', '2026-03-14 16:41:30'),
(25, 19, 4, 1, 26, 'NATURAL', 'DNI', '70554411', 'ELOISA MARTINEZ FERNANDEZ', '963323214', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 25, '2026-03-06 17:46:04', 'PEN', 'EMITIDA', 4800.00, 4800.00, 0.00, 0.00, 0, NULL, 10, '2026-03-06 17:46:04', '2026-03-14 16:41:30'),
(26, 19, 5, 1, 27, 'NATURAL', 'DNI', '70333362', 'CRISTIAN SOTO SOL', '963332321', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 26, '2026-03-10 09:16:36', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-10 09:16:36', '2026-03-14 16:41:30'),
(27, 19, 5, 1, 28, 'NATURAL', 'DNI', '70555542', 'JUAN LUIS VARGAS VARGAS', '963332142', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 27, '2026-03-10 09:27:00', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-10 09:27:00', '2026-03-14 16:41:30'),
(28, 19, 5, 1, 29, 'NATURAL', 'DNI', '78888765', 'JUAN VARGAS VARGAS', '988887654', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 28, '2026-03-10 11:34:08', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-10 11:34:08', '2026-03-14 16:41:30'),
(29, 19, 5, 1, 30, 'NATURAL', 'BREVETE', 'B76654543', 'JUANA GONZALES PEREZ', 'null', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 29, '2026-03-10 11:35:57', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-10 11:35:57', '2026-03-14 16:41:30'),
(30, 19, 5, 1, 31, 'NATURAL', 'DNI', '78888876', 'LUIS VILLANUEVA', '964555532', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 30, '2026-03-10 11:43:19', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-10 11:43:19', '2026-03-14 16:41:30'),
(31, 19, 6, 1, 32, 'NATURAL', 'DNI', '77889966', 'Luigi Villanueva', '964881842', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 31, '2026-03-12 22:37:03', 'PEN', 'EMITIDA', 1050.00, 0.00, 0.00, 1050.00, 0, NULL, 10, '2026-03-12 22:37:03', '2026-03-14 16:41:30'),
(32, 19, 6, 1, 32, 'NATURAL', 'DNI', '77889966', 'Luigi Villanueva', '964881842', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 32, '2026-03-12 22:38:23', 'PEN', 'EMITIDA', 1050.00, 0.00, 0.00, 1050.00, 0, NULL, 10, '2026-03-12 22:38:23', '2026-03-14 16:41:30'),
(33, 19, 6, 1, 33, 'NATURAL', 'DNI', '70444444', 'JUANA FLORES MARQUEZ', '964445124', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 33, '2026-03-12 22:39:15', 'PEN', 'EMITIDA', 500.00, 0.00, 0.00, 500.00, 0, NULL, 10, '2026-03-12 22:39:15', '2026-03-14 16:41:30'),
(34, 19, 6, 1, 32, 'NATURAL', 'DNI', '77889966', 'Luigi Villanueva', '964881842', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 34, '2026-03-12 22:51:20', 'PEN', 'EMITIDA', 1200.00, 0.00, 0.00, 1200.00, 0, NULL, 10, '2026-03-12 22:51:20', '2026-03-14 16:41:30'),
(35, 19, 6, 1, 32, 'NATURAL', 'DNI', '77889966', 'Luigi Villanueva', '964881842', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 35, '2026-03-12 22:51:22', 'PEN', 'EMITIDA', 1200.00, 0.00, 0.00, 1200.00, 0, NULL, 10, '2026-03-12 22:51:22', '2026-03-14 16:41:30'),
(36, 19, 6, 1, 32, 'NATURAL', 'DNI', '77889966', 'Luigi Villanueva', '964881842', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 36, '2026-03-12 22:58:24', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-12 22:58:24', '2026-03-14 16:41:30'),
(37, 19, 6, 1, 34, 'NATURAL', 'DNI', '70441336', 'Anastacia León León', '963332321', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 37, '2026-03-12 23:01:16', 'PEN', 'EMITIDA', 1050.00, 500.00, 0.00, 550.00, 1, NULL, 10, '2026-03-12 23:01:16', '2026-03-14 16:41:30'),
(38, 19, 6, 1, 35, 'NATURAL', 'DNI', '70441212', 'LUIS GUERRA GUERRA', 'null', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 38, '2026-03-12 23:59:00', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-12 23:59:00', '2026-03-14 16:41:30'),
(39, 19, 6, 1, 36, 'NATURAL', 'DNI', '70555523', 'LUIS GUERRA PAZ', 'null', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 39, '2026-03-13 00:00:06', 'PEN', 'EMITIDA', 1200.00, 0.00, 0.00, 1200.00, 0, NULL, 10, '2026-03-13 00:00:06', '2026-03-14 16:41:30'),
(40, 19, 7, 1, 37, 'NATURAL', 'DNI', '70525142', 'DIANA PAZ', 'null', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 40, '2026-03-13 00:18:03', 'PEN', 'EMITIDA', 150.00, 150.00, 0.00, 0.00, 0, NULL, 10, '2026-03-13 00:18:03', '2026-03-14 16:41:30'),
(41, 19, 7, 1, 38, 'NATURAL', 'DNI', '70123625', 'SANDRA ERIKA MONTOYA CAMARGO', '966635263', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 41, '2026-03-13 01:41:01', 'PEN', 'EMITIDA', 600.00, 500.00, 0.00, 100.00, 0, NULL, 10, '2026-03-13 01:41:01', '2026-03-14 16:41:30'),
(42, 19, 7, 1, 39, 'NATURAL', 'DNI', '70455253', 'CYNTHIA MARIA CARMEN ROUILLON', '963323214', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 42, '2026-03-13 01:43:02', 'PEN', 'EMITIDA', 150.00, 150.00, 0.00, 0.00, 0, NULL, 10, '2026-03-13 01:43:02', '2026-03-14 16:41:30'),
(43, 19, 7, 1, 40, 'NATURAL', 'DNI', '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', '964881841', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 43, '2026-03-13 08:03:04', 'PEN', 'ANULADA', 150.00, 0.00, 150.00, 0.00, 0, ' | DEVOLUCIÓN: Su huella no pasa.', 10, '2026-03-13 08:03:04', '2026-03-15 07:37:08'),
(44, 19, 7, 1, 41, 'NATURAL', 'DNI', '18198265', 'ROXANA MARILU TRELLES URQUIZA', '963632145', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 44, '2026-03-13 10:52:41', 'PEN', 'EMITIDA', 10.00, 10.00, 0.00, 0.00, 0, NULL, 10, '2026-03-13 10:52:41', '2026-03-14 16:41:30'),
(45, 19, 7, 1, 42, 'NATURAL', 'DNI', '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', '963232142', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 45, '2026-03-13 10:56:42', 'PEN', 'ANULADA', 900.00, 0.00, 900.00, 0.00, 1, ' | DEVOLUCIÓN: La huella del cliente no pasaba', 10, '2026-03-13 10:56:42', '2026-03-15 07:37:08'),
(46, 19, 7, 1, 43, 'NATURAL', 'DNI', '47305338', 'KARLA HELEN BELTRAN ARANDA', '964885412', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 46, '2026-03-13 11:04:41', 'PEN', 'EMITIDA', 150.00, 150.00, 0.00, 0.00, 0, NULL, 10, '2026-03-13 11:04:41', '2026-03-14 16:41:30'),
(47, 19, 7, 1, 44, 'JURIDICA', 'RUC', '20482833811', 'ESCUELA DE CONDUCTORES INTEGRALES ALLAIN PROST E.I.R.L.', '965332321', 'CE', '7036365214', 'LUIS', 'PAREDES PAREDES', '965332321', 'TICKET', 'T018', 47, '2026-03-13 11:10:18', 'PEN', 'EMITIDA', 1100.00, 1100.00, 0.00, 0.00, 0, NULL, 10, '2026-03-13 11:10:18', '2026-03-14 16:41:30'),
(48, 19, 7, 1, 45, 'NATURAL', 'DNI', '70352321', 'TATHIANA ALEXE MARIA CAMA CAMASCA', '963323214', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 48, '2026-03-13 15:27:42', 'PEN', 'EMITIDA', 150.00, 150.00, 0.00, 0.00, 0, NULL, 10, '2026-03-13 15:27:42', '2026-03-14 16:41:30'),
(49, 19, 7, 1, 40, 'NATURAL', 'DNI', '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', '964881841', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 49, '2026-03-13 21:31:29', 'PEN', 'EMITIDA', 150.00, 150.00, 0.00, 0.00, 0, NULL, 10, '2026-03-13 21:31:29', '2026-03-14 16:41:30'),
(50, 19, 8, 1, 46, 'NATURAL', 'DNI', '70010001', 'Juan Perez', '900111111', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 50, '2026-03-14 11:53:21', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 11:53:21', '2026-03-14 11:53:21'),
(51, 19, 8, 1, 47, 'NATURAL', 'CE', '70010002', 'Maria Loayza', '900111112', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 51, '2026-03-14 11:54:45', 'PEN', 'EMITIDA', 10.00, 10.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 11:54:45', '2026-03-14 11:54:45'),
(52, 19, 8, 1, 48, 'NATURAL', 'DNI', '70010003', 'Luis Vargas', '900111113', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 52, '2026-03-14 11:57:54', 'PEN', 'EMITIDA', 1100.00, 1100.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 11:57:54', '2026-03-14 11:57:54'),
(53, 19, 8, 1, 49, 'NATURAL', 'DNI', '70010005', 'Ana Ruiz', '900111115', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 53, '2026-03-14 12:14:13', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 12:14:13', '2026-03-14 12:14:13'),
(54, 19, 8, 1, 50, 'NATURAL', 'CE', '70010006', 'Elena Paz', '900111116', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 54, '2026-03-14 12:34:19', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 12:34:19', '2026-03-14 12:34:19'),
(55, 19, 8, 1, 51, 'NATURAL', 'DNI', '52719384', 'Rocío Castro Luna Arce', '912345675', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 55, '2026-03-14 13:23:27', 'PEN', 'EMITIDA', 120.00, 120.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 13:23:27', '2026-03-14 13:23:27'),
(56, 19, 8, 1, 52, 'NATURAL', 'DNI', '784512309', 'Matías Herrera Campos', '912345676', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 56, '2026-03-14 13:25:06', 'PEN', 'EMITIDA', 600.00, 600.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 13:25:06', '2026-03-14 13:25:06'),
(57, 19, 8, 1, 53, 'NATURAL', 'DNI', '63917420', 'Fernanda Núñez Ramos', '912345677', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 57, '2026-03-14 13:28:30', 'PEN', 'EMITIDA', 1000.00, 1000.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 13:28:30', '2026-03-14 13:28:30'),
(58, 19, 8, 1, 54, 'NATURAL', 'DNI', '47820563', 'Kevin Torres Mejía', '912345678', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 58, '2026-03-14 13:31:28', 'PEN', 'EMITIDA', 1500.00, 1500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 13:31:28', '2026-03-14 13:31:28'),
(59, 19, 8, 1, 55, 'JURIDICA', 'RUC', '20574839216', 'Servicios Andinos UAT S.A.C.', '912345679', 'DNI', '71640258', 'Marcos', 'Cruz Valdivia', '912345679', 'TICKET', 'T018', 59, '2026-03-14 19:34:48', 'PEN', 'EMITIDA', 1100.00, 1100.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 19:34:48', '2026-03-14 19:34:48'),
(60, 19, 8, 1, 56, 'JURIDICA', 'RUC', '20650173928', 'Constructora Nuevo Horizonte UAT S.R.L.', '912345681', 'DNI', '68492017', 'Renzo', 'Flores Castañeda', '912345681', 'TICKET', 'T018', 60, '2026-03-14 19:40:16', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 19:40:16', '2026-03-14 19:40:16'),
(61, 19, 8, 1, 57, 'JURIDICA', 'RUC', '20948573612', 'Grupo Ferretero Tambo UAT S.R.L.', '739201458', 'CE', '912345684', 'Elena', 'Navarro Cordero', '739201458', 'TICKET', 'T018', 61, '2026-03-14 19:45:37', 'PEN', 'EMITIDA', 1000.00, 1000.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 19:45:37', '2026-03-14 19:45:37'),
(62, 19, 8, 1, 58, 'JURIDICA', 'RUC', '20173948526', 'Tecnored UAT Solutions S.A.C.', '912345686', 'BREVETE', 'A90371642', 'Nicolás', 'Ávila Sarmiento', '912345686', 'TICKET', 'T018', 62, '2026-03-14 19:52:18', 'PEN', 'EMITIDA', 600.00, 600.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 19:52:18', '2026-03-14 19:52:18'),
(63, 19, 8, 1, 58, 'JURIDICA', 'RUC', '20173948526', 'Tecnored UAT Solutions S.A.C.', '912345686', 'BREVETE', 'A90371642', 'Nicolás', 'Ávila Sarmiento', '912345686', 'TICKET', 'T018', 63, '2026-03-14 19:56:31', 'PEN', 'EMITIDA', 150.00, 150.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 19:56:31', '2026-03-14 19:56:31'),
(64, 19, 8, 1, 40, 'NATURAL', 'DNI', '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', '964881842', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 64, '2026-03-14 20:00:17', 'PEN', 'EMITIDA', 10.00, 10.00, 0.00, 0.00, 0, NULL, 10, '2026-03-14 20:00:17', '2026-03-14 20:00:51'),
(65, 19, 8, 1, 59, 'NATURAL', 'DNI', '70000001', 'JUAN PEREZ', '900111222', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 65, '2026-03-14 22:48:09', 'PEN', 'ANULADA', 1200.00, 0.00, 1200.00, 0.00, 0, ' | DEVOLUCIÓN: tyutyuytu', 10, '2026-03-14 22:48:09', '2026-03-15 07:37:08'),
(66, 19, 8, 1, 60, 'NATURAL', 'DNI', '70212121', 'WILIE MARQUEZ', '963232142', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 66, '2026-03-14 22:54:53', 'PEN', 'ANULADA', 350.00, 0.00, 220.00, 0.00, 0, ' | DEVOLUCION TOTAL: asdasd', 10, '2026-03-14 22:54:53', '2026-03-15 02:41:45'),
(67, 19, 8, 1, 40, 'NATURAL', 'DNI', '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', '964881854', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 67, '2026-03-15 01:24:18', 'PEN', 'ANULADA', 150.00, 0.00, 100.00, 0.00, 0, ' | ANULADA: tg', 10, '2026-03-15 01:24:18', '2026-03-15 07:37:08'),
(68, 19, 9, 1, 40, 'NATURAL', 'DNI', '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 68, '2026-03-15 10:02:46', 'PEN', 'ANULADA', 150.00, 0.00, 250.00, 0.00, 0, ' | DEVOLUCION TOTAL: gttrr', 10, '2026-03-15 10:02:46', '2026-03-15 10:08:23'),
(69, 19, 9, 1, 40, 'NATURAL', 'DNI', '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 69, '2026-03-15 10:48:41', 'PEN', 'EMITIDA', 1100.00, 500.00, 1100.00, 600.00, 0, NULL, 10, '2026-03-15 10:48:41', '2026-03-15 18:42:38'),
(70, 19, 9, 1, 61, 'NATURAL', 'DNI', '70363241', 'LUIS FERNANDO LOPEZ VARGAS', '964121214', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 70, '2026-03-15 18:38:59', 'PEN', 'EMITIDA', 1000.00, 1000.00, 0.00, 0.00, 0, NULL, 10, '2026-03-15 18:38:59', '2026-03-15 18:38:59'),
(71, 19, 9, 1, 62, 'NATURAL', 'DNI', '70362121', 'JUNIOR TEODORO RODRIGUEZ GUEVARA', '964881523', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 71, '2026-03-15 18:40:18', 'PEN', 'EMITIDA', 1000.00, 1000.00, 0.00, 0.00, 0, NULL, 10, '2026-03-15 18:40:18', '2026-03-15 18:40:18'),
(72, 19, 9, 1, 63, 'NATURAL', 'DNI', '70121232', 'ISABEL ROSALI TORREJON GONZALES', '964112123', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 72, '2026-03-15 18:42:13', 'PEN', 'EMITIDA', 1000.00, 1000.00, 0.00, 0.00, 0, NULL, 10, '2026-03-15 18:42:13', '2026-03-15 18:42:13'),
(73, 19, 9, 1, 20, 'NATURAL', 'DNI', '59201734', 'MILAGROS VARGAS VARGAS', '964881852', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 73, '2026-03-15 19:08:33', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-15 19:08:33', '2026-03-15 19:08:33'),
(74, 19, 9, 1, 63, 'NATURAL', 'DNI', '70121232', 'ISABEL ROSALI TORREJON GONZALES', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 74, '2026-03-15 22:17:28', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-15 22:17:28', '2026-03-15 22:17:28'),
(75, 19, 10, 1, 64, 'NATURAL', 'DNI', '70441214', 'LIZBETH ROXANA CAMPOS RAMOS', '964441452', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 75, '2026-03-16 09:43:57', 'PEN', 'EMITIDA', 1200.00, 1200.00, 0.00, 0.00, 0, NULL, 10, '2026-03-16 09:43:57', '2026-03-16 09:43:57'),
(76, 19, 10, 1, 63, 'NATURAL', 'DNI', '70121232', 'ISABEL ROSALI TORREJON GONZALES', '964881842', NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 76, '2026-03-16 10:13:58', 'PEN', 'ANULADA', 10.00, 0.00, 10.00, 0.00, 0, ' | DEVOLUCION TOTAL: ssssssssss', 10, '2026-03-16 10:13:58', '2026-03-16 10:14:08'),
(77, 20, 11, 2, 65, 'NATURAL', 'DNI', '12121212', 'PROMOTOR JOEL', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 1, '2026-03-16 12:44:10', 'PEN', 'EMITIDA', 40.00, 40.00, 0.00, 0.00, 0, NULL, 18, '2026-03-16 12:44:10', '2026-03-16 12:44:10'),
(78, 20, 11, 2, 66, 'NATURAL', 'DNI', '42309191', 'HEHIVER ANTENOR REYNA SILVESTRE', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 2, '2026-03-16 14:52:01', 'PEN', 'EMITIDA', 150.00, 150.00, 0.00, 0.00, 0, NULL, 19, '2026-03-16 14:52:01', '2026-03-16 14:52:01'),
(79, 20, 11, 2, 65, 'NATURAL', 'DNI', '12121212', 'PROMOTOR MAGUIN', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 3, '2026-03-16 15:23:27', 'PEN', 'EMITIDA', 50.00, 50.00, 0.00, 0.00, 0, NULL, 18, '2026-03-16 15:23:27', '2026-03-16 15:23:27'),
(80, 20, 11, 2, 65, 'NATURAL', 'DNI', '12121212', 'PROMOTOR TRUJILLO', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 4, '2026-03-16 15:24:55', 'PEN', 'EMITIDA', 50.00, 50.00, 0.00, 0.00, 0, NULL, 18, '2026-03-16 15:24:55', '2026-03-16 15:24:55'),
(81, 20, 11, 2, 65, 'NATURAL', 'DNI', '12121212', 'PROMOTOR SUSY', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 5, '2026-03-16 15:26:19', 'PEN', 'EMITIDA', 40.00, 40.00, 0.00, 0.00, 0, NULL, 18, '2026-03-16 15:26:19', '2026-03-16 15:26:19'),
(82, 20, 11, 2, 67, 'NATURAL', 'DNI', '18196115', 'TEOFILO VICTOR FLORES FLORES', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 6, '2026-03-16 15:29:14', 'PEN', 'EMITIDA', 250.00, 250.00, 0.00, 0.00, 1, NULL, 18, '2026-03-16 15:29:14', '2026-03-16 15:29:14'),
(83, 20, 11, 2, 68, 'NATURAL', 'DNI', '40498468', 'LUIS YHONY SERRANO DIAZ', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 7, '2026-03-16 21:23:42', 'PEN', 'EMITIDA', 50.00, 50.00, 0.00, 0.00, 0, NULL, 19, '2026-03-16 21:23:42', '2026-03-16 21:23:42'),
(84, 20, 11, 2, 69, 'NATURAL', 'DNI', '45400085', 'JHONNATTAN VENTURA CERNA', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 8, '2026-03-16 21:27:01', 'PEN', 'EMITIDA', 70.00, 70.00, 0.00, 0.00, 0, NULL, 19, '2026-03-16 21:27:01', '2026-03-16 21:27:01'),
(85, 20, 11, 2, 70, 'NATURAL', 'DNI', '4854581', 'PROMOTOR ZAVALETA', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 9, '2026-03-16 21:28:50', 'PEN', 'EMITIDA', 50.00, 50.00, 0.00, 0.00, 0, NULL, 19, '2026-03-16 21:28:50', '2026-03-16 21:28:50'),
(86, 20, 11, 2, 71, 'NATURAL', 'DNI', '1515318', 'PROMOTOR TRUJILLO', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 10, '2026-03-16 21:30:25', 'PEN', 'EMITIDA', 100.00, 100.00, 0.00, 0.00, 0, NULL, 19, '2026-03-16 21:30:25', '2026-03-16 21:30:25'),
(87, 20, 11, 2, 72, 'NATURAL', 'DNI', '5198145', 'PROMOTORA NECKY', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 11, '2026-03-16 21:31:18', 'PEN', 'EMITIDA', 50.00, 50.00, 0.00, 0.00, 0, NULL, 19, '2026-03-16 21:31:18', '2026-03-16 21:31:18'),
(88, 20, 11, 2, 73, 'NATURAL', 'DNI', '581684', 'PROMOTORA KELLY', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 12, '2026-03-16 21:31:52', 'PEN', 'EMITIDA', 50.00, 50.00, 0.00, 0.00, 0, NULL, 19, '2026-03-16 21:31:52', '2026-03-16 21:31:52'),
(89, 20, 11, 2, 74, 'NATURAL', 'DNI', '78104830', 'APONTE LEIVA', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 13, '2026-03-16 21:33:54', 'PEN', 'EMITIDA', 130.00, 130.00, 0.00, 0.00, 1, NULL, 19, '2026-03-16 21:33:54', '2026-03-16 21:33:54'),
(90, 20, 11, 2, 75, 'NATURAL', 'DNI', '17988823', 'AZAÑERO LLAROS', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 14, '2026-03-16 21:34:59', 'PEN', 'EMITIDA', 130.00, 130.00, 0.00, 0.00, 1, NULL, 19, '2026-03-16 21:34:59', '2026-03-16 21:34:59'),
(91, 20, 11, 2, 76, 'NATURAL', 'DNI', '27161104', 'CIRO GAMANIEL APONTE CASTILLO', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 15, '2026-03-16 21:35:53', 'PEN', 'EMITIDA', 130.00, 130.00, 0.00, 0.00, 1, NULL, 19, '2026-03-16 21:35:53', '2026-03-16 21:35:53'),
(92, 20, 11, 2, 77, 'NATURAL', 'DNI', '76686301', 'CAMPOS ZAVALA', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 16, '2026-03-16 21:37:07', 'PEN', 'EMITIDA', 130.00, 130.00, 0.00, 0.00, 1, NULL, 19, '2026-03-16 21:37:07', '2026-03-16 21:37:07'),
(93, 20, 11, 2, 78, 'NATURAL', 'DNI', '18174136', 'DORIS RICARDINA ZAVALA ESPEJO', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 17, '2026-03-16 21:37:39', 'PEN', 'EMITIDA', 130.00, 130.00, 0.00, 0.00, 1, NULL, 19, '2026-03-16 21:37:39', '2026-03-16 21:37:39'),
(94, 20, 12, 2, 79, 'NATURAL', 'DNI', '41905307', 'PORFIRIO LUCANO ACUÑA', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 18, '2026-03-17 11:56:54', 'PEN', 'EMITIDA', 50.00, 50.00, 0.00, 0.00, 0, NULL, 18, '2026-03-17 11:56:54', '2026-03-17 11:56:54'),
(95, 20, 12, 2, 80, 'NATURAL', 'DNI', '19536728', 'JOSE LUIS ROMAN CRUZ', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 19, '2026-03-17 11:59:46', 'PEN', 'EMITIDA', 70.00, 70.00, 0.00, 0.00, 0, NULL, 18, '2026-03-17 11:59:46', '2026-03-17 11:59:46'),
(96, 20, 12, 2, 81, 'NATURAL', 'DNI', '31652296', 'VALENTIN MORALES DEXTRE', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 20, '2026-03-17 12:01:18', 'PEN', 'EMITIDA', 50.00, 50.00, 0.00, 0.00, 0, NULL, 18, '2026-03-17 12:01:18', '2026-03-17 12:01:18'),
(97, 20, 12, 2, 82, 'NATURAL', 'DNI', '43751779', 'EDWIN JAKHON CASTILLO JICARO', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 21, '2026-03-17 12:03:58', 'PEN', 'EMITIDA', 60.00, 60.00, 0.00, 0.00, 0, NULL, 18, '2026-03-17 12:03:58', '2026-03-17 12:03:58'),
(98, 20, 12, 2, 83, 'NATURAL', 'DNI', '00103906', 'CARLOS MORI SALDAÑA', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 22, '2026-03-17 12:04:45', 'PEN', 'EMITIDA', 60.00, 60.00, 0.00, 0.00, 0, NULL, 18, '2026-03-17 12:04:45', '2026-03-17 12:04:45'),
(99, 20, 12, 2, 84, 'NATURAL', 'DNI', '40761046', 'PERSIL VIDAL PEREZ BALTODANO', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 23, '2026-03-17 12:06:34', 'PEN', 'EMITIDA', 50.00, 50.00, 0.00, 0.00, 0, NULL, 18, '2026-03-17 12:06:34', '2026-03-17 12:06:34'),
(100, 20, 12, 2, 85, 'NATURAL', 'DNI', '45243491', 'JOSE FREDDY DE LA CRUZ AZABACHE', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T001', 24, '2026-03-17 12:08:01', 'PEN', 'EMITIDA', 60.00, 60.00, 0.00, 0.00, 0, NULL, 18, '2026-03-17 12:08:01', '2026-03-17 12:08:01'),
(101, 19, 13, 1, 63, 'NATURAL', 'DNI', '70121232', 'ISABEL ROSALI TORREJON GONZALES', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 77, '2026-03-17 14:10:10', 'PEN', 'EMITIDA', 500.00, 500.00, 0.00, 0.00, 0, NULL, 10, '2026-03-17 14:10:10', '2026-03-17 14:10:10'),
(102, 19, 13, 1, 63, 'NATURAL', 'DNI', '70121232', 'ISABEL ROSALI TORREJON GONZALES', NULL, NULL, NULL, NULL, NULL, NULL, 'TICKET', 'T018', 78, '2026-03-17 14:34:16', 'PEN', 'EMITIDA', 150.00, 150.00, 0.00, 0.00, 0, NULL, 10, '2026-03-17 14:34:16', '2026-03-17 14:34:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_ventas_anulaciones`
--

CREATE TABLE `pos_ventas_anulaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `anulado_por` int(10) UNSIGNED NOT NULL,
  `anulado_en` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_ventas_anulaciones`
--

INSERT INTO `pos_ventas_anulaciones` (`id`, `venta_id`, `motivo`, `anulado_por`, `anulado_en`) VALUES
(1, 43, 'Su huella no pasa.', 10, '2026-03-13 08:03:27'),
(2, 45, 'La huella del cliente no pasaba', 10, '2026-03-13 10:59:26'),
(3, 65, 'tyutyuytu', 10, '2026-03-14 22:50:06'),
(4, 67, 'tg', 10, '2026-03-15 01:25:17'),
(6, 66, 'asdasd', 10, '2026-03-15 02:41:45'),
(7, 68, 'gttrr', 10, '2026-03-15 10:08:23'),
(8, 76, 'ssssssssss', 10, '2026-03-16 10:14:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_venta_conductores`
--

CREATE TABLE `pos_venta_conductores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `conductor_tipo` enum('CLIENTE','REGISTRADO','PENDIENTE') NOT NULL,
  `conductor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `conductor_doc_tipo` enum('DNI','CE','PAS','BREVETE') DEFAULT NULL,
  `conductor_doc_numero` varchar(20) DEFAULT NULL,
  `conductor_nombres` varchar(120) DEFAULT NULL,
  `conductor_apellidos` varchar(120) DEFAULT NULL,
  `conductor_telefono` varchar(30) DEFAULT NULL,
  `conductor_es_mismo_cliente` tinyint(1) NOT NULL DEFAULT 0,
  `conductor_origen` varchar(40) DEFAULT NULL,
  `estado` enum('ASIGNADO','PENDIENTE','ANULADO') NOT NULL DEFAULT 'ASIGNADO',
  `es_principal` tinyint(1) NOT NULL DEFAULT 0,
  `canal` varchar(30) DEFAULT NULL,
  `email_contacto` varchar(150) DEFAULT NULL,
  `nacimiento` date DEFAULT NULL,
  `conductor_categoria_auto_id` smallint(5) UNSIGNED DEFAULT NULL,
  `conductor_categoria_moto_id` smallint(5) UNSIGNED DEFAULT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_venta_conductores`
--

INSERT INTO `pos_venta_conductores` (`id`, `venta_id`, `conductor_tipo`, `conductor_id`, `conductor_doc_tipo`, `conductor_doc_numero`, `conductor_nombres`, `conductor_apellidos`, `conductor_telefono`, `conductor_es_mismo_cliente`, `conductor_origen`, `estado`, `es_principal`, `canal`, `email_contacto`, `nacimiento`, `conductor_categoria_auto_id`, `conductor_categoria_moto_id`, `nota`, `creado`, `actualizado`) VALUES
(1, 1, 'REGISTRADO', 3, 'DNI', '70366365', 'JULIAN', 'ALVAREZ MANA', '965541421', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 00:34:34', '2026-03-14 16:41:30'),
(2, 2, 'REGISTRADO', 4, 'DNI', '72554145', 'MELISA', 'RODRIGUEZ', '966653254', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 10:05:28', '2026-03-14 16:41:30'),
(3, 3, 'REGISTRADO', 5, 'DNI', '48392015', 'Carlos', 'Mendoza Ríos', '987654321', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 11:25:54', '2026-03-14 16:41:30'),
(4, 4, 'CLIENTE', NULL, 'DNI', '52412514', 'KIMBERLY FLORES LOPEZ', 'FLORES LOPEZ', '965252145', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 13:29:25', '2026-03-14 16:41:30'),
(5, 5, 'REGISTRADO', 6, 'DNI', '44556677', 'Juan', 'Perez', '966363623', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 13:32:25', '2026-03-14 16:41:30'),
(6, 6, 'REGISTRADO', 7, 'DNI', '70363636', 'Americo', 'Barrios Canto', '966366632', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 13:35:38', '2026-03-14 16:41:30'),
(7, 7, 'CLIENTE', NULL, 'CE', '48392716', 'Diego Ramírez Soto', 'Ramírez Soto', '912345678', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 13:38:28', '2026-03-14 16:41:30'),
(8, 8, 'REGISTRADO', 8, 'DNI', '41414525', 'ANA LUCIA', 'JARA PEREZ', '966363636', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 14:16:27', '2026-03-14 16:41:30'),
(9, 9, 'CLIENTE', NULL, 'DNI', '70111141', 'Maricarmen Villalobos Alfaro', 'Villalobos Alfaro', '9633632541', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 15:15:37', '2026-03-14 16:41:30'),
(10, 10, 'CLIENTE', NULL, 'BREVETE', 'B63635478', 'MARIA ELENA COBARRUBIAS ALVA', 'COBARRUBIAS ALVA', '966663574', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 15:23:39', '2026-03-14 16:41:30'),
(11, 11, 'CLIENTE', NULL, 'DNI', '70414215', 'CAMILA PAREDES GONZALES', 'PAREDES GONZALES', '966565415', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 15:53:20', '2026-03-14 16:41:30'),
(12, 12, 'CLIENTE', NULL, 'DNI', '41141251', 'CRISTIANM CASTRO CARRILLO', 'CASTRO CARRILLO', '965211412', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 16:03:27', '2026-03-14 16:41:30'),
(13, 13, 'CLIENTE', NULL, 'DNI', '71114121', 'julian juarez juvenal', 'juarez juvenal', '963323214', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 16:04:52', '2026-03-14 16:41:30'),
(14, 14, 'CLIENTE', NULL, 'DNI', '70414125', 'ALBERTO BARROS BAILON', 'BARROS BAILON', '966363251', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 16:08:54', '2026-03-14 16:41:30'),
(15, 15, 'CLIENTE', NULL, 'DNI', '70252541', 'melisa perez juarez', 'perez juarez', '963323214', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 16:16:37', '2026-03-14 16:41:30'),
(16, 16, 'CLIENTE', NULL, 'DNI', '70333236', 'ROBERTO BLADES JUAREZ', 'BLADES JUAREZ', '965554474', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 16:18:00', '2026-03-14 16:41:30'),
(17, 17, 'REGISTRADO', 9, 'DNI', '50504012', 'ALEXANDRA', 'ALAMA VASQUEZ', '966363254', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 21:29:45', '2026-03-14 16:41:30'),
(18, 18, 'CLIENTE', NULL, 'BREVETE', 'Q34567891', 'Luis Vargas Soto', 'Vargas Soto', '965874123', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-05 09:42:20', '2026-03-14 16:41:30'),
(19, 19, 'CLIENTE', NULL, 'DNI', '59201734', 'Andrea Torres Vega', 'Torres Vega', '944785236', 1, 'cliente_natural', 'ASIGNADO', 1, 'WHATSAPP', 'juan.ramirez@mail.com', '1995-05-12', 1, NULL, 'Interesado en curso básico', '2026-03-05 09:56:05', '2026-03-14 16:41:30'),
(20, 20, 'CLIENTE', NULL, 'DNI', '52625214', 'VICENTE CARDENAS CARDENAS', 'CARDENAS CARDENAS', '963332145', 1, 'cliente_natural', 'ASIGNADO', 1, 'LLAMADA', 'a@gmail.com', '1999-09-06', 5, 9, 'El señor viajará pronto.', '2026-03-05 13:40:04', '2026-03-14 16:41:30'),
(21, 21, 'CLIENTE', NULL, 'DNI', '50201214', 'Miguel Mariños Marcial', 'Mariños Marcial', '963323214', 1, 'cliente_natural', 'ASIGNADO', 1, 'LLAMADA', 'qqq@gmail.com', '1980-09-09', 3, 9, 'Señor interesado en revalidar.', '2026-03-05 13:42:27', '2026-03-14 16:41:30'),
(22, 22, 'CLIENTE', NULL, 'DNI', '70555562', 'JUAN LUIS GUERRA PAZ', 'GUERRA PAZ', '963333632', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-05 15:23:03', '2026-03-14 16:41:30'),
(23, 23, 'REGISTRADO', 10, 'CE', '90303060233', 'JULIAN', 'PEREZ PEREZ', '966636254', 0, 'conductor_otra_persona', 'ASIGNADO', 1, 'TIKTOK', 'aaa@gmail.com', '1975-08-08', 4, 9, 'Vio un tiktok y le dio risa, entonces vino al local.', '2026-03-05 16:09:22', '2026-03-14 16:41:30'),
(24, 24, 'REGISTRADO', 11, 'DNI', '70000000', 'PEPITO', 'RUIZ JUAREZ', '965555555', 0, 'conductor_otra_persona', 'ASIGNADO', 1, 'TIKTOK', 'a@gmail.com', '1998-05-05', 4, 8, 'VIO UN TIKTOK Y LE GUSTÓ', '2026-03-05 16:19:50', '2026-03-14 16:41:30'),
(25, 25, 'CLIENTE', NULL, 'DNI', '70554411', 'ELOISA MARTINEZ FERNANDEZ', 'MARTINEZ FERNANDEZ', '963323214', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-06 17:46:04', '2026-03-14 16:41:30'),
(26, 26, 'CLIENTE', NULL, 'DNI', '70333362', 'CRISTIAN SOTO SOL', 'SOTO SOL', '963332321', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:16:36', '2026-03-14 16:41:30'),
(27, 27, 'CLIENTE', NULL, 'DNI', '70555542', 'JUAN LUIS VARGAS VARGAS', 'VARGAS VARGAS', '963332142', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:27:00', '2026-03-14 16:41:30'),
(28, 28, 'CLIENTE', NULL, 'DNI', '78888765', 'JUAN VARGAS VARGAS', 'VARGAS VARGAS', '988887654', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-10 11:34:08', '2026-03-14 16:41:30'),
(29, 29, 'CLIENTE', NULL, 'BREVETE', 'B76654543', 'JUANA GONZALES PEREZ', 'GONZALES PEREZ', 'null', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-10 11:35:57', '2026-03-14 16:41:30'),
(30, 30, 'CLIENTE', NULL, 'DNI', '78888876', 'LUIS VILLANUEVA', 'VILLANUEVA', '964555532', 1, 'cliente_natural', 'ASIGNADO', 1, 'FACEBOOK', 'null', '1999-03-04', 3, 10, 'El señor viene de una municipalidad posible convenio', '2026-03-10 11:43:19', '2026-03-14 16:41:30'),
(31, 36, 'CLIENTE', NULL, 'DNI', '77889966', 'Luigi Villanueva', 'Villanueva', '964881842', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-12 22:58:24', '2026-03-14 16:41:30'),
(32, 37, 'CLIENTE', NULL, 'DNI', '70441336', 'Anastacia León León', 'León León', '963332321', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-12 23:01:16', '2026-03-14 16:41:30'),
(33, 38, 'CLIENTE', NULL, 'DNI', '70441212', 'LUIS GUERRA GUERRA', 'GUERRA GUERRA', 'null', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-12 23:59:00', '2026-03-14 16:41:30'),
(34, 39, 'CLIENTE', NULL, 'DNI', '70555523', 'LUIS GUERRA PAZ', 'GUERRA PAZ', 'null', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 00:00:06', '2026-03-14 16:41:30'),
(35, 40, 'CLIENTE', NULL, 'DNI', '70525142', 'DIANA PAZ', 'PAZ', 'null', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 00:18:03', '2026-03-14 16:41:30'),
(36, 41, 'CLIENTE', NULL, 'DNI', '70123625', 'SANDRA ERIKA MONTOYA CAMARGO', 'MONTOYA CAMARGO', '966635263', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 01:41:01', '2026-03-14 16:41:30'),
(37, 42, 'CLIENTE', NULL, 'DNI', '70455253', 'CYNTHIA MARIA CARMEN ROUILLON', 'CARMEN ROUILLON', '963323214', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 01:43:02', '2026-03-14 16:41:30'),
(38, 43, 'CLIENTE', NULL, 'DNI', '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', 'VILLANUEVA PEREZ', '964881841', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 08:03:04', '2026-03-14 16:41:30'),
(39, 44, 'CLIENTE', NULL, 'DNI', '18198265', 'ROXANA MARILU TRELLES URQUIZA', 'TRELLES URQUIZA', '963632145', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 10:52:41', '2026-03-14 16:41:30'),
(40, 45, 'CLIENTE', NULL, 'DNI', '71252952', 'JHEFERSON ALESSANDRO RODRIGUEZ PAREDES', 'RODRIGUEZ PAREDES', '963232142', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 10:56:42', '2026-03-14 16:41:30'),
(41, 46, 'CLIENTE', NULL, 'DNI', '47305338', 'KARLA HELEN BELTRAN ARANDA', 'BELTRAN ARANDA', '964885412', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 11:04:41', '2026-03-14 16:41:30'),
(42, 47, 'CLIENTE', NULL, 'CE', '7036365214', 'LUIS', 'PAREDES PAREDES', '965332321', 0, 'contratante_juridica', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 11:10:18', '2026-03-14 16:41:30'),
(43, 48, 'CLIENTE', NULL, 'DNI', '70352321', 'TATHIANA ALEXE MARIA CAMA CAMASCA', 'CAMA CAMASCA', '963323214', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 15:27:42', '2026-03-14 16:41:30'),
(44, 49, 'CLIENTE', NULL, 'DNI', '70379752', 'LUIGI ISRAEL VILLANUEVA PEREZ', 'VILLANUEVA PEREZ', '964881841', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-13 21:31:29', '2026-03-14 16:41:30'),
(45, 50, 'CLIENTE', NULL, 'DNI', '70010001', 'Juan', 'Perez', '900111111', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 11:53:21', '2026-03-14 11:53:21'),
(46, 51, 'CLIENTE', NULL, 'CE', '70010002', 'Maria', 'Loayza', '900111112', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 11:54:45', '2026-03-14 11:54:45'),
(47, 52, 'CLIENTE', NULL, 'DNI', '70010003', 'Luis', 'Vargas', '900111113', 1, 'cliente_natural', 'ASIGNADO', 1, 'WHATSAPP', 'luis.test@demo.com', '1993-04-10', NULL, NULL, 'cliente satisfecho. Volverá.', '2026-03-14 11:57:54', '2026-03-14 11:57:54'),
(48, 53, 'REGISTRADO', 12, 'DNI', '70020005', 'Pedro', 'Soto', '911222331', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 12:14:13', '2026-03-14 12:14:13'),
(49, 54, 'CLIENTE', NULL, 'CE', '70010006', 'Elena', 'Paz', '900111116', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 12:34:19', '2026-03-14 12:34:19'),
(50, 55, 'REGISTRADO', 13, 'DNI', '46820517', 'Bruno', 'Soto Aguilar', '923456781', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 13:23:27', '2026-03-14 13:23:27'),
(51, 56, 'REGISTRADO', 14, 'BREVETE', 'B90817263', 'Andrea', 'Peña Cárdenas', '923456782', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 13:25:06', '2026-03-14 13:25:06'),
(52, 57, 'REGISTRADO', 15, 'DNI', '57284019', 'Santiago', 'Vega Lozano', '923456783', 0, 'conductor_otra_persona', 'ASIGNADO', 1, 'SMS', 'santiago.vega+uatf401@example.com', '1993-11-27', NULL, NULL, 'Enviar aviso por SMS', '2026-03-14 13:28:30', '2026-03-14 13:28:30'),
(53, 58, 'REGISTRADO', 16, 'CE', '659301842', 'Lucía', 'Suárez Pinto', '923456784', 0, 'conductor_otra_persona', 'ASIGNADO', 1, 'SMS', 'lucia.suarez+uatf402@example.com', '2001-05-09', NULL, NULL, 'Correo de confirmación requerido', '2026-03-14 13:31:28', '2026-03-14 13:31:28'),
(54, 59, 'CLIENTE', NULL, 'DNI', '71640258', 'Marcos', 'Cruz Valdivia', '912345679', 0, 'contratante_juridica', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 19:34:48', '2026-03-14 19:34:48'),
(55, 60, 'CLIENTE', NULL, 'DNI', '68492017', 'Renzo', 'Flores Castañeda', '912345681', 0, 'contratante_juridica', 'ASIGNADO', 1, 'WHATSAPP', 'renzo.flores+uatf601@example.com', '1985-01-22', 2, NULL, 'Enviar constancia firmada', '2026-03-14 19:40:16', '2026-03-14 19:40:16'),
(56, 61, 'REGISTRADO', 17, 'BREVETE', 'B61529407', 'Iván', 'Quinteros Ledesma', '923456786', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 19:45:37', '2026-03-14 19:45:37'),
(57, 62, 'REGISTRADO', 18, 'DNI', '54839271', 'Carla', 'Bautista Romero', '923456788', 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 19:52:18', '2026-03-14 19:52:18'),
(58, 63, 'REGISTRADO', 18, 'DNI', '54839271', 'Carla', 'Bautista Romero', '923456788', 0, 'conductor_otra_persona', 'ASIGNADO', 1, 'SMS', 'carla.bautista+uatf802@example.com', '1998-05-12', 4, 9, 'Notificar por SMS al conductor', '2026-03-14 19:56:31', '2026-03-14 19:56:31'),
(59, 64, 'CLIENTE', NULL, 'DNI', '70379752', 'LUIGI ISRAEL', 'VILLANUEVA PEREZ', '964881842', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 20:00:17', '2026-03-14 20:00:17'),
(60, 65, 'CLIENTE', NULL, 'DNI', '70000001', 'JUAN', 'PEREZ', '900111222', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 22:48:09', '2026-03-14 22:48:09'),
(61, 66, 'CLIENTE', NULL, 'DNI', '70212121', 'WILIE', 'MARQUEZ', '963232142', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 22:54:53', '2026-03-14 22:54:53'),
(62, 67, 'CLIENTE', NULL, 'DNI', '70379752', 'LUIGI ISRAEL', 'VILLANUEVA PEREZ', '964881854', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-15 01:24:18', '2026-03-15 01:24:18'),
(63, 68, 'CLIENTE', NULL, 'DNI', '70379752', 'LUIGI ISRAEL', 'VILLANUEVA PEREZ', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-15 10:02:46', '2026-03-15 10:02:46'),
(64, 69, 'CLIENTE', NULL, 'DNI', '70379752', 'LUIGI ISRAEL', 'VILLANUEVA PEREZ', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-15 10:48:41', '2026-03-15 10:48:41'),
(65, 70, 'CLIENTE', NULL, 'DNI', '70363241', 'LUIS FERNANDO', 'LOPEZ VARGAS', '964121214', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-15 18:38:59', '2026-03-15 18:38:59'),
(66, 71, 'CLIENTE', NULL, 'DNI', '70362121', 'JUNIOR TEODORO', 'RODRIGUEZ GUEVARA', '964881523', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-15 18:40:18', '2026-03-15 18:40:18'),
(67, 72, 'CLIENTE', NULL, 'DNI', '70121232', 'ISABEL ROSALI', 'TORREJON GONZALES', '964112123', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-15 18:42:13', '2026-03-15 18:42:13'),
(68, 73, 'CLIENTE', NULL, 'DNI', '59201734', 'MILAGROS', 'VARGAS VARGAS', '964881852', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-15 19:08:33', '2026-03-15 19:08:33'),
(69, 74, 'CLIENTE', NULL, 'DNI', '70121232', 'ISABEL ROSALI', 'TORREJON GONZALES', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-15 22:17:28', '2026-03-15 22:17:28'),
(70, 75, 'CLIENTE', NULL, 'DNI', '70441214', 'LIZBETH ROXANA', 'CAMPOS RAMOS', '964441452', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 09:43:57', '2026-03-16 09:43:57'),
(71, 76, 'CLIENTE', NULL, 'DNI', '70121232', 'ISABEL ROSALI', 'TORREJON GONZALES', '964881842', 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 10:13:58', '2026-03-16 10:13:58'),
(72, 77, 'REGISTRADO', 19, 'DNI', '42240258', 'FRANKLIN TARDELLI', 'MARTINEZ SOLANO', NULL, 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 12:44:10', '2026-03-16 12:44:10'),
(73, 78, 'CLIENTE', NULL, 'DNI', '42309191', 'HEHIVER ANTENOR', 'REYNA SILVESTRE', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 14:52:01', '2026-03-16 14:52:01'),
(74, 79, 'REGISTRADO', 20, 'DNI', '41906002', 'JOSE LUIS', 'MANTILLA QUILICHE', NULL, 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 15:23:27', '2026-03-16 15:23:27'),
(75, 80, 'REGISTRADO', 21, 'DNI', '47481147', 'FRANCISCO', 'GARCIA BRICEÑO', NULL, 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 15:24:55', '2026-03-16 15:24:55'),
(76, 81, 'REGISTRADO', 22, 'DNI', '46782087', 'JILDER', 'CORONEL DELGADO', NULL, 0, 'conductor_otra_persona', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 15:26:19', '2026-03-16 15:26:19'),
(77, 82, 'CLIENTE', NULL, 'DNI', '18196115', 'TEOFILO VICTOR', 'FLORES FLORES', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 15:29:14', '2026-03-16 15:29:14'),
(78, 83, 'CLIENTE', NULL, 'DNI', '40498468', 'LUIS YHONY', 'SERRANO DIAZ', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:23:42', '2026-03-16 21:23:42'),
(79, 84, 'CLIENTE', NULL, 'DNI', '45400085', 'JHONNATTAN', 'VENTURA CERNA', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:27:01', '2026-03-16 21:27:01'),
(80, 85, 'CLIENTE', NULL, 'DNI', '4854581', 'PROMOTOR', 'ZAVALETA', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:28:50', '2026-03-16 21:28:50'),
(81, 86, 'CLIENTE', NULL, 'DNI', '1515318', 'PROMOTOR', 'TRUJILLO', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:30:25', '2026-03-16 21:30:25'),
(82, 87, 'CLIENTE', NULL, 'DNI', '5198145', 'PROMOTORA', 'NECKY', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:31:18', '2026-03-16 21:31:18'),
(83, 88, 'CLIENTE', NULL, 'DNI', '581684', 'PROMOTORA', 'KELLY', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:31:52', '2026-03-16 21:31:52'),
(84, 89, 'CLIENTE', NULL, 'DNI', '78104830', 'APONTE', 'LEIVA', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:33:54', '2026-03-16 21:33:54'),
(85, 90, 'CLIENTE', NULL, 'DNI', '17988823', 'AZAÑERO', 'LLAROS', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:34:59', '2026-03-16 21:34:59'),
(86, 91, 'CLIENTE', NULL, 'DNI', '27161104', 'CIRO GAMANIEL', 'APONTE CASTILLO', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:35:53', '2026-03-16 21:35:53'),
(87, 92, 'CLIENTE', NULL, 'DNI', '76686301', 'CAMPOS', 'ZAVALA', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:37:07', '2026-03-16 21:37:07'),
(88, 93, 'CLIENTE', NULL, 'DNI', '18174136', 'DORIS RICARDINA', 'ZAVALA ESPEJO', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-16 21:37:39', '2026-03-16 21:37:39'),
(89, 94, 'CLIENTE', NULL, 'DNI', '41905307', 'PORFIRIO', 'LUCANO ACUÑA', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 11:56:54', '2026-03-17 11:56:54'),
(90, 95, 'CLIENTE', NULL, 'DNI', '19536728', 'JOSE LUIS', 'ROMAN CRUZ', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 11:59:46', '2026-03-17 11:59:46'),
(91, 96, 'CLIENTE', NULL, 'DNI', '31652296', 'VALENTIN', 'MORALES DEXTRE', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 12:01:18', '2026-03-17 12:01:18'),
(92, 97, 'CLIENTE', NULL, 'DNI', '43751779', 'EDWIN JAKHON', 'CASTILLO JICARO', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 12:03:58', '2026-03-17 12:03:58'),
(93, 98, 'CLIENTE', NULL, 'DNI', '00103906', 'CARLOS', 'MORI SALDAÑA', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 12:04:45', '2026-03-17 12:04:45'),
(94, 99, 'CLIENTE', NULL, 'DNI', '40761046', 'PERSIL VIDAL', 'PEREZ BALTODANO', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 12:06:34', '2026-03-17 12:06:34'),
(95, 100, 'CLIENTE', NULL, 'DNI', '45243491', 'JOSE FREDDY', 'DE LA CRUZ AZABACHE', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 12:08:01', '2026-03-17 12:08:01'),
(96, 101, 'CLIENTE', NULL, 'DNI', '70121232', 'ISABEL ROSALI', 'TORREJON GONZALES', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 14:10:10', '2026-03-17 14:10:10'),
(97, 102, 'CLIENTE', NULL, 'DNI', '70121232', 'ISABEL ROSALI', 'TORREJON GONZALES', NULL, 1, 'cliente_natural', 'ASIGNADO', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-17 14:34:16', '2026-03-17 14:34:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_venta_detalles`
--

CREATE TABLE `pos_venta_detalles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `servicio_id` int(10) UNSIGNED NOT NULL,
  `servicio_nombre` varchar(200) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `descuento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_linea` decimal(14,2) NOT NULL,
  `precio_origen` enum('LISTA','TEMPORAL') NOT NULL DEFAULT 'LISTA',
  `precio_lista_id` int(10) UNSIGNED DEFAULT NULL,
  `precio_lista_base` decimal(12,2) DEFAULT NULL,
  `precio_temporal_actor_id` int(10) UNSIGNED DEFAULT NULL,
  `precio_temporal_motivo` varchar(255) DEFAULT NULL,
  `precio_temporal_en` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pos_venta_detalles`
--

INSERT INTO `pos_venta_detalles` (`id`, `venta_id`, `servicio_id`, `servicio_nombre`, `descripcion`, `cantidad`, `precio_unitario`, `descuento`, `total_linea`, `precio_origen`, `precio_lista_id`, `precio_lista_base`, `precio_temporal_actor_id`, `precio_temporal_motivo`, `precio_temporal_en`) VALUES
(1, 1, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(2, 2, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(3, 3, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(4, 4, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(5, 5, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(6, 6, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(7, 7, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(8, 8, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(9, 9, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(10, 10, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(11, 11, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(12, 12, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(13, 13, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(14, 14, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(15, 15, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(16, 16, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(17, 17, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(18, 18, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(19, 19, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(20, 20, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(21, 21, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(22, 21, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(23, 22, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(24, 23, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(25, 24, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(26, 25, 2, 'RECA AII', NULL, 4.000, 1200.00, 0.00, 4800.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(27, 26, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(28, 27, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(29, 28, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(30, 29, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(31, 30, 2, 'RECA AII', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', NULL, NULL, NULL, NULL, NULL),
(32, 36, 2, 'RECA AIIA', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', 31, 1200.00, NULL, NULL, NULL),
(33, 37, 3, 'RECA AIIB', NULL, 1.000, 1050.00, 0.00, 1050.00, 'TEMPORAL', NULL, NULL, 10, 'Coordinado con ventas', '2026-03-12 23:01:16'),
(34, 38, 2, 'RECA AIIA', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', 31, 1200.00, NULL, NULL, NULL),
(35, 39, 2, 'RECA AIIA', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', 31, 1200.00, NULL, NULL, NULL),
(36, 40, 12, 'Curso de actualización - Pasajeros', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 656, 150.00, NULL, NULL, NULL),
(37, 41, 4, 'OBTENCIÓN A1', NULL, 1.000, 600.00, 0.00, 600.00, 'LISTA', 671, 600.00, NULL, NULL, NULL),
(38, 42, 11, 'Curso de actualización - Carga', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 651, 150.00, NULL, NULL, NULL),
(39, 43, 12, 'Curso de actualización - Pasajeros', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 656, 150.00, NULL, NULL, NULL),
(40, 44, 13, 'BALOTARIO', NULL, 1.000, 10.00, 0.00, 10.00, 'LISTA', 831, 10.00, NULL, NULL, NULL),
(41, 45, 7, 'RECA AIIIB', NULL, 1.000, 900.00, 0.00, 900.00, 'TEMPORAL', NULL, NULL, 10, 'coordinado con gerencia', '2026-03-13 10:56:42'),
(42, 46, 11, 'Curso de actualización - Carga', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 651, 150.00, NULL, NULL, NULL),
(43, 47, 3, 'RECA AIIB', NULL, 1.000, 1100.00, 0.00, 1100.00, 'LISTA', 131, 1100.00, NULL, NULL, NULL),
(44, 48, 12, 'Curso de actualización - Pasajeros', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 656, 150.00, NULL, NULL, NULL),
(45, 49, 11, 'Curso de actualización - Carga', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 651, 150.00, NULL, NULL, NULL),
(46, 50, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', 1, 500.00, NULL, NULL, NULL),
(47, 51, 13, 'BALOTARIO', NULL, 1.000, 10.00, 0.00, 10.00, 'LISTA', 831, 10.00, NULL, NULL, NULL),
(48, 52, 3, 'RECA AIIB', NULL, 1.000, 1100.00, 0.00, 1100.00, 'LISTA', 131, 1100.00, NULL, NULL, NULL),
(49, 53, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', 1, 500.00, NULL, NULL, NULL),
(50, 54, 2, 'RECA AIIA', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', 31, 1200.00, NULL, NULL, NULL),
(51, 55, 10, 'Licencia especial AIV', NULL, 1.000, 120.00, 0.00, 120.00, 'LISTA', 661, 120.00, NULL, NULL, NULL),
(52, 56, 4, 'OBTENCIÓN A1', NULL, 1.000, 600.00, 0.00, 600.00, 'LISTA', 671, 600.00, NULL, NULL, NULL),
(53, 57, 8, 'RECA AIIIC', NULL, 1.000, 1000.00, 0.00, 1000.00, 'LISTA', 791, 1000.00, NULL, NULL, NULL),
(54, 58, 7, 'RECA AIIIB', NULL, 1.000, 1500.00, 0.00, 1500.00, 'LISTA', 766, 1500.00, NULL, NULL, NULL),
(55, 59, 3, 'RECA AIIB', NULL, 1.000, 1100.00, 0.00, 1100.00, 'LISTA', 131, 1100.00, NULL, NULL, NULL),
(56, 60, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', 1, 500.00, NULL, NULL, NULL),
(57, 61, 6, 'RECA AIIIA', NULL, 1.000, 1000.00, 0.00, 1000.00, 'LISTA', 756, 1000.00, NULL, NULL, NULL),
(58, 62, 4, 'OBTENCIÓN A1', NULL, 1.000, 600.00, 0.00, 600.00, 'LISTA', 671, 600.00, NULL, NULL, NULL),
(59, 63, 11, 'Curso de actualización - Carga', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 651, 150.00, NULL, NULL, NULL),
(60, 64, 13, 'BALOTARIO', NULL, 1.000, 10.00, 0.00, 10.00, 'LISTA', 831, 10.00, NULL, NULL, NULL),
(61, 65, 2, 'RECA AIIA', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', 31, 1200.00, NULL, NULL, NULL),
(62, 66, 9, 'Taller Cambiemos de Actitud', NULL, 1.000, 350.00, 0.00, 350.00, 'LISTA', 811, 350.00, NULL, NULL, NULL),
(63, 67, 11, 'Curso de actualización - Carga', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 651, 150.00, NULL, NULL, NULL),
(64, 68, 12, 'Curso de actualización - Pasajeros', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 656, 150.00, NULL, NULL, NULL),
(65, 69, 3, 'RECA AIIB', NULL, 1.000, 1100.00, 0.00, 1100.00, 'LISTA', 131, 1100.00, NULL, NULL, NULL),
(66, 70, 6, 'RECA AIIIA', NULL, 1.000, 1000.00, 0.00, 1000.00, 'LISTA', 756, 1000.00, NULL, NULL, NULL),
(67, 71, 6, 'RECA AIIIA', NULL, 1.000, 1000.00, 0.00, 1000.00, 'LISTA', 756, 1000.00, NULL, NULL, NULL),
(68, 72, 8, 'RECA AIIIC', NULL, 1.000, 1000.00, 0.00, 1000.00, 'LISTA', 791, 1000.00, NULL, NULL, NULL),
(69, 73, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', 1, 500.00, NULL, NULL, NULL),
(70, 74, 2, 'RECA AIIA', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', 31, 1200.00, NULL, NULL, NULL),
(71, 75, 2, 'RECA AIIA', NULL, 1.000, 1200.00, 0.00, 1200.00, 'LISTA', 31, 1200.00, NULL, NULL, NULL),
(72, 76, 13, 'BALOTARIO', NULL, 1.000, 10.00, 0.00, 10.00, 'LISTA', 831, 10.00, NULL, NULL, NULL),
(73, 77, 11, 'Curso de actualización - Carga', NULL, 1.000, 40.00, 0.00, 40.00, 'LISTA', 850, 40.00, NULL, NULL, NULL),
(74, 78, 1, 'MOTO BIIC', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 862, 150.00, NULL, NULL, NULL),
(75, 79, 11, 'Curso de actualización - Carga', NULL, 1.000, 50.00, 0.00, 50.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(76, 80, 11, 'Curso de actualización - Carga', NULL, 1.000, 50.00, 0.00, 50.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(77, 81, 11, 'Curso de actualización - Carga', NULL, 1.000, 40.00, 0.00, 40.00, 'LISTA', 850, 40.00, NULL, NULL, NULL),
(78, 82, 3, 'RECA AIIB', NULL, 1.000, 250.00, 0.00, 250.00, 'TEMPORAL', NULL, NULL, 18, 'CANC. SALDO RECA MATRICULADA VIERNES 13/03', '2026-03-16 15:29:14'),
(79, 83, 11, 'Curso de actualización - Carga', NULL, 1.000, 50.00, 0.00, 50.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(80, 84, 11, 'Curso de actualización - Carga', NULL, 1.000, 70.00, 0.00, 70.00, 'LISTA', 847, 70.00, NULL, NULL, NULL),
(81, 85, 11, 'Curso de actualización - Carga', NULL, 1.000, 50.00, 0.00, 50.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(82, 86, 11, 'Curso de actualización - Carga', NULL, 2.000, 50.00, 0.00, 100.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(83, 87, 11, 'Curso de actualización - Carga', NULL, 1.000, 50.00, 0.00, 50.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(84, 88, 11, 'Curso de actualización - Carga', NULL, 1.000, 50.00, 0.00, 50.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(85, 89, 1, 'MOTO BIIC', NULL, 1.000, 130.00, 0.00, 130.00, 'TEMPORAL', NULL, NULL, 19, 'Coordinado con Gerencia', '2026-03-16 21:33:54'),
(86, 90, 1, 'MOTO BIIC', NULL, 1.000, 130.00, 0.00, 130.00, 'TEMPORAL', NULL, NULL, 19, 'Coordinado con Gerencia', '2026-03-16 21:34:59'),
(87, 91, 1, 'MOTO BIIC', NULL, 1.000, 130.00, 0.00, 130.00, 'TEMPORAL', NULL, NULL, 19, 'Coordinado con Gerencia', '2026-03-16 21:35:53'),
(88, 92, 1, 'MOTO BIIC', NULL, 1.000, 130.00, 0.00, 130.00, 'TEMPORAL', NULL, NULL, 19, 'Coordinado con Gerencia', '2026-03-16 21:37:07'),
(89, 93, 1, 'MOTO BIIC', NULL, 1.000, 130.00, 0.00, 130.00, 'TEMPORAL', NULL, NULL, 19, 'Coordinado con Gerencia', '2026-03-16 21:37:39'),
(90, 94, 11, 'Curso de actualización - Carga', NULL, 1.000, 50.00, 0.00, 50.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(91, 95, 11, 'Curso de actualización - Carga', NULL, 1.000, 70.00, 0.00, 70.00, 'LISTA', 847, 70.00, NULL, NULL, NULL),
(92, 96, 11, 'Curso de actualización - Carga', NULL, 1.000, 50.00, 0.00, 50.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(93, 97, 11, 'Curso de actualización - Carga', NULL, 1.000, 60.00, 0.00, 60.00, 'LISTA', 848, 60.00, NULL, NULL, NULL),
(94, 98, 11, 'Curso de actualización - Carga', NULL, 1.000, 60.00, 0.00, 60.00, 'LISTA', 848, 60.00, NULL, NULL, NULL),
(95, 99, 11, 'Curso de actualización - Carga', NULL, 1.000, 50.00, 0.00, 50.00, 'LISTA', 849, 50.00, NULL, NULL, NULL),
(96, 100, 11, 'Curso de actualización - Carga', NULL, 1.000, 60.00, 0.00, 60.00, 'LISTA', 848, 60.00, NULL, NULL, NULL),
(97, 101, 1, 'MOTO BIIC', NULL, 1.000, 500.00, 0.00, 500.00, 'LISTA', 1, 500.00, NULL, NULL, NULL),
(98, 102, 12, 'Curso de actualización - Pasajeros', NULL, 1.000, 150.00, 0.00, 150.00, 'LISTA', 656, 150.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pos_venta_detalle_conductores`
--

CREATE TABLE `pos_venta_detalle_conductores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_detalle_id` bigint(20) UNSIGNED NOT NULL,
  `conductor_tipo` enum('CLIENTE','REGISTRADO','PENDIENTE') NOT NULL,
  `conductor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` enum('ASIGNADO','PENDIENTE','ANULADO') NOT NULL DEFAULT 'ASIGNADO',
  `canal` varchar(30) DEFAULT NULL,
  `email_contacto` varchar(150) DEFAULT NULL,
  `nacimiento` date DEFAULT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vn_logs_precios`
--

CREATE TABLE `vn_logs_precios` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED DEFAULT NULL,
  `servicio_id` int(10) UNSIGNED DEFAULT NULL,
  `precio_id` int(10) UNSIGNED DEFAULT NULL,
  `accion` varchar(30) NOT NULL,
  `detalle` text DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vn_log_precios`
--

CREATE TABLE `vn_log_precios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_precio` int(10) UNSIGNED DEFAULT NULL,
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `accion` enum('create','update','toggle_disponible','soft_delete','set_actual','clone') NOT NULL,
  `antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`antes`)),
  `despues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`despues`)),
  `hecho_por` int(10) UNSIGNED DEFAULT NULL,
  `hecho_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vn_precios`
--

CREATE TABLE `vn_precios` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `etiqueta` varchar(80) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `vigente_desde` date DEFAULT NULL,
  `vigente_hasta` date DEFAULT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vn_precios_servicio`
--

CREATE TABLE `vn_precios_servicio` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `etiqueta` varchar(60) NOT NULL DEFAULT 'Oficial',
  `monto` decimal(10,2) NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `notas` varchar(255) DEFAULT NULL,
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `actualizado_por` int(10) UNSIGNED DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vn_precio_actual`
--

CREATE TABLE `vn_precio_actual` (
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_precio` int(10) UNSIGNED NOT NULL,
  `actualizado_por` int(10) UNSIGNED DEFAULT NULL,
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vn_servicios`
--

CREATE TABLE `vn_servicios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `categoria` varchar(60) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `actualizado_por` int(10) UNSIGNED DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vn_servicios_empresas`
--

CREATE TABLE `vn_servicios_empresas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_servicio` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `notas` varchar(255) DEFAULT NULL,
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `actualizado_por` int(10) UNSIGNED DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_banner`
--

CREATE TABLE `web_banner` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_superior` varchar(60) NOT NULL DEFAULT 'Rent Your Car',
  `titulo_principal` varchar(100) NOT NULL DEFAULT 'Interested in Renting?',
  `descripcion` varchar(220) NOT NULL,
  `boton_1_texto` varchar(40) NOT NULL,
  `boton_1_url` varchar(255) NOT NULL,
  `boton_2_texto` varchar(40) NOT NULL,
  `boton_2_url` varchar(255) NOT NULL,
  `imagen_path` varchar(255) NOT NULL DEFAULT '',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_banner`
--

INSERT INTO `web_banner` (`id`, `titulo_superior`, `titulo_principal`, `descripcion`, `boton_1_texto`, `boton_1_url`, `boton_2_texto`, `boton_2_url`, `imagen_path`, `actualizacion`) VALUES
(1, 'Matriculate ya', 'Quieres subir de categoría?', 'Animate y lleva tu curso como conductor acreditado por el MTC.', 'WhatsApp', 'https://api.whatsapp.com/send/?phone=51964881841&text&type=phone_number&app_absent=0', 'Facebook', 'https://www.facebook.com/guiasmisrutas/', 'almacen/2026/03/08/img_banner/banner-web-banner-1-20260308T204655-ee27ed.webp', '2026-03-09 18:00:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_caracteristicas`
--

CREATE TABLE `web_caracteristicas` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_rojo` varchar(40) NOT NULL DEFAULT 'Central',
  `titulo_azul` varchar(40) NOT NULL DEFAULT 'Features',
  `descripcion_general` varchar(320) NOT NULL,
  `imagen_path` varchar(255) NOT NULL DEFAULT '',
  `items_json` longtext NOT NULL,
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_caracteristicas`
--

INSERT INTO `web_caracteristicas` (`id`, `titulo_rojo`, `titulo_azul`, `descripcion_general`, `imagen_path`, `items_json`, `actualizacion`) VALUES
(1, 'NUESTRO', 'SERVICIO', 'Somos parte del Grupo Génesis, Escuelas de Conductores Profesionales autorizadas por el MTC. Brindamos recategorización AII/AIII, BIIC (moto) y el taller “Cambiemos de Actitud”, con atención rápida y orientación por WhatsApp.', '', '[{\"icono\":\"fa fa-bolt fa-2x\",\"titulo\":\"Servicio premium\",\"texto\":\"Atención ágil y confiable para ayudarte con requisitos, inscripción y seguimiento.\"},{\"icono\":\"fa fa-graduation-cap fa-2x\",\"titulo\":\"Instructores autorizados\",\"texto\":\"Clases con instructores autorizados por el MTC y contenidos actualizados.\"},{\"icono\":\"fa fa-laptop fa-2x\",\"titulo\":\"Aula Virtual\",\"texto\":\"Accede a MTC PRO y obtén clases online en nuestro moderno sistema, con evaluaciones virtuales y certificados confiables.\"},{\"icono\":\"fa fa-comments fa-2x\",\"titulo\":\"Orientación A1\",\"texto\":\"Te guiamos paso a paso para completar tu trámite sin complicaciones.\"}]', '2026-03-09 13:43:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_carrusel_empresas_config`
--

CREATE TABLE `web_carrusel_empresas_config` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_base` varchar(40) NOT NULL DEFAULT 'Customer',
  `titulo_resaltado` varchar(40) NOT NULL DEFAULT 'Suport Center',
  `descripcion_general` varchar(260) NOT NULL DEFAULT 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_carrusel_empresas_config`
--

INSERT INTO `web_carrusel_empresas_config` (`id`, `titulo_base`, `titulo_resaltado`, `descripcion_general`, `actualizacion`) VALUES
(1, 'Grupo', 'Génesis', 'En Grupo Génesis reunimos escuelas de conductores profesionales autorizadas por el MTC. Con Allain Prost, Vías Seguras y Guía Mis Rutas, te capacitamos y orientamos para que completes tu curso y sigas tu trámite con confianza.', '2026-03-13 10:26:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_carrusel_empresas_items`
--

CREATE TABLE `web_carrusel_empresas_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL,
  `titulo` varchar(80) NOT NULL,
  `profesion` varchar(80) NOT NULL,
  `imagen_path` varchar(255) NOT NULL DEFAULT '',
  `redes_json` longtext NOT NULL,
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_carrusel_empresas_items`
--

INSERT INTO `web_carrusel_empresas_items` (`id`, `orden`, `titulo`, `profesion`, `imagen_path`, `redes_json`, `actualizacion`) VALUES
(1, 1, 'GUIA MIS RUTAS - TRUJILLO', 'Escuela de Conductores', 'almacen/2026/03/09/img_carrusel_empresas/carrusel-empresa-carrusel_empresas-1-20260309T092914-cd85c5.png', '{\"whatsapp\":{\"visible\":1,\"link\":\"#\"},\"facebook\":{\"visible\":1,\"link\":\"#\"},\"instagram\":{\"visible\":0,\"link\":\"#\"},\"youtube\":{\"visible\":0,\"link\":\"#\"}}', '2026-03-13 10:26:01'),
(2, 2, 'ALLAIN PROST', 'Escuela de Conductores', 'almacen/2026/03/09/img_carrusel_empresas/carrusel-empresa-carrusel_empresas-1-20260309T092914-5dd2a9.png', '{\"whatsapp\":{\"visible\":1,\"link\":\"#\"},\"facebook\":{\"visible\":1,\"link\":\"#\"},\"instagram\":{\"visible\":0,\"link\":\"#\"},\"youtube\":{\"visible\":0,\"link\":\"#\"}}', '2026-03-13 10:26:01'),
(3, 3, 'VIAS SEGURAS', 'Escuela de Conductores', 'almacen/2026/03/09/img_carrusel_empresas/carrusel-empresa-carrusel_empresas-1-20260309T092914-37ba05.png', '{\"whatsapp\":{\"visible\":1,\"link\":\"#\"},\"facebook\":{\"visible\":1,\"link\":\"#\"},\"instagram\":{\"visible\":0,\"link\":\"#\"},\"youtube\":{\"visible\":0,\"link\":\"#\"}}', '2026-03-13 10:26:01'),
(4, 4, 'GRUPO GENESIS', 'Escuela de Conductores', 'almacen/2026/03/09/img_carrusel_empresas/carrusel-empresa-carrusel_empresas-1-20260309T092914-ca19fe.png', '{\"whatsapp\":{\"visible\":1,\"link\":\"#\"},\"facebook\":{\"visible\":1,\"link\":\"#\"},\"instagram\":{\"visible\":0,\"link\":\"#\"},\"youtube\":{\"visible\":0,\"link\":\"#\"}}', '2026-03-13 10:26:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_carrusel_servicios_config`
--

CREATE TABLE `web_carrusel_servicios_config` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_base` varchar(40) NOT NULL DEFAULT 'Vehicle',
  `titulo_resaltado` varchar(40) NOT NULL DEFAULT 'Categories',
  `descripcion_general` varchar(320) NOT NULL,
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_carrusel_servicios_config`
--

INSERT INTO `web_carrusel_servicios_config` (`id`, `titulo_base`, `titulo_resaltado`, `descripcion_general`, `actualizacion`) VALUES
(1, 'Servicios', 'más buscados', 'Estos son los cursos y trámites con mayor demanda en nuestras escuelas: recategorización AII/AIII, obtención BIIC (moto), Cambiemos de Actitud y MATPEL (A4). Escríbenos por WhatsApp y te orientamos según tu caso.', '2026-03-10 08:20:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_carrusel_servicios_items`
--

CREATE TABLE `web_carrusel_servicios_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL,
  `titulo` varchar(80) NOT NULL,
  `review_text` varchar(60) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 4,
  `mostrar_estrellas` tinyint(1) NOT NULL DEFAULT 1,
  `badge_text` varchar(80) NOT NULL,
  `detalles_json` longtext NOT NULL,
  `boton_texto` varchar(50) NOT NULL DEFAULT 'Book Now',
  `boton_url` varchar(255) NOT NULL DEFAULT '#',
  `imagen_path` varchar(255) NOT NULL DEFAULT '',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_carrusel_servicios_items`
--

INSERT INTO `web_carrusel_servicios_items` (`id`, `orden`, `titulo`, `review_text`, `rating`, `mostrar_estrellas`, `badge_text`, `detalles_json`, `boton_texto`, `boton_url`, `imagen_path`, `actualizacion`) VALUES
(1, 1, 'Curso de Recategorización AIII', 'Autorizado MTC', 5, 1, '55h mín.', '[{\"visible\":1,\"icono\":\"fa fa-hand-o-up text-primary\",\"texto\":\"Huella\"},{\"visible\":1,\"icono\":\"fa fa-book text-primary\",\"texto\":\"30h Teo\"},{\"visible\":1,\"icono\":\"fa fa-road text-primary\",\"texto\":\"25h Prac\"},{\"visible\":1,\"icono\":\"fa fa-file-text-o text-primary\",\"texto\":\"COFIPRO\"},{\"visible\":1,\"icono\":\"fa fa-certificate text-primary\",\"texto\":\"Cert. MTC\"},{\"visible\":1,\"icono\":\"fa fa-building-o text-primary\",\"texto\":\"Presencial\"}]', 'WhatsApp', 'https://wa.me/51964881841?text=Hola%2C%20quiero%20informaci%C3%B3n%20para%20Recategorizaci%C3%B3n%20AIII.', 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-8d8532.png', '2026-03-10 08:20:16'),
(2, 2, 'Curso de Recategorización AII', 'Autorizado MTC', 5, 1, '55h mín.', '[{\"visible\":1,\"icono\":\"fa fa-hand-o-up text-primary\",\"texto\":\"Huella\"},{\"visible\":1,\"icono\":\"fa fa-book text-primary\",\"texto\":\"30h Teo\"},{\"visible\":1,\"icono\":\"fa fa-road text-primary\",\"texto\":\"25h Prac\"},{\"visible\":1,\"icono\":\"fa fa-file-text-o text-primary\",\"texto\":\"COFIPRO\"},{\"visible\":1,\"icono\":\"fa fa-certificate text-primary\",\"texto\":\"Cert. MTC\"},{\"visible\":1,\"icono\":\"fa fa-building-o text-primary\",\"texto\":\"Presencial\"}]', 'WhatsApp', 'https://wa.me/51964881841?text=Hola%2C%20quiero%20informaci%C3%B3n%20para%20Recategorizaci%C3%B3n%20AII.', 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-d942ee.png', '2026-03-10 08:20:16'),
(3, 3, 'Curso de Obtención MOTO BIIC', 'Autorizado MTC', 5, 1, '23h mín.', '[{\"visible\":1,\"icono\":\"fa fa-hand-o-up text-primary\",\"texto\":\"Huella\"},{\"visible\":1,\"icono\":\"fa fa-book text-primary\",\"texto\":\"15h Teo\"},{\"visible\":1,\"icono\":\"fa fa-road\",\"texto\":\"8h Prac\"},{\"visible\":1,\"icono\":\"fa fa-user text-primary\",\"texto\":\"18+\"},{\"visible\":1,\"icono\":\"fa fa-certificate text-primary\",\"texto\":\"Cert. MTC\"},{\"visible\":1,\"icono\":\"fa fa-building-o text-primary\",\"texto\":\"Presencial\"}]', 'WhatsApp', 'https://wa.me/51964881841?text=Hola%2C%20quiero%20informaci%C3%B3n%20para%20Obtenci%C3%B3n%20MOTO%20BIIC.', 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-1c6764.png', '2026-03-10 08:20:16'),
(4, 4, 'Taller Cambiemos de Actitud', 'Para sancionados', 5, 1, 'Vig. 6 meses', '[{\"visible\":1,\"icono\":\"fa fa-th-large text-primary\",\"texto\":\"3 módulos\"},{\"visible\":1,\"icono\":\"fa fa-clock-o text-primary\",\"texto\":\"90min c\\/u\"},{\"visible\":1,\"icono\":\"fa fa-check-square-o text-primary\",\"texto\":\"Eval 10p\"},{\"visible\":1,\"icono\":\"fa fa-calendar text-primary\",\"texto\":\"Vig 6m\"},{\"visible\":1,\"icono\":\"fa fa-hand-o-up text-primary\",\"texto\":\"Huella\"},{\"visible\":1,\"icono\":\"fa fa-certificate text-primary\",\"texto\":\"Constancia\"}]', 'WhatsApp', 'https://wa.me/51964881841?text=Hola%2C%20quiero%20informaci%C3%B3n%20del%20Taller%20Cambiemos%20de%20Actitud.', 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-77ae58.png', '2026-03-10 08:20:16'),
(5, 5, 'MATPEL - Residuos Peligrosos', 'Licencia especial A4', 5, 1, 'Eval 70%', '[{\"visible\":1,\"icono\":\"fa fa-list-ol text-primary\",\"texto\":\"9 clases\"},{\"visible\":1,\"icono\":\"fa fa-check-circle text-primary\",\"texto\":\"70%+\"},{\"visible\":1,\"icono\":\"fa fa-refresh text-primary\",\"texto\":\"3 años\"},{\"visible\":1,\"icono\":\"fa fa-life-ring text-primary\",\"texto\":\"Plan cont.\"},{\"visible\":1,\"icono\":\"fa fa-certificate text-primary\",\"texto\":\"Cert. MTC\"},{\"visible\":1,\"icono\":\"fa fa-credit-card text-primary\",\"texto\":\"A4\"}]', 'WhatsApp', 'https://wa.me/51964881841?text=Hola%2C%20quiero%20informaci%C3%B3n%20del%20curso%20MATPEL%20(A4).', 'almacen/2026/03/09/img_carrusel_servicios/carrusel-servicio-carrusel_servicios-1-20260309T003502-665200.png', '2026-03-10 08:20:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_contadores`
--

CREATE TABLE `web_contadores` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `items_json` longtext NOT NULL,
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_contadores`
--

INSERT INTO `web_contadores` (`id`, `items_json`, `actualizacion`) VALUES
(1, '[{\"icono\":\"fas fa-thumbs-up fa-2x\",\"numero\":\"5000\",\"titulo\":\"Conductores satisfechos\"},{\"icono\":\"fas fa-car-alt fa-2x\",\"numero\":\"20\",\"titulo\":\"servicios para conductores\"},{\"icono\":\"fas fa-building fa-2x\",\"numero\":\"10\",\"titulo\":\"Escuelas de conductores\"},{\"icono\":\"fas fa-clock fa-2x\",\"numero\":\"10\",\"titulo\":\"Años de experiencia\"}]', '2026-03-08 19:12:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_formulario_carrusel_items`
--

CREATE TABLE `web_formulario_carrusel_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL,
  `titulo` varchar(140) NOT NULL,
  `texto` varchar(260) NOT NULL,
  `imagen_path` varchar(255) NOT NULL DEFAULT '',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_formulario_carrusel_items`
--

INSERT INTO `web_formulario_carrusel_items` (`id`, `orden`, `titulo`, `texto`, `imagen_path`, `actualizacion`) VALUES
(1, 1, 'Trámite y capacitación', 'Te orientamos paso a paso. Inscripción rápida y atención inmediata por WhatsApp.', 'almacen/2026/03/09/img_formulario_carrusel/slide-formulario-carrusel-formulario_carrusel-1-20260309T133558-a65eac.png', '2026-03-09 13:38:21'),
(2, 2, 'Recategorización AII y AIII', 'Clases presenciales con instructores autorizados y control biométrico para un proceso válido y seguro.', 'almacen/2026/03/09/img_formulario_carrusel/slide-formulario-carrusel-formulario_carrusel-1-20260309T133558-50f51e.png', '2026-03-09 13:38:21'),
(3, 3, 'Cambiemos de Actitud (sancionados)', 'Cumple con el taller y regulariza tu situación con una institución autorizada.', 'almacen/2026/03/09/img_formulario_carrusel/slide-formulario-carrusel-formulario_carrusel-1-20260309T133558-c227ee.png', '2026-03-09 13:38:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_formulario_carrusel_mensajes`
--

CREATE TABLE `web_formulario_carrusel_mensajes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo_solicitante` enum('persona','empresa') NOT NULL,
  `servicio_codigo` varchar(80) NOT NULL,
  `servicio_nombre` varchar(150) NOT NULL,
  `ciudad_codigo` varchar(80) NOT NULL,
  `ciudad_nombre` varchar(80) NOT NULL,
  `escuela_nombre` varchar(120) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `nombres_apellidos` varchar(140) NOT NULL DEFAULT '',
  `razon_social` varchar(160) NOT NULL DEFAULT '',
  `celular` varchar(20) NOT NULL,
  `correo` varchar(150) NOT NULL DEFAULT '',
  `horario_codigo` varchar(20) NOT NULL,
  `horario_nombre` varchar(60) NOT NULL,
  `estado` enum('en_espera','contactado','venta_cerrada','no_cerrada','no_contesto') NOT NULL DEFAULT 'en_espera',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_formulario_carrusel_mensajes`
--

INSERT INTO `web_formulario_carrusel_mensajes` (`id`, `tipo_solicitante`, `servicio_codigo`, `servicio_nombre`, `ciudad_codigo`, `ciudad_nombre`, `escuela_nombre`, `documento`, `nombres_apellidos`, `razon_social`, `celular`, `correo`, `horario_codigo`, `horario_nombre`, `estado`, `fecha_registro`, `actualizacion`) VALUES
(1, 'persona', 'obtencion_moto_biic', 'Obtencion MOTO BIIC', 'chocope_escuela_allain_prost', 'Chocope', 'Escuela Allain Prost', '70225235', 'JUAN VARGAS RIOS', '', '966636521', 'a@gmail.com', '10_12', '10:00 am a 12:00 pm', 'no_cerrada', '2026-03-08 22:55:53', '2026-03-08 23:12:47'),
(2, 'empresa', 'manejo_defensivo', 'Manejo defensivo', 'chocope_escuela_allain_prost', 'Chocope', 'Escuela Allain Prost', '20603652145', '', 'AAAA SAC', '964881854', '', '12_14', '12:00 pm a 2:00 pm', 'venta_cerrada', '2026-03-08 22:56:26', '2026-03-08 23:12:41'),
(3, 'persona', 'curso_matpel_a4', 'Curso MATPEL / Licencia A4', 'la_merced_allain_prost', 'La Merced', 'Allain Prost', '70336325', 'MARIO PINTO CUADROS', '', '966666565', 'a@gmail.com', '10_12', '10:00 am a 12:00 pm', 'contactado', '2026-03-08 23:08:31', '2026-03-08 23:12:35'),
(4, 'empresa', 'curso_matpel_a4', 'Curso MATPEL / Licencia A4', 'huaraz_escuela_guia_mis_rutas', 'Huaraz', 'Escuela Guia mis Rutas', '20605633625', '', 'EMPRESITA SAC', '966666352', 'ore@gmail.pe', '17_19', '5:00 pm a 7:00 pm', 'en_espera', '2026-03-08 23:17:40', '2026-03-08 23:17:40'),
(5, 'persona', 'curso_actualizacion_personas', 'Curso de actualizacion normativa - Personas', 'huancayo_escuela_allain_prost', 'Huancayo', 'Escuela Allain Prost', '70379962', 'JUAN LARA LARA', '', '869998568', '', 'any', 'Cualquier horario', 'en_espera', '2026-03-08 23:18:32', '2026-03-08 23:18:32'),
(6, 'persona', 'primeros_auxilios', 'Primeros auxilios en accidentes de transito', 'chocope_escuela_allain_prost', 'Chocope', 'Escuela Allain Prost', '70366363', 'LUISA MARIA MARIA', '', '363621402', 'a@gmail.com', '12_14', '12:00 pm a 2:00 pm', 'en_espera', '2026-03-08 23:20:19', '2026-03-08 23:20:19'),
(7, 'empresa', 'educacion_vial', 'Educacion vial', 'huancayo_escuela_allain_prost', 'Huancayo', 'Escuela Allain Prost', '20603636363', '', 'CEMENTOS SAC', '963333632', 'a@duck.pe', 'any', 'Cualquier horario', 'en_espera', '2026-03-08 23:38:08', '2026-03-08 23:38:08'),
(8, 'empresa', 'primeros_auxilios', 'Primeros auxilios en accidentes de transito', 'chiclayo_escuela_vias_seguras', 'Chiclayo', 'Escuela Vias Seguras', '20603336321', '', 'LLANTAS SAC', '966666666', '', '12_14', '12:00 pm a 2:00 pm', 'en_espera', '2026-03-08 23:39:19', '2026-03-08 23:39:19'),
(9, 'persona', 'educacion_vial', 'Educacion vial', 'chiclayo_escuela_vias_seguras', 'Chiclayo', 'Escuela Vias Seguras', '70115235', 'MARTIN MENDOZA MARCIAL', '', '966363632', 'q@gmail.com', 'any', 'Cualquier horario', 'en_espera', '2026-03-08 23:39:57', '2026-03-08 23:39:57'),
(10, 'persona', 'obtencion_moto_biic', 'Obtencion MOTO BIIC', 'huancayo_escuela_allain_prost', 'Huancayo', 'Escuela Allain Prost', '70000021', 'EUSEBIO RODRIGUEZ VASQUEZ', '', '964444451', '', '10_12', '10:00 am a 12:00 pm', 'en_espera', '2026-03-09 12:19:09', '2026-03-09 12:19:09'),
(11, 'empresa', 'curso_actualizacion_personas', 'Curso de actualizacion normativa - Personas', 'piura_allain_prost', 'Piura', 'Allain Prost', '20609876789', '', 'LLANTAS SAC', '968878765', 'qqq@gmail.com', '14_17', '2:00 pm a 5:00 pm', 'contactado', '2026-03-10 11:24:43', '2026-03-10 11:25:36'),
(12, 'persona', 'obtencion_moto_biic', 'Obtencion MOTO BIIC', 'trujillo_escuela_guia_mis_rutas', 'Trujillo', 'Escuela Guia mis Rutas', '70363632', 'LUIGI VILLANUEVA PEREZ', '', '964881523', 'Q@GMAIL.COM', '14_17', '2:00 pm a 5:00 pm', 'en_espera', '2026-03-13 10:21:28', '2026-03-13 10:21:28'),
(13, 'empresa', 'obtencion_moto_biic', 'Obtencion MOTO BIIC', 'piura_allain_prost', 'Piura', 'Allain Prost', '20602536212', '', 'LLANTAS SA', '964886325', 'q@gmail.com', '10_12', '10:00 am a 12:00 pm', 'venta_cerrada', '2026-03-13 22:07:01', '2026-03-13 22:07:44'),
(14, 'persona', 'curso_matpel_a4', 'Curso MATPEL / Licencia A4', 'trujillo_escuela_guia_mis_rutas', 'Trujillo', 'Escuela Guia mis Rutas', '76315077', 'malena', '', '975018100', 'mafesure301@gmail.com', '12_14', '12:00 pm a 2:00 pm', 'en_espera', '2026-03-14 10:39:03', '2026-03-14 10:39:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_menu`
--

CREATE TABLE `web_menu` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_pagina` varchar(120) NOT NULL,
  `logo_path` varchar(255) NOT NULL DEFAULT '',
  `menu_items_json` longtext NOT NULL,
  `boton_texto` varchar(80) NOT NULL,
  `boton_url` varchar(255) NOT NULL,
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_menu`
--

INSERT INTO `web_menu` (`id`, `titulo_pagina`, `logo_path`, `menu_items_json`, `boton_texto`, `boton_url`, `actualizacion`) VALUES
(1, 'brevete.pe', 'almacen/2026/03/09/logo_web/logo-web-menu-1-20260309T122700-9ee68a.png', '[{\"texto\":\"Inicio\",\"url\":\"\\/\",\"visible\":1,\"submenus\":[]},{\"texto\":\"Nosotros\",\"url\":\"#nosotros\",\"visible\":1,\"submenus\":[]},{\"texto\":\"Servicios\",\"url\":\"#categorias\",\"visible\":1,\"submenus\":[]},{\"texto\":\"Noticias\",\"url\":\"#blog\",\"visible\":1,\"submenus\":[]},{\"texto\":\"Escuelas\",\"url\":\"#equipo\",\"visible\":1,\"submenus\":[]},{\"texto\":\"Contacto\",\"url\":\"#promocion\",\"visible\":1,\"submenus\":[]}]', 'Mtc Pro', '/sistema/login.php', '2026-03-09 18:14:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_nosotros`
--

CREATE TABLE `web_nosotros` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_base` varchar(40) NOT NULL DEFAULT 'Cental',
  `titulo_resaltado` varchar(40) NOT NULL DEFAULT 'About',
  `descripcion_principal` varchar(320) NOT NULL,
  `tarjetas_json` longtext NOT NULL,
  `descripcion_secundaria` varchar(500) NOT NULL,
  `experiencia_numero` varchar(10) NOT NULL DEFAULT '17',
  `experiencia_texto` varchar(80) NOT NULL DEFAULT 'Years Of Experience',
  `checklist_json` longtext NOT NULL,
  `boton_texto` varchar(80) NOT NULL DEFAULT 'More About Us',
  `boton_url` varchar(255) NOT NULL DEFAULT '#',
  `fundador_nombre` varchar(80) NOT NULL DEFAULT 'William Burgess',
  `fundador_cargo` varchar(80) NOT NULL DEFAULT 'Carveo Founder',
  `imagen_fundador_path` varchar(255) NOT NULL DEFAULT '',
  `imagen_principal_path` varchar(255) NOT NULL DEFAULT '',
  `imagen_secundaria_path` varchar(255) NOT NULL DEFAULT '',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_nosotros`
--

INSERT INTO `web_nosotros` (`id`, `titulo_base`, `titulo_resaltado`, `descripcion_principal`, `tarjetas_json`, `descripcion_secundaria`, `experiencia_numero`, `experiencia_texto`, `checklist_json`, `boton_texto`, `boton_url`, `fundador_nombre`, `fundador_cargo`, `imagen_fundador_path`, `imagen_principal_path`, `imagen_secundaria_path`, `actualizacion`) VALUES
(1, 'SOBRE', 'NOSOTROS', 'Somos Grupo Génesis, un grupo empresarial en Perú que integra Escuelas de Conductores Profesionales autorizadas por el MTC. Ayudamos a conductores y empresas a capacitarse y cumplir sus requisitos con un servicio rápido, claro y confiable, con atención principal por WhatsApp.', '[{\"icono_path\":\"\",\"titulo\":\"Nuestra Visión\",\"texto\":\"Ser el grupo líder en formación y certificación de conductores y transporte en el Perú, destacando por calidad, confianza y cumplimiento normativo.\"},{\"icono_path\":\"\",\"titulo\":\"Nuestra Misión\",\"texto\":\"Brindar capacitación y orientación autorizada por el MTC, con instructores calificados, procesos transparentes y una experiencia ágil para cada alumno.\"}]', 'Contamos con sedes y marcas que forman parte del grupo (Allain Prost, Vías Seguras, Guía Mis Rutas y Argos) y complementamos la formación con aula virtual MTC PRO y certificados verificables por QR.', '10', 'Años de experiencia', '[\"Autorizados por el MTC\",\"Control y validación biométrica\",\"Certificados con QR verificable\",\"Atención rápida por WhatsApp\"]', 'Conoce más', '#', 'Giancarlo Suarez', 'Gerente General', '', '', 'almacen/2026/03/09/img_nosotros/nosotros-secundaria-nosotros-1-20260309T135348-7813f7.png', '2026-03-09 13:53:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_novedades_config`
--

CREATE TABLE `web_novedades_config` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_base` varchar(40) NOT NULL DEFAULT 'Cental',
  `titulo_resaltado` varchar(40) NOT NULL DEFAULT 'Blog & News',
  `descripcion_general` varchar(280) NOT NULL DEFAULT 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_novedades_config`
--

INSERT INTO `web_novedades_config` (`id`, `titulo_base`, `titulo_resaltado`, `descripcion_general`, `actualizacion`) VALUES
(1, 'Noticias &', 'Novedades', 'Entérate de nuestras últimas novedades, campañas y aperturas de matrícula. Para confirmar horarios y vacantes, escríbenos por WhatsApp.', '2026-03-09 17:58:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_novedades_items`
--

CREATE TABLE `web_novedades_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `titulo` varchar(110) NOT NULL,
  `meta_1_icono` varchar(120) NOT NULL DEFAULT 'fa fa-user text-primary',
  `meta_1_texto` varchar(80) NOT NULL DEFAULT 'Autor',
  `meta_2_icono` varchar(120) NOT NULL DEFAULT 'fa fa-comment-alt text-primary',
  `meta_2_texto` varchar(80) NOT NULL DEFAULT 'Sin comentarios',
  `badge_texto` varchar(50) NOT NULL DEFAULT 'Novedad',
  `resumen_texto` varchar(220) NOT NULL,
  `boton_texto` varchar(50) NOT NULL DEFAULT 'Read More',
  `boton_url` varchar(255) NOT NULL DEFAULT '#',
  `imagen_path` varchar(255) NOT NULL DEFAULT '',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_novedades_items`
--

INSERT INTO `web_novedades_items` (`id`, `orden`, `visible`, `titulo`, `meta_1_icono`, `meta_1_texto`, `meta_2_icono`, `meta_2_texto`, `badge_texto`, `resumen_texto`, `boton_texto`, `boton_url`, `imagen_path`, `actualizacion`) VALUES
(4, 1, 1, 'Matrículas AII y AIII abiertas', 'fa fa-phone text-primary', 'Respuesta rápida', 'fa fa-calendar text-primary', 'Turnos disponibles', '09 Mar 2026', 'Nuevos grupos presenciales con control biométrico y guía completa para tu recategorización.', 'Cotizar por WhatsApp', '#', 'almacen/2026/03/09/img_novedades/novedad-blog-novedades-1-20260309T175818-8ef62f.png', '2026-03-09 17:58:18'),
(5, 2, 1, 'BIIC Moto con nuevos horarios', 'fa fa-motorcycle text-primary', 'BIIC Moto', 'fa fa-clock text-primary', 'Horarios flexibles', '09 Mar 2026', 'Formación teórica y práctica para BIIC, con asesoría rápida y atención directa por WhatsApp.', 'Pedir información', '#', 'almacen/2026/03/09/img_novedades/novedad-blog-novedades-1-20260309T175647-683df9.png', '2026-03-09 17:58:18'),
(6, 3, 1, 'MATPEL A4: cupos disponibles hoy', 'fa fa-shield-alt text-primary', 'Seguridad primero', 'fa fa-certificate text-primary', 'Certificado MTC', '09 Mar 2026', 'Capacitación en protocolos y seguridad para transporte de materiales o residuos peligrosos A4.', 'Consultar por WhatsApp', '#', 'almacen/2026/03/09/img_novedades/novedad-blog-novedades-1-20260309T175818-702759.png', '2026-03-09 17:58:18'),
(7, 4, 1, 'Convenios para empresas y flotas', 'fa fa-building text-primary', 'Tarifas por convenio', 'fa fa-tag text-primary', 'Precios especiales', '09 Mar 2026', 'Precios especiales por convenio en cursos de transporte, con certificados verificables y soporte continuo.', 'Solicitar cotización', '#', 'almacen/2026/03/09/img_novedades/novedad-blog-novedades-1-20260309T175647-129ae7.jpg', '2026-03-09 17:58:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_proceso`
--

CREATE TABLE `web_proceso` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_base` varchar(35) NOT NULL DEFAULT 'Cental',
  `titulo_resaltado` varchar(35) NOT NULL DEFAULT 'Process',
  `descripcion_general` varchar(280) NOT NULL,
  `items_json` longtext NOT NULL,
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_proceso`
--

INSERT INTO `web_proceso` (`id`, `titulo_base`, `titulo_resaltado`, `descripcion_general`, `items_json`, `actualizacion`) VALUES
(1, 'Cómo', 'funciona', 'Te acompañamos desde el primer mensaje hasta la emisión de tu certificado. El proceso incluye matrícula, asistencia con control biométrico y validación final para que continúes tu trámite sin complicaciones.', '[{\"titulo\":\"Nos contactas\",\"texto\":\"Escríbenos por WhatsApp y te indicamos requisitos, horarios y sedes según el curso que necesitas.\"},{\"titulo\":\"Eliges un curso\",\"texto\":\"Seleccionas el servicio: AII\\/AIII, BIIC, Cambiemos de Actitud, MATPEL (A4) u otros cursos de transporte.\"},{\"titulo\":\"Completas el curso\",\"texto\":\"Realizas tus clases y asistencias con registro de huella biométrica y control por cámara durante el proceso.\"},{\"titulo\":\"Recibes tu certificado\",\"texto\":\"Te entregamos tu certificado validado por el MTC (y\\/o verificable), y quedas listo para seguir con tu obtención, revalidación o recategorización.\"}]', '2026-03-09 14:02:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_servicios`
--

CREATE TABLE `web_servicios` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_base` varchar(40) NOT NULL DEFAULT 'Cental',
  `titulo_resaltado` varchar(40) NOT NULL DEFAULT 'Services',
  `descripcion_general` varchar(320) NOT NULL,
  `items_json` longtext NOT NULL,
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_servicios`
--

INSERT INTO `web_servicios` (`id`, `titulo_base`, `titulo_resaltado`, `descripcion_general`, `items_json`, `actualizacion`) VALUES
(1, 'Nuestros', 'Servicios', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,', '[{\"icono\":\"fa fa-phone-alt fa-2x\",\"titulo\":\"Curso de Recategorización AII y AIII\",\"texto\":\"Capacitación para conducir de forma profesional y completar tu proceso de recategorización.\"},{\"icono\":\"fa fa-money-bill-alt fa-2x\",\"titulo\":\"Curso de Obtención MOTO BIIC\",\"texto\":\"Formación teórica y práctica para obtener tu licencia de moto BIIC de manera segura.\"},{\"icono\":\"fa fa-road fa-2x\",\"titulo\":\"Actualización de la Normativa\",\"texto\":\"Actualiza tus conocimientos de normas de transporte para operar correctamente y evitar sanciones.\"},{\"icono\":\"fa fa-umbrella fa-2x\",\"titulo\":\"Taller Cambiemos de Actitud\",\"texto\":\"Taller para conductores sancionados que refuerza conductas seguras y cumple el requisito exigido.\"},{\"icono\":\"fa fa-building fa-2x\",\"titulo\":\"MATPEL - Residuos Peligrosos\",\"texto\":\"Capacitación en seguridad y protocolos para el transporte de materiales o residuos peligrosos (A4).\"},{\"icono\":\"fa fa-car-alt fa-2x\",\"titulo\":\"Manejo Defensivo\",\"texto\":\"Técnicas para anticipar riesgos y prevenir accidentes en la vía.\"}]', '2026-03-09 13:57:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_testimonios_config`
--

CREATE TABLE `web_testimonios_config` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `titulo_base` varchar(40) NOT NULL DEFAULT 'Our Clients',
  `titulo_resaltado` varchar(40) NOT NULL DEFAULT 'Riviews',
  `descripcion_general` varchar(260) NOT NULL DEFAULT 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_testimonios_config`
--

INSERT INTO `web_testimonios_config` (`id`, `titulo_base`, `titulo_resaltado`, `descripcion_general`, `actualizacion`) VALUES
(1, 'Testimonios', 'de clientes', 'La experiencia de nuestros alumnos y conductores nos respalda. Atención rápida por WhatsApp, clases claras y acompañamiento hasta obtener el certificado.', '2026-03-09 18:07:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_testimonios_items`
--

CREATE TABLE `web_testimonios_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL,
  `nombre_cliente` varchar(80) NOT NULL,
  `profesion` varchar(80) NOT NULL,
  `testimonio` varchar(280) NOT NULL,
  `imagen_path` varchar(255) NOT NULL DEFAULT '',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_testimonios_items`
--

INSERT INTO `web_testimonios_items` (`id`, `orden`, `nombre_cliente`, `profesion`, `testimonio`, `imagen_path`, `actualizacion`) VALUES
(1, 1, 'Juan Paredes Perez', 'Taxista', '“Me orientaron desde el primer día. Todo fue ordenado con el control de huella y terminé mi curso sin complicaciones. Recomendado.”', 'almacen/2026/03/09/img_testimonios/testimonio-cliente-testimonios-1-20260309T100405-67da69.jpg', '2026-03-09 18:07:21'),
(2, 2, 'Diana Paz Rojas', 'Transporte Escolar', '“Buena atención y clases bien explicadas. Me ayudaron con los requisitos y al final recibí mi certificado para seguir mi trámite.”', 'almacen/2026/03/09/img_testimonios/testimonio-cliente-testimonios-1-20260309T100405-760983.jpg', '2026-03-09 18:07:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_topbar_config`
--

CREATE TABLE `web_topbar_config` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `direccion` varchar(180) NOT NULL,
  `telefono` char(9) NOT NULL,
  `correo` varchar(120) NOT NULL,
  `whatsapp_url` varchar(255) NOT NULL,
  `facebook_url` varchar(255) NOT NULL DEFAULT '',
  `instagram_url` varchar(255) NOT NULL DEFAULT '',
  `youtube_url` varchar(255) NOT NULL DEFAULT '',
  `actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `web_topbar_config`
--

INSERT INTO `web_topbar_config` (`id`, `direccion`, `telefono`, `correo`, `whatsapp_url`, `facebook_url`, `instagram_url`, `youtube_url`, `actualizacion`) VALUES
(1, 'Av. Los Incas 154, Trujillo', '942148348', 'corporativoo.genesis@gmail.com', 'https://wa.me/51964881841', 'https://www.facebook.com/guiasmisrutas/', '', '', '2026-03-10 07:19:38');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `al_alertas`
--
ALTER TABLE `al_alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_al_empresa` (`id_empresa`),
  ADD KEY `idx_al_empresa_activo` (`id_empresa`,`activo`),
  ADD KEY `idx_al_empresa_tipo` (`id_empresa`,`tipo`);

--
-- Indices de la tabla `al_alertas_log`
--
ALTER TABLE `al_alertas_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_al_log_alerta` (`id_alerta`);

--
-- Indices de la tabla `cam_camaras`
--
ALTER TABLE `cam_camaras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cam_empresa_nombre` (`id_empresa`,`nombre`),
  ADD KEY `idx_cam_id_empresa` (`id_empresa`);

--
-- Indices de la tabla `cam_camaras_usuarios`
--
ALTER TABLE `cam_camaras_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cam_usuario` (`id_camara`,`usuario`),
  ADD KEY `idx_ccu_camara` (`id_camara`);

--
-- Indices de la tabla `cam_hdd`
--
ALTER TABLE `cam_hdd`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cam_hdd_camara` (`id_camara`),
  ADD KEY `idx_cam_hdd_estado` (`estado`),
  ADD KEY `idx_cam_hdd_camara_estado` (`id_camara`,`estado`);

--
-- Indices de la tabla `cam_hdd_consumo`
--
ALTER TABLE `cam_hdd_consumo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cam_hdd_consumo_dia` (`id_hdd`,`fecha_dia`),
  ADD KEY `idx_cam_hdd_consumo_hdd` (`id_hdd`);

--
-- Indices de la tabla `ce_componentes`
--
ALTER TABLE `ce_componentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pl_orden` (`plantilla_id`,`orden`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Indices de la tabla `ce_componente_opciones`
--
ALTER TABLE `ce_componente_opciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_comp_valor` (`componente_id`,`valor`),
  ADD KEY `idx_comp_orden` (`componente_id`,`orden`);

--
-- Indices de la tabla `ce_plantillas`
--
ALTER TABLE `ce_plantillas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `fk_ce_pl_user` (`creado_por`);

--
-- Indices de la tabla `com_comunicados`
--
ALTER TABLE `com_comunicados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_com_activo` (`activo`),
  ADD KEY `idx_com_inicio` (`fecha_inicio`),
  ADD KEY `idx_com_fin` (`fecha_fin`),
  ADD KEY `idx_com_limite` (`fecha_limite`);

--
-- Indices de la tabla `com_comunicado_target`
--
ALTER TABLE `com_comunicado_target`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tgt_com` (`comunicado_id`),
  ADD KEY `idx_tgt_tipo` (`tipo`),
  ADD KEY `idx_tgt_user` (`usuario_id`),
  ADD KEY `idx_tgt_rol` (`rol_id`),
  ADD KEY `idx_tgt_emp` (`empresa_id`);

--
-- Indices de la tabla `com_comunicado_vista`
--
ALTER TABLE `com_comunicado_vista`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_vista_com_user` (`comunicado_id`,`usuario_id`),
  ADD KEY `idx_vista_flags` (`visto`,`leido`),
  ADD KEY `idx_vista_times` (`visto_en`,`leido_en`),
  ADD KEY `fk_vista_user` (`usuario_id`);

--
-- Indices de la tabla `cq_categorias_licencia`
--
ALTER TABLE `cq_categorias_licencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cq_cl_codigo` (`codigo`),
  ADD KEY `idx_cq_cl_tipo_categoria` (`tipo_categoria`);

--
-- Indices de la tabla `cq_certificados`
--
ALTER TABLE `cq_certificados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cq_cert_empresa_corr` (`id_empresa`,`correlativo_empresa`),
  ADD UNIQUE KEY `uk_cq_cert_empresa_codigo` (`id_empresa`,`codigo_certificado`),
  ADD UNIQUE KEY `uk_cq_cert_codigo_qr` (`codigo_qr`),
  ADD KEY `idx_cq_cert_emp_estado_fecha` (`id_empresa`,`estado`,`fecha_emision`),
  ADD KEY `idx_cq_cert_emp_curso` (`id_empresa`,`id_curso`),
  ADD KEY `idx_cq_cert_emp_doc` (`id_empresa`,`documento_cliente`),
  ADD KEY `idx_cq_cert_emp_nombre` (`id_empresa`,`apellidos_cliente`,`nombres_cliente`),
  ADD KEY `fk_cq_cert_usuario` (`id_usuario_emisor`),
  ADD KEY `fk_cq_cert_curso` (`id_curso`),
  ADD KEY `fk_cq_cert_plantilla` (`id_plantilla_certificado`),
  ADD KEY `fk_cq_cert_tipo_doc` (`id_tipo_doc`),
  ADD KEY `fk_cq_cert_categoria` (`id_categoria_licencia`);

--
-- Indices de la tabla `cq_plantillas_certificados`
--
ALTER TABLE `cq_plantillas_certificados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pc_empresa_nombre` (`id_empresa`,`nombre`),
  ADD KEY `idx_pc_empresa` (`id_empresa`);

--
-- Indices de la tabla `cq_plantillas_elementos`
--
ALTER TABLE `cq_plantillas_elementos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pe_plantilla_elemento` (`id_plantilla_certificado`,`codigo_elemento`);

--
-- Indices de la tabla `cq_plantillas_posiciones`
--
ALTER TABLE `cq_plantillas_posiciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pp_plantilla_elemento_pagina` (`id_plantilla_certificado`,`codigo_elemento`,`pagina`);

--
-- Indices de la tabla `cq_tipos_documento`
--
ALTER TABLE `cq_tipos_documento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cq_td_codigo` (`codigo`);

--
-- Indices de la tabla `cr_cursos`
--
ALTER TABLE `cr_cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cursos_activo` (`activo`);

--
-- Indices de la tabla `cr_curso_etiqueta`
--
ALTER TABLE `cr_curso_etiqueta`
  ADD PRIMARY KEY (`curso_id`,`etiqueta_id`),
  ADD KEY `fk_cr_ce_etiqueta` (`etiqueta_id`);

--
-- Indices de la tabla `cr_etiquetas`
--
ALTER TABLE `cr_etiquetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cr_tags_nombre` (`nombre`);

--
-- Indices de la tabla `cr_formularios`
--
ALTER TABLE `cr_formularios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cr_form_public_code` (`public_code`),
  ADD KEY `idx_cr_form_empresa_modo_estado` (`empresa_id`,`modo`,`estado`),
  ADD KEY `idx_cr_form_grupo` (`grupo_id`),
  ADD KEY `idx_cr_form_curso_tema` (`curso_id`,`tema_id`),
  ADD KEY `fk_cr_form_tema` (`tema_id`);

--
-- Indices de la tabla `cr_formulario_intentos`
--
ALTER TABLE `cr_formulario_intentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cr_fi_token` (`token`),
  ADD UNIQUE KEY `uq_cr_fi_fast` (`formulario_id`,`tipo_doc_id`,`nro_doc`,`intento_nro`),
  ADD UNIQUE KEY `uq_cr_fi_aula` (`formulario_id`,`usuario_id`,`intento_nro`),
  ADD KEY `idx_cr_fi_form_status` (`formulario_id`,`status`),
  ADD KEY `idx_cr_fi_form_doc` (`formulario_id`,`tipo_doc_id`,`nro_doc`),
  ADD KEY `idx_cr_fi_form_user` (`formulario_id`,`usuario_id`),
  ADD KEY `fk_cr_fi_usuario` (`usuario_id`),
  ADD KEY `fk_cr_fi_tipo_doc` (`tipo_doc_id`);

--
-- Indices de la tabla `cr_formulario_intento_categoria`
--
ALTER TABLE `cr_formulario_intento_categoria`
  ADD PRIMARY KEY (`intento_id`,`categoria_id`),
  ADD KEY `idx_cr_fic_categoria` (`categoria_id`);

--
-- Indices de la tabla `cr_formulario_opciones`
--
ALTER TABLE `cr_formulario_opciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cr_fo_preg_orden` (`pregunta_id`,`orden`);

--
-- Indices de la tabla `cr_formulario_preguntas`
--
ALTER TABLE `cr_formulario_preguntas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cr_fp_form_orden` (`formulario_id`,`orden`);

--
-- Indices de la tabla `cr_formulario_respuestas`
--
ALTER TABLE `cr_formulario_respuestas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cr_fr_intento_pregunta` (`intento_id`,`pregunta_id`),
  ADD KEY `idx_cr_fr_pregunta` (`pregunta_id`);

--
-- Indices de la tabla `cr_grupos`
--
ALTER TABLE `cr_grupos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_grupos_codigo` (`codigo`),
  ADD KEY `idx_grupos_curso` (`curso_id`),
  ADD KEY `idx_grupos_empresa` (`empresa_id`),
  ADD KEY `idx_grupos_curso_empresa` (`curso_id`,`empresa_id`),
  ADD KEY `idx_grupos_empresa_curso_activo` (`empresa_id`,`curso_id`,`activo`);

--
-- Indices de la tabla `cr_matriculas_grupo`
--
ALTER TABLE `cr_matriculas_grupo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_matricula_usuario_curso` (`usuario_id`,`curso_id`),
  ADD KEY `idx_matricula_grupo` (`grupo_id`),
  ADD KEY `idx_matricula_estado` (`estado`),
  ADD KEY `idx_matricula_curso` (`curso_id`),
  ADD KEY `idx_matricula_expulsado_by` (`expulsado_by`);

--
-- Indices de la tabla `cr_temas`
--
ALTER TABLE `cr_temas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cr_temas_curso` (`curso_id`);

--
-- Indices de la tabla `cr_usuario_curso`
--
ALTER TABLE `cr_usuario_curso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ucr_usuario_curso` (`usuario_id`,`curso_id`),
  ADD KEY `idx_ucr_usuario_activo` (`usuario_id`,`activo`,`curso_id`),
  ADD KEY `idx_ucr_curso_activo` (`curso_id`,`activo`,`usuario_id`),
  ADD KEY `fk_ucr_asignado_por` (`asignado_por`);

--
-- Indices de la tabla `egr_correlativos`
--
ALTER TABLE `egr_correlativos`
  ADD PRIMARY KEY (`id_empresa`);

--
-- Indices de la tabla `egr_egresos`
--
ALTER TABLE `egr_egresos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_egr_codigo` (`codigo`),
  ADD UNIQUE KEY `uk_egr_emp_correlativo` (`id_empresa`,`correlativo`),
  ADD KEY `idx_egr_empresa_fecha` (`id_empresa`,`fecha_emision`),
  ADD KEY `idx_egr_estado` (`estado`),
  ADD KEY `idx_egr_caja_diaria` (`id_caja_diaria`),
  ADD KEY `idx_egr_caja_mensual` (`id_caja_mensual`),
  ADD KEY `idx_egr_creado_por` (`creado_por`),
  ADD KEY `idx_egr_anulado_por` (`anulado_por`);

--
-- Indices de la tabla `egr_egreso_fuentes`
--
ALTER TABLE `egr_egreso_fuentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_egr_fuente_egreso_caja_key` (`id_egreso`,`id_caja_diaria`,`fuente_key`),
  ADD KEY `idx_egr_fuente_emp_caja` (`id_empresa`,`id_caja_diaria`),
  ADD KEY `idx_egr_fuente_key` (`fuente_key`),
  ADD KEY `idx_egr_fuente_medio` (`medio_id`),
  ADD KEY `fk_egr_fuente_caja_diaria` (`id_caja_diaria`),
  ADD KEY `idx_egr_fuente_caja_key` (`id_caja_diaria`,`fuente_key`);

--
-- Indices de la tabla `ferreteria_productos`
--
ALTER TABLE `ferreteria_productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inv_bienes`
--
ALTER TABLE `inv_bienes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_inv_bienes_empresa_serie` (`id_empresa`,`serie`),
  ADD KEY `idx_inv_bienes_empresa` (`id_empresa`),
  ADD KEY `idx_inv_bienes_tipo` (`tipo`),
  ADD KEY `idx_inv_bienes_estado` (`estado`),
  ADD KEY `idx_inv_bienes_activo` (`activo`),
  ADD KEY `idx_inv_bienes_ubic` (`id_ubicacion`),
  ADD KEY `idx_inv_bienes_resp` (`id_responsable`);

--
-- Indices de la tabla `inv_bien_categoria`
--
ALTER TABLE `inv_bien_categoria`
  ADD PRIMARY KEY (`id_bien`,`id_categoria`),
  ADD KEY `idx_inv_bc_cat` (`id_categoria`);

--
-- Indices de la tabla `inv_categorias`
--
ALTER TABLE `inv_categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_inv_cat_empresa_nombre` (`id_empresa`,`nombre`),
  ADD KEY `idx_inv_cat_empresa` (`id_empresa`);

--
-- Indices de la tabla `inv_movimientos`
--
ALTER TABLE `inv_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inv_mov_empresa` (`id_empresa`),
  ADD KEY `idx_inv_mov_bien` (`id_bien`),
  ADD KEY `idx_inv_mov_tipo` (`tipo`),
  ADD KEY `idx_inv_mov_creado` (`creado`),
  ADD KEY `fk_inv_mov_usr` (`id_usuario`),
  ADD KEY `fk_inv_mov_desde_ubic` (`desde_ubicacion`),
  ADD KEY `fk_inv_mov_hacia_ubic` (`hacia_ubicacion`),
  ADD KEY `fk_inv_mov_desde_resp` (`desde_responsable`),
  ADD KEY `fk_inv_mov_hacia_resp` (`hacia_responsable`);

--
-- Indices de la tabla `inv_ubicaciones`
--
ALTER TABLE `inv_ubicaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_inv_ubic_empresa_nombre` (`id_empresa`,`nombre`),
  ADD KEY `idx_inv_ubic_empresa` (`id_empresa`);

--
-- Indices de la tabla `iv_camaras`
--
ALTER TABLE `iv_camaras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_iv_cam_empresa` (`id_empresa`);

--
-- Indices de la tabla `iv_computadoras`
--
ALTER TABLE `iv_computadoras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_iv_pc_empresa` (`id_empresa`);

--
-- Indices de la tabla `iv_dvrs`
--
ALTER TABLE `iv_dvrs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_iv_dvr_empresa` (`id_empresa`);

--
-- Indices de la tabla `iv_huelleros`
--
ALTER TABLE `iv_huelleros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_iv_huella_empresa` (`id_empresa`);

--
-- Indices de la tabla `iv_red`
--
ALTER TABLE `iv_red`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_iv_red_empresa` (`id_empresa`);

--
-- Indices de la tabla `iv_switches`
--
ALTER TABLE `iv_switches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_iv_sw_empresa` (`id_empresa`);

--
-- Indices de la tabla `iv_transmision`
--
ALTER TABLE `iv_transmision`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_iv_tx_empresa` (`id_empresa`);

--
-- Indices de la tabla `mod_api_hub_uso_mensual`
--
ALTER TABLE `mod_api_hub_uso_mensual`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_api_hub_empresa_mes` (`empresa_id`,`periodo_mes`),
  ADD KEY `idx_api_hub_periodo` (`periodo_mes`),
  ADD KEY `idx_api_hub_empresa` (`empresa_id`);

--
-- Indices de la tabla `mod_caja_auditoria`
--
ALTER TABLE `mod_caja_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emp_tiempo` (`id_empresa`,`creado_en`),
  ADD KEY `idx_mens` (`id_caja_mensual`),
  ADD KEY `idx_dia` (`id_caja_diaria`);

--
-- Indices de la tabla `mod_caja_diaria`
--
ALTER TABLE `mod_caja_diaria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_empresa_fecha` (`id_empresa`,`fecha`),
  ADD UNIQUE KEY `uk_codigo` (`codigo`),
  ADD UNIQUE KEY `uk_diaria_abierta` (`id_empresa`,`abierta_key`),
  ADD KEY `idx_emp_estado` (`id_empresa`,`estado`),
  ADD KEY `fk_cd_mensual` (`id_caja_mensual`),
  ADD KEY `fk_cd_abre` (`abierto_por`),
  ADD KEY `fk_cd_cierra` (`cerrado_por`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_emp_fecha` (`id_empresa`,`fecha`);

--
-- Indices de la tabla `mod_caja_mensual`
--
ALTER TABLE `mod_caja_mensual`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_empresa_periodo` (`id_empresa`,`anio`,`mes`),
  ADD UNIQUE KEY `uk_codigo` (`codigo`),
  ADD UNIQUE KEY `uk_mensual_abierta` (`id_empresa`,`abierta_key`),
  ADD KEY `idx_emp_estado` (`id_empresa`,`estado`),
  ADD KEY `fk_cm_abre` (`abierto_por`),
  ADD KEY `fk_cm_cierra` (`cerrado_por`),
  ADD KEY `idx_periodo` (`periodo`),
  ADD KEY `idx_emp_periodo` (`id_empresa`,`periodo`);

--
-- Indices de la tabla `mod_empresa_servicio`
--
ALTER TABLE `mod_empresa_servicio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mod_emp_srv` (`empresa_id`,`servicio_id`),
  ADD KEY `idx_mod_emp` (`empresa_id`),
  ADD KEY `idx_mod_srv` (`servicio_id`);

--
-- Indices de la tabla `mod_etiquetas`
--
ALTER TABLE `mod_etiquetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mod_etiquetas_nombre` (`nombre`);

--
-- Indices de la tabla `mod_precios`
--
ALTER TABLE `mod_precios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mod_precios_emp_srv_rol` (`empresa_id`,`servicio_id`,`rol`),
  ADD KEY `idx_mod_precios_emp_srv` (`empresa_id`,`servicio_id`),
  ADD KEY `fk_mod_precios__servicio` (`servicio_id`);

--
-- Indices de la tabla `mod_servicios`
--
ALTER TABLE `mod_servicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mod_servicios_nombre` (`nombre`),
  ADD KEY `idx_mod_servicios_activo` (`activo`);

--
-- Indices de la tabla `mod_servicio_etiqueta`
--
ALTER TABLE `mod_servicio_etiqueta`
  ADD PRIMARY KEY (`servicio_id`,`etiqueta_id`),
  ADD KEY `fk_mse_etiqueta` (`etiqueta_id`);

--
-- Indices de la tabla `mtp_alumnos`
--
ALTER TABLE `mtp_alumnos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_alumno_doc` (`documento`);

--
-- Indices de la tabla `mtp_archivos`
--
ALTER TABLE `mtp_archivos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mtp_archivos_ruta` (`ruta_relativa`),
  ADD KEY `idx_mtp_archivos_categoria_creado` (`categoria`,`creado`),
  ADD KEY `idx_mtp_archivos_estado` (`estado`),
  ADD KEY `idx_mtp_archivos_entidad` (`triada`,`entidad`,`entidad_id`);

--
-- Indices de la tabla `mtp_control_interfaces_usuario`
--
ALTER TABLE `mtp_control_interfaces_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_slug` (`id_usuario`,`interface_slug`),
  ADD KEY `idx_usuario_estado` (`id_usuario`,`estado`),
  ADD KEY `idx_slug_estado` (`interface_slug`,`estado`),
  ADD KEY `fk_ci_actualizado_por` (`actualizado_por`);

--
-- Indices de la tabla `mtp_departamentos`
--
ALTER TABLE `mtp_departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_depa_nombre` (`nombre`);

--
-- Indices de la tabla `mtp_detalle_usuario`
--
ALTER TABLE `mtp_detalle_usuario`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Indices de la tabla `mtp_empresas`
--
ALTER TABLE `mtp_empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_empresas_ruc` (`ruc`,`nombre`),
  ADD KEY `idx_empresas_tipo` (`id_tipo`),
  ADD KEY `idx_empresas_depa` (`id_depa`),
  ADD KEY `idx_empresas_repleg` (`id_repleg`);

--
-- Indices de la tabla `mtp_representante_legal`
--
ALTER TABLE `mtp_representante_legal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_repleg_documento` (`documento`);

--
-- Indices de la tabla `mtp_roles`
--
ALTER TABLE `mtp_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_roles_nombre` (`nombre`);

--
-- Indices de la tabla `mtp_tipos_empresas`
--
ALTER TABLE `mtp_tipos_empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tipos_nombre` (`nombre`);

--
-- Indices de la tabla `mtp_usuarios`
--
ALTER TABLE `mtp_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuarios_usuario` (`usuario`),
  ADD KEY `idx_usuarios_empresa` (`id_empresa`);

--
-- Indices de la tabla `mtp_usuario_roles`
--
ALTER TABLE `mtp_usuario_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_rol` (`id_usuario`,`id_rol`),
  ADD KEY `idx_ur_usuario` (`id_usuario`),
  ADD KEY `idx_ur_rol` (`id_rol`);

--
-- Indices de la tabla `pb_etiquetas`
--
ALTER TABLE `pb_etiquetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pb_tag` (`nombre`);

--
-- Indices de la tabla `pb_grupos`
--
ALTER TABLE `pb_grupos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pb_grp_activo` (`activo`);

--
-- Indices de la tabla `pb_grupo_item`
--
ALTER TABLE `pb_grupo_item`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pb_grupo_pub` (`grupo_id`,`publicidad_id`),
  ADD KEY `idx_pb_gi_grupo` (`grupo_id`),
  ADD KEY `idx_pb_gi_orden` (`orden`),
  ADD KEY `fk_pb_gi_pub` (`publicidad_id`);

--
-- Indices de la tabla `pb_grupo_target`
--
ALTER TABLE `pb_grupo_target`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pb_gt_grp` (`grupo_id`),
  ADD KEY `idx_pb_gt_tipo` (`tipo`),
  ADD KEY `idx_pb_gt_user` (`usuario_id`),
  ADD KEY `idx_pb_gt_rol` (`rol_id`),
  ADD KEY `idx_pb_gt_emp` (`empresa_id`);

--
-- Indices de la tabla `pb_publicidades`
--
ALTER TABLE `pb_publicidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pb_activo` (`activo`);

--
-- Indices de la tabla `pb_publicidad_etiqueta`
--
ALTER TABLE `pb_publicidad_etiqueta`
  ADD PRIMARY KEY (`publicidad_id`,`etiqueta_id`),
  ADD KEY `idx_pb_pe_tag` (`etiqueta_id`);

--
-- Indices de la tabla `pb_publicidad_target`
--
ALTER TABLE `pb_publicidad_target`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pb_tgt_pub` (`publicidad_id`),
  ADD KEY `idx_pb_tgt_tipo` (`tipo`),
  ADD KEY `idx_pb_tgt_user` (`usuario_id`),
  ADD KEY `idx_pb_tgt_rol` (`rol_id`),
  ADD KEY `idx_pb_tgt_emp` (`empresa_id`);

--
-- Indices de la tabla `pos_abonos`
--
ALTER TABLE `pos_abonos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pos_ab_emp_fecha` (`id_empresa`,`fecha`),
  ADD KEY `idx_pos_ab_cliente` (`cliente_id`),
  ADD KEY `idx_pos_ab_medio` (`medio_id`),
  ADD KEY `idx_pos_ab_caja` (`caja_diaria_id`),
  ADD KEY `fk_pos_ab_user` (`creado_por`);

--
-- Indices de la tabla `pos_abono_aplicaciones`
--
ALTER TABLE `pos_abono_aplicaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pos_apl_abono_unico` (`abono_id`),
  ADD KEY `idx_pos_apl_abono` (`abono_id`),
  ADD KEY `idx_pos_apl_venta` (`venta_id`);

--
-- Indices de la tabla `pos_auditoria`
--
ALTER TABLE `pos_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pos_aud_emp_time` (`id_empresa`,`creado_en`),
  ADD KEY `idx_pos_aud_tabla_reg` (`tabla`,`registro_id`),
  ADD KEY `idx_pos_aud_evento` (`evento`),
  ADD KEY `fk_pos_aud_user` (`actor_id`);

--
-- Indices de la tabla `pos_clientes`
--
ALTER TABLE `pos_clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pos_cli_doc` (`id_empresa`,`doc_tipo`,`doc_numero`),
  ADD KEY `idx_pos_cli_nombre` (`id_empresa`,`nombre`);

--
-- Indices de la tabla `pos_comprobantes`
--
ALTER TABLE `pos_comprobantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pc_emp_tipo_venta` (`id_empresa`,`tipo`,`venta_id`),
  ADD KEY `idx_pc_emp_ticket` (`id_empresa`,`ticket_serie`,`ticket_numero`),
  ADD KEY `idx_pc_emitido_en` (`emitido_en`),
  ADD KEY `idx_pc_emitido_por` (`emitido_por`),
  ADD KEY `fk_pc_venta` (`venta_id`);

--
-- Indices de la tabla `pos_comprobante_abonos`
--
ALTER TABLE `pos_comprobante_abonos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pca_comp_abono` (`comprobante_id`,`abono_id`),
  ADD KEY `idx_pca_abono` (`abono_id`),
  ADD KEY `idx_pca_venta` (`venta_id`);

--
-- Indices de la tabla `pos_conductores`
--
ALTER TABLE `pos_conductores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pos_cond_doc` (`id_empresa`,`doc_tipo`,`doc_numero`),
  ADD KEY `idx_pos_cond_apenom` (`id_empresa`,`apellidos`,`nombres`);

--
-- Indices de la tabla `pos_devoluciones`
--
ALTER TABLE `pos_devoluciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pos_dev_emp_fecha` (`id_empresa`,`devuelto_en`),
  ADD KEY `idx_pos_dev_venta` (`venta_id`),
  ADD KEY `idx_pos_dev_apl` (`abono_aplicacion_id`),
  ADD KEY `idx_pos_dev_caja` (`caja_diaria_id`),
  ADD KEY `fk_pos_dev_medio` (`medio_id`),
  ADD KEY `fk_pos_dev_user` (`devuelto_por`);

--
-- Indices de la tabla `pos_medios_pago`
--
ALTER TABLE `pos_medios_pago`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pos_mp_nombre` (`nombre`);

--
-- Indices de la tabla `pos_perfil_conductor`
--
ALTER TABLE `pos_perfil_conductor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pos_pc_doc` (`id_empresa`,`doc_tipo`,`doc_numero`),
  ADD KEY `idx_pos_pc_cat_auto` (`categoria_auto_id`),
  ADD KEY `idx_pos_pc_cat_moto` (`categoria_moto_id`);

--
-- Indices de la tabla `pos_series`
--
ALTER TABLE `pos_series`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pos_series` (`id_empresa`,`tipo_comprobante`,`serie`),
  ADD KEY `idx_pos_series_emp_activo` (`id_empresa`,`activo`);

--
-- Indices de la tabla `pos_ventas`
--
ALTER TABLE `pos_ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pos_ticket` (`id_empresa`,`tipo_comprobante`,`serie`,`numero`),
  ADD KEY `idx_pos_v_emp_fecha` (`id_empresa`,`fecha_emision`),
  ADD KEY `idx_pos_v_emp_estado` (`id_empresa`,`estado`),
  ADD KEY `idx_pos_v_emp_saldo` (`id_empresa`,`saldo`),
  ADD KEY `idx_pos_v_cli` (`cliente_id`),
  ADD KEY `idx_pos_v_caja` (`caja_diaria_id`),
  ADD KEY `fk_pos_v_serie` (`serie_id`),
  ADD KEY `fk_pos_v_user` (`creado_por`),
  ADD KEY `idx_contratante_doc` (`contratante_doc_tipo`,`contratante_doc_numero`),
  ADD KEY `idx_pos_ventas_tmp_fecha` (`tiene_precio_temporal`,`fecha_emision`),
  ADD KEY `idx_pos_v_cli_snap_doc` (`cliente_snapshot_doc_tipo`,`cliente_snapshot_doc_numero`);

--
-- Indices de la tabla `pos_ventas_anulaciones`
--
ALTER TABLE `pos_ventas_anulaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pos_anulacion_venta` (`venta_id`),
  ADD KEY `idx_pos_anula_fecha` (`anulado_en`),
  ADD KEY `fk_pos_anula_user` (`anulado_por`);

--
-- Indices de la tabla `pos_venta_conductores`
--
ALTER TABLE `pos_venta_conductores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pos_vc_reg` (`venta_id`,`conductor_id`),
  ADD KEY `idx_pos_vc_venta` (`venta_id`),
  ADD KEY `idx_pos_vc_cond` (`conductor_id`),
  ADD KEY `idx_pos_vc_estado` (`estado`),
  ADD KEY `idx_pos_vc_doc_snap` (`conductor_doc_tipo`,`conductor_doc_numero`),
  ADD KEY `idx_pos_vc_mismo_cli` (`conductor_es_mismo_cliente`),
  ADD KEY `idx_pos_vc_cat_auto_snap` (`conductor_categoria_auto_id`),
  ADD KEY `idx_pos_vc_cat_moto_snap` (`conductor_categoria_moto_id`);

--
-- Indices de la tabla `pos_venta_detalles`
--
ALTER TABLE `pos_venta_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pos_vd_venta` (`venta_id`),
  ADD KEY `idx_pos_vd_serv` (`servicio_id`),
  ADD KEY `idx_pos_vd_origen_actor` (`precio_origen`,`precio_temporal_actor_id`);

--
-- Indices de la tabla `pos_venta_detalle_conductores`
--
ALTER TABLE `pos_venta_detalle_conductores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pos_vdc_reg` (`venta_detalle_id`,`conductor_id`),
  ADD KEY `idx_pos_vdc_det` (`venta_detalle_id`),
  ADD KEY `idx_pos_vdc_cond` (`conductor_id`);

--
-- Indices de la tabla `vn_logs_precios`
--
ALTER TABLE `vn_logs_precios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_user` (`usuario_id`),
  ADD KEY `idx_logs_emp` (`empresa_id`),
  ADD KEY `idx_logs_svc` (`servicio_id`),
  ADD KEY `idx_logs_acc` (`accion`);

--
-- Indices de la tabla `vn_log_precios`
--
ALTER TABLE `vn_log_precios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vn_log_precio` (`id_precio`),
  ADD KEY `idx_vn_log_serv_emp` (`id_servicio`,`id_empresa`),
  ADD KEY `fk_vn_log_emp` (`id_empresa`),
  ADD KEY `fk_vn_log_user` (`hecho_por`);

--
-- Indices de la tabla `vn_precios`
--
ALTER TABLE `vn_precios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_precio` (`id_servicio`,`id_empresa`,`etiqueta`),
  ADD KEY `idx_precio_se` (`id_servicio`,`id_empresa`),
  ADD KEY `fk_precio_emp` (`id_empresa`);

--
-- Indices de la tabla `vn_precios_servicio`
--
ALTER TABLE `vn_precios_servicio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_vn_precio_etiqueta` (`id_servicio`,`id_empresa`,`etiqueta`),
  ADD KEY `idx_vn_precio_serv_emp` (`id_servicio`,`id_empresa`),
  ADD KEY `fk_vn_precio_emp` (`id_empresa`),
  ADD KEY `fk_vn_precio_cuser` (`creado_por`),
  ADD KEY `fk_vn_precio_uuser` (`actualizado_por`);

--
-- Indices de la tabla `vn_precio_actual`
--
ALTER TABLE `vn_precio_actual`
  ADD PRIMARY KEY (`id_servicio`,`id_empresa`),
  ADD KEY `idx_vn_precio_actual_precio` (`id_precio`),
  ADD KEY `fk_vn_pa_emp` (`id_empresa`),
  ADD KEY `fk_vn_pa_user` (`actualizado_por`);

--
-- Indices de la tabla `vn_servicios`
--
ALTER TABLE `vn_servicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_vn_servicio_nombre` (`nombre`),
  ADD KEY `idx_vn_serv_activo` (`activo`),
  ADD KEY `fk_vn_serv_creado_user` (`creado_por`),
  ADD KEY `fk_vn_serv_actualizado_user` (`actualizado_por`);

--
-- Indices de la tabla `vn_servicios_empresas`
--
ALTER TABLE `vn_servicios_empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_vn_serv_emp` (`id_servicio`,`id_empresa`),
  ADD KEY `idx_vn_emp_serv` (`id_empresa`),
  ADD KEY `fk_vn_se_cuser` (`creado_por`),
  ADD KEY `fk_vn_se_uuser` (`actualizado_por`);

--
-- Indices de la tabla `web_banner`
--
ALTER TABLE `web_banner`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_caracteristicas`
--
ALTER TABLE `web_caracteristicas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_carrusel_empresas_config`
--
ALTER TABLE `web_carrusel_empresas_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_carrusel_empresas_items`
--
ALTER TABLE `web_carrusel_empresas_items`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_carrusel_servicios_config`
--
ALTER TABLE `web_carrusel_servicios_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_carrusel_servicios_items`
--
ALTER TABLE `web_carrusel_servicios_items`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_contadores`
--
ALTER TABLE `web_contadores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_formulario_carrusel_items`
--
ALTER TABLE `web_formulario_carrusel_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_web_formulario_carrusel_items_orden` (`orden`);

--
-- Indices de la tabla `web_formulario_carrusel_mensajes`
--
ALTER TABLE `web_formulario_carrusel_mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_web_formulario_carrusel_mensajes_estado` (`estado`),
  ADD KEY `idx_web_formulario_carrusel_mensajes_fecha` (`fecha_registro`);

--
-- Indices de la tabla `web_menu`
--
ALTER TABLE `web_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_nosotros`
--
ALTER TABLE `web_nosotros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_novedades_config`
--
ALTER TABLE `web_novedades_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_novedades_items`
--
ALTER TABLE `web_novedades_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_web_novedades_items_orden` (`orden`),
  ADD KEY `idx_web_novedades_items_visible` (`visible`);

--
-- Indices de la tabla `web_proceso`
--
ALTER TABLE `web_proceso`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_servicios`
--
ALTER TABLE `web_servicios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_testimonios_config`
--
ALTER TABLE `web_testimonios_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `web_testimonios_items`
--
ALTER TABLE `web_testimonios_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_web_testimonios_items_orden` (`orden`);

--
-- Indices de la tabla `web_topbar_config`
--
ALTER TABLE `web_topbar_config`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `al_alertas`
--
ALTER TABLE `al_alertas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `al_alertas_log`
--
ALTER TABLE `al_alertas_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `cam_camaras`
--
ALTER TABLE `cam_camaras`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `cam_camaras_usuarios`
--
ALTER TABLE `cam_camaras_usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `cam_hdd`
--
ALTER TABLE `cam_hdd`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `cam_hdd_consumo`
--
ALTER TABLE `cam_hdd_consumo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=282;

--
-- AUTO_INCREMENT de la tabla `ce_componentes`
--
ALTER TABLE `ce_componentes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ce_componente_opciones`
--
ALTER TABLE `ce_componente_opciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ce_plantillas`
--
ALTER TABLE `ce_plantillas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `com_comunicados`
--
ALTER TABLE `com_comunicados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `com_comunicado_target`
--
ALTER TABLE `com_comunicado_target`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `com_comunicado_vista`
--
ALTER TABLE `com_comunicado_vista`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cq_categorias_licencia`
--
ALTER TABLE `cq_categorias_licencia`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `cq_certificados`
--
ALTER TABLE `cq_certificados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de la tabla `cq_plantillas_certificados`
--
ALTER TABLE `cq_plantillas_certificados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cq_plantillas_elementos`
--
ALTER TABLE `cq_plantillas_elementos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT de la tabla `cq_plantillas_posiciones`
--
ALTER TABLE `cq_plantillas_posiciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=640;

--
-- AUTO_INCREMENT de la tabla `cq_tipos_documento`
--
ALTER TABLE `cq_tipos_documento`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cr_cursos`
--
ALTER TABLE `cr_cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `cr_etiquetas`
--
ALTER TABLE `cr_etiquetas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cr_formularios`
--
ALTER TABLE `cr_formularios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cr_formulario_intentos`
--
ALTER TABLE `cr_formulario_intentos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `cr_formulario_opciones`
--
ALTER TABLE `cr_formulario_opciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `cr_formulario_preguntas`
--
ALTER TABLE `cr_formulario_preguntas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cr_formulario_respuestas`
--
ALTER TABLE `cr_formulario_respuestas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT de la tabla `cr_grupos`
--
ALTER TABLE `cr_grupos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cr_matriculas_grupo`
--
ALTER TABLE `cr_matriculas_grupo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cr_temas`
--
ALTER TABLE `cr_temas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cr_usuario_curso`
--
ALTER TABLE `cr_usuario_curso`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `egr_egresos`
--
ALTER TABLE `egr_egresos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `egr_egreso_fuentes`
--
ALTER TABLE `egr_egreso_fuentes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `ferreteria_productos`
--
ALTER TABLE `ferreteria_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `inv_bienes`
--
ALTER TABLE `inv_bienes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT de la tabla `inv_categorias`
--
ALTER TABLE `inv_categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `inv_movimientos`
--
ALTER TABLE `inv_movimientos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT de la tabla `inv_ubicaciones`
--
ALTER TABLE `inv_ubicaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `iv_camaras`
--
ALTER TABLE `iv_camaras`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `iv_computadoras`
--
ALTER TABLE `iv_computadoras`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `iv_dvrs`
--
ALTER TABLE `iv_dvrs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `iv_huelleros`
--
ALTER TABLE `iv_huelleros`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `iv_red`
--
ALTER TABLE `iv_red`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `iv_switches`
--
ALTER TABLE `iv_switches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `iv_transmision`
--
ALTER TABLE `iv_transmision`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `mod_api_hub_uso_mensual`
--
ALTER TABLE `mod_api_hub_uso_mensual`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de la tabla `mod_caja_auditoria`
--
ALTER TABLE `mod_caja_auditoria`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `mod_caja_diaria`
--
ALTER TABLE `mod_caja_diaria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `mod_caja_mensual`
--
ALTER TABLE `mod_caja_mensual`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `mod_empresa_servicio`
--
ALTER TABLE `mod_empresa_servicio`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `mod_etiquetas`
--
ALTER TABLE `mod_etiquetas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `mod_precios`
--
ALTER TABLE `mod_precios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1291;

--
-- AUTO_INCREMENT de la tabla `mod_servicios`
--
ALTER TABLE `mod_servicios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `mtp_alumnos`
--
ALTER TABLE `mtp_alumnos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mtp_archivos`
--
ALTER TABLE `mtp_archivos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de la tabla `mtp_control_interfaces_usuario`
--
ALTER TABLE `mtp_control_interfaces_usuario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `mtp_departamentos`
--
ALTER TABLE `mtp_departamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `mtp_empresas`
--
ALTER TABLE `mtp_empresas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `mtp_representante_legal`
--
ALTER TABLE `mtp_representante_legal`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `mtp_roles`
--
ALTER TABLE `mtp_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `mtp_tipos_empresas`
--
ALTER TABLE `mtp_tipos_empresas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `mtp_usuarios`
--
ALTER TABLE `mtp_usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `mtp_usuario_roles`
--
ALTER TABLE `mtp_usuario_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `pb_etiquetas`
--
ALTER TABLE `pb_etiquetas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pb_grupos`
--
ALTER TABLE `pb_grupos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pb_grupo_item`
--
ALTER TABLE `pb_grupo_item`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pb_grupo_target`
--
ALTER TABLE `pb_grupo_target`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pb_publicidades`
--
ALTER TABLE `pb_publicidades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pb_publicidad_target`
--
ALTER TABLE `pb_publicidad_target`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pos_abonos`
--
ALTER TABLE `pos_abonos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT de la tabla `pos_abono_aplicaciones`
--
ALTER TABLE `pos_abono_aplicaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT de la tabla `pos_auditoria`
--
ALTER TABLE `pos_auditoria`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT de la tabla `pos_clientes`
--
ALTER TABLE `pos_clientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT de la tabla `pos_comprobantes`
--
ALTER TABLE `pos_comprobantes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT de la tabla `pos_comprobante_abonos`
--
ALTER TABLE `pos_comprobante_abonos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `pos_conductores`
--
ALTER TABLE `pos_conductores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `pos_devoluciones`
--
ALTER TABLE `pos_devoluciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `pos_medios_pago`
--
ALTER TABLE `pos_medios_pago`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pos_perfil_conductor`
--
ALTER TABLE `pos_perfil_conductor`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `pos_series`
--
ALTER TABLE `pos_series`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pos_ventas`
--
ALTER TABLE `pos_ventas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de la tabla `pos_ventas_anulaciones`
--
ALTER TABLE `pos_ventas_anulaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `pos_venta_conductores`
--
ALTER TABLE `pos_venta_conductores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT de la tabla `pos_venta_detalles`
--
ALTER TABLE `pos_venta_detalles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT de la tabla `pos_venta_detalle_conductores`
--
ALTER TABLE `pos_venta_detalle_conductores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vn_logs_precios`
--
ALTER TABLE `vn_logs_precios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vn_log_precios`
--
ALTER TABLE `vn_log_precios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vn_precios`
--
ALTER TABLE `vn_precios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vn_precios_servicio`
--
ALTER TABLE `vn_precios_servicio`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vn_servicios`
--
ALTER TABLE `vn_servicios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vn_servicios_empresas`
--
ALTER TABLE `vn_servicios_empresas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `web_carrusel_empresas_items`
--
ALTER TABLE `web_carrusel_empresas_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `web_carrusel_servicios_items`
--
ALTER TABLE `web_carrusel_servicios_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `web_formulario_carrusel_items`
--
ALTER TABLE `web_formulario_carrusel_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `web_formulario_carrusel_mensajes`
--
ALTER TABLE `web_formulario_carrusel_mensajes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `web_novedades_items`
--
ALTER TABLE `web_novedades_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `web_testimonios_items`
--
ALTER TABLE `web_testimonios_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `al_alertas`
--
ALTER TABLE `al_alertas`
  ADD CONSTRAINT `fk_al_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `al_alertas_log`
--
ALTER TABLE `al_alertas_log`
  ADD CONSTRAINT `fk_al_log_alerta` FOREIGN KEY (`id_alerta`) REFERENCES `al_alertas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cam_camaras`
--
ALTER TABLE `cam_camaras`
  ADD CONSTRAINT `fk_cam_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cam_camaras_usuarios`
--
ALTER TABLE `cam_camaras_usuarios`
  ADD CONSTRAINT `fk_ccu_camara` FOREIGN KEY (`id_camara`) REFERENCES `cam_camaras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cam_hdd`
--
ALTER TABLE `cam_hdd`
  ADD CONSTRAINT `fk_cam_hdd_camara` FOREIGN KEY (`id_camara`) REFERENCES `cam_camaras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cam_hdd_consumo`
--
ALTER TABLE `cam_hdd_consumo`
  ADD CONSTRAINT `fk_cam_hdd_consumo_hdd` FOREIGN KEY (`id_hdd`) REFERENCES `cam_hdd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ce_componentes`
--
ALTER TABLE `ce_componentes`
  ADD CONSTRAINT `fk_ce_comp_pl` FOREIGN KEY (`plantilla_id`) REFERENCES `ce_plantillas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ce_componente_opciones`
--
ALTER TABLE `ce_componente_opciones`
  ADD CONSTRAINT `fk_ce_opt_comp` FOREIGN KEY (`componente_id`) REFERENCES `ce_componentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ce_plantillas`
--
ALTER TABLE `ce_plantillas`
  ADD CONSTRAINT `fk_ce_pl_user` FOREIGN KEY (`creado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `com_comunicado_target`
--
ALTER TABLE `com_comunicado_target`
  ADD CONSTRAINT `fk_tgt_com` FOREIGN KEY (`comunicado_id`) REFERENCES `com_comunicados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tgt_emp` FOREIGN KEY (`empresa_id`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tgt_rol` FOREIGN KEY (`rol_id`) REFERENCES `mtp_roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tgt_user` FOREIGN KEY (`usuario_id`) REFERENCES `mtp_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `com_comunicado_vista`
--
ALTER TABLE `com_comunicado_vista`
  ADD CONSTRAINT `fk_vista_com` FOREIGN KEY (`comunicado_id`) REFERENCES `com_comunicados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vista_user` FOREIGN KEY (`usuario_id`) REFERENCES `mtp_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cq_certificados`
--
ALTER TABLE `cq_certificados`
  ADD CONSTRAINT `fk_cq_cert_categoria` FOREIGN KEY (`id_categoria_licencia`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cq_cert_curso` FOREIGN KEY (`id_curso`) REFERENCES `cr_cursos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cq_cert_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cq_cert_plantilla` FOREIGN KEY (`id_plantilla_certificado`) REFERENCES `cq_plantillas_certificados` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cq_cert_tipo_doc` FOREIGN KEY (`id_tipo_doc`) REFERENCES `cq_tipos_documento` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cq_cert_usuario` FOREIGN KEY (`id_usuario_emisor`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cq_plantillas_certificados`
--
ALTER TABLE `cq_plantillas_certificados`
  ADD CONSTRAINT `fk_pc_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cq_plantillas_elementos`
--
ALTER TABLE `cq_plantillas_elementos`
  ADD CONSTRAINT `fk_pe_plantilla` FOREIGN KEY (`id_plantilla_certificado`) REFERENCES `cq_plantillas_certificados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cq_plantillas_posiciones`
--
ALTER TABLE `cq_plantillas_posiciones`
  ADD CONSTRAINT `fk_pp_plantilla` FOREIGN KEY (`id_plantilla_certificado`) REFERENCES `cq_plantillas_certificados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_curso_etiqueta`
--
ALTER TABLE `cr_curso_etiqueta`
  ADD CONSTRAINT `fk_cr_ce_curso` FOREIGN KEY (`curso_id`) REFERENCES `cr_cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_ce_etiqueta` FOREIGN KEY (`etiqueta_id`) REFERENCES `cr_etiquetas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_formularios`
--
ALTER TABLE `cr_formularios`
  ADD CONSTRAINT `fk_cr_form_curso` FOREIGN KEY (`curso_id`) REFERENCES `cr_cursos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_form_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_form_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `cr_grupos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_form_tema` FOREIGN KEY (`tema_id`) REFERENCES `cr_temas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_formulario_intentos`
--
ALTER TABLE `cr_formulario_intentos`
  ADD CONSTRAINT `fk_cr_fi_formulario` FOREIGN KEY (`formulario_id`) REFERENCES `cr_formularios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_fi_tipo_doc` FOREIGN KEY (`tipo_doc_id`) REFERENCES `cq_tipos_documento` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_fi_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_formulario_intento_categoria`
--
ALTER TABLE `cr_formulario_intento_categoria`
  ADD CONSTRAINT `fk_cr_fic_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_fic_intento` FOREIGN KEY (`intento_id`) REFERENCES `cr_formulario_intentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_formulario_opciones`
--
ALTER TABLE `cr_formulario_opciones`
  ADD CONSTRAINT `fk_cr_fo_pregunta` FOREIGN KEY (`pregunta_id`) REFERENCES `cr_formulario_preguntas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_formulario_preguntas`
--
ALTER TABLE `cr_formulario_preguntas`
  ADD CONSTRAINT `fk_cr_fp_formulario` FOREIGN KEY (`formulario_id`) REFERENCES `cr_formularios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_formulario_respuestas`
--
ALTER TABLE `cr_formulario_respuestas`
  ADD CONSTRAINT `fk_cr_fr_intento` FOREIGN KEY (`intento_id`) REFERENCES `cr_formulario_intentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_fr_pregunta` FOREIGN KEY (`pregunta_id`) REFERENCES `cr_formulario_preguntas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_grupos`
--
ALTER TABLE `cr_grupos`
  ADD CONSTRAINT `fk_cr_grupos_curso` FOREIGN KEY (`curso_id`) REFERENCES `cr_cursos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_grupos_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_matriculas_grupo`
--
ALTER TABLE `cr_matriculas_grupo`
  ADD CONSTRAINT `fk_cr_mg_curso` FOREIGN KEY (`curso_id`) REFERENCES `cr_cursos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_mg_expulsado_by` FOREIGN KEY (`expulsado_by`) REFERENCES `mtp_usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_mg_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `cr_grupos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cr_mg_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_temas`
--
ALTER TABLE `cr_temas`
  ADD CONSTRAINT `fk_cr_tema_curso` FOREIGN KEY (`curso_id`) REFERENCES `cr_cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cr_usuario_curso`
--
ALTER TABLE `cr_usuario_curso`
  ADD CONSTRAINT `fk_ucr_asignado_por` FOREIGN KEY (`asignado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ucr_curso` FOREIGN KEY (`curso_id`) REFERENCES `cr_cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ucr_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `mtp_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `egr_correlativos`
--
ALTER TABLE `egr_correlativos`
  ADD CONSTRAINT `fk_egr_correlativo_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `egr_egresos`
--
ALTER TABLE `egr_egresos`
  ADD CONSTRAINT `fk_egr_anulado_por` FOREIGN KEY (`anulado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_egr_caja_diaria` FOREIGN KEY (`id_caja_diaria`) REFERENCES `mod_caja_diaria` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_egr_caja_mensual` FOREIGN KEY (`id_caja_mensual`) REFERENCES `mod_caja_mensual` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_egr_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_egr_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `egr_egreso_fuentes`
--
ALTER TABLE `egr_egreso_fuentes`
  ADD CONSTRAINT `fk_egr_fuente_caja_diaria` FOREIGN KEY (`id_caja_diaria`) REFERENCES `mod_caja_diaria` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_egr_fuente_egreso` FOREIGN KEY (`id_egreso`) REFERENCES `egr_egresos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_egr_fuente_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_egr_fuente_medio` FOREIGN KEY (`medio_id`) REFERENCES `pos_medios_pago` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `inv_bienes`
--
ALTER TABLE `inv_bienes`
  ADD CONSTRAINT `fk_inv_bienes_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_bienes_resp` FOREIGN KEY (`id_responsable`) REFERENCES `mtp_usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_bienes_ubic` FOREIGN KEY (`id_ubicacion`) REFERENCES `inv_ubicaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `inv_bien_categoria`
--
ALTER TABLE `inv_bien_categoria`
  ADD CONSTRAINT `fk_inv_bc_bien` FOREIGN KEY (`id_bien`) REFERENCES `inv_bienes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_bc_cat` FOREIGN KEY (`id_categoria`) REFERENCES `inv_categorias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `inv_categorias`
--
ALTER TABLE `inv_categorias`
  ADD CONSTRAINT `fk_inv_cat_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `inv_movimientos`
--
ALTER TABLE `inv_movimientos`
  ADD CONSTRAINT `fk_inv_mov_bien` FOREIGN KEY (`id_bien`) REFERENCES `inv_bienes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_mov_desde_resp` FOREIGN KEY (`desde_responsable`) REFERENCES `mtp_usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_mov_desde_ubic` FOREIGN KEY (`desde_ubicacion`) REFERENCES `inv_ubicaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_mov_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_mov_hacia_resp` FOREIGN KEY (`hacia_responsable`) REFERENCES `mtp_usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_mov_hacia_ubic` FOREIGN KEY (`hacia_ubicacion`) REFERENCES `inv_ubicaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_mov_usr` FOREIGN KEY (`id_usuario`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `inv_ubicaciones`
--
ALTER TABLE `inv_ubicaciones`
  ADD CONSTRAINT `fk_inv_ubic_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `iv_camaras`
--
ALTER TABLE `iv_camaras`
  ADD CONSTRAINT `fk_iv_cam_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `iv_computadoras`
--
ALTER TABLE `iv_computadoras`
  ADD CONSTRAINT `fk_iv_pc_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `iv_dvrs`
--
ALTER TABLE `iv_dvrs`
  ADD CONSTRAINT `fk_iv_dvr_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `iv_huelleros`
--
ALTER TABLE `iv_huelleros`
  ADD CONSTRAINT `fk_iv_huella_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `iv_red`
--
ALTER TABLE `iv_red`
  ADD CONSTRAINT `fk_iv_red_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `iv_switches`
--
ALTER TABLE `iv_switches`
  ADD CONSTRAINT `fk_iv_sw_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `iv_transmision`
--
ALTER TABLE `iv_transmision`
  ADD CONSTRAINT `fk_iv_tx_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mod_caja_auditoria`
--
ALTER TABLE `mod_caja_auditoria`
  ADD CONSTRAINT `fk_a_cd` FOREIGN KEY (`id_caja_diaria`) REFERENCES `mod_caja_diaria` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_a_cm` FOREIGN KEY (`id_caja_mensual`) REFERENCES `mod_caja_mensual` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_a_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mod_caja_diaria`
--
ALTER TABLE `mod_caja_diaria`
  ADD CONSTRAINT `fk_cd_abre` FOREIGN KEY (`abierto_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cd_cierra` FOREIGN KEY (`cerrado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cd_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cd_mensual` FOREIGN KEY (`id_caja_mensual`) REFERENCES `mod_caja_mensual` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mod_caja_mensual`
--
ALTER TABLE `mod_caja_mensual`
  ADD CONSTRAINT `fk_cm_abre` FOREIGN KEY (`abierto_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cm_cierra` FOREIGN KEY (`cerrado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cm_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mod_empresa_servicio`
--
ALTER TABLE `mod_empresa_servicio`
  ADD CONSTRAINT `fk_mes_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mes_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `mod_servicios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mod_precios`
--
ALTER TABLE `mod_precios`
  ADD CONSTRAINT `fk_mod_precios__empresa` FOREIGN KEY (`empresa_id`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mod_precios__servicio` FOREIGN KEY (`servicio_id`) REFERENCES `mod_servicios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mod_servicio_etiqueta`
--
ALTER TABLE `mod_servicio_etiqueta`
  ADD CONSTRAINT `fk_mse_etiqueta` FOREIGN KEY (`etiqueta_id`) REFERENCES `mod_etiquetas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mse_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `mod_servicios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mtp_control_interfaces_usuario`
--
ALTER TABLE `mtp_control_interfaces_usuario`
  ADD CONSTRAINT `fk_ci_actualizado_por` FOREIGN KEY (`actualizado_por`) REFERENCES `mtp_usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ci_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `mtp_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mtp_detalle_usuario`
--
ALTER TABLE `mtp_detalle_usuario`
  ADD CONSTRAINT `fk_detalle_usuario_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `mtp_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mtp_empresas`
--
ALTER TABLE `mtp_empresas`
  ADD CONSTRAINT `fk_empresas_depa` FOREIGN KEY (`id_depa`) REFERENCES `mtp_departamentos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_empresas_repleg` FOREIGN KEY (`id_repleg`) REFERENCES `mtp_representante_legal` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_empresas_tipos` FOREIGN KEY (`id_tipo`) REFERENCES `mtp_tipos_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mtp_usuarios`
--
ALTER TABLE `mtp_usuarios`
  ADD CONSTRAINT `fk_usuarios_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mtp_usuario_roles`
--
ALTER TABLE `mtp_usuario_roles`
  ADD CONSTRAINT `fk_ur_rol` FOREIGN KEY (`id_rol`) REFERENCES `mtp_roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ur_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `mtp_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pb_grupo_item`
--
ALTER TABLE `pb_grupo_item`
  ADD CONSTRAINT `fk_pb_gi_grp` FOREIGN KEY (`grupo_id`) REFERENCES `pb_grupos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_gi_pub` FOREIGN KEY (`publicidad_id`) REFERENCES `pb_publicidades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pb_grupo_target`
--
ALTER TABLE `pb_grupo_target`
  ADD CONSTRAINT `fk_pb_gt_emp` FOREIGN KEY (`empresa_id`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_gt_grp` FOREIGN KEY (`grupo_id`) REFERENCES `pb_grupos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_gt_rol` FOREIGN KEY (`rol_id`) REFERENCES `mtp_roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_gt_user` FOREIGN KEY (`usuario_id`) REFERENCES `mtp_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pb_publicidad_etiqueta`
--
ALTER TABLE `pb_publicidad_etiqueta`
  ADD CONSTRAINT `fk_pb_pe_pub` FOREIGN KEY (`publicidad_id`) REFERENCES `pb_publicidades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_pe_tag` FOREIGN KEY (`etiqueta_id`) REFERENCES `pb_etiquetas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pb_publicidad_target`
--
ALTER TABLE `pb_publicidad_target`
  ADD CONSTRAINT `fk_pb_tgt_emp` FOREIGN KEY (`empresa_id`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_tgt_pub` FOREIGN KEY (`publicidad_id`) REFERENCES `pb_publicidades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_tgt_rol` FOREIGN KEY (`rol_id`) REFERENCES `mtp_roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pb_tgt_user` FOREIGN KEY (`usuario_id`) REFERENCES `mtp_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_abonos`
--
ALTER TABLE `pos_abonos`
  ADD CONSTRAINT `fk_pos_ab_caja` FOREIGN KEY (`caja_diaria_id`) REFERENCES `mod_caja_diaria` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_ab_cli` FOREIGN KEY (`cliente_id`) REFERENCES `pos_clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_ab_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_ab_medio` FOREIGN KEY (`medio_id`) REFERENCES `pos_medios_pago` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_ab_user` FOREIGN KEY (`creado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_abono_aplicaciones`
--
ALTER TABLE `pos_abono_aplicaciones`
  ADD CONSTRAINT `fk_pos_apl_abono` FOREIGN KEY (`abono_id`) REFERENCES `pos_abonos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_apl_venta` FOREIGN KEY (`venta_id`) REFERENCES `pos_ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_auditoria`
--
ALTER TABLE `pos_auditoria`
  ADD CONSTRAINT `fk_pos_aud_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_aud_user` FOREIGN KEY (`actor_id`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_clientes`
--
ALTER TABLE `pos_clientes`
  ADD CONSTRAINT `fk_pos_cli_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_comprobantes`
--
ALTER TABLE `pos_comprobantes`
  ADD CONSTRAINT `fk_pc_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_user` FOREIGN KEY (`emitido_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_venta` FOREIGN KEY (`venta_id`) REFERENCES `pos_ventas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_comprobante_abonos`
--
ALTER TABLE `pos_comprobante_abonos`
  ADD CONSTRAINT `fk_pca_abono` FOREIGN KEY (`abono_id`) REFERENCES `pos_abonos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pca_comp` FOREIGN KEY (`comprobante_id`) REFERENCES `pos_comprobantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pca_venta` FOREIGN KEY (`venta_id`) REFERENCES `pos_ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_conductores`
--
ALTER TABLE `pos_conductores`
  ADD CONSTRAINT `fk_pos_cond_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_devoluciones`
--
ALTER TABLE `pos_devoluciones`
  ADD CONSTRAINT `fk_pos_dev_apl` FOREIGN KEY (`abono_aplicacion_id`) REFERENCES `pos_abono_aplicaciones` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_dev_caja` FOREIGN KEY (`caja_diaria_id`) REFERENCES `mod_caja_diaria` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_dev_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_dev_medio` FOREIGN KEY (`medio_id`) REFERENCES `pos_medios_pago` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_dev_user` FOREIGN KEY (`devuelto_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_dev_venta` FOREIGN KEY (`venta_id`) REFERENCES `pos_ventas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_perfil_conductor`
--
ALTER TABLE `pos_perfil_conductor`
  ADD CONSTRAINT `fk_pos_pc_cat_auto` FOREIGN KEY (`categoria_auto_id`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_pc_cat_moto` FOREIGN KEY (`categoria_moto_id`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_pc_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_series`
--
ALTER TABLE `pos_series`
  ADD CONSTRAINT `fk_pos_series_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_ventas`
--
ALTER TABLE `pos_ventas`
  ADD CONSTRAINT `fk_pos_v_caja` FOREIGN KEY (`caja_diaria_id`) REFERENCES `mod_caja_diaria` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_v_cli` FOREIGN KEY (`cliente_id`) REFERENCES `pos_clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_v_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_v_serie` FOREIGN KEY (`serie_id`) REFERENCES `pos_series` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_v_user` FOREIGN KEY (`creado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_ventas_anulaciones`
--
ALTER TABLE `pos_ventas_anulaciones`
  ADD CONSTRAINT `fk_pos_anula_user` FOREIGN KEY (`anulado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_anula_venta` FOREIGN KEY (`venta_id`) REFERENCES `pos_ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_venta_conductores`
--
ALTER TABLE `pos_venta_conductores`
  ADD CONSTRAINT `fk_pos_vc_cat_auto_snap` FOREIGN KEY (`conductor_categoria_auto_id`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_vc_cat_moto_snap` FOREIGN KEY (`conductor_categoria_moto_id`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_vc_cond` FOREIGN KEY (`conductor_id`) REFERENCES `pos_conductores` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_vc_venta` FOREIGN KEY (`venta_id`) REFERENCES `pos_ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_venta_detalles`
--
ALTER TABLE `pos_venta_detalles`
  ADD CONSTRAINT `fk_pos_vd_serv` FOREIGN KEY (`servicio_id`) REFERENCES `mod_servicios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_vd_venta` FOREIGN KEY (`venta_id`) REFERENCES `pos_ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pos_venta_detalle_conductores`
--
ALTER TABLE `pos_venta_detalle_conductores`
  ADD CONSTRAINT `fk_pos_vdc_cond` FOREIGN KEY (`conductor_id`) REFERENCES `pos_conductores` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_vdc_det` FOREIGN KEY (`venta_detalle_id`) REFERENCES `pos_venta_detalles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `vn_log_precios`
--
ALTER TABLE `vn_log_precios`
  ADD CONSTRAINT `fk_vn_log_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_log_serv` FOREIGN KEY (`id_servicio`) REFERENCES `vn_servicios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_log_user` FOREIGN KEY (`hecho_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `vn_precios`
--
ALTER TABLE `vn_precios`
  ADD CONSTRAINT `fk_precio_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_precio_svc` FOREIGN KEY (`id_servicio`) REFERENCES `vn_servicios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `vn_precios_servicio`
--
ALTER TABLE `vn_precios_servicio`
  ADD CONSTRAINT `fk_vn_precio_cuser` FOREIGN KEY (`creado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_precio_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_precio_serv` FOREIGN KEY (`id_servicio`) REFERENCES `vn_servicios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_precio_uuser` FOREIGN KEY (`actualizado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `vn_precio_actual`
--
ALTER TABLE `vn_precio_actual`
  ADD CONSTRAINT `fk_vn_pa_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_pa_precio` FOREIGN KEY (`id_precio`) REFERENCES `vn_precios_servicio` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_pa_serv` FOREIGN KEY (`id_servicio`) REFERENCES `vn_servicios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_pa_user` FOREIGN KEY (`actualizado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `vn_servicios`
--
ALTER TABLE `vn_servicios`
  ADD CONSTRAINT `fk_vn_serv_actualizado_user` FOREIGN KEY (`actualizado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_serv_creado_user` FOREIGN KEY (`creado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `vn_servicios_empresas`
--
ALTER TABLE `vn_servicios_empresas`
  ADD CONSTRAINT `fk_vn_se_cuser` FOREIGN KEY (`creado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_se_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_se_serv` FOREIGN KEY (`id_servicio`) REFERENCES `vn_servicios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vn_se_uuser` FOREIGN KEY (`actualizado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
