<?php
// /registro.php
require __DIR__.'/includes/conexion.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// --- Procesamiento POST (PRG) ---
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $accion = $_POST['accion'] ?? '';

  try {
    if ($accion === 'nuevo_repleg') {
      $nombres   = trim($_POST['nombres'] ?? '');
      $apellidos = trim($_POST['apellidos'] ?? '');
      $documento = trim($_POST['documento'] ?? '');
      $clave     = $_POST['clave'] ?? '';

      if ($nombres === '' || $apellidos === '') $errores[] = 'Nombres y apellidos son obligatorios.';
      if (!preg_match('/^\d{8}$/', $documento)) $errores[] = 'Documento debe ser DNI de 8 dígitos.';
      if (strlen($clave) < 1) $errores[] = 'La contraseña no puede estar vacía.';

      if (!$errores) {
    // Guardar en texto plano (sin hash)
    $sql = "INSERT INTO mtp_representante_legal(nombres, apellidos, documento, clave_mana) VALUES (?,?,?,?)";
    $st = db()->prepare($sql);
    $st->bind_param('ssss', $nombres, $apellidos, $documento, $clave);
    $st->execute();
    header('Location: registro.php?ok=repleg'); exit;
      }
    }

    if ($accion === 'nueva_empresa') {
      $nombre       = trim($_POST['nombre'] ?? '');
      $razon_social = trim($_POST['razon_social'] ?? '');
      $ruc          = trim($_POST['ruc'] ?? '');
      $direccion    = trim($_POST['direccion'] ?? '');
      $id_tipo      = (int)($_POST['id_tipo'] ?? 0);
      $id_depa      = (int)($_POST['id_depa'] ?? 0);
      $id_repleg    = (int)($_POST['id_repleg'] ?? 0);

      if ($nombre === '' || $razon_social === '' || $direccion === '') $errores[] = 'Todos los campos de texto son obligatorios.';
      if (!preg_match('/^\d{11}$/', $ruc)) $errores[] = 'RUC debe tener 11 dígitos.';
      if ($id_tipo <= 0 || $id_depa <= 0 || $id_repleg <= 0) $errores[] = 'Selecciona tipo, departamento y representante.';

      if (!$errores) {
        $sql = "INSERT INTO mtp_empresas(nombre, razon_social, ruc, direccion, id_tipo, id_depa, id_repleg)
                VALUES (?,?,?,?,?,?,?)";
        $st = db()->prepare($sql);
        $st->bind_param('ssssiii', $nombre, $razon_social, $ruc, $direccion, $id_tipo, $id_depa, $id_repleg);
        $st->execute();
        header('Location: registro.php?ok=empresa'); exit;
      }
    }

    if ($accion === 'nuevo_usuario_rol') {
      $usuario   = trim($_POST['usuario'] ?? '');
      $clave     = $_POST['clave'] ?? '';
      $nombres   = trim($_POST['nombres'] ?? '');
      $apellidos = trim($_POST['apellidos'] ?? '');
      $id_emp    = (int)($_POST['id_empresa'] ?? 0);
      $id_rol    = (int)($_POST['id_rol'] ?? 0);

      if (!preg_match('/^\d{8,11}$/', $usuario)) $errores[] = 'Usuario debe ser DNI (8) o CE (hasta 11) dígitos.';
      if (strlen($clave) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
      if ($nombres === '' || $apellidos === '') $errores[] = 'Nombres y apellidos son obligatorios.';
      if ($id_emp <= 0 || $id_rol <= 0) $errores[] = 'Selecciona empresa y rol.';

      if (!$errores) {
        // Insert usuario
        $hash = password_hash($clave, PASSWORD_BCRYPT);
        $sql = "INSERT INTO mtp_usuarios(usuario, clave, nombres, apellidos, id_empresa)
                VALUES (?,?,?,?,?)";
        $st = db()->prepare($sql);
        $st->bind_param('ssssi', $usuario, $hash, $nombres, $apellidos, $id_emp);
        $st->execute();
        $uid = db()->insert_id;

        // Asignar rol
        $sql2 = "INSERT INTO mtp_usuario_roles(id_usuario, id_rol) VALUES (?,?)";
        $st2 = db()->prepare($sql2);
        $st2->bind_param('ii', $uid, $id_rol);
        $st2->execute();

        header('Location: registro.php?ok=usuario'); exit;
      }
    }

  } catch (mysqli_sql_exception $e) {
    // Errores comunes: duplicados por UNIQUE, FK inválidas, etc.
    $errores[] = 'Error SQL: '.$e->getMessage();
  }
}

