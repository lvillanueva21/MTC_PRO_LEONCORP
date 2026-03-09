<?php
// modules/control_web/contadores/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_counter_admin_h')) {
    function cw_counter_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$counterData = cw_counter_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $counterData = cw_counter_fetch($cn);
    }
}

$items = cw_counter_normalize_items($counterData['items'] ?? []);
$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/contadores/guardar.php';
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Contadores</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Configura los cuatro contadores del bloque "Fact Counter" (numero y titulo).
  </p>

  <div id="cw-counter-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-counter-form" action="<?php echo cw_counter_admin_h($guardarUrl); ?>" method="post" novalidate>
    <?php foreach ($items as $idx => $item): ?>
      <?php $num = $idx + 1; ?>
      <div class="card card-outline card-light mb-3">
        <div class="card-header py-2">
          <strong>Contador <?php echo cw_counter_admin_h((string)$num); ?></strong>
        </div>
        <div class="card-body py-3">
          <div class="form-row">
            <div class="form-group col-md-2 d-flex align-items-center">
              <i class="<?php echo cw_counter_admin_h($item['icono']); ?> text-primary mr-2"></i>
              <small class="text-muted">Icono fijo</small>
            </div>
            <div class="form-group col-md-4">
              <label for="cw_counter_numero_<?php echo cw_counter_admin_h((string)$num); ?>">Numero</label>
              <input
                type="text"
                class="form-control"
                id="cw_counter_numero_<?php echo cw_counter_admin_h((string)$num); ?>"
                name="item_numero[]"
                maxlength="8"
                pattern="[0-9]{1,8}"
                value="<?php echo cw_counter_admin_h($item['numero']); ?>"
                placeholder="Ejemplo: 829"
              >
              <small class="form-text text-muted">Solo digitos. El signo <code>+</code> se muestra automaticamente.</small>
            </div>
            <div class="form-group col-md-6">
              <label for="cw_counter_titulo_<?php echo cw_counter_admin_h((string)$num); ?>">Titulo</label>
              <input
                type="text"
                class="form-control"
                id="cw_counter_titulo_<?php echo cw_counter_admin_h((string)$num); ?>"
                name="item_titulo[]"
                maxlength="80"
                value="<?php echo cw_counter_admin_h($item['titulo']); ?>"
                placeholder="Ejemplo: Happy Clients"
              >
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-counter-submit">Guardar contadores</button>
      <small class="text-muted mb-2">Los cambios se veran en el bloque de contadores de la pagina principal.</small>
    </div>
  </form>
</div>
