# Guia de modularizacion web (control_web + partials)

## 1) Proposito de esta modularizacion
El objetivo es modularizar bloques del sitio publico (`index.php` en raiz) para administrarlos desde el sistema interno (`sistema/modules/control_web/`) sin romper el tema ni el fallback visual.

Beneficios principales:
- Orden claro entre capa de administracion y capa publica.
- Cambios dinamicos en BD sin editar HTML estatico en cada ajuste.
- Defaults seguros cuando no hay datos personalizados o falla una consulta.

## 2) Arquitectura actual
### 2.1 Flujo general
1. El sitio publico (`index.php` raiz) incluye 12 partials:
   - `web/partials/topbar.php`
   - `web/partials/navbar.php`
   - `web/partials/formulario_carrusel.php`
   - `web/partials/features.php`
   - `web/partials/about.php`
   - `web/partials/counter.php`
   - `web/partials/services.php`
   - `web/partials/carrusel_servicios.php`
   - `web/partials/process.php`
   - `web/partials/banner.php`
   - `web/partials/carrusel_empresas.php`
   - `web/partials/testimonios.php`
2. Cada partial consulta su `*_model.php` y aplica defaults si no hay datos en BD.
3. El modulo admin `sistema/modules/control_web/index.php` muestra 12 botones (`data-target`) y expone sus URLs en `window.CONTROL_WEB`.
4. `control_web.js` carga cada subvista por AJAX (`loadView`) y envia formularios a `guardar.php` sin recargar.
5. `formulario_carrusel` ademas tiene:
   - Endpoint publico de envio: `web/partials/formulario_carrusel_submit.php`
   - Endpoint admin para gestionar mensajes: `sistema/modules/control_web/formulario_carrusel/mensajes.php`

### 2.2 Carpetas que participan
- `sistema/modules/control_web/`
  - Vista principal de control web (`index.php`), JS y CSS.
  - Submodulos: `cabecera`, `menu`, `caracteristicas`, `nosotros`, `contadores`, `servicios`, `carrusel_servicios`, `carrusel_empresas`, `testimonios`, `proceso`, `banner`, `formulario_carrusel`.
- `web/partials/`
  - Render del frontend publico + modelos (`*_model.php`) compartidos con admin.

### 2.3 Patron de puente (admin -> modelo compartido)
Cada submodulo admin incluye un `model.php` que hace `require_once` al modelo del partial correspondiente.  
Ejemplo: `sistema/modules/control_web/servicios/model.php` -> `web/partials/services_model.php`.

## 3) Modularizaciones activas (12)
1. Cabecera
- Admin: `sistema/modules/control_web/cabecera/`
- Publico: `web/partials/topbar.php` + `topbar_model.php`
- Tabla: `web_topbar_config`
- Controla: direccion, telefono, correo, redes sociales.

2. Menu
- Admin: `sistema/modules/control_web/menu/`
- Publico: `web/partials/navbar.php` + `menu_model.php`
- Tabla: `web_menu`
- Controla: titulo, logo, menu principal/submenus y boton de accion.

3. Caracteristicas
- Admin: `sistema/modules/control_web/caracteristicas/`
- Publico: `web/partials/features.php` + `features_model.php`
- Tabla: `web_caracteristicas`
- Controla: titulo, descripcion, imagen central y tarjetas.

4. Nosotros
- Admin: `sistema/modules/control_web/nosotros/`
- Publico: `web/partials/about.php` + `about_model.php`
- Tabla: `web_nosotros`
- Controla: textos principales, tarjetas, checklist, CTA, fundador e imagenes.

5. Contadores
- Admin: `sistema/modules/control_web/contadores/`
- Publico: `web/partials/counter.php` + `counter_model.php`
- Tabla: `web_contadores`
- Controla: bloques de contadores (`numero` + `titulo`).

6. Servicios
- Admin: `sistema/modules/control_web/servicios/`
- Publico: `web/partials/services.php` + `services_model.php`
- Tabla: `web_servicios`
- Controla: titulo, descripcion e items de servicios.

7. Carrusel Servicios (Vehicle Categories)
- Admin: `sistema/modules/control_web/carrusel_servicios/`
- Publico: `web/partials/carrusel_servicios.php` + `carrusel_servicios_model.php`
- Tablas:
  - `web_carrusel_servicios_config` (encabezado del bloque)
  - `web_carrusel_servicios_items` (items del carrusel)
