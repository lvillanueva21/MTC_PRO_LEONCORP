-- Aula Virtual - Migracion Marzo 2026
-- Objetivo: grupos por empresa + constraints de integridad para cr_grupos/cr_matriculas_grupo
--
-- IMPORTANTE:
-- 1) Este script asume que las tablas cr_grupos y cr_matriculas_grupo ya existen (Paso 1).
-- 2) Ejecutar primero en entorno de pruebas.

-- =========================================================
-- 1) LIMPIEZA PREVIA (obligatoria para esta migracion)
-- =========================================================
TRUNCATE TABLE cr_matriculas_grupo;
TRUNCATE TABLE cr_grupos;

-- OPCIONAL (recomendado si se desea iniciar desde cero tambien en compatibilidad legacy):
-- UPDATE cr_usuario_curso SET activo = 0;

-- =========================================================
-- 2) ESTRUCTURA - empresa_id en grupos
-- =========================================================
ALTER TABLE cr_grupos
  ADD COLUMN empresa_id INT UNSIGNED NOT NULL AFTER curso_id;

ALTER TABLE cr_grupos
  ADD KEY idx_grupos_empresa (empresa_id),
  ADD KEY idx_grupos_curso_empresa (curso_id, empresa_id),
  ADD KEY idx_grupos_empresa_curso_activo (empresa_id, curso_id, activo);

ALTER TABLE cr_matriculas_grupo
  ADD KEY idx_matricula_expulsado_by (expulsado_by);

-- =========================================================
-- 3) FOREIGN KEYS
-- =========================================================
ALTER TABLE cr_grupos
  ADD CONSTRAINT fk_cr_grupos_curso
    FOREIGN KEY (curso_id) REFERENCES cr_cursos(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  ADD CONSTRAINT fk_cr_grupos_empresa
    FOREIGN KEY (empresa_id) REFERENCES mtp_empresas(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE;

ALTER TABLE cr_matriculas_grupo
  ADD CONSTRAINT fk_cr_mg_grupo
    FOREIGN KEY (grupo_id) REFERENCES cr_grupos(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  ADD CONSTRAINT fk_cr_mg_usuario
    FOREIGN KEY (usuario_id) REFERENCES mtp_usuarios(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  ADD CONSTRAINT fk_cr_mg_curso
    FOREIGN KEY (curso_id) REFERENCES cr_cursos(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  ADD CONSTRAINT fk_cr_mg_expulsado_by
    FOREIGN KEY (expulsado_by) REFERENCES mtp_usuarios(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;
