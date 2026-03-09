<?php
// web/partials/carrusel_empresas.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/carrusel_empresas_model.php';

if (!function_exists('cw_ce_h')) {
    function cw_ce_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$data = [
    'config' => cw_ce_config_defaults(),
    'items' => cw_ce_normalize_items(cw_ce_defaults()['items'] ?? []),
];
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $data = cw_ce_fetch($cn);
    }
}

$configDefaults = cw_ce_config_defaults();
$config = is_array($data['config'] ?? null) ? $data['config'] : $configDefaults;
$items = cw_ce_normalize_items($data['items'] ?? []);

$tituloBase = trim((string)($config['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
if ($tituloBase === '') {
    $tituloBase = (string)($configDefaults['titulo_base'] ?? 'Customer');
}
if ($tituloResaltado === '') {
    $tituloResaltado = (string)($configDefaults['titulo_resaltado'] ?? 'Suport Center');
}

$socialMeta = [
    'whatsapp' => ['icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp'],
    'facebook' => ['icon' => 'fab fa-facebook-f', 'label' => 'Facebook'],
    'instagram' => ['icon' => 'fab fa-instagram', 'label' => 'Instagram'],
    'youtube' => ['icon' => 'fab fa-youtube', 'label' => 'YouTube'],
];
?>
<style>
.cw-ce-image-fit {
    height: 260px;
    object-fit: contain;
    object-position: center;
    background: #fff;
}
</style>

<div id="equipo" class="container-fluid team pb-5">
    <div class="container pb-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 800px;">
            <h1 class="display-5 text-capitalize mb-3"><?php echo cw_ce_h($tituloBase); ?><span class="text-primary"> <?php echo cw_ce_h($tituloResaltado); ?></span></h1>
            <p class="mb-0">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,</p>
        </div>
        <div class="team-carousel owl-carousel wow fadeInUp" data-wow-delay="0.1s">
            <?php foreach ($items as $idx => $item): ?>
                <?php
                $base = cw_ce_item_default_for_position($idx);
                $titulo = trim((string)($item['titulo'] ?? ''));
                if ($titulo === '') {
                    $titulo = (string)($base['titulo'] ?? 'MARTIN DOE');
                }

                $profesion = trim((string)($item['profesion'] ?? ''));
                if ($profesion === '') {
                    $profesion = (string)($base['profesion'] ?? 'Profession');
                }

                $imageUrl = cw_ce_resolve_item_image_url($item, $idx);
                $redes = cw_ce_normalize_socials($item['redes'] ?? [], $idx);
                ?>
                <div class="team-item p-4 pt-0">
                    <div class="team-img">
                        <img src="<?php echo cw_ce_h($imageUrl); ?>" class="img-fluid rounded w-100 cw-ce-image-fit" alt="<?php echo cw_ce_h($titulo); ?>">
                    </div>
                    <div class="team-content pt-4">
                        <h4><?php echo cw_ce_h($titulo); ?></h4>
                        <p><?php echo cw_ce_h($profesion); ?></p>
                        <div class="team-icon d-flex justify-content-center flex-wrap">
                            <?php foreach (cw_ce_social_keys() as $socialKey): ?>
                                <?php
                                $meta = $socialMeta[$socialKey] ?? ['icon' => 'fas fa-link', 'label' => ucfirst($socialKey)];
                                $row = (isset($redes[$socialKey]) && is_array($redes[$socialKey])) ? $redes[$socialKey] : ['visible' => 0, 'link' => '#'];
                                $visible = ((int)($row['visible'] ?? 0) === 1);
                                if (!$visible) {
                                    continue;
                                }

                                $link = trim((string)($row['link'] ?? '#'));
                                if (!cw_ce_link_valid($link)) {
                                    $link = '#';
                                }
                                $isExternal = (bool)preg_match('#^https?://#i', $link);
                                ?>
                                <a
                                    class="btn btn-square btn-light rounded-circle mx-1"
                                    href="<?php echo cw_ce_h($link); ?>"
                                    <?php echo $isExternal ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                                    aria-label="<?php echo cw_ce_h((string)$meta['label']); ?>"
                                >
                                    <i class="<?php echo cw_ce_h((string)$meta['icon']); ?>"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
