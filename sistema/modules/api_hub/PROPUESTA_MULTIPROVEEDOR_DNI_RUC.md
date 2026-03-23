# Propuesta Tecnica: ApiHub Multiproveedor (APISPERU + DECOLECTA + JSON.PE)

## 1) Objetivo de la mejora

Implementar consultas DNI/RUC con respaldo entre proveedores, manteniendo un formato unico de respuesta para el frontend y trazabilidad de que proveedor resolvio (o fallo) cada consulta.

Objetivo funcional solicitado:

- Orden de fallback: `apisperu -> decolecta -> jsonpe`
- Si un proveedor falla o no encuentra, probar el siguiente
- Configurar multiples tokens por proveedor en `includes/config.php` (hardcodeados por ahora)
- Registrar en ApiHub desde que proveedor se resolvio la consulta
- Registrar cuando hubo fallos previos y luego exito en otro proveedor

## 2) Estado actual (codigo existente)

Rutas revisadas:

- `sistema/modules/api_hub/api.php`
- `sistema/modules/api_hub/apisperu_client.php`
- `sistema/modules/api_hub/usage_repo.php`
- `sistema/modules/api_hub/sql/mod_api_hub_uso_mensual.sql`
- `sistema/modules/api_hub/index.php`
- `sistema/modules/api_hub/api_hub.js`
- `sistema/includes/config.php`
- `sistema/modules/caja/index.php`

Hallazgos actuales:

- ApiHub solo usa APISPERU hoy.
- El frontend de Caja ya consume solo ApiHub interno (`/modules/api_hub/api.php`), que esta bien.
- Ya existe validacion de formato:
  - DNI: 8 digitos
  - RUC: 11 digitos
- Ya existe logging mensual por empresa (`mod_api_hub_uso_mensual`) con:
  - `dni_ok`, `dni_fail`, `ruc_ok`, `ruc_fail`
  - `ultima_tipo`, `ultima_estado`, `ultima_mensaje`, `ultima_consulta_at`
- Aun no existe columna/campo para proveedor usado.
- Aun no existe registro detallado de intentos por consulta (solo agregado mensual).

## 3) Propuesta de diseno (sin implementar aun)

### 3.1 Contrato interno unificado (se mantiene)

El frontend no debe conocer proveedores. ApiHub debe seguir entregando un formato estable para Caja.

DNI normalizado:

```json
{
  "dni": "46027897",
  "nombres": "ROXANA KARINA",
  "apellido_paterno": "DELGADO",
  "apellido_materno": "HUAMANI",
  "nombre_completo": "DELGADO HUAMANI ROXANA KARINA"
}
```

RUC normalizado:

```json
{
  "ruc": "20601030013",
  "razon_social": "REXTIE S.A.C.",
  "estado": "ACTIVO",
  "condicion": "HABIDO",
  "direccion": "..."
}
```

Respuesta ApiHub (mantener compatibilidad y ampliar metadatos):

```json
{
  "ok": true,
  "tipo": "DNI",
  "data": { "...": "..." },
  "provider": {
    "name": "decolecta",
    "status": 200,
    "message": "",
    "token_label": "decolecta_main",
    "fallback_used": true
  }
}
```

### 3.2 Motor de fallback

Flujo propuesto para cada consulta:

1. Validar documento (DNI/RUC).
2. Cargar orden de proveedores configurado.
3. Para cada proveedor:
   1. Intentar sus tokens en orden.
   2. Si responde exito con datos validos, terminar.
   3. Si falla, registrar intento y continuar.
4. Si todos fallan, responder error normalizado.

Regla clave solicitada: si APISPERU responde "no encontrado", igual debe probar DECOLECTA y JSON.PE antes de cerrar como no encontrado final.

### 3.3 Soporte de multiples tokens por proveedor

Se propone ampliar `sistema/includes/config.php` asi:

```php
'api_hub' => [
  'providers' => [
    'apisperu' => [
      'enabled' => true,
      'base_url' => 'https://dniruc.apisperu.com/api/v1',
      'timeout_seconds' => 12,
      'connect_timeout_seconds' => 6,
      'tokens' => [
        ['label' => 'apisperu_main', 'value' => 'TOKEN_1'],
        ['label' => 'apisperu_backup', 'value' => 'TOKEN_2'],
      ],
    ],
    'decolecta' => [
      'enabled' => true,
      'base_url' => 'https://api.decolecta.com/v1',
      'timeout_seconds' => 12,
      'connect_timeout_seconds' => 6,
      'tokens' => [
        ['label' => 'decolecta_main', 'value' => 'TOKEN_1'],
      ],
    ],
    'jsonpe' => [
      'enabled' => true,
      'base_url' => 'https://api.json.pe',
      'timeout_seconds' => 12,
      'connect_timeout_seconds' => 6,
      'tokens' => [
        ['label' => 'jsonpe_main', 'value' => 'TOKEN_1'],
      ],
    ],
  ],
  'fallback_order' => [
    'dni' => ['apisperu', 'decolecta', 'jsonpe'],
    'ruc' => ['apisperu', 'decolecta', 'jsonpe'],
  ],
]
```

