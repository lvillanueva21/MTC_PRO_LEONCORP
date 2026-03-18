// modules/egresos/index.js
(function(){
const root=document.querySelector('#egApp'); if(!root) return;
const API=String(root.dataset.api||''),EMP=String(root.dataset.emp||''),USR=String(root.dataset.usr||'');
const qs=(s,c=document)=>c.querySelector(s),qsa=(s,c=document)=>Array.from(c.querySelectorAll(s));
const FUENTE_ORDER=['EFECTIVO','YAPE','PLIN','TRANSFERENCIA'];
const FUENTE_LABEL={EFECTIVO:'Efectivo',YAPE:'Yape',PLIN:'Plin',TRANSFERENCIA:'Transferencia'};
const st={p:1,pp:8,q:'',t:'TODOS',e:'TODOS',schema:true,schemaMsg:'',caja:null,saldo:null,prev:null,fuentesAsignadas:{},detailCache:{}};
const esc=s=>(s||'').toString().replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
const round2=v=>Math.round((Number(v||0)+Number.EPSILON)*100)/100;
const num=v=>{const n=parseFloat(v);return Number.isFinite(n)?n:0;};
const money=v=>'S/ '+round2(v).toFixed(2);
const fmt=(x)=>{if(!x)return '-';const d=new Date(String(x).replace(' ','T'));if(isNaN(d))return '-';return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear()+' '+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0');};
const fmtDate=(x)=>{if(!x)return '-';const d=new Date(String(x)+'T00:00:00');if(isNaN(d))return '-';return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear();};
const canonKey=(k)=>{const n=String(k||'').toUpperCase().trim();if(FUENTE_ORDER.includes(n))return n;if(n.includes('EFECTIVO')||n.includes('CASH'))return 'EFECTIVO';if(n.includes('YAPE'))return 'YAPE';if(n.includes('PLIN'))return 'PLIN';if(n.includes('TRANSFER'))return 'TRANSFERENCIA';return '';};
const montoObjetivo=()=>round2(num((qs('#egMonto')||{}).value));
const tipoEgresoActual=()=>String((qs('#egTipoEgreso')||{}).value||'NORMAL').toUpperCase()==='MULTICAJA'?'MULTICAJA':'NORMAL';
const isMulticajaMode=()=>tipoEgresoActual()==='MULTICAJA';
const normalizeFuentes=(m)=>{const o={};Object.keys(m||{}).forEach(k=>{const key=canonKey(k),v=round2(num(m[k]));if(!key||v<=0)return;o[key]=round2((o[key]||0)+v);});return o;};
const sumFuentes=(m)=>round2(Object.values(m||{}).reduce((a,v)=>a+num(v),0));
const fuentesPayload=(m)=>{const o=normalizeFuentes(m);return FUENTE_ORDER.filter(k=>(o[k]||0)>0).map(k=>({key:k,monto:round2(o[k]),label:FUENTE_LABEL[k]||k}));};
const rawMulticajaPayload=()=>{const raw=((qs('#egMulticajaPayload')||{}).value||'[]');try{const arr=JSON.parse(raw);return Array.isArray(arr)?arr:[];}catch(_e){return [];}};

function cajaRegistroActualInfo(){
  const cd=(((st.caja||{}).diaria)||{});
  const cajaId=parseInt(cd.id||0,10);
  return {
    id_caja_diaria:(cajaId>0?cajaId:0),
    caja_codigo:String(cd.codigo||''),
    caja_fecha:String(cd.fecha||'')
  };
}

function normalizeMulticajaPayload(items){
  const out=[];
  const cajaActual=cajaRegistroActualInfo();

  (Array.isArray(items)?items:[]).forEach(item=>{
    const key=canonKey((item&&item.key)||(item&&item.fuente_key)||'');
    const monto=round2(num(item&&item.monto));
    const cajaIdRaw=parseInt((item&&item.id_caja_diaria)||(item&&item.caja_diaria_id)||(item&&item.caja_id)||0,10);
    const cajaId=(cajaIdRaw>0?cajaIdRaw:cajaActual.id_caja_diaria);

    if(!key||!(monto>0)||!(cajaId>0)) return;

    out.push({
      id_caja_diaria:cajaId,
      key:key,
      monto:monto,
      label:String((item&&item.label)||(item&&item.medio)||FUENTE_LABEL[key]||key),
      caja_codigo:String((item&&item.caja_codigo)||(item&&item.caja_diaria_codigo)||cajaActual.caja_codigo||''),
      caja_fecha:String((item&&item.caja_fecha)||(item&&item.caja_diaria_fecha)||cajaActual.caja_fecha||'')
    });
  });

  return out;
}

function enrichNormalFuentesPayload(items){
  const cajaActual=cajaRegistroActualInfo();
  return (Array.isArray(items)?items:[]).map(item=>{
    const key=canonKey((item&&item.key)||'');
    const monto=round2(num(item&&item.monto));
    if(!key||!(monto>0)||!(cajaActual.id_caja_diaria>0)) return null;

    return {
      id_caja_diaria:cajaActual.id_caja_diaria,
      key:key,
      monto:monto,
      label:String((item&&item.label)||FUENTE_LABEL[key]||key),
      caja_codigo:cajaActual.caja_codigo,
      caja_fecha:cajaActual.caja_fecha
    };
  }).filter(Boolean);
}

function groupedFuentesByCaja(items){
  const groups={};

  normalizeMulticajaPayload(items).forEach(item=>{
    const k=String(item.id_caja_diaria);
    if(!groups[k]){
      groups[k]={
        id_caja_diaria:item.id_caja_diaria,
        caja_diaria_codigo:item.caja_codigo||('Caja '+k),
        caja_diaria_fecha:item.caja_fecha||'',
        rows:[],
        total:0
      };
    }
    groups[k].rows.push(item);
    groups[k].total=round2(groups[k].total+item.monto);
  });

  return Object.values(groups);
}

function tipoEgresoLabel(tipo){
  return String(tipo||'').toUpperCase()==='MULTICAJA'?'Multicaja':'Normal';
}

function buildFuentesForSubmit(monto){
  if(isMulticajaMode()){
    const helper=window.egMulticaja||null;
    const validation=helper&&typeof helper.validateCurrent==='function' ? helper.validateCurrent() : null;
    const payload=validation&&validation.payload ? validation.payload : normalizeMulticajaPayload(rawMulticajaPayload());

    if(!validation||!validation.ok){
      const msg=(validation&&validation.msg)||'Completa la distribucion Multicaja antes de guardar.';
      return {ok:false,msg:msg,multicaja:true};
    }

    const total=round2(payload.reduce((a,x)=>a+num(x.monto),0));
    if(Math.abs(total-round2(monto))>0.009){
      return {ok:false,msg:'La suma distribuida en Multicaja debe ser exactamente '+money(monto)+'.',multicaja:true};
    }

    return {ok:true,tipo_egreso:'MULTICAJA',payload:payload};
  }

  const vf=validateFuentes(st.fuentesAsignadas,monto);
  if(!vf.ok) return {ok:false,msg:vf.msg,multicaja:false};

  const payloadNormal=enrichNormalFuentesPayload(fuentesPayload(vf.map));
  if(!payloadNormal.length){
    return {ok:false,msg:'No se pudo determinar la caja de registro actual para las fuentes del egreso normal.',multicaja:false};
  }

  return {ok:true,tipo_egreso:'NORMAL',payload:payloadNormal,vf:vf};
}

function buildFuentesForPreview(monto){
  return buildFuentesForSubmit(monto);
}

function saldoMediosRows(saldo){
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

function renderListScopeInfo(context){
  const el=qs('#egScopeInfo');
  if(!el) return;
  if(!context){el.innerHTML='';return;}
  const title=esc(context.title||'Contexto');
  const detail=esc(context.detail||'');
  el.innerHTML='<span class="eg-scope-chip"><span class="badge badge-light border">Periodo</span><strong>'+title+'</strong><span>'+detail+'</span></span>';
}

function renderListCounter(total,page,per,currentCount){
  const el=qs('#egResumenListado');
  if(!el) return;
  if(total<=0){el.textContent='Sin resultados para este filtro.';return;}
  const pages=Math.max(1,Math.ceil(total/per));
  const from=((page-1)*per)+(currentCount>0?1:0);
  const to=Math.min(total,((page-1)*per)+currentCount);
  el.textContent='Mostrando '+from+'-'+to+' de '+total+' egresos | pagina '+page+' de '+pages;
}

function syncListScopeUI(){
  const scope=(qs('#egScope')||{}).value||'latest';
  const fechaWrap=qs('#egFechaWrap'),desdeWrap=qs('#egDesdeWrap'),hastaWrap=qs('#egHastaWrap');
  if(fechaWrap)fechaWrap.classList.toggle('d-none',scope!=='date');
  if(desdeWrap)desdeWrap.classList.toggle('d-none',scope!=='range');
  if(hastaWrap)hastaWrap.classList.toggle('d-none',scope!=='range');
  const reset=qs('#egResetScope');
  if(reset){
    const isDefault=scope==='latest' && !(qs('#egFechaFiltro')||{}).value && !(qs('#egDesde')||{}).value && !(qs('#egHasta')||{}).value;
    reset.disabled=isDefault;
  }
}

function applyListScopeFilters(){
  const scope=((qs('#egScope')||{}).value||'latest').trim();
  const fecha=((qs('#egFechaFiltro')||{}).value||'').trim();
  const desde=((qs('#egDesde')||{}).value||'').trim();
  const hasta=((qs('#egHasta')||{}).value||'').trim();

  if(scope==='date'){
    if(!fecha){alertF('warning','Selecciona una fecha para filtrar los egresos.');return;}
    st.scope='date';st.fecha=fecha;st.desde='';st.hasta='';
  }else if(scope==='range'){
    if(!desde||!hasta){alertF('warning','Completa las fechas Desde y Hasta para aplicar el rango.');return;}
    if(desde>hasta){alertF('warning','La fecha inicial no puede ser mayor que la final.');return;}
    st.scope='range';st.desde=desde;st.hasta=hasta;st.fecha='';
  }else if(scope==='all'){
    st.scope='all';st.fecha='';st.desde='';st.hasta='';
  }else{
    st.scope='latest';st.fecha='';st.desde='';st.hasta='';
    if(qs('#egFechaFiltro'))qs('#egFechaFiltro').value='';
    if(qs('#egDesde'))qs('#egDesde').value='';
    if(qs('#egHasta'))qs('#egHasta').value='';
  }

  st.p=1;
  syncListScopeUI();
  loadList();
}

function resetListScopeToLatest(){
  if(qs('#egScope'))qs('#egScope').value='latest';
  if(qs('#egFechaFiltro'))qs('#egFechaFiltro').value='';
  if(qs('#egDesde'))qs('#egDesde').value='';
  if(qs('#egHasta'))qs('#egHasta').value='';
  st.scope='latest';
  st.fecha='';
  st.desde='';
  st.hasta='';
  st.p=1;
  syncListScopeUI();
  loadList();
}

function egListCols(){return 7;}

function egCompText(r){
  const tipo=(r.tipo_comprobante||'').toUpperCase();
  if(tipo==='RECIBO') return r.referencia||'INTERNO';
  const serie=String(r.serie||'').trim();
  const numero=String(r.numero||'').trim();
  return (serie||numero)?[serie,numero].filter(Boolean).join('-'):'-';
}

function egNl2BrEsc(v){
  return esc(String(v||'')).replace(/\r\n|\r|\n/g,'<br>');
}

function egDetailBodyId(id){return 'egDetailBody_'+String(id);}
function egDetailRowId(id){return 'egDetailRow_'+String(id);}
function egToggleId(id){return 'egToggleIcon_'+String(id);}

function detalleFuentesAgrupadasHTML(row){
  const groups=groupedFuentesByCaja((row&&row.fuentes)||[]);
  if(!groups.length){
    return '<div class="text-muted small">Sin fuentes registradas.</div>';
  }
  return groups.map(g=>{
    const rows=(g.rows||[]).map(item=>'<tr><td>'+esc(item.label||item.key||'Fuente')+'</td><td class="text-right">'+esc(money(item.monto||0))+'</td></tr>').join('');
    return '<div class="mb-2"><div class="font-weight-bold">'+esc(g.caja_diaria_codigo||('Caja '+g.id_caja_diaria))+' <span class="text-muted">('+esc(fmtDate(g.caja_diaria_fecha||''))+')</span></div><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr><th>Fuente</th><th class="text-right">Monto</th></tr></thead><tbody>'+rows+'<tr><th>Total caja</th><th class="text-right">'+esc(money(g.total||0))+'</th></tr></tbody></table></div></div>';
  }).join('');
}
function egDetailHTML(row){
  const concepto=((row&&row.concepto)||'').toString().trim()||'-';
  const obs=((row&&row.observaciones)||'').toString().trim()||'Sin observaciones internas.';
  const tipo=tipoEgresoLabel((row&&row.tipo_egreso)||'NORMAL');
  const caja=((row&&row.caja_diaria_codigo)||'-').toString();
  const fechaCaja=((row&&row.caja_diaria_fecha)||'').toString();
  return `
    <div class="eg-detail-box">
      <div class="eg-detail-grid">
        <div class="eg-detail-item">
          <span class="eg-detail-label">Tipo de egreso</span>
          <div class="eg-detail-text">${esc(tipo)}</div>
        </div>
        <div class="eg-detail-item">
          <span class="eg-detail-label">Caja de registro</span>
          <div class="eg-detail-text">${esc(caja)}${fechaCaja?' <span class="text-muted">('+esc(fmtDate(fechaCaja))+')</span>':''}</div>
        </div>
        <div class="eg-detail-item">
          <span class="eg-detail-label">Concepto</span>
          <div class="eg-detail-text">${egNl2BrEsc(concepto)}</div>
        </div>
        <div class="eg-detail-item">
          <span class="eg-detail-label">Observaciones internas</span>
          <div class="eg-detail-text">${egNl2BrEsc(obs)}</div>
        </div>
        <div class="eg-detail-item" style="grid-column:1/-1;">
          <span class="eg-detail-label">Fuentes de dinero por caja</span>
          <div class="eg-detail-text">${detalleFuentesAgrupadasHTML(row)}</div>
        </div>
      </div>
    </div>
  `;
}

async function toggleEgresoDetail(id){
  const row=qs('#'+egDetailRowId(id));
  const body=qs('#'+egDetailBodyId(id));
  const icon=qs('#'+egToggleId(id));
  if(!row||!body) return;

  const isOpen=row.classList.contains('show');
  if(isOpen){
    row.classList.remove('show');
    if(icon) icon.classList.remove('is-open');
    return;
  }

  row.classList.add('show');
  if(icon) icon.classList.add('is-open');

  const cacheKey=String(id);
  if(st.detailCache[cacheKey]){
    body.innerHTML=egDetailHTML(st.detailCache[cacheKey]);
    return;
  }

  body.innerHTML='<div class="eg-detail-box"><div class="eg-detail-placeholder">Cargando detalle del egreso...</div></div>';

  try{
    const r=await get('detalle',{id:id});
    st.detailCache[cacheKey]=r.row||{};
    body.innerHTML=egDetailHTML(st.detailCache[cacheKey]);
  }catch(err){
    body.innerHTML='<div class="eg-detail-box"><div class="text-danger small">'+esc(err.message||'No se pudo cargar el detalle del egreso.')+'</div></div>';
  }
}

function rowsHTML(rows){
  if(!rows.length){
    return '<tr><td colspan="'+egListCols()+'" class="text-muted small">No hay egresos que coincidan con el filtro.</td></tr>';
  }

  return rows.map(r=>{
    const tipo=(r.tipo_comprobante||'').toUpperCase();
    const tipoEgreso=String(r.tipo_egreso||'NORMAL').toUpperCase();
    const an=((r.estado||'').toUpperCase()==='ANULADO');
    const tBadge=tipo==='FACTURA'?'badge-info':(tipo==='BOLETA'?'badge-primary':'badge-secondary');
    const tLbl=tipo==='FACTURA'?'Factura':(tipo==='BOLETA'?'Boleta':'Recibo');
    const teBadge=tipoEgreso==='MULTICAJA'?'badge-warning':'badge-light';
    const teLbl=tipoEgreso==='MULTICAJA'?'Multicaja':'Normal';
    const comp=egCompText(r);
    const cajaTxt=r.caja_diaria_codigo?('<div class="small text-muted">Caja registro: '+esc(r.caja_diaria_codigo)+'</div>'):''; 

    return `
      <tr class="eg-row-main ${an?'eg-row-anulado':''}" data-role="eg-row-main" data-id="${r.id}">
        <td>
          <div class="d-flex align-items-start justify-content-between">
            <strong>${esc(r.codigo||'')}</strong>
            <span id="${egToggleId(r.id)}" class="eg-row-toggle text-muted ml-2"><i class="fas fa-chevron-down"></i></span>
          </div>
        </td>
        <td>
          <div class="text-nowrap">${esc(fmt(r.fecha_emision))}</div>
          ${cajaTxt}
        </td>
        <td class="eg-type-cell">
          <span class="badge ${tBadge}">${tLbl}</span>
          <span class="badge ${teBadge} ml-1">${teLbl}</span>
          <div class="eg-type-ref">${esc(comp)}</div>
        </td>
        <td>${esc(r.beneficiario||'-')}</td>
        <td class="text-right font-weight-bold">${esc(money(r.monto))}</td>
        <td><span class="badge ${an?'badge-danger':'badge-success'}">${an?'Anulado':'Activo'}</span></td>
        <td class="text-center text-nowrap" data-stop-row-toggle="1">
          <button class="btn btn-xs btn-outline-primary mr-1" data-stop-row-toggle="1" data-action="preview" data-id="${r.id}" title="Vista previa"><i class="fas fa-eye"></i></button>
          <a class="btn btn-xs btn-outline-secondary mr-1" data-stop-row-toggle="1" target="_blank" rel="noopener" href="${API}?accion=egreso_pdf&id=${r.id}" title="Imprimir PDF"><i class="fas fa-print"></i></a>
          <button class="btn btn-xs btn-outline-danger" data-stop-row-toggle="1" data-action="anular" data-id="${r.id}" ${an?'disabled':''} title="Anular"><i class="fas fa-ban"></i></button>
        </td>
      </tr>
      <tr class="eg-detail-row" id="${egDetailRowId(r.id)}">
        <td colspan="${egListCols()}" class="eg-detail-cell">
          <div id="${egDetailBodyId(r.id)}" class="eg-detail-placeholder p-3">Haz clic para ver concepto, observaciones y fuentes del egreso.</div>
        </td>
      </tr>
    `;
  }).join('');
}

function pager(tp,p){
  const ul=qs('#egPager');if(tp<=1){ul.innerHTML='';return;}
  const it=[],add=(x,l,d,a)=>it.push('<li class="page-item '+(d?'disabled':'')+' '+(a?'active':'')+'"><a href="#" class="page-link" data-page="'+x+'">'+l+'</a></li>');
  add(p-1,'«',p<=1,false);let s=Math.max(1,p-2),e=Math.min(tp,s+4);s=Math.max(1,e-4);for(let i=s;i<=e;i++)add(i,''+i,false,i===p);add(p+1,'»',p>=tp,false);ul.innerHTML=it.join('');
}
async function loadList(){
  const tb=qs('#egTableBody'),resTxt=qs('#egResumenListado'),tot=qs('#egTotalesDia');
  if(!st.schema){
    tb.innerHTML='<tr><td colspan="9" class="text-danger small">'+esc(st.schemaMsg||'No se puede listar por un error de conexion/API.')+'</td></tr>';
    if(resTxt)resTxt.textContent='';
    if(tot)tot.textContent='';
    renderListScopeInfo(null);
    pager(1,1);
    return;
  }
  tb.innerHTML='<tr><td colspan="9" class="text-muted small">Cargando...</td></tr>';
  try{
    const r=await get('listar',{page:st.p,per:st.pp,q:st.q,tipo:st.t,estado:st.e,scope:st.scope,fecha:st.fecha,desde:st.desde,hasta:st.hasta});
    const rows=Array.isArray(r.rows)?r.rows:[];
    st.context=r.context||null;
    st.totalActivos=Number(r.total_activos||0);
    tb.innerHTML=rowsHTML(rows);
    pager(r.total_pages||1,r.page||1);
    renderListScopeInfo(st.context);
    renderListCounter(Number(r.total||0),Number(r.page||1),Number(r.per||st.pp),rows.length);
    const visibleActivos=rows.reduce((a,x)=>((x.estado||'').toUpperCase()==='ANULADO')?a:a+Number(x.monto||0),0);
    if(tot){
      if((r.total||0)>0){
        tot.textContent='Total activos filtrados: '+money(st.totalActivos)+(rows.length?(' | Activos visibles: '+money(visibleActivos)):'');
      }else{
        tot.textContent='';
      }
    }
  }catch(e){
    tb.innerHTML='<tr><td colspan="9" class="text-danger small">'+esc(e.message||'Error al listar egresos.')+'</td></tr>';
    if(resTxt)resTxt.textContent='';
    if(tot)tot.textContent='';
    renderListScopeInfo(null);
  }
}

function buildVoucherComp(v){
  const tipo=(v.tipo_comprobante||v.tipo||'RECIBO').toUpperCase();
  const ref=String(v.referencia||'').trim();
  const serie=String(v.serie||'').trim();
  const numero=String(v.numero||'').trim();

  if(tipo==='RECIBO'){
    return ref || 'RECIBO INTERNO';
  }

  const doc=(serie&&numero)?(`${serie}-${numero}`):(serie||numero||'-');
  return `${tipo} ${doc}`.trim();
}

function buildVoucherEstado(v){
  return ((v.estado||'ACTIVO').toUpperCase()==='ANULADO') ? 'ANULADO' : 'EMITIDO';
}

function cleanVoucherText(v){
  return String(v||'').replace(/\s+/g,' ').trim();
}

function clipVoucherText(v,max){
  const txt=cleanVoucherText(v);
  if(!txt) return '-';
  if(txt.length<=max) return txt;
  return txt.slice(0,Math.max(0,max-3)).replace(/[ ,.;:-]+$/,'')+'...';
}

function voucherResponsable(v){
  return cleanVoucherText(v.creado_nombre||v.creado_usuario||USR||'Responsable') || 'Responsable';
}

function voucherFuentesRows(v){
  const groups=groupedFuentesByCaja((v&&v.fuentes)||[]);
  if(!groups.length){
    return `<div class="egpv-source-row"><span>Sin fuentes registradas</span><strong>S/. 0.00</strong></div>`;
  }
  return groups.map(g=>`<div class="egpv-source-row"><span>${esc(g.caja_diaria_codigo||('Caja '+g.id_caja_diaria))}</span><strong>${esc(money(g.total||0))}</strong></div>`).join('');
}

function voucherCompText(v){
  const tipo=String(v.tipo_comprobante||v.tipo||'RECIBO').toUpperCase().trim();
  const referencia=String(v.referencia||'').trim();
  const serie=String(v.serie||'').trim();
  const numero=String(v.numero||'').trim();

  if(tipo==='RECIBO'){
    return referencia || 'RECIBO INTERNO';
  }

  const doc=(serie&&numero)?(`${serie}-${numero}`):(serie||numero||'-');
  return `${tipo} ${doc}`.trim();
}

function voucherEstadoText(v){
  return ((String(v.estado||'ACTIVO').toUpperCase()==='ANULADO') ? 'ANULADO' : 'EMITIDO');
}

function voucherText(v){
  return String(v||'').replace(/\s+/g,' ').trim();
}

function voucherCompanyFontSize(name){
  const len=voucherText(name).length;
  if(len>=42) return 24;
  if(len>=34) return 26;
  if(len>=28) return 28;
  return 32;
}

function voucherAmountFontSize(amountText){
  const len=String(amountText||'').length;
  if(len>=12) return 20;
  if(len>=10) return 22;
  if(len>=8) return 25;
  return 30;
}

function voucherFuentesHtml(v){
  const groups=groupedFuentesByCaja((v&&v.fuentes)||[]);
  if(!groups.length){
    return '<div class="egpv3-source-card"><div class="egpv3-source-card-head">Sin fuentes</div><div class="egpv3-source-row"><div class="egpv3-source-name">Sin fuentes registradas</div><div class="egpv3-source-amount">S/ 0.00</div></div></div>';
  }

  return groups.map(g=>{
    const rows=(g.rows||[]).map(item=>`
      <div class="egpv3-source-row">
        <div class="egpv3-source-name">${esc(item.label||item.key||'Fuente')}</div>
        <div class="egpv3-source-amount">${esc(money(item.monto||0))}</div>
      </div>
    `).join('');

    return `
      <div class="egpv3-source-card">
        <div class="egpv3-source-card-head">${esc(g.caja_diaria_codigo||('Caja '+g.id_caja_diaria))} <span class="text-muted">(${esc(fmtDate(g.caja_diaria_fecha||''))})</span></div>
        ${rows}
        <div class="egpv3-source-row egpv3-source-total">
          <div class="egpv3-source-name">Total caja</div>
          <div class="egpv3-source-amount">${esc(money(g.total||0))}</div>
        </div>
      </div>
    `;
  }).join('');
}

function voucherHTML(v,logo){
  const empresa=voucherText(v.empresa_nombre||EMP||'EMPRESA')||'EMPRESA';
  const codigo=voucherText(v.codigo||'Sin guardar')||'Sin guardar';
  const fecha=fmt(v.fecha_emision||v.fecha||'');
  const comprobante=voucherCompText(v);
  const estado=voucherEstadoText(v);
  const tipoEgreso=tipoEgresoLabel(v.tipo_egreso||'NORMAL');
  const beneficiario=voucherText(v.beneficiario||'-')||'-';
  const documento=voucherText(v.documento||'-')||'-';
  const concepto=voucherText(v.concepto||'-')||'-';
  const responsable=voucherText(v.creado_nombre||v.creado_usuario||USR||'Responsable')||'Responsable';
  const montoValor=round2(num(v.monto||0)).toFixed(2);
  const fuentesHtml=voucherFuentesHtml(v);
  const companySize=voucherCompanyFontSize(empresa);
  const amountSize=voucherAmountFontSize(montoValor);
  const cajaRegistro=voucherText(v.caja_diaria_codigo||'-')||'-';
  const cajaRegistroFecha=(v.caja_diaria_fecha?fmtDate(v.caja_diaria_fecha):'-');

  const logoHtml=logo
    ? `<div class="egpv3-logo"><img src="${esc(logo)}" alt="Logo"></div>`
    : `<div class="egpv3-logo"><span>LOGO</span></div>`;

  return `
    <style>
      #egVoucher{ background:transparent; padding:0; }
      #egVoucher .egpv3-wrap{ width:100%; max-width:1120px; margin:0 auto; background:#f5f5f3; border:2px solid #1f1f1f; border-radius:32px; padding:22px 28px 24px; color:#111; font-family:Arial,Helvetica,sans-serif; box-sizing:border-box; }
      #egVoucher .egpv3-head{ display:grid; grid-template-columns:84px minmax(0,1fr) 220px; gap:16px; align-items:start; }
      #egVoucher .egpv3-logo{ width:80px; height:80px; background:transparent; display:flex; align-items:center; justify-content:center; overflow:hidden; }
      #egVoucher .egpv3-logo img{ width:100%; height:100%; object-fit:contain; }
      #egVoucher .egpv3-logo span{ font-size:12px; font-weight:700; }
      #egVoucher .egpv3-title{ min-width:0; text-align:center; padding-top:4px; }
      #egVoucher .egpv3-company{ font-weight:800; line-height:1.08; word-break:break-word; }
      #egVoucher .egpv3-subtitle{ margin-top:8px; font-size:13px; line-height:1.2; word-break:break-word; }
      #egVoucher .egpv3-amount{ display:grid; grid-template-columns:52px 1fr; gap:10px; align-items:center; padding-top:8px; }
      #egVoucher .egpv3-currency{ font-size:24px; font-weight:800; text-align:right; }
      #egVoucher .egpv3-amount-box{ min-height:64px; border:2px solid #1f1f1f; border-radius:14px; background:#fff; display:flex; align-items:center; justify-content:center; padding:6px 10px; }
      #egVoucher .egpv3-band{ margin-top:14px; background:#6d7782; color:#fff; border-radius:16px; padding:10px 14px; display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:10px; font-size:12px; }
      #egVoucher .egpv3-band-label{ font-weight:700; display:block; margin-bottom:2px; }
      #egVoucher .egpv3-grid2{ display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:16px; }
      #egVoucher .egpv3-block{ background:#fff; border:1px solid #d8dde3; border-radius:14px; padding:12px 14px; }
      #egVoucher .egpv3-block-label{ font-size:11px; font-weight:700; color:#4a5b6c; margin-bottom:6px; text-transform:uppercase; }
      #egVoucher .egpv3-block-text{ font-size:14px; line-height:1.45; word-break:break-word; }
      #egVoucher .egpv3-sources-wrap{ margin-top:16px; }
      #egVoucher .egpv3-sources-title{ font-size:13px; font-weight:800; color:#1f2d3d; margin-bottom:8px; text-transform:uppercase; }
      #egVoucher .egpv3-sources-grid{ display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
      #egVoucher .egpv3-source-card{ background:#fff; border:1px solid #d8dde3; border-radius:14px; padding:10px 12px; }
      #egVoucher .egpv3-source-card-head{ font-weight:800; font-size:13px; color:#1f2d3d; margin-bottom:8px; }
      #egVoucher .egpv3-source-row{ display:flex; justify-content:space-between; gap:10px; border-bottom:1px solid #eceff2; padding:6px 0; font-size:13px; }
      #egVoucher .egpv3-source-row:last-child{ border-bottom:none; }
      #egVoucher .egpv3-source-total{ font-weight:800; }
      #egVoucher .egpv3-signs{ margin-top:18px; display:grid; grid-template-columns:${String((String(v.tipo_comprobante||'RECIBO').toUpperCase()==='RECIBO')?'1fr 1fr':'1fr')}; gap:22px; }
      #egVoucher .egpv3-sign{ padding-top:26px; text-align:center; }
      #egVoucher .egpv3-sign-line{ border-top:1.5px solid #111; margin:0 auto 8px; width:82%; }
      #egVoucher .egpv3-sign-name{ font-weight:800; font-size:13px; }
      #egVoucher .egpv3-sign-role{ color:#556371; font-size:12px; margin-top:2px; }
      #egVoucher .text-muted{ color:#6c757d; }
    </style>
    <div class="egpv3-wrap">
      <div class="egpv3-head">
        ${logoHtml}
        <div class="egpv3-title">
          <div class="egpv3-company" style="font-size:${companySize}px;">${esc(empresa)}</div>
          <div class="egpv3-subtitle">RECIBO DE EGRESO · ${esc(codigo)}</div>
        </div>
        <div class="egpv3-amount">
          <div class="egpv3-currency">S/.</div>
          <div class="egpv3-amount-box"><div style="font-size:${amountSize}px;font-weight:800;">${esc(montoValor)}</div></div>
        </div>
      </div>

      <div class="egpv3-band">
        <div><span class="egpv3-band-label">Fecha y hora</span>${esc(fecha)}</div>
        <div><span class="egpv3-band-label">Estado</span>${esc(estado)}</div>
        <div><span class="egpv3-band-label">Comprobante</span>${esc(comprobante)}</div>
        <div><span class="egpv3-band-label">Tipo egreso</span>${esc(tipoEgreso)}</div>
      </div>

      <div class="egpv3-grid2">
        <div class="egpv3-block">
          <div class="egpv3-block-label">Beneficiario</div>
          <div class="egpv3-block-text">${esc(beneficiario)}</div>
        </div>
        <div class="egpv3-block">
          <div class="egpv3-block-label">Documento</div>
          <div class="egpv3-block-text">${esc(documento)}</div>
        </div>
      </div>

      <div class="egpv3-grid2">
        <div class="egpv3-block">
          <div class="egpv3-block-label">Caja de registro</div>
          <div class="egpv3-block-text">${esc(cajaRegistro)} <span class="text-muted">(${esc(cajaRegistroFecha)})</span></div>
        </div>
        <div class="egpv3-block">
          <div class="egpv3-block-label">Responsable</div>
          <div class="egpv3-block-text">${esc(responsable)}</div>
        </div>
      </div>

      <div class="egpv3-block" style="margin-top:16px;">
        <div class="egpv3-block-label">Concepto</div>
        <div class="egpv3-block-text">${esc(concepto)}</div>
      </div>

      <div class="egpv3-sources-wrap">
        <div class="egpv3-sources-title">Fuentes de dinero por caja</div>
        <div class="egpv3-sources-grid">
          ${fuentesHtml}
        </div>
      </div>

      <div class="egpv3-signs">
        ${String(v.tipo_comprobante||'RECIBO').toUpperCase()==='RECIBO' ? `
          <div class="egpv3-sign">
            <div class="egpv3-sign-line"></div>
            <div class="egpv3-sign-name">${esc(beneficiario)}</div>
            <div class="egpv3-sign-role">Beneficiario</div>
          </div>` : ''}
        <div class="egpv3-sign">
          <div class="egpv3-sign-line"></div>
          <div class="egpv3-sign-name">${esc(responsable)}</div>
          <div class="egpv3-sign-role">Responsable</div>
        </div>
      </div>
    </div>
  `;
}

function showVoucher(v,logo){
  st.prev=v||null;
  const c=qs('#egVoucher');
  if(!c||!v) return;

  c.innerHTML=voucherHTML(v,logo||'../../dist/img/AdminLTELogo.png');

  const modal=qs('#egresoPrintModal');
  if(modal){
    const dialog=qs('.modal-dialog',modal);
    if(dialog){
      dialog.classList.add('modal-xl');
      dialog.style.maxWidth='1180px';
      dialog.style.width='calc(100vw - 40px)';
    }
  }

  qs('#egBtnPrintReal').disabled=!(v.id>0);

  if(window.jQuery) window.jQuery('#egresoPrintModal').modal('show');
}

async function previewId(id){const r=await get('detalle',{id:id});showVoucher(r.row,r.row.empresa_logo_web||'../../dist/img/AdminLTELogo.png');}
function clearForm(){
  const f=qs('#egForm');
  if(!f)return;
  f.reset();
  setTipo('RECIBO');
  setNow();
  countC();
  st.fuentesAsignadas={};
  renderFuentesResumen();
  if(window.egMulticaja&&typeof window.egMulticaja.resetAll==='function'){
    window.egMulticaja.resetAll();
  }
  alertF('info','Formulario limpio. Completa los datos y registra el egreso.');
}
async function save(e){
  e.preventDefault();
  if(!st.schema){alertF('danger','No se puede registrar: falta migracion SQL de tablas egr_.');return;}
  if(!st.caja||!st.caja.puede_registrar){alertF('warning','No hay caja diaria abierta. Abre caja desde el modulo Caja.');return;}
  const d=datos();if(!validar(d))return;
  const dist=buildFuentesForSubmit(d.monto);
  if(!dist.ok){
    alertF('warning',esc(dist.msg)+'<br>Haz clic en <strong>Distribuir</strong> para corregir.');
    if(qs('#egBtnDistribuir'))qs('#egBtnDistribuir').click();
    return;
  }
  const b=qs('#egBtnGuardar');b.disabled=true;
  try{
    const r=await post('crear',{
      tipo_comprobante:d.tipo,
      tipo_egreso:dist.tipo_egreso,
      serie:d.serie,
      numero:d.numero,
      referencia:d.referencia,
      fecha_emision:d.fecha,
      monto:d.monto,
      beneficiario:d.benef,
      documento:d.doc,
      concepto:d.concepto,
      observaciones:d.obs,
      fuentes_json:JSON.stringify(dist.payload)
    });
    alertF('success',esc(r.msg||'Egreso registrado.'));
    await loadCaja();
    st.p=1;
    await loadList();
    if(r.id) await previewId(r.id);
    clearForm();
  }catch(err){
    const p=err.payload||{},sd=p.saldo,fe=p.fuente_error,ce=p.caja_error;
    if(fe){
      const k=canonKey(fe.key);
      const cajaInfo=fe.caja_codigo ? ('<br>Caja fuente: <strong>'+esc(fe.caja_codigo)+'</strong>'+(fe.caja_fecha?(' <span class="text-muted">('+esc(fmtDate(fe.caja_fecha))+')</span>'):'')) : '';
      alertF('warning',esc(err.message||'No se pudo guardar el egreso.')+'<br>Fuente: <strong>'+esc(FUENTE_LABEL[k]||k)+'</strong>'+cajaInfo+'<br>Solicitado: <strong>'+esc(money(fe.monto_solicitado))+'</strong> · Disponible: <strong>'+esc(money(fe.disponible))+'</strong>');
      await loadCaja();
      if(qs('#egBtnDistribuir'))qs('#egBtnDistribuir').click();
    } else if(ce&&ce.saldo){
      alertF('warning',esc(err.message||'No se pudo guardar el egreso.')+'<br>Caja fuente: <strong>'+esc(ce.codigo||'-')+'</strong><br>Disponible: <strong>'+esc(money((((ce||{}).saldo||{}).saldo_disponible)||0))+'</strong>');
    } else if(sd) {
      alertF('warning',esc(err.message)+'<br>Saldo disponible: <strong>'+esc(money(sd.saldo_disponible))+'</strong>');
    } else {
      alertF('danger',esc(err.message||'No se pudo guardar el egreso.'));
    }
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
    const chip=ev.target.closest('#egTipoChipGroup .eg-chip');
    if(chip){
      ev.preventDefault();
      setTipo(chip.dataset.tipo||'RECIBO');
      return;
    }

    const pg=ev.target.closest('#egPager a[data-page]');
    if(pg){
      ev.preventDefault();
      const p=parseInt(pg.dataset.page,10);
      if(!isNaN(p)&&p>=1){
        st.p=p;
        loadList();
      }
      return;
    }

    const act=ev.target.closest('[data-action][data-id]');
    if(act){
      const id=parseInt(act.dataset.id,10);
      const a=act.dataset.action;
      if(!(id>0)) return;
      if(a==='preview') previewId(id).catch(e=>alertF('danger',esc(e.message||'No se pudo abrir vista previa.')));
      if(a==='anular') anular(id);
      return;
    }

    const row=ev.target.closest('tr[data-role="eg-row-main"][data-id]');
    if(row && !ev.target.closest('[data-stop-row-toggle],a,button,input,textarea,select,label')){
      const id=parseInt(row.dataset.id,10);
      if(id>0) toggleEgresoDetail(id);
      return;
    }
  });

  document.addEventListener('input',ev=>{
    if(ev.target&&ev.target.classList&&ev.target.classList.contains('js-eg-fuente-monto')) updateFuentesModalTotals();
  });

  qs('#egForm').addEventListener('submit',save);
  qs('#egBtnLimpiar').addEventListener('click',clearForm);

  if(qs('#egBtnDistribuir')) qs('#egBtnDistribuir').addEventListener('click',()=>{
    if(!(montoObjetivo()>0)){
      alertF('warning','Ingresa primero el monto del egreso para distribuir por fuente.');
      if(qs('#egMonto')) qs('#egMonto').focus();
      return;
    }
    renderFuentesModalRows();
    if(window.jQuery) window.jQuery('#egFuentesModal').modal('show');
  });

  if(qs('#egBtnFuentesAuto')) qs('#egBtnFuentesAuto').addEventListener('click',autoDistribuirFuentes);
  if(qs('#egBtnFuentesClear')) qs('#egBtnFuentesClear').addEventListener('click',clearFuentesModalInputs);
  if(qs('#egBtnFuentesAplicar')) qs('#egBtnFuentesAplicar').addEventListener('click',applyFuentesModal);

  if(qs('#egMonto')) qs('#egMonto').addEventListener('input',()=>{
    renderFuentesResumen();
    if(hasFuentesModal()) updateFuentesModalTotals();
  });

  qs('#egBtnVistaPrevia').addEventListener('click',()=>{
    const d=datos();
    if(!d.concepto||!(d.monto>0)){
      alertF('warning','Para la vista previa, completa al menos concepto y monto.');
      return;
    }
    const built=buildFuentesForPreview(d.monto);
    if(!built.ok){
      alertF('warning',esc(built.msg));
      if(qs('#egBtnDistribuir')) qs('#egBtnDistribuir').click();
      return;
    }
    showVoucher({
      id:0,
      codigo:'SIN GUARDAR',
      tipo_comprobante:d.tipo,
      tipo_egreso:built.tipo_egreso,
      serie:d.serie,
      numero:d.numero,
      referencia:d.referencia,
      fecha_emision:d.fecha||new Date().toISOString(),
      monto:d.monto||0,
      beneficiario:d.benef||'',
      documento:d.doc||'',
      concepto:d.concepto||'',
      estado:'ACTIVO',
      empresa_nombre:EMP,
      creado_nombre:USR,
      caja_diaria_codigo:(((st.caja||{}).diaria||{}).codigo)||'-',
      caja_diaria_fecha:(((st.caja||{}).diaria||{}).fecha)||'',
      fuentes:built.payload
    },'../../dist/img/AdminLTELogo.png');
  });

  qs('#egBtnPrintReal').addEventListener('click',()=>{
    const v=st.prev;
    if(!v||!(v.id>0)){
      alertF('warning','Primero guarda el egreso para poder imprimir el PDF oficial.');
      return;
    }
    window.open(API+'?accion=egreso_pdf&id='+v.id,'_blank','noopener');
  });

  let t=null;
  qs('#egQ').addEventListener('input',()=>{
    clearTimeout(t);
    t=setTimeout(()=>{
      st.q=qs('#egQ').value||'';
      st.p=1;
      loadList();
    },250);
  });

  qs('#egFiltroTipo').addEventListener('change',()=>{
    st.t=(qs('#egFiltroTipo').value||'TODOS').toUpperCase();
    st.p=1;
    loadList();
  });

  qs('#egFiltroEstado').addEventListener('change',()=>{
    st.e=(qs('#egFiltroEstado').value||'TODOS').toUpperCase();
    st.p=1;
    loadList();
  });

  if(qs('#egScope')) qs('#egScope').addEventListener('change',syncListScopeUI);
  if(qs('#egApplyScope')) qs('#egApplyScope').addEventListener('click',applyListScopeFilters);
  if(qs('#egResetScope')) qs('#egResetScope').addEventListener('click',resetListScopeToLatest);

  qs('#egConcepto').setAttribute('maxlength','1000');
  qs('#egConcepto').addEventListener('input',countC);
}

async function init(){
  setTipo('RECIBO');
  setNow();
  countC();
  renderFuentesResumen();
  if(qs('#egScope'))qs('#egScope').value='latest';
  if(qs('#egFiltroTipo'))qs('#egFiltroTipo').value='TODOS';
  if(qs('#egFiltroEstado'))qs('#egFiltroEstado').value='TODOS';
  syncListScopeUI();
  bind();
  await loadCaja();
  await loadList();
}
init();
})();
