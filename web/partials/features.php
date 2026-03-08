<?php
// web/partials/features.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/features_model.php';

if (!function_exists('cw_features_h')) {
    function cw_features_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$featuresData = cw_features_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $featuresData = cw_features_fetch($cn);
    }
}

$tituloRojo = trim((string)($featuresData['titulo_rojo'] ?? ''));
$tituloAzul = trim((string)($featuresData['titulo_azul'] ?? ''));
$descripcionGeneral = trim((string)($featuresData['descripcion_general'] ?? ''));

if ($tituloRojo === '') {
    $tituloRojo = cw_features_defaults()['titulo_rojo'];
}
if ($tituloAzul === '') {
    $tituloAzul = cw_features_defaults()['titulo_azul'];
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = cw_features_defaults()['descripcion_general'];
}

$imageUrl = cw_features_resolve_image_url((string)($featuresData['imagen_path'] ?? ''));
$items = cw_features_normalize_items($featuresData['items'] ?? []);
$leftItems = array_slice($items, 0, 2);
$rightItems = array_slice($items, 2, 2);
?>
<div id="caracteristicas" class="container-fluid feature py-5">
    <div class="container py-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 800px;">
            <h1 class="display-5 text-capitalize mb-3">
                <?php echo cw_features_h($tituloRojo); ?>
                <span class="text-primary"><?php echo cw_features_h($tituloAzul); ?></span>
            </h1>
            <p class="mb-0"><?php echo cw_features_h($descripcionGeneral); ?></p>
        </div>
        <div class="row g-4 align-items-center">
            <div class="col-xl-4">
                <div class="row gy-4 gx-0">
                    <?php foreach ($leftItems as $idx => $item): ?>
                        <div class="col-12 wow fadeInUp" data-wow-delay="<?php echo $idx === 0 ? '0.1s' : '0.3s'; ?>">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <span class="<?php echo cw_features_h($item['icono']); ?>"></span>
                                </div>
                                <div class="ms-4">
                                    <h5 class="mb-3"><?php echo cw_features_h($item['titulo']); ?></h5>
                                    <p class="mb-0"><?php echo cw_features_h($item['texto']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-12 col-xl-4 wow fadeInUp" data-wow-delay="0.2s">
                <img src="<?php echo cw_features_h($imageUrl); ?>" class="img-fluid w-100" style="object-fit: cover;" alt="Imagen de caracteristicas">
            </div>
            <div class="col-xl-4">
                <div class="row gy-4 gx-0">
                    <?php foreach ($rightItems as $idx => $item): ?>
                        <div class="col-12 wow fadeInUp" data-wow-delay="<?php echo $idx === 0 ? '0.1s' : '0.3s'; ?>">
                            <div class="feature-item justify-content-end">
                                <div class="text-end me-4">
                                    <h5 class="mb-3"><?php echo cw_features_h($item['titulo']); ?></h5>
                                    <p class="mb-0"><?php echo cw_features_h($item['texto']); ?></p>
                                </div>
                                <div class="feature-icon">
                                    <span class="<?php echo cw_features_h($item['icono']); ?>"></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
