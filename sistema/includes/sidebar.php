<?php
/**
 * /includes/sidebar.php
 * -----------------------------------------------------------------------------
 * Sidebar (AdminLTE) con ruteo por ROL ACTIVO y usuario.
 * - Avatar + mini-logo de empresa superpuesto.
 * - En modo colapsado, se ocultan textos y se reducen tamaños.
 * - Sin bordes blancos en avatar ni en el mini-logo (para no “robar” espacio).
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/auth.php';

$u          = currentUser();
$rolActivo  = $u['rol_activo'] ?? '';
$empresaId  = $u['empresa']['id'] ?? null;
$empresaNom = $u['empresa']['nombre'] ?? '';
$nombreUsr  = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
$depaNombre = $u['empresa']['depa']['nombre'] ?? '';
$tipoId     = null;  // Se completará si faltan datos
$tipoNombre = '';

// -----------------------------------------------------------------------------
// (1) Completar datos de empresa si faltan (defensa en profundidad)
// -----------------------------------------------------------------------------
if ($empresaId && ($depaNombre === '' || $empresaNom === '' || $tipoId === null)) {
    try {
        $st = db()->prepare("
            SELECT e.nombre AS empresa,
                   te.id     AS tipo_id,
                   te.nombre AS tipo_nombre,
                   d.nombre  AS depa
            FROM mtp_empresas e
            LEFT JOIN mtp_tipos_empresas te ON te.id = e.id_tipo
            LEFT JOIN mtp_departamentos d    ON d.id = e.id_depa
            WHERE e.id = ?
            LIMIT 1
        ");
        $st->bind_param('i', $empresaId);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();

        if ($row) {
            if ($empresaNom === '')  $empresaNom  = $row['empresa'] ?? '';
            if ($depaNombre === '')  $depaNombre  = $row['depa'] ?? '';
            $tipoId     = isset($row['tipo_id']) ? (int)$row['tipo_id'] : null;
            $tipoNombre = $row['tipo_nombre'] ?? '';
        }
        $st->close();
    } catch (Throwable $e) {
        // Opcional: log de error
    }
}

// -----------------------------------------------------------------------------
// (2) Helper visual para badge según tipo de empresa (ajusta a tu mapeo real)
// -----------------------------------------------------------------------------
function empresa_badge_class($tipoId)
{
    switch ($tipoId) {
        case 1: return 'bg-warning text-dark';   // ESCON
        case 2: return 'bg-primary text-white';  // ECSAL
        case 3: return 'bg-secondary text-white';// MATPEL
        case 4: return 'bg-danger text-white';   // Escuela de Manejo
        case 5: return 'bg-success text-white';  // Proyecto
        case 6: return 'bg-dark text-white';     // Central
        default:return 'bg-light text-dark';     // Otros / sin tipo
    }
}
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- (A) Logo -->
    <a href="<?= BASE_URL ?>/inicio.php" class="brand-link">
        <img src="<?= BASE_URL ?>/includes/logo_mouse.png"
             alt="Logo"
             class="brand-image img-circle elevation-3"
             style="opacity:.8">
        <span class="brand-text font-weight-light">MTC Pro</span>
    </a>

    <div class="sidebar">

        <!-- (B) Estilos rápidos -->
        <style>
            .main-sidebar .user-panel { margin: 8px 6px 10px; padding: 0; border-radius: 12px; overflow: hidden; }
            .user-panel .image img { width: 90px; height: 90px; object-fit: cover; border: 0; }
            .u-name { font-weight: 700; color: #fff; line-height: 1.15; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 230px; text-shadow: 0 1px 2px rgba(0,0,0,.35); }
            .u-badges .badge { font-size: .75rem; font-weight: 700; border-radius: 999px; padding: .25rem .65rem; }
            .nav-header { margin: 8px 8px 6px; color: #94a3b8!important; }
            .nav-sidebar > .nav-item { margin-bottom: 4px; }
            .user-card { position: relative; }
            .uc-avatar { position: relative; display: inline-block; }
            .uc-avatar > img { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 0 !important; }
            .emp-mini-logo{ position: absolute; right: -2px; bottom: -2px; width: 40px; height: 40px; border-radius: 50%; background: transparent; padding: 0; z-index: 2; box-shadow: 0 2px 6px rgba(0,0,0,.25); display: flex; align-items: center; justify-content: center; border: 0; }
            .emp-mini-logo img{ width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 0; }
            .sidebar-mini.sidebar-collapse .main-sidebar .user-card{ padding:12px 10px; background-image:none !important; border-radius:12px; }
            .sidebar-mini.sidebar-collapse .main-sidebar .user-card .uc-name,
            .sidebar-mini.sidebar-collapse .main-sidebar .user-card .uc-company,
            .sidebar-mini.sidebar-collapse .main-sidebar .user-card .uc-meta{ display:none !important; }
            .sidebar-mini.sidebar-collapse .main-sidebar .user-card .uc-avatar > img{ width:56px !important; height:56px !important; border:0 !important; }
            .sidebar-mini.sidebar-collapse .main-sidebar .user-card .emp-mini-logo{ width:28px; height:28px; bottom:-2px; right:-2px; box-shadow:0 1px 4px rgba(0,0,0,.35); border:0; }
        </style>

        <!-- (C) Tarjeta de usuario -->
        <?php
        $colorClass = empresa_badge_class($u['empresa']['id_tipo'] ?? $tipoId);

        // Foto de perfil (real o genérica) normalizando ruta
        $fotoUrl = BASE_URL . '/dist/img/user2-160x160.jpg';
        try {
            $uid = (int)($u['id'] ?? ($_SESSION['user']['id'] ?? 0));
            if ($uid > 0) {
                $st = db()->prepare("SELECT ruta_foto FROM mtp_detalle_usuario WHERE id_usuario=? LIMIT 1");
                $st->bind_param('i', $uid);
                $st->execute();
                if ($row = $st->get_result()->fetch_assoc()) {
                    $ruta = trim((string)($row['ruta_foto'] ?? ''));
                    if ($ruta !== '') $fotoUrl = BASE_URL . '/' . ltrim($ruta, '/');
                }
                $st->close();
            }
        } catch (Throwable $e) {}

        // Logo de empresa (mini) con fallback
        $logoEmpUrl = BASE_URL . '/dist/img/AdminLTELogo.png';
        try {
            $logoFromSession = $u['empresa']['logo_path'] ?? null;
            if ($logoFromSession) {
                $logoEmpUrl = BASE_URL . '/' . ltrim((string)$logoFromSession, '/');
            } elseif ($empresaId) {
                $st2 = db()->prepare("SELECT logo_path FROM mtp_empresas WHERE id=? LIMIT 1");
                $st2->bind_param('i', $empresaId);
                $st2->execute();
                if ($r2 = $st2->get_result()->fetch_assoc()) {
                    $p = trim((string)($r2['logo_path'] ?? ''));
                    if ($p !== '') $logoEmpUrl = BASE_URL . '/' . ltrim($p, '/');
                }
                $st2->close();
            }
        } catch (Throwable $e) {}
        ?>

        <div class="user-card text-center p-3 mb-3"
             style="background:url('<?= BASE_URL ?>/assets/img/bg1.webp') no-repeat center center; background-size:cover; border-radius:12px;">
            <div class="mb-2 uc-avatar"
                 title="<?= htmlspecialchars(($nombreUsr ?: ($u['usuario'] ?? 'Usuario'))) . ' — ' . (($u['empresa']['nombre'] ?? $empresaNom) ?: '—') . ' — ' . ($rolActivo ?: 'Perfil') ?>">
                <img src="<?= htmlspecialchars($fotoUrl) ?>" alt="User" class="img-circle elevation-3">
                <span class="emp-mini-logo" title="Logo de la empresa"><img src="<?= htmlspecialchars($logoEmpUrl) ?>" alt="Logo empresa"></span>
            </div>
            <div class="fw-bold text-white fs-5 mb-1 uc-name"><?= htmlspecialchars($nombreUsr ?: ($u['usuario'] ?? 'Usuario')) ?></div>
            <div class="mb-2 uc-company"><span class="badge <?= $colorClass ?> px-3 py-2 fs-6"><?= htmlspecialchars(($u['empresa']['nombre'] ?? $empresaNom) ?: '—') ?></span></div>
            <div class="d-flex justify-content-between mt-2 uc-meta">
                <span class="badge bg-light text-dark me-1">📍 <?= htmlspecialchars($depaNombre ?: '—') ?></span>
                <span class="badge bg-primary"><i class="fas fa-id-badge me-1"></i> <?= htmlspecialchars($rolActivo ?: 'Perfil') ?></span>
            </div>
        </div>

        <!-- (D) MENÚ: basado en matriz por ID de rol -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <li class="nav-header">NAVEGACIÓN</li>
            <?php
              // 1) Resolver rol_activo_id
              $rolActivoId = $_SESSION['user']['rol_activo_id'] ?? null;
              if (!$rolActivoId) {
                try { $st=db()->prepare("SELECT id FROM mtp_roles WHERE nombre=? LIMIT 1"); $st->bind_param('s',$rolActivo); $st->execute(); if($r=$st->get_result()->fetch_assoc()) $rolActivoId=(int)$r['id']; $st->close(); } catch (Throwable $e) {}
                $_SESSION['user']['rol_activo_id'] = $rolActivoId;
              }

              // 2) Cargar matriz
              $MM = require __DIR__ . '/menu_matrix.php';

              // 3) Permisos y normalización
              $isAllowed=function(array $it,?int $rolId,array $u):bool{ $roles=$it['roles']??'*'; $ok=($roles==='*')||(is_array($roles)&&$rolId&&in_array($rolId,$roles,true)); if(!$ok)return false; return isset($it['when'])&&is_callable($it['when']) ? (bool)($it['when'])($u) : true; };
              $normalize=function($p){ $x=parse_url($p,PHP_URL_PATH)??''; $x=preg_replace('#/index\.php$#','',$x); return rtrim($x,'/')?:'/'; };
              $current=$normalize($_SERVER['REQUEST_URI']??'/');

              // 4) Cálculo de prefijo relativo y base canónica del app
$appFolder = basename(dirname(__DIR__));                                      // ej: 'ventas'
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');               // ej: 'ventas/modules/caja' o 'modules/caja'
$parts     = $scriptDir === '' ? [] : explode('/', $scriptDir);
$idx       = array_search($appFolder, $parts, true);                          // posición de 'ventas' si existe
$depth     = ($idx === false) ? count($parts) : (count($parts) - ($idx + 1)); // niveles para subir
$APP_ROOT_REL = str_repeat('../', max(0, $depth));                            // prefijo RELATIVO para href
$CANON_BASE   = '/' . ($idx === false ? '' : implode('/', array_slice($parts, 0, $idx + 1)) . '/'); // base para comparar “activo”

              // 5) Renderer recursivo con children y rutas relativas
              $render=function(array $items) use (&$render,$isAllowed,$rolActivoId,$u,$normalize,$current,$APP_ROOT_REL,$CANON_BASE){
                foreach($items as $it){
                  if(!$isAllowed($it,$rolActivoId,$u)) continue;
                  $kids=$it['children']??null; $icon=$it['icon']??'far fa-circle'; $label=$it['label']??'';
                  if($kids){
                    $open=false; foreach($kids as $ch){ if(!isset($ch['path'])) continue; $t=$normalize($CANON_BASE.ltrim($ch['path'],'/')); if($current===$t||strpos($current,rtrim($t,'/').'/')===0){$open=true;break;} }
                    echo '<li class="nav-item has-treeview'.($open?' menu-open':'').'"><a href="#" class="nav-link'.($open?' active':'').'"><i class="nav-icon '.$icon.'"></i><p>'.$label.'<i class="right fas fa-angle-left"></i></p></a><ul class="nav nav-treeview">';
                    $render($kids);
                    echo '</ul></li>';
                  } else {
                    $p=$it['path']??'#'; $href=$APP_ROOT_REL.ltrim($p,'/'); $t=$normalize($CANON_BASE.ltrim($p,'/')); $active=($t!=='/'&&($current===$t||strpos($current,rtrim($t,'/').'/')===0));
                    echo '<li class="nav-item"><a href="'.$href.'" class="nav-link'.($active?' active':'').'"><i class="nav-icon '.$icon.'"></i><p>'.$label.'</p></a></li>';
                  }
                }
              };

              // 6) Render
              $render($MM['common'] ?? []);
              $render($MM['items']  ?? []);
            ?>
          </ul>
        </nav>
        <!-- FIN MENÚ -->
    </div>
</aside>
