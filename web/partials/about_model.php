<?php
// web/partials/about_model.php

if (!function_exists('cw_about_defaults')) {
    function cw_about_defaults(): array
    {
        return [
            'titulo_base' => 'Cental',
            'titulo_resaltado' => 'About',
            'descripcion_principal' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
            'tarjetas' => [
                [
                    'icono_path' => '',
                    'titulo' => 'Our Vision',
                    'texto' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit.',
                ],
                [
                    'icono_path' => '',
                    'titulo' => 'Our Mision',
                    'texto' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit.',
                ],
            ],
            'descripcion_secundaria' => 'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Beatae, aliquam ipsum. Sed suscipit dolorem libero sequi aut natus debitis reprehenderit facilis quaerat similique, est at in eum. Quo, obcaecati in!',
            'experiencia_numero' => '17',
            'experiencia_texto' => 'Years Of Experience',
            'checklist' => [
                'Morbi tristique senectus',
                'A scelerisque purus',
                'Dictumst vestibulum',
                'dio aenean sed adipiscing',
            ],
            'boton_texto' => 'More About Us',
            'boton_url' => '#',
            'fundador_nombre' => 'William Burgess',
            'fundador_cargo' => 'Carveo Founder',
            'imagen_fundador_path' => '',
            'imagen_principal_path' => '',
            'imagen_secundaria_path' => '',
        ];
    }
}

if (!function_exists('cw_about_card_defaults')) {
    function cw_about_card_defaults(): array
    {
        $defaults = cw_about_defaults();
        return $defaults['tarjetas'];
    }
}

if (!function_exists('cw_about_checklist_defaults')) {
    function cw_about_checklist_defaults(): array
    {
        $defaults = cw_about_defaults();
        return $defaults['checklist'];
    }
}

