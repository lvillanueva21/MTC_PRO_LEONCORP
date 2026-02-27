<?php 
// certificado.php
include('db.php');

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibir datos del formulario
    $nombres   = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $documento = $_POST['documento'];
    $curso     = $_POST['curso'];
    $horas     = $_POST['horas'];
    $empresa   = $_POST['empresa'];

    // Obtener el último código de esta empresa
    $stmt = $conexion->prepare("SELECT ultimo_codigo FROM codigos_empresa WHERE empresa = ?");
    $stmt->bind_param("s", $empresa);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $ultimo_codigo = $row ? $row['ultimo_codigo'] : 0;
    $stmt->close();

    // Generar nuevo código: +1 y completar con ceros
    $nuevo_codigo_int = $ultimo_codigo + 1;
    $codigo = str_pad($nuevo_codigo_int, 5, "0", STR_PAD_LEFT);

    // Actualizar codigos_empresa
    $stmt = $conexion->prepare("UPDATE codigos_empresa SET ultimo_codigo = ? WHERE empresa = ?");
    $stmt->bind_param("is", $nuevo_codigo_int, $empresa);
    $stmt->execute();
    $stmt->close();

    // Generar nombre del QR
$qr_code_value = strtolower(trim($nombres) . '-' . trim($apellidos) . '-' . trim($curso));

// Reemplazar espacios por guiones
$qr_code_value = preg_replace('/\s+/', '-', $qr_code_value);

// Eliminar cualquier carácter que no sea letra, número o guión
$qr_code_value = preg_replace('/[^a-z0-9\-]/', '', $qr_code_value);

    // Insertar en certificados incluyendo codigo y empresa
    $stmt = $conexion->prepare("INSERT INTO certificados (codigo, qr_code, nombres, apellidos, documento, curso, horas, empresa) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssis", $codigo, $qr_code_value, $nombres, $apellidos, $documento, $curso, $horas, $empresa);
    $stmt->execute();
    $stmt->close();

    // Incluir la librería phpqrcode para generar el QR
    include('phpqrcode/qrlib.php');
    $qr_dir = "qrs/";
    if (!is_dir($qr_dir)) {
        mkdir($qr_dir, 0777, true);
    }
    $qr_file = $qr_dir . $qr_code_value . ".png";
    $url = "http://leoncorp.pe/cert2/verificar_certificado.php?code=" . $qr_code_value;
    QRcode::png($url, $qr_file);

    $message = "Certificado generado correctamente.";
}

// Consultar todos los certificados
// Control de filas por página
$limite = isset($_GET['limite']) ? ($_GET['limite'] === 'todos' ? 'todos' : intval($_GET['limite'])) : 5;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

