-- Migracion POS (aditiva): backfill de snapshots cliente/conductor por venta
-- Fecha: 2026-03-14
-- Requiere: columnas nuevas de 2026-03-14_pos_snapshot_cliente_conductor.sql
-- Criterio: completar vacios con maestro y luego enriquecer con pos_auditoria (VENTA_CREADA).

START TRANSACTION;

-- =========================================================
-- 1) Snapshot cliente por venta (fuente principal: maestro)
-- =========================================================
UPDATE `pos_ventas` v
LEFT JOIN `pos_clientes` c ON c.id = v.cliente_id
SET
  v.cliente_snapshot_tipo_persona = COALESCE(v.cliente_snapshot_tipo_persona, c.tipo_persona),
  v.cliente_snapshot_doc_tipo     = COALESCE(v.cliente_snapshot_doc_tipo, c.doc_tipo),
  v.cliente_snapshot_doc_numero   = COALESCE(v.cliente_snapshot_doc_numero, c.doc_numero),
  v.cliente_snapshot_nombre       = COALESCE(v.cliente_snapshot_nombre, c.nombre),
  v.cliente_snapshot_telefono     = COALESCE(v.cliente_snapshot_telefono, c.telefono)
WHERE
  v.cliente_snapshot_tipo_persona IS NULL
  OR v.cliente_snapshot_doc_tipo IS NULL
  OR v.cliente_snapshot_doc_numero IS NULL
  OR v.cliente_snapshot_nombre IS NULL
  OR v.cliente_snapshot_telefono IS NULL;

-- =========================================================
-- 2) Snapshot cliente por venta (fuente auxiliar: auditoria)
-- =========================================================
UPDATE `pos_ventas` v
JOIN (
  SELECT
    a.registro_id AS venta_id,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.cliente.tipo_persona')), '') AS cli_tipo_persona,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.cliente.doc_tipo')), '')     AS cli_doc_tipo,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.cliente.doc_numero')), '')   AS cli_doc_numero,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.cliente.nombre')), '')       AS cli_nombre,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.cliente.telefono')), '')     AS cli_telefono
  FROM `pos_auditoria` a
  INNER JOIN (
    SELECT MAX(id) AS id
    FROM `pos_auditoria`
    WHERE tabla = 'pos_ventas' AND evento = 'VENTA_CREADA'
    GROUP BY registro_id
  ) x ON x.id = a.id
  WHERE a.tabla = 'pos_ventas' AND a.evento = 'VENTA_CREADA'
) aud ON aud.venta_id = v.id
SET
  v.cliente_snapshot_tipo_persona = COALESCE(v.cliente_snapshot_tipo_persona, aud.cli_tipo_persona),
  v.cliente_snapshot_doc_tipo     = COALESCE(v.cliente_snapshot_doc_tipo, aud.cli_doc_tipo),
  v.cliente_snapshot_doc_numero   = COALESCE(v.cliente_snapshot_doc_numero, aud.cli_doc_numero),
  v.cliente_snapshot_nombre       = COALESCE(v.cliente_snapshot_nombre, aud.cli_nombre),
  v.cliente_snapshot_telefono     = COALESCE(v.cliente_snapshot_telefono, aud.cli_telefono)
WHERE
  v.cliente_snapshot_tipo_persona IS NULL
  OR v.cliente_snapshot_doc_tipo IS NULL
  OR v.cliente_snapshot_doc_numero IS NULL
  OR v.cliente_snapshot_nombre IS NULL
  OR v.cliente_snapshot_telefono IS NULL;

-- =========================================================
-- 3) Snapshot conductor por venta (fuente principal)
-- =========================================================
UPDATE `pos_venta_conductores` vc
INNER JOIN `pos_ventas` v ON v.id = vc.venta_id
LEFT JOIN `pos_conductores` co ON co.id = vc.conductor_id
LEFT JOIN `pos_clientes` c ON c.id = v.cliente_id
SET
  vc.conductor_doc_tipo = COALESCE(
    vc.conductor_doc_tipo,
    co.doc_tipo,
    CASE
      WHEN v.contratante_doc_tipo IS NOT NULL THEN v.contratante_doc_tipo
      ELSE COALESCE(v.cliente_snapshot_doc_tipo, c.doc_tipo)
    END
  ),
  vc.conductor_doc_numero = COALESCE(
    vc.conductor_doc_numero,
    co.doc_numero,
    CASE
      WHEN v.contratante_doc_numero IS NOT NULL THEN v.contratante_doc_numero
      ELSE COALESCE(v.cliente_snapshot_doc_numero, c.doc_numero)
    END
  ),
  vc.conductor_nombres = COALESCE(
    vc.conductor_nombres,
    co.nombres,
    CASE
      WHEN v.contratante_nombres IS NOT NULL THEN v.contratante_nombres
      ELSE COALESCE(v.cliente_snapshot_nombre, c.nombre)
    END
  ),
  vc.conductor_apellidos = COALESCE(
    vc.conductor_apellidos,
    co.apellidos,
    CASE
      WHEN v.contratante_apellidos IS NOT NULL THEN v.contratante_apellidos
      ELSE NULL
    END
  ),
  vc.conductor_telefono = COALESCE(
    vc.conductor_telefono,
    co.telefono,
    CASE
      WHEN v.contratante_telefono IS NOT NULL THEN v.contratante_telefono
      ELSE COALESCE(v.cliente_snapshot_telefono, c.telefono)
    END
  ),
  vc.conductor_origen = COALESCE(
    vc.conductor_origen,
    CASE
      WHEN vc.conductor_id IS NOT NULL THEN 'conductor_otra_persona'
      WHEN v.contratante_doc_tipo IS NOT NULL THEN 'contratante_juridica'
      ELSE 'cliente_natural'
    END
  ),
  vc.conductor_es_mismo_cliente = CASE
    WHEN vc.conductor_es_mismo_cliente = 1 THEN 1
    WHEN vc.conductor_id IS NOT NULL THEN 0
    WHEN v.contratante_doc_tipo IS NOT NULL THEN 0
    WHEN vc.conductor_tipo = 'CLIENTE' THEN 1
    WHEN COALESCE(v.cliente_snapshot_doc_numero, c.doc_numero) IS NOT NULL
         AND COALESCE(v.cliente_snapshot_doc_tipo, c.doc_tipo) IS NOT NULL
         AND COALESCE(v.cliente_snapshot_doc_numero, c.doc_numero) = COALESCE(vc.conductor_doc_numero, co.doc_numero)
         AND COALESCE(v.cliente_snapshot_doc_tipo, c.doc_tipo) = COALESCE(vc.conductor_doc_tipo, co.doc_tipo)
      THEN 1
    ELSE 0
  END
