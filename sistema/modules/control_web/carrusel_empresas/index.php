<?php
// modules/control_web/carrusel_empresas/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_ce_admin_h')) {
    function cw_ce_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_ce_admin_remaining')) {
    function cw_ce_admin_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$data = [
    'config' => cw_ce_config_defaults(),
    'items' => cw_ce_normalize_items(cw_ce_defaults()['items'] ?? []),
];
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $data = cw_ce_fetch($cn);
    }
}

$configDefaults = cw_ce_config_defaults();
$config = is_array($data['config'] ?? null) ? $data['config'] : $configDefaults;
$items = cw_ce_normalize_items($data['items'] ?? []);

$tituloBase = trim((string)($config['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
if ($tituloBase === '') {
    $tituloBase = (string)($configDefaults['titulo_base'] ?? 'Customer');
}
if ($tituloResaltado === '') {
    $tituloResaltado = (string)($configDefaults['titulo_resaltado'] ?? 'Suport Center');
}

$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/carrusel_empresas/guardar.php';
$socialMeta = [
    'whatsapp' => ['icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp'],
    'facebook' => ['icon' => 'fab fa-facebook-f', 'label' => 'Facebook'],
    'instagram' => ['icon' => 'fab fa-instagram', 'label' => 'Instagram'],
    'youtube' => ['icon' => 'fab fa-youtube', 'label' => 'YouTube'],
];
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Carrusel Empresas</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Modulariza el bloque Customer Suport Center como carrusel de empresas. Minimo 1 empresa y maximo 15.
  </p>

  <div id="cw-ce-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-ce-form" action="<?php echo cw_ce_admin_h($guardarUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
    <h5 class="mb-2">1. Encabezado del bloque</h5>
    <p class="text-muted mb-3">
      Define el titulo principal en dos partes para respetar el estilo del tema (`h1` + `span`).
    </p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_ce_titulo_base" class="mb-1">Titulo 1</label>
          <small class="text-muted cw-char-counter"><span id="cw_ce_count_titulo_base"><?php echo cw_ce_admin_h((string)cw_ce_admin_remaining($tituloBase, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_ce_titulo_base"
          name="titulo_base"
          maxlength="40"
          data-cw-counter="cw_ce_count_titulo_base"
          value="<?php echo cw_ce_admin_h($tituloBase); ?>"
          placeholder="Customer"
        >
      </div>
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_ce_titulo_resaltado" class="mb-1">Titulo 2 (resaltado)</label>
          <small class="text-muted cw-char-counter"><span id="cw_ce_count_titulo_resaltado"><?php echo cw_ce_admin_h((string)cw_ce_admin_remaining($tituloResaltado, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_ce_titulo_resaltado"
          name="titulo_resaltado"
          maxlength="40"
          data-cw-counter="cw_ce_count_titulo_resaltado"
          value="<?php echo cw_ce_admin_h($tituloResaltado); ?>"
          placeholder="Suport Center"
        >
      </div>
    </div>

    <hr>

    <h5 class="mb-2">2. Empresas del carrusel</h5>
    <p class="text-muted mb-3">
      Puedes editar titulo, profesion, imagen y enlaces de redes. Redes fijas: WhatsApp, Facebook, Instagram y YouTube.
      Cada bloque debe mostrar al menos 1 red social.
    </p>

    <div id="cw-ce-items">
      <?php foreach ($items as $idx => $item): ?>
        <?php
          $num = $idx + 1;
          $titleCounterId = 'cw_ce_count_titulo_' . $num;
          $professionCounterId = 'cw_ce_count_profesion_' . $num;
          $removeId = 'cw_ce_remove_img_' . $num;

          $title = trim((string)($item['titulo'] ?? ''));
          $profession = trim((string)($item['profesion'] ?? ''));
          $imageUrl = cw_ce_resolve_item_image_url($item, $idx);
          $defaultImageUrl = cw_ce_default_asset_url((string)($item['default_image'] ?? cw_ce_default_image_for_position($idx)));
          $socials = cw_ce_normalize_socials($item['redes'] ?? [], $idx);
        ?>
        <div class="card card-outline card-light cw-ce-item mb-3" data-default-image="<?php echo cw_ce_admin_h($defaultImageUrl); ?>">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <div>
              <strong class="cw-ce-item-title">Empresa <?php echo cw_ce_admin_h((string)$num); ?></strong>
              <span class="badge badge-light ml-2 cw-ce-item-slide-label">Slide <?php echo cw_ce_admin_h((string)$num); ?></span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger cw-ce-remove-item">Quitar</button>
          </div>
          <div class="card-body py-3">
            <input type="hidden" class="cw-ce-item-id" name="item_id[<?php echo cw_ce_admin_h((string)$idx); ?>]" value="<?php echo cw_ce_admin_h((string)($item['id'] ?? 0)); ?>">

            <div class="form-row">
              <div class="form-group col-md-6">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Titulo</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_ce_admin_h($titleCounterId); ?>"><?php echo cw_ce_admin_h((string)cw_ce_admin_remaining($title, 80)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-ce-item-titulo"
                  name="item_titulo[<?php echo cw_ce_admin_h((string)$idx); ?>]"
                  maxlength="80"
                  data-cw-counter="<?php echo cw_ce_admin_h($titleCounterId); ?>"
                  value="<?php echo cw_ce_admin_h($title); ?>"
                  placeholder="Nombre de la empresa"
                >
              </div>

              <div class="form-group col-md-6">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Profesion</label>
                  <small class="text-muted cw-char-counter"><span id="<?php echo cw_ce_admin_h($professionCounterId); ?>"><?php echo cw_ce_admin_h((string)cw_ce_admin_remaining($profession, 80)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control cw-ce-item-profesion"
                  name="item_profesion[<?php echo cw_ce_admin_h((string)$idx); ?>]"
                  maxlength="80"
                  data-cw-counter="<?php echo cw_ce_admin_h($professionCounterId); ?>"
                  value="<?php echo cw_ce_admin_h($profession); ?>"
                  placeholder="Descripcion corta"
                >
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="mb-1">Imagen</label>
                <input
                  type="file"
                  class="form-control-file cw-ce-item-imagen"
                  name="item_imagen_archivo[<?php echo cw_ce_admin_h((string)$idx); ?>]"
                  accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
                >
                <small class="form-text text-muted">Categoria: <strong>img_carrusel_empresas</strong>.</small>

                <div class="custom-control custom-checkbox mt-2">
                  <input type="hidden" class="cw-ce-item-remove-hidden" name="item_eliminar_imagen[<?php echo cw_ce_admin_h((string)$idx); ?>]" value="0">
                  <input type="checkbox" class="custom-control-input cw-ce-item-remove-check" id="<?php echo cw_ce_admin_h($removeId); ?>" value="1">
                  <label class="custom-control-label cw-ce-item-remove-label" for="<?php echo cw_ce_admin_h($removeId); ?>">Quitar imagen personalizada</label>
                </div>
              </div>

              <div class="form-group col-md-6">
                <label class="d-block mb-1">Vista previa</label>
                <div class="cw-ce-image-preview p-2 border rounded bg-light">
                  <img
                    class="cw-ce-item-preview-img img-fluid"
                    src="<?php echo cw_ce_admin_h($imageUrl); ?>"
                    data-current-src="<?php echo cw_ce_admin_h($imageUrl); ?>"
                    data-default-src="<?php echo cw_ce_admin_h($defaultImageUrl); ?>"
                    alt="Preview empresa"
                  >
                </div>
              </div>
            </div>

            <div class="card card-outline card-secondary mt-2 mb-1">
              <div class="card-header py-2">
                <strong>Redes sociales (minimo 1 visible)</strong>
              </div>
              <div class="card-body py-2">
                <?php foreach (cw_ce_social_keys() as $socialKey): ?>
                  <?php
                    $meta = $socialMeta[$socialKey] ?? ['icon' => 'fas fa-link', 'label' => ucfirst($socialKey)];
                    $socialRow = (isset($socials[$socialKey]) && is_array($socials[$socialKey])) ? $socials[$socialKey] : ['visible' => 1, 'link' => '#'];
                    $visible = ((int)($socialRow['visible'] ?? 1) === 1);
                    $socialCheckId = 'cw_ce_social_' . $socialKey . '_' . $num;
                  ?>
                  <div class="form-row align-items-center cw-ce-social-row mb-2" data-network="<?php echo cw_ce_admin_h($socialKey); ?>">
                    <div class="col-md-3 mb-2 mb-md-0">
                      <div class="custom-control custom-checkbox">
                        <input type="hidden" class="cw-ce-social-visible-hidden" name="item_red_visible[<?php echo cw_ce_admin_h((string)$idx); ?>][<?php echo cw_ce_admin_h($socialKey); ?>]" value="<?php echo $visible ? '1' : '0'; ?>">
                        <input type="checkbox" class="custom-control-input cw-ce-social-visible-check" id="<?php echo cw_ce_admin_h($socialCheckId); ?>" value="1" <?php echo $visible ? 'checked' : ''; ?>>
                        <label class="custom-control-label small cw-ce-social-visible-label" for="<?php echo cw_ce_admin_h($socialCheckId); ?>">
                          <i class="<?php echo cw_ce_admin_h((string)$meta['icon']); ?> mr-1"></i>Mostrar
                        </label>
                      </div>
                    </div>
                    <div class="col-md-9">
                      <input
                        type="text"
                        class="form-control form-control-sm cw-ce-social-link"
                        name="item_red_link[<?php echo cw_ce_admin_h((string)$idx); ?>][<?php echo cw_ce_admin_h($socialKey); ?>]"
                        maxlength="255"
                        value="<?php echo cw_ce_admin_h((string)($socialRow['link'] ?? '#')); ?>"
                        placeholder="Enlace de <?php echo cw_ce_admin_h((string)$meta['label']); ?>"
                      >
                    </div>
                  </div>
                <?php endforeach; ?>
                <small class="text-muted">Orden fijo de iconos en la web: WhatsApp, Facebook, Instagram y YouTube.</small>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-outline-primary" id="cw-ce-add-item">
        <i class="fas fa-plus mr-1"></i>Agregar empresa
      </button>
      <small class="text-muted ml-2">Maximo 15 empresas en el carrusel.</small>
    </div>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-ce-submit">Guardar carrusel de empresas</button>
      <small class="text-muted mb-2">Los cambios impactan el bloque Customer Suport Center de la web publica.</small>
    </div>
  </form>
</div>
