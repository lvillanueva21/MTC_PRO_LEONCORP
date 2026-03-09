<?php
// web/partials/about.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/about_model.php';

if (!function_exists('cw_about_h')) {
    function cw_about_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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

$icono1Url = cw_about_resolve_image_url((string)($cards[0]['icono_path'] ?? ''), '/web/img/about-icon-1.png');
$icono2Url = cw_about_resolve_image_url((string)($cards[1]['icono_path'] ?? ''), '/web/img/about-icon-2.png');
$imagenFundadorUrl = cw_about_resolve_image_url((string)($aboutData['imagen_fundador_path'] ?? ''), '/web/img/attachment-img.jpg');
$imagenPrincipalUrl = cw_about_resolve_image_url((string)($aboutData['imagen_principal_path'] ?? ''), '/web/img/about-img.jpg');
$imagenSecundariaUrl = cw_about_resolve_image_url((string)($aboutData['imagen_secundaria_path'] ?? ''), '/web/img/about-img-1.jpg');
?>
<div id="nosotros" class="container-fluid overflow-hidden about py-5">
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-xl-6 wow fadeInLeft" data-wow-delay="0.2s">
                <div class="about-item">
                    <div class="pb-5">
                        <h1 class="display-5 text-capitalize"><?php echo cw_about_h($tituloBase); ?> <span class="text-primary"><?php echo cw_about_h($tituloResaltado); ?></span></h1>
                        <p class="mb-0"><?php echo cw_about_h($descripcionPrincipal); ?>
                        </p>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="about-item-inner border p-4">
                                <div class="about-icon mb-4">
                                    <img src="<?php echo cw_about_h($icono1Url); ?>" class="img-fluid w-50 h-50" alt="Icon">
                                </div>
                                <h5 class="mb-3"><?php echo cw_about_h($cards[0]['titulo']); ?></h5>
                                <p class="mb-0"><?php echo cw_about_h($cards[0]['texto']); ?></p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="about-item-inner border p-4">
                                <div class="about-icon mb-4">
                                    <img src="<?php echo cw_about_h($icono2Url); ?>" class="img-fluid h-50 w-50" alt="Icon">
                                </div>
                                <h5 class="mb-3"><?php echo cw_about_h($cards[1]['titulo']); ?></h5>
                                <p class="mb-0"><?php echo cw_about_h($cards[1]['texto']); ?></p>
                            </div>
                        </div>
                    </div>
                    <p class="text-item my-4"><?php echo cw_about_h($descripcionSecundaria); ?>
                    </p>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="text-center rounded bg-secondary p-4">
                                <h1 class="display-6 text-white"><?php echo cw_about_h($experienciaNumero); ?></h1>
                                <h5 class="text-light mb-0"><?php echo cw_about_h($experienciaTexto); ?></h5>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="rounded">
                                <?php foreach ($checklist as $idx => $item): ?>
                                    <p class="<?php echo $idx === 3 ? 'mb-0' : 'mb-2'; ?>"><i class="fa fa-check-circle text-primary me-1"></i> <?php echo cw_about_h($item); ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-lg-5 d-flex align-items-center">
                            <a href="<?php echo cw_about_h($botonUrl); ?>" class="btn btn-primary rounded py-3 px-5"><?php echo cw_about_h($botonTexto); ?></a>
                        </div>
                        <div class="col-lg-7">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo cw_about_h($imagenFundadorUrl); ?>" class="img-fluid rounded-circle border border-4 border-secondary" style="width: 100px; height: 100px;" alt="Image">
                                <div class="ms-4">
                                    <h4><?php echo cw_about_h($fundadorNombre); ?></h4>
                                    <p class="mb-0"><?php echo cw_about_h($fundadorCargo); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 wow fadeInRight" data-wow-delay="0.2s">
                <div class="about-img">
                    <div class="img-1">
                        <img src="<?php echo cw_about_h($imagenPrincipalUrl); ?>" class="img-fluid rounded h-100 w-100" alt="">
                    </div>
                    <div class="img-2">
                        <img src="<?php echo cw_about_h($imagenSecundariaUrl); ?>" class="img-fluid rounded w-100" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
