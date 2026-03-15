-- Migracion POS: tabla de comprobantes historicos (snapshot inmutable)
-- Fecha: 2026-03-15

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `pos_comprobantes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pc_emp_tipo_venta` (`id_empresa`,`tipo`,`venta_id`),
  KEY `idx_pc_emp_ticket` (`id_empresa`,`ticket_serie`,`ticket_numero`),
  KEY `idx_pc_emitido_en` (`emitido_en`),
  KEY `idx_pc_emitido_por` (`emitido_por`),
  CONSTRAINT `fk_pc_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pc_venta` FOREIGN KEY (`venta_id`) REFERENCES `pos_ventas` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_pc_user` FOREIGN KEY (`emitido_por`) REFERENCES `mtp_usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

COMMIT;

