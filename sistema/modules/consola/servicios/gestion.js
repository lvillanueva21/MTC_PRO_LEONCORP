// modules/consola/servicios/gestion.js
export function init(slot, apiUrl) {
  // Si ya está enlazado, solo refrescamos (relee DOM nuevo, empresas y lista)
  if (slot.__modServiciosBound) {
    slot.__modServiciosRefresh?.();
    return;
  }
  slot.__modServiciosBound = true;

  // ---------- Helpers ----------
  const esc = s => (s ?? '').replace(/[&<>\"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
  const $  = sel => slot.querySelector(sel);
  const alertTimers = new WeakMap();
  const clearAlertTimer = (el) => {
    if (!el) return;
    const t = alertTimers.get(el);
    if (t) {
      clearTimeout(t);
      alertTimers.delete(el);
    }
  };
  const show = (el, msg='', opts={}) => {
    if (!el) return;
    const span = el.querySelector?.('.msg');
    if (span) span.textContent = msg;
    else el.textContent = msg;
    el.classList.remove('d-none');
    el.classList.add('show');
    clearAlertTimer(el);
    const ms = Object.prototype.hasOwnProperty.call(opts, 'autoHideMs') ? Number(opts.autoHideMs) : 5000;
    if (ms > 0) {
      const t = setTimeout(() => hide(el), ms);
      alertTimers.set(el, t);
    }
  };
  const hide = el => {
    if (!el) return;
    clearAlertTimer(el);
    el.classList.add('d-none');
    el.classList.remove('show');
  };
  const debounce = (fn,ms) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  async function j(url, opts={}) {
    const r = await fetch(url, { credentials:'same-origin', ...opts });
    const d = await r.json().catch(()=>({ok:false,msg:'Respuesta inválida'}));
    if (!r.ok || !d.ok) throw new Error(d.msg || `HTTP ${r.status}`);
    return d;
  }

  // ---------- Getters dinámicos (no guardamos nodos viejos) ----------
  const boxEl    = () => $('#s-tags');
  const inputEl  = () => $('#s-tags-input');
  const hiddenEl = () => $('#s-etiquetas');
  const formEl   = () => $('#srv-create');
  const currentImageHiddenEl = () => $('#s-imagen-actual');
  const uploadWrapEl = () => $('#s-upload-wrap');
  const uploadBarEl = () => $('#s-upload-bar');
  const uploadPctEl = () => $('#s-upload-pct');
  const uploadLabelEl = () => $('#s-upload-label');
  const uploadNoteEl = () => $('#s-upload-note');
  const imageInputEl = () => $('#s-imagen');
  const cancelEditBtnEl = () => $('#s-cancel-edit');
  const confirmModalEl = () => $('#srv-confirm-modal');
  const confirmTitleEl = () => $('#srv-confirm-title');
  const confirmMessageEl = () => $('#srv-confirm-message');
  const confirmOkBtnEl = () => $('#srv-confirm-ok');
  const confirmCancelBtnEl = () => $('#srv-confirm-cancel');
  const MAX_IMAGE_SIZE = 5 * 1024 * 1024;
  const ALLOWED_IMAGE_TYPES = [
    'image/jpeg', 'image/pjpeg',
    'image/png', 'image/x-png',
    'image/webp',
    'image/gif',
    'image/bmp', 'image/x-ms-bmp',
    'image/avif'
  ];
  let uploadHideTimer = 0;

  function clearUploadHideTimer() {
    if (!uploadHideTimer) return;
    clearTimeout(uploadHideTimer);
    uploadHideTimer = 0;
  }

  function scheduleHideUploadProgress(ms = 1800) {
    clearUploadHideTimer();
    uploadHideTimer = setTimeout(() => {
      hideUploadProgress();
    }, Math.max(0, Number(ms) || 0));
  }

  function hideUploadProgress() {
    clearUploadHideTimer();
    const wrap = uploadWrapEl();
    const bar = uploadBarEl();
    const pct = uploadPctEl();
    const label = uploadLabelEl();
    const note = uploadNoteEl();
    if (bar) {
      bar.style.width = '0%';
      bar.setAttribute('aria-valuenow', '0');
      bar.classList.add('progress-bar-striped', 'progress-bar-animated');
      bar.classList.remove('bg-success', 'bg-danger');
    }
    if (pct) pct.textContent = '0%';
    if (label) label.textContent = 'Subiendo imagen...';
    if (note) note.textContent = 'No cierres esta pantalla mientras se sube el archivo.';
    wrap?.classList.add('d-none');
  }

  function startUploadProgress(file) {
    clearUploadHideTimer();
    const wrap = uploadWrapEl();
    const bar = uploadBarEl();
    const pct = uploadPctEl();
    const label = uploadLabelEl();
    const note = uploadNoteEl();
    wrap?.classList.remove('d-none');
    if (bar) {
      bar.style.width = '0%';
      bar.setAttribute('aria-valuenow', '0');
      bar.classList.add('progress-bar-striped', 'progress-bar-animated');
      bar.classList.remove('bg-success', 'bg-danger');
    }
    if (pct) pct.textContent = '0%';
    if (label) label.textContent = 'Subiendo imagen...';
    if (note) note.textContent = file ? `${file.name} (${formatBytes(file.size)})` : 'Procesando archivo...';
  }

  function updateUploadProgress(percent) {
    const p = Math.max(0, Math.min(100, Number(percent) || 0));
    const bar = uploadBarEl();
    if (bar) {
      bar.style.width = `${p}%`;
      bar.setAttribute('aria-valuenow', String(p));
    }
    const pct = uploadPctEl();
    if (pct) pct.textContent = `${p}%`;
  }

  function finishUploadProgress(ok, noteMsg) {
    clearUploadHideTimer();
    const bar = uploadBarEl();
    const label = uploadLabelEl();
    const note = uploadNoteEl();
    if (bar) {
      bar.classList.remove('progress-bar-animated');
      bar.classList.remove('bg-success', 'bg-danger');
      bar.classList.add(ok ? 'bg-success' : 'bg-danger');
    }
    if (label) label.textContent = ok ? 'Imagen subida correctamente.' : 'No se pudo subir la imagen.';
    if (note && noteMsg) note.textContent = noteMsg;
  }

  function stripHtmlForConfirm(text) {
    return String(text || '')
      .replace(/<[^>]*>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  async function openActionConfirm(opts = {}) {
    const title = String(opts.title || 'Confirmar accion');
    const messageHtml = String(opts.messageHtml || '');
    const messagePlain = String(opts.message || stripHtmlForConfirm(messageHtml) || 'Confirma esta accion.');
    const confirmText = String(opts.confirmText || 'Confirmar');
    const confirmClass = String(opts.confirmClass || 'btn-primary');

    const modal = confirmModalEl();
    const okBtn = confirmOkBtnEl();
    const cancelBtn = confirmCancelBtnEl();

    if (!modal || !(window.jQuery && jQuery.fn && jQuery.fn.modal)) {
      return window.confirm(`${title}\n\n${messagePlain}`);
    }

    const titleEl = confirmTitleEl();
    const messageEl = confirmMessageEl();
    if (titleEl) titleEl.textContent = title;
    if (messageEl) messageEl.innerHTML = messageHtml;

    if (okBtn) {
      okBtn.textContent = confirmText;
      okBtn.className = 'btn btn-sm';
      okBtn.classList.add(confirmClass);
    }

    return new Promise((resolve) => {
      const $modal = jQuery(modal);
      let settled = false;

      const settle = (value) => {
        if (settled) return;
        settled = true;
        cleanup();
        resolve(value);
      };

      const onOk = (ev) => {
        ev.preventDefault();
        settle(true);
        $modal.modal('hide');
      };

      const onCancel = (ev) => {
        ev?.preventDefault?.();
        settle(false);
        $modal.modal('hide');
      };

      const onHidden = () => {
        settle(false);
      };

      const cleanup = () => {
        okBtn?.removeEventListener('click', onOk);
        cancelBtn?.removeEventListener('click', onCancel);
        $modal.off('hidden.bs.modal.srvConfirm', onHidden);
      };

      okBtn?.addEventListener('click', onOk);
      cancelBtn?.addEventListener('click', onCancel);
      $modal.off('hidden.bs.modal.srvConfirm');
      $modal.on('hidden.bs.modal.srvConfirm', onHidden);
      $modal.modal('show');
    });
  }

  function mapFriendlyUploadError(msg) {
    const text = (msg || '').trim();
    const low = text.toLowerCase();
    if (!text) return 'No se pudo subir la imagen. Intenta nuevamente.';
    if (low.indexOf('5mb') >= 0 || low.indexOf('excede') >= 0) {
      return 'No se pudo subir: la imagen supera 5MB.';
    }
    if (low.indexOf('formato no permitido') >= 0) {
      return 'No se pudo subir: formato no permitido. Usa JPG, PNG, WebP, GIF, BMP o AVIF.';
    }
    if (low.indexOf('incompleta') >= 0) {
      return 'La subida quedo incompleta. Reintenta con una conexion estable.';
    }
    if (low.indexOf('respuesta no valida') >= 0 || low.indexOf('http') >= 0) {
      return 'No se pudo confirmar la subida con el servidor.';
    }
    return text;
  }

  function validateImageBeforeUpload(file) {
    if (!file) return '';
    if (file.size > MAX_IMAGE_SIZE) return 'La imagen supera 5MB. Elige un archivo mas liviano.';
    const mime = (file.type || '').toLowerCase();
    if (mime && ALLOWED_IMAGE_TYPES.indexOf(mime) < 0) {
      return 'Formato no permitido. Usa JPG, PNG, WebP, GIF, BMP o AVIF.';
    }
    return '';
  }

  function postFormWithProgress(url, formData, onProgress) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', url, true);
      xhr.withCredentials = true;
      xhr.responseType = 'json';

      xhr.upload.onprogress = (ev) => {
        if (!ev.lengthComputable || typeof onProgress !== 'function') return;
        const p = Math.round((ev.loaded / ev.total) * 100);
        onProgress(p, ev.loaded, ev.total);
      };

      xhr.onerror = () => reject(new Error('No se pudo conectar con el servidor.'));
      xhr.onabort = () => reject(new Error('La subida fue cancelada.'));
      xhr.onload = () => {
        let data = xhr.response;
        if (!data || typeof data !== 'object') {
          try {
            data = JSON.parse(xhr.responseText || '{}');
          } catch (_) {
            data = { ok: false, msg: 'Respuesta no valida' };
          }
        }
        if (xhr.status < 200 || xhr.status >= 300 || !data.ok) {
          reject(new Error(data.msg || `HTTP ${xhr.status}`));
          return;
        }
        resolve(data);
      };

      xhr.send(formData);
    });
  }

  // ---------- Preview de imagen ----------
  let newImageObjectUrl = '';
  const baseApiUrl = (() => {
    try { return new URL(apiUrl, window.location.href); }
    catch (_) { return null; }
  })();

  function appBaseUrl() {
    if (!baseApiUrl) return '';
    const idx = baseApiUrl.pathname.indexOf('/modules/');
    const basePath = idx >= 0 ? baseApiUrl.pathname.slice(0, idx) : '';
    return `${baseApiUrl.origin}${basePath}`;
  }

  function toPublicImageUrl(path, cacheBuster = '') {
    const p = (path || '').trim();
    if (!p) return '';
    if (/^https?:\/\//i.test(p)) {
      if (!cacheBuster) return p;
      const sepAbs = p.indexOf('?') >= 0 ? '&' : '?';
      return `${p}${sepAbs}_v=${encodeURIComponent(cacheBuster)}`;
    }
    const clean = p.replace(/^\/+/, '');
    const base = appBaseUrl();
    const sep = clean.indexOf('?') >= 0 ? '&' : '?';
    const v = cacheBuster ? `${sep}_v=${encodeURIComponent(cacheBuster)}` : '';
    if (!base) return `/${clean}${v}`;
    return `${base}/${clean}${v}`;
  }

  function formatBytes(bytes) {
    const b = Number(bytes) || 0;
    if (b < 1024) return `${b} B`;
    if (b < (1024 * 1024)) return `${(b / 1024).toFixed(1)} KB`;
    return `${(b / (1024 * 1024)).toFixed(1)} MB`;
  }

  function revokeNewImageObjectUrl() {
    if (!newImageObjectUrl) return;
    URL.revokeObjectURL(newImageObjectUrl);
    newImageObjectUrl = '';
  }

  function setPreviewState(imgEl, emptyEl, src, altText) {
    if (!imgEl || !emptyEl) return;
    if (src) {
      imgEl.src = src;
      imgEl.alt = altText || '';
      imgEl.style.display = 'block';
      emptyEl.classList.add('d-none');
      return;
    }
    imgEl.removeAttribute('src');
    imgEl.style.display = 'none';
    emptyEl.classList.remove('d-none');
  }

  function setCurrentImagePreview(path, cacheBuster = '') {
    const cleanPath = (path || '').trim();
    const imgEl = $('#s-prev-current-img');
    const emptyEl = $('#s-prev-current-empty');
    const metaEl = $('#s-prev-current-meta');
    const hidden = currentImageHiddenEl();

    if (hidden) hidden.value = cleanPath;
    if (cleanPath) {
      const v = cacheBuster || String(Date.now());
      setPreviewState(imgEl, emptyEl, toPublicImageUrl(cleanPath, v), 'Imagen actual');
      if (metaEl) metaEl.textContent = cleanPath.split('/').pop() || cleanPath;
      return;
    }

    setPreviewState(imgEl, emptyEl, '', '');
    if (metaEl) metaEl.textContent = '';
  }

  function setNewImagePreviewFromFile(file) {
    const imgEl = $('#s-prev-new-img');
    const emptyEl = $('#s-prev-new-empty');
    const metaEl = $('#s-prev-new-meta');
    const clearBtn = $('#s-clear-image');

    revokeNewImageObjectUrl();

    if (file) {
      newImageObjectUrl = URL.createObjectURL(file);
      setPreviewState(imgEl, emptyEl, newImageObjectUrl, 'Nueva imagen');
      if (metaEl) metaEl.textContent = `${file.name} (${formatBytes(file.size)})`;
      clearBtn?.classList.remove('d-none');
      return;
    }

    setPreviewState(imgEl, emptyEl, '', '');
    if (metaEl) metaEl.textContent = 'Selecciona un archivo para previsualizar.';
    clearBtn?.classList.add('d-none');
  }

  function clearSelectedImage() {
    const fileInput = $('#s-imagen');
    if (fileInput) fileInput.value = '';
    setNewImagePreviewFromFile(null);
    hideUploadProgress();
  }

  // ---------- Chips ----------
  const readTags = () => {
    const box = boxEl();
    if (box?.dataset.tags) { try { const a = JSON.parse(box.dataset.tags); return Array.isArray(a)?a:[]; } catch { return []; } }
    const arr=[]; box?.querySelectorAll('.chip .txt')?.forEach(n=>arr.push(n.textContent||'')); return arr;
  };
  const renderChips = (arr) => {
    const box = boxEl(); const input = inputEl(); if (!box || !input) return;
    box.querySelectorAll('.chip').forEach(c => c.remove());
    arr.forEach((t,i) => {
      const chip = document.createElement('span');
      chip.className = 'chip';
      chip.innerHTML = `<span class="txt">${esc(t)}</span><button type="button" class="x" data-i="${i}" aria-label="Quitar">×</button>`;
      box.insertBefore(chip, input);
    });
  };
  const writeTags = (arr) => {
    const box = boxEl(); if (box) box.dataset.tags = JSON.stringify(arr);
    renderChips(arr);
    const hidden = hiddenEl(); if (hidden) hidden.value = arr.join(',');
  };
  const norm   = t => (t||'').replace(/\s+/g,' ').trim().slice(0,50);
  const addTag = (text) => { const t = norm(text); if (!t) return; const tags = readTags(); if (!tags.includes(t)) { tags.push(t); writeTags(tags); } };
  const removeTag = (idx) => { const tags = readTags(); if (idx>=0 && idx<tags.length) { tags.splice(idx,1); writeTags(tags); } };

  // foco fácil en área de chips
  slot.addEventListener('mousedown', (e) => {
    const area = e.target?.closest('#s-tags'); if (!area) return;
    if (!e.target.closest('#s-tags-input')) { e.preventDefault(); inputEl()?.focus(); }
  });

  // evitar enter nativo (menos en chips)
  slot.addEventListener('keydown', (e) => {
    if (e.target && e.target.closest('#srv-create') && e.key === 'Enter') {
      if (!e.target.closest('#s-tags-input')) e.preventDefault();
    }
  });

  // coma/enter/backspace chips
  slot.addEventListener('keydown', (e) => {
    if (!e.target?.closest || !e.target.closest('#s-tags-input')) return;
    const input = inputEl(); if (!input) return;
    if (e.key === ',' || e.key === 'Enter') { e.preventDefault(); addTag(input.value); input.value=''; }
    else if (e.key === 'Backspace' && input.value==='') { const tags=readTags(); if (tags.length){ tags.pop(); writeTags(tags);} }
  });

  // pegar con comas
  slot.addEventListener('input', (e) => {
    if (!e.target?.closest || !e.target.closest('#s-tags-input')) return;
    const input = inputEl(); if (!input) return;
    const v = input.value || '';
    if (v.includes(',')) {
      const parts = v.split(/[,，、،]+/);
      for (let i=0; i<parts.length-1; i++) addTag(parts[i]);
      input.value = norm(parts.at(-1));
    }
    writeTags(readTags());
  });

  // quitar chip
  slot.addEventListener('click', (e) => {
    const btn = e.target?.closest('.chip .x'); if (!btn) return;
    const i = parseInt(btn.dataset.i,10); if (Number.isNaN(i)) return;
    removeTag(i); inputEl()?.focus();
  });

  // Preview de nueva imagen seleccionada
  slot.addEventListener('change', (e) => {
    if (e.target?.id !== 's-imagen') return;
    const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
    if (!file) {
      setNewImagePreviewFromFile(null);
      hideUploadProgress();
      return;
    }
    const invalid = validateImageBeforeUpload(file);
    if (invalid) {
      show($('#s-alert'), invalid);
      clearSelectedImage();
      return;
    }
    setNewImagePreviewFromFile(file);
    hideUploadProgress();
  });

  // Limpiar imagen seleccionada antes de guardar
  slot.addEventListener('click', (e) => {
    const btn = e.target?.closest('#s-clear-image');
    if (!btn) return;
    clearSelectedImage();
    $('#s-imagen')?.focus();
  });

  // Salir de modo edición para volver a crear servicios nuevos
  slot.addEventListener('click', (e) => {
    const btn = e.target?.closest('#s-cancel-edit');
    if (!btn) return;
    hide($('#s-alert')); hide($('#s-ok'));
    leaveEditMode({ clearForm: true, focusName: true });
  });

  // crear servicio
  slot.addEventListener('click', async (e) => {
    const btn = e.target?.closest('#s-crear'); if (!btn) return;

    const form  = formEl();
    const okEl  = $('#s-ok');
    const errEl = $('#s-alert');
    const fileInput = imageInputEl();
    const file = fileInput?.files && fileInput.files[0] ? fileInput.files[0] : null;

    hide(errEl); hide(okEl); hide($('#l-alert')); hide($('#e-alert'));

    const nombre = ($('#s-nombre')?.value || '').trim();
    if (!nombre) { show(errEl, 'El nombre es obligatorio'); return; }
    const invalidImage = validateImageBeforeUpload(file);
    if (invalidImage) { show(errEl, invalidImage); return; }

    writeTags(readTags()); // asegura CSV
    
    const fd = new FormData(form);
if (L.editId > 0) {
  fd.append('action','update');
  fd.append('id', String(L.editId));
} else {
  fd.append('action','create');
    }

    btn.disabled = true;
    btn.textContent = (L.editId > 0 ? 'Guardando...' : 'Creando...');
    if (file) startUploadProgress(file);
    else hideUploadProgress();
    try{
      const j = await postFormWithProgress(apiUrl, fd, (percent) => {
        if (!file) return;
        updateUploadProgress(percent);
      });
      const esUpdate = L.editId > 0;
const sid = (j && j.id) ? j.id : L.editId;   // por si el backend no devuelve id en algún caso
if (file) {
  updateUploadProgress(100);
  finishUploadProgress(true, 'Archivo subido y guardado correctamente.');
  scheduleHideUploadProgress(1800);
}
const msgOk = `Servicio "${nombre}" ${esUpdate ? 'actualizado' : 'creado'} con éxito.${file ? ' Imagen subida correctamente.' : ''} Id: ${sid}.`;
show(okEl, msgOk);
      // reset UI y estado
form.reset?.();
writeTags([]); const inp = inputEl(); if (inp) inp.value='';
setCurrentImagePreview('');
setNewImagePreviewFromFile(null);
if (!file) hideUploadProgress();
L.page = 1;            // si quieres mantener página, quita esta línea
L.openId = 0;

// Si estábamos editando, salir de modo edición
if (L.editId > 0) {
  leaveEditMode();
}

// refrescar la lista
await cargarLista();
      // refresca lista
      L.page = 1; await cargarLista();
    }catch(err){
      const friendly = mapFriendlyUploadError(err?.message || '');
      if (file) finishUploadProgress(false, friendly);
      show(errEl, friendly || 'Error al guardar el servicio');
    }
    finally{
      btn.disabled = false;
      setEditModeUi(L.editId > 0);
    }
  });

  // cerrar alertas manualmente con X
  slot.addEventListener('click', (e) => {
    const x = e.target?.closest('.srv-alert-close, .s-ok-close');
    if (!x) return;
    hide(x.closest('.alert'));
  });

  // ---------- Listado (DIV 3) ----------
  const L = { empresa:0, q:'', estado:'', page:1, per_page:5, openId:0, rows:[], editId:0 };

  function setEditModeUi(isEdit) {
    const btnSave = slot.querySelector('#s-crear');
    const btnCancel = cancelEditBtnEl();
    if (btnSave) btnSave.textContent = isEdit ? 'Guardar cambios' : 'Crear';
    btnCancel?.classList.toggle('d-none', !isEdit);
  }

  function resetCreateForm() {
    formEl()?.reset?.();
    writeTags([]);
    const inp = inputEl();
    if (inp) inp.value = '';
    setCurrentImagePreview('');
    setNewImagePreviewFromFile(null);
    hideUploadProgress();
  }

  function leaveEditMode({ clearForm = false, focusName = false } = {}) {
    L.editId = 0;
    setEditModeUi(false);
    if (clearForm) resetCreateForm();
    if (focusName) slot.querySelector('#s-nombre')?.focus();
  }

  async function cargarEmpresas(){
    try{
      const res = await j(`${apiUrl}?action=empresas`);
      const sel = $('#f-empresa');
      if (sel) {
        sel.innerHTML = `<option value="0">Todas las empresas</option>` +
          res.data.map(e=>`<option value="${e.id}">${esc(e.nombre)}</option>`).join('');
      }
    }catch(_){}
  }

  function pintarTabla(rows){
  const tb = $('#l-tbody');
  const isOpen = id => L.openId === id;
  const thumbHtml = (r) => {
    const path = (r && r.imagen_path) ? String(r.imagen_path).trim() : '';
    if (!path) return `<span class="srv-thumb srv-thumb-empty" title="Sin imagen">SIN</span>`;
    const v = (r && r.actualizado) ? String(r.actualizado) : String(r.id || Date.now());
    const src = toPublicImageUrl(path, v);
    return `<img class="srv-thumb" src="${esc(src)}" alt="Imagen de ${esc(r.nombre || 'servicio')}" loading="lazy">`;
  };

  const rowMain = (r) => `
    <tr class="srv-row" data-id="${r.id}">
      <td>${r.id}</td>
<td class="name-cell">
  <div class="name-main">
    ${thumbHtml(r)}
    <span class="name-text">${esc(r.nombre)}</span>
  </div>
  <span class="status-dot ${r.activo?'status-on':'status-off'}"></span>
</td>
      <td class="actions-cell">
<div class="actions-inline">
  <button class="btn btn-sm btn-primary l-edit" data-id="${r.id}">Editar</button>
  <button class="btn btn-sm btn-warning l-desact" data-id="${r.id}">
    ${r.activo ? 'Desactivar' : 'Activar'}
  </button>
  <button class="btn btn-sm btn-success l-empresas" data-id="${r.id}" data-nombre="${esc(r.nombre)}">
    Empresas
  </button>
</div>
      </td>
    </tr>`;

  const rowDetail = (r) => `
    <tr class="srv-detail ${isOpen(r.id)?'':'d-none'}" data-for="${r.id}">
      <td></td>
      <td colspan="2">
        <div><strong>Descripción:</strong> ${esc(r.descripcion || '—')}</div>
        <div class="mt-2 chips">
          ${(r.tags||[]).map(t=>`<span class="chip soft">${esc(t)}</span>`).join('') || '<span class="text-muted">Sin etiquetas</span>'}
        </div>
      </td>
    </tr>`;

  tb.innerHTML = rows.map(r => rowMain(r) + rowDetail(r)).join('');
}

  function pintarPager(total){
    const ul = $('#l-pager');
    const pages = Math.max(1, Math.ceil(total / L.per_page));
    L.page = Math.min(L.page, pages);
    const cur = L.page;
    const items = [];
    const add = (p, label, cls='') => items.push(
      `<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`
    );
    add(cur-1,'«', cur<=1?'disabled':'');
    let start = Math.max(1, cur-2), end = Math.min(pages, start+4); start = Math.max(1, end-4);
    for (let p=start; p<=end; p++) add(p, p, p===cur?'active':'');
    add(cur+1,'»', cur>=pages?'disabled':'');
    ul.innerHTML = items.join('');
  }

  async function cargarLista(){
    hide($('#l-alert'));
    const qs = new URLSearchParams({
      action:'list',
      empresa: L.empresa || 0,
      q: L.q,
      estado: L.estado,
      page: L.page,
      per_page: L.per_page
    });
    try{
      const res = await j(`${apiUrl}?${qs.toString()}`);
      L.rows = res.data || [];
      if (!L.rows.some(r => r.id === L.openId)) L.openId = 0;
      pintarTabla(L.rows); pintarPager(res.total);
    }catch(err){
      show($('#l-alert'), err.message || 'Error al listar');
    }
  }

  // filtros
  slot.addEventListener('change', (e)=>{
    if (e.target?.id === 'f-empresa') { L.empresa = parseInt(e.target.value,10)||0; L.page=1; cargarLista(); }
    if (e.target?.id === 'f-estado')  { L.estado  = e.target.value; L.page=1; cargarLista(); }
  });
  slot.addEventListener('input', debounce((e)=>{
    if (e.target?.id === 'f-q') { L.q = e.target.value; L.page=1; cargarLista(); }
  }, 300));

  // pager
  slot.addEventListener('click', (e)=>{
    const a = e.target?.closest('#l-pager a[data-page]'); if (!a) return;
    e.preventDefault();
    const li = a.parentElement;
    if (li.classList.contains('disabled') || li.classList.contains('active')) return;
    const p = parseInt(a.dataset.page,10); if (p>0) { L.page=p; cargarLista(); }
  });
  
  // Click en Editar: cargar datos al DIV SUPERIOR
slot.addEventListener('click', (e)=>{
  const btn = e.target?.closest('.l-edit'); if (!btn) return;
  const id = parseInt(btn.dataset.id, 10); if (!id) return;

  // Buscar el registro en la página actual
  const row = (L.rows || []).find(r => r.id === id);
  if (!row) return;

  // Poblar campos
  const nombre = slot.querySelector('#s-nombre');
  const desc   = slot.querySelector('#s-desc');
  const file   = slot.querySelector('#s-imagen');
  if (nombre) nombre.value = row.nombre || '';
  if (desc)   desc.value   = row.descripcion || '';
  if (file)   file.value   = '';            // limpiar input file
  writeTags(Array.isArray(row.tags) ? row.tags : []);
  setCurrentImagePreview(row.imagen_path || '');
  setNewImagePreviewFromFile(null);

  // Marcar modo edición
  L.editId = id;
  setEditModeUi(true);

  // Llevar foco al nombre
  nombre?.focus();
  // Opcional: abrir el detalle de esa fila
  L.openId = id; if (Array.isArray(L.rows)) pintarTabla(L.rows);
});

  // toggle detalle (click en fila, no en botones)
  slot.addEventListener('click', (e)=>{
    const tr = e.target?.closest('tr.srv-row');
    if (!tr || !tr.closest('#srv-lista')) return;
    if (e.target.closest('.actions-inline') || e.target.closest('button')) return;
    const id = parseInt(tr.dataset.id,10);
    L.openId = (L.openId === id) ? 0 : id;
    if (Array.isArray(L.rows)) pintarTabla(L.rows);
  });
  
// Activar / Desactivar (toggle)
slot.addEventListener('click', async (e) => {
  const btn = e.target?.closest('.l-desact');
  if (!btn) return;

  const id = parseInt(btn.dataset.id, 10);
  if (!id) return;

  const row = (L.rows || []).find(r => r.id === id);
  if (!row) return;

  const nuevo = row.activo ? 0 : 1;                 // 0 = desactivar, 1 = activar
  const accionTxt = nuevo ? 'Activar' : 'Desactivar';
  if (!confirm(`¿${accionTxt} el servicio "${row.nombre}"?`)) return;

  btn.disabled = true;
  try {
    const fd = new FormData();
    fd.append('action', 'set_activo');
    fd.append('id', String(id));
    fd.append('activo', String(nuevo));

    const res = await fetch(apiUrl, { method:'POST', body: fd, credentials:'same-origin' });
    const j = await res.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
    if (!res.ok || !j.ok) throw new Error(j.msg || `HTTP ${res.status}`);

    show(slot.querySelector('#s-ok'), `Servicio "${row.nombre}" ${nuevo ? 'activado' : 'desactivado'} con éxito.`);

    await cargarLista();
  } catch (err) {
    show(slot.querySelector('#l-alert'), err.message || 'Error al actualizar estado');
  } finally {
    btn.disabled = false;
  }
});

// Abrir panel izquierdo desde el botón "Empresas"
slot.addEventListener('click', async (e)=>{
  const btn = e.target?.closest('.l-empresas'); if (!btn) return;
  const id  = parseInt(btn.dataset.id, 10); if (!id) return;
  E.servicioId = id;
  E.servicioNombre = btn.dataset.nombre || '';
  E.page = 1; E.estado = ''; E.empresa_id = 0; E.q = '';
  const eQ = slot.querySelector('#e-q'); if (eQ) eQ.value = '';
  await e_cargarSelectEmpresas();
  await e_cargarLista();
});

// Filtros izquierdo
slot.addEventListener('change', (e)=>{
  if (e.target?.id === 'e-empresa') { E.empresa_id = parseInt(e.target.value,10) || 0; E.page=1; e_cargarLista(); }
  if (e.target?.id === 'e-estado')  { E.estado     = e.target.value; E.page=1; e_cargarLista(); }
});
slot.addEventListener('input', debounce((e)=>{
  if (e.target?.id === 'e-q') { E.q = (e.target.value || '').trim(); E.page=1; e_cargarLista(); }
}, 300));

// Paginación izquierdo
slot.addEventListener('click', (e)=>{
  const a = e.target?.closest('#e-pager a[data-page]'); if (!a) return;
  e.preventDefault();
  const li = a.parentElement;
  if (li.classList.contains('disabled') || li.classList.contains('active')) return;
  const p = parseInt(a.dataset.page,10); if (p>0) { E.page=p; e_cargarLista(); }
});

// Asignar / Quitar empresa <-> servicio
slot.addEventListener('click', async (e)=>{
  const btn = e.target?.closest('.e-toggle'); if (!btn) return;
  if (!E.servicioId) return;

  const empresa_id = parseInt(btn.dataset.empresa, 10);
  const asignado   = parseInt(btn.dataset.asignado, 10) === 1;
  const assign     = asignado ? 0 : 1; // 1 asignar, 0 quitar
  const nombreSrv  = E.servicioNombre || 'Servicio';
  const nombreEmp  = (btn.dataset.empresaNombre || '').trim() || 'esta empresa';

  if (!empresa_id) return;

  const accionTxt = assign ? 'Asignar' : 'Quitar';
  const confirmado = await openActionConfirm({
    title: `${accionTxt} servicio`,
    messageHtml: `Se va a <strong>${accionTxt.toLowerCase()}</strong> el servicio <strong>${esc(nombreSrv)}</strong> ${assign ? 'a' : 'de'} la empresa <strong>${esc(nombreEmp)}</strong>.`,
    message: `Se va a ${accionTxt.toLowerCase()} el servicio "${nombreSrv}" ${assign ? 'a' : 'de'} la empresa "${nombreEmp}".`,
    confirmText: accionTxt,
    confirmClass: assign ? 'btn-success' : 'btn-danger'
  });
  if (!confirmado) return;

  btn.disabled = true;
  try{
    const fd = new FormData();
    fd.append('action','set_emp_srv');
    fd.append('empresa_id', String(empresa_id));
    fd.append('servicio_id', String(E.servicioId));
    fd.append('assign', String(assign));
    const r = await fetch(apiUrl, { method:'POST', body: fd, credentials:'same-origin' });
    const j = await r.json().catch(()=>({ok:false,msg:'Respuesta no válida'}));
    if (!r.ok || !j.ok) throw new Error(j.msg || `HTTP ${r.status}`);

    // Feedback en el alert verde superior (reutilizado)
    show(slot.querySelector('#s-ok'), `Servicio "${nombreSrv}" ${assign? 'asignado a':'quitado de'} la empresa.`);

    await e_cargarLista();
  }catch(err){
    show(slot.querySelector('#e-alert'), err.message || 'Error al actualizar asignación');
  }finally{
    btn.disabled = false;
  }
});

// ---------- Panel izquierdo: Empresas del servicio ----------
const E = { servicioId:0, servicioNombre:'', empresa_id:0, estado:'', q:'', page:1, per_page:5 };

async function e_cargarSelectEmpresas(){
  try{
    const r = await j(`${apiUrl}?action=empresas`);
    const sel = slot.querySelector('#e-empresa');
    if (sel) {
      sel.innerHTML = `<option value="0">Todas</option>` +
        r.data.map(x => `<option value="${x.id}">${esc(x.nombre)}</option>`).join('');
      if (E.empresa_id) sel.value = String(E.empresa_id);
    }
  }catch(_){}
}

function e_pintarTabla(rows, total){
  const tb = slot.querySelector('#e-tbody');
  const start = (E.page - 1) * E.per_page;
  tb.innerHTML = rows.map((r,i)=>`
    <tr>
      <td>${start + i + 1}</td>
<td>
  ${esc(r.nombre)}<br>
  <span class="badge rounded-pill ${r.asignado ? 'bg-success' : 'bg-secondary'}">
    ${r.asignado ? 'Asignado' : 'No asignado'}
  </span>
</td>
      <td class="text-end">
        <button class="btn btn-sm ${r.asignado?'btn-danger':'btn-success'} e-toggle"
                data-empresa="${r.id}" data-asignado="${r.asignado?1:0}" data-empresa-nombre="${esc(r.nombre)}">
          ${r.asignado ? 'Quitar' : 'Asignar'}
        </button>
      </td>
    </tr>
  `).join('');

  const ul = slot.querySelector('#e-pager');
  const pages = Math.max(1, Math.ceil(total / E.per_page));
  E.page = Math.min(E.page, pages);
  const cur = E.page;
  const li = [];
  const add = (p, label, cls='')=> li.push(
    `<li class="page-item ${cls}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`
  );
  add(cur-1, '«', cur<=1?'disabled':'');
  let s = Math.max(1, cur-2), e = Math.min(pages, s+4); s = Math.max(1, e-4);
  for (let p=s; p<=e; p++) add(p, p, p===cur?'active':'');
  add(cur+1, '»', cur>=pages?'disabled':'');
  ul.innerHTML = li.join('');
}

async function e_cargarLista(){
  const hint  = slot.querySelector('#emp-hint');
  const panel = slot.querySelector('#emp-panel');
  if (!E.servicioId) {
    panel?.classList.add('d-none'); hint?.classList.remove('d-none');
    return;
  }
  hint?.classList.add('d-none'); panel?.classList.remove('d-none');

const label = slot.querySelector('#e-srv-actual');
if (label) {
  label.textContent = E.servicioNombre || `ID ${E.servicioId}`;

  // Activa animación solo si el contenido desborda el contenedor
  const box = label.closest('.e-srv-marquee');
  if (box) {
    // Espera un frame para que el layout esté listo
    requestAnimationFrame(() => {
      const need = label.scrollWidth > box.clientWidth + 2;
      label.classList.toggle('animate', need);
    });
  }
}

  const qs = new URLSearchParams({
    action:'empresas_srv',
    servicio_id: E.servicioId,
    empresa_id: E.empresa_id || 0,
    estado: E.estado,
    q: E.q || '',
    page: E.page,
    per_page: E.per_page
  });

  const alert = slot.querySelector('#e-alert');
  hide(alert);

  try{
    const r = await j(`${apiUrl}?${qs.toString()}`);
    e_pintarTabla(r.data || [], r.total || 0);
  }catch(err){
    show(alert, err.message || 'Error al listar empresas');
  }
}

  // ---------- REFRESH para reabrir el modal ----------
  slot.__modServiciosRefresh = async function(){
    // limpiar mensajes
    hide($('#s-alert')); hide($('#s-ok')); hide($('#l-alert')); hide($('#e-alert'));
    // reset filtros UI
    const fe = $('#f-empresa'); const fq = $('#f-q'); const fs = $('#f-estado');
    if (fe) fe.value = '0'; if (fq) fq.value = ''; if (fs) fs.value = '';
    // reset chips
    writeTags([]); const inp = inputEl(); if (inp) inp.value = '';
    setCurrentImagePreview('');
    setNewImagePreviewFromFile(null);
    hideUploadProgress();
    // reset estado de lista
    L.empresa=0; L.q=''; L.estado=''; L.page=1; L.openId=0;
    await cargarEmpresas();
    await cargarLista();
    leaveEditMode();
// reset panel izquierdo
E.servicioId = 0; E.servicioNombre = ''; E.empresa_id = 0; E.estado = ''; E.q = ''; E.page = 1;
const eQ = slot.querySelector('#e-q'); if (eQ) eQ.value = '';
const hint  = slot.querySelector('#emp-hint');
const panel = slot.querySelector('#emp-panel');
if (panel) panel.classList.add('d-none');
if (hint)  hint.classList.remove('d-none');
slot.querySelector('#e-tbody')?.replaceChildren();
slot.querySelector('#e-pager')?.replaceChildren();
slot.querySelector('#e-srv-actual') && (slot.querySelector('#e-srv-actual').textContent = '');
  };

  // Primera carga
  slot.__modServiciosRefresh();
}
