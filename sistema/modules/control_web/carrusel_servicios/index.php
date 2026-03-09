<?php
// modules/control_web/carrusel_servicios/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_cs_admin_h')) {
    function cw_cs_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_cs_admin_remaining')) {
    function cw_cs_admin_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$data = [
    'config' => cw_cs_config_defaults(),
    'items' => cw_cs_normalize_items(cw_cs_defaults()['items'] ?? []),
];

if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $data = cw_cs_fetch($cn);
    }
}

$configDefaults = cw_cs_config_defaults();
$config = is_array($data['config'] ?? null) ? $data['config'] : $configDefaults;
$items = cw_cs_normalize_items($data['items'] ?? []);

$tituloBase = trim((string)($config['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($config['descripcion_general'] ?? ''));

if ($tituloBase === '') {
    $tituloBase = (string)($configDefaults['titulo_base'] ?? 'Vehicle');
}
if ($tituloResaltado === '') {
    $tituloResaltado = (string)($configDefaults['titulo_resaltado'] ?? 'Categories');
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = (string)($configDefaults['descripcion_general'] ?? '');
}

$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/carrusel_servicios/guardar.php';
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Carrusel Servicios</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Modulariza el bloque Vehicle Categories como carrusel de servicios. Minimo 1 item y maximo 9.
  </p>

  <div id="cw-cs-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-cs-form" action="<?php echo cw_cs_admin_h($guardarUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
    <h5 class="mb-2">1. Encabezado del bloque</h5>
    <p class="text-muted mb-3">Si dejas campos vacios, se usaran los textos por defecto.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_cs_titulo_base" class="mb-1">Titulo base</label>
          <small class="text-muted cw-char-counter"><span id="cw_cs_count_titulo_base"><?php echo cw_cs_admin_h((string)cw_cs_admin_remaining($tituloBase, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_cs_titulo_base"
          name="titulo_base"
          maxlength="40"
          data-cw-counter="cw_cs_count_titulo_base"
          value="<?php echo cw_cs_admin_h($tituloBase); ?>"
          placeholder="Vehicle"
        >
      </div>
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_cs_titulo_resaltado" class="mb-1">Titulo resaltado</label>
          <small class="text-muted cw-char-counter"><span id="cw_cs_count_titulo_resaltado"><?php echo cw_cs_admin_h((string)cw_cs_admin_remaining($tituloResaltado, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_cs_titulo_resaltado"
          name="titulo_resaltado"
          maxlength="40"
          data-cw-counter="cw_cs_count_titulo_resaltado"
          value="<?php echo cw_cs_admin_h($tituloResaltado); ?>"
          placeholder="Categories"
        >
      </div>
    </div>

    <div class="form-group">
      <div class="d-flex justify-content-between">
        <label for="cw_cs_descripcion_general" class="mb-1">Descripcion general</label>
        <small class="text-muted cw-char-counter"><span id="cw_cs_count_descripcion_general"><?php echo cw_cs_admin_h((string)cw_cs_admin_remaining($descripcionGeneral, 320)); ?></span> restantes</small>
      </div>
      <textarea
        class="form-control"
        id="cw_cs_descripcion_general"
        name="descripcion_general"
        rows="3"
        maxlength="320"
        data-cw-counter="cw_cs_count_descripcion_general"
        placeholder="Descripcion del bloque"
      ><?php echo cw_cs_admin_h($descripcionGeneral); ?></textarea>
    </div>

    <hr>

    <h5 class="mb-2">2. Items del carrusel</h5>
    <p class="text-muted mb-3">
      Cada item permite: imagen, titulo, texto review, estrellas (1 a 5 o ocultas), badge de precio/texto,
      hasta 6 detalles (icono + texto + visibilidad), y boton (texto + enlace).
    </p>

    <div id="cw-cs-items">
      <?php foreach ($items as $idx => $item): ?>
        <?php
          $num = $idx + 1;
          $titleCounterId = 'cw_cs_count_titulo_' . $num;
          $reviewCounterId = 'cw_cs_count_review_' . $num;
          $badgeCounterId = 'cw_cs_count_badge_' . $num;
          $btnTextCounterId = 'cw_cs_count_btn_text_' . $num;
          $btnUrlCounterId = 'cw_cs_count_btn_url_' . $num;
          $removeId = 'cw_cs_remove_img_' . $num;
          $starsId = 'cw_cs_stars_visible_' . $num;

          $imageUrl = cw_cs_resolve_item_image_url($item, $idx);
          $defaultImageUrl = cw_cs_default_asset_url((string)($item['default_image'] ?? cw_cs_default_image_for_position($idx)));
          $showStars = ((int)($item['mostrar_estrellas'] ?? 1) === 1);
          $details = cw_cs_normalize_details($item['detalles'] ?? [], $idx);
        ?>
        <div class="card card-outline card-light cw-cs-item mb-3" data-default-image="<?php echo cw_cs_admin_h($defaultImageUrl); ?>">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <div>
              <strong class="cw-cs-item-title">Servicio <?php echo cw_cs_admin_h((string)$num); ?></strong>
              <span class="badge badge-light ml-2 cw-cs-item-slide-label">Slide <?php echo cw_cs_admin_h((string)$num); ?></span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger cw-cs-remove-item">Quitar</button>
          </div>
          <div class="card-body py-3">
            <input type="hidden" class="cw-cs-item-id" name="item_id[<?php echo cw_cs_admin_h((string)$idx); ?>]" value="<?php echo cw_cs_admin_h((string)($item['id'] ?? 0)); ?>">

            <div class="form-row">
              <div class="form-group col-md-6">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Titulo</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_cs_admin_h($titleCounterId); ?>"><?php echo cw_cs_admin_h((string)cw_cs_admin_remaining((string)($item['titulo'] ?? ''), 80)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-cs-item-titulo"
                  name="item_titulo[<?php echo cw_cs_admin_h((string)$idx); ?>]"
                  maxlength="80"
                  data-cw-counter="<?php echo cw_cs_admin_h($titleCounterId); ?>"
                  value="<?php echo cw_cs_admin_h((string)($item['titulo'] ?? '')); ?>"
                  placeholder="Nombre del servicio"
                >
              </div>

              <div class="form-group col-md-3">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Texto review</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_cs_admin_h($reviewCounterId); ?>"><?php echo cw_cs_admin_h((string)cw_cs_admin_remaining((string)($item['review_text'] ?? ''), 60)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-cs-item-review"
                  name="item_review_text[<?php echo cw_cs_admin_h((string)$idx); ?>]"
                  maxlength="60"
                  data-cw-counter="<?php echo cw_cs_admin_h($reviewCounterId); ?>"
                  value="<?php echo cw_cs_admin_h((string)($item['review_text'] ?? '')); ?>"
                  placeholder="4.5 Review"
                >
              </div>

              <div class="form-group col-md-3">
                <label class="mb-1">Estrellas</label>
                <div class="d-flex align-items-center">
                  <select class="form-control form-control-sm cw-cs-item-rating mr-2" name="item_rating[<?php echo cw_cs_admin_h((string)$idx); ?>]">
                    <?php for ($star = 1; $star <= 5; $star++): ?>
                      <option value="<?php echo cw_cs_admin_h((string)$star); ?>" <?php echo ((int)($item['rating'] ?? 4) === $star) ? 'selected' : ''; ?>><?php echo cw_cs_admin_h((string)$star); ?></option>
                    <?php endfor; ?>
                  </select>
                  <div class="custom-control custom-checkbox">
                    <input type="hidden" class="cw-cs-stars-visible-hidden" name="item_mostrar_estrellas[<?php echo cw_cs_admin_h((string)$idx); ?>]" value="<?php echo $showStars ? '1' : '0'; ?>">
                    <input type="checkbox" class="custom-control-input cw-cs-stars-visible-check" id="<?php echo cw_cs_admin_h($starsId); ?>" value="1" <?php echo $showStars ? 'checked' : ''; ?>>
                    <label class="custom-control-label small cw-cs-stars-visible-label" for="<?php echo cw_cs_admin_h($starsId); ?>">Mostrar</label>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Badge precio / texto</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_cs_admin_h($badgeCounterId); ?>"><?php echo cw_cs_admin_h((string)cw_cs_admin_remaining((string)($item['badge_text'] ?? ''), 80)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-cs-item-badge"
                  name="item_badge_text[<?php echo cw_cs_admin_h((string)$idx); ?>]"
                  maxlength="80"
                  data-cw-counter="<?php echo cw_cs_admin_h($badgeCounterId); ?>"
                  value="<?php echo cw_cs_admin_h((string)($item['badge_text'] ?? '')); ?>"
                  placeholder="$99:00/Day"
                >
              </div>

              <div class="form-group col-md-3">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Texto boton</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_cs_admin_h($btnTextCounterId); ?>"><?php echo cw_cs_admin_h((string)cw_cs_admin_remaining((string)($item['boton_texto'] ?? ''), 50)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-cs-item-btn-text"
                  name="item_boton_texto[<?php echo cw_cs_admin_h((string)$idx); ?>]"
                  maxlength="50"
                  data-cw-counter="<?php echo cw_cs_admin_h($btnTextCounterId); ?>"
                  value="<?php echo cw_cs_admin_h((string)($item['boton_texto'] ?? '')); ?>"
                  placeholder="Book Now"
                >
              </div>

              <div class="form-group col-md-3">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Enlace boton</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_cs_admin_h($btnUrlCounterId); ?>"><?php echo cw_cs_admin_h((string)cw_cs_admin_remaining((string)($item['boton_url'] ?? ''), 255)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-cs-item-btn-url"
                  name="item_boton_url[<?php echo cw_cs_admin_h((string)$idx); ?>]"
                  maxlength="255"
                  data-cw-counter="<?php echo cw_cs_admin_h($btnUrlCounterId); ?>"
                  value="<?php echo cw_cs_admin_h((string)($item['boton_url'] ?? '#')); ?>"
                  placeholder="# o /ruta o https://"
                >
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="mb-1">Imagen</label>
                <input
                  type="file"
                  class="form-control-file cw-cs-item-imagen"
                  name="item_imagen_archivo[<?php echo cw_cs_admin_h((string)$idx); ?>]"
                  accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
                >
                <small class="form-text text-muted">Categoria: <strong>img_carrusel_servicios</strong>.</small>

                <div class="custom-control custom-checkbox mt-2">
                  <input type="hidden" class="cw-cs-item-remove-hidden" name="item_eliminar_imagen[<?php echo cw_cs_admin_h((string)$idx); ?>]" value="0">
                  <input type="checkbox" class="custom-control-input cw-cs-item-remove-check" id="<?php echo cw_cs_admin_h($removeId); ?>" value="1">
                  <label class="custom-control-label cw-cs-item-remove-label" for="<?php echo cw_cs_admin_h($removeId); ?>">Quitar imagen personalizada</label>
                </div>
              </div>

              <div class="form-group col-md-6">
                <label class="d-block mb-1">Vista previa</label>
                <div class="cw-cs-image-preview p-2 border rounded bg-light">
                  <img
                    class="cw-cs-item-preview-img img-fluid"
                    src="<?php echo cw_cs_admin_h($imageUrl); ?>"
                    data-current-src="<?php echo cw_cs_admin_h($imageUrl); ?>"
                    data-default-src="<?php echo cw_cs_admin_h($defaultImageUrl); ?>"
                    alt="Preview servicio"
                  >
                </div>
              </div>
            </div>

            <div class="card card-outline card-secondary mt-2 mb-1">
              <div class="card-header py-2">
                <strong>Detalles (hasta 6 iconos)</strong>
              </div>
              <div class="card-body py-2">
                <?php foreach ($details as $dIdx => $detail): ?>
                  <?php
                    $detailVisible = ((int)($detail['visible'] ?? 1) === 1);
                    $detailCheckId = 'cw_cs_detail_visible_' . $num . '_' . ($dIdx + 1);
                  ?>
                  <div class="form-row align-items-center cw-cs-detail-row mb-2" data-detail-index="<?php echo cw_cs_admin_h((string)$dIdx); ?>">
                    <div class="col-md-2 mb-2 mb-md-0">
                      <div class="custom-control custom-checkbox">
                        <input type="hidden" class="cw-cs-detail-visible-hidden" name="item_detalle_visible[<?php echo cw_cs_admin_h((string)$idx); ?>][<?php echo cw_cs_admin_h((string)$dIdx); ?>]" value="<?php echo $detailVisible ? '1' : '0'; ?>">
                        <input type="checkbox" class="custom-control-input cw-cs-detail-visible-check" id="<?php echo cw_cs_admin_h($detailCheckId); ?>" value="1" <?php echo $detailVisible ? 'checked' : ''; ?>>
                        <label class="custom-control-label small cw-cs-detail-visible-label" for="<?php echo cw_cs_admin_h($detailCheckId); ?>">Mostrar</label>
                      </div>
                    </div>
                    <div class="col-md-5 mb-2 mb-md-0">
                      <input
                        type="text"
                        class="form-control form-control-sm cw-cs-detail-icon"
                        name="item_detalle_icono[<?php echo cw_cs_admin_h((string)$idx); ?>][<?php echo cw_cs_admin_h((string)$dIdx); ?>]"
                        maxlength="120"
                        value="<?php echo cw_cs_admin_h((string)($detail['icono'] ?? '')); ?>"
                        placeholder="fa fa-car"
                      >
                    </div>
                    <div class="col-md-5">
                      <input
                        type="text"
                        class="form-control form-control-sm cw-cs-detail-text"
                        name="item_detalle_texto[<?php echo cw_cs_admin_h((string)$idx); ?>][<?php echo cw_cs_admin_h((string)$dIdx); ?>]"
                        maxlength="40"
                        value="<?php echo cw_cs_admin_h((string)($detail['texto'] ?? '')); ?>"
                        placeholder="Texto breve"
                      >
                    </div>
                  </div>
                <?php endforeach; ?>
                <small class="text-muted">Puedes ocultar cualquiera de los 6 detalles o cambiar icono/texto libremente.</small>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-outline-primary" id="cw-cs-add-item">
        <i class="fas fa-plus mr-1"></i>Agregar servicio
      </button>
      <small class="text-muted ml-2">Maximo 9 servicios en el carrusel.</small>
    </div>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-cs-submit">Guardar carrusel de servicios</button>
      <small class="text-muted mb-2">Los cambios impactan el bloque Vehicle Categories de la web publica.</small>
    </div>
  </form>
</div>
