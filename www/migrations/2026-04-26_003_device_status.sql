-- 003 · device_status
-- Registra online/offline de las estaciones IoT a partir de los mensajes
-- retained {SN}/status que publica el firmware nuevo (LWT MQTT).
-- BE-E: el backend se suscribe a +/status y mantiene esta tabla para que
-- la UI pueda mostrar qué estaciones están caídas.

CREATE TABLE IF NOT EXISTS `device_status` (
    `device_serie` VARCHAR(40) NOT NULL,
    `status`       ENUM('online','offline') NOT NULL DEFAULT 'offline',
    `updated_at`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`device_serie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
