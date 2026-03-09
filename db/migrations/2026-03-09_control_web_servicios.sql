CREATE TABLE IF NOT EXISTS web_servicios (
    id TINYINT UNSIGNED NOT NULL,
    titulo_base VARCHAR(40) NOT NULL DEFAULT 'Cental',
    titulo_resaltado VARCHAR(40) NOT NULL DEFAULT 'Services',
    descripcion_general VARCHAR(320) NOT NULL,
    items_json LONGTEXT NOT NULL,
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_servicios
    (id, titulo_base, titulo_resaltado, descripcion_general, items_json)
VALUES
    (
        1,
        'Cental',
        'Services',
        'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
        '[{"icono":"fa fa-phone-alt fa-2x","titulo":"Phone Reservation","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Reprehenderit ipsam quasi quibusdam ipsa perferendis iusto?"},{"icono":"fa fa-money-bill-alt fa-2x","titulo":"Special Rates","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Reprehenderit ipsam quasi quibusdam ipsa perferendis iusto?"},{"icono":"fa fa-road fa-2x","titulo":"One Way Rental","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Reprehenderit ipsam quasi quibusdam ipsa perferendis iusto?"},{"icono":"fa fa-umbrella fa-2x","titulo":"Life Insurance","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Reprehenderit ipsam quasi quibusdam ipsa perferendis iusto?"},{"icono":"fa fa-building fa-2x","titulo":"City to City","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Reprehenderit ipsam quasi quibusdam ipsa perferendis iusto?"},{"icono":"fa fa-car-alt fa-2x","titulo":"Free Rides","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Reprehenderit ipsam quasi quibusdam ipsa perferendis iusto?"}]'
    )
ON DUPLICATE KEY UPDATE
    id = id;
