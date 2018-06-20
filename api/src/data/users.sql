CREATE TABLE `users` (
  `id` INT unsigned NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(30) NOT NULL,
  `password` VARCHAR(60) NOT NULL,
  `type` TINYINT unsigned NOT NULL,
  `balance` BIGINT NOT NULL,
UNIQUE KEY `login_password` (`login`,`password`) USING HASH,
PRIMARY KEY (`id`)
) ENGINE=InnoDB;