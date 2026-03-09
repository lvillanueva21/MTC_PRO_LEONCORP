CREATE TABLE IF NOT EXISTS web_banner (
    id TINYINT UNSIGNED NOT NULL,
    titulo_superior VARCHAR(60) NOT NULL DEFAULT 'Rent Your Car',
    titulo_principal VARCHAR(100) NOT NULL DEFAULT 'Interested in Renting?',
    descripcion VARCHAR(220) NOT NULL,
    boton_1_texto VARCHAR(40) NOT NULL,
    boton_1_url VARCHAR(255) NOT NULL,
    boton_2_texto VARCHAR(40) NOT NULL,
    boton_2_url VARCHAR(255) NOT NULL,
    imagen_path VARCHAR(255) NOT NULL DEFAULT '',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_banner
    (id, titulo_superior, titulo_principal, descripcion, boton_1_texto, boton_1_url, boton_2_texto, boton_2_url, imagen_path)
VALUES
    (
        1,
        'Rent Your Car',
        'Interested in Renting?',
        'Don''t hesitate and send us a message.',
        'WhatchApp',
        '#',
        'Contact Us',
        '#',
        ''
    )
ON DUPLICATE KEY UPDATE
    id = id;
