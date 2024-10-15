CREATE DATABASE `shop`;
USE `shop`;

CREATE TABLE `permission_group` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `showUser` tinyint(1) NOT NULL DEFAULT 0,
  `modifyUser` tinyint(1) NOT NULL DEFAULT 0,
  `deleteUser` tinyint(1) NOT NULL DEFAULT 0,
  `showUserPerms` tinyint(1) NOT NULL DEFAULT 0,
  `modifyUserPerms` tinyint(1) NOT NULL DEFAULT 0,
  `showProduct` tinyint(1) NOT NULL DEFAULT 0,
  `createProduct` tinyint(1) NOT NULL DEFAULT 0,
  `modifyProduct` tinyint(1) NOT NULL DEFAULT 0,
  `showCategories` tinyint(1) NOT NULL DEFAULT 0,
  `modifyCategories` tinyint(1) NOT NULL DEFAULT 0,
  `deleteCategories` tinyint(1) NOT NULL DEFAULT 0,
  `createCategories` tinyint(1) NOT NULL DEFAULT 0,
  `showOrders` tinyint(1) NOT NULL DEFAULT 0,
  `markOrders` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `users` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `permission_group` int(10) NOT NULL DEFAULT 1,
  `email` varchar(255) NOT NULL,
  `passwort` varchar(255) NOT NULL,
  `vorname` varchar(255) NOT NULL DEFAULT '',
  `nachname` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `passwortcode` varchar(255) DEFAULT NULL,
  `passwortcode_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`permission_group`) REFERENCES `permission_group`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `securitytokens` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `securitytoken` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `citys` (
    `id` int(10) NOT NULL AUTO_INCREMENT,
    `PLZ` int(6) NOT NULL,
    `city` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `address` (
    `id` int(10) NOT NULL AUTO_INCREMENT,
    `user_id` int(10) NOT NULL,
    `citys_id` int(10) NOT NULL,
    `street` varchar(255) NOT NULL,
    `number` varchar(255) NOT NULL,
    `default` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`citys_id`) REFERENCES `citys`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `products_types` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `products` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `product_type_id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `desc` mediumtext NOT NULL,
  `price` decimal(7,2) NOT NULL,
  `rrp` decimal(7,2) NOT NULL DEFAULT 0.00,
  `quantity` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `visible` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`product_type_id`) REFERENCES `products_types`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `product_images` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `product_id` int(10) NOT NULL,
  `img` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `orders` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `kunden_id` int(10) NOT NULL,
  `rechnungsadresse` int(10),
  `lieferadresse` int(10),
  `ordered` tinyint(1) NOT NULL DEFAULT 0,
  `ordered_date` datetime DEFAULT NULL,
  `sent` tinyint(1) NOT NULL DEFAULT 0,
  `sent_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`kunden_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`rechnungsadresse`) REFERENCES `address`(`id`),
  FOREIGN KEY (`lieferadresse`) REFERENCES `address`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `product_list` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `list_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`list_id`) REFERENCES `orders`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE USER IF NOT EXISTS 'shopuser'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON shop.* TO 'shopuser'@'localhost';
FLUSH PRIVILEGES;