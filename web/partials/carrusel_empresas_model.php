<?php
// web/partials/carrusel_empresas_model.php

if (!function_exists('cw_ce_social_keys')) {
    function cw_ce_social_keys(): array
    {
        return ['whatsapp', 'facebook', 'instagram', 'youtube'];
    }
}

if (!function_exists('cw_ce_social_defaults')) {
    function cw_ce_social_defaults(): array
    {
        return [
            'whatsapp' => ['visible' => 1, 'link' => '#'],
            'facebook' => ['visible' => 1, 'link' => '#'],
            'instagram' => ['visible' => 1, 'link' => '#'],
            'youtube' => ['visible' => 1, 'link' => '#'],
        ];
    }
}

if (!function_exists('cw_ce_defaults')) {
    function cw_ce_defaults(): array
    {
        $social = cw_ce_social_defaults();

        return [
            'config' => [
                'titulo_base' => 'Customer',
                'titulo_resaltado' => 'Suport Center',
            ],
            'items' => [
                [
                    'id' => 0,
                    'orden' => 1,
                    'titulo' => 'MARTIN DOE',
                    'profesion' => 'Profession',
                    'imagen_path' => '',
                    'default_image' => 'web/img/team-1.jpg',
                    'redes' => $social,
                ],
                [
                    'id' => 0,
                    'orden' => 2,
                    'titulo' => 'MARTIN DOE',
                    'profesion' => 'Profession',
                    'imagen_path' => '',
                    'default_image' => 'web/img/team-2.jpg',
                    'redes' => $social,
                ],
                [
                    'id' => 0,
                    'orden' => 3,
                    'titulo' => 'MARTIN DOE',
                    'profesion' => 'Profession',
                    'imagen_path' => '',
                    'default_image' => 'web/img/team-3.jpg',
                    'redes' => $social,
                ],
                [
                    'id' => 0,
                    'orden' => 4,
                    'titulo' => 'MARTIN DOE',
                    'profesion' => 'Profession',
                    'imagen_path' => '',
                    'default_image' => 'web/img/team-4.jpg',
                    'redes' => $social,
                ],
            ],
        ];
    }
}

if (!function_exists('cw_ce_config_defaults')) {
    function cw_ce_config_defaults(): array
    {
        return cw_ce_defaults()['config'];
    }
}

