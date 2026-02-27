<div class="cert-form">
  <div class="d-flex justify-content-between align-items-baseline mb-3">
    <h3 class="cert-form-title mb-0">Emitir certificado Fast</h3>
    <span class="cert-form-code">#000123</span>
  </div>

  <form>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="curso">Curso</label>
        <select id="curso" class="form-control">
          <option>Manejo Defensivo</option>
        </select>
      </div>
      <div class="form-group col-md-6">
        <label for="tipo_certificado">Tipo Certificado</label>
        <select id="tipo_certificado" class="form-control">
          <option>No tiene</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="nombres">Nombres</label>
        <input type="text" id="nombres" class="form-control" value="Luigi Israel">
      </div>
      <div class="form-group col-md-6">
        <label for="apellidos">Apellidos</label>
        <input type="text" id="apellidos" class="form-control" value="Villanueva Pérez">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="tipo_doc">Tipo Doc.</label>
        <select id="tipo_doc" class="form-control">
          <option>DNI</option>
        </select>
      </div>
      <div class="form-group col-md-6">
        <label for="caracteres_doc">Documento</label>
        <input type="text" id="caracteres_doc" class="form-control" value="70379752">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="categoria">Categoría</label>
        <select id="categoria" class="form-control">
          <option>No tiene</option>
        </select>
      </div>
      <div class="form-group col-md-6">
        <label for="fecha_emision">Fecha Emisión</label>
        <input type="text" id="fecha_emision" class="form-control" value="15/11/2025">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="fecha_inicio">Fecha Inicio</label>
        <input type="text" id="fecha_inicio" class="form-control" value="15/11/2025">
      </div>
      <div class="form-group col-md-6">
        <label for="fecha_fin">Fecha Fin</label>
        <input type="text" id="fecha_fin" class="form-control" value="15/11/2025">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="horas_teoricas">Horas Teóricas</label>
        <input type="text" id="horas_teoricas" class="form-control" value="4">
      </div>
      <div class="form-group col-md-6">
        <label for="horas_practicas">Horas Prácticas</label>
        <input type="text" id="horas_practicas" class="form-control" value="4">
      </div>
    </div>

    <div class="text-center mt-3">
      <button type="button" class="btn btn-cert-generar">
        Generar certificado
      </button>
    </div>
  </form>
</div>
