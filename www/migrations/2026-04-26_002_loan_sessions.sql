-- 002 · loan_sessions
-- Persiste el estado de la sesión de préstamo del backend (countLoanCard /
-- serialLoanUser que vivían en RAM del proceso flutter-api).
-- BE-B: si el contenedor reinicia con un préstamo abierto, la sesión se
-- recupera de aquí y el usuario puede devolver la herramienta sin reloguear.

CREATE TABLE IF NOT EXISTS `loan_sessions` (
    `device_serie` VARCHAR(40) NOT NULL,
    `hab_id`       INT(11)     NOT NULL,
    `hab_name`     VARCHAR(100) NOT NULL,
    `cards_number` VARCHAR(40) NOT NULL,
    `started_at`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`   TIMESTAMP   NULL DEFAULT NULL,
    PRIMARY KEY (`device_serie`),
    INDEX `idx_loan_sessions_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
