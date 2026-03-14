-- modules/egresos/sql/20260313_egr_egreso_fuentes.sql
-- Distribucion de cada egreso por fuente de dinero.

CREATE TABLE IF NOT EXISTS `egr_egreso_fuentes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_egreso` bigint(20) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_caja_diaria` int(10) UNSIGNED NOT NULL,
  `fuente_key` enum('EFECTIVO','YAPE','PLIN','TRANSFERENCIA') NOT NULL,
  `medio_id` int(10) UNSIGNED NOT NULL,
  `monto` decimal(14,2) NOT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_egr_fuente_egreso_key` (`id_egreso`,`fuente_key`),
  KEY `idx_egr_fuente_emp_caja` (`id_empresa`,`id_caja_diaria`),
  KEY `idx_egr_fuente_key` (`fuente_key`),
  KEY `idx_egr_fuente_medio` (`medio_id`),
  CONSTRAINT `fk_egr_fuente_egreso` FOREIGN KEY (`id_egreso`) REFERENCES `egr_egresos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_egr_fuente_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_egr_fuente_caja_diaria` FOREIGN KEY (`id_caja_diaria`) REFERENCES `mod_caja_diaria` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_egr_fuente_medio` FOREIGN KEY (`medio_id`) REFERENCES `pos_medios_pago` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
