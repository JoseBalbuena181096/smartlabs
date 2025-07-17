module.exports = {
    external: {
        host: "192.168.0.100",
        user: "root",
        password: "emqxpass",
        database: "emqx",
        port: 4000,
        acquireTimeout: 60000,
        timeout: 60000,
        reconnect: true
    },
    local: {
        host: 'localhost',
        user: 'root',
        password: '',
        database: 'emqx'
    }
};