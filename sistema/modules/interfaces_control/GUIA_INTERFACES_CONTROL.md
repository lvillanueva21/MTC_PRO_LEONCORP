<!-- /modules/interfaces_control/GUIA_INTERFACES_CONTROL.md -->
# Guia de construccion y operacion: Interfaces de Control

## 1) Objetivo
Este documento define como construir y operar el sistema de interfaces para usuarios con rol **Control**.

Regla principal:
- Solo el rol activo **Control** (id=2) tendra permisos granulares por usuario.
- Solo aplica a carpetas dentro de `modules/interfaces_control/`.
- Los demas roles y sus menus siguen funcionando como siempre.

## 2) Alcance
Incluye:
- Descubrimiento automatico de interfaces por carpetas.
- Asignacion de interfaces a usuarios del rol Control.
- Menu dinamico para rol Control.
- Validacion backend por interfaz (seguridad real).

Regla de esquema SQL:
- No improvisar tipos de columnas ni signed/unsigned.
- Verificar siempre el esquema real en los SQL del proyecto antes de crear/alterar tablas.

No incluye:
- Cambios en permisos/menu de otros roles.
- Migracion de modulos externos a `interfaces_control` (a menos que se solicite).

## 2.1) Mapa real de fuentes SQL (importante)
La informacion de tablas NO esta en un solo archivo.
Esta distribuida en:

1. Base principal (snapshot):
- `db/lsistemas_erp_2026.sql`

2. Migraciones generales:
- `db/migrations/*.sql`

3. SQL de modulos:
- `sistema/modules/*/sql/*.sql`

Conclusion operativa:
- Para una tabla existente, el snapshot puede estar desactualizado respecto a migraciones posteriores.
- Para tablas nuevas de modulos (ej. `web_*`, `cr_formularios*`, `cr_grupos*`), su creacion puede vivir solo en migraciones o SQL de modulo.

## 2.2) Orden de verificacion para obtener estructura exacta
Antes de escribir SQL nuevo, seguir este orden:

1. Buscar la tabla en `db/migrations/*.sql` (CREATE/ALTER mas reciente).
2. Buscar la tabla en `sistema/modules/*/sql/*.sql` (si pertenece a un modulo).
3. Revisar `db/lsistemas_erp_2026.sql` como base inicial.
4. Si hay conflicto, prevalece el cambio mas reciente por fecha/archivo de migracion.

Comando sugerido:
```bash
rg -n "CREATE TABLE|ALTER TABLE|NOMBRE_TABLA" -g "*.sql" db sistema/modules
```

## 3) Archivos involucrados

### 3.1 Archivos actuales
- `sistema/modules/interfaces_control/index.php`
- `sistema/includes/menu_matrix.php`
- `sistema/includes/sidebar.php`
- `sistema/includes/acl.php`
- `sistema/includes/permisos.php`
- `sistema/includes/auth.php`

### 3.2 Archivos recomendados para esta arquitectura
- `sistema/modules/interfaces_control/api.php`
- `sistema/modules/interfaces_control/_scanner.php`
- `sistema/modules/interfaces_control/_control_acl.php`

### 3.3 Estructura esperada por cada interfaz
- `sistema/modules/interfaces_control/<slug>/manifest.php` (obligatorio)
- `sistema/modules/interfaces_control/<slug>/index.php` (obligatorio)
- `sistema/modules/interfaces_control/<slug>/api.php` (opcional, segun necesidad)
- `sistema/modules/interfaces_control/<slug>/assets/` (opcional)

## 4) Tabla involucrada (asignaciones)
Fuente de verdad:
- El filesystem define que interfaces existen.
- La BD define que usuario Control tiene acceso a que interfaz.

```sql
CREATE TABLE IF NOT EXISTS mtp_control_interfaces_usuario (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario INT(10) UNSIGNED NOT NULL,
  interface_slug VARCHAR(120) NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  actualizado_por INT(10) UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuario_slug (id_usuario, interface_slug),
  KEY idx_usuario_estado (id_usuario, estado),
  KEY idx_slug_estado (interface_slug, estado),
  CONSTRAINT fk_ci_usuario
    FOREIGN KEY (id_usuario) REFERENCES mtp_usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ci_actualizado_por
    FOREIGN KEY (actualizado_por) REFERENCES mtp_usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
```

## 5) Contrato de `manifest.php` por interfaz
Cada carpeta de interfaz debe exponer un arreglo PHP.