if (!function_exists('cw_about_limit_text')) {
    function cw_about_limit_text(string $value, int $max): string
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

if (!function_exists('cw_about_normalize_cards')) {
    function cw_about_normalize_cards($cards): array
    {
        $defaults = cw_about_card_defaults();
        $out = [];

        for ($i = 0; $i < 2; $i++) {
            $raw = [];
            if (is_array($cards) && isset($cards[$i]) && is_array($cards[$i])) {
                $raw = $cards[$i];
            }

            $iconoPath = trim((string)($raw['icono_path'] ?? ''));
            $titulo = trim((string)($raw['titulo'] ?? ''));
            $texto = trim((string)($raw['texto'] ?? ''));

            if ($titulo === '') {
                $titulo = $defaults[$i]['titulo'];
            }
            if ($texto === '') {
                $texto = $defaults[$i]['texto'];
            }

            $out[] = [
                'icono_path' => cw_about_limit_text($iconoPath, 255),
                'titulo' => cw_about_limit_text($titulo, 70),
                'texto' => cw_about_limit_text($texto, 220),
            ];
        }

        return $out;
    }
}

if (!function_exists('cw_about_normalize_checklist')) {
    function cw_about_normalize_checklist($items): array
    {
        $defaults = cw_about_checklist_defaults();
        $out = [];

        for ($i = 0; $i < 4; $i++) {
            $raw = '';
            if (is_array($items) && isset($items[$i])) {
                $raw = (string)$items[$i];
            }

            $value = trim($raw);
            if ($value === '') {
                $value = $defaults[$i];
            }

            $out[] = cw_about_limit_text($value, 90);
        }

        return $out;
    }
}

if (!function_exists('cw_about_fetch')) {
    function cw_about_fetch(mysqli $cn): array
    {
        $defaults = cw_about_defaults();

        try {
            $sql = "SELECT titulo_base, titulo_resaltado, descripcion_principal, tarjetas_json,
                           descripcion_secundaria, experiencia_numero, experiencia_texto, checklist_json,
                           boton_texto, boton_url, fundador_nombre, fundador_cargo,
                           imagen_fundador_path, imagen_principal_path, imagen_secundaria_path
                    FROM web_nosotros
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

        $tarjetasRaw = json_decode((string)($row['tarjetas_json'] ?? ''), true);
        $checklistRaw = json_decode((string)($row['checklist_json'] ?? ''), true);

        $data = [
            'titulo_base' => trim((string)($row['titulo_base'] ?? '')),
            'titulo_resaltado' => trim((string)($row['titulo_resaltado'] ?? '')),
            'descripcion_principal' => trim((string)($row['descripcion_principal'] ?? '')),
            'tarjetas' => cw_about_normalize_cards($tarjetasRaw),
            'descripcion_secundaria' => trim((string)($row['descripcion_secundaria'] ?? '')),
            'experiencia_numero' => trim((string)($row['experiencia_numero'] ?? '')),
            'experiencia_texto' => trim((string)($row['experiencia_texto'] ?? '')),
            'checklist' => cw_about_normalize_checklist($checklistRaw),
            'boton_texto' => trim((string)($row['boton_texto'] ?? '')),
            'boton_url' => trim((string)($row['boton_url'] ?? '')),
            'fundador_nombre' => trim((string)($row['fundador_nombre'] ?? '')),
            'fundador_cargo' => trim((string)($row['fundador_cargo'] ?? '')),
            'imagen_fundador_path' => trim((string)($row['imagen_fundador_path'] ?? '')),
            'imagen_principal_path' => trim((string)($row['imagen_principal_path'] ?? '')),
            'imagen_secundaria_path' => trim((string)($row['imagen_secundaria_path'] ?? '')),
        ];

        if ($data['titulo_base'] === '') {
            $data['titulo_base'] = $defaults['titulo_base'];
        }
        if ($data['titulo_resaltado'] === '') {
            $data['titulo_resaltado'] = $defaults['titulo_resaltado'];
        }
        if ($data['descripcion_principal'] === '') {
            $data['descripcion_principal'] = $defaults['descripcion_principal'];
        }
        if ($data['descripcion_secundaria'] === '') {
            $data['descripcion_secundaria'] = $defaults['descripcion_secundaria'];
        }
        if ($data['experiencia_numero'] === '') {
            $data['experiencia_numero'] = $defaults['experiencia_numero'];
        }
        if ($data['experiencia_texto'] === '') {
            $data['experiencia_texto'] = $defaults['experiencia_texto'];
        }
        if ($data['boton_texto'] === '') {
            $data['boton_texto'] = $defaults['boton_texto'];
        }
        if ($data['boton_url'] === '') {
            $data['boton_url'] = $defaults['boton_url'];
        }
        if ($data['fundador_nombre'] === '') {
            $data['fundador_nombre'] = $defaults['fundador_nombre'];
        }
        if ($data['fundador_cargo'] === '') {
            $data['fundador_cargo'] = $defaults['fundador_cargo'];
        }

        return $data;
    }
}

if (!function_exists('cw_about_upsert')) {
    function cw_about_upsert(mysqli $cn, array $data): bool
    {
        $tarjetasJson = json_encode($data['tarjetas'], JSON_UNESCAPED_UNICODE);
        $checklistJson = json_encode($data['checklist'], JSON_UNESCAPED_UNICODE);
        if ($tarjetasJson === false || $checklistJson === false) {
            return false;
        }

        try {
            $sql = "INSERT INTO web_nosotros
                    (id, titulo_base, titulo_resaltado, descripcion_principal, tarjetas_json,
                     descripcion_secundaria, experiencia_numero, experiencia_texto, checklist_json,
                     boton_texto, boton_url, fundador_nombre, fundador_cargo,
                     imagen_fundador_path, imagen_principal_path, imagen_secundaria_path, actualizacion)
                    VALUES
                    (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                      titulo_base = VALUES(titulo_base),
                      titulo_resaltado = VALUES(titulo_resaltado),
                      descripcion_principal = VALUES(descripcion_principal),
                      tarjetas_json = VALUES(tarjetas_json),
                      descripcion_secundaria = VALUES(descripcion_secundaria),
                      experiencia_numero = VALUES(experiencia_numero),
                      experiencia_texto = VALUES(experiencia_texto),
                      checklist_json = VALUES(checklist_json),
                      boton_texto = VALUES(boton_texto),
                      boton_url = VALUES(boton_url),
                      fundador_nombre = VALUES(fundador_nombre),
                      fundador_cargo = VALUES(fundador_cargo),
                      imagen_fundador_path = VALUES(imagen_fundador_path),
                      imagen_principal_path = VALUES(imagen_principal_path),
                      imagen_secundaria_path = VALUES(imagen_secundaria_path),
                      actualizacion = NOW()";

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param(
                $st,
                'sssssssssssssss',
                $data['titulo_base'],
                $data['titulo_resaltado'],
                $data['descripcion_principal'],
                $tarjetasJson,
                $data['descripcion_secundaria'],
                $data['experiencia_numero'],
                $data['experiencia_texto'],
                $checklistJson,
                $data['boton_texto'],
                $data['boton_url'],
                $data['fundador_nombre'],
                $data['fundador_cargo'],
                $data['imagen_fundador_path'],
                $data['imagen_principal_path'],
                $data['imagen_secundaria_path']
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_about_site_base_url')) {
    function cw_about_site_base_url(): string
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

if (!function_exists('cw_about_default_asset_url')) {
    function cw_about_default_asset_url(string $relativePath): string
    {
        $relativePath = '/' . ltrim(trim($relativePath), '/');
        $siteBase = cw_about_site_base_url();
        if ($siteBase === '') {
            return $relativePath;
        }

        return $siteBase . $relativePath;
    }
}

if (!function_exists('cw_about_image_public_url')) {
    function cw_about_image_public_url(string $imagePath): string
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

if (!function_exists('cw_about_image_exists')) {
    function cw_about_image_exists(string $imagePath): bool
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

if (!function_exists('cw_about_resolve_image_url')) {
    function cw_about_resolve_image_url(string $imagePath, string $defaultRelativePath): string
    {
        $customUrl = cw_about_image_public_url($imagePath);
        if ($customUrl !== '' && cw_about_image_exists($imagePath)) {
            return $customUrl;
        }

        return cw_about_default_asset_url($defaultRelativePath);
    }
}

if (!function_exists('cw_about_link_valid')) {
    function cw_about_link_valid(string $url): bool
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
