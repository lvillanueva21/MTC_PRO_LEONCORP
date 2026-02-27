<?php
// modules/consola/precios/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$mysqli = db();
$mysqli->set_charset('utf8mb4');

function jerror($code, $msg, $extra = []) {
  http_response_code($code);
  echo json_encode(['ok'=>false, 'msg'=>$msg] + $extra); exit;
}
function jok($arr = []) { echo json_encode(['ok'=>true] + $arr); exit; }

function dec($s) {
  // admite "1.234,56", "1234.56", "1234"
  $s = trim((string)$s);
  $s = str_replace([' ', ','], ['', '.'], $s);
  if (!is_numeric($s)) return null;
  return round((float)$s, 2);
}

// Crea los 5 slots A..E si faltan (A=principal por defecto)
function ensure_price_slots(mysqli $db, int $empresa_id, int $servicio_id) {
  $sql = "INSERT IGNORE INTO mod_precios (empresa_id,servicio_id,rol,precio,activo,nota,es_principal)
          VALUES
          (?,?,?,?,?,?,?);";
  $st = $db->prepare($sql);

  // A (principal)
  $rol = 'A'; $precio=1.00; $activo=1; $nota=null; $principal=1;
  $st->bind_param('iisdisi', $empresa_id,$servicio_id,$rol,$precio,$activo,$nota,$principal);
  $st->execute();

  // B..E (inactivos, 0)
  foreach (['B','C','D','E'] as $rol) {
    $precio=0.00; $activo=0; $nota=null; $principal=0;
    $st->bind_param('iisdisi', $empresa_id,$servicio_id,$rol,$precio,$activo,$nota,$principal);
    $st->execute();
  }
}

// Devuelve filas A..E con principal al inicio
function list_prices(mysqli $db, int $empresa_id, int $servicio_id, ?string $estado='') {
  ensure_price_slots($db, $empresa_id, $servicio_id);

  $where = "empresa_id=? AND servicio_id=?";
  $types = 'ii'; $pars = [$empresa_id, $servicio_id];
  if ($estado === '1' || $estado === '0') { $where .= " AND activo=?"; $types.='i'; $pars[]=(int)$estado; }

  // orden: principal primero, luego por rol A..E
  $sql = "SELECT id, empresa_id, servicio_id, rol, precio, activo, nota, es_principal
          FROM mod_precios
          WHERE $where
          ORDER BY (es_principal=1) DESC, FIELD(rol,'A','B','C','D','E')";
  $st = $db->prepare($sql);
  $st->bind_param($types, ...$pars);
  $st->execute();
  $rs = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  return $rs;
}

