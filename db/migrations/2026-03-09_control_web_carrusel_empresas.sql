CREATE TABLE IF NOT EXISTS web_carrusel_empresas_config (
    id TINYINT UNSIGNED NOT NULL,
    titulo_base VARCHAR(40) NOT NULL DEFAULT 'Customer',
    titulo_resaltado VARCHAR(40) NOT NULL DEFAULT 'Suport Center',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_carrusel_empresas_config
    (id, titulo_base, titulo_resaltado)
VALUES
    (1, 'Customer', 'Suport Center')
ON DUPLICATE KEY UPDATE
    id = id;

CREATE TABLE IF NOT EXISTS web_carrusel_empresas_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    orden TINYINT UNSIGNED NOT NULL,
    titulo VARCHAR(80) NOT NULL,
    profesion VARCHAR(80) NOT NULL,
    imagen_path VARCHAR(255) NOT NULL DEFAULT '',
    redes_json LONGTEXT NOT NULL,
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_carrusel_empresas_items
    (id, orden, titulo, profesion, imagen_path, redes_json)
VALUES
    (
        1,
        1,
        'MARTIN DOE',
        'Profession',
        '',
        '{"whatsapp":{"visible":1,"link":"#"},"facebook":{"visible":1,"link":"#"},"instagram":{"visible":1,"link":"#"},"youtube":{"visible":1,"link":"#"}}'
    ),
    (
        2,
        2,
        'MARTIN DOE',
        'Profession',
        '',
        '{"whatsapp":{"visible":1,"link":"#"},"facebook":{"visible":1,"link":"#"},"instagram":{"visible":1,"link":"#"},"youtube":{"visible":1,"link":"#"}}'
    ),
    (
        3,
        3,
        'MARTIN DOE',
        'Profession',
        '',
        '{"whatsapp":{"visible":1,"link":"#"},"facebook":{"visible":1,"link":"#"},"instagram":{"visible":1,"link":"#"},"youtube":{"visible":1,"link":"#"}}'
    ),
    (
        4,
        4,
        'MARTIN DOE',
        'Profession',
        '',
        '{"whatsapp":{"visible":1,"link":"#"},"facebook":{"visible":1,"link":"#"},"instagram":{"visible":1,"link":"#"},"youtube":{"visible":1,"link":"#"}}'
    )
ON DUPLICATE KEY UPDATE
    id = id;
