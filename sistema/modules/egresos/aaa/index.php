<?php
// modules/egresos/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([3,4]);
verificarPermiso(['Recepción','Administración']);

function h($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$u = currentUser();
$usrNom = trim(
    (isset($u['nombres']) ? $u['nombres'] : '') . ' ' .
    (isset($u['apellidos']) ? $u['apellidos'] : '')
);
if ($usrNom === '') {
    $usrNom = isset($u['usuario']) ? $u['usuario'] : 'Usuario';
}
$empNom = isset($u['empresa']['nombre']) ? (string)$u['empresa']['nombre'] : '—';

// Logo empresa (similar a caja)
$empresaId  = isset($u['empresa']['id']) ? (int)$u['empresa']['id'] : 0;
$empLogoRel = '';
try {
    $logoFromSession = isset($u['empresa']['logo_path']) ? trim((string)$u['empresa']['logo_path']) : '';
    if ($logoFromSession !== '') {
        $ruta = __DIR__ . '/../../' . ltrim($logoFromSession, '/');
        if (is_file($ruta)) {
            $empLogoRel = '../../' . ltrim($logoFromSession, '/');
        }
    }
    if ($empLogoRel === '' && $empresaId > 0) {
        $st = db()->prepare("SELECT logo_path FROM mtp_empresas WHERE id=? LIMIT 1");
        if ($st) {
            $st->bind_param('i', $empresaId);
            $st->execute();
            $r = $st->get_result()->fetch_assoc();
            if ($r) {
                $p = trim((string)$r['logo_path']);
                if ($p !== '') {
                    $ruta = __DIR__ . '/../../' . ltrim($p, '/');
                    if (is_file($ruta)) {
                        $empLogoRel = '../../' . ltrim($p, '/');
                    }
                }
            }
            $st->close();
        }
    }
    if ($empLogoRel === '') {
        $fallback = '../../dist/img/AdminLTELogo.png';
        if (is_file(__DIR__ . '/../../dist/img/AdminLTELogo.png')) {
            $empLogoRel = $fallback;
        }
    }
} catch (Throwable $e) {
    // silencioso
}

include __DIR__ . '/../../includes/header.php';
?>
<link rel="stylesheet" href="style.css?v=1">

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">

      <!-- BARRA SUPERIOR -->
      <div class="eg-bar shadow-sm">
        <div class="eg-bar-left">
          <div class="eg-icon">
            <i class="fas fa-file-invoice-dollar"></i>
          </div>
          <div class="eg-titles">
            <div class="eg-title">Módulo de egresos</div>
            <div class="eg-subtitle">
              Empresa: <strong>"<?= h($empNom) ?>"</strong>
              &nbsp;•&nbsp;
              Usuario: <strong><?= h($usrNom) ?></strong>
            </div>
            <div class="eg-subtitle small">
              Registra facturas, boletas y recibos vinculados a la caja diaria.
            </div>
          </div>
        </div>

        <div class="eg-bar-right">
          <div class="eg-caja-pill">
            <span class="label">Caja diaria</span>
            <span class="badge badge-pill badge-secondary" id="egCajaBadge">Verificando…</span>
          </div>

          <div class="eg-caja-info">
            <span id="egCajaMensualInfo">Mensual: —</span>
            <span id="egCajaDiariaInfo">Diaria: —</span>
          </div>

          <div class="eg-bar-meta small text-right">
            <div>
              <i class="far fa-calendar-alt mr-1"></i>
              <span id="egFechaHoy"></span>
            </div>
            <div class="text-muted">
              Los egresos del día solo se permiten con caja abierta.
            </div>
          </div>
        </div>
      </div>

      <div id="egCajaMsg" class="alert eg-caja-alert mt-3 mb-0" role="alert"></div>

    </div>
  </div>

  <section class="content pb-3">
    <div class="container-fluid">
      <div class="row eg-main-row">

        <!-- COLUMNA IZQUIERDA: FORMULARIO -->
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm eg-card-form">
            <div class="card-header border-0 pb-1">
              <h5 class="card-title mb-0">Registrar egreso</h5>
              <div class="text-muted small">Completa los datos del comprobante y guarda.</div>
            </div>
            <div class="card-body pt-2">
              <form id="egForm">

                <!-- Tipo de comprobante -->
                <div class="form-group">
                  <label for="egTipoComprobante">Tipo de comprobante*</label>
                  <select id="egTipoComprobante" name="tipo" class="form-control form-control-sm" required>
                    <option value="">Seleccione…</option>
                    <option value="FACTURA">Factura</option>
                    <option value="BOLETA">Boleta</option>
                    <option value="RECIBO">Recibo de egreso</option>
                  </select>
                </div>

                <div class="form-row">
                  <div class="form-group col-6 eg-box-serie" id="egBoxSerieNumero">
                    <label for="egSerie">Serie</label>
                    <input type="text" id="egSerie" maxlength="10" class="form-control form-control-sm">
                  </div>
                  <div class="form-group col-6 eg-box-serie" id="egBoxNumero">
                    <label for="egNumero">Número</label>
                    <input type="text" id="egNumero" maxlength="20" class="form-control form-control-sm">
                  </div>

                  <div class="form-group col-12 eg-box-ref" id="egBoxReferencia" style="display:none;">
                    <label for="egReferencia">Referencia</label>
                    <input type="text" id="egReferencia" maxlength="80" class="form-control form-control-sm">
                  </div>
                </div>

                <hr class="my-2">

                <!-- Fecha / hora / monto / cantidad -->
                <div class="form-row">
                  <div class="form-group col-sm-4">
                    <label for="egFecha">Fecha*</label>
                    <input type="date" id="egFecha" class="form-control form-control-sm" required>
                  </div>
                  <div class="form-group col-sm-4">
                    <label for="egHora">Hora*</label>
                    <input type="time" id="egHora" class="form-control form-control-sm" required>
                  </div>
                  <div class="form-group col-sm-4">
                    <label for="egCantidad">Cantidad</label>
                    <input type="number" id="egCantidad" class="form-control form-control-sm" min="1" step="1" value="1">
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-sm-6">
                    <label for="egMonto">Monto (S/)*</label>
                    <input type="number" id="egMonto" class="form-control form-control-sm" min="0" step="0.01" required>
                  </div>
                  <div class="form-group col-sm-6">
                    <label for="egMoneda">Moneda</label>
                    <select id="egMoneda" class="form-control form-control-sm">
                      <option value="PEN">Soles (S/)</option>
                      <option value="USD">Dólares ($)</option>
                    </select>
                  </div>
                </div>

                <!-- Persona / documento -->
                <div class="form-row">
                  <div class="form-group col-sm-4">
                    <label for="egTipoDoc">Tipo doc.</label>
                    <select id="egTipoDoc" class="form-control form-control-sm">
                      <option value="DNI">DNI</option>
                      <option value="CE">CE</option>
                      <option value="RUC">RUC</option>
                      <option value="OTRO">Otro</option>
                    </select>
                  </div>
                  <div class="form-group col-sm-8">
                    <label for="egNumDoc">N° documento</label>
                    <input type="text" id="egNumDoc" maxlength="20" class="form-control form-control-sm">
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-sm-6">
                    <label for="egNombres">Nombres</label>
                    <input type="text" id="egNombres" maxlength="120" class="form-control form-control-sm">
                  </div>
                  <div class="form-group col-sm-6">
                    <label for="egApellidos">Apellidos / Razón social</label>
                    <input type="text" id="egApellidos" maxlength="160" class="form-control form-control-sm">
                  </div>
                </div>

                <!-- Concepto -->
                <div class="form-group">
                  <label for="egConcepto">Concepto del egreso*</label>
                  <textarea id="egConcepto" class="form-control form-control-sm" rows="3"
                            placeholder="Detalle de lo pagado, puede contener varias líneas…" required></textarea>
                </div>

                <!-- Responsable (solo lectura, viene del usuario actual) -->
                <div class="form-group">
                  <label>Responsable</label>
                  <input type="text" class="form-control form-control-sm" value="<?= h($usrNom) ?>" readonly>
                </div>

                <!-- Acciones -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                  <div class="small text-muted mb-2 mb-sm-0">
                    Los campos marcados con * son obligatorios.
                  </div>
                  <div class="btn-group">
                    <button type="button" id="egBtnLimpiar" class="btn btn-outline-secondary btn-sm">
                      <i class="fas fa-eraser mr-1"></i>Limpiar
                    </button>
                    <button type="submit" id="egBtnGuardar" class="btn btn-primary btn-sm">
                      <i class="fas fa-save mr-1"></i>Guardar egreso
                    </button>
                  </div>
                </div>

              </form>
            </div>
          </div>
        </div>

        <!-- COLUMNA DERECHA: LISTADO -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm eg-card-list">
            <div class="card-header border-0 pb-1">
              <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                  <h5 class="card-title mb-0">Egresos del día (simulado)</h5>
                  <div class="text-muted small">Los registros se mantienen solo mientras la página esté abierta.</div>
                </div>
                <div class="small text-muted eg-total-box">
                  Total S/ del listado:
                  <strong id="egTotalListado">0.00</strong>
                </div>
              </div>
            </div>
            <div class="card-body pt-2">

              <div class="row mb-3">
                <div class="col-md-5 mb-2">
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="egFiltroTexto" class="form-control"
                           placeholder="Buscar por concepto, nombres, doc…">
                  </div>
                </div>
                <div class="col-md-3 mb-2">
                  <select id="egFiltroTipo" class="form-control form-control-sm">
                    <option value="">Todos los tipos</option>
                    <option value="FACTURA">Factura</option>
                    <option value="BOLETA">Boleta</option>
                    <option value="RECIBO">Recibo</option>
                  </select>
                </div>
                <div class="col-md-2 mb-2">
                  <select id="egFiltroEstado" class="form-control form-control-sm">
                    <option value="">Todos</option>
                    <option value="REGISTRADO">Vigentes</option>
                    <option value="ANULADO">Anulados</option>
                  </select>
                </div>
                <div class="col-md-2 mb-2 text-md-right">
                  <button type="button" id="egBtnExportar" class="btn btn-outline-secondary btn-sm btn-block">
                    <i class="fas fa-file-excel mr-1"></i>Exportar
                  </button>
                </div>
              </div>

              <div class="table-responsive eg-table-wrapper">
                <table class="table table-sm table-hover mb-2" id="egTabla">
                  <thead class="thead-light">
                    <tr>
                      <th>Fecha</th>
                      <th>Comprobante</th>
                      <th>Persona</th>
                      <th class="text-right">Monto</th>
                      <th>Estado</th>
                      <th class="text-right">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="egTBody">
                    <tr>
                      <td colspan="6" class="text-muted small text-center">
                        No hay egresos registrados aún.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <nav>
                <ul class="pagination pagination-sm mb-0" id="egPager"></ul>
              </nav>

            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<script type="text/javascript">
(function(){
  // Utilidades básicas
  function qs(sel) { return document.querySelector(sel); }
  function qsa(sel) { return document.querySelectorAll(sel); }
  function trim(s) { return (s || '').replace(/^\s+|\s+$/g, ''); }
  function pad(num, size) {
    var s = String(num);
    while (s.length < size) { s = '0' + s; }
    return s;
  }
  function money(v) {
    var n = parseFloat(v);
    if (isNaN(n)) { n = 0; }
    return n.toFixed(2);
  }

  // Estado en memoria
  var state = {
    cajaAbierta: true,   // siempre abierta (simulado)
    egresos: [],         // registros
    nextId: 1,
    nextCorrelativo: 1,
    page: 1,
    perPage: 8
  };

  // Simular estado de caja
  function simularCaja() {
    var hoy = new Date();
    var dd = pad(hoy.getDate(), 2);
    var mm = pad(hoy.getMonth() + 1, 2);
    var yy = hoy.getFullYear();

    var fechaHoyEl = qs('#egFechaHoy');
    if (fechaHoyEl) {
      fechaHoyEl.textContent = dd + '/' + mm + '/' + yy;
    }

    var codMensual = 'CM-' + yy + mm + '-001';
    var codDiaria  = 'CD-' + yy + mm + dd + '-001';

    var mens = qs('#egCajaMensualInfo');
    if (mens) {
      mens.textContent = 'Mensual: ' + codMensual + ' (Abierta)';
    }
    var dia = qs('#egCajaDiariaInfo');
    if (dia) {
      dia.textContent = 'Diaria: ' + codDiaria + ' (Abierta)';
    }

    var badge = qs('#egCajaBadge');
    if (badge) {
      badge.className = 'badge badge-pill badge-success';
      badge.innerHTML = '<i class="fas fa-unlock-alt mr-1"></i>Abierta';
    }

    var msg = qs('#egCajaMsg');
    if (msg) {
      msg.className = 'alert eg-caja-alert mt-3 mb-0 alert-success';
      msg.innerHTML = '<i class="fas fa-check-circle mr-1"></i>' +
        'Simulación: caja mensual y diaria abiertas. Puedes registrar egresos.';
    }
  }

  // Bloquear / desbloquear formulario según caja
  function aplicarBloqueoFormulario(bloquear) {
    var form = qs('#egForm');
    var btnGuardar = qs('#egBtnGuardar');
    var inputs;
    if (!form) { return; }

    inputs = form.querySelectorAll('input, select, textarea, button');
    for (var i = 0; i < inputs.length; i++) {
      var el = inputs[i];
      if (el.id === 'egBtnGuardar' || el.id === 'egBtnLimpiar') {
        el.disabled = bloquear;
      } else {
        el.disabled = bloquear;
      }
    }

    var badge = qs('#egCajaBadge');
    if (badge) {
      if (bloquear) {
        badge.className = 'badge badge-pill badge-secondary';
        badge.innerHTML = 'Cerrada';
      } else {
        badge.className = 'badge badge-pill badge-success';
        badge.innerHTML = '<i class="fas fa-unlock-alt mr-1"></i>Abierta';
      }
    }
  }

  // Mostrar / ocultar campos según tipo de comprobante
  function actualizarCamposComprobante() {
    var tipo = qs('#egTipoComprobante').value;
    var boxSerie1 = qs('#egBoxSerieNumero');
    var boxSerie2 = qs('#egBoxNumero');
    var boxRef = qs('#egBoxReferencia');

    if (tipo === 'FACTURA' || tipo === 'BOLETA') {
      if (boxSerie1) { boxSerie1.style.display = ''; }
      if (boxSerie2) { boxSerie2.style.display = ''; }
      if (boxRef) { boxRef.style.display = 'none'; }
    } else if (tipo === 'RECIBO') {
      if (boxSerie1) { boxSerie1.style.display = 'none'; }
      if (boxSerie2) { boxSerie2.style.display = 'none'; }
      if (boxRef) { boxRef.style.display = ''; }
    } else {
      // nada seleccionado
      if (boxSerie1) { boxSerie1.style.display = 'none'; }
      if (boxSerie2) { boxSerie2.style.display = 'none'; }
      if (boxRef) { boxRef.style.display = 'none'; }
    }
  }

  // Limpiar formulario
  function limpiarFormulario() {
    var form = qs('#egForm');
    if (!form) { return; }
    form.reset();
    // Restaurar fecha y hora actuales
    var ahora = new Date();
    var dd = pad(ahora.getDate(), 2);
    var mm = pad(ahora.getMonth() + 1, 2);
    var yy = ahora.getFullYear();
    var hh = pad(ahora.getHours(), 2);
    var mi = pad(ahora.getMinutes(), 2);

    var f = qs('#egFecha');
    var h = qs('#egHora');
    if (f) { f.value = yy + '-' + mm + '-' + dd; }
    if (h) { h.value = hh + ':' + mi; }

    var c = qs('#egCantidad');
    if (c && (!c.value || parseInt(c.value,10) < 1)) {
      c.value = 1;
    }

    actualizarCamposComprobante();
  }

  // Leer datos del formulario y validar
  function leerFormulario() {
    var tipo = trim(qs('#egTipoComprobante').value);
    var serie = trim(qs('#egSerie').value);
    var numero = trim(qs('#egNumero').value);
    var referencia = trim(qs('#egReferencia').value);
    var fecha = qs('#egFecha').value;
    var hora = qs('#egHora').value;
    var cantidad = qs('#egCantidad').value;
    var monto = qs('#egMonto').value;
    var moneda = qs('#egMoneda').value;
    var tipoDoc = qs('#egTipoDoc').value;
    var numDoc = trim(qs('#egNumDoc').value);
    var nombres = trim(qs('#egNombres').value);
    var apellidos = trim(qs('#egApellidos').value);
    var concepto = trim(qs('#egConcepto').value);

    if (tipo === '') {
      alert('Seleccione el tipo de comprobante.');
      return null;
    }

    if (tipo === 'FACTURA' || tipo === 'BOLETA') {
      if (serie === '' || numero === '') {
        alert('Serie y número son obligatorios para factura y boleta.');
        return null;
      }
    }

    if (!fecha) {
      alert('Seleccione la fecha.');
      return null;
    }
    if (!hora) {
      alert('Seleccione la hora.');
      return null;
    }

    var m = parseFloat(monto);
    if (isNaN(m) || m <= 0) {
      alert('Ingrese un monto mayor a 0.');
      return null;
    }

    if (concepto === '') {
      alert('Ingrese el concepto del egreso.');
      return null;
    }

    var cant = parseInt(cantidad, 10);
    if (isNaN(cant) || cant < 1) { cant = 1; }

    var id = state.nextId++;
    var correlativo = pad(state.nextCorrelativo++, 6);

    return {
      id: id,
      correlativo: correlativo,
      tipo: tipo,
      serie: serie,
      numero: numero,
      referencia: referencia,
      fecha: fecha,
      hora: hora,
      cantidad: cant,
      monto: m,
      moneda: moneda,
      tipoDoc: tipoDoc,
      numDoc: numDoc,
      nombres: nombres,
      apellidos: apellidos,
      concepto: concepto,
      estado: 'REGISTRADO'
    };
  }

  // Guardar egreso (memoria)
  function guardarEgreso(e) {
    if (e && e.preventDefault) { e.preventDefault(); }

    if (!state.cajaAbierta) {
      alert('La caja simulada está cerrada. (En este ejemplo siempre está abierta).');
      return false;
    }

    var eg = leerFormulario();
    if (!eg) { return false; }

    // Insertar al inicio
    state.egresos.unshift(eg);
    state.page = 1;
    limpiarFormulario();
    renderTabla();
    abrirVentanaImpresion(eg);

    return false;
  }

  // Filtros actuales
  function obtenerFiltros() {
    return {
      texto: trim(qs('#egFiltroTexto').value).toLowerCase(),
      tipo: qs('#egFiltroTipo').value,
      estado: qs('#egFiltroEstado').value
    };
  }

  // Aplicar filtros y devolver arreglo filtrado
  function filtrarEgresos() {
    var filtros = obtenerFiltros();
    var list = [];
    var i, eg, texto;

    for (i = 0; i < state.egresos.length; i++) {
      eg = state.egresos[i];

      if (filtros.tipo && eg.tipo !== filtros.tipo) {
        continue;
      }
      if (filtros.estado && eg.estado !== filtros.estado) {
        continue;
      }

      if (filtros.texto) {
        texto = (eg.concepto + ' ' + eg.nombres + ' ' + eg.apellidos + ' ' + eg.numDoc).toLowerCase();
        if (texto.indexOf(filtros.texto) === -1) {
          continue;
        }
      }

      list.push(eg);
    }
    return list;
  }

  // Pintar tabla y paginación
  function renderTabla() {
    var tbody = qs('#egTBody');
    var pager = qs('#egPager');
    var totalEl = qs('#egTotalListado');
    if (!tbody || !pager) { return; }

    var filtrados = filtrarEgresos();
    var total = 0;
    var i;

    for (i = 0; i < filtrados.length; i++) {
      if (filtrados[i].estado === 'REGISTRADO' && filtrados[i].moneda === 'PEN') {
        total += filtrados[i].monto;
      }
    }
    if (totalEl) { totalEl.textContent = money(total); }

    var pages = Math.max(1, Math.ceil(filtrados.length / state.perPage));
    if (state.page > pages) { state.page = pages; }
    if (state.page < 1) { state.page = 1; }

    var ini = (state.page - 1) * state.perPage;
    var fin = ini + state.perPage;
    var slice = filtrados.slice(ini, fin);

    if (slice.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-muted small text-center">No se encontraron egresos.</td></tr>';
    } else {
      var filas = [];
      for (i = 0; i < slice.length; i++) {
        var eg = slice[i];
        var comp;
        if (eg.tipo === 'RECIBO') {
          comp = 'REC- ' + eg.correlativo;
        } else {
          comp = eg.tipo.substr(0,1) + ': ' + eg.serie + '-' + eg.numero;
        }
        var persona = (eg.nombres || '') + ' ' + (eg.apellidos || '');
        var docTexto = eg.numDoc ? ' (' + eg.tipoDoc + ': ' + eg.numDoc + ')' : '';
        persona = (persona.replace(/^\s+|\s+$/g,'') || '—') + docTexto;

        var badgeEstado = eg.estado === 'REGISTRADO'
          ? '<span class="badge badge-success">Vigente</span>'
          : '<span class="badge badge-danger">Anulado</span>';

        filas.push(
          '<tr data-id="' + eg.id + '">' +
            '<td>' + eg.fecha.split('-').reverse().join('/') + '<br><span class="small text-muted">' + eg.hora + '</span></td>' +
            '<td>' + comp + '</td>' +
            '<td>' + persona + '</td>' +
            '<td class="text-right">' + (eg.moneda === 'PEN' ? 'S/ ' : '$ ') + money(eg.monto) + '</td>' +
            '<td>' + badgeEstado + '</td>' +
            '<td class="text-right">' +
              '<button type="button" class="btn btn-xs btn-outline-secondary eg-btn-ver" data-id="' + eg.id + '">' +
                '<i class="fas fa-print"></i>' +
              '</button> ' +
              '<button type="button" class="btn btn-xs ' + (eg.estado === 'REGISTRADO' ? 'btn-outline-danger' : 'btn-outline-success') +
                ' eg-btn-toggle" data-id="' + eg.id + '">' +
                (eg.estado === 'REGISTRADO' ? '<i class="fas fa-ban"></i>' : '<i class="fas fa-undo"></i>') +
              '</button>' +
            '</td>' +
          '</tr>'
        );
      }
      tbody.innerHTML = filas.join('');
    }

    // Paginación
    var htmlPager = [];
    if (pages <= 1) {
      pager.innerHTML = '';
      return;
    }

    var p;
    htmlPager.push(
      '<li class="page-item' + (state.page === 1 ? ' disabled' : '') + '">' +
      '<a class="page-link" href="#" data-page="' + (state.page - 1) + '">«</a>' +
      '</li>'
    );
    for (p = 1; p <= pages; p++) {
      htmlPager.push(
        '<li class="page-item' + (p === state.page ? ' active' : '') + '">' +
        '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a>' +
        '</li>'
      );
    }
    htmlPager.push(
      '<li class="page-item' + (state.page === pages ? ' disabled' : '') + '">' +
      '<a class="page-link" href="#" data-page="' + (state.page + 1) + '">»</a>' +
      '</li>'
    );
    pager.innerHTML = htmlPager.join('');
  }

  // Cambiar estado (anular / reactivar)
  function toggleEstado(id) {
    var i;
    for (i = 0; i < state.egresos.length; i++) {
      if (state.egresos[i].id === id) {
        if (state.egresos[i].estado === 'REGISTRADO') {
          if (!confirm('¿Anular este egreso?')) { return; }
          state.egresos[i].estado = 'ANULADO';
        } else {
          if (!confirm('¿Volver a marcar este egreso como vigente?')) { return; }
          state.egresos[i].estado = 'REGISTRADO';
        }
        break;
      }
    }
    renderTabla();
  }

  // Ventana de impresión (recibo)
  function abrirVentanaImpresion(eg) {
    var logo = '<?= h($empLogoRel) ?>';
    var empresa = '<?= h($empNom) ?>';
    var responsable = '<?= h($usrNom) ?>';

    var tipoTexto;
    if (eg.tipo === 'FACTURA') {
      tipoTexto = 'FACTURA';
    } else if (eg.tipo === 'BOLETA') {
      tipoTexto = 'BOLETA';
    } else {
      tipoTexto = 'RECIBO DE EGRESO';
    }

    var cod;
    if (eg.tipo === 'RECIBO') {
      cod = 'REC-' + eg.correlativo;
    } else {
      cod = eg.serie + '-' + eg.numero;
    }

    var win = window.open('', 'egreso_print_' + eg.id, 'width=900,height=600');
    if (!win) {
      alert('El navegador bloqueó la ventana emergente. Habilita las ventanas emergentes para imprimir.');
      return;
    }

    var fechaTexto = eg.fecha.split('-').reverse().join('/') + ' ' + eg.hora;

    var persona = (eg.nombres || '') + ' ' + (eg.apellidos || '');
    persona = persona.replace(/^\s+|\s+$/g,'');
    if (persona === '') { persona = '—'; }

    var docTexto = eg.numDoc ? eg.tipoDoc + ' ' + eg.numDoc : '—';
    var refTexto = eg.referencia || '';

    var html =
      '<!doctype html>' +
      '<html><head><meta charset="utf-8">' +
      '<title>Egreso ' + cod + '</title>' +
      '<style>' +
      'body{font-family:Arial,Helvetica,sans-serif;font-size:12px;padding:16px;}' +
      '.box{border:1px solid #000;padding:10px;margin-bottom:6px;}' +
      '.row{display:flex;flex-wrap:wrap;margin-bottom:6px;}' +
      '.col{flex:1;padding:4px;}' +
      '.title{font-weight:bold;text-align:center;font-size:16px;margin-bottom:4px;}' +
      '.right{text-align:right;}' +
      '.center{text-align:center;}' +
      '.small{font-size:11px;}' +
      'table{width:100%;border-collapse:collapse;margin-top:6px;}' +
      'td,th{border:1px solid #000;padding:4px;font-size:12px;}' +
      '</style>' +
      '</head><body>' +
      '<div class="box">' +
        '<div class="row">' +
          '<div class="col center" style="max-width:90px;">' +
            (logo ? '<img src="' + logo + '" style="max-width:80px;max-height:80px;border-radius:40px;">' : '') +
          '</div>' +
          '<div class="col">' +
            '<div class="title">' + empresa + '</div>' +
            '<div class="center"><strong>' + tipoTexto + '</strong></div>' +
          '</div>' +
          '<div class="col right">' +
            '<div>Importe: <strong>' + (eg.moneda === 'PEN' ? 'S/ ' : '$ ') + money(eg.monto) + '</strong></div>' +
            '<div>N° <strong>' + cod + '</strong></div>' +
          '</div>' +
        '</div>' +

        '<div class="row">' +
          '<div class="col"><strong>Cantidad:</strong> ' + eg.cantidad + '</div>' +
          '<div class="col"><strong>Documento:</strong> ' + docTexto + '</div>' +
        '</div>' +

        '<div class="box">' +
          '<strong>Concepto:</strong><br>' +
          '<div>' + eg.concepto.replace(/\n/g,'<br>') + '</div>' +
        '</div>' +

        (refTexto ? '<div class="box"><strong>Referencia:</strong> ' + refTexto + '</div>' : '') +

        '<div class="row">' +
          '<div class="col"><strong>Nombre:</strong> ' + persona + '</div>' +
        '</div>' +
        '<div class="row">' +
          '<div class="col"><strong>Fecha y hora:</strong> ' + fechaTexto + '</div>' +
        '</div>' +
        '<div class="row" style="margin-top:30px;">' +
          '<div class="col center">' +
            '_______________________________<br>' +
            '<span class="small">Responsable: ' + responsable + '</span>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<script>window.print();<\/script>' +
      '</body></html>';

    win.document.open();
    win.document.write(html);
    win.document.close();
  }

  // Exportación muy simple a CSV (temporal)
  function exportarCSV() {
    if (state.egresos.length === 0) {
      alert('No hay egresos para exportar.');
      return;
    }
    var filtrados = filtrarEgresos();
    if (filtrados.length === 0) {
      alert('Los filtros actuales no tienen resultados.');
      return;
    }
    var lineas = [];
    lineas.push(
      'ID;Tipo;Serie;Numero;Referencia;Fecha;Hora;Cantidad;Monto;Moneda;TipoDoc;NumDoc;Nombres;Apellidos;Concepto;Estado'
    );
    var i, eg;
    for (i = 0; i < filtrados.length; i++) {
      eg = filtrados[i];
      lineas.push(
        eg.id + ';' +
        eg.tipo + ';' +
        (eg.serie || '') + ';' +
        (eg.numero || '') + ';' +
        (eg.referencia || '') + ';' +
        eg.fecha + ';' +
        eg.hora + ';' +
        eg.cantidad + ';' +
        money(eg.monto) + ';' +
        eg.moneda + ';' +
        eg.tipoDoc + ';' +
        (eg.numDoc || '') + ';' +
        (eg.nombres || '') + ';' +
        (eg.apellidos || '') + ';' +
        (eg.concepto || '').replace(/(\r\n|\n|\r)/g,' ') + ';' +
        eg.estado
      );
    }
    var csv = lineas.join('\r\n');
    var blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'egresos_simulados.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  // Eventos globales
  function initEventos() {
    var form = qs('#egForm');
    if (form) {
      form.addEventListener('submit', guardarEgreso, false);
    }

    var btnLimpiar = qs('#egBtnLimpiar');
    if (btnLimpiar) {
      btnLimpiar.addEventListener('click', function(e){
        e.preventDefault();
        limpiarFormulario();
      }, false);
    }

    var selTipo = qs('#egTipoComprobante');
    if (selTipo) {
      selTipo.addEventListener('change', function(){
        actualizarCamposComprobante();
      }, false);
    }

    var filtroTexto = qs('#egFiltroTexto');
    var filtroTipo = qs('#egFiltroTipo');
    var filtroEstado = qs('#egFiltroEstado');
    if (filtroTexto) {
      filtroTexto.addEventListener('input', function(){
        state.page = 1;
        renderTabla();
      }, false);
    }
    if (filtroTipo) {
      filtroTipo.addEventListener('change', function(){
        state.page = 1;
        renderTabla();
      }, false);
    }
    if (filtroEstado) {
      filtroEstado.addEventListener('change', function(){
        state.page = 1;
        renderTabla();
      }, false);
    }

    var pager = qs('#egPager');
    if (pager) {
      pager.addEventListener('click', function(e){
        var a = e.target;
        if (a.tagName.toLowerCase() === 'a' && a.getAttribute('data-page')) {
          e.preventDefault();
          var p = parseInt(a.getAttribute('data-page'), 10);
          if (!isNaN(p)) {
            state.page = p;
            renderTabla();
          }
        }
      }, false);
    }

    var tabla = qs('#egTabla');
    if (tabla) {
      tabla.addEventListener('click', function(e){
        var btnVer = e.target;
        while (btnVer && btnVer !== tabla && !btnVer.classList.contains('eg-btn-ver') && !btnVer.classList.contains('eg-btn-toggle')) {
          btnVer = btnVer.parentNode;
        }
        if (!btnVer || btnVer === tabla) { return; }

        var idStr = btnVer.getAttribute('data-id');
        var id = parseInt(idStr, 10);
        if (isNaN(id)) { return; }

        if (btnVer.classList.contains('eg-btn-ver')) {
          var i, eg;
          for (i = 0; i < state.egresos.length; i++) {
            if (state.egresos[i].id === id) {
              eg = state.egresos[i];
              break;
            }
          }
          if (eg) {
            abrirVentanaImpresion(eg);
          }
        } else if (btnVer.classList.contains('eg-btn-toggle')) {
          toggleEstado(id);
        }
      }, false);
    }

    var btnExport = qs('#egBtnExportar');
    if (btnExport) {
      btnExport.addEventListener('click', function(e){
        e.preventDefault();
        exportarCSV();
      }, false);
    }
  }

  // Inicio
  document.addEventListener('DOMContentLoaded', function(){
    simularCaja();
    aplicarBloqueoFormulario(false);
    limpiarFormulario();
    initEventos();
    renderTabla();
  }, false);

})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
