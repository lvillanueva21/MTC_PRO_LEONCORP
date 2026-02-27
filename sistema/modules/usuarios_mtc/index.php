<?php
// modules/usuarios_mtc/index.php

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

// Solo: Desarrollo(1), Control(2), Administración(4)
acl_require_ids([1, 2, 4]);
verificarPermiso(['Desarrollo', 'Control', 'Administración']);

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$u = currentUser();

// Empresa / usuario (por consistencia con el resto del sistema)
$empresaId  = (int)($u['empresa']['id'] ?? ($u['id_empresa'] ?? 0));
$empresaNom = (string)($u['empresa']['nombre'] ?? '—');
$usrNom     = trim(($u['nombres'] ?? '').' '.($u['apellidos'] ?? '')) ?: ($u['usuario'] ?? 'Usuario');

// Si tu sistema exige empresa en sesión, mantenemos el estándar
if ($empresaId <= 0) {
  http_response_code(403);
  exit('Empresa no asignada en sesión.');
}

/**
 * Detección de rol en sesión (robusta, sin asumir un formato único).
 * - Intenta leer roles por ID y/o por nombre desde lo que entregue currentUser().
 * - Define "superior" para Desarrollo y Control; "inferior" para Administración.
 */
$ROLE_IDS = [
  'DES' => 1,
  'CON' => 2,
  'ADM' => 4,
];

// 1) Roles por ID (si existen en sesión)
$rolesIds = [];

// posibles formatos: $u['roles_ids'] = [1,4], $u['roles'] = [ ... ], $u['roles_id'] etc
if (!empty($u['roles_ids']) && is_array($u['roles_ids'])) {
  $rolesIds = array_map('intval', $u['roles_ids']);
} elseif (!empty($u['roles']) && is_array($u['roles'])) {
  // soporta: ['Desarrollo','Administración'] o [['id'=>1,'nombre'=>'Desarrollo'], ...]
  foreach ($u['roles'] as $r) {
    if (is_array($r) && isset($r['id'])) $rolesIds[] = (int)$r['id'];
    if (is_numeric($r)) $rolesIds[] = (int)$r;
  }
  $rolesIds = array_values(array_unique($rolesIds));
}

// 2) Roles por nombre (si existen en sesión)
$rolesNames = [];
if (!empty($u['roles_names']) && is_array($u['roles_names'])) {
  $rolesNames = array_map('strval', $u['roles_names']);
} elseif (!empty($u['roles']) && is_array($u['roles'])) {
  foreach ($u['roles'] as $r) {
    if (is_array($r) && isset($r['nombre'])) $rolesNames[] = (string)$r['nombre'];
    if (is_string($r)) $rolesNames[] = $r;
  }
  $rolesNames = array_values(array_unique($rolesNames));
}

// Flags de rol (por ID o por nombre)
$isDes = in_array($ROLE_IDS['DES'], $rolesIds, true) || in_array('Desarrollo', $rolesNames, true);
$isCon = in_array($ROLE_IDS['CON'], $rolesIds, true) || in_array('Control', $rolesNames, true);
$isAdm = in_array($ROLE_IDS['ADM'], $rolesIds, true) || in_array('Administración', $rolesNames, true);

// Jerarquía simple: superior si es Desarrollo o Control
$isSuperior = ($isDes || $isCon);
$rolLabel   = $isDes ? 'Desarrollo' : ($isCon ? 'Control' : 'Administración');

// Exporta a JS por si luego quieres variar UI/acciones según rol
$cfg = [
  'base'        => (string)BASE_URL,
  'module'      => (string)(BASE_URL . '/modules/usuarios_mtc/'),
  'empresaId'   => $empresaId,
  'empresaNom'  => $empresaNom,
  'usuarioNom'  => $usrNom,
  'rol'         => $rolLabel,
  'isSuperior'  => $isSuperior ? 1 : 0,
  'flags'       => ['DES'=>$isDes?1:0, 'CON'=>$isCon?1:0, 'ADM'=>$isAdm?1:0],
];

include __DIR__ . '/../../includes/header.php';
?>

<!-- (Opcional) CSS propio del módulo -->
<link rel="stylesheet" href="<?= h(BASE_URL) ?>/modules/usuarios_mtc/usuarios_mtc.css?v=1">

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">

      <div class="d-flex align-items-start justify-content-between flex-wrap">
        <div class="pr-2">
          <h1 class="m-0 text-dark">
            <i class="fas fa-user-shield mr-2"></i>Usuarios MTC
          </h1>

          <div class="text-muted mt-1">
            Empresa: <b><?= h($empresaNom) ?></b>
            &nbsp;•&nbsp;
            Usuario: <b><?= h($usrNom) ?></b>
            &nbsp;•&nbsp;
            Rol sesión: <b><?= h($rolLabel) ?></b>
            <?php if ($isSuperior): ?>
              <span class="badge badge-dark ml-2">Superior</span>
            <?php else: ?>
              <span class="badge badge-secondary ml-2">Administración</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>

  <section class="content pb-3">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-body">
          Hola mundo
        </div>
      </div>
    </div>
  </section>
</div>

<script>
window.USUARIOS_MTC_CFG = <?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<!-- (Opcional) JS propio del módulo -->
<script src="<?= h(BASE_URL) ?>/modules/usuarios_mtc/usuarios_mtc.js?v=1"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
