// modules/reportes/index.js
// Toggle de filas secundarias por click en la fila principal.
// Nota: al ser type="module", este script se ejecuta en defer automáticamente.
document.addEventListener('click', (ev) => {
  const tr = ev.target.closest('tr.js-row');
  if (!tr) return;
  const id = tr.getAttribute('data-id');
  const det = document.getElementById('det-' + id);
  if (!det) return;
  det.classList.toggle('d-none');
});
