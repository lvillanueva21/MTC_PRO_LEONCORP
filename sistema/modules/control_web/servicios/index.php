<?php
// modules/control_web/servicios/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_services_admin_h')) {
    function cw_services_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_services_admin_remaining')) {
    function cw_services_admin_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$servicesData = cw_services_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $servicesData = cw_services_fetch($cn);
    }
}

$defaults = cw_services_defaults();
$items = cw_services_normalize_items($servicesData['items'] ?? []);
$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/servicios/guardar.php';

$tituloBase = trim((string)($servicesData['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($servicesData['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($servicesData['descripcion_general'] ?? ''));

if ($tituloBase === '') {
    $tituloBase = $defaults['titulo_base'];
}
if ($tituloResaltado === '') {
    $tituloResaltado = $defaults['titulo_resaltado'];
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = $defaults['descripcion_general'];
}
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Servicios</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Configura el texto principal y los 6 servicios destacados del bloque "Central Services".
  </p>

  <div id="cw-services-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-services-form" action="<?php echo cw_services_admin_h($guardarUrl); ?>" method="post" novalidate>
    <h5 class="mb-2">1. Texto principal</h5>
    <p class="text-muted mb-3">Se muestra en el encabezado del bloque de servicios.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_services_titulo_base" class="mb-1">Texto base (oscuro)</label>
          <small class="text-muted cw-char-counter"><span id="cw_services_count_titulo_base"><?php echo cw_services_admin_h((string)cw_services_admin_remaining($tituloBase, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_services_titulo_base"
          name="titulo_base"
          maxlength="40"
          data-cw-counter="cw_services_count_titulo_base"
          value="<?php echo cw_services_admin_h($tituloBase); ?>"
          placeholder="Cental"
        >
      </div>
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_services_titulo_resaltado" class="mb-1">Texto resaltado (primary)</label>
          <small class="text-muted cw-char-counter"><span id="cw_services_count_titulo_resaltado"><?php echo cw_services_admin_h((string)cw_services_admin_remaining($tituloResaltado, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_services_titulo_resaltado"
          name="titulo_resaltado"
          maxlength="40"
          data-cw-counter="cw_services_count_titulo_resaltado"
          value="<?php echo cw_services_admin_h($tituloResaltado); ?>"
          placeholder="Services"
        >
      </div>
    </div>

    <div class="form-group">
      <div class="d-flex justify-content-between">
        <label for="cw_services_descripcion_general" class="mb-1">Descripcion general</label>
        <small class="text-muted cw-char-counter"><span id="cw_services_count_descripcion_general"><?php echo cw_services_admin_h((string)cw_services_admin_remaining($descripcionGeneral, 320)); ?></span> restantes</small>
      </div>
      <textarea
        class="form-control"
        id="cw_services_descripcion_general"
        name="descripcion_general"
        rows="3"
        maxlength="320"
        data-cw-counter="cw_services_count_descripcion_general"
        placeholder="Descripcion breve del bloque de servicios"
      ><?php echo cw_services_admin_h($descripcionGeneral); ?></textarea>
    </div>

    <hr>

    <h5 class="mb-2">2. Servicios destacados</h5>
    <p class="text-muted mb-3">Puedes cambiar icono, titulo y texto. Si dejas un campo vacio, se usa el default.</p>

    <?php foreach ($items as $idx => $item): ?>
      <?php
        $num = $idx + 1;
        $defaultItem = $defaults['items'][$idx];
        $iconCounterId = 'cw_services_count_item_icono_' . $num;
        $titleCounterId = 'cw_services_count_item_titulo_' . $num;
        $textCounterId = 'cw_services_count_item_texto_' . $num;
      ?>
      <div class="card card-outline card-light cw-service-card mb-3">
        <div class="card-header py-2">
          <strong>Servicio <?php echo cw_services_admin_h((string)$num); ?></strong>
        </div>
        <div class="card-body py-3">
          <div class="form-row">
            <div class="form-group col-md-4">
              <div class="d-flex justify-content-between">
                <label for="cw_services_item_icono_<?php echo cw_services_admin_h((string)$num); ?>" class="mb-1">Codigo de icono</label>
                <small class="text-muted cw-char-counter"><span id="<?php echo cw_services_admin_h($iconCounterId); ?>"><?php echo cw_services_admin_h((string)cw_services_admin_remaining($item['icono'], 120)); ?></span> restantes</small>
              </div>
              <input
                type="text"
                class="form-control"
                id="cw_services_item_icono_<?php echo cw_services_admin_h((string)$num); ?>"
                name="item_icono[]"
                maxlength="120"
                data-cw-counter="<?php echo cw_services_admin_h($iconCounterId); ?>"
                value="<?php echo cw_services_admin_h($item['icono']); ?>"
                placeholder="<?php echo cw_services_admin_h($defaultItem['icono']); ?>"
              >
              <small class="form-text text-muted">Ejemplos: <code>fa fa-car-alt fa-2x</code>, <code>fas fa-star</code>, <code>bi bi-award</code></small>
            </div>
            <div class="form-group col-md-4">
              <div class="d-flex justify-content-between">
                <label for="cw_services_item_titulo_<?php echo cw_services_admin_h((string)$num); ?>" class="mb-1">Titulo</label>
                <small class="text-muted cw-char-counter"><span id="<?php echo cw_services_admin_h($titleCounterId); ?>"><?php echo cw_services_admin_h((string)cw_services_admin_remaining($item['titulo'], 55)); ?></span> restantes</small>
              </div>
              <input
                type="text"
                class="form-control"
                id="cw_services_item_titulo_<?php echo cw_services_admin_h((string)$num); ?>"
                name="item_titulo[]"
                maxlength="55"
                data-cw-counter="<?php echo cw_services_admin_h($titleCounterId); ?>"
                value="<?php echo cw_services_admin_h($item['titulo']); ?>"
                placeholder="<?php echo cw_services_admin_h($defaultItem['titulo']); ?>"
              >
            </div>
            <div class="form-group col-md-4">
              <div class="d-flex justify-content-between">
                <label for="cw_services_item_texto_<?php echo cw_services_admin_h((string)$num); ?>" class="mb-1">Texto</label>
                <small class="text-muted cw-char-counter"><span id="<?php echo cw_services_admin_h($textCounterId); ?>"><?php echo cw_services_admin_h((string)cw_services_admin_remaining($item['texto'], 170)); ?></span> restantes</small>
              </div>
              <textarea
                class="form-control"
                id="cw_services_item_texto_<?php echo cw_services_admin_h((string)$num); ?>"
                name="item_texto[]"
                rows="2"
                maxlength="170"
                data-cw-counter="<?php echo cw_services_admin_h($textCounterId); ?>"
                placeholder="<?php echo cw_services_admin_h($defaultItem['texto']); ?>"
              ><?php echo cw_services_admin_h($item['texto']); ?></textarea>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-services-submit">Guardar servicios</button>
      <small class="text-muted mb-2">Los cambios se reflejan en la seccion de servicios de la pagina principal.</small>
    </div>
  </form>
</div>
