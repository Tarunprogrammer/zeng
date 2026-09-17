-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `tshirt_shop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tshirt_shop`;

-- Create the users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NULL UNIQUE,
  `phone` VARCHAR(20) NULL UNIQUE,
  `password` VARCHAR(255) NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create the orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `shirt_size` VARCHAR(10) NOT NULL,
  `shirt_color` VARCHAR(20) NOT NULL,
  `design_type` ENUM('preset', 'custom') NOT NULL,
  `design_src` VARCHAR(255) NOT NULL,
  `design_x` FLOAT NOT NULL DEFAULT 50.0,
  `design_y` FLOAT NOT NULL DEFAULT 50.0,
  `design_scale` FLOAT NOT NULL DEFAULT 1.0,
  `preview_image` LONGTEXT NOT NULL,
  `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