if (!function_exists('cw_ce_limit_text')) {
    function cw_ce_limit_text(string $value, int $max): string
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

if (!function_exists('cw_ce_to_flag')) {
    function cw_ce_to_flag($value, int $default = 1): int
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

if (!function_exists('cw_ce_link_valid')) {
    function cw_ce_link_valid(string $url): bool
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

if (!function_exists('cw_ce_default_image_for_position')) {
    function cw_ce_default_image_for_position(int $index): string
    {
        $fallbacks = [
            'web/img/team-1.jpg',
            'web/img/team-2.jpg',
            'web/img/team-3.jpg',
            'web/img/team-4.jpg',
        ];

        $idx = $index % count($fallbacks);
        if ($idx < 0) {
            $idx = 0;
        }

        return $fallbacks[$idx];
    }
}

if (!function_exists('cw_ce_item_default_for_position')) {
    function cw_ce_item_default_for_position(int $index): array
    {
        $defaults = cw_ce_defaults()['items'];
        if (isset($defaults[$index]) && is_array($defaults[$index])) {
            return $defaults[$index];
        }

        $num = $index + 1;

        return [
            'id' => 0,
            'orden' => $num,
            'titulo' => 'MARTIN DOE',
            'profesion' => 'Profession',
            'imagen_path' => '',
            'default_image' => cw_ce_default_image_for_position($index),
            'redes' => cw_ce_social_defaults(),
        ];
    }
}

if (!function_exists('cw_ce_normalize_socials')) {
    function cw_ce_normalize_socials($socials, int $itemIndex): array
    {
        $keys = cw_ce_social_keys();
        $baseItem = cw_ce_item_default_for_position($itemIndex);
        $defaults = isset($baseItem['redes']) && is_array($baseItem['redes'])
            ? $baseItem['redes']
            : cw_ce_social_defaults();

        $source = is_array($socials) ? $socials : [];
        $out = [];
        $visibleCount = 0;

        foreach ($keys as $key) {
            $raw = (isset($source[$key]) && is_array($source[$key])) ? $source[$key] : [];
            $base = (isset($defaults[$key]) && is_array($defaults[$key])) ? $defaults[$key] : ['visible' => 1, 'link' => '#'];

            $visible = cw_ce_to_flag($raw['visible'] ?? null, (int)($base['visible'] ?? 1));
            $link = trim((string)($raw['link'] ?? ''));

            if ($link === '' || !cw_ce_link_valid($link)) {
                $link = trim((string)($base['link'] ?? '#'));
            }
            if ($link === '' || !cw_ce_link_valid($link)) {
                $link = '#';
            }

            if ($visible === 1) {
                $visibleCount++;
            }

            $out[$key] = [
                'visible' => $visible,
                'link' => cw_ce_limit_text($link, 255),
            ];
        }

        if ($visibleCount < 1) {
            $firstKey = $keys[0];
            if (isset($out[$firstKey])) {
                $out[$firstKey]['visible'] = 1;
            }
        }

        return $out;
    }
}

if (!function_exists('cw_ce_site_base_url')) {
    function cw_ce_site_base_url(): string
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

if (!function_exists('cw_ce_default_asset_url')) {
    function cw_ce_default_asset_url(string $relativePath): string
    {
        $relativePath = '/' . ltrim(trim($relativePath), '/');
        $siteBase = cw_ce_site_base_url();
        if ($siteBase === '') {
            return $relativePath;
        }

        return $siteBase . $relativePath;
    }
}

if (!function_exists('cw_ce_image_public_url')) {
    function cw_ce_image_public_url(string $imagePath): string
    {
        $imagePath = ltrim(trim($imagePath), '/\\');
        if ($imagePath === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        if (preg_match('#^web/#i', $imagePath)) {
            return cw_ce_default_asset_url($imagePath);
        }

        if (!defined('BASE_URL')) {
            return '/sistema/' . $imagePath;
        }

        return rtrim((string)BASE_URL, '/') . '/' . $imagePath;
    }
}

if (!function_exists('cw_ce_image_exists')) {
    function cw_ce_image_exists(string $imagePath): bool
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

if (!function_exists('cw_ce_resolve_item_image_url')) {
    function cw_ce_resolve_item_image_url(array $item, int $index): string
    {
        $imagePath = trim((string)($item['imagen_path'] ?? ''));
        if ($imagePath !== '' && cw_ce_image_exists($imagePath)) {
            $customUrl = cw_ce_image_public_url($imagePath);
            if ($customUrl !== '') {
                return $customUrl;
            }
        }

        $base = cw_ce_item_default_for_position($index);
        $defaultImage = trim((string)($item['default_image'] ?? $base['default_image'] ?? ''));
        if ($defaultImage === '') {
            $defaultImage = cw_ce_default_image_for_position($index);
        }

        return cw_ce_default_asset_url($defaultImage);
    }
}

if (!function_exists('cw_ce_normalize_items')) {
    function cw_ce_normalize_items($items): array
    {
        $source = is_array($items) ? array_values($items) : [];
        if (count($source) < 1) {
            $source = cw_ce_defaults()['items'];
        }

        if (count($source) > 15) {
            $source = array_slice($source, 0, 15);
        }

        $out = [];
        foreach ($source as $i => $raw) {
            $base = cw_ce_item_default_for_position($i);
            $raw = is_array($raw) ? $raw : [];

            $id = (int)($raw['id'] ?? 0);
            $orden = (int)($raw['orden'] ?? ($i + 1));
            if ($orden < 1) {
                $orden = $i + 1;
            }

            $titulo = trim((string)($raw['titulo'] ?? ''));
            if ($titulo === '') {
                $titulo = (string)($base['titulo'] ?? 'MARTIN DOE');
            }

            $profesion = trim((string)($raw['profesion'] ?? ''));
            if ($profesion === '') {
                $profesion = (string)($base['profesion'] ?? 'Profession');
            }

            $imagenPath = cw_ce_limit_text(trim((string)($raw['imagen_path'] ?? '')), 255);

            $out[] = [
                'id' => $id,
                'orden' => $orden,
                'titulo' => cw_ce_limit_text($titulo, 80),
                'profesion' => cw_ce_limit_text($profesion, 80),
                'redes' => cw_ce_normalize_socials($raw['redes'] ?? [], $i),
                'imagen_path' => $imagenPath,
                'default_image' => (string)($base['default_image'] ?? cw_ce_default_image_for_position($i)),
            ];
        }

        usort($out, static function (array $a, array $b): int {
            return ((int)$a['orden'] <=> (int)$b['orden']);
        });

        foreach ($out as $idx => &$row) {
            $row['orden'] = $idx + 1;
            $base = cw_ce_item_default_for_position($idx);
            $row['default_image'] = (string)($base['default_image'] ?? cw_ce_default_image_for_position($idx));
            $row['redes'] = cw_ce_normalize_socials($row['redes'] ?? [], $idx);
        }
        unset($row);

        return $out;
    }
}

if (!function_exists('cw_ce_fetch_config')) {
    function cw_ce_fetch_config(mysqli $cn): array
    {
        $defaults = cw_ce_config_defaults();

        try {
            $sql = 'SELECT titulo_base, titulo_resaltado
                    FROM web_carrusel_empresas_config
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

        $tituloBase = trim((string)($row['titulo_base'] ?? ''));
        $tituloResaltado = trim((string)($row['titulo_resaltado'] ?? ''));

        if ($tituloBase === '') {
            $tituloBase = (string)($defaults['titulo_base'] ?? 'Customer');
        }
        if ($tituloResaltado === '') {
            $tituloResaltado = (string)($defaults['titulo_resaltado'] ?? 'Suport Center');
        }

        return [
            'titulo_base' => cw_ce_limit_text($tituloBase, 40),
            'titulo_resaltado' => cw_ce_limit_text($tituloResaltado, 40),
        ];
    }
}

if (!function_exists('cw_ce_upsert_config')) {
    function cw_ce_upsert_config(mysqli $cn, array $config): bool
    {
        try {
            $sql = 'INSERT INTO web_carrusel_empresas_config
                    (id, titulo_base, titulo_resaltado, actualizacion)
                    VALUES
                    (1, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        titulo_base = VALUES(titulo_base),
                        titulo_resaltado = VALUES(titulo_resaltado),
                        actualizacion = NOW()';

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            $defaults = cw_ce_config_defaults();
            $tituloBase = cw_ce_limit_text(trim((string)($config['titulo_base'] ?? '')), 40);
            $tituloResaltado = cw_ce_limit_text(trim((string)($config['titulo_resaltado'] ?? '')), 40);

            if ($tituloBase === '') {
                $tituloBase = (string)($defaults['titulo_base'] ?? 'Customer');
            }
            if ($tituloResaltado === '') {
                $tituloResaltado = (string)($defaults['titulo_resaltado'] ?? 'Suport Center');
            }

            mysqli_stmt_bind_param(
                $st,
                'ss',
                $tituloBase,
                $tituloResaltado
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_ce_fetch_items')) {
    function cw_ce_fetch_items(mysqli $cn): array
    {
        try {
            $sql = 'SELECT id, orden, titulo, profesion, imagen_path, redes_json
                    FROM web_carrusel_empresas_items
                    ORDER BY orden ASC, id ASC
                    LIMIT 15';

            $rs = mysqli_query($cn, $sql);
            if (!$rs) {
                return cw_ce_normalize_items([]);
            }

            $rows = [];
            while ($row = mysqli_fetch_assoc($rs)) {
                $redes = json_decode((string)($row['redes_json'] ?? ''), true);
                $rows[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'orden' => (int)($row['orden'] ?? 0),
                    'titulo' => trim((string)($row['titulo'] ?? '')),
                    'profesion' => trim((string)($row['profesion'] ?? '')),
                    'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                    'redes' => is_array($redes) ? $redes : [],
                ];
            }
            mysqli_free_result($rs);

            return cw_ce_normalize_items($rows);
        } catch (Throwable $e) {
            return cw_ce_normalize_items([]);
        }
    }
}

if (!function_exists('cw_ce_fetch_rows_by_id')) {
    function cw_ce_fetch_rows_by_id(mysqli $cn): array
    {
        $out = [];

        try {
            $sql = 'SELECT id, orden, titulo, profesion, imagen_path, redes_json
                    FROM web_carrusel_empresas_items
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

                $redes = json_decode((string)($row['redes_json'] ?? ''), true);

                $out[$id] = [
                    'id' => $id,
                    'orden' => (int)($row['orden'] ?? 0),
                    'titulo' => trim((string)($row['titulo'] ?? '')),
                    'profesion' => trim((string)($row['profesion'] ?? '')),
                    'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                    'redes' => is_array($redes) ? $redes : [],
                ];
            }
            mysqli_free_result($rs);
        } catch (Throwable $e) {
            return [];
        }

        return $out;
    }
}

if (!function_exists('cw_ce_upsert_item')) {
    function cw_ce_upsert_item(mysqli $cn, array $item): int
    {
        $id = (int)($item['id'] ?? 0);
        $orden = (int)($item['orden'] ?? 1);
        if ($orden < 1) {
            $orden = 1;
        }

        $titulo = cw_ce_limit_text(trim((string)($item['titulo'] ?? '')), 80);
        $profesion = cw_ce_limit_text(trim((string)($item['profesion'] ?? '')), 80);
        $imagenPath = cw_ce_limit_text(trim((string)($item['imagen_path'] ?? '')), 255);
        $redes = cw_ce_normalize_socials($item['redes'] ?? [], max(0, $orden - 1));
        $redesJson = json_encode($redes, JSON_UNESCAPED_UNICODE);
        if ($redesJson === false) {
            return 0;
        }

        if ($id > 0) {
            $sql = 'UPDATE web_carrusel_empresas_items
                    SET orden = ?, titulo = ?, profesion = ?, imagen_path = ?, redes_json = ?, actualizacion = NOW()
                    WHERE id = ?
                    LIMIT 1';
            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return 0;
            }

            mysqli_stmt_bind_param(
                $st,
                'issssi',
                $orden,
                $titulo,
                $profesion,
                $imagenPath,
                $redesJson,
                $id
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return $ok ? $id : 0;
        }

        $sql = 'INSERT INTO web_carrusel_empresas_items
                (orden, titulo, profesion, imagen_path, redes_json, actualizacion)
                VALUES (?, ?, ?, ?, ?, NOW())';
        $st = mysqli_prepare($cn, $sql);
        if (!$st) {
            return 0;
        }

        mysqli_stmt_bind_param(
            $st,
            'issss',
            $orden,
            $titulo,
            $profesion,
            $imagenPath,
            $redesJson
        );

        $ok = mysqli_stmt_execute($st);
        $newId = $ok ? (int)mysqli_insert_id($cn) : 0;
        mysqli_stmt_close($st);

        return $newId;
    }
}

if (!function_exists('cw_ce_delete_items_not_in')) {
    function cw_ce_delete_items_not_in(mysqli $cn, array $keepIds): array
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

                $sqlSelect = 'SELECT id, imagen_path FROM web_carrusel_empresas_items WHERE id NOT IN (' . $placeholders . ')';
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

                $sqlDelete = 'DELETE FROM web_carrusel_empresas_items WHERE id NOT IN (' . $placeholders . ')';
                $stDelete = mysqli_prepare($cn, $sqlDelete);
                if ($stDelete) {
                    mysqli_stmt_bind_param($stDelete, $types, ...$keep);
                    mysqli_stmt_execute($stDelete);
                    mysqli_stmt_close($stDelete);
                }

                return $deleted;
            }

            $rsAll = mysqli_query($cn, 'SELECT id, imagen_path FROM web_carrusel_empresas_items');
            if ($rsAll) {
                while ($row = mysqli_fetch_assoc($rsAll)) {
                    $deleted[] = [
                        'id' => (int)($row['id'] ?? 0),
                        'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                    ];
                }
                mysqli_free_result($rsAll);
            }

            mysqli_query($cn, 'DELETE FROM web_carrusel_empresas_items');
        } catch (Throwable $e) {
            return [];
        }

        return $deleted;
    }
}

if (!function_exists('cw_ce_fetch')) {
    function cw_ce_fetch(mysqli $cn): array
    {
        return [
            'config' => cw_ce_fetch_config($cn),
            'items' => cw_ce_fetch_items($cn),
        ];
    }
}