Nota: el label del token si se puede mostrar en logs internos. El valor real del token nunca debe salir en respuesta.

### 3.4 Adaptadores por proveedor (normalizacion)

Cada proveedor debe tener su adaptador propio para desacoplar diferencias HTTP y formato:

- APISPERU:
  - DNI/RUC: GET actual (como ya esta)
- DECOLECTA:
  - DNI: GET `/reniec/dni?numero=...`
  - RUC: GET `/sunat/ruc?numero=...`
- JSON.PE:
  - DNI: POST `/api/dni` body `{"dni":"..."}`
  - RUC: POST `/api/ruc` body `{"ruc":"..."}`

Mapeo minimo propuesto:

- DNI:
  - APISPERU: `nombres`, `apellidoPaterno`, `apellidoMaterno`
  - DECOLECTA: `first_name`, `first_last_name`, `second_last_name`
  - JSON.PE: `data.nombres`, `data.apellido_paterno`, `data.apellido_materno`
- RUC:
  - APISPERU: `razonSocial`
  - DECOLECTA: `razon_social`
  - JSON.PE: `data.nombre_o_razon_social`

### 3.5 Registro y trazabilidad

Para cubrir tu requerimiento de "que quede registro de por cual paso", con la tabla mensual actual no alcanza por si sola.

Se recomiendan 2 niveles:

1. Mantener agregado mensual (tabla actual) para KPI rapido.
2. Agregar log detallado por consulta (nueva tabla), por ejemplo:
   - `tipo_documento`, `numero_documento_masked`
   - `estado_final` (OK/FAIL)
   - `proveedor_final`
   - `token_label_final`
   - `fallback_used` (0/1)
   - `attempt_trace_json` (intentos con status/message por proveedor)
   - `empresa_id`, `user_id`, `created_at`

Y en la tabla mensual actual, opcionalmente agregar:

- `ultima_proveedor`
- `ultima_token_label`
- `ultima_fallback` (0/1)

### 3.6 Cambios esperados en dashboard ApiHub

En `modules/api_hub/index.php` + `api_hub.js`:

- Mostrar al menos "Ultimo proveedor" por empresa/mes.
- Mostrar indicador de fallback usado en la ultima consulta.
- Opcional: resumen por proveedor (OK/FAIL por proveedor en el periodo).

### 3.7 Politica de errores

Clasificacion sugerida:

- `invalid_document`: no countable, no consulta externa
- `not_configured`: no countable
- `service_unavailable`: countable
- `not_found`:
  - countable
  - solo devolver final cuando todos los proveedores ya fueron probados sin exito

### 3.8 Consideraciones operativas

- Costo y limites: 3 proveedores + multiples tokens puede incrementar consumo.
- Latencia: fallback secuencial puede alargar tiempo en fallos; ajustar timeouts.
- Seguridad: tokens en `config.php` exponen riesgo si el repo se comparte.
- Observabilidad: guardar `provider_status/provider_message` y tiempos por intento ayuda a soporte.

## 4) Riesgos y mitigaciones

- Riesgo: respuestas parciales o campos distintos por proveedor.
  - Mitigacion: validacion estricta del payload normalizado antes de marcar exito.
- Riesgo: errores transitorios de red.
  - Mitigacion: timeouts cortos + fallback.
- Riesgo: token invalido en un proveedor.
  - Mitigacion: rotacion por lista de tokens y registro de token label fallido.
- Riesgo: cambios de contrato del proveedor.
  - Mitigacion: adaptadores encapsulados por proveedor y logs de parseo.

## 5) Preguntas de definicion (antes de implementar)

1. Confirmas el orden fijo para ambos tipos (`DNI` y `RUC`): `apisperu -> decolecta -> jsonpe`?
2. Si un proveedor devuelve `not_found`, confirmas que SIEMPRE debemos continuar al siguiente proveedor?
3. Si un proveedor devuelve datos incompletos (ejemplo: sin apellido materno), lo tomamos como exito o como fallo para seguir fallback?
4. Para RUC, cuales campos quieres persistir siempre en salida normalizada ademas de `razon_social`? (estado, condicion, direccion, distrito, provincia, departamento)
5. Quieres registrar cada intento en una tabla nueva de detalle? (recomendado para auditoria real)
6. En el dashboard de ApiHub, quieres ver:
   - solo proveedor final de la ultima consulta, o
   - tambien contadores por proveedor en el mes?
