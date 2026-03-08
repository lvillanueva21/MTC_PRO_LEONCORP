<?php
// web/partials/topbar_model.php

if (!function_exists('cw_topbar_defaults')) {
    function cw_topbar_defaults(): array
    {
        return [
            'direccion' => 'Find A Location',
            'telefono' => '912345678',
            'correo' => 'example@gmail.com',
            'whatsapp_url' => 'https://wa.me/51912345678',
            'facebook_url' => 'https://facebook.com/',
            'instagram_url' => 'https://instagram.com/',
            'youtube_url' => 'https://youtube.com/',
        ];
    }
}

if (!function_exists('cw_topbar_fetch')) {
    function cw_topbar_fetch(mysqli $cn): array
    {
        $defaults = cw_topbar_defaults();

        try {
            $sql = "
                SELECT direccion, telefono, correo, whatsapp_url, facebook_url, instagram_url, youtube_url
                FROM web_topbar_config
                WHERE id = 1
                LIMIT 1
            ";
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

        foreach ($row as $k => $v) {
            if (!is_string($v)) {
                continue;
            }
            $row[$k] = trim($v);
        }

        return array_merge($defaults, $row);
    }
}

if (!function_exists('cw_topbar_upsert')) {
    function cw_topbar_upsert(mysqli $cn, array $data): bool
    {
        try {
            $sql = "
                INSERT INTO web_topbar_config
                    (id, direccion, telefono, correo, whatsapp_url, facebook_url, instagram_url, youtube_url, actualizacion)
                VALUES
                    (1, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    direccion = VALUES(direccion),
                    telefono = VALUES(telefono),
                    correo = VALUES(correo),
                    whatsapp_url = VALUES(whatsapp_url),
                    facebook_url = VALUES(facebook_url),
                    instagram_url = VALUES(instagram_url),
                    youtube_url = VALUES(youtube_url),
                    actualizacion = NOW()
            ";

            $stmt = mysqli_prepare($cn, $sql);
            if (!$stmt) {
                return false;
            }

            mysqli_stmt_bind_param(
                $stmt,
                'sssssss',
                $data['direccion'],
                $data['telefono'],
                $data['correo'],
                $data['whatsapp_url'],
                $data['facebook_url'],
                $data['instagram_url'],
                $data['youtube_url']
            );

            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_topbar_normalize_url')) {
    function cw_topbar_normalize_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return $url;
    }
}

if (!function_exists('cw_topbar_is_valid_social_url')) {
    function cw_topbar_is_valid_social_url(string $url, string $network): bool
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        $allowedHosts = [
            'whatsapp' => ['wa.me', 'whatsapp.com', 'api.whatsapp.com'],
            'facebook' => ['facebook.com', 'fb.com'],
            'instagram' => ['instagram.com'],
            'youtube' => ['youtube.com', 'youtu.be'],
        ];

        if (!isset($allowedHosts[$network])) {
            return false;
        }

        return cw_topbar_host_allowed($host, $allowedHosts[$network]);
    }
}

if (!function_exists('cw_topbar_host_allowed')) {
    function cw_topbar_host_allowed(string $host, array $allowedRoots): bool
    {
        foreach ($allowedRoots as $root) {
            if ($host === $root) {
                return true;
            }

            $suffix = '.' . $root;
            $suffixLen = strlen($suffix);
            if ($suffixLen > 0 && strlen($host) > $suffixLen && substr($host, -$suffixLen) === $suffix) {
                return true;
            }
        }
        return false;
    }
}
