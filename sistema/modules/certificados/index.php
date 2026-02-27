<?php 
// /modules/certificados/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/funciones_formulario.php';

acl_require_ids([3, 4]);
verificarPermiso(['Recepción', 'Administración']);

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

$u      = currentUser();
$usrNom = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?: ($u['usuario'] ?? 'Usuario');
$empNom = (string)($u['empresa']['nombre'] ?? '—');

// -------------------------------------------------------------
// Totales de certificados (Activo / Vencido / Total)
// usando la MISMA lógica que listar_certificados.php
// -------------------------------------------------------------
$totalCertificados = 0;
$totalActivos      = 0;
$totalVencidos     = 0;

// Resolver empresa igual que en listar_certificados.php
$idEmpresa = cf_resolver_id_empresa_actual($u);

// Obtener conexión mysqli
$db = null;
if (function_exists('db')) {
    $db = db();
}

if ($idEmpresa > 0 && $db instanceof mysqli) {
    $sqlTotales = "
        SELECT
            COUNT(*) AS total,
            SUM(
                CASE
                    WHEN c.estado = 'Vencido'
                         OR (c.estado = 'Activo' AND c.fecha_emision < DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
                    THEN 1
                    ELSE 0
                END
            ) AS vencidos,
            SUM(
                CASE
                    WHEN (c.estado = 'Activo' AND c.fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
                    THEN 1
                    ELSE 0
                END
            ) AS activos
        FROM cq_certificados c
        WHERE c.id_empresa = ?
    ";

    if ($stmt = $db->prepare($sqlTotales)) {
        $stmt->bind_param('i', $idEmpresa);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res && $fila = $res->fetch_assoc()) {
                if (isset($fila['total'])) {
                    $totalCertificados = (int)$fila['total'];
                }
                if (isset($fila['activos'])) {
                    $totalActivos = (int)$fila['activos'];
                }
                if (isset($fila['vencidos'])) {
                    $totalVencidos = (int)$fila['vencidos'];
                }
            }
            if ($res instanceof mysqli_result) {
                $res->free();
            }
        }
        $stmt->close();
    }
}

// Header de AdminLTE
include __DIR__ . '/../../includes/header.php';
?>
<!-- Estilos específicos de este módulo -->
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/certificados/estilos.css?v=1">
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/certificados/estilo_certificado.css?v=1">

<div class="content-wrapper">
  <!-- HEADER INTERNO DEL MÓDULO -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="certbar shadow-sm">
        <div class="certbar-inner">
          <!-- LADO IZQUIERDO: ÍCONO + TÍTULO -->
          <div class="cert-main">
            <div class="cert-logo">
              <i class="fas fa-award main-ico"></i>
              <i class="fas fa-qrcode sub-ico"></i>
            </div>
            <div>
              <div class="cert-title">
                Sistema de Emisión de Certificados validados con QR
              </div>
              <div class="cert-meta">
                Empresa: <strong>"<?= h($empNom) ?>"</strong> |
                Usuario: <strong><?= h($usrNom) ?></strong>
              </div>
            </div>
          </div>
          <!-- LADO DERECHO: TARJETA BLANCA -->
          <div class="cert-panel">
            <div class="cert-panel-left">
              <span class="cert-panel-label">Certificados</span>
              <span class="cert-panel-label">Totales</span>
              <span class="cert-panel-total">
                <?php echo (int)$totalCertificados; ?>
              </span>
            </div>
            <div class="cert-panel-divider"></div>
            <div class="cert-panel-right">
              <div class="panel-row">
                <span class="panel-name">Activos</span>
                <span class="panel-value">
                  <?php echo (int)$totalActivos; ?>
                </span>
              </div>
              <div class="panel-row">
                <span class="panel-name">Vencidos</span>
                <span class="panel-value">
                  <?php echo (int)$totalVencidos; ?>
                </span>
              </div>
            </div>
          </div>
        </div><!-- /.certbar-inner -->
      </div><!-- /.certbar -->
    </div><!-- /.container-fluid -->
  </div><!-- /.content-header -->

  <!-- SECTION: FORMULARIO (IZQ) + LISTADO (DER) -->
  <section class="content pb-3">
    <div class="container-fluid">
      <div class="row cert-layout-row">
        <!-- IZQUIERDA: 4 columnas -->
        <div class="col-lg-5 col-md-5 cert-layout-left">
          <?php include __DIR__ . '/formulario_certificado.php'; ?>
        </div>

        <!-- DERECHA: 6 columnas -->
        <div class="col-lg-7 col-md-7 cert-layout-right">
          <?php include __DIR__ . '/certificados_emitidos.php'; ?>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
