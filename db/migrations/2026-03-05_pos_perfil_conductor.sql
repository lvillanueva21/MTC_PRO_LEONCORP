-- Migracion POS: perfil opcional de conductor + limpieza de columnas no usadas en clientes.
-- Fecha: 2026-03-05
-- Requiere: tabla `cq_categorias_licencia` existente.

START TRANSACTION;

CREATE TABLE `pos_perfil_conductor` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pos_pc_doc` (`id_empresa`,`doc_tipo`,`doc_numero`),
  KEY `idx_pos_pc_cat_auto` (`categoria_auto_id`),
  KEY `idx_pos_pc_cat_moto` (`categoria_moto_id`),
  CONSTRAINT `fk_pos_pc_emp` FOREIGN KEY (`id_empresa`) REFERENCES `mtp_empresas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_pc_cat_auto` FOREIGN KEY (`categoria_auto_id`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pos_pc_cat_moto` FOREIGN KEY (`categoria_moto_id`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

ALTER TABLE `pos_clientes`
  DROP COLUMN `email`,
  DROP COLUMN `direccion`;

COMMIT;
