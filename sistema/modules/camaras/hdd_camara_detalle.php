<?php
// modules/camaras/hdd_camara_detalle.php
require_once __DIR__ . '/_bootstrap.php';

function cam_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Para .load(): siempre 200 con HTML (evita freeze)
http_response_code(200);

// Solo Desarrollo y Gerente pueden crear / editar / eliminar discos y consumos
$puedeGestionarHdd = ($esDesarrollo || $esGerente);

$idCamara = isset($_GET['id_camara']) ? (int)$_GET['id_camara'] : 0;
if ($idCamara <= 0) {
    echo '<div class="alert alert-danger mb-0">Cámara no válida.</div>';
    return;
}

try {
    // Datos de cámara + empresa
    $sqlCam = "
        SELECT c.id,
               c.nombre AS camara_nombre,
               c.id_empresa,
               e.nombre AS empresa_nombre
        FROM cam_camaras c
        INNER JOIN mtp_empresas e ON e.id = c.id_empresa
        WHERE c.id = ?
        LIMIT 1
    ";
    $stmtCam = mysqli_prepare($cn, $sqlCam);
    mysqli_stmt_bind_param($stmtCam, 'i', $idCamara);
    mysqli_stmt_execute($stmtCam);
    $resCam = mysqli_stmt_get_result($stmtCam);
    $camara = $resCam ? mysqli_fetch_assoc($resCam) : null;
    if ($resCam) mysqli_free_result($resCam);
    mysqli_stmt_close($stmtCam);

    if (!$camara) {
        echo '<div class="alert alert-danger mb-0">No se encontró la cámara.</div>';
        return;
    }

    // HDD instalado (si lo hay)
    $hddActual = null;
    $sqlHdd = "
        SELECT *
        FROM cam_hdd
        WHERE id_camara = ? AND estado = 'INSTALADO'
        ORDER BY fecha_instalacion DESC
        LIMIT 1
    ";
    $stmtHdd = mysqli_prepare($cn, $sqlHdd);
    mysqli_stmt_bind_param($stmtHdd, 'i', $idCamara);
    mysqli_stmt_execute($stmtHdd);
    $resHdd = mysqli_stmt_get_result($stmtHdd);
    $hddActual = $resHdd ? mysqli_fetch_assoc($resHdd) : null;
    if ($resHdd) mysqli_free_result($resHdd);
    mysqli_stmt_close($stmtHdd);

    // Historial de consumo (solo si hay HDD instalado)
    $consumos = array();
    if ($hddActual) {
        $idHdd = (int)$hddActual['id'];
        $sqlCons = "
            SELECT fecha_registro, fecha_dia, tipo, valor_gb, nota
            FROM cam_hdd_consumo
            WHERE id_hdd = ?
            ORDER BY fecha_dia DESC
            LIMIT 30
        ";
        $stmtCons = mysqli_prepare($cn, $sqlCons);
        mysqli_stmt_bind_param($stmtCons, 'i', $idHdd);
        mysqli_stmt_execute($stmtCons);
        $resCons = mysqli_stmt_get_result($stmtCons);
        if ($resCons) {
            while ($row = mysqli_fetch_assoc($resCons)) $consumos[] = $row;
            mysqli_free_result($resCons);
        }
        mysqli_stmt_close($stmtCons);
    }

    // Datos para gráfico
    $chartValues = array();
    if (!empty($consumos)) {
        $consumosAsc = array_reverse($consumos);
        foreach ($consumosAsc as $c) $chartValues[] = (int)$c['valor_gb'];
    }
    $chartValuesJson = json_encode($chartValues);

    // Discos retirados
    $hddsRetirados = array();
    $sqlRet = "
        SELECT id, marca, nro_serie, capacidad_gb,
               fecha_retiro, responsable_retiro,
               fecha_inicio_grab, fecha_fin_grab
        FROM cam_hdd
        WHERE id_camara = ? AND estado = 'RETIRADO'
        ORDER BY fecha_retiro DESC
        LIMIT 50
    ";
    $stmtRet = mysqli_prepare($cn, $sqlRet);
    mysqli_stmt_bind_param($stmtRet, 'i', $idCamara);
    mysqli_stmt_execute($stmtRet);
    $resRet = mysqli_stmt_get_result($stmtRet);
    if ($resRet) {
        while ($row = mysqli_fetch_assoc($resRet)) $hddsRetirados[] = $row;
        mysqli_free_result($resRet);
    }
    mysqli_stmt_close($stmtRet);

    // Cálculos
    $hddIdActual       = $hddActual ? (int)$hddActual['id'] : 0;
    $capacidadGbActual = $hddActual ? (int)$hddActual['capacidad_gb'] : 0;
    $capacidadTexto    = $capacidadGbActual > 0 ? $capacidadGbActual . ' GB' : '—';

    $diasEnUso = null;
    $fechaInstalacionTexto = '—';
    if ($hddActual && !empty($hddActual['fecha_instalacion'])) {
        $fi = new DateTime($hddActual['fecha_instalacion']);
        $fechaInstalacionTexto = $fi->format('d/m/Y H:i');
        $hoy = new DateTime();
        $diasEnUso = (int)$fi->diff($hoy)->days;
    }

    $ultimoConsumo = !empty($consumos) ? $consumos[0] : null;

} catch (Throwable $e) {
    error_log('[CAMARAS] hdd_camara_detalle.php: ' . $e->getMessage());
    echo '<div class="alert alert-danger mb-0">No se pudo cargar la información de HDD.</div>';
    if (!empty($esDesarrollo)) {
        echo '<div class="small text-muted mt-2" style="font-family:monospace;">' . cam_h($e->getMessage()) . '</div>';
    }
    return;
}
?>

