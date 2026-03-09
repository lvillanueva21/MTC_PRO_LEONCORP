<?php
// web/partials/formulario_carrusel_submit.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/formulario_carrusel_model.php';

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('cw_fc_submit_json_exit')) {
    function cw_fc_submit_json_exit(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cw_fc_submit_strlen')) {
    function cw_fc_submit_strlen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }
}

if (!function_exists('cw_fc_submit_only_digits')) {
    function cw_fc_submit_only_digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}

if (!function_exists('cw_fc_submit_compact_spaces')) {
    function cw_fc_submit_compact_spaces(string $value): string
    {
        return trim((string)preg_replace('/\s+/', ' ', trim($value)));
    }
}

if (!function_exists('cw_fc_submit_normalize_mobile')) {
    function cw_fc_submit_normalize_mobile(string $value): string
    {
        $digits = cw_fc_submit_only_digits($value);
        if (strpos($digits, '51') === 0 && strlen($digits) === 11) {
            $digits = substr($digits, 2);
        }
        return $digits;
    }
}

if (!function_exists('cw_fc_submit_is_valid_mobile')) {
    function cw_fc_submit_is_valid_mobile(string $value): bool
    {
        $normalized = cw_fc_submit_normalize_mobile($value);
        return (bool)preg_match('/^9\d{8}$/', $normalized);
    }
}

if (!function_exists('cw_fc_submit_is_valid_dni_ce')) {
    function cw_fc_submit_is_valid_dni_ce(string $value): bool
    {
        $clean = strtoupper(trim($value));
        return (bool)preg_match('/^(?:\d{8}|[A-Z0-9]{9,12})$/', $clean);
    }
}

