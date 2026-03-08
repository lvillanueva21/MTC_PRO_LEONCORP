CREATE TABLE IF NOT EXISTS web_caracteristicas (
    id TINYINT UNSIGNED NOT NULL,
    titulo_rojo VARCHAR(40) NOT NULL DEFAULT 'Central',
    titulo_azul VARCHAR(40) NOT NULL DEFAULT 'Features',
    descripcion_general VARCHAR(320) NOT NULL,
    imagen_path VARCHAR(255) NOT NULL DEFAULT '',
    items_json LONGTEXT NOT NULL,
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_caracteristicas
    (id, titulo_rojo, titulo_azul, descripcion_general, imagen_path, items_json)
VALUES
    (
        1,
        'Central',
        'Features',
        'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
        '',
        '[{"icono":"fa fa-trophy fa-2x","titulo":"First Class services","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur, in illum aperiam ullam magni eligendi?"},{"icono":"fa fa-road fa-2x","titulo":"24/7 road assistance","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur, in illum aperiam ullam magni eligendi?"},{"icono":"fa fa-tag fa-2x","titulo":"Quality at Minimum","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur, in illum aperiam ullam magni eligendi?"},{"icono":"fa fa-map-pin fa-2x","titulo":"Free Pick-Up & Drop-Off","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Consectetur, in illum aperiam ullam magni eligendi?"}]'
    )
ON DUPLICATE KEY UPDATE
    id = id;
