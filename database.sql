CREATE DATABASE IF NOT EXISTS `siparistakip_mini`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `siparistakip_mini`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(30) NOT NULL DEFAULT 'admin',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `whatsapp_number` VARCHAR(40) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_key_unique` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(40) DEFAULT NULL,
    `email` VARCHAR(190) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `customers_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `sku` VARCHAR(80) DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stock_quantity` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `products_sku_unique` (`sku`),
    KEY `products_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT UNSIGNED DEFAULT NULL,
    `order_no` VARCHAR(40) NOT NULL,
    `status` VARCHAR(40) NOT NULL DEFAULT 'pending',
    `payment_status` VARCHAR(40) NOT NULL DEFAULT 'waiting',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `orders_order_no_unique` (`order_no`),
    KEY `orders_customer_id_index` (`customer_id`),
    CONSTRAINT `orders_customer_id_fk`
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED DEFAULT NULL,
    `product_name` VARCHAR(150) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `line_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_items_order_id_index` (`order_id`),
    KEY `order_items_product_id_index` (`product_id`),
    CONSTRAINT `order_items_order_id_fk`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `order_items_product_id_fk`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan admin girişi: admin@demo.com / admin123
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`)
VALUES
    (1, 'Admin', 'admin@demo.com', '$2y$10$sKomY5.BnSHxgc9RhYk3T.HBZh0Eo8RKPHGryjH/ldoCiX9wy1wDe', 'admin', 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `email` = VALUES(`email`),
    `password_hash` = VALUES(`password_hash`),
    `role` = VALUES(`role`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `settings` (`setting_key`, `setting_value`, `whatsapp_number`)
VALUES
    ('app_name', 'SiparişTakip Mini', NULL),
    ('currency', 'TRY', NULL),
    ('whatsapp_number', '905551112233', '905551112233')
ON DUPLICATE KEY UPDATE
    `setting_value` = VALUES(`setting_value`),
    `whatsapp_number` = VALUES(`whatsapp_number`);

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`)
VALUES
    (1, 'Ayşe Yılmaz', '0532 111 22 33', 'ayse@example.com', 'Kadıköy / İstanbul', 'Düzenli müşteri'),
    (2, 'Mehmet Demir', '0544 222 33 44', 'mehmet@example.com', 'Çankaya / Ankara', 'Kargo öğleden sonra'),
    (3, 'Zeynep Kaya', '0555 333 44 55', 'zeynep@example.com', 'Konak / İzmir', 'Hediye paketi sever'),
    (4, 'Ali Şahin', '0533 444 55 66', 'ali@example.com', 'Nilüfer / Bursa', 'Kurumsal sipariş'),
    (5, 'Elif Acar', '0542 555 66 77', 'elif@example.com', 'Muratpaşa / Antalya', 'WhatsApp ile bilgilendir')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `phone` = VALUES(`phone`),
    `email` = VALUES(`email`),
    `address` = VALUES(`address`),
    `notes` = VALUES(`notes`);

INSERT INTO `products` (`id`, `name`, `sku`, `price`, `stock_quantity`, `is_active`)
VALUES
    (1, 'Kahve Seti', 'ST-KHV-001', 349.90, 24, 1),
    (2, 'Seramik Kupa', 'ST-KUP-002', 129.90, 60, 1),
    (3, 'Defter ve Kalem Paketi', 'ST-DFT-003', 189.50, 35, 1),
    (4, 'Doğal Sabun Kutusu', 'ST-SBN-004', 219.00, 18, 1),
    (5, 'Hediye Çikolata', 'ST-CKL-005', 159.90, 42, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `sku` = VALUES(`sku`),
    `price` = VALUES(`price`),
    `stock_quantity` = VALUES(`stock_quantity`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `orders` (`id`, `customer_id`, `order_no`, `status`, `payment_status`, `subtotal`, `discount_total`, `tax_total`, `total_amount`, `notes`, `created_at`)
VALUES
    (1, 1, 'SP-DEMO-1001', 'pending', 'waiting', 349.90, 0.00, 0.00, 349.90, 'Yeni gelen sipariş', NOW()),
    (2, 2, 'SP-DEMO-1002', 'preparing', 'paid', 259.80, 0.00, 0.00, 259.80, 'İki adet kupa', NOW()),
    (3, 3, 'SP-DEMO-1003', 'shipped', 'paid', 379.00, 0.00, 0.00, 379.00, 'Kargo takip bekleniyor', DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (4, 4, 'SP-DEMO-1004', 'completed', 'paid', 657.00, 0.00, 0.00, 657.00, 'Teslim edildi', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (5, 5, 'SP-DEMO-1005', 'cancelled', 'waiting', 159.90, 0.00, 0.00, 159.90, 'Müşteri iptal etti', DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (6, 1, 'SP-DEMO-1006', 'completed', 'partial', 539.40, 0.00, 0.00, 539.40, 'Kısmi ödeme alındı', DATE_SUB(NOW(), INTERVAL 4 DAY)),
    (7, 2, 'SP-DEMO-1007', 'pending', 'waiting', 219.00, 0.00, 0.00, 219.00, 'Hazırlık bekliyor', NOW()),
    (8, 3, 'SP-DEMO-1008', 'preparing', 'paid', 319.80, 0.00, 0.00, 319.80, 'Hediye notu eklenecek', DATE_SUB(NOW(), INTERVAL 5 DAY))
ON DUPLICATE KEY UPDATE
    `customer_id` = VALUES(`customer_id`),
    `order_no` = VALUES(`order_no`),
    `status` = VALUES(`status`),
    `payment_status` = VALUES(`payment_status`),
    `subtotal` = VALUES(`subtotal`),
    `discount_total` = VALUES(`discount_total`),
    `tax_total` = VALUES(`tax_total`),
    `total_amount` = VALUES(`total_amount`),
    `notes` = VALUES(`notes`),
    `created_at` = VALUES(`created_at`);

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `line_total`)
VALUES
    (1, 1, 1, 'Kahve Seti', 1, 349.90, 349.90),
    (2, 2, 2, 'Seramik Kupa', 2, 129.90, 259.80),
    (3, 3, 3, 'Defter ve Kalem Paketi', 2, 189.50, 379.00),
    (4, 4, 4, 'Doğal Sabun Kutusu', 3, 219.00, 657.00),
    (5, 5, 5, 'Hediye Çikolata', 1, 159.90, 159.90),
    (6, 6, 1, 'Kahve Seti', 1, 349.90, 349.90),
    (7, 6, 3, 'Defter ve Kalem Paketi', 1, 189.50, 189.50),
    (8, 7, 4, 'Doğal Sabun Kutusu', 1, 219.00, 219.00),
    (9, 8, 5, 'Hediye Çikolata', 2, 159.90, 319.80)
ON DUPLICATE KEY UPDATE
    `order_id` = VALUES(`order_id`),
    `product_id` = VALUES(`product_id`),
    `product_name` = VALUES(`product_name`),
    `quantity` = VALUES(`quantity`),
    `unit_price` = VALUES(`unit_price`),
    `line_total` = VALUES(`line_total`);

ALTER TABLE `users` AUTO_INCREMENT = 2;
ALTER TABLE `customers` AUTO_INCREMENT = 6;
ALTER TABLE `products` AUTO_INCREMENT = 6;
ALTER TABLE `orders` AUTO_INCREMENT = 9;
ALTER TABLE `order_items` AUTO_INCREMENT = 10;
