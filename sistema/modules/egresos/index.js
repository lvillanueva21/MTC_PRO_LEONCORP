// modules/egresos/index.js
(function(){
const root=document.querySelector('#egApp'); if(!root) return;
const API=String(root.dataset.api||''),EMP=String(root.dataset.emp||''),USR=String(root.dataset.usr||'');
const qs=(s,c=document)=>c.querySelector(s),qsa=(s,c=document)=>Array.from(c.querySelectorAll(s));
const FUENTE_ORDER=['EFECTIVO','YAPE','PLIN','TRANSFERENCIA'];
const FUENTE_LABEL={EFECTIVO:'Efectivo',YAPE:'Yape',PLIN:'Plin',TRANSFERENCIA:'Transferencia'};
const st={p:1,pp:8,q:'',t:'TODOS',e:'TODOS',schema:true,schemaMsg:'',caja:null,saldo:null,prev:null,fuentesAsignadas:{}};
const esc=s=>(s||'').toString().replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
const round2=v=>Math.round((Number(v||0)+Number.EPSILON)*100)/100;
const num=v=>{const n=parseFloat(v);return Number.isFinite(n)?n:0;};
const money=v=>'S/ '+round2(v).toFixed(2);
const fmt=(x)=>{if(!x)return '-';const d=new Date(String(x).replace(' ','T'));if(isNaN(d))return '-';return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear()+' '+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0');};
const fmtDate=(x)=>{if(!x)return '-';const d=new Date(String(x)+'T00:00:00');if(isNaN(d))return '-';return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear();};
const canonKey=(k)=>{const n=String(k||'').toUpperCase().trim();if(FUENTE_ORDER.includes(n))return n;if(n.includes('EFECTIVO')||n.includes('CASH'))return 'EFECTIVO';if(n.includes('YAPE'))return 'YAPE';if(n.includes('PLIN'))return 'PLIN';if(n.includes('TRANSFER'))return 'TRANSFERENCIA';return '';};
const montoObjetivo=()=>round2(num((qs('#egMonto')||{}).value));
const normalizeFuentes=(m)=>{const o={};Object.keys(m||{}).forEach(k=>{const key=canonKey(k),v=round2(num(m[k]));if(!key||v<=0)return;o[key]=round2((o[key]||0)+v);});return o;};
const sumFuentes=(m)=>round2(Object.values(m||{}).reduce((a,v)=>a+num(v),0));
const fuentesPayload=(m)=>{const o=normalizeFuentes(m);return FUENTE_ORDER.filter(k=>(o[k]||0)>0).map(k=>({key:k,monto:round2(o[k]),label:FUENTE_LABEL[k]||k}));};function saldoMediosRows(saldo){
  const rows=Array.isArray(saldo&&saldo.por_medio)?saldo.por_medio:[];
  const byKey={};
  rows.forEach(r=>{const k=canonKey((r&&r.key)||'');if(k)byKey[k]=r;});
  return FUENTE_ORDER.map(k=>{
    const r=byKey[k]||{};
    const label=String(r.label||FUENTE_LABEL[k]||k);
    const neto=round2(num(r.monto_neto!=null?r.monto_neto:(r.neto||0)));
    const egAct=round2(num(r.egresos_activos!=null?r.egresos_activos:0));
    const disp=round2(num(r.saldo_disponible!=null?r.saldo_disponible:neto));
    return {key:k,label:label,monto_neto:neto,egresos_activos:egAct,saldo_disponible:disp};
  });
}
const fuenteMap=()=>{const out={};saldoMediosRows(st.saldo||{}).forEach(r=>out[r.key]=r);return out;};
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
  qs('#egCajaDiariaCodigo').textContent='CD: '+(cd.codigo||'-')+(cd.fecha?' ('+fmtDate(cd.fecha)+')':'');
  qs('#egCajaMensualCodigo').textContent='CM: '+(cm.codigo||'-');
  const m=qs('#egCajaMsg');const cl=!st.schema?'alert-danger':(st.caja.puede_registrar?'alert-success':'alert-warning');
  m.className='alert eg-caja-alert mt-3 mb-0 '+cl;
  m.innerHTML='<i class="fas fa-info-circle mr-1"></i>'+esc(!st.schema?(st.schemaMsg||'Falta migracion egr_.'):(st.caja.mensaje||''));
  const sb=qs('#egSaldoResumen');
  if(st.schema&&st.saldo&&cd.id){
    const mediosRows=saldoMediosRows(st.saldo);
    const mediosHtml=mediosRows.map(r=>'<tr><th scope="row">'+esc(r.label)+'</th><td class="text-right">'+esc(money(r.saldo_disponible))+'</td></tr>').join('');
    sb.classList.remove('d-none');
    sb.innerHTML='<div class="eg-saldo-grid"><div class="eg-saldo-col"><div class="eg-saldo-line"><strong>Ingresos:</strong> <span>'+esc(money(st.saldo.ingresos))+'</span></div><div class="eg-saldo-line"><strong>Devoluciones:</strong> <span>'+esc(money(st.saldo.devoluciones))+'</span></div><div class="eg-saldo-line"><strong>Egresos activos:</strong> <span>'+esc(money(st.saldo.egresos))+'</span></div><div class="eg-saldo-main"><strong>Saldo disponible:</strong> <span>'+esc(money(st.saldo.saldo_disponible))+'</span></div></div><div class="eg-saldo-col"><div class="eg-saldo-medios-wrap"><table class="table table-sm table-borderless mb-0 eg-saldo-medios-table"><thead><tr><th>Medio</th><th class="text-right">Cantidad</th></tr></thead><tbody>'+mediosHtml+'</tbody></table></div></div></div>';
  }else{sb.classList.add('d-none');sb.innerHTML='';}
  lockForm(!!(st.schema&&st.caja.puede_registrar));renderFuentesResumen();if(hasFuentesModal())renderFuentesModalRows();
}
const hasFuentesModal=()=>!!(window.jQuery&&window.jQuery('#egFuentesModal').hasClass('show'));
const collectFuentesModal=()=>{const out={};qsa('.js-eg-fuente-monto',qs('#egFuentesTableBody')).forEach(i=>{const k=canonKey(i.dataset.key),v=round2(num(i.value));if(k&&v>0)out[k]=v;});return normalizeFuentes(out);};
function setFuentesModalMsg(type,html){const m=qs('#egFuentesModalMsg');if(!m)return;m.className='alert py-2 mb-2 alert-'+type;m.innerHTML=html;}
function updateFuentesModalTotals(){const monto=montoObjetivo(),map=collectFuentesModal(),asig=sumFuentes(map),diff=round2(monto-asig);const dmap=fuenteMap();if(qs('#egFuentesMontoObjetivo'))qs('#egFuentesMontoObjetivo').textContent=money(monto);if(qs('#egFuentesMontoAsignado'))qs('#egFuentesMontoAsignado').textContent=money(asig);if(qs('#egFuentesMontoDiff'))qs('#egFuentesMontoDiff').textContent=(Math.abs(diff)<0.01?money(Math.abs(diff)):(diff>0?'Falta ':'Exceso ')+money(Math.abs(diff)));let err='';Object.keys(map).forEach(k=>{const disp=round2(num((dmap[k]||{}).saldo_disponible));if(map[k]>disp+0.0001)err='La fuente '+(FUENTE_LABEL[k]||k)+' excede su disponible ('+money(disp)+').';});const btn=qs('#egBtnFuentesAplicar');if(!(monto>0)){setFuentesModalMsg('danger','Ingresa un monto mayor a cero para distribuir.');if(btn)btn.disabled=true;return;}if(err!==''){setFuentesModalMsg('danger',esc(err));if(btn)btn.disabled=true;return;}if(Object.keys(map).length===0){setFuentesModalMsg('info','Selecciona una o mas fuentes para el egreso.');if(btn)btn.disabled=true;return;}if(Math.abs(diff)<0.01){setFuentesModalMsg('success','Distribucion valida.');if(btn)btn.disabled=false;return;}if(diff>0){setFuentesModalMsg('warning','Falta asignar '+esc(money(diff))+'.');if(btn)btn.disabled=true;return;}setFuentesModalMsg('danger','Exceso de '+esc(money(Math.abs(diff)))+'.');if(btn)btn.disabled=true;}
function renderFuentesModalRows(){const tb=qs('#egFuentesTableBody');if(!tb)return;const map=normalizeFuentes(st.fuentesAsignadas),rows=saldoMediosRows(st.saldo||{});tb.innerHTML=rows.map(r=>{const v=round2(num(map[r.key]||0));return '<tr><td><strong>'+esc(r.label)+'</strong></td><td class="text-right">'+esc(money(r.saldo_disponible))+'</td><td class="text-right">'+esc(money(r.egresos_activos))+'</td><td class="text-right"><input type="number" min="0" step="0.01" class="form-control form-control-sm text-right js-eg-fuente-monto" data-key="'+esc(r.key)+'" value="'+esc(v>0?v.toFixed(2):'')+'" placeholder="0.00"></td></tr>';}).join('');updateFuentesModalTotals();}
function autoDistribuirFuentes(){const monto=montoObjetivo();if(!(monto>0)){updateFuentesModalTotals();return;}const dmap=fuenteMap();let rem=monto;FUENTE_ORDER.forEach(k=>{const i=qs('.js-eg-fuente-monto[data-key="'+k+'"]',qs('#egFuentesTableBody'));if(!i)return;const disp=round2(num((dmap[k]||{}).saldo_disponible));const take=round2(Math.max(0,Math.min(disp,rem)));i.value=take>0?take.toFixed(2):'';rem=round2(rem-take);});updateFuentesModalTotals();if(rem>0.009)setFuentesModalMsg('warning','No alcanza el disponible total. Faltante: '+esc(money(rem))+'.');}
function clearFuentesModalInputs(){qsa('.js-eg-fuente-monto',qs('#egFuentesTableBody')).forEach(i=>i.value='');updateFuentesModalTotals();}
function validateFuentes(mapInput,monto){const map=normalizeFuentes(mapInput);if(!(monto>0))return {ok:false,msg:'Define primero el monto del egreso.'};if(Object.keys(map).length===0)return {ok:false,msg:'La distribucion por fuente es obligatoria.'};const sum=sumFuentes(map);if(Math.abs(sum-monto)>0.009)return {ok:false,msg:'La suma de fuentes debe ser exactamente '+money(monto)+'.'};const dmap=fuenteMap();for(const k of Object.keys(map)){const disp=round2(num((dmap[k]||{}).saldo_disponible));if(map[k]>disp+0.0001)return {ok:false,msg:'La fuente '+(FUENTE_LABEL[k]||k)+' solo tiene '+money(disp)+' disponible.'};}return {ok:true,map:map,total:sum};}
function applyFuentesModal(){const v=validateFuentes(collectFuentesModal(),montoObjetivo());if(!v.ok){setFuentesModalMsg('danger',esc(v.msg));return;}st.fuentesAsignadas=v.map;renderFuentesResumen();if(window.jQuery)window.jQuery('#egFuentesModal').modal('hide');}
function renderFuentesResumen(){const box=qs('#egFuentesResumen');if(!box)return;const monto=montoObjetivo(),map=normalizeFuentes(st.fuentesAsignadas),sum=sumFuentes(map);st.fuentesAsignadas=map;const parts=FUENTE_ORDER.filter(k=>(map[k]||0)>0).map(k=>'<span class="eg-fuente-pill">'+esc(FUENTE_LABEL[k]||k)+': <strong>'+esc(money(map[k]))+'</strong></span>').join('');let cls='pending',msg='';if(!(monto>0)){msg='Ingresa un monto para habilitar la distribucion obligatoria por fuente.';}else if(Object.keys(map).length===0){msg='Pendiente de distribucion. Debes asignar una o mas fuentes.';}else{const v=validateFuentes(map,monto);if(v.ok){cls='ok';msg='Distribucion valida.';}else if(sum>monto){cls='bad';msg='Exceso de '+money(sum-monto)+'.';}else{cls='warn';msg=v.msg;}}box.className='eg-fuentes-resumen '+cls;box.innerHTML='<div class="eg-fuentes-head"><strong>'+esc(msg)+'</strong>'+(monto>0?' <span class="text-muted">Asignado: '+esc(money(sum))+' / '+esc(money(monto))+'</span>':'')+'</div>'+(parts?'<div class="eg-fuentes-pills">'+parts+'</div>':'');}

function datos(){return{tipo:(qs('#egTipo').value||'RECIBO').toUpperCase(),serie:qs('#egSerie').value.trim(),numero:qs('#egNumero').value.trim(),referencia:qs('#egReferencia').value.trim(),monto:round2(num(qs('#egMonto').value||'0')),fecha:qs('#egFecha').value.trim(),benef:qs('#egBeneficiario').value.trim(),doc:qs('#egDocumento').value.trim(),concepto:qs('#egConcepto').value.trim(),obs:qs('#egObs').value.trim()};}
function validar(d){if(!d.concepto){alertF('danger','Escribe el concepto del egreso.');return false;}if(!(d.monto>0)){alertF('danger','El monto debe ser mayor a cero.');return false;}if(!d.fecha){alertF('danger','Selecciona fecha y hora.');return false;}if((d.tipo==='FACTURA'||d.tipo==='BOLETA')&&(!d.serie||!d.numero)){alertF('danger','Serie y numero son obligatorios para factura/boleta.');return false;}return true;}
async function loadCaja(){try{renderCaja(await get('estado'));}catch(e){renderCaja({schema_ok:false,schema_message:e.message,caja:{puede_registrar:false,diaria:{},mensual:{}},saldo:null});}}
function rowsHTML(rows){
  if(!rows.length)return '<tr><td colspan="9" class="text-muted small">No hay egresos que coincidan con el filtro.</td></tr>';
  return rows.map(r=>{
    const tipo=(r.tipo_comprobante||'').toUpperCase(),an=((r.estado||'').toUpperCase()==='ANULADO');
    const tBadge=tipo==='FACTURA'?'badge-info':(tipo==='BOLETA'?'badge-primary':'badge-secondary');
    const tLbl=tipo==='FACTURA'?'Factura':(tipo==='BOLETA'?'Boleta':'Recibo');
    const comp=tipo==='RECIBO'?(r.referencia||'INTERNO'):(((r.serie||'')+'-'+(r.numero||'')).replace(/^-|-$|^$/,'-'));
    const c=(r.concepto||'').length>80?(r.concepto||'').slice(0,77)+'...':(r.concepto||'');
    return '<tr class="'+(an?'eg-row-anulado':'')+'"><td><strong>'+esc(r.codigo||'')+'</strong></td><td class="text-nowrap">'+esc(fmt(r.fecha_emision))+'</td><td><span class="badge '+tBadge+'">'+tLbl+'</span></td><td class="text-nowrap">'+esc(comp)+'</td><td>'+esc(r.beneficiario||'-')+'</td><td class="small">'+esc(c||'-')+'</td><td class="text-right font-weight-bold">'+esc(money(r.monto))+'</td><td><span class="badge '+(an?'badge-danger':'badge-success')+'">'+(an?'Anulado':'Activo')+'</span></td><td class="text-center text-nowrap"><button class="btn btn-xs btn-outline-primary mr-1" data-action="preview" data-id="'+r.id+'" title="Vista previa"><i class="fas fa-eye"></i></button><a class="btn btn-xs btn-outline-secondary mr-1" target="_blank" rel="noopener" href="'+API+'?accion=egreso_pdf&id='+r.id+'" title="Imprimir PDF"><i class="fas fa-print"></i></a><button class="btn btn-xs btn-outline-danger" data-action="anular" data-id="'+r.id+'" '+(an?'disabled':'')+' title="Anular"><i class="fas fa-ban"></i></button></td></tr>';
  }).join('');
}
function pager(tp,p){
  const ul=qs('#egPager');if(tp<=1){ul.innerHTML='';return;}
  const it=[],add=(x,l,d,a)=>it.push('<li class="page-item '+(d?'disabled':'')+' '+(a?'active':'')+'"><a href="#" class="page-link" data-page="'+x+'">'+l+'</a></li>');
  add(p-1,'«',p<=1,false);let s=Math.max(1,p-2),e=Math.min(tp,s+4);s=Math.max(1,e-4);for(let i=s;i<=e;i++)add(i,''+i,false,i===p);add(p+1,'»',p>=tp,false);ul.innerHTML=it.join('');
}
async function loadList(){
  const tb=qs('#egTableBody'),resTxt=qs('#egResumenListado'),tot=qs('#egTotalesDia');
  if(!st.schema){tb.innerHTML='<tr><td colspan="9" class="text-danger small">'+esc(st.schemaMsg||'No se puede listar por un error de conexion/API.')+'</td></tr>';resTxt.textContent='';tot.textContent='';pager(1,1);return;}
  tb.innerHTML='<tr><td colspan="9" class="text-muted small">Cargando...</td></tr>';
  try{
    const r=await get('listar',{page:st.p,per:st.pp,q:st.q,tipo:st.t,estado:st.e}),rows=Array.isArray(r.rows)?r.rows:[];
    tb.innerHTML=rowsHTML(rows);pager(r.total_pages||1,r.page||1);
    if((r.total||0)>0){const from=((r.page-1)*r.per)+1,to=Math.min(r.total,from+rows.length-1);resTxt.textContent='Mostrando '+from+'-'+to+' de '+r.total+' egresos.';}else{resTxt.textContent='Sin egresos para los filtros actuales.';}
    const s=rows.reduce((a,x)=>((x.estado||'').toUpperCase()==='ANULADO')?a:a+Number(x.monto||0),0);tot.textContent=rows.length?('Total visible activos: '+money(s)):'';
  }catch(e){tb.innerHTML='<tr><td colspan="9" class="text-danger small">'+esc(e.message||'Error al listar egresos.')+'</td></tr>';resTxt.textContent='';tot.textContent='';}
}
function voucherHTML(v,logo){
  const tipo=(v.tipo_comprobante||v.tipo||'RECIBO').toUpperCase();
  const comp=tipo==='RECIBO'?(v.referencia||'RECIBO INTERNO'):((v.serie||'')+'-'+(v.numero||'')).replace(/^-|-$|^$/,'-');
  const est=((v.estado||'ACTIVO').toUpperCase()==='ANULADO')?'ANULADO':'EMITIDO';
  const resp=esc((v.creado_nombre||'').trim()||USR||'Responsable');
  const logoHtml=logo?'<div class="eg-voucher-logo"><img src="'+esc(logo)+'" alt="Logo"></div>':'<div class="eg-voucher-logo eg-voucher-logo-placeholder"></div>';
  const fuentes=Array.isArray(v.fuentes)?v.fuentes:[];
  const fuentesHtml=fuentes.length
    ? '<ul class="eg-voucher-fuentes-list">'+fuentes.map(f=>{const key=canonKey((f&&f.key)||(f&&f.fuente_key)||'');const lbl=(f&&f.label)||FUENTE_LABEL[key]||key||'Fuente';return '<li><span>'+esc(lbl)+'</span><strong>'+esc(money((f&&f.monto)||0))+'</strong></li>';}).join('')+'</ul>'
    : '<div class="text-muted small">Sin fuentes registradas.</div>';
  return '<div class="eg-voucher"><div class="eg-voucher-head">'+logoHtml+'<div class="eg-voucher-head-main"><div class="eg-voucher-empresa">'+esc(v.empresa_nombre||EMP)+'</div><div class="eg-voucher-sub text-muted small">Recibo de egreso</div></div><div class="eg-voucher-amount-box"><div class="label">S/.</div><div class="value">'+esc(Number(v.monto||0).toFixed(2))+'</div></div></div><div class="eg-voucher-row mt-2"><div class="eg-voucher-block"><div class="label">Fecha y hora</div><div class="value">'+esc(fmt(v.fecha_emision||v.fecha||''))+'</div></div><div class="eg-voucher-block"><div class="label">Comprobante</div><div class="value">'+esc(tipo+' '+comp)+'</div></div><div class="eg-voucher-block"><div class="label">Estado</div><div class="value">'+esc(est)+'</div></div></div><div class="eg-voucher-row mt-2"><div class="eg-voucher-block eg-voucher-block-wide"><div class="label">Beneficiario</div><div class="value">'+esc(v.beneficiario||'-')+'</div></div><div class="eg-voucher-block"><div class="label">Documento</div><div class="value">'+esc(v.documento||'-')+'</div></div></div><div class="eg-voucher-section mt-3"><div class="label">Concepto</div><div class="eg-voucher-concepto">'+esc(v.concepto||'')+'</div></div><div class="eg-voucher-section mt-2"><div class="label">Fuentes de salida</div>'+fuentesHtml+'</div><div class="eg-voucher-footer"><div class="eg-voucher-firma"><div class="line"></div><div class="caption">Responsable<br><strong>'+resp+'</strong><br><span class="small">Codigo: '+esc(v.codigo||'Sin guardar')+'</span></div></div></div></div>';
}
function showVoucher(v,logo){
  st.prev=v||null;const c=qs('#egVoucher');if(!c||!v)return;c.innerHTML=voucherHTML(v,logo||'../../dist/img/AdminLTELogo.png');
  qs('#egBtnPrintReal').disabled=!(v.id>0);
  if(window.jQuery)window.jQuery('#egresoPrintModal').modal('show');
}
async function previewId(id){const r=await get('detalle',{id:id});showVoucher(r.row,r.row.empresa_logo_web||'../../dist/img/AdminLTELogo.png');}
function clearForm(){const f=qs('#egForm');if(!f)return;f.reset();setTipo('RECIBO');setNow();countC();st.fuentesAsignadas={};renderFuentesResumen();alertF('info','Formulario limpio. Completa los datos y registra el egreso.');}
async function save(e){
  e.preventDefault();
  if(!st.schema){alertF('danger','No se puede registrar: falta migracion SQL de tablas egr_.');return;}
  if(!st.caja||!st.caja.puede_registrar){alertF('warning','No hay caja diaria abierta. Abre caja desde el modulo Caja.');return;}
  const d=datos();if(!validar(d))return;
  const vf=validateFuentes(st.fuentesAsignadas,d.monto);
  if(!vf.ok){alertF('warning',esc(vf.msg)+'<br>Haz clic en <strong>Distribuir</strong> para corregir.');if(qs('#egBtnDistribuir'))qs('#egBtnDistribuir').click();return;}
  const b=qs('#egBtnGuardar');b.disabled=true;
  try{
    const r=await post('crear',{tipo_comprobante:d.tipo,serie:d.serie,numero:d.numero,referencia:d.referencia,fecha_emision:d.fecha,monto:d.monto,beneficiario:d.benef,documento:d.doc,concepto:d.concepto,observaciones:d.obs,fuentes_json:JSON.stringify(fuentesPayload(vf.map))});
    alertF('success',esc(r.msg||'Egreso registrado.'));await loadCaja();st.p=1;await loadList();if(r.id)await previewId(r.id);clearForm();
  }catch(err){
    const p=err.payload||{},sd=p.saldo,fe=p.fuente_error;
    if(fe){const k=canonKey(fe.key);alertF('warning',esc(err.message||'No se pudo guardar el egreso.')+'<br>Fuente: <strong>'+esc(FUENTE_LABEL[k]||k)+'</strong><br>Solicitado: <strong>'+esc(money(fe.monto_solicitado))+'</strong> · Disponible: <strong>'+esc(money(fe.disponible))+'</strong>');await loadCaja();if(qs('#egBtnDistribuir'))qs('#egBtnDistribuir').click();}
    else if(sd)alertF('warning',esc(err.message)+'<br>Saldo disponible: <strong>'+esc(money(sd.saldo_disponible))+'</strong>');
    else alertF('danger',esc(err.message||'No se pudo guardar el egreso.'));
  }finally{b.disabled=false;}
}
async function anular(id){
  if(!window.confirm('¿Seguro que deseas anular este egreso?'))return;
  const motivo=window.prompt('Motivo de anulacion (opcional):','')||'';
  try{await post('anular',{id:id,motivo:motivo});alertF('success','Egreso anulado correctamente.');await loadCaja();await loadList();}
  catch(e){alertF('danger',esc(e.message||'No se pudo anular el egreso.'));}
}
function bind(){
  document.addEventListener('click',ev=>{
    const chip=ev.target.closest('#egTipoChipGroup .eg-chip');if(chip){ev.preventDefault();setTipo(chip.dataset.tipo||'RECIBO');return;}
    const pg=ev.target.closest('#egPager a[data-page]');if(pg){ev.preventDefault();const p=parseInt(pg.dataset.page,10);if(!isNaN(p)&&p>=1){st.p=p;loadList();}return;}
    const act=ev.target.closest('[data-action][data-id]');if(act){const id=parseInt(act.dataset.id,10),a=act.dataset.action;if(!(id>0))return;if(a==='preview')previewId(id).catch(e=>alertF('danger',esc(e.message||'No se pudo abrir vista previa.')));if(a==='anular')anular(id);}
  });
  document.addEventListener('input',ev=>{if(ev.target&&ev.target.classList&&ev.target.classList.contains('js-eg-fuente-monto'))updateFuentesModalTotals();});
  qs('#egForm').addEventListener('submit',save);
  qs('#egBtnLimpiar').addEventListener('click',clearForm);
  if(qs('#egBtnDistribuir'))qs('#egBtnDistribuir').addEventListener('click',()=>{if(!(montoObjetivo()>0)){alertF('warning','Ingresa primero el monto del egreso para distribuir por fuente.');if(qs('#egMonto'))qs('#egMonto').focus();return;}renderFuentesModalRows();if(window.jQuery)window.jQuery('#egFuentesModal').modal('show');});
  if(qs('#egBtnFuentesAuto'))qs('#egBtnFuentesAuto').addEventListener('click',autoDistribuirFuentes);
  if(qs('#egBtnFuentesClear'))qs('#egBtnFuentesClear').addEventListener('click',clearFuentesModalInputs);
  if(qs('#egBtnFuentesAplicar'))qs('#egBtnFuentesAplicar').addEventListener('click',applyFuentesModal);
  if(qs('#egMonto'))qs('#egMonto').addEventListener('input',()=>{renderFuentesResumen();if(hasFuentesModal())updateFuentesModalTotals();});
  qs('#egBtnVistaPrevia').addEventListener('click',()=>{
    const d=datos();if(!d.concepto||!(d.monto>0)){alertF('warning','Para la vista previa, completa al menos concepto y monto.');return;}
    const vf=validateFuentes(st.fuentesAsignadas,d.monto);if(!vf.ok){alertF('warning',esc(vf.msg));if(qs('#egBtnDistribuir'))qs('#egBtnDistribuir').click();return;}
    showVoucher({id:0,codigo:'SIN GUARDAR',tipo_comprobante:d.tipo,serie:d.serie,numero:d.numero,referencia:d.referencia,fecha_emision:d.fecha||new Date().toISOString(),monto:d.monto||0,beneficiario:d.benef||'',documento:d.doc||'',concepto:d.concepto||'',estado:'ACTIVO',empresa_nombre:EMP,creado_nombre:USR,fuentes:fuentesPayload(vf.map)},'../../dist/img/AdminLTELogo.png');
  });
  qs('#egBtnPrintReal').addEventListener('click',()=>{const v=st.prev;if(!v||!(v.id>0)){alertF('warning','Primero guarda el egreso para poder imprimir el PDF oficial.');return;}window.open(API+'?accion=egreso_pdf&id='+v.id,'_blank','noopener');});
  let t=null;qs('#egQ').addEventListener('input',()=>{clearTimeout(t);t=setTimeout(()=>{st.q=qs('#egQ').value||'';st.p=1;loadList();},250);});
  qs('#egFiltroTipo').addEventListener('change',()=>{st.t=(qs('#egFiltroTipo').value||'TODOS').toUpperCase();st.p=1;loadList();});
  qs('#egFiltroEstado').addEventListener('change',()=>{st.e=(qs('#egFiltroEstado').value||'TODOS').toUpperCase();st.p=1;loadList();});
  qs('#egConcepto').setAttribute('maxlength','1000');qs('#egConcepto').addEventListener('input',countC);
}
async function init(){setTipo('RECIBO');setNow();countC();renderFuentesResumen();bind();await loadCaja();await loadList();}
init();
})();






