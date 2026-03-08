-- Aula Virtual - Formularios EXAMEN v1
-- Fecha: 2026-03
-- Alcance: solo tablas cr_ nuevas para FAST/AULA (tipo EXAMEN en esta fase)
--
-- Nota importante:
-- - Se usan referencias a tablas maestras existentes (mtp_*, cq_*, cr_* base).
-- - No se modifica estructura de tablas mtp_*.
-- - Las validaciones de negocio (modo AULA requiere grupo, total de puntos=20, etc.)
--   se controlan desde API para mantener compatibilidad entre versiones de MySQL.

SET NAMES utf8mb4;

-- =========================================================
-- 1) FORMULARIOS
-- =========================================================
CREATE TABLE IF NOT EXISTS cr_formularios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id INT UNSIGNED NOT NULL,
  modo ENUM('FAST','AULA') NOT NULL,
  tipo ENUM('EXAMEN','TEST','ENCUESTA') NOT NULL DEFAULT 'EXAMEN',
  grupo_id INT UNSIGNED DEFAULT NULL,
  curso_id INT UNSIGNED DEFAULT NULL,
  tema_id INT UNSIGNED DEFAULT NULL,
  titulo VARCHAR(180) NOT NULL,
  descripcion TEXT DEFAULT NULL,
  estado ENUM('BORRADOR','PUBLICADO','CERRADO') NOT NULL DEFAULT 'BORRADOR',
  intentos_max INT UNSIGNED NOT NULL DEFAULT 1,
  tiempo_activo TINYINT(1) NOT NULL DEFAULT 0,
  duracion_min INT UNSIGNED DEFAULT NULL,
  nota_min DECIMAL(5,2) NOT NULL DEFAULT 11.00,
  mostrar_resultado TINYINT(1) NOT NULL DEFAULT 1,
  requisito_cumplimiento ENUM('ENVIAR','APROBAR') NOT NULL DEFAULT 'ENVIAR',
  campos_fast LONGTEXT DEFAULT NULL,
  public_code VARCHAR(32) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cr_form_public_code (public_code),
  KEY idx_cr_form_empresa_modo_estado (empresa_id, modo, estado),
  KEY idx_cr_form_grupo (grupo_id),
  KEY idx_cr_form_curso_tema (curso_id, tema_id),
  CONSTRAINT fk_cr_form_empresa
    FOREIGN KEY (empresa_id) REFERENCES mtp_empresas(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_cr_form_grupo
    FOREIGN KEY (grupo_id) REFERENCES cr_grupos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_cr_form_curso
    FOREIGN KEY (curso_id) REFERENCES cr_cursos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_cr_form_tema
    FOREIGN KEY (tema_id) REFERENCES cr_temas(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =========================================================
-- 2) PREGUNTAS
-- =========================================================
CREATE TABLE IF NOT EXISTS cr_formulario_preguntas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  formulario_id INT UNSIGNED NOT NULL,
  tipo ENUM('OM_UNICA','OM_MULTIPLE') NOT NULL,
  enunciado TEXT NOT NULL,
  puntos DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  orden INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cr_fp_form_orden (formulario_id, orden),
  CONSTRAINT fk_cr_fp_formulario
    FOREIGN KEY (formulario_id) REFERENCES cr_formularios(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =========================================================
-- 3) OPCIONES
-- =========================================================
CREATE TABLE IF NOT EXISTS cr_formulario_opciones (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pregunta_id INT UNSIGNED NOT NULL,
  texto VARCHAR(255) NOT NULL,
  es_correcta TINYINT(1) NOT NULL DEFAULT 0,
  orden INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cr_fo_preg_orden (pregunta_id, orden),
  CONSTRAINT fk_cr_fo_pregunta
    FOREIGN KEY (pregunta_id) REFERENCES cr_formulario_preguntas(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =========================================================
-- 4) INTENTOS
-- =========================================================
CREATE TABLE IF NOT EXISTS cr_formulario_intentos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  formulario_id INT UNSIGNED NOT NULL,
  modo ENUM('FAST','AULA') NOT NULL,
  usuario_id INT UNSIGNED DEFAULT NULL,
  tipo_doc_id TINYINT UNSIGNED DEFAULT NULL,
  nro_doc VARCHAR(20) DEFAULT NULL,
  nombres VARCHAR(120) DEFAULT NULL,
  apellidos VARCHAR(120) DEFAULT NULL,
  celular VARCHAR(20) DEFAULT NULL,
  categorias_json LONGTEXT DEFAULT NULL,
  intento_nro INT UNSIGNED NOT NULL,
  token VARCHAR(64) NOT NULL,
  status ENUM('EN_PROGRESO','ENVIADO','EXPIRADO') NOT NULL DEFAULT 'EN_PROGRESO',
  start_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME DEFAULT NULL,
  submitted_at DATETIME DEFAULT NULL,
  last_saved_at DATETIME DEFAULT NULL,
  puntaje_obtenido DECIMAL(6,2) DEFAULT NULL,
  nota_final DECIMAL(6,2) DEFAULT NULL,
  aprobado TINYINT(1) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cr_fi_token (token),
  UNIQUE KEY uq_cr_fi_fast (formulario_id, tipo_doc_id, nro_doc, intento_nro),
  UNIQUE KEY uq_cr_fi_aula (formulario_id, usuario_id, intento_nro),
  KEY idx_cr_fi_form_status (formulario_id, status),
  KEY idx_cr_fi_form_doc (formulario_id, tipo_doc_id, nro_doc),
  KEY idx_cr_fi_form_user (formulario_id, usuario_id),
  CONSTRAINT fk_cr_fi_formulario
    FOREIGN KEY (formulario_id) REFERENCES cr_formularios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cr_fi_usuario
    FOREIGN KEY (usuario_id) REFERENCES mtp_usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_cr_fi_tipo_doc
    FOREIGN KEY (tipo_doc_id) REFERENCES cq_tipos_documento(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =========================================================
-- 5) CATEGORIAS POR INTENTO (tabla puente)
-- =========================================================
CREATE TABLE IF NOT EXISTS cr_formulario_intento_categoria (
  intento_id BIGINT UNSIGNED NOT NULL,
  categoria_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (intento_id, categoria_id),
  KEY idx_cr_fic_categoria (categoria_id),
  CONSTRAINT fk_cr_fic_intento
    FOREIGN KEY (intento_id) REFERENCES cr_formulario_intentos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cr_fic_categoria
    FOREIGN KEY (categoria_id) REFERENCES cq_categorias_licencia(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =========================================================
-- 6) RESPUESTAS
-- =========================================================
CREATE TABLE IF NOT EXISTS cr_formulario_respuestas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  intento_id BIGINT UNSIGNED NOT NULL,
  pregunta_id INT UNSIGNED NOT NULL,
  respuesta_json LONGTEXT DEFAULT NULL,
  is_correct TINYINT(1) DEFAULT NULL,
  puntos_obtenidos DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cr_fr_intento_pregunta (intento_id, pregunta_id),
  KEY idx_cr_fr_pregunta (pregunta_id),
  CONSTRAINT fk_cr_fr_intento
    FOREIGN KEY (intento_id) REFERENCES cr_formulario_intentos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cr_fr_pregunta
    FOREIGN KEY (pregunta_id) REFERENCES cr_formulario_preguntas(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
