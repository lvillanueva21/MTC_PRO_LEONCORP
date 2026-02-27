<?php 
// /modules/certificados/formulario_certificado.php

require_once __DIR__ . '/funciones_formulario.php';

// $u viene de index.php (currentUser())
// Por seguridad, si no existe, lo intentamos cargar.
if (!isset($u) || !is_array($u)) {
    if (function_exists('currentUser')) {
        $u = currentUser();
    } else {
        $u = [];
    }
}

$idEmpresaActual = cf_resolver_id_empresa_actual($u);
$datosForm       = cf_cargar_datos_formulario($idEmpresaActual);

$cursos      = $datosForm['cursos'];
$plantillas  = $datosForm['plantillas'];
$tiposDoc    = $datosForm['tipos_doc'];
$categorias  = $datosForm['categorias'];
$sigCodigo   = $datosForm['siguiente_codigo'];

// Fecha de hoy en formato ISO para input type="date" (Lima ya seteada en conexion.php)
$hoyIso = date('Y-m-d');

// Helper mínimo de escape
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="cert-form">
  <div class="d-flex justify-content-between align-items-baseline mb-3">
    <h3 class="cert-form-title mb-0">Emitir certificado Fast</h3>
    <span class="cert-form-code" id="cert-proximo-codigo"><?php echo h($sigCodigo); ?></span>
  </div>

  <form id="form-certificado" autocomplete="off">
    <!-- Mensaje general de errores -->
    <div id="cert-form-error-global" style="display:none;color:#c00;font-size:13px;margin-bottom:8px;"></div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="curso">Curso</label>
        <select id="curso" name="curso" class="form-control">
          <option value="">Seleccione...</option>
          <?php foreach ($cursos as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>">
              <?php echo h($c['nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small id="error_curso" style="color:#c00;font-size:12px;"></small>
      </div>

      <div class="form-group col-md-6">
        <label for="tipo_certificado">Tipo Certificado</label>
        <select id="tipo_certificado" name="tipo_certificado" class="form-control">
          <option value="">Seleccione...</option>
          <?php foreach ($plantillas as $p): ?>
            <option value="<?php echo (int)$p['id']; ?>">
              <?php echo h($p['nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small id="error_tipo_certificado" style="color:#c00;font-size:12px;"></small>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="nombres">Nombres</label>
        <input type="text" id="nombres" name="nombres" class="form-control" maxlength="100">
        <small id="error_nombres" style="color:#c00;font-size:12px;"></small>
      </div>
      <div class="form-group col-md-6">
        <label for="apellidos">Apellidos</label>
        <input type="text" id="apellidos" name="apellidos" class="form-control" maxlength="100">
        <small id="error_apellidos" style="color:#c00;font-size:12px;"></small>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="tipo_doc">Tipo Doc.</label>
<select id="tipo_doc" name="tipo_doc" class="form-control">
  <?php
    // Si hay tipos de documento, seleccionamos el primero por defecto.
    // Si NO hay, mostramos el placeholder "Seleccione..."
    $primerTipoDocId = 0;
    if (is_array($tiposDoc) && count($tiposDoc) > 0 && isset($tiposDoc[0]['id'])) {
      $primerTipoDocId = (int)$tiposDoc[0]['id'];
    }
  ?>

  <?php if ($primerTipoDocId === 0): ?>
    <option value="">Seleccione...</option>
  <?php endif; ?>

  <?php foreach ($tiposDoc as $td): ?>
    <?php
      $idTd = (int)$td['id'];
      $selected = ($idTd === $primerTipoDocId) ? ' selected' : '';
    ?>
    <option value="<?php echo $idTd; ?>"<?php echo $selected; ?>>
      <?php echo h($td['codigo']); ?>
    </option>
  <?php endforeach; ?>
</select>
        <small id="error_tipo_doc" style="color:#c00;font-size:12px;"></small>
      </div>
      <div class="form-group col-md-6">
        <label for="caracteres_doc">Documento</label>
        <input type="text" id="caracteres_doc" name="caracteres_doc" class="form-control" maxlength="20">
        <small id="error_caracteres_doc" style="color:#c00;font-size:12px;"></small>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria" class="form-control">
          <option value="">Seleccione...</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>">
              <?php echo h($cat['codigo']); ?> (<?php echo h($cat['tipo_categoria']); ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <small id="error_categoria" style="color:#c00;font-size:12px;"></small>
      </div>
      <div class="form-group col-md-6">
        <label for="fecha_emision">Fecha Emisión</label>
        <input
          type="date"
          id="fecha_emision"
          name="fecha_emision"
          class="form-control"
          value="<?php echo h($hoyIso); ?>">
        <small id="error_fecha_emision" style="color:#c00;font-size:12px;"></small>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="fecha_inicio">Fecha Inicio</label>
        <input
          type="date"
          id="fecha_inicio"
          name="fecha_inicio"
          class="form-control"
          value="<?php echo h($hoyIso); ?>">
        <small id="error_fecha_inicio" style="color:#c00;font-size:12px;"></small>
      </div>
      <div class="form-group col-md-6">
        <label for="fecha_fin">Fecha Fin</label>
        <input
          type="date"
          id="fecha_fin"
          name="fecha_fin"
          class="form-control"
          value="<?php echo h($hoyIso); ?>">
        <small id="error_fecha_fin" style="color:#c00;font-size:12px;"></small>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="horas_teoricas">Horas Teóricas</label>
        <input
          type="number"
          id="horas_teoricas"
          name="horas_teoricas"
          class="form-control"
          min="0"
          max="100">
        <small id="error_horas_teoricas" style="color:#c00;font-size:12px;"></small>
      </div>
      <div class="form-group col-md-6">
        <label for="horas_practicas">Horas Prácticas</label>
        <input
          type="number"
          id="horas_practicas"
          name="horas_practicas"
          class="form-control"
          min="0"
          max="100">
        <small id="error_horas_practicas" style="color:#c00;font-size:12px;"></small>
      </div>
    </div>

    <div class="text-center mt-3">
      <button type="submit" class="btn btn-cert-generar">
        Generar certificado
      </button>
    </div>
  </form>
</div>

<!-- Modal de éxito / detalle del certificado -->
<div class="modal fade" id="certSuccessModal" tabindex="-1" role="dialog" aria-labelledby="certSuccessTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg cert-modal-dialog" role="document">
    <div class="modal-content cert-modal">
      <div class="modal-header cert-modal-header">
        <h5 class="modal-title" id="certSuccessTitle">Detalle del certificado</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body cert-modal-body" id="certSuccessBody">
        <!-- Aquí se inserta el resumen vía JS -->
      </div>
      <div class="modal-footer cert-modal-footer">
        <a href="#" id="cert-btn-descargar-qr" class="btn btn-outline-secondary btn-sm" style="display:none;">
          Descargar QR
        </a>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
// JS sencillo para manejar envío, validación básica y modal + QR

(function() {
  function limpiarErrores() {
    var campos = [
      'curso','tipo_certificado','nombres','apellidos',
      'tipo_doc','caracteres_doc','categoria',
      'fecha_emision','fecha_inicio','fecha_fin',
      'horas_teoricas','horas_practicas'
    ];

    var g = document.getElementById('cert-form-error-global');
    if (g) {
      g.style.display = 'none';
      g.textContent = '';
    }

    campos.forEach(function(c) {
      var el = document.getElementById('error_' + c);
      if (el) {
        el.textContent = '';
      }
    });
  }

  function mostrarErrores(errores) {
    if (!errores) return;

    var hayGlobal = false;
    var g = document.getElementById('cert-form-error-global');

    if (errores._global && g) {
      g.style.display = 'block';
      g.textContent = errores._global;
      hayGlobal = true;
    }

    Object.keys(errores).forEach(function(campo) {
      if (campo === '_global') return;
      var el = document.getElementById('error_' + campo);
      if (el) {
        el.textContent = errores[campo];
      } else if (!hayGlobal && g) {
        g.style.display = 'block';
        g.textContent = errores[campo];
        hayGlobal = true;
      }
    });
  }

        function abrirModalResumen(resumen) {
    var body = document.getElementById('certSuccessBody');
    if (!body) {
      return;
    }

    var html = '';
    var tokenParaDescarga = '';
    var codigoParaDescarga = '';

    if (!resumen || typeof resumen !== 'object') {
      html = '<p>El certificado fue creado correctamente.</p>';
    } else {
      function v(key) {
        if (resumen[key] === null || resumen[key] === undefined) {
          return '';
        }
        return String(resumen[key]);
      }

      var empresa     = v('empresa_nombre');
      var usuario     = v('usuario_nombre');
      var idBd        = v('id_bd');
      var creado      = v('creado');
      var actualizado = v('actualizado');

      var codigoCert  = v('codigo_certificado');
      var curso       = v('curso');
      var estado      = v('estado');
      var categoria   = v('categoria');
      var tipoDoc     = v('tipo_doc');
      var documento   = v('documento_cliente');
      var fechaEm     = v('fecha_emision');
      var fechaIni    = v('fecha_inicio');
      var fechaFin    = v('fecha_fin');
      var hTeor       = v('horas_teoricas');
      var hPrac       = v('horas_practicas');
      var nombreCli   = v('nombre_cliente');
      var tokenQr     = v('codigo_qr');

      tokenParaDescarga  = tokenQr;
      codigoParaDescarga = codigoCert;

      var estadoUpper = (estado || '').toUpperCase();
      var estadoClass = 'status-activo';
      if (estadoUpper === 'INACTIVO') {
        estadoClass = 'status-inactivo';
      } else if (estadoUpper === 'VENCIDO') {
        estadoClass = 'status-vencido';
      }

      var docLabel = '';
      if (tipoDoc && documento) {
        docLabel = tipoDoc + ': ' + documento;
      } else if (documento) {
        docLabel = documento;
      }

      var qrImgUrl  = '';
      var publicUrl = '';
      if (tokenQr) {
        qrImgUrl  = 'qr_imagen.php?token=' + encodeURIComponent(tokenQr);
        publicUrl = 'validar_certificado_publico.php?token=' + encodeURIComponent(tokenQr);
      }

      var hTeorNum = parseInt(hTeor, 10);
      if (isNaN(hTeorNum)) hTeorNum = 0;
      var hPracNum = parseInt(hPrac, 10);
      if (isNaN(hPracNum)) hPracNum = 0;
      var hTotal   = hTeorNum + hPracNum;

      // --- Tarjeta principal ---
      html += '<div class="cert-modal-card">';

      // CABECERA
      html += '  <div class="cert-modal-card-header">';
      html += '    <div class="cert-modal-title-row">';
      html += '      <h3 class="cert-modal-title">Certificado ' + (codigoCert || '') + '</h3>';
      html += '      <span class="badge-status cert-status-pill ' + estadoClass + '">' + (estado || '') + '</span>';
      html += '    </div>';
      html += '    <div class="cert-modal-meta-line">';
      html += '      <span><strong>Empresa:</strong> ' + (empresa || '') + '</span>';
      html += '      <span class="cert-modal-dot">&bull;</span>';
      html += '      <span><strong>Emitido a:</strong> ' + (nombreCli || '') + '</span>';
      html += '      <span class="cert-modal-dot">&bull;</span>';
      html += '      <span><strong>Id_bd:</strong> ' + (idBd || '') + '</span>';
      html += '    </div>';
      html += '  </div>';

      // CUERPO
      html += '  <div class="cert-modal-card-body">';

      // BLOQUE 1: Datos del curso
      html += '    <div class="cert-section">';
      html += '      <div class="cert-section-title">DATOS DEL CURSO</div>';
      html += '      <div class="row">';
      html += '        <div class="col-sm-6 cert-modal-col">';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Curso</div>';
      html += '            <div class="cert-field-value">' + (curso || '') + '</div>';
      html += '          </div>';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Categoría</div>';
      html += '            <div class="cert-field-value">' + (categoria || '—') + '</div>';
      html += '          </div>';
      html += '        </div>';
      html += '        <div class="col-sm-6 cert-modal-col">';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Nombre completo</div>';
      html += '            <div class="cert-field-value">' + (nombreCli || '') + '</div>';
      html += '          </div>';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Documento</div>';
      html += '            <div class="cert-field-value">' + (docLabel || '—') + '</div>';
      html += '          </div>';
      html += '        </div>';
      html += '      </div>';
      html += '    </div>';

      // BLOQUE 2: Fechas
      html += '    <div class="cert-section">';
      html += '      <div class="cert-section-title">FECHAS</div>';
      html += '      <div class="row">';
      html += '        <div class="col-sm-4 cert-modal-col">';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Fecha inicio</div>';
      html += '            <div class="cert-field-value">' + (fechaIni || '—') + '</div>';
      html += '          </div>';
      html += '        </div>';
      html += '        <div class="col-sm-4 cert-modal-col">';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Fecha de emisión</div>';
      html += '            <div class="cert-field-value">' + (fechaEm || '—') + '</div>';
      html += '          </div>';
      html += '        </div>';
      html += '        <div class="col-sm-4 cert-modal-col">';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Fecha fin</div>';
      html += '            <div class="cert-field-value">' + (fechaFin || '—') + '</div>';
      html += '          </div>';
      html += '        </div>';
      html += '      </div>';
      html += '    </div>';

      // BLOQUE 3: Carga horaria
      html += '    <div class="cert-section">';
      html += '      <div class="cert-section-title">CARGA HORARIA</div>';
      html += '      <div class="row">';
      html += '        <div class="col-sm-4 cert-modal-col">';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Horas teóricas</div>';
      html += '            <div class="cert-field-value">' + (hTeor || '0') + '</div>';
      html += '          </div>';
      html += '        </div>';
      html += '        <div class="col-sm-4 cert-modal-col">';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Horas prácticas</div>';
      html += '            <div class="cert-field-value">' + (hPrac || '0') + '</div>';
      html += '          </div>';
      html += '        </div>';
      html += '        <div class="col-sm-4 cert-modal-col">';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Total horas</div>';
      html += '            <div class="cert-field-value">' + hTotal + '</div>';
      html += '          </div>';
      html += '        </div>';
      html += '      </div>';
      html += '    </div>';

      // BLOQUE 4: QR + acceso público
      html += '    <div class="cert-section cert-section-qr">';
      html += '      <div class="cert-section-title">CÓDIGO QR Y ACCESO PÚBLICO</div>';
      html += '      <div class="row align-items-center">';
      html += '        <div class="col-md-4 text-center">';
      if (qrImgUrl) {
        html += '          <div class="cert-qr-box"><img src="' + qrImgUrl + '" alt="QR del certificado"></div>';
      } else {
        html += '          <div class="cert-field-value">QR no disponible.</div>';
      }
      html += '        </div>';
      html += '        <div class="col-md-8">';
      html += '          <div class="cert-field">';
      html += '            <div class="cert-field-label">Instrucciones</div>';
      if (publicUrl) {
        html += '            <div class="cert-field-value">Escanee el código con la cámara de su dispositivo o use el siguiente enlace:<br><a href="' + publicUrl + '" target="_blank" rel="noopener">Ver certificado público</a></div>';
      } else {
        html += '            <div class="cert-field-value">El enlace público no está disponible.</div>';
      }
      html += '          </div>';
      html += '        </div>';
      html += '      </div>';
      html += '    </div>';

      html += '  </div>'; // fin card-body

      // Pie de auditoría
      html += '  <div class="cert-modal-card-footer-meta">';
      html += '    <span><strong>Creado:</strong> ' + (creado || '') + '</span>';
      html += '    <span><strong>Actualizado:</strong> ' + (actualizado || '') + '</span>';
      html += '    <span><strong>Emitido por:</strong> ' + (usuario || '') + '</span>';
      html += '  </div>';

      html += '</div>'; // fin tarjeta
    }

    body.innerHTML = html;

    // Botón "Descargar QR"
    var btnDesc = document.getElementById('cert-btn-descargar-qr');
    if (btnDesc) {
      if (tokenParaDescarga) {
        var href = 'qr_imagen.php?token=' + encodeURIComponent(tokenParaDescarga);
        btnDesc.href = href;
        var nombreArchivo = 'qr-certificado-' + (codigoParaDescarga || 'codigo') + '.png';
        btnDesc.setAttribute('download', nombreArchivo);
        btnDesc.style.display = '';
      } else {
        btnDesc.removeAttribute('href');
        btnDesc.removeAttribute('download');
        btnDesc.style.display = 'none';
      }
    }

    if (window.jQuery && typeof jQuery.fn.modal === 'function') {
      jQuery('#certSuccessModal').modal('show');
    } else {
      var modal = document.getElementById('certSuccessModal');
      if (modal) {
        modal.style.display = 'block';
      }
    }
  }

  // Exponer la función para reutilizar el mismo diseño desde otros scripts
  window.abrirModalResumen = abrirModalResumen;
  // Exponer la función para reutilizarla desde el listado
  window.abrirModalDetalleCertificado = abrirModalResumen;

  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('form-certificado');
    if (!form) return;

    // Forzar mayúsculas en nombres, apellidos y documento
    ['nombres', 'apellidos', 'caracteres_doc'].forEach(function(id) {
      var el = document.getElementById(id);
      if (!el) {
        return;
      }
      el.style.textTransform = 'uppercase';
      el.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
      });
    });

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      limpiarErrores();

      var fd = new FormData(form);

      fetch('guardar_certificado.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data || typeof data !== 'object') {
          mostrarErrores({_global: 'Respuesta inesperada del servidor.'});
          return;
        }

        if (!data.ok) {
          mostrarErrores(data.errores || {_global: 'Hay errores en el formulario.'});
          return;
        }

        if (data.siguiente_codigo) {
          var lbl = document.getElementById('cert-proximo-codigo');
          if (lbl) {
            lbl.textContent = data.siguiente_codigo;
          }
        }

        abrirModalResumen(data.resumen || null);

        // Recargar listado de certificados (derecha) si la función global existe
        if (typeof window.recargarTablaCertificados === 'function') {
          window.recargarTablaCertificados(1);
        }

        // Limpiar formulario y restaurar fechas a hoy
        form.reset();
        var hoy = '<?php echo h($hoyIso); ?>';
        var fe = document.getElementById('fecha_emision');
        var fi = document.getElementById('fecha_inicio');
        var ff = document.getElementById('fecha_fin');
        if (fe) fe.value = hoy;
        if (fi) fi.value = hoy;
        if (ff) ff.value = hoy;
      })
      .catch(function(err) {
        console.error(err);
        mostrarErrores({_global: 'Error de comunicación con el servidor.'});
      });
    });
  });
})();
</script>
