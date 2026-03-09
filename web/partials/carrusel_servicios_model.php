<?php
// web/partials/carrusel_servicios_model.php

if (!function_exists('cw_cs_defaults')) {
    function cw_cs_defaults(): array
    {
        $defaultDetails = cw_cs_detail_defaults();

        return [
            'config' => [
                'titulo_base' => 'Vehicle',
                'titulo_resaltado' => 'Categories',
                'descripcion_general' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
            ],
            'items' => [
                [
                    'id' => 0,
                    'orden' => 1,
                    'titulo' => 'Mercedes Benz R3',
                    'review_text' => '4.5 Review',
                    'rating' => 4,
                    'mostrar_estrellas' => 1,
                    'badge_text' => '$99:00/Day',
                    'detalles' => $defaultDetails,
                    'boton_texto' => 'Book Now',
                    'boton_url' => '#',
                    'imagen_path' => '',
                    'default_image' => 'web/img/car-1.png',
                ],
                [
                    'id' => 0,
                    'orden' => 2,
                    'titulo' => 'Toyota Corolla Cross',
                    'review_text' => '3.5 Review',
                    'rating' => 4,
                    'mostrar_estrellas' => 1,
                    'badge_text' => '$128:00/Day',
                    'detalles' => $defaultDetails,
                    'boton_texto' => 'Book Now',
                    'boton_url' => '#',
                    'imagen_path' => '',
                    'default_image' => 'web/img/car-2.png',
                ],
                [
                    'id' => 0,
                    'orden' => 3,
                    'titulo' => 'Tesla Model S Plaid',
                    'review_text' => '3.8 Review',
                    'rating' => 4,
                    'mostrar_estrellas' => 1,
                    'badge_text' => '$170:00/Day',
                    'detalles' => $defaultDetails,
                    'boton_texto' => 'Book Now',
                    'boton_url' => '#',
                    'imagen_path' => '',
                    'default_image' => 'web/img/car-3.png',
                ],
                [
                    'id' => 0,
                    'orden' => 4,
                    'titulo' => 'Hyundai Kona Electric',
                    'review_text' => '4.8 Review',
                    'rating' => 5,
                    'mostrar_estrellas' => 1,
                    'badge_text' => '$187:00/Day',
                    'detalles' => $defaultDetails,
                    'boton_texto' => 'Book Now',
                    'boton_url' => '#',
                    'imagen_path' => '',
                    'default_image' => 'web/img/car-4.png',
                ],
            ],
        ];
    }
}

if (!function_exists('cw_cs_detail_defaults')) {
    function cw_cs_detail_defaults(): array
    {
        return [
            ['visible' => 1, 'icono' => 'fa fa-users', 'texto' => '4 Seat'],
            ['visible' => 1, 'icono' => 'fa fa-car', 'texto' => 'AT/MT'],
            ['visible' => 1, 'icono' => 'fa fa-gas-pump', 'texto' => 'Petrol'],
            ['visible' => 1, 'icono' => 'fa fa-car', 'texto' => '2015'],
            ['visible' => 1, 'icono' => 'fa fa-cogs', 'texto' => 'AUTO'],
            ['visible' => 1, 'icono' => 'fa fa-road', 'texto' => '27K'],
        ];
    }
}

