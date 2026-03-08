<?php
// modules/control_web/menu/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_menu_h')) {
    function cw_menu_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$menuData = cw_menu_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $menuData = cw_menu_fetch($cn);
    }
}

$items = cw_menu_normalize_items($menuData['menu_items'] ?? []);
if (empty($items)) {
    $items = cw_menu_defaults()['menu_items'];
}

if (!empty($items)) {
    $items[0]['visible'] = 1;
}

$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/menu/guardar.php';
$logoUrl = cw_menu_logo_public_url((string)($menuData['logo_path'] ?? ''));
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Menú</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Desde aquí configuras el título, logo, opciones del menú y el botón de acción del navbar principal.
  </p>

  <div id="cw-menu-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-menu-form" action="<?php echo cw_menu_h($guardarUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
    <h5 class="mb-2">1. Titulo y logo</h5>
    <p class="text-muted mb-3">
      Si no subes logo, se usará el icono de auto por defecto. Formatos permitidos: PNG, WEBP o JPEG.
    </p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="cw_titulo_pagina">Titulo de página</label>
        <input
          type="text"
          class="form-control"
          id="cw_titulo_pagina"
          name="titulo_pagina"
          maxlength="120"
          required
          value="<?php echo cw_menu_h($menuData['titulo_pagina']); ?>"
          placeholder="Ejemplo: Cental"
        >
      </div>
      <div class="form-group col-md-6">
        <label for="cw_logo_archivo">Logo de página</label>
        <input
          type="file"
          class="form-control-file"
          id="cw_logo_archivo"
          name="logo_archivo"
          accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
        >
        <small class="form-text text-muted">
          Se guarda con gestor de archivos en categoria <strong>logo_web</strong>.
        </small>
      </div>
    </div>

    <div class="form-row align-items-center">
      <div class="col-md-8 mb-2">
        <?php if ($logoUrl !== ''): ?>
          <div class="cw-menu-logo-preview p-2 border rounded bg-light">
            <div class="small text-muted mb-1">Logo actual:</div>
            <img src="<?php echo cw_menu_h($logoUrl); ?>" alt="Logo actual" class="img-fluid" style="max-height: 55px;">
          </div>
        <?php else: ?>
          <div class="small text-muted">Actualmente se usa el icono de auto por defecto.</div>
        <?php endif; ?>
      </div>
      <div class="col-md-4 mb-2">
        <div class="custom-control custom-checkbox mt-2">
          <input type="checkbox" class="custom-control-input" id="cw_eliminar_logo" name="eliminar_logo" value="1">
          <label class="custom-control-label" for="cw_eliminar_logo">Quitar logo y usar icono por defecto</label>
        </div>
      </div>
    </div>

    <hr>

    <h5 class="mb-2">2. Menú</h5>
    <p class="text-muted mb-2">
      Mínimo 1 opción y máximo 6 opciones principales. La primera opción es obligatoria.
    </p>
    <p class="text-muted mb-3">
      Puedes usar enlaces de tipo <code>#seccion</code> para navegar dentro de la página o URL/paths para otras páginas.
    </p>

    <div class="mb-2">
      <button type="button" class="btn btn-outline-primary btn-sm" id="cw-menu-add-item">
        <i class="fas fa-plus mr-1"></i>Agregar opción principal
      </button>
    </div>
    <div id="cw-menu-items" class="cw-menu-items"></div>
    <textarea id="cw-menu-items-seed" class="d-none"><?php echo cw_menu_h(json_encode($items, JSON_UNESCAPED_UNICODE)); ?></textarea>
    <input type="hidden" id="cw_menu_items_json" name="menu_items_json" value="">

    <hr>

    <h5 class="mb-2">3. Botón de Acción</h5>
    <p class="text-muted mb-3">Configura el texto y enlace del botón derecho del menú.</p>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="cw_boton_texto">Texto del botón</label>
        <input
          type="text"
          class="form-control"
          id="cw_boton_texto"
          name="boton_texto"
          maxlength="80"
          required
          value="<?php echo cw_menu_h($menuData['boton_texto']); ?>"
          placeholder="Ejemplo: Get Started"
        >
      </div>
      <div class="form-group col-md-6">
        <label for="cw_boton_url">Enlace del botón</label>
        <input
          type="text"
          class="form-control"
          id="cw_boton_url"
          name="boton_url"
          maxlength="255"
          required
          value="<?php echo cw_menu_h($menuData['boton_url']); ?>"
          placeholder="Ejemplo: # o /web/contact.html"
        >
      </div>
    </div>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-menu-submit">Guardar menú</button>
      <small class="text-muted mb-2">Los cambios se verán en el navbar de la página principal.</small>
    </div>
  </form>
</div>
