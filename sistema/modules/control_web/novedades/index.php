<?php
// modules/control_web/novedades/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_novedades_admin_h')) {
    function cw_novedades_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_novedades_admin_remaining')) {
    function cw_novedades_admin_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$data = [
    'config' => cw_novedades_config_defaults(),
    'items' => cw_novedades_normalize_items(cw_novedades_defaults()['items'] ?? []),
];
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $data = cw_novedades_fetch($cn);
    }
}

$configDefaults = cw_novedades_config_defaults();
$config = cw_novedades_normalize_config($data['config'] ?? []);
$items = cw_novedades_normalize_items($data['items'] ?? []);

$tituloBase = trim((string)($config['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($config['descripcion_general'] ?? ''));

if ($tituloBase === '') {
    $tituloBase = (string)($configDefaults['titulo_base'] ?? 'Cental');
}
if ($tituloResaltado === '') {
    $tituloResaltado = (string)($configDefaults['titulo_resaltado'] ?? 'Blog & News');
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = (string)($configDefaults['descripcion_general'] ?? '');
}

$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/novedades/guardar.php';
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Novedades</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Configura el bloque de novedades tipo blog. Puedes gestionar entre 1 y 9 novedades y decidir cuales se muestran en la web.
  </p>

  <div id="cw-nv-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-nv-form" action="<?php echo cw_novedades_admin_h($guardarUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
    <h5 class="mb-2">1. Encabezado del modulo</h5>
    <p class="text-muted mb-3">
      El titulo se divide en dos partes para mantener el estilo de dos colores (`h1` + `span`).
    </p>

    <div class="form-row">
      <div class="form-group col-md-4">
        <div class="d-flex justify-content-between">
          <label for="cw_nv_titulo_base" class="mb-1">Titulo 1</label>
          <small class="text-muted cw-char-counter"><span id="cw_nv_count_titulo_base"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($tituloBase, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_nv_titulo_base"
          name="titulo_base"
          maxlength="40"
          data-cw-counter="cw_nv_count_titulo_base"
          value="<?php echo cw_novedades_admin_h($tituloBase); ?>"
          placeholder="Cental"
        >
      </div>

      <div class="form-group col-md-4">
        <div class="d-flex justify-content-between">
          <label for="cw_nv_titulo_resaltado" class="mb-1">Titulo 2 (resaltado)</label>
          <small class="text-muted cw-char-counter"><span id="cw_nv_count_titulo_resaltado"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($tituloResaltado, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_nv_titulo_resaltado"
          name="titulo_resaltado"
          maxlength="40"
          data-cw-counter="cw_nv_count_titulo_resaltado"
          value="<?php echo cw_novedades_admin_h($tituloResaltado); ?>"
          placeholder="Blog & News"
        >
      </div>

      <div class="form-group col-md-4">
        <div class="d-flex justify-content-between">
          <label for="cw_nv_descripcion_general" class="mb-1">Texto central</label>
          <small class="text-muted cw-char-counter"><span id="cw_nv_count_descripcion_general"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($descripcionGeneral, 280)); ?></span> restantes</small>
        </div>
        <textarea
          class="form-control"
          id="cw_nv_descripcion_general"
          name="descripcion_general"
          rows="3"
          maxlength="280"
          data-cw-counter="cw_nv_count_descripcion_general"
          placeholder="Texto central del modulo de novedades"
        ><?php echo cw_novedades_admin_h($descripcionGeneral); ?></textarea>
      </div>
    </div>

    <hr>

    <h5 class="mb-2">2. Novedades (1 a 9)</h5>
    <p class="text-muted mb-3">
      Cada novedad permite titulo, 2 iconos con texto, badge, resumen, boton y enlace, visibilidad e imagen.
      Si dejas campos vacios, se usa contenido por defecto.
    </p>

    <div id="cw-nv-items">
      <?php foreach ($items as $idx => $item): ?>
        <?php
        $num = $idx + 1;
        $base = cw_novedades_item_default_for_position($idx);

        $titulo = trim((string)($item['titulo'] ?? ''));
        $meta1Icon = trim((string)($item['meta_1_icono'] ?? ''));
        $meta1Text = trim((string)($item['meta_1_texto'] ?? ''));
        $meta2Icon = trim((string)($item['meta_2_icono'] ?? ''));
        $meta2Text = trim((string)($item['meta_2_texto'] ?? ''));
        $badgeText = trim((string)($item['badge_texto'] ?? ''));
        $resumenText = trim((string)($item['resumen_texto'] ?? ''));
        $buttonText = trim((string)($item['boton_texto'] ?? ''));
        $buttonUrl = trim((string)($item['boton_url'] ?? '#'));
        $isVisible = ((int)($item['visible'] ?? 1) === 1);

        if ($meta1Icon === '') {
            $meta1Icon = (string)($base['meta_1_icono'] ?? 'fa fa-user text-primary');
        }
        if ($meta2Icon === '') {
            $meta2Icon = (string)($base['meta_2_icono'] ?? 'fa fa-comment-alt text-primary');
        }

        $imageUrl = cw_novedades_resolve_item_image_url($item, $idx);
        $defaultImageUrl = cw_novedades_default_asset_url((string)($item['default_image'] ?? cw_novedades_default_image_for_position($idx)));

        $titleCounterId = 'cw_nv_count_titulo_' . $num;
        $meta1TextCounterId = 'cw_nv_count_meta1_texto_' . $num;
        $meta2TextCounterId = 'cw_nv_count_meta2_texto_' . $num;
        $badgeCounterId = 'cw_nv_count_badge_' . $num;
        $resumenCounterId = 'cw_nv_count_resumen_' . $num;
        $btnTextCounterId = 'cw_nv_count_btn_text_' . $num;
        $btnUrlCounterId = 'cw_nv_count_btn_url_' . $num;
        $removeId = 'cw_nv_remove_img_' . $num;
        $visibleId = 'cw_nv_visible_' . $num;
        ?>
        <div class="card card-outline card-light cw-nv-item mb-3" data-default-image="<?php echo cw_novedades_admin_h($defaultImageUrl); ?>">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <div>
              <strong class="cw-nv-item-title">Novedad <?php echo cw_novedades_admin_h((string)$num); ?></strong>
              <span class="badge badge-light ml-2 cw-nv-item-slide-label">Slide <?php echo cw_novedades_admin_h((string)$num); ?></span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger cw-nv-remove-item">Quitar</button>
          </div>
          <div class="card-body py-3">
            <input type="hidden" class="cw-nv-item-id" name="item_id[<?php echo cw_novedades_admin_h((string)$idx); ?>]" value="<?php echo cw_novedades_admin_h((string)($item['id'] ?? 0)); ?>">

            <div class="form-row">
              <div class="form-group col-md-8">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Titulo de la novedad</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_novedades_admin_h($titleCounterId); ?>"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($titulo, 110)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-nv-item-titulo"
                  name="item_titulo[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                  maxlength="110"
                  data-cw-counter="<?php echo cw_novedades_admin_h($titleCounterId); ?>"
                  value="<?php echo cw_novedades_admin_h($titulo); ?>"
                  placeholder="<?php echo cw_novedades_admin_h((string)($base['titulo'] ?? '')); ?>"
                >
              </div>
              <div class="form-group col-md-4">
                <label class="mb-1 d-block">Visibilidad</label>
                <div class="custom-control custom-checkbox mt-2">
                  <input type="hidden" class="cw-nv-item-visible-hidden" name="item_visible[<?php echo cw_novedades_admin_h((string)$idx); ?>]" value="<?php echo $isVisible ? '1' : '0'; ?>">
                  <input type="checkbox" class="custom-control-input cw-nv-item-visible-check" id="<?php echo cw_novedades_admin_h($visibleId); ?>" value="1" <?php echo $isVisible ? 'checked' : ''; ?>>
                  <label class="custom-control-label cw-nv-item-visible-label" for="<?php echo cw_novedades_admin_h($visibleId); ?>">Mostrar en web</label>
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-3">
                <label class="mb-1">Icono 1 (codigo)</label>
                <input
                  type="text"
                  class="form-control cw-nv-item-meta1-icon"
                  name="item_meta_1_icono[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                  maxlength="120"
                  value="<?php echo cw_novedades_admin_h($meta1Icon); ?>"
                  placeholder="fa fa-user text-primary"
                >
              </div>
              <div class="form-group col-md-3">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Texto 1</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_novedades_admin_h($meta1TextCounterId); ?>"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($meta1Text, 80)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-nv-item-meta1-text"
                  name="item_meta_1_texto[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                  maxlength="80"
                  data-cw-counter="<?php echo cw_novedades_admin_h($meta1TextCounterId); ?>"
                  value="<?php echo cw_novedades_admin_h($meta1Text); ?>"
                  placeholder="<?php echo cw_novedades_admin_h((string)($base['meta_1_texto'] ?? 'Autor')); ?>"
                >
              </div>
              <div class="form-group col-md-3">
                <label class="mb-1">Icono 2 (codigo)</label>
                <input
                  type="text"
                  class="form-control cw-nv-item-meta2-icon"
                  name="item_meta_2_icono[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                  maxlength="120"
                  value="<?php echo cw_novedades_admin_h($meta2Icon); ?>"
                  placeholder="fa fa-comment-alt text-primary"
                >
              </div>
              <div class="form-group col-md-3">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Texto 2</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_novedades_admin_h($meta2TextCounterId); ?>"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($meta2Text, 80)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-nv-item-meta2-text"
                  name="item_meta_2_texto[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                  maxlength="80"
                  data-cw-counter="<?php echo cw_novedades_admin_h($meta2TextCounterId); ?>"
                  value="<?php echo cw_novedades_admin_h($meta2Text); ?>"
                  placeholder="<?php echo cw_novedades_admin_h((string)($base['meta_2_texto'] ?? 'Sin comentarios')); ?>"
                >
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-3">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Texto del badge</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_novedades_admin_h($badgeCounterId); ?>"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($badgeText, 50)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-nv-item-badge"
                  name="item_badge_texto[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                  maxlength="50"
                  data-cw-counter="<?php echo cw_novedades_admin_h($badgeCounterId); ?>"
                  value="<?php echo cw_novedades_admin_h($badgeText); ?>"
                  placeholder="<?php echo cw_novedades_admin_h((string)($base['badge_texto'] ?? 'Novedad')); ?>"
                >
              </div>
              <div class="form-group col-md-3">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Texto del boton</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_novedades_admin_h($btnTextCounterId); ?>"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($buttonText, 50)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-nv-item-btn-text"
                  name="item_boton_texto[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                  maxlength="50"
                  data-cw-counter="<?php echo cw_novedades_admin_h($btnTextCounterId); ?>"
                  value="<?php echo cw_novedades_admin_h($buttonText); ?>"
                  placeholder="<?php echo cw_novedades_admin_h((string)($base['boton_texto'] ?? 'Read More')); ?>"
                >
              </div>
              <div class="form-group col-md-6">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Enlace del boton</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_novedades_admin_h($btnUrlCounterId); ?>"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($buttonUrl, 255)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-nv-item-btn-url"
                  name="item_boton_url[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                  maxlength="255"
                  data-cw-counter="<?php echo cw_novedades_admin_h($btnUrlCounterId); ?>"
                  value="<?php echo cw_novedades_admin_h($buttonUrl); ?>"
                  placeholder="# o /ruta o https://"
                >
              </div>
            </div>

            <div class="form-group">
              <div class="d-flex justify-content-between">
                <label class="mb-1">Texto resumen</label>
                <small class="text-muted cw-char-counter"><span id="<?php echo cw_novedades_admin_h($resumenCounterId); ?>"><?php echo cw_novedades_admin_h((string)cw_novedades_admin_remaining($resumenText, 220)); ?></span> restantes</small>
              </div>
              <textarea
                class="form-control cw-nv-item-resumen"
                name="item_resumen_texto[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                rows="3"
                maxlength="220"
                data-cw-counter="<?php echo cw_novedades_admin_h($resumenCounterId); ?>"
                placeholder="<?php echo cw_novedades_admin_h((string)($base['resumen_texto'] ?? 'Resumen corto de la novedad.')); ?>"
              ><?php echo cw_novedades_admin_h($resumenText); ?></textarea>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="mb-1">Imagen</label>
                <input
                  type="file"
                  class="form-control-file cw-nv-item-imagen"
                  name="item_imagen_archivo[<?php echo cw_novedades_admin_h((string)$idx); ?>]"
                  accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
                >
                <small class="form-text text-muted">Categoria: <strong>img_novedades</strong>.</small>

                <div class="custom-control custom-checkbox mt-2">
                  <input type="hidden" class="cw-nv-item-remove-hidden" name="item_eliminar_imagen[<?php echo cw_novedades_admin_h((string)$idx); ?>]" value="0">
                  <input type="checkbox" class="custom-control-input cw-nv-item-remove-check" id="<?php echo cw_novedades_admin_h($removeId); ?>" value="1">
                  <label class="custom-control-label cw-nv-item-remove-label" for="<?php echo cw_novedades_admin_h($removeId); ?>">Quitar imagen personalizada</label>
                </div>
              </div>

              <div class="form-group col-md-6">
                <label class="d-block mb-1">Vista previa</label>
                <div class="cw-nv-image-preview p-2 border rounded bg-light">
                  <img
                    class="cw-nv-item-preview-img img-fluid"
                    src="<?php echo cw_novedades_admin_h($imageUrl); ?>"
                    data-current-src="<?php echo cw_novedades_admin_h($imageUrl); ?>"
                    data-default-src="<?php echo cw_novedades_admin_h($defaultImageUrl); ?>"
                    alt="Preview novedad"
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-outline-primary" id="cw-nv-add-item">
        <i class="fas fa-plus mr-1"></i>Agregar novedad
      </button>
      <small class="text-muted ml-2">Maximo 9 novedades en total.</small>
    </div>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-nv-submit">Guardar novedades</button>
      <small class="text-muted mb-2">Debes dejar al menos 1 novedad visible para la web publica.</small>
    </div>
  </form>
</div>

