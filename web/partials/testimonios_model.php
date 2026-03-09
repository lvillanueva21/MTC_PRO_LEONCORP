<?php
// web/partials/testimonios_model.php

if (!function_exists('cw_testimonios_defaults')) {
    function cw_testimonios_defaults(): array
    {
        return [
            'config' => [
                'titulo_base' => 'Our Clients',
                'titulo_resaltado' => 'Riviews',
                'descripcion_general' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
            ],
            'items' => [
                [
                    'id' => 0,
                    'orden' => 1,
                    'nombre_cliente' => 'Person Name',
                    'profesion' => 'Profession',
                    'testimonio' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quam soluta neque ab repudiandae reprehenderit ipsum eos cumque esse repellendus impedit.',
                    'imagen_path' => '',
                    'default_image' => 'web/img/testimonial-1.jpg',
                ],
                [
                    'id' => 0,
                    'orden' => 2,
                    'nombre_cliente' => 'Person Name',
                    'profesion' => 'Profession',
                    'testimonio' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quam soluta neque ab repudiandae reprehenderit ipsum eos cumque esse repellendus impedit.',
                    'imagen_path' => '',
                    'default_image' => 'web/img/testimonial-2.jpg',
                ],
            ],
        ];
    }
}

if (!function_exists('cw_testimonios_config_defaults')) {
    function cw_testimonios_config_defaults(): array
    {
        return cw_testimonios_defaults()['config'];
    }
}

