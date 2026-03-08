CREATE TABLE IF NOT EXISTS web_topbar_config (
    id TINYINT UNSIGNED NOT NULL,
    direccion VARCHAR(180) NOT NULL,
    telefono CHAR(9) NOT NULL,
    correo VARCHAR(120) NOT NULL,
    whatsapp_url VARCHAR(255) NOT NULL,
    facebook_url VARCHAR(255) NOT NULL DEFAULT '',
    instagram_url VARCHAR(255) NOT NULL DEFAULT '',
    youtube_url VARCHAR(255) NOT NULL DEFAULT '',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_topbar_config
    (id, direccion, telefono, correo, whatsapp_url, facebook_url, instagram_url, youtube_url)
VALUES
    (
        1,
        'Find A Location',
        '912345678',
        'example@gmail.com',
        'https://wa.me/51912345678',
        'https://facebook.com/',
        'https://instagram.com/',
        'https://youtube.com/'
    )
ON DUPLICATE KEY UPDATE
    id = id;
