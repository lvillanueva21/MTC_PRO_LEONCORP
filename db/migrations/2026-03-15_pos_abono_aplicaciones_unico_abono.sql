-- Migracion POS: refuerzo de regla 1 abono -> 1 venta
-- Fecha: 2026-03-15
-- Nota:
--  - Se agrega UNIQUE(abono_id) solo si:
--    1) no existe ya el indice
--    2) no hay datos duplicados por abono_id
--  - Si hay duplicados, la migracion no rompe y deja mensaje de skip.

SET @idx_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'pos_abono_aplicaciones'
    AND index_name = 'uq_pos_apl_abono_unico'
);

SET @dup_count := (
  SELECT COUNT(*) FROM (
    SELECT abono_id
    FROM pos_abono_aplicaciones
    GROUP BY abono_id
    HAVING COUNT(*) > 1
  ) t
);

SET @sql_apply := IF(
  @idx_exists > 0,
  'SELECT ''SKIP: indice uq_pos_apl_abono_unico ya existe.'' AS info',
  IF(
    @dup_count > 0,
    'SELECT ''SKIP: existen abonos asociados a mas de una aplicacion. Revisar datos antes de forzar unique.'' AS info',
    'ALTER TABLE `pos_abono_aplicaciones` ADD UNIQUE KEY `uq_pos_apl_abono_unico` (`abono_id`)'
  )
);

PREPARE stmt_apply FROM @sql_apply;
EXECUTE stmt_apply;
DEALLOCATE PREPARE stmt_apply;

