CREATE TABLE IF NOT EXISTS web_novedades_config (
    id TINYINT UNSIGNED NOT NULL,
    titulo_base VARCHAR(40) NOT NULL DEFAULT 'Cental',
    titulo_resaltado VARCHAR(40) NOT NULL DEFAULT 'Blog & News',
    descripcion_general VARCHAR(280) NOT NULL DEFAULT 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_novedades_config
    (id, titulo_base, titulo_resaltado, descripcion_general)
VALUES
    (
        1,
        'Cental',
        'Blog & News',
        'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,'
    )
ON DUPLICATE KEY UPDATE
    id = id;

CREATE TABLE IF NOT EXISTS web_novedades_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    orden TINYINT UNSIGNED NOT NULL,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    titulo VARCHAR(110) NOT NULL,
    meta_1_icono VARCHAR(120) NOT NULL DEFAULT 'fa fa-user text-primary',
    meta_1_texto VARCHAR(80) NOT NULL DEFAULT 'Autor',
    meta_2_icono VARCHAR(120) NOT NULL DEFAULT 'fa fa-comment-alt text-primary',
    meta_2_texto VARCHAR(80) NOT NULL DEFAULT 'Sin comentarios',
    badge_texto VARCHAR(50) NOT NULL DEFAULT 'Novedad',
    resumen_texto VARCHAR(220) NOT NULL,
    boton_texto VARCHAR(50) NOT NULL DEFAULT 'Read More',
    boton_url VARCHAR(255) NOT NULL DEFAULT '#',
    imagen_path VARCHAR(255) NOT NULL DEFAULT '',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_web_novedades_items_orden (orden),
    KEY idx_web_novedades_items_visible (visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_novedades_items
    (id, orden, visible, titulo, meta_1_icono, meta_1_texto, meta_2_icono, meta_2_texto, badge_texto, resumen_texto, boton_texto, boton_url, imagen_path)
VALUES
    (
        1,
        1,
        1,
        'Rental Cars how to check driving fines?',
        'fa fa-user text-primary',
        'Martin.C',
        'fa fa-comment-alt text-primary',
        '6 Comments',
        '30 Dec 2025',
        'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eius libero soluta impedit eligendi? Quibusdam, laudantium.',
        'Read More',
        '#',
        ''
    ),
    (
        2,
        2,
        1,
        'Rental cost of sport and other cars',
        'fa fa-user text-primary',
        'Martin.C',
        'fa fa-comment-alt text-primary',
        '6 Comments',
        '25 Dec 2025',
        'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eius libero soluta impedit eligendi? Quibusdam, laudantium.',
        'Read More',
        '#',
        ''
    ),
    (
        3,
        3,
        1,
        'Document required for car rental',
        'fa fa-user text-primary',
        'Martin.C',
        'fa fa-comment-alt text-primary',
        '6 Comments',
        '27 Dec 2025',
        'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eius libero soluta impedit eligendi? Quibusdam, laudantium.',
        'Read More',
        '#',
        ''
    )
ON DUPLICATE KEY UPDATE
    id = id;

