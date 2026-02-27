// /modules/caja/ventas_pendientes.js
(() => {
  const VP_API = `${BASE_URL}/modules/caja/api_ventas.php`;

  // ----- Helpers de fetch -----
    async function vpGET(params) {
    const usp = new URLSearchParams(params);
    const r = await fetch(`${VP_API}?${usp.toString()}`, { credentials: 'same-origin' });
    const txt = await r.text();
    let j;
    try { j = JSON.parse(txt); } catch (_) {
      throw new Error(txt ? txt.slice(0,200) : 'Respuesta no válida del servidor');
    }
    if (!j.ok) throw new Error(j.error || 'Error');
    return j;
  }

  async function vpPOST(action, payload) {
    const fd = new FormData();
    fd.append('accion', action);
    Object.entries(payload || {}).forEach(([k,v]) => fd.append(k, v));
    const r = await fetch(VP_API, { method: 'POST', credentials: 'same-origin', body: fd });
    const j = await r.json();
    if (!j.ok) throw new Error(j.error || 'Error');
    return j;
  }

// ==== Alertas embebidas en modales de Ventas Pendientes ====
function vpShowInlineAlert(modalId, type, html){
  const holder = document.querySelector(`#${modalId} .modal-inline-alert`) || document.querySelector(`#${modalId.replace('#','')}Alert`);
  if(!holder) return;
  const icon = (type==='danger') ? 'fa-exclamation-triangle' : (type==='warning' ? 'fa-exclamation-circle' : 'fa-info-circle');
  holder.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show mb-2" role="alert">
      <i class="fas ${icon} me-1"></i> ${html}
      <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close" onclick="this.closest('.alert').remove()">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>`;
  try{ holder.scrollIntoView({behavior:'smooth', block:'center'}); }catch(_){}
}
function vpClearInlineAlert(modalId){
  const holder = document.querySelector(`#${modalId} .modal-inline-alert`) || document.querySelector(`#${modalId.replace('#','')}Alert`);
  if(holder) holder.innerHTML = '';
}
function vpMapApiError(msg){
  const s = String(msg||'').toLowerCase();
  if (s.includes('no hay caja diaria abierta')) return 'Debes abrir la caja diaria de hoy para registrar pagos.';
  if (s.includes('medio de pago inválido')) return 'Selecciona un medio de pago válido.';
  if (s.includes('monto inválido')) return 'Ingresa un monto mayor a 0.00.';
  if (s.includes('referencia obligatoria')) return 'Este medio exige referencia. Complétala para continuar.';
  if (s.includes('excede el saldo')) return 'El monto ingresado supera el saldo. Ingresa un monto menor o igual al saldo.';
  if (s.includes('venta no encontrada')) return 'No se encontró la venta.';
  if (s.includes('anulada')) return 'La venta está anulada. No es posible registrar más operaciones.';
  return 'Ocurrió un error. Revisa los datos e inténtalo nuevamente.';
}

// Contador: escribe "Mostrando A–B de N • N resultados (página X de Y)"
function vpRenderCounter(total, page, per, currentCount){
  const el = document.querySelector('#vpCounter');
  if(!el) return;
  if (total<=0){ el.textContent = 'Sin resultados'; return; }
  const pages = Math.max(1, Math.ceil(total / per));
  const a = (page-1)*per + (currentCount>0 ? 1 : 0);
  const b = Math.min(total, (page-1)*per + currentCount);
  el.textContent = `Mostrando ${a}–${b} de ${total} • ${total} resultados (página ${page} de ${pages})`;
}

  // =========================================================
  // UI: referencias
  // =========================================================
  const card = document.querySelector('#vpCard');
  if (!card) return; // si no existe el contenedor, no hacemos nada

  const qInput = document.querySelector('#vpQ');
  const qClear = document.querySelector('#vpClear');
  const tbody  = document.querySelector('#vpTBody');
  const pager  = document.querySelector('#vpPager');
  const estadoBtns = Array.from(document.querySelectorAll('[data-vp-estado]'));

  const STATE = {
    q: '',
    estado: 'pending',
    page: 1,
    per: 5,     // <- paginación por defecto de 5 en 5
    total: 0,
    rows: []
  };

  function pm_money(n){ return 'S/ ' + Number(n||0).toFixed(2); }
  
    function pad6(n){ n = parseInt(n||0,10); return String(n).padStart(6,'0'); }
  function buildClienteForVoucher(H){
    // Inferimos tipo_persona del doc del cliente (si es RUC => JURIDICA)
    const isRUC = (String(H?.cliente?.doc_tipo||'').toUpperCase() === 'RUC');
    if (isRUC){
      return {
        tipo_persona: 'JURIDICA',
        tipo: 'RUC',
        doc: H?.cliente?.doc || '',
        razon: H?.cliente?.nombre || '',
        nombres: '',
        apellidos: '',
        telefono: H?.contratante?.telefono || ''
      };
    } else {
      const full = String(H?.cliente?.nombre||'').trim();
      // Heurística simple: último token como apellido, resto nombres
      const parts = full.split(/\s+/);
      const apellidos = parts.length>1 ? parts.pop() : '';
      const nombres  = parts.join(' ') || full;
      return {
        tipo_persona: 'NATURAL',
        tipo: H?.cliente?.doc_tipo || '',
        doc: H?.cliente?.doc || '',
        razon: '',
        nombres,
        apellidos,
        telefono: H?.contratante?.telefono || ''
      };
    }
  }

  // =========================================================
// Render tabla + paginación
// =========================================================
function renderRows() {
  const rows = STATE.rows || [];
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-muted small">Sin resultados</td></tr>`;
    pager.innerHTML = '';
    return;
  }

  const stateBadge = (r) => {
    const code = r.estado_code || ((Number(r.saldo||0) > 0) ? 'pending' : 'paid');
    if (code === 'paid')    return `<span class="badge badge-pill badge-success">Pagado</span>`;
    if (code === 'pending') return `<span class="badge badge-pill badge-warning">Pendiente</span>`;
    if (code === 'void')    return `<span class="badge badge-pill badge-danger">Anulada</span>`;
    if (code === 'refund')  return `<span class="badge badge-pill badge-info">Con devolución</span>`;
    return `<span class="badge badge-pill badge-secondary">—</span>`;
  };

  tbody.innerHTML = rows.map(r => {
    let actionHTML = '';
    const canAbonar = (r.estado_venta !== 'ANULADA') && (Number(r.saldo||0) > 0);

    if (r.estado_code === 'pending') {
      actionHTML = `
        <div class="d-flex flex-wrap gap-1">
          <button class="btn btn-sm btn-success vp-abonar" data-id="${r.id}"><i class="fas fa-coins me-1"></i>Completar pago</button>
          <button class="btn btn-sm btn-danger vp-anular" data-id="${r.id}"><i class="fas fa-ban me-1"></i>Anular</button>
          <button class="btn btn-sm btn-outline-secondary vp-detalle" data-id="${r.id}"><i class="fas fa-eye me-1"></i>Ver detalle</button>
        </div>`;
    } else if (r.estado_code === 'paid') {
      actionHTML = `
        <div class="d-flex flex-wrap gap-1">
          <button class="btn btn-sm btn-warning vp-devolucion" data-id="${r.id}"><i class="fas fa-undo me-1"></i>Devolución</button>
          <button class="btn btn-sm btn-outline-secondary vp-detalle" data-id="${r.id}"><i class="fas fa-eye me-1"></i>Ver detalle</button>
        </div>`;
    } else if (r.estado_code === 'refund') {
      actionHTML = `
        <div class="d-flex flex-wrap gap-1">
          ${ canAbonar ? `<button class="btn btn-sm btn-success vp-abonar" data-id="${r.id}"><i class="fas fa-coins me-1"></i>Completar pago</button>` : '' }
          <button class="btn btn-sm btn-warning vp-devolucion" data-id="${r.id}"><i class="fas fa-undo me-1"></i>Devolución</button>
          <button class="btn btn-sm btn-outline-secondary vp-detalle" data-id="${r.id}"><i class="fas fa-eye me-1"></i>Ver detalle</button>
        </div>`;
    } else {
      actionHTML = `
        <div class="d-flex flex-wrap gap-1">
          <button class="btn btn-sm btn-outline-secondary vp-detalle" data-id="${r.id}"><i class="fas fa-eye me-1"></i>Ver detalle</button>
        </div>`;
    }

    const namesBlock = `
      <div><strong>${esc(r.cliente || '—')}</strong></div>
      ${ r.contratante ? `<div class="small text-muted">Contratante: ${esc(r.contratante)}</div>` : '' }
      ${ r.conductor   ? `<div class="small text-muted">Conductor: ${esc(r.conductor)}</div>`   : '' }
    `;

    return `
      <tr>
        <td>${esc(r.ticket)}</td>
        <td>${fmtDT(r.fecha) || '—'}</td>
        <td>${namesBlock}</td>
        <td class="text-end">${pm_money(r.total)}</td>
        <td class="text-end">${pm_money(r.pagado)}</td>
        <td class="text-end">${pm_money(r.saldo)}</td>
        <td>${stateBadge(r)}</td>
        <td>${actionHTML}</td>
      </tr>`;
  }).join('');

  // Contador
  vpRenderCounter(STATE.total, STATE.page, STATE.per, rows.length);
  renderPager();

  // Aplicar estado actual de caja a los botones recién renderizados
  if (typeof setSellUI === 'function') setSellUI(CAN_SELL, SELL_REASON);
}

function renderPager() {
  const pages = Math.max(1, Math.ceil(STATE.total / STATE.per));
  const cur = Math.min(STATE.page, pages);
  const li = [];
  const add = (p, label, disabled=false, active=false)=>
    li.push(`<li class="page-item ${disabled?'disabled':''} ${active?'active':''}"><a class="page-link" href="#" data-p="${p}">${label}</a></li>`);
  add(cur-1,'«',cur<=1,false);
  let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
  for(let p=s;p<=e;p++) add(p,p,false,p===cur);
  add(cur+1,'»',cur>=pages,false);
  pager.innerHTML = li.join('');
}


  // =========================================================
  // Búsqueda
  // =========================================================
  async function doSearch() {
    const q = (qInput.value||'').trim();
    STATE.q = q;

    try{
      const j = await vpGET({ action:'ventas_buscar', q: q, estado: STATE.estado, page: STATE.page, per: STATE.per });
      STATE.rows = j.data || [];
      STATE.total = Number(j.total||0);
      renderRows();
    }catch(e){
      tbody.innerHTML = `<tr><td colspan="8" class="text-danger">${esc(e.message)}</td></tr>`;
      pager.innerHTML = '';
    }
  }

  qInput.addEventListener('input', (()=>{ let t; return ()=>{ clearTimeout(t); t=setTimeout(()=>{ STATE.page=1; doSearch(); },300); }; })());
  qClear.addEventListener('click', ()=>{ qInput.value=''; STATE.page=1; doSearch(); });

  pager.addEventListener('click', (e)=>{
    const a = e.target.closest('a[data-p]');
    if(!a) return;
    e.preventDefault();
    const p = parseInt(a.dataset.p||'1',10);
    if(!isNaN(p)){ STATE.page=p; doSearch(); }
  });

    estadoBtns.forEach(b=>{
    b.addEventListener('click', ()=>{
      estadoBtns.forEach(x=>x.classList.remove('active','btn-outline-primary'));
      estadoBtns.forEach(x=>x.classList.add('btn-outline-secondary'));
      b.classList.add('active');
      b.classList.remove('btn-outline-secondary');
      if (!b.classList.contains('btn-outline-primary')) b.classList.add('btn-outline-primary');

      STATE.estado = b.dataset.vpEstado || 'pending';
      STATE.page = 1;
      doSearch();
    });
  });

  // =========================================================
  // Modales (creados dinámicamente)
  // =========================================================
  function ensureModals(){
  if (!document.querySelector('#vpAbonoModal')) {
    const m = document.createElement('div');
    m.innerHTML = `
<div class="modal fade" id="vpAbonoModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-cash-register me-2"></i>Completar pago</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="modal-inline-alert" id="vpAbonoAlert"></div>
        <div id="vpAbonoBody"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success btn-sm" id="vpAbonoConfirm"><i class="fas fa-check-circle me-1"></i>Registrar abonos</button>
      </div>
    </div>
  </div>
</div>`;
    document.body.appendChild(m.firstElementChild);
  }
  if (!document.querySelector('#vpDetalleModal')) {
    const m2 = document.createElement('div');
    m2.innerHTML = `
<div class="modal fade" id="vpDetalleModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2 bg-dark text-white">
        <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Detalle de venta</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="modal-inline-alert" id="vpDetAlert"></div>
        <div id="vpDetalleBody"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>`;
    document.body.appendChild(m2.firstElementChild);
  }
  // estilos mínimos para la zona de abonos
  if (!document.querySelector('#vpStyles')) {
    const st = document.createElement('style');
    st.id = 'vpStyles';
    st.textContent = `
      .vp-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
      @media (max-width: 768px){ .vp-grid{grid-template-columns:1fr} }
      .vp-box{border:1px solid #e5e7eb;border-radius:12px;padding:10px;margin-bottom:8px;}
    `;
    document.head.appendChild(st);
  }
}

// Carga de medios de pago (catálogo) — normaliza requiere_ref a 0/1 numérico
function isCashName(name){
  return /efect/i.test(String(name||''));
}

// Carga de medios de pago (catálogo) — normaliza requiere_ref a 0/1 numérico y fuerza EFECTIVO=0
async function loadMediosPago(){
  const r = await fetch(`${BASE_URL}/modules/caja/api.php?action=pos_medios_pago`, {credentials:'same-origin'});
  const j = await r.json();
  if (!j.ok) throw new Error(j.error || 'Error medios de pago');
  const data = j.data || [];
  return data.map(m => {
    const req = (m.requiere_ref === 1 || m.requiere_ref === '1' || m.requiere_ref === true) ? 1 : 0;
    const finalReq = isCashName(m.nombre) ? 0 : req;
    return { ...m, requiere_ref: finalReq };
  });
}

// Abrir modal de detalle (lectura)
async function openDetalle(ventaId){
  ensureModals();
  try{
    const j = await vpGET({ action:'venta_detalle', id: ventaId });
    const H = j.cabecera, ITEMS = j.items||[], AB = j.abonos||[];

    const itemsRows = ITEMS.length
      ? ITEMS.map(it=>`<tr><td>${esc(it.servicio_nombre||'Servicio')}</td><td class="text-end">${Number(it.cantidad||0)}</td><td class="text-end">${pm_money(it.precio_unitario)}</td><td class="text-end">${pm_money(it.total_linea)}</td></tr>`).join('')
      : `<tr><td colspan="4" class="text-muted small">— Sin ítems —</td></tr>`;

    const abRows = AB.length
      ? AB.map(a=>`<tr>
            <td>${esc(a.medio)}</td>
            <td class="text-end">${pm_money(a.monto)}</td>
            <td class="text-end">${pm_money(a.monto_aplicado)}</td>
            <td>${esc(a.referencia||'')}</td>
            <td class="small text-muted">${fmtDT(a.fecha)||''}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-danger vp-refund" data-venta="${H.id}" data-apl="${a.aplicacion_id}">
                <i class="fas fa-undo"></i>
              </button>
            </td>
          </tr>`).join('')
      : `<tr><td colspan="6" class="text-muted small">— Sin abonos —</td></tr>`;

    const contr = H.contratante || {};
    const cond  = H.conductor || {};

    const body = document.querySelector('#vpDetalleBody');
    body.innerHTML = `
      <div class="vp-grid">
        <div class="vp-box">
          <div class="fw-bold mb-1">Venta</div>
          <div class="small">Ticket: <strong>${esc(H.ticket)}</strong></div>
          <div class="small">Fecha: ${fmtDT(H.fecha)||'—'}</div>
          <div class="small">Moneda: ${esc(H.moneda||'PEN')}</div>
          <div class="small">Estado: ${esc(H.estado||'')}</div>
        </div>
        <div class="vp-box">
          <div class="fw-bold mb-1">Totales</div>
          <div class="d-flex justify-content-between"><div>Total</div><div>${pm_money(H.total)}</div></div>
          <div class="d-flex justify-content-between"><div>Pagado</div><div>${pm_money(H.pagado)}</div></div>
          <div class="d-flex justify-content-between"><div>Saldo</div><div>${pm_money(H.saldo)}</div></div>
        </div>
        <div class="vp-box">
          <div class="fw-bold mb-1">Cliente</div>
          <div class="small">Doc: ${esc(H.cliente.doc_tipo||'')} ${esc(H.cliente.doc||'')}</div>
          <div class="small">Nombre: ${esc(H.cliente.nombre||'')}</div>
        </div>
        <div class="vp-box">
          <div class="fw-bold mb-1">Contratante</div>
          <div class="small">Doc: ${esc(contr.doc_tipo||'')} ${esc(contr.doc||'')}</div>
          <div class="small">Nombre: ${esc((contr.nombres||'')+' '+(contr.apellidos||''))}</div>
          <div class="small">Teléfono: ${esc(contr.telefono||'—')}</div>
        </div>
        <div class="vp-box">
          <div class="fw-bold mb-1">Conductor</div>
          <div class="small">Doc: ${esc(cond.doc_tipo||'')} ${esc(cond.doc_numero||'')}</div>
          <div class="small">Nombre: ${esc((cond.nombres||'')+' '+(cond.apellidos||''))}</div>
          <div class="small">Teléfono: ${esc(cond.telefono||'—')}</div>
        </div>
      </div>

      <div class="vp-box">
        <div class="fw-bold mb-1">Ítems</div>
        <div class="table-responsive">
          <table class="table table-sm"><thead class="table-light">
            <tr><th>Servicio</th><th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Total</th></tr>
          </thead><tbody>${itemsRows}</tbody></table>
        </div>
      </div>

      <div class="vp-box">
        <div class="fw-bold mb-1">Abonos</div>
        <div class="table-responsive">
          <table class="table table-sm"><thead class="table-light">
            <tr><th>Medio</th><th class="text-end">Monto</th><th class="text-end">Aplicado</th><th>Ref.</th><th>Fecha</th><th></th></tr>
          </thead><tbody id="vpDetAbonosBody">${abRows}</tbody></table>
        </div>
      </div>
    `;

    // Devolver abono desde el modal de Detalle
    body.querySelector('#vpDetAbonosBody').addEventListener('click', async (e)=>{
      const btn = e.target.closest('.vp-refund');
      if(!btn) return;
      const venta_id = parseInt(btn.dataset.venta||'0',10);
      const apl_id   = parseInt(btn.dataset.apl||'0',10);
      if(!venta_id || !apl_id) return;
      const motivo = prompt('Motivo de devolución del abono:','');
      if(motivo===null) return;
      if((motivo||'').trim()===''){ showMsg('Aviso','Debes indicar un motivo.','danger'); return; }
      try{
        const r = await vpPOST('venta_devolver_abono', { venta_id, aplicacion_id: apl_id, motivo });
        showMsg('Listo', 'Devolución registrada.');
        openDetalle(venta_id); // refresca detalle
        await doSearch();      // refresca tabla principal
      }catch(e){
        showMsg('Error', e.message, 'danger');
      }
    });

    if (window.jQuery) jQuery('#vpDetalleModal').modal('show');
  }catch(e){
    showMsg('Error', e.message, 'danger');
  }
}

// Abrir modal para abonar
async function openAbonar(ventaId){
  ensureModals();
  try{
    const j = await vpGET({ action:'venta_detalle', id: ventaId });
    const H = j.cabecera, ABH = j.abonos||[];
    const medios = await loadMediosPago();

    const hist = ABH.length
      ? ABH.map(a=>`<tr>
          <td>${esc(a.medio)}</td>
          <td class="text-end">${pm_money(a.monto)}</td>
          <td class="text-end">${pm_money(a.monto_aplicado)}</td>
          <td>${esc(a.referencia||'')}</td>
          <td class="small text-muted">${fmtDT(a.fecha)||''}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-danger vp-refund" data-venta="${H.id}" data-apl="${a.aplicacion_id}">
              <i class="fas fa-undo"></i>
            </button>
          </td>
        </tr>`).join('')
      : `<tr><td colspan="6" class="text-muted small">— Sin abonos previos —</td></tr>`;

    const body = document.querySelector('#vpAbonoBody');
    body.innerHTML = `
      <div class="vp-grid">
        <div class="vp-box">
          <div class="fw-bold mb-1">Venta</div>
          <div class="small">Ticket: <strong>${esc(H.ticket)}</strong></div>
          <div class="small">Fecha: ${fmtDT(H.fecha)||'—'}</div>
          <div class="small">Cliente: ${esc(H.cliente.nombre||'')}</div>
          <div class="small">Contratante: ${esc(((H.contratante.nombres||'')+' '+(H.contratante.apellidos||'')).trim() || '—')}</div>
        </div>
        <div class="vp-box">
          <div class="fw-bold mb-1">Saldo</div>
          <div class="display-6" id="vpSaldoNow">${pm_money(H.saldo)}</div>
          <div class="small text-muted">Total: ${pm_money(H.total)} • Pagado: ${pm_money(H.pagado)}</div>
        </div>
      </div>

      <div class="vp-box">
        <div class="fw-bold mb-1">Registrar nuevos abonos</div>
        <div class="row g-2 align-items-end mb-2">
          <div class="col-12 col-sm-3">
            <label class="form-label small mb-1">Medio de pago</label>
            <select id="vpMedio" class="form-select form-select-sm">
              ${medios.map(m => `<option value="${m.id}" data-req="${m.requiere_ref ? '1' : '0'}">${esc(m.nombre)}</option>`).join('')}
            </select>
          </div>
          <div class="col-12 col-sm-2">
            <label class="form-label small mb-1">Monto</label>
            <input id="vpMonto" type="number" step="0.01" min="0" class="form-control form-control-sm">
          </div>
          <div class="col-12 col-sm-3">
            <label class="form-label small mb-1">Referencia</label>
            <input id="vpRef" class="form-control form-control-sm" maxlength="80" placeholder="según el medio">
          </div>
          <div class="col-12 col-sm-4">
            <label class="form-label small mb-1">Detalle / Nota</label>
            <input id="vpObs" class="form-control form-control-sm" maxlength="255">
          </div>
          <div class="col-12">
            <button id="vpAddAbono" type="button" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Agregar abono</button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-2">
            <thead class="table-light">
              <tr>
                <th style="width:40%">Medio</th>
                <th style="width:15%" class="text-end">Monto</th>
                <th style="width:25%">Referencia</th>
                <th style="width:15%">Agregado</th>
                <th style="width:5%"></th>
              </tr>
            </thead>
            <tbody id="vpAbonosBody"><tr><td colspan="5" class="text-muted small">— Sin abonos nuevos —</td></tr></tbody>
          </table>
        </div>

        <div class="d-flex align-items-center justify-content-end gap-3">
          <div class="small">Abonos nuevos: <span id="vpAbonosTotal">S/ 0.00</span></div>
          <div class="fw-bold">Saldo estimado: <span id="vpSaldoEst">${pm_money(H.saldo)}</span></div>
        </div>
      </div>

      <div class="vp-box">
        <div class="fw-bold mb-1">Historial de abonos</div>
        <div class="table-responsive">
          <table class="table table-sm"><thead class="table-light">
            <tr><th>Medio</th><th class="text-end">Monto</th><th class="text-end">Aplicado</th><th>Ref.</th><th>Fecha</th><th></th></tr>
          </thead><tbody id="vpHistBody">${hist}</tbody></table>
        </div>
      </div>
    `;

    // Placeholder de referencia según medio de pago
    const refInput = document.querySelector('#vpRef');
    const medioSel = document.querySelector('#vpMedio');

    function vpMedioSelectedOption(){
      const sel = document.querySelector('#vpMedio');
      return sel && sel.selectedOptions ? sel.selectedOptions[0] : null;
    }
    function vpMedioRequiereRef(){
      const opt = vpMedioSelectedOption();
      return !!(opt && opt.dataset && opt.dataset.req === '1');
    }

    const updateRefPH = () => {
      const req = vpMedioRequiereRef();
      refInput.placeholder = req ? 'Obligatoria para este medio' : 'Opcional';
      if (!req) refInput.classList.remove('is-invalid');
    };

    document.querySelector('#vpRef').addEventListener('input', (ev)=>{
      const refEl = ev.target;
      const req = vpMedioRequiereRef();
      const has = (refEl.value||'').trim() !== '';
      if (req && !has){ refEl.classList.add('is-invalid'); }
      else { refEl.classList.remove('is-invalid'); }
    });

    medioSel.addEventListener('change', updateRefPH);
    updateRefPH();

    const AB = []; // abonos nuevos
    function recompute(){
      const abonado = AB.reduce((s,x)=>s+x.monto,0);
      const est = Math.max(0, Number(H.saldo||0) - abonado);
      document.querySelector('#vpAbonosTotal').textContent = pm_money(abonado);
      document.querySelector('#vpSaldoEst').textContent    = pm_money(est);
    }
    function renderAbonos(){
      const tb = document.querySelector('#vpAbonosBody');
      if (!AB.length){ tb.innerHTML = `<tr><td colspan="5" class="text-muted small">— Sin abonos nuevos —</td></tr>`; return; }
      tb.innerHTML = AB.map(a=>`
        <tr>
          <td>${esc(a.medio)}</td>
          <td class="text-end">${pm_money(a.monto)}</td>
          <td>${esc(a.ref||'')}</td>
          <td class="small text-muted">${esc(a.ts)}</td>
          <td><button class="btn btn-link btn-sm text-danger vp-del" data-id="${a.id}" title="Quitar"><i class="fas fa-times"></i></button></td>
        </tr>
      `).join('');
    }

    // Validación en tiempo real
    document.querySelector('#vpMonto').addEventListener('input', (ev)=>{
      const el = ev.target;
      const v = Number(el.value||0);
      const abonadoNuevo = AB.reduce((s,x)=>s+x.monto,0);
      const maxPermitido = Math.max(0, Number(H.saldo||0) - abonadoNuevo);
      if (!isFinite(v) || v<=0){ el.classList.add('is-invalid'); return; }
      if (v > maxPermitido){ el.classList.add('is-invalid'); return; }
      el.classList.remove('is-invalid');
    });

    // Eventos locales del modal (agregar/quitar)
    document.querySelector('#vpAddAbono').onclick = ()=>{
      const sel   = document.querySelector('#vpMedio');
      const id    = parseInt(sel.value||'0',10);
      const opt   = sel ? sel.selectedOptions[0] : null;
      const montoEl = document.querySelector('#vpMonto');
      const refEl   = document.querySelector('#vpRef');
      const obsEl   = document.querySelector('#vpObs');

      // Limpieza visual/alertas
      vpClearInlineAlert('vpAbonoModal');
      montoEl.classList.remove('is-invalid');
      refEl.classList.remove('is-invalid');

      if (!id || !opt){ vpShowInlineAlert('vpAbonoModal','danger','Selecciona un medio de pago.'); return; }

      const nombreMedio = opt.textContent || '';
      const requiere    = vpMedioRequiereRef();

      const monto = Number(montoEl.value||0);
      if (!isFinite(monto) || monto<=0){
        montoEl.classList.add('is-invalid');
        vpShowInlineAlert('vpAbonoModal','danger','Ingresa un monto mayor a 0.00.');
        return;
      }
      const abonadoNuevo = AB.reduce((s,x)=>s+x.monto,0);
      const maxPermitido = Math.max(0, Number(H.saldo||0) - abonadoNuevo);
      if (monto > maxPermitido){
        montoEl.classList.add('is-invalid');
        vpShowInlineAlert('vpAbonoModal','danger','El monto ingresado supera el saldo. Ingresa un monto menor o igual al saldo.');
        return;
      }
      const ref = (refEl.value||'').trim();
      if (requiere && !ref){
        refEl.classList.add('is-invalid');
        vpShowInlineAlert('vpAbonoModal','danger','Este medio exige referencia. Complétala para continuar.');
        return;
      }
      const obs = (obsEl.value||'').trim();

      AB.push({ id:Date.now(), medio_id:id, medio:nombreMedio, monto, ref, obs, ts:new Date().toLocaleString() });
      montoEl.value=''; refEl.value=''; obsEl.value='';
      renderAbonos(); recompute();
    };

    document.querySelector('#vpAbonosBody').addEventListener('click',(e)=>{
      const b = e.target.closest('.vp-del'); if(!b) return;
      const id = parseInt(b.dataset.id||'0',10);
      const i = AB.findIndex(x=>x.id===id);
      if (i>=0){ AB.splice(i,1); renderAbonos(); recompute(); }
    });

    // Confirmar registro de abonos
    document.querySelector('#vpAbonoConfirm').onclick = async ()=>{
      if (!CAN_SELL){ showMsg('Aviso', 'Abre la caja diaria de hoy para registrar abonos.', 'danger'); return; }
      if (!AB.length){ showMsg('Aviso', 'Agrega al menos un abono.', 'danger'); return; }
      try{
        const payload = {
          venta_id: H.id,
          abonos_json: JSON.stringify(AB.map(a=>({medio_id:a.medio_id, monto:a.monto, referencia:a.ref, observacion:a.obs})))
        };
        const r = await vpPOST('venta_abonar', payload);

        document.querySelector('#vpSaldoNow').textContent = pm_money(r.saldo);

        const histBody = document.querySelector('#vpHistBody');
        r.nuevos.forEach(n=>{
          const tr = document.createElement('tr');
          tr.innerHTML = `<td>${esc(n.medio)}</td><td class="text-end">${pm_money(n.monto)}</td><td class="text-end">${pm_money(n.aplicado)}</td><td>${esc(n.referencia||'')}</td><td class="small text-muted">${new Date().toLocaleString()}</td><td></td>`;
          histBody.appendChild(tr);
        });

        const abnCodes = r.nuevos.map(n => 'ABN-' + pad6(n.abono_id));
        const abonosForVoucher = r.nuevos.map(n => ({
          medio: n.medio,
          monto: n.monto,
          ref: `Recibo ${'ABN-'+pad6(n.abono_id)}${n.referencia ? ' · '+n.referencia : ''}`
        }));

        const clienteVoucher = buildClienteForVoucher(H);
        const cond = H.conductor || {};
        const voucherConductor = {
          tipo: cond.doc_tipo || '',
          doc: cond.doc_numero || '',
          nombres: cond.nombres || '',
          apellidos: cond.apellidos || '',
          telefono: cond.telefono || ''
        };

        if (window.jQuery) jQuery('#vpAbonoModal').modal('hide');

        openVoucher({
          empresa: EMPRESA_NOMBRE,
          ticket: `${r.ticket} • Abono(s): ${abnCodes.join(', ')}`,
          fecha: new Date().toLocaleString(),
          cajero: USUARIO_NOMBRE,
          cliente: clienteVoucher,
          conductor: voucherConductor,
          items: [],
          abonos: abonosForVoucher,
          totales: { total: r.total, pagado: r.pagado, saldo: r.saldo }
        });

        AB.splice(0, AB.length); renderAbonos(); recompute();
        await doSearch();

        if (r.saldo<=0){
          showMsg('Listo','Venta saldada. Se ha generado el comprobante de abono.');
        }else{
          showMsg('Listo','Abonos registrados. Se ha generado el comprobante de abono.');
        }
      } catch(e){
        const raw = String(e.message||'');
        if (raw.toLowerCase().includes('referencia obligatoria')) {
          const req = vpMedioRequiereRef();
          const msg = req ? 'Este medio exige referencia. Complétala para continuar.' : 'Para Efectivo no se requiere referencia. Vuelve a intentarlo.';
          vpShowInlineAlert('vpAbonoModal','danger', msg);
        } else {
          vpShowInlineAlert('vpAbonoModal','danger', vpMapApiError(raw));
        }
      }
    };

    // Devolución de abono desde el historial
    document.querySelector('#vpHistBody').addEventListener('click', async (e)=>{
      const btn = e.target.closest('.vp-refund');
      if(!btn) return;
      const venta_id = parseInt(btn.dataset.venta||'0',10);
      const apl_id   = parseInt(btn.dataset.apl||'0',10);
      if(!venta_id || !apl_id) return;
      const motivo = prompt('Motivo de devolución del abono:','');
      if(motivo===null) return;
      if((motivo||'').trim()===''){ showMsg('Aviso','Debes indicar un motivo.','danger'); return; }
      try{
        const r = await vpPOST('venta_devolver_abono', { venta_id, aplicacion_id: apl_id, motivo });
        document.querySelector('#vpSaldoNow').textContent = pm_money(r.saldo);
        showMsg('Listo', 'Devolución registrada.');
        await doSearch();
        openAbonar(venta_id); // refrescar historial y detalle
      }catch(e){
        showMsg('Error', e.message, 'danger');
      }
    });

    if (window.jQuery) jQuery('#vpAbonoModal').modal('show');
  }catch(e){
    showMsg('Error', e.message, 'danger');
  }
}

    // Dispatcher de botones en la tabla
  document.addEventListener('click',(e)=>{
    const ab = e.target.closest('.vp-abonar');
    if (ab){
      const id = parseInt(ab.dataset.id||'0',10);
      if (!id) return;
      openAbonar(id);
      return;
    }
    const vd = e.target.closest('.vp-detalle');
    if (vd){
      const id = parseInt(vd.dataset.id||'0',10);
      if (!id) return;
      openDetalle(id);
      return;
    }
    const an = e.target.closest('.vp-anular');
    if (an){
      const id = parseInt(an.dataset.id||'0',10);
      if (!id) return;
      const motivo = prompt('Motivo de anulación:','');
      if (motivo===null) return;
      if ((motivo||'').trim()===''){ showMsg('Aviso','Debes indicar un motivo.','danger'); return; }
      vpPOST('venta_anular', { venta_id:id, motivo })
        .then(()=>{ showMsg('Listo','Venta anulada.'); doSearch(); })
        .catch(err=> showMsg('Error',err.message,'danger'));
      return;
    }
    const dv = e.target.closest('.vp-devolucion');
    if (dv){
      const id = parseInt(dv.dataset.id||'0',10);
      if (!id) return;
      const motivo = prompt('Motivo de devolución total (anulación con devolución):','');
      if (motivo===null) return;
      if ((motivo||'').trim()===''){ showMsg('Aviso','Debes indicar un motivo.','danger'); return; }
      vpPOST('venta_devolucion', { venta_id:id, motivo })
        .then(()=>{ showMsg('Listo','Venta anulada con devolución.'); doSearch(); })
        .catch(err=> showMsg('Error',err.message,'danger'));
      return;
    }
  });

  // Estado inicial
    STATE.estado = 'pending';
  STATE.page = 1;
  doSearch();
})();
