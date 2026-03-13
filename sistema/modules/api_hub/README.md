# /modules/api_hub/README.md

## 1) Objetivo del módulo

`ApiHub` centraliza las consultas externas de DNI/RUC (APISPERU) para:

- Proteger el token (nunca exponerlo en frontend).
- Reutilizar la lógica en varios módulos (`caja` y futuros).
- Registrar uso por empresa y por mes (éxitos/fallos).
- Mostrar un dashboard básico de consumo para rol Desarrollo.

## 2) Arquitectura implementada

### Backend

- `modules/api_hub/api.php`
  - Endpoint interno del sistema.
  - Acciones:
    - `consultar_dni` (POST)
    - `consultar_ruc` (POST)
    - `dashboard_month` (GET, solo Desarrollo)

- `modules/api_hub/apisperu_client.php`
  - Cliente HTTP con cURL a APISPERU.
  - Normaliza respuesta y errores amigables.
  - Validaciones:
    - DNI: 8 dígitos
    - RUC: 11 dígitos

- `modules/api_hub/usage_repo.php`
  - Registro mensual por empresa.
  - Contabiliza `dni_ok`, `dni_fail`, `ruc_ok`, `ruc_fail`.
  - Guarda última consulta (tipo, estado, fecha, mensaje).

### Frontend de ApiHub

- `modules/api_hub/index.php`
- `modules/api_hub/api_hub.js`
- `modules/api_hub/api_hub.css`

Muestra dashboard por período con tarjetas y tabla por empresa.

## 3) Seguridad aplicada

- Token en backend (no se imprime en JS/HTML).
- Control de acceso por roles:
  - Consultas DNI/RUC en `api.php`: Desarrollo(1), Recepción(3), Administración(4).
  - Dashboard `dashboard_month`: solo Desarrollo(1).
- Validación estricta de documento antes de consultar proveedor.
- Registro de uso no bloquea la consulta si falla el insert (tolerancia operativa).

## 4) Configuración del token

Archivo:

- `includes/config.php`

Nodo agregado:

```php
'api_hub' => [
  'apisperu' => [
    'token'                   => getenv('MTC_APISPERU_TOKEN') ?: '',
    'base_url'                => 'https://dniruc.apisperu.com/api/v1',
    'timeout_seconds'         => 12,
    'connect_timeout_seconds' => 6
  ]
]
```

Recomendación:

- Usar variable de entorno del servidor: `MTC_APISPERU_TOKEN`.
- Solo si no tienes env vars, puedes poner token fijo en `config.php`.

## 5) Base de datos

Tabla nueva:

- `mod_api_hub_uso_mensual`

Script SQL:

- `modules/api_hub/sql/mod_api_hub_uso_mensual.sql`

Migración de proyecto:

- `db/migrations/2026-03-13_api_hub_uso_mensual.sql`

La tabla no guarda cada consulta individual; guarda acumulados mensuales por empresa.

## 6) Integración en Caja (ya implementada)

Archivo integrado:

- `modules/caja/index.php`

Comportamiento en modal **Registrar pago > 2. Datos de cliente**:

- Botón `RENIEC` visible solo cuando `Tipo Doc = DNI`.
- Botón `SUNAT` visible solo cuando `Tipo Doc = RUC`.
- Al consultar:
  - RENIEC: llena `Nombres` y `Apellidos`.
  - SUNAT: llena `Razón social`.
- Mensajes amigables en alertas del modal.

Estilos:

- `modules/caja/estado.css` (clases `pm-btn-reniec`, `pm-btn-sunat`, etc.).

## 7) Guía para usar RENIEC/SUNAT en otros módulos

### Paso A: UI mínima

Agregar campos:

- Tipo de documento (`DNI`/`RUC`).
- Número de documento.
- Inputs destino:
  - DNI: nombres/apellidos.
  - RUC: razón social.
- Botones:
  - `RENIEC` (solo para DNI)
  - `SUNAT` (solo para RUC)

### Paso B: Helper JS reutilizable

Consumir endpoint interno (no APISPERU directo):

```js
const API_HUB_ENDPOINT = `${BASE_URL}/modules/api_hub/api.php`;

async function apiHubLookup(action, numero){
  const fd = new FormData();
  fd.append('action', action);      // consultar_dni | consultar_ruc
  fd.append('numero', numero);
  const r = await fetch(API_HUB_ENDPOINT, {
    method: 'POST',
    credentials: 'same-origin',
    body: fd
  });
  const j = await r.json();
  if (!j.ok) throw new Error(j.error || 'Error');
  return j;
}
```

### Paso C: Reglas de validación

- Antes de consultar DNI: regex `/^\d{8}$/`
- Antes de consultar RUC: regex `/^\d{11}$/`
- Bloquear doble click mientras consulta (disabled buttons).

### Paso D: Autocompletar

- Resultado DNI:
  - `data.nombres`
  - `data.apellido_paterno`
  - `data.apellido_materno`
- Resultado RUC:
  - `data.razon_social`

### Paso E: Manejo de errores

Mostrar mensajes de:

- Documento inválido.
- No encontrado.
- Servicio no disponible.
- ApiHub no configurado.

## 8) Endpoints de referencia interna

### POST `modules/api_hub/api.php`

`action=consultar_dni`

- input: `numero=########`
- output éxito:

```json
{
  "ok": true,
  "tipo": "DNI",
  "data": {
    "dni": "12345678",
    "nombres": "NOMBRES",
    "apellido_paterno": "APELLIDO1",
    "apellido_materno": "APELLIDO2",
    "cod_verifica": "X"
  }
}
```

`action=consultar_ruc`

- input: `numero=###########`
- output éxito:

```json
{
  "ok": true,
  "tipo": "RUC",
  "data": {
    "ruc": "20131312955",
    "razon_social": "EMPRESA SAC"
  }
}
```

### GET `modules/api_hub/api.php?action=dashboard_month&periodo=YYYY-MM`

- Solo rol Desarrollo.
- Devuelve totales del mes + filas por empresa.

## 9) Menú y accesos

- Menú agregado en `includes/menu_matrix.php`:
  - `ApiHub` solo para rol Desarrollo.

## 10) Checklist cuando se cambie ApiHub

- Verificar token/config.
- Verificar permisos por rol.
- Probar consulta DNI y RUC desde módulo consumidor.
- Confirmar incremento de contadores en tabla mensual.
- Revisar dashboard en `modules/api_hub/`.
- Si cambia estructura de tabla, crear nueva migración en `db/migrations/`.
