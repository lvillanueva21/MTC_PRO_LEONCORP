// modules/reporte_ventas/index.js
// Toggle del detalle solo al hacer clic en el botón "Detalle", sin afectar otros botones.

document.addEventListener('click', function (ev) {
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
