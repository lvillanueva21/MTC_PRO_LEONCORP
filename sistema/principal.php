<?php /* principal.php - Modal profesional: Cursos + Matrículas/Notas */ ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Académico (demo pro)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <h1>Académico — Cursos, Matrículas y Notas</h1>
  <button id="abrir">Abrir panel académico</button>

  <dialog id="dlg" style="width:1100px;max-width:98vw;height:85vh;padding:0;border:1px solid #ccc">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #ddd;background:#f8f9fa">
      <strong>Panel académico</strong>
      <button id="cerrar">Cerrar</button>
    </div>
    <div id="contenido" style="height:calc(100% - 48px);overflow:auto;padding:12px">Cargando…</div>
  </dialog>

  <script>
    const dlg = document.getElementById('dlg');
    const box = document.getElementById('contenido');

    document.getElementById('abrir').addEventListener('click', () => {
      box.textContent = 'Cargando…';
      fetch('academico.php', {cache:'no-store'})
        .then(r => r.text()).then(html => {
          box.innerHTML = html;
          initAcademicoUI();
        })
        .catch(() => box.textContent = 'No se pudo cargar el panel.');
      dlg.showModal();
    });
    document.getElementById('cerrar').addEventListener('click', () => dlg.close());

    // ======== Lógica del panel (engancha eventos a lo recibido) ========
    function initAcademicoUI(){
      const root = box;

      // Utilitarios
      const debounce = (fn, ms=300) => { let t; return (...a)=>{clearTimeout(t); t=setTimeout(()=>fn(...a),ms);} };
      const asNum = v => { const x = parseFloat(v); return isNaN(x) ? null : x; };

      // ---------- PANEL: CURSOS ----------
      const fCid  = root.querySelector('#c_id');
      const fCod  = root.querySelector('#c_codigo');
      const fNom  = root.querySelector('#c_nombre');
      const fDes  = root.querySelector('#c_desc');
      const fAct  = root.querySelector('#c_activo');
      const msgC  = root.querySelector('#c_msg');
      const qCur  = root.querySelector('#c_q');
      const tCur  = root.querySelector('#c_tbody');
      const btnCSave = root.querySelector('#c_guardar');
      const btnCNew  = root.querySelector('#c_nuevo');

      function clearCursoForm(){ fCid.value=''; fCod.value=''; fNom.value=''; fDes.value=''; fAct.checked=true; msgC.textContent=''; }
      async function loadCursos(page=1){
        const params = new URLSearchParams({action:'cursos_table', q:qCur.value||'', page});
        const r = await fetch('academico.php?'+params.toString());
        tCur.innerHTML = await r.text();
        // refresca opciones de curso para matrículas
        loadCursoOptions();
      }
      async function loadCursoOptions(){
        const r = await fetch('academico.php?action=cursos_options');
        root.querySelector('#m_curso').innerHTML = await r.text();
      }
      qCur.addEventListener('input', debounce(()=>loadCursos(1), 300));
      btnCNew.addEventListener('click', clearCursoForm);
      btnCSave.addEventListener('click', async () => {
        msgC.textContent='';
        const fd = new FormData();
        fd.append('action','curso_save');
        fd.append('id', fCid.value || '');
        fd.append('codigo', fCod.value.trim());
        fd.append('nombre', fNom.value.trim());
        fd.append('descripcion', fDes.value.trim());
        fd.append('activo', fAct.checked ? '1' : '0');
        btnCSave.disabled = true;
        const r = await fetch('academico.php', {method:'POST', body:fd});
        btnCSave.disabled = false;
        const data = await r.json();
        if(!data.ok){ msgC.textContent = data.message || 'No se pudo guardar.'; return; }
        await loadCursos();
        clearCursoForm();
      });
      tCur.addEventListener('click', async (e)=>{
        const btn = e.target.closest('button'); if(!btn) return;
        const id = btn.dataset.id;
        if(btn.dataset.act === 'edit'){
          const r = await fetch('academico.php?action=curso_get&id='+id);
          const d = await r.json();
          if(d.ok){
            fCid.value = d.data.id;
            fCod.value = d.data.codigo;
            fNom.value = d.data.nombre;
            fDes.value = d.data.descripcion || '';
            fAct.checked = d.data.activo === 1 || d.data.activo === '1';
            msgC.textContent = '';
          }
        }
        if(btn.dataset.act === 'del'){
          if(!confirm('¿Eliminar curso #'+id+'?')) return;
          const fd = new FormData(); fd.append('action','curso_del'); fd.append('id', id);
          const r = await fetch('academico.php', {method:'POST', body:fd});
          const d = await r.json();
          if(d.ok){ loadCursos(); }
        }
      });

      // ---------- PANEL: MATRÍCULAS / NOTAS ----------
      const qAlu    = root.querySelector('#m_qalumno');
      const lstAlu  = root.querySelector('#m_alumnos');
      const labSel  = root.querySelector('#m_alumno_sel');
      const hidAid  = root.querySelector('#m_alumno_id');
      const selCurso= root.querySelector('#m_curso');
      const btnMat  = root.querySelector('#m_matricular');
      const msgM    = root.querySelector('#m_msg');
      const tMat    = root.querySelector('#m_tbody');

      async function searchAlumnos(){
        const q = qAlu.value.trim();
        if(q.length<2){ lstAlu.innerHTML=''; return; }
        const r = await fetch('academico.php?action=alumno_find&q='+encodeURIComponent(q));
        const data = await r.json();
        lstAlu.innerHTML = '';
        data.forEach(a=>{
          const li = document.createElement('div');
          li.style.cssText='padding:6px;border:1px solid #eee;border-radius:8px;cursor:pointer';
          li.textContent = `${a.documento} — ${a.nombres} ${a.apellidos}`;
          li.addEventListener('click', ()=>{
            hidAid.value = a.id;
            labSel.textContent = `${a.documento} — ${a.nombres} ${a.apellidos}`;
            lstAlu.innerHTML='';
            qAlu.value='';
            loadMatriculas();
          });
          lstAlu.appendChild(li);
        });
      }
      qAlu.addEventListener('input', debounce(searchAlumnos, 300));

      async function loadMatriculas(){
        const aid = hidAid.value;
        if(!aid){ tMat.innerHTML=''; return; }
        const r = await fetch('academico.php?action=matriculas_table&alumno_id='+encodeURIComponent(aid));
        tMat.innerHTML = await r.text();
      }

      btnMat.addEventListener('click', async ()=>{
        msgM.textContent='';
        const aid = hidAid.value;
        const cid = selCurso.value;
        if(!aid || !cid){ msgM.textContent='Selecciona alumno y curso.'; return; }
        const fd = new FormData();
        fd.append('action','matricular');
        fd.append('alumno_id', aid);
        fd.append('curso_id', cid);
        const r = await fetch('academico.php', {method:'POST', body:fd});
        const d = await r.json();
        if(!d.ok){ msgM.textContent = d.message || 'No se pudo matricular.'; return; }
        loadMatriculas();
      });

      tMat.addEventListener('click', async (e)=>{
        const btn = e.target.closest('button'); if(!btn) return;
        const id = btn.dataset.id;
        if(btn.dataset.act === 'save'){
          const row = btn.closest('tr');
          const n1 = asNum(row.querySelector('[data-f="n1"]').value);
          const n2 = asNum(row.querySelector('[data-f="n2"]').value);
          const n3 = asNum(row.querySelector('[data-f="n3"]').value);
          const fd = new FormData();
          fd.append('action','nota_save');
          fd.append('matricula_id', id);
          if(n1!==null) fd.append('n1', n1);
          if(n2!==null) fd.append('n2', n2);
          if(n3!==null) fd.append('n3', n3);
          btn.disabled=true;
          const r = await fetch('academico.php', {method:'POST', body:fd});
          btn.disabled=false;
          const d = await r.json();
          if(d.ok){ row.querySelector('[data-f="prom"]').textContent = d.promedio; }
        }
        if(btn.dataset.act === 'del'){
          if(!confirm('¿Eliminar matrícula #'+id+'?')) return;
          const fd = new FormData(); fd.append('action','matricula_del'); fd.append('id', id);
          const r = await fetch('academico.php', {method:'POST', body:fd});
          const d = await r.json();
          if(d.ok){ loadMatriculas(); }
        }
      });

      // Carga inicial
      loadCursos();
      loadCursoOptions();
    }
  </script>
</body>
</html>
