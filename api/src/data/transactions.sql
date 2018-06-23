CREATE TABLE `transactions` (
  `id` BINARY(16),
  `type` TINYINT(1) unsigned NOT NULL,
  `order_id` INT unsigned NOT NULL,
  `status` TINYINT(1) unsigned NOT NULL,
UNIQUE KEY `order_id_idx` (`order_id`) USING BTREE,
PRIMARY KEY (`id`)
) ENGINE=InnoDB;