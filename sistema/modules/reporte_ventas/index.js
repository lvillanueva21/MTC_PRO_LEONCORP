// modules/reporte_ventas/index.js
// Toggle del detalle solo al hacer clic en el botón "Detalle", sin afectar otros botones.

document.addEventListener('click', function (ev) {
  var btnAbonar = ev.target.closest('.js-abonar');
  if (btnAbonar) {
    var ventaId = parseInt(btnAbonar.getAttribute('data-id') || '0', 10);
    if (!ventaId) {
      return;
    }
    var tbl = document.getElementById('tblVentas');
    var base = tbl ? String(tbl.getAttribute('data-abonar-url-base') || '').trim() : '';
    if (!base) {
      return;
    }
    var url = new URL(base, window.location.href);
    url.searchParams.set('abonar_venta', String(ventaId));
    window.location.href = url.toString();
    return;
  }

  var btn = ev.target.closest('.js-detalle');
  if (!btn) {
    return;
  }
  var id = btn.getAttribute('data-id');
  if (!id) {
    return;
  }
  var detRow = document.getElementById('det-' + id);
  if (!detRow) {
    return;
  }
  if (detRow.classList.contains('d-none')) {
    detRow.classList.remove('d-none');
  } else {
    detRow.classList.add('d-none');
  }
});
