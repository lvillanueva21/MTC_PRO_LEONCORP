<?php
// web/partials/features_model.php

if (!function_exists('cw_features_defaults')) {
    function cw_features_defaults(): array
    {
        return [
            'titulo_rojo' => 'Central',
            'titulo_azul' => 'Features',
            'descripcion_general' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
            'imagen_path' => '',
            'items' => [
                [
                    'icono' => 'fa fa-trophy fa-2x',
                    'titulo' => 'First Class services',
                    'texto' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur, in illum aperiam ullam magni eligendi?',
                ],
                [
                    'icono' => 'fa fa-road fa-2x',
                    'titulo' => '24/7 road assistance',
                    'texto' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur, in illum aperiam ullam magni eligendi?',
                ],
                [
                    'icono' => 'fa fa-tag fa-2x',
                    'titulo' => 'Quality at Minimum',
                    'texto' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur, in illum aperiam ullam magni eligendi?',
                ],
                [
                    'icono' => 'fa fa-map-pin fa-2x',
                    'titulo' => 'Free Pick-Up & Drop-Off',
                    'texto' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur, in illum aperiam ullam magni eligendi?',
                ],
            ],
        ];
    }
}

if (!function_exists('cw_features_item_defaults')) {
    function cw_features_item_defaults(): array
    {
        $defaults = cw_features_defaults();
        return $defaults['items'];
    }
}

if (!function_exists('cw_features_limit_text')) {
    function cw_features_limit_text(string $value, int $max): string
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

if (!function_exists('cw_features_sanitize_icon_class')) {
    function cw_features_sanitize_icon_class(string $iconClass): string
    {
        $iconClass = trim($iconClass);
        if ($iconClass === '') {
            return '';
        }

        $iconClass = preg_replace('/\s+/', ' ', $iconClass);
        if (!preg_match('/^[a-zA-Z0-9 _:\-]+$/', $iconClass)) {
            return '';
        }

        return cw_features_limit_text($iconClass, 120);
    }
}

if (!function_exists('cw_features_normalize_items')) {
    function cw_features_normalize_items($items): array
    {
        $defaults = cw_features_item_defaults();
        $out = [];

        for ($i = 0; $i < 4; $i++) {
            $raw = [];
            if (is_array($items) && isset($items[$i]) && is_array($items[$i])) {
                $raw = $items[$i];
            }

            $icono = cw_features_sanitize_icon_class((string)($raw['icono'] ?? ''));
            $titulo = trim((string)($raw['titulo'] ?? ''));
            $texto = trim((string)($raw['texto'] ?? ''));

            if ($icono === '') {
                $icono = $defaults[$i]['icono'];
            }
            if ($titulo === '') {
                $titulo = $defaults[$i]['titulo'];
            }
            if ($texto === '') {
                $texto = $defaults[$i]['texto'];
            }

            $out[] = [
                'icono' => cw_features_limit_text($icono, 120),
                'titulo' => cw_features_limit_text($titulo, 70),
                'texto' => cw_features_limit_text($texto, 220),
            ];
        }

        return $out;
    }
}

if (!function_exists('cw_features_fetch')) {
    function cw_features_fetch(mysqli $cn): array
    {
        $defaults = cw_features_defaults();

        try {
            $sql = "SELECT titulo_rojo, titulo_azul, descripcion_general, imagen_path, items_json
                    FROM web_caracteristicas
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

        $itemsRaw = json_decode((string)($row['items_json'] ?? ''), true);
        $items = cw_features_normalize_items($itemsRaw);

        $data = [
            'titulo_rojo' => trim((string)($row['titulo_rojo'] ?? '')),
            'titulo_azul' => trim((string)($row['titulo_azul'] ?? '')),
            'descripcion_general' => trim((string)($row['descripcion_general'] ?? '')),
            'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
            'items' => $items,
        ];

        if ($data['titulo_rojo'] === '') {
            $data['titulo_rojo'] = $defaults['titulo_rojo'];
        }
        if ($data['titulo_azul'] === '') {
            $data['titulo_azul'] = $defaults['titulo_azul'];
        }
        if ($data['descripcion_general'] === '') {
            $data['descripcion_general'] = $defaults['descripcion_general'];
        }

        return $data;
    }
}

if (!function_exists('cw_features_upsert')) {
    function cw_features_upsert(mysqli $cn, array $data): bool
    {
        $itemsJson = json_encode($data['items'], JSON_UNESCAPED_UNICODE);
        if ($itemsJson === false) {
            return false;
        }

        try {
            $sql = "INSERT INTO web_caracteristicas
                    (id, titulo_rojo, titulo_azul, descripcion_general, imagen_path, items_json, actualizacion)
                    VALUES
                    (1, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        titulo_rojo = VALUES(titulo_rojo),
                        titulo_azul = VALUES(titulo_azul),
                        descripcion_general = VALUES(descripcion_general),
                        imagen_path = VALUES(imagen_path),
                        items_json = VALUES(items_json),
                        actualizacion = NOW()";

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param(
                $st,
                'sssss',
                $data['titulo_rojo'],
                $data['titulo_azul'],
                $data['descripcion_general'],
                $data['imagen_path'],
                $itemsJson
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_features_site_base_url')) {
    function cw_features_site_base_url(): string
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

if (!function_exists('cw_features_default_image_url')) {
    function cw_features_default_image_url(): string
    {
        $siteBase = cw_features_site_base_url();
        if ($siteBase === '') {
            return '/web/img/features-img.png';
        }

        return $siteBase . '/web/img/features-img.png';
    }
}

if (!function_exists('cw_features_image_public_url')) {
    function cw_features_image_public_url(string $imagePath): string
    {
        $imagePath = ltrim(trim($imagePath), '/\\');
        if ($imagePath === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        if (!defined('BASE_URL')) {
            return '/sistema/' . $imagePath;
        }

        return rtrim(BASE_URL, '/') . '/' . $imagePath;
    }
}

if (!function_exists('cw_features_image_exists')) {
    function cw_features_image_exists(string $imagePath): bool
    {
        $imagePath = ltrim(trim($imagePath), '/\\');
        if ($imagePath === '') {
            return false;
        }
        if (preg_match('#^https?://#i', $imagePath)) {
            return true;
        }

        $abs = realpath(__DIR__ . '/../../sistema/' . $imagePath);
        if (!$abs || !is_file($abs)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('cw_features_resolve_image_url')) {
    function cw_features_resolve_image_url(string $imagePath): string
    {
        $customUrl = cw_features_image_public_url($imagePath);
        if ($customUrl !== '' && cw_features_image_exists($imagePath)) {
            return $customUrl;
        }

        return cw_features_default_image_url();
    }
}
