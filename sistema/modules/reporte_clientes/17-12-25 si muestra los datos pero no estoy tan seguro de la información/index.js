// modules/reporte_clientes/index.js
// Comportamiento básico de la central de clientes.

document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.js-detalle-cliente');
    if (!btn) {
        return;
    }
    var id = btn.getAttribute('data-id');
    if (!id) {
        return;
    }
    var filaDetalle = document.getElementById('det-' + id);
    if (!filaDetalle) {
        return;
    }
    if (filaDetalle.classList.contains('d-none')) {
        filaDetalle.classList.remove('d-none');
    } else {
        filaDetalle.classList.add('d-none');
    }
});
