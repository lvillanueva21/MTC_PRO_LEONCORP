<?php
// index.php
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Datos PC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>

<body style="padding-top: 140px;">
  <!-- Ajuste para no tapar contenido -->
  <div id="bg1" class="fondo-dinamico"></div>
  <div id="bg2" class="fondo-dinamico"></div>

  <?php include 'components/header.php'; ?>

  <div class="container">
    <?php
      $query_sedes = "SELECT * FROM rdp_sedes ORDER BY id ASC";
      $result_sedes = $conexion->query($query_sedes);

      while ($sede = $result_sedes->fetch_assoc()) {
        $sede_id = (int) $sede['id'];

        echo '<div class="city">';
        echo '<h2>
                <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png" width="20" height="20">
                ' . htmlspecialchars($sede['nombre']) . '
              </h2>';

        echo '<div class="city_container">';

        $query_empresas = "SELECT * FROM rdp_empresas WHERE sede_id = $sede_id ORDER BY nombre ASC";
        $result_empresas = $conexion->query($query_empresas);

        while ($empresa = $result_empresas->fetch_assoc()) {
          $color = htmlspecialchars($empresa['color']);
          $icono = htmlspecialchars($empresa['icono']);

          echo '<a href="empresa_view.php?id=' . (int)$empresa['id'] . '" class="cam" style="background:' . $color . '">'
                . $icono . ' ' . htmlspecialchars($empresa['nombre']) .
              '</a>';
        }

        echo '<button onclick="agregarEmpresa(' . $sede_id . ')" class="btn-mini">➕ Empresa</button>';
        echo '</div>'; // city_container
        echo '</div>'; // city
      }
    ?>
  </div>

  <button onclick="mostrarModalSede()" class="btn-flotante">➕ Sede</button>

  <?php
    include 'components/modal_add_sede.php';
    include 'components/modal_add_empresa.php';
  ?>

  <script>
    function mostrarModalSede() {
      document.getElementById('modalSede').style.display = 'block';
    }

    function agregarEmpresa(sedeId) {
      document.getElementById('modalEmpresa').style.display = 'block';
      document.getElementById('empresa_sede_id').value = sedeId;
    }
  </script>

  <script>
    const backgrounds = [
      'assets/mtc_bg/bg2.webp',
      'assets/mtc_bg/bg3.webp',
      'assets/mtc_bg/bg4.webp',
      'assets/mtc_bg/bg5.webp',
      'assets/mtc_bg/bg7.webp',
      'assets/mtc_bg/bg8.webp',
      'assets/mtc_bg/bg9.webp'
    ];

    let current = 0;
    let showing = 0;

    const bg1 = document.getElementById('bg1');
    const bg2 = document.getElementById('bg2');

    bg1.style.backgroundImage = `url('${backgrounds[current]}')`;
    bg1.classList.add('visible');

    function cambiarFondo() {
      const siguiente = (current + 1) % backgrounds.length;
      const nuevoFondo = backgrounds[siguiente];

      const fondoNuevo = (showing === 0) ? bg2 : bg1;
      const fondoActual = (showing === 0) ? bg1 : bg2;

      const img = new Image();
      img.src = nuevoFondo;

      img.onload = () => {
        fondoNuevo.style.backgroundImage = `url('${nuevoFondo}')`;
        fondoNuevo.classList.add('visible');
        fondoActual.classList.remove('visible');

        showing = 1 - showing;
        current = siguiente;
      };
    }

    setInterval(cambiarFondo, 20000);
  </script>
</body>
</html>
