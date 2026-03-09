<?php
// web/partials/process_model.php

if (!function_exists('cw_process_defaults')) {
    function cw_process_defaults(): array
    {
        return [
            'titulo_base' => 'Cental',
            'titulo_resaltado' => 'Process',
            'descripcion_general' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
            'items' => [
                [
                    'titulo' => 'Come In Contact',
                    'texto' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Ad, dolorem!',
                ],
                [
                    'titulo' => 'Choose A Car',
                    'texto' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Ad, dolorem!',
                ],
                [
                    'titulo' => 'Enjoy Driving',
                    'texto' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Ad, dolorem!',
                ],
            ],
        ];
    }
}

if (!function_exists('cw_process_item_default_for_position')) {
    function cw_process_item_default_for_position(int $index): array
    {
        $defaults = cw_process_defaults()['items'];
        if (isset($defaults[$index]) && is_array($defaults[$index])) {
            return $defaults[$index];
        }

        $num = $index + 1;
        return [
            'titulo' => 'Step ' . str_pad((string)$num, 2, '0', STR_PAD_LEFT),
            'texto' => 'Short description for this process step.',
        ];
    }
}

if (!function_exists('cw_process_limit_text')) {
    function cw_process_limit_text(string $value, int $max): string
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

if (!function_exists('cw_process_normalize_items')) {
    function cw_process_normalize_items($items): array
    {
        $minItems = 3;
        $maxItems = 9;
        $source = is_array($items) ? array_values($items) : [];
        $count = count($source);

        if ($count < $minItems) {
            $count = $minItems;
        }
        if ($count > $maxItems) {
            $count = $maxItems;
        }

        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $raw = [];
            if (isset($source[$i]) && is_array($source[$i])) {
                $raw = $source[$i];
            }

            $base = cw_process_item_default_for_position($i);
            $titulo = trim((string)($raw['titulo'] ?? ''));
            $texto = trim((string)($raw['texto'] ?? ''));

            if ($titulo === '') {
                $titulo = (string)($base['titulo'] ?? '');
            }
            if ($texto === '') {
                $texto = (string)($base['texto'] ?? '');
            }

            $out[] = [
                'titulo' => cw_process_limit_text($titulo, 40),
                'texto' => cw_process_limit_text($texto, 150),
            ];
        }

        return $out;
    }
}

if (!function_exists('cw_process_fetch')) {
    function cw_process_fetch(mysqli $cn): array
    {
        $defaults = cw_process_defaults();

        try {
            $sql = "SELECT titulo_base, titulo_resaltado, descripcion_general, items_json
                    FROM web_proceso
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
            'items' => cw_process_normalize_items($itemsRaw),
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

if (!function_exists('cw_process_upsert')) {
    function cw_process_upsert(mysqli $cn, array $data): bool
    {
        $itemsJson = json_encode($data['items'], JSON_UNESCAPED_UNICODE);
        if ($itemsJson === false) {
            return false;
        }

        try {
            $sql = "INSERT INTO web_proceso
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
