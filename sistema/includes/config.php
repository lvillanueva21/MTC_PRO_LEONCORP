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
        // Compatibilidad heredada (lectura opcional si no existe providers[]).
        'apisperu' => [
            'token'                   => getenv('MTC_APISPERU_TOKEN') ?: '',
            'base_url'                => 'https://dniruc.apisperu.com/api/v1',
            'timeout_seconds'         => 12,
            'connect_timeout_seconds' => 6
        ],

        // Configuracion multiproveedor (estructura activa recomendada).
        'providers' => [
            'apisperu' => [
                'enabled'                 => true,
                'base_url'                => 'https://dniruc.apisperu.com/api/v1',
                'timeout_seconds'         => 12,
                'connect_timeout_seconds' => 6,
                'monthly_limit'           => 0, // 0 = sin limite
                'tokens'                  => [
                    ['label' => 'apisperu_main', 'value' => getenv('MTC_APISPERU_TOKEN') ?: ''],
                    // ['label' => 'apisperu_backup', 'value' => ''],
                ],
            ],
            'decolecta' => [
                'enabled'                 => true,
                'base_url'                => 'https://api.decolecta.com/v1',
                'timeout_seconds'         => 12,
                'connect_timeout_seconds' => 6,
                'monthly_limit'           => 100,
                'tokens'                  => [
                    ['label' => 'decolecta_main', 'value' => getenv('MTC_DECOLECTA_TOKEN') ?: ''],
                    // ['label' => 'decolecta_backup', 'value' => ''],
                ],
            ],
            'jsonpe' => [
                'enabled'                 => true,
                'base_url'                => 'https://api.json.pe',
                'timeout_seconds'         => 12,
                'connect_timeout_seconds' => 6,
                'monthly_limit'           => 100,
                'tokens'                  => [
                    ['label' => 'jsonpe_main', 'value' => getenv('MTC_JSONPE_TOKEN') ?: ''],
                    // ['label' => 'jsonpe_backup', 'value' => ''],
                ],
            ],
        ],
        'fallback_order' => [
            'dni' => ['apisperu', 'decolecta', 'jsonpe'],
            'ruc' => ['apisperu', 'decolecta', 'jsonpe'],
        ],
    ]
];
