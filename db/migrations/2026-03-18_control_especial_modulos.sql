-- /db/migrations/2026-03-18_control_especial_modulos.sql
-- FASE 01: infraestructura base de permisos especiales por modulo para rol Control.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `mtp_control_modulos_usuario` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `modulo_slug` varchar(120) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actualizado_por` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cme_usuario_modulo` (`id_usuario`,`modulo_slug`),
  KEY `idx_cme_usuario_estado` (`id_usuario`,`estado`),
  KEY `idx_cme_modulo_estado` (`modulo_slug`,`estado`),
  KEY `idx_cme_actualizado_por` (`actualizado_por`),
  CONSTRAINT `fk_cme_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `mtp_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cme_actualizado_por` FOREIGN KEY (`actualizado_por`) REFERENCES `mtp_usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

COMMIT;
