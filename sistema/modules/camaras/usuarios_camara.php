<?php
// modules/camaras/usuarios_camara.php
require_once __DIR__ . '/_bootstrap.php';

function cam_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Para .load(): siempre 200 con HTML (evita freeze)
http_response_code(200);

// Solo Desarrollo y Gerente pueden ver / gestionar usuarios de cámara
if (!($esDesarrollo || $esGerente)) {
    echo '<div class="alert alert-danger mb-0">No tienes permiso para ver usuarios de esta cámara.</div>';
    return;
}

$idCamara = isset($_GET['id_camara']) ? (int)$_GET['id_camara'] : 0;
if ($idCamara <= 0) {
    echo '<div class="alert alert-danger mb-0">Cámara no válida.</div>';
    return;
}

try {
    // Datos cámara
    $sqlCam = "
        SELECT c.id, c.nombre, c.id_empresa, e.nombre AS empresa_nombre
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

    // Usuarios
    $usuarios = array();
    $sqlUsr = "
        SELECT id, usuario, contrasena, nota
        FROM cam_camaras_usuarios
        WHERE id_camara = ?
        ORDER BY usuario
    ";
    $stmtUsr = mysqli_prepare($cn, $sqlUsr);
    mysqli_stmt_bind_param($stmtUsr, 'i', $idCamara);
    mysqli_stmt_execute($stmtUsr);
    $resUsr = mysqli_stmt_get_result($stmtUsr);
    if ($resUsr) {
        while ($row = mysqli_fetch_assoc($resUsr)) $usuarios[] = $row;
        mysqli_free_result($resUsr);
    }
    mysqli_stmt_close($stmtUsr);

} catch (Throwable $e) {
    error_log('[CAMARAS] usuarios_camara.php: ' . $e->getMessage());
    echo '<div class="alert alert-danger mb-0">No se pudieron cargar los usuarios.</div>';
    if (!empty($esDesarrollo)) {
        echo '<div class="small text-muted mt-2" style="font-family:monospace;">' . cam_h($e->getMessage()) . '</div>';
    }
    return;
}
?>

<div class="mb-2">
  <strong>Cámara:</strong> <?php echo cam_h($camara['nombre']); ?>
  <?php if (!empty($camara['empresa_nombre'])): ?>
    <span class="text-muted">— <?php echo cam_h($camara['empresa_nombre']); ?></span>
  <?php endif; ?>
</div>

<?php if (empty($usuarios)): ?>
  <div class="alert alert-info mb-0">Esta cámara aún no tiene usuarios configurados.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-striped mb-0">
      <thead>
        <tr>
          <th style="width:25%;">Usuario</th>
          <th style="width:35%;">Contraseña</th>
          <th style="width:30%;">Nota</th>
          <th style="width:10%;" class="text-right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $uRow): ?>
          <tr>
            <td><?php echo cam_h($uRow['usuario']); ?></td>
            <td><?php echo cam_h($uRow['contrasena']); ?></td>
            <td><?php echo cam_h($uRow['nota']); ?></td>
            <td class="text-right">
              <button type="button"
                class="btn btn-xs btn-light btn-editar-usuario-camara"
                data-id="<?php echo (int)$uRow['id']; ?>"
                data-usuario="<?php echo cam_h($uRow['usuario']); ?>"
                data-contrasena="<?php echo cam_h($uRow['contrasena']); ?>"
                data-nota="<?php echo cam_h($uRow['nota']); ?>"
                title="Editar usuario">
                <i class="fas fa-edit"></i>
              </button>

              <button type="button"
                class="btn btn-xs btn-danger btn-eliminar-usuario-camara"
                data-id="<?php echo (int)$uRow['id']; ?>"
                data-usuario="<?php echo cam_h($uRow['usuario']); ?>"
                title="Eliminar usuario">
                <i class="fas fa-trash-alt"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
