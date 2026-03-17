# Plan maestro de implementación: Egreso Multicaja

## Estado del documento
- Archivo objetivo del repo: `sistema/modules/Plan_maestro_de_implementacion_Egreso_Multicaja.md`
- Alcance: diseño funcional, técnico y de despliegue por fases
- Criterio principal: **agregar Multicaja sin romper ventas, devoluciones, egresos históricos ni dashboards**

---

## 1. Objetivo funcional

Implementar un nuevo modo de egreso llamado **Multicaja** dentro del módulo actual `sistema/modules/egresos`, permitiendo registrar un egreso desde la **caja operadora abierta del día**, pero consumiendo saldo real desde **una o varias cajas diarias** de la **misma empresa**, incluso si esas cajas ya están cerradas.

El resultado esperado es este:

- el egreso se sigue registrando en el módulo actual de egresos;
- el comprobante deja claro **dónde se registró** el egreso y **de qué cajas salió realmente el dinero**;
- los saldos históricos se actualizan de forma real;
- las devoluciones siguen restando dinero a la caja del movimiento, como ya hace hoy el sistema;
- los egresos normales siguen funcionando exactamente como hoy.

---

## 2. Hallazgos reales del sistema actual

### 2.1. Ingresos de caja
Hoy los ingresos de caja no se toman desde `pos_ventas.total`, sino desde **abonos aplicados**:

- `sistema/modules/egresos/api.php` en `eg_saldo_diaria()` suma `pos_abono_aplicaciones.monto_aplicado` por `pos_abonos.caja_diaria_id`.
- `sistema/modules/egresos/finanzas_medios.php` calcula ingresos por medio desde `pos_abonos` + `pos_abono_aplicaciones`.
- `sistema/dashboard/administracion/funcion_caja_diaria_mensual.php` también usa abonos aplicados para el widget principal de caja.

### 2.2. Devoluciones
Hoy las devoluciones **sí restan caja** y se registran contra la **caja abierta del momento de la devolución**:

- `sistema/modules/caja/api_ventas.php`
- tabla `pos_devoluciones`
- impacto monetario por `caja_diaria_id`

Eso ya coincide con la idea de “saldo vivo” por caja.

### 2.3. Egreso normal actual
Hoy el módulo `egresos` trabaja así:

- exige **caja diaria abierta** y **caja mensual abierta** para registrar;
- usa `fuentes_json` y `eg_parse_fuentes_payload()`;
- el frontend obliga a distribuir el egreso por fuente antes de guardar;
- el detalle se guarda en `egr_egreso_fuentes`;
- el encabezado se guarda en `egr_egresos`.

### 2.4. Limitación actual clave
Hoy `eg_parse_fuentes_payload()` agrupa por `key` de fuente (`EFECTIVO`, `YAPE`, `PLIN`, `TRANSFERENCIA`). Eso sirve para un egreso normal, pero no para Multicaja porque perdería la caja origen.

### 2.5. Estructura actual útil para Multicaja
La tabla `egr_egreso_fuentes` **ya tiene** `id_caja_diaria`, así que ya existe la base correcta para registrar procedencia real del dinero. El problema actual no es de concepto, sino de restricción y de consultas.

### 2.6. Restricción actual que bloquea Multicaja
La tabla `egr_egreso_fuentes` tiene hoy la llave única:

```sql
UNIQUE KEY `ux_egr_fuente_egreso_key` (`id_egreso`,`fuente_key`)
```

Eso impide tener, dentro del mismo egreso, dos filas `EFECTIVO` de cajas distintas.

### 2.7. Inconsistencia actual a corregir
Hoy el sistema mezcla dos enfoques:

- el saldo global diario en `eg_saldo_diaria()` descuenta desde `egr_egresos.id_caja_diaria`;
- la disponibilidad por medio en `fin_disponible_por_fuente_diaria()` descuenta desde `egr_egreso_fuentes.id_caja_diaria` unido a egresos activos.

Con egreso normal no se nota, porque ambas cajas coinciden. Con Multicaja sí se rompería si no se corrige.

---

## 3. Decisiones de diseño

## 3.1. Qué sí se hará

### Se implementará dentro del mismo módulo `egresos`
No se creará otro módulo aparte. El usuario seguirá entrando por:

- `sistema/modules/egresos/index.php`
- `sistema/modules/egresos/index.js`
- `sistema/modules/egresos/api.php`