if (!function_exists('cw_fc_submit_is_valid_ruc')) {
    function cw_fc_submit_is_valid_ruc(string $value): bool
    {
        $digits = cw_fc_submit_only_digits($value);
        return (bool)preg_match('/^(10|15|16|17|20)\d{9}$/', $digits);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cw_fc_submit_json_exit([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ]);
}

$tipoSolicitante = trim((string)($_POST['tipo_solicitante'] ?? ''));
$servicioCodigo = trim((string)($_POST['servicio_interes'] ?? ''));
$ciudadCodigo = trim((string)($_POST['ciudad_escuela'] ?? ''));
$horarioCodigo = trim((string)($_POST['horario_contacto'] ?? ''));

$documentoPersona = trim((string)($_POST['documento_persona'] ?? ''));
$nombresPersona = trim((string)($_POST['nombres_persona'] ?? ''));
$celularPersona = trim((string)($_POST['celular_persona'] ?? ''));
$correoPersona = trim((string)($_POST['correo_persona'] ?? ''));

$rucEmpresa = trim((string)($_POST['ruc_empresa'] ?? ''));
$razonSocialEmpresa = trim((string)($_POST['razon_social_empresa'] ?? ''));
$celularEmpresa = trim((string)($_POST['celular_empresa'] ?? ''));
$correoEmpresa = trim((string)($_POST['correo_empresa'] ?? ''));

$serviceMap = cw_fc_service_map();
$cityMap = cw_fc_city_map();
$scheduleMap = cw_fc_schedule_map();

$errors = [];

if ($tipoSolicitante !== 'persona' && $tipoSolicitante !== 'empresa') {
    $errors[] = 'Selecciona si eres Persona o Empresa.';
}

if (!isset($serviceMap[$servicioCodigo])) {
    $errors[] = 'Selecciona un servicio valido.';
}

if (!isset($cityMap[$ciudadCodigo])) {
    $errors[] = 'Selecciona una ciudad y escuela valida.';
}

if (!isset($scheduleMap[$horarioCodigo])) {
    $errors[] = 'Selecciona un horario valido.';
}

$documento = '';
$nombresApellidos = '';
$razonSocial = '';
$celular = '';
$correo = '';

if ($tipoSolicitante === 'persona') {
    $documento = strtoupper(trim($documentoPersona));
    $nombresApellidos = cw_fc_submit_compact_spaces($nombresPersona);
    $celular = $celularPersona;
    $correo = $correoPersona;

    if ($documento === '') {
        $errors[] = 'El DNI o CE es obligatorio.';
    } elseif (!cw_fc_submit_is_valid_dni_ce($documento)) {
        $errors[] = 'El DNI o CE es invalido. DNI: 8 digitos. CE: 9 a 12 caracteres alfanumericos.';
    }

    if ($nombresApellidos === '') {
        $errors[] = 'Los nombres y apellidos son obligatorios.';
    } elseif (cw_fc_submit_strlen($nombresApellidos) < 3) {
        $errors[] = 'Los nombres y apellidos deben tener al menos 3 caracteres.';
    } elseif (cw_fc_submit_strlen($nombresApellidos) > 140) {
        $errors[] = 'Los nombres y apellidos no pueden superar 140 caracteres.';
    }
} elseif ($tipoSolicitante === 'empresa') {
    $documento = cw_fc_submit_only_digits($rucEmpresa);
    $razonSocial = cw_fc_submit_compact_spaces($razonSocialEmpresa);
    $celular = $celularEmpresa;
    $correo = $correoEmpresa;

    if ($documento === '') {
        $errors[] = 'El RUC es obligatorio.';
    } elseif (!cw_fc_submit_is_valid_ruc($documento)) {
        $errors[] = 'El RUC es invalido. Debe tener 11 digitos y prefijo valido (10, 15, 16, 17 o 20).';
    }

    if ($razonSocial === '') {
        $errors[] = 'La razon social es obligatoria.';
    } elseif (cw_fc_submit_strlen($razonSocial) < 3) {
        $errors[] = 'La razon social debe tener al menos 3 caracteres.';
    } elseif (cw_fc_submit_strlen($razonSocial) > 160) {
        $errors[] = 'La razon social no puede superar 160 caracteres.';
    }
}

if ($celular === '') {
    $errors[] = 'El celular / WhatsApp es obligatorio.';
} elseif (!cw_fc_submit_is_valid_mobile($celular)) {
    $errors[] = 'El celular es invalido. Debe tener 9 digitos y comenzar con 9 (se permite +51).';
} else {
    $celular = cw_fc_submit_normalize_mobile($celular);
}

if ($correo !== '') {
    $correo = trim($correo);
    if (cw_fc_submit_strlen($correo) > 150) {
        $errors[] = 'El correo no puede superar 150 caracteres.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo no es valido.';
    }
}

if (cw_fc_submit_strlen($documento) > 20) {
    $errors[] = 'El documento no puede superar 20 caracteres.';
}
if (cw_fc_submit_strlen($celular) > 20) {
    $errors[] = 'El celular no puede superar 20 caracteres.';
}

if (!empty($errors)) {
    cw_fc_submit_json_exit([
        'ok' => false,
        'message' => 'No se pudo registrar tu solicitud.',
        'errors' => $errors,
    ]);
}

$servicioNombre = (string)$serviceMap[$servicioCodigo];
$cityInfo = $cityMap[$ciudadCodigo];
$horarioNombre = (string)$scheduleMap[$horarioCodigo];

$payload = [
    'tipo_solicitante' => $tipoSolicitante,
    'servicio_codigo' => cw_fc_limit_text($servicioCodigo, 80),
    'servicio_nombre' => cw_fc_limit_text($servicioNombre, 150),
    'ciudad_codigo' => cw_fc_limit_text($ciudadCodigo, 80),
    'ciudad_nombre' => cw_fc_limit_text((string)($cityInfo['city'] ?? ''), 80),
    'escuela_nombre' => cw_fc_limit_text((string)($cityInfo['school'] ?? ''), 120),
    'documento' => cw_fc_limit_text($documento, 20),
    'nombres_apellidos' => cw_fc_limit_text($nombresApellidos, 140),
    'razon_social' => cw_fc_limit_text($razonSocial, 160),
    'celular' => cw_fc_limit_text($celular, 20),
    'correo' => cw_fc_limit_text($correo, 150),
    'horario_codigo' => cw_fc_limit_text($horarioCodigo, 20),
    'horario_nombre' => cw_fc_limit_text($horarioNombre, 60),
];

try {
    $cn = db();
    if (!($cn instanceof mysqli)) {
        cw_fc_submit_json_exit([
            'ok' => false,
            'message' => 'No se pudo procesar la solicitud.',
        ]);
    }

    $ok = cw_fc_insert_message($cn, $payload);
    if (!$ok) {
        cw_fc_submit_json_exit([
            'ok' => false,
            'message' => 'No se pudo guardar tu solicitud. Intenta nuevamente.',
        ]);
    }
} catch (Throwable $e) {
    cw_fc_submit_json_exit([
        'ok' => false,
        'message' => 'Ocurrio un error inesperado al registrar la solicitud.',
    ]);
}

cw_fc_submit_json_exit([
    'ok' => true,
    'message' => 'Solicitud registrada. Un asesor se comunicara contigo pronto.',
]);
