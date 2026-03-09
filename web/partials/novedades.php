<?php
// web/partials/novedades.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/novedades_model.php';

if (!function_exists('cw_novedades_h')) {
    function cw_novedades_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$data = [
    'config' => cw_novedades_config_defaults(),
    'items' => cw_novedades_normalize_items(cw_novedades_defaults()['items'] ?? []),
];
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $data = cw_novedades_fetch($cn);
    }
}

$configDefaults = cw_novedades_config_defaults();
$config = cw_novedades_normalize_config($data['config'] ?? []);
$items = cw_novedades_normalize_items($data['items'] ?? []);

$tituloBase = trim((string)($config['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($config['descripcion_general'] ?? ''));

if ($tituloBase === '') {
    $tituloBase = (string)($configDefaults['titulo_base'] ?? 'Cental');
}
if ($tituloResaltado === '') {
    $tituloResaltado = (string)($configDefaults['titulo_resaltado'] ?? 'Blog & News');
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = (string)($configDefaults['descripcion_general'] ?? '');
}

$visibleItems = [];
foreach ($items as $item) {
    if ((int)($item['visible'] ?? 1) === 1) {
        $visibleItems[] = $item;
    }
}
if (empty($visibleItems) && !empty($items)) {
    $visibleItems[] = $items[0];
}
?>
<style>
.novedades .cw-novedades-image-fit {
    width: 100%;
    height: 240px;
    object-fit: contain;
    object-position: center;
    background: #fff;
}

.novedades .blog-item:hover .blog-img img.cw-novedades-image-fit {
    transform: none;
}

.novedades-carousel .owl-stage-outer {
    margin: -15px;
    padding: 15px;
}

.novedades-carousel .owl-dots {
    display: flex;
    justify-content: center;
    margin-top: 18px;
}

.novedades-carousel .owl-dots .owl-dot {
    width: 12px;
    height: 12px;
    border-radius: 12px;
    margin: 0 5px;
    background: rgba(15, 23, 42, 0.2);
}

.novedades-carousel .owl-dots .owl-dot.active {
    background: var(--bs-primary);
}
</style>

<div id="blog" class="container-fluid blog novedades py-5">
    <div class="container py-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 800px;">
            <h1 class="display-5 text-capitalize mb-3"><?php echo cw_novedades_h($tituloBase); ?><span class="text-primary"> <?php echo cw_novedades_h($tituloResaltado); ?></span></h1>
            <p class="mb-0"><?php echo cw_novedades_h($descripcionGeneral); ?></p>
        </div>

        <div class="novedades-carousel owl-carousel wow fadeInUp" data-wow-delay="0.1s">
            <?php foreach ($visibleItems as $idx => $item): ?>
                <?php
                $orderIndex = (int)($item['orden'] ?? ($idx + 1));
                if ($orderIndex < 1) {
                    $orderIndex = $idx + 1;
                }
                $orderIndex -= 1;

                $base = cw_novedades_item_default_for_position($orderIndex);

                $titulo = trim((string)($item['titulo'] ?? ''));
                if ($titulo === '') {
                    $titulo = (string)($base['titulo'] ?? 'Novedad');
                }

                $meta1Icon = cw_novedades_sanitize_icon_class((string)($item['meta_1_icono'] ?? ''));
                if ($meta1Icon === '') {
                    $meta1Icon = cw_novedades_sanitize_icon_class((string)($base['meta_1_icono'] ?? 'fa fa-user text-primary'));
                }

                $meta1Text = trim((string)($item['meta_1_texto'] ?? ''));
                if ($meta1Text === '') {
                    $meta1Text = (string)($base['meta_1_texto'] ?? 'Autor');
                }

                $meta2Icon = cw_novedades_sanitize_icon_class((string)($item['meta_2_icono'] ?? ''));
                if ($meta2Icon === '') {
                    $meta2Icon = cw_novedades_sanitize_icon_class((string)($base['meta_2_icono'] ?? 'fa fa-comment-alt text-primary'));
                }

                $meta2Text = trim((string)($item['meta_2_texto'] ?? ''));
                if ($meta2Text === '') {
                    $meta2Text = (string)($base['meta_2_texto'] ?? 'Sin comentarios');
                }

                $badgeText = trim((string)($item['badge_texto'] ?? ''));
                if ($badgeText === '') {
                    $badgeText = (string)($base['badge_texto'] ?? 'Novedad');
                }

                $resumenText = trim((string)($item['resumen_texto'] ?? ''));
                if ($resumenText === '') {
                    $resumenText = (string)($base['resumen_texto'] ?? '');
                }

                $buttonText = trim((string)($item['boton_texto'] ?? ''));
                if ($buttonText === '') {
                    $buttonText = (string)($base['boton_texto'] ?? 'Read More');
                }

                $buttonUrl = trim((string)($item['boton_url'] ?? '#'));
                if (!cw_novedades_link_valid($buttonUrl)) {
                    $buttonUrl = '#';
                }
                $isExternal = (bool)preg_match('#^https?://#i', $buttonUrl);

                $imageUrl = cw_novedades_resolve_item_image_url($item, $orderIndex);
                ?>
                <div class="blog-item">
                    <div class="blog-img">
                        <img src="<?php echo cw_novedades_h($imageUrl); ?>" class="img-fluid rounded-top w-100 cw-novedades-image-fit" alt="<?php echo cw_novedades_h($titulo); ?>">
                    </div>
                    <div class="blog-content rounded-bottom p-4">
                        <div class="blog-date"><?php echo cw_novedades_h($badgeText); ?></div>
                        <div class="blog-comment my-3">
                            <div class="small">
                                <span class="<?php echo cw_novedades_h($meta1Icon); ?>"></span>
                                <span class="ms-2"><?php echo cw_novedades_h($meta1Text); ?></span>
                            </div>
                            <div class="small">
                                <span class="<?php echo cw_novedades_h($meta2Icon); ?>"></span>
                                <span class="ms-2"><?php echo cw_novedades_h($meta2Text); ?></span>
                            </div>
                        </div>
                        <a href="<?php echo cw_novedades_h($buttonUrl); ?>" class="h4 d-block mb-3" <?php echo $isExternal ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                            <?php echo cw_novedades_h($titulo); ?>
                        </a>
                        <p class="mb-3"><?php echo cw_novedades_h($resumenText); ?></p>
                        <a href="<?php echo cw_novedades_h($buttonUrl); ?>" <?php echo $isExternal ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                            <?php echo cw_novedades_h($buttonText); ?> <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
