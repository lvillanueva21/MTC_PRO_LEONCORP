# Guia de modularizacion web (control_web + partials)

## 1) Objetivo
Esta guia describe **como esta modularizada hoy** la web publica (`index.php` raiz) y como se administra desde `sistema/modules/control_web/`.

Objetivos de la modularizacion:
- Separar capa publica (render) y capa admin (edicion) sin romper el tema.
- Permitir cambios por BD con fallback seguro a defaults.
- Mantener un patron repetible para futuras secciones.

Estado del documento: actualizado a implementacion vigente al **2026-03-10**.

## 2) Mapa actual del sistema
### 2.1 Flujo publico (`index.php` raiz)
Actualmente se incluyen **13 partials** en este orden:
1. `web/partials/topbar.php`
2. `web/partials/navbar.php`
3. `web/partials/formulario_carrusel.php`
4. `web/partials/features.php`
5. `web/partials/about.php`
6. `web/partials/counter.php`
7. `web/partials/services.php`
8. `web/partials/carrusel_servicios.php`
9. `web/partials/process.php`
10. `web/partials/novedades.php`
11. `web/partials/banner.php`
12. `web/partials/carrusel_empresas.php`
13. `web/partials/testimonios.php`

Cada partial consume su `*_model.php` para:
- Leer BD.
- Normalizar datos.
- Aplicar fallback cuando falte configuracion.

### 2.2 Flujo admin (`sistema/modules/control_web`)
- Vista principal: `sistema/modules/control_web/index.php`.
- Carga dinamica por AJAX: `sistema/modules/control_web/control_web.js`.
- Botones/targets activos: `cabecera`, `menu`, `caracteristicas`, `nosotros`, `contadores`, `servicios`, `carrusel_servicios`, `carrusel_empresas`, `testimonios`, `novedades`, `proceso`, `banner`, `formulario_carrusel`.
- Cada target carga su `index.php` y guarda via `guardar.php` (JSON: `{ ok, message, errors? }`).

### 2.3 Patron de puente admin -> modelo compartido
Cada submodulo admin usa un `model.php` que hace `require_once` del modelo del partial publico.

Ejemplo:
- `sistema/modules/control_web/servicios/model.php`
- `web/partials/services_model.php`

## 3) Modularizaciones activas (13)
### 3.1 Cabecera (Topbar)
- Admin: `sistema/modules/control_web/cabecera/`
- Publico: `web/partials/topbar.php` + `web/partials/topbar_model.php`
- Tabla: `web_topbar_config`
- Controla: direccion, telefono, correo y redes en barra superior.

### 3.2 Menu (Navbar)
- Admin: `sistema/modules/control_web/menu/`
- Publico: `web/partials/navbar.php` + `web/partials/menu_model.php`
- Tabla: `web_menu`
- Controla: logo, titulo, opciones principales/submenus, CTA.

### 3.3 Caracteristicas (Features)
- Admin: `sistema/modules/control_web/caracteristicas/`
- Publico: `web/partials/features.php` + `web/partials/features_model.php`
- Tabla: `web_caracteristicas`
- Controla: titulo, descripcion, imagen central y tarjetas.

### 3.4 Nosotros (About)
- Admin: `sistema/modules/control_web/nosotros/`
- Publico: `web/partials/about.php` + `web/partials/about_model.php`
- Tabla: `web_nosotros`
- Controla: textos, checklist, CTA, fundador e imagenes.

### 3.5 Contadores (Counter)
- Admin: `sistema/modules/control_web/contadores/`
- Publico: `web/partials/counter.php` + `web/partials/counter_model.php`
- Tabla: `web_contadores`
- Controla: bloques numero + titulo.

### 3.6 Servicios
- Admin: `sistema/modules/control_web/servicios/`
- Publico: `web/partials/services.php` + `web/partials/services_model.php`
- Tabla: `web_servicios`
- Controla: titulo base, titulo resaltado, descripcion general, 6 items.

### 3.7 Carrusel Servicios
- Admin: `sistema/modules/control_web/carrusel_servicios/`
- Publico: `web/partials/carrusel_servicios.php` + `web/partials/carrusel_servicios_model.php`
- Tablas:
  - `web_carrusel_servicios_config`
  - `web_carrusel_servicios_items`
- Controla por item: imagen, titulo, review, estrellas, badge, 6 detalles (icono/texto/visible), boton.
- Reglas: minimo 1 item, maximo 9.

### 3.8 Proceso
- Admin: `sistema/modules/control_web/proceso/`
- Publico: `web/partials/process.php` + `web/partials/process_model.php`
- Tabla: `web_proceso`
- Controla: titulo, descripcion y pasos.
- Reglas: minimo 3 pasos, maximo 9.

