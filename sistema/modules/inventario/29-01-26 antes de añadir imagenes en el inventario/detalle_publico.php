<?php
// modules/inventario/detalle_publico.php
// Página PÚBLICA: sin ACL, sin header/sidebar del sistema.

require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/inv_lib.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function rel_exists($relPathFromHere) {
  $fs = __DIR__ . '/' . $relPathFromHere;
  return file_exists($fs);
}
function css_tag_local_or_cdn($localRelFromHere, $cdnUrl) {
  if ($localRelFromHere && rel_exists($localRelFromHere)) {
    echo '<link rel="stylesheet" href="'.h($localRelFromHere).'">' . "\n";
  } else {
    echo '<link rel="stylesheet" href="'.h($cdnUrl).'">' . "\n";
  }
}

function logo_src_rel($p) {
  $p = trim((string)$p);
  if ($p === '') return '';
  if (preg_match('#^https?://#i', $p)) return $p;

  // Evitar root-relativas: convertir a relativa desde /modules/inventario/
  $p = ltrim($p, '/');
  return '../../' . $p; // desde modules/inventario -> sistema/
}

function badge_estado_class($e) {
  $e = (string)$e;
  if ($e === 'AVERIADO') return 'bad';
  if ($e === 'REGULAR') return 'reg';
  return 'good';
}

$code = trim((string)($_GET['code'] ?? ''));
$info = inv_parse_codigo($code);

$err = '';
$row = null;
$mov = null;

