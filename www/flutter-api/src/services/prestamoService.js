const dbConfig = require('../config/database');
const mqttConfig = require('../config/mqtt');
const mqtt = require('mqtt');

/**
 * Servicio para manejo de préstamos de dispositivos
 * Sustituye la funcionalidad del dispositivo físico main_usuariosLV2.cpp
 */
class PrestamoService {
    constructor() {
        this.mqttClient = null;
        this.serialLoanUser = null;
        this.countLoanCard = 0;
        this.initMQTT();
    }

    /**
     * Inicializa la conexión MQTT
     */
    async initMQTT() {
        try {
            const mqttOptions = {
                host: process.env.MQTT_HOST || process.env.SERVER_HOST || '192.168.0.100',
                port: process.env.MQTT_PORT || 1883,
                username: process.env.MQTT_USERNAME || 'jose',
                password: process.env.MQTT_PASSWORD || 'public',
                clientId: `flutter_api_${Math.round(Math.random() * 10000)}`,
                clean: true,
                connectTimeout: 4000,
                reconnectPeriod: 1000
            };

            this.mqttClient = mqtt.connect(`mqtt://${mqttOptions.host}`, mqttOptions);
            
            this.mqttClient.on('connect', () => {
                console.log('✅ Flutter API conectado a MQTT broker');
            });
            
            this.mqttClient.on('error', (error) => {
                console.error('❌ Error MQTT en Flutter API:', error);
            });
            
        } catch (error) {
            console.error('❌ Error inicializando MQTT:', error);
        }
    }

    /**
     * Obtiene información de un usuario por matrícula
     * Incluye información de la tarjeta RFID usando la vista cards_habs
     * @param {string} registration - Matrícula del usuario
     * @returns {Object|null} - Datos del usuario o null si no existe
     */
    async getUserByRegistration(registration) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            // Usar la vista cards_habs para obtener información completa del usuario incluyendo RFID
            const [rows] = await connection.execute(
                `SELECT 
                    h.hab_id,
                    h.hab_date,
                    h.hab_name,
                    h.hab_registration,
                    h.hab_email,
                    h.hab_card_id,
                    h.hab_device_id,
                    ch.cards_number,
                    ch.cards_assigned
                FROM habintants h
                LEFT JOIN cards_habs ch ON h.hab_id = ch.hab_id
                WHERE h.hab_registration = ?`,
                [registration]
            );
            
            if (rows.length > 0) {
                const user = rows[0];
                console.log(`✅ Usuario encontrado por matrícula ${registration}:`, {
                    hab_id: user.hab_id,
                    hab_name: user.hab_name,
                    hab_registration: user.hab_registration,
                    cards_number: user.cards_number
                });
                return user;
            }
            
