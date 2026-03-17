Plan maestro de implementación: Egreso Multicaja
Objetivo funcional

Agregar un nuevo modo de egreso llamado Multicaja dentro del módulo actual de egresos, permitiendo registrar un egreso desde la caja operadora abierta del día, pero consumiendo saldo real desde una o varias cajas diarias de la misma empresa, incluso si esas cajas ya están cerradas. El saldo histórico de cada caja debe actualizarse en función de sus egresos y devoluciones, y el comprobante debe dejar evidencia clara de la caja de registro y de todas las cajas fuente utilizadas. El sistema actual ya calcula saldos con la fórmula ingresos - devoluciones - egresos en el widget principal de caja y ya registra devoluciones contra la caja abierta del momento del movimiento, por lo que el modelo Multicaja es consistente con la lógica existente.

Decisión de diseño
Qué sí se hará

Se implementará dentro del mismo módulo egresos, no en otro módulo separado. El usuario seguirá entrando por sistema/modules/egresos/index.php, verá el mismo listado y el mismo historial, pero al crear un egreso podrá elegir modo Normal o modo Multicaja. El frontend actual ya tiene formulario, modal de distribución y preview/PDF; la mejora encaja mejor extendiendo esa base que creando un módulo paralelo.

Qué no se hará

No se tocará primero sistema/includes/.
No se hará un refactor grande del módulo caja.
No se creará una interfaz aparte llamada “Traspasos”, porque hoy la operación sigue siendo un egreso, no una transferencia entre cajas. El listado, anulación, impresión y detalle ya viven en egresos; duplicarlo te complicaría más de lo que te ayuda.

Fase 0 — Congelar el contrato funcional
Propósito

Dejar fijadas las reglas para no improvisar en mitad de la implementación.

Reglas funcionales definitivas

egr_egresos.id_caja_diaria seguirá siendo la caja de registro.

egr_egreso_fuentes.id_caja_diaria será la caja fuente real de cada porción del dinero.

Un egreso NORMAL podrá seguir sacando dinero solo de la caja diaria abierta actual.

Un egreso MULTICAJA podrá sacar dinero de una o varias cajas diarias de la misma empresa.

El comprobante mostrará:

caja de registro;

tipo de egreso;

fuentes agrupadas por caja fuente;

firmas al final.

Los dashboards y saldos deberán descontar egresos por caja fuente real, no solo por el encabezado del egreso. El frontend actual ya muestra en el comprobante “Fuentes de salida” y el estado de caja ya expone ingresos, devoluciones, egresos y saldo disponible, así que esta fase solo fija reglas sobre estructuras que ya existen.

Verificación

No hay despliegue aún. Solo sirve para que todas las siguientes fases mantengan una sola interpretación.

Archivos

Ninguno todavía.

Tablas

Ninguna todavía.

Fase 1 — Ajuste de base de datos
Propósito

Preparar la base para soportar varias fuentes del mismo medio provenientes de cajas distintas dentro de un mismo egreso, y para identificar formalmente si el egreso es NORMAL o MULTICAJA.

Hallazgo actual

El diseño actual ya usa egr_egresos como encabezado y egr_egreso_fuentes como detalle; además egr_egreso_fuentes ya tiene id_caja_diaria, lo que la vuelve la tabla correcta para guardar la procedencia real del dinero.

Cambio recomendado
Tabla a modificar: egr_egresos

Agregar campo:

ALTER TABLE `egr_egresos`
ADD COLUMN `tipo_egreso` ENUM('NORMAL','MULTICAJA') NOT NULL DEFAULT 'NORMAL'
AFTER `estado`;
Tabla a modificar: egr_egreso_fuentes

La idea es permitir que un mismo id_egreso tenga varias filas EFECTIVO, YAPE, etc., siempre que vengan de cajas distintas. Para eso hay que reemplazar la restricción única actual por una compuesta con caja fuente.

ALTER TABLE `egr_egreso_fuentes`
DROP INDEX `ux_egr_fuente_egreso_key`,
ADD UNIQUE KEY `ux_egr_fuente_egreso_caja_key` (`id_egreso`, `id_caja_diaria`, `fuente_key`);
Índice recomendado adicional
ALTER TABLE `egr_egreso_fuentes`
ADD KEY `idx_egr_fuente_caja_key` (`id_caja_diaria`, `fuente_key`);
Comentario importante

