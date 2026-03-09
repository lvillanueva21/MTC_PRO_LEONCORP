<?php
// web/partials/carrusel_servicios.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/carrusel_servicios_model.php';

if (!function_exists('cw_cs_h')) {
    function cw_cs_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$data = [
    'config' => cw_cs_config_defaults(),
    'items' => cw_cs_normalize_items(cw_cs_defaults()['items'] ?? []),
];

if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $data = cw_cs_fetch($cn);
    }
}

$configDefaults = cw_cs_config_defaults();
$config = is_array($data['config'] ?? null) ? $data['config'] : $configDefaults;
$items = cw_cs_normalize_items($data['items'] ?? []);

$tituloBase = trim((string)($config['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($config['descripcion_general'] ?? ''));

if ($tituloBase === '') {
    $tituloBase = (string)($configDefaults['titulo_base'] ?? 'Vehicle');
}
if ($tituloResaltado === '') {
    $tituloResaltado = (string)($configDefaults['titulo_resaltado'] ?? 'Categories');
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = (string)($configDefaults['descripcion_general'] ?? '');
}
?>
<style>
.cw-cs-image-fit {
    height: 220px;
    object-fit: contain;
    object-position: center;
    background: #fff;
}
</style>
<div id="categorias" class="container-fluid categories pb-5">
    <div class="container pb-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 800px;">
            <h1 class="display-5 text-capitalize mb-3">
                <?php echo cw_cs_h($tituloBase); ?>
                <span class="text-primary"><?php echo cw_cs_h($tituloResaltado); ?></span>
            </h1>
            <p class="mb-0"><?php echo cw_cs_h($descripcionGeneral); ?></p>
        </div>
        <div class="categories-carousel owl-carousel wow fadeInUp" data-wow-delay="0.1s">
            <?php foreach ($items as $idx => $item): ?>
                <?php
                $itemTitle = trim((string)($item['titulo'] ?? 'Servicio'));
                $reviewText = trim((string)($item['review_text'] ?? '4.0 Review'));
                $rating = (int)($item['rating'] ?? 4);
                if ($rating < 1 || $rating > 5) {
                    $rating = 4;
                }
                $showStars = ((int)($item['mostrar_estrellas'] ?? 1) === 1);
                $badgeText = trim((string)($item['badge_text'] ?? 'Consulta precio'));
                $buttonText = trim((string)($item['boton_texto'] ?? 'Book Now'));
                $buttonUrl = trim((string)($item['boton_url'] ?? '#'));
                if (!cw_cs_link_valid($buttonUrl)) {
                    $buttonUrl = '#';
                }
                $imageUrl = cw_cs_resolve_item_image_url($item, $idx);

                $details = cw_cs_normalize_details($item['detalles'] ?? [], $idx);
                $visibleDetails = [];
                foreach ($details as $detail) {
                    if ((int)($detail['visible'] ?? 0) === 1) {
                        $visibleDetails[] = $detail;
                    }
                }
                ?>
                <div class="categories-item p-4">
                    <div class="categories-item-inner">
                        <div class="categories-img rounded-top">
                            <img src="<?php echo cw_cs_h($imageUrl); ?>" class="img-fluid w-100 rounded-top cw-cs-image-fit" alt="<?php echo cw_cs_h($itemTitle); ?>">
                        </div>
                        <div class="categories-content rounded-bottom p-4">
                            <h4><?php echo cw_cs_h($itemTitle); ?></h4>
                            <div class="categories-review mb-4 d-flex justify-content-between align-items-center flex-wrap">
                                <div class="me-3"><?php echo cw_cs_h($reviewText); ?></div>
                                <?php if ($showStars): ?>
                                    <div class="d-flex justify-content-center text-secondary">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i class="fas fa-star<?php echo $s > $rating ? ' text-body' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <h4 class="bg-white text-primary rounded-pill py-2 px-4 mb-0"><?php echo cw_cs_h($badgeText); ?></h4>
                            </div>
                            <?php if (!empty($visibleDetails)): ?>
                                <div class="row gy-2 gx-0 text-center mb-4">
                                    <?php foreach ($visibleDetails as $dIdx => $detail): ?>
                                        <?php $colClass = ($dIdx % 3 !== 2) ? 'col-4 border-end border-white' : 'col-4'; ?>
                                        <div class="<?php echo cw_cs_h($colClass); ?>">
                                            <i class="<?php echo cw_cs_h((string)($detail['icono'] ?? 'fa fa-circle')); ?> text-dark"></i>
                                            <span class="text-body ms-1"><?php echo cw_cs_h((string)($detail['texto'] ?? 'Detalle')); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <a href="<?php echo cw_cs_h($buttonUrl); ?>" class="btn btn-primary rounded-pill d-flex justify-content-center py-3"><?php echo cw_cs_h($buttonText); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
