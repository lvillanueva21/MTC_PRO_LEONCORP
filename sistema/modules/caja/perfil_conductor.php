<?php
// modules/caja/perfil_conductor.php
// Helpers para manejar el perfil opcional de conductor.

function pos_perfil_doc_tipos_permitidos(): array {
  return ['DNI', 'CE', 'BREVETE'];
}

function pos_perfil_parse_nullable_int($value): ?int {
  $raw = trim((string)$value);
  if ($raw === '' || $raw === '0') return null;
  if (!preg_match('/^\d+$/', $raw)) {
    throw new InvalidArgumentException('Categoría de licencia inválida.');
  }
  $id = (int)$raw;
  return $id > 0 ? $id : null;
}

function pos_perfil_normalize_payload(array $src): array {
  $canal = trim((string)($src['conductor_extra_canal'] ?? ''));
  if ($canal === '') {
    $canal = null;
  } else {
    $len = function_exists('mb_strlen') ? mb_strlen($canal, 'UTF-8') : strlen($canal);
    if ($len > 30) {
      throw new InvalidArgumentException('El canal del conductor supera el máximo de 30 caracteres.');
    }
  }

  $email = trim((string)($src['conductor_extra_email'] ?? ''));
  if ($email === '') {
    $email = null;
  } else {
    $email = function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException('El correo del conductor no es válido.');
    }
    $len = function_exists('mb_strlen') ? mb_strlen($email, 'UTF-8') : strlen($email);
    if ($len > 150) {
      throw new InvalidArgumentException('El correo del conductor supera el máximo de 150 caracteres.');
    }
  }

  $nacimientoRaw = trim((string)($src['conductor_extra_nacimiento'] ?? ''));
  $nacimiento = null;
  if ($nacimientoRaw !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nacimientoRaw)) {
      throw new InvalidArgumentException('La fecha de nacimiento del conductor no tiene un formato válido.');
    }
    $dt = DateTime::createFromFormat('Y-m-d', $nacimientoRaw);
    if (!$dt || $dt->format('Y-m-d') !== $nacimientoRaw) {
      throw new InvalidArgumentException('La fecha de nacimiento del conductor no es válida.');
    }
    $hoy = new DateTime('today');
    if ($dt > $hoy) {
      throw new InvalidArgumentException('La fecha de nacimiento del conductor no puede ser futura.');
    }
    $nacimiento = $nacimientoRaw;
  }

  $categoriaAutoId = pos_perfil_parse_nullable_int($src['conductor_extra_categoria_auto_id'] ?? null);
  $categoriaMotoId = pos_perfil_parse_nullable_int($src['conductor_extra_categoria_moto_id'] ?? null);

  $nota = trim((string)($src['conductor_extra_nota'] ?? ''));
  if ($nota === '') {
    $nota = null;
  } else {
    $len = function_exists('mb_strlen') ? mb_strlen($nota, 'UTF-8') : strlen($nota);
    if ($len > 255) {
      throw new InvalidArgumentException('La nota del conductor supera el máximo de 255 caracteres.');
    }
  }

  return [
    'canal'            => $canal,
    'email'            => $email,
    'nacimiento'       => $nacimiento,
    'categoria_auto_id'=> $categoriaAutoId,
    'categoria_moto_id'=> $categoriaMotoId,
    'nota'             => $nota
  ];
}

function pos_perfil_data_has_content(array $data): bool {
  return !(
    ($data['canal'] ?? null) === null &&
    ($data['email'] ?? null) === null &&
    ($data['nacimiento'] ?? null) === null &&
    ($data['categoria_auto_id'] ?? null) === null &&
    ($data['categoria_moto_id'] ?? null) === null &&
    ($data['nota'] ?? null) === null
  );
}

function pos_perfil_categoria_valida_tipo(mysqli $db, ?int $categoriaId, string $tipoEsperado): bool {
  if ($categoriaId === null) return true;

  static $cache = [];
  if (!array_key_exists($categoriaId, $cache)) {
    $st = $db->prepare("SELECT tipo_categoria FROM cq_categorias_licencia WHERE id=? LIMIT 1");
    $st->bind_param('i', $categoriaId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $cache[$categoriaId] = $row ? strtoupper((string)$row['tipo_categoria']) : null;
  }

  return $cache[$categoriaId] === strtoupper($tipoEsperado);
}

function pos_perfil_list_categorias(mysqli $db): array {
  $rs = $db->query("SELECT id, codigo, tipo_categoria FROM cq_categorias_licencia ORDER BY tipo_categoria ASC, id ASC");
  $auto = [];
  $moto = [];
  foreach (($rs->fetch_all(MYSQLI_ASSOC) ?: []) as $r) {
    $item = [
      'id'     => (int)$r['id'],
      'codigo' => (string)$r['codigo']
    ];
    $tipo = strtoupper((string)$r['tipo_categoria']);
    if ($tipo === 'A') $auto[] = $item;
    if ($tipo === 'B') $moto[] = $item;
  }
  return ['auto' => $auto, 'moto' => $moto];
}

function pos_perfil_get(mysqli $db, int $empresaId, string $docTipo, string $docNumero): ?array {
  $st = $db->prepare("SELECT
                        canal,
                        email,
                        nacimiento,
                        categoria_auto_id,
                        categoria_moto_id,
                        nota
                      FROM pos_perfil_conductor
                      WHERE id_empresa=? AND doc_tipo=? AND doc_numero=?
                      LIMIT 1");
  $st->bind_param('iss', $empresaId, $docTipo, $docNumero);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  if (!$row) return null;

  return [
    'canal'             => $row['canal'] !== null ? (string)$row['canal'] : null,
    'email'             => $row['email'] !== null ? (string)$row['email'] : null,
    'nacimiento'        => $row['nacimiento'] !== null ? (string)$row['nacimiento'] : null,
    'categoria_auto_id' => $row['categoria_auto_id'] !== null ? (int)$row['categoria_auto_id'] : null,
    'categoria_moto_id' => $row['categoria_moto_id'] !== null ? (int)$row['categoria_moto_id'] : null,
    'nota'              => $row['nota'] !== null ? (string)$row['nota'] : null
  ];
}

function pos_perfil_upsert(mysqli $db, int $empresaId, string $docTipo, string $docNumero, array $data): void {
  $canal = $data['canal'] ?? null;
  $email = $data['email'] ?? null;
  $nacimiento = $data['nacimiento'] ?? null;
  $categoriaAutoId = $data['categoria_auto_id'] ?? null;
  $categoriaMotoId = $data['categoria_moto_id'] ?? null;
  $nota = $data['nota'] ?? null;

  $st = $db->prepare("INSERT INTO pos_perfil_conductor(
                        id_empresa, doc_tipo, doc_numero,
                        canal, email, nacimiento, categoria_auto_id, categoria_moto_id, nota
                      ) VALUES (?,?,?,?,?,?,?,?,?)
                      ON DUPLICATE KEY UPDATE
                        canal=VALUES(canal),
                        email=VALUES(email),
                        nacimiento=VALUES(nacimiento),
                        categoria_auto_id=VALUES(categoria_auto_id),
                        categoria_moto_id=VALUES(categoria_moto_id),
                        nota=VALUES(nota),
                        actualizado=NOW()");
  $st->bind_param(
    'isssssiis',
    $empresaId,
    $docTipo,
    $docNumero,
    $canal,
    $email,
    $nacimiento,
    $categoriaAutoId,
    $categoriaMotoId,
    $nota
  );
  $st->execute();
}