WHERE vc.es_principal = 1;

-- =========================================================
-- 4) Snapshot conductor + extra por venta (auditoria JSON)
-- =========================================================
UPDATE `pos_venta_conductores` vc
JOIN (
  SELECT
    a.registro_id AS venta_id,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor.doc_tipo')), '')    AS cond_doc_tipo,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor.doc_numero')), '')  AS cond_doc_numero,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor.nombres')), '')     AS cond_nombres,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor.apellidos')), '')   AS cond_apellidos,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor.telefono')), '')    AS cond_telefono,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor.origen')), '')      AS cond_origen,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor.tipo_relacion')), '') AS cond_tipo_relacion,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor_perfil_extra.canal')), '') AS extra_canal,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor_perfil_extra.email')), '') AS extra_email,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor_perfil_extra.nacimiento')), '') AS extra_nacimiento,
    CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor_perfil_extra.categoria_auto_id')), '') AS UNSIGNED) AS extra_cat_auto,
    CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor_perfil_extra.categoria_moto_id')), '') AS UNSIGNED) AS extra_cat_moto,
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.datos, '$.conductor_perfil_extra.nota')), '') AS extra_nota
  FROM `pos_auditoria` a
  INNER JOIN (
    SELECT MAX(id) AS id
    FROM `pos_auditoria`
    WHERE tabla = 'pos_ventas' AND evento = 'VENTA_CREADA'
    GROUP BY registro_id
  ) x ON x.id = a.id
  WHERE a.tabla = 'pos_ventas' AND a.evento = 'VENTA_CREADA'
) aud ON aud.venta_id = vc.venta_id
SET
  vc.conductor_doc_tipo = COALESCE(vc.conductor_doc_tipo, aud.cond_doc_tipo),
  vc.conductor_doc_numero = COALESCE(vc.conductor_doc_numero, aud.cond_doc_numero),
  vc.conductor_nombres = COALESCE(vc.conductor_nombres, aud.cond_nombres),
  vc.conductor_apellidos = COALESCE(vc.conductor_apellidos, aud.cond_apellidos),
  vc.conductor_telefono = COALESCE(vc.conductor_telefono, aud.cond_telefono),
  vc.conductor_origen = COALESCE(vc.conductor_origen, aud.cond_origen),
  vc.conductor_es_mismo_cliente = CASE
    WHEN vc.conductor_es_mismo_cliente = 1 THEN 1
    WHEN UPPER(COALESCE(aud.cond_origen, '')) = 'CLIENTE_NATURAL' THEN 1
    WHEN UPPER(COALESCE(aud.cond_tipo_relacion, '')) = 'CLIENTE' AND UPPER(COALESCE(aud.cond_origen, '')) <> 'CONTRATANTE_JURIDICA' THEN 1
    ELSE vc.conductor_es_mismo_cliente
  END,
  vc.canal = COALESCE(vc.canal, aud.extra_canal),
  vc.email_contacto = COALESCE(vc.email_contacto, aud.extra_email),
  vc.nacimiento = COALESCE(vc.nacimiento, aud.extra_nacimiento),
  vc.conductor_categoria_auto_id = COALESCE(
    vc.conductor_categoria_auto_id,
    (
      SELECT cl.id
      FROM `cq_categorias_licencia` cl
      WHERE cl.id = aud.extra_cat_auto
        AND cl.tipo_categoria = 'A'
      LIMIT 1
    )
  ),
  vc.conductor_categoria_moto_id = COALESCE(
    vc.conductor_categoria_moto_id,
    (
      SELECT cl.id
      FROM `cq_categorias_licencia` cl
      WHERE cl.id = aud.extra_cat_moto
        AND cl.tipo_categoria = 'B'
      LIMIT 1
    )
  ),
  vc.nota = COALESCE(vc.nota, aud.extra_nota)
WHERE vc.es_principal = 1;

COMMIT;
