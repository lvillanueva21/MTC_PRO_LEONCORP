# Guia de modularizacion web (control_web + partials)

## 1) Proposito de esta modularizacion
El objetivo es mover partes de la web publica (`index.php` en raiz) a modulos configurables desde el sistema interno (`sistema/modules/control_web/`), sin romper el tema ni la base existente.

Beneficios:
- Mantener orden: logica de edicion en `control_web`, render publico en `web/partials`.
- Permitir cambios dinamicos en BD sin tocar HTML estatico cada vez.
- Tener defaults seguros cuando no hay datos personalizados.

## 2) Arquitectura actual
### 2.1 Flujo general
1. El sitio publico (`index.php` raiz) incluye partials:
   - `web/partials/topbar.php`
   - `web/partials/navbar.php`
   - `web/partials/features.php`
2. Cada partial consulta su modelo (`*_model.php`), que lee BD y cae a defaults si no hay datos.
3. El modulo admin `sistema/modules/control_web/index.php` muestra botones (Cabecera, Menu, Caracteristicas).
4. `control_web.js` carga cada subvista por AJAX y envia formularios a `guardar.php` sin recargar pagina.

### 2.2 Carpetas que participan
- `sistema/modules/control_web/`
  - UI admin principal, botones, carga dinamica, JS y CSS de gestion.
  - Submodulos actuales:
    - `cabecera/`
    - `menu/`
    - `caracteristicas/`
- `web/partials/`
  - Render del frontend publico y modelos de datos.
  - Aqui viven:
    - `topbar.php` + `topbar_model.php`
    - `navbar.php` + `menu_model.php`
    - `features.php` + `features_model.php`

## 3) Modulos implementados hasta hoy
1. Cabecera
- Control admin: `sistema/modules/control_web/cabecera/`
- Render publico: `web/partials/topbar.php`
- Tabla: `web_topbar_config`
- Controla: direccion, telefono, correo, redes (WhatsApp/Facebook/Instagram/YouTube)

2. Menu
- Control admin: `sistema/modules/control_web/menu/`
- Render publico: `web/partials/navbar.php`
- Tabla: `web_menu`
- Controla: titulo, logo, menu principal/submenus, boton de accion

3. Caracteristicas
- Control admin: `sistema/modules/control_web/caracteristicas/`
- Render publico: `web/partials/features.php`
- Tabla: `web_caracteristicas`
- Controla: titulo en 2 partes, descripcion, imagen central, 4 tarjetas de caracteristicas

## 4) Regla de acceso (rol que puede editar)
Todos los endpoints de `control_web` usan:
- `acl_require_ids([1]);`
- `verificarPermiso(['Desarrollo']);`

Esto deja la edicion para perfil de Desarrollo (con la validacion ACL definida por el sistema).

Ademas, el menu lateral registra la opcion Web en:
- `sistema/includes/menu_matrix.php`
- item actual: `['path' => 'modules/control_web/', 'icon' => 'fas fa-globe', 'label' => 'Web', 'roles' => [$R['DES']]]`

## 5) Como se crean botones en Control Web
Archivo base: `sistema/modules/control_web/index.php`

Para agregar un boton nuevo:
1. Crear boton HTML con `data-target` unico.
2. Registrar URL en `window.CONTROL_WEB`.
3. En `control_web.js`, mapear ese `data-target` a la URL.
4. Crear carpeta del submodulo con `index.php`, `guardar.php`, `model.php`.

Patron actual:
- Botones: Cabecera, Menu, Caracteristicas
- `control_web.js` usa `loadView(target)` + `$workspace.load(url)`

## 6) Reglas de negocio y programacion (patron vigente)
1. Siempre usar defaults
- Cada modelo en `web/partials/*_model.php` define `*_defaults()`.
- Si falla query, no hay fila `id=1`, o hay campos vacios, se retorna default.

2. Tabla de configuracion de fila unica
- Patron actual: `id = 1` con `INSERT ... ON DUPLICATE KEY UPDATE`.
- Esto simplifica lectura/escritura para configuraciones globales del sitio.

3. Validacion doble
- Frontend: `maxlength`, `pattern`, `required`, ayudas visuales.
- Backend (`guardar.php`): validacion final obligatoria.
- Nunca confiar solo en JS.

4. Sin recarga completa
- Formularios se envian por AJAX desde `control_web.js`.
- Mensajes inline (success/error) con cierre manual y auto ocultado en 5s.