// --- Datos para selects ---
$roles   = db()->query("SELECT id, nombre FROM mtp_roles ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$depas   = db()->query("SELECT id, nombre FROM mtp_departamentos ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$tipos   = db()->query("SELECT id, nombre FROM mtp_tipos_empresas ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$repleg  = db()->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nom FROM mtp_representante_legal ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
$empresas= db()->query("SELECT id, nombre FROM mtp_empresas ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// --- Listados (cards) ---
$lstRep = db()->query("
  SELECT id, CONCAT(nombres,' ',apellidos) AS nombre, documento
  FROM mtp_representante_legal ORDER BY id DESC LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

$lstEmp = db()->query("
  SELECT e.id, e.nombre, e.ruc, te.nombre AS tipo, d.nombre AS depa
  FROM mtp_empresas e
  JOIN mtp_tipos_empresas te ON te.id = e.id_tipo
  JOIN mtp_departamentos d ON d.id = e.id_depa
  ORDER BY e.id DESC LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

$lstUsu = db()->query("
  SELECT u.id, u.usuario, CONCAT(u.nombres,' ',u.apellidos) AS nombre, e.nombre AS empresa,
         COALESCE(GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ', '), '—') AS roles
  FROM mtp_usuarios u
  JOIN mtp_empresas e ON e.id = u.id_empresa
  LEFT JOIN mtp_usuario_roles ur ON ur.id_usuario = u.id
  LEFT JOIN mtp_roles r ON r.id = ur.id_rol
  GROUP BY u.id, u.usuario, nombre, empresa
  ORDER BY u.id DESC
  LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

$okMsg = [
  'repleg'  => 'Representante legal creado correctamente.',
  'empresa' => 'Empresa creada correctamente.',
  'usuario' => 'Usuario y rol asignado correctamente.'
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro inicial | Sistema</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 m-0">Carga inicial (registro.php)</h1>
    <a class="btn btn-outline-secondary" href="index.php">Volver a inicio</a>
  </div>

  <?php if (isset($_GET['ok'], $okMsg[$_GET['ok']])): ?>
    <div class="alert alert-success"><?= h($okMsg[$_GET['ok']]) ?></div>
  <?php endif; ?>

  <?php if ($errores): ?>
    <div class="alert alert-danger">
      <strong>Revisa:</strong>
      <ul class="mb-0">
        <?php foreach ($errores as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Form: Representante legal -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h5">Nuevo representante legal</h2>
          <form method="post" class="mt-3" autocomplete="off">
            <input type="hidden" name="accion" value="nuevo_repleg">
            <div class="mb-2">
              <label class="form-label">Nombres</label>
              <input class="form-control" name="nombres" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Apellidos</label>
              <input class="form-control" name="apellidos" required>
            </div>
            <div class="mb-2">
              <label class="form-label">DNI (8 dígitos)</label>
              <input class="form-control" name="documento" maxlength="8" pattern="\d{8}" required>
            </div>
<div class="mb-3">
  <label class="form-label">Contraseña (visible, no cifrada)</label>
  <input type="text" class="form-control" name="clave" minlength="1" required>
  <div class="form-text">Se almacenará en texto plano para fines referenciales.</div>
</div>
            <button class="btn btn-primary w-100">Guardar</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Form: Empresa -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h5">Nueva empresa</h2>
          <form method="post" class="mt-3" autocomplete="off">
            <input type="hidden" name="accion" value="nueva_empresa">
            <div class="mb-2">
              <label class="form-label">Nombre comercial</label>
              <input class="form-control" name="nombre" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Razón social</label>
              <input class="form-control" name="razon_social" required>
            </div>
            <div class="mb-2">
              <label class="form-label">RUC (11 dígitos)</label>
              <input class="form-control" name="ruc" maxlength="11" pattern="\d{11}" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Dirección</label>
              <input class="form-control" name="direccion" required>
            </div>
            <div class="row">
              <div class="col-6 mb-2">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="id_tipo" required>
                  <option value="">Selecciona…</option>
                  <?php foreach ($tipos as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"><?= h($t['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 mb-2">
                <label class="form-label">Departamento</label>
                <select class="form-select" name="id_depa" required>
                  <option value="">Selecciona…</option>
                  <?php foreach ($depas as $d): ?>
                    <option value="<?= (int)$d['id'] ?>"><?= h($d['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Representante legal</label>
              <select class="form-select" name="id_repleg" required>
                <option value="">Selecciona…</option>
                <?php foreach ($repleg as $r): ?>
                  <option value="<?= (int)$r['id'] ?>"><?= h($r['nom']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button class="btn btn-primary w-100">Guardar</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Form: Usuario + Rol -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h5">Nuevo usuario + rol</h2>
          <form method="post" class="mt-3" autocomplete="off">
            <input type="hidden" name="accion" value="nuevo_usuario_rol">
            <div class="mb-2">
              <label class="form-label">Usuario (DNI/CE)</label>
              <input class="form-control" name="usuario" maxlength="11" pattern="\d{8,11}" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Contraseña</label>
              <input type="password" class="form-control" name="clave" minlength="6" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Nombres</label>
              <input class="form-control" name="nombres" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Apellidos</label>
              <input class="form-control" name="apellidos" required>
            </div>
            <div class="row">
              <div class="col-6 mb-2">
                <label class="form-label">Empresa</label>
                <select class="form-select" name="id_empresa" required>
                  <option value="">Selecciona…</option>
                  <?php foreach ($empresas as $e): ?>
                    <option value="<?= (int)$e['id'] ?>"><?= h($e['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 mb-3">
                <label class="form-label">Rol</label>
                <select class="form-select" name="id_rol" required>
                  <option value="">Selecciona…</option>
                  <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id'] ?>"><?= h($r['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <button class="btn btn-primary w-100">Guardar</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Listados -->
  <hr class="my-4">
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h3 class="h6 text-uppercase text-muted">Representantes (últimos)</h3>
          <?php foreach ($lstRep as $it): ?>
            <div class="border-bottom py-2 small">
              <div class="fw-semibold"><?= h($it['nombre']) ?></div>
              <div class="text-muted">DNI: <?= h($it['documento']) ?> · ID: <?= (int)$it['id'] ?></div>
            </div>
          <?php endforeach; if (!$lstRep): ?>
            <div class="text-muted">Sin registros aún.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h3 class="h6 text-uppercase text-muted">Empresas (últimas)</h3>
          <?php foreach ($lstEmp as $it): ?>
            <div class="border-bottom py-2 small">
              <div class="fw-semibold"><?= h($it['nombre']) ?></div>
              <div class="text-muted">RUC: <?= h($it['ruc']) ?> · <?= h($it['tipo']) ?> · <?= h($it['depa']) ?></div>
            </div>
          <?php endforeach; if (!$lstEmp): ?>
            <div class="text-muted">Sin registros aún.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h3 class="h6 text-uppercase text-muted">Usuarios (últimos)</h3>
          <?php foreach ($lstUsu as $it): ?>
            <div class="border-bottom py-2 small">
              <div class="fw-semibold"><?= h($it['nombre']) ?></div>
              <div class="text-muted">Usuario: <?= h($it['usuario']) ?> · Empresa: <?= h($it['empresa']) ?></div>
              <div class="text-muted">Roles: <?= h($it['roles']) ?></div>
            </div>
          <?php endforeach; if (!$lstUsu): ?>
            <div class="text-muted">Sin registros aún.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="alert alert-warning mt-4">
    <strong>Nota de seguridad:</strong> <code>registro.php</code> es solo para carga inicial en desarrollo. 
    En producción <strong>elimínalo</strong> o protégelo con autenticación.
  </div>
</div>
</body>
</html>
