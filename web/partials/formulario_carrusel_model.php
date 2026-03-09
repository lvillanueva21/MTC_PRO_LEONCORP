<?php
// web/partials/formulario_carrusel_model.php

if (!function_exists('cw_fc_defaults')) {
    function cw_fc_defaults(): array
    {
        return [
            'formulario' => [
                'titulo' => 'Solicita un servicio con asesoria gratuita',
                'descripcion' => 'Te contactaremos para informarte sobre requisitos, precios y horarios.',
                'texto_boton' => 'Quiero que me contacten',
            ],
            'carousel_items' => [
                [
                    'id' => 0,
                    'orden' => 1,
                    'titulo' => 'Capacitacion para conductores y empresas',
                    'texto' => 'Recibe asesoria personalizada para cursos, recategorizaciones y tramites de licencias.',
                    'imagen_path' => '',
                    'default_image' => 'web/img/carousel-2.jpg',
                ],
                [
                    'id' => 0,
                    'orden' => 2,
                    'titulo' => 'Programas corporativos con cobertura nacional',
                    'texto' => 'Creamos convenios para empresas con soluciones de capacitacion en seguridad y normativa.',
                    'imagen_path' => '',
                    'default_image' => 'web/img/carousel-1.jpg',
                ],
            ],
        ];
    }
}

if (!function_exists('cw_fc_service_options')) {
    function cw_fc_service_options(): array
    {
        return [
            ['code' => 'recategorizacion_aii', 'label' => 'Recategorizacion AII'],
            ['code' => 'recategorizacion_aiii', 'label' => 'Recategorizacion AIII'],
            ['code' => 'obtencion_moto_biic', 'label' => 'Obtencion MOTO BIIC'],
            ['code' => 'taller_cambiemos_actitud', 'label' => 'Taller Cambiemos de Actitud'],
            ['code' => 'curso_actualizacion_personas', 'label' => 'Curso de actualizacion normativa - Personas'],
            ['code' => 'curso_actualizacion_mercancias', 'label' => 'Curso de actualizacion normativa - Mercancias'],
            ['code' => 'examenes_medicos', 'label' => 'Examenes medicos'],
            ['code' => 'curso_matpel_a4', 'label' => 'Curso MATPEL / Licencia A4'],
            ['code' => 'manejo_defensivo', 'label' => 'Manejo defensivo'],
            ['code' => 'mecanica_basica', 'label' => 'Mecanica basica'],
            ['code' => 'primeros_auxilios', 'label' => 'Primeros auxilios en accidentes de transito'],
            ['code' => 'educacion_vial', 'label' => 'Educacion vial'],
            ['code' => 'cursos_virtuales_mtcpro', 'label' => 'Cursos virtuales MTC PRO'],
            ['code' => 'otro_orientacion', 'label' => 'Otro / Deseo orientacion'],
        ];
    }
}

