<?php
// web/partials/banner_model.php

if (!function_exists('cw_banner_defaults')) {
    function cw_banner_defaults(): array
    {
        return [
            'titulo_superior' => 'Rent Your Car',
            'titulo_principal' => 'Interested in Renting?',
            'descripcion' => "Don't hesitate and send us a message.",
            'boton_1_texto' => 'WhatchApp',
            'boton_1_url' => '#',
            'boton_2_texto' => 'Contact Us',
            'boton_2_url' => '#',
            'imagen_path' => '',
        ];
    }
}

if (!function_exists('cw_banner_limit_text')) {
    function cw_banner_limit_text(string $value, int $max): string
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

if (!function_exists('cw_banner_link_valid')) {
    function cw_banner_link_valid(string $url): bool
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

if (!function_exists('cw_banner_fetch')) {
    function cw_banner_fetch(mysqli $cn): array
    {
        $defaults = cw_banner_defaults();

        try {
            $sql = "SELECT titulo_superior, titulo_principal, descripcion,
                           boton_1_texto, boton_1_url, boton_2_texto, boton_2_url,
                           imagen_path
                    FROM web_banner
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

        $data = [
            'titulo_superior' => trim((string)($row['titulo_superior'] ?? '')),
            'titulo_principal' => trim((string)($row['titulo_principal'] ?? '')),
            'descripcion' => trim((string)($row['descripcion'] ?? '')),
            'boton_1_texto' => trim((string)($row['boton_1_texto'] ?? '')),
            'boton_1_url' => trim((string)($row['boton_1_url'] ?? '')),
            'boton_2_texto' => trim((string)($row['boton_2_texto'] ?? '')),
            'boton_2_url' => trim((string)($row['boton_2_url'] ?? '')),
            'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
        ];

        if ($data['titulo_superior'] === '') {
            $data['titulo_superior'] = $defaults['titulo_superior'];
        }
        if ($data['titulo_principal'] === '') {
            $data['titulo_principal'] = $defaults['titulo_principal'];
        }
        if ($data['descripcion'] === '') {
            $data['descripcion'] = $defaults['descripcion'];
        }
        if ($data['boton_1_texto'] === '') {
            $data['boton_1_texto'] = $defaults['boton_1_texto'];
        }
        if ($data['boton_1_url'] === '' || !cw_banner_link_valid($data['boton_1_url'])) {
            $data['boton_1_url'] = $defaults['boton_1_url'];
        }
        if ($data['boton_2_texto'] === '') {
            $data['boton_2_texto'] = $defaults['boton_2_texto'];
        }
        if ($data['boton_2_url'] === '' || !cw_banner_link_valid($data['boton_2_url'])) {
            $data['boton_2_url'] = $defaults['boton_2_url'];
        }

        return $data;
    }
}

if (!function_exists('cw_banner_upsert')) {
    function cw_banner_upsert(mysqli $cn, array $data): bool
    {
        try {
            $sql = "INSERT INTO web_banner
                    (id, titulo_superior, titulo_principal, descripcion, boton_1_texto, boton_1_url, boton_2_texto, boton_2_url, imagen_path, actualizacion)
                    VALUES
                    (1, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        titulo_superior = VALUES(titulo_superior),
                        titulo_principal = VALUES(titulo_principal),
                        descripcion = VALUES(descripcion),
                        boton_1_texto = VALUES(boton_1_texto),
                        boton_1_url = VALUES(boton_1_url),
                        boton_2_texto = VALUES(boton_2_texto),
                        boton_2_url = VALUES(boton_2_url),
                        imagen_path = VALUES(imagen_path),
                        actualizacion = NOW()";

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param(
                $st,
                'ssssssss',
                $data['titulo_superior'],
                $data['titulo_principal'],
                $data['descripcion'],
                $data['boton_1_texto'],
                $data['boton_1_url'],
                $data['boton_2_texto'],
                $data['boton_2_url'],
                $data['imagen_path']
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_banner_site_base_url')) {
    function cw_banner_site_base_url(): string
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

if (!function_exists('cw_banner_default_image_url')) {
    function cw_banner_default_image_url(): string
    {
        $siteBase = cw_banner_site_base_url();
        if ($siteBase === '') {
            return '/web/img/banner-1.jpg';
        }

        return $siteBase . '/web/img/banner-1.jpg';
    }
}

if (!function_exists('cw_banner_image_public_url')) {
    function cw_banner_image_public_url(string $imagePath): string
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

if (!function_exists('cw_banner_image_exists')) {
    function cw_banner_image_exists(string $imagePath): bool
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

if (!function_exists('cw_banner_resolve_image_url')) {
    function cw_banner_resolve_image_url(string $imagePath): string
    {
        $customUrl = cw_banner_image_public_url($imagePath);
        if ($customUrl !== '' && cw_banner_image_exists($imagePath)) {
            return $customUrl;
        }

        return cw_banner_default_image_url();
    }
}
