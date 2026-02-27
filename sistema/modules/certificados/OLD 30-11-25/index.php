<?php 
// /modules/certificados/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([3, 4]);
verificarPermiso(['Recepción', 'Administración']);

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$u      = currentUser();
$usrNom = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?: ($u['usuario'] ?? 'Usuario');
$empNom = (string)($u['empresa']['nombre'] ?? '—');

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
              <span class="cert-panel-total">150</span><!-- provisional -->
            </div>
            <div class="cert-panel-divider"></div>
            <div class="cert-panel-right">
              <div class="panel-row">
                <span class="panel-name">Fast</span>
                <span class="panel-value">05</span>
              </div>
              <div class="panel-row">
                <span class="panel-name">Aula Virtual</span>
                <span class="panel-value">12</span>
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
