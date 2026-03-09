<?php
// web/partials/banner.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/banner_model.php';

if (!function_exists('cw_banner_h')) {
    function cw_banner_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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
if ($boton1Url === '' || !cw_banner_link_valid($boton1Url)) {
    $boton1Url = $defaults['boton_1_url'];
}
if ($boton2Texto === '') {
    $boton2Texto = $defaults['boton_2_texto'];
}
if ($boton2Url === '' || !cw_banner_link_valid($boton2Url)) {
    $boton2Url = $defaults['boton_2_url'];
}

$imagenUrl = cw_banner_resolve_image_url($imagenPath);
?>
<div id="promocion" class="container-fluid banner pb-5 wow zoomInDown" data-wow-delay="0.1s">
    <div class="container pb-5">
        <div class="banner-item rounded">
            <img src="<?php echo cw_banner_h($imagenUrl); ?>" class="img-fluid rounded w-100" alt="Banner promocional">
            <div class="banner-content">
                <h2 class="text-primary"><?php echo cw_banner_h($tituloSuperior); ?></h2>
                <h1 class="text-white"><?php echo cw_banner_h($tituloPrincipal); ?></h1>
                <p class="text-white"><?php echo cw_banner_h($descripcion); ?></p>
                <div class="banner-btn">
                    <a href="<?php echo cw_banner_h($boton1Url); ?>" class="btn btn-secondary rounded-pill py-3 px-4 px-md-5 me-2"><?php echo cw_banner_h($boton1Texto); ?></a>
                    <a href="<?php echo cw_banner_h($boton2Url); ?>" class="btn btn-primary rounded-pill py-3 px-4 px-md-5 ms-2"><?php echo cw_banner_h($boton2Texto); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
