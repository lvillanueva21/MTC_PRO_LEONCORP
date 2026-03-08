<?php
// web/partials/menu_model.php

if (!function_exists('cw_menu_defaults')) {
    function cw_menu_defaults(): array
    {
        return [
            'titulo_pagina' => 'Cental',
            'logo_path' => '',
            'menu_items' => [
                ['texto' => 'Home', 'url' => '/', 'visible' => 1, 'submenus' => []],
                ['texto' => 'About', 'url' => '/web/about.html', 'visible' => 1, 'submenus' => []],
                ['texto' => 'Service', 'url' => '/web/service.html', 'visible' => 1, 'submenus' => []],
                ['texto' => 'Blog', 'url' => '/web/blog.html', 'visible' => 1, 'submenus' => []],
                [
                    'texto' => 'Pages',
                    'url' => '#',
                    'visible' => 1,
                    'submenus' => [
                        ['texto' => 'Our Feature', 'url' => '/web/feature.html', 'visible' => 1],
                        ['texto' => 'Our Cars', 'url' => '/web/cars.html', 'visible' => 1],
                        ['texto' => 'Our Team', 'url' => '/web/team.html', 'visible' => 1],
                        ['texto' => 'Testimonial', 'url' => '/web/testimonial.html', 'visible' => 1],
                        ['texto' => '404 Page', 'url' => '/web/404.html', 'visible' => 1],
                    ],
                ],
                ['texto' => 'Contact', 'url' => '/web/contact.html', 'visible' => 1, 'submenus' => []],
            ],
            'boton_texto' => 'Get Started',
            'boton_url' => '#',
        ];
    }
}

if (!function_exists('cw_menu_normalize_items')) {
    function cw_menu_normalize_items($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $texto = trim((string)($item['texto'] ?? ''));
            $url = trim((string)($item['url'] ?? ''));
            $visible = !empty($item['visible']) ? 1 : 0;

            $submenus = [];
            if (!empty($item['submenus']) && is_array($item['submenus'])) {
                foreach ($item['submenus'] as $sub) {
                    if (!is_array($sub)) {
                        continue;
                    }
                    $st = trim((string)($sub['texto'] ?? ''));
                    $su = trim((string)($sub['url'] ?? ''));
                    if ($st === '' && $su === '') {
                        continue;
                    }
                    $submenus[] = [
                        'texto' => $st,
                        'url' => $su,
                        'visible' => !empty($sub['visible']) ? 1 : 0,
                    ];
                }
            }

            if ($texto === '' && $url === '' && empty($submenus)) {
                continue;
            }

            $out[] = [
                'texto' => $texto,
                'url' => $url,
                'visible' => $visible,
                'submenus' => $submenus,
            ];
        }

        return $out;
    }
}

if (!function_exists('cw_menu_fetch')) {
    function cw_menu_fetch(mysqli $cn): array
    {
        $defaults = cw_menu_defaults();

        try {
            $sql = "SELECT titulo_pagina, logo_path, menu_items_json, boton_texto, boton_url
                    FROM web_menu
                    WHERE id = 1
                    LIMIT 1";
            $rs = mysqli_query($cn, $sql);
            if (!$rs) {
                return $defaults;
            }
            $row = mysqli_fetch_assoc($rs);
            mysqli_free_result($rs);
        } catch (Throwable $e) {
            return $defaults;
        }

        if (!$row) {
            return $defaults;
        }

        $itemsRaw = json_decode((string)($row['menu_items_json'] ?? ''), true);
        $items = cw_menu_normalize_items($itemsRaw);
        if (empty($items)) {
            $items = $defaults['menu_items'];
        }
        if (count($items) > 6) {
            $items = array_slice($items, 0, 6);
        }

        $data = [
            'titulo_pagina' => trim((string)($row['titulo_pagina'] ?? '')),
            'logo_path' => trim((string)($row['logo_path'] ?? '')),
            'menu_items' => $items,
            'boton_texto' => trim((string)($row['boton_texto'] ?? '')),
            'boton_url' => trim((string)($row['boton_url'] ?? '')),
        ];

        if ($data['titulo_pagina'] === '') {
            $data['titulo_pagina'] = $defaults['titulo_pagina'];
        }
        if ($data['boton_texto'] === '') {
            $data['boton_texto'] = $defaults['boton_texto'];
        }
        if ($data['boton_url'] === '') {
            $data['boton_url'] = $defaults['boton_url'];
        }

        return $data;
    }
}

if (!function_exists('cw_menu_upsert')) {
    function cw_menu_upsert(mysqli $cn, array $data): bool
    {
        $itemsJson = json_encode($data['menu_items'], JSON_UNESCAPED_UNICODE);
        if ($itemsJson === false) {
            return false;
        }

        try {
            $sql = "INSERT INTO web_menu
                    (id, titulo_pagina, logo_path, menu_items_json, boton_texto, boton_url, actualizacion)
                    VALUES
                    (1, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                      titulo_pagina = VALUES(titulo_pagina),
                      logo_path = VALUES(logo_path),
                      menu_items_json = VALUES(menu_items_json),
                      boton_texto = VALUES(boton_texto),
                      boton_url = VALUES(boton_url),
                      actualizacion = NOW()";

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param(
                $st,
                'sssss',
                $data['titulo_pagina'],
                $data['logo_path'],
                $itemsJson,
                $data['boton_texto'],
                $data['boton_url']
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_menu_logo_public_url')) {
    function cw_menu_logo_public_url(string $logoPath): string
    {
        $logoPath = ltrim(trim($logoPath), '/\\');
        if ($logoPath === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $logoPath)) {
            return $logoPath;
        }

        if (!defined('BASE_URL')) {
            return '/sistema/' . $logoPath;
        }

        return rtrim(BASE_URL, '/') . '/' . $logoPath;
    }
}
