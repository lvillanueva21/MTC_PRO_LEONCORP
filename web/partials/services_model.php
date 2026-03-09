<?php
// web/partials/services_model.php

if (!function_exists('cw_services_defaults')) {
    function cw_services_defaults(): array
    {
        $defaultText = 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Reprehenderit ipsam quasi quibusdam ipsa perferendis iusto?';

        return [
            'titulo_base' => 'Cental',
            'titulo_resaltado' => 'Services',
            'descripcion_general' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
            'items' => [
                [
                    'icono' => 'fa fa-phone-alt fa-2x',
                    'titulo' => 'Phone Reservation',
                    'texto' => $defaultText,
                ],
                [
                    'icono' => 'fa fa-money-bill-alt fa-2x',
                    'titulo' => 'Special Rates',
                    'texto' => $defaultText,
                ],
                [
                    'icono' => 'fa fa-road fa-2x',
                    'titulo' => 'One Way Rental',
                    'texto' => $defaultText,
                ],
                [
                    'icono' => 'fa fa-umbrella fa-2x',
                    'titulo' => 'Life Insurance',
                    'texto' => $defaultText,
                ],
                [
                    'icono' => 'fa fa-building fa-2x',
                    'titulo' => 'City to City',
                    'texto' => $defaultText,
                ],
                [
                    'icono' => 'fa fa-car-alt fa-2x',
                    'titulo' => 'Free Rides',
                    'texto' => $defaultText,
                ],
            ],
        ];
    }
}

if (!function_exists('cw_services_item_defaults')) {
    function cw_services_item_defaults(): array
    {
        $defaults = cw_services_defaults();
        return $defaults['items'];
    }
}

if (!function_exists('cw_services_limit_text')) {
    function cw_services_limit_text(string $value, int $max): string
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

if (!function_exists('cw_services_sanitize_icon_class')) {
    function cw_services_sanitize_icon_class(string $iconClass): string
    {
        $iconClass = trim($iconClass);
        if ($iconClass === '') {
            return '';
        }

        $iconClass = preg_replace('/\s+/', ' ', $iconClass);
        if (!preg_match('/^[a-zA-Z0-9 _:\-]+$/', $iconClass)) {
            return '';
        }

        return cw_services_limit_text($iconClass, 120);
    }
}

if (!function_exists('cw_services_normalize_items')) {
    function cw_services_normalize_items($items): array
    {
        $defaults = cw_services_item_defaults();
        $out = [];

        for ($i = 0; $i < 6; $i++) {
            $raw = [];
            if (is_array($items) && isset($items[$i]) && is_array($items[$i])) {
                $raw = $items[$i];
            }

            $icono = cw_services_sanitize_icon_class((string)($raw['icono'] ?? ''));
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
                'icono' => cw_services_limit_text($icono, 120),
                'titulo' => cw_services_limit_text($titulo, 55),
                'texto' => cw_services_limit_text($texto, 170),
            ];
        }

        return $out;
    }
}

if (!function_exists('cw_services_fetch')) {
    function cw_services_fetch(mysqli $cn): array
    {
        $defaults = cw_services_defaults();

        try {
            $sql = "SELECT titulo_base, titulo_resaltado, descripcion_general, items_json
                    FROM web_servicios
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

        $data = [
            'titulo_base' => trim((string)($row['titulo_base'] ?? '')),
            'titulo_resaltado' => trim((string)($row['titulo_resaltado'] ?? '')),
            'descripcion_general' => trim((string)($row['descripcion_general'] ?? '')),
            'items' => cw_services_normalize_items($itemsRaw),
        ];

        if ($data['titulo_base'] === '') {
            $data['titulo_base'] = $defaults['titulo_base'];
        }
        if ($data['titulo_resaltado'] === '') {
            $data['titulo_resaltado'] = $defaults['titulo_resaltado'];
        }
        if ($data['descripcion_general'] === '') {
            $data['descripcion_general'] = $defaults['descripcion_general'];
        }

        return $data;
    }
}

if (!function_exists('cw_services_upsert')) {
    function cw_services_upsert(mysqli $cn, array $data): bool
    {
        $itemsJson = json_encode($data['items'], JSON_UNESCAPED_UNICODE);
        if ($itemsJson === false) {
            return false;
        }

        try {
            $sql = "INSERT INTO web_servicios
                    (id, titulo_base, titulo_resaltado, descripcion_general, items_json, actualizacion)
                    VALUES
                    (1, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        titulo_base = VALUES(titulo_base),
                        titulo_resaltado = VALUES(titulo_resaltado),
                        descripcion_general = VALUES(descripcion_general),
                        items_json = VALUES(items_json),
                        actualizacion = NOW()";

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param(
                $st,
                'ssss',
                $data['titulo_base'],
                $data['titulo_resaltado'],
                $data['descripcion_general'],
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
