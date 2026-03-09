<?php
// web/partials/services.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/services_model.php';

if (!function_exists('cw_services_h')) {
    function cw_services_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$servicesData = cw_services_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $servicesData = cw_services_fetch($cn);
    }
}

$defaults = cw_services_defaults();
$tituloBase = trim((string)($servicesData['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($servicesData['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($servicesData['descripcion_general'] ?? ''));
$items = cw_services_normalize_items($servicesData['items'] ?? []);
$delays = ['0.1s', '0.3s', '0.5s', '0.1s', '0.3s', '0.5s'];

if ($tituloBase === '') {
    $tituloBase = $defaults['titulo_base'];
}
if ($tituloResaltado === '') {
    $tituloResaltado = $defaults['titulo_resaltado'];
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = $defaults['descripcion_general'];
}
?>
<div id="servicios" class="container-fluid service py-5">
    <div class="container py-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 800px;">
            <h1 class="display-5 text-capitalize mb-3">
                <?php echo cw_services_h($tituloBase); ?>
                <span class="text-primary"><?php echo cw_services_h($tituloResaltado); ?></span>
            </h1>
            <p class="mb-0"><?php echo cw_services_h($descripcionGeneral); ?></p>
        </div>
        <div class="row g-4">
            <?php foreach ($items as $idx => $item): ?>
                <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="<?php echo cw_services_h($delays[$idx] ?? '0.1s'); ?>">
                    <div class="service-item p-4">
                        <div class="service-icon mb-4">
                            <i class="<?php echo cw_services_h($item['icono']); ?>"></i>
                        </div>
                        <h5 class="mb-3"><?php echo cw_services_h($item['titulo']); ?></h5>
                        <p class="mb-0"><?php echo cw_services_h($item['texto']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
