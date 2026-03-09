<?php
// modules/control_web/banner/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_banner_admin_h')) {
    function cw_banner_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_banner_admin_remaining')) {
    function cw_banner_admin_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$bannerData = cw_banner_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $bannerData = cw_banner_fetch($cn);
    }
}

$defaults = cw_banner_defaults();
$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/banner/guardar.php';

$tituloSuperior = trim((string)($bannerData['titulo_superior'] ?? ''));
$tituloPrincipal = trim((string)($bannerData['titulo_principal'] ?? ''));
$descripcion = trim((string)($bannerData['descripcion'] ?? ''));
$boton1Texto = trim((string)($bannerData['boton_1_texto'] ?? ''));
$boton1Url = trim((string)($bannerData['boton_1_url'] ?? ''));
$boton2Texto = trim((string)($bannerData['boton_2_texto'] ?? ''));
$boton2Url = trim((string)($bannerData['boton_2_url'] ?? ''));
$imagenPath = trim((string)($bannerData['imagen_path'] ?? ''));

if ($tituloSuperior === '') {
    $tituloSuperior = $defaults['titulo_superior'];
}
if ($tituloPrincipal === '') {
    $tituloPrincipal = $defaults['titulo_principal'];
}
if ($descripcion === '') {
    $descripcion = $defaults['descripcion'];
}
if ($boton1Texto === '') {
    $boton1Texto = $defaults['boton_1_texto'];
}
if ($boton1Url === '') {
    $boton1Url = $defaults['boton_1_url'];
}
if ($boton2Texto === '') {
    $boton2Texto = $defaults['boton_2_texto'];
}
if ($boton2Url === '') {
    $boton2Url = $defaults['boton_2_url'];
}

