# Plan Maestro: Devoluciones Sin Anulacion Manual

Fecha: 2026-03-15
Estado: APROBADO PARA IMPLEMENTACION
Alcance: Caja + Reporte Ventas + Reporte Abonos + coherencia de datos historicos
Tipo: Documento de control para ejecucion tecnica segura

## 1) Objetivo de este documento

Este archivo es la fuente de verdad para implementar el nuevo comportamiento del sistema sin perder contexto.

Meta principal:
- Eliminar la anulacion manual de ventas.
- Mantener solo devoluciones:
  - Devolucion total de venta.
  - Devolucion por abono (icono redo).
- Garantizar coherencia entre:
  - Estado de venta.
  - Dinero real en caja.
  - Comprobantes original/actual.
  - Reportes.

## 2) Decisiones de negocio cerradas (definidas por usuario)

1. Se elimina boton "Anular" y su flujo operativo.
2. Se elimina codigo backend de `venta_anular`.
3. Boton "Devolucion" mantiene ese nombre y significa devolucion total de venta.
4. Devolucion de abono se mantiene con icono redo en historial/modal de abonos.
5. Semantica visual:
   - DEVOLUCION TOTAL: cuando la venta fue devuelta completamente.
   - DEVOLUCION PARCIAL: cuando se devolvio uno o varios abonos pero no todo.
6. La anulacion en terminos de negocio significa retirar el dinero de caja.
7. No se acepta "anular y dejar dinero adentro".
8. Comprobantes se mantienen con logica original/actual ya implementada.
9. Primera impresion en caja debe seguir siendo formato cliente (sin metadata interna de auditoria).
10. Reimpresiones en reportes pueden mostrar metadata de auditoria (original/actual/reimpreso por).

## 3) Regla funcional final del sistema

### 3.1 Estados tecnicos de venta (BD)

- `EMITIDA`: venta vigente (pagada o pendiente), con o sin devolucion parcial.
- `ANULADA`: venta con devolucion total ejecutada.

Nota:
- No se crea estado nuevo en tabla de ventas para evitar impacto amplio.
- "DEVOLUCION TOTAL/PARCIAL" se maneja como semantica visual calculada.

### 3.2 Estado visual (UI)

Reglas recomendadas:

1. Si `v.estado = ANULADA` -> badge principal `DEVOLUCION TOTAL`.
2. Si `v.estado = EMITIDA` y existe devolucion real > 0 -> badge principal `DEVOLUCION PARCIAL`.
3. Si `v.estado = EMITIDA` y saldo > 0 y sin devolucion -> `PENDIENTE`.
4. Si `v.estado = EMITIDA` y saldo = 0 y sin devolucion -> `PAGADO`.

## 4) Alcance tecnico (modulos y archivos)

Archivos objetivo para la implementacion posterior:

- `sistema/modules/caja/api_ventas.php`
- `sistema/modules/caja/ventas_pendientes.js`
- `sistema/modules/reporte_ventas/index.php`
- `sistema/modules/reporte_ventas/index.js`
- `sistema/modules/reporte_ventas/funciones.php`
- `sistema/modules/reporte_abonos/index.php` (solo si se requiere ajuste visual/etiquetas)
- `sistema/modules/reporte_abonos/index.js` (solo si se requiere ajuste visual/etiquetas)

## 5) Diagnostico actual (resumen tecnico)

1. Existe `venta_anular` que solo cambia estado a ANULADA y registra anulacion, sin devolver dinero.
2. Existe `venta_devolucion` que devuelve lo aplicado y marca ANULADA.
3. Existe `venta_devolver_abono` para devolver por aplicacion de abono.
4. Esto permite hoy un caso inconsistente: venta anulada con dinero no devuelto.
5. En reportes, la semantica visual y calculos pueden divergir si no se recalculan totales de forma unificada.

## 6) Propuesta de implementacion por fases (sin ejecutar aun)

### Fase A: Regla operativa unica (sin anular manual)

1. Eliminar UI de boton `Anular` en caja.
2. Eliminar dispatcher JS asociado a `.vp-anular`.
3. Eliminar bloque backend `if ($accion === 'venta_anular')`.
4. Asegurar que todos los caminos de "cancelar venta" usen `venta_devolucion`.

### Fase B: Etiquetas y semantica visual

1. Ajustar badges de caja:
   - Mostrar `DEVOLUCION TOTAL` si estado venta ANULADA.
   - Mostrar `DEVOLUCION PARCIAL` si hay devolucion y estado EMITIDA.
2. Ajustar badges de reporte_ventas con la misma regla.
3. Mantener icono redo para devolucion de abono.