### 3.9 Novedades
- Admin: `sistema/modules/control_web/novedades/`
- Publico: `web/partials/novedades.php` + `web/partials/novedades_model.php`
- Tablas:
  - `web_novedades_config`
  - `web_novedades_items`
- Controla en config: titulo base, titulo resaltado, descripcion general.
- Controla por item: visible, titulo, metas (icono/texto), badge, resumen, boton, imagen.
- Reglas: minimo 1 item, maximo 9, al menos 1 visible.

### 3.10 Banner
- Admin: `sistema/modules/control_web/banner/`
- Publico: `web/partials/banner.php` + `web/partials/banner_model.php`
- Tabla: `web_banner`
- Controla: textos principales, botones, imagen.

### 3.11 Carrusel Empresas (Customer Suport Center)
- Admin: `sistema/modules/control_web/carrusel_empresas/`
- Publico: `web/partials/carrusel_empresas.php` + `web/partials/carrusel_empresas_model.php`
- Tablas:
  - `web_carrusel_empresas_config`
  - `web_carrusel_empresas_items`
- Controla en config: `titulo_base`, `titulo_resaltado`, `descripcion_general`.
- Controla por item: imagen, titulo, profesion, redes (WhatsApp/Facebook/Instagram/YouTube) con visibilidad y link.
- Reglas: minimo 1 item, maximo 15; cada item debe tener al menos 1 red visible.

### 3.12 Formulario y Carrusel
- Admin: `sistema/modules/control_web/formulario_carrusel/`
- Publico: `web/partials/formulario_carrusel.php` + `web/partials/formulario_carrusel_model.php`
- Endpoints:
  - Publico submit: `web/partials/formulario_carrusel_submit.php`
  - Admin mensajes: `sistema/modules/control_web/formulario_carrusel/mensajes.php`
- Tablas:
  - `web_formulario_carrusel_items`
  - `web_formulario_carrusel_mensajes`
- Controla: slides del carrusel y gestion de leads.
- Reglas: items 1..5.

### 3.13 Testimonios
- Admin: `sistema/modules/control_web/testimonios/`
- Publico: `web/partials/testimonios.php` + `web/partials/testimonios_model.php`
- Tablas:
  - `web_testimonios_config`
  - `web_testimonios_items`
- Controla en config: titulo base, titulo resaltado, descripcion general.
- Controla por item: nombre cliente, profesion, testimonio, imagen.
- Reglas: 2 tarjetas fijas, estrellas e iconografia fija del template.

## 4) Persistencia: patrones reales
### 4.1 Fila unica (`id = 1`, upsert)
- `web_topbar_config`
- `web_menu`
- `web_caracteristicas`
- `web_nosotros`
- `web_contadores`
- `web_servicios`
- `web_carrusel_servicios_config`
- `web_proceso`
- `web_novedades_config`
- `web_banner`
- `web_carrusel_empresas_config`
- `web_testimonios_config`

### 4.2 Multi-fila (items)
- `web_carrusel_servicios_items` (1..9)
- `web_novedades_items` (1..9)
- `web_carrusel_empresas_items` (1..15)
- `web_formulario_carrusel_items` (1..5)
- `web_formulario_carrusel_mensajes` (historial)
- `web_testimonios_items` (2 fijas por orden)

## 5) Seguridad y control de acceso
Todos los endpoints del modulo web usan:
- `acl_require_ids([1]);`
- `verificarPermiso(['Desarrollo']);`

Entrada de menu lateral:
- `sistema/includes/menu_matrix.php`

## 6) Validacion y salida segura
Patron aplicado en modulos:
- Validacion frontend (maxlength/required/ayudas visuales).
- Validacion backend final en `guardar.php` o submit publico.
- Escape de salida con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- JS solo como apoyo UX; las reglas de negocio viven en backend/modelo.

## 7) AJAX en control_web
- Carga de vistas: `loadView(target)` en `control_web.js`.
- Envio: `submitAjaxForm(...)`.
- Respuesta estandar: `{ ok, message, errors? }`.
- Por defecto, al entrar al modulo web se carga `cabecera`.

## 8) Archivos y uploads
Se usa `sistema/modules/consola/gestion_archivos.php`:
- `ga_save_upload(...)`
- `ga_mark_and_delete(...)`

Categorias usadas en web modularizada:
- `logo_web`
- `img_caracteristica`
- `img_nosotros`
- `img_banner`
- `img_carrusel_servicios`
- `img_novedades`
- `img_carrusel_empresas`
- `img_testimonios`
- `img_formulario_carrusel`

Reglas comunes:
- Maximo 3MB
- MIME permitidos: `image/png`, `image/webp`, `image/jpeg`
- Reemplazo borra/marca anterior
- Al eliminar imagen personalizada se regresa a fallback del tema