if (!function_exists('cw_testimonios_limit_text')) {
    function cw_testimonios_limit_text(string $value, int $max): string
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

if (!function_exists('cw_testimonios_site_base_url')) {
    function cw_testimonios_site_base_url(): string
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

if (!function_exists('cw_testimonios_default_asset_url')) {
    function cw_testimonios_default_asset_url(string $relativePath): string
    {
        $relativePath = '/' . ltrim(trim($relativePath), '/');
        $siteBase = cw_testimonios_site_base_url();
        if ($siteBase === '') {
            return $relativePath;
        }

        return $siteBase . $relativePath;
    }
}

if (!function_exists('cw_testimonios_image_public_url')) {
    function cw_testimonios_image_public_url(string $imagePath): string
    {
        $imagePath = ltrim(trim($imagePath), '/\\');
        if ($imagePath === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        if (preg_match('#^web/#i', $imagePath)) {
            return cw_testimonios_default_asset_url($imagePath);
        }

        if (!defined('BASE_URL')) {
            return '/sistema/' . $imagePath;
        }

        return rtrim((string)BASE_URL, '/') . '/' . $imagePath;
    }
}

if (!function_exists('cw_testimonios_image_exists')) {
    function cw_testimonios_image_exists(string $imagePath): bool
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

if (!function_exists('cw_testimonios_default_image_for_position')) {
    function cw_testimonios_default_image_for_position(int $index): string
    {
        $fallbacks = [
            'web/img/testimonial-1.jpg',
            'web/img/testimonial-2.jpg',
        ];

        $idx = $index % count($fallbacks);
        if ($idx < 0) {
            $idx = 0;
        }

        return $fallbacks[$idx];
    }
}

if (!function_exists('cw_testimonios_item_default_for_position')) {
    function cw_testimonios_item_default_for_position(int $index): array
    {
        $defaults = cw_testimonios_defaults()['items'];
        if (isset($defaults[$index]) && is_array($defaults[$index])) {
            return $defaults[$index];
        }

        return [
            'id' => 0,
            'orden' => $index + 1,
            'nombre_cliente' => 'Person Name',
            'profesion' => 'Profession',
            'testimonio' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quam soluta neque ab repudiandae reprehenderit ipsum eos cumque esse repellendus impedit.',
            'imagen_path' => '',
            'default_image' => cw_testimonios_default_image_for_position($index),
        ];
    }
}

if (!function_exists('cw_testimonios_resolve_item_image_url')) {
    function cw_testimonios_resolve_item_image_url(array $item, int $index): string
    {
        $imagePath = trim((string)($item['imagen_path'] ?? ''));
        if ($imagePath !== '' && cw_testimonios_image_exists($imagePath)) {
            $customUrl = cw_testimonios_image_public_url($imagePath);
            if ($customUrl !== '') {
                return $customUrl;
            }
        }

        $base = cw_testimonios_item_default_for_position($index);
        $defaultImage = trim((string)($item['default_image'] ?? $base['default_image'] ?? ''));
        if ($defaultImage === '') {
            $defaultImage = cw_testimonios_default_image_for_position($index);
        }

        return cw_testimonios_default_asset_url($defaultImage);
    }
}

if (!function_exists('cw_testimonios_normalize_config')) {
    function cw_testimonios_normalize_config($config): array
    {
        $defaults = cw_testimonios_config_defaults();
        $config = is_array($config) ? $config : [];

        $tituloBase = trim((string)($config['titulo_base'] ?? ''));
        $tituloResaltado = trim((string)($config['titulo_resaltado'] ?? ''));
        $descripcionGeneral = trim((string)($config['descripcion_general'] ?? ''));

        if ($tituloBase === '') {
            $tituloBase = (string)($defaults['titulo_base'] ?? 'Our Clients');
        }
        if ($tituloResaltado === '') {
            $tituloResaltado = (string)($defaults['titulo_resaltado'] ?? 'Riviews');
        }
        if ($descripcionGeneral === '') {
            $descripcionGeneral = (string)($defaults['descripcion_general'] ?? '');
        }

        return [
            'titulo_base' => cw_testimonios_limit_text($tituloBase, 40),
            'titulo_resaltado' => cw_testimonios_limit_text($tituloResaltado, 40),
            'descripcion_general' => cw_testimonios_limit_text($descripcionGeneral, 260),
        ];
    }
}

if (!function_exists('cw_testimonios_normalize_items')) {
    function cw_testimonios_normalize_items($items): array
    {
        $source = is_array($items) ? array_values($items) : [];
        $mappedByOrder = [];

        foreach ($source as $raw) {
            if (!is_array($raw)) {
                continue;
            }

            $order = (int)($raw['orden'] ?? 0);
            if ($order < 1 || $order > 2) {
                continue;
            }

            if (!isset($mappedByOrder[$order])) {
                $mappedByOrder[$order] = $raw;
            }
        }

        $out = [];
        for ($i = 0; $i < 2; $i++) {
            $order = $i + 1;
            $base = cw_testimonios_item_default_for_position($i);
            $raw = (isset($mappedByOrder[$order]) && is_array($mappedByOrder[$order])) ? $mappedByOrder[$order] : [];

            $nombre = trim((string)($raw['nombre_cliente'] ?? ''));
            $profesion = trim((string)($raw['profesion'] ?? ''));
            $testimonio = trim((string)($raw['testimonio'] ?? ''));

            if ($nombre === '') {
                $nombre = (string)($base['nombre_cliente'] ?? 'Person Name');
            }
            if ($profesion === '') {
                $profesion = (string)($base['profesion'] ?? 'Profession');
            }
            if ($testimonio === '') {
                $testimonio = (string)($base['testimonio'] ?? '');
            }

            $out[] = [
                'id' => (int)($raw['id'] ?? 0),
                'orden' => $order,
                'nombre_cliente' => cw_testimonios_limit_text($nombre, 80),
                'profesion' => cw_testimonios_limit_text($profesion, 80),
                'testimonio' => cw_testimonios_limit_text($testimonio, 280),
                'imagen_path' => cw_testimonios_limit_text(trim((string)($raw['imagen_path'] ?? '')), 255),
                'default_image' => (string)($base['default_image'] ?? cw_testimonios_default_image_for_position($i)),
            ];
        }

        return $out;
    }
}

if (!function_exists('cw_testimonios_fetch_config')) {
    function cw_testimonios_fetch_config(mysqli $cn): array
    {
        $defaults = cw_testimonios_config_defaults();

        try {
            $sql = 'SELECT titulo_base, titulo_resaltado, descripcion_general
                    FROM web_testimonios_config
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

        return cw_testimonios_normalize_config($row);
    }
}

if (!function_exists('cw_testimonios_upsert_config')) {
    function cw_testimonios_upsert_config(mysqli $cn, array $config): bool
    {
        $payload = cw_testimonios_normalize_config($config);

        try {
            $sql = 'INSERT INTO web_testimonios_config
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

if (!function_exists('cw_testimonios_fetch_items')) {
    function cw_testimonios_fetch_items(mysqli $cn): array
    {
        try {
            $sql = 'SELECT id, orden, nombre_cliente, profesion, testimonio, imagen_path
                    FROM web_testimonios_items
                    ORDER BY orden ASC, id ASC';

            $rs = mysqli_query($cn, $sql);
            if (!$rs) {
                return cw_testimonios_normalize_items([]);
            }

            $rows = [];
            while ($row = mysqli_fetch_assoc($rs)) {
                $rows[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'orden' => (int)($row['orden'] ?? 0),
                    'nombre_cliente' => trim((string)($row['nombre_cliente'] ?? '')),
                    'profesion' => trim((string)($row['profesion'] ?? '')),
                    'testimonio' => trim((string)($row['testimonio'] ?? '')),
                    'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                ];
            }
            mysqli_free_result($rs);

            return cw_testimonios_normalize_items($rows);
        } catch (Throwable $e) {
            return cw_testimonios_normalize_items([]);
        }
    }
}

if (!function_exists('cw_testimonios_fetch_rows_by_order')) {
    function cw_testimonios_fetch_rows_by_order(mysqli $cn): array
    {
        $out = [];

        try {
            $sql = 'SELECT id, orden, nombre_cliente, profesion, testimonio, imagen_path
                    FROM web_testimonios_items
                    ORDER BY orden ASC, id ASC';

            $rs = mysqli_query($cn, $sql);
            if (!$rs) {
                return $out;
            }

            while ($row = mysqli_fetch_assoc($rs)) {
                $order = (int)($row['orden'] ?? 0);
                if ($order < 1) {
                    continue;
                }

                if (!isset($out[$order])) {
                    $out[$order] = [
                        'id' => (int)($row['id'] ?? 0),
                        'orden' => $order,
                        'nombre_cliente' => trim((string)($row['nombre_cliente'] ?? '')),
                        'profesion' => trim((string)($row['profesion'] ?? '')),
                        'testimonio' => trim((string)($row['testimonio'] ?? '')),
                        'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                    ];
                }
            }
            mysqli_free_result($rs);
        } catch (Throwable $e) {
            return [];
        }

        return $out;
    }
}

if (!function_exists('cw_testimonios_upsert_item')) {
    function cw_testimonios_upsert_item(mysqli $cn, array $item): bool
    {
        $order = (int)($item['orden'] ?? 0);
        if ($order < 1 || $order > 2) {
            return false;
        }

        $base = cw_testimonios_item_default_for_position($order - 1);
        $nombre = cw_testimonios_limit_text(trim((string)($item['nombre_cliente'] ?? '')), 80);
        $profesion = cw_testimonios_limit_text(trim((string)($item['profesion'] ?? '')), 80);
        $testimonio = cw_testimonios_limit_text(trim((string)($item['testimonio'] ?? '')), 280);
        $imagenPath = cw_testimonios_limit_text(trim((string)($item['imagen_path'] ?? '')), 255);

        if ($nombre === '') {
            $nombre = (string)($base['nombre_cliente'] ?? 'Person Name');
        }
        if ($profesion === '') {
            $profesion = (string)($base['profesion'] ?? 'Profession');
        }
        if ($testimonio === '') {
            $testimonio = (string)($base['testimonio'] ?? '');
        }

        try {
            $sql = 'INSERT INTO web_testimonios_items
                    (orden, nombre_cliente, profesion, testimonio, imagen_path, actualizacion)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        nombre_cliente = VALUES(nombre_cliente),
                        profesion = VALUES(profesion),
                        testimonio = VALUES(testimonio),
                        imagen_path = VALUES(imagen_path),
                        actualizacion = NOW()';

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param(
                $st,
                'issss',
                $order,
                $nombre,
                $profesion,
                $testimonio,
                $imagenPath
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_testimonios_delete_items_not_in_orders')) {
    function cw_testimonios_delete_items_not_in_orders(mysqli $cn, array $keepOrders): array
    {
        $keep = [];
        foreach ($keepOrders as $order) {
            $order = (int)$order;
            if ($order > 0) {
                $keep[] = $order;
            }
        }

        $deleted = [];

        try {
            if (!empty($keep)) {
                $placeholders = implode(',', array_fill(0, count($keep), '?'));
                $types = str_repeat('i', count($keep));

                $sqlSelect = 'SELECT id, imagen_path FROM web_testimonios_items WHERE orden NOT IN (' . $placeholders . ')';
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

                $sqlDelete = 'DELETE FROM web_testimonios_items WHERE orden NOT IN (' . $placeholders . ')';
                $stDelete = mysqli_prepare($cn, $sqlDelete);
                if ($stDelete) {
                    mysqli_stmt_bind_param($stDelete, $types, ...$keep);
                    mysqli_stmt_execute($stDelete);
                    mysqli_stmt_close($stDelete);
                }

                return $deleted;
            }

            $rsAll = mysqli_query($cn, 'SELECT id, imagen_path FROM web_testimonios_items');
            if ($rsAll) {
                while ($row = mysqli_fetch_assoc($rsAll)) {
                    $deleted[] = [
                        'id' => (int)($row['id'] ?? 0),
                        'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                    ];
                }
                mysqli_free_result($rsAll);
            }

            mysqli_query($cn, 'DELETE FROM web_testimonios_items');
        } catch (Throwable $e) {
            return [];
        }

        return $deleted;
    }
}

if (!function_exists('cw_testimonios_fetch')) {
    function cw_testimonios_fetch(mysqli $cn): array
    {
        return [
            'config' => cw_testimonios_fetch_config($cn),
            'items' => cw_testimonios_fetch_items($cn),
        ];
    }
}
