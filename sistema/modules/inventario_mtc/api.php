<?php
// modules/inventario_mtc/api.php
require_once __DIR__.'/../../includes/acl.php';
require_once __DIR__.'/../../includes/permisos.php';
require_once __DIR__.'/../../includes/conexion.php';
require_once __DIR__.'/_empresa_target.php';

header('Content-Type: application/json; charset=utf-8');

// Acceso al API del módulo:
// Desarrollo (1), Control (2), Recepción (3), Administración (4), Gerente (6)
acl_require_ids([1,2,3,4,6]);
verificarPermiso(['Desarrollo','Control','Recepción','Administración','Gerente']);

$mysqli = db();
$mysqli->set_charset('utf8mb4');

// ---- helpers ----
function jerror($code, $msg, $extra=[]) { http_response_code($code); echo json_encode(['ok'=>false,'msg'=>$msg]+$extra); exit; }
function jok($arr=[]) { echo json_encode(['ok'=>true]+$arr); exit; }
function norm_s($s){ return trim((string)$s); }

// Empresa objetivo (multi-empresa para Dev/Control/Gerente)
$emp = invmtc_require_empresa_target($mysqli, true);
$empresaId = (int)$emp['id'];
$isMulti = (bool)$emp['is_multi'];

$map = [
  'computadoras' => [
    'table' => 'iv_computadoras',
    'cols'  => ['ambiente','nombre_equipo','marca','modelo','serie','procesador','disco_gb','ram_gb','sistema_operativo','mac','ip','notas','activo'],
    'search'=> ['ambiente','nombre_equipo','marca','modelo','serie','procesador','disco_gb','ram_gb','sistema_operativo','mac','ip','notas'],
    'thead' => ['Ambiente','Nombre','Marca/Modelo','SO','IP','Activo','Acciones'],
    'form'  => [
      ['name'=>'ambiente','label'=>'Ambiente','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'nombre_equipo','label'=>'Nombre','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'marca','label'=>'Marca','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'modelo','label'=>'Modelo','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'serie','label'=>'N° Serie','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'procesador','label'=>'Procesador','type'=>'text','max'=>160,'req'=>false],
      ['name'=>'disco_gb','label'=>'Disco (GB)','type'=>'text','max'=>60,'req'=>false],
      ['name'=>'ram_gb','label'=>'RAM (GB)','type'=>'text','max'=>60,'req'=>false],
      ['name'=>'sistema_operativo','label'=>'Sistema Operativo','type'=>'text','max'=>160,'req'=>false],
      ['name'=>'mac','label'=>'MAC','type'=>'text','max'=>60,'req'=>false],
      ['name'=>'ip','label'=>'IP','type'=>'text','max'=>60,'req'=>false],
      ['name'=>'notas','label'=>'Notas','type'=>'textarea','max'=>255,'req'=>false],
      ['name'=>'activo','label'=>'Activo','type'=>'switch','req'=>false],
    ],
  ],
  'camaras' => [
    'table'=>'iv_camaras',
    'cols' => ['etiqueta','ambiente','marca','modelo','serie','notas','activo'],
    'search'=>['etiqueta','ambiente','marca','modelo','serie','notas'],
    'thead'=>['Etiqueta','Ambiente','Marca/Modelo','Serie','Activo','Acciones'],
    'form' => [
      ['name'=>'etiqueta','label'=>'Etiqueta','type'=>'text','max'=>100,'req'=>false],
      ['name'=>'ambiente','label'=>'Ambiente','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'marca','label'=>'Marca','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'modelo','label'=>'Modelo','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'serie','label'=>'N° Serie','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'notas','label'=>'Notas','type'=>'textarea','max'=>255,'req'=>false],
      ['name'=>'activo','label'=>'Activo','type'=>'switch','req'=>false],
    ],
  ],
  'dvrs' => [
    'table'=>'iv_dvrs',
    'cols' => ['marca','modelo','serie','notas','activo'],
    'search'=>['marca','modelo','serie','notas'],
    'thead'=>['Marca/Modelo','Serie','Activo','Acciones'],
    'form' => [
      ['name'=>'marca','label'=>'Marca','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'modelo','label'=>'Modelo','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'serie','label'=>'N° Serie','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'notas','label'=>'Notas','type'=>'textarea','max'=>255,'req'=>false],
      ['name'=>'activo','label'=>'Activo','type'=>'switch','req'=>false],
    ],
  ],
  'huelleros' => [
    'table'=>'iv_huelleros',
    'cols' => ['etiqueta','marca','modelo','serie','notas','activo'],
    'search'=>['etiqueta','marca','modelo','serie','notas'],
    'thead'=>['Etiqueta','Marca/Modelo','Serie','Activo','Acciones'],
    'form' => [
      ['name'=>'etiqueta','label'=>'Etiqueta','type'=>'text','max'=>100,'req'=>false],
      ['name'=>'marca','label'=>'Marca','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'modelo','label'=>'Modelo','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'serie','label'=>'N° Serie','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'notas','label'=>'Notas','type'=>'textarea','max'=>255,'req'=>false],
      ['name'=>'activo','label'=>'Activo','type'=>'switch','req'=>false],
    ],
  ],
  'switches' => [
    'table'=>'iv_switches',
    'cols' => ['marca','modelo','serie','notas','activo'],
    'search'=>['marca','modelo','serie','notas'],
    'thead'=>['Marca/Modelo','Serie','Activo','Acciones'],
    'form' => [
      ['name'=>'marca','label'=>'Marca','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'modelo','label'=>'Modelo','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'serie','label'=>'N° Serie','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'notas','label'=>'Notas','type'=>'textarea','max'=>255,'req'=>false],
      ['name'=>'activo','label'=>'Activo','type'=>'switch','req'=>false],
    ],
  ],
  'red' => [
    'table'=>'iv_red',
    'cols' => ['ip_publica','transmision_online','bajada_txt','subida_txt','notas','activo'],
    'search'=>['ip_publica','transmision_online','bajada_txt','subida_txt','notas'],
    'thead'=>['IP Pública','Transmisión en línea','Bajada/Subida','Activo','Acciones'],
    'form' => [
      ['name'=>'ip_publica','label'=>'IP Pública','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'transmision_online','label'=>'URL transmisión','type'=>'text','max'=>255,'req'=>false],
      ['name'=>'bajada_txt','label'=>'Bajada','type'=>'text','max'=>60,'req'=>false],
      ['name'=>'subida_txt','label'=>'Subida','type'=>'text','max'=>60,'req'=>false],
      ['name'=>'notas','label'=>'Notas','type'=>'textarea','max'=>255,'req'=>false],
      ['name'=>'activo','label'=>'Activo','type'=>'switch','req'=>false],
    ],
  ],
  'transmision' => [
    'table'=>'iv_transmision',
    'cols' => ['acceso_url','usuario','clave','notas','activo'],
    'search'=>['acceso_url','usuario','clave','notas'],
    'thead'=>['Acceso','Usuario','Contraseña','Activo','Acciones'],
    'form' => [
      ['name'=>'acceso_url','label'=>'Acceso (URL)','type'=>'text','max'=>255,'req'=>false],
      ['name'=>'usuario','label'=>'Usuario','type'=>'text','max'=>120,'req'=>false],
      ['name'=>'clave','label'=>'Contraseña','type'=>'text','max'=>160,'req'=>false],
      ['name'=>'notas','label'=>'Notas','type'=>'textarea','max'=>255,'req'=>false],
      ['name'=>'activo','label'=>'Activo','type'=>'switch','req'=>false],
    ],
  ],
];

$singleton = [
  'red' => true,
  'transmision' => true,
  'dvrs' => true,
  'switches' => true,
];

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$tabla  = $_POST['tabla']  ?? $_GET['tabla']  ?? '';

if (!isset($map[$tabla]) && $action !== 'stats' && $action !== 'meta' && $action !== 'empresas') {
  jerror(400,'Tabla no válida');
}
$def = $map[$tabla] ?? null;

try {
  switch ($action) {

    case 'empresas': {
      // SOLO roles multi-empresa pueden listar todas las empresas
      if (!$isMulti) jerror(403, 'No autorizado');

      $rows = [];
      $res = $mysqli->query("SELECT id, nombre FROM mtp_empresas ORDER BY nombre ASC");
      if ($res) $rows = $res->fetch_all(MYSQLI_ASSOC);

      // normaliza tipos
      foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['nombre'] = (string)$r['nombre'];
      }
      unset($r);

      jok(['data' => $rows]);
    }

    case 'meta': {
      $out = [];
      foreach ($map as $slug=>$m) {
        $out[$slug] = ['thead'=>$m['thead'],'form'=>$m['form']];
      }
      jok(['data'=>$out]);
    }

    case 'stats': {
      $stats = [];
      foreach ($map as $slug=>$m) {
        $t = $m['table'];
        $st = $mysqli->prepare("SELECT SUM(activo=1) a1, COUNT(*) tot FROM $t WHERE id_empresa=?");
        $st->bind_param('i', $empresaId);
        $st->execute();
        $r = $st->get_result()->fetch_assoc() ?: ['a1'=>0,'tot'=>0];
        $stats[$slug] = ['activos'=>(int)$r['a1'], 'total'=>(int)$r['tot']];
      }
      jok(['data'=>$stats]);
    }

    case 'list': {
      $page = max(1, (int)($_GET['page'] ?? 1));
      $per  = max(1, min(50, (int)($_GET['per'] ?? 10)));
      $q    = norm_s($_GET['q'] ?? '');
      $estado = $_GET['estado'] ?? '';
      $offset = ($page-1)*$per;

      $table = $def['table'];
      $W=[]; $types='i'; $pars=[$empresaId];
      $W[] = "id_empresa = ?";

      if ($estado==='0' || $estado==='1') {
        $W[]="activo=?";
        $types.='i';
        $pars[]=(int)$estado;
      }

      if ($q!=='') {
        $chunks=[];
        foreach ($def['search'] as $c) $chunks[] = "$c LIKE ?";
        $W[] = '(' . implode(' OR ', $chunks) . ')';
        $types .= str_repeat('s', count($def['search']));
        foreach ($def['search'] as $_) $pars[] = '%'.$q.'%';
      }

      $where = 'WHERE '.implode(' AND ',$W);

      $st = $mysqli->prepare("SELECT COUNT(*) c FROM $table $where");
      $st->bind_param($types, ...$pars);
      $st->execute();
      $total = (int)$st->get_result()->fetch_assoc()['c'];

      $st = $mysqli->prepare("SELECT * FROM $table $where ORDER BY id DESC LIMIT ? OFFSET ?");
      $types2 = $types.'ii';
      $pars2  = $pars; $pars2[] = $per; $pars2[] = $offset;
      $st->bind_param($types2, ...$pars2);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per'=>$per]);
    }

    case 'get': {
      $id = (int)($_GET['id'] ?? 0);
      if ($id<=0) jerror(400,'ID inválido');
      $table = $def['table'];

      $st = $mysqli->prepare("SELECT * FROM $table WHERE id=? AND id_empresa=? LIMIT 1");
      $st->bind_param('ii', $id, $empresaId);
      $st->execute();
      $row = $st->get_result()->fetch_assoc();
      if (!$row) jerror(404,'Registro no encontrado');

      jok(['data'=>$row]);
    }

    case 'save': {
      $id = (int)($_POST['id'] ?? 0);
      $table = $def['table'];
      $isSingleton = isset($singleton[$tabla]);

      $data = [];
      foreach ($def['cols'] as $c) {
        if ($c==='activo') { $v = isset($_POST['activo']) ? (int)($_POST['activo'] ? 1 : 0) : 0; }
        else { $v = norm_s($_POST[$c] ?? ''); }
        $data[$c] = $v;
      }

      $doUpdate = function($targetId) use ($mysqli, $table, $def, $data, $empresaId, $isSingleton) {
        $setCols = [];
        $types=''; $pars=[];

        foreach ($def['cols'] as $c) {
          $setCols[] = "$c=?";
          $types .= ($c==='activo' ? 'i' : 's');
          $pars[] = $data[$c];
        }

        $sql = "UPDATE $table SET ".implode(',',$setCols)." WHERE id=? AND id_empresa=? LIMIT 1";
        $st = $mysqli->prepare($sql);
        $types .= 'ii';
        $pars[] = (int)$targetId;
        $pars[] = (int)$empresaId;
        $st->bind_param($types, ...$pars);
        $st->execute();

        if ($isSingleton && isset($data['activo']) && (int)$data['activo'] === 1) {
          $st2 = $mysqli->prepare("UPDATE $table SET activo=0 WHERE id_empresa=? AND id<>?");
          $st2->bind_param('ii', $empresaId, $targetId);
          $st2->execute();
        }
      };

      if ($id > 0) {
        $chk = $mysqli->prepare("SELECT id FROM $table WHERE id=? AND id_empresa=? LIMIT 1");
        $chk->bind_param('ii', $id, $empresaId);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) jerror(404,'Registro no encontrado');

        $doUpdate($id);
        jok(['id'=>$id]);
      } else {
        if ($isSingleton) {
          $st0 = $mysqli->prepare("SELECT id FROM $table WHERE id_empresa=? ORDER BY id DESC LIMIT 1");
          $st0->bind_param('i', $empresaId);
          $st0->execute();
          $row0 = $st0->get_result()->fetch_assoc();
          if ($row0 && (int)$row0['id'] > 0) {
            $existingId = (int)$row0['id'];
            $doUpdate($existingId);
            jok(['id'=>$existingId,'singleton'=>1,'updated'=>1]);
          }
        }

        $cols = array_merge(['id_empresa'], $def['cols']);
        $qs   = implode(',', array_fill(0, count($cols), '?'));
        $sql  = "INSERT INTO $table (".implode(',',$cols).") VALUES ($qs)";
        $st   = $mysqli->prepare($sql);

        $types = 'i';
        $pars  = [$empresaId];
        foreach ($def['cols'] as $c) { $types .= ($c==='activo' ? 'i' : 's'); $pars[] = $data[$c]; }

        $st->bind_param($types, ...$pars);
        $st->execute();
        $newId = (int)$mysqli->insert_id;

        jok(['id'=>$newId]);
      }
    }

    case 'toggle': {
      $id = (int)($_POST['id'] ?? 0);
      $new = (int)($_POST['activo'] ?? 0);
      if ($id<=0 || ($new!==0 && $new!==1)) jerror(400,'Parámetros inválidos');

      $table = $def['table'];
      $isSingleton = isset($singleton[$tabla]);

      if ($isSingleton && $new === 1) {
        $st2 = $mysqli->prepare("UPDATE $table SET activo=0 WHERE id_empresa=? AND id<>?");
        $st2->bind_param('ii', $empresaId, $id);
        $st2->execute();
      }

      $st = $mysqli->prepare("UPDATE $table SET activo=? WHERE id=? AND id_empresa=? LIMIT 1");
      $st->bind_param('iii', $new, $id, $empresaId);
      $st->execute();

      jok(['id'=>$id,'activo'=>$new]);
    }

    default:
      jerror(400,'Acción no válida');
  }

} catch (mysqli_sql_exception $e) {
  jerror(500,'Error de servidor',['dev'=>$e->getMessage()]);
}
