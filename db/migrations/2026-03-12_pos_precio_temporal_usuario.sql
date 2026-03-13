-- Migracion POS: precio temporal por usuario en detalles de venta.
-- Fecha: 2026-03-12
-- Nota: FK opcional precio_temporal_actor_id -> mtp_usuarios(id) omitida por compatibilidad de despliegue.

START TRANSACTION;

ALTER TABLE `pos_venta_detalles`
  ADD COLUMN IF NOT EXISTS `precio_origen` ENUM('LISTA','TEMPORAL') NOT NULL DEFAULT 'LISTA' AFTER `total_linea`,
  ADD COLUMN IF NOT EXISTS `precio_lista_id` INT(10) UNSIGNED NULL AFTER `precio_origen`,
  ADD COLUMN IF NOT EXISTS `precio_lista_base` DECIMAL(12,2) NULL AFTER `precio_lista_id`,
  ADD COLUMN IF NOT EXISTS `precio_temporal_actor_id` INT(10) UNSIGNED NULL AFTER `precio_lista_base`,
  ADD COLUMN IF NOT EXISTS `precio_temporal_motivo` VARCHAR(255) NULL AFTER `precio_temporal_actor_id`,
  ADD COLUMN IF NOT EXISTS `precio_temporal_en` DATETIME NULL AFTER `precio_temporal_motivo`;

ALTER TABLE `pos_ventas`
  ADD COLUMN IF NOT EXISTS `tiene_precio_temporal` TINYINT(1) NOT NULL DEFAULT 0 AFTER `saldo`;

ALTER TABLE `pos_ventas`
  ADD INDEX `idx_pos_ventas_tmp_fecha` (`tiene_precio_temporal`, `fecha_emision`);

ALTER TABLE `pos_venta_detalles`
  ADD INDEX `idx_pos_vd_origen_actor` (`precio_origen`, `precio_temporal_actor_id`);

COMMIT;