### Habrá dos modos de egreso

- `NORMAL`
- `MULTICAJA`

### El encabezado y el detalle tendrán roles distintos

- `egr_egresos.id_caja_diaria` = **caja de registro**
- `egr_egreso_fuentes.id_caja_diaria` = **caja fuente real**

### El comprobante mostrará ambos conceptos

- caja de registro
- tipo de egreso
- cajas fuente agrupadas
- fuentes por caja
- firmas

## 3.2. Qué no se hará

- no se tocará primero `sistema/includes/`;
- no se hará refactor grande del módulo `caja`;
- no se creará un módulo aparte llamado “Traspasos”;
- no se migrarán egresos viejos a otra estructura;
- no se reinterpretarán datos históricos con `UPDATE` masivos.

---

## 4. Compatibilidad y principio de seguridad

Este punto es obligatorio.

### 4.1. Qué no debe romperse

- ventas históricas
- devoluciones parciales
- devoluciones totales
- egresos normales existentes
- anulación de egresos existentes
- widget principal de caja
- gráficos y cards del dashboard

### 4.2. Regla de compatibilidad
Multicaja debe aplicarse **solo a registros nuevos**.

Los egresos existentes deben seguir siendo válidos como `NORMAL`.

### 4.3. Principio operativo
**No se modifican datos históricos.**
Solo se agregan:

- un nuevo tipo de egreso;
- una nueva forma de registrar el detalle por caja fuente;
- nuevas consultas y vistas para leer correctamente el saldo.

---

## 5. Riesgos reales y cómo controlarlos

## 5.1. Riesgo: saldos mal calculados
Puede ocurrir si se guarda el Multicaja por detalle, pero el saldo global sigue leyendo solo el encabezado `egr_egresos.id_caja_diaria`.

### Control
Actualizar en la misma salida:

- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/finanzas_medios.php`
- `sistema/dashboard/administracion/funcion_caja_diaria_mensual.php`
- `sistema/dashboard/administracion/funcion_ingreso_egreso_mensual.php`
- `sistema/dashboard/administracion/funcion_card_ganancia_neta_ultima_caja.php`

## 5.2. Riesgo: romper egresos históricos
Puede ocurrir si se reescriben filas viejas o si se cambia la lógica sin mantener `NORMAL` como predeterminado.

### Control
- agregar `tipo_egreso` con default `NORMAL`;
- no tocar filas viejas;
- mantener el flujo actual de egreso normal intacto.

## 5.3. Riesgo: permitir fuentes duplicadas inconsistentes
Puede ocurrir si se deja la llave actual por `id_egreso + fuente_key`.

### Control
Cambiarla a:

```sql
(id_egreso, id_caja_diaria, fuente_key)
```

## 5.4. Riesgo: PDF y detalle engañosos
Puede ocurrir si el comprobante solo muestra medios y montos, pero no la caja fuente.

### Control
Agrupar visualmente por caja fuente en preview y PDF.

## 5.5. Riesgo: desplegar a medias
Puede ocurrir si primero se habilita guardar Multicaja y después se actualizan dashboards.

### Control
No activar Multicaja en producción hasta haber completado:

- base de datos;
- backend de lectura;
- persistencia;
- consultas de saldo;
- dashboard principal.

---

## 6. Auditoría previa obligatoria antes de tocar PHP

Ejecutar esto primero en la base real.

### 6.1. Verificar si existe algún egreso sin detalle de fuentes

```sql
SELECT e.id
FROM egr_egresos e
LEFT JOIN egr_egreso_fuentes f ON f.id_egreso = e.id
WHERE f.id IS NULL
LIMIT 20;
```

### 6.2. Verificar si hay duplicados inesperados por egreso y fuente

```sql
SELECT id_egreso, fuente_key, COUNT(*) AS repeticiones
FROM egr_egreso_fuentes
GROUP BY id_egreso, fuente_key
HAVING COUNT(*) > 1;
```

### 6.3. Confirmar nombre exacto del índice actual

```sql
SHOW CREATE TABLE egr_egreso_fuentes;
```

### 6.4. Confirmar estructura actual de egresos

```sql
SHOW CREATE TABLE egr_egresos;
SHOW CREATE TABLE egr_egreso_fuentes;
```

## Criterio de avance
Solo avanzar si:

- no hay egresos huérfanos sin detalle;
- no hay duplicados raros incompatibles con la estructura actual;
- el nombre del índice a reemplazar está confirmado.

---

## 7. Fases de implementación

# Fase 0 — Congelar contrato funcional

## Propósito
Definir la interpretación única del modelo antes de tocar base o código.

## Resultado esperado
- todo egreso tiene una caja de registro;
- todo egreso puede tener una o varias cajas fuente;
- Multicaja es un egreso, no una transferencia.

## Archivos a modificar
Ninguno.

## Tablas a modificar
Ninguna.

## Verificación
Aprobación funcional del diseño.

---

# Fase 1 — Ajuste de base de datos

## Propósito
Preparar la BD para soportar egreso Multicaja sin romper egresos históricos.

## Tablas a modificar
- `egr_egresos`
- `egr_egreso_fuentes`

## SQL exacto recomendado

### 1. Agregar `tipo_egreso`

```sql
ALTER TABLE `egr_egresos`
ADD COLUMN `tipo_egreso` ENUM('NORMAL','MULTICAJA') NOT NULL DEFAULT 'NORMAL'
AFTER `estado`;
```

### 2. Reemplazar la llave única de fuentes

> Antes de correr esto, validar el nombre exacto con `SHOW CREATE TABLE egr_egreso_fuentes;`

```sql
ALTER TABLE `egr_egreso_fuentes`
DROP INDEX `ux_egr_fuente_egreso_key`,
ADD UNIQUE KEY `ux_egr_fuente_egreso_caja_key` (`id_egreso`, `id_caja_diaria`, `fuente_key`);
```

### 3. Índice auxiliar recomendado

```sql
ALTER TABLE `egr_egreso_fuentes`
ADD KEY `idx_egr_fuente_caja_key` (`id_caja_diaria`, `fuente_key`);
```

## Riesgo
Bajo, siempre que primero se valide el nombre del índice actual.

## Verificación manual
```sql
SHOW COLUMNS FROM egr_egresos LIKE 'tipo_egreso';
SHOW INDEX FROM egr_egreso_fuentes;
```

## Archivos modificados
Ninguno.

## Archivos creados
Ninguno.

---

# Fase 2 — Backend compatible: distinguir Normal vs Multicaja

## Propósito
Permitir que `api.php` entienda ambos modos sin romper el flujo actual.

## Archivo a modificar
- `sistema/modules/egresos/api.php`

## Cambios

### 2.1. Ampliar payload de fuentes
Hoy el frontend manda algo como:

```json
[
  {"key":"EFECTIVO","monto":100},
  {"key":"YAPE","monto":50}
]
```

Para Multicaja deberá soportarse también:

```json
[
  {"id_caja_diaria":12,"key":"EFECTIVO","monto":100},
  {"id_caja_diaria":18,"key":"EFECTIVO","monto":50},
  {"id_caja_diaria":18,"key":"YAPE","monto":30}
]
```

### 2.2. Cambiar `eg_parse_fuentes_payload()`
Debe dejar de colapsar por solo `key` cuando el modo sea `MULTICAJA`.

### 2.3. Insertar `tipo_egreso`
Al crear el encabezado en `egr_egresos`, guardar:

- `NORMAL`
- `MULTICAJA`

## Riesgo
Medio. Si se modifica mal esta fase, el egreso normal puede fallar al guardar.

## Control
Mantener compatibilidad total con el payload actual.

## Verificación manual
1. Registrar egreso normal.
2. Confirmar que guarda igual que antes.
3. Confirmar que `tipo_egreso='NORMAL'`.
4. Confirmar que la distribución normal sigue usando una sola caja fuente: la caja abierta actual.

## Archivos modificados
- `sistema/modules/egresos/api.php`

## Archivos creados
Ninguno todavía.

---

# Fase 3 — Backend de lectura de cajas fuente

## Propósito
Poder consultar cajas históricas de la misma empresa y conocer su saldo disponible real.

## Archivos a modificar
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/finanzas_medios.php`

## Archivo nuevo recomendado
- `sistema/modules/egresos/multicaja_service.php`

## Cambios

### 3.1. Nuevo endpoint: listar cajas fuente
Debe devolver por empresa actual:

- `id`
- `codigo`
- `fecha`
- `estado`
- `id_caja_mensual`
- `saldo_disponible`

