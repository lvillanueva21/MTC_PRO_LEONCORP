<?php
// web/partials/novedades_model.php

if (!function_exists('cw_novedades_defaults')) {
    function cw_novedades_defaults(): array
    {
        return [
            'config' => [
                'titulo_base' => 'Cental',
                'titulo_resaltado' => 'Blog & News',
                'descripcion_general' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
            ],
            'items' => [
                [
                    'id' => 0,
                    'orden' => 1,
                    'visible' => 1,
                    'titulo' => 'Rental Cars how to check driving fines?',
                    'meta_1_icono' => 'fa fa-user text-primary',
                    'meta_1_texto' => 'Martin.C',
                    'meta_2_icono' => 'fa fa-comment-alt text-primary',
                    'meta_2_texto' => '6 Comments',
                    'badge_texto' => '30 Dec 2025',
                    'resumen_texto' => 'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eius libero soluta impedit eligendi? Quibusdam, laudantium.',
                    'boton_texto' => 'Read More',
                    'boton_url' => '#',
                    'imagen_path' => '',
                    'default_image' => 'web/img/blog-1.jpg',
                ],
                [
                    'id' => 0,
                    'orden' => 2,
                    'visible' => 1,
                    'titulo' => 'Rental cost of sport and other cars',
                    'meta_1_icono' => 'fa fa-user text-primary',
                    'meta_1_texto' => 'Martin.C',
                    'meta_2_icono' => 'fa fa-comment-alt text-primary',
                    'meta_2_texto' => '6 Comments',
                    'badge_texto' => '25 Dec 2025',
                    'resumen_texto' => 'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eius libero soluta impedit eligendi? Quibusdam, laudantium.',
                    'boton_texto' => 'Read More',
                    'boton_url' => '#',
                    'imagen_path' => '',
                    'default_image' => 'web/img/blog-2.jpg',
                ],
                [
                    'id' => 0,
                    'orden' => 3,
                    'visible' => 1,
                    'titulo' => 'Document required for car rental',
                    'meta_1_icono' => 'fa fa-user text-primary',
                    'meta_1_texto' => 'Martin.C',
                    'meta_2_icono' => 'fa fa-comment-alt text-primary',
                    'meta_2_texto' => '6 Comments',
                    'badge_texto' => '27 Dec 2025',
                    'resumen_texto' => 'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eius libero soluta impedit eligendi? Quibusdam, laudantium.',
                    'boton_texto' => 'Read More',
                    'boton_url' => '#',
                    'imagen_path' => '',
                    'default_image' => 'web/img/blog-3.jpg',
                ],
            ],
        ];
    }
}

if (!function_exists('cw_novedades_config_defaults')) {
    function cw_novedades_config_defaults(): array
    {
        return cw_novedades_defaults()['config'];
    }
}

if (!function_exists('cw_novedades_limit_text')) {
    function cw_novedades_limit_text(string $value, int $max): string
    {
        if ($max < 1) {
            return '';
        }

        if (function_exists('mb_substr')) {
            return (string)mb_substr($value, 0, $max, 'UTF-8');
        }

        return substr($value, 0, $max);
    }
}

