CREATE TABLE `users` (
  `id` INT unsigned NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(30) NOT NULL,
  `password` VARCHAR(60) NOT NULL,
  `type` TINYINT unsigned NOT NULL,
  `balance` BIGINT NOT NULL DEFAULT 0,
UNIQUE KEY `login_password_key` (`login`,`password`) USING HASH,
UNIQUE KEY `login_key` (`login`) USING BTREE,
PRIMARY KEY (`id`)
) ENGINE=InnoDB;