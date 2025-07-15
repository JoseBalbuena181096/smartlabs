module.exports = {
  apps: [{
    name: 'device-status-server',
    script: 'device-status-server.js',
    cwd: '/c/Users/josetec/Desktop/smartlabs/www/node',
    instances: 1,
    exec_mode: 'fork',
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    env_development: {
      NODE_ENV: 'development',
      PORT: 3000
    },
    log_file: './logs/combined.log',
    out_file: './logs/out.log',
    error_file: './logs/error.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    time: true,
    // Configuraciones adicionales para estabilidad
    min_uptime: '10s',
    max_restarts: 10,
    restart_delay: 4000,
    // Configuración para WebSocket
    kill_timeout: 5000,
    listen_timeout: 8000,
    // Configuración para MySQL
    max_memory_restart: '512M',
    // Configuración de logs
    log_type: 'json',
    // Configuración de cluster (en caso de querer escalar)
    increment_var: 'PORT',
    // Configuración para debugging
    node_args: '--max-old-space-size=512'
  }]
}; 