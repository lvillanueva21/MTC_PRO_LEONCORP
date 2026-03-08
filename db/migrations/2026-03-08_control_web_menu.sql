CREATE TABLE IF NOT EXISTS web_menu (
    id TINYINT UNSIGNED NOT NULL,
    titulo_pagina VARCHAR(120) NOT NULL,
    logo_path VARCHAR(255) NOT NULL DEFAULT '',
    menu_items_json LONGTEXT NOT NULL,
    boton_texto VARCHAR(80) NOT NULL,
    boton_url VARCHAR(255) NOT NULL,
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_menu
    (id, titulo_pagina, logo_path, menu_items_json, boton_texto, boton_url)
VALUES
    (
        1,
        'Cental',
        '',
        '[{"texto":"Home","url":"/","visible":1,"submenus":[]},{"texto":"About","url":"/web/about.html","visible":1,"submenus":[]},{"texto":"Service","url":"/web/service.html","visible":1,"submenus":[]},{"texto":"Blog","url":"/web/blog.html","visible":1,"submenus":[]},{"texto":"Pages","url":"#","visible":1,"submenus":[{"texto":"Our Feature","url":"/web/feature.html","visible":1},{"texto":"Our Cars","url":"/web/cars.html","visible":1},{"texto":"Our Team","url":"/web/team.html","visible":1},{"texto":"Testimonial","url":"/web/testimonial.html","visible":1},{"texto":"404 Page","url":"/web/404.html","visible":1}]},{"texto":"Contact","url":"/web/contact.html","visible":1,"submenus":[]}]',
        'Get Started',
        '#'
    )
ON DUPLICATE KEY UPDATE
    id = id;