<div class="hdd-panel">

  <div class="hdd-header mb-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
      <div class="mb-2 mb-md-0">
        <div class="font-weight-bold">
          Cámara: <?php echo cam_h($camara['camara_nombre']); ?>
        </div>
        <div class="text-muted small">
          Empresa: <?php echo cam_h($camara['empresa_nombre']); ?>
        </div>
      </div>
      <div class="text-md-right">
        <?php if ($hddActual): ?>
          <span class="badge badge-success">HDD instalado</span>
          <span class="badge badge-light">Capacidad: <?php echo cam_h($capacidadTexto); ?></span>
          <?php if ($diasEnUso !== null): ?>
            <span class="badge badge-info">En uso: <?php echo (int)$diasEnUso; ?> días</span>
          <?php endif; ?>
        <?php else: ?>
          <span class="badge badge-secondary">Sin HDD instalado</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($hddActual): ?>
    <input type="hidden" id="hdd_actual_id" value="<?php echo (int)$hddIdActual; ?>">

    <div class="card hdd-card mb-3">
      <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between">
          <div>
            <div class="hdd-title">Disco instalado actual</div>
            <div class="hdd-meta">
              <div><strong>Marca:</strong> <?php echo cam_h($hddActual['marca']); ?></div>
              <div><strong>Nº serie:</strong> <?php echo cam_h($hddActual['nro_serie']); ?></div>
              <div><strong>Capacidad:</strong> <?php echo cam_h($capacidadTexto); ?></div>
              <div><strong>Instalado desde:</strong> <?php echo cam_h($fechaInstalacionTexto); ?></div>
              <?php if (!empty($hddActual['nota_instalacion'])): ?>
                <div><strong>Nota instalación:</strong> <?php echo cam_h($hddActual['nota_instalacion']); ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="text-md-right mt-3 mt-md-0">
            <?php if ($ultimoConsumo): ?>
              <?php
              $fechaUlt = new DateTime($ultimoConsumo['fecha_registro']);
              $txtUlt   = $fechaUlt->format('d/m/Y H:i');
              $valorUlt = (int)$ultimoConsumo['valor_gb'];
              $tipoUlt  = ($ultimoConsumo['tipo'] === 'USADO') ? 'Usado' : 'Libre';
              ?>
              <div class="small text-muted mb-1">Último registro de consumo</div>
              <div class="hdd-consumo-ultimo">
                <div><?php echo cam_h($txtUlt); ?></div>
                <div><?php echo cam_h($tipoUlt); ?>: <strong><?php echo (int)$valorUlt; ?> GB</strong></div>
              </div>
            <?php else: ?>
              <div class="small text-muted">Sin registros de consumo todavía.</div>
            <?php endif; ?>

            <?php if ($puedeGestionarHdd): ?>
              <button
                type="button"
                class="btn btn-sm btn-outline-warning mt-2 btn-retirar-hdd"
                data-hdd-id="<?php echo (int)$hddIdActual; ?>"
                data-marca="<?php echo cam_h($hddActual['marca']); ?>"
                data-serie="<?php echo cam_h($hddActual['nro_serie']); ?>"
                data-capacidad="<?php echo (int)$capacidadGbActual; ?>"
              >
                Retirar HDD
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php if ($puedeGestionarHdd): ?>
      <div class="card hdd-card mb-3">
        <div class="card-body">
          <div class="hdd-title mb-2">Registro de consumo del día (espacio libre)</div>
          <form id="formHddConsumo" method="post" action="guardar_hdd_consumo.php">
            <input type="hidden" name="id_hdd" value="<?php echo (int)$hddIdActual; ?>">
            <input type="hidden" name="tipo" value="LIBRE">

            <div class="form-row">
              <div class="form-group col-md-4">
                <label>Fecha y hora</label>
                <input type="datetime-local" name="fecha" class="form-control"
                  value="<?php echo cam_h(date('Y-m-d\TH:i')); ?>" required>
              </div>
              <div class="form-group col-md-4">
                <label>Espacio libre</label>
                <div class="input-group">
                  <input type="number" class="form-control" name="valor" min="0" max="1000000" step="1" required>
                  <div class="input-group-append">
                    <select name="unidad" class="form-control">
                      <option value="GB">GB</option>
                      <option value="TB">TB</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="form-group col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-block">Guardar consumo</button>
              </div>
            </div>

            <div class="form-group mb-0">
              <label>Nota (opcional)</label>
              <input type="text" class="form-control" name="nota" maxlength="255">
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="card hdd-card mb-3">
      <div class="card-body hdd-consumo-list">
        <div class="hdd-title mb-2">Historial de consumo</div>
        <?php if (empty($consumos)): ?>
          <div class="text-muted small">No hay registros de consumo para este HDD.</div>
        <?php else: ?>
          <div class="table-responsive hdd-table-wrapper">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th style="width:25%;">Fecha y hora</th>
                  <th style="width:15%;">Tipo</th>
                  <th style="width:20%;">Valor</th>
                  <th style="width:40%;">Nota</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($consumos as $c): ?>
                  <?php
                  $fReg = new DateTime($c['fecha_registro']);
                  $txtF = $fReg->format('d/m/Y H:i');
                  $txtTipo = ($c['tipo'] === 'USADO') ? 'Usado' : 'Libre';
                  ?>
                  <tr>
                    <td><?php echo cam_h($txtF); ?></td>
                    <td><?php echo cam_h($txtTipo); ?></td>
                    <td><?php echo (int)$c['valor_gb']; ?> GB</td>
                    <td><?php echo cam_h($c['nota']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (is_array($chartValues) && count($chartValues) >= 2): ?>
      <div class="card hdd-card mb-3 hdd-chart-card">
        <div class="card-body">
          <div class="hdd-title mb-2">Gráfico de espacio libre</div>
          <canvas id="hddConsumoChart" width="400" height="160"
            data-values="<?php echo cam_h($chartValuesJson); ?>"></canvas>
          <div class="small text-muted mt-2">Eje Y: espacio libre en GB. Eje X: secuencia de registros.</div>
        </div>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <input type="hidden" id="hdd_actual_id" value="0">
    <div class="alert alert-secondary">Esta cámara no tiene un HDD instalado actualmente.</div>
  <?php endif; ?>

  <?php if ($puedeGestionarHdd): ?>
    <div class="card hdd-card mb-3">
      <div class="card-body">
        <div class="hdd-title mb-2"><?php echo $hddActual ? 'Actualizar datos del HDD instalado' : 'Instalar nuevo HDD'; ?></div>

        <form id="formHddBase" method="post" action="guardar_hdd.php">
          <input type="hidden" name="id_camara" value="<?php echo (int)$camara['id']; ?>">
          <input type="hidden" name="id_hdd" value="<?php echo (int)$hddIdActual; ?>">

          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Marca</label>
              <input type="text" class="form-control" name="marca" maxlength="100" required
                value="<?php echo cam_h($hddActual['marca'] ?? ''); ?>">
            </div>

            <div class="form-group col-md-4">
              <label>Nº de serie</label>
              <input type="text" class="form-control" name="nro_serie" maxlength="100" required
                value="<?php echo cam_h($hddActual['nro_serie'] ?? ''); ?>">
            </div>

            <div class="form-group col-md-4">
              <label>Capacidad</label>
              <?php
              $capValor = '';
              $capUnidad = 'GB';
              if ($capacidadGbActual > 0) {
                  if ($capacidadGbActual % 1024 === 0) { $capValor = (int)($capacidadGbActual / 1024); $capUnidad = 'TB'; }
                  else { $capValor = $capacidadGbActual; $capUnidad = 'GB'; }
              }
              ?>
              <div class="input-group">
                <input type="number" class="form-control" name="capacidad_valor" min="1" max="1000000" step="1" required
                  value="<?php echo cam_h($capValor); ?>">
                <div class="input-group-append">
                  <select name="capacidad_unidad" class="form-control">
                    <option value="GB" <?php echo ($capUnidad === 'GB') ? 'selected' : ''; ?>>GB</option>
                    <option value="TB" <?php echo ($capUnidad === 'TB') ? 'selected' : ''; ?>>TB</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <?php
          $valorFechaInst = ($hddActual && !empty($hddActual['fecha_instalacion']))
              ? (new DateTime($hddActual['fecha_instalacion']))->format('Y-m-d\TH:i')
              : date('Y-m-d\TH:i');
          ?>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Fecha instalación</label>
              <input type="datetime-local" class="form-control" name="fecha_instalacion" required
                value="<?php echo cam_h($valorFechaInst); ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Nota instalación (opcional)</label>
              <input type="text" class="form-control" name="nota_instalacion" maxlength="500"
                value="<?php echo cam_h($hddActual['nota_instalacion'] ?? ''); ?>">
            </div>
          </div>

          <div class="text-right">
            <button type="submit" class="btn btn-primary"><?php echo $hddActual ? 'Actualizar HDD' : 'Instalar HDD'; ?></button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
  <div class="card hdd-card mb-0">
    <div class="card-body hdd-retirados-list">
      <div class="hdd-title mb-2">Discos retirados de esta cámara</div>

      <?php if (empty($hddsRetirados)): ?>
        <div class="text-muted small">No hay discos retirados registrados para esta cámara.</div>
      <?php else: ?>
        <div class="table-responsive hdd-table-wrapper">
          <table class="table table-sm table-striped mb-0">
            <thead>
              <tr>
                <th>Marca</th>
                <th>Nº serie</th>
                <th>Capacidad</th>
                <th>Retirado el</th>
                <th>Responsable</th>
                <?php if ($puedeGestionarHdd): ?>
                  <th class="text-right">Acciones</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($hddsRetirados as $h): ?>
                <?php
                $capTxt = ((int)$h['capacidad_gb']) . ' GB';
                $fRet = (!empty($h['fecha_retiro'])) ? (new DateTime($h['fecha_retiro']))->format('d/m/Y H:i') : '—';

                $diasGrab = 0;
                if (!empty($h['fecha_inicio_grab']) && !empty($h['fecha_fin_grab'])) {
                    $fiG = new DateTime($h['fecha_inicio_grab']);
                    $ffG = new DateTime($h['fecha_fin_grab']);
                    $diasGrab = (int)$fiG->diff($ffG)->days + 1;
                }
                ?>
                <tr>
                  <td><?php echo cam_h($h['marca']); ?></td>
                  <td><?php echo cam_h($h['nro_serie']); ?></td>
                  <td><?php echo cam_h($capTxt); ?></td>
                  <td><?php echo cam_h($fRet); ?></td>
                  <td><?php echo cam_h($h['responsable_retiro']); ?></td>

                  <?php if ($puedeGestionarHdd): ?>
                    <td class="text-right">
                      <button type="button"
                        class="btn btn-xs btn-danger btn-eliminar-hdd"
                        data-hdd-id="<?php echo (int)$h['id']; ?>"
                        data-marca="<?php echo cam_h($h['marca']); ?>"
                        data-serie="<?php echo cam_h($h['nro_serie']); ?>"
                        data-dias-grab="<?php echo (int)$diasGrab; ?>">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>
