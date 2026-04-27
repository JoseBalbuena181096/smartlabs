const dbConfig = require('../config/database');
const mqttConfig = require('../config/mqtt');

/**
 * Servicio de préstamo de equipos.
 *
 * Cambios respecto a la versión anterior:
 *   - BE-D: usa el cliente MQTT singleton (config/mqtt.js) en lugar de abrir
 *     una conexión propia.
 *   - BE-F: usa el pool MySQL (config/database.js) en lugar de
 *     mysql.createConnection en cada método.
 *   - BE-B: la sesión de préstamo (countLoanCard / serialLoanUser) se
 *     persiste en la tabla `loan_sessions` (migración 002). Sobrevive
 *     reinicios del proceso, soporta varios pares USUARIO+HERRAMIENTA y
 *     evita la race condition implícita gracias al PRIMARY KEY por device.
 *   - BE-I: ya no publica `APP:rfid` desde procesarPrestamo (era código
 *     zombie que el filtro luego ignoraba).
 *   - El método handleLoanUserQuery publica `nofound` cuando el usuario
 *     no existe (ya estaba aplicado en commit anterior, mantenido).
 */
class PrestamoService {
    constructor() {
        // Sin estado en memoria: todo va a `loan_sessions`.
    }

    // ---------------------------------------------------------------
    // MQTT helpers (singleton)
    // ---------------------------------------------------------------
    async _publish(topic, message) {
        try {
            if (!mqttConfig.isConnected()) {
                console.warn('⚠️ MQTT no conectado, no se publicó en', topic);
                return false;
            }
            await mqttConfig.publish(topic, String(message));
            return true;
        } catch (err) {
            console.error('❌ Error publicando MQTT', topic, err.message);
            return false;
        }
    }

    /**
     * Publica user_name y/o command para una estación.
     */
    async enviarComandosMQTT(deviceSerie, userName, command) {
        if (userName) {
            await this._publish(`${deviceSerie}/user_name`, userName);
        }
        if (command) {
            await this._publish(`${deviceSerie}/command`, command);
        }
        return { success: true, topic: `${deviceSerie}/command`, message: command };
    }

    // ---------------------------------------------------------------
    // DB helpers (pool)
    // ---------------------------------------------------------------
    async getUserByRegistration(registration) {
        const rows = await dbConfig.execute(
            `SELECT
                h.hab_id, h.hab_date, h.hab_name, h.hab_registration, h.hab_email,
                h.hab_card_id, h.hab_device_id,
                ch.cards_number, ch.cards_assigned
             FROM habintants h
             LEFT JOIN cards_habs ch ON h.hab_id = ch.hab_id
             WHERE h.hab_registration = ?`,
            [registration]
        );
        return rows.length ? rows[0] : null;
    }

    async getUserByRFID(rfidNumber) {
        const rows = await dbConfig.execute(
            `SELECT cards_id, cards_number, cards_assigned, hab_id, hab_name, hab_device_id
             FROM cards_habs
             WHERE cards_number = ?`,
            [rfidNumber]
        );
        return rows.length ? rows[0] : null;
    }

    async getDeviceBySerie(deviceSerie) {
        const rows = await dbConfig.execute(
            'SELECT * FROM devices WHERE devices_serie = ?',
            [deviceSerie]
        );
        return rows.length ? rows[0] : null;
    }

    async getEquipmentByRFID(equipRFID) {
        const rows = await dbConfig.execute(
            'SELECT * FROM equipments WHERE equipments_rfid = ?',
            [equipRFID]
        );
        return rows.length ? rows[0] : null;
    }

    async getLastLoan(equipRFID) {
        const rows = await dbConfig.execute(
            'SELECT * FROM loans WHERE loans_equip_rfid = ? ORDER BY loans_date DESC LIMIT 1',
            [equipRFID]
        );
        return rows.length ? rows[0] : null;
    }

    async registrarPrestamo(userRFID, equipRFID, state) {
        try {
            await dbConfig.execute(
                `INSERT INTO loans (loans_date, loans_hab_rfid, loans_equip_rfid, loans_state)
                 VALUES (CURRENT_TIMESTAMP, ?, ?, ?)`,
                [userRFID, equipRFID, state]
            );
            return true;
        } catch (err) {
            console.error('❌ Error registrando préstamo:', err.message);
            return false;
        }
    }

