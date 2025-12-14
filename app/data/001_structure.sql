-- --------------------------------------------------------
-- Reusable validation patterns
-- --------------------------------------------------------
SET @email_pattern = '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$';
SET @phone_pattern = '^[\+]?[0-9\s\-\(\)\.]{8,20}$';

-- --------------------------------------------------------
-- Table `message`
-- --------------------------------------------------------
CREATE TABLE `message` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `subject` VARCHAR(150) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `started_at` DATETIME DEFAULT NULL,  -- Processing started
  `resolved_at` DATETIME DEFAULT NULL, -- Marked as resolved
  `closed_at` DATETIME DEFAULT NULL,   -- Final closure
  `priority` SMALLINT DEFAULT 1 COMMENT '0 = low, 1 = normal, 2 = high, 3 = urgent',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `revoked_at` DATETIME DEFAULT NULL,  -- Soft-delete timestamp
  
  CONSTRAINT `chk_message_email_format` CHECK (`email` REGEXP @email_pattern),
  CONSTRAINT `chk_message_phone_format` CHECK (`phone` IS NULL OR `phone` REGEXP @phone_pattern),
  CONSTRAINT `chk_message_workflow_order`
    CHECK (
      (`started_at` IS NULL OR `started_at` >= `created_at`) AND
      (`resolved_at` IS NULL OR (`started_at` IS NOT NULL AND `resolved_at` >= `started_at`)) AND
      (`closed_at`   IS NULL OR (`resolved_at` IS NOT NULL AND `closed_at`   >= `resolved_at`))
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `person_contact`
-- --------------------------------------------------------
CREATE TABLE `person_contact` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `role` VARCHAR(100) DEFAULT NULL,   -- e.g., point of contact, admin, etc.
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = disabled
  `revoked_at` DATETIME DEFAULT NULL, -- Soft-delete timestamp
  
  CONSTRAINT `chk_person_email_format` CHECK (`email` IS NULL OR `email` REGEXP @email_pattern),
  CONSTRAINT `chk_person_phone_format` CHECK (`phone` IS NULL OR `phone` REGEXP @phone_pattern),
  CONSTRAINT `chk_person_at_least_one_channel` CHECK (`email` IS NOT NULL OR `phone` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `donation`
-- --------------------------------------------------------
CREATE TABLE `donation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `amount` DECIMAL(10,2) NOT NULL,
  `description` TEXT NOT NULL,
  
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` DATETIME DEFAULT NULL, -- Soft-delete timestamp

  CONSTRAINT `chk_donation_amount_positive` CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `page`
-- --------------------------------------------------------
CREATE TABLE `page` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `meta_description` VARCHAR(160) DEFAULT NULL, -- SEO meta description
  `content` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL, -- NULL = draft
  `revoked_at` DATETIME DEFAULT NULL, -- Soft-delete timestamp

  CONSTRAINT `chk_page_publication_flow`
    CHECK (`revoked_at` IS NULL OR `enabled_at` IS NULL OR `enabled_at` < `revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `statistics`
-- --------------------------------------------------------
CREATE TABLE `statistics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(150) NOT NULL,
  `value` VARCHAR(50) NOT NULL,
  `sort_order` INT DEFAULT 0,                 -- Manual display order
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,         -- NULL = not featured
  `revoked_at` DATETIME DEFAULT NULL          -- Soft-delete timestamp
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `mime_type`
-- --------------------------------------------------------
CREATE TABLE `mime_type` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(50) NOT NULL UNIQUE,         -- e.g., image/png
  `max_size` INT DEFAULT 50000000,            -- Max size in bytes (default 50 MB)
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,         -- NULL = not allowed
  `revoked_at` DATETIME DEFAULT NULL          -- Soft-delete timestamp
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Table `media` (WCAG-aware)
-- --------------------------------------------------------
CREATE TABLE `media` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) DEFAULT NULL,
  `alt_text` TEXT NOT NULL,                   -- Required for screen readers (WCAG)
  `path` VARCHAR(255) NOT NULL,
  `mime_type_id` INT NOT NULL,
  `file_size` INT DEFAULT NULL,               -- Bytes; helps with performance
  `width` INT DEFAULT NULL,                   -- Image width in px
  `height` INT DEFAULT NULL,                  -- Image height in px

  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,         -- NULL = private, DATETIME = public
  `revoked_at` DATETIME DEFAULT NULL,         -- Soft-delete timestamp

  CONSTRAINT `fk_media_mime_type`
    FOREIGN KEY (`mime_type_id`) REFERENCES `mime_type`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- Tag (self-referencing hierarchy)
-- --------------------------------------------------------
CREATE TABLE `tag` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `parent_id` INT DEFAULT NULL,                 -- NULL = root tag
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,           -- NULL = disabled
  `revoked_at` DATETIME DEFAULT NULL,           -- Soft-delete timestamp
  CONSTRAINT `fk_tag_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `tag`(`id`)
      ON UPDATE CASCADE ON DELETE SET NULL,
  UNIQUE KEY `uk_tag_parent_name` (`parent_id`, `name`) -- same name allowed under different parents
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed two roots (optional but recommended)
INSERT INTO `tag` (`name`, `parent_id`) VALUES ('organization', NULL), ('service', NULL);

-- --------------------------------------------------------
-- organization (references tag)
-- --------------------------------------------------------
CREATE TABLE `organization` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `tag_id` INT NOT NULL,                        -- child of 'organization' root
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,           -- NULL = private
  `revoked_at` DATETIME DEFAULT NULL,           -- Soft-delete timestamp
  CONSTRAINT `fk_organization_tag`
    FOREIGN KEY (`tag_id`) REFERENCES `tag`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- service (references tag)
-- --------------------------------------------------------
CREATE TABLE `service` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `tag_id` INT DEFAULT NULL,                    -- child of 'service' root
  `sort_order` INT DEFAULT 0,                   -- Manual display order (0 = first)
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,           -- NULL = not featured
  `revoked_at` DATETIME DEFAULT NULL,           -- Soft-delete timestamp

  CONSTRAINT `fk_service_tag` FOREIGN KEY (`tag_id`) REFERENCES `tag`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Helpful indexes
CREATE INDEX `ix_tag_parent`     ON `tag`(`parent_id`);
CREATE INDEX `ix_org_tag`        ON `organization`(`tag_id`);
CREATE INDEX `ix_service_tag`    ON `service`(`tag_id`);
