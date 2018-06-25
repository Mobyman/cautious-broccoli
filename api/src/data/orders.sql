CREATE TABLE IF NOT EXISTS orders (
  `id` INT NOT NULL AUTO_INCREMENT,
  `hirer_id` INT unsigned,
  `worker_id` INT unsigned,
  `cost` INT unsigned,
  `transaction_id` BINARY(16),
  `status` TINYINT(1) unsigned NOT NULL DEFAULT 0,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `description` VARCHAR(4096) DEFAULT '',
UNIQUE KEY `transaction_id_idx` (`transaction_id`) USING HASH,
KEY `status_idx` (`status`) USING BTREE,
PRIMARY KEY (`id`)
) ENGINE=InnoDB;