### 3.2. Nuevo endpoint: detalle de caja fuente
Debe devolver:

- datos de la caja
- ingresos
- devoluciones
- egresos
- saldo disponible
- saldo por medio

### 3.3. Reutilizar `fin_disponible_por_fuente_diaria()`
Esa función ya descuenta egresos activos por `egr_egreso_fuentes.id_caja_diaria`, así que es la base correcta para leer caja fuente real.

## Riesgo
Bajo a medio.

## Verificación manual
1. Listar cajas de la empresa actual.
2. Consultar una caja cerrada.
3. Confirmar que devuelve saldo real.
4. Confirmar que una caja sin saldo no aparece como útil.

## Archivos modificados
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/finanzas_medios.php`

## Archivos creados
- `sistema/modules/egresos/multicaja_service.php`

---

# Fase 4 — UI: modo de egreso y selector Multicaja

## Propósito
Agregar la experiencia de uso sin confundir al usuario ni romper el flujo actual.

## Archivos a modificar
- `sistema/modules/egresos/index.php`
- `sistema/modules/egresos/index.js`

## Archivo nuevo recomendado
- `sistema/modules/egresos/egresos_multicaja.js`

## Decisión UI
Se mantiene el mismo módulo de egresos y el mismo listado.

### Nuevo selector visible en el formulario
- `Normal`
- `Multicaja`

### Si el modo es `Normal`
Todo sigue igual.

- mismo botón `egBtnDistribuir`
- mismo modal `egFuentesModal`
- mismas validaciones actuales

### Si el modo es `Multicaja`
El botón de distribución abrirá un flujo especial.

## Diseño recomendado del nuevo flujo

### Bloque 1: búsqueda de cajas fuente
- filtro por fecha exacta
- filtro por rango
- búsqueda por código
- tabla de resultados

### Bloque 2: cajas seleccionadas
- código
- fecha
- estado
- saldo disponible
- botón quitar

### Bloque 3: distribución por caja y fuente
Por cada caja elegida:

- cabecera con código y fecha
- filas para `EFECTIVO`, `YAPE`, `PLIN`, `TRANSFERENCIA`
- monto editable por fila
- subtotal por caja

### Bloque 4: resumen total
- monto del egreso
- total asignado
- diferencia
- validación exacta

## Riesgo
Medio. Si se mezcla demasiado con la lógica actual, `index.js` puede volverse más frágil.

## Control
Sacar la lógica Multicaja a un archivo separado:

- `sistema/modules/egresos/egresos_multicaja.js`

## Verificación manual
1. Probar modo `Normal` y confirmar que no cambió.
2. Probar modo `Multicaja`.
3. Buscar dos cajas.
4. Distribuir por medios.
5. Confirmar cuadratura exacta.
6. Confirmar que no se excede saldo por caja ni por medio.

## Archivos modificados
- `sistema/modules/egresos/index.php`
- `sistema/modules/egresos/index.js`

## Archivos creados
- `sistema/modules/egresos/egresos_multicaja.js`

---

# Fase 5 — Guardado real de egreso Multicaja

## Propósito
Persistir correctamente el egreso multicaja sin afectar el histórico.

## Archivos a modificar
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/multicaja_service.php`

## Cambios

### 5.1. Validar empresa
Cada `id_caja_diaria` fuente debe pertenecer a la empresa actual.

### 5.2. Validar saldo por caja y por fuente
Recalcular dentro de transacción.

### 5.3. Mantener caja abierta como operadora
El egreso solo se puede registrar si hay caja diaria abierta actual.

### 5.4. Guardado final
- `egr_egresos.id_caja_diaria` = caja operadora
- `egr_egreso_fuentes.id_caja_diaria` = caja fuente real

### 5.5. Anulación
La anulación debe seguir funcionando por estado del encabezado. No se requiere tabla nueva.

## Riesgo
Alto si esta fase se despliega antes de actualizar saldos y dashboard.

## Control
Esta fase solo se libera junto con Fase 8 y Fase 9.

## Verificación manual
1. Registrar Multicaja con 2 cajas.
2. Confirmar filas en `egr_egreso_fuentes`.
3. Anular egreso.
4. Confirmar liberación del saldo.

## Archivos modificados
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/multicaja_service.php`

## Archivos creados
Ninguno adicional.

---

# Fase 6 — Listado, detalle y vista previa

## Propósito
Hacer visible la realidad del egreso multicaja en UI.

## Archivos a modificar
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/index.js`

