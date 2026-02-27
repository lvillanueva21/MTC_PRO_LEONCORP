<!-- components/modal_add_empresa.php -->
<div id="modalEmpresa" style="display:none; position:fixed; top:20%; left:35%; background:white; padding:20px; border-radius:12px; box-shadow:0 0 10px #000; z-index:1000;">
  <h3>Agregar Nueva Empresa</h3>
  <form action="empresa_add.php" method="POST">
    <input type="hidden" name="sede_id" id="empresa_sede_id">
    <input type="text" name="nombre" placeholder="Nombre de la empresa" required>
    <br><br>
    <label>Color:</label><br>
<input type="color" name="color" value="#007bff"><br><br>
    <br><br>
    <label>Ícono:</label><br>
<select name="icono" required>
  <option value="🚗">Auto</option>
  <option value="🏥">Centro Médico</option>
  <option value="🏠">Casa</option>
  <option value="🏢">Edificio</option>
  <option value="🦁">León</option>
</select><br><br>

    <button type="submit">Guardar</button>
    <button type="button" onclick="document.getElementById('modalEmpresa').style.display='none'">Cancelar</button>
  </form>
</div>