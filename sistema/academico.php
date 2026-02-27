<?php
// academico.php — Panel académico (fragmento HTML) + endpoints AJAX
require __DIR__ . '/includes/conexion.php'; // usa tu db(): mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
header('Content-Type: text/html; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ===================== ENDPOINTS ===================== */

/* --- Cursos: tabla HTML --- */
if ($action === 'cursos_table') {
  $q   = trim($_GET['q'] ?? '');
  $sql = "SELECT id,codigo,nombre,descripcion,activo FROM mtp_cursos ";
  if ($q !== '') {
    $sql .= "WHERE codigo LIKE ? OR nombre LIKE ? ";
    $sql .= "ORDER BY activo DESC, id DESC LIMIT 200";
    $like = "%$q%";
    $st = db()->prepare($sql);
    $st->bind_param('ss', $like, $like);
    $st->execute();
    $rs = $st->get_result();
  } else {
    $sql .= "ORDER BY activo DESC, id DESC LIMIT 200";
    $rs = db()->query($sql);
  }
  while ($r = $rs->fetch_assoc()) {
    $id=(int)$r['id'];
    echo '<tr>';
    echo '<td style="width:70px">'.$id.'</td>';
    echo '<td>'.h($r['codigo']).'</td>';
    echo '<td>'.h($r['nombre']).'</td>';
    echo '<td>'.h($r['descripcion']).'</td>';
    echo '<td>'.((int)$r['activo']===1?'Sí':'No').'</td>';
    echo '<td style="width:160px;white-space:nowrap">';
    echo '<button data-act="edit" data-id="'.$id.'">Editar</button> ';
    echo '<button data-act="del"  data-id="'.$id.'">Eliminar</button>';
    echo '</td></tr>';
  }
  exit;
}

/* --- Cursos: opciones <option> --- */
if ($action === 'cursos_options') {
  $rs = db()->query("SELECT id, CONCAT('[',codigo,'] ',nombre) AS t FROM mtp_cursos WHERE activo=1 ORDER BY nombre");
  echo '<option value="">Selecciona curso…</option>';
  while ($r = $rs->fetch_assoc()) {
    echo '<option value="'.(int)$r['id'].'">'.h($r['t']).'</option>';
  }
  exit;
}

/* --- Curso: obtener uno (JSON) --- */
if ($action === 'curso_get') {
  header('Content-Type: application/json; charset=utf-8');
  $id = (int)($_GET['id'] ?? 0);
  $st = db()->prepare("SELECT id,codigo,nombre,descripcion,activo FROM mtp_cursos WHERE id=?");
  $st->bind_param('i',$id);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  echo json_encode(['ok'=>(bool)$row,'data'=>$row]); exit;
}

/* --- Curso: guardar (INSERT/UPDATE) --- */
if ($action === 'curso_save') {
  header('Content-Type: application/json; charset=utf-8');
  $id   = (int)($_POST['id'] ?? 0);
  $cod  = trim($_POST['codigo'] ?? '');
  $nom  = trim($_POST['nombre'] ?? '');
  $des  = trim($_POST['descripcion'] ?? '');
  $act  = (int)(($_POST['activo'] ?? '1') === '1');

  if ($cod==='' || $nom==='') { echo json_encode(['ok'=>false,'message'=>'Código y nombre son obligatorios.']); exit; }

  try {
    if ($id > 0) {
      $st = db()->prepare("UPDATE mtp_cursos SET codigo=?, nombre=?, descripcion=?, activo=? WHERE id=?");
      $st->bind_param('sssii',$cod,$nom,$des,$act,$id);
      $st->execute();
    } else {
      $st = db()->prepare("INSERT INTO mtp_cursos(codigo,nombre,descripcion,activo) VALUES (?,?,?,?)");
      $st->bind_param('sssi',$cod,$nom,$des,$act);
      $st->execute();
      $id = (int)db()->insert_id;
    }
    echo json_encode(['ok'=>true,'id'=>$id]); exit;
  } catch (mysqli_sql_exception $e) {
    $msg = ($e->getCode()===1062) ? 'Código de curso duplicado.' : 'Error guardando curso.';
    echo json_encode(['ok'=>false,'message'=>$msg]); exit;
  }
}

/* --- Curso: eliminar --- */
if ($action === 'curso_del') {
  header('Content-Type: application/json; charset=utf-8');
  $id = (int)($_POST['id'] ?? 0);
  if ($id<=0) { echo json_encode(['ok'=>false]); exit; }
  // Bloqueo básico: no permitir borrar si hay matrículas
  $st = db()->prepare("SELECT COUNT(*) c FROM mtp_matriculas WHERE id_curso=?");
  $st->bind_param('i',$id); $st->execute();
  $c = (int)$st->get_result()->fetch_assoc()['c'];
  if ($c>0) { echo json_encode(['ok'=>false,'message'=>'Hay matrículas asociadas.']); exit; }
  $st = db()->prepare("DELETE FROM mtp_cursos WHERE id=?");
  $st->bind_param('i',$id); $st->execute();
  echo json_encode(['ok'=>true]); exit;
}

/* --- Buscar alumnos (JSON) --- */
if ($action === 'alumno_find') {
  header('Content-Type: application/json; charset=utf-8');
  $q = trim($_GET['q'] ?? '');
  if ($q===''){ echo json_encode([]); exit; }
  $like = "%$q%";
  $st = db()->prepare("SELECT id, nombres, apellidos, documento
                       FROM mtp_alumnos
                       WHERE documento LIKE ? OR nombres LIKE ? OR apellidos LIKE ?
                       ORDER BY id DESC LIMIT 20");
  $st->bind_param('sss',$like,$like,$like);
  $st->execute();
  $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  echo json_encode($rows); exit;
}

/* --- Matricular alumno en curso (crea matrícula y nota) --- */
if ($action === 'matricular') {
  header('Content-Type: application/json; charset=utf-8');
  $aid = (int)($_POST['alumno_id'] ?? 0);
  $cid = (int)($_POST['curso_id'] ?? 0);
  if ($aid<=0 || $cid<=0) { echo json_encode(['ok'=>false,'message'=>'Faltan datos.']); exit; }

  db()->begin_transaction();
  try {
    // upsert simple por UNIQUE (id_alumno,id_curso)
    $st = db()->prepare("INSERT INTO mtp_matriculas(id_alumno,id_curso,estado) VALUES (?,?, 'Activo')
                         ON DUPLICATE KEY UPDATE actualizado = CURRENT_TIMESTAMP");
    $st->bind_param('ii',$aid,$cid);
    $st->execute();

    // Obtener ID matrícula (insert recién hecho o existente)
    $st2 = db()->prepare("SELECT id FROM mtp_matriculas WHERE id_alumno=? AND id_curso=?");
    $st2->bind_param('ii',$aid,$cid);
    $st2->execute();
    $mid = (int)$st2->get_result()->fetch_assoc()['id'];

    // Asegurar fila en notas (una por matrícula)
    $st3 = db()->prepare("INSERT IGNORE INTO mtp_notas(id_matricula,n1,n2,n3) VALUES (?,NULL,NULL,NULL)");
    $st3->bind_param('i',$mid);
    $st3->execute();

    db()->commit();
    echo json_encode(['ok'=>true,'matricula_id'=>$mid]); exit;
  } catch (mysqli_sql_exception $e) {
    db()->rollback();
    echo json_encode(['ok'=>false,'message'=>'No se pudo matricular.']); exit;
  }
}

/* --- Tabla de matrículas para un alumno (HTML) --- */
if ($action === 'matriculas_table') {
  $aid = (int)($_GET['alumno_id'] ?? 0);
  if ($aid<=0) { exit; }
  $sql = "SELECT m.id, c.codigo, c.nombre,
                 n.n1, n.n2, n.n3,
                 ROUND((COALESCE(n.n1,0)+COALESCE(n.n2,0)+COALESCE(n.n3,0))/3,2) AS prom
          FROM mtp_matriculas m
          JOIN mtp_cursos c  ON c.id = m.id_curso
          LEFT JOIN mtp_notas n ON n.id_matricula = m.id
          WHERE m.id_alumno = ?
          ORDER BY m.id DESC";
  $st = db()->prepare($sql);
  $st->bind_param('i',$aid);
  $st->execute();
  $rs = $st->get_result();
  while ($r = $rs->fetch_assoc()) {
    $id=(int)$r['id'];
    echo '<tr>';
    echo '<td style="width:80px">'.$id.'</td>';
    echo '<td>'.h('['.$r['codigo'].'] '.$r['nombre']).'</td>';
    echo '<td><input data-f="n1" type="number" step="0.01" min="0" max="20" value="'.h($r['n1']).'" style="width:90px"></td>';
    echo '<td><input data-f="n2" type="number" step="0.01" min="0" max="20" value="'.h($r['n2']).'" style="width:90px"></td>';
    echo '<td><input data-f="n3" type="number" step="0.01" min="0" max="20" value="'.h($r['n3']).'" style="width:90px"></td>';
    echo '<td data-f="prom" style="width:90px">'.h($r['prom']).'</td>';
    echo '<td style="width:180px;white-space:nowrap">';
    echo '<button data-act="save" data-id="'.$id.'">Guardar</button> ';
    echo '<button data-act="del"  data-id="'.$id.'">Eliminar</button>';
    echo '</td></tr>';
  }
  exit;
}

/* --- Guardar notas (n1,n2,n3) --- */
if ($action === 'nota_save') {
  header('Content-Type: application/json; charset=utf-8');
  $mid = (int)($_POST['matricula_id'] ?? 0);
  if ($mid<=0) { echo json_encode(['ok'=>false]); exit; }

  $fields = ['n1','n2','n3'];
  $vals = [];
  foreach ($fields as $f){
    if (array_key_exists($f, $_POST)) {
      $v = trim((string)$_POST[$f]);
      if ($v === '') $vals[$f] = null;
      else {
        $n = (float)$v;
        if ($n < 0 || $n > 20) { echo json_encode(['ok'=>false,'message'=>'Notas fuera de rango (0-20).']); exit; }
        $vals[$f] = $n;
      }
    }
  }
  if (!$vals) { echo json_encode(['ok'=>false]); exit; }

  // Build dinámico seguro
  $set = [];
  $types=''; $params=[];
  foreach ($vals as $k=>$v){
    $set[] = "$k=?";
    if ($v === null){ $types.='s'; $params[] = null; }  // será tratado abajo
    else { $types.='d'; $params[] = $v; }
  }
  $sql = "UPDATE mtp_notas SET ".implode(',', $set)." WHERE id_matricula=?";
  $types .= 'i'; $params[] = $mid;

  $st = db()->prepare($sql);

  // bind_param no acepta null directo: normaliza con references
  $bind = [$types];
  foreach ($params as $i=>$p){ $bind[] = &$params[$i]; }
  call_user_func_array([$st,'bind_param'], $bind);
  $st->execute();

  // devolver nuevo promedio
  $r = db()->query("SELECT ROUND((COALESCE(n1,0)+COALESCE(n2,0)+COALESCE(n3,0))/3,2) prom FROM mtp_notas WHERE id_matricula=".$mid)->fetch_assoc();
  echo json_encode(['ok'=>true,'promedio'=>$r['prom']]); exit;
}

/* --- Eliminar matrícula (+ notas en cascada) --- */
if ($action === 'matricula_del') {
  header('Content-Type: application/json; charset=utf-8');
  $id = (int)($_POST['id'] ?? 0);
  if ($id<=0){ echo json_encode(['ok'=>false]); exit; }
  $st = db()->prepare("DELETE FROM mtp_matriculas WHERE id=?");
  $st->bind_param('i',$id);
  $st->execute();
  echo json_encode(['ok'=>true]); exit;
}

/* ===================== VISTA (FRAGMENTO HTML) ===================== */

# Ping de estado (bonito y útil)
$dbName = db()->query("SELECT DATABASE() db")->fetch_assoc()['db'] ?? '(desconocida)';
$alumC  = (int)db()->query("SELECT COUNT(*) c FROM mtp_alumnos")->fetch_assoc()['c'];
$curC   = (int)db()->query("SELECT COUNT(*) c FROM mtp_cursos")->fetch_assoc()['c'];
$matC   = (int)db()->query("SELECT COUNT(*) c FROM mtp_matriculas")->fetch_assoc()['c'];
?>
<!-- Estado -->
<div style="padding:10px;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:12px;background:#f8fafc">
  Conectado a <strong><?= h($dbName) ?></strong>. Alumnos: <strong><?= $alumC ?></strong> ·
  Cursos: <strong><?= $curC ?></strong> · Matrículas: <strong><?= $matC ?></strong>.
</div>

<!-- GRID: 2 columnas -->
<div style="display:grid;grid-template-columns:1.15fr 1fr;gap:16px">

  <!-- CURSOS -->
  <section style="border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:#fff">
    <h3 style="margin:0 0 8px">Cursos</h3>

    <!-- Filtro -->
    <div style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:end;margin-bottom:8px">
      <div>
        <label>Buscar (código/nombre)</label>
        <input id="c_q" style="width:100%" placeholder="p. ej. A1, Conducción">
      </div>
      <div><small class="text-muted">Se actualiza al escribir</small></div>
    </div>

    <!-- Tabla -->
    <div style="overflow:auto;max-height:260px;border:1px solid #eee;border-radius:8px">
      <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
          <tr>
            <th style="width:70px">ID</th>
            <th>Código</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Activo</th>
            <th style="width:160px">Acciones</th>
          </tr>
        </thead>
        <tbody id="c_tbody"></tbody>
      </table>
    </div>

    <!-- Form -->
    <div style="margin-top:10px;display:grid;grid-template-columns:repeat(2,1fr);gap:10px">
      <input type="hidden" id="c_id">
      <div>
        <label>Código</label>
        <input id="c_codigo" style="width:100%" maxlength="20">
      </div>
      <div>
        <label>Nombre</label>
        <input id="c_nombre" style="width:100%" maxlength="120">
      </div>
      <div style="grid-column:1 / -1">
        <label>Descripción</label>
        <input id="c_desc" style="width:100%" maxlength="255">
      </div>
      <div>
        <label><input type="checkbox" id="c_activo" checked> Activo</label>
      </div>
      <div style="grid-column:1 / -1;display:flex;gap:8px;align-items:center">
        <button id="c_guardar">Guardar</button>
        <button id="c_nuevo" type="button">Nuevo</button>
        <span id="c_msg" style="color:#b91c1c"></span>
      </div>
    </div>
  </section>

  <!-- MATRÍCULAS / NOTAS -->
  <section style="border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:#fff">
    <h3 style="margin:0 0 8px">Matrículas y Notas</h3>

    <!-- Selección de alumno + curso -->
    <div style="display:grid;grid-template-columns:1fr;gap:8px;margin-bottom:8px">
      <div>
        <label>Buscar alumno (DNI/Nombre)</label>
        <input id="m_qalumno" style="width:100%" placeholder="70379752, Sara, etc.">
        <div id="m_alumnos" style="display:grid;gap:6px;margin-top:6px"></div>
      </div>
      <div>
        <small>Alumno seleccionado:</small>
        <div id="m_alumno_sel" style="font-weight:700;min-height:20px">—</div>
        <input type="hidden" id="m_alumno_id">
      </div>
      <div>
        <label>Curso</label>
        <select id="m_curso" style="width:100%"><option>Cargando…</option></select>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <button id="m_matricular">Matricular</button>
        <span id="m_msg" style="color:#b91c1c"></span>
      </div>
    </div>

    <!-- Tabla matrículas + notas -->
    <div style="overflow:auto;max-height:320px;border:1px solid #eee;border-radius:8px">
      <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
          <tr>
            <th style="width:80px">ID</th>
            <th>Curso</th>
            <th>N1</th><th>N2</th><th>N3</th>
            <th>Prom.</th>
            <th style="width:180px">Acciones</th>
          </tr>
        </thead>
        <tbody id="m_tbody"></tbody>
      </table>
    </div>
  </section>

</div>