## Cambios

### 6.1. Listado
Agregar badge o columna para tipo de egreso.

### 6.2. Detalle
`eg_select_fuentes()` debe devolver además:

- `id_caja_diaria`
- `codigo_caja`
- `fecha_caja`

### 6.3. Preview
Agrupar fuentes por caja en la vista previa.

## Riesgo
Bajo.

## Verificación manual
1. Abrir detalle de egreso normal.
2. Abrir detalle de egreso multicaja.
3. Confirmar que se agrupa por caja fuente.

## Archivos modificados
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/index.js`

## Archivos creados
Ninguno.

---

# Fase 7 — Comprobante y PDF

## Propósito
Dejar trazabilidad sólida en el comprobante.

## Archivos a modificar
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/index.js`

## Cambios

### 7.1. Cabecera
Mostrar:
- código
- fecha y hora
- estado
- tipo de egreso
- caja de registro

### 7.2. Fuentes agrupadas por caja
Ejemplo esperado:

- Caja `CD-20260301` | `01/03/2026`
  - Efectivo — S/ 400.00
  - Yape — S/ 300.00
- Caja `CD-20260316` | `16/03/2026`
  - Efectivo — S/ 200.00

### 7.3. Firmas
Mantener bloque de firmas al final.

## Riesgo
Bajo.

## Verificación manual
1. Preview de egreso normal.
2. Preview de egreso multicaja.
3. PDF oficial.
4. Revisar claridad visual y consistencia de datos.

## Archivos modificados
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/index.js`

## Archivos creados
Ninguno.

---

# Fase 8 — Corrección de saldos del módulo egresos

## Propósito
Alinear saldo global con saldo real por caja fuente.

## Archivos a modificar
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/finanzas_medios.php`

## Cambio principal
`eg_saldo_diaria()` ya no debe calcular egresos solo desde:

```sql
egr_egresos.id_caja_diaria
```

Debe usar el detalle por caja fuente real, unido a egresos activos.

## Efecto esperado
Si el 29/03 se registra un Multicaja usando dinero de la caja del 01/03, entonces:

- la caja del 01/03 baja su saldo;
- la caja del 29/03 no baja si no aportó dinero.

## Riesgo
Alto si no se corrige antes del despliegue.

## Verificación manual
1. Medir saldo de una caja antigua.
2. Usarla en un egreso Multicaja desde otra fecha.
3. Confirmar que baja el saldo de la caja fuente.