    async registrarTrafico(userId, deviceSerie, state) {
        try {
            await dbConfig.execute(
                `INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state)
                 VALUES (CURRENT_TIMESTAMP, ?, ?, ?)`,
                [userId, deviceSerie, state]
            );
            return true;
        } catch (err) {
            console.error('❌ Error registrando tráfico:', err.message);
            return false;
        }
    }

    // ---------------------------------------------------------------
    // Sesión persistente (tabla loan_sessions, migración 002)
    // ---------------------------------------------------------------
    async _loadSession(deviceSerie) {
        try {
            const rows = await dbConfig.execute(
                'SELECT * FROM loan_sessions WHERE device_serie = ? LIMIT 1',
                [deviceSerie]
            );
            return rows.length ? rows[0] : null;
        } catch (err) {
            // Si la migración 002 aún no se aplicó, degradar a null para no romper
            // (modo legacy: no hay sesión persistida, equivale a sin login).
            if (err && err.code === 'ER_NO_SUCH_TABLE') {
                console.warn('⚠️ Tabla loan_sessions no existe (aplica migración 002)');
                return null;
            }
            throw err;
        }
    }

    async _openSession(deviceSerie, user, ttlMs = 150000) {
        const expiresAt = new Date(Date.now() + ttlMs);
        try {
            await dbConfig.execute(
                `INSERT INTO loan_sessions (device_serie, hab_id, hab_name, cards_number, expires_at)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    hab_id = VALUES(hab_id),
                    hab_name = VALUES(hab_name),
                    cards_number = VALUES(cards_number),
                    started_at = CURRENT_TIMESTAMP,
                    expires_at = VALUES(expires_at)`,
                [deviceSerie, user.hab_id, user.hab_name, user.cards_number, expiresAt]
            );
            return true;
        } catch (err) {
            if (err && err.code === 'ER_NO_SUCH_TABLE') {
                console.warn('⚠️ Sesión no persistida: aplica migración 002 (loan_sessions).');
                return false;
            }
            throw err;
        }
    }

    async _closeSession(deviceSerie) {
        try {
            await dbConfig.execute(
                'DELETE FROM loan_sessions WHERE device_serie = ?',
                [deviceSerie]
            );
        } catch (err) {
            if (err && err.code !== 'ER_NO_SUCH_TABLE') throw err;
        }
    }

    /**
     * Devuelve la sesión activa más reciente para emparejar el lector
     * USUARIO con el lector HERRAMIENTA (cuando una estación tiene un solo
     * par, la sesión es global de facto).
     */
    async _latestActiveSession() {
        try {
            const rows = await dbConfig.execute(
                `SELECT * FROM loan_sessions
                 WHERE expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP
                 ORDER BY started_at DESC LIMIT 1`
            );
            return rows.length ? rows[0] : null;
        } catch (err) {
            if (err && err.code === 'ER_NO_SUCH_TABLE') return null;
            throw err;
        }
    }

    /**
     * API back-compat con el viejo getSessionState() en RAM.
     */
    async getSessionState(deviceSerie) {
        const session = deviceSerie
            ? await this._loadSession(deviceSerie)
            : await this._latestActiveSession();

        if (!session) {
            return { active: false, user: null, count: 0 };
        }
        return {
            active: true,
            user: session.hab_name,
            count: 1,
            device_serie: session.device_serie,
            cards_number: session.cards_number,
            hab_id: session.hab_id,
        };
    }

    // ---------------------------------------------------------------
    // Lógica de préstamo
    // ---------------------------------------------------------------

