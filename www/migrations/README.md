# SMARTLABS · Migraciones SQL

Migraciones manuales que hay que aplicar al schema MariaDB. Ejecutar en orden
contra la base `emqx`.

## Cómo aplicar

Desde el host (con docker compose levantado):

```bash
cat www/migrations/2026-04-26_001_users_role.sql | \
    docker exec -i smartlabs-mariadb mysql -uemqxuser -pemqxpass emqx
```

O desde phpMyAdmin (puerto 8080) → SQL → pegar el contenido.

## Migraciones

| ID | Archivo | Propósito |
|---|---|---|
| 001 | `2026-04-26_001_users_role.sql` | Añade `users.users_role` para diferenciar admin de user (necesario para PHP-G: role check en CSV export). |
| 002 | `2026-04-26_002_loan_sessions.sql` | Tabla `loan_sessions` para persistir la sesión de préstamo del backend (BE-B). |
| 003 | `2026-04-26_003_device_status.sql` | Tabla `device_status` para registrar online/offline desde MQTT LWT (BE-E). |

## Cómo marcar un usuario como admin

Tras aplicar la migración 001, los usuarios existentes son `user` por default.
Para promover a admin:

```sql
UPDATE users SET users_role = 'admin' WHERE users_email = 'tu_correo@dominio';
```
