<?php
// modules/control_web/proceso/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_process_admin_h')) {
    function cw_process_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_process_admin_remaining')) {
    function cw_process_admin_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$processData = cw_process_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $processData = cw_process_fetch($cn);
    }
}

$defaults = cw_process_defaults();
$items = cw_process_normalize_items($processData['items'] ?? []);
$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/proceso/guardar.php';

$tituloBase = trim((string)($processData['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($processData['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($processData['descripcion_general'] ?? ''));

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
  <h3 class="card-title mb-0">Proceso</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Configura el texto principal y los bloques del area "Central Process". Los numeros se generan automaticamente.
  </p>

  <div id="cw-process-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-process-form" action="<?php echo cw_process_admin_h($guardarUrl); ?>" method="post" novalidate>
    <h5 class="mb-2">1. Texto principal</h5>
    <p class="text-muted mb-3">Este contenido aparece en el encabezado de la seccion de proceso.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_process_titulo_base" class="mb-1">Texto base (blanco)</label>
          <small class="text-muted cw-char-counter"><span id="cw_process_count_titulo_base"><?php echo cw_process_admin_h((string)cw_process_admin_remaining($tituloBase, 35)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_process_titulo_base"
          name="titulo_base"
          maxlength="35"
          data-cw-counter="cw_process_count_titulo_base"
          value="<?php echo cw_process_admin_h($tituloBase); ?>"
          placeholder="Cental"
        >
      </div>
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_process_titulo_resaltado" class="mb-1">Texto resaltado (primary)</label>
          <small class="text-muted cw-char-counter"><span id="cw_process_count_titulo_resaltado"><?php echo cw_process_admin_h((string)cw_process_admin_remaining($tituloResaltado, 35)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_process_titulo_resaltado"
          name="titulo_resaltado"
          maxlength="35"
          data-cw-counter="cw_process_count_titulo_resaltado"
          value="<?php echo cw_process_admin_h($tituloResaltado); ?>"
          placeholder="Process"
        >
      </div>
    </div>

    <div class="form-group">
      <div class="d-flex justify-content-between">
        <label for="cw_process_descripcion_general" class="mb-1">Descripcion general</label>
        <small class="text-muted cw-char-counter"><span id="cw_process_count_descripcion_general"><?php echo cw_process_admin_h((string)cw_process_admin_remaining($descripcionGeneral, 280)); ?></span> restantes</small>
      </div>
      <textarea
        class="form-control"
        id="cw_process_descripcion_general"
        name="descripcion_general"
        rows="3"
        maxlength="280"
        data-cw-counter="cw_process_count_descripcion_general"
        placeholder="Descripcion breve del bloque de proceso"
      ><?php echo cw_process_admin_h($descripcionGeneral); ?></textarea>
    </div>

    <hr>

    <h5 class="mb-2">2. Bloques del proceso</h5>
    <p class="text-muted mb-3">Minimo 3 bloques y maximo 9. El numero (01, 02, 03...) se asigna segun el orden.</p>

    <div id="cw-process-items">
      <?php foreach ($items as $idx => $item): ?>
        <?php
        $num = $idx + 1;
        $defaultItem = cw_process_item_default_for_position($idx);
        $numberText = str_pad((string)$num, 2, '0', STR_PAD_LEFT) . '.';
        $titleCounterId = 'cw_process_count_item_titulo_' . $num;
        $textCounterId = 'cw_process_count_item_texto_' . $num;
        ?>
        <div class="card card-outline card-light cw-process-item mb-3">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <div>
              <strong class="cw-process-item-title">Bloque <?php echo cw_process_admin_h((string)$num); ?></strong>
              <span class="badge badge-secondary ml-2">Numero <span class="cw-process-number"><?php echo cw_process_admin_h($numberText); ?></span></span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger cw-process-remove-item">Quitar</button>
          </div>
          <div class="card-body py-3">
            <div class="form-row">
              <div class="form-group col-md-5">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Titulo</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_process_admin_h($titleCounterId); ?>"><?php echo cw_process_admin_h((string)cw_process_admin_remaining($item['titulo'], 40)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-process-item-title-input"
                  name="item_titulo[]"
                  maxlength="40"
                  data-cw-counter="<?php echo cw_process_admin_h($titleCounterId); ?>"
                  value="<?php echo cw_process_admin_h($item['titulo']); ?>"
                  placeholder="<?php echo cw_process_admin_h((string)($defaultItem['titulo'] ?? '')); ?>"
                >
              </div>
              <div class="form-group col-md-7">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Descripcion</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_process_admin_h($textCounterId); ?>"><?php echo cw_process_admin_h((string)cw_process_admin_remaining($item['texto'], 150)); ?></span> restantes</small>
                </div>
                <textarea
                  class="form-control cw-process-item-text-input"
                  name="item_texto[]"
                  rows="3"
                  maxlength="150"
                  data-cw-counter="<?php echo cw_process_admin_h($textCounterId); ?>"
                  placeholder="<?php echo cw_process_admin_h((string)($defaultItem['texto'] ?? '')); ?>"
                ><?php echo cw_process_admin_h($item['texto']); ?></textarea>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-outline-primary" id="cw-process-add-item">
        <i class="fas fa-plus mr-1"></i>Agregar bloque
      </button>
      <small class="text-muted ml-2">Puedes agregar mas bloques sin editar los numeros manualmente.</small>
    </div>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-process-submit">Guardar proceso</button>
      <small class="text-muted mb-2">Los cambios se reflejan en la seccion de proceso de la pagina principal.</small>
    </div>
  </form>
</div>
