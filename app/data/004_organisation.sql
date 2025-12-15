CREATE TABLE `organization` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  `label` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL UNIQUE,

  `logo` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  `content` TEXT DEFAULT NULL,

  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

CREATE TABLE `organization_type` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(20) NOT NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;



CREATE TABLE `organization_organization_type` (
  `organization_id` INT NOT NULL,
  `organization_type_id` INT NOT NULL,

  PRIMARY KEY (`organization_id`, `organization_type_id`),
  KEY `idx_oot_type` (`organization_type_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;




-- ORGANIZATION ↔ CONTACT POINT (N–N)
-- =========================================================
CREATE TABLE `organization_contact_point` (
  `organization_id` INT NOT NULL,
  `contact_point_id` INT NOT NULL,
  `sort_order` SMALLINT DEFAULT NULL,

  PRIMARY KEY (`organization_id`, `contact_point_id`),
  KEY `idx_ocp_contact_point` (`contact_point_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