7. Para tokens multiples por proveedor, el orden de uso debe ser siempre el orden declarado en `config.php`?
8. Si un token falla por `401/403`, se debe probar inmediatamente el siguiente token del mismo proveedor?
9. En respuestas al frontend, quieres incluir `provider.name` y `fallback_used` siempre (incluido exito)?
10. Quieres enmascarar el numero consultado en el log detallado (ejemplo, DNI `4602****`) o guardarlo completo?

## 6) Alcance acordado para la siguiente fase (cuando autorices codigo)

Cuando cierres las preguntas anteriores, el plan de implementacion seria:

1. Ampliar config para proveedores/tokens/fallback.
2. Crear adaptadores de APISPERU, DECOLECTA y JSON.PE.
3. Implementar orquestador de fallback en ApiHub.
4. Ampliar logging (mensual + detalle) y migraciones SQL.
5. Ajustar dashboard ApiHub para proveedor/fallback.
6. Mantener compatibilidad con Caja sin cambios funcionales en frontend.

## 7) Decisiones cerradas con negocio

Decisiones confirmadas por el usuario:

1. Orden fijo de fallback para DNI y RUC:
   - `apisperu -> decolecta -> jsonpe`
2. Si un proveedor responde `not_found`, se debe continuar con el siguiente proveedor hasta agotar la cadena.
3. Si la respuesta viene incompleta (ejemplo sin apellido materno), se acepta como resultado valido.
4. Salida final requerida:
   - DNI: solo `nombres`, `apellido_paterno`, `apellido_materno` (ademas del numero consultado)
   - RUC: solo `razon_social` (ademas del numero consultado)
5. Si un token devuelve `401/403`, se rota automaticamente al siguiente token del mismo proveedor.
6. Se autoriza crear tabla nueva de log detallado por consulta.
7. Dashboard: incluir contadores por proveedor en formato compacto.

## 8) Linea profesional de logging de documento

Criterio recomendado y adoptado:

- No guardar documento completo en el log detallado.
- Guardar:
  - `documento_masked` (ejemplo: DNI `4602****`, RUC `206010*****3`)
  - `documento_hash` (SHA-256) para trazabilidad tecnica sin exponer PII completa.

Esto es mas seguro y profesional para auditoria y soporte.

## 9) Bitacora de implementacion

### 2026-03-23 - Inicio de implementacion

Contexto confirmado antes de editar:

- `api.php` consume solo `apisperu_client.php`.
- `usage_repo.php` solo registra OK/FAIL por tipo (sin proveedor).
- `index.php` y `api_hub.js` no muestran proveedor ni contadores por proveedor.
- `config.php` aun tiene estructura antigua (`api_hub.apisperu.token` unico).

Plan de ejecucion aplicado:

1. Implementar orquestador multiproveedor con fallback y rotacion de tokens.
2. Extender repositorio de uso con:
   - contador de llamadas por proveedor (DNI/RUC)
   - ultimo proveedor/token/fallback
   - log detallado por consulta con documento enmascarado y hash
3. Actualizar dashboard para mostrar contadores compactos por proveedor.
4. Validar compatibilidad con flujo actual de Caja (mismos endpoints y formato base).

### 2026-03-23 - Avance backend y persistencia

Cambios implementados:

- `sistema/includes/config.php`
  - Estructura multiproveedor agregada:
    - `providers.apisperu`
    - `providers.decolecta`
    - `providers.jsonpe`
  - `fallback_order` fijo para DNI/RUC.
  - `monthly_limit` configurado:
    - decolecta = 100
    - jsonpe = 100

- `sistema/modules/api_hub/apisperu_client.php`
  - Reescrito como orquestador multiproveedor conservando funciones publicas:
    - `apihub_consultar_dni()`
    - `apihub_consultar_ruc()`
  - Implementado:
    - fallback `apisperu -> decolecta -> jsonpe`
    - rotacion de token por `401/403`
    - continuidad por `not_found`
    - aceptacion de payload incompleto para DNI
    - normalizacion de salida:
      - DNI: `dni`, `nombres`, `apellido_paterno`, `apellido_materno`
      - RUC: `ruc`, `razon_social`
    - control de limite mensual por proveedor (skip por limite alcanzado)
    - traza de intentos (`attempts`) y contadores de llamadas por proveedor (`provider_calls`)

- `sistema/modules/api_hub/usage_repo.php`
  - Reescrito para registrar:
    - acumulado mensual con contadores por proveedor (DNI y RUC)
    - ultimo proveedor/token/fallback
    - detalle por consulta en tabla nueva
  - Implementado log profesional:
    - `documento_masked`
    - `documento_hash` (SHA-256)
    - `intentos_json`
    - `duracion_ms`