if (!function_exists('cw_fc_service_map')) {
    function cw_fc_service_map(): array
    {
        $out = [];
        foreach (cw_fc_service_options() as $row) {
            $code = trim((string)($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $out[$code] = (string)($row['label'] ?? '');
        }
        return $out;
    }
}

if (!function_exists('cw_fc_city_options')) {
    function cw_fc_city_options(): array
    {
        $rows = [
            ['code' => 'la_merced_allain_prost', 'city' => 'La Merced', 'school' => 'Allain Prost'],
            ['code' => 'piura_allain_prost', 'city' => 'Piura', 'school' => 'Allain Prost'],
            ['code' => 'chocope_escuela_allain_prost', 'city' => 'Chocope', 'school' => 'Escuela Allain Prost'],
            ['code' => 'huancayo_escuela_allain_prost', 'city' => 'Huancayo', 'school' => 'Escuela Allain Prost'],
            ['code' => 'chiclayo_escuela_vias_seguras', 'city' => 'Chiclayo', 'school' => 'Escuela Vias Seguras'],
            ['code' => 'pasco_escuela_vias_seguras', 'city' => 'Pasco', 'school' => 'Escuela Vias Seguras'],
            ['code' => 'huaraz_escuela_guia_mis_rutas', 'city' => 'Huaraz', 'school' => 'Escuela Guia mis Rutas'],
            ['code' => 'trujillo_escuela_guia_mis_rutas', 'city' => 'Trujillo', 'school' => 'Escuela Guia mis Rutas'],
            ['code' => 'lima_escuela_guia_mis_rutas', 'city' => 'Lima', 'school' => 'Escuela Guia mis Rutas'],
            ['code' => 'huancayo_escuela_guia_mis_rutas', 'city' => 'Huancayo', 'school' => 'Escuela Guia mis Rutas'],
        ];

        foreach ($rows as &$row) {
            $row['label'] = (string)$row['city'] . ' - ' . (string)$row['school'];
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('cw_fc_city_map')) {
    function cw_fc_city_map(): array
    {
        $out = [];
        foreach (cw_fc_city_options() as $row) {
            $code = trim((string)($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $city = trim((string)($row['city'] ?? ''));
            $school = trim((string)($row['school'] ?? ''));
            $label = trim((string)($row['label'] ?? ''));
            if ($label === '') {
                $label = $city . ' - ' . $school;
            }

            $out[$code] = [
                'city' => $city,
                'school' => $school,
                'label' => $label,
            ];
        }
        return $out;
    }
}

if (!function_exists('cw_fc_schedule_options')) {
    function cw_fc_schedule_options(): array
    {
        return [
            ['code' => '8_10', 'label' => '8:00 am a 10:00 am'],
            ['code' => '10_12', 'label' => '10:00 am a 12:00 pm'],
            ['code' => '12_14', 'label' => '12:00 pm a 2:00 pm'],
            ['code' => '14_17', 'label' => '2:00 pm a 5:00 pm'],
            ['code' => '17_19', 'label' => '5:00 pm a 7:00 pm'],
            ['code' => 'any', 'label' => 'Cualquier horario'],
        ];
    }
}

if (!function_exists('cw_fc_schedule_map')) {
    function cw_fc_schedule_map(): array
    {
        $out = [];
        foreach (cw_fc_schedule_options() as $row) {
            $code = trim((string)($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $out[$code] = (string)($row['label'] ?? '');
        }
        return $out;
    }
}

if (!function_exists('cw_fc_status_options')) {
    function cw_fc_status_options(): array
    {
        return [
            'en_espera' => 'En espera',
            'contactado' => 'Contactado',
            'venta_cerrada' => 'Venta cerrada',
            'no_cerrada' => 'No cerrada',
            'no_contesto' => 'No contesto',
        ];
    }
}

if (!function_exists('cw_fc_status_badge_class')) {
    function cw_fc_status_badge_class(string $status): string
    {
        switch ($status) {
            case 'contactado':
                return 'badge-info';
            case 'venta_cerrada':
                return 'badge-success';
            case 'no_cerrada':
                return 'badge-warning';
            case 'no_contesto':
                return 'badge-secondary';
            case 'en_espera':
            default:
                return 'badge-primary';
        }
    }
}

if (!function_exists('cw_fc_limit_text')) {
    function cw_fc_limit_text(string $value, int $max): string
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

if (!function_exists('cw_fc_site_base_url')) {
    function cw_fc_site_base_url(): string
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

if (!function_exists('cw_fc_default_asset_url')) {
    function cw_fc_default_asset_url(string $relativePath): string
    {
        $relativePath = '/' . ltrim(trim($relativePath), '/');
        $siteBase = cw_fc_site_base_url();
        if ($siteBase === '') {
            return $relativePath;
        }

        return $siteBase . $relativePath;
    }
}

if (!function_exists('cw_fc_image_public_url')) {
    function cw_fc_image_public_url(string $imagePath): string
    {
        $imagePath = ltrim(trim($imagePath), '/\\');
        if ($imagePath === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        if (preg_match('#^web/#i', $imagePath)) {
            return cw_fc_default_asset_url($imagePath);
        }

        if (!defined('BASE_URL')) {
            return '/sistema/' . $imagePath;
        }

        return rtrim(BASE_URL, '/') . '/' . $imagePath;
    }
}

if (!function_exists('cw_fc_image_exists')) {
    function cw_fc_image_exists(string $imagePath): bool
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

if (!function_exists('cw_fc_default_slide_for_position')) {
    function cw_fc_default_slide_for_position(int $index): array
    {
        $defaults = cw_fc_defaults()['carousel_items'];
        if (isset($defaults[$index])) {
            return $defaults[$index];
        }

        $num = $index + 1;
        $fallbacks = ['web/img/carousel-2.jpg', 'web/img/carousel-1.jpg'];
        $fallbackImage = $fallbacks[$index % count($fallbacks)];

        return [
            'id' => 0,
            'orden' => $num,
            'titulo' => 'Asesoria comercial ' . $num,
            'texto' => 'Solicita información y un asesor te ayudara a elegir el servicio que necesitas.',
            'imagen_path' => '',
            'default_image' => $fallbackImage,
        ];
    }
}

if (!function_exists('cw_fc_resolve_slide_image_url')) {
    function cw_fc_resolve_slide_image_url(array $item, int $index): string
    {
        $imagePath = trim((string)($item['imagen_path'] ?? ''));
        $base = cw_fc_default_slide_for_position($index);
        $defaultRelative = trim((string)($base['default_image'] ?? 'web/img/carousel-1.jpg'));
        if ($defaultRelative === '') {
            $defaultRelative = 'web/img/carousel-1.jpg';
        }

        if ($imagePath !== '' && cw_fc_image_exists($imagePath)) {
            $customUrl = cw_fc_image_public_url($imagePath);
            if ($customUrl !== '') {
                return $customUrl;
            }
        }

        return cw_fc_default_asset_url($defaultRelative);
    }
}

if (!function_exists('cw_fc_normalize_carousel_items')) {
    function cw_fc_normalize_carousel_items($items): array
    {
        $source = is_array($items) ? array_values($items) : [];
        if (count($source) < 1) {
            $source = cw_fc_defaults()['carousel_items'];
        }

        if (count($source) > 5) {
            $source = array_slice($source, 0, 5);
        }

        $out = [];
        foreach ($source as $i => $raw) {
            $base = cw_fc_default_slide_for_position($i);
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

            $texto = trim((string)($raw['texto'] ?? ''));
            if ($texto === '') {
                $texto = (string)($base['texto'] ?? '');
            }

            $imagenPath = trim((string)($raw['imagen_path'] ?? ''));

            $out[] = [
                'id' => $id,
                'orden' => $orden,
                'titulo' => cw_fc_limit_text($titulo, 140),
                'texto' => cw_fc_limit_text($texto, 260),
                'imagen_path' => cw_fc_limit_text($imagenPath, 255),
                'default_image' => (string)($base['default_image'] ?? 'web/img/carousel-1.jpg'),
            ];
        }

        usort($out, static function (array $a, array $b): int {
            return ((int)$a['orden'] <=> (int)$b['orden']);
        });

        foreach ($out as $idx => &$row) {
            $row['orden'] = $idx + 1;
        }
        unset($row);

        return $out;
    }
}

if (!function_exists('cw_fc_fetch_carousel_items')) {
    function cw_fc_fetch_carousel_items(mysqli $cn): array
    {
        try {
            $sql = 'SELECT id, orden, titulo, texto, imagen_path
                    FROM web_formulario_carrusel_items
                    ORDER BY orden ASC, id ASC
                    LIMIT 5';
            $rs = mysqli_query($cn, $sql);
            if (!$rs) {
                return cw_fc_normalize_carousel_items([]);
            }

            $rows = [];
            while ($row = mysqli_fetch_assoc($rs)) {
                $rows[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'orden' => (int)($row['orden'] ?? 0),
                    'titulo' => trim((string)($row['titulo'] ?? '')),
                    'texto' => trim((string)($row['texto'] ?? '')),
                    'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                ];
            }
            mysqli_free_result($rs);

            return cw_fc_normalize_carousel_items($rows);
        } catch (Throwable $e) {
            return cw_fc_normalize_carousel_items([]);
        }
    }
}

if (!function_exists('cw_fc_fetch_carousel_rows_by_id')) {
    function cw_fc_fetch_carousel_rows_by_id(mysqli $cn): array
    {
        $out = [];

        try {
            $sql = 'SELECT id, orden, titulo, texto, imagen_path
                    FROM web_formulario_carrusel_items
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

                $out[$id] = [
                    'id' => $id,
                    'orden' => (int)($row['orden'] ?? 0),
                    'titulo' => trim((string)($row['titulo'] ?? '')),
                    'texto' => trim((string)($row['texto'] ?? '')),
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

if (!function_exists('cw_fc_upsert_carousel_item')) {
    function cw_fc_upsert_carousel_item(mysqli $cn, array $item): int
    {
        $id = (int)($item['id'] ?? 0);
        $orden = (int)($item['orden'] ?? 1);
        if ($orden < 1) {
            $orden = 1;
        }

        $titulo = cw_fc_limit_text(trim((string)($item['titulo'] ?? '')), 140);
        $texto = cw_fc_limit_text(trim((string)($item['texto'] ?? '')), 260);
        $imagenPath = cw_fc_limit_text(trim((string)($item['imagen_path'] ?? '')), 255);

        if ($id > 0) {
            $sql = 'UPDATE web_formulario_carrusel_items
                    SET orden = ?, titulo = ?, texto = ?, imagen_path = ?, actualizacion = NOW()
                    WHERE id = ?
                    LIMIT 1';
            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return 0;
            }

            mysqli_stmt_bind_param($st, 'isssi', $orden, $titulo, $texto, $imagenPath, $id);
            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            return $ok ? $id : 0;
        }

        $sql = 'INSERT INTO web_formulario_carrusel_items
                (orden, titulo, texto, imagen_path, actualizacion)
                VALUES (?, ?, ?, ?, NOW())';
        $st = mysqli_prepare($cn, $sql);
        if (!$st) {
            return 0;
        }

        mysqli_stmt_bind_param($st, 'isss', $orden, $titulo, $texto, $imagenPath);
        $ok = mysqli_stmt_execute($st);
        $newId = $ok ? (int)mysqli_insert_id($cn) : 0;
        mysqli_stmt_close($st);

        return $newId;
    }
}

if (!function_exists('cw_fc_delete_carousel_items_not_in')) {
    function cw_fc_delete_carousel_items_not_in(mysqli $cn, array $keepIds): array
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

                $sqlSelect = 'SELECT id, imagen_path FROM web_formulario_carrusel_items WHERE id NOT IN (' . $placeholders . ')';
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

                $sqlDelete = 'DELETE FROM web_formulario_carrusel_items WHERE id NOT IN (' . $placeholders . ')';
                $stDelete = mysqli_prepare($cn, $sqlDelete);
                if ($stDelete) {
                    mysqli_stmt_bind_param($stDelete, $types, ...$keep);
                    mysqli_stmt_execute($stDelete);
                    mysqli_stmt_close($stDelete);
                }

                return $deleted;
            }

            $rsAll = mysqli_query($cn, 'SELECT id, imagen_path FROM web_formulario_carrusel_items');
            if ($rsAll) {
                while ($row = mysqli_fetch_assoc($rsAll)) {
                    $deleted[] = [
                        'id' => (int)($row['id'] ?? 0),
                        'imagen_path' => trim((string)($row['imagen_path'] ?? '')),
                    ];
                }
                mysqli_free_result($rsAll);
            }

            mysqli_query($cn, 'DELETE FROM web_formulario_carrusel_items');
        } catch (Throwable $e) {
            return [];
        }

        return $deleted;
    }
}

if (!function_exists('cw_fc_insert_message')) {
    function cw_fc_insert_message(mysqli $cn, array $data): bool
    {
        try {
            $sql = 'INSERT INTO web_formulario_carrusel_mensajes
                    (tipo_solicitante, servicio_codigo, servicio_nombre, ciudad_codigo, ciudad_nombre, escuela_nombre,
                     documento, nombres_apellidos, razon_social, celular, correo, horario_codigo, horario_nombre, estado, fecha_registro, actualizacion)
                    VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "en_espera",
                     CONVERT_TZ(UTC_TIMESTAMP(), "+00:00", "-05:00"),
                     CONVERT_TZ(UTC_TIMESTAMP(), "+00:00", "-05:00"))';

            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param(
                $st,
                'sssssssssssss',
                $data['tipo_solicitante'],
                $data['servicio_codigo'],
                $data['servicio_nombre'],
                $data['ciudad_codigo'],
                $data['ciudad_nombre'],
                $data['escuela_nombre'],
                $data['documento'],
                $data['nombres_apellidos'],
                $data['razon_social'],
                $data['celular'],
                $data['correo'],
                $data['horario_codigo'],
                $data['horario_nombre']
            );

            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            return (bool)$ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_fc_messages_list')) {
    function cw_fc_messages_list(mysqli $cn, int $page, int $perPage = 10): array
    {
        if ($perPage < 1) {
            $perPage = 10;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        if ($page < 1) {
            $page = 1;
        }

        $total = 0;
        try {
            $rsCount = mysqli_query($cn, 'SELECT COUNT(*) AS total FROM web_formulario_carrusel_mensajes');
            if ($rsCount) {
                $rowCount = mysqli_fetch_assoc($rsCount);
                $total = (int)($rowCount['total'] ?? 0);
                mysqli_free_result($rsCount);
            }
        } catch (Throwable $e) {
            $total = 0;
        }

        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $rows = [];

        try {
            $sql = 'SELECT id, tipo_solicitante, servicio_nombre, ciudad_nombre, escuela_nombre,
                           documento, nombres_apellidos, razon_social, celular, correo,
                           horario_nombre, estado, fecha_registro
                    FROM web_formulario_carrusel_mensajes
                    ORDER BY fecha_registro DESC, id DESC
                    LIMIT ? OFFSET ?';
            $st = mysqli_prepare($cn, $sql);
            if ($st) {
                mysqli_stmt_bind_param($st, 'ii', $perPage, $offset);
                mysqli_stmt_execute($st);
                $rs = mysqli_stmt_get_result($st);
                if ($rs) {
                    while ($row = mysqli_fetch_assoc($rs)) {
                        $rows[] = [
                            'id' => (int)($row['id'] ?? 0),
                            'tipo_solicitante' => trim((string)($row['tipo_solicitante'] ?? '')),
                            'servicio_nombre' => trim((string)($row['servicio_nombre'] ?? '')),
                            'ciudad_nombre' => trim((string)($row['ciudad_nombre'] ?? '')),
                            'escuela_nombre' => trim((string)($row['escuela_nombre'] ?? '')),
                            'documento' => trim((string)($row['documento'] ?? '')),
                            'nombres_apellidos' => trim((string)($row['nombres_apellidos'] ?? '')),
                            'razon_social' => trim((string)($row['razon_social'] ?? '')),
                            'celular' => trim((string)($row['celular'] ?? '')),
                            'correo' => trim((string)($row['correo'] ?? '')),
                            'horario_nombre' => trim((string)($row['horario_nombre'] ?? '')),
                            'estado' => trim((string)($row['estado'] ?? 'en_espera')),
                            'fecha_registro' => trim((string)($row['fecha_registro'] ?? '')),
                        ];
                    }
                    mysqli_free_result($rs);
                }
                mysqli_stmt_close($st);
            }
        } catch (Throwable $e) {
            $rows = [];
        }

        return [
            'rows' => $rows,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'pages' => $pages,
        ];
    }
}

if (!function_exists('cw_fc_update_message_status')) {
    function cw_fc_update_message_status(mysqli $cn, int $id, string $status): bool
    {
        if ($id < 1) {
            return false;
        }

        $allowed = cw_fc_status_options();
        if (!isset($allowed[$status])) {
            return false;
        }

        try {
            $sql = 'UPDATE web_formulario_carrusel_mensajes
                    SET estado = ?, actualizacion = CONVERT_TZ(UTC_TIMESTAMP(), "+00:00", "-05:00")
                    WHERE id = ?
                    LIMIT 1';
            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param($st, 'si', $status, $id);
            $ok = mysqli_stmt_execute($st);
            $affected = (int)mysqli_stmt_affected_rows($st);
            mysqli_stmt_close($st);
            return (bool)$ok && $affected > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cw_fc_delete_message')) {
    function cw_fc_delete_message(mysqli $cn, int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        try {
            $sql = 'DELETE FROM web_formulario_carrusel_mensajes WHERE id = ? LIMIT 1';
            $st = mysqli_prepare($cn, $sql);
            if (!$st) {
                return false;
            }

            mysqli_stmt_bind_param($st, 'i', $id);
            $ok = mysqli_stmt_execute($st);
            $affected = (int)mysqli_stmt_affected_rows($st);
            mysqli_stmt_close($st);
            return (bool)$ok && $affected > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}
