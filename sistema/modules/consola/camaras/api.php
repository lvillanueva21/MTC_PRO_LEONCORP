<?php
// modules/consola/camaras/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = db();
$mysqli->set_charset('utf8mb4');

function jerror($code, $msg, $extra = []) {
  http_response_code($code);
  echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit;
}
function jok($arr = []) { echo json_encode(['ok'=>true]+$arr); exit; }

try {
  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    // -------- Empresas (combo)
    case 'empresas': {
      $rs = $mysqli->query("SELECT id,nombre FROM mtp_empresas ORDER BY nombre");
      jok(['data'=>$rs->fetch_all(MYSQLI_ASSOC)]);
    }

    // -------- DVR: obtener 1:1 por empresa
    case 'dvr_get': {
      $empresa_id = (int)($_GET['empresa_id'] ?? 0);
      if ($empresa_id<=0) jerror(400,'Empresa requerida');
      $st = $mysqli->prepare("
        SELECT d.id, d.principal_usuario, d.principal_clave, d.sutran_usuario, d.sutran_clave,
               d.link_remoto, d.link_local, d.id_disco_actual, d.total_camaras
        FROM cm_dvr d
        JOIN cm_dvr_empresa de ON de.id_dvr=d.id
        WHERE de.id_empresa=?
        LIMIT 1
      ");
      $st->bind_param('i', $empresa_id);
      $st->execute();
      $row = $st->get_result()->fetch_assoc();
      jok(['data'=>$row ?: null]);
    }

    // -------- DVR: crear/actualizar (upsert) 1:1 por empresa
    case 'dvr_save': {
      $id          = (int)($_POST['id'] ?? 0);
      $empresa_id  = (int)($_POST['empresa_id'] ?? 0);
      $u  = trim($_POST['principal_usuario'] ?? '');
      $p  = trim($_POST['principal_clave'] ?? '');
      $su = trim($_POST['sutran_usuario'] ?? '');
      $sp = trim($_POST['sutran_clave'] ?? '');
      $rl = trim($_POST['link_remoto'] ?? '');
      $ll = trim($_POST['link_local'] ?? '');
      $cams = (int)($_POST['total_camaras'] ?? 0);

      if ($empresa_id<=0 || $u==='' || $p==='') jerror(400,'Datos obligatorios faltantes');

      $mysqli->begin_transaction();

      if ($id>0) {
        // actualizar DVR existente
        $st = $mysqli->prepare("UPDATE cm_dvr SET principal_usuario=?, principal_clave=?, sutran_usuario=?, sutran_clave=?, link_remoto=?, link_local=?, total_camaras=? WHERE id=?");
        $st->bind_param('ssssssii', $u,$p,$su,$sp,$rl,$ll,$cams,$id);
        $st->execute();

        // asegurar vínculo (empresa -> dvr único)
        $st2 = $mysqli->prepare("INSERT INTO cm_dvr_empresa (id_dvr,id_empresa) VALUES (?,?) ON DUPLICATE KEY UPDATE id_empresa=VALUES(id_empresa)");
        $st2->bind_param('ii', $id, $empresa_id);
        $st2->execute();
        $dvr_id = $id;
      } else {
        // si empresa ya tiene DVR, lo actualizamos en vez de crear uno nuevo
        $stx = $mysqli->prepare("SELECT d.id FROM cm_dvr d JOIN cm_dvr_empresa de ON de.id_dvr=d.id WHERE de.id_empresa=? LIMIT 1");
        $stx->bind_param('i', $empresa_id);
        $stx->execute();
        $got = $stx->get_result()->fetch_assoc();

        if ($got) {
          $dvr_id = (int)$got['id'];
          $st = $mysqli->prepare("UPDATE cm_dvr SET principal_usuario=?, principal_clave=?, sutran_usuario=?, sutran_clave=?, link_remoto=?, link_local=?, total_camaras=? WHERE id=?");
          $st->bind_param('ssssssii', $u,$p,$su,$sp,$rl,$ll,$cams,$dvr_id);
          $st->execute();
        } else {
          $st = $mysqli->prepare("INSERT INTO cm_dvr (principal_usuario,principal_clave,sutran_usuario,sutran_clave,link_remoto,link_local,total_camaras) VALUES (?,?,?,?,?,?,?)");
          $st->bind_param('ssssssi', $u,$p,$su,$sp,$rl,$ll,$cams);
          $st->execute();
          $dvr_id = (int)$mysqli->insert_id;

          $st2 = $mysqli->prepare("INSERT INTO cm_dvr_empresa (id_dvr,id_empresa) VALUES (?,?)");
          $st2->bind_param('ii', $dvr_id, $empresa_id);
          $st2->execute();
        }
      }

      $mysqli->commit();
      jok(['id'=>$dvr_id]);
    }

    // -------- Discos: listar con paginación
    case 'discos_list': {
      $page = max(1,(int)($_GET['page']??1));
      $per  = max(1, min(50, (int)($_GET['per_page']??10)));
      $off  = ($page-1)*$per;

      $c = $mysqli->query("SELECT COUNT(*) c FROM cm_discos")->fetch_assoc()['c'] ?? 0;

      $st = $mysqli->prepare("SELECT id, disco_total, disco_restante, DATE_FORMAT(ultimo_cambio,'%Y-%m-%d %H:%i:%s') AS ultimo_cambio FROM cm_discos ORDER BY id DESC LIMIT ? OFFSET ?");
      $st->bind_param('ii', $per, $off); $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      jok(['data'=>$rows,'total'=>(int)$c,'page'=>$page,'per_page'=>$per]);
    }

    // -------- Discos: crear / actualizar / eliminar
    case 'disco_create': {
      $tot = (int)($_POST['disco_total'] ?? 0);
      if ($tot<=0) jerror(400,'Capacidad total requerida');
      $rest = ($_POST['disco_restante']==='' ? null : (int)$_POST['disco_restante']);
      $uc   = trim($_POST['ultimo_cambio'] ?? '');
      $st = $mysqli->prepare("INSERT INTO cm_discos (disco_total,disco_restante,ultimo_cambio) VALUES (?,?, NULLIF(?,''))");
      $st->bind_param('iis', $tot, $rest, $uc);
      $st->execute();
      jok(['id'=>(int)$mysqli->insert_id]);
    }

    case 'disco_update': {
      $id  = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      $tot = (int)($_POST['disco_total'] ?? 0);
      if ($tot<=0) jerror(400,'Capacidad total requerida');
      $rest = ($_POST['disco_restante']==='' ? null : (int)$_POST['disco_restante']);
      $uc   = trim($_POST['ultimo_cambio'] ?? '');
      $st = $mysqli->prepare("UPDATE cm_discos SET disco_total=?, disco_restante=?, ultimo_cambio=NULLIF(?, '') WHERE id=?");
      $st->bind_param('iisi', $tot, $rest, $uc, $id);
      $st->execute();
      jok(['id'=>$id]);
    }

    case 'disco_delete': {
      $id  = (int)($_POST['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      // Si el disco está como actual en algún DVR, el FK lo permite (SET NULL) o está en historial
      $st = $mysqli->prepare("DELETE FROM cm_discos WHERE id=?");
      $st->bind_param('i', $id);
      $st->execute();
      jok(['id'=>$id]);
    }

    // -------- Historial por empresa (requiere DVR 1:1)
    case 'dvr_hist': {
      $empresa_id = (int)($_GET['empresa_id'] ?? 0);
      if ($empresa_id<=0) jerror(400,'Empresa requerida');

      // obtener DVR id
      $st = $mysqli->prepare("SELECT d.id, d.id_disco_actual FROM cm_dvr d JOIN cm_dvr_empresa de ON de.id_dvr=d.id WHERE de.id_empresa=? LIMIT 1");
      $st->bind_param('i', $empresa_id);
      $st->execute();
      $dvr = $st->get_result()->fetch_assoc();
      if (!$dvr) jok(['data'=>[]]);

      $st2 = $mysqli->prepare("
        SELECT h.id, h.id_dvr, h.id_disco, DATE_FORMAT(h.fecha_instalacion,'%Y-%m-%d %H:%i:%s') AS fecha_instalacion,
               DATE_FORMAT(h.fecha_retiro,'%Y-%m-%d %H:%i:%s') AS fecha_retiro,
               c.disco_total, c.disco_restante
        FROM cm_dvr_disco h
        JOIN cm_discos c ON c.id=h.id_disco
        WHERE h.id_dvr=?
        ORDER BY h.fecha_instalacion DESC, h.id DESC
      ");
      $st2->bind_param('i', $dvr['id']);
      $st2->execute();
      $rows = $st2->get_result()->fetch_all(MYSQLI_ASSOC);
      jok(['data'=>$rows, 'dvr'=>$dvr]);
    }

    // -------- Asignar disco al DVR de una empresa (cierra historial previo)
    case 'dvr_assign_disco': {
      $empresa_id = (int)($_POST['empresa_id'] ?? 0);
      $disco_id   = (int)($_POST['disco_id'] ?? 0);
      if ($empresa_id<=0 || $disco_id<=0) jerror(400,'Datos inválidos');

      $mysqli->begin_transaction();
      // DVR id
      $st = $mysqli->prepare("SELECT d.id FROM cm_dvr d JOIN cm_dvr_empresa de ON de.id_dvr=d.id WHERE de.id_empresa=? LIMIT 1");
      $st->bind_param('i', $empresa_id);
      $st->execute();
      $row = $st->get_result()->fetch_assoc();
      if (!$row) { $mysqli->rollback(); jerror(404,'La empresa no tiene DVR'); }
      $dvr_id = (int)$row['id'];

      // cerrar historial previo (si existe)
      $st2 = $mysqli->prepare("UPDATE cm_dvr_disco SET fecha_retiro=NOW() WHERE id_dvr=? AND fecha_retiro IS NULL");
      $st2->bind_param('i', $dvr_id); $st2->execute();

      // insertar nuevo historial
      $st3 = $mysqli->prepare("INSERT INTO cm_dvr_disco (id_dvr,id_disco,fecha_instalacion,fecha_retiro) VALUES (?,?,NOW(),NULL)");
      $st3->bind_param('ii', $dvr_id, $disco_id); $st3->execute();

      // puntero actual en DVR
      $st4 = $mysqli->prepare("UPDATE cm_dvr SET id_disco_actual=? WHERE id=?");
      $st4->bind_param('ii', $disco_id, $dvr_id); $st4->execute();

      $mysqli->commit();
      jok(['ok'=>true]);
    }

    // -------- Retirar disco actual del DVR
    case 'dvr_retire_disco': {
      $empresa_id = (int)($_POST['empresa_id'] ?? 0);
      if ($empresa_id<=0) jerror(400,'Empresa requerida');

      $mysqli->begin_transaction();
      $st = $mysqli->prepare("SELECT d.id FROM cm_dvr d JOIN cm_dvr_empresa de ON de.id_dvr=d.id WHERE de.id_empresa=? LIMIT 1");
      $st->bind_param('i', $empresa_id);
      $st->execute();
      $row = $st->get_result()->fetch_assoc();
      if (!$row) { $mysqli->rollback(); jerror(404,'La empresa no tiene DVR'); }
      $dvr_id = (int)$row['id'];

      // cerrar historial activo
      $st2 = $mysqli->prepare("UPDATE cm_dvr_disco SET fecha_retiro=NOW() WHERE id_dvr=? AND fecha_retiro IS NULL");
      $st2->bind_param('i', $dvr_id); $st2->execute();

      // quitar puntero
      $st3 = $mysqli->prepare("UPDATE cm_dvr SET id_disco_actual=NULL WHERE id=?");
      $st3->bind_param('i', $dvr_id); $st3->execute();

      $mysqli->commit();
      jok(['ok'=>true]);
    }

    default:
      jerror(400,'Acción no válida');
  }

} catch (mysqli_sql_exception $e) {
  if ($mysqli->errno) { @ $mysqli->rollback(); }
  jerror(500, 'Error del servidor', ['dev'=>$e->getMessage()]);
}
