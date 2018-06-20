CREATE TABLE IF NOT EXISTS orders (
  `id` INT NOT NULL AUTO_INCREMENT,
  `hirer_id` INT unsigned NOT NULL,
  `worker_id` INT unsigned NOT NULL,
  `transaction_id` BIGINT unsigned NOT NULL,
  `status` TINYINT(1) unsigned NOT NULL DEFAULT 0,
PRIMARY KEY (`id`)
) ENGINE=InnoDB;