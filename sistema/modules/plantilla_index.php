<?php
// modules/<modulo>/index.php — Plantilla base portable

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
// (Opcional si lo usas) require_once __DIR__ . '/../../includes/auth.php';

/* ========= Config de la página ========= */
$PAGE_TITLE = 'Título del Módulo';                 // ← cámbialo
$ALLOWED_ROLE_IDS   = [3,4];                       // ← IDs permitidos (Recepción, Administración)
$ALLOWED_ROLE_NAMES = ['Recepción','Administración']; // ← Nombres del rol activo

/* ========= Guardas de acceso ========= */
acl_require_ids($ALLOWED_ROLE_IDS);
if (function_exists('verificarPermiso')) verificarPermiso($ALLOWED_ROLE_NAMES);

/* ========= Usuario y DB ========= */
$u = currentUser();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = db();
$db->set_charset('utf8mb4');

/* ========= Helpers ========= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/**
 * Cálculo de prefijo relativo a la raíz de la app y helper de enlaces.
 * - No depende de dominios ni de '/ventas/' hardcodeado.
 * - Soporta despliegue en subcarpeta o en subdominio.
 */
$appFolder = basename(dirname(dirname(__DIR__)));              // p.ej. 'ventas'
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // ej. 'ventas/modules/caja' o 'modules/caja'
$parts     = $scriptDir === '' ? [] : explode('/', $scriptDir);
$idx       = array_search($appFolder, $parts, true);
$depth     = ($idx === false) ? count($parts) : (count($parts) - ($idx + 1));
$APP_ROOT_REL = str_repeat('../', max(0, $depth)); // '../../' o '../' o ''

/** Devuelve una ruta relativa portable desde la página actual */
function rel(string $path) {
  global $APP_ROOT_REL; return $APP_ROOT_REL . ltrim($path, '/');
}

/* ========= (Opcional) Consultas iniciales =========
   Ejemplo:
   $st = $db->prepare("SELECT id,nombre FROM tabla WHERE activo=1 ORDER BY id DESC");
   $st->execute();
   $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
*/

include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrapper">
  <!-- Header de la página -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6"><h1 class="m-0"><?= h($PAGE_TITLE) ?></h1></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <!-- Breadcrumb 100% relativo -->
            <li class="breadcrumb-item"><a href="<?= h(rel('inicio.php')) ?>">Inicio</a></li>
            <li class="breadcrumb-item active"><?= h($PAGE_TITLE) ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Contenido principal -->
  <section class="content">
    <div class="container-fluid">
      <!-- Mensaje de bienvenida (puedes quitarlo) -->
      <div class="alert alert-info">
        Hola <?= h(trim(($u['nombres'] ?? '').' '.($u['apellidos'] ?? '')) ?: ($u['usuario'] ?? 'Usuario')) ?>,
        estás en <strong><?= h($PAGE_TITLE) ?></strong>.
      </div>

      <!-- Zona de trabajo del módulo -->
      <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Contenido</h3></div>
        <div class="card-body">
          <!-- TODO: Implementa tu UI aquí (formularios, tablas, etc.) -->
          <p class="text-muted mb-0">Agrega tu lógica y vistas del módulo.</p>
        </div>
      </div>

      <!-- Ejemplo de tabla (borra si no la usas)
      <div class="card">
        <div class="card-body table-responsive p-0">
          <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Nombre</th></tr></thead>
            <tbody>
              <?php // foreach (($rows ?? []) as $r): ?>
                <tr><td><?php //= (int)$r['id'] ?></td><td><?php //= h($r['nombre']) ?></td></tr>
              <?php // endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      -->
    </div>
  </section>
</div>

<!-- JS del módulo (solo si existe modules/<modulo>/index.js) -->
<?php if (is_file(__DIR__ . '/index.js')): ?>
  <script type="module" src="<?= h(rel('modules/' . basename(__DIR__) . '/index.js?v=1')) ?>"></script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
