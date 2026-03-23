# CONTROL ESPECIAL - IMPLEMENTACION

## Branch actual
- `control-especial`

## Fase actual
- `FASE 08`
- Estado: regresion final completada sobre infraestructura ACL, menu, index y APIs de los modulos objetivo.

## Resumen de estrategia aplicada
- Catalogo manual/lista blanca para modulos clasicos elegibles (`reporte_ventas`, `reporte_abonos`, `reporte_clientes`, `caja`, `egresos`).
- Tabla dedicada `mtp_control_modulos_usuario` para asignacion por usuario Control y por `modulo_slug`.
- Helper central ACL para validar:
  - roles normales permitidos
  - o rol Control con permiso especial explicito
- Validacion en menu/base:
  - `menu_matrix.php` define items con `control_special_slug`
  - `sidebar.php` resuelve acceso con `acl_can_ids_or_control_special`
- Validacion en entrypoints publicos:
  - `index.php` en reportes, caja y egresos
  - `api.php`/`api_ventas.php` en caja y egresos
  - bloqueo de acceso directo en auxiliares include-only
- Sin descubrimiento dinamico de modulos clasicos.

## Catalogo manual (FASE 01)
- `reporte_ventas`
- `reporte_abonos`
- `reporte_clientes`
- `caja`
- `egresos`

## Archivos modificados (acumulado real en branch)
- `sistema/includes/acl.php`
- `sistema/includes/menu_matrix.php`
- `sistema/includes/sidebar.php`
- `sistema/modules/interfaces_control/index.php`
- `sistema/modules/interfaces_control/api.php`
- `sistema/modules/interfaces_control/assets/interfaces_control.js`
- `sistema/modules/reporte_ventas/index.php`
- `sistema/modules/reporte_abonos/index.php`
- `sistema/modules/reporte_clientes/index.php`
- `sistema/modules/caja/index.php`
- `sistema/modules/caja/api.php`
- `sistema/modules/caja/api_ventas.php`
- `sistema/modules/caja/perfil_conductor.php`
- `sistema/modules/caja/voucher_history_service.php`
- `sistema/modules/caja/prueba.php`
- `sistema/modules/egresos/index.php`
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/finanzas_medios.php`
- `sistema/modules/egresos/multicaja_service.php`
- `sistema/modules/CONTROL_ESPECIAL_IMPLEMENTACION.md`

## Archivos creados (acumulado real en branch)
- `sistema/includes/control_especial_catalog.php`
- `db/migrations/2026-03-18_control_especial_modulos.sql`
- `sistema/modules/CONTROL_ESPECIAL_IMPLEMENTACION.md`

## SQL aplicado
- Migracion: `db/migrations/2026-03-18_control_especial_modulos.sql`
- Tabla nueva: `mtp_control_modulos_usuario`
- Tabla existente confirmada en dump actual para interfaces dinamicas: `mtp_control_interfaces_usuario`

## Regresion final ejecutada (FASE 08)
- ACL central:
  - `acl_user_has_control_special_module`
  - `acl_can_ids_or_control_special`
  - `acl_require_ids_or_control_special`
- Menu:
  - visibilidad condicional para Control en `Caja` y bloque `FINANZAS`
  - control por `control_special_slug` en `Ventas`, `Abonos`, `Clientes`, `Egresos`
- URL directa:
  - guardas aplicadas en `reporte_ventas`, `reporte_abonos`, `reporte_clientes`, `caja`, `egresos`
- Backend:
  - guardas aplicadas en `caja/api.php`, `caja/api_ventas.php`, `egresos/api.php`
- Auxiliares include-only:
  - bloqueo directo en `caja/perfil_conductor.php`, `caja/voucher_history_service.php`, `caja/prueba.php`
  - bloqueo directo en `egresos/finanzas_medios.php`, `egresos/multicaja_service.php`
- Dump SQL actual revisado:
  - `mtp_control_interfaces_usuario` existe en `db/bd_actual_17-03-26.sql`
  - `mtp_control_modulos_usuario` queda en migracion dedicada (no en snapshot base)

## Pruebas manuales sugeridas (checklist final)
1. Ejecutar migracion `db/migrations/2026-03-18_control_especial_modulos.sql`.
2. Verificar existencia de `mtp_control_modulos_usuario`.
3. Desde Desarrollo, abrir `modules/interfaces_control/` y asignar/guardar modulos clasicos para un usuario Control.
4. Con usuario Control asignado a `reporte_ventas`, validar menu y URL directa.
5. Repetir validacion para `reporte_abonos`.
6. Repetir validacion para `reporte_clientes`.
7. Con usuario Control asignado a `caja`, validar:
   - abre interfaz
   - listar servicios
   - vender
   - registrar pago
8. Con usuario Control asignado a `egresos`, validar:
   - abre interfaz
   - egreso normal
   - egreso multicaja
   - anular egreso
   - preview/PDF
9. Con usuario Control sin permiso de cada modulo, validar ocultamiento de menu y 403 por URL/API.
10. Con Recepcion y Administracion, validar que el acceso historico siga igual en todos los modulos objetivo.

## Estado actual
- FASE 01 completada: infraestructura base.
- FASE 02 completada: UI/API de asignacion de permisos especiales.
- FASE 03 completada: `reporte_ventas`.
- FASE 04 completada: `reporte_abonos`.
- FASE 05 completada: `reporte_clientes`.
- FASE 06 completada: `caja` (UI + backend).
- FASE 07 completada: `egresos` (UI + backend).
- FASE 08 completada: regresion final de consistencia.
- Ajuste post-sincronizacion manual aplicado: restauradas guardas de Control Especial en `egresos/index.php` y `egresos/api.php`, y bloqueo de acceso directo en `egresos/finanzas_medios.php` y `egresos/multicaja_service.php`.
- Ajuste de consolidacion aplicado: `modules/egresos/*` actualizado desde `modules/egresos/mejora/*` y reinyectadas guardas de Control Especial para mantener compatibilidad funcional de multicaja con permisos por rol Control.

## Pendientes
- Ejecutar regression funcional completa en entorno con PHP habilitado en CLI/web para evidenciar 403/200 end-to-end por rol.
- Definir politica de limpieza para carpetas de backup historicas dentro de modulos (`caja`, `egresos`) si se decide endurecer superficie expuesta en produccion.