Ese DROP INDEX asume que el índice actual se llama ux_egr_fuente_egreso_key, que es el nombre que vi al revisar el dump. Antes de ejecutar en producción, valida el nombre exacto con SHOW CREATE TABLE egr_egreso_fuentes;. El objetivo no cambia: la llave única debe pasar a ser por egreso + caja + fuente. El dump del proyecto ya contiene las tablas egr_egresos y egr_egreso_fuentes, así que no es necesario crear tablas nuevas para esta mejora.

Verificación manual

Ejecutar SHOW COLUMNS FROM egr_egresos LIKE 'tipo_egreso';

Ejecutar SHOW INDEX FROM egr_egreso_fuentes;

Confirmar que existe:

tipo_egreso

índice único por id_egreso, id_caja_diaria, fuente_key

Archivos modificados

Ninguno.

Archivos creados

Ninguno.

Tablas modificadas

egr_egresos

egr_egreso_fuentes

Fase 2 — Backend base para distinguir Normal vs Multicaja
Propósito

Preparar api.php para entender ambos modos sin romper el flujo actual.

Archivo a modificar

sistema/modules/egresos/api.php

Cambios
1) Extender el payload de creación

Hoy eg_parse_fuentes_payload() consolida por key de fuente y suma montos por medio. Eso sirve para egreso normal, pero en Multicaja perdería la procedencia por caja. Hay que cambiar esa función para que soporte dos formatos:

Formato actual Normal
[
  {"key":"EFECTIVO","monto":100},
  {"key":"YAPE","monto":50}
]
Formato nuevo Multicaja
[
  {"id_caja_diaria":12,"key":"EFECTIVO","monto":100},
  {"id_caja_diaria":18,"key":"EFECTIVO","monto":50},
  {"id_caja_diaria":18,"key":"YAPE","monto":30}
]
2) Nueva lógica de parseo

Separar el parseo en dos niveles:

validación del payload;

agrupación por (id_caja_diaria, key) cuando el modo sea MULTICAJA.

3) Persistencia

Cuando se cree el egreso:

insertar encabezado en egr_egresos con tipo_egreso;

insertar detalle en egr_egreso_fuentes usando:

id_egreso

id_caja_diaria fuente

fuente_key

monto

datos de medio de pago si hoy ya se guardan desde catálogo.

El endpoint actual ya crea egresos dentro del módulo y ya trabaja con fuentes_json, así que no hace falta inventar un endpoint totalmente distinto; conviene extender el contrato actual.

Verificación manual

Crear egreso normal y verificar que se siga guardando igual que antes.

Revisar que en egr_egresos quede tipo_egreso='NORMAL'.

Verificar que el detalle siga insertándose en egr_egreso_fuentes con una sola caja fuente: la abierta actual.

Probar payload Multicaja por Postman o formulario:

dos filas EFECTIVO de cajas distintas;

confirmar que ambas se insertan sin conflicto.

Archivos modificados

sistema/modules/egresos/api.php

Archivos creados

Ninguno todavía.

Tablas modificadas

No nuevas en esta fase; usa las de la fase 1.

Fase 3 — Lectura de cajas fuente disponibles
Propósito

Permitir que el frontend consulte cajas diarias históricas de la misma empresa y vea su saldo real disponible por fuente.

Archivos a modificar

sistema/modules/egresos/api.php

sistema/modules/egresos/finanzas_medios.php

Cambios
1) Nuevo endpoint de listado de cajas fuente

Agregar algo como accion=listar_cajas_fuente, filtrado por empresa del usuario. Debe devolver:

id

codigo

fecha

estado

id_caja_mensual

saldo disponible global

2) Nuevo endpoint de detalle de caja fuente

Agregar algo como accion=detalle_caja_fuente&id_caja_diaria=... y devolver:

datos de la caja

ingresos

devoluciones

egresos

saldo disponible

saldo disponible por fuente (EFECTIVO, YAPE, PLIN, TRANSFERENCIA)

3) Reutilizar helper existente

finanzas_medios.php ya resuelve catálogo de medios y disponibilidad por fuente, y hoy el frontend de egresos muestra ingresos, devoluciones, egresos activos y saldo disponible en el estado de caja. La idea es reutilizar esa base para consultar cualquier caja diaria, no solo la caja abierta.

Recomendación técnica

Aquí sí conviene crear un archivo nuevo, pero solo si ves que api.php está creciendo demasiado. Mi sugerencia moderada:

Archivo nuevo opcional

sistema/modules/egresos/multicaja_service.php

Uso:

helpers de consulta de cajas candidatas

validación de pertenencia a empresa

