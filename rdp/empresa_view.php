<?php
// empresa_view.php
require_once 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$empresa_id = intval($_GET['id']);

// Obtener datos de la empresa
$stmt_empresa = $conexion->prepare("
  SELECT e.nombre, e.color, s.nombre as sede 
  FROM rdp_empresas e 
  JOIN rdp_sedes s ON e.sede_id = s.id 
  WHERE e.id = ?");
$stmt_empresa->bind_param("i", $empresa_id);
$stmt_empresa->execute();
$stmt_empresa->bind_result($empresa_nombre, $empresa_color, $sede_nombre);
$stmt_empresa->fetch();
$stmt_empresa->close();

// Obtener computadoras de esa empresa
$stmt_computadoras = $conexion->prepare("SELECT * FROM rdp_computadoras WHERE empresa_id = ?");
$stmt_computadoras->bind_param("i", $empresa_id);
$stmt_computadoras->execute();
$result_computadoras = $stmt_computadoras->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Computadoras de <?= htmlspecialchars($empresa_nombre) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div id="bg1" class="fondo-dinamico"></div>
<div id="bg2" class="fondo-dinamico"></div>
<?php include 'components/header.php'; ?>

<div class="container" style="flex-direction: column; align-items: center;">
  <h2 style="color: white;">
    Empresa: <?= htmlspecialchars($empresa_nombre) ?> | Sede: <?= htmlspecialchars($sede_nombre) ?>
  </h2>

  <div class="row w-100 justify-content-center">
    <?php while ($row = $result_computadoras->fetch_assoc()): ?>
    <div class="col-md-3 m-2">
      <div class="card shadow">
        <div class="card-header text-white text-center fw-bold" style="background: <?= htmlspecialchars($empresa_color) ?>">
          <?= htmlspecialchars($row['etiqueta']) ?>
        </div>
        <div class="card-body">
          <p><strong>Dirección IP:</strong></p>
          <div class="input-group mb-2">
            <input type="text" class="form-control form-control-sm text-truncate" style="font-size: 0.85rem;" value="<?= htmlspecialchars($row['direccion_ip']) ?>" readonly>
            <button class="btn btn-outline-secondary btn-sm copiar" data-copy="<?= htmlspecialchars($row['direccion_ip']) ?>">📋</button>
          </div>

          <p><strong>Nombre PC:</strong></p>
          <div class="input-group mb-2">
            <input type="text" class="form-control form-control-sm text-truncate" style="font-size: 0.85rem;" value="<?= htmlspecialchars($row['nombre_pc']) ?>" readonly>
            <button class="btn btn-outline-secondary btn-sm copiar" data-copy="<?= htmlspecialchars($row['nombre_pc']) ?>">📋</button>
          </div>

          <p><strong>MAC:</strong></p>
          <div class="input-group mb-2">
            <input type="text" class="form-control form-control-sm text-truncate" style="font-size: 0.85rem;" value="<?= htmlspecialchars($row['mac']) ?>" readonly>
            <button class="btn btn-outline-secondary btn-sm copiar" data-copy="<?= htmlspecialchars($row['mac']) ?>">📋</button>
          </div>

          <p><strong>Nro Serie:</strong></p>
          <div class="input-group mb-2">
            <input type="text" class="form-control form-control-sm text-truncate" style="font-size: 0.85rem;" value="<?= htmlspecialchars($row['nro_serie']) ?>" readonly>
            <button class="btn btn-outline-secondary btn-sm copiar" data-copy="<?= htmlspecialchars($row['nro_serie']) ?>">📋</button>
          </div>

          <div class="d-flex justify-content-between">
            <a href="computadora_update.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
            <a href="computadora_delete.php?id=<?= $row['id'] ?>&empresa=<?= $empresa_id ?>" onclick="return confirm('¿Deseas eliminar esta computadora?')" class="btn btn-danger btn-sm">Eliminar</a>
          </div>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>

  <br>
  <button class="btn btn-primary mt-3" onclick="agregarPc(<?= $empresa_id ?>)">➕ Agregar Computadora</button>
</div>

<?php include 'components/modal_add_pc.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function agregarPc(empresaId) {
  document.getElementById('empresa_pc_id').value = empresaId;
  const modalPc = new bootstrap.Modal(document.getElementById('modalPc'));
  modalPc.show();
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

  const fondoNuevo = showing === 0 ? bg2 : bg1;
  const fondoActual = showing === 0 ? bg1 : bg2;

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

<script>
document.querySelectorAll('.copiar').forEach(btn => {
  btn.addEventListener('click', function () {
    const texto = this.getAttribute('data-copy');
    navigator.clipboard.writeText(texto).then(() => {
      this.textContent = '✅';
      setTimeout(() => { this.textContent = '📋'; }, 1000);
    });
  });
});
</script>

</body>
</html>