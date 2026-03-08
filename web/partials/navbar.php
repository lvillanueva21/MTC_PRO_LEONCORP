<?php
// web/partials/navbar.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/menu_model.php';

if (!function_exists('cw_nav_h')) {
    function cw_nav_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$menuData = cw_menu_defaults();
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $menuData = cw_menu_fetch($cn);
    }
}

$items = cw_menu_normalize_items($menuData['menu_items'] ?? []);
if (empty($items)) {
    $items = cw_menu_defaults()['menu_items'];
}

// Mantener siempre visible la primera opcion para evitar un menu vacio.
if (!empty($items)) {
    $items[0]['visible'] = 1;
}

$logoUrl = cw_menu_logo_public_url((string)($menuData['logo_path'] ?? ''));
$tituloPagina = trim((string)($menuData['titulo_pagina'] ?? ''));
if ($tituloPagina === '') {
    $tituloPagina = 'Cental';
}

$botonTexto = trim((string)($menuData['boton_texto'] ?? ''));
if ($botonTexto === '') {
    $botonTexto = 'Get Started';
}
$botonUrl = trim((string)($menuData['boton_url'] ?? ''));
if ($botonUrl === '') {
    $botonUrl = '#';
}

$visibleItems = [];
foreach ($items as $item) {
    if (empty($item['visible'])) {
        continue;
    }
    $visibleItems[] = $item;
}
?>
<style>
.cw-navbar-brand {
    display: inline-flex;
    align-items: center;
    gap: .55rem;
    min-height: 44px;
}
.cw-navbar-brand-logo {
    max-height: 42px;
    max-width: 160px;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
}
.cw-navbar-brand-title {
    margin: 0;
    line-height: 1;
}
@media (max-width: 575.98px) {
    .cw-navbar-brand-logo {
        max-height: 34px;
        max-width: 120px;
    }
}
</style>

<div class="container-fluid nav-bar sticky-top px-0 px-lg-4 py-2 py-lg-0">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light">
            <a href="/" class="navbar-brand p-0 cw-navbar-brand">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?php echo cw_nav_h($logoUrl); ?>" alt="<?php echo cw_nav_h($tituloPagina); ?>" class="cw-navbar-brand-logo">
                <?php else: ?>
                    <i class="fas fa-car-alt text-primary"></i>
                <?php endif; ?>
                <h1 class="display-6 text-primary cw-navbar-brand-title"><?php echo cw_nav_h($tituloPagina); ?></h1>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav mx-auto py-0">
                    <?php foreach ($visibleItems as $idx => $item): ?>
                        <?php
                            $label = trim((string)($item['texto'] ?? ''));
                            $url = trim((string)($item['url'] ?? ''));
                            if ($label === '') {
                                continue;
                            }
                            if ($url === '') {
                                $url = '#';
                            }
                            $submenus = [];
                            if (!empty($item['submenus']) && is_array($item['submenus'])) {
                                foreach ($item['submenus'] as $sub) {
                                    if (empty($sub['visible'])) {
                                        continue;
                                    }
                                    $sLabel = trim((string)($sub['texto'] ?? ''));
                                    $sUrl = trim((string)($sub['url'] ?? ''));
                                    if ($sLabel === '') {
                                        continue;
                                    }
                                    if ($sUrl === '') {
                                        $sUrl = '#';
                                    }
                                    $submenus[] = ['texto' => $sLabel, 'url' => $sUrl];
                                }
                            }
                        ?>
                        <?php if (!empty($submenus)): ?>
                            <div class="nav-item dropdown">
                                <a href="<?php echo cw_nav_h($url); ?>" class="nav-link dropdown-toggle<?php echo $idx === 0 ? ' active' : ''; ?>" data-bs-toggle="dropdown">
                                    <?php echo cw_nav_h($label); ?>
                                </a>
                                <div class="dropdown-menu m-0">
                                    <?php foreach ($submenus as $sub): ?>
                                        <a href="<?php echo cw_nav_h($sub['url']); ?>" class="dropdown-item"><?php echo cw_nav_h($sub['texto']); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo cw_nav_h($url); ?>" class="nav-item nav-link<?php echo $idx === 0 ? ' active' : ''; ?>">
                                <?php echo cw_nav_h($label); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <a href="<?php echo cw_nav_h($botonUrl); ?>" class="btn btn-primary rounded-pill py-2 px-4">
                    <?php echo cw_nav_h($botonTexto); ?>
                </a>
            </div>
        </nav>
    </div>
</div>
