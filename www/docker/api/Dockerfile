# Dockerfile para SMARTLABS Flutter API
FROM node:18-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    curl \
    tzdata

# Crear usuario no-root
RUN addgroup -g 1001 -S nodejs && \
    adduser -S smartlabs -u 1001 -G nodejs

# Establecer directorio de trabajo
WORKDIR /app

# Copiar archivos de dependencias
COPY package*.json ./

# Instalar dependencias de producción
RUN npm ci --only=production && npm cache clean --force

# Copiar código fuente
COPY . .

# Crear directorio de logs y establecer permisos
RUN mkdir -p /app/logs && \
    chown -R smartlabs:nodejs /app

# Cambiar a usuario no-root
USER smartlabs

# Exponer puerto
EXPOSE 3000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:3000/health || exit 1

# Comando de inicio
CMD ["npm", "start"]