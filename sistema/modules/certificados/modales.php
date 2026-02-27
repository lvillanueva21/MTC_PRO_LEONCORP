<?php
// /modules/certificados/modales.php
?>
<div class="modal fade" id="modalConfirmEstadoCert" tabindex="-1" role="dialog" aria-labelledby="modalConfirmEstadoCertLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header" id="modalConfirmEstadoCertHeader">
        <h5 class="modal-title" id="modalConfirmEstadoCertLabel">Cambiar estado del certificado</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="modalConfirmEstadoCertError" class="alert alert-danger" style="display:none;margin-bottom:10px;"></div>

        <p id="modalConfirmEstadoCertMensaje" style="font-size:14px;margin-bottom:10px;"></p>

        <div class="card mb-2">
          <div class="card-body p-2">
            <div style="font-size:13px;">
              <div><strong>Código:</strong> <span id="modalConfirmEstadoCertCodigo"></span></div>
              <div><strong>Alumno:</strong> <span id="modalConfirmEstadoCertAlumno"></span></div>
              <div><strong>Curso:</strong> <span id="modalConfirmEstadoCertCurso"></span></div>
              <div><strong>Estado actual:</strong> <span id="modalConfirmEstadoCertEstado"></span></div>
            </div>
          </div>
        </div>

        <input type="hidden" id="modalConfirmEstadoCertId" value="">
        <input type="hidden" id="modalConfirmEstadoCertAccion" value="">
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm" id="btnConfirmEstadoCert">
          Confirmar
        </button>
      </div>
    </div>
  </div>
</div>
