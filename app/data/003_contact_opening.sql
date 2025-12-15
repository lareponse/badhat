-- CONTACT POINT
-- =========================================================
CREATE TABLE `contact_point` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  `label` VARCHAR(150) NOT NULL,

  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,

  `address_line1` VARCHAR(255) DEFAULT NULL,
  `address_line2` VARCHAR(255) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `city` VARCHAR(150) DEFAULT NULL,
  `country` VARCHAR(150) DEFAULT NULL,

  `notes` TEXT DEFAULT NULL,

  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;


-- OPENING HOURS
-- =========================================================
CREATE TABLE `opening_hour` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  `contact_point_id` INT NOT NULL,

  `day_of_week` TINYINT NOT NULL,   -- 1 = Monday … 7 = Sunday
  `opens_at` TIME DEFAULT NULL,
  `closes_at` TIME DEFAULT NULL,

  `notes` VARCHAR(255) DEFAULT NULL,

  KEY `idx_opening_contact_day` (`contact_point_id`, `day_of_week`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

