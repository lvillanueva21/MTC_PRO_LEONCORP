<?php
/**
 * /includes/menu_matrix.php
 * Matriz de menú por ID de rol (mtp_roles.id). No toca BD.
 * roles: '*' (todos) o array de IDs [1,4,...]
 * when: callable opcional fn($u)=>bool para condiciones extra (ej. solo usuario id=1)
 *
 * IDs actuales:
 * 1 Desarrollo, 2 Control, 3 Recepción, 4 Administración, 5 COTI, 6 Gerente, 7 Cliente
 */

// Alias para evitar "magic numbers" (solo aquí dentro)
$R = [
    'DES' => 1, // Desarrollo
    'CON' => 2, // Control
    'REC' => 3, // Recepción
    'ADM' => 4, // Administración
    'COT' => 5, // COTI
    'GER' => 6, // Gerente
    'CLI' => 7, // Cliente
];

$RA = [$R['REC'], $R['ADM']];
$RAV = [$R['REC'], $R['ADM'], $R['CLI'], $R['DES'], $R['GER']];
$RAV_NO_ADM = [$R['REC'], $R['CLI'], $R['DES'], $R['GER']];

return [
    // ==========================
    // ÍTEMS COMUNES (para todos)
    // ==========================
    'common' => [
        ['path' => '/inicio.php', 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'roles' => '*'],
    ],

    // ======================================
    // ÍTEMS POR ROL / MULTIROL (segmentados)
    // ======================================
    'items' => [
        // ---------- Desarrollo (1) y Gerente (6) ----------
        // 👉 Cambio: Concedemos acceso a Consola a GERENTE también.
        ['path' => '/modules/consola/', 'icon' => 'fas fa-cog', 'label' => 'Consola', 'roles' => [$R['DES'], $R['GER']]],

        // Módulos de Desarrollo
        ['path' => '/modules/usuarios/', 'icon' => 'fas fa-user-cog', 'label' => 'Usuarios', 'roles' => [$R['DES']]],
        // ['path' => '/modules/certificados/', 'icon' => 'fas fa-file-signature', 'label' => 'Certificados', 'roles' => [$R['DES']]],
        // Inventario (PRUEBAS) — acceso: Desarrollo, Administración, Gerente
        ['path' => '/modules/inventario/', 'icon' => 'fas fa-boxes', 'label' => 'Inventario', 'roles' => [$R['DES'], $R['ADM'], $R['GER']]],

// Desarrollo (sin restricción por usuario)
['path' => '/modules/inventario_mtc/', 'icon' => 'fas fa-boxes', 'label' => 'Inventario MTC', 'roles' => [$R['DES'], $R['CON'], $R['GER']]],
['path' => '/modules/camaras/', 'icon' => 'fas fa-video', 'label' => 'Cámaras', 'roles' => [$R['DES']]],

        [
            'label'    => 'Web',
            'icon'     => 'fas fa-globe',
            'roles'    => [$R['DES']],
            'children' => [
                ['path' => '#', 'icon' => 'fas fa-heading', 'label' => 'Cabecera', 'roles' => [$R['DES']]],
                ['path' => '#', 'icon' => 'fas fa-bars', 'label' => 'Menú', 'roles' => [$R['DES']]],
            ],
        ],

        // ---------- Control (2) ----------
        ['path' => '/modules/reportes/', 'icon' => 'fas fa-clipboard-check', 'label' => 'Reportes', 'roles' => [$R['CON']]],
        // ['path'=>'/modules/certificados/','icon'=>'fas fa-file-signature','label'=>'Certificados','roles'=>[$R['CON']]],

        // ---------- Recepción (3) ----------
        ['path' => 'modules/caja/', 'icon' => 'fas fa-cash-register', 'label' => 'Caja', 'roles' => [$R['REC']]],

        // ---------- Administración (4) ----------
        ['path' => 'modules/caja/', 'icon' => 'fas fa-cash-register', 'label' => 'Caja', 'roles' => [$R['ADM']]],

        // ---------- COTI (5) ----------
        ['path' => '/modules/hardware/', 'icon' => 'fas fa-tools', 'label' => 'Hardware', 'roles' => [$R['COT']]],

        // ---------- Gerente (6) ----------
        ['path' => '/modules/reportes/', 'icon' => 'fas fa-chart-bar', 'label' => 'Reportes', 'roles' => [$R['GER']]],

        // ---------- Cliente (7) ----------
        //['path'=>'/modules/cursos/','icon'=>'fas fa-book-open','label'=>'Cursos','roles'=>[$R['CLI']]],

        // ---------- Multirol (Cliente, Desarrollo, Administración, Gerente) ----------
        ['path' => '/modules/examen/', 'icon' => 'fas fa-graduation-cap', 'label' => 'Examen', 'roles' => [$R['DES'], $R['GER']]],

        // ---------- Multirol (Recepción y Administración) ----------
        [
            'label'    => 'FINANZAS',
            'icon'     => 'fas fa-coins',
            'roles'    => $RA,
            'children' => [
                ['path' => 'modules/reporte_ventas/', 'icon' => 'fas fa-receipt', 'label' => 'Ventas'],
                ['path' => 'modules/reporte_abonos/', 'icon' => 'fas fa-hand-holding-usd', 'label' => 'Abonos'],
                ['path' => 'modules/egresos/', 'icon' => 'fas fa-wallet', 'label' => 'Egresos'],
                ['path' => 'modules/reporte_clientes/', 'icon' => 'fas fa-users', 'label' => 'Clientes'],
                ['path' => 'modules/alerta/', 'icon' => 'fas fa-bell', 'label' => 'Alertas'],
            ],
        ],
        [
            'label'    => 'MTC',
            'icon'     => 'fas fa-landmark',
            'roles'    => $RA,
            'children' => [
                ['path' => 'modules/conductores/', 'icon' => 'fas fa-id-card', 'label' => 'Conductores'],
                ['path' => 'modules/usuarios_mtc/', 'icon' => 'fas fa-user-cog', 'label' => 'Usuarios MTC'],
                ['path' => 'modules/inventario_mtc/', 'icon' => 'fas fa-boxes', 'label' => 'Inventario MTC'],
            ],
        ],
        [
            'label'    => 'AULA VIRTUAL',
            'icon'     => 'fas fa-graduation-cap',
            'roles'    => $RAV,
            'children' => [
                // Solo Administracion: nuevo orden y separacion de submenus
                ['path' => 'modules/aula_virtual/aula_virtual_administracion_matriculas.php', 'icon' => 'fas fa-user-check', 'label' => 'Matriculas', 'roles' => [$R['ADM']]],
                ['path' => 'modules/aula_virtual/aula_virtual_administracion_cursos.php', 'icon' => 'fas fa-book-open', 'label' => 'Cursos', 'roles' => [$R['ADM']]],
                ['path' => 'modules/aula_virtual/aula_virtual_administracion_formularios.php', 'icon' => 'fas fa-clipboard-list', 'label' => 'Formularios', 'roles' => [$R['ADM']]],
                ['path' => 'modules/certificados/', 'icon' => 'fas fa-file-signature', 'label' => 'Certificados', 'roles' => [$R['ADM']]],

                // Resto de roles: mantener estructura actual
                ['path' => 'modules/certificados/', 'icon' => 'fas fa-file-signature', 'label' => 'Certificados', 'roles' => $RAV_NO_ADM],
                ['path' => 'modules/aula_virtual/', 'icon' => 'fas fa-book-open', 'label' => 'Cursos', 'roles' => $RAV_NO_ADM],
            ],
        ],

        // ==== PLANTILLAS (copia/pega y descomenta) ====
        // ['path'=>'/modules/finanzas/','icon'=>'fas fa-coins','label'=>'Finanzas','roles'=>[$R['ADM']]], // Administración
        // ['path'=>'/modules/tickets/','icon'=>'fas fa-headset','label'=>'Tickets','roles'=>[$R['COT']]], // COTI
        // ['path'=>'/modules/auditorias/','icon'=>'fas fa-search-plus','label'=>'Auditorías','roles'=>[$R['CON']]], // Control
        // ['path'=>'/modules/mis_datos/','icon'=>'fas fa-id-card','label'=>'Mis Datos','roles'=>[$R['CLI']]], // Cliente
        // ['path'=>'/modules/mi_modulo/','icon'=>'fas fa-cubes','label'=>'Mi Módulo','roles'=>[$R['DES'],$R['COT']]], // Múltiples
        // ['path'=>'/modules/privado/','icon'=>'fas fa-user-shield','label'=>'Área Privada','roles'=>[$R['DES']], 'when'=>fn($u)=>($u['id']??0)===1],
    ],
];
