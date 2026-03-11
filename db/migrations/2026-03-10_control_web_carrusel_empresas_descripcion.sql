ALTER TABLE web_carrusel_empresas_config
    ADD COLUMN IF NOT EXISTS descripcion_general VARCHAR(260) NOT NULL
    DEFAULT 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,'
    AFTER titulo_resaltado;

UPDATE web_carrusel_empresas_config
SET descripcion_general = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut amet nemo expedita asperiores commodi accusantium at cum harum, excepturi, quia tempora cupiditate! Adipisci facilis modi quisquam quia distinctio,'
WHERE TRIM(COALESCE(descripcion_general, '')) = '';
