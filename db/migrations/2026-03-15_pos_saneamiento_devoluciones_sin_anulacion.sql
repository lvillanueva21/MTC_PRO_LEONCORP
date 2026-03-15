-- Migracion POS: saneamiento para flujo sin anulacion manual
-- Fecha: 2026-03-15
-- Objetivo:
--   1) Detectar ventas ANULADA con dinero neto aun no devuelto.
--   2) Reactivar esas ventas a EMITIDA para corregir inconsistencias historicas.
--   3) Normalizar totales monetarios en pos_ventas:
--      - total_pagado = neto actual (aplicado - devuelto), o 0 si ANULADA
--      - total_devuelto = suma real en pos_devoluciones
--      - saldo = total - total_pagado (o 0 si ANULADA)
--
-- Nota:
--   Este script NO crea tablas ni columnas.

/* 1) Diagnostico previo: anuladas con dinero neto */
SELECT
  COUNT(*) AS anuladas_con_dinero
FROM (
  SELECT
    v.id,
    ROUND(GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0)), 2) AS neto_ingresado
  FROM pos_ventas v
  LEFT JOIN (
    SELECT venta_id, SUM(monto_aplicado) AS aplicado
    FROM pos_abono_aplicaciones
    GROUP BY venta_id
  ) ap ON ap.venta_id = v.id
  LEFT JOIN (
    SELECT venta_id, SUM(monto_devuelto) AS devuelto
    FROM pos_devoluciones
    GROUP BY venta_id
  ) dv ON dv.venta_id = v.id
  WHERE v.estado = 'ANULADA'
) x
WHERE x.neto_ingresado > 0.00;

/* 2) Reactivar anuladas inconsistentes (si existen) */
UPDATE pos_ventas v
LEFT JOIN (
  SELECT venta_id, SUM(monto_aplicado) AS aplicado
  FROM pos_abono_aplicaciones
  GROUP BY venta_id
) ap ON ap.venta_id = v.id
LEFT JOIN (
  SELECT venta_id, SUM(monto_devuelto) AS devuelto
  FROM pos_devoluciones
  GROUP BY venta_id
) dv ON dv.venta_id = v.id
SET
  v.estado = 'EMITIDA',
  v.total_devuelto = ROUND(COALESCE(dv.devuelto,0),2),
  v.total_pagado = ROUND(GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0)),2),
  v.saldo = ROUND(GREATEST(0, v.total - GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0))),2),
  v.observacion = CASE
    WHEN COALESCE(v.observacion,'') LIKE '%SANEAMIENTO 2026-03-15%'
      THEN v.observacion
    ELSE CONCAT(COALESCE(v.observacion,''), ' | SANEAMIENTO 2026-03-15: reactivada por inconsistencia de anulacion sin devolucion total')
  END
WHERE v.estado = 'ANULADA'
  AND ROUND(GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0)),2) > 0.00;

/* 3) Normalizacion global de columnas monetarias */
UPDATE pos_ventas v
LEFT JOIN (
  SELECT venta_id, SUM(monto_aplicado) AS aplicado
  FROM pos_abono_aplicaciones
  GROUP BY venta_id
) ap ON ap.venta_id = v.id
LEFT JOIN (
  SELECT venta_id, SUM(monto_devuelto) AS devuelto
  FROM pos_devoluciones
  GROUP BY venta_id
) dv ON dv.venta_id = v.id
SET
  v.total_devuelto = ROUND(COALESCE(dv.devuelto,0),2),
  v.total_pagado = CASE
    WHEN v.estado = 'ANULADA' THEN 0.00
    ELSE ROUND(GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0)),2)
  END,
  v.saldo = CASE
    WHEN v.estado = 'ANULADA' THEN 0.00
    ELSE ROUND(GREATEST(0, v.total - GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0))),2)
  END;

/* 4) Verificacion posterior */
SELECT
  COUNT(*) AS anuladas_con_dinero_post
FROM (
  SELECT
    v.id,
    ROUND(GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0)), 2) AS neto_ingresado
  FROM pos_ventas v
  LEFT JOIN (
    SELECT venta_id, SUM(monto_aplicado) AS aplicado
    FROM pos_abono_aplicaciones
    GROUP BY venta_id
  ) ap ON ap.venta_id = v.id
  LEFT JOIN (
    SELECT venta_id, SUM(monto_devuelto) AS devuelto
    FROM pos_devoluciones
    GROUP BY venta_id
  ) dv ON dv.venta_id = v.id
  WHERE v.estado = 'ANULADA'
) x
WHERE x.neto_ingresado > 0.00;
