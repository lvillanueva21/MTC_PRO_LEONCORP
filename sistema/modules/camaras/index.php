<?php
// modules/camaras/index.php
require_once __DIR__ . '/_bootstrap.php';

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Solo Gerente y Desarrollo pueden crear/editar/eliminar cámaras
$puedeGestionarCamaras  = ($esDesarrollo || $esGerente);
$puedeVerUsuariosCamara = $puedeGestionarCamaras;

// Empresa actual (para cabecera principal)
$empresaNombre   = isset($u['empresa']['nombre']) ? (string)$u['empresa']['nombre'] : '';
$empresaLogoPath = isset($u['empresa']['logo_path']) ? (string)$u['empresa']['logo_path'] : '';

if ($empresaActualId > 0 && ($empresaNombre === '' || $empresaLogoPath === '')) {
    $sqlEmp = "
        SELECT nombre, logo_path
        FROM mtp_empresas
        WHERE id = ?
        LIMIT 1
    ";
    $stmtEmp = mysqli_prepare($cn, $sqlEmp);
    if ($stmtEmp) {
        mysqli_stmt_bind_param($stmtEmp, 'i', $empresaActualId);
        mysqli_stmt_execute($stmtEmp);
        mysqli_stmt_bind_result($stmtEmp, $empNombre, $empLogo);
        if (mysqli_stmt_fetch($stmtEmp)) {
            if ($empresaNombre === '') {
                $empresaNombre = (string)$empNombre;
            }
            if ($empresaLogoPath === '') {
                $empresaLogoPath = (string)$empLogo;
            }
        }
        mysqli_stmt_close($stmtEmp);
    }
}

$empresaActual = array(
    'id'        => $empresaActualId,
    'nombre'    => $empresaNombre,
    'logo_path' => $empresaLogoPath
);

// Mensajes por GET (fallback sin JS)
$mensaje  = isset($_GET['msg']) ? trim($_GET['msg']) : '';
$hayError = (isset($_GET['e']) && $_GET['e'] == '1');

include __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" href="camaras.css">
<div class="content-wrapper camscape">
  <div class="camscape-bg"></div>
  <div class="camscape-overlay"></div>

  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-between">
        <h1 class="m-0 text-white">Cámaras</h1>
        <ol class="breadcrumb float-sm-right m-0">
          <li class="breadcrumb-item">
            <a class="text-white-50" href="<?php echo htmlspecialchars(BASE_URL . '/inicio.php', ENT_QUOTES, 'UTF-8'); ?>">Inicio</a>
          </li>
          <li class="breadcrumb-item active text-white">Cámaras</li>
        </ol>
      </div>
    </div>
  </div>

  <section class="content pb-4">
    <div class="container-fluid">

      <!-- Mensajes (éxito/error) -->
      <div id="cam-messages">
        <?php if ($mensaje !== ''): ?>
          <div class="alert alert-<?php echo $hayError ? 'danger' : 'success'; ?> alert-dismissible fade show cam-alert" role="alert">
            <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Cabecera principal (logo circular, nombre, hora, selector) -->
      <div class="cams-card mx-auto">
        <div class="cams-card-inner">
          <div class="cams-brand d-flex align-items-center">
            <div class="cams-logo-wrap">
              <?php if (!empty($empresaActual['logo_path'])): ?>
                <img
                  class="cams-logo-img"
                  src="<?php echo htmlspecialchars(BASE_URL . '/' . ltrim($empresaActual['logo_path'], '/'), ENT_QUOTES, 'UTF-8'); ?>"
                  alt="<?php echo htmlspecialchars($empresaActual['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                >
              <?php else: ?>
                <span class="cams-logo-placeholder">
                  <i class="fas fa-video"></i>
                </span>
              <?php endif; ?>
            </div>
            <div class="cams-brand-text">
              <?php
              $nombreEmpresa = ($empresaActual['nombre'] !== '')
                  ? $empresaActual['nombre']
                  : 'Empresa sin asignar';
              ?>
              <div class="cams-company">
                <?php echo htmlspecialchars($nombreEmpresa, ENT_QUOTES, 'UTF-8'); ?>
              </div>
              <div class="cams-subtitle">
                Sistema de Videovigilancia
              </div>
            </div>
          </div>

          <div class="cams-meta text-right">
            <div id="clock" class="cams-clock">Cargando hora de Lima...</div>
            <div class="cams-mode-selector mt-1">
              <label for="modo-acceso" class="mb-0 mr-1">Modo de acceso:</label>
              <select id="modo-acceso" class="form-control form-control-sm d-inline-block" style="width: auto;">
  <option value="remoto">Acceso remoto</option>
  <option value="local">Acceso local</option>
  <?php if ($puedeVerUsuariosCamara): ?>
    <option value="usuarios">Usuarios</option>
  <?php endif; ?>
