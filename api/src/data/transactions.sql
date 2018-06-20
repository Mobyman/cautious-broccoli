CREATE TABLE `transactions` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `type` TINYINT(1) unsigned NOT NULL,
  `order_id` INT unsigned NOT NULL,
  `status` TINYINT(1) unsigned NOT NULL,
KEY `order_id_idx` (`order_id`) USING BTREE,
PRIMARY KEY (`id`)
) ENGINE=InnoDB;