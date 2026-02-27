<?php
// modules/camaras/_bootstrap.php
// Base común para TODO el módulo Cámaras

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

// IDs de rol que pueden entrar a cualquier cosa del módulo cámaras
// 1 Desarrollo, 3 Recepción, 4 Administración, 6 Gerente
acl_require_ids(array(1, 3, 4, 6));

// Validación por nombre de rol activo
verificarPermiso(array('Desarrollo', 'Gerente', 'Administración', 'Recepción'));

// Conexión y usuario actual
$cn = db();
$u  = currentUser();

// Flags de rol
$rolActivo    = isset($u['rol_activo']) ? (string)$u['rol_activo'] : '';
$esDesarrollo = ($rolActivo === 'Desarrollo');
$esGerente    = ($rolActivo === 'Gerente');
$esAdmin      = ($rolActivo === 'Administración');
$esRecepcion  = ($rolActivo === 'Recepción');

// Empresa actual
$empresaActualId = isset($u['empresa']['id'])
    ? (int)$u['empresa']['id']
    : (int)($u['id_empresa'] ?? 0);
