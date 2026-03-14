// modules/reporte_ventas/index.js
// Abonar 1:1 con caja: modal, validaciones, voucher y mensajes.

(() => {
  'use strict';

  const RV_CTX = window.RV_CTX || {};
  const BASE_URL = String(RV_CTX.baseUrl || window.BASE_URL || '').replace(/\/+$/, '');
  const API_VENTAS = BASE_URL ? `${BASE_URL}/modules/caja/api_ventas.php` : '../caja/api_ventas.php';
  const API_CAJA = BASE_URL ? `${BASE_URL}/modules/caja/api.php` : '../caja/api.php';
  const EMPRESA_NOMBRE = String(RV_CTX.empresaNombre || 'Empresa');
  const USUARIO_NOMBRE = String(RV_CTX.usuarioNombre || 'Usuario');
  const EMPRESA_LOGO = String(RV_CTX.empresaLogo || '');

  let CAN_SELL = false;
  let SELL_REASON = '';
  let VOUCHER_CTX = null;

  const qs = (s, r = document) => r.querySelector(s);
  const qsa = (s, r = document) => Array.from(r.querySelectorAll(s));

  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
  function money(n) { return 'S/ ' + Number(n || 0).toFixed(2); }
  function num(v) { const n = Number(v); return Number.isFinite(n) ? n : 0; }
  function fmtDT(v) {
    if (!v) return '';
    const d = new Date(String(v).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return String(v);
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yy = d.getFullYear();
    const hh = String(d.getHours()).padStart(2, '0');
    const mi = String(d.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yy} ${hh}:${mi}`;
  }
  function pad6(n) { return String(parseInt(n || 0, 10)).padStart(6, '0'); }

  async function parseJsonResponse(r) {
    const txt = await r.text();
    let j;
    try { j = JSON.parse(txt); } catch (_) { throw new Error(txt ? txt.slice(0, 200) : 'Respuesta invalida del servidor'); }
    if (!j.ok) throw new Error(j.error || 'Error');
    return j;
  }
  async function vpGET(params) {
    const usp = new URLSearchParams(params || {});
    const r = await fetch(`${API_VENTAS}?${usp.toString()}`, { credentials: 'same-origin' });
    return parseJsonResponse(r);
  }
  async function vpPOST(action, payload) {
    const fd = new FormData();
    fd.append('accion', action);
    Object.entries(payload || {}).forEach(([k, v]) => fd.append(k, v));
    const r = await fetch(API_VENTAS, { method: 'POST', credentials: 'same-origin', body: fd });
    return parseJsonResponse(r);
  }
  async function cajaGET(params) {
    const usp = new URLSearchParams(params || {});
    const r = await fetch(`${API_CAJA}?${usp.toString()}`, { credentials: 'same-origin' });
    return parseJsonResponse(r);
  }

  function showMsg(title, html, type = 'success') {
    const mh = qs('#msgModal .modal-header');
    const mt = qs('#msgModalTitle');
    const mb = qs('#msgModalBody');
    if (!mh || !mt || !mb) {
      window.alert(`${title}: ${String(html || '').replace(/<[^>]*>/g, '')}`);
      return;
    }
    mh.classList.remove('bg-success', 'bg-danger', 'text-white');
    if (type === 'success') mh.classList.add('bg-success', 'text-white');
    if (type === 'danger') mh.classList.add('bg-danger', 'text-white');
    mt.textContent = title;
    mb.innerHTML = html;
    if (window.jQuery && jQuery.fn && jQuery.fn.modal) jQuery('#msgModal').modal('show');
    else window.alert(`${title}: ${mb.textContent}`);
  }

  function modalAlertContainer(modalId) {
    const id = String(modalId || '').replace(/^#/, '');
    return qs(`#${id} .modal-inline-alert`) || qs(`#${id}Alert`) || null;
  }
  function showInlineAlert(modalId, type, html) {
    const h = modalAlertContainer(modalId);
    if (!h) return;
    const icon = type === 'danger' ? 'fa-exclamation-triangle' : (type === 'warning' ? 'fa-exclamation-circle' : 'fa-info-circle');
    h.innerHTML = `
      <div class="alert alert-${type} alert-dismissible fade show mb-2" role="alert">
        <i class="fas ${icon} mr-1"></i> ${html}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>`;
    try { h.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (_) {}
  }
  function clearInlineAlert(modalId) {
    const h = modalAlertContainer(modalId);
    if (h) h.innerHTML = '';
  }

  function isRefRequiredError(msg) {
    const s = String(msg || '').toLowerCase();
    return s.includes('referencia obligatoria') || s.includes('requiere una referencia');
  }
  function mapApiError(msg) {
    const s = String(msg || '').toLowerCase();
    if (s.includes('no hay caja diaria abierta')) return 'Debes abrir la caja diaria de hoy para registrar pagos.';
    if (s.includes('medio de pago invalido') || s.includes('medio de pago inv')) return 'Selecciona un medio de pago valido.';
    if (s.includes('monto invalido')) return 'Ingresa un monto mayor a 0.00.';
    if (s.includes('al menos un abono')) return 'Debes registrar al menos un abono para completar la venta.';
    if (isRefRequiredError(s)) return 'Este medio exige referencia. Completala para continuar.';
    if (s.includes('excede el total') || s.includes('excede el saldo')) return 'El monto ingresado supera el saldo. Ingresa un monto menor o igual al saldo.';
    if (s.includes('venta no encontrada')) return 'No se encontro la venta.';
    if (s.includes('anulada')) return 'La venta esta anulada. No es posible registrar mas operaciones.';
    return 'Ocurrio un error. Revisa los datos e intentalo nuevamente.';
  }

  function setSellUI(canSell, reason) {
    CAN_SELL = !!canSell;
    SELL_REASON = reason || '';
    qsa('.js-abonar').forEach((b) => {
      b.disabled = !CAN_SELL;
      b.classList.toggle('disabled', !CAN_SELL);
      b.title = !CAN_SELL ? SELL_REASON : '';
    });
  }
  function applySellPolicyFromEstado(data) {
    const canSell = !!(
      data && data.cd && data.cd.estado === 'abierta'
    ) || !!(
      data && data.locks && data.locks.otra_diaria_abierta
    );
    let reason = '';
    if (!canSell) {
      if (!(data && data.cm && data.cm.existe) || data.cm.estado !== 'abierta') {
        reason = 'Debes abrir primero la caja mensual del periodo y luego la caja diaria de hoy.';
      }
      else reason = 'No hay ninguna caja diaria abierta. Abre la caja diaria de hoy para habilitar pagos.';
    }
    setSellUI(canSell, reason);
  }
  async function refreshEstado() {
    try {
      const j = await cajaGET({ action: 'estado' });
      applySellPolicyFromEstado(j);
    } catch (err) {
      setSellUI(false, 'No se pudo validar el estado de caja.');
      showMsg('Error', mapApiError(err.message || ''), 'danger');
    }
  }
  async function loadMediosPago() {
    const j = await cajaGET({ action: 'pos_medios_pago' });
    return (j.data || []).map((m) => {
      let req = (m.requiere_ref === 1 || m.requiere_ref === '1' || m.requiere_ref === true) ? 1 : 0;
      if (/efect/i.test(String(m.nombre || ''))) req = 0;
      return { ...m, requiere_ref: req };
    });
  }

  function buildClienteForVoucher(H) {
    const cli = (H && H.cliente) ? H.cliente : {};
    const ctr = (H && H.contratante) ? H.contratante : {};
    const isRuc = String(cli.doc_tipo || '').toUpperCase() === 'RUC';
    if (isRuc) {
      return {
        tipo_persona: 'JURIDICA', tipo: 'RUC', doc: cli.doc || '', razon: cli.nombre || '',
        nombres: '', apellidos: '', telefono: cli.telefono || ctr.telefono || ''
      };
    }
    return {
      tipo_persona: 'NATURAL', tipo: cli.doc_tipo || '', doc: cli.doc || '', razon: '',
      nombres: cli.nombre || '', apellidos: '', telefono: cli.telefono || ctr.telefono || ''
    };
  }

  function normalizeVoucherCtx(v) {
    const kind = String((v && v.kind) || 'venta').toLowerCase() === 'abono' ? 'abono' : 'venta';
    const ventaId = Number((v && v.venta_id) || 0);
    const abonoIds = Array.isArray(v && v.abono_ids)
      ? v.abono_ids.map((x) => parseInt(x, 10)).filter((x) => Number.isFinite(x) && x > 0)
      : [];
    return { kind, venta_id: Number.isFinite(ventaId) ? Math.trunc(ventaId) : 0, abono_ids: abonoIds };
  }
  function buildVoucherPdfUrl(size) {
    if (!VOUCHER_CTX || !(VOUCHER_CTX.venta_id > 0)) return '';
    const s = (size === 'a4' || size === 'ticket58' || size === 'ticket80') ? size : 'ticket80';
    const p = new URLSearchParams({
      action: 'voucher_pdf',
      venta_id: String(VOUCHER_CTX.venta_id),
      kind: VOUCHER_CTX.kind === 'abono' ? 'abono' : 'venta',
      size: s
    });
    if (Array.isArray(VOUCHER_CTX.abono_ids) && VOUCHER_CTX.abono_ids.length) p.set('abono_ids', VOUCHER_CTX.abono_ids.join(','));
    return `${API_VENTAS}?${p.toString()}`;
  }

  function openVoucher(v) {
    VOUCHER_CTX = normalizeVoucherCtx(v || {});
    const isAbono = VOUCHER_CTX.kind === 'abono';

    const el = qs('#voucherBody');
    if (!el) return;

    const titleEl = qs('#voucherModalTitle');
    if (titleEl) titleEl.innerHTML = `<i class="fas fa-receipt mr-2"></i>${isAbono ? 'Voucher de abono' : 'Voucher de venta'}`;

    const logoHtml = EMPRESA_LOGO ? `<div class="v-head-left"><img class="v-logo" src="${esc(EMPRESA_LOGO)}" alt="Logo"></div>` : '';

    const cliente = v.cliente || {};
    const contratante = v.contratante || {};
    const conductor = v.conductor || {};

    let clienteBlock = '';
    if (cliente.tipo_persona === 'JURIDICA') {
      clienteBlock = `
        <div class="v-grid">
          <div class="text-muted">Documento</div> <div>RUC ${esc(cliente.doc)}</div>
          <div class="text-muted">Razon social</div> <div>${esc(cliente.razon)}</div>
          <div class="text-muted">Telefono</div> <div>${esc(cliente.telefono || '-')}</div>
        </div>`;
    } else {
      clienteBlock = `
        <div class="v-grid">
          <div class="text-muted">Documento</div> <div>${esc(cliente.tipo)} ${esc(cliente.doc)}</div>
          <div class="text-muted">Nombre</div> <div>${esc(cliente.nombres)} ${esc(cliente.apellidos)}</div>
          <div class="text-muted">Telefono</div> <div>${esc(cliente.telefono || '-')}</div>
        </div>`;
    }

    const hasContratante = !!((contratante.tipo && contratante.doc) || contratante.nombres || contratante.apellidos || contratante.telefono);
    const contratanteBlock = hasContratante ? `
      <div class="v-box">
        <div class="v-title">Contratante</div>
        <div class="v-grid">
          <div class="text-muted">Documento</div> <div>${esc(contratante.tipo || '')} ${esc(contratante.doc || '')}</div>
          <div class="text-muted">Nombre</div> <div>${esc(contratante.nombres || '')} ${esc(contratante.apellidos || '')}</div>
          <div class="text-muted">Telefono</div> <div>${esc(contratante.telefono || '-')}</div>
        </div>
      </div>` : '';

    const condDoc = (conductor.tipo && conductor.doc) ? `${esc(conductor.tipo)} ${esc(conductor.doc)}` : '-';
    const conductorBlock = `
      <div class="v-grid">
        <div class="text-muted">Documento</div> <div>${condDoc}</div>
        <div class="text-muted">Nombre</div> <div>${esc(conductor.nombres || '')} ${esc(conductor.apellidos || '')}</div>
        <div class="text-muted">Telefono</div> <div>${esc(conductor.telefono || '-')}</div>
      </div>`;

    const itemsRows = (v.items || []).length
      ? (v.items || []).map((it) => `
          <tr>
            <td>${esc(it.nombre)}<div class="text-muted small">x${Number(it.qty || 0)} - ${money(it.precio)}</div></td>
            <td class="text-end">${money(Number(it.qty || 0) * Number(it.precio || 0))}</td>
          </tr>
        `).join('')
      : '<tr><td colspan="2" class="text-muted small">- Sin items -</td></tr>';

    const abonoRows = (v.abonos || []).length
      ? (v.abonos || []).map((a) => `
          <tr><td>${esc(a.medio)}<div class="text-muted small">${esc(a.ref || '')}</div></td><td class="text-end">${money(a.monto)}</td></tr>
        `).join('')
      : '<tr><td colspan="2" class="text-muted small">- Sin abonos -</td></tr>';

    el.innerHTML = `
      <div class="voucher">
        <div class="v-box"><div class="v-head">${logoHtml}<div class="v-head-right">
          <div class="fw-bold">${esc(v.empresa || EMPRESA_NOMBRE)}</div>
          <div class="text-muted small">${esc(v.fecha || '')}</div>
          <div class="small">Ticket: <strong>${esc(v.ticket || '')}</strong> - Cajero: <strong>${esc(v.cajero || USUARIO_NOMBRE)}</strong></div>
        </div></div></div>

        <div class="v-box"><div class="v-title">Cliente</div>${clienteBlock}</div>
        ${contratanteBlock}
        <div class="v-box"><div class="v-title">Conductor</div>${conductorBlock}</div>
        <div class="v-box"><div class="v-title">${isAbono ? 'Detalle de venta' : 'Items'}</div><table><tbody>${itemsRows}</tbody></table></div>
        <div class="v-box"><div class="v-title">${isAbono ? 'Abonos registrados' : 'Abonos'}</div><table><tbody>${abonoRows}</tbody></table></div>
        <div class="v-box">
          <div class="t"><div class="fw-bold">Total</div><div class="v-total">${money(v.totales && v.totales.total)}</div></div>
          <div class="t"><div>Pagado</div><div class="v-total">${money(v.totales && v.totales.pagado)}</div></div>
          <div class="t"><div>Saldo</div><div class="v-total">${money(v.totales && v.totales.saldo)}</div></div>
        </div>
      </div>`;

    if (window.jQuery && jQuery.fn && jQuery.fn.modal) jQuery('#voucherModal').modal('show');
  }

  function updateMainRowAfterAbono(ventaId, data) {
    const row = qs(`tr.js-row[data-id="${Number(ventaId)}"]`);
    if (!row) return;

    const saldo = num(data && data.saldo);
    const pagado = num(data && data.pagado);
    const c = row.children;

    if (c[7]) c[7].textContent = money(pagado);
    if (c[8]) c[8].textContent = money(saldo);

    if (c[9]) {
      const badge = saldo > 0.000001
        ? '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Pendiente</span>'
        : '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Pagado</span>';
      const meta = c[9].querySelector('.small');
      const metaHtml = meta ? meta.outerHTML : '<div class="small text-muted mt-1"><i class="fas fa-wallet mr-1"></i>Con abonos</div>';
      c[9].innerHTML = badge + metaHtml;
    }

    const btn = row.querySelector('.js-abonar');
    if (saldo <= 0.000001) {
      if (btn) btn.remove();
    } else if (btn) {
      btn.disabled = !CAN_SELL;
      btn.classList.toggle('disabled', !CAN_SELL);
    }
  }

  function ensureAbonoModal() {
    if (qs('#vpAbonoModal')) return;

    const m = document.createElement('div');
    m.innerHTML = `
<div class="modal fade" id="vpAbonoModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-cash-register mr-2"></i>Completar pago</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="modal-inline-alert" id="vpAbonoAlert"></div>
        <div id="vpAbonoBody"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success btn-sm" id="vpAbonoConfirm"><i class="fas fa-check-circle mr-1"></i>Registrar abonos</button>
      </div>
    </div>
  </div>
</div>`;
    document.body.appendChild(m.firstElementChild);

    if (!qs('#vpAbonoStyles')) {
      const st = document.createElement('style');
      st.id = 'vpAbonoStyles';
      st.textContent = '.vp-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}@media (max-width:768px){.vp-grid{grid-template-columns:1fr}}.vp-box{border:1px solid #e5e7eb;border-radius:12px;padding:10px;margin-bottom:8px;}';
      document.head.appendChild(st);
    }
  }

  async function openAbonar(ventaId) {
    if (!CAN_SELL) {
      showMsg('Aviso', SELL_REASON || 'Abre la caja diaria de hoy para registrar abonos.', 'danger');
      return;
    }

    ensureAbonoModal();
    clearInlineAlert('vpAbonoModal');

    try {
      const j = await vpGET({ action: 'venta_detalle', id: ventaId });
      const H = j.cabecera || {};
      const ABH = j.abonos || [];
      const ITEMS = j.items || [];
      const medios = await loadMediosPago();
      const ctr = H.contratante || {};

      const hist = ABH.length
        ? ABH.map((a) => `
          <tr>
            <td>${esc(a.medio || '')}</td><td class="text-end">${money(a.monto)}</td><td class="text-end">${money(a.monto_aplicado)}</td>
            <td>${esc(a.referencia || '')}</td><td class="small text-muted">${esc(fmtDT(a.fecha) || '')}</td>
            <td class="text-end"><button class="btn btn-sm btn-outline-danger vp-refund" data-venta="${Number(H.id || 0)}" data-apl="${Number(a.aplicacion_id || 0)}"><i class="fas fa-undo"></i></button></td>
          </tr>`).join('')
        : '<tr><td colspan="6" class="text-muted small">- Sin abonos previos -</td></tr>';

      const body = qs('#vpAbonoBody');
      body.innerHTML = `
        <div class="vp-grid">
          <div class="vp-box">
            <div class="fw-bold mb-1">Venta</div>
            <div class="small">Ticket: <strong>${esc(H.ticket || '')}</strong></div>
            <div class="small">Fecha: ${esc(fmtDT(H.fecha) || '-')}</div>
            <div class="small">Cliente: ${esc((H.cliente || {}).nombre || '')}</div>
            <div class="small">Telefono cliente: ${esc((H.cliente || {}).telefono || '-')}</div>
            <div class="small">Contratante: ${esc(`${ctr.nombres || ''} ${ctr.apellidos || ''}`.trim() || '-')}</div>
          </div>
          <div class="vp-box">
            <div class="fw-bold mb-1">Saldo</div>
            <div class="display-6" id="vpSaldoNow">${money(H.saldo)}</div>
            <div class="small text-muted">Total: ${money(H.total)} - Pagado: ${money(H.pagado)}</div>
          </div>
        </div>

        <div class="vp-box">
          <div class="fw-bold mb-1">Registrar nuevos abonos</div>
          <div class="row g-2 align-items-end mb-2">
            <div class="col-12 col-sm-3"><label class="form-label small mb-1">Medio de pago</label><select id="vpMedio" class="form-control form-control-sm">${medios.map((m) => `<option value="${Number(m.id || 0)}" data-req="${m.requiere_ref ? '1' : '0'}">${esc(m.nombre || '')}</option>`).join('')}</select></div>
            <div class="col-12 col-sm-2"><label class="form-label small mb-1">Monto</label><input id="vpMonto" type="number" step="0.01" min="0" class="form-control form-control-sm"></div>
            <div class="col-12 col-sm-3"><label class="form-label small mb-1">Referencia</label><input id="vpRef" class="form-control form-control-sm" maxlength="80" placeholder="segun el medio"></div>
            <div class="col-12 col-sm-4"><label class="form-label small mb-1">Detalle / Nota</label><input id="vpObs" class="form-control form-control-sm" maxlength="255"></div>
            <div class="col-12"><button id="vpAddAbono" type="button" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Agregar abono</button></div>
          </div>

          <div class="table-responsive"><table class="table table-sm table-striped align-middle mb-2"><thead class="table-light"><tr><th style="width:40%">Medio</th><th style="width:15%" class="text-end">Monto</th><th style="width:25%">Referencia</th><th style="width:15%">Agregado</th><th style="width:5%"></th></tr></thead><tbody id="vpAbonosBody"><tr><td colspan="5" class="text-muted small">- Sin abonos nuevos -</td></tr></tbody></table></div>
          <div class="d-flex align-items-center justify-content-end gap-3"><div class="small">Abonos nuevos: <span id="vpAbonosTotal">S/ 0.00</span></div><div class="fw-bold">Saldo estimado: <span id="vpSaldoEst">${money(H.saldo)}</span></div></div>
        </div>

        <div class="vp-box">
          <div class="fw-bold mb-1">Historial de abonos</div>
          <div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr><th>Medio</th><th class="text-end">Monto</th><th class="text-end">Aplicado</th><th>Ref.</th><th>Fecha</th><th></th></tr></thead><tbody id="vpHistBody">${hist}</tbody></table></div>
        </div>`;

      const AB = [];
      const medioSel = qs('#vpMedio');
      const montoEl = qs('#vpMonto');
      const refEl = qs('#vpRef');
      const obsEl = qs('#vpObs');

      const selectedMedio = () => (medioSel && medioSel.selectedOptions ? medioSel.selectedOptions[0] : null);
      const medioRequiereRef = () => {
        const opt = selectedMedio();
        return !!(opt && opt.dataset && opt.dataset.req === '1');
      };

      function updateRefPH() {
        const req = medioRequiereRef();
        refEl.placeholder = req ? 'Obligatoria para este medio' : 'Opcional';
        if (!req) refEl.classList.remove('is-invalid');
      }
      function renderAbonos() {
        const tb = qs('#vpAbonosBody');
        if (!AB.length) { tb.innerHTML = '<tr><td colspan="5" class="text-muted small">- Sin abonos nuevos -</td></tr>'; return; }
        tb.innerHTML = AB.map((a) => `<tr><td>${esc(a.medio)}</td><td class="text-end">${money(a.monto)}</td><td>${esc(a.ref || '')}</td><td class="small text-muted">${esc(a.ts)}</td><td><button class="btn btn-link btn-sm text-danger vp-del" data-id="${a.id}" title="Quitar"><i class="fas fa-times"></i></button></td></tr>`).join('');
      }
      function recompute() {
        const abonado = AB.reduce((s, x) => s + x.monto, 0);
        const est = Math.max(0, num(H.saldo) - abonado);
        qs('#vpAbonosTotal').textContent = money(abonado);
        qs('#vpSaldoEst').textContent = money(est);
      }

      medioSel.addEventListener('change', updateRefPH);
      refEl.addEventListener('input', () => {
        if (medioRequiereRef() && !String(refEl.value || '').trim()) refEl.classList.add('is-invalid');
        else refEl.classList.remove('is-invalid');
      });
      montoEl.addEventListener('input', () => {
        const monto = num(montoEl.value);
        const abonado = AB.reduce((s, x) => s + x.monto, 0);
        const max = Math.max(0, num(H.saldo) - abonado);
        if (!Number.isFinite(monto) || monto <= 0 || monto > max) montoEl.classList.add('is-invalid');
        else montoEl.classList.remove('is-invalid');
      });
      updateRefPH();

      qs('#vpAddAbono').onclick = () => {
        clearInlineAlert('vpAbonoModal');
        montoEl.classList.remove('is-invalid');
        refEl.classList.remove('is-invalid');

        const opt = selectedMedio();
        const id = parseInt(medioSel.value || '0', 10);
        if (!id || !opt) { showInlineAlert('vpAbonoModal', 'danger', 'Selecciona un medio de pago.'); return; }

        const monto = num(montoEl.value);
        if (!Number.isFinite(monto) || monto <= 0) {
          montoEl.classList.add('is-invalid');
          showInlineAlert('vpAbonoModal', 'danger', 'Ingresa un monto mayor a 0.00.');
          return;
        }

        const abonadoNuevo = AB.reduce((s, x) => s + x.monto, 0);
        const maxPermitido = Math.max(0, num(H.saldo) - abonadoNuevo);
        if (monto > maxPermitido) {
          montoEl.classList.add('is-invalid');
          showInlineAlert('vpAbonoModal', 'danger', 'El monto ingresado supera el saldo. Ingresa un monto menor o igual al saldo.');
          return;
        }

        const ref = String(refEl.value || '').trim();
        if (medioRequiereRef() && !ref) {
          refEl.classList.add('is-invalid');
          showInlineAlert('vpAbonoModal', 'danger', 'Este medio exige referencia. Completala para continuar.');
          return;
        }

        AB.push({
          id: Date.now(),
          medio_id: id,
          medio: opt.textContent || '',
          monto,
          ref,
          obs: String(obsEl.value || '').trim(),
          ts: new Date().toLocaleString()
        });

        montoEl.value = '';
        refEl.value = '';
        obsEl.value = '';
        renderAbonos();
        recompute();
      };

      qs('#vpAbonosBody').addEventListener('click', (e) => {
        const btn = e.target.closest('.vp-del');
        if (!btn) return;
        const id = parseInt(btn.dataset.id || '0', 10);
        const i = AB.findIndex((x) => x.id === id);
        if (i >= 0) { AB.splice(i, 1); renderAbonos(); recompute(); }
      });

      qs('#vpAbonoConfirm').onclick = async () => {
        if (!CAN_SELL) { showMsg('Aviso', SELL_REASON || 'Abre la caja diaria de hoy para registrar abonos.', 'danger'); return; }
        if (!AB.length) { showMsg('Aviso', 'Agrega al menos un abono.', 'danger'); return; }

        try {
          const payload = {
            venta_id: Number(H.id || 0),
            abonos_json: JSON.stringify(AB.map((a) => ({ medio_id: a.medio_id, monto: a.monto, referencia: a.ref, observacion: a.obs })))
          };
          const r = await vpPOST('venta_abonar', payload);

          if (window.jQuery && jQuery.fn && jQuery.fn.modal) jQuery('#vpAbonoModal').modal('hide');

          const clienteVoucher = buildClienteForVoucher(H);
          const ctrVoucher = {
            tipo: ctr.doc_tipo || '', doc: ctr.doc || '', nombres: ctr.nombres || '', apellidos: ctr.apellidos || '', telefono: ctr.telefono || ''
          };
          const cond = H.conductor || {};
          const conductorVoucher = {
            tipo: cond.doc_tipo || '', doc: cond.doc_numero || '', nombres: cond.nombres || '', apellidos: cond.apellidos || '', telefono: cond.telefono || ''
          };
          const abonosVoucher = (r.nuevos || []).map((n) => ({
            medio: n.medio,
            monto: n.monto,
            ref: `Recibo ABN-${pad6(n.abono_id)}${n.referencia ? ` - ${n.referencia}` : ''}`
          }));

          openVoucher({
            kind: 'abono',
            venta_id: Number(H.id || 0),
            abono_ids: (r.nuevos || []).map((n) => Number(n.abono_id || 0)).filter((x) => x > 0),
            empresa: EMPRESA_NOMBRE,
            ticket: r.ticket || H.ticket || '',
            fecha: new Date().toLocaleString(),
            cajero: USUARIO_NOMBRE,
            cliente: clienteVoucher,
            contratante: ctrVoucher,
            conductor: conductorVoucher,
            items: ITEMS.map((it) => ({ nombre: it.servicio_nombre || 'Servicio', qty: Number(it.cantidad || 0), precio: Number(it.precio_unitario || 0) })),
            abonos: abonosVoucher,
            totales: { total: num(r.total), pagado: num(r.pagado), saldo: num(r.saldo) }
          });

          updateMainRowAfterAbono(H.id, r);
          AB.splice(0, AB.length);
          renderAbonos();
          recompute();

          if (num(r.saldo) <= 0.000001) showMsg('Listo', 'Venta saldada. Se ha generado el comprobante de abono.');
          else showMsg('Listo', 'Abonos registrados. Se ha generado el comprobante de abono.');
        } catch (err) {
          const raw = String(err.message || '');
          if (isRefRequiredError(raw)) showInlineAlert('vpAbonoModal', 'danger', 'Este medio exige referencia. Completala para continuar.');
          else showInlineAlert('vpAbonoModal', 'danger', mapApiError(raw));
        }
      };

      qs('#vpHistBody').addEventListener('click', async (e) => {
        const btn = e.target.closest('.vp-refund');
        if (!btn) return;
        const venta = parseInt(btn.dataset.venta || '0', 10);
        const apl = parseInt(btn.dataset.apl || '0', 10);
        if (!venta || !apl) return;

        const motivo = window.prompt('Motivo de devolucion del abono:', '');
        if (motivo === null) return;
        if (!String(motivo || '').trim()) { showMsg('Aviso', 'Debes indicar un motivo.', 'danger'); return; }

        try {
          const r = await vpPOST('venta_devolver_abono', { venta_id: venta, aplicacion_id: apl, motivo });
          updateMainRowAfterAbono(venta, r);
          showMsg('Listo', 'Devolucion registrada.');
          openAbonar(venta);
        } catch (err) {
          showMsg('Error', mapApiError(err.message || ''), 'danger');
        }
      });

      if (window.jQuery && jQuery.fn && jQuery.fn.modal) jQuery('#vpAbonoModal').modal('show');
      else showMsg('Aviso', 'No se pudo abrir el modal de abonos.', 'danger');
    } catch (err) {
      showMsg('Error', mapApiError(err.message || ''), 'danger');
    }
  }

  document.addEventListener('click', (e) => {
    const d = e.target.closest('.js-detalle');
    if (d) {
      const id = d.getAttribute('data-id');
      if (!id) return;
      const det = qs(`#det-${id}`);
      if (det) det.classList.toggle('d-none');
      return;
    }

    const a = e.target.closest('.js-abonar');
    if (!a) return;
    const id = parseInt(a.getAttribute('data-id') || '0', 10);
    if (!id) return;

    (async () => {
      await refreshEstado();
      if (!CAN_SELL) { showMsg('Aviso', SELL_REASON || 'Abre la caja diaria de hoy para registrar abonos.', 'danger'); return; }
      openAbonar(id);
    })();
  });

  document.addEventListener('click', (e) => {
    const b = e.target.closest('#voucherPrint');
    if (!b) return;
    e.preventDefault();
    e.stopImmediatePropagation();

    const sizeSel = qs('#voucherSize');
    const size = (sizeSel && sizeSel.value) || 'ticket80';
    const url = buildVoucherPdfUrl(size);
    if (url) {
      const w = window.open(url, 'voucher_pdf');
      if (!w) showMsg('Aviso', 'Tu navegador bloqueo la ventana del PDF. Habilita pop-ups para este sitio.', 'danger');
    }
  });

  (function fixModalStacking() {
    if (!(window.jQuery && jQuery.fn && jQuery.fn.modal)) return;
    jQuery(document).on('show.bs.modal', '.modal', function () {
      const open = jQuery('.modal:visible');
      const z = 1050 + (10 * open.length);
      jQuery(this).css('z-index', z);
      setTimeout(() => {
        jQuery('.modal-backdrop').not('.modal-stack').css('z-index', z - 1).addClass('modal-stack');
      }, 0);
    });
    jQuery(document).on('hidden.bs.modal', '.modal', () => {
      if (jQuery('.modal:visible').length === 0) jQuery('.modal-backdrop').removeClass('modal-stack');
    });
  })();

  document.addEventListener('DOMContentLoaded', async () => {
    setSellUI(false, 'Verificando estado de caja...');
    await refreshEstado();
  });
})();
