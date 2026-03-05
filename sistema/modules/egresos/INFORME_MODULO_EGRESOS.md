# Informe tecnico - Modulo Egresos

> Nota de estado (2026-03-05): este documento nacio como analisis de maqueta.
> El modulo ya tiene primera version real con `index.php`, `api.php` y `estilo.css`.

## 1) Objetivo de este documento
Ordenar la propuesta funcional del modulo `modules/egresos` para llevarlo de maqueta demo a modulo real, sin romper la logica actual de `modules/caja` ni el estilo visual existente.

## 2) Estado actual del modulo (hoy)
### Archivos encontrados
- `modules/egresos/index.php` -> modulo activo actual.
- `modules/egresos/style.css` -> estilos activos actuales.
- `modules/egresos/bbb/index.php` y `modules/egresos/bbb/style.css` -> copia identica del activo.
- `modules/egresos/aaa/index.php` y `modules/egresos/aaa/style.css` -> version anterior (legacy/prototipo previo).

### Conclusion rapida
Si, el modulo actual es **demo/ficticio**:
- No llama API de backend para guardar egresos.
- No hace `INSERT/UPDATE/SELECT` de egresos reales.
- Trabaja con arreglo JS en memoria (`DEMO_EGRESOS`).
- Muestra mensajes explicitos de modo demo.

## 3) Como funciona la plantilla actual (index.php activo)
## 3.1 Header y contexto
- Carga ACL/permisos y solo permite roles 3 y 4 (Recepcion/Administracion).
- Lee usuario/empresa/logotipo para pintar cabecera.
- Muestra barra superior "Modulo de egresos" con estado de caja simulado.

## 3.2 Bloque izquierdo: formulario "Nuevo egreso"
- Tipo de comprobante por chips:
  - `RECIBO`
  - `BOLETA`
  - `FACTURA`
- Reglas de campos:
  - Factura/Boleta: exige serie + numero.
  - Recibo: usa referencia opcional.
- Captura:
  - monto
  - fecha/hora
  - beneficiario/proveedor
  - documento
  - concepto
  - observaciones internas
- Acciones:
  - limpiar
  - guardar (demo)
  - vista previa de recibo

## 3.3 Bloque derecho: listado
- Tabla con filtros:
  - texto (concepto o beneficiario)
  - tipo
  - estado
- Paginacion local.
- Acciones por fila:
  - vista previa / imprimir (demo)
  - anular (solo cambia estado en memoria)
- Orden actual del listado:
  - por fecha descendente dentro del arreglo demo.

## 3.4 Modal de recibo
- Genera HTML de voucher en cliente.
- Muestra logo/empresa/beneficiario/concepto/monto.
- Boton "Imprimir (demo)" solo lanza `alert`.

## 3.5 Estado de caja en modulo actual
- Es 100% simulado:
  - `state.cajaAbierta = true`
  - mensaje verde fijo
  - no consulta `mod_caja_diaria` real.

## 4) Estilo visual actual (style.css)
- Estetica principal:
  - gradiente morado en barra superior
  - tarjetas con radio suave
  - chips redondeados para tipo de comprobante
  - tabla con fila anulado en rojo claro
  - voucher estilo comprobante impreso
- Responsive:
  - ajustes de barra y voucher en breakpoints.

### Regla visual recomendada
Mantener intacto el look actual:
- no cambiar paleta
- no cambiar estructura de bloques
- solo conectar datos reales y estados reales.

## 5) Integracion actual con el sistema
## 5.1 Navegacion
`menu_matrix.php` ya expone `modules/egresos/` dentro de FINANZAS.

## 5.2 Caja (modulo real)
`modules/caja/api.php` ya tiene logica robusta de:
- apertura/cierre mensual y diaria
- validacion de caja diaria abierta
- ventas, abonos y devoluciones.

## 5.3 Dashboard administracion
`dashboard/administracion/comunicados.php` ya tiene placeholders:
- Ingresos VS Egresos
- KPI "Gastos (Egresos)".
Actualmente sin datos reales de egresos.

## 6) Base de datos actual (analisis)
### Tablas que SI existen y se relacionan con caja
- `mod_caja_mensual`
- `mod_caja_diaria`
- `mod_caja_auditoria`
- `pos_abonos` (ingresos aplicados a ventas)
- `pos_devoluciones` (salida de dinero por devolucion)

### Tablas de egresos dedicadas
- No existe tabla real de egresos (`pos_egresos` o equivalente).
- No existe auditoria propia de egresos.

