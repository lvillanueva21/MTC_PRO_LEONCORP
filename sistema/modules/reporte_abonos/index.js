// modules/reporte_abonos/index.js
// Comprobantes original/actual para abonos + detalle expandible.

(() => {
  'use strict';

  const RA_CTX = window.RA_CTX || {};
  const BASE_URL = String(RA_CTX.baseUrl || window.BASE_URL || '').replace(/\/+$/, '');
  const API_VENTAS = BASE_URL ? `${BASE_URL}/modules/caja/api_ventas.php` : '../caja/api_ventas.php';
  const EMPRESA_NOMBRE = String(RA_CTX.empresaNombre || 'Empresa');
  const USUARIO_NOMBRE = String(RA_CTX.usuarioNombre || 'Usuario');
  const EMPRESA_LOGO = String(RA_CTX.empresaLogo || '');

  let VOUCHER_CTX = null;

  const qs = (s, r = document) => r.querySelector(s);

  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function money(n) {
    return 'S/ ' + Number(n || 0).toFixed(2);
  }

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

  function mapApiError(msg) {
    const s = String(msg || '').toLowerCase();
    if (s.includes('venta no encontrada')) return 'No se encontro la venta asociada al comprobante.';
    if (s.includes('venta invalida')) return 'No se pudo resolver la venta para este comprobante.';
    if (s.includes('no se encontraron abonos')) return 'No se encontraron abonos para este comprobante.';
    return 'Ocurrio un error al cargar el comprobante.';
  }

  function normalizeVoucherCtx(v) {
    const kind = String((v && v.kind) || 'abono').toLowerCase() === 'venta' ? 'venta' : 'abono';
    const scopeRaw = String((v && v.scope) || (v && v.alcance_label) || 'actual').toLowerCase();
    const scope = scopeRaw === 'original' ? 'original' : 'actual';
    const ventaId = Number((v && v.venta_id) || 0);
    const abonoId = Number((v && v.abono_id) || 0);
    const abonoIds = Array.isArray(v && v.abono_ids)
      ? v.abono_ids.map((x) => parseInt(x, 10)).filter((x) => Number.isFinite(x) && x > 0)
      : [];
    return {
      kind,
      scope,
      venta_id: Number.isFinite(ventaId) ? Math.trunc(ventaId) : 0,
      abono_id: Number.isFinite(abonoId) ? Math.trunc(abonoId) : 0,
      abono_ids: abonoIds
    };
  }

  function buildVoucherPdfUrl(size) {
    if (!VOUCHER_CTX) return '';
    const s = (size === 'a4' || size === 'ticket58' || size === 'ticket80') ? size : 'ticket80';
    const p = new URLSearchParams({
      action: 'voucher_pdf',
      kind: VOUCHER_CTX.kind === 'venta' ? 'venta' : 'abono',
      scope: VOUCHER_CTX.scope === 'original' ? 'original' : 'actual',
      presentation: 'auditoria',
      size: s
    });
    if (VOUCHER_CTX.venta_id > 0) p.set('venta_id', String(VOUCHER_CTX.venta_id));
    if (VOUCHER_CTX.abono_id > 0) p.set('abono_id', String(VOUCHER_CTX.abono_id));
    if (Array.isArray(VOUCHER_CTX.abono_ids) && VOUCHER_CTX.abono_ids.length) p.set('abono_ids', VOUCHER_CTX.abono_ids.join(','));
    return `${API_VENTAS}?${p.toString()}`;
  }

  async function fetchVoucherPreview(ctx) {
    const c = normalizeVoucherCtx(ctx || {});
    const params = {
      action: 'voucher_preview',
      kind: c.kind === 'venta' ? 'venta' : 'abono',
      scope: c.scope === 'original' ? 'original' : 'actual',
      presentation: 'auditoria'
    };
    if (c.venta_id > 0) params.venta_id = String(c.venta_id);
    if (c.abono_id > 0) params.abono_id = String(c.abono_id);
    if (Array.isArray(c.abono_ids) && c.abono_ids.length) params.abono_ids = c.abono_ids.join(',');
    const j = await vpGET(params);
    return j.payload || null;
  }

  function openVoucher(v) {
    VOUCHER_CTX = normalizeVoucherCtx(v || {});
    const isAbono = VOUCHER_CTX.kind === 'abono';
    const alcanceLabel = String(v && v.alcance_label ? v.alcance_label : (VOUCHER_CTX.scope === 'original' ? 'ORIGINAL' : 'ACTUAL'));
    const exactitud = String((v && v.exactitud) || 'EXACTO').toUpperCase();
    const alcanceHtml = exactitud === 'APROXIMADO'
      ? `${alcanceLabel} (APROXIMADO)`
      : alcanceLabel;

    const el = qs('#voucherBody');
    if (!el) return;

    const titleEl = qs('#voucherModalTitle');
    if (titleEl) titleEl.innerHTML = `<i class="fas fa-receipt mr-2"></i>${isAbono ? 'Voucher de abono' : 'Voucher de venta'} <span class="badge badge-secondary ml-2">${esc(alcanceHtml)}</span>`;

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
          <div class="small">Ticket: <strong>${esc(v.ticket || '')}</strong> - Operacion por: <strong>${esc(v.cajero || USUARIO_NOMBRE)}</strong></div>
          ${(v.reimpreso_por ? `<div class="small text-muted">Reimpreso por: <strong>${esc(v.reimpreso_por)}</strong></div>` : '')}
          ${(String(alcanceLabel).toUpperCase() === 'ACTUAL' && (v.estado_venta_texto || v.estado_venta) ? `<div class="small text-muted">Estado actual: <strong>${esc(v.estado_venta_texto || v.estado_venta)}</strong></div>` : '')}
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
          ${(Number(v.totales && v.totales.devuelto) > 0 ? `<div class="t"><div>Devuelto</div><div class="v-total">${money(v.totales.devuelto)}</div></div>` : '')}
        </div>
      </div>`;

    if (window.jQuery && jQuery.fn && jQuery.fn.modal) jQuery('#voucherModal').modal('show');
    else showMsg('Aviso', 'No se pudo abrir la vista previa del comprobante.', 'danger');
  }

  document.addEventListener('click', (ev) => {
    const bOrig = ev.target.closest('.js-voucher-original');
    if (bOrig) {
      ev.preventDefault();
      ev.stopPropagation();
      const abonoId = parseInt(bOrig.getAttribute('data-abono-id') || '0', 10);
      const ventaId = parseInt(bOrig.getAttribute('data-venta-id') || '0', 10);
      if (!abonoId && !ventaId) return;
      (async () => {
        try {
          const payload = await fetchVoucherPreview({
            kind: 'abono',
            scope: 'original',
            venta_id: ventaId,
            abono_id: abonoId
          });
          if (!payload) throw new Error('No se pudo cargar comprobante.');
          openVoucher({ ...payload, scope: 'original', venta_id: ventaId, abono_id: abonoId });
        } catch (err) {
          showMsg('Error', mapApiError(err.message || ''), 'danger');
        }
      })();
      return;
    }

    const bAct = ev.target.closest('.js-voucher-actual');
    if (bAct) {
      ev.preventDefault();
      ev.stopPropagation();
      const abonoId = parseInt(bAct.getAttribute('data-abono-id') || '0', 10);
      const ventaId = parseInt(bAct.getAttribute('data-venta-id') || '0', 10);
      if (!abonoId && !ventaId) return;
      (async () => {
        try {
          const payload = await fetchVoucherPreview({
            kind: 'abono',
            scope: 'actual',
            venta_id: ventaId,
            abono_id: abonoId
          });
          if (!payload) throw new Error('No se pudo cargar comprobante.');
          openVoucher({ ...payload, scope: 'actual', venta_id: ventaId, abono_id: abonoId });
        } catch (err) {
          showMsg('Error', mapApiError(err.message || ''), 'danger');
        }
      })();
      return;
    }
  });

  document.addEventListener('click', (ev) => {
    const bPrint = ev.target.closest('#voucherPrint');
    if (!bPrint) return;
    ev.preventDefault();
    ev.stopImmediatePropagation();
    const sizeSel = qs('#voucherSize');
    const size = (sizeSel && sizeSel.value) || 'ticket80';
    const url = buildVoucherPdfUrl(size);
    if (!url) {
      showMsg('Aviso', 'No hay contexto de comprobante para imprimir.', 'danger');
      return;
    }
    const w = window.open(url, 'voucher_pdf');
    if (!w) showMsg('Aviso', 'Tu navegador bloqueo la ventana del PDF. Habilita pop-ups para este sitio.', 'danger');
  });

  document.addEventListener('click', function (ev) {
    if (ev.target.closest('.js-voucher-original') || ev.target.closest('.js-voucher-actual')) return;
    const tr = ev.target.closest('tr.js-row');
    if (!tr) return;
    if (ev.target.closest('button, a, input, select, textarea, label')) return;
    const id = tr.getAttribute('data-id');
    if (!id) return;
    const det = document.getElementById('det-' + id);
    if (!det) return;
    det.classList.toggle('d-none');
  });
})();