if (!function_exists('cw_novedades_to_flag')) {
    function cw_novedades_to_flag($value, int $default = 1): int
    {
        if ($value === null || $value === '') {
            return $default > 0 ? 1 : 0;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $str = strtolower(trim((string)$value));
        if ($str === '0' || $str === 'false' || $str === 'off' || $str === 'no') {
            return 0;
        }
        if ($str === '1' || $str === 'true' || $str === 'on' || $str === 'yes') {
            return 1;
        }

        return ((int)$value > 0) ? 1 : 0;
    }
}

if (!function_exists('cw_novedades_link_valid')) {
    function cw_novedades_link_valid(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if ($url === '#') {
            return true;
        }
        if (preg_match('#^\s*(javascript:|data:)#i', $url)) {
            return false;
        }
        if (preg_match('/^#[a-zA-Z][a-zA-Z0-9\-_:.]*$/', $url)) {
            return true;
        }
        if (preg_match('#^https?://#i', $url)) {
            return (bool)filter_var($url, FILTER_VALIDATE_URL);
        }
        if ($url[0] === '/' || $url[0] === '.') {
            return true;
        }

        return (bool)preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-._\/#?=&%]*$/', $url);
    }
}

if (!function_exists('cw_novedades_sanitize_icon_class')) {
    function cw_novedades_sanitize_icon_class(string $icon): string
    {
        $icon = trim($icon);
        if ($icon === '') {
            return '';
        }

        if (strpos($icon, '<') !== false && preg_match('/class\s*=\s*["\']([^"\']+)["\']/i', $icon, $m)) {
            $icon = (string)$m[1];
        }

        $icon = trim((string)preg_replace('/\s+/', ' ', $icon));
        if ($icon === '') {
            return '';
        }

        if (!preg_match('/^[a-zA-Z0-9 _:\-]+$/', $icon)) {
            return '';
        }

        return cw_novedades_limit_text($icon, 120);
    }
}

if (!function_exists('cw_novedades_default_image_for_position')) {
    function cw_novedades_default_image_for_position(int $index): string
    {
        $fallbacks = [
            'web/img/blog-1.jpg',
            'web/img/blog-2.jpg',
            'web/img/blog-3.jpg',
        ];

        $idx = $index % count($fallbacks);
        if ($idx < 0) {
            $idx = 0;
        }

        return $fallbacks[$idx];
    }
}

if (!function_exists('cw_novedades_item_default_for_position')) {
    function cw_novedades_item_default_for_position(int $index): array
    {
        $defaults = cw_novedades_defaults()['items'];
        if (isset($defaults[$index]) && is_array($defaults[$index])) {
            return $defaults[$index];
        }

        $num = $index + 1;
        return [
            'id' => 0,
            'orden' => $num,
            'visible' => 1,
            'titulo' => 'Novedad destacada ' . $num,
            'meta_1_icono' => 'fa fa-user text-primary',
            'meta_1_texto' => 'Autor',
            'meta_2_icono' => 'fa fa-comment-alt text-primary',
            'meta_2_texto' => 'Sin comentarios',
            'badge_texto' => 'Novedad',
            'resumen_texto' => 'Resumen corto de la novedad.',
            'boton_texto' => 'Read More',
            'boton_url' => '#',
            'imagen_path' => '',
            'default_image' => cw_novedades_default_image_for_position($index),
        ];
    }
}

if (!function_exists('cw_novedades_site_base_url')) {
    function cw_novedades_site_base_url(): string
    {
        if (!defined('BASE_URL')) {
            return '';
        }

        $base = rtrim((string)BASE_URL, '/');
        if (preg_match('#/sistema$#i', $base)) {
            return (string)substr($base, 0, -8);
        }

        return $base;
    }
}

if (!function_exists('cw_novedades_default_asset_url')) {
    function cw_novedades_default_asset_url(string $relativePath): string
    {
        $relativePath = '/' . ltrim(trim($relativePath), '/');
        $siteBase = cw_novedades_site_base_url();
        if ($siteBase === '') {
            return $relativePath;
        }

        return $siteBase . $relativePath;
    }
}

if (!function_exists('cw_novedades_image_public_url')) {
    function cw_novedades_image_public_url(string $imagePath): string
    {
        $imagePath = ltrim(trim($imagePath), '/\\');
        if ($imagePath === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        if (preg_match('#^web/#i', $imagePath)) {
            return cw_novedades_default_asset_url($imagePath);
        }

        if (!defined('BASE_URL')) {
            return '/sistema/' . $imagePath;
        }

        return rtrim((string)BASE_URL, '/') . '/' . $imagePath;
    }
}

if (!function_exists('cw_novedades_image_exists')) {
    function cw_novedades_image_exists(string $imagePath): bool
    {
        $imagePath = ltrim(trim($imagePath), '/\\');
        if ($imagePath === '') {
            return false;
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return true;
        }

        if (preg_match('#^web/#i', $imagePath)) {
            $absWeb = realpath(__DIR__ . '/../../' . $imagePath);
            return $absWeb !== false && is_file($absWeb);
        }

        $absStorage = realpath(__DIR__ . '/../../sistema/' . $imagePath);
        return $absStorage !== false && is_file($absStorage);
    }
}

if (!function_exists('cw_novedades_resolve_item_image_url')) {
    function cw_novedades_resolve_item_image_url(array $item, int $index): string
    {
        $imagePath = trim((string)($item['imagen_path'] ?? ''));
        if ($imagePath !== '' && cw_novedades_image_exists($imagePath)) {
            $customUrl = cw_novedades_image_public_url($imagePath);
            if ($customUrl !== '') {
                return $customUrl;
            }
        }

        $base = cw_novedades_item_default_for_position($index);
        $defaultImage = trim((string)($item['default_image'] ?? $base['default_image'] ?? ''));
        if ($defaultImage === '') {
            $defaultImage = cw_novedades_default_image_for_position($index);
        }

        return cw_novedades_default_asset_url($defaultImage);
    }
}

if (!function_exists('cw_novedades_normalize_config')) {
    function cw_novedades_normalize_config($config): array
    {
        $defaults = cw_novedades_config_defaults();
        $config = is_array($config) ? $config : [];

        $tituloBase = trim((string)($config['titulo_base'] ?? ''));
        $tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
        $descripcionGeneral = trim((string)($config['descripcion_general'] ?? ''));

        if ($tituloBase === '') {
            $tituloBase = (string)($defaults['titulo_base'] ?? 'Cental');
        }
        if ($tituloResaltado === '') {
            $tituloResaltado = (string)($defaults['titulo_resaltado'] ?? 'Blog & News');
        }
        if ($descripcionGeneral === '') {
            $descripcionGeneral = (string)($defaults['descripcion_general'] ?? '');
        }

        return [
            'titulo_base' => cw_novedades_limit_text($tituloBase, 40),
            'titulo_resaltado' => cw_novedades_limit_text($tituloResaltado, 40),
            'descripcion_general' => cw_novedades_limit_text($descripcionGeneral, 280),
        ];
    }
}

if (!function_exists('cw_novedades_normalize_items')) {
    function cw_novedades_normalize_items($items): array
    {
        $source = is_array($items) ? array_values($items) : [];
        if (count($source) < 1) {
            $source = cw_novedades_defaults()['items'];
        }

        if (count($source) > 9) {
            $source = array_slice($source, 0, 9);
        }

        $out = [];
        foreach ($source as $i => $raw) {
            $base = cw_novedades_item_default_for_position($i);
            $raw = is_array($raw) ? $raw : [];

            $id = (int)($raw['id'] ?? 0);
            $orden = (int)($raw['orden'] ?? ($i + 1));
            if ($orden < 1) {
                $orden = $i + 1;
            }

            $visible = cw_novedades_to_flag($raw['visible'] ?? null, (int)($base['visible'] ?? 1));

            $titulo = trim((string)($raw['titulo'] ?? ''));
            if ($titulo === '') {
                $titulo = (string)($base['titulo'] ?? 'Novedad');
            }

            $meta1Icon = cw_novedades_sanitize_icon_class((string)($raw['meta_1_icono'] ?? ''));
            if ($meta1Icon === '') {
                $meta1Icon = cw_novedades_sanitize_icon_class((string)($base['meta_1_icono'] ?? 'fa fa-user text-primary'));
            }

            $meta1Text = trim((string)($raw['meta_1_texto'] ?? ''));
            if ($meta1Text === '') {
                $meta1Text = (string)($base['meta_1_texto'] ?? 'Autor');
            }

            $meta2Icon = cw_novedades_sanitize_icon_class((string)($raw['meta_2_icono'] ?? ''));
            if ($meta2Icon === '') {
                $meta2Icon = cw_novedades_sanitize_icon_class((string)($base['meta_2_icono'] ?? 'fa fa-comment-alt text-primary'));
            }

            $meta2Text = trim((string)($raw['meta_2_texto'] ?? ''));
            if ($meta2Text === '') {
                $meta2Text = (string)($base['meta_2_texto'] ?? 'Sin comentarios');
            }

            $badgeText = trim((string)($raw['badge_texto'] ?? ''));
            if ($badgeText === '') {
                $badgeText = (string)($base['badge_texto'] ?? 'Novedad');
            }

            $resumenText = trim((string)($raw['resumen_texto'] ?? ''));
            if ($resumenText === '') {
                $resumenText = (string)($base['resumen_texto'] ?? '');
            }

            $botonTexto = trim((string)($raw['boton_texto'] ?? ''));
            if ($botonTexto === '') {
                $botonTexto = (string)($base['boton_texto'] ?? 'Read More');
            }

            $botonUrl = trim((string)($raw['boton_url'] ?? ''));
            if ($botonUrl === '' || !cw_novedades_link_valid($botonUrl)) {
                $botonUrl = (string)($base['boton_url'] ?? '#');
            }
            if ($botonUrl === '' || !cw_novedades_link_valid($botonUrl)) {
                $botonUrl = '#';
            }

            $imagenPath = cw_novedades_limit_text(trim((string)($raw['imagen_path'] ?? '')), 255);

            $out[] = [
                'id' => $id,
                'orden' => $orden,
                'visible' => $visible,
                'titulo' => cw_novedades_limit_text($titulo, 110),
                'meta_1_icono' => cw_novedades_limit_text($meta1Icon, 120),
                'meta_1_texto' => cw_novedades_limit_text($meta1Text, 80),
                'meta_2_icono' => cw_novedades_limit_text($meta2Icon, 120),
                'meta_2_texto' => cw_novedades_limit_text($meta2Text, 80),
                'badge_texto' => cw_novedades_limit_text($badgeText, 50),
                'resumen_texto' => cw_novedades_limit_text($resumenText, 220),
                'boton_texto' => cw_novedades_limit_text($botonTexto, 50),
                'boton_url' => cw_novedades_limit_text($botonUrl, 255),
                'imagen_path' => $imagenPath,
                'default_image' => (string)($base['default_image'] ?? cw_novedades_default_image_for_position($i)),
            ];
        }

        usort($out, static function (array $a, array $b): int {
            return ((int)$a['orden'] <=> (int)$b['orden']);
        });

        foreach ($out as $idx => &$row) {
            $base = cw_novedades_item_default_for_position($idx);
            $row['orden'] = $idx + 1;
            $row['default_image'] = (string)($base['default_image'] ?? cw_novedades_default_image_for_position($idx));
        }
        unset($row);

        $visibleCount = 0;
        foreach ($out as $row) {
            if ((int)($row['visible'] ?? 0) === 1) {
                $visibleCount++;
            }
        }
        if ($visibleCount < 1 && isset($out[0])) {
            $out[0]['visible'] = 1;
        }

        return $out;
    }
}

if (!function_exists('cw_novedades_fetch_config')) {
    function cw_novedades_fetch_config(mysqli $cn): array
    {
        $defaults = cw_novedades_config_defaults();

        try {
            $sql = 'SELECT titulo_base, titulo_resaltado, descripcion_general
                    FROM web_novedades_config
                    WHERE id = 1
                    LIMIT 1';

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

        return cw_novedades_normalize_config($row);
    }
}

if (!function_exists('cw_novedades_upsert_config')) {
    function cw_novedades_upsert_config(mysqli $cn, array $config): bool
    {
        $payload = cw_novedades_normalize_config($config);

        try {
            $sql = 'INSERT INTO web_novedades_config
                    (id, titulo_base, titulo_resaltado, descripcion_general, actualizacion)
                    VALUES
                    (1, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        titulo_base = VALUES(titulo_base),
                        titulo_resaltado = VALUES(titulo_resaltado),
                        descripcion_general = VALUES(descripcion_general),
                        actualizacion = NOW()';

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param(
                $st,
                'sss',
                $payload['titulo_base'],
                $payload['titulo_resaltado'],
                $payload['descripcion_general']
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_novedades_fetch_items')) {
    function cw_novedades_fetch_items(mysqli $cn): array
    {
        try {
            $sql = 'SELECT id, orden, visible, titulo, meta_1_icono, meta_1_texto,
                           meta_2_icono, meta_2_texto, badge_texto, resumen_texto,
                           boton_texto, boton_url, imagen_path
                    FROM web_novedades_items
                    ORDER BY orden ASC, id ASC
                    LIMIT 9';

            $rs = mysqli_query($cn, $sql);
            if (!$rs) {
                return cw_novedades_normalize_items([]);
            }

            $rows = [];
            while ($row = mysqli_fetch_assoc($rs)) {
                $rows[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'orden' => (int)($row['orden'] ?? 0),
                    'visible' => (int)($row['visible'] ?? 1),
                    'titulo' => trim((string)($row['titulo'] ?? '')),
                    'meta_1_icono' => trim((string)($row['meta_1_icono'] ?? '')),
                    'meta_1_texto' => trim((string)($row['meta_1_texto'] ?? '')),
                    'meta_2_icono' => trim((string)($row['meta_2_icono'] ?? '')),
                    'meta_2_texto' => trim((string)($row['meta_2_texto'] ?? '')),
                    'badge_texto' => trim((string)($row['badge_texto'] ?? '')),
                    'resumen_texto' => trim((string)($row['resumen_texto'] ?? '')),
                    'boton_texto' => trim((string)($row['boton_texto'] ?? '')),
                    'boton_url' => trim((string)($row['boton_url'] ?? '')),
                    'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                ];
            }
            mysqli_free_result($rs);

            return cw_novedades_normalize_items($rows);
        } catch (Throwable $e) {
            return cw_novedades_normalize_items([]);
        }
    }
}

if (!function_exists('cw_novedades_fetch_rows_by_id')) {
    function cw_novedades_fetch_rows_by_id(mysqli $cn): array
    {
        $out = [];

        try {
            $sql = 'SELECT id, orden, visible, titulo, meta_1_icono, meta_1_texto,
                           meta_2_icono, meta_2_texto, badge_texto, resumen_texto,
                           boton_texto, boton_url, imagen_path
                    FROM web_novedades_items
                    ORDER BY orden ASC, id ASC';

            $rs = mysqli_query($cn, $sql);
            if (!$rs) {
                return $out;
            }

            while ($row = mysqli_fetch_assoc($rs)) {
                $id = (int)($row['id'] ?? 0);
                if ($id < 1) {
                    continue;
                }

                $out[$id] = [
                    'id' => $id,
                    'orden' => (int)($row['orden'] ?? 0),
                    'visible' => (int)($row['visible'] ?? 1),
                    'titulo' => trim((string)($row['titulo'] ?? '')),
                    'meta_1_icono' => trim((string)($row['meta_1_icono'] ?? '')),
                    'meta_1_texto' => trim((string)($row['meta_1_texto'] ?? '')),
                    'meta_2_icono' => trim((string)($row['meta_2_icono'] ?? '')),
                    'meta_2_texto' => trim((string)($row['meta_2_texto'] ?? '')),
                    'badge_texto' => trim((string)($row['badge_texto'] ?? '')),
                    'resumen_texto' => trim((string)($row['resumen_texto'] ?? '')),
                    'boton_texto' => trim((string)($row['boton_texto'] ?? '')),
                    'boton_url' => trim((string)($row['boton_url'] ?? '')),
                    'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                ];
            }
            mysqli_free_result($rs);
        } catch (Throwable $e) {
            return [];
        }

        return $out;
    }
}

if (!function_exists('cw_novedades_upsert_item')) {
    function cw_novedades_upsert_item(mysqli $cn, array $item): int
    {
        $id = (int)($item['id'] ?? 0);
        $orden = (int)($item['orden'] ?? 1);
        if ($orden < 1) {
            $orden = 1;
        }

        $base = cw_novedades_item_default_for_position(max(0, $orden - 1));

        $visible = cw_novedades_to_flag($item['visible'] ?? 1, 1);
        $titulo = cw_novedades_limit_text(trim((string)($item['titulo'] ?? '')), 110);
        if ($titulo === '') {
            $titulo = (string)($base['titulo'] ?? 'Novedad');
        }

        $meta1Icon = cw_novedades_sanitize_icon_class((string)($item['meta_1_icono'] ?? ''));
        if ($meta1Icon === '') {
            $meta1Icon = cw_novedades_sanitize_icon_class((string)($base['meta_1_icono'] ?? 'fa fa-user text-primary'));
        }
        $meta1Icon = cw_novedades_limit_text($meta1Icon, 120);

        $meta1Text = cw_novedades_limit_text(trim((string)($item['meta_1_texto'] ?? '')), 80);
        if ($meta1Text === '') {
            $meta1Text = (string)($base['meta_1_texto'] ?? 'Autor');
        }

        $meta2Icon = cw_novedades_sanitize_icon_class((string)($item['meta_2_icono'] ?? ''));
        if ($meta2Icon === '') {
            $meta2Icon = cw_novedades_sanitize_icon_class((string)($base['meta_2_icono'] ?? 'fa fa-comment-alt text-primary'));
        }
        $meta2Icon = cw_novedades_limit_text($meta2Icon, 120);

        $meta2Text = cw_novedades_limit_text(trim((string)($item['meta_2_texto'] ?? '')), 80);
        if ($meta2Text === '') {
            $meta2Text = (string)($base['meta_2_texto'] ?? 'Sin comentarios');
        }

        $badgeText = cw_novedades_limit_text(trim((string)($item['badge_texto'] ?? '')), 50);
        if ($badgeText === '') {
            $badgeText = (string)($base['badge_texto'] ?? 'Novedad');
        }

        $resumenText = cw_novedades_limit_text(trim((string)($item['resumen_texto'] ?? '')), 220);
        if ($resumenText === '') {
            $resumenText = (string)($base['resumen_texto'] ?? '');
        }

        $botonTexto = cw_novedades_limit_text(trim((string)($item['boton_texto'] ?? '')), 50);
        if ($botonTexto === '') {
            $botonTexto = (string)($base['boton_texto'] ?? 'Read More');
        }

        $botonUrl = trim((string)($item['boton_url'] ?? '#'));
        if (!cw_novedades_link_valid($botonUrl)) {
            $botonUrl = (string)($base['boton_url'] ?? '#');
        }
        if (!cw_novedades_link_valid($botonUrl)) {
            $botonUrl = '#';
        }
        $botonUrl = cw_novedades_limit_text($botonUrl, 255);

        $imagenPath = cw_novedades_limit_text(trim((string)($item['imagen_path'] ?? '')), 255);

        if ($id > 0) {
            $sql = 'UPDATE web_novedades_items
                    SET orden = ?, visible = ?, titulo = ?, meta_1_icono = ?, meta_1_texto = ?,
                        meta_2_icono = ?, meta_2_texto = ?, badge_texto = ?, resumen_texto = ?,
                        boton_texto = ?, boton_url = ?, imagen_path = ?, actualizacion = NOW()
                    WHERE id = ?
                    LIMIT 1';
            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return 0;
            }

            mysqli_stmt_bind_param(
                $st,
                'iissssssssssi',
                $orden,
                $visible,
                $titulo,
                $meta1Icon,
                $meta1Text,
                $meta2Icon,
                $meta2Text,
                $badgeText,
                $resumenText,
                $botonTexto,
                $botonUrl,
                $imagenPath,
                $id
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return $ok ? $id : 0;
        }

        $sql = 'INSERT INTO web_novedades_items
                (orden, visible, titulo, meta_1_icono, meta_1_texto, meta_2_icono, meta_2_texto,
                 badge_texto, resumen_texto, boton_texto, boton_url, imagen_path, actualizacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $st = mysqli_prepare($cn, $sql);
        if (!$st) {
            return 0;
        }

        mysqli_stmt_bind_param(
            $st,
            'iissssssssss',
            $orden,
            $visible,
            $titulo,
            $meta1Icon,
            $meta1Text,
            $meta2Icon,
            $meta2Text,
            $badgeText,
            $resumenText,
            $botonTexto,
            $botonUrl,
            $imagenPath
        );

        $ok = mysqli_stmt_execute($st);
        $newId = $ok ? (int)mysqli_insert_id($cn) : 0;
        mysqli_stmt_close($st);

        return $newId;
    }
}

if (!function_exists('cw_novedades_delete_items_not_in')) {
    function cw_novedades_delete_items_not_in(mysqli $cn, array $keepIds): array
    {
        $keep = [];
        foreach ($keepIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $keep[] = $id;
            }
        }

        $deleted = [];

        try {
            if (!empty($keep)) {
                $placeholders = implode(',', array_fill(0, count($keep), '?'));
                $types = str_repeat('i', count($keep));

                $sqlSelect = 'SELECT id, imagen_path FROM web_novedades_items WHERE id NOT IN (' . $placeholders . ')';
                $stSelect = mysqli_prepare($cn, $sqlSelect);
                if ($stSelect) {
                    mysqli_stmt_bind_param($stSelect, $types, ...$keep);
                    mysqli_stmt_execute($stSelect);
                    $rs = mysqli_stmt_get_result($stSelect);
                    if ($rs) {
                        while ($row = mysqli_fetch_assoc($rs)) {
                            $deleted[] = [
                                'id' => (int)($row['id'] ?? 0),
                                'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                            ];
                        }
                        mysqli_free_result($rs);
                    }
                    mysqli_stmt_close($stSelect);
                }

                $sqlDelete = 'DELETE FROM web_novedades_items WHERE id NOT IN (' . $placeholders . ')';
                $stDelete = mysqli_prepare($cn, $sqlDelete);
                if ($stDelete) {
                    mysqli_stmt_bind_param($stDelete, $types, ...$keep);
                    mysqli_stmt_execute($stDelete);
                    mysqli_stmt_close($stDelete);
                }

                return $deleted;
            }

            $rsAll = mysqli_query($cn, 'SELECT id, imagen_path FROM web_novedades_items');
            if ($rsAll) {
                while ($row = mysqli_fetch_assoc($rsAll)) {
                    $deleted[] = [
                        'id' => (int)($row['id'] ?? 0),
                        'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                    ];
                }
                mysqli_free_result($rsAll);
            }

            mysqli_query($cn, 'DELETE FROM web_novedades_items');
        } catch (Throwable $e) {
            return [];
        }

        return $deleted;
    }
}

if (!function_exists('cw_novedades_fetch')) {
    function cw_novedades_fetch(mysqli $cn): array
    {
        return [
            'config' => cw_novedades_fetch_config($cn),
            'items' => cw_novedades_fetch_items($cn),
        ];
    }
}