### Conclusiones de datos
- El sistema ya tiene base para vincular operaciones a `caja_diaria_id`.
- Falta la capa de persistencia de egresos como entidad propia.

## 7) Viabilidad de implementacion con sistema actual
Si, es totalmente viable implementar egresos reales con la arquitectura actual:
- ya hay control de caja diaria abierta en `modules/caja`.
- ya existe modelo de transacciones monetarias por `caja_diaria_id`.
- los ultimos cambios de caja/perfil conductor no bloquean ni chocan con egresos.

## 8) Logica de negocio recomendada (redonda y coherente)
## 8.1 Regla principal
- No se puede registrar egreso si no hay caja diaria abierta para la empresa.

## 8.2 Vinculacion obligatoria
- Todo egreso debe tener:
  - `id_empresa`
  - `caja_diaria_id`
  - `creado_por`
  - fecha/hora del egreso.

## 8.3 Estados de egreso
- `ACTIVO` (vigente)
- `ANULADO`

## 8.4 Anulacion
- No borrar fisicamente.
- Registrar quien anula, cuando y motivo.
- Mantener trazabilidad para auditoria.

## 8.5 Caso critico: egresos mayores que ingresos del dia
Hay 2 politicas validas:

1. Politica estricta (recomendada para control fuerte):
- bloquear registro si `monto_egreso > saldo_disponible_caja`.

2. Politica flexible (operativa):
- permitir egreso, pero marcar caja en negativo y generar alerta.

### Recomendacion tecnica
Implementar politica estricta por defecto y permitir cambio a flexible por configuracion futura.

### Formula base sugerida de saldo disponible diario
`ingresos_aplicados_dia - devoluciones_dia - egresos_activos_dia`

Nota:
- Como no existe hoy `monto_apertura_caja`, el control parte de movimientos registrados.
- Si luego agregas apertura de efectivo, la formula se amplia sin romper diseno.

## 9) Propuesta de modelo de datos minimo
### Tabla principal sugerida: `pos_egresos`
Campos minimos:
- `id`
- `id_empresa`
- `caja_diaria_id`
- `tipo_comprobante` (`FACTURA`,`BOLETA`,`RECIBO`)
- `serie` nullable
- `numero` nullable
- `referencia` nullable
- `fecha_egreso`
- `monto`
- `beneficiario`
- `documento`
- `concepto`
- `observaciones`
- `estado` (`ACTIVO`,`ANULADO`)
- `creado_por`, `creado_en`
- `anulado_por`, `anulado_en`, `anulado_motivo`

Indices recomendados:
- `(id_empresa, caja_diaria_id, fecha_egreso)`
- `(id_empresa, estado)`
- `(id_empresa, tipo_comprobante, serie, numero)` para busqueda/comprobante.

## 10) Impacto esperado en dashboard (pregunta del card de egresos)
Si, se puede agregar card real de egresos en dashboard admin.

Implicaciones:
- Query adicional para sumar egresos activos por caja diaria/mensual.
- Ajustar widget actual de ingresos para mostrar:
  - ingresos
  - egresos
  - neto
- Costo tecnico bajo/medio, sin tocar `includes` sagrados si se hace en dashboard/modulos.

## 11) Riesgos actuales detectados
- `modules/egresos/index.php` usa `BASE_URL` en CSS del modulo; si hay despliegues no estandar, conviene luego homologar rutas relativas como en otros modulos.
- Existen carpetas espejo `aaa` y `bbb`; puede causar confusion al mantener.
- No hay API ni transacciones de egresos; hoy todo es frontend demo.

## 12) Plan por fases recomendado (sin romper base)
1. Crear tablas SQL de egresos (sin alterar includes).
2. Crear `modules/egresos/api.php` con:
   - estado caja real
   - crear egreso
   - listar/filtrar/paginar
   - anular egreso
3. Conectar `index.php` actual al API conservando UI/estilos.
4. Integrar metricas de egresos en dashboard admin.
5. Ajustar reportes financieros (`reporte_ventas`/nuevo reporte egresos) con datos reales.

## 13) Decision de arquitectura sugerida
Para respetar tu logica de caja:
- El modulo egresos debe ser "hermano" de caja, no reemplazo.
- Caja sigue abriendo/cerrando periodos.
- Egresos solo opera sobre caja diaria ya abierta.
- Toda salida queda auditada y trazable.

---
Documento generado tras analizar:
- `modules/egresos/` (activo + copias)
- `modules/caja/api.php`
- `dashboard/administracion/comunicados.php`
- `dashboard/administracion/funcion_caja_diaria_mensual.php`
- `db/lsistemas_erp_2026.sql`