            console.log(`❌ No se encontró usuario con matrícula: ${registration}`);
            return null;
        } catch (error) {
            console.error('❌ Error obteniendo usuario:', error);
            return null;
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }

    /**
     * Obtiene información de un usuario por número RFID
     * Replica la funcionalidad del backend Node.js para consultas RFID del hardware
     * @param {string} rfidNumber - Número RFID de la tarjeta
     * @returns {Object|null} - Datos del usuario o null si no existe
     */
    async getUserByRFID(rfidNumber) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            // Usar la vista cards_habs igual que el backend Node.js
            const [rows] = await connection.execute(
                `SELECT 
                    ch.cards_id,
                    ch.cards_number,
                    ch.cards_assigned,
                    ch.hab_id,
                    ch.hab_name,
                    ch.hab_device_id
                FROM cards_habs ch 
                WHERE ch.cards_number = ?`,
                [rfidNumber]
            );
            
            if (rows.length > 0) {
                console.log(`✅ Usuario encontrado por RFID ${rfidNumber}:`, rows[0]);
                return rows[0];
            }
            
            console.log(`❌ No se encontró usuario con RFID: ${rfidNumber}`);
            return null;
        } catch (error) {
            console.error('❌ Error obteniendo usuario por RFID:', error);
            return null;
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }

    /**
     * Obtiene información de un dispositivo por serie
     * @param {string} deviceSerie - Serie del dispositivo
     * @returns {Object|null} - Datos del dispositivo o null si no existe
     */
    async getDeviceBySerie(deviceSerie) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            const [rows] = await connection.execute(
                'SELECT * FROM devices WHERE devices_serie = ?',
                [deviceSerie]
            );
            return rows.length > 0 ? rows[0] : null;
        } catch (error) {
            console.error('❌ Error obteniendo dispositivo:', error);
            return null;
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }

    /**
     * Registra préstamo de equipo en la base de datos
     * @param {string} userRFID - RFID del usuario
     * @param {string} equipRFID - RFID del equipo
     * @param {number} state - Estado del préstamo (1=prestado, 0=devuelto)
     * @returns {boolean} - True si se registró correctamente
     */
    async registrarPrestamo(userRFID, equipRFID, state) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            await connection.execute(
                `INSERT INTO loans (loans_date, loans_hab_rfid, loans_equip_rfid, loans_state) 
                 VALUES (CURRENT_TIMESTAMP, ?, ?, ?)`,
                [userRFID, equipRFID, state]
            );
            return true;
        } catch (error) {
            console.error('❌ Error registrando préstamo:', error);
            return false;
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }

    /**
     * Obtiene el último préstamo de un equipo
     * @param {string} equipRFID - RFID del equipo
     * @returns {Object|null} - Último préstamo o null
     */
    async getLastLoan(equipRFID) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            const [rows] = await connection.execute(
                'SELECT * FROM loans WHERE loans_equip_rfid = ? ORDER BY loans_date DESC LIMIT 1',
                [equipRFID]
            );
            
            return rows.length > 0 ? rows[0] : null;
        } catch (error) {
            console.error('❌ Error obteniendo último préstamo:', error);
            return null;
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }

    /**
     * Obtiene información de un equipo por RFID
     * @param {string} equipRFID - RFID del equipo
     * @returns {Object|null} - Datos del equipo o null si no existe
     */
    async getEquipmentByRFID(equipRFID) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            const [rows] = await connection.execute(
                'SELECT * FROM equipments WHERE equipments_rfid = ?',
                [equipRFID]
            );
            
            return rows.length > 0 ? rows[0] : null;
        } catch (error) {
            console.error('❌ Error obteniendo equipo:', error);
            return null;
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }

    /**
     * Registra el tráfico/préstamo en la base de datos
     * @param {string} userId - ID del usuario
     * @param {string} deviceSerie - Serie del dispositivo
     * @param {boolean} state - Estado del dispositivo (true=encendido, false=apagado)
     * @returns {boolean} - True si se registró correctamente
     */
    async registrarTrafico(userId, deviceSerie, state) {
        let connection = null;
        try {
            const mysql = require('mysql2/promise');
            connection = await mysql.createConnection({
                host: process.env.DB_HOST,
                user: process.env.DB_USER,
                password: process.env.DB_PASSWORD,
                database: process.env.DB_NAME,
                port: process.env.DB_PORT
            });
            
            await connection.execute(
                `INSERT INTO traffic (traffic_date, traffic_hab_id, traffic_device, traffic_state) 
                 VALUES (CURRENT_TIMESTAMP, ?, ?, ?)`,
                [userId, deviceSerie, state]
            );
            return true;
        } catch (error) {
            console.error('❌ Error registrando tráfico:', error);
            return false;
        } finally {
            if (connection) {
                await connection.end();
            }
        }
    }



    /**
     * Envía comandos MQTT al dispositivo (replicando main_usuariosLV2.cpp)
     * @param {string} deviceSerie - Serie del dispositivo
     * @param {string} userName - Nombre del usuario (puede ser null si no se encontró)
     * @param {string} command - Comando específico a enviar ('found', 'nofound', 'unload', 'prestado', 'devuelto')
     * @returns {Object} - Resultado del envío MQTT
     */
    async enviarComandosMQTT(deviceSerie, userName, command) {
        try {
            if (!this.mqttClient || !this.mqttClient.connected) {
                console.warn('⚠️ MQTT no conectado, solo se registrará en base de datos');
                return {
                    success: false,
                    message: 'MQTT no conectado'
                };
            }

            // Enviar nombre del usuario solo si existe (equivalente a user_name en main_usuariosLV2.cpp)
            if (userName) {
                this.mqttClient.publish(`${deviceSerie}/user_name`, userName);
                console.log(`📤 Mensaje publicado en ${deviceSerie}/user_name: ${userName}`);
            }
            
            // Enviar comando de control (equivalente a command en main_usuariosLV2.cpp)
            this.mqttClient.publish(`${deviceSerie}/command`, command);
            console.log(`📤 Mensaje publicado en ${deviceSerie}/command: ${command}`);
            
            console.log(`✅ Comandos MQTT enviados a ${deviceSerie}: user_name=${userName || 'N/A'}, command=${command}`);
            
            return {
                success: true,
                topic: `${deviceSerie}/command`,
                message: command
            };
        } catch (error) {
            console.error('❌ Error enviando comandos MQTT:', error);
            return {
                success: false,
                message: 'Error enviando comandos MQTT',
                error: error.message
            };
        }
    }

    /**
     * Maneja consulta de usuario para préstamos (replicando handleLoanUserQuery de main_usuariosLV2.cpp)
     * Ahora usa la instancia del servidor IoT de Node.js para mantener sincronización
     * @param {string} deviceSerie - Serie del dispositivo
     * @param {string} userRFID - RFID del usuario
     * @returns {Object} - Resultado del procesamiento
     */
    async handleLoanUserQuery(deviceSerie, userRFID) {
         try {
             console.log(`🔍 Manejando consulta de usuario para préstamo: ${deviceSerie} - RFID: ${userRFID}`);
             
             // ✅ USAR SOLO LÓGICA LOCAL - No usar servidor IoT Node.js para evitar inicio automático de sesiones
             console.log('🔧 Usando lógica local para evitar inicio automático de sesiones');
             
             const user = await this.getUserByRFID(userRFID);
             
             if (user) {
                 if (this.countLoanCard === 1) {
                     // Usuario ya logueado, cerrar sesión
                     await this.enviarComandosMQTT(deviceSerie, null, 'unload');
                     this.countLoanCard = 0;
                     this.serialLoanUser = null;
                     console.log('🔄 Sesión de préstamo finalizada');
                     
                     return {
                         success: true,
                         message: 'Sesión finalizada',
                         data: {
                             usuario: user.hab_name,
                             rfid: userRFID,
                             estado: 'sesión finalizada'
                         }
                     };
                 } else {
                     // Nuevo login de usuario - el servidor IoT enviará 'found'
                     this.serialLoanUser = [user];
                     this.countLoanCard = 1;
                     console.log(`✅ Usuario encontrado para préstamo: ${user.hab_name}`);
                     await this.enviarComandosMQTT(deviceSerie, user.hab_name, 'found');
                     console.log(`🔍 Usuario almacenado en sesión:`, {
                         hab_name: user.hab_name,
                         cards_number: user.cards_number,
                         hab_id: user.hab_id
                     });
                     
                     return {
                         success: true,
                         message: 'Usuario autenticado para préstamo',
                         data: {
                             usuario: user.hab_name,
                             rfid: userRFID,
                             estado: 'autenticado'
                         }
                     };
                 }
             } else {
                 // El servidor IoT legacy ya no existe; este servicio debe publicar 'nofound'
                 // para que el ESP32 salga del estado "ENVIANDO AL SERVER" y muestre el error.
                 await this.enviarComandosMQTT(deviceSerie, null, 'nofound');
                 console.log('❌ Usuario no encontrado para préstamo');

                 return {
                     success: false,
                     message: 'Usuario no encontrado',
                     data: { rfid: userRFID }
                 };
             }
         } catch (error) {
             console.error('❌ Error en consulta de usuario para préstamo:', error);
             return {
                 success: false,
                 message: 'Error interno del servidor',
                 error: error.message
             };
         }
     }

    /**
     * Maneja consulta de equipo para préstamos (replicando handleLoanEquipmentQuery de main_usuariosLV2.cpp)
     * @param {string} deviceSerie - Serie del dispositivo
     * @param {string} equipRFID - RFID del equipo
     * @returns {Object} - Resultado del procesamiento
     */
    async handleLoanEquipmentQuery(deviceSerie, equipRFID) {
        try {
            console.log(`🔍 [Loan Equipment Query] Dispositivo: ${deviceSerie}, RFID Equipo: ${equipRFID}`);
            console.log(`📊 Estado actual: countLoanCard=${this.countLoanCard}, serialLoanUser:`, this.serialLoanUser);
            
            if (this.countLoanCard === 0 || this.serialLoanUser === null) {
                await this.enviarComandosMQTT(deviceSerie, null, 'nologin');
                console.log('⚠️ No hay usuario logueado para préstamo');
                
                return {
                    success: false,
                    message: 'No hay usuario logueado',
                    action: 'no_login'
                };
            }

            const equipment = await this.getEquipmentByRFID(equipRFID);
            
            if (equipment) {
                await this.enviarComandosMQTT(deviceSerie, equipment.equipments_name, null);
                
                // Obtener último préstamo del equipo
                const lastLoan = await this.getLastLoan(equipment.equipments_rfid);
                
                let newLoanState = 1; // Por defecto: prestado
                
                if (lastLoan) {
                    newLoanState = lastLoan.loans_state === 1 ? 0 : 1;
                }
                
                // Registrar nuevo préstamo
                // Validar que el usuario tenga RFID antes de registrar
                console.log(`🔍 Validando RFID del usuario en sesión:`, this.serialLoanUser[0]);
                const userRFID = this.serialLoanUser[0].cards_number;
                if (!userRFID) {
                    console.error('❌ Error: Usuario no tiene RFID asignado');
                    console.log('📊 Datos del usuario en sesión:', this.serialLoanUser[0]);
                    return {
                        success: false,
                        message: 'Usuario no tiene RFID asignado',
                        action: 'no_rfid'
                    };
                }
                
                const loanRegistered = await this.registrarPrestamo(
                    userRFID,
                    equipment.equipments_rfid,
                    newLoanState
                );
                
                if (loanRegistered) {
                    const command = newLoanState === 1 ? 'prestado' : 'devuelto';
                    await this.enviarComandosMQTT(deviceSerie, null, command);
                    
                    console.log(`✅ Equipo ${command}: ${equipment.equipments_name}`);
                    
                    return {
                        success: true,
                        message: `Equipo ${command} exitosamente`,
                        action: command,
                        equipment: equipment.equipments_name,
                        user: this.serialLoanUser[0].hab_name,
                        state: newLoanState
                    };
                } else {
                    return {
                        success: false,
                        message: 'Error registrando préstamo',
                        action: 'database_error'
                    };
                }
            } else {
                await this.enviarComandosMQTT(deviceSerie, null, 'nofound');
                console.log('❌ Equipo no encontrado');
                
                return {
                    success: false,
                    message: 'Equipo no encontrado',
                    action: 'equipment_not_found'
                };
            }
        } catch (error) {
            console.error('❌ Error en consulta de equipo para préstamo:', error);
            return {
                success: false,
                message: 'Error interno del servidor',
                error: error.message
            };
        }
    }

    /**
     * Procesa una consulta RFID desde el hardware físico
     * Replica exactamente la funcionalidad del dispositivo main_usuariosLV2.cpp
     * @param {string} deviceSerie - Serie del dispositivo que envía la consulta
     * @param {string} rfidNumber - Número RFID de la tarjeta
     * @returns {Object} - Resultado del procesamiento
     */
    async procesarConsultaRFID(deviceSerie, rfidNumber) {
        try {
            console.log(`🔄 Procesando consulta RFID desde hardware: ${deviceSerie} - RFID: ${rfidNumber}`);
            
            // Obtener información del usuario por RFID usando la vista cards_habs
            const usuario = await this.getUserByRFID(rfidNumber);
            if (!usuario) {
                console.log(`❌ Usuario no encontrado para RFID: ${rfidNumber}`);
                // El servidor IoT enviará 'nofound' automáticamente
                return {
                    success: false,
                    message: 'Usuario no encontrado para RFID',
                    data: { rfid: rfidNumber }
                };
            }
            
            // Procesar como consulta de usuario para préstamos
            return await this.handleLoanUserQuery(deviceSerie, rfidNumber);
            
        } catch (error) {
            console.error('❌ Error procesando consulta RFID:', error);
            return {
                success: false,
                message: 'Error interno del servidor',
                data: { rfid: rfidNumber }
            };
        }
    }

    /**
     * Procesa una solicitud de préstamo desde la app Flutter
     * @param {string} registration - Matrícula del usuario
     * @param {string} deviceSerie - Serie del dispositivo
     * @param {string} action - Acción a realizar (on/off)
     * @returns {Object} - Resultado del procesamiento
     */
    async procesarPrestamo(registration, deviceSerie, action) {
        try {
            console.log(`🔄 Procesando préstamo desde app: ${registration} - ${deviceSerie} - ${action}`);
            console.log(`📊 Estado actual de sesión: countLoanCard=${this.countLoanCard}, usuario=${this.serialLoanUser ? this.serialLoanUser[0].hab_name : 'ninguno'}`);
            
            // Obtener información del usuario por matrícula
            const usuario = await this.getUserByRegistration(registration);
            if (!usuario) {
                console.log(`❌ Usuario no encontrado: ${registration}`);
                // El servidor IoT enviará 'nofound' automáticamente cuando procese el RFID
                return {
                    success: false,
                    message: 'Usuario no encontrado',
                    data: null
                };
            }
            
            // Obtener información del dispositivo
            const dispositivo = await this.getDeviceBySerie(deviceSerie);
            if (!dispositivo) {
                console.log(`❌ Dispositivo no encontrado: ${deviceSerie}`);
                return {
                    success: false,
                    message: 'Dispositivo no encontrado',
                    data: null
                };
            }
            
            // ✅ FUNCIONALIDAD MEJORADA: Obtener RFID del usuario directamente del objeto usuario
            const userRFID = usuario.cards_number;
            console.log(`🔍 RFID del usuario logueado: ${userRFID}`);
            
            // Validar que el usuario tenga RFID asignado
            if (!userRFID) {
                console.error('❌ Error: Usuario no tiene RFID asignado');
                console.log('📊 Datos del usuario:', {
                    hab_id: usuario.hab_id,
                    hab_date: usuario.hab_date,
                    hab_name: usuario.hab_name,
                    hab_registration: usuario.hab_registration,
                    hab_email: usuario.hab_email,
                    hab_card_id: usuario.hab_card_id,
                    hab_device_id: usuario.hab_device_id
                });
                return {
                    success: false,
                    message: 'Usuario no tiene RFID asignado',
                    data: null
                };
            }
            
            // ✅ RESTAURADO: Publicar RFID al tópico loan_queryu con prefijo para evitar bucle infinito
            // Usar prefijo "APP:" para distinguir publicaciones desde la app vs. hardware
            if (userRFID && this.mqttClient && this.mqttClient.connected) {
                try {
                    const topic = `${deviceSerie}/loan_queryu`;
                    const messageWithPrefix = `APP:${userRFID}`;
                    this.mqttClient.publish(topic, messageWithPrefix);
                    console.log(`📤 RFID publicado en ${topic}: ${messageWithPrefix} (desde app, no procesará automáticamente)`);
                } catch (error) {
                    console.error('❌ Error publicando RFID en loan_queryu:', error);
                }
            }

            if (action === 'on') {
                // Acción de login/autenticación de usuario
                // Enviar comandos MQTT directamente desde aquí para evitar duplicación
                
                this.serialLoanUser = [usuario];
                this.countLoanCard = 1;
                
                console.log(`🔍 Usuario almacenado en sesión para préstamos:`, {
                    hab_name: usuario.hab_name,
                    cards_number: usuario.cards_number,
                    hab_id: usuario.hab_id
                });
                
                // Enviar comandos MQTT directamente
                await this.enviarComandosMQTT(deviceSerie, usuario.hab_name, 'found');
                
                console.log(`✅ Usuario encontrado para préstamo: ${usuario.hab_name} - Sesión ACTIVA`);
                
                return {
                    success: true,
                    message: 'Usuario autenticado para préstamo',
                    data: {
                        usuario: usuario.hab_name,
                        dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                        estado: 'active',
                        rfid_published: userRFID
                    }
                };
            } else {
                // Acción de logout/finalizar sesión
                // Enviar comando unload directamente desde aquí
                if (this.countLoanCard === 1) {
                    this.countLoanCard = 0;
                    this.serialLoanUser = null;
                    
                    // Enviar comando unload directamente
                    await this.enviarComandosMQTT(deviceSerie, null, 'unload');
                    console.log('🔄 Sesión de préstamo reiniciada');
                    
                    return {
                        success: true,
                        message: 'Sesión finalizada exitosamente',
                        data: {
                            usuario: usuario.hab_name,
                            dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                            estado: 'sesión finalizada',
                            rfid_published: userRFID
                        }
                    };
                } else {
                    console.log(`⚠️ Intento de finalizar sesión sin sesión activa para usuario: ${usuario.hab_name}`);
                    
                    return {
                        success: true,
                        message: 'No había sesión activa, operación completada',
                        data: {
                            usuario: usuario.hab_name,
                            dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                            estado: 'sin sesión activa',
                            rfid_published: userRFID
                        }
                    };
                }
            }
            
        } catch (error) {
            console.error('❌ Error procesando préstamo desde app:', error);
            return {
                success: false,
                message: 'Error interno del servidor',
                data: null
            };
        }
    }

    // ELIMINADO - Función procesarConsultaRFID removida junto con el listener MQTT

    /**
     * Simula el dispositivo físico RFID: busca usuario por matrícula y obtiene su RFID automáticamente
     * Replica exactamente el comportamiento del hardware main_usuariosLV2.cpp
     * Ahora usa la instancia del servidor IoT de Node.js para mantener sincronización
     * @param {string} registration - Matrícula del usuario (ej: L03533767)
     * @param {string} deviceSerie - Serie del dispositivo
     * @returns {Object} - Resultado del procesamiento
     */
    async simularDispositivoFisico(registration, deviceSerie) {
        try {
            console.log(`🤖 Simulando dispositivo físico: buscando usuario ${registration} en dispositivo ${deviceSerie}`);
            
            // 1. Buscar usuario por matrícula en la base de datos
            const usuario = await this.getUserByRegistration(registration);
            if (!usuario) {
                console.log(`❌ Usuario no encontrado por matrícula: ${registration}`);
                // El servidor IoT enviará 'nofound' automáticamente cuando procese el RFID
                return {
                    success: false,
                    message: 'Usuario no encontrado',
                    data: { 
                        matricula: registration,
                        estado: 'no encontrado'
                    }
                };
            }
            
            // 2. Obtener el RFID del usuario desde la vista cards_habs
            let connection = null;
            let userRFID = null;
            try {
                const mysql = require('mysql2/promise');
                connection = await mysql.createConnection({
                    host: process.env.DB_HOST,
                    user: process.env.DB_USER,
                    password: process.env.DB_PASSWORD,
                    database: process.env.DB_NAME,
                    port: process.env.DB_PORT
                });
                
                const [rows] = await connection.execute(
                    `SELECT ch.cards_number, ch.hab_name 
                     FROM cards_habs ch 
                     INNER JOIN habintants h ON ch.hab_id = h.hab_id 
                     WHERE h.hab_registration = ?`,
                    [registration]
                );
                
                if (rows.length === 0) {
                    console.log(`❌ No se encontró tarjeta RFID para la matrícula: ${registration}`);
                    // El servidor IoT enviará 'nofound' automáticamente cuando no encuentre el RFID
                    return {
                        success: false,
                        message: 'Usuario no tiene tarjeta RFID asignada',
                        data: { 
                            matricula: registration,
                            usuario: usuario.hab_name,
                            estado: 'sin tarjeta RFID'
                        }
                    };
                }
                
                userRFID = rows[0].cards_number;
                console.log(`✅ RFID encontrado para ${registration}: ${userRFID}`);
                
            } finally {
                if (connection) {
                    await connection.end();
                }
            }
            
            // 3. Verificar que el dispositivo existe
            const dispositivo = await this.getDeviceBySerie(deviceSerie);
            if (!dispositivo) {
                console.log(`❌ Dispositivo no encontrado: ${deviceSerie}`);
                return {
                    success: false,
                    message: 'Dispositivo no encontrado',
                    data: { 
                        matricula: registration,
                        deviceSerie: deviceSerie
                    }
                };
            }
            
            // 4. Simular el comportamiento exacto del hardware físico
            console.log(`🔄 Simulando lectura RFID del dispositivo físico: ${userRFID}`);
            
            // Simular exactamente lo que hace el hardware cuando send_access_query == true
            // Publicar directamente en el tópico loan_queryu como lo hace el ESP32
            const topic = `${deviceSerie}/loan_queryu`;
            console.log(`📡 Publicando en MQTT como dispositivo físico: ${topic} -> ${userRFID}`);
            
            if (this.mqttClient && this.mqttClient.connected) {
                // Publicar el RFID en el tópico loan_queryu (exactamente como el hardware)
                this.mqttClient.publish(topic, userRFID, (err) => {
                    if (err) {
                        console.error('❌ Error publicando en MQTT:', err);
                    } else {
                        console.log(`✅ RFID publicado exitosamente en ${topic}: ${userRFID}`);
                    }
                });
                
                // Esperar un momento para que el backend de Node.js procese la consulta
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                return {
                    success: true,
                    message: 'Simulación de dispositivo físico completada - RFID enviado por MQTT',
                    data: {
                        matricula: registration,
                        usuario: usuario.hab_name,
                        rfid: userRFID,
                        dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                        estado: 'RFID enviado por MQTT',
                        device_serie: deviceSerie,
                        topic: topic,
                        timestamp: new Date().toISOString(),
                        simulation: true,
                        hardware_behavior: true
                    }
                };
            } else {
                console.log('⚠️ Cliente MQTT no conectado, usando lógica de fallback');
                
                // Fallback: usar lógica local si MQTT no está disponible
                if (this.countLoanCard === 1) {
                    // Ya hay una sesión activa, cerrarla
                    await this.enviarComandosMQTT(deviceSerie, null, 'unload');
                    this.countLoanCard = 0;
                    this.serialLoanUser = null;
                    console.log('🔄 Sesión de préstamo finalizada por dispositivo físico simulado');
                    
                    return {
                        success: true,
                        message: 'Sesión finalizada por dispositivo físico (fallback)',
                        data: {
                            matricula: registration,
                            usuario: usuario.hab_name,
                            rfid: userRFID,
                            dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                            estado: 'sesión finalizada',
                            device_serie: deviceSerie,
                            timestamp: new Date().toISOString(),
                            simulation: true
                        }
                    };
                } else {
                    // No hay sesión activa, iniciar nueva sesión
                    await this.enviarComandosMQTT(deviceSerie, usuario.hab_name, 'found');
                    this.serialLoanUser = [usuario];
                    this.countLoanCard = 1;
                    
                    // Registrar el tráfico
                    await this.registrarTrafico(usuario.hab_id, deviceSerie, 1);
                    
                    console.log(`✅ Sesión iniciada por dispositivo físico simulado: ${usuario.hab_name}`);
                    
                    return {
                        success: true,
                        message: 'Sesión iniciada por dispositivo físico (fallback)',
                        data: {
                            matricula: registration,
                            usuario: usuario.hab_name,
                            rfid: userRFID,
                            dispositivo: dispositivo.devices_alias || dispositivo.devices_name,
                            estado: 'sesión iniciada',
                            device_serie: deviceSerie,
                            timestamp: new Date().toISOString(),
                            simulation: true
                        }
                    };
                }
            }
            
        } catch (error) {
            console.error('❌ Error simulando dispositivo físico:', error);
            return {
                success: false,
                message: 'Error interno del servidor',
                data: {
                    matricula: registration,
                    error: error.message
                }
            };
        }
    }

    /**
     * Obtiene el estado actual de la sesión de préstamo
     * @returns {Object} - Estado de la sesión
     */
    getSessionState() {
        return {
            active: this.countLoanCard === 1,
            user: this.serialLoanUser ? this.serialLoanUser[0].hab_name : null,
            count: this.countLoanCard
        };
    }
}

module.exports = new PrestamoService();