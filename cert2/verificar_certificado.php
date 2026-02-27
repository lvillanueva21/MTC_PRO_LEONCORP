<?php
// verificar_certificado.php
include('db.php');

$results = [];
$searchPerformed = false;

// Buscar por código QR (GET)
if (isset($_GET['code'])) {
    $searchPerformed = true;
    $code = $_GET['code'];
    $stmt = $conexion->prepare("SELECT * FROM certificados WHERE qr_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($cert = $result->fetch_assoc()) {
        $results[] = $cert;
    }
    $stmt->close();
}
// Buscar por documento (POST)
elseif (isset($_POST['documento'])) {
    $searchPerformed = true;
    $doc = $_POST['documento'];
    $stmt = $conexion->prepare("SELECT * FROM certificados WHERE documento = ?");
    $stmt->bind_param("s", $doc);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Verificar Certificado</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
      .bg-celeste { background-color: #d1ecf1; }
      .bg-violeta { background-color: #e8daef; }
      .icon-qr { font-size: 48px; color: #007bff; }
      .icon-check {
          font-size: 24px;
          width: 48px;
          height: 48px;
          line-height: 48px;
          border: 2px solid #28a745;
          border-radius: 50%;
          text-align: center;
          color: #28a745;
      }
  </style>
</head>
<body>
  <div class="container mt-4">
    <div class="row">
      <!-- Formulario de búsqueda -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header bg-primary text-white">VERIFICAR CERTIFICADO</div>
          <div class="card-body">
            <div class="d-flex align-items-center bg-celeste p-3 mb-3">
              <div class="flex-grow-1">
                <p class="mb-0">Puedes escanear el QR de tu certificado desde tu celular</p>
              </div>
              <div><i class="fas fa-qrcode icon-qr"></i></div>
            </div>
            <div class="d-flex align-items-center bg-violeta p-3 mb-3">
              <div class="flex-grow-1">
                <p class="mb-0">También puedes ingresar datos para buscar tus certificados.</p>
              </div>
              <div><div class="icon-check">&#10003;</div></div>
            </div>
            <div class="card border-success mb-3">
              <div class="card-header bg-success text-white">Buscar certificado por documento</div>
              <div class="card-body">
                <form method="post" action="verificar_certificado.php">
                  <div class="form-group">
                    <input type="text" class="form-control" name="documento" id="documento" pattern="[0-9]{1,11}" maxlength="11" placeholder="Ingresa DNI o carnet (solo números)" required>
                  </div>
                  <button type="submit" class="btn btn-success">Buscar Certificados</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resultados -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header bg-primary text-white">Resultados de búsqueda</div>
          <div class="card-body">
            <?php if (!$searchPerformed): ?>
              <div class="alert alert-info" role="alert">
                Aquí podrás visualizar los certificados que coincidan con tu documento de identidad.
              </div>
            <?php elseif (count($results) > 0): ?>
              <?php foreach ($results as $cert): ?>
                <div class="border p-3 mb-3">
                  <p><strong>Código:</strong> <?php echo htmlspecialchars($cert['codigo']); ?></p>
                  <p><strong>Empresa:</strong> <?php echo htmlspecialchars($cert['empresa']); ?></p>
                  <p><strong>Nombres:</strong> <?php echo htmlspecialchars($cert['nombres']); ?></p>
                  <p><strong>Apellidos:</strong> <?php echo htmlspecialchars($cert['apellidos']); ?></p>
                  <p><strong>Documento:</strong> <?php echo htmlspecialchars($cert['documento']); ?></p>
                  <p><strong>Curso:</strong> <?php echo htmlspecialchars($cert['curso']); ?></p>
                  <p><strong>Número de horas:</strong> <?php echo intval($cert['horas']); ?></p>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="alert alert-warning" role="alert">
                No se encontraron certificados.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>