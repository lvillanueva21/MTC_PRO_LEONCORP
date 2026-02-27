<!-- components/modal_add_pc.php -->
<div class="modal fade" id="modalPc" tabindex="-1" aria-labelledby="modalPcLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form action="computadora_add.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalPcLabel">Agregar Computadora</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="empresa_id" id="empresa_pc_id">

          <div class="mb-3">
            <label class="form-label">Etiqueta:</label>
            <input type="text" name="etiqueta" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Dirección IP:</label>
            <input type="text" name="direccion_ip" class="form-control" required
              inputmode="numeric"
              pattern="^(25[0-5]|2[0-4][0-9]|1\d{2}|[1-9]?\d)\.(25[0-5]|2[0-4][0-9]|1\d{2}|[1-9]?\d)\.(25[0-5]|2[0-4][0-9]|1\d{2}|[1-9]?\d)\.(25[0-5]|2[0-4][0-9]|1\d{2}|[1-9]?\d)$"
              title="Ingresa una dirección IP válida, por ejemplo: 192.168.0.99">
          </div>

          <div class="mb-3">
            <label class="form-label">Nombre PC:</label>
            <input type="text" name="nombre_pc" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">MAC:</label>
            <input type="text" name="mac" class="form-control"
              pattern="^([0-9A-Fa-f]{2}-){5}[0-9A-Fa-f]{2}$"
              placeholder="Ej: AA-BB-CC-DD-EE-FF" required
              title="Formato esperado: AA-BB-CC-DD-EE-FF">
          </div>

          <div class="mb-3">
            <label class="form-label">Nro Serie:</label>
            <input type="text" name="nro_serie" class="form-control"
              maxlength="10" pattern="\d{1,10}" required
              title="Máximo 10 dígitos numéricos">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>