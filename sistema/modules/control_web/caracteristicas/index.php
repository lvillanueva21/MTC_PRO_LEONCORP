<?php
// modules/control_web/caracteristicas/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_feat_h')) {
    function cw_feat_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_feat_remaining')) {
    function cw_feat_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$featuresData = cw_features_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $featuresData = cw_features_fetch($cn);
    }
}

$defaults = cw_features_defaults();
$items = cw_features_normalize_items($featuresData['items'] ?? []);
$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/caracteristicas/guardar.php';

$tituloRojo = trim((string)($featuresData['titulo_rojo'] ?? ''));
$tituloAzul = trim((string)($featuresData['titulo_azul'] ?? ''));
$descripcionGeneral = trim((string)($featuresData['descripcion_general'] ?? ''));

if ($tituloRojo === '') {
    $tituloRojo = $defaults['titulo_rojo'];
}
if ($tituloAzul === '') {
    $tituloAzul = $defaults['titulo_azul'];
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = $defaults['descripcion_general'];
}

$imagenActualUrl = cw_features_resolve_image_url((string)($featuresData['imagen_path'] ?? ''));
$imagenDefaultUrl = cw_features_default_image_url();
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Caracteristicas</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Configura el titulo, imagen central y las cuatro caracteristicas principales del bloque "Central Features".
  </p>

  <div id="cw-features-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-features-form" action="<?php echo cw_feat_h($guardarUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
    <h5 class="mb-2">1. Titulo de caracteristicas</h5>
    <p class="text-muted mb-3">Primera parte: texto base del H1 (azul oscuro del tema). Segunda parte: texto resaltado en rojo con <code>text-primary</code>.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_feat_titulo_rojo" class="mb-1">Texto principal (azul oscuro)</label>
          <small class="text-muted cw-char-counter"><span id="cw_feat_count_titulo_rojo"><?php echo cw_feat_h((string)cw_feat_remaining($tituloRojo, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_feat_titulo_rojo"
          name="titulo_rojo"
          maxlength="40"
          data-cw-counter="cw_feat_count_titulo_rojo"
          value="<?php echo cw_feat_h($tituloRojo); ?>"
          placeholder="Central"
        >
      </div>
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_feat_titulo_azul" class="mb-1">Texto resaltado (rojo)</label>
          <small class="text-muted cw-char-counter"><span id="cw_feat_count_titulo_azul"><?php echo cw_feat_h((string)cw_feat_remaining($tituloAzul, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_feat_titulo_azul"
          name="titulo_azul"
          maxlength="40"
          data-cw-counter="cw_feat_count_titulo_azul"
          value="<?php echo cw_feat_h($tituloAzul); ?>"
          placeholder="Features"
        >
      </div>
    </div>

    <hr>

    <h5 class="mb-2">2. Imagen central</h5>
    <p class="text-muted mb-3">Si subes una nueva imagen se reemplaza la anterior. Si no hay imagen personalizada, se usa la imagen por defecto.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="cw_feat_imagen_archivo">Imagen de caracteristicas</label>
        <input
          type="file"
          class="form-control-file"
          id="cw_feat_imagen_archivo"
          name="imagen_archivo"
          accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
        >
        <small class="form-text text-muted">Categoria del gestor de archivos: <strong>img_caracteristica</strong>.</small>
      </div>
      <div class="form-group col-md-6">
        <label class="d-block">Vista previa</label>
        <div class="cw-features-image-preview p-2 border rounded bg-light">
          <img
            id="cw-features-preview-img"
            src="<?php echo cw_feat_h($imagenActualUrl); ?>"
            data-current-src="<?php echo cw_feat_h($imagenActualUrl); ?>"
            data-default-src="<?php echo cw_feat_h($imagenDefaultUrl); ?>"
            alt="Imagen central de caracteristicas"
            class="img-fluid"
          >
        </div>
      </div>
    </div>

    <div class="custom-control custom-checkbox mb-2">
      <input type="checkbox" class="custom-control-input" id="cw_feat_eliminar_imagen" name="eliminar_imagen" value="1">
      <label class="custom-control-label" for="cw_feat_eliminar_imagen">Quitar imagen personalizada y usar la imagen por defecto</label>
    </div>

    <hr>

    <h5 class="mb-2">3. Texto central</h5>
    <p class="text-muted mb-2">Descripcion general que se muestra bajo el titulo.</p>
    <div class="form-group">
      <div class="d-flex justify-content-between">
        <label for="cw_feat_descripcion_general" class="mb-1">Descripcion general</label>
        <small class="text-muted cw-char-counter"><span id="cw_feat_count_descripcion"><?php echo cw_feat_h((string)cw_feat_remaining($descripcionGeneral, 320)); ?></span> restantes</small>
      </div>
      <textarea
        class="form-control"
        id="cw_feat_descripcion_general"
        name="descripcion_general"
        rows="3"
        maxlength="320"
        data-cw-counter="cw_feat_count_descripcion"
        placeholder="Descripcion breve de las caracteristicas"
      ><?php echo cw_feat_h($descripcionGeneral); ?></textarea>
    </div>

    <hr>

    <h5 class="mb-2">4. Descripcion de caracteristicas</h5>
    <p class="text-muted mb-3">Personaliza icono, titulo y texto de cada bloque. Si un campo queda vacio, se usara el valor por defecto.</p>

    <?php foreach ($items as $idx => $item): ?>
      <?php
        $num = $idx + 1;
        $defaultItem = $defaults['items'][$idx];
        $titleCounterId = 'cw_feat_count_item_titulo_' . $num;
        $textCounterId = 'cw_feat_count_item_texto_' . $num;
      ?>
      <div class="card card-outline card-light cw-feature-card mb-3">
        <div class="card-header py-2">
          <strong>Caracteristica <?php echo cw_feat_h((string)$num); ?></strong>
        </div>
        <div class="card-body py-3">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="cw_feat_icono_<?php echo cw_feat_h((string)$num); ?>">Codigo de icono</label>
              <input
                type="text"
                class="form-control"
                id="cw_feat_icono_<?php echo cw_feat_h((string)$num); ?>"
                name="item_icono[]"
                maxlength="120"
                value="<?php echo cw_feat_h($item['icono']); ?>"
                placeholder="<?php echo cw_feat_h($defaultItem['icono']); ?>"
              >
              <small class="form-text text-muted">Ejemplos: <code>fa fa-trophy fa-2x</code>, <code>fas fa-star</code>, <code>bi bi-award</code></small>
            </div>
            <div class="form-group col-md-4">
              <div class="d-flex justify-content-between">
                <label for="cw_feat_item_titulo_<?php echo cw_feat_h((string)$num); ?>" class="mb-1">Titulo</label>
                <small class="text-muted cw-char-counter"><span id="<?php echo cw_feat_h($titleCounterId); ?>"><?php echo cw_feat_h((string)cw_feat_remaining($item['titulo'], 70)); ?></span> restantes</small>
              </div>
              <input
                type="text"
                class="form-control"
                id="cw_feat_item_titulo_<?php echo cw_feat_h((string)$num); ?>"
                name="item_titulo[]"
                maxlength="70"
                data-cw-counter="<?php echo cw_feat_h($titleCounterId); ?>"
                value="<?php echo cw_feat_h($item['titulo']); ?>"
                placeholder="<?php echo cw_feat_h($defaultItem['titulo']); ?>"
              >
            </div>
            <div class="form-group col-md-4">
              <div class="d-flex justify-content-between">
                <label for="cw_feat_item_texto_<?php echo cw_feat_h((string)$num); ?>" class="mb-1">Texto</label>
                <small class="text-muted cw-char-counter"><span id="<?php echo cw_feat_h($textCounterId); ?>"><?php echo cw_feat_h((string)cw_feat_remaining($item['texto'], 220)); ?></span> restantes</small>
              </div>
              <textarea
                class="form-control"
                id="cw_feat_item_texto_<?php echo cw_feat_h((string)$num); ?>"
                name="item_texto[]"
                rows="2"
                maxlength="220"
                data-cw-counter="<?php echo cw_feat_h($textCounterId); ?>"
                placeholder="<?php echo cw_feat_h($defaultItem['texto']); ?>"
              ><?php echo cw_feat_h($item['texto']); ?></textarea>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-features-submit">Guardar caracteristicas</button>
      <small class="text-muted mb-2">Los cambios se veran en la seccion de caracteristicas de la pagina principal.</small>
    </div>
  </form>
</div>
