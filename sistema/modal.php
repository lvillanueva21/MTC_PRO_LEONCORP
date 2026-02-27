
<?php
// modal.php — devuelve un FRAGMENTO HTML con:
// 1) Mensaje "Conectado... tabla ... registros" y
// 2) CRUD debajo (si la tabla existe).
require __DIR__ . '/includes/conexion.php'; // usa db(): mysqli

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
header('Content-Type: text/html; charset=utf-8');

$tabla = 'mtp_alumnos';
$dbName = '(desconocida)';
$tablaExiste = false;
$total = 0;

try {
  $rdb = db()->query("SELECT DATABASE() AS db");
  $dbName = $rdb->fetch_assoc()['db'] ?? $dbName;

  $st = db()->prepare("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
  $st->bind_param('s', $tabla);
  $st->execute();
  $tablaExiste = ((int)$st->get_result()->fetch_assoc()['c'] === 1);

  if ($tablaExiste) {
    $res2 = db()->query("SELECT COUNT(*) c FROM `{$tabla}`");
    $total = (int)$res2->fetch_assoc()['c'];
  }
} catch (Throwable $e) {
  // seguimos y mostramos mensaje genérico
}

// === ENDPOINTS AJAX (list/get/save/delete) ===
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === 'list') {
  if (!$tablaExiste) { exit; }
  $q = trim($_GET['q'] ?? '');
  if ($q !== '') {
    $like = "%$q%";
    $st = db()->prepare("SELECT id,nombres,apellidos,documento,email,telefono
                         FROM `{$tabla}`
                         WHERE nombres LIKE ? OR apellidos LIKE ? OR documento LIKE ? OR email LIKE ?
                         ORDER BY id DESC LIMIT 300");
    $st->bind_param('ssss',$like,$like,$like,$like);
    $st->execute();
    $rs = $st->get_result();
  } else {
    $rs = db()->query("SELECT id,nombres,apellidos,documento,email,telefono FROM `{$tabla}` ORDER BY id DESC LIMIT 300");
  }
  while ($row = $rs->fetch_assoc()) {
    $id=(int)$row['id'];
    echo '<tr id="row-'.$id.'">';
    echo '<td style="width:60px">'.$id.'</td>';
    echo '<td>'.h($row['nombres']).'</td>';
    echo '<td>'.h($row['apellidos']).'</td>';
    echo '<td>'.h($row['documento']).'</td>';
    echo '<td>'.h($row['email']).'</td>';
    echo '<td>'.h($row['telefono']).'</td>';
    echo '<td style="width:160px;white-space:nowrap">';
    echo '<button data-action="edit" data-id="'.$id.'">Editar</button> ';
    echo '<button data-action="delete" data-id="'.$id.'">Eliminar</button>';
    echo '</td></tr>';
  }
  exit;
}
if ($action === 'get') {
  header('Content-Type: application/json; charset=utf-8');
  $id = (int)($_GET['id'] ?? 0);
  $row = null;
  if ($tablaExiste && $id>0) {
    $st = db()->prepare("SELECT id,nombres,apellidos,documento,email,telefono FROM `{$tabla}` WHERE id=?");
    $st->bind_param('i',$id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
  }
  echo json_encode(['ok'=> (bool)$row, 'data'=>$row]); exit;
}
if ($action === 'save') {
  header('Content-Type: application/json; charset=utf-8');
  if (!$tablaExiste) { echo json_encode(['ok'=>false,'message'=>'La tabla no existe.']); exit; }
  $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $nombres   = trim($_POST['nombres']   ?? '');
  $apellidos = trim($_POST['apellidos'] ?? '');
  $documento = trim($_POST['documento'] ?? '');
  $email     = trim($_POST['email']     ?? '');
  $telefono  = trim($_POST['telefono']  ?? '');

  $errors = [];
  if ($nombres === '')   $errors['nombres']   = 'Requerido';
  if ($apellidos === '') $errors['apellidos'] = 'Requerido';
  if ($documento === '') $errors['documento'] = 'Requerido';
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email inválido';
  if ($errors) { echo json_encode(['ok'=>false,'errors'=>$errors]); exit; }

  try {
    if ($id > 0) {
      $st = db()->prepare("UPDATE `{$tabla}` SET nombres=?, apellidos=?, documento=?, email=?, telefono=? WHERE id=?");
      $st->bind_param('sssssi',$nombres,$apellidos,$documento,$email,$telefono,$id);
      $st->execute();
    } else {
      $st = db()->prepare("INSERT INTO `{$tabla}`(nombres,apellidos,documento,email,telefono) VALUES (?,?,?,?,?)");
      $st->bind_param('sssss',$nombres,$apellidos,$documento,$email,$telefono);
      $st->execute();
      $id = (int)db()->insert_id;
    }
    echo json_encode(['ok'=>true,'id'=>$id]); exit;
  } catch (mysqli_sql_exception $e) {
    $msg = ($e->getCode() === 1062) ? 'Documento duplicado.' : 'Error de BD.';
    echo json_encode(['ok'=>false,'message'=>$msg]); exit;
  }
}
if ($action === 'delete') {
  header('Content-Type: application/json; charset=utf-8');
  if (!$tablaExiste) { echo json_encode(['ok'=>false]); exit; }
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) { echo json_encode(['ok'=>false]); exit; }
  $st = db()->prepare("DELETE FROM `{$tabla}` WHERE id=?");
  $st->bind_param('i',$id);
  $st->execute();
  echo json_encode(['ok'=>true]); exit;
}

// === VISTA (fragmento HTML) ===
?>
<!-- Mensaje de conexión -->
<p>
  <?php if ($tablaExiste): ?>
    Conectado correctamente  a la bd <?= h($dbName) ?> y se ha logrado demostrar la existencia de la tabla <?= h($tabla) ?> que actualmente tiene <?= (int)$total ?> .
  <?php else: ?>
    Conectado correctamente  a la bd <?= h($dbName) ?> pero <strong>NO existe</strong> la tabla <?= h($tabla) ?>.
  <?php endif; ?>
</p>

<?php if ($tablaExiste): ?>
<!-- CRUD (solo si la tabla existe) -->
<div id="crudWrap" style="display:grid; grid-template-columns: 1fr; gap: 10px;">

  <!-- Filtro -->
  <div style="display:grid; grid-template-columns: 1fr auto auto; gap:8px; align-items:end;">
    <div>
      <label>Buscar</label>
      <input id="q" type="text" placeholder="Nombre, DNI, email" style="width:100%">
    </div>
    <button id="btnBuscar">Buscar</button>
    <button id="btnLimpiar">Limpiar</button>
  </div>

  <!-- Lista -->
  <div>
    <table border="1" cellpadding="6" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th style="width:60px">ID</th>
          <th>Nombres</th>
          <th>Apellidos</th>
          <th>Documento</th>
          <th>Email</th>
          <th>Teléfono</th>
          <th style="width:160px">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody"></tbody>
    </table>
  </div>

  <!-- Form Crear/Editar -->
  <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px;">
    <div style="grid-column: 1 / -1;"><strong id="tForm">Nuevo alumno</strong></div>

    <input type="hidden" id="id">

    <div>
      <label>Nombres</label>
      <input id="nombres" type="text" style="width:100%">
      <div id="e_nombres" style="color:red"></div>
    </div>
    <div>
      <label>Apellidos</label>
      <input id="apellidos" type="text" style="width:100%">
      <div id="e_apellidos" style="color:red"></div>
    </div>

    <div>
      <label>Documento</label>
      <input id="documento" type="text" style="width:100%">
      <div id="e_documento" style="color:red"></div>
    </div>
    <div>
      <label>Email</label>
      <input id="email" type="text" style="width:100%">
      <div id="e_email" style="color:red"></div>
    </div>

    <div>
      <label>Teléfono</label>
      <input id="telefono" type="text" style="width:100%">
    </div>
    <div>&nbsp;</div>

    <div style="grid-column: 1 / -1; display:flex; gap:8px;">
      <button id="guardar">Guardar</button>
      <button id="nuevo">Nuevo</button>
      <div id="msg" style="color:crimson; margin-left:8px;"></div>
    </div>
  </div>
</div>
<?php endif; ?>
