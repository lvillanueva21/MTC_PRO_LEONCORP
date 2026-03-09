CREATE TABLE IF NOT EXISTS web_nosotros (
    id TINYINT UNSIGNED NOT NULL,
    titulo_base VARCHAR(40) NOT NULL DEFAULT 'Cental',
    titulo_resaltado VARCHAR(40) NOT NULL DEFAULT 'About',
    descripcion_principal VARCHAR(320) NOT NULL,
    tarjetas_json LONGTEXT NOT NULL,
    descripcion_secundaria VARCHAR(500) NOT NULL,
    experiencia_numero VARCHAR(10) NOT NULL DEFAULT '17',
    experiencia_texto VARCHAR(80) NOT NULL DEFAULT 'Years Of Experience',
    checklist_json LONGTEXT NOT NULL,
    boton_texto VARCHAR(80) NOT NULL DEFAULT 'More About Us',
    boton_url VARCHAR(255) NOT NULL DEFAULT '#',
    fundador_nombre VARCHAR(80) NOT NULL DEFAULT 'William Burgess',
    fundador_cargo VARCHAR(80) NOT NULL DEFAULT 'Carveo Founder',
    imagen_fundador_path VARCHAR(255) NOT NULL DEFAULT '',
    imagen_principal_path VARCHAR(255) NOT NULL DEFAULT '',
    imagen_secundaria_path VARCHAR(255) NOT NULL DEFAULT '',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_nosotros
    (id, titulo_base, titulo_resaltado, descripcion_principal, tarjetas_json, descripcion_secundaria, experiencia_numero, experiencia_texto, checklist_json, boton_texto, boton_url, fundador_nombre, fundador_cargo, imagen_fundador_path, imagen_principal_path, imagen_secundaria_path)
VALUES
    (
        1,
        'Cental',
        'About',
        'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,',
        '[{"icono_path":"","titulo":"Our Vision","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit."},{"icono_path":"","titulo":"Our Mision","texto":"Lorem ipsum dolor sit amet consectetur adipisicing elit."}]',
        'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Beatae, aliquam ipsum. Sed suscipit dolorem libero sequi aut natus debitis reprehenderit facilis quaerat similique, est at in eum. Quo, obcaecati in!',
        '17',
        'Years Of Experience',
        '["Morbi tristique senectus","A scelerisque purus","Dictumst vestibulum","dio aenean sed adipiscing"]',
        'More About Us',
        '#',
        'William Burgess',
        'Carveo Founder',
        '',
        '',
        ''
    )
ON DUPLICATE KEY UPDATE
    id = id;
