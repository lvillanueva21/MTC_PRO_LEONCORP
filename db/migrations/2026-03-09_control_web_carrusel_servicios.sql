CREATE TABLE IF NOT EXISTS web_carrusel_servicios_config (
    id TINYINT UNSIGNED NOT NULL,
    titulo_base VARCHAR(40) NOT NULL DEFAULT 'Vehicle',
    titulo_resaltado VARCHAR(40) NOT NULL DEFAULT 'Categories',
    descripcion_general VARCHAR(320) NOT NULL,
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_carrusel_servicios_config
    (id, titulo_base, titulo_resaltado, descripcion_general)
VALUES
    (
        1,
        'Vehicle',
        'Categories',
        'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,'
    )
ON DUPLICATE KEY UPDATE
    id = id;

CREATE TABLE IF NOT EXISTS web_carrusel_servicios_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    orden TINYINT UNSIGNED NOT NULL,
    titulo VARCHAR(80) NOT NULL,
    review_text VARCHAR(60) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 4,
    mostrar_estrellas TINYINT(1) NOT NULL DEFAULT 1,
    badge_text VARCHAR(80) NOT NULL,
    detalles_json LONGTEXT NOT NULL,
    boton_texto VARCHAR(50) NOT NULL DEFAULT 'Book Now',
    boton_url VARCHAR(255) NOT NULL DEFAULT '#',
    imagen_path VARCHAR(255) NOT NULL DEFAULT '',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_carrusel_servicios_items
    (id, orden, titulo, review_text, rating, mostrar_estrellas, badge_text, detalles_json, boton_texto, boton_url, imagen_path)
VALUES
    (
        1,
        1,
        'Mercedes Benz R3',
        '4.5 Review',
        4,
        1,
        '$99:00/Day',
        '[{"visible":1,"icono":"fa fa-users","texto":"4 Seat"},{"visible":1,"icono":"fa fa-car","texto":"AT/MT"},{"visible":1,"icono":"fa fa-gas-pump","texto":"Petrol"},{"visible":1,"icono":"fa fa-car","texto":"2015"},{"visible":1,"icono":"fa fa-cogs","texto":"AUTO"},{"visible":1,"icono":"fa fa-road","texto":"27K"}]',
        'Book Now',
        '#',
        ''
    ),
    (
        2,
        2,
        'Toyota Corolla Cross',
        '3.5 Review',
        4,
        1,
        '$128:00/Day',
        '[{"visible":1,"icono":"fa fa-users","texto":"4 Seat"},{"visible":1,"icono":"fa fa-car","texto":"AT/MT"},{"visible":1,"icono":"fa fa-gas-pump","texto":"Petrol"},{"visible":1,"icono":"fa fa-car","texto":"2015"},{"visible":1,"icono":"fa fa-cogs","texto":"AUTO"},{"visible":1,"icono":"fa fa-road","texto":"27K"}]',
        'Book Now',
        '#',
        ''
    ),
    (
        3,
        3,
        'Tesla Model S Plaid',
        '3.8 Review',
        4,
        1,
        '$170:00/Day',
        '[{"visible":1,"icono":"fa fa-users","texto":"4 Seat"},{"visible":1,"icono":"fa fa-car","texto":"AT/MT"},{"visible":1,"icono":"fa fa-gas-pump","texto":"Petrol"},{"visible":1,"icono":"fa fa-car","texto":"2015"},{"visible":1,"icono":"fa fa-cogs","texto":"AUTO"},{"visible":1,"icono":"fa fa-road","texto":"27K"}]',
        'Book Now',
        '#',
        ''
    ),
    (
        4,
        4,
        'Hyundai Kona Electric',
        '4.8 Review',
        5,
        1,
        '$187:00/Day',
        '[{"visible":1,"icono":"fa fa-users","texto":"4 Seat"},{"visible":1,"icono":"fa fa-car","texto":"AT/MT"},{"visible":1,"icono":"fa fa-gas-pump","texto":"Petrol"},{"visible":1,"icono":"fa fa-car","texto":"2015"},{"visible":1,"icono":"fa fa-cogs","texto":"AUTO"},{"visible":1,"icono":"fa fa-road","texto":"27K"}]',
        'Book Now',
        '#',
        ''
    )
ON DUPLICATE KEY UPDATE
    id = id;
