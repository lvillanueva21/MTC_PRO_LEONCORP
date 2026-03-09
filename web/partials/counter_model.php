<?php
// web/partials/counter_model.php

if (!function_exists('cw_counter_defaults')) {
    function cw_counter_defaults(): array
    {
        return [
            'items' => [
                [
                    'icono' => 'fas fa-thumbs-up fa-2x',
                    'numero' => '829',
                    'titulo' => 'Happy Clients',
                ],
                [
                    'icono' => 'fas fa-car-alt fa-2x',
                    'numero' => '56',
                    'titulo' => 'Number of Cars',
                ],
                [
                    'icono' => 'fas fa-building fa-2x',
                    'numero' => '127',
                    'titulo' => 'Car Center',
                ],
                [
                    'icono' => 'fas fa-clock fa-2x',
                    'numero' => '589',
                    'titulo' => 'Total kilometers',
                ],
            ],
        ];
    }
}

if (!function_exists('cw_counter_limit_text')) {
    function cw_counter_limit_text(string $value, int $max): string
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

if (!function_exists('cw_counter_normalize_items')) {
    function cw_counter_normalize_items($items): array
    {
        $defaults = cw_counter_defaults()['items'];
        $out = [];

        for ($i = 0; $i < 4; $i++) {
            $raw = [];
            if (is_array($items) && isset($items[$i]) && is_array($items[$i])) {
                $raw = $items[$i];
            }

            $numero = preg_replace('/\D+/', '', (string)($raw['numero'] ?? ''));
            $titulo = trim((string)($raw['titulo'] ?? ''));

            if ($numero === '') {
                $numero = $defaults[$i]['numero'];
            }
            if ($titulo === '') {
                $titulo = $defaults[$i]['titulo'];
            }

            $out[] = [
                'icono' => $defaults[$i]['icono'],
                'numero' => cw_counter_limit_text($numero, 8),
                'titulo' => cw_counter_limit_text($titulo, 80),
            ];
        }

        return $out;
    }
}

if (!function_exists('cw_counter_fetch')) {
    function cw_counter_fetch(mysqli $cn): array
    {
        $defaults = cw_counter_defaults();

        try {
            $sql = "SELECT items_json
                    FROM web_contadores
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

        return [
            'items' => cw_counter_normalize_items($itemsRaw),
        ];
    }
}

if (!function_exists('cw_counter_upsert')) {
    function cw_counter_upsert(mysqli $cn, array $data): bool
    {
        $itemsJson = json_encode($data['items'], JSON_UNESCAPED_UNICODE);
        if ($itemsJson === false) {
            return false;
        }

        try {
            $sql = "INSERT INTO web_contadores
                    (id, items_json, actualizacion)
                    VALUES
                    (1, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                      items_json = VALUES(items_json),
                      actualizacion = NOW()";

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param($st, 's', $itemsJson);

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}
