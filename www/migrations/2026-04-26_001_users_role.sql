-- 001 · users_role
-- Añade columna de rol a la tabla users para diferenciar admin de user.
-- Necesaria para PHP-G: restringir CSV export a admins.

ALTER TABLE `users`
    ADD COLUMN `users_role` VARCHAR(20) NOT NULL DEFAULT 'user'
        AFTER `users_email`;

-- Marcar al primer usuario como admin para no quedarse sin acceso.
-- Ajustar manualmente si necesitas otro criterio.
UPDATE `users`
   SET `users_role` = 'admin'
 WHERE `users_id` = (SELECT * FROM (SELECT MIN(users_id) FROM `users`) AS t);
