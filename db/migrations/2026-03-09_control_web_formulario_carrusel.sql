CREATE TABLE IF NOT EXISTS web_formulario_carrusel_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    orden TINYINT UNSIGNED NOT NULL,
    titulo VARCHAR(140) NOT NULL,
    texto VARCHAR(260) NOT NULL,
    imagen_path VARCHAR(255) NOT NULL DEFAULT '',
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_web_formulario_carrusel_items_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO web_formulario_carrusel_items
    (id, orden, titulo, texto, imagen_path)
VALUES
    (
        1,
        1,
        'Capacitacion para conductores y empresas',
        'Recibe asesoria personalizada para cursos, recategorizaciones y tramites de licencias.',
        ''
    ),
    (
        2,
        2,
        'Programas corporativos con cobertura nacional',
        'Creamos convenios para empresas con soluciones de capacitacion en seguridad y normativa.',
        ''
    )
ON DUPLICATE KEY UPDATE
    id = id;

CREATE TABLE IF NOT EXISTS web_formulario_carrusel_mensajes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo_solicitante ENUM('persona', 'empresa') NOT NULL,
    servicio_codigo VARCHAR(80) NOT NULL,
    servicio_nombre VARCHAR(150) NOT NULL,
    ciudad_codigo VARCHAR(80) NOT NULL,
    ciudad_nombre VARCHAR(80) NOT NULL,
    escuela_nombre VARCHAR(120) NOT NULL,
    documento VARCHAR(20) NOT NULL,
    nombres_apellidos VARCHAR(140) NOT NULL DEFAULT '',
    razon_social VARCHAR(160) NOT NULL DEFAULT '',
    celular VARCHAR(20) NOT NULL,
    correo VARCHAR(150) NOT NULL DEFAULT '',
    horario_codigo VARCHAR(20) NOT NULL,
    horario_nombre VARCHAR(60) NOT NULL,
    estado ENUM('en_espera', 'contactado', 'venta_cerrada', 'no_cerrada', 'no_contesto') NOT NULL DEFAULT 'en_espera',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_web_formulario_carrusel_mensajes_estado (estado),
    KEY idx_web_formulario_carrusel_mensajes_fecha (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;