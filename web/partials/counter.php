<?php
// web/partials/counter.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/counter_model.php';

if (!function_exists('cw_counter_h')) {
    function cw_counter_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$counterData = cw_counter_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $counterData = cw_counter_fetch($cn);
    }
}

$items = cw_counter_normalize_items($counterData['items'] ?? []);
$delays = ['0.1s', '0.3s', '0.5s', '0.7s'];
?>
<div class="container-fluid counter bg-secondary py-5">
    <div class="container py-5">
        <div class="row g-5">
            <?php foreach ($items as $idx => $item): ?>
                <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="<?php echo cw_counter_h($delays[$idx] ?? '0.1s'); ?>">
                    <div class="counter-item text-center">
                        <div class="counter-item-icon mx-auto">
                            <i class="<?php echo cw_counter_h($item['icono']); ?>"></i>
                        </div>
                        <div class="counter-counting my-3">
                            <span class="text-white fs-2 fw-bold" data-toggle="counter-up"><?php echo cw_counter_h($item['numero']); ?></span>
                            <span class="h1 fw-bold text-white">+</span>
                        </div>
                        <h4 class="text-white mb-0"><?php echo cw_counter_h($item['titulo']); ?></h4>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