    /**
     * Consulta de usuario en lector USUARIO. Toggle: si hay sesión
     * abierta para ese device → cierra. Si no → abre.
     */
    async handleLoanUserQuery(deviceSerie, userRFID) {
        try {
            const user = await this.getUserByRFID(userRFID);

            if (!user) {
                await this.enviarComandosMQTT(deviceSerie, null, 'nofound');
                return { success: false, message: 'Usuario no encontrado', data: { rfid: userRFID } };
            }

            const session = await this._loadSession(deviceSerie);

            if (session) {
                // Cerrar sesión
                await this._closeSession(deviceSerie);
                await this.enviarComandosMQTT(deviceSerie, null, 'unload');
                return {
                    success: true,
                    message: 'Sesión finalizada',
                    data: { usuario: user.hab_name, rfid: userRFID, estado: 'sesión finalizada' },
                };
            }

            // Abrir sesión nueva
            await this._openSession(deviceSerie, user);
            await this.enviarComandosMQTT(deviceSerie, user.hab_name, 'found');
            return {
                success: true,
                message: 'Usuario autenticado para préstamo',
                data: { usuario: user.hab_name, rfid: userRFID, estado: 'autenticado' },
            };
        } catch (err) {
            console.error('❌ Error handleLoanUserQuery:', err);
            return { success: false, message: 'Error interno', error: err.message };
        }
    }

    /**
     * Consulta de equipo en lector HERRAMIENTA (o lector unificado con
     * sesión activa). Hoy maneja tres casos sobre el mismo UID entrante:
     *
     *   1. Es la credencial del usuario actual → cierra sesión (`unload`).
     *      Esto permite al firmware unificado cerrar pasando la credencial
     *      sin tener que decidir el topic.
     *   2. Es la credencial de OTRO usuario → publica `refused`. Evita que
     *      alguien aproveche un descuido para apropiarse de la sesión.
     *   3. Es un tag de equipment → flujo de préstamo/devolución.
     *
     * Si no hay sesión activa, responde `nologin`.
     * Cualquier interacción refresca `expires_at` para que el timeout local
     * y el del backend queden alineados.
     */
    async handleLoanEquipmentQuery(deviceSerie, uid) {
        try {
            const session = await this._latestActiveSession();
            if (!session) {
                await this.enviarComandosMQTT(deviceSerie, null, 'nologin');
                return { success: false, message: 'No hay usuario logueado', action: 'no_login' };
            }

            // Caso 1: cierre con la credencial del usuario actual
            if (uid === session.cards_number) {
                await this._closeSession(session.device_serie);
                await this.enviarComandosMQTT(deviceSerie, null, 'unload');
                return {
                    success: true,
                    message: 'Sesión finalizada con credencial',
                    action: 'unload',
                    user: session.hab_name,
                };
            }

            // Caso 2: credencial de OTRO usuario (rechazar)
            const otherUser = await this.getUserByRFID(uid);
            if (otherUser) {
                await this.enviarComandosMQTT(deviceSerie, null, 'refused');
                return {
                    success: false,
                    message: 'Credencial de otro usuario en sesión activa',
                    action: 'refused',
                };
            }

            // Caso 3: equipment
            const equipment = await this.getEquipmentByRFID(uid);
            if (!equipment) {
                await this.enviarComandosMQTT(deviceSerie, null, 'nofound');
                return { success: false, message: 'Equipo no encontrado', action: 'equipment_not_found' };
            }

            await this.enviarComandosMQTT(deviceSerie, equipment.equipments_name, null);

            const lastLoan = await this.getLastLoan(equipment.equipments_rfid);
            let newLoanState = 1;
            if (lastLoan) {
                newLoanState = lastLoan.loans_state === 1 ? 0 : 1;
            }

            const userRFID = session.cards_number;
            if (!userRFID) {
                console.error('❌ Usuario en sesión sin RFID asignado:', session);
                return { success: false, message: 'Usuario sin RFID', action: 'no_rfid' };
            }

            const ok = await this.registrarPrestamo(userRFID, equipment.equipments_rfid, newLoanState);
            if (!ok) {
                return { success: false, message: 'Error registrando préstamo', action: 'database_error' };
            }

            // Refrescar TTL de la sesión (cualquier préstamo cuenta como actividad).
            await this._refreshSession(session.device_serie);

            const command = newLoanState === 1 ? 'prestado' : 'devuelto';
            await this.enviarComandosMQTT(deviceSerie, null, command);

            return {
                success: true,
                message: `Equipo ${command} exitosamente`,
                action: command,
                equipment: equipment.equipments_name,
                user: session.hab_name,
                state: newLoanState,
            };
        } catch (err) {
            console.error('❌ Error handleLoanEquipmentQuery:', err);
            return { success: false, message: 'Error interno', error: err.message };
        }
    }