- `sistema/modules/api_hub/api.php`
  - Integrado con nuevo orquestador.
  - Pasa consumo mensual por empresa al orquestador para evaluar limites.
  - Respuesta API ahora incluye metadata de proveedor:
    - `provider.name`
    - `provider.token_label`
    - `provider.fallback_used`
    - `provider.status`
    - `provider.message`
  - Logging ampliado con payload estructurado.

- `sistema/modules/api_hub/index.php`
- `sistema/modules/api_hub/api_hub.js`
  - Dashboard ampliado con:
    - contadores compactos por proveedor (APISPERU, DECOLECTA, JSON.PE)
    - columnas compactas por empresa:
      - `DNI AP/DE/JS`
      - `RUC AP/DE/JS`
      - ultimo proveedor (con marca fallback)

- SQL versionado:
  - `db/migrations/2026-03-23_api_hub_multiproveedor.sql`
  - `sistema/modules/api_hub/sql/mod_api_hub_uso_mensual.sql` (actualizado)
  - `sistema/modules/api_hub/sql/mod_api_hub_consulta_detalle.sql` (nuevo)

Pendiente inmediato:

1. Validacion sintactica y de integracion.
2. Ajustes puntuales detectados en validacion.

### 2026-03-23 - Validacion y ajustes

Validaciones ejecutadas:

- Se intento ejecutar lint PHP (`php -l`) en:
  - `sistema/includes/config.php`
  - `sistema/modules/api_hub/api.php`
  - `sistema/modules/api_hub/apisperu_client.php`
  - `sistema/modules/api_hub/usage_repo.php`
  - `sistema/modules/api_hub/index.php`
- Resultado: no fue posible en este entorno porque `php` no esta instalado en PATH.

Revision tecnica manual realizada:

1. Compatibilidad de firmas entre:
   - `api.php` -> `apihub_consultar_dni/ruc(..., ctx)`
   - `api.php` -> `apihub_register_usage($db, array $payload)`
2. Verificacion de nombres de columnas nuevas usadas en SQL de `usage_repo.php`.
3. Revision del flujo de respuesta para mantener contrato con frontend de Caja:
   - `ok`, `tipo`, `data`, `provider`
4. Revision del dashboard:
   - nuevas columnas y tarjetas coherentes con claves devueltas por backend.
5. Correccion de bind types en `usage_repo.php`:
   - ajuste de `bind_param` para insercion mensual
   - ajuste de `bind_param` para insercion de detalle

Estado tras validacion:

- Implementacion lista para prueba funcional en servidor con PHP/MySQL.
- Se requiere aplicar migracion `db/migrations/2026-03-23_api_hub_multiproveedor.sql` antes de probar consultas.

### 2026-03-23 - Cierre de implementacion

Checklist de despliegue inmediato:

1. Ejecutar migracion:
   - `db/migrations/2026-03-23_api_hub_multiproveedor.sql`
2. Configurar tokens en:
   - `sistema/includes/config.php`
   - `api_hub.providers.apisperu.tokens`
   - `api_hub.providers.decolecta.tokens`
   - `api_hub.providers.jsonpe.tokens`
3. Probar en Caja:
   - DNI valido (RENIEC) con fallback forzado cuando APISPERU falle.
   - RUC valido (SUNAT) con fallback forzado cuando APISPERU falle.
4. Revisar dashboard de ApiHub:
   - incremento de `APISPERU/DECOLECTA/JSON.PE`
   - ultima consulta y ultimo proveedor.

Resultado esperado:

- El frontend de Caja sigue usando el mismo endpoint interno.
- ApiHub decide proveedor y fallback de forma transparente.
- Queda trazabilidad mensual y detalle por consulta.

### 2026-03-23 - Ajuste de claridad de mensajes (No encontrado)

Regla ajustada por requerimiento de negocio:

- Si se consultan los 3 proveedores y los 3 responden `not_found`,
  el resultado final debe ser `not_found` con mensaje claro:
  - DNI: `No se encontro informacion para ese DNI.`
  - RUC: `No se encontro informacion para ese RUC.`

Adicional para evitar confusion:

- Si hay mezcla de respuestas (`not_found` + fallas tecnicas), se usa estado `inconclusive`
  con mensaje amigable y neutral:
  - DNI: `No se pudo confirmar el DNI en este momento. Intenta nuevamente en unos minutos.`
  - RUC: `No se pudo confirmar el RUC en este momento. Intenta nuevamente en unos minutos.`

Este enfoque evita exponer detalle tecnico al usuario comun.
