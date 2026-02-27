<?php
// /modules/caja/prueba.php
?>
<!-- Paneles de prueba (solo fase de pruebas) -->
<div class="row g-3 mt-1" id="posDebugRow">
  <div class="col-12">
    <div class="alert alert-info py-2 mb-2">
      <i class="fas fa-flask me-1"></i> Fase de pruebas: paneles de verificación rápida (se retirarán luego).
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm">
      <div class="card-header py-2"><strong>Últimas ventas</strong></div>
      <div class="card-body p-2">
        <div class="table-responsive"><table class="table table-sm mb-0" id="dbgVentas"></table></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm">
      <div class="card-header py-2"><strong>Últimos abonos</strong></div>
      <div class="card-body p-2">
        <div class="table-responsive"><table class="table table-sm mb-0" id="dbgAbonos"></table></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm">
      <div class="card-header py-2"><strong>Últimos clientes</strong></div>
      <div class="card-body p-2">
        <div class="table-responsive"><table class="table table-sm mb-0" id="dbgClientes"></table></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm">
      <div class="card-header py-2"><strong>Últimos conductores</strong></div>
      <div class="card-body p-2">
        <div class="table-responsive"><table class="table table-sm mb-0" id="dbgConductores"></table></div>
      </div>
    </div>
  </div>
</div>

<script>
// Lógica exportada como global para que index.php siga llamando await refreshDebugPanels()
(function(){
  window.refreshDebugPanels = async function(){
    try{
      const j = await apiGET({action:'pos_debug_last'});
      const V = j.ventas||[], A = j.abonos||[], C = j.clientes||[], D = j.conductores||[];

      const tbl = (el, head, rows) => {
        el.innerHTML = `
          <thead class="table-light"><tr>${head.map(h=>`<th>${esc(h)}</th>`).join('')}</tr></thead>
          <tbody>${
            rows.length
              ? rows.map(r=>`<tr>${r.map(c=>`<td>${c}</td>`).join('')}</tr>`).join('')
              : `<tr><td colspan="${head.length}" class="text-muted small">Sin datos</td></tr>`
          }</tbody>`;
      };

      tbl(qs('#dbgVentas'),
        ['ID','Ticket','Fecha','Cliente','Total','Pagado','Devuelto','Saldo'],
        V.map(x=>[
          x.id, esc(x.ticket), esc(x.fecha), esc(x.cliente||'—'),
          money(x.total), money(x.pagado), money(x.devuelto), money(x.saldo)
        ])
      );

      tbl(qs('#dbgAbonos'),
        ['ID','Fecha','Medio','Cliente','Monto','Ref'],
        A.map(x=>[
          x.id, esc(x.fecha), esc(x.medio), esc(x.cliente||'—'),
          money(x.monto), esc(x.referencia||'')
        ])
      );

      tbl(qs('#dbgClientes'),
        ['ID','Doc','Nombre','Teléfono','Activo'],
        C.map(x=>[
          x.id,
          `${esc(x.doc_tipo)} ${esc(x.doc_numero)}`,
          esc(x.nombre),
          esc(x.telefono||'—'),
          x.activo ? 'Sí' : 'No'
        ])
      );

      tbl(qs('#dbgConductores'),
        ['ID','Doc','Nombres','Apellidos','Teléfono','Activo'],
        D.map(x=>[
          x.id,
          `${esc(x.doc_tipo)} ${esc(x.doc_numero)}`,
          esc(x.nombres),
          esc(x.apellidos),
          esc(x.telefono||'—'),
          x.activo ? 'Sí' : 'No'
        ])
      );
    }catch(_){}
  };
})();
</script>
