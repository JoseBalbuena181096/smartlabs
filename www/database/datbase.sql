
/* CREAR LA BASE DE DATOS SI NO EXISTE */
CREATE DATABASE IF NOT EXISTS `emqx` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci;
USE `emqx`;

/* CREAR LA TABLA MQTT USER */
CREATE TABLE `mqtt_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `salt` varchar(35) DEFAULT NULL,
  `is_superuser` tinyint(1) DEFAULT 0,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mqtt_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* INSERTAR USUARIOS */
INSERT INTO `mqtt_user` ( `username`, `password`, `salt`, `is_superuser`) VALUES
('emqx', 'efa1f375d76194fa51a3556a97e641e61685f914d446979da50a551a4333ffd7', NULL, 1),
('jose', 'efa1f375d76194fa51a3556a97e641e61685f914d446979da50a551a4333ffd7', NULL, 1);

--
-- Estructura de tabla para la tabla `mqtt_acl`
--

CREATE TABLE `mqtt_acl` (
  `id` int(10) UNSIGNED NOT NULL,
  `allow` int(11) NOT NULL DEFAULT 0 COMMENT '0: deny, 1: allow',
  `ipaddr` varchar(60) DEFAULT NULL COMMENT 'IpAddress',
  `username` varchar(100) DEFAULT NULL COMMENT 'Username',
  `clientid` varchar(100) DEFAULT NULL COMMENT 'ClientId',
  `access` int(11) NOT NULL DEFAULT 3 COMMENT '1: subscribe, 2: publish, 3: pubsub',
  `topic` varchar(100) NOT NULL COMMENT 'Topic Filter'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `mqtt_acl`
--

INSERT INTO `mqtt_acl` (`id`, `allow`, `ipaddr`, `username`, `clientid`, `access`, `topic`) VALUES
(1, 0, NULL, '$all', NULL, 3, '+/#'),
(2, 1, NULL, '$all', NULL, 3, '%u/+/#');

CREATE TABLE `emqx`.`users` 
(
  `users_id` INT(7) NOT NULL AUTO_INCREMENT , 
  `users_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  `users_email` VARCHAR(60) NOT NULL , 
  `users_password` VARCHAR(60) NOT NULL , 
  PRIMARY KEY (`users_id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `emqx`.`devices` 
(
  `devices_id` INT(7) NOT NULL AUTO_INCREMENT , 
  `devices_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
  `devices_alias` VARCHAR(50) NOT NULL , 
  `devices_serie` VARCHAR(50) NOT NULL , 
  `devices_user_id` INT(7) NOT NULL , 
  PRIMARY KEY (`devices_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `devices` 
  (`devices_id`, `devices_date`, `devices_alias`, `devices_serie`, `devices_user_id`) 
  VALUES ('1', current_timestamp(), 'CORTADORA LASER CO2 SR1390N-PRO', 'SMART00005', '1');


INSERT INTO `users` (`users_id`, `users_date`, `users_email`, `users_password`) 
  VALUES (NULL, current_timestamp(), 'josebalbuena181096@gmail.com', SHA1('181096'));

CREATE TABLE `emqx`.`data` 
(
  `data_id` INT(11) NOT NULL AUTO_INCREMENT , 
  `data_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  `data_temp1` FLOAT(7,1) NOT NULL , 
  `data_temp2` FLOAT(7,1) NOT NULL , 
  `data_volts` FLOAT(7,1) NOT NULL , 
  PRIMARY KEY (`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `emqx`.`habintants` 
(
  `hab_id` INT(7) NOT NULL AUTO_INCREMENT , 
  `hab_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
  `hab_name` VARCHAR(50) NOT NULL , 
  `hab_registration` VARCHAR(50) NOT NULL UNIQUE,
  `hab_email` VARCHAR(50) NOT NULL,
  `hab_card_id` INT(7) NOT NULL , 
  `hab_device_id` INT(7) NOT NULL , 
  PRIMARY KEY (`hab_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `habintants` 
  (`hab_id`, `hab_date`, `hab_name`, `hab_registration`,  `hab_email`,`hab_card_id`, `hab_device_id`) 
  VALUES ('1', current_timestamp(), 'Jose Angel Balbuena Palma','L03533767','jose.balbuena.palma@tec.mx' , '1', '1');

CREATE TABLE `emqx`.`cards` 
(
  `cards_id` INT(7) NOT NULL AUTO_INCREMENT , 
  `cards_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
  `cards_number` VARCHAR(50) NOT NULL , 
  `cards_assigned` TINYINT(1) NOT NULL DEFAULT '0' , 
  PRIMARY KEY (`cards_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `cards` 
  (`cards_id`, `cards_date`, `cards_number`, `cards_assigned`) 
  VALUES ('1', current_timestamp(), '5242243191', '1');

CREATE TABLE `emqx`.`temps` 
(
  `temps_id` INT(7) NOT NULL AUTO_INCREMENT , 
  `temps_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
  `temps_temp` FLOAT(5,2) NOT NULL , 
  PRIMARY KEY (`temps_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `emqx`.`traffic` 
(
  `traffic_id` INT(7) NOT NULL AUTO_INCREMENT , 
  `traffic_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
  `traffic_hab_id` INT(7) NOT NULL , 
  `traffic_device` VARCHAR(50) NOT NULL , 
  `traffic_state` BOOLEAN NOT NULL,
  PRIMARY KEY (`traffic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `emqx`.`loans` 
(
  `loans_id` INT(7) NOT NULL AUTO_INCREMENT , 
  `loans_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
  `loans_hab_rfid` VARCHAR(50) NOT NULL ,
  `loans_equip_rfid` VARCHAR(50) NOT NULL , 
  `loans_state` BOOLEAN NOT NULL,
  PRIMARY KEY (`loans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `emqx`.`equipments` 
(
  `equipments_id` INT(7) NOT NULL AUTO_INCREMENT , 
  `equipments_name` VARCHAR(50) NOT NULL ,
  `equipments_rfid` VARCHAR(50) NOT NULL ,
  `equipments_brand` VARCHAR(50) NOT NULL , 
  PRIMARY KEY (`equipments_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE VIEW `habslab` AS
  select
    `loans`.`loans_date` AS `loans_date`,
    `loans`.`loans_state` AS `loans_state`,
    `loans`.`loans_equip_rfid` AS `loans_equip_rfid`,
    `loans`.`loans_hab_rfid` AS `loans_hab_rfid`,
    `equipments`.`equipments_name` AS `equipments_name`,
    `equipments`.`equipments_brand` AS `equipments_brand`,
    `equipments`.`equipments_rfid` AS `equipments_rfid`
  from (`loans` join `equipments`) 
  where ((`loans`.`loans_equip_rfid` = `equipments`.`equipments_rfid`))
  order by `loans`.`loans_date` desc ;


CREATE VIEW `cards_habs`   AS  
  select `cards`.`cards_id` AS `cards_id`,
  `cards`.`cards_number` AS `cards_number`,
  `cards`.`cards_assigned`AS `cards_assigned`,
  `habintants`.`hab_id` AS `hab_id`,
  `habintants`.`hab_name` AS `hab_name`,
  `habintants`.`hab_device_id` AS `hab_device_id`
  from (`cards` join `habintants`) 
  where (`habintants`.`hab_card_id` = `cards`.`cards_id`) ;


CREATE VIEW `traffic_devices` AS  
  select 
    `traffic`.`traffic_id` AS `traffic_id`,
    `traffic`.`traffic_date` AS `traffic_date`,
    `traffic`.`traffic_hab_id` AS `traffic_hab_id`,
    `traffic`.`traffic_device` AS `traffic_device`,
    `traffic`.`traffic_state` AS `traffic_state`,
    `habintants`.`hab_name` AS `hab_name`,
    `habintants`.`hab_registration` AS `hab_registration`,
    `habintants`.`hab_email` AS  `hab_email`,
    `habintants`.`hab_device_id` AS `hab_device_id`
  from (`traffic` join `habintants`) 
  where ((`habintants`.`hab_id` = `traffic`.`traffic_hab_id`))
  order by `traffic`.`traffic_id` desc ;


CREATE USER 'admin_iotcurso'@'%' IDENTIFIED BY '18196';

GRANT ALL PRIVILEGES ON *.* TO 'admin_iotcurso'@'%' 
  REQUIRE NONE WITH GRANT OPTION 
  MAX_QUERIES_PER_HOUR 0 
  MAX_CONNECTIONS_PER_HOUR 0 
  MAX_UPDATES_PER_HOUR 0 
  MAX_USER_CONNECTIONS 0;

GRANT ALL PRIVILEGES ON `emqx`.* TO 'admin_iotcurso'@'%';
FLUSH PRIVILEGES;