armado del detalle por fuente

Esto baja riesgo y mantiene api.php manejable.

Verificación manual

Pedir listado de cajas fuente y comprobar que solo salen cajas de la empresa actual.

Elegir una caja cerrada y confirmar que devuelve saldo disponible.

Confirmar que una caja con devoluciones ya trae saldo descontado.

Confirmar que una caja sin saldo disponible no puede ser útil como fuente.

Archivos modificados

sistema/modules/egresos/api.php

sistema/modules/egresos/finanzas_medios.php

Archivos creados

sistema/modules/egresos/multicaja_service.php (opcional pero recomendado)

Tablas modificadas

Ninguna.

Fase 4 — UI: selector de modo y modal Multicaja
Propósito

Agregar la experiencia de uso sin romper el egreso normal.

Archivos a modificar

sistema/modules/egresos/index.php

sistema/modules/egresos/index.js

Archivo nuevo recomendado

sistema/modules/egresos/egresos_multicaja.js

Diseño recomendado
En el formulario principal

Agregar un selector:

Normal

Multicaja

Si está en Normal

Todo sigue como hoy:

botón “Distribuir”

usa solo caja abierta actual

sin cambios mentales para el usuario actual

Si está en Multicaja

El botón “Distribuir” debe cambiar visualmente a algo como:

Seleccionar cajas y distribuir

Y abrir un modal nuevo con esta estructura:

Bloque 1: búsqueda de cajas

filtro por fecha exacta

filtro por rango

búsqueda por código de caja

tabla de resultados

Bloque 2: resumen de cajas elegidas

caja

fecha

saldo disponible

botón quitar

Bloque 3: distribución por caja y fuente

Por cada caja elegida:

cabecera con código y fecha

filas: EFECTIVO, YAPE, PLIN, TRANSFERENCIA

monto editable por cada fila

subtotal por caja

Bloque 4: resumen final

monto del egreso

total distribuido

diferencia

validación de cuadratura exacta

Por qué así

El frontend actual ya fuerza que la suma distribuida cuadre exactamente con el monto del egreso. No conviene romper ese comportamiento; conviene extenderlo al eje caja + fuente. La vista actual también ya renderiza “Fuentes de salida” y usa la información de caja/estado para mostrar saldos.

Verificación manual

En modo Normal, confirmar que no cambió el comportamiento actual.

En modo Multicaja:

buscar caja por fecha;

agregar dos cajas;

distribuir montos por medios;

comprobar que la suma exacta sea obligatoria;

comprobar que no se exceda el disponible por caja y por fuente.

Archivos modificados

sistema/modules/egresos/index.php

sistema/modules/egresos/index.js

Archivos creados

sistema/modules/egresos/egresos_multicaja.js

Tablas modificadas

Ninguna.

Fase 5 — Persistencia segura del egreso Multicaja
Propósito

Guardar correctamente el egreso multicaja y proteger saldos.

Archivos a modificar

sistema/modules/egresos/api.php

sistema/modules/egresos/multicaja_service.php si se crea en fase 3

Cambios
1) Validación por empresa

Cada id_caja_diaria fuente debe pertenecer a la empresa del usuario actual.

2) Validación por caja

Permitir cajas cerradas y abiertas, pero siempre de la misma empresa.

3) Validación por saldo

Antes de insertar:

recalcular disponibilidad real de cada caja y cada fuente;

asegurar que el monto pedido no exceda el saldo disponible.

4) Transacción

Todo debe ir en una sola transacción:

validar;

insertar encabezado;

insertar detalles;

confirmar.

5) Anulación

La anulación de un egreso multicaja no necesita una tabla nueva; debe seguir marcando el encabezado como anulado y el saldo se libera porque los cálculos de disponibilidad ya filtran por egresos activos. El código actual ya trata anulación de egresos dentro del mismo módulo, así que aquí la clave es que toda consulta futura use el detalle por caja fuente real.

Verificación manual

Crear egreso Multicaja con dos cajas y dos fuentes.

Confirmar:

egr_egresos.tipo_egreso = 'MULTICAJA'

egr_egresos.id_caja_diaria = caja operadora abierta

egr_egreso_fuentes contiene varias filas con distintas cajas fuente

Anular el egreso.

Confirmar que la disponibilidad de las cajas fuente vuelve a quedar libre.

Archivos modificados

sistema/modules/egresos/api.php

sistema/modules/egresos/multicaja_service.php si existe

Archivos creados

Ninguno adicional.

Tablas modificadas

