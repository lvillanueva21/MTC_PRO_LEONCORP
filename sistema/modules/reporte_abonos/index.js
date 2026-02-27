// modules/reporte_abonos/index.js
// Toggle de filas de detalle al hacer click en la fila principal.

document.addEventListener('click', function (ev) {
  var tr = ev.target.closest('tr.js-row');
  if (!tr) return;
  var id = tr.getAttribute('data-id');
  if (!id) return;
  var det = document.getElementById('det-' + id);
  if (!det) return;
  det.classList.toggle('d-none');
});
