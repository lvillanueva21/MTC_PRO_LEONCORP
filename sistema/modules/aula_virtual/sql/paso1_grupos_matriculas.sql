-- Paso 1 - Aula Virtual: grupos y matriculas por grupo
-- Ejecutar en la base de datos activa antes de usar el nuevo flujo.

CREATE TABLE IF NOT EXISTS cr_grupos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  curso_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion VARCHAR(255) NULL,
  inicio_at DATETIME NULL,
  fin_at DATETIME NULL,
  codigo VARCHAR(32) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_grupos_curso (curso_id),
  UNIQUE KEY uq_grupos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS cr_matriculas_grupo (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  curso_id INT UNSIGNED NOT NULL,
  grupo_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  matriculado_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expulsado_at DATETIME NULL,
  expulsado_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_matricula_usuario_curso (usuario_id, curso_id),
  KEY idx_matricula_grupo (grupo_id),
  KEY idx_matricula_estado (estado),
  KEY idx_matricula_curso (curso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
