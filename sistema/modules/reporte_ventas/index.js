// modules/reporte_ventas/index.js
// Detalle + abonos de venta desde reporte_ventas (sin redireccionar a caja).

(function () {
  var API_VENTAS = '../caja/api_ventas.php';
  var API_CAJA = '../caja/api.php';

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function money(n) {
    return 'S/ ' + Number(n || 0).toFixed(2);
  }

  function fmtDT(value) {
    if (!value) return '';
    var dt = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(dt.getTime())) return String(value);
    var dd = String(dt.getDate()).padStart(2, '0');
    var mm = String(dt.getMonth() + 1).padStart(2, '0');
    var yy = dt.getFullYear();
    var hh = String(dt.getHours()).padStart(2, '0');
    var mi = String(dt.getMinutes()).padStart(2, '0');
    return dd + '/' + mm + '/' + yy + ' ' + hh + ':' + mi;
  }

  function showMsg(title, message) {
    window.alert((title ? title + ': ' : '') + message);
  }

  function mapApiError(raw) {
    var s = String(raw || '').toLowerCase();
    if (s.includes('no hay caja diaria abierta')) return 'Abre la caja diaria de hoy para registrar abonos.';
    if (s.includes('medio de pago invalido') || s.includes('medio de pago inv')) return 'Selecciona un medio de pago valido.';
    if (s.includes('monto invalido')) return 'Ingresa un monto mayor a 0.00.';
    if (s.includes('requiere una referencia')) return 'Este medio exige referencia. Completala para continuar.';
    if (s.includes('excede el saldo')) return 'El monto ingresado supera el saldo pendiente.';
    if (s.includes('venta no encontrada')) return 'No se encontro la venta.';
    if (s.includes('anulada')) return 'La venta esta anulada.';
    return String(raw || 'Ocurrio un error. Intentalo nuevamente.');
  }

  async function getVentas(params) {
    var usp = new URLSearchParams(params || {});
    var r = await fetch(API_VENTAS + '?' + usp.toString(), { credentials: 'same-origin' });
    var txt = await r.text();
    var j;
    try {
      j = JSON.parse(txt);
    } catch (_) {
      throw new Error('Respuesta invalida del servidor.');
    }
    if (!j.ok) throw new Error(j.error || 'Error');
    return j;
  }

  async function postVentas(action, payload) {
    var fd = new FormData();
    fd.append('accion', action);
    Object.entries(payload || {}).forEach(function (kv) {
      fd.append(kv[0], kv[1]);
    });
    var r = await fetch(API_VENTAS, { method: 'POST', credentials: 'same-origin', body: fd });
    var txt = await r.text();
    var j;
    try {
      j = JSON.parse(txt);
    } catch (_) {
      throw new Error('Respuesta invalida del servidor.');
    }
    if (!j.ok) throw new Error(j.error || 'Error');
    return j;
  }

  async function loadMediosPago() {
    var r = await fetch(API_CAJA + '?action=pos_medios_pago', { credentials: 'same-origin' });
    var txt = await r.text();
    var j;
    try {
      j = JSON.parse(txt);
    } catch (_) {
      throw new Error('No se pudo cargar medios de pago.');
    }
    if (!j.ok) throw new Error(j.error || 'No se pudo cargar medios de pago.');
    return (j.data || []).map(function (m) {
      var req = (m.requiere_ref === 1 || m.requiere_ref === '1' || m.requiere_ref === true) ? 1 : 0;
      if (/efect/i.test(String(m.nombre || ''))) req = 0;
      return Object.assign({}, m, { requiere_ref: req });
    });
  }

  function modalAlert(html, type) {
    var holder = document.getElementById('vpAbonoAlert');
    if (!holder) return;
    holder.innerHTML =
      '<div class="alert alert-' + type + ' mb-2 py-2" role="alert">' + html + '</div>';
  }

  function clearModalAlert() {
    var holder = document.getElementById('vpAbonoAlert');
    if (holder) holder.innerHTML = '';
  }

  function ensureAbonoModal() {
    if (document.getElementById('vpAbonoModal')) return;

    var wrap = document.createElement('div');
    wrap.innerHTML = [
      '<div class="modal fade" id="vpAbonoModal" tabindex="-1" role="dialog" aria-hidden="true">',
      '  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">',
      '    <div class="modal-content">',
      '      <div class="modal-header py-2 bg-success text-white">',
      '        <h5 class="modal-title"><i class="fas fa-cash-register mr-2"></i>Completar pago</h5>',
      '        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>',
      '      </div>',
      '      <div class="modal-body">',
      '        <div id="vpAbonoAlert"></div>',
      '        <div id="vpAbonoBody"></div>',
      '      </div>',
      '      <div class="modal-footer py-2">',
      '        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>',
      '        <button type="button" class="btn btn-success btn-sm" id="vpAbonoConfirm"><i class="fas fa-check-circle mr-1"></i>Registrar abonos</button>',
      '      </div>',
      '    </div>',
      '  </div>',
      '</div>'
    ].join('');
    document.body.appendChild(wrap.firstElementChild);

    if (!document.getElementById('vpAbonoStyles')) {
      var st = document.createElement('style');
      st.id = 'vpAbonoStyles';
      st.textContent = [
        '.vp-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}',
        '@media (max-width: 768px){.vp-grid{grid-template-columns:1fr}}',
        '.vp-box{border:1px solid #e5e7eb;border-radius:12px;padding:10px;margin-bottom:8px}'
      ].join('');
      document.head.appendChild(st);
    }
  }

  async function openAbonar(ventaId) {
    ensureAbonoModal();
    clearModalAlert();

    try {
      var detalle = await getVentas({ action: 'venta_detalle', id: ventaId });
      var H = detalle.cabecera || {};
      var ABH = detalle.abonos || [];
      var medios = await loadMediosPago();

      var hist = ABH.length
        ? ABH.map(function (a) {
            return [
              '<tr>',
              '  <td>' + esc(a.medio || '') + '</td>',
              '  <td class="text-end">' + money(a.monto) + '</td>',
              '  <td class="text-end">' + money(a.monto_aplicado) + '</td>',
              '  <td>' + esc(a.referencia || '') + '</td>',
              '  <td class="small text-muted">' + esc(fmtDT(a.fecha) || '') + '</td>',
              '  <td class="text-end">',
              '    <button class="btn btn-sm btn-outline-danger vp-refund" data-venta="' + Number(H.id || 0) + '" data-apl="' + Number(a.aplicacion_id || 0) + '">',
              '      <i class="fas fa-undo"></i>',
              '    </button>',
              '  </td>',
              '</tr>'
            ].join('');
          }).join('')
        : '<tr><td colspan="6" class="text-muted small">- Sin abonos previos -</td></tr>';

      var body = document.getElementById('vpAbonoBody');
      body.innerHTML = [
        '<div class="vp-grid">',
        '  <div class="vp-box">',
        '    <div class="fw-bold mb-1">Venta</div>',
        '    <div class="small">Ticket: <strong>' + esc(H.ticket || '') + '</strong></div>',
        '    <div class="small">Fecha: ' + esc(fmtDT(H.fecha) || '-') + '</div>',
        '    <div class="small">Cliente: ' + esc((H.cliente || {}).nombre || '') + '</div>',
        '  </div>',
        '  <div class="vp-box">',
        '    <div class="fw-bold mb-1">Saldo</div>',
        '    <div class="display-6" id="vpSaldoNow">' + money(H.saldo) + '</div>',
        '    <div class="small text-muted">Total: ' + money(H.total) + ' | Pagado: ' + money(H.pagado) + '</div>',
        '  </div>',
        '</div>',
        '',
        '<div class="vp-box">',
        '  <div class="fw-bold mb-1">Registrar nuevos abonos</div>',
        '  <div class="row g-2 align-items-end mb-2">',
        '    <div class="col-12 col-sm-3">',
        '      <label class="form-label small mb-1">Medio de pago</label>',
        '      <select id="vpMedio" class="form-control form-control-sm">',
                medios.map(function (m) {
                  return '<option value="' + Number(m.id || 0) + '" data-req="' + (m.requiere_ref ? '1' : '0') + '">' + esc(m.nombre || '') + '</option>';
                }).join(''),
        '      </select>',
        '    </div>',
        '    <div class="col-12 col-sm-2">',
        '      <label class="form-label small mb-1">Monto</label>',
        '      <input id="vpMonto" type="number" step="0.01" min="0" class="form-control form-control-sm">',
        '    </div>',
        '    <div class="col-12 col-sm-3">',
        '      <label class="form-label small mb-1">Referencia</label>',
        '      <input id="vpRef" class="form-control form-control-sm" maxlength="80" placeholder="segun el medio">',
        '    </div>',
        '    <div class="col-12 col-sm-4">',
        '      <label class="form-label small mb-1">Detalle / Nota</label>',
        '      <input id="vpObs" class="form-control form-control-sm" maxlength="255">',
        '    </div>',
        '    <div class="col-12">',
        '      <button id="vpAddAbono" type="button" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Agregar abono</button>',
        '    </div>',
        '  </div>',
        '  <div class="table-responsive">',
        '    <table class="table table-sm table-striped align-middle mb-2">',
        '      <thead class="table-light"><tr><th style="width:40%">Medio</th><th style="width:15%" class="text-end">Monto</th><th style="width:25%">Referencia</th><th style="width:15%">Agregado</th><th style="width:5%"></th></tr></thead>',
        '      <tbody id="vpAbonosBody"><tr><td colspan="5" class="text-muted small">- Sin abonos nuevos -</td></tr></tbody>',
        '    </table>',
        '  </div>',
        '  <div class="d-flex align-items-center justify-content-end gap-3">',
        '    <div class="small">Abonos nuevos: <span id="vpAbonosTotal">S/ 0.00</span></div>',
        '    <div class="fw-bold">Saldo estimado: <span id="vpSaldoEst">' + money(H.saldo) + '</span></div>',
        '  </div>',
        '</div>',
        '',
        '<div class="vp-box">',
        '  <div class="fw-bold mb-1">Historial de abonos</div>',
        '  <div class="table-responsive">',
        '    <table class="table table-sm"><thead class="table-light"><tr><th>Medio</th><th class="text-end">Monto</th><th class="text-end">Aplicado</th><th>Ref.</th><th>Fecha</th><th></th></tr></thead><tbody id="vpHistBody">' + hist + '</tbody></table>',
        '  </div>',
        '</div>'
      ].join('');

      var AB = [];

      function medioSelected() {
        var sel = document.getElementById('vpMedio');
        return sel && sel.selectedOptions ? sel.selectedOptions[0] : null;
      }

      function medioRequiereRef() {
        var opt = medioSelected();
        return !!(opt && opt.dataset && opt.dataset.req === '1');
      }

      function renderAbonos() {
        var tb = document.getElementById('vpAbonosBody');
        if (!AB.length) {
          tb.innerHTML = '<tr><td colspan="5" class="text-muted small">- Sin abonos nuevos -</td></tr>';
          return;
        }
        tb.innerHTML = AB.map(function (a) {
          return [
            '<tr>',
            '  <td>' + esc(a.medio) + '</td>',
            '  <td class="text-end">' + money(a.monto) + '</td>',
            '  <td>' + esc(a.ref || '') + '</td>',
            '  <td class="small text-muted">' + esc(a.ts) + '</td>',
            '  <td><button class="btn btn-link btn-sm text-danger vp-del" data-id="' + a.id + '" title="Quitar"><i class="fas fa-times"></i></button></td>',
            '</tr>'
          ].join('');
        }).join('');
      }

      function recompute() {
        var abonado = AB.reduce(function (sum, x) { return sum + Number(x.monto || 0); }, 0);
        var est = Math.max(0, Number(H.saldo || 0) - abonado);
        document.getElementById('vpAbonosTotal').textContent = money(abonado);
        document.getElementById('vpSaldoEst').textContent = money(est);
      }

      function updateRefPlaceholder() {
        var refInput = document.getElementById('vpRef');
        if (!refInput) return;
        refInput.placeholder = medioRequiereRef() ? 'Obligatoria para este medio' : 'Opcional';
      }

      var medioSel = document.getElementById('vpMedio');
      var montoEl = document.getElementById('vpMonto');
      var refEl = document.getElementById('vpRef');
      var obsEl = document.getElementById('vpObs');

      medioSel.addEventListener('change', updateRefPlaceholder);
      updateRefPlaceholder();

      document.getElementById('vpAddAbono').onclick = function () {
        clearModalAlert();
        montoEl.classList.remove('is-invalid');
        refEl.classList.remove('is-invalid');

        var sel = document.getElementById('vpMedio');
        var id = parseInt(sel.value || '0', 10);
        var opt = sel.selectedOptions ? sel.selectedOptions[0] : null;
        if (!id || !opt) {
          modalAlert('Selecciona un medio de pago.', 'danger');
          return;
        }

        var monto = Number(montoEl.value || 0);
        if (!Number.isFinite(monto) || monto <= 0) {
          montoEl.classList.add('is-invalid');
          modalAlert('Ingresa un monto mayor a 0.00.', 'danger');
          return;
        }

        var abonadoNuevo = AB.reduce(function (s, x) { return s + Number(x.monto || 0); }, 0);
        var maxPermitido = Math.max(0, Number(H.saldo || 0) - abonadoNuevo);
        if (monto > maxPermitido) {
          montoEl.classList.add('is-invalid');
          modalAlert('El monto ingresado supera el saldo pendiente.', 'danger');
          return;
        }

        var ref = String(refEl.value || '').trim();
        if (medioRequiereRef() && !ref) {
          refEl.classList.add('is-invalid');
          modalAlert('Este medio exige referencia. Completala para continuar.', 'danger');
          return;
        }

        var obs = String(obsEl.value || '').trim();
        AB.push({
          id: Date.now(),
          medio_id: id,
          medio: opt.textContent || '',
          monto: monto,
          ref: ref,
          obs: obs,
          ts: new Date().toLocaleString()
        });
        montoEl.value = '';
        refEl.value = '';
        obsEl.value = '';
        renderAbonos();
        recompute();
      };

      document.getElementById('vpAbonosBody').addEventListener('click', function (ev) {
        var b = ev.target.closest('.vp-del');
        if (!b) return;
        var id = parseInt(b.dataset.id || '0', 10);
        var idx = AB.findIndex(function (x) { return x.id === id; });
        if (idx >= 0) {
          AB.splice(idx, 1);
          renderAbonos();
          recompute();
        }
      });

      document.getElementById('vpHistBody').addEventListener('click', async function (ev) {
        var btn = ev.target.closest('.vp-refund');
        if (!btn) return;
        var venta = parseInt(btn.dataset.venta || '0', 10);
        var apl = parseInt(btn.dataset.apl || '0', 10);
        if (!venta || !apl) return;

        var motivo = window.prompt('Motivo de devolucion del abono:', '');
        if (motivo === null) return;
        if (!String(motivo || '').trim()) {
          showMsg('Aviso', 'Debes indicar un motivo.');
          return;
        }

        try {
          await postVentas('venta_devolver_abono', { venta_id: venta, aplicacion_id: apl, motivo: motivo });
          showMsg('Listo', 'Devolucion registrada.');
          window.location.reload();
        } catch (err) {
          showMsg('Error', mapApiError(err.message));
        }
      });

      document.getElementById('vpAbonoConfirm').onclick = async function () {
        clearModalAlert();

        if (!AB.length) {
          modalAlert('Agrega al menos un abono.', 'danger');
          return;
        }

        try {
          var payload = {
            venta_id: Number(H.id || 0),
            abonos_json: JSON.stringify(AB.map(function (a) {
              return {
                medio_id: a.medio_id,
                monto: a.monto,
                referencia: a.ref,
                observacion: a.obs
              };
            }))
          };
          var r = await postVentas('venta_abonar', payload);

          if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery('#vpAbonoModal').modal('hide');
          }

          var abonoIds = (r.nuevos || []).map(function (n) {
            return Number(n.abono_id || 0);
          }).filter(function (x) { return x > 0; });

          var qs = new URLSearchParams({
            action: 'voucher_pdf',
            kind: 'abono',
            venta_id: String(Number(H.id || 0)),
            size: 'ticket80'
          });
          if (abonoIds.length) {
            qs.set('abono_ids', abonoIds.join(','));
          }
          window.open(API_VENTAS + '?' + qs.toString(), 'voucher_pdf');

          if (Number(r.saldo || 0) <= 0) {
            showMsg('Listo', 'Venta saldada. Se registro el abono.');
          } else {
            showMsg('Listo', 'Abono registrado.');
          }

          window.location.reload();
        } catch (err) {
          modalAlert(mapApiError(err.message), 'danger');
        }
      };

      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
        window.jQuery('#vpAbonoModal').modal('show');
      } else {
        showMsg('Aviso', 'No se pudo abrir el modal de abonos.');
      }
    } catch (err) {
      showMsg('Error', mapApiError(err.message));
    }
  }

  document.addEventListener('click', function (ev) {
    var btnAbonar = ev.target.closest('.js-abonar');
    if (btnAbonar) {
      var ventaId = parseInt(btnAbonar.getAttribute('data-id') || '0', 10);
      if (!ventaId) return;
      openAbonar(ventaId);
      return;
    }

    var btnDetalle = ev.target.closest('.js-detalle');
    if (!btnDetalle) return;

    var id = btnDetalle.getAttribute('data-id');
    if (!id) return;

    var detRow = document.getElementById('det-' + id);
    if (!detRow) return;

    detRow.classList.toggle('d-none');
  });
})();
