-- Migracion Egresos: primera version real del modulo.
-- Fecha: 2026-03-05
-- Requiere: mod_caja_mensual, mod_caja_diaria, mtp_empresas, mtp_usuarios.

START TRANSACTION;

CREATE TABLE `egr_correlativos` (
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `ultimo_numero` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_empresa`),
  CONSTRAINT `fk_egr_correlativo_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `egr_egresos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `anulado_por` int(10) UNSIGNED DEFAULT NULL,
  `anulado_en` datetime DEFAULT NULL,
  `anulado_motivo` varchar(255) DEFAULT NULL,
  `creado_por` int(10) UNSIGNED NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_egr_codigo` (`codigo`),
  UNIQUE KEY `uk_egr_emp_correlativo` (`id_empresa`,`correlativo`),
  KEY `idx_egr_empresa_fecha` (`id_empresa`,`fecha_emision`),
  KEY `idx_egr_estado` (`estado`),
  KEY `idx_egr_caja_diaria` (`id_caja_diaria`),
  KEY `idx_egr_caja_mensual` (`id_caja_mensual`),
  KEY `idx_egr_creado_por` (`creado_por`),
  KEY `idx_egr_anulado_por` (`anulado_por`),
  CONSTRAINT `fk_egr_anulado_por` FOREIGN KEY (`anulado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_egr_caja_diaria` FOREIGN KEY (`id_caja_diaria`) REFERENCES `mod_caja_diaria` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_egr_caja_mensual` FOREIGN KEY (`id_caja_mensual`) REFERENCES `mod_caja_mensual` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_egr_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_egr_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

COMMIT;
