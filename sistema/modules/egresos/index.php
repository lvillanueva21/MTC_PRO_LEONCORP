<?php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([3, 4]);
verificarPermiso([3, 4]);

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$appFolder = basename(dirname(dirname(__DIR__)));
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$parts = $scriptDir === '' ? [] : explode('/', $scriptDir);
$idx = array_search($appFolder, $parts, true);
$depth = ($idx === false) ? count($parts) : (count($parts) - ($idx + 1));
$APP_ROOT_REL = str_repeat('../', max(0, $depth));

function rel(string $path): string
{
    global $APP_ROOT_REL;
    return $APP_ROOT_REL . ltrim($path, '/');
}

$u = currentUser();
$usrNom = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
if ($usrNom === '') $usrNom = (string)($u['usuario'] ?? 'Usuario');
$empNom = (string)($u['empresa']['nombre'] ?? '—');

include __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" href="<?= h(rel('modules/egresos/estilo.css?v=1')) ?>">

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="eg-bar shadow-sm">
        <div class="eg-bar-left">
          <div class="eg-icon"><i class="fas fa-file-invoice-dollar"></i></div>
          <div class="eg-titles">
            <div class="eg-title">Módulo de egresos</div>
            <div class="eg-subtitle">Empresa: <strong>"<?= h($empNom) ?>"</strong> · Usuario: <strong><?= h($usrNom) ?></strong></div>
            <div class="eg-subtitle small">Registra salidas de dinero vinculadas a la caja diaria abierta.</div>
          </div>
        </div>
        <div class="eg-bar-right">
          <div class="eg-caja-pill"><span class="label">Caja diaria</span><span class="badge badge-pill badge-secondary" id="egCajaBadge">Cargando...</span></div>
          <div class="eg-caja-pill"><span class="label">Caja mensual</span><span class="badge badge-pill badge-secondary" id="egCajaMensualBadge">Cargando...</span></div>
          <div class="eg-bar-meta small text-right">
            <div><strong id="egCajaDiariaCodigo">CD: —</strong></div>
            <div><strong id="egCajaMensualCodigo">CM: —</strong></div>
          </div>
        </div>
      </div>
      <div id="egCajaMsg" class="alert eg-caja-alert mt-3 mb-0" role="alert"></div>
    </div>
  </div>

  <section class="content pb-3">
    <div class="container-fluid">
      <div class="row g-3">
        <div class="col-12 col-lg-5">
          <div class="card eg-card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <h5 class="card-title mb-0">Nuevo egreso</h5>
                  <div class="text-muted small">Operación real y vinculada a caja diaria.</div>
                </div>
                <span class="badge badge-light" id="egFormStateBadge">Verificando caja...</span>
              </div>
              <div id="egSaldoResumen" class="eg-saldo-box mb-2 d-none"></div>
              <div id="egFormAlert"></div>

              <form id="egForm" autocomplete="off">
                <div class="form-group mb-3">
                  <label class="mb-1">Tipo de comprobante</label>
                  <div class="eg-chip-group" id="egTipoChipGroup">
                    <button type="button" class="eg-chip active" data-tipo="RECIBO">Recibo interno</button>
                    <button type="button" class="eg-chip" data-tipo="BOLETA">Boleta</button>
                    <button type="button" class="eg-chip" data-tipo="FACTURA">Factura</button>
                  </div>
                  <input type="hidden" id="egTipo" value="RECIBO">
                  <small class="form-text text-muted">Factura y boleta requieren serie y número.</small>
                </div>
                <div class="form-row" id="egSerieNumeroGroup">
                  <div class="form-group col-4">
                    <label class="mb-1">Serie<span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="egSerie" maxlength="10" placeholder="F001">
                  </div>
                  <div class="form-group col-8">
                    <label class="mb-1">Número<span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="egNumero" maxlength="20" placeholder="00012345">
                  </div>
                </div>
                <div class="form-group mb-3 d-none" id="egReciboRefGroup">
                  <label class="mb-1">Referencia (opcional)</label>
                  <input type="text" class="form-control form-control-sm" id="egReferencia" maxlength="120" placeholder="Ej: Recibo manual 001316">
                </div>
                <div class="form-row">
                  <div class="form-group col-6">
                    <label class="mb-1">Monto (S/)<span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                      <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                      <input type="number" step="0.01" min="0" class="form-control" id="egMonto" required>
                    </div>
                  </div>
                  <div class="form-group col-6">
                    <label class="mb-1">Fecha y hora<span class="text-danger">*</span></label>
                    <input type="datetime-local" class="form-control form-control-sm" id="egFecha" required>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-6">
                    <label class="mb-1">Beneficiario / Proveedor</label>
                    <input type="text" class="form-control form-control-sm" id="egBeneficiario" maxlength="160" placeholder="Nombre completo o razón social">
                  </div>
                  <div class="form-group col-6">
                    <label class="mb-1">Documento</label>
                    <input type="text" class="form-control form-control-sm" id="egDocumento" maxlength="20" placeholder="DNI / RUC">
                  </div>
                </div>
                <div class="form-group mb-3">
                  <label class="mb-1">Concepto detallado<span class="text-danger">*</span></label>
                  <textarea id="egConcepto" rows="5" class="form-control form-control-sm" required></textarea>
                  <div class="d-flex justify-content-between mt-1 small text-muted">
                    <span>Este texto se imprimirá en el recibo.</span><span id="egConceptoCount">0 / 1000</span>
                  </div>
                </div>
                <div class="form-group mb-3">
                  <label class="mb-1">Observaciones internas <span class="text-muted small">(opcional)</span></label>
                  <textarea id="egObs" rows="2" class="form-control form-control-sm"></textarea>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
                  <div class="small text-muted"><i class="fas fa-info-circle mr-1"></i>El egreso queda asociado a la caja diaria abierta.</div>
                  <div class="eg-form-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm mr-1" id="egBtnLimpiar"><i class="fas fa-eraser mr-1"></i>Limpiar</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="egBtnGuardar"><i class="fas fa-save mr-1"></i>Guardar egreso</button>
                    <button type="button" class="btn btn-outline-primary btn-sm ml-1" id="egBtnVistaPrevia"><i class="fas fa-print mr-1"></i>Vista previa</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-7">
          <div class="card eg-card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                <div>
                  <h5 class="card-title mb-0">Egresos registrados</h5>
                  <div class="text-muted small">Control de salidas por caja diaria.</div>
                </div>
                <div class="eg-filters d-flex flex-wrap gap-2">
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text bg-white border-right-0"><i class="fas fa-search"></i></span></div>
                    <input id="egQ" class="form-control border-left-0" placeholder="Buscar por código, concepto o beneficiario...">
                  </div>
                  <select id="egFiltroTipo" class="form-control form-control-sm"><option value="TODOS">Todos</option><option value="FACTURA">Facturas</option><option value="BOLETA">Boletas</option><option value="RECIBO">Recibos</option></select>
                  <select id="egFiltroEstado" class="form-control form-control-sm"><option value="TODOS">Activos y anulados</option><option value="ACTIVO">Solo activos</option><option value="ANULADO">Solo anulados</option></select>
                </div>
              </div>
              <div class="small text-muted mb-2" id="egResumenListado"></div>
              <div class="table-responsive flex-grow-1">
                <table class="table table-sm table-hover mb-2" id="egTable">
                  <thead class="thead-light"><tr><th>Código</th><th>Fecha</th><th>Tipo</th><th>Comp.</th><th>Beneficiario</th><th>Concepto</th><th class="text-right">Monto</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                  <tbody id="egTableBody"><tr><td colspan="9" class="text-muted small">Cargando egresos...</td></tr></tbody>
                </table>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="small text-muted" id="egTotalesDia"></div>
                <nav><ul class="pagination pagination-sm mb-0" id="egPager"></ul></nav>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="egresoPrintModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header py-2 bg-dark text-white">
              <h5 class="modal-title"><i class="fas fa-receipt mr-1"></i>Vista previa de recibo de egreso</h5>
              <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body"><div id="egVoucher" class="eg-voucher-wrapper"></div></div>
            <div class="modal-footer py-2">
              <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary btn-sm" id="egBtnPrintReal" disabled><i class="fas fa-print mr-1"></i>Imprimir PDF</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
