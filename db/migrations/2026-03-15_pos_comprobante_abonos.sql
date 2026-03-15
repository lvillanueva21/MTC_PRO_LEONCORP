-- Migracion POS: relacion comprobante historico <-> abonos
-- Fecha: 2026-03-15

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `pos_comprobante_abonos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `comprobante_id` bigint(20) UNSIGNED NOT NULL,
  `abono_id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `monto_aplicado_snapshot` decimal(14,2) NOT NULL DEFAULT 0.00,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pca_comp_abono` (`comprobante_id`,`abono_id`),
  KEY `idx_pca_abono` (`abono_id`),
  KEY `idx_pca_venta` (`venta_id`),
  CONSTRAINT `fk_pca_comp` FOREIGN KEY (`comprobante_id`) REFERENCES `pos_comprobantes` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_pca_abono` FOREIGN KEY (`abono_id`) REFERENCES `pos_abonos` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_pca_venta` FOREIGN KEY (`venta_id`) REFERENCES `pos_ventas` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

COMMIT;

