CREATE TABLE IF NOT EXISTS web_testimonios_config (
    id TINYINT UNSIGNED NOT NULL,
    titulo_base VARCHAR(40) NOT NULL DEFAULT 'Our Clients',
    titulo_resaltado VARCHAR(40) NOT NULL DEFAULT 'Riviews',
    descripcion_general VARCHAR(260) NOT NULL DEFAULT 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_testimonios_config
    (id, titulo_base, titulo_resaltado, descripcion_general)
VALUES
    (
        1,
        'Our Clients',
        'Riviews',
        'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,'
    )
ON DUPLICATE KEY UPDATE
    id = id;

CREATE TABLE IF NOT EXISTS web_testimonios_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    orden TINYINT UNSIGNED NOT NULL,
    nombre_cliente VARCHAR(80) NOT NULL,
    profesion VARCHAR(80) NOT NULL,
    testimonio VARCHAR(280) NOT NULL,
    imagen_path VARCHAR(255) NOT NULL DEFAULT '',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_web_testimonios_items_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_testimonios_items
    (id, orden, nombre_cliente, profesion, testimonio, imagen_path)
VALUES
    (
        1,
        1,
        'Person Name',
        'Profession',
        'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quam soluta neque ab repudiandae reprehenderit ipsum eos cumque esse repellendus impedit.',
        ''
    ),
    (
        2,
        2,
        'Person Name',
        'Profession',
        'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quam soluta neque ab repudiandae reprehenderit ipsum eos cumque esse repellendus impedit.',
        ''
    )
ON DUPLICATE KEY UPDATE
    id = id;