if ($limite !== 'todos') {
    $inicio = ($pagina - 1) * $limite;
    $result = $conexion->query("SELECT * FROM certificados ORDER BY id DESC LIMIT $inicio, $limite");
    $total_resultado = $conexion->query("SELECT COUNT(*) AS total FROM certificados")->fetch_assoc();
    $total_certificados = $total_resultado['total'];
    $total_paginas = ceil($total_certificados / $limite);
} else {
    $result = $conexion->query("SELECT * FROM certificados ORDER BY id DESC");
    $total_paginas = 1;
    $pagina = 1;
    $limite = 'todos';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Generar Certificado</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
  <div class="container mt-4">
    <?php if ($message != ""): ?>
      <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <div class="row">
      <div class="col-md-4">
        <h2>Generar Certificado</h2>
        <form method="post" action="certificado.php">
          <div class="form-group">
            <label for="nombres">Nombres</label>
            <input type="text" class="form-control" name="nombres" id="nombres" required>
          </div>
          <div class="form-group">
            <label for="apellidos">Apellidos</label>
            <input type="text" class="form-control" name="apellidos" id="apellidos" required>
          </div>
          <div class="form-group">
            <label for="documento">Documento</label>
            <input type="text" class="form-control" name="documento" id="documento" pattern="[0-9]{1,11}" maxlength="11" required placeholder="Solo números (máx 11 dígitos)">
          </div>
          <div class="form-group">
            <label for="curso">Curso</label>
            <input type="text" class="form-control" name="curso" id="curso" required>
          </div>
          <div class="form-group">
            <label for="empresa">Empresa</label>
            <select name="empresa" id="empresa" class="form-control" required>
              <option value="">Selecciona una empresa</option>
              <option value="Global Car Perú SAC - Sede Chiclayo">Global Car Perú SAC - Sede Chiclayo</option>
              <option value="Global Car Piura SAC">Global Car Piura SAC</option>
              <option value="Global Car Perú SAC - Sede Cajamarca">Global Car Perú SAC - Sede Cajamarca</option>
                          <option value="Certificar Peru SAC - Chiclayo">Certificar Peru SAC - Chiclayo</option>
                          <option value="Certificar Peru SAC - Cajamarca">Certificar Peru SAC - Cajamarca</option>
                          <option value="Certificar Piura SAC">Certificar Piura SAC</option>
            </select>
            </select>
          </div>
          <div class="form-group">
            <label for="horas">Número de horas</label>
            <input type="number" class="form-control" name="horas" id="horas" required>
          </div>
          <button type="submit" class="btn btn-primary">Generar Certificado</button>
        </form>
      </div>
      <div class="col-md-8">
        <h2>Certificados Generados</h2>
<div class="mb-3">
  <span>Mostrar:</span>
  <?php
$limites = [5, 10, 20, 50, 'todos'];
    foreach ($limites as $opcion) {
      $activo = ($limite == $opcion) ? 'btn-primary' : 'btn-outline-primary';
      echo "<a href=\"?limite=$opcion\" class=\"btn btn-sm $activo mx-1\">$opcion</a>";
    }
  ?>
</div>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Código</th>
              <th>Nombres</th>
              <th>Apellidos</th>
              <th>Documento</th>
              <th>Curso</th>
              <th>Horas</th>
              <th>Empresa</th>
              <th>QR</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?> 
              <tr>
                <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                <td><?php echo htmlspecialchars($row['nombres']); ?></td>
                <td><?php echo htmlspecialchars($row['apellidos']); ?></td>
                <td><?php echo htmlspecialchars($row['documento']); ?></td>
                <td><?php echo htmlspecialchars($row['curso']); ?></td>
                <td><?php echo intval($row['horas']); ?></td>
                <td><?php echo htmlspecialchars($row['empresa']); ?></td>
                <td>
                  <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#qrModal<?php echo $row['id']; ?>">
                    <i class="fas fa-qrcode"></i>
                  </button>
                  <div class="modal fade" id="qrModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="qrModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="qrModalLabel<?php echo $row['id']; ?>">Detalle del Certificado</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                          </button>
                        </div>
                        <div class="modal-body">
                          <div class="row">
                            <div class="col-md-6">
                              <p><strong>Código:</strong> <?php echo htmlspecialchars($row['codigo']); ?></p>
                              <p><strong>Nombres:</strong> <?php echo htmlspecialchars($row['nombres']); ?></p>
                              <p><strong>Apellidos:</strong> <?php echo htmlspecialchars($row['apellidos']); ?></p>
                              <p><strong>Documento:</strong> <?php echo htmlspecialchars($row['documento']); ?></p>
                              <p><strong>Curso:</strong> <?php echo htmlspecialchars($row['curso']); ?></p>
                              <p><strong>Horas:</strong> <?php echo intval($row['horas']); ?></p>
                              <p><strong>Empresa:</strong> <?php echo htmlspecialchars($row['empresa']); ?></p>
                            </div>
                            <div class="col-md-6 text-center">
                              <img src="qrs/<?php echo $row['qr_code']; ?>.png" alt="QR" class="img-fluid mb-3" style="max-width: 200px;">
                              <br>
                              <a href="qrs/<?php echo $row['qr_code']; ?>.png" download class="btn btn-primary btn-sm">
                                <i class="fas fa-download"></i> Descargar QR
                              </a>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
<td class="d-flex justify-content-between">
  <a href="imprimir_certificado.php?code=<?php echo $row['qr_code']; ?>" target="_blank" class="btn btn-success btn-sm mr-1">
    <i class="fas fa-file-pdf"></i>
  </a>
  <a href="eliminar_certificado.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar este certificado? Esta acción no se puede deshacer.');">
    <i class="fas fa-trash-alt"></i>
  </a>
</td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <?php if ($limite !== 'todos' && $total_paginas > 1): ?>
  <nav>
    <ul class="pagination">
      <?php
        $max_links = 10;
        $start = max(1, $pagina - floor($max_links / 2));
        $end = min($total_paginas, $start + $max_links - 1);

        if ($start > 1) {
          echo '<li class="page-item"><a class="page-link" href="?pagina=1&limite=' . $limite . '">1</a></li>';
          echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        for ($i = $start; $i <= $end; $i++) {
          $active = ($i == $pagina) ? 'active' : '';
          echo "<li class=\"page-item $active\"><a class=\"page-link\" href=\"?pagina=$i&limite=$limite\">$i</a></li>";
        }

        if ($end < $total_paginas) {
          echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
          echo '<li class="page-item"><a class="page-link" href="?pagina=' . $total_paginas . '&limite=' . $limite . '">' . $total_paginas . '</a></li>';
        }
      ?>
    </ul>
  </nav>
<?php endif; ?>

      </div>
    </div>
  </div>
</body>
</html>