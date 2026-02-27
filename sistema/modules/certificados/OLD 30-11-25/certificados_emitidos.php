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

      if (pag) {
        var botones = pag.querySelectorAll('[data-page]');
        botones.forEach(function(btn) {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            var p = parseInt(this.getAttribute('data-page'), 10) || 1;
            actualizarTablaCertificados(p);
          });
        });
      }
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

  // Exponer global para que el formulario pueda forzar recarga tras insertar
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

    // Botón "Limpiar filtros"
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
  });
})();
</script>
