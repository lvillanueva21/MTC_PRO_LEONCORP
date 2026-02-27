<?php
// /modules/certificados/certificados_emitidos.php

require_once __DIR__ . '/funciones_formulario.php';

// $u viene desde index.php; por seguridad lo verificamos
if (!isset($u) || !is_array($u)) {
    if (function_exists('currentUser')) {
        $u = currentUser();
    } else {
        $u = [];
    }
}

$idEmpresaActual = cf_resolver_id_empresa_actual($u);
$cursosFiltro    = cf_cargar_cursos_activos();

// Helper de escape por si no existe
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="cert-list">
  <h3 class="cert-list-title">Certificados emitidos</h3>

  <!-- FILTROS SUPERIORES -->
  <div class="cert-filters">
    <div class="form-row">
      <div class="form-group col-md-3">
        <label for="filtro_fecha_inicio">Fecha Inicio</label>
        <input
          type="date"
          id="filtro_fecha_inicio"
          name="filtro_fecha_inicio"
          class="form-control">
      </div>
      <div class="form-group col-md-3">
        <label for="filtro_fecha_fin">Fecha Fin</label>
        <input
          type="date"
          id="filtro_fecha_fin"
          name="filtro_fecha_fin"
          class="form-control">
      </div>
      <div class="form-group col-md-3">
        <label for="filtro_estado">Estado</label>
        <select id="filtro_estado" name="filtro_estado" class="form-control">
          <option value="">Todos</option>
          <option value="Activo">Activo</option>
          <option value="Inactivo">Inactivo</option>
          <option value="Vencido">Vencido</option>
        </select>
      </div>
      <div class="form-group col-md-3">
        <label for="filtro_curso">Curso</label>
        <select id="filtro_curso" name="filtro_curso" class="form-control">
          <option value="">Todos</option>
          <?php foreach ($cursosFiltro as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>">
              <?php echo h($c['nombre']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group col-md-9">
        <label for="filtro_busqueda">Nombre o documento</label>
        <input
          type="text"
          id="filtro_busqueda"
          name="filtro_busqueda"
          class="form-control"
          placeholder="Escribe un nombre o documento para buscar">
      </div>
      <div class="form-group col-md-3">
        <label>&nbsp;</label>
        <button
          type="button"
          id="filtro_limpiar_btn"
          class="btn btn-block btn-secondary">
          <i class="fas fa-broom"></i> Limpiar filtros
        </button>
      </div>
    </div>
  </div>

  <!-- TABLA -->
  <table class="table-cert">
    <thead>
      <tr>
        <th>Código</th>
        <th>Nombre</th>
        <th>Documento</th>
        <th>Curso</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody id="tabla-certificados-body">
      <tr>
        <td colspan="5">Cargando certificados</td>
      </tr>
    </tbody>
  </table>

  <!-- PAGINACIÓN -->
  <div class="cert-pagination" id="tabla-certificados-paginacion">
    <!-- Aquí se inyecta la paginación vía JS -->
  </div>
</div>

<!-- Modal de edición de certificado -->
<div class="modal fade" id="certEditModal" tabindex="-1" role="dialog" aria-labelledby="certEditTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg cert-modal-dialog" role="document">
    <div class="modal-content cert-modal">
      <div class="modal-header cert-modal-header">
        <h5 class="modal-title" id="certEditTitle">Editar certificado</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body cert-modal-body">
        <form id="form-cert-edit" autocomplete="off">
          <input type="hidden" id="edit_id_certificado" name="id_certificado">

          <div id="cert-edit-error-global" style="display:none;color:#c00;font-size:13px;margin-bottom:8px;"></div>

          <div class="cert-modal-card">
            <div class="cert-modal-card-header">
              <div class="cert-modal-title-row">
                <h3 class="cert-modal-title" id="cert-edit-title-code">Certificado</h3>
                <span class="badge-status cert-status-pill" id="cert-edit-status-pill"></span>
              </div>
              <div class="cert-modal-meta-line" id="cert-edit-meta-line"></div>
            </div>

            <div class="cert-modal-card-body">
              <!-- Sección 1: Datos del curso y alumno -->
              <div class="cert-section">
                <div class="cert-section-title">Datos del curso y alumno</div>
                <div class="row">
                  <div class="col-sm-6 cert-modal-col">
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_curso">Curso</label>
                      <select id="edit_curso" name="curso" class="form-control"></select>
                      <small id="edit_error_curso" style="color:#c00;font-size:12px;"></small>
                    </div>
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_tipo_certificado">Tipo certificado</label>
                      <select id="edit_tipo_certificado" name="tipo_certificado" class="form-control"></select>
                      <small id="edit_error_tipo_certificado" style="color:#c00;font-size:12px;"></small>
                    </div>
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_categoria">Categoría</label>
                      <select id="edit_categoria" name="categoria" class="form-control"></select>
                      <small id="edit_error_categoria" style="color:#c00;font-size:12px;"></small>
                    </div>
                  </div>
                  <div class="col-sm-6 cert-modal-col">
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_nombres">Nombres</label>
                      <input type="text" id="edit_nombres" name="nombres" class="form-control" maxlength="100">
                      <small id="edit_error_nombres" style="color:#c00;font-size:12px;"></small>
                    </div>
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_apellidos">Apellidos</label>
                      <input type="text" id="edit_apellidos" name="apellidos" class="form-control" maxlength="100">
                      <small id="edit_error_apellidos" style="color:#c00;font-size:12px;"></small>
                    </div>
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_tipo_doc">Tipo doc.</label>
                      <select id="edit_tipo_doc" name="tipo_doc" class="form-control"></select>
                      <small id="edit_error_tipo_doc" style="color:#c00;font-size:12px;"></small>
                    </div>
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_documento">Documento</label>
                      <input type="text" id="edit_documento" name="caracteres_doc" class="form-control" maxlength="20">
                      <small id="edit_error_caracteres_doc" style="color:#c00;font-size:12px;"></small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Sección 2: Fechas y horas -->
              <div class="cert-section">
                <div class="cert-section-title">Fechas y carga horaria</div>
                <div class="row">
                  <div class="col-sm-4 cert-modal-col">
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_fecha_inicio">Fecha inicio</label>
                      <input type="date" id="edit_fecha_inicio" name="fecha_inicio" class="form-control">
                      <small id="edit_error_fecha_inicio" style="color:#c00;font-size:12px;"></small>
                    </div>
                  </div>
                  <div class="col-sm-4 cert-modal-col">
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_fecha_emision">Fecha emisión</label>
                      <input type="date" id="edit_fecha_emision" name="fecha_emision" class="form-control">
                      <small id="edit_error_fecha_emision" style="color:#c00;font-size:12px;"></small>
                    </div>
                  </div>
                  <div class="col-sm-4 cert-modal-col">
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_fecha_fin">Fecha fin</label>
                      <input type="date" id="edit_fecha_fin" name="fecha_fin" class="form-control">
                      <small id="edit_error_fecha_fin" style="color:#c00;font-size:12px;"></small>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm-4 cert-modal-col">
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_horas_teoricas">Horas teóricas</label>
                      <input type="number" id="edit_horas_teoricas" name="horas_teoricas" class="form-control" min="0" max="100">
                      <small id="edit_error_horas_teoricas" style="color:#c00;font-size:12px;"></small>
                    </div>
                  </div>
                  <div class="col-sm-4 cert-modal-col">
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_horas_practicas">Horas prácticas</label>
                      <input type="number" id="edit_horas_practicas" name="horas_practicas" class="form-control" min="0" max="100">
                      <small id="edit_error_horas_practicas" style="color:#c00;font-size:12px;"></small>
                    </div>
                  </div>
                  <div class="col-sm-4 cert-modal-col">
                    <div class="form-group">
                      <label class="cert-field-label" for="edit_estado">Estado</label>
                      <select id="edit_estado" name="estado" class="form-control">
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                        <option value="Vencido">Vencido</option>
                      </select>
                      <small id="edit_error_estado" style="color:#c00;font-size:12px;"></small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="cert-modal-card-footer-meta" id="cert-edit-footer-meta"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer cert-modal-footer">
        <button type="submit" form="form-cert-edit" class="btn btn-primary btn-sm">Guardar cambios</button>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  function obtenerFiltrosListado() {
    var fi = document.getElementById('filtro_fecha_inicio');
    var ff = document.getElementById('filtro_fecha_fin');
    var fe = document.getElementById('filtro_estado');
    var fc = document.getElementById('filtro_curso');
    var fb = document.getElementById('filtro_busqueda');

    return {
      fecha_inicio: fi && fi.value ? fi.value : '',
      fecha_fin:    ff && ff.value ? ff.value : '',
      estado:       fe && fe.value ? fe.value : '',
      curso:        fc && fc.value ? fc.value : '',
      busqueda:     fb && fb.value ? fb.value.trim() : ''
    };
  }

  function vincularEventosPaginacion() {
    var pag = document.getElementById('tabla-certificados-paginacion');
    if (!pag) return;

    var botones = pag.querySelectorAll('[data-page]');
    botones.forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var p = parseInt(this.getAttribute('data-page'), 10) || 1;
        actualizarTablaCertificados(p);
      });
    });
  }

  function inicializarAccionesCertificados() {
    var tbody = document.getElementById('tabla-certificados-body');
    if (!tbody) return;

    // Botón detalle + QR
    var botonesQr = tbody.querySelectorAll('.btn-icon-qr[data-cert-id]');
    botonesQr.forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var id = parseInt(this.getAttribute('data-cert-id'), 10) || 0;
        if (!id) return;

        fetch('detalle_certificado.php?id=' + encodeURIComponent(id), {
          method: 'GET',
          credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data || !data.ok || !data.resumen) {
            alert('No se pudo obtener el detalle del certificado.');
            return;
          }
          if (typeof window.abrirModalResumen === 'function') {
            window.abrirModalResumen(data.resumen);
          } else {
            alert('No se encontró la función de detalle (abrirModalResumen).');
          }
        })
        .catch(function(err) {
          console.error(err);
          alert('Error de comunicación al obtener el detalle del certificado.');
        });
      });
    });

    // Botón editar
    var botonesEdit = tbody.querySelectorAll('.btn-icon-edit[data-cert-id]');
    botonesEdit.forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var id = parseInt(this.getAttribute('data-cert-id'), 10) || 0;
        if (!id) return;
        abrirModalEdicionCertificado(id);
      });
    });
  }

  function limpiarErroresEdicion() {
    var g = document.getElementById('cert-edit-error-global');
    if (g) {
      g.style.display = 'none';
      g.textContent = '';
    }

    var campos = [
      'curso','tipo_certificado','nombres','apellidos',
      'tipo_doc','caracteres_doc','categoria',
      'fecha_emision','fecha_inicio','fecha_fin',
      'horas_teoricas','horas_practicas','estado'
    ];

    campos.forEach(function(c) {
      var el = document.getElementById('edit_error_' + c);
      if (el) {
        el.textContent = '';
      }
    });
  }

  function mostrarErroresEdicion(errores) {
    if (!errores) return;

    var g = document.getElementById('cert-edit-error-global');
    var hayGlobal = false;

    if (errores._global && g) {
      g.style.display = 'block';
      g.textContent = errores._global;
      hayGlobal = true;
    }

    Object.keys(errores).forEach(function(campo) {
      if (campo === '_global') return;
      var el = document.getElementById('edit_error_' + campo);
      if (el) {
        el.textContent = errores[campo];
      } else if (!hayGlobal && g) {
        g.style.display = 'block';
        g.textContent = errores[campo];
        hayGlobal = true;
      }
    });
  }

  function poblarSelectDesdeArreglo(selectEl, items, valueKey, labelKey, valorSeleccionado) {
    if (!selectEl) return;
    selectEl.innerHTML = '';

    var optVacio = document.createElement('option');
    optVacio.value = '';
    optVacio.textContent = 'Seleccione...';
    selectEl.appendChild(optVacio);

    items.forEach(function(it) {
      var opt = document.createElement('option');
      opt.value = it[valueKey];
      opt.textContent = it[labelKey];
      if (String(it[valueKey]) === String(valorSeleccionado)) {
        opt.selected = true;
      }
      selectEl.appendChild(opt);
    });
  }

  function abrirModalEdicionCertificado(idCert) {
    limpiarErroresEdicion();

    fetch('obtener_certificado_editar.php?id=' + encodeURIComponent(idCert), {
      method: 'GET',
      credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data || !data.ok || !data.certificado) {
        alert('No se pudo cargar los datos del certificado para edición.');
        return;
      }

      var cert = data.certificado;
      var opts = data.opciones || {};

      // Campos ocultos y básicos
      var idInput = document.getElementById('edit_id_certificado');
      if (idInput) {
        idInput.value = cert.id || '';
      }

      // Título y metadatos
      var tituloCode = document.getElementById('cert-edit-title-code');
      if (tituloCode) {
        tituloCode.textContent = 'Certificado ' + (cert.codigo_certificado || '');
      }

      var metaLine = document.getElementById('cert-edit-meta-line');
      if (metaLine) {
        var partes = [];
        if (cert.empresa_nombre) {
          partes.push('Empresa: ' + cert.empresa_nombre);
        }
        if (cert.nombre_cliente) {
          partes.push('Alumno: ' + cert.nombre_cliente);
        }
        if (cert.nombre_curso) {
          partes.push('Curso: ' + cert.nombre_curso);
        }
        metaLine.textContent = partes.join(' • ');
      }

      var footer = document.getElementById('cert-edit-footer-meta');
      if (footer) {
        var footerPartes = [];
        if (cert.creado) {
          footerPartes.push('Creado: ' + cert.creado);
        }
        if (cert.actualizado) {
          footerPartes.push('Actualizado: ' + cert.actualizado);
        }
        if (cert.usuario_emisor) {
          footerPartes.push('Emitido por: ' + cert.usuario_emisor);
        }
        footer.textContent = footerPartes.join('   ');
      }

      // Estado pill
      var estado = cert.estado || 'Activo';
      var estadoUpper = estado.toUpperCase();
      var estadoClass = 'status-activo';
      if (estadoUpper === 'INACTIVO') {
        estadoClass = 'status-inactivo';
      } else if (estadoUpper === 'VENCIDO') {
        estadoClass = 'status-vencido';
      }

      var statusPill = document.getElementById('cert-edit-status-pill');
      if (statusPill) {
        statusPill.className = 'badge-status cert-status-pill ' + estadoClass;
        statusPill.textContent = estado;
      }

      // Selects con opciones
      poblarSelectDesdeArreglo(
        document.getElementById('edit_curso'),
        opts.cursos || [],
        'id',
        'nombre',
        cert.id_curso
      );

      poblarSelectDesdeArreglo(
        document.getElementById('edit_tipo_certificado'),
        opts.plantillas || [],
        'id',
        'nombre',
        cert.id_plantilla_certificado
      );

      poblarSelectDesdeArreglo(
        document.getElementById('edit_tipo_doc'),
        opts.tipos_doc || [],
        'id',
        'codigo',
        cert.id_tipo_doc
      );

      poblarSelectDesdeArreglo(
        document.getElementById('edit_categoria'),
        opts.categorias || [],
        'id',
        'codigo',
        cert.id_categoria_licencia
      );

      // Inputs simples
      var setVal = function(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = val != null ? String(val) : '';
      };

      setVal('edit_nombres', cert.nombres_cliente || '');
      setVal('edit_apellidos', cert.apellidos_cliente || '');
      setVal('edit_documento', cert.documento_cliente || '');

      setVal('edit_fecha_emision', cert.fecha_emision || '');
      setVal('edit_fecha_inicio', cert.fecha_inicio || '');
      setVal('edit_fecha_fin', cert.fecha_fin || '');

      setVal('edit_horas_teoricas', cert.horas_teoricas || '');
      setVal('edit_horas_practicas', cert.horas_practicas || '');

      var selEstado = document.getElementById('edit_estado');
      if (selEstado) {
        selEstado.value = estado;
      }

      // Mostrar modal
      if (window.jQuery && typeof jQuery.fn.modal === 'function') {
        jQuery('#certEditModal').modal('show');
      } else {
        var m = document.getElementById('certEditModal');
        if (m) m.style.display = 'block';
      }
    })
    .catch(function(err) {
      console.error(err);
      alert('Error de comunicación al cargar el certificado.');
    });
  }

  function actualizarTablaCertificados(pagina) {
    if (typeof pagina === 'undefined' || pagina < 1) {
      pagina = 1;
    }

    var filtros = obtenerFiltrosListado();
    filtros.pagina = pagina;

    var qs = new URLSearchParams(filtros).toString();

    fetch('listar_certificados.php?' + qs, {
      method: 'GET',
      credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var tbody = document.getElementById('tabla-certificados-body');
      var pag   = document.getElementById('tabla-certificados-paginacion');

      if (!data || typeof data !== 'object' || !data.ok) {
        if (tbody) {
          tbody.innerHTML = '<tr><td colspan="5">No se pudo cargar el listado.</td></tr>';
        }
        if (pag) {
          pag.innerHTML = '';
        }
        return;
      }

      if (tbody) {
        tbody.innerHTML = data.html_tbody;
      }
      if (pag) {
        pag.innerHTML = data.html_paginacion;
      }

      vincularEventosPaginacion();
      inicializarAccionesCertificados();
    })
    .catch(function(err) {
      console.error(err);
      var tbody = document.getElementById('tabla-certificados-body');
      var pag   = document.getElementById('tabla-certificados-paginacion');
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="5">Error de comunicación al cargar el listado.</td></tr>';
      }
      if (pag) {
        pag.innerHTML = '';
      }
    });
  }

  // Exponer para usar desde otros lados (por ejemplo, después de guardar o editar)
  window.recargarTablaCertificados = actualizarTablaCertificados;

  var debounceTimer = null;

  document.addEventListener('DOMContentLoaded', function() {
    // Cargar primera página al abrir el módulo
    actualizarTablaCertificados(1);

    var fi = document.getElementById('filtro_fecha_inicio');
    var ff = document.getElementById('filtro_fecha_fin');
    var fe = document.getElementById('filtro_estado');
    var fc = document.getElementById('filtro_curso');
    var fb = document.getElementById('filtro_busqueda');
    var btnLimpiar = document.getElementById('filtro_limpiar_btn');

    var recargarPrimera = function() {
      actualizarTablaCertificados(1);
    };

    if (fi) fi.addEventListener('change', recargarPrimera);
    if (ff) ff.addEventListener('change', recargarPrimera);
    if (fe) fe.addEventListener('change', recargarPrimera);
    if (fc) fc.addEventListener('change', recargarPrimera);

    if (fb) {
      fb.addEventListener('keyup', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
          actualizarTablaCertificados(1);
        }, 400);
      });
    }

    if (btnLimpiar) {
      btnLimpiar.addEventListener('click', function(e) {
        e.preventDefault();
        if (fi) fi.value = '';
        if (ff) ff.value = '';
        if (fe) fe.value = '';
        if (fc) fc.value = '';
        if (fb) fb.value = '';
        actualizarTablaCertificados(1);
      });
    }

    // Submit del formulario de edición
    var formEdit = document.getElementById('form-cert-edit');
    if (formEdit) {
      formEdit.addEventListener('submit', function(e) {
        e.preventDefault();
        limpiarErroresEdicion();

        var fd = new FormData(formEdit);

        fetch('actualizar_certificado.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data || typeof data !== 'object') {
            mostrarErroresEdicion({_global: 'Respuesta inesperada del servidor.'});
            return;
          }

          if (!data.ok) {
            mostrarErroresEdicion(data.errores || {_global: 'Hay errores en el formulario.'});
            return;
          }

          // Cerrar modal
          if (window.jQuery && typeof jQuery.fn.modal === 'function') {
            jQuery('#certEditModal').modal('hide');
          } else {
            var m = document.getElementById('certEditModal');
            if (m) m.style.display = 'none';
          }

          // Recargar listado (página 1 por simplicidad)
          if (typeof window.recargarTablaCertificados === 'function') {
            window.recargarTablaCertificados(1);
          }
        })
        .catch(function(err) {
          console.error(err);
          mostrarErroresEdicion({_global: 'Error de comunicación con el servidor.'});
        });
      });
    }
  });
})();
</script>