## 9) Migraciones SQL (control_web)
Ubicacion: `db/migrations/`

Listado actual:
- `2026-03-08_control_web_topbar.sql`
- `2026-03-08_control_web_menu.sql`
- `2026-03-08_control_web_caracteristicas.sql`
- `2026-03-08_control_web_nosotros.sql`
- `2026-03-08_control_web_contadores.sql`
- `2026-03-09_control_web_servicios.sql`
- `2026-03-09_control_web_carrusel_servicios.sql`
- `2026-03-09_control_web_proceso.sql`
- `2026-03-09_control_web_novedades.sql`
- `2026-03-09_control_web_banner.sql`
- `2026-03-09_control_web_carrusel_empresas.sql`
- `2026-03-10_control_web_carrusel_empresas_descripcion.sql`
- `2026-03-09_control_web_formulario_carrusel.sql`
- `2026-03-09_control_web_testimonios.sql`

## 10) Playbook para nuevas modularizaciones
1. Identificar bloque en `index.php` publico.
2. Extraer render a `web/partials/<modulo>.php` (si aplica).
3. Crear `web/partials/<modulo>_model.php` con:
   - `*_defaults()`
   - `*_fetch(...)`
   - `*_upsert(...)`
   - helpers de normalizacion/fallback
4. Crear migracion SQL en `db/migrations/`.
5. Crear submodulo admin:
   - `sistema/modules/control_web/<modulo>/index.php`
   - `sistema/modules/control_web/<modulo>/guardar.php`
   - `sistema/modules/control_web/<modulo>/model.php`
6. Registrar boton + target + URL en `sistema/modules/control_web/index.php`.
7. Enlazar target en `control_web.js` (`loadView`, `init...Form`, submit).
8. Aplicar seguridad (`acl_require_ids`, `verificarPermiso`).
9. Validar end-to-end (sin datos, con datos, errores, fallback, uploads).

## 11) Ruta rapida para futuros chats/agentes
Para entender cualquier modulo rapidamente revisar en este orden:
1. `db/migrations/<modulo>.sql`
2. `web/partials/<modulo>_model.php`
3. `web/partials/<modulo>.php`
4. `sistema/modules/control_web/<modulo>/index.php`
5. `sistema/modules/control_web/<modulo>/guardar.php`
6. `sistema/modules/control_web/control_web.js`
7. `sistema/modules/control_web/index.php`

Con eso se identifica: tabla, defaults, validaciones, render, AJAX y permisos.

## 12) Checklist de cierre por modulo
- [ ] Migracion SQL creada y versionada.
- [ ] Defaults definidos para estado vacio.
- [ ] Backend valida longitudes/formato/negocio.
- [ ] Frontend escapa salida.
- [ ] Funciona sin datos en BD.
- [ ] Uploads reemplazan/eliminan correctamente.
- [ ] Vista admin funciona por AJAX sin recarga completa.
- [ ] Target y ruta agregados en control_web.
- [ ] Bloque enlazado en `index.php` publico.
- [ ] Se actualiza esta guia.

## 13) Mapa de anchors (`#`) para linkear modulos
Anchors de seccion detectados en la web publica:

| Anchor | Archivo render | Modulo `control_web` relacionado | Tipo |
|---|---|---|---|
| `#inicio` | `web/partials/formulario_carrusel.php` | `formulario_carrusel` | Seccion |
| `#caracteristicas` | `web/partials/features.php` | `caracteristicas` | Seccion |
| `#nosotros` | `web/partials/about.php` | `nosotros` | Seccion |
| `#servicios` | `web/partials/services.php` | `servicios` | Seccion |
| `#categorias` | `web/partials/carrusel_servicios.php` | `carrusel_servicios` | Seccion |
| `#pasos` | `web/partials/process.php` | `proceso` | Seccion |
| `#blog` | `web/partials/novedades.php` | `novedades` | Seccion |
| `#promocion` | `web/partials/banner.php` | `banner` | Seccion |
| `#equipo` | `web/partials/carrusel_empresas.php` | `carrusel_empresas` | Seccion |
| `#testimonios` | `web/partials/testimonios.php` | `testimonios` | Seccion |
| `#contacto` | `index.php` (footer) | _Sin modulo en `control_web`_ | Seccion estatica |

IDs tecnicos (no recomendados para menu publico):
- `#navbarCollapse` (`web/partials/navbar.php`): colapso interno Bootstrap.
- `#spinner` (`index.php`): loader inicial.
- `#copyright` (`index.php`): bloque de copyright.
- `#cwFcFeedbackModal` (`web/partials/formulario_carrusel.php`): modal JS de feedback.

Modulos de `control_web` que hoy **no** tienen anchor de seccion dedicado:
- `cabecera` (topbar)
- `menu` (navbar)
- `contadores`
