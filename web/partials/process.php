<?php
// web/partials/process.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/process_model.php';

if (!function_exists('cw_process_h')) {
    function cw_process_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$processData = cw_process_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $processData = cw_process_fetch($cn);
    }
}

$defaults = cw_process_defaults();
$tituloBase = trim((string)($processData['titulo_base'] ?? ''));
$tituloResaltado = trim((string)($processData['titulo_resaltado'] ?? ''));
$descripcionGeneral = trim((string)($processData['descripcion_general'] ?? ''));
$items = cw_process_normalize_items($processData['items'] ?? []);
$delays = ['0.1s', '0.3s', '0.5s'];

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
<div id="pasos" class="container-fluid steps py-5">
    <div class="container py-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 800px;">
            <h1 class="display-5 text-capitalize text-white mb-3">
                <?php echo cw_process_h($tituloBase); ?><span class="text-primary"> <?php echo cw_process_h($tituloResaltado); ?></span>
            </h1>
            <p class="mb-0 text-white"><?php echo cw_process_h($descripcionGeneral); ?></p>
        </div>
        <div class="row g-4">
            <?php foreach ($items as $idx => $item): ?>
                <?php
                $num = $idx + 1;
                $numberText = str_pad((string)$num, 2, '0', STR_PAD_LEFT) . '.';
                $delay = $delays[$idx % count($delays)];
                ?>
                <div class="col-lg-4 wow fadeInUp" data-wow-delay="<?php echo cw_process_h($delay); ?>">
                    <div class="steps-item p-4 mb-4">
                        <h4><?php echo cw_process_h($item['titulo']); ?></h4>
                        <p class="mb-0"><?php echo cw_process_h($item['texto']); ?></p>
                        <div class="setps-number"><?php echo cw_process_h($numberText); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