- Controla por item: imagen, titulo, review, estrellas (1..5 u ocultas), badge de precio/texto, 6 detalles (icono + texto + visibilidad), boton.

8. Proceso
- Admin: `sistema/modules/control_web/proceso/`
- Publico: `web/partials/process.php` + `process_model.php`
- Tabla: `web_proceso`
- Controla: titulo, descripcion e items del proceso.

9. Banner
- Admin: `sistema/modules/control_web/banner/`
- Publico: `web/partials/banner.php` + `banner_model.php`
- Tabla: `web_banner`
- Controla: textos, botones e imagen del banner.

10. Carrusel Empresas (Customer Suport Center)
- Admin: `sistema/modules/control_web/carrusel_empresas/`
- Publico: `web/partials/carrusel_empresas.php` + `carrusel_empresas_model.php`
- Tablas:
  - `web_carrusel_empresas_config` (titulo principal en 2 partes)
  - `web_carrusel_empresas_items` (items del carrusel)
- Controla por item: imagen (default o personalizada), titulo, profesion, redes sociales (WhatsApp, Facebook, Instagram, YouTube) con visibilidad y enlace por red.
- Reglas: minimo 1 empresa, maximo 15; por empresa minimo 1 red visible y maximo 4.

11. Formulario y Carrusel
- Admin: `sistema/modules/control_web/formulario_carrusel/`
- Publico: `web/partials/formulario_carrusel.php` + `formulario_carrusel_model.php`
- Submit publico: `web/partials/formulario_carrusel_submit.php`
- Tablas:
  - `web_formulario_carrusel_items` (slides del carrusel)
  - `web_formulario_carrusel_mensajes` (leads del formulario)
- Controla:
  - Items del carrusel (1..5, ordenados, con imagen opcional)
  - Gestion de mensajes (listar, actualizar estado, eliminar)
- Nota: opciones de servicios/ciudades/horarios del formulario estan definidas en codigo (helpers del modelo), no en tabla de catalogos.

12. Testimonios
- Admin: `sistema/modules/control_web/testimonios/`
- Publico: `web/partials/testimonios.php` + `testimonios_model.php`
- Tablas:
  - `web_testimonios_config` (titulo 1, titulo 2 y descripcion central)
  - `web_testimonios_items` (2 tarjetas fijas, orden 1 y 2)
- Controla por item: nombre de cliente, profesion, testimonio e imagen.
- Reglas: siempre 2 bloques, 5 estrellas fijas e icono de comillas fijo.

## 4) Regla de acceso (rol que puede editar)
Todos los endpoints de `control_web` usan:
- `acl_require_ids([1]);`
- `verificarPermiso(['Desarrollo']);`

El menu lateral registra la opcion Web en:
- `sistema/includes/menu_matrix.php`

## 5) Patron tecnico vigente
### 5.1 Contrato por modulo
En la practica cada modulo sigue este contrato:
- `*_defaults()`: estado base seguro.
- `*_fetch(mysqli $cn)`: lectura desde BD con fallback.
- `*_upsert(mysqli $cn, array $data)`: persistencia.
- Helpers de normalizacion/URL/resolucion de imagen segun el caso.

### 5.2 Persistencia
Hay dos patrones:

1. Fila unica (`id = 1`) con `INSERT ... ON DUPLICATE KEY UPDATE`
- `web_topbar_config`
- `web_menu`
- `web_caracteristicas`
- `web_nosotros`
- `web_contadores`
- `web_servicios`
- `web_carrusel_servicios_config`
- `web_testimonios_config`
- `web_proceso`
- `web_banner`

2. Multi-fila (casos especiales)
- `web_carrusel_servicios_items`: 1..9 filas ordenadas (`orden`), con altas/ediciones/borrados.
- `web_testimonios_items`: 2 filas fijas (`orden` 1 y 2), sin cambios de cantidad.
- `web_formulario_carrusel_items`: hasta 5 filas ordenadas (`orden`), con altas/ediciones/borrados.
- `web_formulario_carrusel_mensajes`: historial de leads con paginacion y estados.

### 5.3 Validacion y seguridad
- Doble validacion:
  - Frontend (maxlength/pattern/required y ayudas visuales).
  - Backend (`guardar.php` o submit publico) como validacion final obligatoria.
- Salida escapada con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- Sin confiar en JS para reglas de negocio.

