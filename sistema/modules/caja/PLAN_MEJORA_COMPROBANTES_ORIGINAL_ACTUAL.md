# Plan Maestro: Comprobantes Venta/Abono (Original y Actual)

## Estado del documento
- Fecha: 2026-03-15
- Proyecto: `MTC_PRO_LEONCORP`
- Modulo base: `sistema/modules/caja`
- Tipo: documento maestro de contexto, reglas y plan de ejecucion
- Regla: leer este archivo completo antes de tocar codigo o BD

## Objetivo
Consolidar en un solo lugar:
1. Reglas de negocio cerradas por el usuario.
2. Hallazgos reales del sistema actual.
3. Cambios pendientes para cerrar imprecisiones.
4. Plan de implementacion por fases, con criterio de calidad y no regresion.

## Regla operativa para futuras sesiones
1. No iniciar cambios sin leer este archivo.
2. No romper flujos actuales de caja y reportes.
3. No alterar series y numeros existentes.
4. Priorizar compatibilidad y trazabilidad.
5. Separar siempre "ticket para cliente" de "metadatos de auditoria".

## Contexto funcional confirmado
1. Existen dos tipos de comprobante en el dominio:
- Comprobante de venta (operacion comercial de la venta).
- Comprobante de abono (ingreso de dinero aplicado a una venta).

2. Relacion de datos:
- Una venta puede tener muchos abonos.
- Un abono pertenece a una sola venta.

3. Objetivo de interfaz:
- En `reporte_ventas`: botones de comprobante `Original` y `Actual`.
- En `reporte_abonos`: iconos de comprobante `Original` y `Actual` dentro de columna Estado.

4. Objetivo documental:
- `Original`: debe representar lo emitido en ese momento.
- `Actual`: debe representar el estado vigente.

## Preferencias y decisiones de negocio no negociables
1. Debe existir reimpresion de `Original` y `Actual` para venta y abono.
2. El cajero de la operacion original nunca debe ser reemplazado por quien reimprime.
3. `Reimpreso por` puede mostrarse en reimpresion, pero no se desea persistir historico de reimpresiones.
4. Serie y numero del comprobante no se modifican.
5. Formatos permitidos: `ticket80`, `ticket58`, `a4`.
6. En historicos sin snapshot, se permite salida `APROXIMADO`.
7. En anulaciones/devoluciones:
- `Original` se mantiene historico.
- `Actual` muestra estado actual.
8. Terminologia oficial: usar `Actual` (no `Final`).
9. Permisos: los mismos de operaciones de caja.
10. Nombre de archivo PDF obligatorio al descargar:
- Debe incluir `codigo`, `tipo de comprobante` y `nombre de empresa`.
- Tipos permitidos en nombre: `ticket_venta` y `comprobante_abono`.
- El nombre debe ser legible, sin espacios y con caracteres seguros para archivo.

## Hallazgos tecnicos reales (estado actual)
1. Ya existen tablas nuevas para snapshot:
- `pos_comprobantes`
- `pos_comprobante_abonos`

2. Ya existe servicio compartido:
- `sistema/modules/caja/voucher_history_service.php`

3. Ya existe soporte `scope=original|actual` en `voucher_pdf` y `voucher_preview`.

4. Ya existen botones y modal en:
- `reporte_ventas`
- `reporte_abonos`

5. Limitacion detectada:
- En pagos iniciales de `venta_crear` se registran filas en `pos_abonos`, pero no siempre se guarda snapshot `ABONO` original para cada ingreso inicial.
- Resultado: en algunos abonos iniciales, al pedir `Original`, aparece `ORIGINAL (APROXIMADO)`.

6. Fuga de informacion al ticket cliente detectada:
- El PDF puede mostrar datos internos como `Alcance` y `Reimpreso por`.
- En primera impresion despues de cobrar, esos datos NO deben verse para cliente.

## Problemas a cerrar
1. Cerrar imprecision documental de abonos iniciales.
2. Asegurar "codigo de ingreso" por cada ingreso de dinero.
3. Evitar dudas operativas en caja:
- Cliente quiere ticket de venta.
- Auditoria necesita poder ver tambien ticket de abono.
4. Aislar metadatos internos para no mostrarlos en primera impresion cliente.

## Modelo objetivo (regla redonda de dinero)
1. `Comprobante de venta`:
- Siempre existe 1 por venta.
- Es el documento principal para cliente.

2. `Comprobante de abono`:
- Debe existir 1 por cada ingreso de dinero (incluido pago inicial en venta_crear).
- Debe quedar trazable con codigo `ABN-*` y snapshot inmutable.

3. Resultado esperado:
- No hay ingresos sin codigo/documento de abono.
- No hay duda en auditoria por empresa.
- En reportes se puede imprimir venta o abono segun necesidad.

## Politica de impresion: cliente vs auditoria
1. Primera impresion al terminar una operacion en caja:
- Salida limpia para cliente.
- No mostrar `Original/Actual`.
- No mostrar `Reimpreso por`.
- No mostrar etiquetas de control interno.

2. Reimpresion desde reportes:
- Si corresponde, mostrar `Reimpreso por`.
- Mostrar `Original/Actual` para uso interno.
- Mostrar `APROXIMADO` solo cuando no hay snapshot historico.

3. Recomendacion de implementacion:
- Introducir modo visual de salida:
  - `presentation=cliente` (default en caja)
  - `presentation=auditoria` (reportes)
- Mantener compatibilidad si no se envia parametro.

## Politica de nombre de archivo PDF
1. Regla de construccion:
- `<tipo>_<codigo>_<empresa>.pdf`

2. Ejemplos:
- `ticket_venta_VTA-000123_TRANSPORTES_LEONCORP_SAC.pdf`
- `comprobante_abono_ABN-000456_TRANSPORTES_LEONCORP_SAC.pdf`

