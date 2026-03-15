-- Migracion POS (aditiva): snapshots de cliente y conductor por venta
-- Fecha: 2026-03-14
-- Objetivo: conservar contexto historico por venta sin depender solo de tablas maestras.

START TRANSACTION;

-- 1) Snapshot cliente en cabecera de venta
ALTER TABLE `pos_ventas`
  ADD COLUMN `cliente_snapshot_tipo_persona` enum('NATURAL','JURIDICA') DEFAULT NULL AFTER `cliente_id`,
  ADD COLUMN `cliente_snapshot_doc_tipo` enum('DNI','RUC','CE','PAS','BREVETE') DEFAULT NULL AFTER `cliente_snapshot_tipo_persona`,
  ADD COLUMN `cliente_snapshot_doc_numero` varchar(20) DEFAULT NULL AFTER `cliente_snapshot_doc_tipo`,
  ADD COLUMN `cliente_snapshot_nombre` varchar(200) DEFAULT NULL AFTER `cliente_snapshot_doc_numero`,
  ADD COLUMN `cliente_snapshot_telefono` varchar(30) DEFAULT NULL AFTER `cliente_snapshot_nombre`,
  ADD KEY `idx_pos_v_cli_snap_doc` (`cliente_snapshot_doc_tipo`,`cliente_snapshot_doc_numero`);

-- 2) Snapshot conductor en relacion venta-conductores
ALTER TABLE `pos_venta_conductores`
  ADD COLUMN `conductor_doc_tipo` enum('DNI','CE','PAS','BREVETE') DEFAULT NULL AFTER `conductor_id`,
  ADD COLUMN `conductor_doc_numero` varchar(20) DEFAULT NULL AFTER `conductor_doc_tipo`,
  ADD COLUMN `conductor_nombres` varchar(120) DEFAULT NULL AFTER `conductor_doc_numero`,
  ADD COLUMN `conductor_apellidos` varchar(120) DEFAULT NULL AFTER `conductor_nombres`,
  ADD COLUMN `conductor_telefono` varchar(30) DEFAULT NULL AFTER `conductor_apellidos`,
  ADD COLUMN `conductor_es_mismo_cliente` tinyint(1) NOT NULL DEFAULT 0 AFTER `conductor_telefono`,
  ADD COLUMN `conductor_origen` varchar(40) DEFAULT NULL AFTER `conductor_es_mismo_cliente`,
  ADD COLUMN `conductor_categoria_auto_id` smallint(5) UNSIGNED DEFAULT NULL AFTER `nacimiento`,
  ADD COLUMN `conductor_categoria_moto_id` smallint(5) UNSIGNED DEFAULT NULL AFTER `conductor_categoria_auto_id`,
  ADD KEY `idx_pos_vc_doc_snap` (`conductor_doc_tipo`,`conductor_doc_numero`),
  ADD KEY `idx_pos_vc_mismo_cli` (`conductor_es_mismo_cliente`),
  ADD KEY `idx_pos_vc_cat_auto_snap` (`conductor_categoria_auto_id`),
  ADD KEY `idx_pos_vc_cat_moto_snap` (`conductor_categoria_moto_id`);

ALTER TABLE `pos_venta_conductores`
  ADD CONSTRAINT `fk_pos_vc_cat_auto_snap`
    FOREIGN KEY (`conductor_categoria_auto_id`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pos_vc_cat_moto_snap`
    FOREIGN KEY (`conductor_categoria_moto_id`) REFERENCES `cq_categorias_licencia` (`id`) ON UPDATE CASCADE;

COMMIT;