(function(){
const API='<?= h(rel('modules/egresos/api.php')) ?>',EMP='<?= h($empNom) ?>',USR='<?= h($usrNom) ?>';
const qs=(s,c=document)=>c.querySelector(s),qsa=(s,c=document)=>Array.from(c.querySelectorAll(s));
const st={p:1,pp:8,q:'',t:'TODOS',e:'TODOS',schema:true,schemaMsg:'',caja:null,saldo:null,prev:null};
const esc=s=>(s||'').toString().replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
const money=v=>'S/ '+Number(v||0).toFixed(2);
const fmt=(x)=>{if(!x)return '—';const d=new Date(x.replace(' ','T'));if(isNaN(d))return '—';return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear()+' '+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0');};
const fmtDate=(x)=>{if(!x)return '—';const d=new Date(x+'T00:00:00');if(isNaN(d))return '—';return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear();};
async function req(url,opt){const r=await fetch(url,Object.assign({credentials:'same-origin'},opt||{}));const txt=await r.text();let j=null;try{j=JSON.parse(txt);}catch(e){}if(!r.ok||!j||j.ok!==true){let msg=(j&&j.error)?j.error:'';if(!msg){const plain=(txt||'').trim();msg=plain!==''?plain:('Error HTTP '+r.status);}if(j&&j.error_ref)msg+=' Ref: '+j.error_ref;const er=new Error(msg);er.payload=j;throw er;}return j;}
const get=(a,p)=>{const q=new URLSearchParams(Object.assign({accion:a},p||{}));return req(API+'?'+q.toString());};
const post=(a,d)=>{const b=new URLSearchParams();b.set('accion',a);Object.keys(d||{}).forEach(k=>b.set(k,d[k]==null?'':String(d[k])));return req(API,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:b.toString()});};
function alertF(type,html){const c=qs('#egFormAlert');if(!c)return;if(!html){c.innerHTML='';return;}const i=type==='danger'?'fa-exclamation-triangle':type==='warning'?'fa-exclamation-circle':type==='success'?'fa-check-circle':'fa-info-circle';c.innerHTML='<div class="alert alert-'+type+' alert-dismissible fade show mb-2"><i class="fas '+i+' mr-1"></i>'+html+'<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>';}
function setTipo(t){qs('#egTipo').value=t;qsa('#egTipoChipGroup .eg-chip').forEach(b=>b.classList.toggle('active',b.dataset.tipo===t));const comp=(t==='FACTURA'||t==='BOLETA');qs('#egSerieNumeroGroup').classList.toggle('d-none',!comp);qs('#egReciboRefGroup').classList.toggle('d-none',comp);qs('#egSerie').required=comp;qs('#egNumero').required=comp;}
function setNow(){const i=qs('#egFecha'),n=new Date();i.value=new Date(n.getTime()-n.getTimezoneOffset()*60000).toISOString().slice(0,16);}
function countC(){const t=qs('#egConcepto'),o=qs('#egConceptoCount');if(t&&o)o.textContent=t.value.length+' / 1000';}
function lockForm(en){const f=qs('#egForm');if(!f)return;qsa('input,textarea,button',f).forEach(el=>{if(el.id==='egBtnVistaPrevia'||el.id==='egBtnLimpiar'){el.disabled=false;return;}el.disabled=!en;});const b=qs('#egFormStateBadge');if(b){b.className=en?'badge badge-success':'badge badge-secondary';b.textContent=en?'Caja habilitada':'Registro bloqueado';}}
function renderCaja(d){
  st.schema=!!d.schema_ok;st.schemaMsg=(d.schema_message||'');st.caja=d.caja||{};st.saldo=d.saldo||null;
  const cd=st.caja.diaria||{},cm=st.caja.mensual||{};
  const bd=qs('#egCajaBadge'),bm=qs('#egCajaMensualBadge');
  if(bd){bd.className='badge badge-pill '+(st.caja.diaria_abierta?'badge-success':'badge-danger');bd.textContent=st.caja.diaria_abierta?'Abierta':'Cerrada';}
  if(bm){const open=(cm.estado||'').toLowerCase()==='abierta';bm.className='badge badge-pill '+(open?'badge-success':'badge-secondary');bm.textContent=open?'Abierta':(cm.codigo?'Cerrada':'Sin caja');}
  qs('#egCajaDiariaCodigo').textContent='CD: '+(cd.codigo||'—')+(cd.fecha?' ('+fmtDate(cd.fecha)+')':'');
  qs('#egCajaMensualCodigo').textContent='CM: '+(cm.codigo||'—');
  const m=qs('#egCajaMsg');const cl=!st.schema?'alert-danger':(st.caja.puede_registrar?'alert-success':'alert-warning');
  m.className='alert eg-caja-alert mt-3 mb-0 '+cl;
  m.innerHTML='<i class="fas fa-info-circle mr-1"></i>'+esc(!st.schema?(st.schemaMsg||'Falta migración egr_.'):(st.caja.mensaje||''));
  const sb=qs('#egSaldoResumen');
  if(st.schema&&st.saldo&&cd.id){
    sb.classList.remove('d-none');
    sb.innerHTML='<div><strong>Ingresos:</strong> '+esc(money(st.saldo.ingresos))+'</div><div><strong>Devoluciones:</strong> '+esc(money(st.saldo.devoluciones))+'</div><div><strong>Egresos activos:</strong> '+esc(money(st.saldo.egresos))+'</div><div class="eg-saldo-main"><strong>Saldo disponible:</strong> '+esc(money(st.saldo.saldo_disponible))+'</div>';
  }else{sb.classList.add('d-none');sb.innerHTML='';}
  lockForm(!!(st.schema&&st.caja.puede_registrar));
}
function datos(){return{tipo:(qs('#egTipo').value||'RECIBO').toUpperCase(),serie:qs('#egSerie').value.trim(),numero:qs('#egNumero').value.trim(),referencia:qs('#egReferencia').value.trim(),monto:parseFloat(qs('#egMonto').value||'0'),fecha:qs('#egFecha').value.trim(),benef:qs('#egBeneficiario').value.trim(),doc:qs('#egDocumento').value.trim(),concepto:qs('#egConcepto').value.trim(),obs:qs('#egObs').value.trim()};}
function validar(d){if(!d.concepto){alertF('danger','Escribe el concepto del egreso.');return false;}if(!(d.monto>0)){alertF('danger','El monto debe ser mayor a cero.');return false;}if(!d.fecha){alertF('danger','Selecciona fecha y hora.');return false;}if((d.tipo==='FACTURA'||d.tipo==='BOLETA')&&(!d.serie||!d.numero)){alertF('danger','Serie y número son obligatorios para factura/boleta.');return false;}return true;}
async function loadCaja(){try{renderCaja(await get('estado'));}catch(e){renderCaja({schema_ok:false,schema_message:e.message,caja:{puede_registrar:false,diaria:{},mensual:{}},saldo:null});}}
function rowsHTML(rows){
  if(!rows.length)return '<tr><td colspan="9" class="text-muted small">No hay egresos que coincidan con el filtro.</td></tr>';
  return rows.map(r=>{
    const tipo=(r.tipo_comprobante||'').toUpperCase(),an=((r.estado||'').toUpperCase()==='ANULADO');
    const tBadge=tipo==='FACTURA'?'badge-info':(tipo==='BOLETA'?'badge-primary':'badge-secondary');
    const tLbl=tipo==='FACTURA'?'Factura':(tipo==='BOLETA'?'Boleta':'Recibo');
    const comp=tipo==='RECIBO'?(r.referencia||'INTERNO'):(((r.serie||'')+'-'+(r.numero||'')).replace(/^-|-$|^$/,'—'));
    const c=(r.concepto||'').length>80?(r.concepto||'').slice(0,77)+'…':(r.concepto||'');
    return '<tr class="'+(an?'eg-row-anulado':'')+'"><td><strong>'+esc(r.codigo||'')+'</strong></td><td class="text-nowrap">'+esc(fmt(r.fecha_emision))+'</td><td><span class="badge '+tBadge+'">'+tLbl+'</span></td><td class="text-nowrap">'+esc(comp)+'</td><td>'+esc(r.beneficiario||'—')+'</td><td class="small">'+esc(c||'—')+'</td><td class="text-right font-weight-bold">'+esc(money(r.monto))+'</td><td><span class="badge '+(an?'badge-danger':'badge-success')+'">'+(an?'Anulado':'Activo')+'</span></td><td class="text-center text-nowrap"><button class="btn btn-xs btn-outline-primary mr-1" data-action="preview" data-id="'+r.id+'" title="Vista previa"><i class="fas fa-eye"></i></button><a class="btn btn-xs btn-outline-secondary mr-1" target="_blank" rel="noopener" href="'+API+'?accion=egreso_pdf&id='+r.id+'" title="Imprimir PDF"><i class="fas fa-print"></i></a><button class="btn btn-xs btn-outline-danger" data-action="anular" data-id="'+r.id+'" '+(an?'disabled':'')+' title="Anular"><i class="fas fa-ban"></i></button></td></tr>';
  }).join('');
}
function pager(tp,p){
  const ul=qs('#egPager');if(tp<=1){ul.innerHTML='';return;}
  const it=[],add=(x,l,d,a)=>it.push('<li class="page-item '+(d?'disabled':'')+' '+(a?'active':'')+'"><a href="#" class="page-link" data-page="'+x+'">'+l+'</a></li>');
  add(p-1,'«',p<=1,false);let s=Math.max(1,p-2),e=Math.min(tp,s+4);s=Math.max(1,e-4);for(let i=s;i<=e;i++)add(i,''+i,false,i===p);add(p+1,'»',p>=tp,false);ul.innerHTML=it.join('');
}
async function loadList(){
  const tb=qs('#egTableBody'),resTxt=qs('#egResumenListado'),tot=qs('#egTotalesDia');
  if(!st.schema){tb.innerHTML='<tr><td colspan="9" class="text-danger small">'+esc(st.schemaMsg||'No se puede listar por un error de conexión/API.')+'</td></tr>';resTxt.textContent='';tot.textContent='';pager(1,1);return;}
  tb.innerHTML='<tr><td colspan="9" class="text-muted small">Cargando...</td></tr>';
  try{
    const r=await get('listar',{page:st.p,per:st.pp,q:st.q,tipo:st.t,estado:st.e}),rows=Array.isArray(r.rows)?r.rows:[];
    tb.innerHTML=rowsHTML(rows);pager(r.total_pages||1,r.page||1);
    if((r.total||0)>0){const from=((r.page-1)*r.per)+1,to=Math.min(r.total,from+rows.length-1);resTxt.textContent='Mostrando '+from+'–'+to+' de '+r.total+' egresos.';}else{resTxt.textContent='Sin egresos para los filtros actuales.';}
    const s=rows.reduce((a,x)=>((x.estado||'').toUpperCase()==='ANULADO')?a:a+Number(x.monto||0),0);tot.textContent=rows.length?('Total visible activos: '+money(s)):'';
  }catch(e){tb.innerHTML='<tr><td colspan="9" class="text-danger small">'+esc(e.message||'Error al listar egresos.')+'</td></tr>';resTxt.textContent='';tot.textContent='';}
}
function voucherHTML(v,logo){
  const tipo=(v.tipo_comprobante||v.tipo||'RECIBO').toUpperCase();
  const comp=tipo==='RECIBO'?(v.referencia||'RECIBO INTERNO'):((v.serie||'')+'-'+(v.numero||'')).replace(/^-|-$|^$/,'—');
  const est=((v.estado||'ACTIVO').toUpperCase()==='ANULADO')?'ANULADO':'EMITIDO';
  const resp=esc((v.creado_nombre||'').trim()||USR||'Responsable');
  const logoHtml=logo?'<div class="eg-voucher-logo"><img src="'+esc(logo)+'" alt="Logo"></div>':'<div class="eg-voucher-logo eg-voucher-logo-placeholder"></div>';
  return '<div class="eg-voucher"><div class="eg-voucher-head">'+logoHtml+'<div class="eg-voucher-head-main"><div class="eg-voucher-empresa">'+esc(v.empresa_nombre||EMP)+'</div><div class="eg-voucher-sub text-muted small">Recibo de egreso</div></div><div class="eg-voucher-amount-box"><div class="label">S/.</div><div class="value">'+esc(Number(v.monto||0).toFixed(2))+'</div></div></div><div class="eg-voucher-row mt-2"><div class="eg-voucher-block"><div class="label">Fecha y hora</div><div class="value">'+esc(fmt(v.fecha_emision||v.fecha||''))+'</div></div><div class="eg-voucher-block"><div class="label">Comprobante</div><div class="value">'+esc(tipo+' '+comp)+'</div></div><div class="eg-voucher-block"><div class="label">Estado</div><div class="value">'+esc(est)+'</div></div></div><div class="eg-voucher-row mt-2"><div class="eg-voucher-block eg-voucher-block-wide"><div class="label">Beneficiario</div><div class="value">'+esc(v.beneficiario||'—')+'</div></div><div class="eg-voucher-block"><div class="label">Documento</div><div class="value">'+esc(v.documento||'—')+'</div></div></div><div class="eg-voucher-section mt-3"><div class="label">Concepto</div><div class="eg-voucher-concepto">'+esc(v.concepto||'')+'</div></div><div class="eg-voucher-footer"><div class="eg-voucher-firma"><div class="line"></div><div class="caption">Responsable<br><strong>'+resp+'</strong><br><span class="small">Código: '+esc(v.codigo||'Sin guardar')+'</span></div></div></div></div>';
}
function showVoucher(v,logo){
  st.prev=v||null;const c=qs('#egVoucher');if(!c||!v)return;c.innerHTML=voucherHTML(v,logo||'../../dist/img/AdminLTELogo.png');
  qs('#egBtnPrintReal').disabled=!(v.id>0);
  if(window.jQuery)window.jQuery('#egresoPrintModal').modal('show');
}
async function previewId(id){const r=await get('detalle',{id:id});showVoucher(r.row,r.row.empresa_logo_web||'../../dist/img/AdminLTELogo.png');}
function clearForm(){const f=qs('#egForm');if(!f)return;f.reset();setTipo('RECIBO');setNow();countC();alertF('info','Formulario limpio. Completa los datos y registra el egreso.');}
async function save(e){
  e.preventDefault();
  if(!st.schema){alertF('danger','No se puede registrar: falta migración SQL de tablas egr_.');return;}
  if(!st.caja||!st.caja.puede_registrar){alertF('warning','No hay caja diaria abierta. Abre caja desde el módulo Caja.');return;}
  const d=datos();if(!validar(d))return;
  const b=qs('#egBtnGuardar');b.disabled=true;
  try{
    const r=await post('crear',{tipo_comprobante:d.tipo,serie:d.serie,numero:d.numero,referencia:d.referencia,fecha_emision:d.fecha,monto:d.monto,beneficiario:d.benef,documento:d.doc,concepto:d.concepto,observaciones:d.obs});
    alertF('success',esc(r.msg||'Egreso registrado.'));await loadCaja();st.p=1;await loadList();if(r.id)await previewId(r.id);clearForm();
  }catch(err){
    const sd=err.payload&&err.payload.saldo;
    if(sd)alertF('warning',esc(err.message)+'<br>Saldo disponible: <strong>'+esc(money(sd.saldo_disponible))+'</strong>');
    else alertF('danger',esc(err.message||'No se pudo guardar el egreso.'));
  }finally{b.disabled=false;}
}
async function anular(id){
  if(!window.confirm('¿Seguro que deseas anular este egreso?'))return;
  const motivo=window.prompt('Motivo de anulación (opcional):','')||'';
  try{await post('anular',{id:id,motivo:motivo});alertF('success','Egreso anulado correctamente.');await loadCaja();await loadList();}
  catch(e){alertF('danger',esc(e.message||'No se pudo anular el egreso.'));}
}
function bind(){
  document.addEventListener('click',ev=>{
    const chip=ev.target.closest('#egTipoChipGroup .eg-chip');if(chip){ev.preventDefault();setTipo(chip.dataset.tipo||'RECIBO');return;}
    const pg=ev.target.closest('#egPager a[data-page]');if(pg){ev.preventDefault();const p=parseInt(pg.dataset.page,10);if(!isNaN(p)&&p>=1){st.p=p;loadList();}return;}
    const act=ev.target.closest('[data-action][data-id]');if(act){const id=parseInt(act.dataset.id,10),a=act.dataset.action;if(!(id>0))return;if(a==='preview')previewId(id).catch(e=>alertF('danger',esc(e.message||'No se pudo abrir vista previa.')));if(a==='anular')anular(id);}
  });
  qs('#egForm').addEventListener('submit',save);
  qs('#egBtnLimpiar').addEventListener('click',clearForm);
  qs('#egBtnVistaPrevia').addEventListener('click',()=>{
    const d=datos();if(!d.concepto||!(d.monto>0)){alertF('warning','Para la vista previa, completa al menos concepto y monto.');return;}
    showVoucher({id:0,codigo:'SIN GUARDAR',tipo_comprobante:d.tipo,serie:d.serie,numero:d.numero,referencia:d.referencia,fecha_emision:d.fecha||new Date().toISOString(),monto:d.monto||0,beneficiario:d.benef||'',documento:d.doc||'',concepto:d.concepto||'',estado:'ACTIVO',empresa_nombre:EMP,creado_nombre:USR},'../../dist/img/AdminLTELogo.png');
  });
  qs('#egBtnPrintReal').addEventListener('click',()=>{const v=st.prev;if(!v||!(v.id>0)){alertF('warning','Primero guarda el egreso para poder imprimir el PDF oficial.');return;}window.open(API+'?accion=egreso_pdf&id='+v.id,'_blank','noopener');});
  let t=null;qs('#egQ').addEventListener('input',()=>{clearTimeout(t);t=setTimeout(()=>{st.q=qs('#egQ').value||'';st.p=1;loadList();},250);});
  qs('#egFiltroTipo').addEventListener('change',()=>{st.t=(qs('#egFiltroTipo').value||'TODOS').toUpperCase();st.p=1;loadList();});
  qs('#egFiltroEstado').addEventListener('change',()=>{st.e=(qs('#egFiltroEstado').value||'TODOS').toUpperCase();st.p=1;loadList();});
  qs('#egConcepto').setAttribute('maxlength','1000');qs('#egConcepto').addEventListener('input',countC);
}
async function init(){setTipo('RECIBO');setNow();countC();bind();await loadCaja();await loadList();}
init();
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