3. Normalizacion recomendada:
- Convertir espacios a `_`.
- Mantener solo letras, numeros, `_` y `-`.
- Si el nombre de empresa viene vacio, usar `EMPRESA`.

## Arquitectura de datos objetivo
1. `pos_comprobantes`:
- Snapshot inmutable de emision (`VENTA` o `ABONO`).
- Incluye exactitud `EXACTO|APROXIMADO`.

2. `pos_comprobante_abonos`:
- Relacion entre comprobante y abono.
- Permite resolver original exacto por `abono_id`.

3. `pos_abono_aplicaciones`:
- Regla reforzada `1 abono -> 1 venta` con unique defensivo en `abono_id`.

## Estrategia de compatibilidad
1. Mantener endpoint `voucher_pdf`.
2. Mantener `scope=actual` por defecto.
3. Mantener `kind=venta|abono`.
4. Extender sin romper:
- `scope`
- `presentation`
- `abono_id|abono_ids`

## Cambios pendientes (lista ejecutable)
1. Backend caja (`api.php`):
- En `venta_crear`, despues de crear abonos iniciales, crear snapshot `ABONO` original por cada abono creado.
- Relacionar cada snapshot con `pos_comprobante_abonos`.
- Mantener snapshot `VENTA` original ya existente.

2. Backend ventas (`api_ventas.php`):
- Refinar render PDF para ocultar metadatos internos en `presentation=cliente`.
- Mostrar metadatos en `presentation=auditoria`.
- Mantener `ORIGINAL (APROXIMADO)` solo en fallback real sin snapshot.

3. Servicio historial (`voucher_history_service.php`):
- Asegurar builders de payload coherentes para venta y abono en ambos alcances.
- Garantizar traza estable de operador original y reimpresor.

4. Frontend caja/reportes:
- Caja: imprimir en modo cliente.
- Reportes: abrir/imprimir en modo auditoria.
- Mantener experiencia actual de modal y botones.

## Plan de implementacion por fases
### Fase 0 - Preparacion y control
1. Congelar reglas en este documento.
2. Revisar migraciones existentes y estado de despliegue.
3. Definir checklist de regresion funcional.

### Fase 1 - Precision documental de abonos iniciales
1. Ajustar `venta_crear` para snapshot `ABONO` de pagos iniciales.
2. Verificar que cada ingreso inicial tenga correspondencia en `pos_comprobante_abonos`.
3. Confirmar que `reporte_abonos` deje de caer en `APROXIMADO` para operaciones nuevas.

### Fase 2 - Limpieza de ticket cliente
1. Implementar control de presentacion (`cliente` vs `auditoria`).
2. Ocultar `Alcance`, `Reimpreso por` y otros metadatos internos en cliente.
3. Conservar metadatos para reportes/reimpresion interna.

### Fase 3 - Endurecimiento de trazabilidad
1. Revisar consistencia de ABN por cada ingreso.
2. Confirmar que no existan ingresos sin comprobante de abono.
3. Validar anulaciones/devoluciones en `Actual`.

### Fase 4 - QA funcional completo
1. Venta pagada total en una sola operacion.
2. Venta parcial con multiples abonos.
3. Abonos iniciales vs abonos de completar pago.
4. Reimpresion original y actual desde ambos reportes.
5. Anulacion/devolucion parcial/total.
6. Impresion en 80/58/A4.

## Criterios de aceptacion
1. Toda venta nueva con pago inicial genera:
- comprobante VENTA original
- comprobante ABONO original por cada ingreso
2. Todo abono nuevo de completar pago genera comprobante ABONO original exacto.
3. `ORIGINAL (APROXIMADO)` aparece solo cuando no existe snapshot historico real.
4. Primera impresion de caja no expone metadatos internos.
5. Reportes mantienen acceso a original y actual para venta y abono.
6. No hay regresion en caja diaria/mensual ni en estados de venta.

## SQL y migraciones relacionadas
1. `db/migrations/2026-03-15_pos_comprobantes.sql`
2. `db/migrations/2026-03-15_pos_comprobante_abonos.sql`
3. `db/migrations/2026-03-15_pos_abono_aplicaciones_unico_abono.sql`

## Orden de ejecucion SQL recomendado
1. `2026-03-15_pos_comprobantes.sql`
2. `2026-03-15_pos_comprobante_abonos.sql`
3. `2026-03-15_pos_abono_aplicaciones_unico_abono.sql`

## Archivos tecnicos clave (objetivo de trabajo)
1. `sistema/modules/caja/api.php`
2. `sistema/modules/caja/api_ventas.php`
3. `sistema/modules/caja/voucher_history_service.php`
4. `sistema/modules/reporte_ventas/index.php`
5. `sistema/modules/reporte_ventas/index.js`
6. `sistema/modules/reporte_abonos/index.php`
7. `sistema/modules/reporte_abonos/index.js`
8. `sistema/modules/reporte_abonos/style.css`

## Checklist rapido antes de cada commit
1. No se rompio endpoint existente.
2. No se altero serie/numero.
3. Operador original sigue correcto.
4. Reimpreso por solo en contexto de auditoria.
5. Cada ingreso nuevo tiene ABN rastreable.
6. Pruebas minimas de flujo pasadas.

## Fuera de alcance por ahora
1. Guardar historico infinito de reimpresiones.
2. Cambiar numeracion oficial existente de venta.
3. Rediseno total visual del ticket.

## Nota para Codex futuro
Si se pierde contexto:
1. Leer este archivo primero.
2. Validar reglas no negociables.
3. Ejecutar fases en orden.
4. Priorizar estabilidad y precision documental.