</select>
            </div>
          </div>
        </div>
      </div>

      <!-- Contenedor donde se carga el grid (PHP + AJAX) -->
      <div id="cams-grid-wrapper">
        <?php include __DIR__ . '/listar_camaras.php'; ?>
      </div>

    </div>
  </section>

  <?php if ($puedeGestionarCamaras): ?>
    <!-- Botón flotante para nueva cámara -->
    <button
      type="button"
      id="btnNuevaCamara"
      class="btn btn-primary btn-lg cams-fab"
    >
      <i class="fas fa-plus"></i>
    </button>

    <!-- Modal alta/edición de cámara -->
    <div class="modal fade" id="modalNuevaCamara" tabindex="-1" role="dialog" aria-labelledby="modalNuevaCamaraLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <form method="post" action="guardar_camara.php" id="formCamara">
            <input type="hidden" name="cam_id" id="cam_id" value="">
            <div class="modal-header">
              <h5 class="modal-title" id="modalNuevaCamaraLabel">Nueva cámara / DVR</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">

              <div class="form-group">
                <label for="empresa_id">Empresa</label>
                <select name="empresa_id" id="empresa_id" class="form-control" required>
                  <option value="">Seleccione empresa</option>
                  <?php
                  $empresas = array();
                  $sqlEmpAll = "
                      SELECT id, nombre
                      FROM mtp_empresas
                      ORDER BY nombre
                  ";
                  $resEmpAll = mysqli_query($cn, $sqlEmpAll);
                  if ($resEmpAll) {
                      while ($row = mysqli_fetch_assoc($resEmpAll)) {
                          $empresas[] = $row;
                      }
                      mysqli_free_result($resEmpAll);
                  }
                  foreach ($empresas as $emp):
                  ?>
                    <option
                      value="<?php echo (int)$emp['id']; ?>"
                      <?php echo ($empresaActualId == (int)$emp['id']) ? 'selected' : ''; ?>
                    >
                      <?php echo htmlspecialchars($emp['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="nombre">Nombre de cámara</label>
                <input
                  type="text"
                  name="nombre"
                  id="nombre"
                  class="form-control"
                  maxlength="100"
                  required
                >
              </div>

              <div class="form-group">
                <label for="link_externo">Link externo (acceso remoto)</label>
                <input
                  type="url"
                  name="link_externo"
                  id="link_externo"
                  class="form-control"
                  placeholder="http://ejemplo.dvrdns.org:2000"
                >
              </div>

              <div class="form-group">
                <label for="link_local">Link local (acceso local)</label>
                <input
                  type="url"
                  name="link_local"
                  id="link_local"
                  class="form-control"
                  placeholder="http://192.168.1.100:2000"
                >
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="color_bg">Color de fondo del botón</label>
                  <input
                    type="color"
                    name="color_bg"
                    id="color_bg"
                    class="form-control"
                    value="#000000"
                    required
                  >
                </div>
                <div class="form-group col-md-6">
                  <label for="color_text">Color de texto del botón</label>
                  <input
                    type="color"
                    name="color_text"
                    id="color_text"
                    class="form-control"
                    value="#ffffff"
                    required
                  >
                </div>
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              <button type="submit" class="btn btn-primary" id="btnSubmitCamara">Guardar cámara</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal eliminación -->
    <div class="modal fade" id="modalEliminarCamara" tabindex="-1" role="dialog" aria-labelledby="modalEliminarCamaraLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <form method="post" action="eliminar_camara.php" id="formEliminarCamara">
            <input type="hidden" name="cam_id" id="del_cam_id" value="">
            <div class="modal-header">
              <h5 class="modal-title" id="modalEliminarCamaraLabel">Eliminar cámara</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>
                ¿Estás seguro de eliminar la cámara
                <strong id="deleteCamaraNombre"></strong>?
              </p>
              <p class="mb-0 text-danger">
                Esta acción no se puede deshacer.
              </p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
    <!-- Modal control de usuarios de cámara (visible para todos los roles que acceden al módulo) -->
  <div class="modal fade" id="modalUsuariosCamara" tabindex="-1" role="dialog" aria-labelledby="modalUsuariosCamaraLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalUsuariosCamaraLabel">
            Control de usuarios — <span id="usuariosCamaraNombre"></span>
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div id="usuarios-listado-wrapper">
            <!-- Se cargará vía AJAX -->
          </div>

          <?php if ($puedeGestionarCamaras): ?>
            <hr>
            <form method="post" action="guardar_usuario_camara.php" id="formUsuarioCamara">
              <input type="hidden" name="id_camara" id="usr_id_camara" value="">
              <input type="hidden" name="id_usuario_camara" id="usr_id_usuario_camara" value="">

              <div class="form-row">
                <div class="form-group col-md-4">
                  <label for="usr_usuario">Usuario</label>
                  <input
                    type="text"
                    class="form-control"
                    name="usuario"
                    id="usr_usuario"
                    maxlength="100"
                    required
                  >
                </div>
                <div class="form-group col-md-4">
                  <label for="usr_contrasena">Contraseña</label>
                  <input
                    type="text"
                    class="form-control"
                    name="contrasena"
                    id="usr_contrasena"
                    maxlength="255"
                    required
                  >
                </div>
                <div class="form-group col-md-4">
                  <label for="usr_nota">Nota</label>
                  <input
                    type="text"
                    class="form-control"
                    name="nota"
                    id="usr_nota"
                    maxlength="255"
                  >
                </div>
              </div>
            </form>
          <?php endif; ?>
        </div>

        <div class="modal-footer">
          <?php if ($puedeGestionarCamaras): ?>
            <button type="submit" form="formUsuarioCamara" class="btn btn-primary" id="btnGuardarUsuarioCamara">
              Guardar usuario
            </button>
          <?php endif; ?>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
    <!-- Modal gestión de HDD -->
  <div class="modal fade" id="modalHddCamara" tabindex="-1" role="dialog" aria-labelledby="modalHddCamaraLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalHddCamaraLabel">
            Gestión de HDD — <span id="hddCamaraNombre"></span>
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="hdd-modal-body">
          <!-- Se carga vía AJAX desde hdd_camara_detalle.php -->
          <div class="text-muted">Cargando información de HDD...</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal retiro de HDD -->
  <div class="modal fade" id="modalRetiroHdd" tabindex="-1" role="dialog" aria-labelledby="modalRetiroHddLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form method="post" action="retirar_hdd.php" id="formRetiroHdd">
          <input type="hidden" name="id_hdd" id="ret_id_hdd" value="">
          <div class="modal-header bg-warning">
            <h5 class="modal-title" id="modalRetiroHddLabel">Retiro de HDD</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p class="mb-2">
              Usted está retirando el HDD:
            </p>
            <p class="mb-2">
              <strong id="ret_hdd_marca_serie"></strong><br>
              Capacidad: <span id="ret_hdd_capacidad"></span>
            </p>
            <p class="small text-muted">
              Al retirar este HDD perderás la trazabilidad de los datos de grabación asociados a este disco dentro de este sistema
              (las grabaciones seguirán físicamente en el disco).
            </p>

            <hr>

            <div class="form-group">
              <label for="ret_responsable">Nombre de responsable</label>
              <input
                type="text"
                class="form-control"
                name="responsable"
                id="ret_responsable"
                maxlength="150"
                required
              >
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="ret_fecha_inicio">Fecha inicio (grabación)</label>
                <input
                  type="datetime-local"
                  class="form-control"
                  name="fecha_inicio_grab"
                  id="ret_fecha_inicio"
                  required
                >
              </div>
              <div class="form-group col-md-6">
                <label for="ret_fecha_fin">Fecha fin (grabación)</label>
                <input
                  type="datetime-local"
                  class="form-control"
                  name="fecha_fin_grab"
                  id="ret_fecha_fin"
                  required
                >
              </div>
            </div>

            <div class="form-group">
              <label for="ret_nota">Nota (opcional)</label>
              <textarea
                class="form-control"
                name="nota_retiro"
                id="ret_nota"
                rows="3"
                maxlength="500"
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning">Confirmar retiro</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal eliminación definitiva de HDD -->
  <div class="modal fade" id="modalEliminarHdd" tabindex="-1" role="dialog" aria-labelledby="modalEliminarHddLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form method="post" action="eliminar_hdd.php" id="formEliminarHdd">
          <input type="hidden" name="id_hdd" id="del_hdd_id" value="">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="modalEliminarHddLabel">Eliminar HDD</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>
              ¿Estás seguro de eliminar el HDD
              <strong id="del_hdd_marca_serie"></strong>?
            </p>
            <p class="text-danger mb-0">
              Perderás de forma permanente el historial de consumo y
              aproximadamente <strong id="del_hdd_dias"></strong> días de datos de grabación registrados en este sistema.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">Eliminar HDD</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

</div>
</div>
<script>
// Reloj de Lima (America/Lima = UTC-5)
function updateClock() {
  var now = new Date();
  var utc = now.getTime() + (now.getTimezoneOffset() * 60000);
  var limaOffset = -5;
  var limaTime = new Date(utc + (3600000 * limaOffset));

  var options = {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
  };
  var clockEl = document.getElementById('clock');
  if (clockEl) {
    clockEl.innerText = limaTime.toLocaleString('es-PE', options);
  }
}
updateClock();
setInterval(updateClock, 1000);

var empresaActualId = <?php echo (int)$empresaActualId; ?>;
var hddCamaraIdActual = 0;

window.addEventListener('load', function () {
  if (typeof $ === 'undefined') {
    return;
  }

  function autoDismissAlerts() {
    var $alerts = $('#cam-messages .cam-alert');
    if (!$alerts.length) {
      return;
    }
    setTimeout(function () {
      $alerts.fadeOut(600, function () {
        $(this).remove();
      });
    }, 4000);
  }

  function showMessage(type, text) {
    var $box = $('#cam-messages');
    if (!$box.length) {
      return;
    }
    var safe = $('<div>').text(text).html();
    var html = ''
      + '<div class="alert alert-' + type + ' alert-dismissible fade show cam-alert" role="alert">'
      + safe
      + '<button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">'
      + '<span aria-hidden="true">&times;</span>'
      + '</button>'
      + '</div>';
    $box.html(html);
    autoDismissAlerts();
  }

  function refreshGrid() {
    $('#cams-grid-wrapper').load('listar_camaras.php', function (response, status) {
      if (status === 'error') {
        showMessage('danger', 'Error al recargar las cámaras.');
      }
    });
  }

  function cargarUsuariosCamara(camId) {
    if (!camId) {
      return;
    }
    $('#usuarios-listado-wrapper').load('usuarios_camara.php?id_camara=' + camId, function (response, status) {
      if (status === 'error') {
        showMessage('danger', 'Error al cargar los usuarios de la cámara.');
      }
    });
  }

  // Gráfico simple de consumo HDD
  function simpleHddLineChart(canvas, values) {
    if (!canvas || !canvas.getContext) {
      return;
    }
    var ctx = canvas.getContext('2d');
    var width = canvas.width;
    var height = canvas.height;

    ctx.clearRect(0, 0, width, height);

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);

    var paddingLeft = 30;
    var paddingRight = 10;
    var paddingTop = 10;
    var paddingBottom = 20;

    var min = values[0];
    var max = values[0];
    for (var i = 1; i < values.length; i++) {
      if (values[i] < min) {
        min = values[i];
      }
      if (values[i] > max) {
        max = values[i];
      }
    }
    if (min === max) {
      min = min - 1;
      max = max + 1;
    }

    var plotWidth = width - paddingLeft - paddingRight;
    var plotHeight = height - paddingTop - paddingBottom;
    if (plotWidth <= 0 || plotHeight <= 0) {
      return;
    }

    var xStep = values.length > 1 ? plotWidth / (values.length - 1) : 0;

    function xPos(index) {
      if (values.length === 1) {
        return paddingLeft + plotWidth / 2;
      }
      return paddingLeft + xStep * index;
    }

    function yPos(value) {
      var ratio = (value - min) / (max - min);
      return paddingTop + (1 - ratio) * plotHeight;
    }

    // Ejes
    ctx.strokeStyle = '#d1d5db';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(paddingLeft, paddingTop);
    ctx.lineTo(paddingLeft, height - paddingBottom);
    ctx.lineTo(width - paddingRight, height - paddingBottom);
    ctx.stroke();

    // Etiquetas mín y máx
    ctx.fillStyle = '#6b7280';
    ctx.font = '10px sans-serif';
    ctx.textBaseline = 'bottom';
    ctx.fillText(String(Math.round(min)) + ' GB', 2, height - paddingBottom);
    ctx.textBaseline = 'top';
    ctx.fillText(String(Math.round(max)) + ' GB', 2, paddingTop);

    // Línea
    ctx.strokeStyle = '#2563eb';
    ctx.lineWidth = 2;
    ctx.beginPath();
    for (var j = 0; j < values.length; j++) {
      var x = xPos(j);
      var y = yPos(values[j]);
      if (j === 0) {
        ctx.moveTo(x, y);
      } else {
        ctx.lineTo(x, y);
      }
    }
    ctx.stroke();

    // Puntos
    ctx.fillStyle = '#1d4ed8';
    for (var k = 0; k < values.length; k++) {
      var px = xPos(k);
      var py = yPos(values[k]);
      ctx.beginPath();
      ctx.arc(px, py, 3, 0, Math.PI * 2, false);
      ctx.fill();
    }
  }

  function renderHddChart() {
    var canvas = document.getElementById('hddConsumoChart');
    if (!canvas) {
      return;
    }
    var valuesJson = canvas.getAttribute('data-values') || '[]';
    var values;
    try {
      values = JSON.parse(valuesJson);
    } catch (e) {
      values = [];
    }
    if (!values || !values.length) {
      return;
    }
    simpleHddLineChart(canvas, values);
  }

  function cargarHddCamara(camId) {
    if (!camId) {
      return;
    }
    hddCamaraIdActual = camId;
    $('#hdd-modal-body').load('hdd_camara_detalle.php?id_camara=' + camId, function (response, status) {
      if (status === 'error') {
        showMessage('danger', 'Error al cargar la información de HDD.');
      } else {
        renderHddChart();
      }
    });
  }

  autoDismissAlerts();

  // ---------- Comportamiento del botón de cámara según modo ----------
  $(document).on('click', '.cam-dynamic-btn', function (e) {
    e.preventDefault();
    var modo = $('#modo-acceso').val() || 'remoto';
    var $btn = $(this);
    var linkExterno = $btn.data('link-externo');
    var linkLocal   = $btn.data('link-local');
    var camId       = $btn.data('cam-id');
    var camNombre   = $btn.data('cam-nombre') || '';

    if (modo === 'remoto') {
      if (linkExterno) {
        window.open(linkExterno, '_blank');
      } else {
        alert('Esta cámara no tiene configurado acceso remoto.');
      }
    } else if (modo === 'local') {
      if (linkLocal) {
        window.open(linkLocal, '_blank');
      } else {
        alert('Esta cámara no tiene configurado acceso local.');
      }
    } else if (modo === 'usuarios') {
      if (!camId) {
        alert('No se pudo identificar la cámara seleccionada.');
        return;
      }

      $('#usuariosCamaraNombre').text(camNombre);
      var $formUsuario = $('#formUsuarioCamara');

      if ($formUsuario.length) {
        $formUsuario[0].reset();
        $('#usr_id_camara').val(String(camId));
        $('#usr_id_usuario_camara').val('');
      }

      $('#usuarios-listado-wrapper').html('<div class="text-muted">Cargando usuarios...</div>');
      cargarUsuariosCamara(camId);

      $('#modalUsuariosCamara').modal('show');
    }
  });

  // ---------- Botón HDD por cámara ----------
  $(document).on('click', '.btn-hdd-camara', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var camId = $btn.data('cam-id');
    var camNombre = $btn.data('cam-nombre') || '';

    if (!camId) {
      alert('No se pudo identificar la cámara.');
      return;
    }

    $('#hddCamaraNombre').text(camNombre);
    $('#hdd-modal-body').html('<div class="text-muted">Cargando información de HDD...</div>');
    $('#modalHddCamara').modal('show');
    cargarHddCamara(camId);
  });

  // ---------- Toggle opciones edición por departamento ----------
  $(document).on('click', '.cams-toggle-delete', function (e) {
    e.preventDefault();
    var $btn  = $(this);
    var $card = $btn.closest('.city-card');
    if (!$card.length) {
      return;
    }
    $card.toggleClass('show-delete');
  });

  // ---------- Gestión de cámaras ----------
  var $modalCamara   = $('#modalNuevaCamara');
  var $formCamara    = $('#formCamara');
  var $campoId       = $('#cam_id');
  var $campoEmpresa  = $('#empresa_id');
  var $campoNombre   = $('#nombre');
  var $campoExt      = $('#link_externo');
  var $campoLoc      = $('#link_local');
  var $campoBg       = $('#color_bg');
  var $campoText     = $('#color_text');
  var $tituloModal   = $('#modalNuevaCamaraLabel');
  var $btnSubmit     = $('#btnSubmitCamara');

  $('#btnNuevaCamara').on('click', function (e) {
    e.preventDefault();
    if (!$formCamara.length) {
      return;
    }
    $formCamara[0].reset();
    $campoId.val('');
    if (empresaActualId > 0 && $campoEmpresa.length) {
      $campoEmpresa.val(String(empresaActualId));
    }
    if ($campoBg.length) {
      $campoBg.val('#000000');
    }
    if ($campoText.length) {
      $campoText.val('#ffffff');
    }
    if ($tituloModal.length) {
      $tituloModal.text('Nueva cámara / DVR');
    }
    if ($btnSubmit.length) {
      $btnSubmit.text('Guardar cámara');
    }
    $modalCamara.modal('show');
  });

  $(document).on('click', '.cams-edit-btn', function (e) {
    e.preventDefault();
    var $btn = $(this);
    if (!$formCamara.length) {
      return;
    }

    $campoId.val($btn.data('id'));
    $campoEmpresa.val($btn.data('empresa-id'));
    $campoNombre.val($btn.data('nombre'));
    $campoExt.val($btn.data('link-externo'));
    $campoLoc.val($btn.data('link-local'));
    $campoBg.val($btn.data('color-bg'));
    $campoText.val($btn.data('color-text'));

    if ($tituloModal.length) {
      $tituloModal.text('Editar cámara / DVR');
    }
    if ($btnSubmit.length) {
      $btnSubmit.text('Actualizar cámara');
    }

    $modalCamara.modal('show');
  });

  if ($formCamara.length) {
    $formCamara.on('submit', function (e) {
      e.preventDefault();
      var formData = $formCamara.serialize();

      $.ajax({
        url: $formCamara.attr('action'),
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (resp) {
          if (resp && resp.ok) {
            $modalCamara.modal('hide');
            showMessage('success', resp.message || 'Operación realizada correctamente.');
            refreshGrid();
          } else {
            var msg = resp && resp.message ? resp.message : 'No se pudo guardar la cámara.';
            showMessage('danger', msg);
          }
        },
        error: function () {
          showMessage('danger', 'Error de comunicación con el servidor.');
        }
      });
    });
  }

  var $modalEliminar = $('#modalEliminarCamara');
  var $formEliminar  = $('#formEliminarCamara');
  var $delId         = $('#del_cam_id');
  var $delNombre     = $('#deleteCamaraNombre');

  $(document).on('click', '.cams-delete-btn', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id   = $btn.data('id');
    var nom  = $btn.data('nombre');

    $delId.val(id);
    $delNombre.text(nom);

    $modalEliminar.modal('show');
  });

  if ($formEliminar.length) {
    $formEliminar.on('submit', function (e) {
      e.preventDefault();
      var formData = $formEliminar.serialize();

      $.ajax({
        url: $formEliminar.attr('action'),
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (resp) {
          if (resp && resp.ok) {
            $modalEliminar.modal('hide');
            showMessage('success', resp.message || 'Cámara eliminada correctamente.');
            refreshGrid();
          } else {
            var msg = resp && resp.message ? resp.message : 'No se pudo eliminar la cámara.';
            showMessage('danger', msg);
          }
        },
        error: function () {
          showMessage('danger', 'Error de comunicación con el servidor.');
        }
      });
    });
  }

  // ---------- Control de usuarios de cámara ----------
  var $modalUsuarios = $('#modalUsuariosCamara');
  var $formUsuario   = $('#formUsuarioCamara');

  if ($formUsuario.length) {
    $formUsuario.on('submit', function (e) {
      e.preventDefault();
      var formData = $formUsuario.serialize();
      var camId    = $('#usr_id_camara').val();

      $.ajax({
        url: $formUsuario.attr('action'),
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (resp) {
          if (resp && resp.ok) {
            showMessage('success', resp.message || 'Usuario guardado correctamente.');
            $('#usr_id_usuario_camara').val('');
            cargarUsuariosCamara(camId);
          } else {
            var msg = resp && resp.message ? resp.message : 'No se pudo guardar el usuario.';
            showMessage('danger', msg);
          }
        },
        error: function () {
          showMessage('danger', 'Error de comunicación con el servidor.');
        }
      });
    });
  }

  $(document).on('click', '.btn-editar-usuario-camara', function (e) {
    e.preventDefault();
    if (!$formUsuario.length) {
      return;
    }
    var $btn  = $(this);
    var id    = $btn.data('id');
    var user  = $btn.data('usuario') || '';
    var pass  = $btn.data('contrasena') || '';
    var nota  = $btn.data('nota') || '';

    $('#usr_id_usuario_camara').val(String(id));
    $('#usr_usuario').val(user);
    $('#usr_contrasena').val(pass);
    $('#usr_nota').val(nota);
  });

  $(document).on('click', '.btn-eliminar-usuario-camara', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id   = $btn.data('id');
    var user = $btn.data('usuario') || '';
    var camId = $('#usr_id_camara').val();

    if (!id) {
      return;
    }
    if (!confirm('¿Eliminar el usuario "' + user + '" de esta cámara?')) {
      return;
    }

    $.ajax({
      url: 'eliminar_usuario_camara.php',
      type: 'POST',
      data: { id_usuario_camara: id },
      dataType: 'json',
      success: function (resp) {
        if (resp && resp.ok) {
          showMessage('success', resp.message || 'Usuario eliminado correctamente.');
          cargarUsuariosCamara(camId);
        } else {
          var msg = resp && resp.message ? resp.message : 'No se pudo eliminar el usuario.';
          showMessage('danger', msg);
        }
      },
      error: function () {
        showMessage('danger', 'Error de comunicación con el servidor.');
      }
    });
  });

  // ---------- Control de HDD ----------
  // Guardar/actualizar datos base de HDD
  $('#hdd-modal-body').on('submit', '#formHddBase', function (e) {
    e.preventDefault();
    var $form = $(this);
    var formData = $form.serialize();

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function (resp) {
        if (resp && resp.ok) {
          showMessage('success', resp.message || 'HDD guardado correctamente.');
          if (hddCamaraIdActual) {
            cargarHddCamara(hddCamaraIdActual);
            refreshGrid();
          }
        } else {
          var msg = resp && resp.message ? resp.message : 'No se pudo guardar el HDD.';
          showMessage('danger', msg);
        }
      },
      error: function () {
        showMessage('danger', 'Error de comunicación con el servidor.');
      }
    });
  });

  // Registrar consumo
  $('#hdd-modal-body').on('submit', '#formHddConsumo', function (e) {
    e.preventDefault();
    var $form = $(this);
    var formData = $form.serialize();

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function (resp) {
        if (resp && resp.ok) {
          showMessage('success', resp.message || 'Consumo del HDD registrado correctamente.');
          if (hddCamaraIdActual) {
            cargarHddCamara(hddCamaraIdActual);
            refreshGrid();
          }
        } else {
          var msg = resp && resp.message ? resp.message : 'No se pudo registrar el consumo.';
          showMessage('danger', msg);
        }
      },
      error: function () {
        showMessage('danger', 'Error de comunicación con el servidor.');
      }
    });
  });

  // Abrir modal de retiro
  $('#hdd-modal-body').on('click', '.btn-retirar-hdd', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id   = $btn.data('hdd-id');
    var marca = $btn.data('marca') || '';
    var serie = $btn.data('serie') || '';
    var capacidad = $btn.data('capacidad') || '';

    $('#ret_id_hdd').val(String(id));
    $('#ret_hdd_marca_serie').text(marca + ' — ' + serie);
    $('#ret_hdd_capacidad').text(capacidad ? (capacidad + ' GB') : '—');

    $('#ret_responsable').val('');
    $('#ret_fecha_inicio').val('');
    $('#ret_fecha_fin').val('');
    $('#ret_nota').val('');

    $('#modalRetiroHdd').modal('show');
  });

  // Confirmar retiro
  $('#formRetiroHdd').on('submit', function (e) {
    e.preventDefault();
    var $form = $(this);
    var formData = $form.serialize();

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function (resp) {
        if (resp && resp.ok) {
          $('#modalRetiroHdd').modal('hide');
          showMessage('success', resp.message || 'HDD retirado correctamente.');
          if (hddCamaraIdActual) {
            cargarHddCamara(hddCamaraIdActual);
            refreshGrid();
          }
        } else {
          var msg = resp && resp.message ? resp.message : 'No se pudo retirar el HDD.';
          showMessage('danger', msg);
        }
      },
      error: function () {
        showMessage('danger', 'Error de comunicación con el servidor.');
      }
    });
  });

  // Abrir modal de eliminación de HDD retirado
  $('#hdd-modal-body').on('click', '.btn-eliminar-hdd', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id   = $btn.data('hdd-id');
    var marca = $btn.data('marca') || '';
    var serie = $btn.data('serie') || '';
    var dias  = $btn.data('dias-grab') || 0;

    $('#del_hdd_id').val(String(id));
    $('#del_hdd_marca_serie').text(marca + ' — ' + serie);
    $('#del_hdd_dias').text(dias > 0 ? dias : '0');

    $('#modalEliminarHdd').modal('show');
  });

  // Confirmar eliminación HDD
  $('#formEliminarHdd').on('submit', function (e) {
    e.preventDefault();
    var $form = $(this);
    var formData = $form.serialize();

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function (resp) {
        if (resp && resp.ok) {
          $('#modalEliminarHdd').modal('hide');
          showMessage('success', resp.message || 'HDD eliminado correctamente.');
          if (hddCamaraIdActual) {
            cargarHddCamara(hddCamaraIdActual);
            refreshGrid();
          }
        } else {
          var msg = resp && resp.message ? resp.message : 'No se pudo eliminar el HDD.';
          showMessage('danger', msg);
        }
      },
      error: function () {
        showMessage('danger', 'Error de comunicación con el servidor.');
      }
    });
  });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