Ejemplo:
```php
<?php
// /modules/interfaces_control/control_servicios/manifest.php
return [
    'slug'  => 'control_servicios',
    'label' => 'Control Servicios',
    'icon'  => 'fas fa-clipboard-list',
    'path'  => 'modules/interfaces_control/control_servicios/',
    'orden' => 10,
    'activo'=> 1
];
```

Reglas:
- `slug`: obligatorio, unico, minusculas, numeros y guion bajo (`^[a-z0-9_]+$`).
- `label`: obligatorio, texto visible en menu.
- `icon`: opcional (Font Awesome), con fallback.
- `path`: obligatorio, ruta relativa del sistema.
- `orden`: opcional para ordenar menu.
- `activo`: `1` o `0`.

## 6) Flujo completo

1. Usuario Desarrollo entra a `modules/interfaces_control/index.php`.
2. El backend escanea carpetas directas dentro de `modules/interfaces_control/`.
3. Solo se consideran carpetas validas que tengan `manifest.php`.
4. Se listan usuarios que tengan rol Control.
5. Desarrollo asigna o quita interfaces por usuario.
6. Se guardan filas en `mtp_control_interfaces_usuario`.
7. Cuando un usuario entra con rol activo Control:
   - Ve Dashboard comun.
   - En sidebar ve solo interfaces asignadas y existentes en disco.
8. Si intenta abrir por URL una interfaz no asignada, backend responde `403`.

## 7) Seguridad minima obligatoria
- Default deny: sin asignacion no hay acceso.
- Nunca confiar solo en menu.
- Validar permisos en backend de cada `index.php`, `api.php`, `export.php`, etc.
- No construir includes con input del usuario.
- Escaneo de carpetas con whitelist de nombre de carpeta.
- Ignorar carpetas ocultas, backups y enlaces simbolicos.
- Registrar `actualizado_por` al guardar asignaciones.

## 8) Cambios de menu (principio)
- `menu_matrix.php` mantiene opciones fijas para todos los roles como hoy.
- Para rol Control, el bloque de interfaces granulares debe resolverse dinamicamente desde `sidebar.php` + BD + filesystem.
- No alterar logica de los demas roles.

## 9) Convenciones para nuevas interfaces
- Carpeta en: `sistema/modules/interfaces_control/<slug>/`
- Nombre de carpeta = `slug`.
- Siempre agregar `manifest.php`.
- `index.php` con includes base (`acl.php`, `permisos.php`, `conexion.php`, `header.php`, `footer.php`).
- Si hay endpoints, `api.php` tambien valida permiso por `slug`.
- Evitar archivos gigantes; separar en `assets/*.js`, `assets/*.css`, `partials/*.php` si crece.

## 10) Checklist rapido para crear una interfaz nueva
- Crear carpeta `<slug>`.
- Crear `manifest.php`.
- Crear `index.php` base.
- Crear `api.php` (si aplica) con seguridad.
- Verificar que aparezca en `interfaces_control/index.php`.
- Asignar a un usuario Control.
- Probar:
  - Usuario asignado: acceso OK.
  - Usuario no asignado: `403`.
  - Otros roles: sin cambios.

## 11) Prompt reutilizable para crear una nueva interfaz
Copiar y pegar el siguiente prompt cuando quieras una nueva carpeta:

```text
En el branch actual, crea una nueva interfaz para rol Control en:
modules/interfaces_control/<SLUG>/

Usa como reglas el archivo:
modules/interfaces_control/GUIA_INTERFACES_CONTROL.md

Requisitos:
1) Crear manifest.php con:
   - slug: <SLUG>
   - label: <NOMBRE_VISIBLE>
   - icon: <ICONO_FA>
   - path: modules/interfaces_control/<SLUG>/
   - orden: <NUMERO_ORDEN>
   - activo: 1
2) Crear index.php con plantilla base (header/sidebar/footer) y mensaje inicial de modulo en construccion.
3) Crear api.php base protegido (si corresponde).
4) No modificar menus ni permisos de otros roles.
5) Mantener rutas relativas, mysqli y compatibilidad PHP/MySQL amplia.
6) Agregar comentario de ruta al inicio de cada archivo nuevo.
7) Mostrar diff final y archivos creados.
```

## 12) Primer modulo sugerido
Primer slug definido:
- `control_servicios`