Ninguna adicional.

Fase 6 — Detalle, listado y preview
Propósito

Hacer visible el origen real del dinero en el listado y en el detalle.

Archivos a modificar

sistema/modules/egresos/api.php

sistema/modules/egresos/index.js

Cambios
1) Listado

Agregar badge o texto visible:

Normal

Multicaja

El listado actual ya pinta código, fecha, caja, comprobante, beneficiario, monto y estado. Solo hay que sumar una marca visual clara del tipo de egreso.

2) Detalle

eg_select_fuentes() o la función equivalente debe devolver no solo fuente y monto, sino también:

id_caja_diaria

codigo_caja

fecha_caja

3) Render del detalle en JS

Agrupar fuentes por caja:

Caja CD-...

Fecha

tabla interna por medios y montos

Verificación manual

Abrir detalle de egreso normal:

debe verse igual o casi igual

Abrir detalle de egreso multicaja:

debe verse agrupado por cajas fuente

El listado debe identificar el tipo de egreso sin abrir detalle.

Archivos modificados

sistema/modules/egresos/api.php

sistema/modules/egresos/index.js

Archivos creados

Ninguno.

Tablas modificadas

Ninguna.

Fase 7 — Comprobante y PDF
Propósito

Hacer que el comprobante sea auditable y claro.

Archivos a modificar

sistema/modules/egresos/api.php

sistema/modules/egresos/index.js

Cambios
Encabezado del comprobante

Debe mostrar:

código de egreso

tipo de egreso

fecha y hora

comprobante

estado

beneficiario

documento

concepto

caja de registro

Bloque nuevo: cajas fuente

Agrupar en minitablas o tarjetas internas:

Caja CD-20260301 | 01/03/2026

Efectivo — S/ 400.00

Yape — S/ 300.00

Caja CD-20260316 | 16/03/2026

Efectivo — S/ 200.00

Firmas

Al final:

beneficiario

responsable

La vista previa actual ya muestra “RECIBO DE EGRESO”, datos de cabecera y un bloque “FUENTES DE SALIDA”, así que este cambio consiste en pasar de una sola tabla plana a una agrupación por caja fuente.

Verificación manual

Generar preview de egreso normal.

Generar preview de egreso multicaja.

Imprimir PDF y revisar:

claridad de la caja de registro

claridad de cada caja fuente

firmas

Archivos modificados

sistema/modules/egresos/api.php

sistema/modules/egresos/index.js

Archivos creados

Ninguno.

Tablas modificadas

Ninguna.

Fase 8 — Corrección de saldos del módulo egresos
Propósito

Evitar que el egreso multicaja distorsione los saldos.

Archivos a modificar

sistema/modules/egresos/api.php

sistema/modules/egresos/finanzas_medios.php

Problema actual

El sistema ya calcula disponibilidad por fuente usando detalle de egreso, pero el saldo global diario y otros resúmenes pueden seguir descontando por egr_egresos.id_caja_diaria del encabezado. Eso sirve hoy porque encabezado y detalle apuntan a la misma caja; con Multicaja deja de servir.

Cambio

Toda consulta de saldo diario o disponibilidad global que represente “cuánto dinero le queda a esta caja” debe descontar egresos por:

egr_egreso_fuentes.id_caja_diaria = caja consultada

unido a egr_egresos.estado = 'ACTIVO' o equivalente real del proyecto

Verificación manual

Tomar una caja antigua con saldo conocido.

Hacer egreso multicaja desde otra fecha consumiendo esa caja.

Volver a consultar su saldo.

Debe disminuir exactamente el monto usado.

Archivos modificados

sistema/modules/egresos/api.php

sistema/modules/egresos/finanzas_medios.php

Archivos creados

Ninguno.

Tablas modificadas

Ninguna.

Fase 9 — Dashboard de administración
Propósito

Alinear el dashboard con la nueva realidad contable.

Archivos a modificar

sistema/dashboard/administracion/funcion_caja_diaria_mensual.php

sistema/dashboard/administracion/funcion_ingreso_egreso_mensual.php

sistema/dashboard/administracion/funcion_card_ganancia_neta_ultima_caja.php

Cambios
1) Widget principal de caja

Debe seguir mostrando:

Disponible

Ingresado

Egresado

Devuelto

Pero Egresado y Disponible deben calcular egresos por caja fuente real, no por caja operadora del encabezado. El widget actual ya muestra esos cuatro montos, así que aquí cambias consulta, no concepto.

2) Ingresos vs egresos mensual

