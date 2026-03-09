<?php
// web/partials/testimonios.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/testimonios_model.php';

if (!function_exists('cw_testimonios_h')) {
    function cw_testimonios_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$data = [
    'config' => cw_testimonios_config_defaults(),
    'items' => cw_testimonios_normalize_items(cw_testimonios_defaults()['items'] ?? []),
];
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $data = cw_testimonios_fetch($cn);
    }
}

$configDefaults = cw_testimonios_config_defaults();
$config = cw_testimonios_normalize_config($data['config'] ?? []);
$items = cw_testimonios_normalize_items($data['items'] ?? []);

$tituloBase = trim((string)($config['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($config['descripcion_general'] ?? ''));

if ($tituloBase === '') {
    $tituloBase = (string)($configDefaults['titulo_base'] ?? 'Our Clients');
}
if ($tituloResaltado === '') {
    $tituloResaltado = (string)($configDefaults['titulo_resaltado'] ?? 'Riviews');
}
if ($descripcionGeneral === '') {
    $descripcionGeneral = (string)($configDefaults['descripcion_general'] ?? '');
}
?>
<style>
.cw-testimonios-avatar-fit {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    background: #fff;
}
</style>

<div id="testimonios" class="container-fluid testimonial pb-5">
    <div class="container pb-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 800px;">
            <h1 class="display-5 text-capitalize mb-3"><?php echo cw_testimonios_h($tituloBase); ?><span class="text-primary"> <?php echo cw_testimonios_h($tituloResaltado); ?></span></h1>
            <p class="mb-0"><?php echo cw_testimonios_h($descripcionGeneral); ?></p>
        </div>
        <div class="owl-carousel testimonial-carousel wow fadeInUp" data-wow-delay="0.1s">
            <?php foreach ($items as $idx => $item): ?>
                <?php
                $base = cw_testimonios_item_default_for_position($idx);

                $nombre = trim((string)($item['nombre_cliente'] ?? ''));
                if ($nombre === '') {
                    $nombre = (string)($base['nombre_cliente'] ?? 'Person Name');
                }

                $profesion = trim((string)($item['profesion'] ?? ''));
                if ($profesion === '') {
                    $profesion = (string)($base['profesion'] ?? 'Profession');
                }

                $testimonio = trim((string)($item['testimonio'] ?? ''));
                if ($testimonio === '') {
                    $testimonio = (string)($base['testimonio'] ?? '');
                }

                $imageUrl = cw_testimonios_resolve_item_image_url($item, $idx);
                ?>
                <div class="testimonial-item">
                    <div class="testimonial-quote"><i class="fa fa-quote-right fa-2x"></i></div>
                    <div class="testimonial-inner p-4">
                        <img src="<?php echo cw_testimonios_h($imageUrl); ?>" class="img-fluid cw-testimonios-avatar-fit" alt="<?php echo cw_testimonios_h($nombre); ?>">
                        <div class="ms-4">
                            <h4><?php echo cw_testimonios_h($nombre); ?></h4>
                            <p><?php echo cw_testimonios_h($profesion); ?></p>
                            <div class="d-flex text-primary">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <div class="border-top rounded-bottom p-4">
                        <p class="mb-0"><?php echo cw_testimonios_h($testimonio); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
