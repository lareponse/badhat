-- --------------------------------------------------------
-- Define reusable patterns
-- --------------------------------------------------------
SET @email_pattern = '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$';
SET @phone_pattern = '^[\+]?[0-9\s\-\(\)\.]{8,20}$';

-- --------------------------------------------------------
-- Table `contact_form`
-- --------------------------------------------------------
CREATE TABLE `contact_form` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `subject` VARCHAR(150) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `started_at` DATETIME DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  `priority` SMALLINT DEFAULT 1 COMMENT '0=low, 1=normal, 2=high, 3=urgent',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `revoked_at` DATETIME DEFAULT NULL,
  
  CONSTRAINT chk_contact_email_format CHECK (email REGEXP @email_pattern),
  CONSTRAINT chk_contact_phone_format CHECK (phone IS NULL OR phone REGEXP @phone_pattern),
  CONSTRAINT chk_contact_workflow_logic 
    CHECK (
      (started_at IS NULL OR started_at >= created_at) AND
      (resolved_at IS NULL OR (resolved_at >= started_at AND started_at IS NOT NULL)) AND
      (closed_at IS NULL OR (closed_at >= resolved_at AND resolved_at IS NOT NULL))
    ),
  CONSTRAINT chk_contact_content_length 
    CHECK (CHAR_LENGTH(TRIM(description)) >= 10)
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
  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  
  CONSTRAINT chk_person_email_format CHECK (mail IS NULL OR mail REGEXP @email_pattern),
  CONSTRAINT chk_person_phone_format CHECK (phone IS NULL OR phone REGEXP @phone_pattern),
  CONSTRAINT chk_person_communication CHECK (mail IS NOT NULL OR phone IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `donation`
-- --------------------------------------------------------
CREATE TABLE `donation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `amount` DECIMAL(10,2) NOT NULL CHECK (`amount` > 0),
  `description` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` DATETIME DEFAULT NULL, -- Soft delete

  CONSTRAINT chk_donation_amount CHECK (amount > 0)
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
  `url` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `organization_type_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = private
  `revoked_at` DATETIME DEFAULT NULL, -- Soft delete

  CONSTRAINT chk_org_website_format CHECK (`url` IS NULL OR `url` REGEXP '^https?://[A-Za-z0-9.-]+\.[A-Za-z]{2,}'),
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
  `revoked_at` DATETIME DEFAULT NULL, -- Soft delete
   CONSTRAINT chk_page_publication_logic CHECK (revoked_at IS NULL OR enabled_at IS NULL OR enabled_at < revoked_at)

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
-- Table `mime_type`
-- --------------------------------------------------------
CREATE TABLE `mime_type` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(50) NOT NULL UNIQUE,
  `max_size` INT DEFAULT 50000000, -- 50MB default
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = not allowed
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
  `mime_type_id` INT NOT NULL,
  `file_size` INT DEFAULT NULL,       -- Performance optimization
  `width` INT DEFAULT NULL,           -- Image dimensions for responsive design
  `height` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = private, DATETIME = public
  `revoked_at` DATETIME DEFAULT NULL,  -- Soft delete
  
  FOREIGN KEY (`mime_type_id`) REFERENCES `mime_type`(`id`),
  CONSTRAINT chk_media_file_size_positive CHECK (file_size > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;