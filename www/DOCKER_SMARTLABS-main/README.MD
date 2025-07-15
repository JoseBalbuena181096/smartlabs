# EMQX CON DOCKER 
## Crear nuestro servicio de mqtt desde docker compose.

Este repo contiene el docker compose, el cual inicializa tres contenedores necesarios para arrancar smartlabs, mariadb y un script para arrancar la base de datos, tablas, vistas y accesos; un segundo contenedor con phpmyadmin para poder configurar algún detalle de la base de datos y un tercer contenedor que es el corazón de nuestro sistema, el broker de mqtt emqx en su version 4.4.19 el es encargado de comunicar por websockets y mqtt todos nuestros servicios y dispositivos. 
### Instalar los servicios.

1- Instalar docker en Ubuntu, página oficial de Docker
```
https://docs.docker.com/engine/install/ubuntu/#install-from-a-package
```
2- Instalar docker-compose en Ubuntu
```
sudo curl -L "https://github.com/docker/compose/releases/download/1.28.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
```
```
sudo chmod +x /usr/local/bin/docker-compose
```
Verificar instalación de Docker y docker-compose
```
sudo docker --version
```
```
sudo docker-compose --version
```
3- Clonar el repositorio
```
sudo git clone https://github.com/JoseBalbuena181096/DOCKER_SMARTLABS.git
```
4- Entrar a la carpeta descargada
```
cd emqx-docker
```
5- Crear el archivo _.env_
```
sudo nano .env
```
6- Definir las variables de entorno en el archivo _.env_
```
MARIADB_ROOT_PASSWORD=emqxpass
MARIADB_USER=emqxuser
MARIADB_PASSWORD=emqxpass
MARIADB_DATABASE=emqx 
```
7- Ejecutar el comando para correr el servicio
```
sudo docker-compose up -d
```
