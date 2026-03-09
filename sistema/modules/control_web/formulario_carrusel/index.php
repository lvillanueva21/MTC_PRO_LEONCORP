<?php
// modules/control_web/formulario_carrusel/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_fc_admin_h')) {
    function cw_fc_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_fc_admin_remaining')) {
    function cw_fc_admin_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$items = cw_fc_normalize_carousel_items(cw_fc_defaults()['carousel_items'] ?? []);
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $items = cw_fc_fetch_carousel_items($cn);
    }
}

$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/formulario_carrusel/guardar.php';
$mensajesApiUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/formulario_carrusel/mensajes.php';
?>

<div id="cw-fc-scope" data-cw-fc-api-url="<?php echo cw_fc_admin_h($mensajesApiUrl); ?>">
  <div class="card-header border-0">
    <h3 class="card-title mb-0">Formulario y Carrusel</h3>
  </div>

  <div class="card-body">
    <p class="text-muted mb-3">
      Ajusta los elementos del carrusel principal (imagen + titulo + texto) y gestiona los mensajes enviados desde el formulario comercial.
    </p>

    <div id="cw-fc-carousel-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

    <form id="cw-fc-carousel-form" action="<?php echo cw_fc_admin_h($guardarUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
      <h5 class="mb-2">1. Carrusel principal</h5>
      <p class="text-muted mb-3">Minimo 1 elemento y maximo 5. Si no cargas imagen, se usa una imagen por defecto del tema.</p>

      <div id="cw-fc-carousel-items">
        <?php foreach ($items as $idx => $item): ?>
          <?php
            $num = $idx + 1;
            $titleCounterId = 'cw_fc_count_titulo_' . $num;
            $textCounterId = 'cw_fc_count_texto_' . $num;
            $removeId = 'cw_fc_remove_image_' . $num;
            $currentImageUrl = cw_fc_resolve_slide_image_url($item, $idx);
            $defaultImageUrl = cw_fc_default_asset_url((string)($item['default_image'] ?? 'web/img/carousel-1.jpg'));
          ?>
          <div class="card card-outline card-light cw-fc-item mb-3" data-default-image="<?php echo cw_fc_admin_h($defaultImageUrl); ?>">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
              <div>
                <strong class="cw-fc-item-title">Elemento <?php echo cw_fc_admin_h((string)$num); ?></strong>
                <span class="badge badge-light ml-2">Slide <?php echo cw_fc_admin_h((string)$num); ?></span>
              </div>
              <button type="button" class="btn btn-sm btn-outline-danger cw-fc-remove-item">Quitar</button>
            </div>
            <div class="card-body py-3">
              <input type="hidden" class="cw-fc-item-id" name="item_id[<?php echo cw_fc_admin_h((string)$idx); ?>]" value="<?php echo cw_fc_admin_h((string)($item['id'] ?? 0)); ?>">

              <div class="form-row">
                <div class="form-group col-md-5">
                  <div class="d-flex justify-content-between">
                    <label class="mb-1">Titulo</label>
                    <small class="text-muted cw-char-counter"><span id="<?php echo cw_fc_admin_h($titleCounterId); ?>"><?php echo cw_fc_admin_h((string)cw_fc_admin_remaining((string)$item['titulo'], 140)); ?></span> restantes</small>
                  </div>
                  <input
                    type="text"
                    class="form-control cw-fc-item-titulo"
                    name="item_titulo[<?php echo cw_fc_admin_h((string)$idx); ?>]"
                    maxlength="140"
                    data-cw-counter="<?php echo cw_fc_admin_h($titleCounterId); ?>"
                    value="<?php echo cw_fc_admin_h((string)$item['titulo']); ?>"
                    placeholder="Titulo del slide"
                  >
                </div>

                <div class="form-group col-md-7">
                  <div class="d-flex justify-content-between">
                    <label class="mb-1">Texto</label>
                    <small class="text-muted cw-char-counter"><span id="<?php echo cw_fc_admin_h($textCounterId); ?>"><?php echo cw_fc_admin_h((string)cw_fc_admin_remaining((string)$item['texto'], 260)); ?></span> restantes</small>
                  </div>
                  <textarea
                    class="form-control cw-fc-item-texto"
                    name="item_texto[<?php echo cw_fc_admin_h((string)$idx); ?>]"
                    rows="3"
                    maxlength="260"
                    data-cw-counter="<?php echo cw_fc_admin_h($textCounterId); ?>"
                    placeholder="Texto de apoyo del slide"
                  ><?php echo cw_fc_admin_h((string)$item['texto']); ?></textarea>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label class="mb-1">Imagen</label>
                  <input
                    type="file"
                    class="form-control-file cw-fc-item-imagen"
                    name="item_imagen_archivo[<?php echo cw_fc_admin_h((string)$idx); ?>]"
                    accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
                  >
                  <small class="form-text text-muted">Categoria de almacenamiento: <strong>img_formulario_carrusel</strong>.</small>

                  <div class="custom-control custom-checkbox mt-2">
                    <input type="hidden" class="cw-fc-item-remove-hidden" name="item_eliminar_imagen[<?php echo cw_fc_admin_h((string)$idx); ?>]" value="0">
                    <input type="checkbox" class="custom-control-input cw-fc-item-remove-check" id="<?php echo cw_fc_admin_h($removeId); ?>" name="item_eliminar_imagen[<?php echo cw_fc_admin_h((string)$idx); ?>]" value="1">
                    <label class="custom-control-label" for="<?php echo cw_fc_admin_h($removeId); ?>">Quitar imagen personalizada</label>
                  </div>
                </div>

                <div class="form-group col-md-6">
                  <label class="d-block mb-1">Vista previa</label>
                  <div class="cw-fc-image-preview p-2 border rounded bg-light">
                    <img
                      class="cw-fc-item-preview-img img-fluid"
                      src="<?php echo cw_fc_admin_h($currentImageUrl); ?>"
                      data-current-src="<?php echo cw_fc_admin_h($currentImageUrl); ?>"
                      data-default-src="<?php echo cw_fc_admin_h($defaultImageUrl); ?>"
                      alt="Preview carrusel"
                    >
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mb-3">
        <button type="button" class="btn btn-outline-primary" id="cw-fc-carousel-add-item">
          <i class="fas fa-plus mr-1"></i>Agregar elemento
        </button>
        <small class="text-muted ml-2">Puedes administrar hasta 5 elementos.</small>
      </div>

      <div class="d-flex flex-wrap align-items-center">
        <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-fc-carousel-submit">Guardar carrusel</button>
        <small class="text-muted mb-2">Los cambios impactan el bloque inicial con formulario comercial.</small>
      </div>
    </form>

    <hr class="my-4">

    <h5 class="mb-2">2. Gestion de mensajes</h5>
    <p class="text-muted mb-3">Listado paginado de 10 en 10 con estado inicial <strong>En espera</strong>.</p>

    <div id="cw-fc-messages-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

    <div class="table-responsive">
      <table class="table table-sm table-striped table-bordered mb-0">
        <thead class="thead-light">
          <tr>
            <th style="min-width: 130px;">Fecha (Lima)</th>
            <th style="min-width: 95px;">Tipo</th>
            <th style="min-width: 170px;">Interesado</th>
            <th style="min-width: 170px;">Servicio</th>
            <th style="min-width: 180px;">Ciudad / Escuela</th>
            <th style="min-width: 120px;">Celular</th>
            <th style="min-width: 180px;">Estado</th>
            <th style="min-width: 140px;">Acciones</th>
          </tr>
        </thead>
        <tbody id="cw-fc-messages-body">
          <tr>
            <td colspan="8" class="text-center text-muted py-3">Cargando mensajes...</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
      <small id="cw-fc-messages-summary" class="text-muted mb-2">Cargando...</small>
      <nav class="mb-2">
        <ul class="pagination pagination-sm mb-0" id="cw-fc-messages-pagination"></ul>
      </nav>
    </div>
  </div>
</div>