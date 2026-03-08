<?php
// modules/control_web/cabecera/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_h')) {
    function cw_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$topbar = cw_topbar_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $topbar = cw_topbar_fetch($cn);
    }
}

$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/cabecera/guardar.php';
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Cabecera</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Configura los datos de contacto y los enlaces de redes sociales que se muestran en la franja superior del sitio web.
  </p>

  <div id="cw-topbar-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-topbar-form" action="<?php echo cw_h($guardarUrl); ?>" method="post" novalidate>
    <h5 class="mb-2">1. Contacto principal</h5>
    <p class="text-muted mb-3">Los iconos son fijos. Solo cambia direccion, celular y correo.</p>

    <div class="form-row">
      <div class="form-group col-md-12">
        <label for="cw_direccion">Direccion</label>
        <input
          type="text"
          class="form-control"
          id="cw_direccion"
          name="direccion"
          maxlength="180"
          required
          value="<?php echo cw_h($topbar['direccion']); ?>"
          placeholder="Ejemplo: Av. Principal 123, Lima"
        >
      </div>
      <div class="form-group col-md-6">
        <label for="cw_telefono">Celular</label>
        <input
          type="text"
          class="form-control"
          id="cw_telefono"
          name="telefono"
          maxlength="9"
          minlength="9"
          pattern="9[0-9]{8}"
          required
          value="<?php echo cw_h($topbar['telefono']); ?>"
          placeholder="9XXXXXXXX"
        >
        <small class="form-text text-muted">Debe tener 9 digitos e iniciar con 9.</small>
      </div>
      <div class="form-group col-md-6">
        <label for="cw_correo">Correo</label>
        <input
          type="email"
          class="form-control"
          id="cw_correo"
          name="correo"
          maxlength="120"
          required
          value="<?php echo cw_h($topbar['correo']); ?>"
          placeholder="correo@dominio.com"
        >
      </div>
    </div>

    <hr>

    <h5 class="mb-2">2. Redes sociales</h5>
    <p class="text-muted mb-3">
      Redes habilitadas: WhatsApp, Facebook, Instagram y YouTube. WhatsApp es obligatorio.
    </p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="cw_whatsapp_url"><i class="fab fa-whatsapp mr-1"></i>WhatsApp</label>
        <input
          type="text"
          class="form-control"
          id="cw_whatsapp_url"
          name="whatsapp_url"
          maxlength="255"
          required
          value="<?php echo cw_h($topbar['whatsapp_url']); ?>"
          placeholder="https://wa.me/51912345678"
        >
      </div>
      <div class="form-group col-md-6">
        <label for="cw_facebook_url"><i class="fab fa-facebook-f mr-1"></i>Facebook</label>
        <input
          type="text"
          class="form-control"
          id="cw_facebook_url"
          name="facebook_url"
          maxlength="255"
          value="<?php echo cw_h($topbar['facebook_url']); ?>"
          placeholder="https://facebook.com/tu_pagina"
        >
      </div>
      <div class="form-group col-md-6">
        <label for="cw_instagram_url"><i class="fab fa-instagram mr-1"></i>Instagram</label>
        <input
          type="text"
          class="form-control"
          id="cw_instagram_url"
          name="instagram_url"
          maxlength="255"
          value="<?php echo cw_h($topbar['instagram_url']); ?>"
          placeholder="https://instagram.com/tu_cuenta"
        >
      </div>
      <div class="form-group col-md-6">
        <label for="cw_youtube_url"><i class="fab fa-youtube mr-1"></i>YouTube</label>
        <input
          type="text"
          class="form-control"
          id="cw_youtube_url"
          name="youtube_url"
          maxlength="255"
          value="<?php echo cw_h($topbar['youtube_url']); ?>"
          placeholder="https://youtube.com/@tu_canal"
        >
      </div>
    </div>

    <div class="d-flex flex-wrap align-items-center">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-topbar-submit">
        Guardar cambios
      </button>
      <small class="text-muted mb-2">Al guardar, la cabecera de `index.php` se actualiza con estos datos.</small>
    </div>
  </form>
</div>