if (!$info) {
  $err = 'Código inválido.';
} else {
  $empresaId = (int)$info['empresa'];
  $id        = (int)$info['id'];

  $mysqli = db();

  $st = $mysqli->prepare("
    SELECT
      b.*,
      ub.nombre AS ubicacion_nombre,
      CONCAT(u2.nombres,' ',u2.apellidos) AS responsable_user,
      TRIM(CONCAT_WS(' ', b.responsable_nombres, b.responsable_apellidos)) AS responsable_texto,
      GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS categorias_txt,

      e.nombre AS empresa_nombre,
      e.razon_social,
      e.ruc,
      e.direccion,
      e.logo_path
    FROM inv_bienes b
    INNER JOIN mtp_empresas e ON e.id = b.id_empresa
    LEFT JOIN inv_ubicaciones ub ON ub.id = b.id_ubicacion
    LEFT JOIN mtp_usuarios u2 ON u2.id = b.id_responsable
    LEFT JOIN inv_bien_categoria bc ON bc.id_bien = b.id
    LEFT JOIN inv_categorias c ON c.id = bc.id_categoria
    WHERE b.id_empresa=? AND b.id=?
    GROUP BY b.id
    LIMIT 1
  ");
  $st->bind_param('ii', $empresaId, $id);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();

  if (!$row) {
    $err = 'No se encontró el bien.';
  } else {
    // Movimiento inicial (para saber quién registró)
    $st = $mysqli->prepare("
      SELECT
        m.id,
        m.tipo,
        m.creado,
        m.id_usuario,
        CONCAT(u.nombres,' ',u.apellidos) AS usuario_nombre,
        u.usuario AS usuario_login
      FROM inv_movimientos m
      LEFT JOIN mtp_usuarios u ON u.id = m.id_usuario
      WHERE m.id_empresa=? AND m.id_bien=?
      ORDER BY m.id ASC
      LIMIT 1
    ");
    $st->bind_param('ii', $empresaId, $id);
    $st->execute();
    $mov = $st->get_result()->fetch_assoc();
  }
}

// cache-buster para inventario.css
$invCssRel = './inventario.css';
$invCssFs  = __DIR__ . '/inventario.css';
$invCssV   = file_exists($invCssFs) ? (string)filemtime($invCssFs) : (string)time();

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Detalle de bienes de la empresa</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <?php
    // 1) FontAwesome local (AdminLTE) o CDN
    css_tag_local_or_cdn('../../plugins/fontawesome-free/css/all.min.css',
      'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
    );

    // 2) Bootstrap local (AdminLTE) o CDN
    css_tag_local_or_cdn('../../plugins/bootstrap/css/bootstrap.min.css',
      'https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/css/bootstrap.min.css'
    );

    // 3) Tu CSS del módulo (relativo al archivo)
    echo '<link rel="stylesheet" href="'.h($invCssRel).'?v='.h($invCssV).'">' . "\n";
  ?>

  <!-- Fallback mínimo: si Bootstrap NO carga, igual se verá bonito -->
  <style>
    /* Fallback helpers */
    html, body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }

    .container { max-width: 1100px; margin: 0 auto; padding: 0 15px; }
    .row { display:flex; flex-wrap:wrap; margin-left:-10px; margin-right:-10px; }
    .row > [class*="col-"] { padding-left:10px; padding-right:10px; }

    .col-12 { width:100%; }
    .col-6 { width:50%; }

    @media (min-width: 768px) {
      .col-md-4 { width:33.3333%; }
      .col-md-6 { width:50%; }
      .col-md-8 { width:66.6666%; }
    }
    @media (min-width: 992px) {
      .col-lg-8 { width:66.6666%; }
      .col-lg-4 { width:33.3333%; }
    }

    .d-flex{display:flex;}
    .flex-wrap{flex-wrap:wrap;}
    .align-items-start{align-items:flex-start;}
    .align-items-center{align-items:center;}
    .justify-content-between{justify-content:space-between;}

    .mt-2{margin-top:.5rem;}
    .mt-3{margin-top:1rem;}
    .mb-1{margin-bottom:.25rem;}
    .mb-2{margin-bottom:.5rem;}
    .mb-3{margin-bottom:1rem;}
    .ml-3{margin-left:1rem;}
    .mr-1{margin-right:.25rem;}

    .text-muted{color:#64748b;}
    .small{font-size:12px;}
    hr{border:0;border-top:1px solid rgba(15,23,42,.10);margin:12px 0;}

    .btn{
      display:inline-flex; align-items:center; gap:.35rem;
      border:1px solid rgba(15,23,42,.14);
      padding:.42rem .75rem;
      border-radius:12px;
      text-decoration:none;
      cursor:pointer;
      background:#fff;
      color:#0f172a;
      font-weight:700;
      font-size:13px;
      line-height:1.1;
      user-select:none;
    }
    .btn:active{transform:translateY(1px);}
    .btn-sm{padding:.35rem .6rem;font-size:12px;border-radius:12px;}
    .btn-light{background:#fff;}
    .btn-primary{background:#2563eb;border-color:#2563eb;color:#fff;}
    .btn-outline-secondary{background:#fff;border-color:rgba(15,23,42,.18);color:#0f172a;}

    .card{
      background:#fff;
      border:1px solid rgba(15,23,42,.10);
      border-radius:18px;
      box-shadow:0 12px 28px rgba(15,23,42,.06);
    }
    .card-body{padding:14px;}

    /* Si por alguna razón inventario.css no carga, mínimo tema */
    body.invg2 { background:#f8fafc; }
    .invg2-hero {
      background: radial-gradient(1200px 280px at 12% 30%, rgba(124,58,237,.20), transparent 55%),
                  radial-gradient(1200px 280px at 70% 20%, rgba(6,182,212,.18), transparent 55%),
                  radial-gradient(1200px 280px at 92% 30%, rgba(251,113,133,.14), transparent 55%);
    }
    .invg2-b { border-radius:999px;padding:4px 10px;font-weight:900;font-size:12px;display:inline-block; }
    .invg2-b.good { background: rgba(34,197,94,.16); color:#14532d; border:1px solid rgba(34,197,94,.22); }
    .invg2-b.reg  { background: rgba(245,158,11,.18); color:#7c2d12; border:1px solid rgba(245,158,11,.22); }
    .invg2-b.bad  { background: rgba(239,68,68,.16); color:#7f1d1d; border:1px solid rgba(239,68,68,.22); }

    .pub-top{padding:18px 0;}
    .pub-brand{display:flex;align-items:center;gap:12px;}
    .pub-logo{
      width:54px;height:54px;border-radius:18px;overflow:hidden;
      display:flex;align-items:center;justify-content:center;
      background: linear-gradient(135deg, rgba(124,58,237,.18), rgba(6,182,212,.14));
      border: 1px solid rgba(124,58,237,.20);
      color:#3b0764;font-weight:900;flex:0 0 auto;
    }
    .pub-logo img{width:100%;height:100%;object-fit:cover;display:block;}
    .pub-title{font-weight:900;font-size:18px;color:#0f172a;line-height:1.1;}
    .pub-sub{color:#64748b;font-size:13px;margin-top:2px;}

    .pub-codebox{
      background: rgba(255,255,255,.85);
      border: 1px solid rgba(15,23,42,.08);
      border-radius: 16px;
      padding: 12px;
      box-shadow: 0 10px 24px rgba(15,23,42,.05);
    }
    .pub-code{font-weight:900;letter-spacing:.3px;font-size:14px;color:#0f172a;word-break:break-word;}
    .pub-mini{color:#64748b;font-size:12px;margin-top:4px;}

    .pub-qr{
      background: rgba(255,255,255,.90);
      border: 1px solid rgba(15,23,42,.08);
      border-radius: 18px;
      padding: 12px;
      text-align: center;
      box-shadow: 0 12px 28px rgba(15,23,42,.06);
    }
    .pub-qr img{max-width:240px;width:100%;height:auto;border-radius:12px;}
    .pub-wrap{padding-bottom:30px;}

    .pub-kv .k{color:#64748b;font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:.4px;}
    .pub-kv .v{color:#0f172a;font-weight:800;}
    .h{font-weight:900;font-size:16px;color:#0f172a;}
    .mut{color:#64748b;font-size:13px;}
  </style>
</head>

<body class="invg2">
  <div class="invg2-hero pub-top">
    <div class="container">
      <div class="d-flex align-items-start justify-content-between flex-wrap">
        <div class="pub-brand">
          <div class="pub-logo">
            <?php
              if (!$err && $row) {
                $logo = logo_src_rel($row['logo_path'] ?? '');
                if ($logo !== '') {
                  echo '<img alt="Logo" src="'.h($logo).'">';
                } else {
                  $ini = strtoupper(mb_substr((string)($row['empresa_nombre'] ?? 'E'), 0, 1, 'UTF-8'));
                  echo h($ini);
                }
              } else {
                echo '<i class="fas fa-building"></i>';
              }
            ?>
          </div>
          <div>
            <div class="pub-title">Detalle de bienes de la empresa</div>
            <div class="pub-sub">
              <?php if (!$err && $row): ?>
                <b><?= h($row['empresa_nombre']) ?></b>
                <?php if (!empty($row['ruc'])): ?> • RUC <?= h($row['ruc']) ?><?php endif; ?>
              <?php else: ?>
                Información pública del inventario
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if (!$err && $row): ?>
          <?php
            $codigoInv = inv_codigo($row['id_empresa'], $row['creado'], $row['id']);
            $urlPublica = inv_qr_payload(defined('BASE_URL') ? BASE_URL : '', $codigoInv);
          ?>
          <div class="mt-2">
            <button class="btn btn-light btn-sm" id="btnCopyLink" type="button">
              <i class="fas fa-link"></i> Copiar enlace
            </button>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!$err && $row): ?>
        <div class="mt-3 pub-codebox">
          <div class="pub-code"><?= h($codigoInv) ?></div>
          <div class="pub-mini">
            <?php if (!empty($row['razon_social'])): ?>
              <?= h($row['razon_social']) ?><br>
            <?php endif; ?>
            <?php if (!empty($row['direccion'])): ?>
              <i class="fas fa-map-marker-alt mr-1"></i><?= h($row['direccion']) ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <div class="container pub-wrap">
    <?php if ($err): ?>
      <div class="card mt-3">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="pub-logo"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="ml-3">
              <div class="h">No se pudo mostrar el bien</div>
              <div class="mut"><?= h($err) ?></div>
            </div>
          </div>
          <div class="text-muted small mt-3">
            Si el código fue impreso hace mucho, genera nuevamente el QR desde el inventario.
          </div>
        </div>
      </div>
    <?php else: ?>

      <?php
        $resp = trim((string)($row['responsable_user'] ?? ''));
        if ($resp === '') {
          $rt = trim((string)($row['responsable_texto'] ?? ''));
          if ($rt !== '') {
            $resp = $rt;
            if (!empty($row['responsable_dni'])) $resp .= ' (DNI ' . $row['responsable_dni'] . ')';
          } else {
            $resp = '—';
          }
        }

        $cats = trim((string)($row['categorias_txt'] ?? ''));
        if ($cats === '') $cats = '—';

        $ubic = trim((string)($row['ubicacion_nombre'] ?? ''));
        if ($ubic === '') $ubic = '—';

        $creado = (string)($row['creado'] ?? '');
        $actualizado = (string)($row['actualizado'] ?? '');

        $registradoPor = '—';
        $registradoAt  = '';
        if ($mov) {
          $registradoPor = trim((string)($mov['usuario_nombre'] ?? ''));
          if ($registradoPor === '') $registradoPor = trim((string)($mov['usuario_login'] ?? ''));
          if ($registradoPor === '') $registradoPor = '—';
          $registradoAt  = (string)($mov['creado'] ?? '');
        }
      ?>

      <div class="row mt-3">
        <div class="col-12 col-lg-8 mb-3">
          <div class="card">
              <?php
  $codigoInv = inv_codigo($row['id_empresa'], $row['creado'], $row['id']);
?>
<?php if (!empty($row['img_key'])): ?>
  <div class="mb-3">
    <img class="invg2-photo" alt="Imagen del bien"
         src="./img_public.php?code=<?= h(rawurlencode($codigoInv)) ?>">
  </div>
<?php endif; ?>

            <div class="card-body">
              <div class="d-flex align-items-start justify-content-between flex-wrap">
                <div>
                  <div class="h mb-1"><?= h($row['nombre']) ?></div>
                  <div class="mut"><?= h($row['descripcion'] ?? '') ?></div>
                </div>
                <div class="mt-2">
                  <span class="invg2-b <?= h(badge_estado_class($row['estado'] ?? '')) ?>">
                    <?= h($row['estado'] ?? '') ?>
                  </span>
                </div>
              </div>

              <hr>

              <div class="row pub-kv">
                <div class="col-6 col-md-4 mb-3">
                  <div class="k">Tipo</div>
                  <div class="v"><?= h($row['tipo'] ?? '—') ?></div>
                </div>
                <div class="col-6 col-md-4 mb-3">
                  <div class="k">Activo</div>
                  <div class="v"><?= ((int)($row['activo'] ?? 0) === 1) ? 'Sí' : 'No' ?></div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                  <div class="k">Cantidad</div>
                  <div class="v"><?= h($row['cantidad'] ?? '') ?> <?= h($row['unidad'] ?? '') ?></div>
                </div>

                <div class="col-6 col-md-4 mb-3">
                  <div class="k">Marca</div>
                  <div class="v"><?= h($row['marca'] ?? '—') ?></div>
                </div>
                <div class="col-6 col-md-4 mb-3">
                  <div class="k">Modelo</div>
                  <div class="v"><?= h($row['modelo'] ?? '—') ?></div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                  <div class="k">Serie</div>
                  <div class="v"><?= h($row['serie'] ?? '—') ?></div>
                </div>

                <div class="col-12 col-md-6 mb-3">
                  <div class="k">Ubicación</div>
                  <div class="v"><i class="fas fa-map-marker-alt mr-1"></i><?= h($ubic) ?></div>
                </div>
                <div class="col-12 col-md-6 mb-3">
                  <div class="k">Responsable</div>
                  <div class="v"><i class="fas fa-user mr-1"></i><?= h($resp) ?></div>
                </div>

                <div class="col-12 mb-3">
                  <div class="k">Categorías</div>
                  <div class="v"><i class="fas fa-tags mr-1"></i><?= h($cats) ?></div>
                </div>

                <div class="col-12 mb-3">
                  <div class="k">Notas</div>
                  <div class="v"><?= h($row['notas'] ?? '—') ?></div>
                </div>

                <div class="col-12 col-md-6 mb-2">
                  <div class="k">Registrado por</div>
                  <div class="v"><i class="fas fa-user-check mr-1"></i><?= h($registradoPor) ?></div>
                  <?php if ($registradoAt !== ''): ?>
                    <div class="text-muted small">Fecha/hora: <?= h($registradoAt) ?></div>
                  <?php endif; ?>
                </div>
                <div class="col-12 col-md-6 mb-2">
                  <div class="k">Fechas</div>
                  <div class="text-muted small">
                    Creado: <?= h($creado ?: '—') ?><br>
                    Actualizado: <?= h($actualizado ?: '—') ?>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4 mb-3">
          <div class="pub-qr">
            <div class="pub-code mb-2"><?= h($codigoInv) ?></div>
            <img alt="QR" src="./qr_public.php?code=<?= h(rawurlencode($codigoInv)) ?>&s=6">
            <div class="mt-3">
              <a class="btn btn-primary btn-sm" href="./qr_public.php?code=<?= h(rawurlencode($codigoInv)) ?>&dl=1&s=8">
                <i class="fas fa-download"></i> Descargar QR
              </a>
              <a class="btn btn-outline-secondary btn-sm" href="<?= h($urlPublica) ?>" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-external-link-alt"></i> Abrir enlace
              </a>
            </div>
            <div class="text-muted small mt-2">Este QR es público y abre este mismo detalle.</div>
          </div>
        </div>
      </div>

      <script>
        (function(){
          var url = <?= json_encode($urlPublica) ?>;
          var btn = document.getElementById('btnCopyLink');
          if (!btn) return;

          function fallbackCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly','');
            ta.style.position = 'absolute';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch(e) {}
            document.body.removeChild(ta);
          }

          btn.addEventListener('click', function(){
            if (navigator.clipboard && navigator.clipboard.writeText) {
              navigator.clipboard.writeText(url).then(function(){
                btn.innerHTML = '<i class="fas fa-check"></i> Enlace copiado';
                setTimeout(function(){ btn.innerHTML = '<i class="fas fa-link"></i> Copiar enlace'; }, 1400);
              }).catch(function(){ fallbackCopy(url); });
            } else {
              fallbackCopy(url);
              btn.innerHTML = '<i class="fas fa-check"></i> Enlace copiado';
              setTimeout(function(){ btn.innerHTML = '<i class="fas fa-link"></i> Copiar enlace'; }, 1400);
            }
          });
        })();
      </script>

    <?php endif; ?>
  </div>
</body>
</html>