    /**
     * Refresca expires_at de una sesión activa (extiende otros 150 s).
     * Usado tras cada préstamo/devolución para que el timeout cuente
     * inactividad real, no tiempo desde el login.
     */
    async _refreshSession(deviceSerie, ttlMs = 150000) {
        const expiresAt = new Date(Date.now() + ttlMs);
        try {
            await dbConfig.execute(
                'UPDATE loan_sessions SET expires_at = ? WHERE device_serie = ?',
                [expiresAt, deviceSerie]
            );
        } catch (err) {
            if (err && err.code !== 'ER_NO_SUCH_TABLE') throw err;
        }
    }

    /**
     * Login/logout de préstamo desde la app Flutter (en lugar de pasar
     * tarjeta física). El device_serie es el del lector USUARIO físico.
     */
    async procesarPrestamo(registration, deviceSerie, action) {
        try {
            const usuario = await this.getUserByRegistration(registration);
            if (!usuario) {
                return { success: false, message: 'Usuario no encontrado', data: null };
            }

            const dispositivo = await this.getDeviceBySerie(deviceSerie);
            if (!dispositivo) {
                return { success: false, message: 'Dispositivo no encontrado', data: null };
            }

            const userRFID = usuario.cards_number;
            if (!userRFID) {
                return { success: false, message: 'Usuario sin RFID asignado', data: null };
            }

            const userForSession = {
                hab_id: usuario.hab_id,
                hab_name: usuario.hab_name,
                cards_number: userRFID,
            };

            if (action === 'on') {
                await this._openSession(deviceSerie, userForSession);
                await this.enviarComandosMQTT(deviceSerie, usuario.hab_name, 'found');
                return {
                    success: true,
                    message: 'Usuario autenticado para préstamo',
                    data: {
                        usuario: usuario.hab_name,
                        dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                        estado: 'active',
                    },
                };
            }

            // logout
            const session = await this._loadSession(deviceSerie);
            if (session) {
                await this._closeSession(deviceSerie);
                await this.enviarComandosMQTT(deviceSerie, null, 'unload');
                return {
                    success: true,
                    message: 'Sesión finalizada exitosamente',
                    data: {
                        usuario: usuario.hab_name,
                        dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                        estado: 'sesión finalizada',
                    },
                };
            }

            return {
                success: true,
                message: 'No había sesión activa, operación completada',
                data: {
                    usuario: usuario.hab_name,
                    dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                    estado: 'sin sesión activa',
                },
            };
        } catch (err) {
            console.error('❌ Error procesarPrestamo:', err);
            return { success: false, message: 'Error interno', data: null };
        }
    }

    /**
     * Compatibilidad con el endpoint legacy: simula el dispositivo físico
     * publicando el RFID al broker para que el listener procese el flujo
     * normal. Hoy es equivalente a procesarPrestamo en modo toggle.
     */
    async simularDispositivoFisico(registration, deviceSerie) {
        const usuario = await this.getUserByRegistration(registration);
        if (!usuario) {
            return { success: false, message: 'Usuario no encontrado',
                data: { matricula: registration, estado: 'no encontrado' } };
        }

        const userRFID = usuario.cards_number;
        if (!userRFID) {
            return { success: false, message: 'Usuario sin tarjeta RFID',
                data: { matricula: registration, usuario: usuario.hab_name, estado: 'sin tarjeta RFID' } };
        }

        const dispositivo = await this.getDeviceBySerie(deviceSerie);
        if (!dispositivo) {
            return { success: false, message: 'Dispositivo no encontrado',
                data: { matricula: registration, deviceSerie } };
        }

        // Reutiliza la lógica unificada de toggle.
        const result = await this.handleLoanUserQuery(deviceSerie, userRFID);
        return {
            ...result,
            data: {
                ...(result.data || {}),
                matricula: registration,
                usuario: usuario.hab_name,
                rfid: userRFID,
                dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                device_serie: deviceSerie,
                timestamp: new Date().toISOString(),
                simulation: true,
            },
        };
    }

    /**
     * Versión simplificada del endpoint legacy.
     */
    async procesarConsultaRFID(deviceSerie, rfidNumber) {
        return this.handleLoanUserQuery(deviceSerie, rfidNumber);
    }
}

module.exports = new PrestamoService();
