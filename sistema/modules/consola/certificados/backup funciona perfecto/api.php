<?php
// modules/consola/certificados/api.php
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../gestion_archivos.php';

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = db();
$db->set_charset('utf8mb4');

$A = $_POST['action'] ?? $_GET['action'] ?? '';

function jerror($code, $msg, $extra = []) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'msg' => $msg] + $extra);
  exit;
}
function jok($arr = []) {
  echo json_encode(['ok' => true] + $arr);
  exit;
}

try {
  switch ($A) {
    // -------------------- COMBO EMPRESAS --------------------
    case 'empresas': {
      $rs = $db->query("SELECT id, nombre FROM mtp_empresas ORDER BY nombre");
      jok(['data' => $rs->fetch_all(MYSQLI_ASSOC)]);
    }

    case 'list': {
      $q    = trim($_GET['q'] ?? '');
      $page = max(1, (int)($_GET['page'] ?? 1));
      $per  = max(1, min(50, (int)($_GET['per_page'] ?? 5)));
      $off  = ($page - 1) * $per;

      $where = ''; $types=''; $pars=[];
      if ($q !== '') {
        $like = "%$q%";
        $where = "WHERE (pc.nombre LIKE ? COLLATE utf8mb4_spanish_ci
                      OR pc.resolucion LIKE ? COLLATE utf8mb4_spanish_ci
                      OR e.nombre LIKE ? COLLATE utf8mb4_spanish_ci)";
        $types = 'sss'; $pars = [$like,$like,$like];
      }

      // total
      $stC = $db->prepare("SELECT COUNT(*) c
                           FROM cq_plantillas_certificados pc
                           JOIN mtp_empresas e ON e.id = pc.id_empresa
                           $where");
      if ($types) $stC->bind_param($types, ...$pars);
      $stC->execute();
      $total = (int)$stC->get_result()->fetch_assoc()['c'];

      // data
      $sql = "SELECT pc.id, pc.nombre, pc.paginas, pc.activo, pc.creado, e.nombre AS empresa
              FROM cq_plantillas_certificados pc
              JOIN mtp_empresas e ON e.id = pc.id_empresa
              $where
              ORDER BY pc.id DESC
              LIMIT ? OFFSET ?";
      $types2 = $types.'ii'; $pars2 = $pars; $pars2[] = $per; $pars2[] = $off;

      $st = $db->prepare($sql);
      if ($types2) $st->bind_param($types2, ...$pars2);
      $st->execute();
      $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

      jok(['data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per]);
    }

    case 'get': {
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) jerror(400, 'ID inválido');

      $st = $db->prepare(
        "SELECT pc.id, pc.nombre, pc.paginas, pc.id_empresa, pc.representante, pc.ciudad, pc.resolucion,
                pc.fondo_path, pc.logo_path, pc.firma_path, pc.activo, pc.creado, pc.actualizado,
                e.nombre AS empresa
         FROM cq_plantillas_certificados pc
         JOIN mtp_empresas e ON e.id = pc.id_empresa
         WHERE pc.id = ?"
      );
      $st->bind_param('i', $id);
      $st->execute();
      $row = $st->get_result()->fetch_assoc();
      if (!$row) jerror(404, 'No encontrado');

      jok(['data' => $row]);
    }

    // -------------------- CREAR PLANTILLA --------------------
    case 'create': {
      $nombre        = trim($_POST['nombre'] ?? '');
      $paginas       = (int)($_POST['paginas'] ?? 0);
      $id_empresa    = (int)($_POST['id_empresa'] ?? 0);
      $representante = trim($_POST['representante'] ?? '');
      $ciudad        = trim($_POST['ciudad'] ?? '');
      $resolucion    = trim($_POST['resolucion'] ?? '');

      if ($nombre === '') jerror(400, 'El nombre es obligatorio');
      if ($paginas <= 0 || $paginas > 255) jerror(400, 'Páginas debe ser un número entre 1 y 255');
      if ($id_empresa <= 0) jerror(400, 'Debes seleccionar una empresa');

      // Fondo obligatorio
      if (empty($_FILES['fondo']) || ($_FILES['fondo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        jerror(400, 'El fondo de certificado es obligatorio');
      }

      // Valida tipos/tamaños (5MB máx)
      $checkImg = function(array $file) {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) jerror(400, 'Error al subir imagen');
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) jerror(400, 'La imagen excede 5MB');
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fi->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
          jerror(400, 'Formato no permitido (PNG/JPG/WebP)');
        }
      };

      $checkImg($_FILES['fondo']);
      if (!empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $checkImg($_FILES['logo']);
      if (!empty($_FILES['firma']) && ($_FILES['firma']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $checkImg($_FILES['firma']);

      // Guardar imágenes primero (con entidad NULL; nombre estable por timestamp y random)
      $ruta_fondo = $ruta_logo = $ruta_firma = null;
      try {
        $gaF = ga_save_upload($db, $_FILES['fondo'], 'fondo_certificado', 'fondo-certificado', 'certificados', 'plantilla', null);
        $ruta_fondo = $gaF['ruta_relativa'] ?? null;

        if (!empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
          $gaL = ga_save_upload($db, $_FILES['logo'], 'logo_certificado', 'logo-certificado', 'certificados', 'plantilla', null);
          $ruta_logo = $gaL['ruta_relativa'] ?? null;
        }
        if (!empty($_FILES['firma']) && ($_FILES['firma']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
          $gaR = ga_save_upload($db, $_FILES['firma'], 'firma_representante', 'firma-representante', 'certificados', 'plantilla', null);
          $ruta_firma = $gaR['ruta_relativa'] ?? null;
        }
      } catch (Throwable $e) {
        jerror(500, 'No se pudieron guardar las imágenes', ['dev' => $e->getMessage()]);
      }

      if (!$ruta_fondo) jerror(500, 'No se obtuvo ruta del fondo');

      // Insert
      $db->begin_transaction();
      try {
        $sql = "INSERT INTO cq_plantillas_certificados
                  (nombre, paginas, id_empresa, representante, ciudad, resolucion,
                   fondo_path, logo_path, firma_path, activo)
                VALUES (?,?,?,?,?,?,?,?,?,1)";
        $st = $db->prepare($sql);
        // Nulos opcionales si vienen vacíos
        $rep = ($representante !== '') ? $representante : null;
        $ciu = ($ciudad !== '') ? $ciudad : null;
        $res = ($resolucion !== '') ? $resolucion : null;

        $st->bind_param(
          'siissssss',
          $nombre,        // s
          $paginas,       // i
          $id_empresa,    // i
          $rep,           // s (NULL OK)
          $ciu,           // s (NULL OK)
          $res,           // s (NULL OK)
          $ruta_fondo,    // s
          $ruta_logo,     // s (NULL OK)
          $ruta_firma     // s (NULL OK)
        );
        $st->execute();
        $pid = (int)$db->insert_id;

        $db->commit();
        jok(['id' => $pid]);
      } catch (mysqli_sql_exception $e) {
        $db->rollback();
        // Limpia archivos guardados si el insert falla
        if ($ruta_fondo) ga_mark_and_delete($db, $ruta_fondo, 'borrado');
        if ($ruta_logo)  ga_mark_and_delete($db, $ruta_logo,  'borrado');
        if ($ruta_firma) ga_mark_and_delete($db, $ruta_firma, 'borrado');

        if ((int)$e->getCode() === 1062) {
          jerror(409, 'Ya existe una plantilla con ese nombre para la empresa');
        }
        jerror(500, 'Error del servidor', ['dev' => $e->getMessage()]);
      }
    }

    case 'update': {
      $id           = (int)($_POST['id'] ?? 0);
      $nombre       = trim($_POST['nombre'] ?? '');
      $paginas      = max(1, (int)($_POST['paginas'] ?? 1));
      $id_empresa   = (int)($_POST['id_empresa'] ?? 0);
      $representante= trim($_POST['representante'] ?? '');
      $ciudad       = trim($_POST['ciudad'] ?? '');
      $resolucion   = trim($_POST['resolucion'] ?? '');

      if ($id <= 0) jerror(400, 'ID inválido');
      if ($nombre === '') jerror(400, 'El nombre es obligatorio');
      if ($id_empresa <= 0) jerror(400, 'Empresa requerida');

      // Traer paths previos (para borrar si se reemplazan)
      $stp = $db->prepare("SELECT fondo_path, logo_path, firma_path FROM cq_plantillas_certificados WHERE id=?");
      $stp->bind_param('i', $id);
      $stp->execute();
      $prev = $stp->get_result()->fetch_assoc();
      if (!$prev) jerror(404, 'No encontrado');

      $db->begin_transaction();
      try {
        // Actualizar datos base
        $st = $db->prepare("UPDATE cq_plantillas_certificados
                            SET nombre=?, paginas=?, id_empresa=?, representante=?, ciudad=?, resolucion=?
                            WHERE id=?");
        $st->bind_param('siisssi', $nombre, $paginas, $id_empresa, $representante, $ciudad, $resolucion, $id);
        $st->execute();

        // Reemplazo de imágenes (si se envían)
        // Fondo (obligatorio al crear; en update solo si adjuntan uno nuevo)
        if (!empty($_FILES['fondo']) && $_FILES['fondo']['error'] !== UPLOAD_ERR_NO_FILE) {
          if ($_FILES['fondo']['error'] !== UPLOAD_ERR_OK) jerror(400, 'Error al subir el fondo');
          if (($_FILES['fondo']['size'] ?? 0) > 10*1024*1024) jerror(400, 'El fondo excede 10MB');
          $ga = ga_save_upload($db, $_FILES['fondo'], 'fondo_certificado', 'fondo-certificado', 'certificados', 'plantilla', $id);
          $ruta_rel = $ga['ruta_relativa'] ?? '';
          if ($ruta_rel !== '') {
            $up = $db->prepare("UPDATE cq_plantillas_certificados SET fondo_path=? WHERE id=?");
            $up->bind_param('si', $ruta_rel, $id);
            $up->execute();
            if (!empty($prev['fondo_path']) && $prev['fondo_path'] !== $ruta_rel) {
              ga_mark_and_delete($db, $prev['fondo_path'], 'reemplazado');
            }
          }
        }
        // Logo (opcional)
        if (!empty($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
          if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) jerror(400, 'Error al subir el logo');
          if (($_FILES['logo']['size'] ?? 0) > 5*1024*1024) jerror(400, 'El logo excede 5MB');
          $ga = ga_save_upload($db, $_FILES['logo'], 'logo_certificado', 'logo-certificado', 'certificados', 'plantilla', $id);
          $ruta_rel = $ga['ruta_relativa'] ?? '';
          if ($ruta_rel !== '') {
            $up = $db->prepare("UPDATE cq_plantillas_certificados SET logo_path=? WHERE id=?");
            $up->bind_param('si', $ruta_rel, $id);
            $up->execute();
            if (!empty($prev['logo_path']) && $prev['logo_path'] !== $ruta_rel) {
              ga_mark_and_delete($db, $prev['logo_path'], 'reemplazado');
            }
          }
        }
        // Firma (opcional)
        if (!empty($_FILES['firma']) && $_FILES['firma']['error'] !== UPLOAD_ERR_NO_FILE) {
          if ($_FILES['firma']['error'] !== UPLOAD_ERR_OK) jerror(400, 'Error al subir la firma');
          if (($_FILES['firma']['size'] ?? 0) > 5*1024*1024) jerror(400, 'La firma excede 5MB');
          $ga = ga_save_upload($db, $_FILES['firma'], 'firma_representante', 'firma-representante', 'certificados', 'plantilla', $id);
          $ruta_rel = $ga['ruta_relativa'] ?? '';
          if ($ruta_rel !== '') {
            $up = $db->prepare("UPDATE cq_plantillas_certificados SET firma_path=? WHERE id=?");
            $up->bind_param('si', $ruta_rel, $id);
            $up->execute();
            if (!empty($prev['firma_path']) && $prev['firma_path'] !== $ruta_rel) {
              ga_mark_and_delete($db, $prev['firma_path'], 'reemplazado');
            }
          }
        }

        $db->commit();
        jok(['id'=>$id]);
      } catch (Throwable $e) {
        $db->rollback();
        jerror(500, 'Error del servidor', ['dev'=>$e->getMessage()]);
      }
    }

    default:
      jerror(400, 'Acción no válida');
  }
} catch (mysqli_sql_exception $e) {
  if ($db->errno) { @$db->rollback(); }
  jerror(500, 'Error de base de datos', ['dev' => $e->getMessage()]);
} catch (Throwable $e) {
  jerror(500, 'Error inesperado', ['dev' => $e->getMessage()]);
}