## 6) AJAX y endpoints
### 6.1 Carga de vistas admin
- `control_web.js` mapea `target -> url` y usa `$workspace.load(url)`.
- Targets activos: `cabecera`, `menu`, `caracteristicas`, `nosotros`, `contadores`, `servicios`, `carrusel_servicios`, `carrusel_empresas`, `testimonios`, `proceso`, `banner`, `formulario_carrusel`.

### 6.2 Guardado admin
- Cada formulario envia a su `guardar.php` via AJAX.
- Respuesta estandar JSON: `{ ok, message, errors? }`.

### 6.3 Caso formulario_carrusel
- Publico:
  - `formulario_carrusel.php` renderiza formulario + carrusel.
  - `formulario_carrusel_submit.php` valida e inserta lead (`cw_fc_insert_message`).
- Admin:
  - `guardar.php` guarda items del carrusel (incluye uploads y limpieza de imagenes antiguas).
  - `mensajes.php` soporta acciones: `list`, `update_status`, `delete`.

## 7) Subida, reemplazo y eliminacion de archivos
Se usa:
- `sistema/modules/consola/gestion_archivos.php`

Funciones principales:
- `ga_save_upload(...)`
- `ga_mark_and_delete(...)`

Categorias usadas:
- Menu logo: `logo_web`
- Caracteristicas: `img_caracteristica`
- Nosotros: `img_nosotros`
- Banner: `img_banner`
- Carrusel servicios: `img_carrusel_servicios`
- Carrusel empresas: `img_carrusel_empresas`
- Testimonios: `img_testimonios`
- Formulario/Carrusel: `img_formulario_carrusel`

Reglas vigentes:
- Maximo 3MB
- MIME permitido: `image/png`, `image/webp`, `image/jpeg`
- Al reemplazar archivo, se marca/elimina el anterior
- Si se marca eliminar, se limpia ruta y se usa fallback default

## 8) Migraciones SQL relacionadas
Ubicacion recomendada:
- `db/migrations/`

Migraciones actuales de control web:
- `2026-03-08_control_web_topbar.sql`
- `2026-03-08_control_web_menu.sql`
- `2026-03-08_control_web_caracteristicas.sql`
- `2026-03-08_control_web_nosotros.sql`
- `2026-03-08_control_web_contadores.sql`
- `2026-03-09_control_web_servicios.sql`
- `2026-03-09_control_web_carrusel_servicios.sql`
- `2026-03-09_control_web_proceso.sql`
- `2026-03-09_control_web_banner.sql`
- `2026-03-09_control_web_formulario_carrusel.sql`
- `2026-03-09_control_web_carrusel_empresas.sql`
- `2026-03-09_control_web_testimonios.sql`

## 9) Playbook para nueva modularizacion
1. Identificar bloque en `index.php` raiz y extraer a `web/partials/nuevo.php` si aplica.
2. Crear `web/partials/nuevo_model.php` con `defaults/fetch/upsert` + helpers.
3. Crear migracion SQL en `db/migrations/`.
4. Crear submodulo admin:
   - `sistema/modules/control_web/nuevo/index.php`
   - `sistema/modules/control_web/nuevo/guardar.php`
   - `sistema/modules/control_web/nuevo/model.php`
5. Registrar boton y ruta en `sistema/modules/control_web/index.php`.
6. Mapear `data-target` en `control_web.js`.
7. Validar seguridad (`acl_require_ids([1])`, `verificarPermiso(['Desarrollo'])`).
8. Probar end-to-end (guardado valido, errores, fallback y render publico).

## 10) Checklist de cierre
- [ ] Existe migracion SQL.
- [ ] Existe `*_defaults()` coherente.
- [ ] Backend valida todos los campos.
- [ ] La salida esta escapada.
- [ ] No se rompe si BD esta vacia.
- [ ] Upload reemplaza/elimina correctamente.
- [ ] Admin funciona sin recarga completa.
- [ ] Boton y ruta de `control_web` cargan correctamente.
- [ ] Partial publico quedo enlazado en `index.php`.

---
Documento actualizado segun implementacion real actual en:
- `index.php` (raiz)
- `sistema/modules/control_web/*`
- `web/partials/*`
- `sistema/modules/consola/gestion_archivos.php`
- `db/migrations/2026-03-08_control_web_*.sql`
- `db/migrations/2026-03-09_control_web_*.sql`
