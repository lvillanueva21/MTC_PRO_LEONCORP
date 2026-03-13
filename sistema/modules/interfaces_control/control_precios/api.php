<?php
// /modules/interfaces_control/control_precios/api.php
require_once __DIR__ . '/../../../includes/acl.php';
require_once __DIR__ . '/../../../includes/permisos.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../_control_acl.php';

acl_require_ids(array(1, 2));
verificarPermiso(array('Desarrollo', 'Control'));
ic_require_control_interface('control_precios');

// Reutiliza toda la logica estable de gestion de precios.
require __DIR__ . '/../../consola/precios/api.php';