$imagenActualUrl = cw_banner_resolve_image_url($imagenPath);
$imagenDefaultUrl = cw_banner_default_image_url();
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Banner</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Configura el banner de promocion: 3 textos, 2 botones y la imagen principal.
  </p>

  <div id="cw-banner-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-banner-form" action="<?php echo cw_banner_admin_h($guardarUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
    <h5 class="mb-2">1. Textos del banner</h5>
    <p class="text-muted mb-3">Ajusta los tres textos principales del bloque promocional.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_banner_titulo_superior" class="mb-1">Titulo superior</label>
          <small class="text-muted cw-char-counter"><span id="cw_banner_count_titulo_superior"><?php echo cw_banner_admin_h((string)cw_banner_admin_remaining($tituloSuperior, 60)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_banner_titulo_superior"
          name="titulo_superior"
          maxlength="60"
          data-cw-counter="cw_banner_count_titulo_superior"
          value="<?php echo cw_banner_admin_h($tituloSuperior); ?>"
          placeholder="Rent Your Car"
        >
      </div>
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_banner_titulo_principal" class="mb-1">Titulo principal</label>
          <small class="text-muted cw-char-counter"><span id="cw_banner_count_titulo_principal"><?php echo cw_banner_admin_h((string)cw_banner_admin_remaining($tituloPrincipal, 100)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_banner_titulo_principal"
          name="titulo_principal"
          maxlength="100"
          data-cw-counter="cw_banner_count_titulo_principal"
          value="<?php echo cw_banner_admin_h($tituloPrincipal); ?>"
          placeholder="Interested in Renting?"
        >
      </div>
    </div>

    <div class="form-group">
      <div class="d-flex justify-content-between">
        <label for="cw_banner_descripcion" class="mb-1">Descripcion</label>
        <small class="text-muted cw-char-counter"><span id="cw_banner_count_descripcion"><?php echo cw_banner_admin_h((string)cw_banner_admin_remaining($descripcion, 220)); ?></span> restantes</small>
      </div>
      <textarea
        class="form-control"
        id="cw_banner_descripcion"
        name="descripcion"
        rows="3"
        maxlength="220"
        data-cw-counter="cw_banner_count_descripcion"
        placeholder="Don't hesitate and send us a message."
      ><?php echo cw_banner_admin_h($descripcion); ?></textarea>
    </div>

    <hr>

    <h5 class="mb-2">2. Botones</h5>
    <p class="text-muted mb-3">Configura texto y enlace de ambos botones.</p>

    <div class="card card-outline card-light mb-3">
      <div class="card-header py-2">
        <strong>Boton 1 (Secundario)</strong>
      </div>
      <div class="card-body py-3">
        <div class="form-row">
          <div class="form-group col-md-5">
            <div class="d-flex justify-content-between">
              <label for="cw_banner_boton_1_texto" class="mb-1">Texto</label>
              <small class="text-muted cw-char-counter"><span id="cw_banner_count_boton_1_texto"><?php echo cw_banner_admin_h((string)cw_banner_admin_remaining($boton1Texto, 40)); ?></span> restantes</small>
            </div>
            <input
              type="text"
              class="form-control"
              id="cw_banner_boton_1_texto"
              name="boton_1_texto"
              maxlength="40"
              data-cw-counter="cw_banner_count_boton_1_texto"
              value="<?php echo cw_banner_admin_h($boton1Texto); ?>"
              placeholder="WhatchApp"
            >
          </div>
          <div class="form-group col-md-7">
            <label for="cw_banner_boton_1_url">Enlace</label>
            <input
              type="text"
              class="form-control"
              id="cw_banner_boton_1_url"
              name="boton_1_url"
              maxlength="255"
              value="<?php echo cw_banner_admin_h($boton1Url); ?>"
              placeholder="# o /ruta"
            >
          </div>
        </div>
      </div>
    </div>

    <div class="card card-outline card-light mb-3">
      <div class="card-header py-2">
        <strong>Boton 2 (Primario)</strong>
      </div>
      <div class="card-body py-3">
        <div class="form-row">
          <div class="form-group col-md-5">
            <div class="d-flex justify-content-between">
              <label for="cw_banner_boton_2_texto" class="mb-1">Texto</label>
              <small class="text-muted cw-char-counter"><span id="cw_banner_count_boton_2_texto"><?php echo cw_banner_admin_h((string)cw_banner_admin_remaining($boton2Texto, 40)); ?></span> restantes</small>
            </div>
            <input
              type="text"
              class="form-control"
              id="cw_banner_boton_2_texto"
              name="boton_2_texto"
              maxlength="40"
              data-cw-counter="cw_banner_count_boton_2_texto"
              value="<?php echo cw_banner_admin_h($boton2Texto); ?>"
              placeholder="Contact Us"
            >
          </div>
          <div class="form-group col-md-7">
            <label for="cw_banner_boton_2_url">Enlace</label>
            <input
              type="text"
              class="form-control"
              id="cw_banner_boton_2_url"
              name="boton_2_url"
              maxlength="255"
              value="<?php echo cw_banner_admin_h($boton2Url); ?>"
              placeholder="# o /ruta"
            >
          </div>
        </div>
      </div>
    </div>

    <hr>

    <h5 class="mb-2">3. Imagen del banner</h5>
    <p class="text-muted mb-3">Si no hay imagen personalizada se usa la imagen por defecto.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="cw_banner_imagen_archivo">Imagen</label>
        <input
          type="file"
          class="form-control-file"
          id="cw_banner_imagen_archivo"
          name="imagen_archivo"
          accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
        >
        <small class="form-text text-muted">Categoria: <strong>img_banner</strong>.</small>
      </div>
      <div class="form-group col-md-6">
        <label class="d-block">Vista previa</label>
        <div class="cw-banner-image-preview p-2 border rounded bg-light">
          <img
            id="cw-banner-preview-img"
            src="<?php echo cw_banner_admin_h($imagenActualUrl); ?>"
            data-current-src="<?php echo cw_banner_admin_h($imagenActualUrl); ?>"
            data-default-src="<?php echo cw_banner_admin_h($imagenDefaultUrl); ?>"
            alt="Imagen del banner"
            class="img-fluid"
          >
        </div>
      </div>
    </div>

    <div class="custom-control custom-checkbox mb-2">
      <input type="checkbox" class="custom-control-input" id="cw_banner_eliminar_imagen" name="eliminar_imagen" value="1">
      <label class="custom-control-label" for="cw_banner_eliminar_imagen">Quitar imagen personalizada y usar la imagen por defecto</label>
    </div>

    <div class="d-flex flex-wrap align-items-center mt-3">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-banner-submit">Guardar banner</button>
      <small class="text-muted mb-2">Los cambios se reflejan en el bloque promocional de la pagina principal.</small>
    </div>
  </form>
</div>