5. Escapar salida
- En render publico y admin se usa `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.

6. Compatibilidad
- PHP + mysqli, sin dependencias modernas obligatorias.

## 7) Como se crean tablas nuevas
Ubicacion recomendada para migraciones:
- `db/migrations/`

Ejemplos reales:
- `db/migrations/2026-03-08_control_web_topbar.sql`
- `db/migrations/2026-03-08_control_web_menu.sql`
- `db/migrations/2026-03-08_control_web_caracteristicas.sql`

Convenciones usadas:
- Prefijo funcional: `web_...`
- PK simple: `id TINYINT UNSIGNED` (fila unica id=1)
- Campo `actualizacion DATETIME ... ON UPDATE CURRENT_TIMESTAMP`
- JSON para estructuras flexibles (menu/items)

Plantilla sugerida:
```sql
CREATE TABLE IF NOT EXISTS web_nuevo_modulo (
    id TINYINT UNSIGNED NOT NULL,
    -- campos...
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_nuevo_modulo (id, ...)
VALUES (1, ...)
ON DUPLICATE KEY UPDATE id = id;
```

## 8) Manejo de inputs y validaciones (resumen por tipo)
### 8.1 Texto
- Se normaliza con `trim`.
- Se limita por longitud maxima en backend.
- En caracteristicas se usan contadores de caracteres desde `control_web.js`.

### 8.2 Email y telefono
- Email con `FILTER_VALIDATE_EMAIL`.
- Telefono de cabecera: 9 digitos y empieza con 9 (`/^9\d{8}$/`).

### 8.3 URLs
- Cabecera valida dominio permitido por red social.
- Menu valida `#seccion`, `http(s)`, rutas relativas, y bloquea `javascript:`/`data:`.

### 8.4 JSON
- Menu principal/submenu via `menu_items_json`.
- Se parsea con `json_decode` y se normaliza con `cw_menu_normalize_items`.

## 9) Subida, reemplazo y eliminacion de archivos
Se usa:
- `sistema/modules/consola/gestion_archivos.php`

Funciones clave:
- `ga_save_upload(...)`: guarda archivo y registra metadata en `mtp_archivos`.
- `ga_mark_and_delete(...)`: marca estado (`reemplazado`/`borrado`) y elimina archivo fisico.

Estructura de almacenamiento:
- `almacen/AAAA/MM/DD/<categoria>/archivo.ext`

Categorias usadas hasta hoy:
- Menu logo: `logo_web`
- Imagen de caracteristicas: `img_caracteristica`

Reglas vigentes:
- Maximo 3MB
- MIME permitido: `image/png`, `image/webp`, `image/jpeg`
- Al subir nuevo archivo, se elimina el anterior (si existia)
- Si usuario marca "eliminar", se limpia ruta y vuelve fallback default

## 10) Importancia de los defaults
Nunca debe romperse la web publica si faltan datos personalizados.

Patron obligatorio:
- `*_defaults()` define valores base visualmente validos.
- `*_fetch()` siempre regresa datos completos (merge defaults + BD).
- El partial renderiza default cuando:
  - no existe registro
  - valor esta vacio
  - archivo custom no existe fisicamente

Esto evita secciones vacias, logos rotos, o HTML incompleto.

## 11) Playbook para crear una nueva modularizacion
1. Identificar bloque en `index.php` raiz
- Si aplica, extraer a `web/partials/nuevo.php` e incluirlo en raiz.

2. Crear modelo en `web/partials/nuevo_model.php`
- `nuevo_defaults()`
- `nuevo_fetch(mysqli $cn)`
- `nuevo_upsert(mysqli $cn, array $data)`
- helpers de normalizacion/URL/fallback

3. Crear migracion SQL en `db/migrations/`
- tabla `web_nuevo_modulo`
- semilla inicial con `id=1`

4. Crear submodulo admin
- `sistema/modules/control_web/nuevo/index.php` (formulario)
- `sistema/modules/control_web/nuevo/guardar.php` (validacion + guardado)
- `sistema/modules/control_web/nuevo/model.php` (require al modelo partial)

5. Conectar en panel `control_web`
- Boton nuevo en `sistema/modules/control_web/index.php`
- Ruta nueva en `window.CONTROL_WEB`
- Mapa `target => url` en `control_web.js`

6. Ajustar estilos si hace falta
- `sistema/modules/control_web/control_web.css`

7. Validar seguridad
- `acl_require_ids([1])`
- `verificarPermiso(['Desarrollo'])`

8. Probar end-to-end
- Abrir modulo
- Guardar con datos validos
- Forzar errores de validacion
- Confirmar render en web publica
- Confirmar fallback default si faltan datos

## 12) Checklist rapido antes de cerrar una modularizacion
- [ ] Hay migracion SQL en `db/migrations/`
- [ ] Existe `*_defaults()` coherente
- [ ] Se valida en backend todos los campos
- [ ] Se escapa salida en vista
- [ ] No se rompe si BD esta vacia
- [ ] Upload elimina archivo anterior cuando corresponde
- [ ] Se mantiene UX sin recargar pagina
- [ ] Boton y ruta en `control_web` funcionan
- [ ] Render publico en `index.php` quedo enlazado

---
Documento basado en la implementacion actual de:
- `index.php` (raiz)
- `sistema/modules/control_web/*`
- `web/partials/*`
- `sistema/modules/consola/gestion_archivos.php`
- `db/migrations/2026-03-08_control_web_*.sql`