### Fase C: Consistencia de calculos de dinero

1. Revisar y unificar recalc de:
   - `total_pagado`
   - `saldo`
   - `total_devuelto`
2. Garantizar consistencia tras:
   - devolucion total
   - devolucion por abono
   - nuevas operaciones de abono

### Fase D: Saneamiento de datos historicos

1. Detectar ventas ANULADA con neto ingresado > 0.
2. Reactivar esas ventas a EMITIDA para no falsear caja.
3. Recalcular columnas monetarias de `pos_ventas` desde aplicaciones/devoluciones reales.
4. Validar que no queden ANULADA con dinero neto.

### Fase E: QA funcional completa

1. Venta parcial + devolucion abono.
2. Venta pagada total + devolucion total.
3. Venta con varios abonos + devoluciones parciales + comprobantes original/actual.
4. Verificacion de reportes (ventas y abonos) contra movimientos reales.

## 7) SQL de saneamiento propuesto (para fase de datos)

Importante:
- Ejecutar primero en entorno de prueba.
- Respaldar BD antes.
- Verificar que los nombres de columna/tablas existen en la BD actual.

### 7.1 Detectar ANULADA con dinero neto

```sql
SELECT
  v.id,
  v.serie,
  v.numero,
  v.estado,
  v.total,
  v.total_pagado,
  v.total_devuelto,
  v.saldo,
  COALESCE(ap.aplicado,0) AS aplicado_bruto,
  COALESCE(dv.devuelto,0) AS devuelto_real,
  ROUND(GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0)),2) AS neto_ingresado
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
  AND ROUND(GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0)),2) > 0.00
ORDER BY v.id DESC;
```

### 7.2 Reactivar anuladas inconsistentes

```sql
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
  v.observacion = CONCAT(COALESCE(v.observacion,''), ' | MIGRACION: anulada sin devolucion total, reactivada para regularizacion')
WHERE v.estado = 'ANULADA'
  AND ROUND(GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0)),2) > 0.00;
```

### 7.3 Normalizar totales de todas las ventas

```sql
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
```

### 7.4 Verificacion final

```sql
SELECT COUNT(*) AS anuladas_con_dinero
FROM (
  SELECT v.id,
         ROUND(GREATEST(0, COALESCE(ap.aplicado,0) - COALESCE(dv.devuelto,0)),2) AS neto_ingresado
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
  WHERE v.estado='ANULADA'
) x
WHERE x.neto_ingresado > 0.00;
```

Resultado esperado:
- `anuladas_con_dinero = 0`

## 8) Criterios de aceptacion funcional

1. No existe boton `Anular` en caja.
2. No existe endpoint operativo `venta_anular`.
3. "Devolucion" siempre retira dinero y deja rastro.
4. Devolucion total deja estado tecnico ANULADA y estado visual DEVOLUCION TOTAL.
5. Devolucion de abono produce estado visual DEVOLUCION PARCIAL cuando corresponda.
6. Reporte ventas y reporte abonos no muestran contradicciones monetarias.
7. Comprobante original no cambia.
8. Comprobante actual refleja estado vigente real.

## 9) Riesgos y mitigaciones

Riesgo 1:
- Inconsistencia de sumatorias en reportes.
Mitigacion:
- Recalculo unificado y validacion cruzada ventas vs abonos.

Riesgo 2:
- Registros historicos ANULADA con dinero.
Mitigacion:
- SQL de saneamiento antes de activar reglas nuevas.

Riesgo 3:
- Confusion de usuario por terminologia.
Mitigacion:
- Etiquetas explicitas: DEVOLUCION TOTAL / DEVOLUCION PARCIAL.

## 10) Orden recomendado de ejecucion futura

1. Backup DB.
2. Ejecutar SQL de deteccion.
3. Ejecutar SQL de reactivacion de inconsistentes.
4. Ejecutar SQL de normalizacion global.
5. Aplicar cambios de backend (`api_ventas.php`).
6. Aplicar cambios de UI caja (`ventas_pendientes.js`).
7. Ajustar reportes (`reporte_ventas` y opcional `reporte_abonos`).
8. QA funcional completa.
9. Verificacion final de netos y estados.

## 11) No hacer (restricciones acordadas)

1. No mantener anulacion manual.
2. No dejar ventas ANULADA con dinero neto.
3. No mostrar metadata interna al cliente en primera impresion de caja.
4. No introducir cambios destructivos sin respaldo y validacion previa.

---

Documento creado para preservar contexto y ejecutar una mejora profesional, segura y coherente con reglas reales de caja y auditoria.