if (!function_exists('cw_cs_limit_text')) {
    function cw_cs_limit_text(string $value, int $max): string
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

if (!function_exists('cw_cs_sanitize_icon_class')) {
    function cw_cs_sanitize_icon_class(string $iconClass): string
    {
        $iconClass = trim((string)preg_replace('/\s+/', ' ', trim($iconClass)));
        if ($iconClass === '') {
            return '';
        }

        if (!preg_match('/^[a-zA-Z0-9 _:\-]+$/', $iconClass)) {
            return '';
        }

        return cw_cs_limit_text($iconClass, 120);
    }
}

if (!function_exists('cw_cs_to_flag')) {
    function cw_cs_to_flag($value, int $default = 1): int
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

if (!function_exists('cw_cs_link_valid')) {
    function cw_cs_link_valid(string $url): bool
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

if (!function_exists('cw_cs_default_image_for_position')) {
    function cw_cs_default_image_for_position(int $index): string
    {
        $fallbacks = [
            'web/img/car-1.png',
            'web/img/car-2.png',
            'web/img/car-3.png',
            'web/img/car-4.png',
        ];

        $idx = $index % count($fallbacks);
        if ($idx < 0) {
            $idx = 0;
        }

        return $fallbacks[$idx];
    }
}

if (!function_exists('cw_cs_item_default_for_position')) {
    function cw_cs_item_default_for_position(int $index): array
    {
        $defaults = cw_cs_defaults()['items'];
        if (isset($defaults[$index]) && is_array($defaults[$index])) {
            return $defaults[$index];
        }

        $num = $index + 1;
        return [
            'id' => 0,
            'orden' => $num,
            'titulo' => 'Servicio destacado ' . $num,
            'review_text' => '4.0 Review',
            'rating' => 4,
            'mostrar_estrellas' => 1,
            'badge_text' => 'Consulta precio',
            'detalles' => cw_cs_detail_defaults(),
            'boton_texto' => 'Book Now',
            'boton_url' => '#',
            'imagen_path' => '',
            'default_image' => cw_cs_default_image_for_position($index),
        ];
    }
}

if (!function_exists('cw_cs_normalize_details')) {
    function cw_cs_normalize_details($details, int $itemIndex): array
    {
        $defaults = cw_cs_item_default_for_position($itemIndex);
        $defaultsDetails = isset($defaults['detalles']) && is_array($defaults['detalles'])
            ? $defaults['detalles']
            : cw_cs_detail_defaults();

        $source = is_array($details) ? array_values($details) : [];
        $out = [];

        for ($i = 0; $i < 6; $i++) {
            $raw = [];
            if (isset($source[$i]) && is_array($source[$i])) {
                $raw = $source[$i];
            }

            $base = isset($defaultsDetails[$i]) && is_array($defaultsDetails[$i])
                ? $defaultsDetails[$i]
                : ['visible' => 1, 'icono' => 'fa fa-circle', 'texto' => 'Detalle'];

            $visible = cw_cs_to_flag($raw['visible'] ?? null, (int)($base['visible'] ?? 1));
            $icono = cw_cs_sanitize_icon_class((string)($raw['icono'] ?? ''));
            $texto = trim((string)($raw['texto'] ?? ''));

            if ($icono === '') {
                $icono = cw_cs_sanitize_icon_class((string)($base['icono'] ?? 'fa fa-circle'));
            }
            if ($texto === '') {
                $texto = (string)($base['texto'] ?? 'Detalle');
            }

            $out[] = [
                'visible' => $visible,
                'icono' => cw_cs_limit_text($icono, 120),
                'texto' => cw_cs_limit_text($texto, 40),
            ];
        }

        return $out;
    }
}

if (!function_exists('cw_cs_site_base_url')) {
    function cw_cs_site_base_url(): string
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

if (!function_exists('cw_cs_default_asset_url')) {
    function cw_cs_default_asset_url(string $relativePath): string
    {
        $relativePath = '/' . ltrim(trim($relativePath), '/');
        $siteBase = cw_cs_site_base_url();
        if ($siteBase === '') {
            return $relativePath;
        }

        return $siteBase . $relativePath;
    }
}

if (!function_exists('cw_cs_image_public_url')) {
    function cw_cs_image_public_url(string $imagePath): string
    {
        $imagePath = ltrim(trim($imagePath), '/\\');
        if ($imagePath === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        if (preg_match('#^web/#i', $imagePath)) {
            return cw_cs_default_asset_url($imagePath);
        }

        if (!defined('BASE_URL')) {
            return '/sistema/' . $imagePath;
        }

        return rtrim((string)BASE_URL, '/') . '/' . $imagePath;
    }
}

if (!function_exists('cw_cs_image_exists')) {
    function cw_cs_image_exists(string $imagePath): bool
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

if (!function_exists('cw_cs_resolve_item_image_url')) {
    function cw_cs_resolve_item_image_url(array $item, int $index): string
    {
        $imagePath = trim((string)($item['imagen_path'] ?? ''));
        if ($imagePath !== '' && cw_cs_image_exists($imagePath)) {
            $customUrl = cw_cs_image_public_url($imagePath);
            if ($customUrl !== '') {
                return $customUrl;
            }
        }

        $base = cw_cs_item_default_for_position($index);
        $defaultImage = trim((string)($item['default_image'] ?? $base['default_image'] ?? ''));
        if ($defaultImage === '') {
            $defaultImage = cw_cs_default_image_for_position($index);
        }

        return cw_cs_default_asset_url($defaultImage);
    }
}

if (!function_exists('cw_cs_normalize_items')) {
    function cw_cs_normalize_items($items): array
    {
        $source = is_array($items) ? array_values($items) : [];
        if (count($source) < 1) {
            $source = cw_cs_defaults()['items'];
        }

        if (count($source) > 9) {
            $source = array_slice($source, 0, 9);
        }

        $out = [];
        foreach ($source as $i => $raw) {
            $base = cw_cs_item_default_for_position($i);
            $raw = is_array($raw) ? $raw : [];

            $id = (int)($raw['id'] ?? 0);
            $orden = (int)($raw['orden'] ?? ($i + 1));
            if ($orden < 1) {
                $orden = $i + 1;
            }

            $titulo = trim((string)($raw['titulo'] ?? ''));
            if ($titulo === '') {
                $titulo = (string)($base['titulo'] ?? '');
            }

            $reviewText = trim((string)($raw['review_text'] ?? ''));
            if ($reviewText === '') {
                $reviewText = (string)($base['review_text'] ?? '');
            }

            $rating = (int)($raw['rating'] ?? ($base['rating'] ?? 4));
            if ($rating < 1 || $rating > 5) {
                $rating = (int)($base['rating'] ?? 4);
            }
            if ($rating < 1 || $rating > 5) {
                $rating = 4;
            }

            $mostrarEstrellas = cw_cs_to_flag($raw['mostrar_estrellas'] ?? null, (int)($base['mostrar_estrellas'] ?? 1));

            $badgeText = trim((string)($raw['badge_text'] ?? ''));
            if ($badgeText === '') {
                $badgeText = (string)($base['badge_text'] ?? '');
            }

            $botonTexto = trim((string)($raw['boton_texto'] ?? ''));
            if ($botonTexto === '') {
                $botonTexto = (string)($base['boton_texto'] ?? '');
            }

            $botonUrl = trim((string)($raw['boton_url'] ?? ''));
            if ($botonUrl === '' || !cw_cs_link_valid($botonUrl)) {
                $botonUrl = (string)($base['boton_url'] ?? '#');
            }
            if ($botonUrl === '' || !cw_cs_link_valid($botonUrl)) {
                $botonUrl = '#';
            }

            $imagenPath = trim((string)($raw['imagen_path'] ?? ''));

            $out[] = [
                'id' => $id,
                'orden' => $orden,
                'titulo' => cw_cs_limit_text($titulo, 80),
                'review_text' => cw_cs_limit_text($reviewText, 60),
                'rating' => $rating,
                'mostrar_estrellas' => $mostrarEstrellas,
                'badge_text' => cw_cs_limit_text($badgeText, 80),
                'detalles' => cw_cs_normalize_details($raw['detalles'] ?? [], $i),
                'boton_texto' => cw_cs_limit_text($botonTexto, 50),
                'boton_url' => cw_cs_limit_text($botonUrl, 255),
                'imagen_path' => cw_cs_limit_text($imagenPath, 255),
                'default_image' => (string)($base['default_image'] ?? cw_cs_default_image_for_position($i)),
            ];
        }

        usort($out, static function (array $a, array $b): int {
            return ((int)$a['orden'] <=> (int)$b['orden']);
        });

        foreach ($out as $idx => &$row) {
            $row['orden'] = $idx + 1;
            $base = cw_cs_item_default_for_position($idx);
            $row['default_image'] = (string)($base['default_image'] ?? cw_cs_default_image_for_position($idx));
            $row['detalles'] = cw_cs_normalize_details($row['detalles'] ?? [], $idx);
        }
        unset($row);

        return $out;
    }
}

if (!function_exists('cw_cs_config_defaults')) {
    function cw_cs_config_defaults(): array
    {
        return cw_cs_defaults()['config'];
    }
}

if (!function_exists('cw_cs_fetch_config')) {
    function cw_cs_fetch_config(mysqli $cn): array
    {
        $defaults = cw_cs_config_defaults();

        try {
            $sql = 'SELECT titulo_base, titulo_resaltado, descripcion_general
                    FROM web_carrusel_servicios_config
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
        $descripcionGeneral = trim((string)($row['descripcion_general'] ?? ''));

        if ($tituloBase === '') {
            $tituloBase = $defaults['titulo_base'];
        }
        if ($tituloResaltado === '') {
            $tituloResaltado = $defaults['titulo_resaltado'];
        }
        if ($descripcionGeneral === '') {
            $descripcionGeneral = $defaults['descripcion_general'];
        }

        return [
            'titulo_base' => cw_cs_limit_text($tituloBase, 40),
            'titulo_resaltado' => cw_cs_limit_text($tituloResaltado, 40),
            'descripcion_general' => cw_cs_limit_text($descripcionGeneral, 320),
        ];
    }
}

if (!function_exists('cw_cs_upsert_config')) {
    function cw_cs_upsert_config(mysqli $cn, array $config): bool
    {
        try {
            $sql = 'INSERT INTO web_carrusel_servicios_config
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

            $tituloBase = cw_cs_limit_text(trim((string)($config['titulo_base'] ?? '')), 40);
            $tituloResaltado = cw_cs_limit_text(trim((string)($config['titulo_resaltado'] ?? '')), 40);
            $descripcionGeneral = cw_cs_limit_text(trim((string)($config['descripcion_general'] ?? '')), 320);

            mysqli_stmt_bind_param(
                $st,
                'sss',
                $tituloBase,
                $tituloResaltado,
                $descripcionGeneral
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_cs_fetch_items')) {
    function cw_cs_fetch_items(mysqli $cn): array
    {
        try {
            $sql = 'SELECT id, orden, titulo, review_text, rating, mostrar_estrellas,
                           badge_text, detalles_json, boton_texto, boton_url, imagen_path
                    FROM web_carrusel_servicios_items
                    ORDER BY orden ASC, id ASC
                    LIMIT 9';

            $rs = mysqli_query($cn, $sql);
            if (!$rs) {
                return cw_cs_normalize_items([]);
            }

            $rows = [];
            while ($row = mysqli_fetch_assoc($rs)) {
                $detalles = json_decode((string)($row['detalles_json'] ?? ''), true);

                $rows[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'orden' => (int)($row['orden'] ?? 0),
                    'titulo' => trim((string)($row['titulo'] ?? '')),
                    'review_text' => trim((string)($row['review_text'] ?? '')),
                    'rating' => (int)($row['rating'] ?? 0),
                    'mostrar_estrellas' => (int)($row['mostrar_estrellas'] ?? 1),
                    'badge_text' => trim((string)($row['badge_text'] ?? '')),
                    'detalles' => is_array($detalles) ? $detalles : [],
                    'boton_texto' => trim((string)($row['boton_texto'] ?? '')),
                    'boton_url' => trim((string)($row['boton_url'] ?? '')),
                    'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                ];
            }
            mysqli_free_result($rs);

            return cw_cs_normalize_items($rows);
        } catch (Throwable $e) {
            return cw_cs_normalize_items([]);
        }
    }
}

if (!function_exists('cw_cs_fetch_rows_by_id')) {
    function cw_cs_fetch_rows_by_id(mysqli $cn): array
    {
        $out = [];

        try {
            $sql = 'SELECT id, orden, titulo, review_text, rating, mostrar_estrellas,
                           badge_text, detalles_json, boton_texto, boton_url, imagen_path
                    FROM web_carrusel_servicios_items
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

                $detalles = json_decode((string)($row['detalles_json'] ?? ''), true);

                $out[$id] = [
                    'id' => $id,
                    'orden' => (int)($row['orden'] ?? 0),
                    'titulo' => trim((string)($row['titulo'] ?? '')),
                    'review_text' => trim((string)($row['review_text'] ?? '')),
                    'rating' => (int)($row['rating'] ?? 0),
                    'mostrar_estrellas' => (int)($row['mostrar_estrellas'] ?? 1),
                    'badge_text' => trim((string)($row['badge_text'] ?? '')),
                    'detalles' => is_array($detalles) ? $detalles : [],
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

if (!function_exists('cw_cs_upsert_item')) {
    function cw_cs_upsert_item(mysqli $cn, array $item): int
    {
        $id = (int)($item['id'] ?? 0);
        $orden = (int)($item['orden'] ?? 1);
        if ($orden < 1) {
            $orden = 1;
        }

        $titulo = cw_cs_limit_text(trim((string)($item['titulo'] ?? '')), 80);
        $reviewText = cw_cs_limit_text(trim((string)($item['review_text'] ?? '')), 60);
        $rating = (int)($item['rating'] ?? 4);
        if ($rating < 1 || $rating > 5) {
            $rating = 4;
        }

        $mostrarEstrellas = cw_cs_to_flag($item['mostrar_estrellas'] ?? 1, 1);
        $badgeText = cw_cs_limit_text(trim((string)($item['badge_text'] ?? '')), 80);
        $detalles = cw_cs_normalize_details($item['detalles'] ?? [], max(0, $orden - 1));
        $detallesJson = json_encode($detalles, JSON_UNESCAPED_UNICODE);
        if ($detallesJson === false) {
            return 0;
        }

        $botonTexto = cw_cs_limit_text(trim((string)($item['boton_texto'] ?? '')), 50);
        $botonUrl = trim((string)($item['boton_url'] ?? '#'));
        if (!cw_cs_link_valid($botonUrl)) {
            $botonUrl = '#';
        }
        $botonUrl = cw_cs_limit_text($botonUrl, 255);
        $imagenPath = cw_cs_limit_text(trim((string)($item['imagen_path'] ?? '')), 255);

        if ($id > 0) {
            $sql = 'UPDATE web_carrusel_servicios_items
                    SET orden = ?, titulo = ?, review_text = ?, rating = ?, mostrar_estrellas = ?,
                        badge_text = ?, detalles_json = ?, boton_texto = ?, boton_url = ?,
                        imagen_path = ?, actualizacion = NOW()
                    WHERE id = ?
                    LIMIT 1';
            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return 0;
            }

            mysqli_stmt_bind_param(
                $st,
                'issiisssssi',
                $orden,
                $titulo,
                $reviewText,
                $rating,
                $mostrarEstrellas,
                $badgeText,
                $detallesJson,
                $botonTexto,
                $botonUrl,
                $imagenPath,
                $id
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return $ok ? $id : 0;
        }

        $sql = 'INSERT INTO web_carrusel_servicios_items
                (orden, titulo, review_text, rating, mostrar_estrellas, badge_text, detalles_json,
                 boton_texto, boton_url, imagen_path, actualizacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $st = mysqli_prepare($cn, $sql);
        if (!$st) {
            return 0;
        }

        mysqli_stmt_bind_param(
            $st,
            'issiisssss',
            $orden,
            $titulo,
            $reviewText,
            $rating,
            $mostrarEstrellas,
            $badgeText,
            $detallesJson,
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

if (!function_exists('cw_cs_delete_items_not_in')) {
    function cw_cs_delete_items_not_in(mysqli $cn, array $keepIds): array
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

                $sqlSelect = 'SELECT id, imagen_path FROM web_carrusel_servicios_items WHERE id NOT IN (' . $placeholders . ')';
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

                $sqlDelete = 'DELETE FROM web_carrusel_servicios_items WHERE id NOT IN (' . $placeholders . ')';
                $stDelete = mysqli_prepare($cn, $sqlDelete);
                if ($stDelete) {
                    mysqli_stmt_bind_param($stDelete, $types, ...$keep);
                    mysqli_stmt_execute($stDelete);
                    mysqli_stmt_close($stDelete);
                }

                return $deleted;
            }

            $rsAll = mysqli_query($cn, 'SELECT id, imagen_path FROM web_carrusel_servicios_items');
            if ($rsAll) {
                while ($row = mysqli_fetch_assoc($rsAll)) {
                    $deleted[] = [
                        'id' => (int)($row['id'] ?? 0),
                        'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                    ];
                }
                mysqli_free_result($rsAll);
            }

            mysqli_query($cn, 'DELETE FROM web_carrusel_servicios_items');
        } catch (Throwable $e) {
            return [];
        }

        return $deleted;
    }
}

if (!function_exists('cw_cs_fetch')) {
    function cw_cs_fetch(mysqli $cn): array
    {
        return [
            'config' => cw_cs_fetch_config($cn),
            'items' => cw_cs_fetch_items($cn),
        ];
    }
}
