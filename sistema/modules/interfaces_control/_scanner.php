<?php
// /modules/interfaces_control/_scanner.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

if (!function_exists('ic_slug_is_valid')) {
    function ic_slug_is_valid($slug)
    {
        return is_string($slug) && preg_match('/^[a-z0-9_]+$/', $slug);
    }
}

if (!function_exists('ic_interfaces_scan')) {
    function ic_interfaces_scan()
    {
        $root = __DIR__;
        $out = array();
        $entries = @scandir($root);
        if (!is_array($entries)) {
            return $out;
        }

        foreach ($entries as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            if ($name[0] === '.' || $name[0] === '_') {
                continue;
            }
            if (!ic_slug_is_valid($name)) {
                continue;
            }

            $dir = $root . DIRECTORY_SEPARATOR . $name;
            if (!is_dir($dir) || is_link($dir)) {
                continue;
            }

            $manifestFile = $dir . DIRECTORY_SEPARATOR . 'manifest.php';
            if (!is_file($manifestFile)) {
                continue;
            }

            $data = @require $manifestFile;
            if (!is_array($data)) {
                continue;
            }

            $slug = isset($data['slug']) ? trim((string)$data['slug']) : '';
            if ($slug !== $name || !ic_slug_is_valid($slug)) {
                continue;
            }

            $label = isset($data['label']) ? trim((string)$data['label']) : '';
            if ($label === '') {
                continue;
            }

            $path = isset($data['path']) ? trim((string)$data['path']) : '';
            if ($path === '') {
                $path = 'modules/interfaces_control/' . $slug . '/';
            }

            $out[] = array(
                'slug'   => $slug,
                'label'  => $label,
                'icon'   => isset($data['icon']) ? trim((string)$data['icon']) : 'far fa-circle',
                'path'   => $path,
                'orden'  => isset($data['orden']) ? (int)$data['orden'] : 9999,
                'activo' => isset($data['activo']) ? (int)$data['activo'] : 1,
            );
        }

        usort($out, function ($a, $b) {
            if ((int)$a['orden'] === (int)$b['orden']) {
                return strcasecmp((string)$a['label'], (string)$b['label']);
            }
            return ((int)$a['orden'] < (int)$b['orden']) ? -1 : 1;
        });

        return $out;
    }
}