## Archivos modificados
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/finanzas_medios.php`

## Archivos creados
Ninguno.

---

# Fase 9 — Dashboards de administración

## Propósito
Evitar que el dashboard muestre saldos o netos falsos.

## Archivos a modificar
- `sistema/dashboard/administracion/funcion_caja_diaria_mensual.php`
- `sistema/dashboard/administracion/funcion_ingreso_egreso_mensual.php`
- `sistema/dashboard/administracion/funcion_card_ganancia_neta_ultima_caja.php`

## Cambios

### 9.1. Widget principal de caja
Debe seguir mostrando:
- disponible
- ingresado
- egresado
- devuelto

Pero el egreso deberá descontarse por caja fuente real.

### 9.2. Gráfico mensual
Corregir su lectura para que no dependa solo de `egr_egresos.id_caja_diaria`.

### 9.3. Card de ganancia neta
Debe considerar:

```text
ingresos - devoluciones - egresos
```

y no solo:

```text
ingresos - egresos
```

## Riesgo
Medio.

## Verificación manual
1. Registrar Multicaja.
2. Abrir dashboard.
3. Confirmar que baja la caja correcta.
4. Confirmar que devoluciones siguen restando.

## Archivos modificados
- `sistema/dashboard/administracion/funcion_caja_diaria_mensual.php`
- `sistema/dashboard/administracion/funcion_ingreso_egreso_mensual.php`
- `sistema/dashboard/administracion/funcion_card_ganancia_neta_ultima_caja.php`

## Archivos creados
Ninguno.

---

# Fase 10 — Regresión y salida controlada

## Propósito
Confirmar que lo nuevo no dañó lo actual.

## Casos mínimos obligatorios
1. Venta con abono.
2. Venta pendiente con pago posterior.
3. Devolución parcial.
4. Devolución total.
5. Egreso normal con una sola fuente.
6. Egreso normal redistribuido entre varias fuentes.
7. Egreso Multicaja con una caja histórica.
8. Egreso Multicaja con varias cajas.
9. Anulación de egreso normal.
10. Anulación de egreso Multicaja.
11. Dashboard antes y después del egreso Multicaja.

## Riesgo
Medio.

## Verificación
Aprobación funcional completa antes de producción.

---

## 8. Orden real de implementación

Orden recomendado y obligatorio:

1. Auditoría previa SQL
2. Fase 1 — Base de datos
3. Fase 2 — Backend compatible
4. Fase 3 — Lectura de cajas fuente
5. Fase 4 — UI Multicaja
6. Fase 5 — Guardado real
7. Fase 6 — Detalle y preview
8. Fase 7 — PDF
9. Fase 8 — Saldos del módulo
10. Fase 9 — Dashboards
11. Fase 10 — Regresión

**No habilitar producción de Multicaja antes de completar Fase 8 y Fase 9.**

---

## 9. Límite de archivos nuevos

Para mantener el módulo ordenado y sin crecer demasiado, el límite recomendado es este:

### Archivos nuevos
1. `sistema/modules/egresos/multicaja_service.php`
2. `sistema/modules/egresos/egresos_multicaja.js`

No es necesario crear más archivos salvo que, durante la implementación, `api.php` quede demasiado cargado y se justifique un helper adicional pequeño.

---

## 10. Resumen global de cambios

## Archivos que se modificarán
- `sistema/modules/egresos/api.php`
- `sistema/modules/egresos/index.php`
- `sistema/modules/egresos/index.js`
- `sistema/modules/egresos/finanzas_medios.php`
- `sistema/dashboard/administracion/funcion_caja_diaria_mensual.php`
- `sistema/dashboard/administracion/funcion_ingreso_egreso_mensual.php`
- `sistema/dashboard/administracion/funcion_card_ganancia_neta_ultima_caja.php`

## Archivos que se crearán
- `sistema/modules/egresos/multicaja_service.php`
- `sistema/modules/egresos/egresos_multicaja.js`

## Tablas que se modificarán
- `egr_egresos`
- `egr_egreso_fuentes`

## Tablas nuevas
Ninguna.

---

## 11. SQL consolidado

### Alter de `egr_egresos`

```sql
ALTER TABLE `egr_egresos`
ADD COLUMN `tipo_egreso` ENUM('NORMAL','MULTICAJA') NOT NULL DEFAULT 'NORMAL'
AFTER `estado`;
```

### Alter de `egr_egreso_fuentes`

```sql
ALTER TABLE `egr_egreso_fuentes`
DROP INDEX `ux_egr_fuente_egreso_key`,
ADD UNIQUE KEY `ux_egr_fuente_egreso_caja_key` (`id_egreso`, `id_caja_diaria`, `fuente_key`);
```

```sql
ALTER TABLE `egr_egreso_fuentes`
ADD KEY `idx_egr_fuente_caja_key` (`id_caja_diaria`, `fuente_key`);
```

---

## 12. Criterio de “Go / No Go” para producción

## Se puede avanzar a producción si:
- el egreso normal sigue intacto;
- el Multicaja guarda bien;
- la anulación libera saldo;
- el widget de caja muestra saldo correcto;
- el PDF muestra caja de registro y cajas fuente;
- no hay diferencias entre saldo esperado y saldo mostrado.

## No se debe desplegar si:
- el dashboard sigue descontando por encabezado y no por fuente;
- el egreso normal cambió de comportamiento;
- el PDF no distingue caja de registro y caja fuente;
- los saldos de cajas históricas quedan cruzados.

---

## 13. Recomendación final

La implementación es **viable** y **coherente con la lógica actual del sistema**, siempre que se respete esta regla:

> **Multicaja se agrega como una extensión de egresos, no como una reescritura del sistema de caja.**

La tabla correcta para representar la procedencia real del dinero ya existe: `egr_egreso_fuentes`.

Lo que se debe hacer bien es:

- permitir varias cajas fuente por egreso;
- guardar el tipo de egreso;
- leer saldos por caja fuente real;
- reflejarlo correctamente en UI, PDF y dashboard.

Con ese enfoque, el cambio queda robusto, entendible, compatible y mantenible.
