-- --------------------------------------------------------
-- Table `contact_form`
-- --------------------------------------------------------
CREATE TABLE `contact_form` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,     -- Alternative communication method
  `subject` VARCHAR(150) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `started_at` DATETIME DEFAULT NULL,   -- Workflow tracking
  `resolved_at` DATETIME DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `revoked_at` DATETIME DEFAULT NULL    -- Soft delete
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `person_contact`
-- --------------------------------------------------------
CREATE TABLE `person_contact` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `mail` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `role` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = private
  `revoked_at` DATETIME DEFAULT NULL -- Soft delete
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `donation`
-- --------------------------------------------------------
CREATE TABLE `donation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `amount` DECIMAL(10,2) NOT NULL CHECK (`amount` > 0),
  `description` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` DATETIME DEFAULT NULL -- Soft delete
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `news`
-- --------------------------------------------------------
CREATE TABLE `news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table `organization_type`
-- --------------------------------------------------------
CREATE TABLE `organization_type` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table `organization`
-- --------------------------------------------------------
CREATE TABLE `organization` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `web_site` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `organization_type_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = private
  `revoked_at` DATETIME DEFAULT NULL, -- Soft delete
  FOREIGN KEY (`organization_type_id`) REFERENCES `organization_type`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------
-- Table `page`
-- --------------------------------------------------------
CREATE TABLE `page` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `meta_description` VARCHAR(160) DEFAULT NULL, -- SEO
  `content` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = draft
  `revoked_at` DATETIME DEFAULT NULL -- Soft delete
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table `service_category`
-- --------------------------------------------------------
CREATE TABLE `service_category` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------
-- Table `service`
-- --------------------------------------------------------
CREATE TABLE `service` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `service_category_id` INT DEFAULT NULL,
  `sort_order` INT DEFAULT 0,                 -- Manual ordering (0 = first)
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = not featured
  `revoked_at` DATETIME DEFAULT NULL,       -- Soft delete
  FOREIGN KEY (`service_category_id`) REFERENCES `service_category`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------
-- Table `statistics`
-- --------------------------------------------------------
CREATE TABLE `statistics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(150) NOT NULL,
  `value` VARCHAR(50) NOT NULL,
  `sort_order` INT DEFAULT 0, -- Custom display order
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = not featured
  `revoked_at` DATETIME DEFAULT NULL -- Soft delete
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `media` - Enhanced for WCAG compliance
-- --------------------------------------------------------
CREATE TABLE `media` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150),
  `alt_text` TEXT NOT NULL,           -- MANDATORY for screen readers (WCAG compliance)
  `path` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(50) NOT NULL,   -- Content security and proper handling
  `file_size` INT DEFAULT NULL,       -- Performance optimization
  `width` INT DEFAULT NULL,           -- Image dimensions for responsive design
  `height` INT DEFAULT NULL,
  
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = private, DATETIME = public
  `revoked_at` DATETIME DEFAULT NULL  -- Soft delete
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;