CREATE TABLE IF NOT EXISTS web_contadores (
    id TINYINT UNSIGNED NOT NULL,
    items_json LONGTEXT NOT NULL,
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_contadores
    (id, items_json)
VALUES
    (
        1,
        '[{"numero":"829","titulo":"Happy Clients"},{"numero":"56","titulo":"Number of Cars"},{"numero":"127","titulo":"Car Center"},{"numero":"589","titulo":"Total kilometers"}]'
    )
ON DUPLICATE KEY UPDATE
    id = id;