try {
  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {

    // Combo de empresas (para tu panel superior)
    case 'empresas': {
      $rs = $mysqli->query("SELECT id,nombre FROM mtp_empresas ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    // Listar servicios (panel izquierdo) — puedes ajustar filtros si quieres
    case 'servicios_empresa': {
      $empresa_id = (int)($_GET['empresa_id'] ?? 0);
      if ($empresa_id <= 0) jerror(400,'Empresa requerida');

      $q      = trim($_GET['q'] ?? '');
      $estado = $_GET['estado'] ?? ''; // '' | '1' | '0'
      $where  = [];
      $types  = '';
      $pars   = [];
      if ($q !== '') { $like="%$q%"; $where[]="(s.nombre LIKE ? OR s.descripcion LIKE ?)"; $types.='ss'; $pars[]=$like; $pars[]=$like; }
      if ($estado==='1'||$estado==='0'){ $where[]="s.activo=?"; $types.='i'; $pars[]=(int)$estado; }
      $W = $where ? 'WHERE '.implode(' AND ',$where) : '';

      $sql = "SELECT s.id, s.nombre, s.activo
              FROM mod_servicios s
              $W
              ORDER BY s.nombre";
      $st = $mysqli->prepare($sql);
      if ($types) $st->bind_param($types, ...$pars);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data'=>$rows]);
    }

    // Listar precios de un servicio para una empresa (panel derecho)
    case 'precios_list': {
      $empresa_id  = (int)($_GET['empresa_id'] ?? 0);
      $servicio_id = (int)($_GET['servicio_id'] ?? 0);
      if ($empresa_id<=0 || $servicio_id<=0) jerror(400,'Parámetros requeridos');

      $estado = $_GET['estado'] ?? '';
      $rows = list_prices($mysqli, $empresa_id, $servicio_id, $estado);
      jok(['data'=>$rows]);
    }

    // Editar precio + nota (solo esos dos campos)
    case 'precio_update': {
      $id = (int)($_POST['id'] ?? 0);
      $precio = dec($_POST['precio'] ?? '');
      $nota   = trim($_POST['nota'] ?? '');

      if ($id<=0 || $precio===null || $precio<0) jerror(400,'Datos inválidos');

      $st = $mysqli->prepare("UPDATE mod_precios SET precio=?, nota=? WHERE id=?");
      $nota = ($nota==='')? null: $nota;
      $st->bind_param('dsi', $precio, $nota, $id);
      $st->execute();
      jok(['id'=>$id,'precio'=>$precio,'nota'=>$nota]);
    }

    // Activar/Desactivar. Si desactivas el principal: B pasa a principal,
    // se activa y si su precio fuese 0, se pone en 1.00
    case 'precio_toggle': {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');

      $r = $mysqli->query("SELECT * FROM mod_precios WHERE id={$id}")->fetch_assoc();
      if (!$r) jerror(404,'No encontrado');

      $nuevo = $r['activo'] ? 0 : 1;

      $mysqli->begin_transaction();

      // Si apagan al principal -> B pasa a principal
      if ($r['es_principal'] && $nuevo===0) {
        $emp = (int)$r['empresa_id']; $srv = (int)$r['servicio_id'];

        // asegurar slots
        ensure_price_slots($mysqli, $emp, $srv);

        // fila B
        $rb = $mysqli->query("SELECT * FROM mod_precios WHERE empresa_id={$emp} AND servicio_id={$srv} AND rol='B'")->fetch_assoc();
        if (!$rb) jerror(500,'No existe slot B');

        // apagar actual
        $st = $mysqli->prepare("UPDATE mod_precios SET activo=0, es_principal=0 WHERE id=?");
        $st->bind_param('i', $id); $st->execute();

        // promover B
        $precioB = (float)$rb['precio'];
        if ($precioB <= 0) $precioB = 1.00;

        $st = $mysqli->prepare("UPDATE mod_precios SET activo=1, es_principal=1, precio=? WHERE id=?");
        $st->bind_param('di', $precioB, $rb['id']); $st->execute();

        $mysqli->commit();
        jok(['id'=>$id,'activo'=>0,'principal_nuevo'=>$rb['id']]);
      }

      // caso normal: solo toggle
      $st = $mysqli->prepare("UPDATE mod_precios SET activo=? WHERE id=?");
      $st->bind_param('ii', $nuevo, $id);
      $st->execute();
      $mysqli->commit();
      jok(['id'=>$id,'activo'=>$nuevo]);
    }

    // Convertir un slot en Principal. Regla:
    // - La fila elegida queda es_principal=1 y activa=1 (si precio=0 -> 1.00)
    // - La fila que era principal deja de serlo (es_principal=0)
    // - (Opcional swap de roles) dejamos los roles tal cual (A..E), el label "Principal"
    //   se pinta por es_principal. Si prefieres swap A<->(B/C/D/E), descomenta el bloque swap.
    case 'precio_set_principal': {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');

      $r = $mysqli->query("SELECT * FROM mod_precios WHERE id={$id}")->fetch_assoc();
      if (!$r) jerror(404,'No encontrado');

      $emp = (int)$r['empresa_id']; $srv = (int)$r['servicio_id'];

      $mysqli->begin_transaction();

      // desmarcar al actual principal
      $mysqli->query("UPDATE mod_precios SET es_principal=0 WHERE empresa_id={$emp} AND servicio_id={$srv} AND es_principal=1");

      // asegurar activo + precio mínimo 1.00 si era 0
      $nuevoPrecio = max( (float)$r['precio'], 1.00 );
      $st = $mysqli->prepare("UPDATE mod_precios SET es_principal=1, activo=1, precio=? WHERE id=?");
      $st->bind_param('di', $nuevoPrecio, $id);
      $st->execute();

      /*  --- SWAP DE ROLES CON A (opcional) ---
      if ($r['rol'] !== 'A') {
        // rol del nuevo principal
        $rolNuevo = $r['rol'];
        // fila que tiene rol A (puede ser la misma que era principal antes)
        $ra = $mysqli->query("SELECT id FROM mod_precios WHERE empresa_id={$emp} AND servicio_id={$srv} AND rol='A'")->fetch_assoc();
        if ($ra && (int)$ra['id'] !== $id) {
          // intercambiar roles
          $mysqli->query("UPDATE mod_precios SET rol='Z' WHERE id={$ra['id']}");
          $mysqli->query("UPDATE mod_precios SET rol='A' WHERE id={$id}");
          $mysqli->query("UPDATE mod_precios SET rol='{$rolNuevo}' WHERE id={$ra['id']}");
        }
      }
      */

      $mysqli->commit();
      jok(['id'=>$id]);
    }

    default: jerror(400,'Acción no válida');
  }

} catch (mysqli_sql_exception $e) {
  if ($mysqli->errno) $mysqli->rollback();
  jerror(500, 'Error del servidor', ['dev'=>$e->getMessage()]);
}
