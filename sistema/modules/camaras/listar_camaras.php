<?php
// modules/camaras/listar_camaras.php
require_once __DIR__ . '/_bootstrap.php';

function cam_h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Para .load() / includes: siempre responder HTML amigable (evita freeze)
http_response_code(200);

// Solo Desarrollo y Gerente pueden crear/editar/eliminar cámaras
$puedeGestionarCamaras = ($esDesarrollo || $esGerente);

$hoy = date('Y-m-d');

try {
    // SIN orden hardcodeado: orden estable alfabético por departamento y cámara
    $sql = "
        SELECT
            c.id,
            c.nombre,
            c.link_externo,
            c.link_local,
            c.color_bg,
            c.color_text,
            c.id_empresa,
            e.nombre      AS empresa_nombre,
            e.logo_path   AS empresa_logo,
            d.id          AS depa_id,
            d.nombre      AS depa_nombre,
            (
              SELECT COUNT(*)
              FROM cam_hdd h
              INNER JOIN cam_hdd_consumo hc ON hc.id_hdd = h.id
              WHERE h.id_camara = c.id
                AND h.estado = 'INSTALADO'
                AND hc.fecha_dia = ?
                AND hc.tipo = 'LIBRE'
            ) AS consumo_hoy
        FROM cam_camaras c
        INNER JOIN mtp_empresas e        ON e.id = c.id_empresa
        INNER JOIN mtp_departamentos d   ON d.id = e.id_depa
    ";

    if ($esAdmin || $esRecepcion) {
        $sql .= " WHERE c.id_empresa = ?";
    }

    $sql .= " ORDER BY d.nombre ASC, c.nombre ASC";

    $stmt = mysqli_prepare($cn, $sql);
    if (!$stmt) {
        echo '<div class="alert alert-danger mb-0">No se pudieron cargar las cámaras.</div>';
        return;
    }

    if ($esAdmin || $esRecepcion) {
        mysqli_stmt_bind_param($stmt, 'si', $hoy, $empresaActualId);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $hoy);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $grupos = array();

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $depId = isset($row['depa_id']) ? (int)$row['depa_id'] : 0;
            if (!isset($grupos[$depId])) {
                $grupos[$depId] = array(
                    'depa_id'     => $depId,
                    'depa_nombre' => (string)($row['depa_nombre'] ?? 'Sin departamento'),
                    'camaras'     => array(),
                );
            }
            $grupos[$depId]['camaras'][] = $row;
        }
        mysqli_free_result($res);
    }

    mysqli_stmt_close($stmt);

    if (empty($grupos)) {
        echo '<div class="alert alert-info mb-0">No hay cámaras registradas para mostrar.</div>';
        return;
    }

} catch (Throwable $e) {
    error_log('[CAMARAS] listar_camaras.php: ' . $e->getMessage());

    echo '<div class="alert alert-danger mb-0">';
    echo 'No se pudieron cargar las cámaras en este momento.';
    if (!empty($esDesarrollo)) {
        echo '<div class="small text-muted mt-2" style="font-family:monospace;">' . cam_h($e->getMessage()) . '</div>';
    }
    echo '</div>';
    return;
}
?>

<div class="cams-grid">
<?php foreach ($grupos as $grupo): ?>
  <div class="city-card">
    <h2 class="city-title text-center">
      <span class="mr-1">📷</span>
      <?php echo cam_h($grupo['depa_nombre']); ?>
      <button
        type="button"
        class="btn btn-xs btn-outline-secondary float-right cams-toggle-delete"
        title="Opciones de cámara"
      >
        <i class="fas fa-cog"></i>
      </button>
    </h2>

    <div class="city-links">
      <?php foreach ($grupo['camaras'] as $cam): ?>
        <?php
        $tieneHoy = !empty($cam['consumo_hoy']);
        $hddClass = $tieneHoy ? 'btn-hdd-camara btn-hdd-done-today' : 'btn-hdd-camara';
        ?>
        <div class="cam-row">
          <!-- Botón HDD -->
          <button
            type="button"
            class="btn btn-sm btn-light mr-1 <?php echo cam_h($hddClass); ?>"
            data-cam-id="<?php echo (int)$cam['id']; ?>"
            data-cam-nombre="<?php echo cam_h($cam['nombre']); ?>"
            title="<?php echo $tieneHoy ? 'Consumo de hoy registrado' : 'Gestionar HDD de esta cámara'; ?>"
          >
            <i class="fas fa-hdd"></i>
          </button>

          <!-- Botón principal de cámara -->
          <button
            type="button"
            class="cam cam-dynamic-btn"
            data-cam-id="<?php echo (int)$cam['id']; ?>"
            data-cam-nombre="<?php echo cam_h($cam['nombre']); ?>"
            data-link-externo="<?php echo cam_h($cam['link_externo']); ?>"
            data-link-local="<?php echo cam_h($cam['link_local']); ?>"
            style="background: <?php echo cam_h($cam['color_bg']); ?>; color: <?php echo cam_h($cam['color_text']); ?>;"
          >
            <?php if (!empty($cam['empresa_logo'])): ?>
              <img
                class="cam-logo-mini"
                src="<?php echo cam_h(BASE_URL . '/' . ltrim($cam['empresa_logo'], '/')); ?>"
                alt=""
              >
            <?php endif; ?>
            <?php echo cam_h($cam['nombre']); ?>
          </button>

          <?php if ($puedeGestionarCamaras): ?>
            <button
              type="button"
              class="btn btn-xs btn-outline-secondary ml-1 cams-edit-btn cams-edit-hidden"
              data-id="<?php echo (int)$cam['id']; ?>"
              data-empresa-id="<?php echo (int)$cam['id_empresa']; ?>"
              data-nombre="<?php echo cam_h($cam['nombre']); ?>"
              data-link-externo="<?php echo cam_h($cam['link_externo']); ?>"
              data-link-local="<?php echo cam_h($cam['link_local']); ?>"
              data-color-bg="<?php echo cam_h($cam['color_bg']); ?>"
              data-color-text="<?php echo cam_h($cam['color_text']); ?>"
              title="Editar cámara"
            >
              <i class="fas fa-edit"></i>
            </button>

            <button
              type="button"
              class="btn btn-xs btn-outline-danger ml-1 cams-delete-btn cams-edit-hidden"
              data-id="<?php echo (int)$cam['id']; ?>"
              data-nombre="<?php echo cam_h($cam['nombre']); ?>"
              title="Eliminar cámara"
            >
              <i class="fas fa-trash-alt"></i>
            </button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
