<?php
// modules/control_web/nosotros/index.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/model.php';

acl_require_ids([1]);
verificarPermiso(['Desarrollo']);

if (!function_exists('cw_about_admin_h')) {
    function cw_about_admin_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cw_about_admin_remaining')) {
    function cw_about_admin_remaining(string $value, int $max): int
    {
        $len = function_exists('mb_strlen')
            ? (int)mb_strlen($value, 'UTF-8')
            : strlen($value);

        $rest = $max - $len;
        return $rest > 0 ? $rest : 0;
    }
}

$aboutData = cw_about_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $aboutData = cw_about_fetch($cn);
    }
}

$defaults = cw_about_defaults();
$cards = cw_about_normalize_cards($aboutData['tarjetas'] ?? []);
$checklist = cw_about_normalize_checklist($aboutData['checklist'] ?? []);

$guardarUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/control_web/nosotros/guardar.php';

$tituloBase = trim((string)($aboutData['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($aboutData['titulo_resaltado'] ?? ''));
$descripcionPrincipal = trim((string)($aboutData['descripcion_principal'] ?? ''));
$descripcionSecundaria = trim((string)($aboutData['descripcion_secundaria'] ?? ''));
$experienciaNumero = trim((string)($aboutData['experiencia_numero'] ?? ''));
$experienciaTexto = trim((string)($aboutData['experiencia_texto'] ?? ''));
$botonTexto = trim((string)($aboutData['boton_texto'] ?? ''));
$botonUrl = trim((string)($aboutData['boton_url'] ?? ''));
$fundadorNombre = trim((string)($aboutData['fundador_nombre'] ?? ''));
$fundadorCargo = trim((string)($aboutData['fundador_cargo'] ?? ''));

if ($tituloBase === '') {
    $tituloBase = $defaults['titulo_base'];
}
if ($tituloResaltado === '') {
    $tituloResaltado = $defaults['titulo_resaltado'];
}
if ($descripcionPrincipal === '') {
    $descripcionPrincipal = $defaults['descripcion_principal'];
}
if ($descripcionSecundaria === '') {
    $descripcionSecundaria = $defaults['descripcion_secundaria'];
}
if ($experienciaNumero === '') {
    $experienciaNumero = $defaults['experiencia_numero'];
}
if ($experienciaTexto === '') {
    $experienciaTexto = $defaults['experiencia_texto'];
}
if ($botonTexto === '') {
    $botonTexto = $defaults['boton_texto'];
}
if ($botonUrl === '') {
    $botonUrl = $defaults['boton_url'];
}
if ($fundadorNombre === '') {
    $fundadorNombre = $defaults['fundador_nombre'];
}
if ($fundadorCargo === '') {
    $fundadorCargo = $defaults['fundador_cargo'];
}

$icono1ActualUrl = cw_about_resolve_image_url((string)($cards[0]['icono_path'] ?? ''), '/web/img/about-icon-1.png');
$icono2ActualUrl = cw_about_resolve_image_url((string)($cards[1]['icono_path'] ?? ''), '/web/img/about-icon-2.png');
$icono1DefaultUrl = cw_about_default_asset_url('/web/img/about-icon-1.png');
$icono2DefaultUrl = cw_about_default_asset_url('/web/img/about-icon-2.png');
$imagenFundadorActualUrl = cw_about_resolve_image_url((string)($aboutData['imagen_fundador_path'] ?? ''), '/web/img/attachment-img.jpg');
$imagenFundadorDefaultUrl = cw_about_default_asset_url('/web/img/attachment-img.jpg');
$imagenPrincipalActualUrl = cw_about_resolve_image_url((string)($aboutData['imagen_principal_path'] ?? ''), '/web/img/about-img.jpg');
$imagenPrincipalDefaultUrl = cw_about_default_asset_url('/web/img/about-img.jpg');
$imagenSecundariaActualUrl = cw_about_resolve_image_url((string)($aboutData['imagen_secundaria_path'] ?? ''), '/web/img/about-img-1.jpg');
$imagenSecundariaDefaultUrl = cw_about_default_asset_url('/web/img/about-img-1.jpg');
?>

<div class="card-header border-0">
  <h3 class="card-title mb-0">Nosotros</h3>
</div>
<div class="card-body">
  <p class="text-muted mb-3">
    Configura el bloque central "About / Nosotros": textos, tarjetas, checklist, CTA y las imagenes de la seccion.
  </p>

  <div id="cw-about-alert" class="cw-inline-alert mb-3" style="display:none;"></div>

  <form id="cw-about-form" action="<?php echo cw_about_admin_h($guardarUrl); ?>" method="post" enctype="multipart/form-data" novalidate>
    <h5 class="mb-2">1. Encabezado de la seccion</h5>
    <p class="text-muted mb-3">Edita el titulo principal y la descripcion inicial del bloque.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_about_titulo_base" class="mb-1">Texto base (oscuro)</label>
          <small class="text-muted cw-char-counter"><span id="cw_about_count_titulo_base"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($tituloBase, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_about_titulo_base"
          name="titulo_base"
          maxlength="40"
          data-cw-counter="cw_about_count_titulo_base"
          value="<?php echo cw_about_admin_h($tituloBase); ?>"
          placeholder="Cental"
        >
      </div>
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_about_titulo_resaltado" class="mb-1">Texto resaltado (primary)</label>
          <small class="text-muted cw-char-counter"><span id="cw_about_count_titulo_resaltado"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($tituloResaltado, 40)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_about_titulo_resaltado"
          name="titulo_resaltado"
          maxlength="40"
          data-cw-counter="cw_about_count_titulo_resaltado"
          value="<?php echo cw_about_admin_h($tituloResaltado); ?>"
          placeholder="About"
        >
      </div>
    </div>

    <div class="form-group">
      <div class="d-flex justify-content-between">
        <label for="cw_about_descripcion_principal" class="mb-1">Descripcion principal</label>
        <small class="text-muted cw-char-counter"><span id="cw_about_count_descripcion_principal"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($descripcionPrincipal, 320)); ?></span> restantes</small>
      </div>
      <textarea
        class="form-control"
        id="cw_about_descripcion_principal"
        name="descripcion_principal"
        rows="3"
        maxlength="320"
        data-cw-counter="cw_about_count_descripcion_principal"
        placeholder="Descripcion principal del bloque nosotros"
      ><?php echo cw_about_admin_h($descripcionPrincipal); ?></textarea>
    </div>

    <hr>

    <h5 class="mb-2">2. Tarjetas de Vision y Mision</h5>
    <p class="text-muted mb-3">Cada tarjeta permite icono, titulo y texto. Si quitas un icono personalizado se usa el icono por defecto.</p>

    <?php for ($i = 0; $i < 2; $i++): ?>
      <?php
        $num = $i + 1;
        $card = $cards[$i];
        $defaultCard = $defaults['tarjetas'][$i];
        $titleCounterId = 'cw_about_count_card_titulo_' . $num;
        $textCounterId = 'cw_about_count_card_texto_' . $num;
        $imgId = 'cw-about-preview-icon-' . $num;
        $fileId = 'cw_about_icono_archivo_' . $num;
        $removeId = 'cw_about_eliminar_icono_' . $num;
        $currentSrc = $i === 0 ? $icono1ActualUrl : $icono2ActualUrl;
        $defaultSrc = $i === 0 ? $icono1DefaultUrl : $icono2DefaultUrl;
      ?>
      <div class="card card-outline card-light cw-about-card mb-3">
        <div class="card-header py-2">
          <strong>Tarjeta <?php echo cw_about_admin_h((string)$num); ?> (<?php echo $num === 1 ? 'Vision' : 'Mision'; ?>)</strong>
        </div>
        <div class="card-body py-3">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="<?php echo cw_about_admin_h($fileId); ?>">Icono (imagen)</label>
              <input
                type="file"
                class="form-control-file"
                id="<?php echo cw_about_admin_h($fileId); ?>"
                name="<?php echo cw_about_admin_h('icono_archivo_' . $num); ?>"
                accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
              >
              <small class="form-text text-muted">Categoria: <strong>img_nosotros</strong>.</small>
              <div class="cw-about-image-preview p-2 border rounded bg-light mt-2">
                <img
                  id="<?php echo cw_about_admin_h($imgId); ?>"
                  src="<?php echo cw_about_admin_h($currentSrc); ?>"
                  data-current-src="<?php echo cw_about_admin_h($currentSrc); ?>"
                  data-default-src="<?php echo cw_about_admin_h($defaultSrc); ?>"
                  alt="Vista previa icono tarjeta <?php echo cw_about_admin_h((string)$num); ?>"
                  class="img-fluid"
                >
              </div>
              <div class="custom-control custom-checkbox mt-2">
                <input type="checkbox" class="custom-control-input" id="<?php echo cw_about_admin_h($removeId); ?>" name="<?php echo cw_about_admin_h('eliminar_icono_' . $num); ?>" value="1">
                <label class="custom-control-label" for="<?php echo cw_about_admin_h($removeId); ?>">Quitar icono personalizado</label>
              </div>
            </div>
            <div class="form-group col-md-4">
              <div class="d-flex justify-content-between">
                <label for="cw_about_card_titulo_<?php echo cw_about_admin_h((string)$num); ?>" class="mb-1">Titulo</label>
                <small class="text-muted cw-char-counter"><span id="<?php echo cw_about_admin_h($titleCounterId); ?>"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($card['titulo'], 70)); ?></span> restantes</small>
              </div>
              <input
                type="text"
                class="form-control"
                id="cw_about_card_titulo_<?php echo cw_about_admin_h((string)$num); ?>"
                name="card_titulo[]"
                maxlength="70"
                data-cw-counter="<?php echo cw_about_admin_h($titleCounterId); ?>"
                value="<?php echo cw_about_admin_h($card['titulo']); ?>"
                placeholder="<?php echo cw_about_admin_h($defaultCard['titulo']); ?>"
              >
            </div>
            <div class="form-group col-md-4">
              <div class="d-flex justify-content-between">
                <label for="cw_about_card_texto_<?php echo cw_about_admin_h((string)$num); ?>" class="mb-1">Texto</label>
                <small class="text-muted cw-char-counter"><span id="<?php echo cw_about_admin_h($textCounterId); ?>"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($card['texto'], 220)); ?></span> restantes</small>
              </div>
              <textarea
                class="form-control"
                id="cw_about_card_texto_<?php echo cw_about_admin_h((string)$num); ?>"
                name="card_texto[]"
                rows="3"
                maxlength="220"
                data-cw-counter="<?php echo cw_about_admin_h($textCounterId); ?>"
                placeholder="<?php echo cw_about_admin_h($defaultCard['texto']); ?>"
              ><?php echo cw_about_admin_h($card['texto']); ?></textarea>
            </div>
          </div>
        </div>
      </div>
    <?php endfor; ?>

    <hr>

    <h5 class="mb-2">3. Texto complementario y experiencia</h5>
    <p class="text-muted mb-3">Configura el texto intermedio y la caja de anos de experiencia.</p>

    <div class="form-group">
      <div class="d-flex justify-content-between">
        <label for="cw_about_descripcion_secundaria" class="mb-1">Descripcion complementaria</label>
        <small class="text-muted cw-char-counter"><span id="cw_about_count_descripcion_secundaria"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($descripcionSecundaria, 500)); ?></span> restantes</small>
      </div>
      <textarea
        class="form-control"
        id="cw_about_descripcion_secundaria"
        name="descripcion_secundaria"
        rows="4"
        maxlength="500"
        data-cw-counter="cw_about_count_descripcion_secundaria"
        placeholder="Texto complementario de la seccion"
      ><?php echo cw_about_admin_h($descripcionSecundaria); ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group col-md-4">
        <div class="d-flex justify-content-between">
          <label for="cw_about_experiencia_numero" class="mb-1">Numero de experiencia</label>
          <small class="text-muted cw-char-counter"><span id="cw_about_count_experiencia_numero"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($experienciaNumero, 10)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_about_experiencia_numero"
          name="experiencia_numero"
          maxlength="10"
          data-cw-counter="cw_about_count_experiencia_numero"
          value="<?php echo cw_about_admin_h($experienciaNumero); ?>"
          placeholder="17"
        >
      </div>
      <div class="form-group col-md-8">
        <div class="d-flex justify-content-between">
          <label for="cw_about_experiencia_texto" class="mb-1">Texto de experiencia</label>
          <small class="text-muted cw-char-counter"><span id="cw_about_count_experiencia_texto"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($experienciaTexto, 80)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_about_experiencia_texto"
          name="experiencia_texto"
          maxlength="80"
          data-cw-counter="cw_about_count_experiencia_texto"
          value="<?php echo cw_about_admin_h($experienciaTexto); ?>"
          placeholder="Years Of Experience"
        >
      </div>
    </div>

    <hr>

    <h5 class="mb-2">4. Checklist</h5>
    <p class="text-muted mb-3">Lista de 4 puntos que aparece junto al bloque de experiencia.</p>

    <div class="form-row">
      <?php for ($i = 0; $i < 4; $i++): ?>
        <?php
          $num = $i + 1;
          $counterId = 'cw_about_count_check_' . $num;
        ?>
        <div class="form-group col-md-6">
          <div class="d-flex justify-content-between">
            <label for="cw_about_check_<?php echo cw_about_admin_h((string)$num); ?>" class="mb-1">Item <?php echo cw_about_admin_h((string)$num); ?></label>
            <small class="text-muted cw-char-counter"><span id="<?php echo cw_about_admin_h($counterId); ?>"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($checklist[$i], 90)); ?></span> restantes</small>
          </div>
          <input
            type="text"
            class="form-control"
            id="cw_about_check_<?php echo cw_about_admin_h((string)$num); ?>"
            name="checklist_item[]"
            maxlength="90"
            data-cw-counter="<?php echo cw_about_admin_h($counterId); ?>"
            value="<?php echo cw_about_admin_h($checklist[$i]); ?>"
            placeholder="<?php echo cw_about_admin_h($defaults['checklist'][$i]); ?>"
          >
        </div>
      <?php endfor; ?>
    </div>

    <hr>

    <h5 class="mb-2">5. Boton y fundador</h5>
    <p class="text-muted mb-3">Configura CTA, datos del fundador e imagen de perfil.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_about_boton_texto" class="mb-1">Texto del boton</label>
          <small class="text-muted cw-char-counter"><span id="cw_about_count_boton_texto"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($botonTexto, 80)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_about_boton_texto"
          name="boton_texto"
          maxlength="80"
          data-cw-counter="cw_about_count_boton_texto"
          value="<?php echo cw_about_admin_h($botonTexto); ?>"
          placeholder="More About Us"
        >
      </div>
      <div class="form-group col-md-6">
        <label for="cw_about_boton_url">Enlace del boton</label>
        <input
          type="text"
          class="form-control"
          id="cw_about_boton_url"
          name="boton_url"
          maxlength="255"
          value="<?php echo cw_about_admin_h($botonUrl); ?>"
          placeholder="# o /ruta"
        >
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_about_fundador_nombre" class="mb-1">Nombre del fundador</label>
          <small class="text-muted cw-char-counter"><span id="cw_about_count_fundador_nombre"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($fundadorNombre, 80)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_about_fundador_nombre"
          name="fundador_nombre"
          maxlength="80"
          data-cw-counter="cw_about_count_fundador_nombre"
          value="<?php echo cw_about_admin_h($fundadorNombre); ?>"
          placeholder="William Burgess"
        >
      </div>
      <div class="form-group col-md-6">
        <div class="d-flex justify-content-between">
          <label for="cw_about_fundador_cargo" class="mb-1">Cargo del fundador</label>
          <small class="text-muted cw-char-counter"><span id="cw_about_count_fundador_cargo"><?php echo cw_about_admin_h((string)cw_about_admin_remaining($fundadorCargo, 80)); ?></span> restantes</small>
        </div>
        <input
          type="text"
          class="form-control"
          id="cw_about_fundador_cargo"
          name="fundador_cargo"
          maxlength="80"
          data-cw-counter="cw_about_count_fundador_cargo"
          value="<?php echo cw_about_admin_h($fundadorCargo); ?>"
          placeholder="Carveo Founder"
        >
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="cw_about_imagen_fundador_archivo">Imagen del fundador</label>
        <input
          type="file"
          class="form-control-file"
          id="cw_about_imagen_fundador_archivo"
          name="imagen_fundador_archivo"
          accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
        >
        <small class="form-text text-muted">Categoria: <strong>img_nosotros</strong>.</small>
      </div>
      <div class="form-group col-md-6">
        <label class="d-block">Vista previa</label>
        <div class="cw-about-image-preview p-2 border rounded bg-light">
          <img
            id="cw-about-preview-fundador"
            src="<?php echo cw_about_admin_h($imagenFundadorActualUrl); ?>"
            data-current-src="<?php echo cw_about_admin_h($imagenFundadorActualUrl); ?>"
            data-default-src="<?php echo cw_about_admin_h($imagenFundadorDefaultUrl); ?>"
            alt="Imagen del fundador"
            class="img-fluid"
          >
        </div>
      </div>
    </div>
    <div class="custom-control custom-checkbox mb-2">
      <input type="checkbox" class="custom-control-input" id="cw_about_eliminar_imagen_fundador" name="eliminar_imagen_fundador" value="1">
      <label class="custom-control-label" for="cw_about_eliminar_imagen_fundador">Quitar imagen personalizada del fundador</label>
    </div>

    <hr>

    <h5 class="mb-2">6. Imagenes de la columna derecha</h5>
    <p class="text-muted mb-3">Configura imagen principal e imagen secundaria del bloque visual.</p>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="cw_about_imagen_principal_archivo">Imagen principal</label>
        <input
          type="file"
          class="form-control-file"
          id="cw_about_imagen_principal_archivo"
          name="imagen_principal_archivo"
          accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
        >
        <small class="form-text text-muted">Categoria: <strong>img_nosotros</strong>.</small>
      </div>
      <div class="form-group col-md-6">
        <label class="d-block">Vista previa</label>
        <div class="cw-about-image-preview p-2 border rounded bg-light">
          <img
            id="cw-about-preview-principal"
            src="<?php echo cw_about_admin_h($imagenPrincipalActualUrl); ?>"
            data-current-src="<?php echo cw_about_admin_h($imagenPrincipalActualUrl); ?>"
            data-default-src="<?php echo cw_about_admin_h($imagenPrincipalDefaultUrl); ?>"
            alt="Imagen principal nosotros"
            class="img-fluid"
          >
        </div>
      </div>
    </div>
    <div class="custom-control custom-checkbox mb-2">
      <input type="checkbox" class="custom-control-input" id="cw_about_eliminar_imagen_principal" name="eliminar_imagen_principal" value="1">
      <label class="custom-control-label" for="cw_about_eliminar_imagen_principal">Quitar imagen principal personalizada</label>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="cw_about_imagen_secundaria_archivo">Imagen secundaria</label>
        <input
          type="file"
          class="form-control-file"
          id="cw_about_imagen_secundaria_archivo"
          name="imagen_secundaria_archivo"
          accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg"
        >
        <small class="form-text text-muted">Categoria: <strong>img_nosotros</strong>.</small>
      </div>
      <div class="form-group col-md-6">
        <label class="d-block">Vista previa</label>
        <div class="cw-about-image-preview p-2 border rounded bg-light">
          <img
            id="cw-about-preview-secundaria"
            src="<?php echo cw_about_admin_h($imagenSecundariaActualUrl); ?>"
            data-current-src="<?php echo cw_about_admin_h($imagenSecundariaActualUrl); ?>"
            data-default-src="<?php echo cw_about_admin_h($imagenSecundariaDefaultUrl); ?>"
            alt="Imagen secundaria nosotros"
            class="img-fluid"
          >
        </div>
      </div>
    </div>
    <div class="custom-control custom-checkbox mb-2">
      <input type="checkbox" class="custom-control-input" id="cw_about_eliminar_imagen_secundaria" name="eliminar_imagen_secundaria" value="1">
      <label class="custom-control-label" for="cw_about_eliminar_imagen_secundaria">Quitar imagen secundaria personalizada</label>
    </div>

    <div class="d-flex flex-wrap align-items-center mt-3">
      <button type="submit" class="btn btn-success mr-2 mb-2" id="cw-about-submit">Guardar nosotros</button>
      <small class="text-muted mb-2">Los cambios se veran en la seccion About/Nosotros de la pagina principal.</small>
    </div>
  </form>
</div>
