<?php
// web/partials/topbar.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/topbar_model.php';

if (!function_exists('cw_topbar_h')) {
    function cw_topbar_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$topbarData = cw_topbar_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $topbarData = cw_topbar_fetch($cn);
    }
}

$phoneDigits = preg_replace('/\D+/', '', (string)$topbarData['telefono']);
$phoneHref = $phoneDigits;
if (strlen($phoneDigits) === 9 && strpos($phoneDigits, '9') === 0) {
    $phoneHref = '51' . $phoneDigits;
}

$socialItems = [
    ['key' => 'whatsapp_url', 'icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp'],
    ['key' => 'facebook_url', 'icon' => 'fab fa-facebook-f', 'label' => 'Facebook'],
    ['key' => 'instagram_url', 'icon' => 'fab fa-instagram', 'label' => 'Instagram'],
    ['key' => 'youtube_url', 'icon' => 'fab fa-youtube', 'label' => 'YouTube'],
];
?>
<div class="container-fluid topbar bg-secondary d-none d-xl-block w-100">
    <div class="container">
        <div class="row gx-0 align-items-center" style="height: 45px;">
            <div class="col-lg-6 text-center text-lg-start mb-lg-0">
                <div class="d-flex flex-wrap">
                    <span class="text-muted me-4">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i><?php echo cw_topbar_h($topbarData['direccion']); ?>
                    </span>
                    <a href="tel:+<?php echo cw_topbar_h($phoneHref); ?>" class="text-muted me-4">
                        <i class="fas fa-phone-alt text-primary me-2"></i><?php echo cw_topbar_h($topbarData['telefono']); ?>
                    </a>
                    <a href="mailto:<?php echo cw_topbar_h($topbarData['correo']); ?>" class="text-muted me-0">
                        <i class="fas fa-envelope text-primary me-2"></i><?php echo cw_topbar_h($topbarData['correo']); ?>
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center text-lg-end">
                <div class="d-flex align-items-center justify-content-end">
                    <?php foreach ($socialItems as $item): ?>
                        <?php $url = trim((string)$topbarData[$item['key']]); ?>
                        <?php if ($url === '') { continue; } ?>
                        <a href="<?php echo cw_topbar_h($url); ?>" class="btn btn-light btn-sm-square rounded-circle me-3" target="_blank" rel="noopener noreferrer" aria-label="<?php echo cw_topbar_h($item['label']); ?>">
                            <i class="<?php echo cw_topbar_h($item['icon']); ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
