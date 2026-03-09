CREATE TABLE IF NOT EXISTS web_proceso (
    id TINYINT UNSIGNED NOT NULL,
    titulo_base VARCHAR(35) NOT NULL DEFAULT 'Cental',
    titulo_resaltado VARCHAR(35) NOT NULL DEFAULT 'Process',
    descripcion_general VARCHAR(280) NOT NULL,
    items_json LONGTEXT NOT NULL,
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_proceso
    (id, titulo_base, titulo_resaltado, descripcion_general, items_json)
VALUES
    (
        1,
        'Cental',
        'Process',
        'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
        '[{"titulo":"Come In Contact","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Ad, dolorem!"},{"titulo":"Choose A Car","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Ad, dolorem!"},{"titulo":"Enjoy Driving","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit. Ad, dolorem!"}]'
    )
ON DUPLICATE KEY UPDATE
    id = id;
