<?php
return [
    'db' => [
        'host'      => 'localhost',
        'name'      => 'lsistemas_erp_2026',
        'user'      => 'lsistemas_luigi2026',
        'pass'      => '20@26LSistemas#&&',
        'port'      => 3306,
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_spanish_ci'
    ],
    'app' => [
        'env'      => 'prod',
        'base_url' => 'https://leoncorp.pe/sistema/',
        'timezone' => 'America/Lima'
    ],
    'api_hub' => [
        'apisperu' => [
            // Se recomienda cargar el token desde variable de entorno del servidor.
            // Si prefieres fijarlo aquí, reemplaza '' por tu token.
            'token'                   => getenv('MTC_APISPERU_TOKEN') ?: '',
            'base_url'                => 'https://dniruc.apisperu.com/api/v1',
            'timeout_seconds'         => 12,
            'connect_timeout_seconds' => 6
        ]
    ]
];
