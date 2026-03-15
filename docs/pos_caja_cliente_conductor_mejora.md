# Mejora POS Caja: Cliente + Conductor (Aditiva, sin ruptura)

## Objetivo
Fortalecer el proceso de registro de pago/venta en `modules/caja` para conservar correctamente:

- Datos del cliente (natural o juridica).
- Datos del conductor (igual al cliente, contratante o persona distinta).
- Datos extra del conductor.

Sin romper tablas ni ventas existentes en produccion.

## Alcance funcional
Se mantiene el formulario actual del modal de pago y se refuerza persistencia/reporte.

Flujos funcionales cubiertos:

1. Cliente natural + conductor cliente + sin extra.
2. Cliente natural + conductor cliente + con extra.
3. Cliente natural + conductor otra persona + sin extra.
4. Cliente natural + conductor otra persona + con extra.
5. Cliente juridica (RUC) + conductor contratante + sin extra.
6. Cliente juridica (RUC) + conductor contratante + con extra.
7. Cliente juridica (RUC) + conductor otra persona + sin extra.
8. Cliente juridica (RUC) + conductor otra persona + con extra.

## Problemas detectados
1. Reportes dependen de tablas maestras (`pos_clientes`, `pos_conductores`) que pueden cambiar con el tiempo.
2. `pos_venta_conductores` no guardaba snapshot de identidad del conductor por venta.
3. Extra del conductor se guardaba en `pos_perfil_conductor` (global por documento), pero no quedaba necesariamente trazado por venta.
4. `reporte_clientes` mostraba perfil adicional por documento de cliente, no por conductor asociado en ventas.

## Estrategia tecnica (aditiva)
No se elimina ni se reemplaza estructura existente.

### 1) Snapshot cliente por venta
Agregar columnas snapshot en `pos_ventas` para congelar los datos del cliente al momento de emitir la venta.

### 2) Snapshot conductor por venta
Agregar columnas snapshot en `pos_venta_conductores` para congelar identidad/contacto del conductor principal de esa venta.

### 3) Extra de conductor por venta
Reusar `canal/email_contacto/nacimiento/nota` en `pos_venta_conductores` y agregar categorias para guardar contexto extra por venta.

### 4) Compatibilidad historica
Backfill aditivo leyendo `pos_auditoria.datos` (evento `VENTA_CREADA`) para poblar snapshots en ventas historicas.

### 5) Lectura en reportes
`reporte_ventas` y `api_ventas` pasan a usar snapshot primero y fallback a maestro.
`reporte_clientes` agrega contexto del ultimo conductor asociado para no perder informacion cuando conductor != cliente.

## Cambios de BD planificados
Migracion 1 (schema aditivo):

- `ALTER TABLE pos_ventas`:
  - `cliente_snapshot_tipo_persona`
  - `cliente_snapshot_doc_tipo`
  - `cliente_snapshot_doc_numero`
  - `cliente_snapshot_nombre`
  - `cliente_snapshot_telefono`

- `ALTER TABLE pos_venta_conductores`:
  - `conductor_doc_tipo`
  - `conductor_doc_numero`
  - `conductor_nombres`
  - `conductor_apellidos`
  - `conductor_telefono`
  - `conductor_es_mismo_cliente`
  - `conductor_origen`
  - `conductor_categoria_auto_id`
  - `conductor_categoria_moto_id`

Migracion 2 (backfill):

- Poblar snapshots desde datos actuales y desde `pos_auditoria.datos` si existe.
- Sin borrar ni sobreescribir datos no nulos ya existentes.

## Cambios de codigo planificados
1. `modules/caja/api.php`
   - Persistir snapshots cliente/conductor al crear venta.
   - Persistir extra de conductor por venta en `pos_venta_conductores`.
2. `modules/reporte_ventas/funciones.php`
   - Leer cliente y conductor desde snapshot (fallback a maestro).
3. `modules/caja/api_ventas.php`
   - Vouchers/listados/detalles usando snapshot primero.
4. `modules/reporte_clientes/funciones.php`
   - Incluir ultimo conductor asociado para contexto cuando conductor distinto.
5. `modules/reporte_clientes/index.php`
   - Mostrar ultimo conductor asociado en bloque de detalle.

## Criterios de calidad
- Cambios aditivos, sin `DROP` destructivo.
- Compatible con datos existentes.
- Fallback seguro cuando snapshot sea nulo.
- Sin romper flujo actual de caja ni reportes.
- Validacion de sintaxis PHP al finalizar.

## Matriz de pruebas objetivo
Base:
- 8 flujos funcionales del bloque "Datos de cliente".

Validacion/regresion:
- Documentos obligatorios por tipo.
- Conductor otra persona incompleto.
- Extra conductor con email/fecha/categoria invalida.
- Abonos invalidos/sobrepago/referencia obligatoria.
- Reporte ventas conserva telefonos distintos cliente/conductor.
- Reporte clientes muestra cliente + ultimo conductor asociado.

Total recomendado: 16 a 28 casos (segun profundidad UAT).

## Estado
- [x] Documento de contexto creado.
- [x] Migraciones creadas.
- [x] Guardado en caja actualizado.
- [x] Reportes actualizados.
- [ ] Backfill validado en entorno real.
- [ ] Lint/verificacion final (pendiente en este entorno: no hay `php` CLI).

## Orden de despliegue recomendado
1. Ejecutar migracion de schema: `db/migrations/2026-03-14_pos_snapshot_cliente_conductor.sql`.
2. Ejecutar migracion de backfill: `db/migrations/2026-03-14_pos_snapshot_backfill.sql`.
3. Publicar archivos PHP actualizados de caja y reportes.
4. Probar 8 flujos funcionales + validaciones de regresion.
