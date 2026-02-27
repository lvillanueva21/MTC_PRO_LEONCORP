<?php
// /modules/lab/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

// Mantengo el mismo control de acceso del módulo certificados (ajústalo si quieres)
acl_require_ids([3, 4]);
verificarPermiso(['Recepción', 'Administración']);

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// Título de la página (por si header.php lo usa)
$pageTitle = 'Laboratorio de pruebas';
$title     = $pageTitle; // compatibilidad si tu header usa $title
$titulo    = $pageTitle; // compatibilidad si tu header usa $titulo

// Datos de contexto (por si los quieres mostrar o usar en cards luego)
$u      = currentUser();
$usrNom = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?: ($u['usuario'] ?? 'Usuario');
$empNom = (string)($u['empresa']['nombre'] ?? '—');

// Header de AdminLTE (sidebar, topbar, etc.)
include __DIR__ . '/../../includes/header.php';
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0"><?= h($pageTitle) ?></h1>
          <div class="text-muted">
            Empresa: <strong><?= h($empNom) ?></strong> | Usuario: <strong><?= h($usrNom) ?></strong>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CUERPO VACÍO (lienzo en blanco) -->
  <section class="content pb-3">
    <div class="container-fluid">
<?php
include __DIR__ . '/card/card_11_env_versions_v1.php';
include __DIR__ . '/card/card_01_roles_v2.php';
include __DIR__ . '/card/card_03_includes_v1.php';
include __DIR__ . '/card/card_04_sql_tables_v1.php';
include __DIR__ . '/card/card_02_vars_v1.php';
include __DIR__ . '/card/card_06_forms_v1.php';
include __DIR__ . '/card/card_07_ajax_monitor_v1.php';
include __DIR__ . '/card/card_08_assets_v1.php';
include __DIR__ . '/card/card_05_routes_v1.php';
include __DIR__ . '/card/card_09_images_v1.php';
include __DIR__ . '/card/card_10_classifier_v1.php';
include __DIR__ . '/card/card_12_modulemap_v1.php';
?>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
