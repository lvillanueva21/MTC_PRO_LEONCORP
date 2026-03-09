<?php
// modules/control_web/testimonios/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_testimonios_admin_h')) {
    function cw_testimonios_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_testimonios_admin_remaining')) {
    function cw_testimonios_admin_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$data = [
    'config' => cw_testimonios_config_defaults(),
    'items' => cw_testimonios_normalize_items(cw_testimonios_defaults()['items'] ?? []),
];
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $data = cw_testimonios_fetch($cn);
    }
}

$configDefaults = cw_testimonios_config_defaults();
$config = cw_testimonios_normalize_config($data['config'] ?? []);
$items = cw_testimonios_normalize_items($data['items'] ?? []);

$tituloBase = trim((string)($config['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($config['descripcion_general'] ?? ''));

if ($tituloBase === '') {
    $tituloBase = (string)($configDefaults['titulo_base'] ?? 'Our Clients');
}
if ($tituloResaltado === '') {
    $tituloResaltado = (string)($configDefaults['titulo_resaltado'] ?? 'Riviews');
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = (string)($configDefaults['descripcion_general'] ?? '');
}

$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/testimonios/guardar.php';
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Testimonios</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Configura el bloque de testimonios con titulo en 2 partes, descripcion central y 2 tarjetas fijas de clientes.
  </p>

  <div id="cw-testimonios-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-testimonios-form" action="<?php echo cw_testimonios_admin_h($guardarUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
    <h5 class="mb-2">1. Encabezado del modulo</h5>
    <p class="text-muted mb-3">
      El titulo se divide en dos partes para respetar el estilo del h1 y el span.
    </p>

    <div class="form-row">
      <div class="form-group col-md-4">
        <div class="d-flex justify-content-between">
          <label for="cw_testimonios_titulo_base" class="mb-1">Titulo 1</label>
          <small class="text-muted cw-char-counter"><span id="cw_testimonios_count_titulo_base"><?php echo cw_testimonios_admin_h((string)cw_testimonios_admin_remaining($tituloBase, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_testimonios_titulo_base"
          name="titulo_base"
          maxlength="40"
          data-cw-counter="cw_testimonios_count_titulo_base"
          value="<?php echo cw_testimonios_admin_h($tituloBase); ?>"
          placeholder="Our Clients"
        >
      </div>
      <div class="form-group col-md-4">
        <div class="d-flex justify-content-between">
          <label for="cw_testimonios_titulo_resaltado" class="mb-1">Titulo 2 (resaltado)</label>
          <small class="text-muted cw-char-counter"><span id="cw_testimonios_count_titulo_resaltado"><?php echo cw_testimonios_admin_h((string)cw_testimonios_admin_remaining($tituloResaltado, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_testimonios_titulo_resaltado"
          name="titulo_resaltado"
          maxlength="40"
          data-cw-counter="cw_testimonios_count_titulo_resaltado"
          value="<?php echo cw_testimonios_admin_h($tituloResaltado); ?>"
          placeholder="Riviews"
        >
      </div>
      <div class="form-group col-md-4">
        <div class="d-flex justify-content-between">
          <label for="cw_testimonios_descripcion_general" class="mb-1">Descripcion central</label>
          <small class="text-muted cw-char-counter"><span id="cw_testimonios_count_descripcion_general"><?php echo cw_testimonios_admin_h((string)cw_testimonios_admin_remaining($descripcionGeneral, 260)); ?></span> restantes</small>
        </div>
        <textarea
          class="form-control"
          id="cw_testimonios_descripcion_general"
          name="descripcion_general"
          rows="3"
          maxlength="260"
          data-cw-counter="cw_testimonios_count_descripcion_general"
          placeholder="Texto central del modulo"
        ><?php echo cw_testimonios_admin_h($descripcionGeneral); ?></textarea>
      </div>
    </div>

    <hr>

    <h5 class="mb-2">2. Tarjetas de clientes (2 fijas)</h5>
    <p class="text-muted mb-3">
      Puedes editar nombre, profesion, testimonio e imagen en cada tarjeta. Si dejas campos vacios se usa el contenido por defecto.
    </p>

    <div id="cw-testimonios-items">
      <?php foreach ($items as $idx => $item): ?>
        <?php
        $num = $idx + 1;
        $nombre = trim((string)($item['nombre_cliente'] ?? ''));
        $profesion = trim((string)($item['profesion'] ?? ''));
        $testimonio = trim((string)($item['testimonio'] ?? ''));
        $imageUrl = cw_testimonios_resolve_item_image_url($item, $idx);
        $defaultImageUrl = cw_testimonios_default_asset_url((string)($item['default_image'] ?? cw_testimonios_default_image_for_position($idx)));
        $removeId = 'cw_testimonios_remove_img_' . $num;
        ?>
        <div class="card card-outline card-light cw-testimonios-item mb-3" data-default-image="<?php echo cw_testimonios_admin_h($defaultImageUrl); ?>">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Testimonio <?php echo cw_testimonios_admin_h((string)$num); ?></strong>
            <span class="badge badge-light">Bloque fijo</span>
          </div>
          <div class="card-body py-3">
            <input type="hidden" name="item_orden[<?php echo cw_testimonios_admin_h((string)$idx); ?>]" value="<?php echo cw_testimonios_admin_h((string)$num); ?>">

            <div class="form-row">
              <div class="form-group col-md-6">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Nombre de cliente</label>
                  <small class="text-muted cw-char-counter"><span id="cw_testimonios_count_nombre_<?php echo cw_testimonios_admin_h((string)$num); ?>"><?php echo cw_testimonios_admin_h((string)cw_testimonios_admin_remaining($nombre, 80)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control"
                  name="item_nombre_cliente[<?php echo cw_testimonios_admin_h((string)$idx); ?>]"
                  maxlength="80"
                  data-cw-counter="cw_testimonios_count_nombre_<?php echo cw_testimonios_admin_h((string)$num); ?>"
                  value="<?php echo cw_testimonios_admin_h($nombre); ?>"
                  placeholder="Person Name"
                >
              </div>
              <div class="form-group col-md-6">
                <div class="d-flex justify-content-between">
                  <label class="mb-1">Profesion</label>
                  <small class="text-muted cw-char-counter"><span id="cw_testimonios_count_profesion_<?php echo cw_testimonios_admin_h((string)$num); ?>"><?php echo cw_testimonios_admin_h((string)cw_testimonios_admin_remaining($profesion, 80)); ?></span> restantes</small>
                </div>
                <input
                  type="text"
                  class="form-control"
                  name="item_profesion[<?php echo cw_testimonios_admin_h((string)$idx); ?>]"
                  maxlength="80"
                  data-cw-counter="cw_testimonios_count_profesion_<?php echo cw_testimonios_admin_h((string)$num); ?>"
                  value="<?php echo cw_testimonios_admin_h($profesion); ?>"
                  placeholder="Profession"
                >
              </div>
            </div>

            <div class="form-group">
              <div class="d-flex justify-content-between">
                <label class="mb-1">Testimonio</label>
                <small class="text-muted cw-char-counter"><span id="cw_testimonios_count_testimonio_<?php echo cw_testimonios_admin_h((string)$num); ?>"><?php echo cw_testimonios_admin_h((string)cw_testimonios_admin_remaining($testimonio, 280)); ?></span> restantes</small>
              </div>
              <textarea
                class="form-control"
                name="item_testimonio[<?php echo cw_testimonios_admin_h((string)$idx); ?>]"
                rows="3"
                maxlength="280"
                data-cw-counter="cw_testimonios_count_testimonio_<?php echo cw_testimonios_admin_h((string)$num); ?>"
                placeholder="Texto del testimonio"
              ><?php echo cw_testimonios_admin_h($testimonio); ?></textarea>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label class="mb-1">Imagen</label>
                <input
                  type="file"
                  class="form-control-file cw-testimonios-item-imagen"
                  name="item_imagen_archivo[<?php echo cw_testimonios_admin_h((string)$idx); ?>]"
                  accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
                >
                <small class="form-text text-muted">Categoria: <strong>img_testimonios</strong>.</small>

                <div class="custom-control custom-checkbox mt-2">
                  <input type="hidden" class="cw-testimonios-item-remove-hidden" name="item_eliminar_imagen[<?php echo cw_testimonios_admin_h((string)$idx); ?>]" value="0">
                  <input type="checkbox" class="custom-control-input cw-testimonios-item-remove-check" id="<?php echo cw_testimonios_admin_h($removeId); ?>" value="1">
                  <label class="custom-control-label" for="<?php echo cw_testimonios_admin_h($removeId); ?>">Quitar imagen personalizada</label>
                </div>
              </div>
              <div class="form-group col-md-6">
                <label class="d-block mb-1">Vista previa</label>
                <div class="cw-testimonios-image-preview p-2 border rounded bg-light">
                  <img
                    class="cw-testimonios-item-preview-img img-fluid"
                    src="<?php echo cw_testimonios_admin_h($imageUrl); ?>"
                    data-current-src="<?php echo cw_testimonios_admin_h($imageUrl); ?>"
                    data-default-src="<?php echo cw_testimonios_admin_h($defaultImageUrl); ?>"
                    alt="Preview testimonio <?php echo cw_testimonios_admin_h((string)$num); ?>"
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-testimonios-submit">Guardar testimonios</button>
      <small class="text-muted mb-2">El modulo mantiene 2 tarjetas y 5 estrellas fijas por tarjeta.</small>
    </div>
  </form>
</div>
