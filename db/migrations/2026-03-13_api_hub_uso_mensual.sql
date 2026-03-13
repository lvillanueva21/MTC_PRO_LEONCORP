-- /db/migrations/2026-03-13_api_hub_uso_mensual.sql
CREATE TABLE IF NOT EXISTS mod_api_hub_uso_mensual (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id INT NOT NULL,
  periodo_mes DATE NOT NULL COMMENT 'Primer día del mes, p.ej. 2026-03-01',
  dni_ok INT UNSIGNED NOT NULL DEFAULT 0,
  dni_fail INT UNSIGNED NOT NULL DEFAULT 0,
  ruc_ok INT UNSIGNED NOT NULL DEFAULT 0,
  ruc_fail INT UNSIGNED NOT NULL DEFAULT 0,
  ultima_consulta_at DATETIME NULL,
  ultima_tipo ENUM('DNI','RUC') NULL,
  ultima_estado ENUM('OK','FAIL') NULL,
  ultima_mensaje VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_api_hub_empresa_mes (empresa_id, periodo_mes),
  KEY idx_api_hub_periodo (periodo_mes),
  KEY idx_api_hub_empresa (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