Debe quedar claro si el “neto” mensual resta también devoluciones. Hoy ya existe un widget mensual separado; conviene dejar consistente el criterio con el widget principal de caja.

3) Ganancia neta última caja

Hoy conviene corregirlo para que tome:

ingresos

menos devoluciones

menos egresos

No solo ingresos menos egresos.

Verificación manual

Abrir dashboard antes y después de un egreso multicaja.

Confirmar que:

baja el saldo de la caja fuente;

no baja erróneamente una caja que no aportó fondos;

devoluciones siguen restando.

Archivos modificados

sistema/dashboard/administracion/funcion_caja_diaria_mensual.php

sistema/dashboard/administracion/funcion_ingreso_egreso_mensual.php

sistema/dashboard/administracion/funcion_card_ganancia_neta_ultima_caja.php

Archivos creados

Ninguno.

Tablas modificadas

Ninguna.

Fase 10 — Pruebas de regresión y cierre
Propósito

Asegurar que el módulo no rompa el flujo actual.

Casos mínimos obligatorios

Venta normal con abono normal.

Devolución parcial.

Devolución total.

Egreso normal con una sola fuente.

Egreso normal redistribuido entre varias fuentes de la caja actual.

Egreso multicaja usando una caja histórica.

Egreso multicaja usando dos cajas históricas.

Anulación de egreso normal.

Anulación de egreso multicaja.

Revisión del dashboard tras egreso multicaja.

El sistema actual ya distingue devoluciones parciales y totales en caja/api_ventas.php, y el frontend actual de egresos ya exige cuadratura exacta por fuentes antes de guardar; esas son las dos áreas que más debes vigilar para no romper compatibilidad.

Archivos

No necesariamente nuevos cambios aquí; es fase de validación.

Orden real recomendado de implementación

No lo haría todo de una sola vez. El orden más seguro es este:

Fase 1 — Base de datos

Fase 2 — Backend distingue NORMAL / MULTICAJA

Fase 3 — Lectura de cajas fuente

Fase 4 — UI Multicaja

Fase 5 — Guardado real

Fase 6 — Detalle y listado

Fase 7 — PDF/comprobante

Fase 8 — Saldos del módulo

Fase 9 — Dashboards

Fase 10 — Regresión

Ese orden minimiza riesgo porque primero dejas lista la base y el backend, luego agregas UI, y recién al final tocas dashboard.

Límite de archivos nuevos

Para mantenerlo ordenado y dentro de tu preferencia, mi propuesta queda así:

Archivos nuevos recomendados

sistema/modules/egresos/multicaja_service.php

sistema/modules/egresos/egresos_multicaja.js

Con eso basta. No hace falta más si quieres mantener el módulo controlado.

Resumen global de cambios esperados
Archivos que se modificarán

sistema/modules/egresos/api.php

sistema/modules/egresos/index.php

sistema/modules/egresos/index.js

sistema/modules/egresos/finanzas_medios.php

sistema/dashboard/administracion/funcion_caja_diaria_mensual.php

sistema/dashboard/administracion/funcion_ingreso_egreso_mensual.php

sistema/dashboard/administracion/funcion_card_ganancia_neta_ultima_caja.php

Archivos que se crearán

sistema/modules/egresos/multicaja_service.php

sistema/modules/egresos/egresos_multicaja.js

Tablas que se modificarán

egr_egresos

egr_egreso_fuentes

SQL de alter table
ALTER TABLE `egr_egresos`
ADD COLUMN `tipo_egreso` ENUM('NORMAL','MULTICAJA') NOT NULL DEFAULT 'NORMAL'
AFTER `estado`;
ALTER TABLE `egr_egreso_fuentes`
DROP INDEX `ux_egr_fuente_egreso_key`,
ADD UNIQUE KEY `ux_egr_fuente_egreso_caja_key` (`id_egreso`, `id_caja_diaria`, `fuente_key`);
ALTER TABLE `egr_egreso_fuentes`
ADD KEY `idx_egr_fuente_caja_key` (`id_caja_diaria`, `fuente_key`);
Tablas nuevas

Ninguna.

Recomendación final de ejecución

La siguiente tarea ideal ya no es seguir diseñando, sino entrar a la Fase 1 + Fase 2 con material ejecutable:
te preparo el siguiente mensaje con:

archivos a modificar;

SQL exacto listo para correr;

parche backend inicial en api.php;

y pasos de prueba manual para dejar listo el esqueleto NORMAL/MULTICAJA.
