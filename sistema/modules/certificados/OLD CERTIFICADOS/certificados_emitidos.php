<div class="cert-list">
  <h3 class="cert-list-title">Certificados emitidos</h3>

  <!-- FILTROS SUPERIORES -->
  <div class="cert-filters">
    <div class="form-row">
      <div class="form-group col-md-3">
        <label for="filtro_fecha_inicio">Fecha Inicio</label>
        <input type="text" id="filtro_fecha_inicio" class="form-control" value="15/11/2025">
      </div>
      <div class="form-group col-md-3">
        <label for="filtro_fecha_fin">Fecha Fin</label>
        <input type="text" id="filtro_fecha_fin" class="form-control" value="15/11/2025">
      </div>
      <div class="form-group col-md-3">
        <label for="filtro_estado">Estado</label>
        <select id="filtro_estado" class="form-control">
          <option>Activo</option>
          <option>Inactivo</option>
          <option>Vencido</option>
        </select>
      </div>
<div class="form-group col-md-3">
  <label for="filtro_curso">Curso</label>
  <select id="filtro_curso" class="form-control">
    <option>Manejo Defensivo</option>
    <option>Seguridad Vial</option>
    <option>Mecánica Básica</option>
  </select>
</div>
    </div>

    <div class="form-row">
      <div class="form-group col-md-12">
        <label for="filtro_busqueda">Nombre o documento</label>
        <input type="text" id="filtro_busqueda" class="form-control"
               placeholder="Escribe un nombre o documento para buscar ...">
      </div>
    </div>
  </div>

  <!-- TABLA -->
  <table class="table-cert">
    <thead>
      <tr>
        <th>Código</th>
        <th>Nombre</th>
        <th>Documento</th>
        <th>Curso</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          000122<br>
          <span class="badge-status status-activo">Activo</span>
        </td>
        <td>Luigi Israel Villanueva Pérez</td>
        <td>70379752</td>
        <td>Manejo Defensivo</td>
        <td class="cert-actions">
          <button type="button" class="btn-icon btn-icon-qr">
            <i class="fas fa-qrcode"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-pdf">
            <i class="far fa-file-pdf"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-edit">
            <i class="fas fa-pen"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-del">
            <i class="fas fa-trash-alt"></i>
          </button>
        </td>
      </tr>

      <tr>
        <td>
          000122<br>
          <span class="badge-status status-inactivo">Inactivo</span>
        </td>
        <td>Luigi Israel Villanueva Pérez</td>
        <td>70379752</td>
        <td>Manejo Defensivo</td>
        <td class="cert-actions">
          <button type="button" class="btn-icon btn-icon-qr">
            <i class="fas fa-qrcode"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-pdf">
            <i class="far fa-file-pdf"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-edit">
            <i class="fas fa-pen"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-del">
            <i class="fas fa-trash-alt"></i>
          </button>
        </td>
      </tr>

      <tr>
        <td>
          000122<br>
          <span class="badge-status status-vencido">Vencido</span>
        </td>
        <td>Luigi Israel Villanueva Pérez</td>
        <td>70379752</td>
        <td>Manejo Defensivo</td>
        <td class="cert-actions">
          <button type="button" class="btn-icon btn-icon-qr">
            <i class="fas fa-qrcode"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-pdf">
            <i class="far fa-file-pdf"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-edit">
            <i class="fas fa-pen"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-del">
            <i class="fas fa-trash-alt"></i>
          </button>
        </td>
      </tr>

      <tr>
        <td>
          000122<br>
          <span class="badge-status status-activo">Activo</span>
        </td>
        <td>Luigi Israel Villanueva Pérez</td>
        <td>70379752</td>
        <td>Manejo Defensivo</td>
        <td class="cert-actions">
          <button type="button" class="btn-icon btn-icon-qr">
            <i class="fas fa-qrcode"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-pdf">
            <i class="far fa-file-pdf"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-edit">
            <i class="fas fa-pen"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-del">
            <i class="fas fa-trash-alt"></i>
          </button>
        </td>
      </tr>

      <tr>
        <td>
          000122<br>
          <span class="badge-status status-activo">Activo</span>
        </td>
        <td>Luigi Israel Villanueva Pérez</td>
        <td>70379752</td>
        <td>Manejo Defensivo</td>
        <td class="cert-actions">
          <button type="button" class="btn-icon btn-icon-qr">
            <i class="fas fa-qrcode"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-pdf">
            <i class="far fa-file-pdf"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-edit">
            <i class="fas fa-pen"></i>
          </button>
          <button type="button" class="btn-icon btn-icon-del">
            <i class="fas fa-trash-alt"></i>
          </button>
        </td>
      </tr>
    </tbody>
  </table>

  <!-- PAGINACIÓN SIMPLE -->
  <div class="cert-pagination">
    <button type="button" class="page-btn active">1</button>
    <button type="button" class="page-btn">2</button>
    <button type="button" class="page-btn">3</button>
    <span class="page-sep">...</span>
    <button type="button" class="page-btn">8</button>
    <button type="button" class="page-btn">9</button>
    <button type="button" class="page-btn">10</button>
  </div>
</div>
