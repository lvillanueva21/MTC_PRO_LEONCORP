<!-- components/modal_add_sede.php -->
<div id="modalSede" style="display:none; position:fixed; top:20%; left:35%; background:white; padding:20px; border-radius:12px; box-shadow:0 0 10px #000; z-index:1000;">
  <h3>Agregar Nueva Sede</h3>
  <form action="sede_add.php" method="POST">
    <input type="text" name="nombre" placeholder="Nombre de la sede" required>
    <br><br>
    <button type="submit">Guardar</button>
    <button type="button" onclick="document.getElementById('modalSede').style.display='none'">Cancelar</button>
  </form>
</div>