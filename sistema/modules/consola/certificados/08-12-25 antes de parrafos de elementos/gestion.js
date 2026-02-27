// modules/consola/certificados/gestion.js
export function init(slot, apiUrl) {
  if (slot.__pcBound) { slot.__pcRefresh?.(); return; }
  slot.__pcBound = true;

  // ---------- Helpers ----------
  const $ = sel => slot.querySelector(sel);
  const esc = s => (s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const debounce = (fn,ms)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  const show = (el,msg='')=>{ if(!el) return; const sp=el.querySelector?.('.msg'); if(sp) sp.textContent=msg; else el.textContent=msg; el.classList.remove('d-none'); };
  const hide = el => { if(!el) return; el.classList.add('d-none'); };
  async function j(url,opts={}) {
    const r = await fetch(url,{credentials:'same-origin',...opts});
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if(!r.ok||!d.ok) throw new Error(d.msg||`HTTP ${r.status}`);
    return d;
  }
  // Normalizador de URLs relativas -> absolutas respecto al proyecto (igual que otras triadas)
  const PROJECT_ROOT = (location.pathname.split('/modules/')[0] || '');
  function assetUrl(relPath){
    if(!relPath) return '';
    const clean = String(relPath).replace(/^\/+/, '');
    const base  = PROJECT_ROOT ? PROJECT_ROOT : '';
    return `${base}/${clean}`;
  }

  // === Imágenes por defecto para los previews ===
  // Coloca estos archivos en: modules/consola/certificados/img/
  const DEFAULTS = {
    fondo: 'modules/consola/certificados/img/placeholder-fondo.png',
    logo:  'modules/consola/certificados/img/placeholder-logo.png',
    firma: 'modules/consola/certificados/img/placeholder-firma.png'
  };

  // Elementos configurables que puede mostrar un certificado (por plantilla)
  const ELEMENTS = [
    {
      code: 'curso',
      label: 'Nombre del curso',
      desc: 'Nombre del curso asociado al certificado.',
      example: 'CURSO DE MANEJO DEFENSIVO'
    },
    {
      code: 'nombre_completo',
      label: 'Nombre completo',
      desc: 'Nombres y apellidos del cliente.',
      example: 'LUIGI ISRAEL VILLANUEVA PEREZ'
    },
    {
      code: 'documento',
      label: 'Documento',
      desc: 'Tipo de documento y número (ej. DNI: 12345678).',
      example: 'DNI: 12345678'
    },
    {
      code: 'categoria',
      label: 'Categoría de licencia',
      desc: 'Categoría o clase de licencia.',
      example: 'CATEGORÍA A-I'
    },
    {
      code: 'fecha_emision',
      label: 'Fecha de emisión',
      desc: 'Fecha en que se emitió el certificado.',
      example: '01/01/2025'
    },
    {
      code: 'fecha_inicio',
      label: 'Fecha de inicio',
      desc: 'Fecha de inicio del curso o vigencia.',
      example: '01/01/2025'
    },
    {
      code: 'fecha_fin',
      label: 'Fecha de fin',
      desc: 'Fecha de fin del curso o vigencia.',
      example: '31/01/2025'
    },
    {
      code: 'horas_teoricas',
      label: 'Horas teóricas',
      desc: 'Horas teóricas (si existen).',
      example: '20 HORAS TEÓRICAS'
    },
    {
      code: 'horas_practicas',
      label: 'Horas prácticas',
      desc: 'Horas prácticas (si existen).',
      example: '10 HORAS PRÁCTICAS'
    }
  ];

  // ---------- Estado ----------
  const F = { editId: 0 };                                // formulario superior
  const L = { q: '', empresa_id: '', page: 1, per_page: 5, rows: [] }; // listado izq.
  const E = { plantillaId: 0 }; // estado del panel 3 (elementos por plantilla)

  // ---------- Previews (panel creador de plantillas) ----------
  function setPrev(idBox, path){
    const el = $(idBox); if(!el) return;

    // Elegimos el placeholder según el contenedor
    let dflt = '';
    if (idBox === '#pc-fondo-prev') dflt = DEFAULTS.fondo;
    else if (idBox === '#pc-logo-prev') dflt = DEFAULTS.logo;
    else if (idBox === '#pc-firma-prev') dflt = DEFAULTS.firma;

    // Si no hay path, usamos la imagen default
    let src = path && String(path).trim();
    if (!src) src = dflt;

    // blob:/data:/http(s): -> usar tal cual; caso contrario, normalizar con assetUrl
    let url = '';
    if (/^(blob:|data:|https?:\/\/)/i.test(src)) url = src;
    else url = assetUrl(src);

    el.style.backgroundImage = url ? `url("${url}")` : 'none';
  }
  function setCap(idSpan, txt){
    const el = $(idSpan);
    if(el) el.textContent = txt || 'Sin imagen actualmente.';
  }
  function setSize(idAlert, bytes){
    const el = $(idAlert); if(!el) return;
    if(!bytes){
      el.classList.add('d-none');
      el.textContent = '';
      return;
    }
    const kb = (bytes/1024).toFixed(1);
    el.textContent = `Peso de archivo cargado: ${kb} KB`;
    el.classList.remove('d-none');
  }
  function resetImgs(){
    setPrev('#pc-fondo-prev',''); setCap('#pc-fondo-cap','Sin imagen actualmente.'); setSize('#pc-fondo-size',0);
    setPrev('#pc-logo-prev','');  setCap('#pc-logo-cap','Sin imagen actualmente.');  setSize('#pc-logo-size',0);
    setPrev('#pc-firma-prev',''); setCap('#pc-firma-cap','Sin imagen actualmente.'); setSize('#pc-firma-size',0);
    const f1 = $('#pc-fondo'); if(f1) f1.value = '';
    const f2 = $('#pc-logo');  if(f2) f2.value = '';
    const f3 = $('#pc-firma'); if(f3) f3.value = '';
  }

  // ---------- Combos (empresas) ----------
  async function cargarEmpresas(){
    try{
      const r   = await j(`${apiUrl}?action=empresas`);
      const arr = Array.isArray(r.data) ? r.data : [];
      const opts = arr.map(x=>`<option value="${x.id}">${esc(x.nombre)}</option>`).join('');
      const selForm = $('#pc-empresa'); if(selForm){ selForm.innerHTML = `<option value="">Seleccione…</option>${opts}`; }
      const selList = $('#pl-empresa'); if(selList){ selList.innerHTML = `<option value="">Todas</option>${opts}`; }
    }catch(e){
      // Dejar selects con su opción por defecto si hay error
    }
  }

  // ---------- Listado (panel 2) ----------
  function pintarTabla(rows){
    const tb = $('#pl-tbody'); if(!tb) return;
    if(!Array.isArray(rows) || !rows.length){
      tb.innerHTML = '';
      $('#pl-empty')?.classList.remove('d-none');
      return;
    }
    $('#pl-empty')?.classList.add('d-none');
    tb.innerHTML = rows.map(r=>`
      <tr data-id="${r.id}"
          data-nombre="${esc(r.nombre)}"
          data-paginas="${r.paginas}"
          data-id_empresa="${r.id_empresa}"
          data-representante="${esc(r.representante||'')}"
          data-ciudad="${esc(r.ciudad||'')}"
          data-resolucion="${esc(r.resolucion||'')}"
          data-fondo="${esc(r.fondo_path||'')}"
          data-logo="${esc(r.logo_path||'')}"
          data-firma="${esc(r.firma_path||'')}">
        <td>${r.id}</td>
        <td>${esc(r.nombre)}</td>
        <td>${esc(r.empresa)}</td>
        <td>${r.paginas}</td>
        <td>${esc(r.creado||'')}</td>
        <td class="text-nowrap">
          <!-- Ver vista previa (se mantiene igual) -->
          <button class="btn btn-sm btn-outline-secondary pl-view" title="Ver vista previa" aria-label="Ver vista previa">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                 fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
              <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>
              <path d="M8 5.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5z" fill="#fff"/>
            </svg>
          </button>

          <!-- Nuevo: botón verde para configurar elementos -->
          <button class="btn btn-sm btn-success pl-elems" title="Elementos" aria-label="Elementos">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                 fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
              <path d="M2 12.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 2.5 4h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 2 4.5z"/>
              <path d="M6 5h8v1H6V5zm0 4h8v1H6V9zm0 4h8v1H6v-1z"/>
            </svg>
          </button>

          <!-- Editar plantilla: ahora solo icono (sin texto) -->
          <button class="btn btn-sm btn-primary pl-edit" title="Editar plantilla" aria-label="Editar plantilla">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                 fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
              <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l9.5-9.5zM11.207 2.5 3 10.707V13h2.293L13.5 4.793 11.207 2.5z"/>
            </svg>
          </button>
        </td>
      </tr>
    `).join('');
  }

  function pintarPager(total){
    const ul = $('#pl-pager'); if(!ul) return;
    const pages = Math.max(1, Math.ceil(total / L.per_page));
    L.page = Math.min(L.page, pages);
    const cur = L.page;
    const items=[];
    const add=(p,l,cls='')=>items.push(`<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${l}</a></li>`);
    add(cur-1,'«', cur<=1?'disabled':'');
    let s=Math.max(1,cur-2), e=Math.min(pages,s+4); s=Math.max(1,e-4);
    for(let p=s;p<=e;p++) add(p,p,p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML=items.join('');
  }

  async function pl_cargarLista(){
    hide($('#pl-alert'));
    const qs=new URLSearchParams({action:'list', q:L.q, page:L.page, per_page:L.per_page});
    if (L.empresa_id) qs.append('empresa_id', L.empresa_id);
    try{
      const r = await j(`${apiUrl}?${qs.toString()}`);
      L.rows = r.data || [];
      pintarTabla(L.rows);
      pintarPager(r.total || 0);
    }catch(err){
      const a=$('#pl-alert');
      a.textContent = err.message || 'Error al listar';
      a.classList.remove('d-none');
    }
  }

  function pe_syncRow(tr){
    if (!tr) return;
    const check = tr.querySelector('.pe-check');
    const btn = tr.querySelector('.pe-design');
    const msg = tr.querySelector('.pe-pos-msg');
    if (!check || !btn) return;

    if (check.checked) {
      btn.classList.remove('d-none');

      const code = check.value;
      if (msg) {
        if (PV.elements && PV.elements[code]) {
          msg.textContent = '';
        } else {
          msg.textContent = 'Posición y tamaño pendientes.';
        }
      }
    } else {
      btn.classList.add('d-none');
      if (msg) msg.textContent = '';
    }
  }

  // ---------- Panel 3: Elementos de certificado ----------
      async function pe_loadForTemplate(tplId, tplNombre, tplEmpresa){
    const wrap    = $('#pe-wrap');
    const empty   = $('#pe-empty');
    const tbody   = $('#pe-tbody');
    const lblTpl  = $('#pe-plantilla');
    const lblEmp  = $('#pe-empresa');
    const alertEl = $('#pe-alert');
    const okEl    = $('#pe-ok');

    if (!wrap || !empty || !tbody) return;

    E.plantillaId = tplId;
    if (lblTpl) lblTpl.textContent = tplNombre || '—';
    if (lblEmp) lblEmp.textContent = tplEmpresa || '—';

    hide(alertEl);
    hide(okEl);
    empty.classList.add('d-none');
    wrap.classList.remove('d-none');

    // Pintar filas base (controles de ejemplo + formato)
    tbody.innerHTML = ELEMENTS.map(el => `
      <tr data-code="${esc(el.code)}">
        <td style="width:40px;">
          <input type="checkbox" class="form-check-input pe-check" value="${esc(el.code)}">
        </td>
        <td>${esc(el.label)}</td>
        <td class="text-muted small">${esc(el.desc || '')}</td>
        <td>
          <input type="text" class="form-control form-control-sm pe-example" placeholder="${esc(el.example || '')}">
          <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
            <select class="form-select form-select-sm pe-align" style="max-width:120px;">
              <option value="C">Centro</option>
              <option value="L">Izquierda</option>
              <option value="R">Derecha</option>
              <option value="J">Justificado</option>
            </select>
            <select class="form-select form-select-sm pe-font" style="max-width:150px;">
              <option value="">Fuente por defecto</option>
              <option value="helvetica">Sans (Helvetica)</option>
              <option value="times">Serif (Times)</option>
              <option value="courier">Monospace (Courier)</option>
            </select>
            <div class="form-check form-check-inline ms-1">
              <input class="form-check-input pe-bold" type="checkbox" id="pe-bold-${esc(el.code)}">
              <label class="form-check-label small" for="pe-bold-${esc(el.code)}">Negrita</label>
            </div>
          </div>
        </td>
        <td class="text-end">
          <button type="button" class="btn btn-outline-secondary btn-sm pe-design d-none">
            Posición / tamaño
          </button>
          <div class="pe-pos-msg"></div>
        </td>
      </tr>
    `).join('');

    // Prefijar ejemplo y formato desde layout o valores por defecto
    const current = PV.elements || {};
    tbody.querySelectorAll('tr').forEach(tr => {
      const check = tr.querySelector('.pe-check');
      const input = tr.querySelector('.pe-example');
      const selAlign = tr.querySelector('.pe-align');
      const selFont  = tr.querySelector('.pe-font');
      const chkBold  = tr.querySelector('.pe-bold');
      if (!check || !input || !selAlign || !selFont || !chkBold) return;

      const code = check.value;
      const fromLayout = current[code];
      const def = ELEMENTS.find(el => el.code === code);

      // Texto de ejemplo
      if (fromLayout && typeof fromLayout.texto === 'string' && fromLayout.texto !== '') {
        input.value = fromLayout.texto;
      } else if (def && def.example) {
        input.value = def.example;
      } else {
        input.value = '';
      }

      // Formato
      const align = (fromLayout && fromLayout.align) ? fromLayout.align : 'C';
      selAlign.value = align.toUpperCase();

      const font = (fromLayout && fromLayout.font) ? fromLayout.font : '';
      selFont.value = font;

      chkBold.checked = !!(fromLayout && fromLayout.bold);
    });

    // Cargar selección actual desde API (qué elementos se ven)
    try {
      const r = await j(`${apiUrl}?action=elements_get&id=${encodeURIComponent(tplId)}`);
      const active = new Set(Array.isArray(r.data) ? r.data : []);
      tbody.querySelectorAll('.pe-check').forEach(ch => {
        const tr = ch.closest('tr');
        if (active.has(ch.value)) {
          ch.checked = true;
        } else {
          ch.checked = false;
        }
        pe_syncRow(tr);
      });
    } catch (err) {
      show(alertEl, err.message || 'No se pudieron cargar los elementos.');
    }
  }
  
  // ---------- Vista previa (Div 4) ----------
  const PV = {
    row: null,
    logo:  { x:50, y:15, w:30 },  // % relativos al lienzo
    firma: { x:80, y:80, w:25 },
    // elements[code] = { x,y,w,texto,size,bold,align,font }
    elements: {},
    activeElement: ''
  };

  function pv_clamp(v,min,max){ return Math.max(min, Math.min(max, v)); }

  function pv_asset(src, kind){
    const s = (src||'').trim();
    if (!s) return assetUrl(DEFAULTS[kind]);
    if (/^(blob:|data:|https?:\/\/)/i.test(s)) return s;
    return assetUrl(s);
  }

  function pv_showLoader(on){
    const ld = $('#pv-loader'); if(!ld) return;
    ld.classList.toggle('d-none', !on);
  }

  function pv_applyStyles(){
    const Llogo = PV.logo, Lfirma = PV.firma;
    const elLogo  = $('#pv-logo');
    const elFirma = $('#pv-firma');
    if (elLogo){
      elLogo.style.left  = `${pv_clamp(Llogo.x,0,100)}%`;
      elLogo.style.top   = `${pv_clamp(Llogo.y,0,100)}%`;
      elLogo.style.width = `${pv_clamp(Llogo.w,5,90)}%`;
    }
    if (elFirma){
      elFirma.style.left  = `${pv_clamp(Lfirma.x,0,100)}%`;
      elFirma.style.top   = `${pv_clamp(Lfirma.y,0,100)}%`;
      elFirma.style.width = `${pv_clamp(Lfirma.w,5,90)}%`;
    }
  }
  
    function pv_defaultExampleFor(code){
    const el = ELEMENTS.find(e => e.code === code);
    if (el && el.example) return el.example;
    if (el && el.label) return el.label;
    return code;
  }

    function pv_renderTexts(){
    const layer = $('#pv-text-layer');
    if (!layer) return;

    layer.innerHTML = '';
    const elems = PV.elements || {};
    Object.keys(elems).forEach(code => {
      const cfg = elems[code] || {};
      const span = document.createElement('div');
      span.className = 'pv-text' + (code === PV.activeElement ? ' pv-text-active' : '');
      span.dataset.code = code;

      const x = pv_clamp(cfg.x ?? 50, 0, 100);
      const y = pv_clamp(cfg.y ?? 50, 0, 100);
      const w = pv_clamp(cfg.w ?? 40, 5, 90);

      span.style.left  = `${x}%`;
      span.style.top   = `${y}%`;
      span.style.width = `${w}%`;

      // Tamaño (porcentaje relativo sobre 10pt)
      const size = pv_clamp(cfg.size ?? 100, 50, 200); // 50–200
      const fontPt = 10 * (size / 100);
      span.style.fontSize = `${fontPt}pt`;

      // Negrita
      span.style.fontWeight = cfg.bold ? '700' : '500';

      // Alineación
      const a = (cfg.align || 'C').toUpperCase();
      let ta = 'center';
      if (a === 'L') ta = 'left';
      else if (a === 'R') ta = 'right';
      else if (a === 'J') ta = 'justify';
      span.style.textAlign = ta;

      // Fuente
      const f = (cfg.font || '').toLowerCase();
      if (f === 'times') {
        span.style.fontFamily = '"Times New Roman", Times, serif';
      } else if (f === 'courier') {
        span.style.fontFamily = '"Courier New", Courier, monospace';
      } else {
        span.style.fontFamily = 'Helvetica, Arial, sans-serif';
      }

      const texto = (cfg.texto && String(cfg.texto).trim()) || pv_defaultExampleFor(code);
      span.textContent = texto;

      layer.appendChild(span);
    });
  }
  
  function pv_reset(){
    $('#pv-wrap')?.classList.add('d-none');
    $('#pv-empty')?.classList.remove('d-none');
    pv_showLoader(false);
    PV.row = null;
    PV.elements = {};
    PV.activeElement = '';
    const layer = $('#pv-text-layer');
    if (layer) layer.innerHTML = '';
    const lbl = $('#pv-active-name');
    if (lbl) lbl.textContent = 'Ninguno';
  }

  function loadImage(src){
    return new Promise((resolve)=>{
      const img = new Image();
      img.onload  = ()=>resolve(true);
      img.onerror = ()=>resolve(false); // no rompemos la UI si falla
      img.src = src;
    });
  }

        async function pv_loadFromRow(tr){
    if(!tr) return;

    PV.row = {
      id: parseInt(tr.dataset.id,10) || 0,
      nombre: tr.dataset.nombre || '',
      empresa: tr.querySelector('td:nth-child(3)')?.textContent?.trim() || '',
      fondo: tr.dataset.fondo || '',
      logo:  tr.dataset.logo  || '',
      firma: tr.dataset.firma || ''
    };

    // defaults por cada carga (si no hay posiciones guardadas en BD)
    PV.logo  = { x:50, y:15, w:30 };
    PV.firma = { x:80, y:80, w:25 };
    PV.elements = {};
    PV.activeElement = '';

    // Intentar cargar posiciones y estilos guardados desde el API
    try {
      const rLay = await j(`${apiUrl}?action=layout_get&id=${encodeURIComponent(PV.row.id)}`);
      const d = rLay.data || {};
      if (d.logo) {
        PV.logo = {
          x: d.logo.x ?? PV.logo.x,
          y: d.logo.y ?? PV.logo.y,
          w: d.logo.w ?? PV.logo.w
        };
      }
      if (d.firma) {
        PV.firma = {
          x: d.firma.x ?? PV.firma.x,
          y: d.firma.y ?? PV.firma.y,
          w: d.firma.w ?? PV.firma.w
        };
      }
      if (d.elements && typeof d.elements === 'object') {
        Object.keys(d.elements).forEach(code => {
          const p = d.elements[code] || {};
          const size = typeof p.font_size === 'number' ? p.font_size : 100;
          PV.elements[code] = {
            x: p.x ?? 50,
            y: p.y ?? 50,
            w: p.w ?? 40,
            texto: p.texto ?? '',
            size: size || 100,
            bold: !!p.bold,
            align: p.align || 'C',
            font: p.font_family || ''
          };
        });
      }
    } catch (e) {
      // si hay error seguimos con los valores por defecto
    }

    const wrap = $('#pv-wrap'), empty = $('#pv-empty'), canvas = $('#pv-canvas');
    const elLogo  = $('#pv-logo'), elFirma = $('#pv-firma');
    if(!wrap || !canvas || !elLogo || !elFirma){ return; }

    // Mostrar contenedor
    empty.classList.add('d-none');
    wrap.classList.remove('d-none');

    // Cargar imágenes
    pv_showLoader(true);
    const fondoUrl = pv_asset(PV.row.fondo || DEFAULTS.fondo, 'fondo');
    const logoUrl  = pv_asset(PV.row.logo  || DEFAULTS.logo,  'logo');
    const firmaUrl = pv_asset(PV.row.firma || DEFAULTS.firma, 'firma');

    // Fondo como background
    canvas.style.backgroundImage = `url("${fondoUrl}")`;

    // Cargar capas
    elLogo.src  = logoUrl;
    elFirma.src = firmaUrl;

    await Promise.all([ loadImage(logoUrl), loadImage(firmaUrl) ]);
    pv_showLoader(false);

    // Metadatos
    const t = $('#pv-title'), e = $('#pv-empresa');
    if(t) t.textContent = PV.row.nombre || '—';
    if(e) e.textContent = PV.row.empresa || '—';

    // Posicionar imágenes y textos
    pv_applyStyles();
    pv_renderTexts();
    const lbl = $('#pv-active-name');
    if (lbl) lbl.textContent = 'Ninguno';

    // Botón imprimir PDF (pasa posiciones por querystring; ruta relativa al archivo actual)
    const btnPrint = $('#pv-print');
    if(btnPrint){
      btnPrint.onclick = ()=>{
        const q = new URLSearchParams({
          id: String(PV.row.id || 0),
          lx: String(PV.logo.x),  ly: String(PV.logo.y),  lw: String(PV.logo.w),
          fx: String(PV.firma.x), fy: String(PV.firma.y), fw: String(PV.firma.w)
        }).toString();
        const pdfUrl = assetUrl('modules/consola/certificados/pdf_preview.php');
        window.open(pdfUrl + '?' + q, '_blank');
      };
    }
  }

    // Controles (mover/escala) vista previa
  slot.addEventListener('click', e=>{
    const btn = e.target?.closest('.pv-move'); if(!btn) return;
    const tgt = btn.dataset.target;
    let obj = null;

    if (tgt === 'logo') {
      obj = PV.logo;
    } else if (tgt === 'firma') {
      obj = PV.firma;
    } else if (tgt === 'elem') {
      if (!PV.activeElement || !PV.elements[PV.activeElement]) return;
      obj = PV.elements[PV.activeElement];
    }

    if(!obj) return;

    const dx = parseFloat(btn.dataset.dx||'0');
    const dy = parseFloat(btn.dataset.dy||'0');
    const dw = parseFloat(btn.dataset.dw||'0');

    if(!isNaN(dx)) obj.x = pv_clamp(obj.x + dx, 0, 100);
    if(!isNaN(dy)) obj.y = pv_clamp(obj.y + dy, 0, 100);

    if (tgt === 'logo' || tgt === 'firma') {
      // Para logo/firma dw sigue siendo ancho (%)
      if(!isNaN(dw)) obj.w = pv_clamp(obj.w + dw, 5, 90);
    } else if (tgt === 'elem') {
      // Para elementos, dw ajusta tamaño de texto (font_size)
      const cur = typeof obj.size === 'number' ? obj.size : 100;
      const inc = isNaN(dw) ? 0 : dw * 5; // cada paso = +/-5%
      let ns = cur + inc;
      if (ns < 50) ns = 50;
      if (ns > 200) ns = 200;
      obj.size = ns;
    }

    pv_applyStyles();
    pv_renderTexts();
  });

    slot.addEventListener('click', e=>{
    const btn = e.target?.closest('.pe-design');
    if (!btn) return;

    const tr = btn.closest('tr');
    if (!tr) return;

    const check   = tr.querySelector('.pe-check');
    const input   = tr.querySelector('.pe-example');
    const selAlign= tr.querySelector('.pe-align');
    const selFont = tr.querySelector('.pe-font');
    const chkBold = tr.querySelector('.pe-bold');

    if (!check || !check.checked) return;

    const code = check.value;
    if (!code) return;

    const texto = input ? input.value : '';

    // Inicializar/configurar elemento activo
    if (!PV.elements[code]) {
      PV.elements[code] = {
        x: 50,
        y: 50,
        w: 40,
        texto,
        size: 100,
        bold: chkBold ? !!chkBold.checked : false,
        align: selAlign ? (selAlign.value || 'C') : 'C',
        font: selFont ? (selFont.value || '') : ''
      };
    } else {
      const el = PV.elements[code];
      el.texto = texto;
      if (selAlign) el.align = selAlign.value || 'C';
      if (selFont)  el.font  = selFont.value || '';
      if (chkBold)  el.bold  = !!chkBold.checked;
      if (typeof el.size !== 'number') el.size = 100;
    }

    PV.activeElement = code;
    const lbl = $('#pv-active-name');
    const def = ELEMENTS.find(el => el.code === code);
    if (lbl) lbl.textContent = def?.label || code;

    pv_renderTexts();
    pe_syncRow(tr); // al tener algún layout, quitamos el mensaje de pendiente si aplica

    const pvWrap = $('#pv-wrap');
    if (pvWrap) {
      pvWrap.scrollIntoView({behavior:'smooth', block:'center'});
    }
  });

  // Guardar posiciones de logo/firma en BD
    // Guardar posiciones de logo, firma y elementos en BD
  slot.addEventListener('click', async e=>{
    if (!e.target?.closest('#pv-save-layout')) return;
    if (!PV.row?.id) return;

    const btn = e.target.closest('#pv-save-layout');
    btn.disabled = true;
    try {
      const payload = {
        logo:  PV.logo,
        firma: PV.firma,
        elements: pv_collectElementsFromUI()
      };

      const fd = new FormData();
      fd.append('action', 'layout_save');
      fd.append('id', String(PV.row.id));
      fd.append('layout', JSON.stringify(payload));

      await j(apiUrl, { method:'POST', body: fd });
      alert('Posiciones guardadas.');
    } catch (err) {
      alert(err.message || 'Error al guardar posiciones.');
    } finally {
      btn.disabled = false;
    }
  });
  
      function pv_collectElementsFromUI(){
    const tbody = $('#pe-tbody');
    const result = {};
    if (!tbody) return result;

    tbody.querySelectorAll('tr').forEach(tr => {
      const check    = tr.querySelector('.pe-check');
      const input    = tr.querySelector('.pe-example');
      const selAlign = tr.querySelector('.pe-align');
      const selFont  = tr.querySelector('.pe-font');
      const chkBold  = tr.querySelector('.pe-bold');

      if (!check || !check.checked) return;
      const code = check.value;
      if (!code) return;

      const texto = (input && typeof input.value === 'string') ? input.value.trim() : '';

      const base = PV.elements[code] || {
        x:50, y:50, w:40, texto,
        size:100, bold:false, align:'C', font:''
      };

      const align = selAlign ? (selAlign.value || base.align || 'C') : (base.align || 'C');
      const font  = selFont  ? (selFont.value  || base.font  || '')  : (base.font  || '');
      const bold  = chkBold  ? !!chkBold.checked                           : !!base.bold;
      const size  = typeof base.size === 'number' ? base.size : 100;

      result[code] = {
        x: pv_clamp(base.x ?? 50, 0, 100),
        y: pv_clamp(base.y ?? 50, 0, 100),
        w: pv_clamp(base.w ?? 40, 5, 90),
        texto,
        size,
        bold,
        align,
        font
      };
    });

    return result;
  }

  // ---------- Formulario superior ----------
  function form_setCreate(){
    F.editId = 0;
    $('#pc-nombre').value = '';
    $('#pc-paginas').value = '1';
    $('#pc-representante').value = '';
    $('#pc-empresa').value = '';
    $('#pc-ciudad').value = '';
    $('#pc-resolucion').value = '';
    resetImgs();
    $('#pc-cancelar')?.classList.add('d-none');
    $('#pc-guardar')?.classList.remove('disabled');
  }

  function form_setEdit(tr){
    if(!tr) return;
    F.editId = parseInt(tr.dataset.id,10) || 0;
    $('#pc-nombre').value = tr.dataset.nombre || '';
    $('#pc-paginas').value = tr.dataset.paginas || '1';
    $('#pc-representante').value = tr.dataset.representante || '';
    $('#pc-empresa').value = tr.dataset.id_empresa || '';
    $('#pc-ciudad').value = tr.dataset.ciudad || '';
    $('#pc-resolucion').value = tr.dataset.resolucion || '';
    setPrev('#pc-fondo-prev', tr.dataset.fondo || '');
    setPrev('#pc-logo-prev',  tr.dataset.logo  || '');
    setPrev('#pc-firma-prev', tr.dataset.firma || '');
    setCap('#pc-fondo-cap', tr.dataset.fondo ? tr.dataset.fondo.split('/').pop() : 'Sin imagen actualmente.');
    setCap('#pc-logo-cap',  tr.dataset.logo  ? tr.dataset.logo.split('/').pop()   : 'Sin imagen actualmente.');
    setCap('#pc-firma-cap', tr.dataset.firma ? tr.dataset.firma.split('/').pop()  : 'Sin imagen actualmente.');
    setSize('#pc-fondo-size',0); setSize('#pc-logo-size',0); setSize('#pc-firma-size',0);
    $('#pc-cancelar')?.classList.remove('d-none');
  }

  // Previews on change (inputs file)
  slot.addEventListener('change', (e)=>{
    const t=e.target;
    if(!t || t.type!=='file') return;
    const f=t.files?.[0];
    const id=t.id;
    if(!f){
      if(id==='pc-fondo'){ setPrev('#pc-fondo-prev',''); setCap('#pc-fondo-cap','Sin imagen actualmente.'); setSize('#pc-fondo-size',0); }
      if(id==='pc-logo'){  setPrev('#pc-logo-prev','');  setCap('#pc-logo-cap','Sin imagen actualmente.');  setSize('#pc-logo-size',0); }
      if(id==='pc-firma'){ setPrev('#pc-firma-prev',''); setCap('#pc-firma-cap','Sin imagen actualmente.'); setSize('#pc-firma-size',0); }
      return;
    }
    const url=URL.createObjectURL(f);
    if(id==='pc-fondo'){ setPrev('#pc-fondo-prev',url); setCap('#pc-fondo-cap', f.name); setSize('#pc-fondo-size', f.size); }
    if(id==='pc-logo'){  setPrev('#pc-logo-prev', url); setCap('#pc-logo-cap',  f.name); setSize('#pc-logo-size',  f.size); }
    if(id==='pc-firma'){ setPrev('#pc-firma-prev',url); setCap('#pc-firma-cap', f.name); setSize('#pc-firma-size', f.size); }
    setTimeout(()=>URL.revokeObjectURL(url), 4000);
  });

  // Guardar plantilla (create/update)
  slot.addEventListener('click', async e=>{
    if(!e.target?.closest('#pc-guardar')) return;
    const btn=e.target.closest('#pc-guardar'); btn.disabled=true;
    const okEl=$('#pc-ok'), errEl=$('#pc-alert'); hide(okEl); hide(errEl);
    try{
      const fd=new FormData($('#pc-form'));
      fd.delete('fondo'); fd.delete('logo'); fd.delete('firma'); // nos aseguramos que existan una sola vez
      if($('#pc-fondo')?.files?.[0]) fd.append('fondo',$('#pc-fondo').files[0]);
      if($('#pc-logo')?.files?.[0])  fd.append('logo',$('#pc-logo').files[0]);
      if($('#pc-firma')?.files?.[0]) fd.append('firma',$('#pc-firma').files[0]);

      fd.append('action', F.editId>0 ? 'update' : 'create');
      if(F.editId>0) fd.append('id', String(F.editId));

      await j(apiUrl, {method:'POST', body:fd});
      show(okEl, F.editId>0 ? 'Plantilla actualizada.' : 'Plantilla creada.');
      form_setCreate();
      L.page=1; await pl_cargarLista();
    }catch(err){ show($('#pc-alert'), err.message || 'Error al guardar'); }
    finally{ btn.disabled=false; }
  });

  // Cancelar edición
  slot.addEventListener('click', e=>{
    if(!e.target?.closest('#pc-cancelar')) return;
    form_setCreate();
  });

  // Ver vista previa desde la tabla (panel 2 -> panel 4)
  slot.addEventListener('click', e=>{
    const btn = e.target?.closest('.pl-view'); if(!btn) return;
    const tr = btn.closest('tr'); pv_loadFromRow(tr);
  });

  // Abrir configuración de elementos (panel 3) desde la tabla y cargar vista previa
  slot.addEventListener('click', async e => {
    const btn = e.target?.closest('.pl-elems'); if (!btn) return;
    const tr = btn.closest('tr'); if (!tr) return;

    const id = parseInt(tr.dataset.id, 10) || 0;
    if (!id) return;

    const nombre  = tr.dataset.nombre || '';
    const empresa = tr.querySelector('td:nth-child(3)')?.textContent?.trim() || '';

    await pv_loadFromRow(tr);
    pe_loadForTemplate(id, nombre, empresa);
  });

  // Guardar elementos seleccionados (panel 3)
  slot.addEventListener('click', async e => {
    if (!e.target?.closest('#pe-guardar')) return;
    const btn = e.target.closest('#pe-guardar'); btn.disabled = true;

    const alertEl = $('#pe-alert');
    const okEl    = $('#pe-ok');
    hide(alertEl); hide(okEl);

    if (!E.plantillaId) {
      show(alertEl, 'No hay plantilla seleccionada.');
      btn.disabled = false;
      return;
    }

    try {
      const tbody = $('#pe-tbody');
      const codes = [];
      tbody?.querySelectorAll('.pe-check').forEach(ch => {
        if (ch.checked) codes.push(ch.value);
      });

      const fd = new FormData();
      fd.append('action', 'elements_save');
      fd.append('id', String(E.plantillaId));
      fd.append('elements', JSON.stringify(codes));

      await j(apiUrl, { method: 'POST', body: fd });
      show(okEl, 'Elementos guardados.');
    } catch (err) {
      show(alertEl, err.message || 'Error al guardar elementos.');
    } finally {
      btn.disabled = false;
    }
  });

  // Editar plantilla desde la tabla (panel 2 -> panel 1)
  slot.addEventListener('click', e=>{
    const btn=e.target?.closest('.pl-edit'); if(!btn) return;
    const tr=btn.closest('tr'); form_setEdit(tr);
    slot.scrollIntoView({behavior:'smooth', block:'start'});
  });

  // Paginación
  slot.addEventListener('click', e=>{
    const a=e.target?.closest('#pl-pager a[data-page]'); if(!a) return; e.preventDefault();
    const li=a.parentElement; if(li.classList.contains('disabled')||li.classList.contains('active')) return;
    const p=parseInt(a.dataset.page,10); if(p>0){ L.page=p; pl_cargarLista(); }
  });

  // Filtros (buscar / empresa)
  slot.addEventListener('input', debounce(e=>{
    if(e.target?.id==='pl-q'){ L.q=e.target.value||''; L.page=1; pl_cargarLista(); }
  },300));
  
    slot.addEventListener('input', e=>{
    const input = e.target;
    if (!input || !input.classList || !input.classList.contains('pe-example')) return;
    const tr = input.closest('tr');
    if (!tr) return;
    const check = tr.querySelector('.pe-check');
    if (!check) return;
    const code = check.value;
    if (!code) return;

    if (!PV.elements[code]) {
      PV.elements[code] = {
        x: 50,
        y: 50,
        w: 40,
        texto: input.value
      };
    } else {
      PV.elements[code].texto = input.value;
    }
    pv_renderTexts();
  });
  
  slot.addEventListener('change', e=>{
    const t = e.target;
    if (!t) return;

    if (t.id === 'pl-empresa') {
      L.empresa_id = t.value || '';
      L.page = 1;
      pl_cargarLista();
    }

    if (t.classList && t.classList.contains('pe-check')) {
      const tr = t.closest('tr');
      pe_syncRow(tr);
    }
  });

  // ---------- Refresh ----------
  slot.__pcRefresh = async function(){
    hide($('#pc-alert')); hide($('#pc-ok')); hide($('#pl-alert'));
    hide($('#pe-alert')); hide($('#pe-ok'));
    const peWrap  = $('#pe-wrap');
    const peEmpty = $('#pe-empty');
    if (peWrap && peEmpty){
      peWrap.classList.add('d-none');
      peEmpty.classList.remove('d-none');
    }
    await cargarEmpresas();
    form_setCreate();
    pv_reset();
    L.q=''; L.empresa_id=''; L.page=1; await pl_cargarLista();
  };

  // Primera carga
  slot.__pcRefresh();
}
