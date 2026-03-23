-- /modules/api_hub/sql/mod_api_hub_consulta_detalle.sql
CREATE TABLE IF NOT EXISTS mod_api_hub_consulta_detalle (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id INT NOT NULL,
  usuario_id INT NULL,
  periodo_mes DATE NOT NULL COMMENT 'Primer dia del mes, p.ej. 2026-03-01',
  tipo ENUM('DNI','RUC') NOT NULL,
  documento_masked VARCHAR(20) NOT NULL,
  documento_hash CHAR(64) NOT NULL,
  estado_final ENUM('OK','FAIL') NOT NULL,
  proveedor_final ENUM('apisperu','decolecta','jsonpe') NULL,
  token_label_final VARCHAR(60) NOT NULL DEFAULT '',
  fallback_usado TINYINT(1) NOT NULL DEFAULT 0,
  intentos_json LONGTEXT NOT NULL,
  mensaje_final VARCHAR(255) NOT NULL DEFAULT '',
  duracion_ms INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ahd_empresa_periodo (empresa_id, periodo_mes),
  KEY idx_ahd_periodo (periodo_mes),
  KEY idx_ahd_proveedor_periodo (proveedor_final, periodo_mes),
  KEY idx_ahd_tipo_periodo (tipo, periodo_mes),
  KEY idx_ahd_doc_hash (documento_hash),
  KEY idx_ahd_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

